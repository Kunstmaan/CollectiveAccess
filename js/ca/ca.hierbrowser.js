/* ----------------------------------------------------------------------
 * js/ca/ca.hierbrowser.js
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
 * ----------------------------------------------------------------------
 */
 
var caUI = caUI || {};

(function ($) {
	caUI.initHierBrowser = function(container, options) {
		// --------------------------------------------------------------------------------
		// setup options
		var that = jQuery.extend({
			container: container,
			levelDataUrl: '',
			initDataUrl: '',
			editUrl: '',
			
			editUrlForFirstLevel: '',
			editDataForFirstLevel: '',	/* name of key in data to use for item_id in first level, if different from other levels */
			dontAllowEditForFirstLevel: false,
			
			name: options.name ? options.name : container.replace(/[^A-Za-z0-9]+/, ''),
			levelWidth: 230,
			
			readOnly: false,	// if set to true, no navigation is allowed
			
			initItemID: null,
			
			className: 'hierarchyBrowserLevel',
			classNameSelected: 'hierarchyBrowserLevelSelected',
			
			currentSelectionDisplayID: '',
			currentSelectionDisplayFormat: '%1',
			currentSelectionIDID: '',
			
			onSelection: null,		/* function to call whenever an item is selected; passed item_id, name and formatted display string */
			
			displayCurrentSelectionOnLoad: true,
			typeMenuID: '',
			
			indicatorUrl: '',
			editButtonIcon: '',
			
			hasChildrenIndicator: 'has_children',	/* name of key in data to use to determine if an item has children */
			alwaysShowChildCount: true,
			
			levelLists: [],
			selectedItemIDs: [],
			
			_numOpenLoads: 0,
			_openLoadsForLevel:[]
		}, options);
		
		if (!that.levelDataUrl) { 
			alert("No level data url specified for " + that.name + "!");
			return null;
		}
		
		// create scrolling container
		jQuery('#' + that.container).append("<div class='hierarchyBrowserContainer' id='" + that.container + "_scrolling_container'></div>");
		
		if (that.typeMenuID) {
			jQuery('#' + that.typeMenuID).hide();
		}
		
		// --------------------------------------------------------------------------------
		// Define methods
		// --------------------------------------------------------------------------------
		that.setUpHierarchy = function(item_id) {
			if (!item_id) { that.setUpHierarchyLevel(0, 0); return; }
			that.levelLists = [];
			that.selectedItemIDs = [];
			jQuery.getJSON(that.initDataUrl, { id: item_id}, function(data) {
				if (data.length) {
					that.selectedItemIDs = data.join(';').split(';');
					data.unshift(0);
				} else {
					data = [0];
				}
				var l = 0;
				jQuery.each(data, function(i, id) {
					that.setUpHierarchyLevel(i, id, 1);
					l++;
				});
				
				jQuery('#' + that.container + '_scrolling_container').animate({scrollLeft: l * that.levelWidth}, 500);
			});
		}
		// --------------------------------------------------------------------------------
		that.clearLevelsStartingAt = function(level) {
			var l = level;
			
			// remove all level divs above the current one
			while(jQuery('#hierBrowser_' + that.name + '_' + l).length > 0) {
				jQuery('#hierBrowser_' + that.name + '_' + l).remove();
				that.levelLists[l] = undefined;
				l++;
			}
			
		}
		// --------------------------------------------------------------------------------
		that.setUpHierarchyLevel = function (level, item_id, is_init) {
			that._numOpenLoads++;
			if (that._openLoadsForLevel[level]) { return null; }	// load is already open for this level
			that._openLoadsForLevel[level] = true;
			
			// Remove any levels *after* the one we're populating
			that.clearLevelsStartingAt(level);
			
			if (!item_id) { item_id = 0; }
			if (!is_init) { is_init = 0; }
			
			// Create div to enclose new level
			var newLevelDivID = 'hierBrowser_' + that.name + '_' + level;
			var newLevelDiv = "<div class='hierarchyBrowserLevel' style='left:" + (that.levelWidth * level) + "px;' id='" + newLevelDivID + "'></div>";
			
			jQuery('#' + newLevelDivID).remove();
			
			// Create new ul to display list of items
			var newLevelListID = 'hierBrowser_' + that.name + '_list_' + level;
			var newLevelList = "<ul class='" + that.className + "' id='" + newLevelListID + "'></ul>";
			
			jQuery('#' + that.container + '_scrolling_container').append(newLevelDiv);
			jQuery('#' + newLevelDivID).data('level', level);
			jQuery('#' + newLevelDivID).data('parent_id', item_id);
			jQuery('#' + newLevelDivID).append(newLevelList);
			
			if (that.indicatorUrl) {
				var indicator = document.createElement('img');
				indicator.src = that.indicatorUrl;
				indicator.className = '_indicator';
				indicator.style.position = 'absolute';
				indicator.style.left = '50%';
				indicator.style.top = '50%';
				jQuery('#' + newLevelDivID).append(indicator);
			}
			
			var parent_id = item_id;
			
			jQuery.getJSON(that.levelDataUrl, { id: item_id, init: is_init ? 1 : '', root_item_id: that.selectedItemIDs[0] ? that.selectedItemIDs[0] : ''}, function(data) {
				that._numOpenLoads--;
				
				var l = jQuery('#' + newLevelDivID).data('level');
				that._openLoadsForLevel[l] = false;
				
				jQuery.each(data, function(i, item) {
					if (item[data._primaryKey]) {
						
						if ((is_init) && (l == 0) && (!that.selectedItemIDs[0])) {
							that.selectedItemIDs[0] = item[data._primaryKey];
						}
						var listItem = ((item.children > 0) || (that.alwaysShowChildCount)) ? ' (' + item.children + ')' : '';
						
						var editButton = '';
						if ((that.editButtonIcon) && (!((l == 0) && that.dontAllowEditForFirstLevel))) {
							editButton = "<div style='float: right;'><a href='#' id='hierBrowser_" + that.name + '_level_' + l + '_item_' + item[data._primaryKey] + "_edit'>" +that.editButtonIcon + "</a></div>";
						}
						jQuery('#' + newLevelListID).append(
								"<li class='" + that.className + "'>" + editButton + "<a href='#' id='hierBrowser_" + that.name + '_level_' + l + '_item_' + item[data._primaryKey] + "' class='" + that.className + "'>" + jQuery('<div/>').text(item.name).html() + listItem + '</a>' +  '</li>'
						);
						
						jQuery('#' + newLevelListID + " li:last a").data('item_id', item[data._primaryKey]);
						if(that.editDataForFirstLevel) {
							jQuery('#' + newLevelListID + " li:last a").data(that.editDataForFirstLevel, item[that.editDataForFirstLevel]);
						}
						
						if (that.hasChildrenIndicator) {
							jQuery('#' + newLevelListID + " li:last a").data('has_children', item[that.hasChildrenIndicator] ? true : false);
						}
						
						// edit button
						if ((that.editButtonIcon) && (!((l == 0) && that.dontAllowEditForFirstLevel))) {
							var editUrl = '';
							var editData = 'item_id';
							if (that.editUrlForFirstLevel && (l == 0)) {
								editUrl = that.editUrlForFirstLevel;
								if(that.editDataForFirstLevel) {
									editData = that.editDataForFirstLevel;
								}
							} else {
								editUrl = that.editUrl;
							}
							if (editUrl) {
								jQuery('#' + newLevelListID + " li:last a:first").click(function() { 
									window.location = editUrl + jQuery(this).data(editData);
									return false;
								});
							}
						}
						
						// hierarchy forward navigation
						if (!that.readOnly) {
							jQuery('#' + newLevelListID + " li:last a:last").click(function() { 
								var l = jQuery(this).parent().parent().parent().data('level');
								var item_id = jQuery(this).data('item_id');
								var has_children = jQuery(this).data('has_children');
								
								// set current selection display
								var formattedDisplayString = that.currentSelectionDisplayFormat.replace('%1', item.name);
								
								if (that.currentSelectionDisplayID) {
									jQuery('#' + that.currentSelectionDisplayID).html(formattedDisplayString);
								}
								
								if (that.currentSelectionIDID) {
									jQuery('#' + that.currentSelectionIDID).attr('value', item_id);
								}
								
								if (that.onSelection) {
									that.onSelection(item_id, parent_id, item.name, formattedDisplayString);
								}
								
								while(that.selectedItemIDs.length > l) {
									that.selectedItemIDs.pop();
								}
								jQuery('#hierBrowser_' + that.name + '_' + l + ' a').removeClass(that.classNameSelected).addClass(that.className);
								jQuery('#hierBrowser_' + that.name + '_level_' + l + '_item_' + item_id).addClass(that.classNameSelected);
								
								
								//if (((that.hasChildrenIndicator) && (has_children)) || !that.hasChildrenIndicator) {
									// scroll to new level
									that.setUpHierarchyLevel(l + 1, item_id);
									jQuery('#' + that.container + '_scrolling_container').animate({scrollLeft: l * that.levelWidth}, 500);
								//} else {
								//	that.clearLevelsStartingAt(l+1);
								//}
								return false;
							});
						}
						
						if (that.readOnly) {
							jQuery('#' + newLevelListID + " li:last a").click(function() { 
								return false;
							});
						}
					}
				});
				if (!is_init) {
					that.selectedItemIDs[level-1] = item_id;
					jQuery('#' + newLevelListID + ' a').removeClass(that.classNameSelected).addClass(that.className);
					jQuery('#hierBrowser_' + that.name + '_' + (level - 1) + ' a').removeClass(that.classNameSelected).addClass(that.className);
					jQuery('#hierBrowser_' + that.name + '_level_' + (level - 1) + '_item_' + item_id).addClass(that.classNameSelected);
				} else {
					if (that.selectedItemIDs[level] !== undefined) {
						jQuery('#hierBrowser_' + that.name + '_level_' + (level) + '_item_' + that.selectedItemIDs[level]).addClass(that.classNameSelected);
						jQuery('#hierBrowser_' + that.name + '_' + level).scrollTo('#hierBrowser_' + that.name + '_level_' + level + '_item_' + that.selectedItemIDs[level]);
					}
				}
				
				if ((that._numOpenLoads == 0) && that.currentSelectionDisplayID) {
					var selectedID = that.getSelectedItemID();
					var l = that.numLevels();
					while(l >= 0) {
						if (that.displayCurrentSelectionOnLoad && is_init && jQuery('#hierBrowser_' + that.name + '_level_' + l + '_item_' + selectedID).length > 0) {
							if (that.currentSelectionDisplayID) {
								jQuery('#' + that.currentSelectionDisplayID).html(that.currentSelectionDisplayFormat.replace('%1', jQuery('#hierBrowser_' + that.name + '_level_' + l + '_item_' + selectedID).html()));
							}
							break;
						}
						l--;
					}
				}
				
				if ((that._numOpenLoads == 0) && that.typeMenuID) {
					jQuery('#' + that.typeMenuID).show(300);
				}
				
				jQuery('#' + newLevelDivID + ' img._indicator').remove();		// hide loading indicator
				
			});
			
			that.levelLists[level] = newLevelDiv;
			return newLevelDiv;
		}
		// --------------------------------------------------------------------------------
		// return database id (the primary key in the database, *NOT* the DOM ID) of currently selected item
		that.getSelectedItemID = function() {
			return that.selectedItemIDs[that.selectedItemIDs.length - 1];
		}
		// --------------------------------------------------------------------------------
		// returns the number of levels loaded
		that.numLevels = function() {
			return that.levelLists.length;
		}
		// --------------------------------------------------------------------------------
		// initialize before returning object
		that.setUpHierarchy(that.initItemID);
		
		return that;
	};	
})(jQuery);