<div><div id="topNavContainer">
	<div id="topNav">
		<div class="roundedNav">
			<div id="logo" onclick='document.location="<?php print $this->request->getBaseUrlPath().'/'; ?>";'><?php print "<img src='".$this->request->getThemeUrlPath()."/graphics/logos/ca_wide.png' border='0' alt='"._t("Search")."'/>" ?></div>
	
<?php
		if ($this->request->isLoggedIn()) {
			if ($this->request->user->canDoAction('can_quicksearch')) {
?>
				<div class="sf-menu sf-menu-search">
					
					<!-- Quick search -->
<?php 					print caFormTag($this->request, 'Index', 'caQuickSearchForm', 'find/QuickSearch', 'post', 'multipart/form-data', '_top', array('disableUnsavedChangesWarning' => true)); 
							if ($this->request->isLoggedIn() && ($this->request->user->getPreference('clear_quicksearch') == 'auto_clear')) { 
?>
							<input type="text" name="search" length="15" id="caQuickSearchFormText" value="<?php print htmlspecialchars($this->request->session->getVar('quick_search_last_search'), ENT_QUOTES, 'UTF-8'); ?>" onfocus="this.value='';"/>
<?php						
							} else {
?>
							<input type="text" name="search" length="15" id="caQuickSearchFormText" value="<?php print htmlspecialchars($this->request->session->getVar('quick_search_last_search'), ENT_QUOTES, 'UTF-8'); ?>" onfocus="<?php print htmlspecialchars($this->request->session->getVar('quick_search_last_search'), ENT_QUOTES, 'UTF-8'); ?>"/>	
<?php
							}
							print caFormSubmitLink($this->request, "<img src='".$this->request->getThemeUrlPath()."/graphics/buttons/glass.gif' border='0' style='float:right; margin:4px 5px 0px 0px;' alt='"._t("Search")."'/>", 'caQuickSearchFormSubmit', 'caQuickSearchForm'); ?>
						</form>
				</div>
<?php
			}
?>
			<ul class="sf-menu">
	<?php
			print $va_menu_bar = $this->getVar('nav')->getHTMLMenuBar('menuBar', $this->request);
?>
			</ul>
	<?php
		}
?>	
		</div><!-- END roundedNav -->
		<div style="clear:both;"><!--EMPTY--></div>
	</div><!-- END topNav -->
</div><!-- END topNavContainer --></div>
<div id="main" class="width">