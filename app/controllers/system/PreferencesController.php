<?php
/* ----------------------------------------------------------------------
 * includes/PreferencesController.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008 Whirl-i-Gig
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
 
 require_once(__CA_MODELS_DIR__."/ca_users.php");
 
 	class PreferencesController extends ActionController {
 		# -------------------------------------------------------
		public function __construct(&$po_request, &$po_response, $pa_view_paths=null) {
 			parent::__construct($po_request, $po_response, $pa_view_paths);
 		}
 		# -------------------------------------------------------
 		public function EditUIPrefs() {
 			$this->view->setVar('t_user', $this->request->user);
 			$this->view->setVar('group', 'ui');
 			$this->render('preferences_html.php');
 		}
 		# -------------------------------------------------------
 		public function EditCataloguingPrefs() {
 			$this->view->setVar('t_user', $this->request->user);
 			$this->view->setVar('group', 'cataloguing');
 			$this->render('preferences_html.php');
 		}
 		# -------------------------------------------------------
 		public function EditUnitsPrefs() {
 			$this->view->setVar('t_user', $this->request->user);
 			$this->view->setVar('group', 'units');
 			$this->render('preferences_html.php');
 		}
 		# -------------------------------------------------------
 		public function EditMediaPrefs() {
 			$this->view->setVar('t_user', $this->request->user);
 			$this->view->setVar('group', 'media');
 			$this->render('preferences_html.php');
 		}
 		# -------------------------------------------------------
 		public function EditProfilePrefs() {
 			$this->view->setVar('t_user', $this->request->user);
 			$this->view->setVar('group', 'user_profile');
 			
 			foreach(array('fname', 'lname', 'email') as $vs_pref) {
 				if (!$this->request->user->getPreference($vs_pref)) {
 					$this->request->user->setPreference($vs_pref, $this->request->user->get($vs_pref));
 				}
 			}
 			
 			$this->render('preferences_html.php');
 		}
 		# -------------------------------------------------------
 		public function Save() {
 			
 			$vs_action = $this->request->getParameter('action', pString);
 		
 			switch($vs_action) {
 				case 'EditCataloguingPrefs':
 					$vs_group = 'cataloguing';
 					
 					foreach($this->request->user->getValidPreferences($vs_group) as $vs_pref) {
						$this->request->user->setPreference($vs_pref, $this->request->getParameter('pref_'.$vs_pref, pString));
					}
 					break;
 				case 'EditMediaPrefs':
 					$vs_group = 'media';
 					
 					foreach($this->request->user->getValidPreferences($vs_group) as $vs_pref) {
						$this->request->user->setPreference($vs_pref, $this->request->getParameter('pref_'.$vs_pref, pString));
					}
 					break;
 				case 'EditUnitsPrefs':
 					$vs_group = 'units';
 					
 					foreach($this->request->user->getValidPreferences($vs_group) as $vs_pref) {
						$this->request->user->setPreference($vs_pref, $this->request->getParameter('pref_'.$vs_pref, pString));
					}
 					break;
 				case 'EditProfilePrefs':
 					$vs_group = 'user_profile';
 					
 					foreach(array('fname', 'lname', 'email') as $vs_pref) {
						$this->request->user->setPreference($vs_pref, $this->request->getParameter('pref_'.$vs_pref, pString));
 						$this->request->user->set($vs_pref, $this->request->getParameter('pref_'.$vs_pref, pString));
 					}
 					break;
 				case 'EditUIPrefs':
 				default:
					$vs_group = 'ui';
 					$vs_action = 'EditUIPrefs';
 					
 					foreach($this->request->user->getValidPreferences($vs_group) as $vs_pref) {
						$this->request->user->setPreference($vs_pref, $vs_locale = $this->request->getParameter('pref_'.$vs_pref, pString));
						
						if (($vs_pref == 'ui_locale') && $vs_locale) {
							global $_, $g_ui_locale_id, $g_ui_locale, $_locale;
							
							// set UI locale for this request (causes UI language to change immediately - and in time - for this request)
							// if we didn't do this, you'd have to reload the page to see the locale change
							$this->request->user->setPreference('ui_locale', $vs_locale);
							
							$g_ui_locale_id = $this->request->user->getPreferredUILocaleID();			// get current UI locale as locale_id	 			(available as global)
							$g_ui_locale = $this->request->user->getPreferredUILocale();				// get current UI locale as locale string 			(available as global)
							
							if(!file_exists($vs_locale_path = __CA_APP_DIR__.'/locale/user/'.$g_ui_locale.'/messages.mo')) {
								$vs_locale_path = __CA_APP_DIR__.'/locale/'.$g_ui_locale.'/messages.mo';
							}
							$_ = new Zend_Translate('gettext',$vs_locale_path, $g_ui_locale);
							$_locale = new Zend_Locale($g_ui_locale);
							Zend_Registry::set('Zend_Locale', $_locale);
							
							// reload menu bar
							AppNavigation::clearMenuBarCache($this->request);
						}
						
						if ($vs_pref == 'ui_theme') {
							// set the view path to use the new theme; if we didn't set this here you'd have to reload the page to
							// see the theme change.
							$this->view->setViewPath($this->request->getViewsDirectoryPath().'/'.$this->request->getModulePath());
						}
					}
					
 					break;
 			}
 			
 			
 			$this->request->setAction($vs_action);
 			$this->view->setVar('group', $vs_group);
 			$this->notification->addNotification(_t("Saved preference settings"), __NOTIFICATION_TYPE_INFO__);	
 			$this->view->setVar('t_user', $this->request->user);
 			$this->render('preferences_html.php');
 		}
 		# -------------------------------------------------------
 	}
 ?>