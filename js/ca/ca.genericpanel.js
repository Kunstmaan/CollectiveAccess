/* ----------------------------------------------------------------------
 * js/ca/ca.genericpanel.js
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
 
var caUI = caUI || {};

(function ($) {
	caUI.initPanel = function(options) {
		// --------------------------------------------------------------------------------
		// setup options
		var that = jQuery.extend({
			panelID: 'caPanel',										/* id of enclosing panel div */
			panelContentID: 'caPanelContent',				/* id of div within enclosing panel div that contains content */
	
			useExpose: true,
			exposeBackgroundColor: '#000000',
			exposeBackgroundOpacity: 0.5,
			panelTransitionSpeed: 200,
			
			isChanging: false
		}, options);
		
		
		// --------------------------------------------------------------------------------
		// Define methods
		// --------------------------------------------------------------------------------
		that.showPanel = function(url) {
			that.isChanging = true;
			jQuery('#' + that.panelID).fadeIn(that.panelTransitionSpeed, function() { that.isChanging = false; });
			
			if (that.useExpose) { 
				jQuery('#' + that.panelID).expose({api: true, color: that.exposeBackgroundColor , opacity: that.exposeBackgroundOpacity}).load(); 
			}
			jQuery('#' + that.panelContentID).load(url, { });
		}
		
		that.hidePanel = function() {
			that.isChanging = true;
			jQuery('#' + that.panelID).fadeOut(that.panelTransitionSpeed, function() { that.isChanging = false; });
			
			if (that.useExpose) {
				jQuery.mask.close();
			}
			jQuery('#' + that.panelContentID).empty();
		}
		
		that.panelIsVisible = function() {
			return (jQuery('#' + that.panelID + ':visible').length > 0) ? true : false;
		}

		// --------------------------------------------------------------------------------
		// Set up handler to trigger appearance of panel
		// --------------------------------------------------------------------------------
		jQuery(document).ready(function() {
			// hide panel if click is outside of panel
			jQuery(document).click(function(event) {
				var p = jQuery(event.target).parents().map(function() { return this.id; }).get();
				if (!that.isChanging && that.panelIsVisible() && (jQuery.inArray(that.panelID, p) == -1)) {
					that.hidePanel();
				}
			});
			
			// hide panel if escape key is clicked
			jQuery(document).keyup(function(event) {
				if ((event.keyCode == 27) && !that.isChanging && that.panelIsVisible()) {
					that.hidePanel();
				}
			});
		});
		
		// --------------------------------------------------------------------------------
		
		return that;
	};	
})(jQuery);