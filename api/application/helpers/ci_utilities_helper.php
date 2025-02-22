<?php
function insert_csrf_field($return = false)
{
	$CI = &get_instance();
	$csrf = array(
		'name' => $CI->security->get_csrf_token_name(),
		'hash' => $CI->security->get_csrf_hash()
	);
	$input = '<input type="hidden" name="' . $csrf['name'] . '" value="' . $csrf['hash'] . '" />';
	if ($return == true) {
		return $input;
	} else {
		echo $input;
	}
}

function addTabs($num)
{
	return str_repeat("\t", $num);
}

function set_single_qoute($field_type)
{
	$string = '';
	if ($field_type != 'int' && $field_type != 'float' && $field_type != 'double') {
		$string = "'";
	}
	return $string;
}

function isTime($time)
{
	if (strlen($time) == 5) {
		return preg_match("#([0-1]{1}[0-9]{1}|[2]{1}[0-3]{1}):[0-5]{1}[0-9]{1}#", $time);
	} elseif (strlen($time) == 8) {
		return preg_match("#([0-1]{1}[0-9]{1}|[2]{1}[0-3]{1}):[0-5]{1}[0-9]{1}:[0-5]{1}[0-9]{1}#", $time);
	}
	return false;
}

function getTimeFromDate($date)
{
	if ($date != '') {
		$dte = $arrDate = explode(" ", $date);
		if (isset($dte[1])) {
			return $dte[1];
		}
	}
}

function setDateFormat($date)
{ //สร้างรูปแบบของวันที่ yyyy-mm-dd
	$y = '';
	$m = '';
	$d = '';
	if ($date != '') {
		//ZAN@2017-06-20
		$dte = $arrDate = explode(" ", $date);
		$date = $dte[0];
		if (preg_match("/^([0-9]{1,2})\-([0-9]{1,2})\-([0-9]{4})$/", $date, $arr) || preg_match("/^([0-9]{1,2})\/([0-9]{1,2})\/([0-9]{4})$/", $date, $arr)) {
			//ถ้าเป็น xx-xx-yyyy หรือ xx/xx/yyyy
			$y = $arr[3];
			$m = sprintf("%02d", $arr[2]);
			$d = sprintf("%02d", $arr[1]);
		} else if (preg_match("/^([0-9]{4})\-([0-9]{1,2})\-([0-9]{1,2})$/", $date, $arr) || preg_match("/^([0-9]{4})\/([0-9]{1,2})\/([0-9]{1,2})$/", $date, $arr)) {
			//ถ้าเป็น yyyy-xx-xx หรือ yyyy/xx/xx
			$y = $arr[1];
			$m = sprintf("%02d", $arr[2]);
			$d = sprintf("%02d", $arr[3]);
		}
	}
	if (($y != "" && $m != "" && $d != "") and ($y != '0000' && $m != '00' && $d != '00')) {
		return $y . "-" . $m . "-" . $d; //คืนค่า ปี-เดือน-วัน
	} else {
		return $date;
	}
}

// DD/MM/YYYY+543 ??:??:??
function setDateToThai($date, $time = true, $style = '', $check_zero_day = true)
{
	if ($date == '') return $date;
	$arr    = explode(' ', $date);
	if ($time == true) {
		$time = isset($arr[1]) ? ' ' . $arr[1] : '';
	} else {
		$time = '';
	}

	$new_format = setDateFormat($arr[0]);
	$dte    = explode('-', $new_format);
	$y      = (isset($dte[0]) && $dte[0] > 0) ? $dte[0] + 543 : '-';
	$m      = isset($dte[1]) ? $dte[1] : '-';
	$d      = isset($dte[2]) ? (($check_zero_day) ? $dte[2] : intval($dte[2])) : '-';

	switch ($style) {
		case 'full_month':
			$full = array('', 'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน', 'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม');
			$month = $full[$m + 0];
			$thaiDate = $d . ' ' . $month . ' ' . $y . $time;
			break;
		case 'short_month':
			$short = array('', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.');
			$month = $short[$m + 0];
			$thaiDate = $d . ' ' . $month . ' ' . $y . $time;
			break;
		case 'short_date':
			$thaiDate = $d . '-' . $m . '-' . $y;
			$thaiDate = date("d-M-Y", strtotime($thaiDate));
			break;
		default:
			$thaiDate = $d . '/' . $m . '/' . $y . $time;
			break;
	}
	return $thaiDate;
}

function setThaiDateFullMonth($date, $time = true)
{
	return setDateToThai($date, $time, 'full_month');
}

function setThaiDateShortMonth($date, $time = true)
{
	return setDateToThai($date, $time, 'short_month');
}

function setThaiDate($date, $time = true)
{
	return setDateToThai($date, $time);
}

function setThaiShortDate($date, $time = true)
{
	return setDateToThai($date, $time, 'short_date');
}

function setDateToBirthday($date, $time = true)
{
	if ($date == '') return $date;
	$dateA = explode(' ', $date);
	$time   = isset($dateA[1]) ? ' ' . $dateA[1] : '';

	$new_format = setDateFormat($dateA[0]);
	$arrD   = explode('-', $new_format);
	$y      = (isset($arrD[0]) && $arrD[0] > 0) ? $arrD[0] : $arrD[0];
	$m      = isset($arrD[1]) ? $arrD[1] : '/';
	$d      = isset($arrD[2]) ? $arrD[2] : '/';
	return $y . '-' . $m . '-' . $d;
}


// YYYY-MM-DD ??:??:??
function setDateToStandard($date, $time = true)
{
	if ($date == '') return $date;
	$dateA = explode(' ', $date);
	$time   = isset($dateA[1]) ? ' ' . $dateA[1] : '';

	$new_format = setDateFormat($dateA[0]);
	$arrD   = explode('-', $new_format);
	$y      = (isset($arrD[0]) && $arrD[0] > 0) ? $arrD[0] - 543 : $arrD[0];
	$m      = isset($arrD[1]) ? $arrD[1] : '/';
	$d      = isset($arrD[2]) ? $arrD[2] : '/';
	return $y . '-' . $m . '-' . $d . $time;
}

// Set Number
function stringToNumber($val)
{
	$val = str_replace(",", "", $val);
	return floatval($val);
}


//-- Database Helper --//
function getValueAll($table, $field_value, $field_text, $where = '', $db = NULL)
{
	if ($db === NULL) {
		$CI = &get_instance();
		$db = $CI->db;
	}
	if ($where != '') $where = "WHERE " . $where;

	$sql = "SELECT $field_value, $field_text FROM $table $where";
	$qry = $db->query($sql);
	$data = array();
	foreach ($qry->result_array() as $row) {
		$data[$row[$field_value]] = $row[$field_text];
	}
	return $data;
}

function getValueOf($table, $field_select, $where = '', $db = NULL)
{
	if ($db === NULL) {
		$CI = &get_instance();
		$db = $CI->db;
	}
	if ($where != '') $where = "WHERE " . $where;
	$sql = "SELECT $field_select FROM $table $where LIMIT 1";
	$qry = $db->query($sql);
	if ($row = $qry->row_array()) {
		return $row[$field_select];
	}
}

function getRowOf($table, $field_select = '*', $where = '', $db = NULL)
{
	if ($db === NULL) {
		$CI = &get_instance();
		$db = $CI->db;
	}
	if ($where != '') $where = "WHERE " . $where;
	$sql = "SELECT $field_select FROM $table $where LIMIT 1";
	$qry = $db->query($sql);
	return $qry->row_array();
}

function optionList($table, $field_value, $field_text, $condition = array(), $db = NULL)
{
	if ($db === NULL) {
		$CI = &get_instance();
		$mydb = $CI->db;
	} else {
		$mydb = $db;
	}
	$where = '';
	if (isset($condition['where'])) {
		$where = "WHERE " . $condition['where'];
	}
	if (isset($condition['order_by'])) {
		$order_by = $condition['order_by'];
	} else {
		$order_by = $field_text;
	}

	$ret = false;
	if (isset($condition['return'])) {
		$ret = $condition['return'];
	}

	$select_value = '';
	if (isset($condition['active'])) {
		$select_value = $condition['active'];
	}

	$list = '';
	$order_by = 'ORDER BY ' . $order_by;
	$sql = "SELECT $field_value, $field_text FROM $table $where $order_by";
	$qry = $mydb->query($sql);
	foreach ($qry->result_array() as $row) {
		$selected = '';
		if ($select_value == $row[$field_value]) {
			$selected = 'selected="selected"';
		}
		$option = '<option value="' . $row[$field_value] . '" ' . $selected . '>' . $row[$field_text] . '</option>';
		if ($ret == true) {
			$list .= $option;
		} else {
			echo $option;
		}
	}

	if ($ret == true) {
		return $list;
	}
}

function dump($data)
{
	echo '<pre>', print_r($data, TRUE), '</pre>';
}

function my_simple_crypt($string, $action = 'e')
{
	// you may change these values to your own
	$secret_key = 'my@simple#secret-key234';
	$secret_iv = 'my@simple#secret-iv345';

	$output = false;
	$encrypt_method = "AES-256-CBC";
	$key = hash('sha256', $secret_key);
	$iv = substr(hash('sha256', $secret_iv), 0, 16);

	if ($action == 'e') {
		$output = base64_encode(openssl_encrypt($string, $encrypt_method, $key, 0, $iv));
	} else if ($action == 'd') {
		$output = openssl_decrypt(base64_decode($string), $encrypt_method, $key, 0, $iv);
	}

	return $output;
}

function encrypt($string)
{
	$salting = substr(md5(microtime()), -1) . $string;
	return my_simple_crypt($salting, 'e');
}

function decrypt($string)
{
	$encode = my_simple_crypt($string, 'd');
	return substr($encode, 1);
}

function ci_encrypt($string)
{
	return my_simple_crypt($string, 'e');
}

function ci_decrypt($string)
{
	return my_simple_crypt($string, 'd');
}

function checkEncryptData($value)
{
	$check_id = decrypt($value); //ถ้าถอดรหัสมาก่อนแล้ว จะกลายเป็นค่าว่าง
	if ($check_id != '') {
		$value = $check_id;         //ถ้าไม่เป็นค่าว่าง แสดงว่าก่อนหน้านี้ยังเข้ารหัสอยู่ ให้ใช้ค่าที่ถอดรหัสแล้ว
	}
	return $value;
}

/**
 * Call md5() with salting
 * @param String $input_pass from user register
 * @return String a 32-character hexadecimal number
 */
function encrypt_md5_salt($input_pass)
{
	// 123456 ($2y$11$7E1Dw5fgB1FifW0apMj8meNHQG9janZMxtnaWPC4niyulskCov5sa)
	$key1 = 'RTy4$58/*tdr#t';	//default = RTy4$58/*tdr#t
	$key2 = 'ci@gen#$_sdf';		//default = ci@gen#$_sdf

	$key_md5 = md5($key1 . $input_pass . $key2);
	$key_md5 = md5($key2 . $key_md5 . $key1);
	$sub1 = substr($key_md5, 0, 7);
	$sub2 = substr($key_md5, 7, 10);
	$sub3 = substr($key_md5, 17, 12);
	$sub4 = substr($key_md5, 29, 3);
	return md5($sub3 . $sub1 . $sub4 . $sub2);
}

/**
 * Call password_hash() with md5 + salting
 * @param String $input_pass from user register
 * @return String always be a 60 character string
 */
function pass_secure_hash($input_pass)
{
	$encrypt_pass = encrypt_md5_salt($input_pass);
	$options = array('cost' => 11);
	return password_hash($encrypt_pass, PASSWORD_BCRYPT, $options);
}

/**
 * Call password_verify() with md5 + salting
 * @param String $input_pass from user Login
 * @param String $record_password from database user record
 * @return Boolean 
 */
function pass_secure_verify($input_pass, $record_password)
{
	$encrypt_pass = encrypt_md5_salt($input_pass);
	return password_verify($encrypt_pass, $record_password);
}


//check language
if (!function_exists('check_lang')) {
	function check_lang()
	{
		$CI = &get_instance();
		if ($CI->input->get('lang') and ($CI->input->get('lang') == 'th' || $CI->input->get('lang') == 'en' || $CI->input->get('lang') == 'jp')) {
			$CI->session->set_userdata('language', $CI->input->get('lang'));
		}

		if (!$CI->session->userdata('language')) {
			$CI->session->set_userdata('language', 'th');
			redirect(current_url());
		} else {
			switch ($CI->session->userdata('language')) {
				case 'th':
					$CI->lang->load('pages', 'thai');
					break;
				case 'en':
					$CI->lang->load('pages', 'english');
					break;
				case 'jp':
					$CI->lang->load('pages', 'japanese');
					break;
			}
		}
	}
}

/** ฟังก์ชันสำหรับแปลงนาทีเป็น วัน ชั่วโมง นาที (สำหรับคิดเวลาการลา)
 */
function convertMinuteToTimeBaseOnShiftHour($min, $base)
{
	$sec = $min * 60;
	$day = 0;
	$hour = 0;
	$minute = 0;
	$lang = [
		'd' => lang('hrm')['leave']['day'],
		'h' => lang('hrm')['leave']['hour'],
		'm' => lang('hrm')['leave']['minute']
	];
	$fulltext = "";
	$shorttext = '';
	if ($min > 0) {
		$day = floor($sec / (3600 * $base));
		$hour = floor(($sec % (3600 * $base)) / 3600);
		$minute = floor($sec % 3600 / 60);


		if ($day > 0) {
			$shorttext .= $day;
			$fulltext .= $day . ' ' . $lang['d'];
		}
		if ($hour > 0) {
			$fulltext .= ' ' . $hour . ' ' . $lang['h'];
			$shorttext .= "●" . $hour;
		}
		if ($minute > 0) {
			$fulltext .= ' ' . $minute . ' ' . $lang['m'];
		}
	}
	$timeResult = [
		'day' => $day != 0 ? $day . ' ' . $lang['d'] : " ",
		'hour' =>  $hour != 0 ? $hour . ' ' . $lang['h'] : " ",
		'minute' =>  $minute != 0 ? $minute . ' ' . $lang['m'] : " ",
		'fulltext' => $fulltext ? $fulltext : "0 " . $lang['d'],
		'text' => [
			'full' => $day . ' ' . $lang['d'] . ' ' . $hour . ' ' . $lang['h'] . ' ' . $minute . ' ' . $lang['m'],
			'short' => $day . ' ' . $lang['d'] . ' ' . $hour . ' ' . $lang['h'],
		],
		'show' => $shorttext,
		'time' => [
			'day' => $day,
			'hour' => $hour,
			'minute' => $minute
		],
		'status' => !$day && !$hour ? false : true,
	];

	return $timeResult;
}

function betweenDates($cmpDate, $startDate, $endDate)
{
	return (date($cmpDate) > date($startDate)) && (date($cmpDate) < date($endDate));
}

function monthDateDiff($strStartDate, $strEndDate)
{
	$intWorkDay = 0;
	$intHoliday = 0;
	$intTotalDay = ((strtotime($strEndDate) - strtotime($strStartDate)) /  (60 * 60 * 24)) + 1;
	$intTotalDayDeduct = ((strtotime($strEndDate) - strtotime($strStartDate)) /  (60 * 60 * 24)) + 1;

	if (date('d', strtotime($strStartDate)) == 31) {
		$intTotalDayDeduct = $intTotalDay - 1;
	} else if (date('m', strtotime($strStartDate)) == 2 && date('d', strtotime($strStartDate)) == 28) {
		$intTotalDayDeduct = $intTotalDayDeduct + 2;
	} else if (date('m', strtotime($strStartDate)) == 2 && date('d', strtotime($strStartDate)) == 29) {
		$intTotalDayDeduct = $intTotalDayDeduct + 1;
	} else if (date('d', strtotime($strEndDate)) == 31) {
		$intTotalDayDeduct = $intTotalDayDeduct - 1;
	} else if (date('m', strtotime($strEndDate)) == 2 && date('d', strtotime($strEndDate)) == 28) {
		$intTotalDayDeduct = $intTotalDayDeduct + 2;
	} else if (date('m', strtotime($strEndDate)) == 2 && date('d', strtotime($strEndDate)) == 29) {
		$intTotalDayDeduct = $intTotalDayDeduct + 1;
	} else if (date('m', strtotime($strStartDate)) == 2 && $intTotalDayDeduct < 30) {
		if ($intTotalDayDeduct == 28) {
			$intTotalDayDeduct = $intTotalDayDeduct + 2;
		} else if ($intTotalDayDeduct == 29) {
			$intTotalDayDeduct = $intTotalDayDeduct + 1;
		}
	}
	if ($intTotalDayDeduct > 30) {
		$intTotalDayDeduct = 30;
	}

	while (strtotime($strStartDate) <= strtotime($strEndDate)) {

		$DayOfWeek = date("w", strtotime($strStartDate));
		if ($DayOfWeek == 0 or $DayOfWeek == 6)  // 0 = Sunday, 6 = Saturday;
		{
			$intHoliday++;
			// echo "$strStartDate = <font color=red>Holiday</font><br>";
		} else {
			$intWorkDay++;
			// echo "$strStartDate = <b>Work Day</b><br>";
		}
		//$DayOfWeek = date("l", strtotime($strStartDate)); // return Sunday, Monday,Tuesday....

		$strStartDate = date("Y-m-d", strtotime("+1 day", strtotime($strStartDate)));
	}
	$allday = array('total' => $intTotalDay, 'work' => $intWorkDay, 'holiday' => $intHoliday, 'daydeduct' => $intTotalDayDeduct);

	return $allday;
}

function createMonth()
{
	// wait for change eng text to multi language
	$month = ['', lang('nav_jan'), lang('nav_feb'), lang('nav_mar'), lang('nav_apr'), lang('nav_may'), lang('nav_jun'), lang('nav_jul'), lang('nav_aug'), lang('nav_sep'), lang('nav_oct'), lang('nav_nov'), lang('nav_dec')];
	return $month;
}



function HTMLToRGB($htmlCode)
{
	if ($htmlCode[0] == '#')
		$htmlCode = substr($htmlCode, 1);

	if (strlen($htmlCode) == 3) {
		$htmlCode = $htmlCode[0] . $htmlCode[0] . $htmlCode[1] . $htmlCode[1] . $htmlCode[2] . $htmlCode[2];
	}

	$r = hexdec($htmlCode[0] . $htmlCode[1]);
	$g = hexdec($htmlCode[2] . $htmlCode[3]);
	$b = hexdec($htmlCode[4] . $htmlCode[5]);

	return $b + ($g << 0x8) + ($r << 0x10);
}

function RGBToHSL($RGB)
{
	$r = 0xFF & ($RGB >> 0x10);
	$g = 0xFF & ($RGB >> 0x8);
	$b = 0xFF & $RGB;

	$r = ((float)$r) / 255.0;
	$g = ((float)$g) / 255.0;
	$b = ((float)$b) / 255.0;

	$maxC = max($r, $g, $b);
	$minC = min($r, $g, $b);

	$l = ($maxC + $minC) / 2.0;

	if ($maxC == $minC) {
		$s = 0;
		$h = 0;
	} else {
		if ($l < .5) {
			$s = ($maxC - $minC) / ($maxC + $minC);
		} else {
			$s = ($maxC - $minC) / (2.0 - $maxC - $minC);
		}
		if ($r == $maxC)
			$h = ($g - $b) / ($maxC - $minC);
		if ($g == $maxC)
			$h = 2.0 + ($b - $r) / ($maxC - $minC);
		if ($b == $maxC)
			$h = 4.0 + ($r - $g) / ($maxC - $minC);

		$h = $h / 6.0;
	}

	$h = (int)round(255.0 * $h);
	$s = (int)round(255.0 * $s);
	$l = (int)round(255.0 * $l);

	return (object) array('hue' => $h, 'saturation' => $s, 'lightness' => $l);
}

function fileExists($picture_path, $type = 'profile')
{
	$image_no_found = '';
	switch ($type) {
		case 'profile':
			$image_no_found = 'assets/images/icon/blank_person.jpg';
			break;
		case 'content':
			$image_no_found = 'upload/images/No_image.png';
		case 'product':
			$image_no_found = 'assets/images/no-product.png';
		default:
			# code...
			break;
	}
	return $picture_path && file_exists($picture_path) ? base_url() . substr($picture_path, 2) : base_url() . $image_no_found;
}
function getDatesFromRange($start, $end, $format = 'Y-m-d')
{
	// Declare an empty array 
	$array = array();

	// Variable that store the date interval 
	// of period 1 day 
	$interval = new DateInterval('P1D');

	$realEnd = new DateTime($end);
	$realEnd->add($interval);

	$period = new DatePeriod(new DateTime($start), $interval, $realEnd);

	// Use loop to store date into array 
	foreach ($period as $date) {
		$array[] = $date->format($format);
	}

	// Return the array elements 
	return $array;
}

function dateToString($date_start, $date_end, $is_all_day)
{
	$CI = &get_instance();

	$lang = $CI->session->userdata('language');

	$day_title = $lang == 'th' ? 'วัน' : '';
	$time_title = $lang == 'th' ? 'เวลา' : '';
	$to_title = $lang == 'th' ? 'ถึง' : 'To';

	$ds = date_format(date_create($date_start), 'Y-m-d');
	$ds_time = date_format(date_create($date_start), 'H:i');
	$de = date_format(date_create($date_end), 'Y-m-d');
	$de_time = date_format(date_create($date_end), 'H:i');

	$month_th = $CI->config->item('monthsTHFull');
	$days_th = $CI->config->item('daysTHFull');

	$s_day = date_format(date_create($date_start), 'd');
	$s_dayth = $lang == 'th' ? $days_th[((int)$s_day % 7)]['label'] : date_format(date_create($date_start), 'l');
	$s_month = $lang == 'th' ? $month_th[(int)date_format(date_create($date_start), 'm') - 1]['label'] : date_format(date_create($date_start), 'F');
	$e_day = date_format(date_create($date_end), 'd');
	$e_dayth =  $lang == 'th' ?  $days_th[((int)$e_day % 7)]['label'] : date_format(date_create($date_end), 'l');
	$e_month =  $lang == 'th' ?  $month_th[(int)date_format(date_create($date_end), 'm') - 1]['label'] : date_format(date_create($date_end), 'F');
	$str = '';
	if ($ds == $de) {
		/*
			*** Format ***
			if all day format
			  ->  [วัน]พฤหัสบดี, 09 กันยายน
			else range time
			  ->  [วัน]พฤหัสบดี, 09 กันยายน · [เวลา] 13:00 [ถึง] 15:00
		*/
		$str = "$day_title$s_dayth, $s_day $s_month";
		if (!$is_all_day) $str .= " · $time_title $ds_time $to_title $de_time";
	} else {
		/*
			*** Format ***
			[วัน]พฤหัสบดี, 09 กันยายน · 13:00 [ถึง] [วัน]อาทิตย์, 12 กันยายน · 15:00
		*/
		$str = "$day_title$s_dayth, $s_day $s_month · $ds_time $to_title $day_title$e_dayth, $e_day $e_month · $de_time";
	}

	return $str;
}
function diffWorkdate($from, $to, $full = false)
{
	$from = new DateTime($from);
	$to = new DateTime($to);
	$diff = $from->diff($to);

	$arr_date = array(28, 29, 30, 31);

	if (in_array($diff->d, $arr_date)) {
		$diff->m += 1;
	}

	if ($diff->m >= 12) {
		$diff->y += 1;
		$diff->m = $diff->m / 12;
	}

	$string = array(
		'y' => 'ปี',
		'm' => 'เดือน',
	);
	foreach ($string as $k => &$v) {
		if ($diff->$k) {
			$v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? '' : '');
		} else {
			unset($string[$k]);
		}
	}

	if (!$full) $string = array_slice($string, 0, 1);
	return $string ? implode(', ', $string) : 'just now';
}
function markidcard($idcard, $stringmark = '*')
{
	if (!$idcard) return "";
	$length = strlen($idcard);
	$front = substr($idcard, 0, 4);
	$back = substr($idcard, -4);

	$mark = "";
	for ($i = 0; $i < $length; $i++) {
		$mark .= $stringmark;
	}
	return  $front . $mark . $back;
}


function get_client_ip()
{
	$ipaddress = '';
	if (getenv('HTTP_CLIENT_IP'))
		$ipaddress = getenv('HTTP_CLIENT_IP');
	else if (getenv('HTTP_X_FORWARDED_FOR'))
		$ipaddress = getenv('HTTP_X_FORWARDED_FOR');
	else if (getenv('HTTP_X_FORWARDED'))
		$ipaddress = getenv('HTTP_X_FORWARDED');
	else if (getenv('HTTP_FORWARDED_FOR'))
		$ipaddress = getenv('HTTP_FORWARDED_FOR');
	else if (getenv('HTTP_FORWARDED'))
		$ipaddress = getenv('HTTP_FORWARDED');
	else if (getenv('REMOTE_ADDR'))
		$ipaddress = getenv('REMOTE_ADDR');
	else
		$ipaddress = 'UNKNOWN';
	return $ipaddress;
}
/**
 * @param url UrlLink
 * @param class class
 * @param icon tagicon
 * @param text text
 */

function generateRandomString($length = 10)
{
	$characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
	$charactersLength = strlen($characters);
	$randomString = '';
	for ($i = 0; $i < $length; $i++) {
		$randomString .= $characters[rand(0, $charactersLength - 1)];
	}
	return $randomString;
}
function generateOTP($length = 6)
{
	$otp = '';
	for ($i = 0; $i < $length; $i++) {
		$otp .= random_int(0, 9);
	}
	return $otp;
}

const KEY1 = "RTy4$58/*tdr#t";
const KEY2 = 'ci@gen#$_sdf';
const CIPHER = "AES-128-CBC";

function encryption($pass)
{
	$ivlen = openssl_cipher_iv_length(CIPHER);
	$iv = openssl_random_pseudo_bytes($ivlen);
	$ciphertext_raw = openssl_encrypt($pass, CIPHER, KEY1, $options = OPENSSL_RAW_DATA, $iv);
	$hmac = hash_hmac('sha256', $ciphertext_raw, KEY1, $as_binary = true);
	$ciphertext = base64_encode($iv . $hmac . $ciphertext_raw);

	$subivlen = openssl_cipher_iv_length(CIPHER);
	$subiv = openssl_random_pseudo_bytes($subivlen);
	$subciphertext_raw = openssl_encrypt($ciphertext, CIPHER, KEY2, $options = OPENSSL_RAW_DATA, $subiv);
	$subhmac = hash_hmac('sha256', $subciphertext_raw, KEY2, $as_binary = true);
	$subciphertext = base64_encode($subiv . $subhmac . $subciphertext_raw);

	$encrypt_key = encrypt($subciphertext);
	return   $encrypt_key;
}
function decryption($pass)
{
	$chipher = '';
	$decrypt_key = decrypt($pass);
	$c = base64_decode($decrypt_key);
	$ivlen = openssl_cipher_iv_length(CIPHER);
	$iv = substr($c, 0, $ivlen);
	$hmac = substr($c, $ivlen, $sha2len = 32);
	$ciphertext_raw = substr($c, $ivlen + $sha2len);
	$original_plaintext = openssl_decrypt($ciphertext_raw, CIPHER, KEY2, $options = OPENSSL_RAW_DATA, $iv);
	$calcmac = hash_hmac('sha256', $ciphertext_raw, KEY2, $as_binary = true);
	if (hash_equals($hmac, $calcmac)) {
		$subc = base64_decode($original_plaintext);
		$subivlen = openssl_cipher_iv_length(CIPHER);
		$subiv = substr($subc, 0, $subivlen);
		$subhmac = substr($subc, $subivlen, $sha2len = 32);
		$subciphertext_raw = substr($subc, $subivlen + $sha2len);
		$suboriginal_plaintext = openssl_decrypt($subciphertext_raw, CIPHER, KEY1, $options = OPENSSL_RAW_DATA, $subiv);
		$subcalcmac = hash_hmac('sha256', $subciphertext_raw, KEY1, $as_binary = true);
		if (hash_equals($subhmac, $subcalcmac)) {
			$chipher =  $suboriginal_plaintext;
		}
	}

	return $chipher;
}


function arabic_to_thai($number)
{
	$arabic_digits = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
	$thai_digits = ['๐', '๑', '๒', '๓', '๔', '๕', '๖', '๗', '๘', '๙'];

	$thai_number = str_replace($arabic_digits, $thai_digits, $number);

	return $thai_number;
}

/**
 * 
 */


function Avatar($firstname, $lastname)
{
	return "https://ui-avatars.com/api/?name=$firstname+$lastname&rounded=true&background=ffc000&color=fff&size=128";
}



function checkTimeBetweenDates($startTime, $endTime)
{
	$start = DateTime::createFromFormat('H:i', $startTime);
	$end = DateTime::createFromFormat('H:i', $endTime);
	if ($end <= $start) {
		$end->modify('+1 day');
		$date = $end->format('Y-m-d H:i:s');
	} else {
		$date = $start->format('Y-m-d H:i:s');
	}

	return $date;
}

function calculateHoursDifference($datetime1, $datetime2)
{
	$datetime1 = new DateTime($datetime1);
	$datetime2 = new DateTime($datetime2);

	$interval = $datetime1->diff($datetime2);
	$hours = $interval->days * 24 + $interval->h + $interval->i / 60 + $interval->s / 3600;

	return $hours;
}

function calculateTime($startTime, $endTime)
{
	if (!$startTime || !$endTime) return null;
	$start = new DateTime($startTime);
	$end = new DateTime($endTime);
	$interval = $start->diff($end);
	$days = $interval->d;
	$hours = $interval->h;
	$minutes = $interval->i;


	$textComponents = [];
	$keyComponent = [];
	$timeComponent = [];
	// Add days, hours, and minutes to the array if they are non-zero
	$isday = false;
	if ($days > 0) {
		$isday = true;
		$timeComponent[] =  $days;
		$textComponents[] = $days . ' วัน';
		$keyComponent[] = "day";
	}
	if ($hours > 0) {
		$timeComponent[] =  $hours;
		$textComponents[] = $hours . ' ชั่วโมง';
		$keyComponent[] = "hour";
	}
	if ($minutes > 0) {
		$timeComponent[] =  $minutes;
		$textComponents[] = $minutes . ' นาที';
		$keyComponent[] = "minute";
	}
	$time =  empty($textComponents) ? '0 นาที' : implode(', ', $textComponents);
	return [
		'items' => $timeComponent,
		'key' => $keyComponent,
		'time' => $isday ? date('d-M-Y H:i:s', strtotime($startTime)) : $time,
		'isday' => $isday,
	];
}

function checkValidPassword($password, $option = [
	'mix' => 8,
	'max' => 30,
	'char' => true,
	'condition' => true,
	'number' => true,
	'special' => true,
], $resarray = false)
{
	$option['min'] ? $option['min'] : 8;
	$option['max'] ? $option['max'] : 30;
	$option['char'] ? $option['char'] : true;
	$option['number'] ? $option['number'] : true;
	$option['special'] ? $option['special'] : true;
	$option['condition'] ? $option['condition'] : true;

	$password = trim($password);

	$minLength = $option['min'];
	$maxLength = $option['max'];
	$containsUpperOrLower = '/[a-zA-Z]/';
	$containsNumber = '/[0-9]/';
	$containsSpecialChar = '/[-!@#$%^&*()+._]/';

	if (strlen($password) < $minLength || strlen($password) > $maxLength) {
		if ($resarray) return ['status' => false, 'msg' => 'Check Min ' . $minLength . ' character Or Max ' . $maxLength . ' character'];
		return false;
	}
	// Check for uppercase or lowercase letter
	if ($option['char']) {
		if (!preg_match($containsUpperOrLower, $password)) {
			if ($resarray) return ['status' => false, 'msg' => 'Check uppercase or lowercase letter a-zA-Z'];
			return false;
		}
	}

	// Check for number

	if ($option['condition']) {
		if ($option['number']) {
			if (!preg_match($containsNumber, $password)) {
				if ($resarray) return ['status' => false, 'msg' => 'Check for number 0-9'];
				return false;
			}
		}
		// Check for special character
		if ($option['special']) {
			if (!preg_match($containsSpecialChar, $password)) {
				if ($resarray) return ['status' => false, 'msg' => 'Check for special character -!@#$%^&*()+._'];
				return false;
			}
		}
	}
	if ($resarray) return ['status' => true];
	return true;
}
function checkValidUsername($username)
{
	$username = trim($username);
	if (!$username) return false;
	if (!preg_match('/^(?=.{6,22}$)(?![_.-])(?!.*[_.]{2})[a-zA-Z0-9._-]+(?<![_.])$/', $username)) return false;
}
function isNull($payload)
{
	if (!!$payload) return $payload;
	return null;
}

function sumStringTimes(...$time)
{
	$sum = 0;
	foreach ($time as $key => $value) {
		if ($value != '00:00') {
			$exp = array_map('intval', explode(':', $value));
			$sum += ($exp[0] * 60) + $exp[1];
			// echo '<pre>';print_r($time);die;
		}
	}

	return str_pad((floor($sum / 60)) . '', 2, '0', STR_PAD_LEFT) . ':' . str_pad((floor($sum % 60)) . '', 2, '0', STR_PAD_LEFT);
}
function getDaysBetweenDates($startDate, $endDate, $inclusive = false)
{
	// Convert dates to DateTime objects
	$start = new DateTime($startDate);
	$end = new DateTime($endDate);

	// Calculate the difference
	$interval = $start->diff($end);

	// Get the number of days
	$days = $interval->days;

	// Add 1 day for inclusive calculation
	if ($inclusive) {
		$days += 1;
	}

	return $days;
}
function getYears($yearsToAdd)
{
	// Get the current year
	$currentYear = (int)date("Y");

	// Generate the array of years
	$years = [];
	for ($i = 0; $i <= $yearsToAdd; $i++) {
		$years[] = $currentYear + $i;
	}

	return $years;
}
