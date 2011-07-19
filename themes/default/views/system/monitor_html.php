<?php
/** ---------------------------------------------------------------------
 * themes/default/views/system/monitor_html.php : view for performance monitor output
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2011 Whirl-i-Gig
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
 * @package CollectiveAccess_Default_Theme
 * @subpackage system
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 * 
 * ----------------------------------------------------------------------
 */
 
 /**
   *
   */
   
	if (!Db::$monitor) { return; }
	
	$va_log = Db::$monitor->getLogOutput();
?>
	<div style="display: none; overflow: auto; width: 900px; height: 400px; position: fixed; top: 50px; left: 50px; background: #ffffff; padding: 15px;" id="caApplicationMonitor">
<?php
	if (is_array($va_log['queries']) && ($vn_num_queries = sizeof($va_log['queries']))) {
?>
		<h1>Logged <?php print $vn_num_queries; ?> queries</h1>
		<table>
			<tr><th width="620">Query</th><th width="120">Parameters</th><th width="30">Execution time</th><th width="30">Hits</th></tr>
<?php
		foreach($va_log['queries'] as $vn_i => $va_query) {
			$vs_params = '';
			if(is_array($va_query['params'])) {
				foreach($va_query['params'] as $vn_i => $vs_val) {
					$vs_params .= "Param ".($vn_i + 1)." = ".print_r($vs_val, true)."<br/>\n";
				}
			}
			print "<tr>";
			print "<td>".str_replace(',', ', ',$va_query['query'])."</td><td>{$vs_params}</td><td>{$va_query['time']}s</td><td>{$va_query['hits']}</td>";
			print "</tr>\n";
			print "<tr><td colspan='4'><hr/></td></tr>\n";
		}
?>
		</table>
<?php
	} else {
		print "No queries met logging requirements";
	}
?>
	</div>