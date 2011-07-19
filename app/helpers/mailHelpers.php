<?php
/** ---------------------------------------------------------------------
 * app/helpers/mailHelpers.php : e-mail utility functions
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2010 Whirl-i-Gig
 *
 * For more information visit http://www.CollectiveAccess.org
 *
 * This program is free software; you may redistribute it and/or modify it under
 * the terms of the provided license as published by Whirl-i-Gig
 *
 * CollectiveAccess is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTIES whatsoever, including any implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * This source code is free and modifiable under the terms of
 * GNU General Public License. (http://www.gnu.org/copyleft/gpl.html). See
 * the "license.txt" file for details, or visit the CollectiveAccess web site at
 * http://www.CollectiveAccess.org
 * 
 * @package CollectiveAccess
 * @subpackage utils
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 * 
 * ----------------------------------------------------------------------
 */
 	
  /**
   *
   */ 
   
 	require_once(__CA_LIB_DIR__.'/core/Configuration.php');
 	require_once(__CA_LIB_DIR__.'/core/Logging/Eventlog.php');
 	require_once(__CA_LIB_DIR__.'/core/Zend/Mail.php');
 	require_once(__CA_LIB_DIR__.'/core/Zend/Mail/Transport/Smtp.php');
	require_once(__CA_LIB_DIR__.'/core/Zend/Mail/Transport/Sendmail.php');
 	
 	/**
 	 * Sends mail using server settings specificed in app.conf/global.conf
 	 *
 	 * Parameters are:
 	 *
 	 * 	$pa_to: 	Email address(es) of message recipients. Can be a string containing a single email address or
 	 *				an associative array with keys set to multiple addresses and corresponding values optionally set to
 	 *				a human-readable recipient name.
 	 *	$pa_from:	The email address of the message sender. Can be a string containing a single email address or
 	 *				an associative array with keys set to multiple addresses and corresponding values optionally set to
 	 *				a human-readable sender name.
 	 *	$ps_subject:	The subject line of the message
 	 *	$ps_body_text:	The text of the message				(optional)
 	 *	$ps_html_text:	The HTML-format text of the message (optional)
 	 *
 	 * While both $ps_body_text and $ps_html_text are optional, at least one should be set and both can be set for a 
 	 * combination text and HTML email
 	 */
	function caSendmail($pa_to, $pa_from, $ps_subject, $ps_body_text, $ps_body_html='') {
		$o_config = Configuration::load();
		$o_log = new Eventlog();
		
		$va_smtp_config = array();
		if($o_config->get('smtp_auth')){
			$vs_smtp_auth = $o_config->get('smtp_auth');
		} else {
			$vs_smtp_auth = '';
		}
		if($o_config->get('smtp_username')){
			$vs_smtp_uname = $o_config->get('smtp_username');
			$vs_smtp_auth = 'login';
		} else {
			$vs_smtp_uname = '';
		}
		if($o_config->get('smtp_password')){
			$vs_smtp_pw = $o_config->get('smtp_password');
			$vs_smtp_auth = 'login';
		} else {
			$vs_smtp_pw = '';
		}
		$va_smtp_config = array(
			'username' => $vs_smtp_uname,
			'password' => $vs_smtp_pw
		);
		
		if($vs_smtp_auth){
			$va_smtp_config['auth'] = $vs_smtp_auth;
		}
		if($vs_ssl = $o_config->get('smtp_ssl')){
			$va_smtp_config['ssl'] = $vs_ssl;
		}
		if($vs_port = $o_config->get('smtp_port')){
			$va_smtp_config['port'] = $vs_port;
		}
		
		try {
			if($o_config->get('smtp_use_sendmail_transport')){
				$vo_tr = new Zend_Mail_Transport_Sendmail($o_config->get('smtp_server'), $va_smtp_config);
			} else {
				$vo_tr = new Zend_Mail_Transport_Smtp($o_config->get('smtp_server'), $va_smtp_config);
			}
			
			$o_mail = new Zend_Mail('UTF-8');
			
			if (is_array($pa_from)) {
				foreach($pa_from as $vs_from_email => $vs_from_name) {
					$o_mail->setFrom($vs_from_email, $vs_from_name);
				}
			} else {
				$o_mail->setFrom($pa_from);
			}
			
			if (!is_array($pa_to)) {
				$pa_to = array($pa_to => $pa_to);
			}
			
			foreach($pa_to as $vs_to_email => $vs_to_name) {
				$o_mail->addTo($vs_to_email, $vs_to_name);
			}
			
			$o_mail->setSubject($ps_subject);
			if ($ps_body_text) {
				$o_mail->setBodyText($ps_body_text);
			}
			if ($ps_body_html) {
				$o_mail->setBodyHtml($ps_body_html);
			}
			$o_mail->send($vo_tr);
			
			$o_log->log(array('CODE' => 'SYS', 'SOURCE' => 'Registration', 'MESSAGE' => _t('Registration confirmation email was sent to %1', join(';', array_keys($pa_to)))));
			return true;
		} catch (Exception $e) {
			$o_log->log(array('CODE' => 'ERR', 'SOURCE' => 'Registration',  'MESSAGE' => _t('Could not send registration confirmation email to %1: %2', join(';', array_keys($pa_to)), $e->getMessage())));
			return false;
		}
	}
	
	/**
	 * Verifies the $ps_address is a properly formatted email address
	 * by passing it through a regular expression pattern check and then
	 * verifying that the domain exists. This is not a foolproof check but
	 * will catch most data entry errors
	 */ 
	function caCheckEmailAddress($ps_address) {
		if (!caCheckEmailAddressRegex($ps_address)) { return false; }
		
		if (!function_exists('checkdnsrr')) { return true; }
		
		//list($vs_username, $vs_domain) = split('@', $ps_address);
		//if(!checkdnsrr($vs_domain,'MX')) {
			///return false;
		//}
		
		return true;
	}
	
	/**
	 * Verifies using a regular expression the $ps_address looks like a valid email address
	 * Returns true if $ps_address looks like an email address, false if it doesn't
	 */
	function caCheckEmailAddressRegex($ps_address) {
		if (!preg_match("/^([a-zA-Z0-9])+([a-zA-Z0-9\._-])*@([a-zA-Z0-9_-])+([a-zA-Z0-9\._-]+)+$/" , $ps_address)) {
			return false;
		}
		return true;
	}
?>