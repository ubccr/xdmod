<?php

	header('Content-type: application/x-javascript');
	@require_once '../../../configuration/linker.php';
	
	$tech_support_recipient = xd_utilities\getConfiguration('general', 'tech_support_recipient');
		
?>

var active_panel = 'panel_login';

var divTag = document.createElement("div");
    divTag.id = 'login_status';
    divTag.className ='login_status';

var messageType = {"PROCESS" : 0, "ERROR" : 1, "SUCCESS" : 2}; 

var login_failed_attempts = 0;
var login_failed_limit = 3;

// ------------------------------------ 

function processEmailRequest() {

	var email_address = jQuery.trim(document.webapp_passreset.tg_email.value).replace(/\s+/g, " ");
       
	if (email_address.length == 0){
		presentMessage('E-mail address required', messageType.ERROR, 'mailer'); 
		return;
	}

	$.post(
	
		"controllers/user_auth.php", 
		{"operation": "pass_reset", "email" : email_address},
    
    	function(data){
			
			var json = eval('(' + data + ')');
			
			if (json.status == "success")
				presentMessage('An e-mail has been sent.', messageType.SUCCESS, 'mailer');
			else if (json.status == 'invalid_email_address')
				presentMessage('Invalid e-mail address specified', messageType.ERROR, 'mailer');
			else if (json.status == 'no_user_mapping')
				presentMessage('No user exists with this e-mail address', messageType.ERROR, 'mailer');
			else if (json.status == 'multiple_accounts_mapped')
				presentMessage('Contact <a href="mailto:<?php print $tech_support_recipient; ?>">tech support</a>', messageType.ERROR, 'mailer');
			else
				presentMessage(json.status, messageType.ERROR, 'mailer');
			
		}
		
	);

}//processEmailRequest

// ------------------------------------ 

function transitionTo(target) {

	$('#' + active_panel).fadeOut('slow', function() {
		active_panel = target;
		$('#' + target).fadeIn('slow', function() {
			
			if (target == 'panel_login'){
			
				if($('#tg_user').val().length == 0) 
					$('#tg_user').focus();
				else
					$('#tg_pass').focus();
			
			}
				
			if (target == 'panel_emailpass')
				$('#tg_email').focus();
			
		});
	});
	
}//transitionTo

// ------------------------------------ 

function keypressed(event, obj) {
	if(event.keyCode == '13') {
		if (obj.id == 'tg_user') document.getElementById('tg_pass').focus();
		if (obj.id == 'tg_pass') document.getElementById('btn_login').click();
		if (obj.id == 'tg_email') document.getElementById('btn_send_mail').click();
	}
}
 
// ------------------------------------ 

function checkFields() {

	var tg_user = jQuery.trim(document.webapp_login.tg_user.value);
	var tg_pass = jQuery.trim(document.webapp_login.tg_pass.value);

	if (tg_user.length == 0){ presentMessage('Username required', messageType.ERROR); return; }
	if (tg_pass.length == 0){ presentMessage('Password required', messageType.ERROR); return; }

	preAuthorize(tg_user, tg_pass);

}//checkFields

// ------------------------------------

function preAuthorize(user, pass) {
	
	$.post("controllers/user_auth.php", { "operation": "login", "username" : user, "password" : pass },
		function(data){

			var json = eval('(' + data + ')');
			
			if (json.status == "success"){
				
				if (json.account_is_active == "true") {
					presentMessage('Welcome, ' + json.first_name, messageType.SUCCESS);
					setTimeout('invokeRedirect()', 2000);
				}
				else{
					presentMessage('This account has been disabled.', messageType.ERROR);
				}
				
			}
			else
				presentMessage('Login Failed', messageType.ERROR);

		}
	);

}//preAuthorize

// ------------------------------------

function invokeRedirect() {
	window.location = location.href.split('#')[0];
}//invokeRedirect

// ------------------------------------

function presentMessage(message, type, target) {

	if (type == messageType.SUCCESS) divTag.className = (target == null) ? 'login_check_success' : 'email_check_success';
	if (type == messageType.ERROR) divTag.className = (target == null) ? 'login_check_fail' : 'email_check_fail';

	if (message == 'Login Failed') login_failed_attempts++;		
	
	divTag.innerHTML = message;

	if (target == null)
		$('.login .footer').append(divTag);
	else
		$('.' + target + ' .control_layer').append(divTag);

	$('#login_status').fadeTo('slow', 1.0).delay(1500);
	$('#login_status').fadeTo('slow', 0.0, function(){ restoreState(target); });

}//presentMessage

// ------------------------------------ 

function restoreState(target) {
	
	if (target == null){
	
		$('#tg_user').removeAttr('disabled');
		$('#tg_pass').removeAttr('disabled');
		$('#tg_user').focus();
		
		if(divTag.innerHTML == 'Password required' || divTag.innerHTML == 'Login Failed')
			$('#tg_pass').focus();
		
		$('#btn_login').fadeIn('slow', null);
	
		if (login_failed_attempts == login_failed_limit){
			login_failed_attempts = 0;
			transitionTo('panel_emailpass');
		}
		
	}
	else {
		$('.mailer .control_layer #login_status').remove();
		$('#tg_email').removeAttr('disabled');
		$('#tg_email').blur();
		$('#tg_email').focus();
		$('#btn_send_mail').fadeIn('slow', null);
	}

}//restoreState
