/* ----------------------------------------------------------------------
 * js/ca/ca.labelbundle.js
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2011 Whirl-i-Gig
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
	caUI.initLabelBundle = function(container, options) {
		var that = jQuery.extend({
			container: container,
			mode: 'preferred',
			templateValues: [],
			initialValues: {},
			forceNewValues: [],
			labelID: 'Label_',
			fieldNamePrefix: '',
			localeClassName: 'caLabelLocale',
			templateClassName: 'caLabelTemplate',
			labelListClassName: 'caLabelList',
			addButtonClassName: 'caAddLabelButton',
			deleteButtonClassName: 'caDeleteLabelButton',
			
			counter: 0
		}, options);
		
		jQuery(container + " ." + that.addButtonClassName).click(function() {
			that.addLabelToLabelBundle(container);
			that.showUnsavedChangesWarning(true);	
			
			return false;
		});
		
		that.showUnsavedChangesWarning = function(b) {
			if(typeof caUI.utils.showUnsavedChangesWarning === 'function') {
				if (b === undefined) { b = true; }
				caUI.utils.showUnsavedChangesWarning(b);
			}
		}
		
		that.addLabelToLabelBundle = function(id, initialValues, forceNew) {
			if (forceNew == undefined) { forceNew = false; }
			
			// prepare template values
			var cnt, templateValues = {};
			var isNew = false;
			if (initialValues) {
				// existing label (if forced to be "new" we ignore the id
				templateValues.n = (!forceNew) ? id : 'new_' + this.getCount();
				jQuery.extend(templateValues, initialValues);
			} else {
				// new label
				initialValues = {};
				jQuery.each(this.templateValues, function(i, v) {
					templateValues[v] = '';
				});
				templateValues.n = 'new_' + this.getCount();
				isNew = true;
			}
			templateValues.fieldNamePrefix = this.fieldNamePrefix; // always pass field name prefix to template
			
			var jElement = jQuery(this.container + ' textarea.' + this.templateClassName).template(templateValues); 
			jQuery(this.container + " ." + this.labelListClassName).append(jElement);
			
			var that = this;	// for closures
			
			// attach delete button
			jQuery(this.container + " #" + this.fieldNamePrefix+this.labelID + templateValues.n + " ." + this.deleteButtonClassName).click(function() { that.deleteLabelFromLabelBundle(templateValues.n); return false; });
			
			// set locale_id
			// find unused locale
			var localeList = jQuery.makeArray(jQuery(this.container + " select." + this.localeClassName + ":first option"));
			
			var defaultLocaleSelectedIndex = 0;
			for(i=0; i < localeList.length; i++) {
				if (!isNew) {
					if (localeList[i].value !== templateValues.locale_id) { continue; }
				} else {
					if (jQuery(this.container + " select." + this.localeClassName + " option:selected[value=" + localeList[i].value + "]").length > 0) { 
						if(jQuery(this.container + " select." + this.localeClassName).length > 1) {
							continue; 
						}
					}
				}
				
				defaultLocaleSelectedIndex = i;
				break;
			}
			
			// set default values for <select> elements
			var i;
			for (i=0; i < this.templateValues.length; i++) {
				if (this.templateValues[i] === 'locale_id') { continue; }
				if (jQuery(this.container + " select#" + this.fieldNamePrefix + this.templateValues[i] + "_" + id).length) {
					jQuery(this.container + " select#" + this.fieldNamePrefix + this.templateValues[i] + "_" + id + " option[value=" + templateValues[this.templateValues[i]] +"]").attr('selected', true);
				}
			}
			
			jQuery(this.container + " #" + this.fieldNamePrefix + "locale_id_" + templateValues.n +" option:eq(" + defaultLocaleSelectedIndex + ")").attr('selected', true);
			
			// attach onchange function to locale_id
			jQuery(this.container + " #" + this.fieldNamePrefix + "locale_id_" + templateValues.n).change(function() { that.updateLabelBundleFormState(); });
			
			this.updateLabelBundleFormState();
			
			this.incrementCount();
			return this;
		};
		
		that.updateLabelBundleFormState = function() {
			switch(this.mode) {
				case 'preferred':
					// make locales already labeled non-selectable (preferred mode only)
					var tmp = jQuery.makeArray(jQuery(this.container + " ." + this.labelListClassName + " select." + this.localeClassName +" option:selected"));
					var selectedLocaleIDs = [];
					var i;
					for(i=0; i < tmp.length; i++) {
						selectedLocaleIDs.push(tmp[i].value);
					}
					var localeSelects = jQuery.makeArray(jQuery(this.container + " ." + this.labelListClassName + " select." + this.localeClassName +""));
					
					for(i=0; i < localeSelects.length; i++) {
						var selectedLocaleID = localeSelects[i].options[localeSelects[i].selectedIndex].value;
						var j;
						for (j=0; j < localeSelects[i].options.length; j++) {
							if ((jQuery.inArray(localeSelects[i].options[j].value, selectedLocaleIDs) >= 0) && (localeSelects[i].options[j].value != selectedLocaleID)) {
								localeSelects[i].options[j].disabled = true;
							} else {
								localeSelects[i].options[j].disabled = false;
							}
						}
					}
					
					
					// remove "add" button if all locales have a label (preferred mode only)
					
					var numLabels = jQuery(this.container + " ." + this.labelListClassName + " > div").length;
					tmp = jQuery.makeArray(jQuery(this.container + " ." + this.labelListClassName + " select." + this.localeClassName + ":first"));
					if ((numLabels > 0) && (!tmp || !tmp[0] || tmp[0].options.length <= jQuery(this.container + " ." + this.labelListClassName + " div select." + this.localeClassName ).length)) {
						// no more
						jQuery(this.container + " ." + this.addButtonClassName).hide();
					} else {
						jQuery(this.container + " ." + this.addButtonClassName).show(200);			
					}
					break;
				default:
					// noop
					break;
			}
			return this;
		};
		
		that.deleteLabelFromLabelBundle = function(id) {
			jQuery(this.container + ' #' + this.fieldNamePrefix + 'Label_' + id).remove();
			jQuery(this.container).append("<input type='hidden' name='" + that.fieldNamePrefix + "Label_" + id + "_delete' value='1'/>");
			this.updateLabelBundleFormState();
			
			that.showUnsavedChangesWarning(true);	
		
			return this;
		};
			
		that.getCount = function() {
			
			return this.counter;
		};
			
		that.incrementCount = function() {
			this.counter++;
		};
		
		// create initial values
		
		var initalizedLabelCount = 0;
		jQuery.each(that.initialValues, function(k, v) {
			that.addLabelToLabelBundle(k, v);
			initalizedLabelCount++;
		});
		
		// add forced values
		jQuery.each(that.forceNewValues, function(k, v) {
			that.addLabelToLabelBundle(k, v, true);
			initalizedLabelCount++;
		});
		
		if (initalizedLabelCount == 0) {
			that.addLabelToLabelBundle();
		}
		
		
		that.updateLabelBundleFormState();
		return that;
	};
	
	
})(jQuery);