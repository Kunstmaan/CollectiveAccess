/*** GENERAL CODE ***/
	function initializeOnTheFlyOverlay(overlayoptions){
		// the hardcoded name of the overlayid, better not change this (related with css and attibuteValues)
		if(!overlayoptions.overlayid || overlayoptions.overlay == ""){
			overlayoptions.overlayid = 'onthefly_overlay';
		}
		if(!document.getElementById(overlayoptions.overlayid)){
			//	If there is no div with this id, create a new one, so we can use the same div for more elements
			jQuery('<div id="'+overlayoptions.overlayid+'" class="overlay"><div class="close"></div><div id="'+overlayoptions.overlayid+'_content"></div></div>').appendTo('body');				
		}
	}


	/**
	 * To use addNewOverlay u need to specify an overlay property
	 * 	overlay needs #contenturl, #inputid, #linktext, #placelink, #textfieldname, #idfieldname
	 * 	the #overlayid with the #overlaycontentid in it, is automatically created in the init function
	 * 
	 * When a key is typed in the given #inputid, a link appears in the #placelinkid element with the given #linktext
	 * Clicking the link will fill the #overlaycontentid element with the html generated in #contenturl and
	 * the #overlayid is shown with the given effects in the createOverlayEvent()-function
	 * 
	 */
	// TODO: this can cause conflicts with other quickadd fields
	var overlayoldnumber = null;
	function addNewOnTheFlyOverlay(id, overlayoptions){
		var inputfieldid = overlayoptions.fieldNamePrefix + overlayoptions.inputid + id;
		if(jQuery("#"+inputfieldid).parents(".overlay").length <= 0) { 
			jQuery("#"+inputfieldid).keydown(function() {
				if(overlayoldnumber != id){
					if(overlayoldnumber != null){
						jQuery("#" + overlayoptions.fieldNamePrefix + overlayoptions.linkid + overlayoldnumber).fadeOut(500);					
					}
					jQuery("#" + overlayoptions.fieldNamePrefix + overlayoptions.linkid + id).fadeIn(500);
					createOnTheFlyOverlayEvent(id,overlayoptions);
					overlayoldnumber = id;
				}
			});
		}
	}
	
	/**
	 * Sets the overlay event on the first link in the specified element with #placelinkid
	 * The returned data will be filtered after append
	 * This is the only time we can see the type_id, so we remember it in the overlayoptions array for later use
	 * 
	 * more configurations can be done here about the overlay
	 */
	function createOnTheFlyOverlayEvent(id,overlayoptions) {
   		jQuery("#" + overlayoptions.fieldNamePrefix + overlayoptions.linkid + id).html("<a href='#' rel='#"+overlayoptions.overlayid+"'>"+overlayoptions.newtext+"</a>");
		jQuery("#" + overlayoptions.fieldNamePrefix + overlayoptions.linkid + id + " a").overlay({
            speed: 200,
            top: 20,
            left: "center",
            absolute: false,
            effect: 'apple',
            closeOnClick: true,
            closeOnEsc: true,
            onBeforeLoad: function(event) {
            	jQuery('#'+overlayoptions.overlayid+'_content').empty();
            },
            onLoad: function(event) {
            	if(overlayoptions.availableTypes && overlayoptions.availableTypes.length == 1){
                	jQuery.ajax({
    					url: overlayoptions.overlayurl+overlayoptions.availableTypes[0]['item_id'],
    					success: function(data){
                			jQuery("#"+overlayoptions.overlayid + '_content').append(data);
                			overlayoptions.typeid = jQuery("#"+overlayoptions.overlayid + '_content' +" form input[name=type_id]").val();
                			jQuery('#'+overlayoptions.overlayid + '_content' ).ready(function(){
                				filterOnTheFlyOverlayResponse(overlayoptions);
                			});
            	        }
                	});
            	}
            	else{
            		var available_types="<ul id='choosetype'>";
            		for ( var i in overlayoptions.availableTypes )
            		{
            			available_types+="<li><span><a onclick='return false;' href='"+overlayoptions.overlayurl+overlayoptions.availableTypes[i]['item_id']+"' >"+overlayoptions.availableTypes[i]['name_singular']+"</a></span></li>";
            		}
            		available_types+="</ul>";
            		jQuery("#"+overlayoptions.overlayid + '_content').html(available_types);
            		jQuery("#"+overlayoptions.overlayid + '_content #choosetype li a').each(function(){
            			jQuery(this).click(function(){
                			jQuery(this).parent().attr("class","loading");
                			jQuery.ajax({
                				url: jQuery(this).attr('href'),
                				success: function(data){
                	    			jQuery("#"+overlayoptions.overlayid + '_content').html(data);
                	    			overlayoptions.typeid = jQuery("#"+overlayoptions.overlayid + '_content' +" form input[name=type_id]").val();
                	    			jQuery('#'+overlayoptions.overlayid + '_content' ).ready(function(){
                	    				filterOnTheFlyOverlayResponse(overlayoptions);
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
		function filterOnTheFlyOverlayResponse(overlayoptions){
			var inputfieldid = overlayoptions.fieldNamePrefix + overlayoptions.inputid + id;
			var btnSave = jQuery('#'+overlayoptions.overlayid + '_content' +" div.control-box-left-content :nth-child(1)");
			var frmForm = jQuery("#"+overlayoptions.overlayid + '_content' +" form");
			var btnCancel = jQuery('#'+overlayoptions.overlayid + '_content' +" div.control-box-left-content :nth-child(3)");
			var btnDel = jQuery('#'+overlayoptions.overlayid + '_content' +" div.control-box-right-content");
			jQuery(btnCancel).remove();
			jQuery(btnDel).empty();
			jQuery(btnSave).bind("dblclick", function(){
				return false;
			});
			jQuery(frmForm).submit(function(){return false;});
			jQuery(btnSave).bind("click", function(){
				jQuery(btnSave).css("visibility","hidden");
				jQuery('#'+overlayoptions.overlayid + '_content' +" div.control-box-left-content").html("<span class='loading'></span>");
				jQuery.post(
					jQuery(frmForm).attr("action"),
					jQuery(frmForm).serialize(),
					function(result){
						jQuery("#"+overlayoptions.overlayid + '_content' ).empty();
						jQuery("#"+overlayoptions.overlayid + '_content' ).html(result);
						filterOnTheFlyOverlayResponse(overlayoptions);
						var item_id = jQuery("#"+overlayoptions.overlayid + '_content' +" form input[name="+overlayoptions.idfieldname+"]").val();
						if(item_id != ""){
							var displayname = jQuery("#"+overlayoptions.overlayid + '_content' +" form input[name*="+overlayoptions.textfieldname+"]").val();
							jQuery('#'+inputfieldid).val(displayname);
							jQuery('#'+inputfieldid).next().val(displayname + "|" + item_id);
						}
    					return false;
					}
				);
				return false;
			});
			if (!jQuery.browser.msie){
				jQuery('#'+overlayoptions.overlayid + '_content' +' .rounded').corner('round 8px'); 
			}
		}
	}