<?PHP
/* sub string from head to length
 * $str: source string
 * $len: sub length,start at beginning
 * $flag: format style, true: transform tag to HTML symbol
 */
function substring($str, $len, $flag = true)
{
	$msg = $str;

	if($len > 0 && strlen($msg) > $len) {
		$msg = str_replace("\r\n", " ", $msg);
		$msg = str_replace("\n", " ", $msg);
		$msg = substr($msg, 0, $len);

		$content = "";
		if(strlen($msg) == $len) {
			$doublelen = 1;
			$tail = "...";
		}
		else {
			$doublelen = 0;
			$tail = "";
		}

		while(strlen($msg) > $doublelen) {
			if(ord($msg) > 0x80) {
				$content .= substr($msg, 0, 2);
				$msg = substr($msg, 2);
			}
			else {
				$content .= substr($msg, 0, 1);
				$msg = substr($msg, 1);
			}
		}
		if($flag) {
			$content = str_replace("<", "&lt;", $content);
			$content = str_replace(">", "&gt;", $content);
			$content = str_replace(" ", "&nbsp;", $content);
			$content = str_replace("¡¡", "&nbsp;&nbsp;", $content);
		}

		$msg = $content . $tail;
	}

	return $msg;
}


function convert_date_format($date)
{

	$day = "00";
	$month = "00";
	$year = "0000";

	if(!empty($date)) {
		// mm-dd-yy
		if(ereg("^([0-9]{1,2})-([0-9]{1,2})-([0-9]{2})$", $date, $components)) {
			$day	= $components[2];
			$month	= $components[1];
			$year	= $components[3];
		}
		// mm-dd-yyyy
		else if(ereg("^([0-9]{1,2})-([0-9]{1,2})-([0-9]{4})$", $date, $components)) {
			$day	= $components[2];
			$month	= $components[1];
			$year	= $components[3];
		}
		// yyyy-mm-dd
		else if(ereg("^([0-9]{4})-([0-9]{1,2})-([0-9]{1,2})$", $date, $components)) {
			$day	= $components[3];
			$month	= $components[2];
			$year	= $components[1];
		}
		// mm/dd/yyyy
		else if(ereg("^([0-9]{1,2})/([0-9]{1,2})/([0-9]{4})$", $date, $components)) {
			$day	= $components[2];
			$month	= $components[1];
			$year	= $components[3];
		}
		// /mm/dd/yy
		else if(ereg("^([0-9]{1,2})/([0-9]{1,2})/([0-9]{2})$", $date, $components)) {
			$day	= $components[2];
			$month	= $components[1];
			$year	= $components[3];
		}

		$day	= (strlen($day) == 1 ) ? "0".$day : $day;
		$month	= (strlen($month) == 1 ) ? "0".$month : $month;
		$year	= (strlen($year) < 4 ) ? (($year < 50) ? (2000 + $year) : (1900 + $year)) : $year;

		return $year."-".$month."-".$day;
	}

	return "0000-00-00";
}
?>
