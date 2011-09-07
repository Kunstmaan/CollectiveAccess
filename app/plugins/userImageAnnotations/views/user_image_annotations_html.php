<?php
	$is_moderated = $this->getVar('moderated') ? $this->getVar('moderated')  : false;
 	$t_annotations = $this->getVar('t_user_annotations');
	$va_annotations_list = $this->getVar('annotations_list');
	if(sizeof($va_annotations_list) > 0):
?>
		<style type="text/css" media="all">@import "<?php echo $this->request->getThemeUrlPath(); ?>/css/annotation.css";</style>
		<script language="JavaScript" type="text/javascript">
		/* <![CDATA[ */
			jQuery(document).ready(function(){
				jQuery('#caAnnotationsList').caFormatListTable();
			});
		/* ]]> */
		</script>
		<div class="sectionBox">
<?php
				print caFormControlBox(
					'<div class="list-filter">Filter: <input type="text" name="filter" value="" onkeyup="$(\'#caAnnotationsList\').caFilterTable(this.value); return false;" size="20"/></div>',
					'',
					'' //caNavHeaderButton($this->request, __CA_NAV_BUTTON_HELP__, _t("Help"), 'manage/comments', 'commentList', 'Edit', array('comment_id' => 0))
				);
?>
			<form id="annotationListForm"><input type="hidden" name="mode" value="list">

			<div style="text-align:right;">
				<?php if(!$is_moderated): ?>
					<?php print _t('Batch actions'); ?>: <a href='#' onclick='jQuery("#annotationListForm").attr("action", "<?php print caNavUrl($this->request, 'userImageAnnotations', 'Annotations', 'Approve'); ?>").submit();' class='form-button'><span class='form-button'>Approve</span></a>
					<a href='#' onclick='jQuery("#annotationListForm").attr("action", "<?php print caNavUrl($this->request, 'userImageAnnotations', 'Annotations', 'Delete'); ?>").submit();' class='form-button'><span class='form-button'>Delete</span></a>
				<?php endif;?>
			</div>
			<table id="caAnnotationsList" class="listtable" width="100%" border="0" cellpadding="0" cellspacing="1">
				<thead>
					<tr>
						<th class="list-header-unsorted">
							<?php print _t('Author'); ?>
						</th>
						<th class="list-header-unsorted">
							<?php print _t('Annotation'); ?>
						</th>
						<th class="list-header-unsorted">
							<?php print _t('Date'); ?>
						</th>
						<th class="list-header-unsorted">
							<?php print _t('Object'); ?>
						</th>
						<?php if(!$is_moderated): ?>
							<th class="{sorter: false} list-header-nosort list-header-unsorted">
								<?php print _t('Annotated On'); ?>
							</th>
							<th class="{sorter: false} list-header-nosort"><?php print _t('Select'); ?></th>
						<?php else: ?>
							<th class="{sorter: false} list-header-nosort list-header-unsorted"><?php print _t('Edit'); ?></th>
						<?php endif; ?>
					</tr>
				</thead>
				<tbody>
<?php
			$c = 0;
			foreach($va_annotations_list as $va_annotation):
?>
					<tr>
						<td>
<?php
							if($va_annotation['user_id']){
								print utf8_decode($va_annotation['fname'])." ".utf8_decode($va_annotation['lname'])."<br/>".utf8_decode($va_annotation['user_email']);
							}else{
								print utf8_decode($va_annotation['name'])."<br/>".utf8_decode($va_annotation['user_email']);
							}
?>
						</td>
						<td>
							<?php print utf8_decode($va_annotation['annotation']); ?>
						</td>
						<td>
							<?php print $va_annotation['created_on']; ?>
						</td>
						<td>
							<?php
								echo $va_annotation['annotated_object'];
							?>
						</td>
						<?php if(!$is_moderated): ?>
							<td>
								<?php
									$representation = $va_annotation['annotated_on'];
									if($representation):
										$media_info = $representation->get('media');
										$possible_mimes = array("image/jpeg", "image/gif", "image/tiff", "image/png", "image/x-bmp", "image/x-psd", "image/tilepic", "image/x-dcraw", "image/x-dpx", "image/x-exr", "image/jp2", "image/x-adobe-dng");
										if(in_array($media_info['original']["MIMETYPE"], $possible_mimes)):

											$original_width = $media_info["original"]["PROPERTIES"]["width"];
											$original_height = $media_info["original"]["PROPERTIES"]["height"];

											$viewer_width = $media_info["preview"]["PROPERTIES"]["width"];
											$viewer_height = $media_info["preview"]["PROPERTIES"]["height"];

											$h_factor = $original_width / $viewer_width;
											$v_factor = $original_height / $viewer_height;

											$top = $va_annotation['original_top'] / $v_factor;
											$left = $va_annotation['original_left'] / $h_factor;
											$width = $va_annotation['original_width'] / $h_factor;
											$height = $va_annotation['original_height'] / $v_factor;
											$text = $va_annotation['annotation'];
											$text = preg_replace("/[\n\r]/","",nl2br($text));

											$name = 'representation_'.$representation->getPrimaryKey().'_'.$c;
											$c++;

											echo "<img src='".$representation->getMediaUrl('media', 'preview', null)."' width='$viewer_width' height='$viewer_height' id='$name' />";
											// we can't set the id of the img tag when doing it like this:
											// echo $representation->getMediaTag('media', 'preview', $name);
											//$this->getMediaUrl($field, $version, isset($options["page"]) ? $options["page"] : null);
								?>
									<script language="javascript">
										jQuery(window).load(function() {
											jQuery("#<?php echo $name; ?>").annotateImage({
												useAjax: false,
												editable: false,
												notes: [ {	"top": <?php echo $top; ?>,
															"left": <?php echo $left; ?>,
															"width": <?php echo $width; ?>,
															"height": <?php echo $height; ?>,
															"text": "<?php echo $text; ?>",
															"editable": false
														} ]

											});
										});
									</script>
								<?php 	endif;
									endif; ?>
							</td>
							<td>
								<input type="checkbox" name="annotation_id[]" value="<?php echo $va_annotation['user_annotation_id']; ?>">
							</td>
						<?php else: ?>
							<td>
								<?php
									$representation = $va_annotation['annotated_on'];
									if(isset($representation)) {
										print urldecode(caNavLink($this->request, caNavIcon($this->request, __CA_NAV_BUTTON_EDIT__), '', 'editor/object_representations', 'ObjectRepresentationEditor', 'Edit/Screen68', array('representation_id' => $representation->getPrimaryKey())));
									}
								?>
							</td>
						<?php endif; ?>
					</tr>
<?php
			endforeach;
?>
				</tbody>
			</table></form>
		</div><!-- end sectionBox -->
<?php
	endif;
?>