<?php
	require_once('PHPUnit/Framework.php');
	require_once('../../../../../setup.php');
	require_once(__CA_LIB_DIR__.'/core/Parsers/TimeExpressionParser.php');
	
	class TimeExpressionParserTests extends PHPUnit_Framework_TestCase {
		public function testParseSimpleDelimitedDateForEnglishLocale() {
			$o_tep = new TimeExpressionParser();
			$o_tep->setLanguage('en_US');
			$vb_res = $o_tep->parse('10/23/2004');
			$this->assertEquals($vb_res, true);
			$va_parse = $o_tep->getUnixTimestamps();
			$this->assertEquals($va_parse['start'], 1098504000);
			$this->assertEquals($va_parse['end'], 1098590399);
			$this->assertEquals($va_parse[0], 1098504000);
			$this->assertEquals($va_parse[1], 1098590399);
		}
		
		public function testParseSimpleDelimitedDateForEuropeanLocale() {
			$o_tep = new TimeExpressionParser();
			$o_tep->setLanguage('fr_FR');
			$vb_res = $o_tep->parse('23/10/2004');
			$this->assertEquals($vb_res, true);
			$va_parse = $o_tep->getUnixTimestamps();
			
			$this->assertEquals($va_parse['start'], 1098504000);
			$this->assertEquals($va_parse['end'], 1098590399);
			$this->assertEquals($va_parse[0], 1098504000);
			$this->assertEquals($va_parse[1], 1098590399);
		}
		
		public function testParseTextDate() {
			$o_tep = new TimeExpressionParser();
			$o_tep->setLanguage('en_US');
			$vb_res = $o_tep->parse('May 10, 1990');
			$this->assertEquals($vb_res, true);
			$va_parse = $o_tep->getUnixTimestamps();
			
			$this->assertEquals($va_parse['start'], 642312000);
			$this->assertEquals($va_parse['end'], 642398399);
			$this->assertEquals($va_parse[0], 642312000);
			$this->assertEquals($va_parse[1], 642398399);
			
			$vb_res = $o_tep->parse('10 May 1990');
			$this->assertEquals($vb_res, true);
			$va_parse = $o_tep->getUnixTimestamps();
			
			$this->assertEquals($va_parse['start'], 642312000);
			$this->assertEquals($va_parse['end'], 642398399);
			$this->assertEquals($va_parse[0], 642312000);
			$this->assertEquals($va_parse[1], 642398399);
		}
		
		public function testParseISO8601Date() {
			$o_tep = new TimeExpressionParser();
			$vb_res = $o_tep->parse('2009-09-18 21:03');
			$this->assertEquals($vb_res, true);
			$va_parse = $o_tep->getUnixTimestamps();
			
			$this->assertEquals($va_parse['start'], 1253322180);
			$this->assertEquals($va_parse['end'], 1253322180);
			$this->assertEquals($va_parse[0], 1253322180);
			$this->assertEquals($va_parse[1], 1253322180);
		}
		
		public function testParseNowDate() {
			$o_tep = new TimeExpressionParser();
			$vb_res = $o_tep->parse('now');
			$this->assertEquals($vb_res, true);
			$va_parse = $o_tep->getUnixTimestamps();
			
			$this->assertEquals($va_parse['start'], $t= time());
			$this->assertEquals($va_parse['end'], $t);
			$this->assertEquals($va_parse[0], $t);
			$this->assertEquals($va_parse[1], $t);
		}
		
		public function testHistoricDayDateForEnglishLocale() {
			$o_tep = new TimeExpressionParser();
			$o_tep->setLanguage('en_US');
			$vb_res = $o_tep->parse('March 8, 1945');
			$this->assertEquals($vb_res, true);
			$va_parse = $o_tep->getHistoricTimestamps();
			
			$this->assertEquals($va_parse['start'], 1945.030800000000);
			$this->assertEquals($va_parse['end'], 1945.030823595900);
			$this->assertEquals($va_parse[0], 1945.030800000000);
			$this->assertEquals($va_parse[1], 1945.030823595900);
		}
		
		public function testHistoricDayDateForGermanLocale() {
			$o_tep = new TimeExpressionParser();
			$o_tep->setLanguage('de_DE');
			$vb_res = $o_tep->parse('8. Mai 1945');
			$this->assertEquals($vb_res, true);
			$va_parse = $o_tep->getHistoricTimestamps();
		
			$this->assertEquals($va_parse['start'], 1945.050800000000);
			$this->assertEquals($va_parse['end'], 1945.050823595900);
			$this->assertEquals($va_parse[0], 1945.050800000000);
			$this->assertEquals($va_parse[1], 1945.050823595900);
		}
		
		public function testHistoricYearRanges() {
			$o_tep = new TimeExpressionParser();
			$vb_res = $o_tep->parse('1930 - 1946');
			$this->assertEquals($vb_res, true);
			$va_parse = $o_tep->getHistoricTimestamps();
		
			$this->assertEquals($va_parse['start'], 1930.010100000000);
			$this->assertEquals($va_parse['end'], 1946.123123595900);
			$this->assertEquals($va_parse[0], 1930.010100000000);
			$this->assertEquals($va_parse[1], 1946.123123595900);
			
			
			$vb_res = $o_tep->parse('1930-1946');
			$this->assertEquals($vb_res, true);
			$va_parse = $o_tep->getHistoricTimestamps();
		
			$this->assertEquals($va_parse['start'], 1930.010100000000);
			$this->assertEquals($va_parse['end'], 1946.123123595900);
			$this->assertEquals($va_parse[0], 1930.010100000000);
			$this->assertEquals($va_parse[1], 1946.123123595900);
			
			$vb_res = $o_tep->parse('1951 to 1955');
			$this->assertEquals($vb_res, true);
			$va_parse = $o_tep->getHistoricTimestamps();
		
			$this->assertEquals($va_parse['start'], 1951.010100000000);
			$this->assertEquals($va_parse['end'], 1955.123123595900);
			$this->assertEquals($va_parse[0], 1951.010100000000);
			$this->assertEquals($va_parse[1], 1955.123123595900);
			
			$vb_res = $o_tep->parse('Between 1951 and 1955');
			$this->assertEquals($vb_res, true);
			$va_parse = $o_tep->getHistoricTimestamps();
		
			$this->assertEquals($va_parse['start'], 1951.010100000000);
			$this->assertEquals($va_parse['end'], 1955.123123595900);
			$this->assertEquals($va_parse[0], 1951.010100000000);
			$this->assertEquals($va_parse[1], 1955.123123595900);
			
			$vb_res = $o_tep->parse('From 1951 to 1955');
			$this->assertEquals($vb_res, true);
			$va_parse = $o_tep->getHistoricTimestamps();
		
			$this->assertEquals($va_parse['start'], 1951.010100000000);
			$this->assertEquals($va_parse['end'], 1955.123123595900);
			$this->assertEquals($va_parse[0], 1951.010100000000);
			$this->assertEquals($va_parse[1], 1955.123123595900);
		}
		
		public function testHistoricCircaYearDate() {
			$o_tep = new TimeExpressionParser();
			$o_tep->setLanguage('en_US');
			
			$vb_res = $o_tep->parse('c 1946');
			$this->assertEquals($vb_res, true);
			$va_parse = $o_tep->getHistoricTimestamps();
		
			$this->assertEquals($va_parse['start'], 1946.010100000010);
			$this->assertEquals($va_parse['end'], 1946.123123595910);
			$this->assertEquals($va_parse[0], 1946.010100000010);
			$this->assertEquals($va_parse[1], 1946.123123595910);
			
			$vb_res = $o_tep->parse('c. 1946');
			$this->assertEquals($vb_res, true);
			$va_parse = $o_tep->getHistoricTimestamps();
		
			$this->assertEquals($va_parse['start'], 1946.010100000010);
			$this->assertEquals($va_parse['end'], 1946.123123595910);
			$this->assertEquals($va_parse[0], 1946.010100000010);
			$this->assertEquals($va_parse[1], 1946.123123595910);
			
			$vb_res = $o_tep->parse('circa 1946');
			$this->assertEquals($vb_res, true);
			$va_parse = $o_tep->getHistoricTimestamps();
		
			$this->assertEquals($va_parse['start'], 1946.010100000010);
			$this->assertEquals($va_parse['end'], 1946.123123595910);
			$this->assertEquals($va_parse[0], 1946.010100000010);
			$this->assertEquals($va_parse[1], 1946.123123595910);
			
			
			$vb_res = $o_tep->parse('ca 1946');
			$this->assertEquals($vb_res, true);
			$va_parse = $o_tep->getHistoricTimestamps();
		
			$this->assertEquals($va_parse['start'], 1946.010100000010);
			$this->assertEquals($va_parse['end'], 1946.123123595910);
			$this->assertEquals($va_parse[0], 1946.010100000010);
			$this->assertEquals($va_parse[1], 1946.123123595910);
			
			
			$vb_res = $o_tep->parse('ca. 1946');
			$this->assertEquals($vb_res, true);
			$va_parse = $o_tep->getHistoricTimestamps();
		
			$this->assertEquals($va_parse['start'], 1946.010100000010);
			$this->assertEquals($va_parse['end'], 1946.123123595910);
			$this->assertEquals($va_parse[0], 1946.010100000010);
			$this->assertEquals($va_parse[1], 1946.123123595910);
			
			
			$vb_res = $o_tep->parse('1946?');
			$this->assertEquals($vb_res, true);
			$va_parse = $o_tep->getHistoricTimestamps();
		
			$this->assertEquals($va_parse['start'], 1946.010100000010);
			$this->assertEquals($va_parse['end'], 1946.123123595910);
			$this->assertEquals($va_parse[0], 1946.010100000010);
			$this->assertEquals($va_parse[1], 1946.123123595910);
		}
		
		public function testHistoricCircaMonthAndYearDate() {
			$o_tep = new TimeExpressionParser();
			$o_tep->setLanguage('en_US');
			
			$vb_res = $o_tep->parse('c June 1946');
			$this->assertEquals($vb_res, true);
			$va_parse = $o_tep->getHistoricTimestamps();
		
			$this->assertEquals($va_parse['start'], 1946.060100000010);
			$this->assertEquals($va_parse['end'], 1946.063023595910);
			$this->assertEquals($va_parse[0], 1946.060100000010);
			$this->assertEquals($va_parse[1], 1946.063023595910);
			
			$vb_res = $o_tep->parse('c. 6/1946');
			$this->assertEquals($vb_res, true);
			$va_parse = $o_tep->getHistoricTimestamps();
		
			$this->assertEquals($va_parse['start'], 1946.060100000010);
			$this->assertEquals($va_parse['end'], 1946.063023595910);
			$this->assertEquals($va_parse[0], 1946.060100000010);
			$this->assertEquals($va_parse[1], 1946.063023595910);
			
			$vb_res = $o_tep->parse('circa June 1946');
			$this->assertEquals($vb_res, true);
			$va_parse = $o_tep->getHistoricTimestamps();
		
			$this->assertEquals($va_parse['start'], 1946.060100000010);
			$this->assertEquals($va_parse['end'], 1946.063023595910);
			$this->assertEquals($va_parse[0], 1946.060100000010);
			$this->assertEquals($va_parse[1], 1946.063023595910);
			
			
			$vb_res = $o_tep->parse('ca 6/1946');
			$this->assertEquals($vb_res, true);
			$va_parse = $o_tep->getHistoricTimestamps();
		
			$this->assertEquals($va_parse['start'], 1946.060100000010);
			$this->assertEquals($va_parse['end'], 1946.063023595910);
			$this->assertEquals($va_parse[0], 1946.060100000010);
			$this->assertEquals($va_parse[1], 1946.063023595910);
			
			
			$vb_res = $o_tep->parse('ca. June 1946');
			$this->assertEquals($vb_res, true);
			$va_parse = $o_tep->getHistoricTimestamps();
		
			$this->assertEquals($va_parse['start'], 1946.060100000010);
			$this->assertEquals($va_parse['end'], 1946.063023595910);
			$this->assertEquals($va_parse[0], 1946.060100000010);
			$this->assertEquals($va_parse[1], 1946.063023595910);
			
			
			$vb_res = $o_tep->parse('6/1946?');
			$this->assertEquals($vb_res, true);
			$va_parse = $o_tep->getHistoricTimestamps();
		
			$this->assertEquals($va_parse['start'], 1946.060100000010);
			$this->assertEquals($va_parse['end'], 1946.063023595910);
			$this->assertEquals($va_parse[0], 1946.060100000010);
			$this->assertEquals($va_parse[1], 1946.063023595910);
		}
		
		public function testDecadeDate() {
			$o_tep = new TimeExpressionParser();
			$vb_res = $o_tep->parse('1950s');
			$this->assertEquals($vb_res, true);
			$va_parse = $o_tep->getHistoricTimestamps();
		
			$this->assertEquals($va_parse['start'], 1950.010100000000);
			$this->assertEquals($va_parse['end'], 1959.123123595900);
			$this->assertEquals($va_parse[0], 1950.010100000000);
			$this->assertEquals($va_parse[1], 1959.123123595900);
			
			$vb_res = $o_tep->parse('1950\'s');
			$this->assertEquals($vb_res, true);
			$va_parse = $o_tep->getHistoricTimestamps();
		
			$this->assertEquals($va_parse['start'], 1950.010100000000);
			$this->assertEquals($va_parse['end'], 1959.123123595900);
			$this->assertEquals($va_parse[0], 1950.010100000000);
			$this->assertEquals($va_parse[1], 1959.123123595900);
			
			$vb_res = $o_tep->parse('195-');
			$this->assertEquals($vb_res, true);
			$va_parse = $o_tep->getHistoricTimestamps();
		
			$this->assertEquals($va_parse['start'], 1950.010100000000);
			$this->assertEquals($va_parse['end'], 1959.123123595900);
			$this->assertEquals($va_parse[0], 1950.010100000000);
			$this->assertEquals($va_parse[1], 1959.123123595900);
		}
		
		public function testCenturyDates() {
			$o_tep = new TimeExpressionParser();
			$o_tep->setLanguage('en_US');
			
			$vb_res = $o_tep->parse('20th century');
			$this->assertEquals($vb_res, true);
			$va_parse = $o_tep->getHistoricTimestamps();
		
			$this->assertEquals($va_parse['start'], 1900.010100000000);
			$this->assertEquals($va_parse['end'], 1999.123123595900);
			$this->assertEquals($va_parse[0], 1900.010100000000);
			$this->assertEquals($va_parse[1], 1999.123123595900);
			
			$vb_res = $o_tep->parse('19--');
			$this->assertEquals($vb_res, true);
			$va_parse = $o_tep->getHistoricTimestamps();
		
			$this->assertEquals($va_parse['start'], 1900.010100000000);
			$this->assertEquals($va_parse['end'], 1999.123123595900);
			$this->assertEquals($va_parse[0], 1900.010100000000);
			$this->assertEquals($va_parse[1], 1999.123123595900);
		}
		
		
		public function testADBCDates() {
			$o_tep = new TimeExpressionParser();
			$o_tep->setLanguage('en_US');
			
			$vb_res = $o_tep->parse('2000bc');
			$this->assertEquals($vb_res, true);
			$va_parse = $o_tep->getHistoricTimestamps();
		
			$this->assertEquals($va_parse['start'], -2000.010100000000);
			$this->assertEquals($va_parse['end'], -2000.123123595900);
			$this->assertEquals($va_parse[0], -2000.010100000000);
			$this->assertEquals($va_parse[1], -2000.123123595900);
			
			
			$vb_res = $o_tep->parse('2000ad');
			$this->assertEquals($vb_res, true);
			$va_parse = $o_tep->getHistoricTimestamps();
		
			$this->assertEquals($va_parse['start'], 2000.010100000000);
			$this->assertEquals($va_parse['end'], 2000.123123595900);
			$this->assertEquals($va_parse[0], 2000.010100000000);
			$this->assertEquals($va_parse[1], 2000.123123595900);
		}
		
		public function testTimes() {
			$o_tep = new TimeExpressionParser();
			$o_tep->setLanguage('en_US');
			
			$vb_res = $o_tep->parseTime('10:55pm');
			$va_parse = $o_tep->getTimes();
			
			$this->assertEquals($va_parse['start'], 82500);
			$this->assertEquals($va_parse['end'], 82500);
			$this->assertEquals($va_parse[0], 82500);
			$this->assertEquals($va_parse[1], 82500);
			
			
			$vb_res = $o_tep->parseTime('22:55');
			$va_parse = $o_tep->getTimes();
			
			$this->assertEquals($va_parse['start'], 82500);
			$this->assertEquals($va_parse['end'], 82500);
			$this->assertEquals($va_parse[0], 82500);
			$this->assertEquals($va_parse[1], 82500);
			
			
			$vb_res = $o_tep->parseTime('22:55:15');
			$va_parse = $o_tep->getTimes();
			
			$this->assertEquals($va_parse['start'], 82515);
			$this->assertEquals($va_parse['end'], 82515);
			$this->assertEquals($va_parse[0], 82515);
			$this->assertEquals($va_parse[1], 82515);
		}
		
		public function testDatesWithTimes() {
			$o_tep = new TimeExpressionParser();
			$o_tep->setLanguage('en_US');
			
			$vb_res = $o_tep->parse('June 6 2009 at 10:55pm');
			$this->assertEquals($vb_res, true);
			$va_parse = $o_tep->getHistoricTimestamps();
			
			$this->assertEquals($va_parse['start'], 2009.060622550000);
			$this->assertEquals($va_parse['end'], 2009.060622550000);
			$this->assertEquals($va_parse[0], 2009.060622550000);
			$this->assertEquals($va_parse[1], 2009.060622550000);
			
			$vb_res = $o_tep->parse('June 6 2009 @ 22:55');
			$this->assertEquals($vb_res, true);
			$va_parse = $o_tep->getHistoricTimestamps();
			
			$this->assertEquals($va_parse['start'], 2009.060622550000);
			$this->assertEquals($va_parse['end'], 2009.060622550000);
			$this->assertEquals($va_parse[0], 2009.060622550000);
			$this->assertEquals($va_parse[1], 2009.060622550000);
			
			$vb_res = $o_tep->parse('June 6 2009 @ 10:55:10pm');
			$this->assertEquals($vb_res, true);
			$va_parse = $o_tep->getHistoricTimestamps();
			
			$this->assertEquals($va_parse['start'], 2009.060622551000);
			$this->assertEquals($va_parse['end'], 2009.060622551000);
			$this->assertEquals($va_parse[0], 2009.060622551000);
			$this->assertEquals($va_parse[1], 2009.060622551000);
			
			
			$vb_res = $o_tep->parse('6 june 2009 @ 10:55:10pm');
			$this->assertEquals($vb_res, true);
			$va_parse = $o_tep->getHistoricTimestamps();
			
			$this->assertEquals($va_parse['start'], 2009.060622551000);
			$this->assertEquals($va_parse['end'], 2009.060622551000);
			$this->assertEquals($va_parse[0], 2009.060622551000);
			$this->assertEquals($va_parse[1], 2009.060622551000);
			
			$vb_res = $o_tep->parse('6/6/2009 @ 10:55:10pm');
			$this->assertEquals($vb_res, true);
			$va_parse = $o_tep->getHistoricTimestamps();
			
			$this->assertEquals($va_parse['start'], 2009.060622551000);
			$this->assertEquals($va_parse['end'], 2009.060622551000);
			$this->assertEquals($va_parse[0], 2009.060622551000);
			$this->assertEquals($va_parse[1], 2009.060622551000);
		}
		
		public function testDateRangesWithTimes() {
			$o_tep = new TimeExpressionParser();
			$o_tep->setLanguage('en_US');
			
			$vb_res = $o_tep->parse('6/5/2007 @ 9am .. 6/5/2007 @ 5pm');
			$this->assertEquals($vb_res, true);
			$va_parse = $o_tep->getHistoricTimestamps();
			
			$this->assertEquals($va_parse['start'], 2007.060509000000);
			$this->assertEquals($va_parse['end'],   2007.060517000000);
			$this->assertEquals($va_parse[0], 2007.060509000000);
			$this->assertEquals($va_parse[1], 2007.060517000000);
		}
		
		public function testDatesWithImplicitYear() {
			$o_tep = new TimeExpressionParser();
			$o_tep->setLanguage('en_US');
			$va_date = getDate();
			$vb_res = $o_tep->parse('6/5 at 9am - 5pm');
			$this->assertEquals($vb_res, true);
			$va_parse = $o_tep->getHistoricTimestamps();
		
			$this->assertEquals($va_parse['start'], $va_date['year'].'.060509000000');
			$this->assertEquals($va_parse['end'], $va_date['year'].'.060517000000');
			$this->assertEquals($va_parse[0], $va_date['year'].'.060509000000');
			$this->assertEquals($va_parse[1], $va_date['year'].'.060517000000');
		}
		
		public function testDateTextOutput() {
			$o_tep = new TimeExpressionParser();
			$o_tep->setLanguage('en_US');
			
			$vb_res = $o_tep->parse('6/6/2009 @ 10:55:10pm');
			$this->assertEquals($vb_res, true);
			
			$this->assertEquals($o_tep->getText(), 'June 6 2009 at 22:55:10');
			
			$o_tep->setLanguage('de_DE');
			
			$this->assertEquals($o_tep->getText(), '6. Juni 2009 um 22:55:10');
		}
	}
?>
