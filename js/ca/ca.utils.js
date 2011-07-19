/* ----------------------------------------------------------------------
 * js/ca/ca.utils.js
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
	caUI.initUtils = function(options) {
		var that = jQuery.extend({
			
			// Unsaved change warning options
			unsavedChangesWarningMessage: 'You have made changes in this form that you have not yet saved. If you navigate away from this form you will lose your unsaved changes.',
			disableUnsavedChangesWarning: false
		}, options);
		
		that.showUnsavedChangesWarningFlag = false;
		caUI.utils = {};
		//
		// Unsaved change warning methods
		//		
			// Sets whether warning should be shown if user tries to navigate away
			caUI.utils.showUnsavedChangesWarning = function(b) {
				if (b === undefined) { b = true; }
				that.showUnsavedChangesWarningFlag = b ? true : false;
				return this;
			};
			
			// Returns true if warning will be shown if user user tries to navigate away
			caUI.utils.shouldShowUnsavedChangesWarning = function() {
				return that.showUnsavedChangesWarningFlag;
			};
			
			// returns text of warning message
			caUI.utils.getUnsavedChangesWarningMessage = function() {
				return that.unsavedChangesWarningMessage;
			};
			
			// If set to true, no warning will be triggered
			caUI.utils.disableUnsavedChangesWarning = function(b) {
				that.disableUnsavedChangesWarning = b ? true : false;
			};
			
			caUI.utils.getDisableUnsavedChangesWarning = function(b) {
				return that.disableUnsavedChangesWarning;
			};
			
			// init event handler
			window.onbeforeunload = function() { 
				if(!caUI.utils.getDisableUnsavedChangesWarning() && caUI.utils.shouldShowUnsavedChangesWarning()) {
					return caUI.utils.getUnsavedChangesWarningMessage();
				}
			}
			
			// ------------------------------------------------------------------------------------
			
			caUI.utils.sortObj = function(arr, isCaseInsensitive) {
				var sortedKeys = new Array();
				var sortedObj = {};
				
				// Separate keys and sort them
				for (var i in arr){
					sortedKeys.push(i);
				}
				
				if (isCaseInsensitive) {
					sortedKeys.sort(caUI.utils._caseInsensitiveSort);
				} else {
					sortedKeys.sort();
				}
				
				// Reconstruct sorted obj based on keys
				for (var i in sortedKeys){
					sortedObj[sortedKeys[i]] = arr[sortedKeys[i]];
				}
				return sortedObj;
			};
			
			caUI.utils._caseInsensitiveSort = function(a, b) { 
			   var ret = 0;
			   a = a.toLowerCase();
			   b = b.toLowerCase();
			   if(a > b) 
				  ret = 1;
			   if(a < b) 
				  ret = -1; 
			   return ret;
			}
			
			// ------------------------------------------------------------------------------------
		
		return that;
	};
	
	
})(jQuery);