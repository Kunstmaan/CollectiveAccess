<?php
/* ----------------------------------------------------------------------
 * support/import/am_move_dutch/import_am_move.php : Import Dutch-language AM-MovE XML files
 * http://www.museuminzicht.be/public/musea_werk/thesaurus/index.cfm
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009 Whirl-i-Gig
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

/*
 * Step 1: Initialisation
 */
set_time_limit(36000);
require_once("../../../setup.php");

if (!file_exists('./am_move.xml')) {
	die("ERROR: you must place the am_move.xml data file in the same directory as this script.\n");
}

require_once(__CA_LIB_DIR__.'/core/Db.php');
require_once(__CA_MODELS_DIR__.'/ca_locales.php');
require_once(__CA_MODELS_DIR__.'/ca_lists.php');
require_once(__CA_MODELS_DIR__.'/ca_list_items.php');
require_once(__CA_MODELS_DIR__.'/ca_list_items_x_list_items.php');
require_once(__CA_MODELS_DIR__.'/ca_relationship_types.php');

$_ = new Zend_Translate('gettext', __CA_APP_DIR__.'/locale/nl_BE/messages.mo', 'nl_BE');

$t_locale = new ca_locales();
$pn_en_locale_id = $t_locale->loadLocaleByCode('en_US');

if (!$pn_nl_locale_id = $t_locale->loadLocaleByCode('nl_BE')) {
	$pn_nl_locale_id = $t_locale->loadLocaleByCode('nl_NL');
}

if (!$pn_nl_locale_id) {
	die("ERROR: You can only import the Dutch-language AAT into an installation configured to support the nl_NL (Dutch) or nl_BE (Flemish Belgium) locale. Add one of these locales to your system and try again.\n");
}

// Create vocabulary list record (if it doesn't exist already)
$t_list = new ca_lists();
if (!$t_list->load(array('list_code' => 'am_move_nl'))) {
	$t_list->setMode(ACCESS_WRITE);
	$t_list->set('list_code', 'am_move_nl');
	$t_list->set('is_system_list', 0);
	$t_list->set('is_hierarchical', 1);
	$t_list->set('use_as_vocabulary', 1);
	$t_list->insert();

	if ($t_list->numErrors()) {
		print "ERROR: couldn't create ca_list row for AAT: ".join('; ', $t_list->getErrors())."\n";
		die;
	}

	$t_list->addLabel(array('name' => 'AM-MovE'), $pn_en_locale_id, null, true);
}
$vn_list_id = $t_list->getPrimaryKey();

// Find out the previous terms
$o_db = new Db();
$o_config = Configuration::load();
$qr = "delete from  ca_list_items_x_list_items";
$qr_del_labels = $o_db->query($qr);
$qr = "delete from ca_list_item_labels WHERE item_id IN( select item_id from ca_list_items where list_id=$vn_list_id)";
$qr_del_labels = $o_db->query($qr);
$qr = "delete from ca_objects_x_vocabulary_terms WHERE item_id IN( select item_id from ca_list_items where list_id=$vn_list_id)";
$qr_voc_terms = $o_db->query($qr);
$qr = "delete from  ca_list_items where list_id=$vn_list_id";
$qr_del_items = $o_db->query($qr);

// get list item types (should be defined by base installation profile [base.profile])
// if your installation didn't use a profile inheriting from base.profile then you should make sure
// that a list with code='list_item_types' is defined and the following four item codes are defined.
// If these are not defined then the AAT will still import, but without any distinction between
// terms, facets and guide terms
$vn_list_item_type_concept = 					$t_list->getItemIDFromList('list_item_types', 'concept');
$vn_list_item_type_facet = 						$t_list->getItemIDFromList('list_item_types', 'facet');
$vn_list_item_type_guide_term = 				$t_list->getItemIDFromList('list_item_types', 'guide_term');
$vn_list_item_type_hierarchy_name = 			$t_list->getItemIDFromList('list_item_types', 'hierarchy_name');

$vn_list_item_type_object_name = 				$t_list->getItemIDFromList('list_item_types', 'object_name');
$vn_list_item_type_material =		 			$t_list->getItemIDFromList('list_item_types', 'material');
$vn_list_item_type_technique = 					$t_list->getItemIDFromList('list_item_types', 'technique');
$vn_list_item_type_keyword = 					$t_list->getItemIDFromList('list_item_types', 'keyword');
$vn_list_item_type_collection = 				$t_list->getItemIDFromList('list_item_types', 'collection');
$vn_list_item_type_subject = 					$t_list->getItemIDFromList('list_item_types', 'subject');

$vn_list_item_order_of_importance = array();
$vn_list_item_order_of_importance["objectnaam"] = 0;
$vn_list_item_order_of_importance["materiaal"] = 1;
$vn_list_item_order_of_importance["techniek"] = 2;
$vn_list_item_order_of_importance["trefwoord"] = 3;
$vn_list_item_order_of_importance["collectie"] = 4;
$vn_list_item_order_of_importance["onderwerp"] = 5;
$vn_list_item_order_of_importance["gidsterm"] = 6;

// get list item label types (should be defined by base installation profile [base.profile])
// if your installation didn't use a profile inheriting from base.profile then you should make sure
// that a list with code='list_item_label_types' is defined and the following two item codes are defined.
// If these are not defined then the AAT will still import
$vn_list_item_label_type_uf = 					$t_list->getItemIDFromList('list_item_label_types', 'uf'); // used for
$vn_list_item_label_type_alt = 					$t_list->getItemIDFromList('list_item_label_types', 'alt'); // alternative

// get list item-to-item relationship type (should be defined by base installation profile [base.profile])
// if your installation didn't use a profile inheriting from base.profile then you should make sure
// that a ca_list_items_x_list_items relationship type with code='related' is defined. Otherwise import of term-to-term
// relationships will fail.
$t_rel_types = new ca_relationship_types();
$vn_list_item_relation_type_id_related = 		$t_rel_types->getRelationshipTypeID('ca_list_items_x_list_items', 'related');

// create log file
$logFile = fopen("output.log", 'w') or die("can't open file");

// load voc_terms
$o_xml = new XMLReader();
$o_xml->open('am_move.xml');

/*
 * Step 2: Import
 */
print "READING AM-MovE TERMS...\n";
fwrite($logFile, "READING AM-MovE TERMS...\n");

$va_item_item_links = array();
$va_subject = array();
$va_records = array(); // added to store terms for finding relations
$va_unique_terms = array();
$va_term_ids = array();

$vn_last_message_length = 0;
$vn_term_count = 0;

// Read xml; node by node till end of file.
while($o_xml->read()) {

	switch($o_xml->name) {
		case 'scope_note':
			switch($o_xml->nodeType) {
				case XMLReader::ELEMENT:
					$o_xml->read();
					$va_subject['scope_note'] = $o_xml->value;
					break;
			}
			break;
			// ---------------------------
		case 'term.type':
			switch($o_xml->nodeType) {
				case XMLReader::ELEMENT:
					$value = $o_xml->getAttribute('value');
					if(isset($va_subject['term_type']) && $va_subject['term_type']) {
						if($vn_list_item_order_of_importance[$value] >= $vn_list_item_order_of_importance[$va_subject['term_type']]) {
							break;
						}
					}
					$va_subject['term_type'] = $value;
					break;
			}
			break;
			// ---------------------------
		case 'term.number':
			switch($o_xml->nodeType) {
				case XMLReader::ELEMENT:
					$o_xml->read();
					$va_subject['term_number'] = $o_xml->value;
					break;
			}
			break;
			// ---------------------------
		case 'term':
			switch($o_xml->nodeType) {
				case XMLReader::ELEMENT:
					$o_xml->read();
					$va_subject['term'] = $o_xml->value;
					break;
			}

			break;
			// ---------------------------
		case 'broader_term':
			switch($o_xml->nodeType) {
				case XMLReader::ELEMENT:
					$o_xml->read();
					$va_subject['broader_term'] = $o_xml->value;
					break;
			}
			break;
		case 'narrower_term':
			switch($o_xml->nodeType) {
				case XMLReader::ELEMENT:
					$o_xml->read();
					$va_subject['narrower_term'][] = $o_xml->value;
			}
			break;
			// ---------------------------
		case 'related_term':
			switch($o_xml->nodeType) {
				case XMLReader::ELEMENT:
					$o_xml->read();
					$va_subject['related_term'][] = $o_xml->value;
					break;
			}
			break;
			// ---------------------------
		case 'used_for':
			switch($o_xml->nodeType) {
				case XMLReader::ELEMENT:
					$o_xml->read();
					$va_subject['used_for'][] = $o_xml->value;
					break;
			}
			break;
			// ---------------------------
		case 'use':
			switch($o_xml->nodeType) {
				case XMLReader::ELEMENT:
					$o_xml->read();
					$va_subject['use'] = $o_xml->value;
					break;
			}
			break;
			// ---------------------------
		case 'Uniek_nummer':
			switch($o_xml->nodeType) {
				case XMLReader::ELEMENT:
					$o_xml->read();
					$va_subject['unique_number'] = $o_xml->value;
					break;
			}
			break;
			// ---------------------------
		case 'record':
			if ($o_xml->nodeType == XMLReader::END_ELEMENT) {
				$vs_term = strtolower($va_subject['term']);
				$vs_use_term = strtolower($va_subject['use']);
				$vs_broader_term = strtolower($va_subject['broader_term']);

				switch($va_subject['term_type']) {
					case 'objectnaam':
						$vn_type_id = $vn_list_item_type_object_name;
						$pb_is_enabled = true;
						break;
					case 'materiaal':
						$vn_type_id = $vn_list_item_type_material;
						$pb_is_enabled = true;
						break;
					case 'techniek':
						$vn_type_id = $vn_list_item_type_technique;
						$pb_is_enabled = true;
						break;
					case 'gidsterm':
						$vn_type_id = $vn_list_item_type_guide_term;
						$pb_is_enabled = false;
						break;
					case 'trefwoord':
						$vn_type_id = $vn_list_item_type_keyword;
						$pb_is_enabled = true;
						break;
					case 'collectie':
						$vn_type_id = $vn_list_item_type_collection;
						$pb_is_enabled = true;
						break;
					case 'onderwerp':
						$vn_type_id = $vn_list_item_type_subject;
						$pb_is_enabled = true;
						break;
					default:
						$vn_type_id = null;
						$pb_is_enabled = true;
						break;
				}

				$vn_term_count++;
				$vs_message = "\tIMPORTING #".($vn_term_count)."  ".$vs_term. " (". $va_subject['term_type'] .")";
				print "\n\n".$vs_message;
				fwrite($logFile, "\n\n".$vs_message);
				$b_add_term = 1;

				// Check if the current broader term is already added
				// + If term is not found, new term is added and its id is returned
				// + If term is found then its id is returned and used as parent id for the term
				// ------------------------------------------------------------------------------
				print "\n\t Processing broader term";
				fwrite($logFile, "\n\t Processing broader term");
				$key = array_search($vs_broader_term, $va_unique_terms);

				if($key<=1 && $vs_broader_term) {
						// check if it isn't a label for another term
						$result = $o_db->query("select item_id from ca_list_item_labels where name_singular = ? and is_preferred = 0 and locale_id = ?", $vs_broader_term, $pn_nl_locale_id);
						while($result->nextRow()) {
							$key = (int) $result->get('item_id', null);
							print "\n\t\t found a non-prefered label which equals ".$vs_broader_term." going to use the term with id ".$main_key." as broader term for ".$vs_term;
							fwrite($logFile, "\n\t\t found a non-prefered label which equals ".$vs_broader_term." going to use the term with id ".$main_key." as broader term for ".$vs_term);
							break;
						}
					}

				if($key<1 && $vs_broader_term) {
					print "\n\t\t adding broader term  ".$vs_broader_term;
					fwrite($logFile, "\n\t\t adding broader term  ".$vs_broader_term);
					if ($t_item = $t_list->addItem('', true, false, null, null, '', '', 4, 1)) {
						// add preferred labels
						if (!($t_item->addLabel(
								array('name_singular' => $vs_broader_term, 'name_plural' => $vs_broader_term, 'description' => ''),
								$pn_nl_locale_id, null, true
							))) {
							print "ERROR: Could not add Dutch preferred label to AM-MovE broader term ".$vs_broader_term.": ".join("; ", $t_item->getErrors())."\n";
							fwrite($logFile, "ERROR: Could not add Dutch preferred label to AM-MovE broader term ".$vs_broader_term.": ".join("; ", $t_item->getErrors())."\n");
						}
						$key =  $t_item->getPrimaryKey();
						$va_unique_terms[$key] = $vs_broader_term;
						$va_term_ids[$vs_broader_term] = $key;
					}
				}

				// USE term to process
				// --------------------
				print "\n\t Processing USE term";
				fwrite($logFile, "\n\t Processing USE term");
				if(strlen($vs_use_term)>1) {

					$main_key = array_search($vs_use_term, $va_unique_terms);

					if($main_key<=1) {
						// check if it isn't a label for another term
						$result = $o_db->query("select item_id from ca_list_item_labels where name_singular = ? and is_preferred = 0 and locale_id = ?", $vs_use_term, $pn_nl_locale_id);
						while($result->nextRow()) {
							$main_key = (int) $result->get('item_id', null);
							print "\n\t\t found a non-prefered label which equals ".$vs_use_term." going to add the label ".$vs_term." to the term with id ".$main_key;
							fwrite($logFile, "\n\t\t found a non-prefered label which equals ".$vs_use_term." going to add the label ".$vs_term." to the term with id ".$main_key);
							break;
						}
					}

					if($main_key>1) {
						// exists already, we only need to add the labels
						print "\n\t\t updating use term ".$vs_use_term." by adding labels for term ".$vs_term;
						fwrite($logFile, "\n\t\t updating use term ".$vs_use_term." by adding labels for term ".$vs_term);
						if(!$t_item->load($main_key)) {
							print "ERROR: could not load item for main key {".$main_key."} \n";
							fwrite($logFile, "ERROR: could not load item for main key {".$main_key."} \n");
							break;
						}
						// will be an array with as key the name of the term and value the id of the term
						$vs_existing_labels = array();
						$labels = $t_item->getLabels();
						// pop the item_id
						$labels = array_pop($labels);
						// pop the language_id
						$labels = array_pop($labels);
						if (is_array($labels)) {
							foreach($labels as $label) {
								$vs_existing_labels[$label['name_singular']] = $label['label_id'];
							}
						}
						if (($existing_label = $vs_existing_labels[$vs_term])) {
							// The label already exists.
							print "\n\t\t existing label found for the use term ".$vs_term." we can skip this one";
							fwrite($logFile, "\n\t\t existing label found for the use term ".$vs_term." we can skip this one");
						} else {
							print "\n\t\t no label found for the use term ".$vs_term." we need to create this";
							fwrite($logFile, "\n\t\t no label found for the use term ".$vs_term." we need to create this");
							if (!($t_item->addLabel(
									array('name_singular' => $vs_term, 'name_plural' => $vs_term,
									'description' => $va_subject['scope_note']), $pn_nl_locale_id, $vn_list_item_label_type_alt, false
								))) {
								print "ERROR: Could not add Dutch non-preferred label to AM-MovE term
									[".$va_subject['unique_number']."] ".$vs_np_label.": ".join("; ", $t_item->getErrors())."\n";
								fwrite($logFile, "ERROR: Could not add Dutch non-preferred label to AM-MovE term
									[".$va_subject['unique_number']."] ".$vs_np_label.": ".join("; ", $t_item->getErrors())."\n");
							}
						}
						break;
					} else {
						// create the use term, and add the labels for this term
						print "\n\t\t creating use term ".$vs_use_term." and adding labels for term ".$vs_term;
						fwrite($logFile, "\n\t\t creating use term ".$vs_use_term." and adding labels for term ".$vs_term);
						if ($t_item = $t_list->addItem('', true, false, null, null, '', '', 4, 1)) {
							// add preferred labels
							if (!($t_item->addLabel(
									array('name_singular' => $vs_use_term, 'name_plural' => $vs_use_term, 'description' => ''),
									$pn_nl_locale_id, null, true
								))) {
								print "ERROR: Could not add Dutch preferred label to AM-MovE term ".$vs_use_term.": ".join("; ", $t_item->getErrors())."\n";
								fwrite($logFile, "ERROR: Could not add Dutch preferred label to AM-MovE term ".$vs_use_term.": ".join("; ", $t_item->getErrors())."\n");
							}
							if (!($t_item->addLabel(
									array('name_singular' => $vs_term, 'name_plural' => $vs_term,
									'description' => $va_subject['scope_note']), $pn_nl_locale_id, $vn_list_item_label_type_alt, false
								))) {
								print "ERROR: Could not add Dutch non-preferred label to AM-MovE term
									[".$va_subject['unique_number']."] ".$vs_np_label.": ".join("; ", $t_item->getErrors())."\n";
								fwrite($logFile, "ERROR: Could not add Dutch non-preferred label to AM-MovE term
									[".$va_subject['unique_number']."] ".$vs_np_label.": ".join("; ", $t_item->getErrors())."\n");
							}
							$key =  $t_item->getPrimaryKey();
							$va_unique_terms[$key] = $vs_use_term;
							$va_term_ids[$vs_use_term] = $key;
							break;
						}
					}
					// do not need to add this terms
					$b_add_term = 0;
				}

				// Check if the current term is already added
				// + If term is not found, new term is added and its parent id is set as retrived from the above borader term
				// + If term is found or any term have alternative label is found same then also adding term is sciped.
				// -----------------------------------------------------------------------------------------------------------
				print "\n\t Processing regular term";
				fwrite($logFile, "\n\t Processing regular term");
				if($b_add_term >0) {
					$vs_pref_key = array_search($vs_term, $va_unique_terms);
					$vs_existing_labels = array();

					if($vs_pref_key<1) {
						// We need to create the term.
						print "\n\t\t creating new term $vs_term";
						fwrite($logFile, "\n\t\t creating new term $vs_term");
						if ($t_item = $t_list->addItem($va_subject['unique_number'], $pb_is_enabled, false, null, $vn_type_id, $va_subject['unique_number'], '', 4, 1)) {
							if (!($t_item->addLabel(
									array('name_singular' => $vs_term, 'name_plural' => $vs_term, 'description' => $va_subject['scope_note']),
									$pn_nl_locale_id, null, true
								))) {
								print "ERROR: Could not add Dutch preferred label to AM-MovE term ".$vs_term.": ".join("; ", $t_item->getErrors())."\n";
								fwrite($logFile, "ERROR: Could not add Dutch preferred label to AM-MovE term ".$vs_term.": ".join("; ", $t_item->getErrors())."\n");
							}
							$vs_pref_key =  $t_item->getPrimaryKey();
							$va_unique_terms[$vs_pref_key] = $vs_term;
							$va_term_ids[$vs_term] = $vs_pref_key;
						}
					} else {
						// We need to update the term.
						print "\n\t\t updating term $vs_term";
						fwrite($logFile, "\n\t\t updating term $vs_term");
						if(!$t_item->load($vs_pref_key)) {
							print "ERROR: could not load item {".$vs_pref_key."} \n";
							fwrite($logFile, "ERROR: could not load item {".$vs_pref_key."} \n");
							break;
						}
						$t_item->set('item_value', $va_subject['unique_number']);
						$t_item->set('idno', $va_subject['unique_number']);
						$t_item->set('type_id', $vn_type_id); // this doesn't work, type cannot be set after insert, so we'll do a manual query.
						$t_item->set('is_enabled', $pb_is_enabled ? 1 : 0);
						$t_item->update();
						if($t_item->get('type_id') != $vn_type_id) {
							print "\n\t\t updating type_id to ".$vn_type_id." for $vs_term";
							$qr = "update ca_list_items set type_id = ".$vn_type_id." where item_id = ".$t_item->getPrimaryKey();
							$qr_update_type = $o_db->query($qr);
						}
						$pref_labels = $t_item->getPreferredLabels();
						while(is_array($pref_labels) && count($pref_labels) == 1) {
							$pref_labels = array_pop($pref_labels);
						}
						if(is_array($pref_labels)) {
							print "\n\t\t updating prefered labels for $vs_term";
							fwrite($logFile, "\n\t\t updating prefered labels for $vs_term");
							$t_item->editLabel($pref_labels['label_id'], array('description' => $va_subject['scope_note']), $pn_nl_locale_id, null, true);
						}
						// will be an array with as key the name of the term and value the id of the term
						$labels = $t_item->getLabels();
						// pop the item_id
						$labels = array_pop($labels);
						// pop the language_id
						$labels = array_pop($labels);
						if (is_array($labels)) {
							foreach($labels as $label) {
								$vs_existing_labels[$label['name_singular']] = $label['label_id'];
							}
						}
					}

					// Process used-for terms
					// ----------------------
					print "\n\t Processing used_for terms";
					fwrite($logFile, "\n\t Processing used_for terms");
					if (is_array($va_subject['used_for'])) {
						if(!$t_item->load($vs_pref_key)) {
							print "ERROR: could not load item {".$vs_pref_key."} \n";
							fwrite($logFile, "ERROR: could not load item {".$vs_pref_key."} \n");
							break;
						}
						foreach($va_subject['used_for'] as $vs_used_for_subject) {
							if (($existing_label = $vs_existing_labels[$vs_used_for_subject])) {
								// The label already exists.
								print "\n\t\t existing label found for the used_for term ".$vs_used_for_subject." we can skip this one";
								fwrite($logFile, "\n\t\t existing label found for the used_for term ".$vs_used_for_subject." we can skip this one");
							} else {
								// We need to add a new label.
								print "\n\t\t no label found for the used_for term ".$vs_used_for_subject." we need to create this";
								fwrite($logFile, "\n\t\t no label found for the used_for term ".$vs_used_for_subject." we need to create this");
								if (!($t_item->addLabel(
										array('name_singular' => $vs_used_for_subject, 'name_plural' => $vs_used_for_subject,
										'description' => $va_subject['scope_note']), $pn_nl_locale_id, $vn_list_item_label_type_alt, false
									))) {
									print "ERROR: Could not add Dutch non-preferred label to AM-MovE term
										[".$va_subject['unique_number']."] ".$vs_np_label.": ".join("; ", $t_item->getErrors())."\n";
									fwrite($logFile, "ERROR: Could not add Dutch non-preferred label to AM-MovE term
										[".$va_subject['unique_number']."] ".$vs_np_label.": ".join("; ", $t_item->getErrors())."\n");
								}
							}
						}
					}

					// Process narrower terms
					// ----------------------
					print "\n\t Processing narrower terms";
					fwrite($logFile, "\n\t Processing narrower terms");
					if (is_array($va_subject['narrower_term'])) {
						foreach($va_subject['narrower_term'] as $vs_narrower_subject) {
							$term_key = array_search($vs_narrower_subject, $va_unique_terms);
							if($term_key>1) {
								// already exists, need to add parent / child relation
								print "\n\t\t narrower term ".$vs_narrower_subject." already exist, adding parent relation";
								fwrite($logFile, "\n\t\t narrower term ".$vs_narrower_subject." already exist, adding parent relation");
								if(!$t_item->load($term_key)) {
									$t_item->set('parent_id', $vs_pref_key);
									$t_item->update();
								}
							} else {
								print "\n\t\t narrower term ".$vs_narrower_subject." didn't exist, creating term and adding parent relation";
								fwrite($logFile, "\n\t\t narrower term ".$vs_narrower_subject." didn't exist, creating term and adding parent relation");
								if ($t_item = $t_list->addItem('', true, false, $vs_pref_key, null, '', '', 4, 1)) {
									// add preferred labels
									if (!($t_item->addLabel(
											array('name_singular' => $vs_narrower_subject, 'name_plural' => $vs_narrower_subject, 'description' => ''),
											$pn_nl_locale_id, null, true
										))) {
										print "ERROR: Could not add Dutch preferred label to AM-MovE narrower term ".$vs_use_term.": ".join("; ", $t_item->getErrors())."\n";
										fwrite($logFile, "ERROR: Could not add Dutch preferred label to AM-MovE narrower term ".$vs_use_term.": ".join("; ", $t_item->getErrors())."\n");
									}
									$narrower_key =  $t_item->getPrimaryKey();
									$va_unique_terms[$narrower_key] = $vs_narrower_subject;
									$va_term_ids[$vs_narrower_subject] = $narrower_key;
								}
							}
						}
					}
				}

				// Keep the hierarchy information for linking making the parent/child relationship later on.
				// -----------------------------------------------------------------------------------------
				if($key>0 && $vs_pref_key>0 && $key!=$vs_pref_key) {
					$va_records[] = array('broader_term'=>$va_subject['broader_term'], 'broader_term_id'=>$key, 'term'=>$vs_term, 'term_id'=>$vs_pref_key);
				}

				// record item-item relations
				// ---------------------------
				print "\n\t Processing related terms";
				if (is_array($va_subject['related_term'])) {
					foreach($va_subject['related_term'] as $vs_rel_subject) {
						$va_item_item_links[$vs_term] = $vs_rel_subject;
					}
				}

				unset($key); unset($vs_pref_key);
			} else {
				$va_subject = array();
			}
			break;
			// ---------------------------
	}
}

$o_xml->close();

/*
 * Step 3: Make hierarchy
 * Terms are added in the DB and hirarichy relation is stored in the array while adding terms, now all terms are related to the parent/child terms for hirarichy.
 */
print "\n\nLINKING TERMS IN HIERARCHY...\n";
$vn_last_message_length = 0;

$t_item = new ca_list_items();
$t_item->setMode(ACCESS_WRITE);

foreach($va_records as $vs_records) {

	if(!$t_item->load($vs_records['term_id'])) {
		print "ERROR: could not load item {".$vs_records['term_id']."} (".$vs_records['term'].")\n";
		fwrite($logFile, "ERROR: could not load item {".$vs_records['term_id']."} (".$vs_records['term'].")\n");
		continue;
	}

	print("\n\tLinking ".$vs_records['term']." to ".$vs_records['broader_term']);
	fwrite($logFile, "\n\tLinking ".$vs_records['term']." to ".$vs_records['broader_term']);

	$t_item->set('parent_id', $vs_records['broader_term_id']);
	$t_item->update();

	if ($t_item->numErrors()) {
		print "ERROR: could not set parent_id(".$vs_records['broader_term_id'].") for ".
		$vs_records['term_id']." (".$vs_records['term']."): ".join('; ', $t_item->getErrors())."\n";
		fwrite($logFile, "ERROR: could not set parent_id(".$vs_records['broader_term_id'].") for ".
		$vs_records['term_id']." (".$vs_records['term']."): ".join('; ', $t_item->getErrors())."\n");
	}
}

/*
 * Step 4: Add relations
 * Related terms are stored in the $va_item_item_links array and are liked with each other in the following code.
 */
if ($vn_list_item_relation_type_id_related > 0) {
	print "\n\nADDING RELATED TERM LINKS...\n";
	fwrite($logFile, "\n\nADDING RELATED TERM LINKS...\n");
	$vn_last_message_length = 0;

	$t_item = new ca_list_items();
	$t_link = new ca_list_items_x_list_items();
	$t_link->setMode(ACCESS_WRITE);
	foreach($va_item_item_links as $vs_left_id => $vs_right_id) {
		print str_repeat(chr(8), $vn_last_message_length);
		$vs_message = "\tLINKING {$vs_left_id} to {$vs_right_id}";
		if (($vn_l = 200-strlen($vs_message)) < 1) { $vn_l = 1; }
		$vs_message .= str_repeat(' ', $vn_l);
		$vn_last_message_length = strlen($vs_message);
		print $vs_message;

		if (!($vn_left_item_id = $va_term_ids[$vs_left_id])) {
			print "ERROR: no list item id for left_id {$vs_left_id} (were there previous errors?)\n";
			fwrite($logFile, "ERROR: no list item id for left_id {$vs_left_id} (were there previous errors?)\n");
			continue;
		}
		if (!($vn_right_item_id = $va_term_ids[$vs_right_id])) {
			print "ERROR: no list item id for right_id {$vs_right_id} (were there previous errors?)\n";
			fwrite($logFile, "ERROR: no list item id for right_id {$vs_right_id} (were there previous errors?)\n");
			continue;
		}

		$t_link->set('term_left_id', $vn_left_item_id);
		$t_link->set('term_right_id', $vn_right_item_id);
		$t_link->set('type_id', $vn_list_item_relation_type_id_related);
		$t_link->insert();

		if ($t_link->numErrors()) {
			print "ERROR: could not set link between {$vs_left_id} (was translated to item_id={$vn_left_item_id}) and {$vs_right_id} (was translated to item_id={$vn_right_item_id}): ".join('; ', $t_link->getErrors())."\n";
			fwrite($logFile, "ERROR: could not set link between {$vs_left_id} (was translated to item_id={$vn_left_item_id}) and {$vs_right_id} (was translated to item_id={$vn_right_item_id}): ".join('; ', $t_link->getErrors())."\n");
		}
	}
} else {
	print "WARNING: Skipped import of term-term relationships because the ca_list_items_x_list_items 'related' relationship type is not defined for your installation\n";
	fwrite($logFile, "WARNING: Skipped import of term-term relationships because the ca_list_items_x_list_items 'related' relationship type is not defined for your installation\n");
}

print "\n\nIMPORT COMPLETE.\n";
fwrite($logFile, "\n\nIMPORT COMPLETE.\n");

fclose($logFile);

?>
