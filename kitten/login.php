<?php

/*  Collector (Garcia, Kornell, Kerr, Blake & Haffey)
    A program for running experiments on the web
    Copyright 2012-2016 Mikey Garcia & Nate Kornell


    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License version 3 as published by
    the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>
 
		Kitten release (2019) author: Dr. Anthony Haffey (a.haffey@reading.ac.uk)
*/

require 		 "Code/initiateCollector.php";
require_once "../../sqlConnect.php";


/* ---------------------- SECURITY CHECKS HERE!!! ---------------------- */


 
$user_email    = isset($_POST['user_email'])    ? $_POST['user_email']    : '';
$user_password = isset($_POST['user_password']) ? $_POST['user_password'] : '';
$return_page 	 = isset($_POST['return_page']) 	? $_POST['return_page']   : '';

if($_POST["login_type"]=="logout"){
	
    unset($_SESSION['user_email']);	
    header("Location:$return_page");	
}
function create_random_code($length){
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);        
    $new_code= '';
    for ($i = 0; $i < $length; $i++) {
        $new_code .= $characters[rand(0, $charactersLength - 1)];
    }
    return $new_code;
}



if($_POST["login_type"]=="register"){
    $_SESSION['login_error'] = 'hi there';
    $sql="SELECT * FROM users WHERE email='$user_email'"; // "WHERE email='".$user_email."' LIMIT 1;
    $result = $conn->query($sql);
    
    if($result->num_rows>0){
        $_SESSION['login_error'] = "user already exists";
        header("Location:$return_page");
    } else {
        // create random string as confirm code
        $hashed_password = password_hash($user_password, PASSWORD_BCRYPT);
				$salt = create_random_code(20);
				$pepper = create_random_code(20);	
        $email_confirm_code = create_random_code(20);

        $sql = "INSERT INTO `users` (`email`, `password`, `email_confirm_code`, `salt`,`pepper`,`account_status`) VALUES('$user_email', '$hashed_password', '$email_confirm_code','$salt','$pepper','u')";
        if ($conn->query($sql) === TRUE) {			
				
			$success_fail = "fail";  //not really, but need to confirm with e-mail code first
			$_SESSION['login_error'] = "Please check the e-mail address you registered with, and confirm. You cannot log in until you have done so."; 
			
			$msg = "Dear $user_email \n \nThank you for registering with Open-Collector. Before you can use your new profile, we need to confirm this is a valid address. Please proceed to the following link to confirm: \n www.ocollector.org/".$_SESSION['version']."/confirm.php?email=$user_email&confirm_code=$email_confirm_code&page=$return_page'\nMany thanks, \nThe Open-Collector team";

			// use wordwrap() if lines are longer than 70 characters
			$msg = wordwrap($msg,70);

			// send email
			mail($user_email,"Confirmation code for Registering with Open Collector",$msg);
			
						
        } else {
            $success_fail = "fail";
            $_SESSION['login_error'] = "Error adding user: $result " . $conn->error;
            header("Location:$return_page");
        }
    }
}

function encrypt_decrypt($action, $string,$local_key,$this_iv) {
    $output = false;
    $encrypt_method = "AES-256-CBC";
    $secret_key = $local_key;
    $secret_iv = $this_iv;
    // hash
    $key = hash('sha256', $secret_key);
    
    // iv - encrypt method AES-256-CBC expects 16 bytes - else you will get a warning
    $iv = substr(hash('sha256', $secret_iv), 0, 16);
    if ( $action == 'encrypt' ) {
        $output = openssl_encrypt($string, $encrypt_method, $key, 0, $iv);
        $output = base64_encode($output);
    } else if( $action == 'decrypt' ) {
        $output = openssl_decrypt(base64_decode($string), $encrypt_method, $key, 0, $iv);
    }
    return $output;
}


// is the user logging in
if($_POST["login_type"]=="forgot"){    
    $sql="SELECT * FROM users WHERE email='$user_email'";     
    $result = $conn->query($sql);
    if($result->num_rows == 0){
        $success_fail = "fail"; 
        $_SESSION['login_error'] = "This account is not registered - please double check that you typed it in correctly.";
    } else {
        $success_fail = "success";
        $email_confirm_code = create_random_code(20);
        $sql = "UPDATE `users` SET `email_confirm_code`  = '$email_confirm_code' WHERE `email` = '$user_email'";
        if ($conn->query($sql) === TRUE) {
            $success_fail = "fail";  //not really, but need to confirm with e-mail code first
            $_SESSION['login_error'] = "You have just been given an e-mail to reset your password. Please click on the link included.";            
        } else {
            $success_fail = "fail";
            $_SESSION['login_error'] = "Error adding user: $result " . $conn->error;
            header("Location:$return_page");
        }
        $msg = "Dear $user_email \n \nThere has been a request to reset the password for your account. Please go to the following link to set your new password: \n www.ocollector.org/UpdatePassword.php?email=$user_email&confirm_code=$email_confirm_code \nMany thanks, \nThe Open-Collector team";

        $msg = wordwrap($msg,70); // use wordwrap() if lines are longer than 70 characters        
        mail($user_email,"Resetting password with Open-Collector",$msg); // send email
    }
}

if($_POST["login_type"]=="login"){		
	$sql = "SELECT * FROM users WHERE email='$user_email'";    
	$result = $conn->query($sql);	
	
	if($result->num_rows > 1){
		$success_fail = "fail";
		$_SESSION['login_error'] = "Please contact a.haffey@reading.ac.uk -  there are multiple instances of this e-mail address registered.";
	} else if($result->num_rows == 1){
		$row = mysqli_fetch_array($result);	
		
		if($row['account_status'] == 'V'){			
	
			if (password_verify($user_password, $row['password'])) {
				$cipher = "aes-128-cbc";//"aes-256-gcm";	//do not use CBC ciphers				
				$_SESSION['user_email'] = "$user_email";
				
				//create public and private keys if they don't yet exist.
				
				if(!file_exists("../../simplekeys/public_$user_email.txt")){
					$config = array(
						"digest_alg" 		=> "sha512",
						"private_key_bits" 	=> 4096,
						"private_key_type" 	=> OPENSSL_KEYTYPE_RSA,
					);
					$res = openssl_pkey_new($config);
					openssl_pkey_export($res, $privKey); 		// Get private key
					$pubKey = openssl_pkey_get_details($res); 	// Get public key
					$pubKey = $pubKey["key"];
						
					array_push($public_key_owners,$user_email);            
					
					$saltpepper = create_random_code(40);
					$salt 			= substr($saltpepper,0,20);
					$pepper 		= substr($saltpepper,21,40);
					
					$hashed_password_key =  hash('sha512', $salt.$user_password.$pepper);
					
					//encrypt private key with password and salt and peeper...?					
					$cipher = "aes-256-cbc";
					//$encrypted_privKey = openssl_encrypt ($privkey, $cipher, hex2bin($hashed_password_key), OPENSSL_RAW_DATA);
					
					$this_iv			 =  openssl_random_pseudo_bytes(16);										
					
					$encrypted_privKey = encrypt_decrypt("encrypt", $privKey,$hashed_password_key,$this_iv);
					
					file_put_contents("../../simplekeys/iv-$user_email.txt",$this_iv);
					file_put_contents("../../simplekeys/local_key.txt",$hashed_password_key);
					file_put_contents("../../simplekeys/local_key.txt",$hashed_password_key);
					file_put_contents("../../simplekeys/priv_$user_email.txt",$encrypted_privKey);
					file_put_contents("../../simplekeys/public_$user_email.txt",$pubKey);
					file_put_contents("../../simplekeys/saltpepper_$user_email.txt",$saltpepper);
					
					
				} else { 
					//need to retrieve salt and pepper				
					$saltpepper 					= file_get_contents("../../simplekeys/saltpepper_$user_email.txt");
					$salt 								= substr($saltpepper,0,20);
					$pepper 							= substr($saltpepper,21,40);
					$hashed_password_key 	= hash('sha512', $salt.$user_password.$pepper);
				}
?>
				<script>
					window.localStorage.setItem("local_key", "<?= $hashed_password_key ?>");
					document.location.href = "<?= $return_page ?>";
				</script>
<?php
			} else {			
				
				$_SESSION['login_error'] = 'Invalid e-mail address and/or password.';
			}			
		} else {
			$success_fail = "fail";
			$_SESSION['login_error'] = "This account has been locked out. Please check your e-mails for a code to log you back in.";
		}		
	} else {
		$success_fail = "fail";
		$_SESSION['login_error'] = 'Invalid e-mail address and/or password.';
	}	
}

if($_POST["login_type"]=="guest"){
    $_SESSION['user_email'] = "guest";
    header("Location:$return_page");
}


if(isset($success_fail) && $success_fail == "success"){	
    $_SESSION['user_email'] = $user_email;
    header("Location:$return_page");
} else {		
	if(isset($return_page) && $return_page !== ""){		
		header("Location:$return_page");
	} else {		
		header("Location:index.php");
	}
} 

?>
