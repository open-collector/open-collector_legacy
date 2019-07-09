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
require_once ("cleanRequests.php");

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$cipher = "aes-256-cbc";
define('AES_256_CBC', 'aes-256-cbc');


require_once "Code/initiateCollector.php";
?>
<head>
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
	<link rel="shortcut icon" type="image/x-icon" href="../logos/collector.ico.png" />
</head>
<script>	
  // Opera 8.0+
  var isOpera = (!!window.opr && !!opr.addons) || !!window.opera || navigator.userAgent.indexOf(' OPR/') >= 0;

  // Firefox 1.0+
  var isFirefox = typeof InstallTrigger !== 'undefined';

  // Safari 3.0+ "[object HTMLElementConstructor]" 
  var isSafari = /constructor/i.test(window.HTMLElement) || (function (p) { return p.toString() === "[object SafariRemoteNotification]"; })(!window['safari'] || (typeof safari !== 'undefined' && safari.pushNotification));

  // Internet Explorer 6-11
  var isIE = /*@cc_on!@*/false || !!document.documentMode;

  // Edge 20+
  var isEdge = !isIE && !!window.StyleMedia;

  // Chrome 1+
  var isChrome = !!window.chrome && !!window.chrome.webstore;

  // Blink engine detection
  var isBlink = (isChrome || isOpera) && !!window.CSS;

  if(isIE){
    alert("This website does not work reliably on Internet Explorer - Please use another browser, preferably Google Chrome.");
  }


	window.Papa 	|| document.write('<script src="../libraries/papaparse.4.3.6.min.js"><\/script>');	
	window.jQuery || document.write('<script src="../libraries/jquery-3.3.1.min.js"><\/script>');	
	window.Popper || document.write('<script src="../libraries/popper.min.js"><\/script>');	
	window.bootbox || document.write('<script src="../libraries/bootbox.4.4.0.min.js"><\/script>');	
	window.bootstrap || document.write('<link rel="stylesheet" href="../libraries/bootstrapCollector.css"><script src="../libraries/bootstrap.3.3.7.min.js"><\/script>');
  
</script>
<?php
require_once "../../sqlConnect.php";

// Is the simulator on?
///////////////////////

if(isset($simulator_on)){
	$exp_json	     = "'tbc'";
	$experiment_id = false;
	$iv  		       = false;
	$location      = "";  
	$published_id  = false;
	$condition 		 = "";	
} else {
	
	if(isset($_GET['name'])){
		$condition = $_GET['name'];
	} else {
		$condition = "";
	}
	$location	= $_GET['location'];	
	require("Welcome.php");
}

?>

<style>

#experiment_div{
	display:none;
	width  : 100%;
	margin : auto;	
}

.trial_iframe{
  visibility:hidden;
	overflow:hidden;
	width:0%;
	height:0%;
	margin-left: auto;
	margin-right: auto;		
}

#feedback_div{
	display:none;
}

#download_json{
	color:green;
}
#post_welcome{
	display:none;
}

</style>


<div id="post_welcome">
	<div class="progress" style="height: 1px;">
		<div id="experiment_progress" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width:0%"></div>
	</div>

	<div id="loading_div">Preparing encryption of data</div>
	<div id="stim_progress" class="progress">
		<div id="stim_listing" class="progress-bar progress-bar-striped progress-bar-animated bg-success" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width:0%"></div>
	</div>
	<div id="beginning_checks" style="display:none">
		External keyboards generally do not work. Please do not try to use an external keyboard.<br><br>
		Sometimes it may take a page a while to load. Please wait, and <b>DO NOT REFRESH THE PAGE</b>.<br><br>
		Please click "Understood" to show that you have understood these instructions. <br><br>			
		<input type="button" value="Understood" id="proc_button">
	</div>
	<div id = "calibrate_div"><?php 	
		if(!isset($simulator_on)){
			require("calibrate.html");			
			$simulator_on = "false";
		} else {
			$simulator_on = "true";
		}		
	?></div>
	<div id="experiment_div" scrolling="no"></div>
	<div id="pp_feedback"></div>

	<div id="feedback_div">
		<div class="form-group">
			<label for="formGroupExampleInput">Did you have any problems completing these tasks?</label>
			<input type="text" class="form-control" id="formGroupExampleInput" placeholder="Example input">
			<button type="button" class="btn btn-primary" id="complete_task">Submit</button>
		</div>		
	</div>
</div>
<div id="participant_country" style="display:none">
  <?php 
		if(isset($simulator_on) == FALSE){
			require ("ParticipantCountry.html");
		}		
	?>
</div>
<script>

exp_condition = "<?= $condition ?>";
exp_location  = "<?= $location ?>";
simulator_on  = "<?= $simulator_on ?>";

if(typeof(block_save) !== "undefined"){ //i.e. simulator
	$("#calibrate_div").hide();
	$("#loading_div").hide();
	$("#stim_progress").show();
	$("#progress").hide();
	//$("#experiment_div").show();
} else {
  $("#experiment_div").hide(); //until calibrate shows it
}
</script>

<script src="../<?= $_SESSION['version'] ?>/iframe_library.js"></script> 
<script src="../<?= $_SESSION['version'] ?>/TrialFunctions.js"></script>

<script>
function collectorPapaParsed(preparsed){
	
	post_parsed = Papa.parse(Papa.unparse(preparsed),{
		beforeFirstChunk: function(chunk) {
			var rows = chunk.split( /\r\n|\r|\n/ );
			var headings = rows[0].toLowerCase();
			rows[0] = headings;
			return rows.join("\r\n");
		},
		header:true,
		skipEmptyLines:true		
	}).data;
	
	return post_parsed;
}

// transform exp_json into readable csv
///////////////////////////////////////
function precrypted_data(decrypted_data,message){
	responses_csv = decrypted_data.responses;
	response_headers = [];
	responses_csv.forEach(function(row){
		Object.keys(row).forEach(function(item){
			if(response_headers.indexOf(item) == -1){
				response_headers.push(item);
			};
		});
	});
	this_condition    = decrypted_data.this_condition;
  
  condition_headers = Object.keys(this_condition).filter(function(item){
    return item !== "_empty_";    
  });
  
	//condition_headers = Object.keys(this_condition).filter(item => item !== "_empty_");
	table_headers			= response_headers.concat(condition_headers);
	downloadable_csv = [table_headers];
	responses_csv.forEach(function(row,row_no){
		downloadable_csv.push([]);
		table_headers.forEach(function(item,item_no){
			if(typeof(row[item]) !== "undefined"){
				downloadable_csv[row_no+1][item_no] = row[item];				
			} else if (condition_headers.indexOf(item) !== -1){
				downloadable_csv[row_no+1][item_no] = this_condition[item];				
			} else {
				downloadable_csv[row_no+1][item_no] = "";				
			}
		});
	});
	
	bootbox.prompt({
		title:message,
		value:$("#participant_code").val()+".csv",
		callback:function(result){
			if(result !== null){
				save_csv(result,Papa.unparse(downloadable_csv));
			}
		}
	});
}
experiment_finished_and_emailed = false;
function final_trial(){
  $("#participant_country").show();
	$("#experiment_div").html("<h3 class='text-primary'>Please do not close this window until it has been confirmed that the researcher has been e-mailed your data (or you have downloaded the data yourself that you will e-mail the researcher). If you do not get a prompt to do this within 30 seconds, press CTRL-S and you should be able to directly download your data.</h3>");
	download_at_end = exp_json.this_condition.download_at_end;
	
	if(typeof(simulator_on) == "undefined" || simulator_on == "false"){
		$.post("emailData.php",{
			all_data   : JSON.stringify(exp_json),
		},function(returned_data){
			console.dir(returned_data);
			
			message_data = returned_data.split(" encrypted data = ");
      
      if(message_data.length == 1){
        //retrieve researcher e-mail address
        
        precrypted_data(exp_json,"Problem encrypting: <b>"+ message_data +"</b>, we'll try again every 10 seconds, but in case it fails, please download and e-mail this file. What do you want to save this file as? (you will get this message each time we fail to e-mail your data to the researcher)");
        
        setTimeout(function(){
          final_trial();  
        },10000);        
			} else {
        
				encrypted_data = message_data[1];
        
        if(typeof(exp_json.this_condition.end_message) !== "undefined" && exp_json.this_condition.end_message !== ""){
          $("#experiment_div").html("<h3 class='text-primary'>"+exp_json.this_condition.end_message+"</h3>");
        } else {
					$("#experiment_div").html("");
				}
        $("#experiment_div").append("<div id='download_div'></div>");
        
				if(download_at_end == "on"){
          $("#download_div").html("<h1 class='text-primary'>"+message_data[0]+" <br><br> You can download the encrypted version of your data <span id='encrypt_click' class='text-success'>here</span> <br><br>or an unencrypted version <span id='raw_click' class='text-success'>here</span></h1>");	
            
            $("#encrypt_click").on("click",function(){
              bootbox.prompt({
                title:"What do you want to save this file as?",
                value:$("#participant_code").val()+"_encrypted.txt",
                callback:function(result){
                  var blob = new Blob([encrypted_data], {type: 'text/csv'});
                  if(window.navigator.msSaveOrOpenBlob) {
                    window.navigator.msSaveBlob(blob, result);
                  }
                  else{
                    var elem = window.document.createElement('a');
                    elem.href = window.URL.createObjectURL(blob);
                    elem.download = result;        
                    document.body.appendChild(elem);
                    elem.click();        
                    document.body.removeChild(elem);
                  }
                }
              });
            });
            $("#raw_click").on("click",function(){
              precrypted_data(exp_json,"What do you want to save this file as?");
            });        
				} else if(download_at_end == "off") {
					// do nothing
				} else {
					bootbox.alert("It's unclear whether the researcher wants you to be able to download your data or not");					
				}
        experiment_finished_and_emailed = true;
			}
		});
		
		
		
	} else {
		$("#experiment_div").html("<h1>You have finished. You can download the data by clicking <b><span id='download_json'>here</span></b>.</h1>");
	}
	$("#download_json").on("click",function(){
		precrypted_data(exp_json,"What do you want to save this file as?");
	});
}
$("#complete_task").on("click",function(){
	alert("code missing for this");
});



///////////////
// Functions //
///////////////


// Pipeline  
/////////////

function initiate_experiment(){  
	select_condition();
	full_screen();
  create_exp_json_variables();
	parse_sheets("stimuli");	
	parse_sheets("procedure");
	create_exp_json_functions();	
	parse_current_proc();	
	shuffle_start_exp();  
	process_welcome();
}


function full_screen(){
  if(typeof(exp_json.this_condition.fullscreen) !== "undefined"){
    if(exp_json.this_condition.fullscreen == "on"){
      var elem = document.getElementById("experiment_div");
      requestFullScreen(elem);    
    }
  }
}


function clean_var(this_variable,default_value){
	if(typeof(this_variable) == "undefined"){
		this_variable = default_value;
	} else {
		this_variable = this_variable.toLowerCase();
	}
	return this_variable;
}

function clean_this_condition(this_cond){
	this_cond.download 				= clean_var(this_cond.download,"on");
	this_cond.participant_id 	= clean_var(this_cond.participant_id,"on");
	if(typeof(this_cond.buffer) == "undefined"){
		this_cond.buffer = 5;
	}
	return this_cond;
}

function process_welcome(){
  
	//detect if this is an experiment or simulator
	//////////////////////////////////////////////
	
  
	if(document.getElementById("loading_exp_json") !== null) {	
  
		// skip participant id? (and thus start_message)
		////////////////////////////////////////////////
		pp_id_setting = exp_json.this_condition.participant_id;

		if(pp_id_setting == "off"){																// put in a participant ID that is clearly not unique (e.g. "notUnique"). 
			$("#participant_code").val("notUnique");
			post_welcome("notUnique","skip"); 											//"skip" means that it will automatically accept non unique ids				
		} else if(pp_id_setting == "random"){
			var this_code = Math.random().toString(36).substr(2, 16)
			post_welcome(this_code,"random");
		} else if(pp_id_setting  == "on"){ 
			$("#loading_exp_json").fadeOut(500);
			$("#researcher_message").fadeIn(2000);
			$("#participant_id_div").show(1000);			
		}	else {
			bootbox.alert("It's not clear if the researcher wants you to give them a user id - please contact them before proceeding.");
		}
	
		// personalised intro message ?
		///////////////////////////////
	
		if(exp_json.this_condition.start_message !== ""){
			$("#researcher_message").html(exp_json.this_condition.start_message);
		} else {
			def_start_msg = "<h1 class='text-primary'> Collector</h1>"+
											"<br><br>" +
											"<h4>It's very important to read the following before starting!</h1>" +
											"<div class='text-danger'>If you complete multiple Collector experiments at the same time, your completion codes may be messed up. Please do not do this!</div>" +
											"<br><br>" + 
											"<div class='text-danger'>If you refresh the page, you will lose <b>ALL</b> your progress. </div>"+
											"<br><br>" +
											"<div class='text-danger'>If the experiment freezes, try pressing <b>CTRL-S</b> to save your data so far. </div>";
			$("#researcher_message").html(def_start_msg);			
		}		
	}
}

String.prototype.replaceAll = function(str1, str2, ignore){ //by qwerty at https://stackoverflow.com/questions/2116558/fastest-method-to-replace-all-instances-of-a-character-in-a-string
  return this.replace(new RegExp(str1.replace(/([\/\,\!\\\^\$\{\}\[\]\(\)\.\*\+\?\|\<\>\-\&])/g,"\\$&"),(ignore?"gi":"g")),(typeof(str2)=="string")?str2.replace(/\$/g,"$$$$"):str2);
}


// other function in alphabetical order 
/////////////////////////////////////////

function buffer_trials(){
	var this_buffer = exp_json.this_condition.buffer;
	var trial_no    = exp_json.trial_no;
  for(var index = trial_no; index < trial_no + this_buffer; index++){
		write_trial_iframe(index);			
	};
	if(trial_no >= exp_json.parsed_proc.length){
		$("#experiment_div").html("<h1>You have already completed this experiment</h1>");
	}
}

function cancelFullscreen(){
	if (document.cancelFullScreen) {
		document.cancelFullScreen();
	} else if (document.mozCancelFullScreen) {
		document.mozCancelFullScreen();
	} else if (document.webkitCancelFullScreen) {
		document.webkitCancelFullScreen();
	}
}

function create_exp_json_functions(){	
	
	// in alphabetical order 
	//////////////////////////
	
	exp_json.finish_trial = function(trial_no,post_no){
		$("#experiment_progress").css("width",(100 * exp_json.trial_no/(exp_json.parsed_proc.length-1))+"%");
		if(typeof(trial_no) !== "undefined"){
			
			//check whether the trial has moved on
			//if(trial_no !== exp_json.trial_no | post_no !== exp_json.post_no){			
			//	return false;
			//} 
		}
		
		trial_end_ms = (new Date()).getTime();
		trial_inputs = {};
		
		for(var i = 0; i < exp_json.inputs.length; i++){	
			if($("input[name='"+exp_json.inputs[i].name+"']:checked").length == 0){		
				trial_inputs[exp_json.inputs[i].name]=exp_json.inputs[i].value;
			} else {
				if(exp_json.inputs[i].checked){
					trial_inputs[exp_json.inputs[i].name]=exp_json.inputs[i].value;	
				}			
			}
		}
		
		this_proc = exp_json.parsed_proc[exp_json.trial_no];
		if(typeof(exp_json.parsed_stims[exp_json.stimuli][this_proc.item]) == "undefined"){
			this_stim = {};
		} else {
			this_stim = exp_json.parsed_stims[exp_json.stimuli][this_proc.item];
		}
		
    var objs = [exp_json.this_trial, trial_inputs, this_proc, this_stim],
    response_data =  objs.reduce(function (r, o) {
        Object.keys(o).forEach(function (k) {
            r[k] = o[k];
        });
        return r;
    }, {});

    response_data["post_"+exp_json.post_no+"_trial_end_ms"] = trial_end_ms;	

		response_data["post_"+exp_json.post_no+"_trial_end_date"] = new Date(parseInt(trial_end_ms, 10)).toString('MM/dd/yy HH:mm:ss');
		
		exp_json.this_trial = response_data;
		
		
		$("#trial"+exp_json.trial_no).contents().children().find("iframe").length;
		if($("#trial"+exp_json.trial_no).contents().children().find("iframe").length == exp_json.post_no + 1){		
			//$(".trial_iframe").hide();
			$("#trial"+exp_json.trial_no).remove(); // destroy iframe for previous trial. 
			exp_json.responses.push(exp_json.this_trial);
			if(exp_json.trial_no == exp_json.parsed_proc.length - 1){
				final_trial();
			} else {
				exp_json.this_trial = {};			
				exp_json.trial_no = parseFloat(exp_json.trial_no) +1;
				exp_json.post_no = 0;
				setTimeout(function(){
          var this_index = parseFloat(exp_json.trial_no) + parseFloat(exp_json.this_condition.buffer) - 1;
					write_trial_iframe(this_index);	
				});				
				exp_json.start_post();
			}            
		} else {
			exp_json.post_no++;
			var start_time = (new Date()).getTime();
			exp_json.this_trial["post_"+exp_json.post_no+"_trial_start_ms"]   = (new Date()).getTime();			
			exp_json.this_trial["post_"+exp_json.post_no+"_trial_start_date"] = new Date(parseInt(start_time, 10)).toString('MM/dd/yy HH:mm:ss');
			
			// and then hide previous post and show next post within trial. 
			$("#trial"+exp_json.trial_no).contents().children().find("iframe").hide();
			exp_json.start_post();		
		}
	};
	
	exp_json.generate_trial = function(trial_no,post_no){
    console.dir("trial_no");
    console.dir(trial_no);
		if(typeof(exp_json.parsed_proc[trial_no]) == "undefined"){
			return false;
		}

		post_no = post_no == 0 ? "" : "post "+post_no+" ";		
		this_proc 	   = exp_json.parsed_proc[trial_no];
		this_trialtype = exp_json.trialtypes[this_proc[post_no+"trial type"]];
		    
		//look through all variables and replace with the value
		
		this_trialtype =  "<scr" + "ipt> Trial = {}; Trial.trial_no = '"+trial_no+"'; Trial.post_no ='"+post_no+"' </scr" + "ipt>" + "<scr" + "ipt src = '../<?= $_SESSION['version'] ?>/TrialFunctions.js' ></scr" + "ipt>" + this_trialtype ; //; trial_script +
		
		
		this_trialtype = this_trialtype.replace("[trial_no]",trial_no);
		this_trialtype = this_trialtype.replace("[post_no]",post_no);
		
		if(this_proc["item"] !== "0"){
			
			if(typeof(this_proc["stimuli"]) !== "undefined" && this_proc["stimuli"] !== ""){
				this_stim = exp_json.parsed_stims[this_proc["stimuli"]][this_proc["item"]];
			} else {
        if(typeof(exp_json.this_condition) !== "undefined" && typeof(exp_json.this_condition.stimuli) !== "undefined"){
          
          
          
          
        } else {
          
          
          
          
        }
        this_stim = exp_json.parsed_stims[exp_json.this_condition.stimuli][this_proc["item"]];
 
			}
      variable_list = Object.keys(this_proc).concat(Object.keys(this_stim));	
			
		} else {
			variable_list = Object.keys(this_proc);	
		}
		variable_list = variable_list.filter(String);
		
		//list everything between {{ and }} and transform them into lowercase 
		split_trialtype = this_trialtype.split("{{");
		split_trialtype = split_trialtype.map(function(split_part){
			if(split_part.indexOf("}}") !== -1){
				more_split_part = split_part.split("}}");
				more_split_part[0] = more_split_part[0].toLowerCase();
				split_part = more_split_part.join("}}");					
			}
			return split_part;
		});
		this_trialtype = split_trialtype.join("{{");	
    
		variable_list.forEach(function(variable,this_index){
			if(typeof(this_proc[variable]) !== "undefined"){
				variable_val = this_proc[variable];
			} else if(typeof(this_stim) !== "undefined" && typeof(this_stim[variable]) !== "undefined"){
				variable_val = this_stim[variable];
			} else {
				if(typeof(this_stim) !== "undefined"){
          console.dir("Not sure whether this means there's a bug or not");
          //bootbox.alert("serious bug, please contact researcher about missing variable");          
        }
			}			
			this_trialtype = this_trialtype.replaceAll("{{"+variable+"}}",variable_val);
		});    
    this_trialtype = this_trialtype.replaceAll("www.dropbox","dl.dropbox"); // in case user forgets    
		return this_trialtype;
	}

	exp_json.start_post = function(){
    
		if(typeof(exp_json.responses[exp_json.trial_no]) == "undefined"){
			exp_json.responses[exp_json.trial_no] = {};
		}
		exp_json.this_trial["post_"+exp_json.post_no+"_trial_start_ms"] = (new Date()).getTime();		
		if($("#trial"+exp_json.trial_no).contents().children().length > 0){
			var this_post_iframe = $("#trial"+exp_json.trial_no).contents().children().find("iframe").filter(function(element){
 				return element == exp_json.post_no;			
			})[0];
			this_post_iframe.style["visibility"] = "visible";
			
			this_post_iframe.style["visibility"] = "visible";
			
			
			$("#trial"+exp_json.trial_no).css("display","inline-block");
			$("#trial"+exp_json.trial_no).css("width", "100%");
			$("#trial"+exp_json.trial_no).css("height", "100%");
			$("#trial"+exp_json.trial_no).css("visibility","visible");
			
      
      $("#trial"+exp_json.trial_no).contents().find("#post"+exp_json.post_no).contents().find("#zzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzz").focus(); //or anything that no-one would accidentally create.
			

      
			//detect if max time exists and start timer
			if(exp_json.post_no == 0){
				var post_val = "";
			} else {
				post_val = "post "+exp_json.post_no + " ";
			}
			if(typeof(exp_json.parsed_proc[exp_json.trial_no][post_val + "max time"]) == "undefined"){
				max_time = "user";
			} else {
				var max_time = exp_json.parsed_proc[exp_json.trial_no][post_val + "max time"];
			}
			if(max_time !== "" & max_time.toLowerCase() !== "user"){
				var this_trial_no = exp_json.trial_no;
				var this_post_no  = exp_json.post_no;
				setTimeout(function(){
					if(this_trial_no == exp_json.trial_no && this_post_no == exp_json.post_no){
						exp_json.finish_trial(this_trial_no,this_post_no);						
					}				
				},parseFloat(max_time));
			}

			//deal with any Trial.set_timer or Trial.time_elapsed functions here
			//var this_timeout = exp_json.time_outs.filter(row => row.trial_no == parseFloat(exp_json.trial_no));
			
      var this_timeout = exp_json.time_outs.filter(function(row){        
        return row.trial_no == parseFloat(exp_json.trial_no);
      });
      
			if(this_timeout.length !== 0){ //should have  && this_timeout.length == 1 - need to deal with when there are multiple
				console.dir("this_timeout");
				console.dir(this_timeout);
        this_timeout.forEach(function(spec_timeout){
          console.dir("spec_timeout");
          console.dir(spec_timeout);
          setTimeout(function(){
            spec_timeout.this_func();
          }, spec_timeout.duration);          
        });
        /*
        for(var i = 0; i < this_timeout.length; i++){
          setTimeout(function(){
            this_timeout[i].this_func();
          }, this_timeout[i].duration);          
        }
        */
			} else {
				//no timers on this trial?
			}
		}
	}
}

function create_exp_json_variables(){
	exp_json.this_trial = {};
	exp_json.parsed_stims = {};	
	exp_json.uninitiated_stims = [];
	exp_json.uninitiated_stims_sum = 0;
	exp_json.initiated_stims = 0;
	exp_json.time_outs = [];
	exp_json.inputs = [];
	exp_json.progress_bar_visible = true; //not doing anything at the moment	
	exp_json.trial_no = 0;        
	
	exp_json.post_no  = 0;
	exp_json.responses = [];
}

function parse_sheets(proc_stim){
	if(proc_stim == "procedure"){
		var parsed_name = "parsed_procs";
		var raw_name    = "all_procs";
	} else if (proc_stim == "stimuli"){
		var parsed_name = "parsed_stims";
		var raw_name    = "all_stims";
	} else {
		bootbox.alert("Problem with proc_stim input into function parse_proc_stim");
	}
	
	if(typeof(exp_json.parsed_procs) == "undefined"){
		exp_json.parsed_procs = {};
	}
	
  Object.keys(exp_json[raw_name]).forEach(function(this_sheet){
		exp_json[parsed_name][this_sheet] = collectorPapaParsed(exp_json[raw_name][this_sheet]);
		exp_json[parsed_name][this_sheet] = exp_json[parsed_name][this_sheet].filter(function(row){
      return Object.keys(row).every(function(x) { return row[x]===''||row[x]===null;}) === false;
    });		
		if(proc_stim == "stimuli"){
			exp_json[parsed_name][this_sheet] = [undefined,undefined].concat(exp_json[parsed_name][this_sheet]);
		}
	});
}

function parse_current_proc(){
  exp_json.procedure = exp_json.this_condition.procedure;
	exp_json.parsed_proc = collectorPapaParsed(exp_json.all_procs[exp_json.procedure]);
	exp_json.parsed_proc = exp_json.parsed_proc.filter( function(row){
    return Object.keys(row).every(function(x) { return row[x]===''||row[x]===null;}) === false;
  });
	proc_fill_items();
	proc_apply_repeats();
}

function proc_apply_repeats(){
	var this_proc = exp_json.parsed_proc;
	repeat_cols = ["weight","frequency","freq","repeat"];
	
	// warn researcher if multiple repeat columns
	///////////////////////////////////////////////
	
	var repeat_cols_pres = [];
	Object.keys(this_proc[0]).forEach(function(header){
		if(repeat_cols.indexOf(header) !== -1){
			repeat_cols_pres.push(header);
		}
	});
	
	if(repeat_cols_pres.length > 1){
		bootbox.alert("There are multiple columns that do the same thing, please only use one of them: " + repeat_cols_pres.join(" , ") + ". If you are a participant, please contact the researcher and tell them about this problem.");
	}
	
	// fill in repeats 
	////////////////////
	
	var filled_proc = [];
	for(var i = 0; i < this_proc.length; i++){
		var this_row = this_proc[i];
		
		this_row.repeat = typeof(this_row.weight)			!== "undefined" ? this_row.weight :
											typeof(this_row.frequency)	!== "undefined" ? this_row.frequency 	:
											typeof(this_row.freq)				!== "undefined" ? this_row.freq 			:
											typeof(this_row.repeat)			!== "undefined" ? this_row.repeat 		: "";
		
		if(typeof(this_row.repeat) !== "undefined" && this_row.repeat !== ""){
			for(var k = 0; k < this_row.repeat; k ++){
				filled_proc.push(this_row);
			}
		} else {
			filled_proc.push(this_row);	
		}		
	}
	exp_json.parsed_proc = filled_proc;
}

function proc_fill_items(){
	var this_proc   = exp_json.parsed_proc;
	var filled_proc = [];
	
	for(var j = 0; j < this_proc.length; j++){		
		var row = this_proc[j];
		if(row.item.indexOf(":") == -1 && row.item.indexOf(",") == -1){
			filled_proc.push(row);
		} else {			
			// split by commas
			///////////////////
			var items_array = row.item.split(",");
			var complete_items_array = [];
			items_array.forEach(function(item){
				if(item.indexOf(":") == -1){
					complete_items_array.push(item);
				} else {					
					// split by colons
					///////////////////
					item_start_end = item.split(":");
					if(item_start_end.length > 2){
						bootbox.alert("There is a problem with the procedure sheet - see the row in which the item column value is " +row.item + ", there is more than 1 ':' which is not allowed. If you are not the researcher, can you please send this message to them.");
					}
					var item_start = parseFloat(item_start_end[0]);
					var item_end   = parseFloat(item_start_end[1]) +1;
					for(var this_item = item_start ; this_item < item_end ; this_item++){
						complete_items_array.push(this_item);
					}
				}
			});
			
			// for each item, push a row 
			/////////////////////////////			
			complete_items_array.forEach(function(item){
				
				// check repetition column
				///////////////////////////				
				var this_row_with_this_item  = JSON.parse(JSON.stringify(row));				
				this_row_with_this_item.item = item;
				filled_proc.push(this_row_with_this_item);									
			});
		}
	};
	exp_json.parsed_proc = filled_proc;	
}

function requestFullScreen(element) {
    // Supports most browsers and their versions.
    var requestMethod = element.requestFullScreen || element.webkitRequestFullScreen || element.mozRequestFullScreen || element.msRequestFullScreen;

    if (requestMethod) { // Native full screen.
        requestMethod.call(element);
    } else if (typeof window.ActiveXObject !== "undefined") { // Older IE.
        var wscript = new ActiveXObject("WScript.Shell");
        if (wscript !== null) {
            wscript.SendKeys("{F11}");
        }
    }
}

function save_csv (filename, data) {
	var blob = new Blob([data], {type: 'text/csv'});
	if(window.navigator.msSaveOrOpenBlob) {
		window.navigator.msSaveBlob(blob, filename);
	}
	else{
		var elem = window.document.createElement('a');
		elem.href = window.URL.createObjectURL(blob);
		elem.download = filename;        
		document.body.appendChild(elem);
		elem.click();        
		document.body.removeChild(elem);
	}
}

function shuffleArray(array) { //by Laurens Holst on https://stackoverflow.com/questions/2450954/how-to-randomize-shuffle-a-javascript-array?utm_medium=organic&utm_source=google_rich_qa&utm_campaign=google_rich_qa
    for (let i = array.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [array[i], array[j]] = [array[j], array[i]]; // eslint-disable-line no-param-reassign
    }
}

function shuffle_start_exp(){
	
	if($("#shuffle_on_button").length == 0 || $("#shuffle_on_button").length == 1 && $("#shuffle_on_button").is(":hidden")){
		shuffle_array = {};
		exp_json.parsed_proc.forEach(function(row,index){
				var this_shuffle = row["shuffle 1"];
				if(typeof(shuffle_array[this_shuffle]) == "undefined"){
						shuffle_array[this_shuffle] = [index];
				} else {
						shuffle_array[this_shuffle].push(index);
				}
		});
		delete shuffle_array.off;
		Object.keys(shuffle_array).forEach(function(key){            
				shuffleArray(shuffle_array[key]);                        
		});
		//apply shuffle to exp_json.parsed_proc         
		new_proc = exp_json.parsed_proc.map(function(row,original_index){
				if(row["shuffle 1"] !== "off" & row["shuffle 1"] !== ""){
						this_shuffle = row["shuffle 1"];
						var this_pos = shuffle_array[this_shuffle].pop();
						return exp_json.parsed_proc[this_pos];
				}
				if(row["shuffle 1"] == "off"){
						return exp_json.parsed_proc[original_index];
				}            
		});
		exp_json.parsed_proc = new_proc;
		exp_json.responses 	 = [];
		
		
	}
	exp_json.wait_to_proc	= false;
	buffer_trials();
}

function write_trial_iframe(index){
  
	if(typeof(exp_json.parsed_proc[index]) == "undefined"){
		return null;
	}
	$("#experiment_div").append("<iframe class='trial_iframe' scrolling='no' frameBorder='0' id='trial"+index+"'></iframe>");
	this_proc = exp_json.parsed_proc[index];
	
	var post_trialtypes =  Object.keys(this_proc).filter(function(key) {
		return /trial type/.test(key);
	});	
	trial_events = post_trialtypes.filter(function(post_trialtype){
    return this_proc[post_trialtype] !== "";
  });
	trial_iframe_code = '';
	for(var i = 0; i < trial_events.length; i++){ // write an iframe with the required number of sub_iframes
		if(this_proc[trial_events[i]] !== ""){
			trial_iframe_code += "<iframe class='post_iframe' style='height:100%; width:100%' frameBorder='0' id='post"+i+"'></iframe>";
		}
	}	
	doc = document.getElementById('trial'+index).contentWindow.document;
	doc.open();
	doc.write(trial_iframe_code);
	doc.close();
		
	//CODE HERE TO DEAL WITH POST TRIAL TYPES
  for(var i = 0; i < trial_events.length; i++){
		
		var trial_content = exp_json.generate_trial(index,i); //exp_json.trial_no	
		
    trial_content += "<button style='opacity:0; filter: alpha(opacity=0)' id='zzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzz'></button>";
    
		doc = document.getElementById('trial'+index).contentWindow.document.getElementById('post'+i).contentWindow;
		doc.document.open();
		doc.document.write(libraries + trial_content); //libraries + trial_content
		doc.document.close();
    
    //autoscroll to top of iframe (in case the trial runs over)
    doc.scrollTo(0,0);

		var no_images = (trial_content.match(/<img/g) || []).length;
		exp_json.uninitiated_stims.push(no_images);
		exp_json.uninitiated_stims_sum = exp_json.uninitiated_stims.reduce(function(acc,val){return acc + val });
	
		if(typeof(stim_interval) == "undefined"){
			//need code here to deal with "buffering" when there are no images.
			stim_interval = setInterval(function(){
				exp_json.initiated_stims = 0;							
				for(var j = exp_json.trial_no; j < exp_json.trial_no + exp_json.this_condition.buffer; j++){
					if($("#trial"+j).contents().children().find("iframe").contents().children().find("img").prop("complete")){
					//if($("#trial"+j).contents().find('img').prop('complete') == true){
						exp_json.initiated_stims += $("#trial"+j).contents().children().find("iframe").contents().children().find("img").length;
					}					
				}
				var completion = 100-exp_json.initiated_stims/exp_json.uninitiated_stims_sum;
				$("#stim_listing").css("width",completion+"%");
				if(completion == 100 | exp_json.uninitiated_stims_sum == 0){
					clearInterval(stim_interval);
					$("#loading_div").hide();
					$("#stim_progress").fadeOut(1000);
					if($("#calibrate_div").is(':visible') == false){
						$("#experiment_div").fadeIn(500);						
					}
					if(exp_json.wait_to_proc){
						bootbox.alert("It looks like you have closed the window midway through an experiment. Please press OK when you are ready to resume the experiment!", function(){
							exp_json.start_post();
						});
					} else {
						exp_json.start_post();
					}
				}						
			},10);
		}
	}	
	
}


//////////////////////
// Start experiment //
//////////////////////

var exp_json = ""; //this will get updated 
if(exp_location !== ""){
	
	if(exp_location.indexOf("www.dropbox") !== -1){
		get_location = exp_location.replace("www.","dl.");
	} else {
		get_location = exp_location;
	}
	
  $.get(get_location,function(this_experiment){
    exp_json = JSON.parse(this_experiment);
    if(simulator_on == "false"){ //note this HAS TO BE IN QUOTES to pass properly from PhP to js.
      initiate_experiment();		
    }
  });  
}

function select_condition(){
	exp_json.conditions = collectorPapaParsed(exp_json.cond_array);
	
	// convert description to names
	//////////////////////////////
	var conditions = exp_json.conditions;
	conditions 		 = conditions.map(function(condition){
		condition 	 = clean_this_condition(condition);
		if(typeof(condition.name) == "undefined"){
			condition.name = condition.description;
		}
		return condition;
	});
	if(exp_condition == ""){			//check if multiple conditions
		
		// select only condition
		////////////////////////		
    
    var cond_names = conditions.map(cond => cond.name).filter(function(item){
      return item !== "";      
    });
    
		if(cond_names.length == 1){
			exp_json.this_condition = conditions.filter(function(cond){
        return cond.name == cond_names[0];
      })[0];
		} else {
			bootbox.alert("It's not clear which condition the researcher wants you to complete - please ask them to double check the link they gave you.");
		}
	} else {
		exp_json.this_condition = conditions.filter(function(cond){
      return cond.name == exp_condition;
    })[0];
	}
}

//////////////////////////////////////
// allow participant to save part way
//////////////////////////////////////

$(window).bind('keydown', function(event) {	
	if (event.ctrlKey || event.metaKey) {
		switch (String.fromCharCode(event.which).toLowerCase()) {
			case 's':
				if(simulator_on !== "true"){
					event.preventDefault();
					precrypted_data(exp_json,"What do you want to save this file as?");
				}
			break;
		}		
	}	
});


//prevent closing without warning
window.onbeforeunload = function() {
  if(experiment_finished_and_emailed == false){
    
    precrypted_data(exp_json,"It looks like you're trying to leave the experiment before you're finished (or at least before the data has been e-mailed to the researcher. Please choose a filename to save your data as and e-mail it to the researcher. It should appear in your downloads folder.");
    
    return "Please do not try to refresh - you will have to restart if you do so.";	    
  }
};

$("body").css("text-align","center");
$("body").css("margin","auto");

</script>