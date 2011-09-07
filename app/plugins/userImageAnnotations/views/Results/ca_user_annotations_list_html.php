<?php
/* ----------------------------------------------------------------------
 * themes/default/views/manage/ca_user_annotations_list_html.php :
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
 	$vo_result = $this->getVar('result');
	$vn_items_per_page = $this->getVar('current_items_per_page');

?>
	<div id="annotationsResults">
		<form id="commentListForm"><input type="hidden" name="mode" value="search">

		<div style="text-align:right;">
			<?php print _t('Batch actions'); ?>: <a href='#' onclick='jQuery("#annotationListForm").attr("action", "<?php print caNavUrl($this->request, 'userImageAnnotations', 'Annotations', 'Approve'); ?>").submit();' class='form-button'><span class='form-button'>Approve</span></a>
				<a href='#' onclick='jQuery("#annotationListForm").attr("action", "<?php print caNavUrl($this->request, 'userImageAnnotations', 'Annotations', 'Delete'); ?>").submit();' class='form-button'><span class='form-button'>Delete</span></a>
		</div>
		<table id="caAnnotationsList" class="listtable" width="100%" border="0" cellpadding="0" cellspacing="1" style="margin-top:3px;">
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
					<th class="list-header-unsorted">
						<?php print _t('Annotated On'); ?>
					</th>
				</tr>
			</thead>
			<tbody>

<?php
		$i = 0;
		$vn_item_count = 0;
		$o_tep = new TimeExpressionParser();
		$o_datamodel = Datamodel::load();
		while(($vn_item_count < $vn_items_per_page) && $vo_result->nextHit()) {
			if (!$t_table = $o_datamodel->getInstanceByTableNum($vo_result->get('table_num'))) {
				continue;
			}
?>
				<tr>
					<td>
<?php
						if($vo_result->get('user_id')){
							print $vo_result->get('ca_users.fname')." ".$vo_result->get('ca_users.lname')."<br/>".$vo_result->get('ca_users.email');
						}else{
							print $vo_result->get('name')."<br/>".$vo_result->get('user_email');
						}
?>
					</td>
					<td>
						<?php print utf8_decode($vo_result->get('comment')); ?>
					</td>
					<td>
						<?php
							$o_tep->setUnixTimestamps($vo_result->get('created_on'), $vo_result->get('created_on'));
							print $o_tep->getText();
						?>
					</td>
					<td>
						<?php
							$t_representation = new ca_object_representations();
							if ($t_representation->load($vo_result->get('row_id'))) {

								$qr_res2 = $o_db->query("
									SELECT object_id
									FROM ca_objects_x_object_representations
									WHERE
										representation_id = ?
								", $t_representation->getPrimaryKey());

								if($qr_res2->nextRow()) {
									$t_object = new ca_objects();
									if($t_object->load($qr_res2->get('object_id'))) {
										echo $t_object->getLabelForDisplay(false).' ['.$t_object->get('idno').']';
									}
								}
							}
						?>
					</td>
					<td></td>
				</tr>
<?php
			$i++;
			$vn_item_count++;
		}
?>
			</tbody>
		</table></form>
	</div><!--end annotationResults -->