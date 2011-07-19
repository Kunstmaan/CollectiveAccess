<?php
/* ----------------------------------------------------------------------
 * app/controllers/lookup/TaxonomyController.php :
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
 	require_once(__CA_APP_DIR__."/helpers/displayHelpers.php");
	require_once(__CA_LIB_DIR__."/core/Configuration.php");
 	
 
 	class TaxonomyController extends ActionController {
 		# -------------------------------------------------------
 		public function __construct(&$po_request, &$po_response, $pa_view_paths=null) {
 			parent::__construct($po_request, $po_response, $pa_view_paths);
 		}
 		# -------------------------------------------------------
 		# AJAX handlers
 		# -------------------------------------------------------
		public function Get() {
			//$tt = new Timer();
			$ps_query = $this->request->getParameter('q', pString);
			//file_put_contents("/tmp/times", "### QUERY: {$ps_query}\n", FILE_APPEND);
			$va_items = array();
			if (unicode_strlen($ps_query) >= 3) {
				try {
					$vo_ctx = stream_context_create(array(
						'http' => array(
							'timeout' => 5
						)
					));

					/* // ITIS
					$i = 0;
					$vo_doc = new DOMDocument();
					//$t = new Timer();
					$vs_result = @file_get_contents("http://www.itis.gov/ITISWebService/services/ITISService/searchForAnyMatch?srchKey={$ps_query}",0,$vo_ctx);
					//file_put_contents("/tmp/times", "ITIS: {$t->getTime(2)}\n", FILE_APPEND);
					if(strlen($vs_result)>0){
						$vo_doc->loadXML($vs_result);
						$vo_resultlist = $vo_doc->getElementsByTagName("anyMatchList");
						foreach($vo_resultlist as $vo_result){
							$vs_cn = $vs_sn = $vs_id = "";
							foreach($vo_result->childNodes as $vo_field){
								switch($vo_field->nodeName){
									case "ax23:commonNameList":
										foreach($vo_field->childNodes as $vo_cns){
											if($vo_cns->nodeName == "ax23:commonNames"){
												foreach($vo_cns->childNodes as $vo_cn){
													if($vo_cn->nodeName == "ax23:commonName"){
														$vs_cn = $vo_cn->textContent;
													}
												}
											}
										}
										break;
									case "ax23:tsn":
										$vs_id = $vo_field->textContent;
										break;
									case "ax23:sciName":
										$vs_sn = $vo_field->textContent;
										break;
									default:
										break;
								}
							}
							if(strlen($vs_id)>0){
								$va_items["itis".$vs_id] = array(
									"idno" => "ITIS:{$vs_id}",
									"common_name" => $vs_cn,
									"sci_name" => $vs_sn
								);
								if(++$i == 50){ // let's limit to 50 results, right?
									break;
								}
							}
						}
					} else {
						$va_items['error_itis'] = array(
							'msg' => _t('ERROR: ITIS web service query failed.'),
						);
					}*/

					// uBio
					$vo_conf = new Configuration();
					$vs_ubio_keycode = trim($vo_conf->get("ubio_keycode"));
					if(strlen($vs_ubio_keycode)>0){
						$vo_doc = new DOMDocument();
						//$t = new Timer();
						$vs_result = @file_get_contents("http://www.ubio.org/webservices/service.php?function=namebank_search&searchName={$ps_query}&sci=1&vern=1&keyCode={$vs_ubio_keycode}",0,$vo_ctx);
						//file_put_contents("/tmp/times", "uBIO: {$t->getTime(2)}\n", FILE_APPEND);
						if(strlen($vs_result)>0){
							$vo_doc->loadXML($vs_result);
							$vo_resultlist = $vo_doc->getElementsByTagName("value");
							$i = 0;
							foreach($vo_resultlist as $vo_result){
								$vs_name = $vs_id = $vs_package = $vs_cn = "";
								if($vo_result->parentNode->nodeName == "scientificNames"){
									foreach($vo_result->childNodes as $vo_field){
										switch($vo_field->nodeName){
											case "nameString":
												$vs_name = base64_decode($vo_field->textContent);
												break;
											case "namebankID":
												$vs_id = $vo_field->textContent;
												break;
											case "packageName":
												$vs_package = $vo_field->textContent;
												break;
											default:
												break;
										}
									}
								} elseif($vo_result->parentNode->nodeName == "vernacularNames"){
									foreach($vo_result->childNodes as $vo_field){
										switch($vo_field->nodeName){
											case "fullNameStringLink":
												$vs_name = base64_decode($vo_field->textContent);
												break;
											case "namebankIDLink":
												$vs_id = $vo_field->textContent;
												break;
											case "packageName":
												$vs_package = $vo_field->textContent;
												break;
											case "nameString":
												$vs_cn = base64_decode($vo_field->textContent);
												break;
											default:
												break;
										}
									}
								}
								if(strlen($vs_name)>0 && strlen($vs_id)>0){
									$va_items["ubio".$vs_id] = array(
										"idno" => "uBio:{$vs_id}",
										"sci_name" => $vs_name.(strlen($vs_package)>0 ? " ({$vs_package}) " : ""),
										"common_name" => $vs_cn
									);
									if(++$i == 100){ // let's limit to 100 results, right?
										break;
									}
								}
							}
						} else {
							$va_items['error_ubio'] = array(
								'msg' => _t('ERROR: uBio web service query failed.'),
							);
						}
					} else {
						$va_items['error_ubio'] = array(
							'msg' => _t('ERROR: No uBio keycode in app.conf.'),
						);
					}
				} catch (Exception $e) {
					$va_items['error'] = array(
						"msg" => _t('ERROR').':'.$e->getMessage(),
					);
				}
			}
			
			$this->view->setVar('taxonomy_list', $va_items);
			//file_put_contents("/tmp/times", "TOTAL: {$tt->getTime(2)}\n", FILE_APPEND);
 			return $this->render('ajax_taxonomy_list_html.php');
		}
		# -------------------------------------------------------
 	}
 ?>