/* ----------------------------------------------------------------------
 * js/ca/ca.relationbundle.js
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2011 Whirl-i-Gig
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
	caUI.initRelationBundle = function(container, options) {
		if(options.overlay){
			// the hardcoded name of the overlayid, when change don't forget to change the css as well
			options.overlay.overlayid = 'onthefly_overlay';
			//	If there is no div with this id, create a new one, so we can use the same div for more elements
			if(!document.getElementById(options.overlay.overlayid)){
				jQuery('<div id="'+options.overlay.overlayid+'" class="overlay"><div class="close"></div><div id="'+options.overlay.overlayid+'_content">XXX</div></div>').appendTo('body');
			}
		}
		options.onInitializeItem = function(id, values, options) { 
			jQuery("#" + options.itemID + id + " select").css('display', 'inline');
			var i, typeList, types = [];
			
			var item_type_id = values['item_type_id'];
			
			// use type map to convert a child type id to the parent type id used in the restriction
			if (options.relationshipTypes && options.relationshipTypes['_type_map'] && options.relationshipTypes['_type_map'][item_type_id]) { item_type_id = options.relationshipTypes['_type_map'][item_type_id]; }
			
			if (options.relationshipTypes && (typeList = options.relationshipTypes[item_type_id])) {
				for(i=0; i < typeList.length; i++) {
					types.push({type_id: typeList[i].type_id, typename: typeList[i].typename, direction: typeList[i].direction});
				}
			} 
			
			// look for null
			if (options.relationshipTypes && (typeList = options.relationshipTypes['NULL'])) {
				for(i=0; i < typeList.length; i++) {
					types.push({type_id: typeList[i].type_id, typename: typeList[i].typename, direction: typeList[i].direction});
				}
			}
			jQuery('#' + options.itemID + id + ' select[name=' + options.fieldNamePrefix + 'type_id' + id + ']').data('item_type_id', item_type_id);

			jQuery.each(types, function (i, t) {
				var type_direction = (t.direction) ? t.direction+ "_" : '';
				jQuery('#' + options.itemID + id + ' select[name=' + options.fieldNamePrefix + 'type_id' + id + ']').append("<option value='" + type_direction + t.type_id + "'>" +  t.typename + "</option>");
			});
			
			var direction = (values['direction']) ? values['direction'] + "_" : '';
			if (jQuery('#' + options.itemID + id + ' select option[value=' + direction + values['relationship_type_id'] + ']').attr('selected', '1').length  == 0) {
				jQuery('#' + options.itemID + id + ' select option[value=' + values['relationship_type_id'] + ']').attr('selected', '1');
			}
		}
		
		options.onAddItem = function(id, options) {
			if(options.overlay){
				addNewOverlay(id, options);
			}
			jQuery('#' + options.itemID + id + ' #' + options.fieldNamePrefix + 'autocomplete' + id).autocomplete(options.autocompleteUrl, 
				{ minChars: ((parseInt(options.minChars) > 0) ? options.minChars : 3), matchSubset: 1, matchContains: 1, delay: 800, scroll: true, max: 100,
					formatResult: function(data, value) {
						return jQuery.trim(value.replace(/<\/?[^>]+>/gi, ''));
					}
				}
			);
			
			jQuery('#' + options.itemID + id + ' #' + options.fieldNamePrefix + 'autocomplete' + id).result(function(event, data, formatted) {
				var item_id = data[1];
				var type_id = data[2];
				showRelations(options,id,item_id,type_id);
				that.showUnsavedChangesWarning(true);
			});
		}


		var that = caUI.initBundle(container, options);

		return that;
	};
})(jQuery);

	/**
	 * To use addNewOverlay u need to specify an overlay property
	 * 	overlay needs #overlayurl, #inputid, #newtext, #placelink, #textfieldname, #idfieldname
	 * 	the #overlayid with the #overlaycontentid in it, is automatically created in the init function
	 *
	 * When a key is typed in the given #inputid, a link appears in the #linkid element with the given #newtext
	 * Clicking the link will fill the #overlaycontentid element with the html generated in #overlayurl and
	 * the #overlayid is shown with the given effects in the createOverlayEvent()-function
	 *
	 */

	var overlayoldnr = null;
	function addNewOverlay(id, options){
		var inputfieldid = options.fieldNamePrefix + options.overlay.inputid + id;
		if(jQuery("#"+inputfieldid).parents(".overlay").length <= 0) {
			jQuery("#"+inputfieldid).keydown(function() {
				if(overlayoldnr != id){
					if(overlayoldnr != null){
						jQuery("#" + options.fieldNamePrefix + options.overlay.linkid + overlayoldnr).fadeOut(500);
					}
					jQuery("#" + options.fieldNamePrefix + options.overlay.linkid + id).fadeIn(500);
					createOverlayEvent(id,options);
					overlayoldnr=id;
				}
			});
		}
	}

	/**
	 * Sets the overlay event on the first link in the specified element with #linkid
	 * The returned data will be filtered after append
	 * This is the only time we can see the type_id, so we remember it in the options array for later use
	 *
	 * more configurations can be done here about the overlay
	 */
	function createOverlayEvent(id,options) {
		jQuery("#" + options.fieldNamePrefix + options.overlay.linkid + id).html("<a href='#' rel='#"+options.overlay.overlayid+"'>"+options.overlay.newtext+"</a>");
                jQuery("#" + options.fieldNamePrefix + options.overlay.linkid + id + " :first").first('a').overlay({
            speed: 200,
            top: 20,
            left: "center",
            absolute: false,
            effect: 'apple',
            closeOnClick: true,
            closeOnEsc: true,
            onBeforeLoad: function(event) {
            	jQuery('#'+options.overlay.overlayid+'_content').empty();
            },
            onLoad: function(event) {
            	if(options.overlay.availableTypes && options.overlay.availableTypes.length == 1){
	            	jQuery.ajax({
						url: options.overlay.overlayurl+options.overlay.availableTypes[0]['item_id'],
						success: function(data){
	            			jQuery("#"+options.overlay.overlayid+'_content').append(data);
	            			options.typeid = jQuery("#"+options.overlay.overlayid+"_content form input[name=type_id]").val();
	            			jQuery('#'+options.overlay.overlayid+'_content').ready(function(){
	            				filterOverlayResponse(options);
	            			});
	        	        }
	            	});
            	} else {
            		var available_types="<ul id='choosetype'>";
            		for ( var i in options.overlay.availableTypes )
            		{
            			available_types+="<li><span><a onclick='return false;' href='"+options.overlay.overlayurl+options.overlay.availableTypes[i]['item_id']+"' >"+options.overlay.availableTypes[i]['name_singular']+"</a></span></li>";
            		}
            		available_types+="</ul>";
            		jQuery("#"+options.overlay.overlayid + '_content').html(available_types);
            		jQuery("#"+options.overlay.overlayid + '_content #choosetype li a').each(function(){
            			jQuery(this).click(function(){
                			jQuery(this).parent().attr("class","loading");
                			jQuery.ajax({
                				url: jQuery(this).attr('href'),
                				success: function(data){
                	    			jQuery("#"+options.overlay.overlayid + '_content').html(data);
                	    			options.typeid = jQuery("#"+options.overlay.overlayid+"_content form input[name=type_id]").val();
                	    			jQuery('#'+options.overlay.overlayid + '_content' ).ready(function(){
                	    				filterOverlayResponse(options);
                	    			});
                		        }
                	    	});
            			});
            		});
            	}
           	}
    	});

		/**
		 *
		 * 	Removes Cancel and Edit button
		 * 	removes the submitaction of the form
		 * 	changes the save action to submitting the form with an ajax request
		 * 		when save passed also update the given data:
		 * 			#inputid from #textfieldname and the hidden id from #idfieldname
		 * 			Remark: #idfieldname has to be the exact name, #textfieldname can be just a part of the name
		 *
		 * 		we also added a loading image for usability, if the request takes some time
		 */
		function filterOverlayResponse(options){
			var inputfieldid = options.fieldNamePrefix + options.overlay.inputid + id;
			var btnSave = jQuery('#'+options.overlay.overlayid+"_content div.control-box-left-content :nth-child(1)");
			var frmForm = jQuery("#"+options.overlay.overlayid+"_content form");
			var btnCancel = jQuery('#'+options.overlay.overlayid+"_content div.control-box-left-content :nth-child(3)");
			var btnDel = jQuery('#'+options.overlay.overlayid+"_content div.control-box-right-content");
			jQuery(btnCancel).remove();
			jQuery(btnDel).empty();
			jQuery(btnSave).bind("dblclick", function(){
				return false;
			});
			jQuery(frmForm).submit(function(){return false;});
			jQuery(btnSave).bind("click", function(){
				jQuery(btnSave).css("visibility","hidden");
				jQuery('#'+options.overlay.overlayid+"_content div.control-box-left-content").html("<span class='loading'></span>");
				jQuery.post(
					jQuery(frmForm).attr("action"),
					jQuery(frmForm).serialize(),
					function(result){
						jQuery("#"+options.overlay.overlayid+'_content').empty();
						jQuery("#"+options.overlay.overlayid+'_content').html(result);
						filterOverlayResponse(options);
						var item_id = jQuery("#"+options.overlay.overlayid+"_content form input[name="+options.overlay.idfieldname+"]").val();
						if(item_id != ""){
							var displayfield = jQuery("#"+options.overlay.overlayid+"_content form input[name*="+options.overlay.textfieldname+"]");
							if(displayfield.length == 0) {
								displayfield = jQuery("#"+options.overlay.overlayid+"_content form textarea[name*="+options.overlay.textfieldname+"]");
							}
							var displayname =  displayfield.val();
							jQuery('#'+options.fieldNamePrefix+'id'+id).val(item_id);
							jQuery('#'+options.fieldNamePrefix+'edit_related_'+id).remove();
							jQuery('#'+inputfieldid).val(displayname);
							showRelations(options, id, item_id, options.typeid);
						}
    					return false;
					}
				);
				return false;
			});
			if (!jQuery.browser.msie){
				jQuery('#'+options.overlay.overlayid+'_content .rounded').corner('round 8px');
			}
		}
	}


	/**
	 *
	 * refactored function because we might need this after an overlay to
	 *
	 */
	function showRelations(options,id,item_id,type_id){
		jQuery('#' + options.itemID + id + ' #' + options.fieldNamePrefix + 'id' + id).val(item_id);
		jQuery('#' + options.itemID + id + ' #' + options.fieldNamePrefix + 'type_id' + id).css('display', 'inline');
		var i, typeList, types = [];

		var default_index = 0;

		if (jQuery('#' + options.itemID + id + ' select[name=' + options.fieldNamePrefix + 'type_id' + id + ']').data('item_type_id') == type_id) {
			// noop - don't change relationship types unless you have to
		} else {
			if (options.relationshipTypes && (typeList = options.relationshipTypes[type_id])) {
				for(i=0; i < typeList.length; i++) {
					types.push({type_id: typeList[i].type_id, typename: typeList[i].typename, direction: typeList[i].direction});

					if (typeList[i].is_default === '1') {
						default_index = (types.length - 1);
					}
				}
			}
			// look for null (these are unrestricted and therefore always displayed)
			if (options.relationshipTypes && (typeList = options.relationshipTypes['NULL'])) {
				for(i=0; i < typeList.length; i++) {
					types.push({type_id: typeList[i].type_id, typename: typeList[i].typename, direction: typeList[i].direction});

					if (typeList[i].is_default === '1') {
						default_index = (types.length - 1);
					}
				}
			}

			jQuery('#' + options.itemID + id + ' select[name=' + options.fieldNamePrefix + 'type_id' + id + '] option').remove();	// clear existing options
			jQuery.each(types, function (i, t) {
				var type_direction = (t.direction) ? t.direction+ "_" : '';
				jQuery('#' + options.itemID + id + ' select[name=' + options.fieldNamePrefix + 'type_id' + id + ']').append("<option value='" + type_direction + t.type_id + "'>" + t.typename + "</option>");
			});

			// select default
			jQuery('#' + options.itemID + id + ' select[name=' + options.fieldNamePrefix + 'type_id' + id + ']').attr('selectedIndex', default_index);

			// set current type
			jQuery('#' + options.itemID + id + ' select[name=' + options.fieldNamePrefix + 'type_id' + id + ']').data('item_type_id', type_id);
		}
	}
