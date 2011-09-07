<?php
	$comment = $this->getVar('comment');
 ?>
<div class="control-box rounded">
	<div class="control-box-left-content">
		<a href='#' onclick='jQuery("#CommentForm").submit();' class='form-button'>
			<span class='form-button'>
				<img src='/themes/waasland/graphics/buttons/save.gif' border='0' alt='Bewaren' class='form-button-left' style='padding-right: 10px;'/> Bewaren
			</span>
		</a>
		<div style='position: absolute; top: 0px; left:-500px;'><input type='submit'/></div>
		<a href='<?php echo caNavUrl($this->request, 'manage', 'Comments', 'Index'); ?>' class='form-button'>
			<span class='form-button '>
				<img src='/themes/waasland/graphics/buttons/cancel.gif' border='0' title='Annuleren' alt='Annuleren'  class='form-button-left' style='padding-right: 10px;'/> Annuleren
			</span>
		</a>
	</div>
</div>
<div class="clear"><!--empty--></div>

<form action='<?php echo caNavUrl($this->request, 'manage', 'Comments', 'Edit'); ?>' method='post' id='CommentForm' target='_top' enctype='multipart/form-data'>
	<input type="hidden" name="comment_submit" id="comment_submit" value="true" />
	<input type="hidden" name="comment_id" value="<?php echo $comment->getPrimaryKey(); ?>" />
	<div class='formLabel'>
		<span id="_ca_item_comments__name">Name</span><br/>
		<input name="name" type="text" size="50" value="<?php echo $comment->get('name'); ?>"  id='name'/>
	</div>
	<div class='formLabel'>
		<span id="_ca_item_comments__email">Email</span><br/>
		<input name="email" type="text" size="50" value="<?php echo $comment->get('email'); ?>"  id='email'/>
	</div>
	<div class='formLabel'>
		<span id="_ca_item_comments__comment">Comment</span><br/>
		<textarea name="comment" type="text" id='comment' maxlength="65535" cols="70" rows="4"><?php echo $comment->get('comment'); ?></textarea>
	</div>
</form>

<div class="control-box rounded">
	<div class="control-box-left-content">
		<a href='#' onclick='jQuery("#CommentForm").submit();' class='form-button'>
			<span class='form-button'>
				<img src='/themes/waasland/graphics/buttons/save.gif' border='0' alt='Bewaren' class='form-button-left' style='padding-right: 10px;'/> Bewaren
			</span>
		</a>
		<div style='position: absolute; top: 0px; left:-500px;'><input type='submit'/></div>
		<a href='<?php echo caNavUrl($this->request, 'manage', 'Comments', 'Index'); ?>' class='form-button'>
			<span class='form-button '>
				<img src='/themes/waasland/graphics/buttons/cancel.gif' border='0' title='Annuleren' alt='Annuleren'  class='form-button-left' style='padding-right: 10px;'/> Annuleren
			</span>
		</a>
	</div>
</div>