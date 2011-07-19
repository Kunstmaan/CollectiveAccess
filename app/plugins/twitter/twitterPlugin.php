<?php
/* ----------------------------------------------------------------------
 * twitterPlugin.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010 Whirl-i-Gig
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
 * ----------------------------------------------------------------------
 */
 	require_once(__CA_MODELS_DIR__.'/ca_lists.php');
 	require_once(__CA_LIB_DIR__.'/core/Logging/Eventlog.php');
 	require_once(__CA_LIB_DIR__.'/core/Zend/Service/Twitter.php');
 

	
	class twitterPlugin extends BaseApplicationPlugin {
		# -------------------------------------------------------
		private $opo_config;
		# -------------------------------------------------------
		public function __construct($ps_plugin_path) {
			$this->description = _t('Publishes newly published records to twitter.');
			parent::__construct();
			
			$this->opo_config = Configuration::load($ps_plugin_path.'/conf/twitter.conf');
		}
		# -------------------------------------------------------
		/**
		 * Override checkStatus() to return true - the twitterPlugin plugin always initializes ok
		 */
		public function checkStatus() {
			return array(
				'description' => $this->getDescription(),
				'errors' => array(),
				'warnings' => array(),
				'available' => ((bool)$this->opo_config->get('enabled'))
			);
		}
		# -------------------------------------------------------
		/**
		 * Insert Twitter configuration option into "manage" menu
		 */
		public function hookRenderMenuBar($pa_menu_bar) {
			if ($o_req = $this->getRequest()) {
				//if (!$o_req->user->canDoAction('can_use_media_import_plugin')) { return null; }
				
				if (isset($pa_menu_bar['manage'])) {
					$va_menu_items = $pa_menu_bar['manage']['navigation'];
					if (!is_array($va_menu_items)) { $va_menu_items = array(); }
				} else {
					$va_menu_items = array();
				}
				$va_menu_items['twitter_auth'] = array(
					'displayName' => _t('Twitter integration'),
					"default" => array(
						'module' => 'twitter', 
						'controller' => 'Auth', 
						'action' => 'Index'
					)
				);
				
				$pa_menu_bar['manage']['navigation'] = $va_menu_items;
			} 
			return $pa_menu_bar;
		}
		# -------------------------------------------------------
		/**
		 * Tweet on save of item
		 */
		public function hookSaveItem(&$pa_params) {
			if ($pa_params['table_name'] == 'ca_objects') {	// only handle objects
				$t_object = $pa_params['instance'];	// get ca_objects instance from params
				include('bitly.php');
				$bitly = new bitly('collectiveaccess', 'R_8a0ecd6ea746c58f787d6769329b9976');
				
				// Get Twitter token. If it doesn't exist silently skip posting.
				if ($o_token = @unserialize(file_get_contents(__CA_APP_DIR__.'/tmp/twitter.token'))) { 
					
					try {
						$o_twitter = new Zend_Service_Twitter(array(
							'consumerKey' => $this->opo_config->get('consumer_key'),
							'consumerSecret' => $this->opo_config->get('consumer_secret'),
							'username' => $this->opo_config->get('twitter_username'),
							'accessToken' => $o_token
						));
						 
						// Post to Twitter
						// for now we just post the title of the object. We need to do something 
						// more interesting and configurable, of course.
						$vn_object_id = $t_object->getPrimaryKey();
						$vs_object_url = $this->opo_config->get('public_url');
						$vs_object_url = $vs_object_url.$vn_object_id;
						$o_response = $o_twitter->status->update('Just updated '.$t_object->getLabelForDisplay().' '.$bitly->shorten($vs_object_url));
					} catch (Exception $e) {
						// Don't post error to user - Twitter failing is not a critical error
						// But let's put it in the event log so you have some chance of knowing what's going on
						//print "Post to Twitter failed: ".$e->getMessage();
						$o_log = new Eventlog();
						$o_log->log(array(
							'SOURCE' => 'twitter plugin',
							'MESSAGE' => _t('Post to Twitter failed: %1', $e->getMessage()),
							'CODE' => 'ERR')
						);
					}
				}
			}
			return $pa_params;
		}
		# -------------------------------------------------------


	}

?>