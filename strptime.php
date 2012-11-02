<?php
/**
 * 
 * @todo Comment me
 * 
 * @license
 * @author Mathieu Chabanon
 * @link https://github.com/matchab/strptime-php
 */

if (!function_exists('strptime'))
{

/**
 * 
 * 
 * @param string $buffer
 * @param string $pattern
 * @return string
 * @access private
 */
function _strptime_match(&$buffer,$pattern)
{
	if (is_array($pattern)){
		$pattern = implode('|',$pattern);
	}
	$pattern = '/^('.$pattern.')/i';
	
	$ret = NULL;
	$matches;
	if (preg_match($pattern,$buffer,$matches))
	{
		$ret = $matches[0];
		
		//Remove the match from the buffer
		$buffer = preg_replace($pattern,'',$buffer);
	}
	return $ret;
}

/**
 * 
 * 
 * @param int $n
 * @param int $min
 * @param int $max
 * @return int
 * @access private
 */
function _strptime_clamp($n,$min,$max){
	return max(min($n,$max),$min);
}

/**
 * 
 * 
 * @param string $p
 * @return array
 * @access private
 */
function _strptime_wdays($p)
{
	$locales = array();
	
	for ($i=0; $i<7; $i++)
	{
		$locales[$i] = strftime('%'.$p,strtotime('next Sunday +'.$i.' days'));
	}
	
	return $locales;
}

/**
 * 
 * 
 * @param string $p
 * @return array
 * @access private
 */
function _strptime_months($p)
{
	$locales = array();
	
	for ($i=1; $i<=12; $i++)
	{
		$locales[$i] = strftime('%'.$p,mktime(0,0,0,$i));
	}
	
	return $locales;
}

/**
 * 
 * 
 * @param string $date
 * @param string $format
 * @return array
 */
function strptime($date,$format)
{
	//Default return values
	$tm_sec = 0;
	$tm_min = 0;
	$tm_hour = 0;
	$tm_mday = 1;
	$tm_mon = 1;
	$tm_year = 1900;
	$tm_wday = 0;
	$tm_yday = 0;
	
	$buffer = $date;
	$length = strlen($format);
	$lastc = NULL;
	
	for ($i=0; $i<$length; $i++)
	{
		$c = $format[$i];
		
		//Remove spaces
		$buffer = ltrim($buffer);
		
		if ($lastc == '%')
		{
			switch ($c)
			{
				case 'A':
				case 'a':
					_strptime_match($buffer,_strptime_wdays($c));
					break;
					
				case 'B':
				case 'b':
				case 'h':
					$months = _strptime_months($c);
					$month = _strptime_match($buffer,$months);
					$tm_mon = array_search($month,$months);
					break;
					
				case 'D':
					//Unsupported by strftime on Windows
					_strptime_match($buffer,'\d{2}\/\d{2}\/\d{2}');
					break;
					
				//case 'e':
				case 'd':
					$tm_mday = intval(_strptime_match($buffer,'\d{2}'));
					break;
					
				case 'F':
					//Unsupported by strftime on Windows
					if ($ret = _strptime_match($buffer,'\d{4}-\d{2}-\d{2}'))
					{
						$frags = explode('-',$ret);
						$tm_year = intval($frags[0]);
						$tm_mon = intval($frags[1]);
						$tm_mday = intval($frags[2]);
					}
					break;
					
				case 'H':
					$tm_hour = intval(_strptime_match($buffer,'\d{2}'));
					break;
					
				case 'M':
					$tm_min = intval(_strptime_match($buffer,'\d{2}'));
					break;
					
				case 'm':
					$tm_mon = intval(_strptime_match($buffer,'\d{2}'));
					break;
					
				case 'S':
					$tm_sec = intval(_strptime_match($buffer,'\d{2}'));
					break;
					
				case 'Y':
					$tm_year = intval(_strptime_match($buffer,'\d{4}'));
					break;
					
				case 'y':
					$year = intval(_strptime_match($buffer,'\d{2}'));
					if ($year < 69) {
						$tm_year = 2000 + $year;
					} else {
						$tm_year = 1900 + $year;
					}
					break;
					
			}
		}
		else{
			$buffer = ltrim($buffer,$c);
		}
		
		$lastc = $c;
	}
	
	//Date must exists!
	if (!checkdate($tm_mon,$tm_mday,$tm_year)){
		return false;
	}
	
	//Clamp hours values
	$tm_sec = _strptime_clamp($tm_sec,0,61); //Leap seconds
	$tm_min = _strptime_clamp($tm_min,0,59);
	$tm_hour = _strptime_clamp($tm_hour,0,23);
	
	//Compute wday and yday
	$timestamp = mktime($tm_hour,$tm_min,$tm_sec,$tm_mon,$tm_mday,$tm_year);
	$tm_wday = date('w',$timestamp);
	$tm_yday = date('z',$timestamp);
	
	//Return
	$time = array();
	$time['tm_sec'] = $tm_sec;
	$time['tm_min'] = $tm_min;
	$time['tm_hour'] = $tm_hour;
	$time['tm_mday'] = $tm_mday;
	$time['tm_mon'] = ($tm_mon-1); //0-11
	$time['tm_year'] = ($tm_year-1900);
	$time['tm_wday'] = $tm_wday;
	$time['tm_yday'] = $tm_yday;
	$time['unparsed'] = $buffer; //Unparsed buffer
	return $time;
}
}
?>