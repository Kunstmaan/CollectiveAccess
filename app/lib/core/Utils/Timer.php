<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Utils/Timer.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2003 Whirl-i-Gig
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
 * @package CollectiveAccess
 * @subpackage Utils
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
 /**
  *
  */

	class Timer {
		var $start;
		
		function Timer ($start_timer = 1) {
			if ($start_timer) {
				$this->startTimer();
			}
			return;
		}
		
		function startTimer ()  {
			$this->start = microtime();
			return true;
		}
		
		function getTime ($decimals=2) {
			// $decimals will set the number of decimals you want for your milliseconds.
			
			// format start time
			$start_time = explode (" ", $this->start);
			$start_time = $start_time[1] + $start_time[0];
			// get and format end time
			$end_time = explode (" ", microtime());
			$end_time = $end_time[1] + $end_time[0];
			return number_format ($end_time - $start_time, $decimals);
		}
	}
# ----------------------------------------------------------------------
?>