/* ----------------------------------------------------------------------
 * js/ca/ca.genericbundle.js
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
	caUI.initBundle = function(container, options) {
		var that = jQuery.extend({
			container: container,
			addMode: 'append',
			templateValues: [],
			initialValues: {},
			forceNewValues: [],
			errors: {},
			itemID: '',
			fieldNamePrefix: '',
			templateClassName: 'caItemTemplate',
			itemListClassName: 'caItemList',
			itemClassName: 'labelInfo',
			localeClassName: 'labelLocale',
			addButtonClassName: 'caAddItemButton',
			deleteButtonClassName: 'caDeleteItemButton',
			showOnNewIDList: [],
			hideOnNewIDList: [],
			enableOnNewIDList: [],
			counter: 0,
			minRepeats: 0,
			maxRepeats: 65535,
			showEmptyFormsOnLoad: 1,
			onInitializeItem: null,
			onItemCreate: null,	/* callback function when a bundle item is created */
			onAddItem: null,
			incrementLocalesForNewBundles: true,
			defaultValues: {},
			
			isSortable: false,
			listSortOrderID: null
		}, options);
		
		jQuery(container + " ." + that.addButtonClassName).click(function() {
			that.addToBundle();
			that.showUnsavedChangesWarning(true);	
			
			return false;
		});
		
		that.showUnsavedChangesWarning = function(b) {
			if(typeof caUI.utils.showUnsavedChangesWarning === 'function') {
				if (b === undefined) { b = true; }
				caUI.utils.showUnsavedChangesWarning(b);
			}
		}
		
		that.addToBundle = function(id, initialValues, dontUpdateBundleFormState) {
			// prepare template values
			var cnt, templateValues = {};
			var isNew = false;
			if (initialValues && !initialValues['_handleAsNew']) {
				// existing item
				templateValues.n = id;
				jQuery.extend(templateValues, initialValues);
				
				jQuery.each(this.templateValues, function(i, v) {
					if (templateValues[v] == null) {  templateValues[v] = ''; }
				});
			} else {
				// new item
				if (!initialValues) {
					initialValues = {};
					jQuery.each(this.templateValues, function(i, v) {
						templateValues[v] = '';
					});
				} else {
					jQuery.extend(templateValues, initialValues);
					
					// init all unset template placeholders to empty string
					jQuery.each(this.templateValues, function(i, v) {
						if (templateValues[v] == null) {  templateValues[v] = ''; }
					});

					this.errors[id] = initialValues['_errors'];
				}
				templateValues.n = 'new_' + this.getCount();
				templateValues.error = '';
				isNew = true;
			}
					
			var defaultLocaleSelectedIndex = 0;
			if (isNew && this.incrementLocalesForNewBundles) {
				// set locale_id for new bundles
				// find unused locale
				var localeList = jQuery.makeArray(jQuery(this.container + " select." + this.localeClassName + ":first option"));
				for(i=0; i < localeList.length; i++) {
					if (jQuery(this.container + " select." + this.localeClassName + " option:selected[value=" + localeList[i].value + "]").length > 0) { 
						continue; 
					}
					
					defaultLocaleSelectedIndex = i;
					break;
				}
			}
			
			// print out any errors
			var errStrs = [];
			if (this.errors && this.errors[id]) {
				var i;
				for (i=0; i < this.errors[id].length; i++) {
					errStrs.push(this.errors[id][i].errorDescription);
				}
			}
			
			templateValues.error = errStrs.join('<br/>');
			templateValues.fieldNamePrefix = this.fieldNamePrefix; // always pass field name prefix to template
			
			// Set default value for new items
			if (!id) {
				jQuery.each(this.defaultValues, function(k, v) {
					if (v && !templateValues[k]) { templateValues[k] = v; }
				});
			}
			
			// replace values in template
			var jElement = jQuery(this.container + ' textarea.' + this.templateClassName).template(templateValues); 
			
			if ((this.addMode == 'prepend') && isNew) {	// addMode only applies to newly created bundles
				jQuery(this.container + " ." + this.itemListClassName).prepend(jElement);
			} else {
				jQuery(this.container + " ." + this.itemListClassName).append(jElement);
			}

			if (this.onInitializeItem && (initialValues && !initialValues['_handleAsNew'])) {
				this.onInitializeItem(id, initialValues, this);
			}

			var that = this;	// for closures
			
			// set defaults in SELECT elements
			var selects = jQuery.makeArray(jQuery(this.container + " select"));

			// assumes name of fields is:
			// {fieldNamePrefix} + {fieldname} + {_} + {row id number}
			var i;
			var fieldRegex = new RegExp(this.fieldNamePrefix + "([A-Za-z0-9_\-]+)_([0-9]+)");
			for(i=0; i < selects.length; i++) {
				var element_id = selects[i].id;
				
				var info = element_id.match(fieldRegex);
				if (info && info[2] && (parseInt(info[2]) == id)) {
					if (typeof(this.initialValues[id][info[1]]) == 'boolean') {
						this.initialValues[id][info[1]] = (this.initialValues[id][info[1]]) ? '1' : '0';
					}
					jQuery(this.container + " #" + element_id + " option[value=" + this.initialValues[id][info[1]] +"]").attr('selected', true);
				}
			}
			
			// set defaults in CHECKBOX elements
			var checkboxes = jQuery.makeArray(jQuery(this.container + " input[type=checkbox]"));

			// assumes name of fields is:
			// {fieldNamePrefix} + {fieldname} + {_} + {row id number}
			var i;
			var fieldRegex = new RegExp(this.fieldNamePrefix + "([A-Za-z0-9_\-]+)_([0-9]+)");
			for(i=0; i < checkboxes.length; i++) {
				var element_id = checkboxes[i].id;
				var info = element_id.match(fieldRegex);
				if (info && info[2] && (parseInt(info[2]) == id)) {
					if (typeof(this.initialValues[id][info[1]]) == 'boolean') {
						this.initialValues[id][info[1]] = (this.initialValues[id][info[1]]) ? '1' : '0';
					}
					jQuery(this.container + " #" + element_id + "[value=" + this.initialValues[id][info[1]] +"]").attr('checked', true);
				}
			}
			
			// set defaults in RADIO elements
			var radios = jQuery.makeArray(jQuery(this.container + " input[type=radio]"));

			// assumes name of fields is:
			// {fieldNamePrefix} + {fieldname} + {_} + {row id number} + {_} + {checkbox sequence number - eg. 0, 1, 2}
			var i;
			var fieldRegex = new RegExp(this.fieldNamePrefix + "([A-Za-z0-9_\-]+)_([0-9]+)_([0-9]+)");
			for(i=0; i < radios.length; i++) {
				var element_id = radios[i].id;
				var info = element_id.match(fieldRegex);
				if (info && info[2] && (parseInt(info[2]) == id)) {
					if (typeof(this.initialValues[id][info[1]]) == 'boolean') {
						this.initialValues[id][info[1]] = (this.initialValues[id][info[1]]) ? '1' : '0';
					}
					jQuery(this.container + " #" + element_id + "[value=" + this.initialValues[id][info[1]] +"]").attr('checked', true);
				}
			}
			
			
			// Do show/hide on creation of new item
			if (isNew) {
				var curCount = this.getCount();
				if (this.showOnNewIDList.length > 0) {
					jQuery.each(this.showOnNewIDList, function(i, show_id) { 
						jQuery(that.container + ' #' + show_id +'new_' + curCount).show(); }
					);
				}
				if (this.hideOnNewIDList.length > 0) {
					jQuery.each(this.hideOnNewIDList, function(i, hide_id) { 
						jQuery(that.container + ' #' + hide_id +'new_' + curCount).hide();}
					);
				}
				if (this.enableOnNewIDList.length > 0) {
					jQuery.each(this.enableOnNewIDList, function(i, enable_id) { 
						jQuery(that.container + ' #' + enable_id +'new_' + curCount).attr('disabled', false); }
					);
				}
			}
		
			// attach delete button
			var relation_id = templateValues['relation_id'];
			if(relation_id == null) {
				relation_id = 1;
			}
			jQuery(this.container + " #" +this.itemID + templateValues.n + " ." + this.deleteButtonClassName).click(function() { that.deleteFromBundle(templateValues.n, null, relation_id); return false; });
				
			// set default locale for new
			if (isNew) {
				jQuery(this.container + " #" + this.fieldNamePrefix + "locale_id_" + templateValues.n +" option:eq(" + defaultLocaleSelectedIndex + ")").attr('selected', true);
			}
			
			if(this.onAddItem) {
				this.onAddItem(id ? id : templateValues.n, this);
			}
			
			this.incrementCount();
			if (!dontUpdateBundleFormState) { this.updateBundleFormState(); }
			
			if (this.onItemCreate) {
				this.onItemCreate(templateValues.n, this.initialValues[id]);
			}
			
			return this;
		};
		
		that.updateBundleFormState = function() {
			// enforce min repeats option (hid "delete" buttons if there are only x # repeats)
			if (this.getCount() <= this.minRepeats) {
				jQuery(this.container + " ." + this.deleteButtonClassName).hide();	
			} else {
				jQuery(this.container + " ." + this.deleteButtonClassName).show(200);
			}
			
			// enforce max repeats option (hide "add" button after a certain # of repeats)
			if (this.getCount() >= this.maxRepeats) {
				jQuery(this.container + " ." + this.addButtonClassName).hide();	
			} else {
				jQuery(this.container + " ." + this.addButtonClassName).show(200);
			}
			return this;
		};

		that.deleteFromBundle = function(id,invokeDeleteHandler) {
			return that.deleteFromBundle(id,invokeDeleteHandler, 1);
		}

		that.deleteFromBundle = function(id,invokeDeleteHandler, relationid) {
			//on delete is called before updating the state, as onDeleteItem(id) needs to access field values from DOM.
			if(this.onDeleteItem && invokeDeleteHandler!=false) {
				this.onDeleteItem(id);
			}

			jQuery(this.container + ' #' + this.itemID + id).remove();
			jQuery(this.container).append("<input type='hidden' name='" + that.fieldNamePrefix + id + "_delete' value='"+relationid+"'/>");

			// this.decrementCount();
			this.updateBundleFormState();

			that.showUnsavedChangesWarning(true);

			return this;
		};

		that.getCount = function() {
			return this.counter;
		};
			
		that.incrementCount = function() {
			this.counter++;
		};
			
		//that.decrementCount = function() {
		//	this.counter--;
		//};
		
		that._updateSortOrderListIDFormElement = function() {
			if (!that.listSortOrderID) { return false; }
			var sort_list = [];
			jQuery.each(jQuery(that.container + " ." + that.itemListClassName + " ." + that.itemClassName), function(k, v) { 
				sort_list.push(jQuery(v).attr('id').replace(that.itemID, ''));
			});
			jQuery('#' + that.listSortOrderID).val(sort_list.join(';'));
			
			return true;
		}
		
		// create initial values
		var initalizedCount = 0;
		jQuery.each(that.initialValues, function(k, v) {
			that.addToBundle(k, v, true);
			initalizedCount++;
		});
		
		// add 'forced' new values (typically used to pre-add new items to the bundle when, for example,
		// in a previous action the add failed)
		if (!that.forceNewValues) { that.forceNewValues = []; }
		jQuery.each(that.forceNewValues, function(k, v) {
			v['_handleAsNew'] = true;
			that.addToBundle('new_' + k, v, true);
			initalizedCount++;
		});
		
		// force creation of empty forms if needed
		if ((initalizedCount <= that.minRepeats) && (that.minRepeats > 0)) {
			// empty forms to meet minimum count
			var i;
			for(i = initalizedCount; i < that.minRepeats; i++) {
				that.addToBundle(null, null, true);
				initalizedCount++;
			}
		} 
			// empty form to show user on load
		if (that.showEmptyFormsOnLoad > that.maxRepeats) { that.showEmptyFormsOnLoad = that.maxRepeats; }
		if (that.showEmptyFormsOnLoad > 0) {
			var j;
			for(j=0; j < (that.showEmptyFormsOnLoad - initalizedCount); j++) {
				that.addToBundle(null, null, true);
			}
		}
		
		if (that.isSortable) {
			jQuery(that.container + " .caItemList").sortable({ opacity: 0.7, 
				revert: 0.2, 
				scroll: true , 
				update: function(event, ui) {
					that._updateSortOrderListIDFormElement();
				}
			});
			
			that._updateSortOrderListIDFormElement();
		}
		
		that.updateBundleFormState();
		
		return that;
	};
	
	
})(jQuery);