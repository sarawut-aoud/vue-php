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

//Get file icon
function getFileIcon($file_path)
{
	$icon = 'noimage.gif';
	if (file_exists($file_path)) {
		switch (mime_content_type($file_path)) {
			case 'image/gif':
			case 'image/jpeg':
			case 'image/png':
			case 'image/bmp':
				$icon = 'picture.png';
				break;
			case 'application/msword':
			case 'application/vnd.ms-msword':
			case 'application/vnd.openxmlformats-officedocument.wordprocessingml.document':
				$icon = 'word.png';
				break;
			case 'application/vnd.oasis.opendocument.text':
				$icon = 'odt.png';
				break;
			case 'application/vnd.ms-powerpoint':
			case 'application/vnd.openxmlformats-officedocument.presentationml.presentation':
				$icon = 'powerpoint.png';
				break;
			case 'application/vnd.ms-excel':
			case 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet':
				$icon = 'excel.png';
				break;
			case 'application/pdf':
				$icon = 'pdf.png';
				break;
			default:
				$icon = 'clip.png';
				break;
		}
	}
	return $icon;
}

function setAttachPreview($input_name, $file_path, $title = 'เปิดไฟล์แนบ', $show_text = FALSE)
{
	$icon = getFileIcon($file_path);
	if ($icon == 'picture.png') {
		$link = '<a class="file_link" target="_blank" title="' . $title . '" href="' . site_url('file/preview/') . ci_encrypt($file_path) . '">';
		$link .= '<img id="' . $input_name . '_preview" height="150px" src="' . base_url() . '' . $file_path . '" />';
		$link .= '</a>';
	} else {
		$link = setAttachLink($input_name, $file_path, $title, $show_text);
	}
	return $link;
}

function setAttachLink($input_name, $file_path, $title = 'เปิดไฟล์แนบ', $show_text = FALSE)
{
	$text_link = '';
	$btn_class = '';
	if ($show_text == TRUE) {
		$text_link = '&nbsp; ' . $title;
		$btn_class = ' btn btn-warning';
	}

	$icon = getFileIcon($file_path);
	if ($icon != 'file_not_found.png') {

		$link = '<a class="file_link' . $btn_class . '" target="_blank" title="' . $title . '" href="' . site_url('file/preview/') . ci_encrypt($file_path) . '">';
		$link .= '<img id="' . $input_name . '_preview" class="link-file-attach" height="80"  src="' . base_url() . 'assets/images/icon/' . $icon . '" />' . $text_link;
		$link .= '</a>';
	} else {
		$link = '<a href="javascript:alert(\'ไม่พบไฟล์แนบ\')">';
		$link .= '<img id="' . $input_name . '_preview" class="link-file-attach" height="80" src="' . base_url() . 'assets/images/icon/' . $icon . '" />';
		$link .= '</a>';
	}
	return $link;
}

function setAttachLinkText($input_name, $file_path, $title = 'เปิดไฟล์แนบ')
{
	return setAttachLink($input_name, $file_path, $title, TRUE);
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
//extract array
function resultarray($array1, $explode_array)
{
	$array2 = explode(',', $explode_array);
	$array = array();

	for ($i = 0; $i < count($array2); $i++) {
		$array[] = '<span class="mb-1 g-chip g-chip-small info">' . $array1[$array2[$i]] . '</span>';
	}

	return implode(' ', $array);
}

//status
function switchstatusshow($status_approve = null, $c_class = null)
{  // function to switch status
	$status = '';
	switch ($status_approve) {
		case "approve":
			$status = '<img src="{base_url}assets/images/icon/approve.png"  style="height:29px" title="' . l('nav_Pass') . '">';
			break;
		case "reconsider":
			$status =  '<img src="{base_url}assets/images/icon/reconsider.png" class="dropdown-toggle dropdown-toggle-split ' . $c_class . '" id="' . $c_class . '" ria-expanded="false" data-bs-reference="parent" style="height:29px" title="' . l('gts.status.reconsider') . '">';
			break;
		case "reject":
			$status =  '<img src="{base_url}assets/images/icon/reject.png" class="dropdown-toggle dropdown-toggle-split ' . $c_class . '" id="' . $c_class . '" ria-expanded="false" data-bs-reference="parent" style="height:29px" title="' . l('gts.status.rejected') . '">';
			break;
		default:
			$status =  '<img src="{base_url}assets/images/icon/wating.png" class="dropdown-toggle dropdown-toggle-split ' . $c_class . '" id="' . $c_class . '" ria-expanded="false" data-bs-reference="parent" style="height:29px" title="' . l('nav_wait') . '">';
	}
	return $status;
}
//status
function switchstatusshowNew($status_approve = null, $c_class = null)
{  // function to switch status
	$status = '';
	// $status_approve = "reconsider";
	switch ($status_approve) {
		case "approve":
			$status = '<i class="fas fa-clipboard-check n-text-color--success" style="font-size: 29px" title="' . l('nav_Pass') . '"></i>';
			break;
		case "reconsider":
			$status = '<i class="fas fa-clipboard-check n-text-color--reconsider" style="font-size: 29px" title="' . l('nav_Pass') . '"></i>';
			// $status =  '<img src="{base_url}assets/images/icon/reconsider.png" class="dropdown-toggle dropdown-toggle-split ' . $c_class . '" id="' . $c_class . '" ria-expanded="false" data-bs-reference="parent" style="height:29px" title="ตรวจสอบอีกครั้ง">';
			break;
		case "reject":
			$status =  '<img src="{base_url}assets/images/icon/reject.png" class="dropdown-toggle dropdown-toggle-split ' . $c_class . '" id="' . $c_class . '" ria-expanded="false" data-bs-reference="parent" style="height:29px" title="' . l('gts.status.rejected') . '">';
			break;
		default:
			$status =  '<img src="{base_url}assets/images/icon/wating.png" class="dropdown-toggle dropdown-toggle-split ' . $c_class . '" id="' . $c_class . '" ria-expanded="false" data-bs-reference="parent" style="height:29px" title="' . l('nav_wait') . '">';
	}
	// $status =  '<img src="{base_url}assets/images/icon/reconsider.png" class="dropdown-toggle dropdown-toggle-split ' . $c_class . '" id="' . $c_class . '" ria-expanded="false" data-bs-reference="parent" style="height:29px" title="ตรวจสอบอีกครั้ง">';
	return $status;
}
function switchstatusedit($status_approve = null, $c_class = null, $id = null, $approve_id = null, $pd_id = null)
{  // function to switch status
	$status = '';
	switch ($status_approve) {
		case "approve":
			$status = '<div class="btn-group mt-1 d-flex" role="group"><button type="button" class="btn btn-link dropdown-toggle dropdown-toggle-split ' . $c_class . '" id="app_' . $c_class . '" data-bs-toggle="dropdown" aria-expanded="false" data-bs-reference="parent"><img src="{base_url}assets/images/icon/approve.png"  style="height:29px" title="' . l('nav_Pass') . '"></button>
							<div class="dropdown-menu" aria-labelledby="' . 'app_' . $c_class . '">
								<a href="javascript:void(0);" class="dropdown-item chgstatus" data-id="' . $id . '" data-approve_id="' . $approve_id . '" data-pd_id="' . $pd_id . '"   data-value="approve" href="#"><i class="fas fa-check-circle text-success fa-1x" title="' . l('hrm.payroll.pass') . '"></i> ' . l('hrm.payroll.pass') . '</a>
								<a href="javascript:void(0);" class="dropdown-item chgstatus" data-id="' . $id . '" data-approve_id="' . $approve_id . '" data-pd_id="' . $pd_id . '"  data-value="reconsider" href="#"><img src="{base_url}assets/images/icon/reconsider.png" style="height:20px" title="' . l('gts.status.reconsider') . '"> ' . l('gts.status.reconsider') . '</a>
								<a href="javascript:void(0);" class="dropdown-item chgstatus" data-id="' . $id . '" data-approve_id="' . $approve_id . '" data-pd_id="' . $pd_id . '"  data-value="reject" href="#"><img src="{base_url}assets/images/icon/reject.png" style="height:20px" title="' . l('gts.status.rejected') . '"> ' . l('gts.status.rejected') . '</a>
								
							</div>
						</div>';
			break;
		case "reconsider":
			$status =  '<div class="btn-group mt-1 d-flex" role="group"><button type="button" class="btn btn-link dropdown-toggle dropdown-toggle-split ' . $c_class . '" id="rec_' . $c_class . '" data-bs-toggle="dropdown" aria-expanded="false" data-bs-reference="parent"><img src="{base_url}assets/images/icon/reconsider.png"   style="height:29px" title="' . l('gts.status.reconsider') . '"></button>
							<div class="dropdown-menu" aria-labelledby="' . 'rec_' . $c_class . '">
								<a href="javascript:void(0);" class="dropdown-item chgstatus" data-id="' . $id . '" data-approve_id="' . $approve_id . '" data-pd_id="' . $pd_id . '"   data-value="approve" href="#"><i class="fas fa-check-circle text-success fa-1x" title="' . l('hrm.payroll.pass') . '"></i> ' . l('hrm.payroll.pass') . '</a>
								<a href="javascript:void(0);" class="dropdown-item chgstatus" data-id="' . $id . '" data-approve_id="' . $approve_id . '" data-pd_id="' . $pd_id . '"   data-value="reconsider" href="#"><img src="{base_url}assets/images/icon/reconsider.png" style="height:20px" title="' . l('gts.status.reconsider') . '"> ' . l('gts.status.reconsider') . '</a>
								<a href="javascript:void(0);" class="dropdown-item chgstatus" data-id="' . $id . '" data-approve_id="' . $approve_id . '" data-pd_id="' . $pd_id . '"   data-value="reject" href="#"><img src="{base_url}assets/images/icon/reject.png" style="height:20px" title="' . l('gts.status.rejected') . '"> ' . l('gts.status.rejected') . '</a>
								
							</div>
						</div>';
			break;
		case "reject":
			$status =  '<div class="btn-group mt-1 d-flex" role="group"><button type="button" class="btn btn-link dropdown-toggle dropdown-toggle-split ' . $c_class . '" id="rej_' . $c_class . '" data-bs-toggle="dropdown" aria-expanded="false" data-bs-reference="parent"><img src="{base_url}assets/images/icon/reject.png"  style="height:29px" title="' . l('gts.status.rejected') . '"></button>
							<div class="dropdown-menu" aria-labelledby="' . 'rej_' . $c_class . '">
								<a href="javascript:void(0);" class="dropdown-item chgstatus" data-id="' . $id . '" data-approve_id="' . $approve_id . '" data-pd_id="' . $pd_id . '"   data-value="approve" href="#"><i class="fas fa-check-circle text-success fa-1x" title="' . l('hrm.payroll.pass') . '"></i> ' . l('hrm.payroll.pass') . '</a>
								<a href="javascript:void(0);" class="dropdown-item chgstatus" data-id="' . $id . '" data-approve_id="' . $approve_id . '" data-pd_id="' . $pd_id . '"   data-value="reconsider" href="#"><img src="{base_url}assets/images/icon/reconsider.png" style="height:20px" title="' . l('gts.status.reconsider') . '"> ' . l('gts.status.reconsider') . '</a>
								<a href="javascript:void(0);" class="dropdown-item chgstatus" data-id="' . $id . '" data-approve_id="' . $approve_id . '" data-pd_id="' . $pd_id . '"   data-value="reject" href="#"><img src="{base_url}assets/images/icon/reject.png" style="height:20px" title="' . l('gts.status.rejected') . '"> ' . l('gts.status.rejected') . '</a>
								
							</div>
						</div>';
			break;
		default:
			$status =  '<div class="btn-group mt-1 d-flex" role="group"><button type="button" class="btn btn-link dropdown-toggle dropdown-toggle-split ' . $c_class . '" id="war_' . $c_class . '" data-bs-toggle="dropdown" aria-expanded="false" data-bs-reference="parent"><img src="{base_url}assets/images/icon/wating.png" style="height:29px" title="' . l('nav_wait') . '"></button>
							<div class="dropdown-menu" aria-labelledby="' . 'war_' . $c_class . '">
								<a href="javascript:void(0);" class="dropdown-item chgstatus" data-id="' . $id . '" data-approve_id="' . $approve_id . '" data-pd_id="' . $pd_id . '"    data-value="approve" href="#"><i class="fas fa-check-circle text-success fa-1x" title="' . l('hrm.payroll.pass') . '"></i> ' . l('hrm.payroll.pass') . '</a>
								<a href="javascript:void(0);" class="dropdown-item chgstatus" data-id="' . $id . '" data-approve_id="' . $approve_id . '" data-pd_id="' . $pd_id . '"     data-value="reconsider" href="#"><img src="{base_url}assets/images/icon/reconsider.png" style="height:20px" title="' . l('gts.status.reconsider') . '"> ' . l('gts.status.reconsider') . '</a>
								<a href="javascript:void(0);" class="dropdown-item chgstatus" data-id="' . $id . '" data-approve_id="' . $approve_id . '" data-pd_id="' . $pd_id . '"     data-value="reject" href="#"><img src="{base_url}assets/images/icon/reject.png" style="height:20px" title="' . l('gts.status.rejected') . '"> ' . l('gts.status.rejected') . '</a>
								
							</div>
						</div>';
	}
	return $status;
}

//status badge windows
function switchstatusbadgeshow($status_approve = null, $c_class = null)
{  // function to switch status
	$status = '';
	switch (true) {
		case ($status_approve == "approve" || $status_approve == "Approved"):
			$status = '<span class="g-chip success ' . $c_class . '">' . lang('nav_Pass') . '</span>';
			break;
		case ($status_approve == "pending" || $status_approve == "Pending"):
			$status = '<span class="g-chip warning ' . $c_class . '">' . lang('nav_wait') . '</span>';
			break;
		case ($status_approve == "reject" || $status_approve == "Rejected"):
			$status = '<span class="g-chip danger ' . $c_class . '">' . lang('nav_nopass') . '</span>';
			break;
		case ($status_approve == "ปกติ" || $status_approve == "Normal"):
			$status = '<span class="g-chip info ' . $c_class . '">' . lang('sup_normal') . '</span>';
			break;
		case ($status_approve == "สาย" || $status_approve == "Late"):
			$status = '<span class="g-chip danger ' . $c_class . '">' . lang('nav_late') . '</span>';
			break;
		case ($status_approve == "ขาดงาน"):
			$status = '<span class="g-chip danger ' . $c_class . '">' . lang('nav_absent') . '</span>';
			break;
		case ($status_approve == "ออกก่อน"):
			$status = '<span class="g-chip ' . $c_class . '">' . lang('nav_leaveEarly') . '</span>';
			break;
		case ($status_approve == "ลา"):
			$status = '<span class="g-chip warning ' . $c_class . '">' . lang('nav_leavec') . '</span>';
			break;
		case ($status_approve == "สาย, ออกก่อน"):
			$status = '<span class="g-chip danger ' . $c_class . '">' . lang('nav_late') . ", " . lang('nav_leaveEarly') . '</span>';
			break;
		case ($status_approve == "พักงาน"):
			$status = '<span class="g-chip danger ' . $c_class . '">' . "พักงาน" . '</span>';
			break;
		default:
			$status = '';
	}
	return $status;
}

//status badge mobile
function switchstatusbadgeshow_mobile($status_approve = null, $c_class = null)
{  // function to switch status
	$status = '';
	switch (true) {
		case ($status_approve == "approve" || $status_approve == "Approved"):
			$status = '<span class="badge rounded-pill bg-success ' . $c_class . '">' . lang('nav_Pass') . '</span>';
			break;
		case ($status_approve == "pending" || $status_approve == "Pending"):
			$status = '<span class="badge rounded-pill bg-warning text-dark ' . $c_class . '">' . lang('nav_wait') . '</span>';
			break;
		case ($status_approve == "reject" || $status_approve == "Rejected"):
			$status = '<span class="badge rounded-pill  bg-danger ' . $c_class . '">' . lang('nav_nopass') . '</span>';
			break;
		case ($status_approve == "ปกติ" || $status_approve == "Normal"):
			$status = '<span class="badge rounded-pill bg-primary ' . $c_class . '">' . lang('sup_normal') . '</span>';
			break;
		case ($status_approve == "สาย" || $status_approve == "Late"):
			$status = '<span class="badge rounded-pill bg-danger ' . $c_class . '">' . lang('nav_late') . '</span>';
			break;
		case ($status_approve == "ขาดงาน"):
			$status = '<span class="badge rounded-pill bg-danger ' . $c_class . '">' . lang('nav_absent') . '</span>';
			break;
		case ($status_approve == "ออกก่อน"):
			$status = '<span class="badge rounded-pill bg-secondary ' . $c_class . '">' . lang('nav_leaveEarly') . '</span>';
			break;
		case ($status_approve == "ลา"):
			$status = '<span class="badge rounded-pill bg-warning text-dark ' . $c_class . '">' . lang('nav_leavec') . '</span>';
			break;
		case ($status_approve == "สาย, ออกก่อน"):
			$status = '<span class="badge rounded-pill bg-danger text-dark ' . $c_class . '">' . lang('nav_late') . ", " . lang('nav_leaveEarly') . '</span>';
			break;
		case ($status_approve == "พักงาน"):
			$status = '<span class="badge rounded-pill bg-danger text-dark' . $c_class . '">' . "พักงาน" . '</span>';
			break;
		default:
			$status = '';
	}
	return $status;
}

//เพิ่มโดยออฟ ใช้สำหรับส่ง api ไปทำการตัดพื้นหลังใน remove.bg 11/05/65
function send_removebg($file_path)
{
	$apiURL = "https://api.remove.bg/v1.0/removebg";
	//api key สร้างโดยพี่โชค
	$arr_key_api_removebg = [
		'S983XKriGXW6q3SkMDdnsYCQ',
		'bP1kZUvZFmJFETqcwmk6rybH'
	];

	foreach ($arr_key_api_removebg as $value) {
		//CURL
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $apiURL);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
			"x-api-key: {$value}",
		]);
		curl_setopt($ch, CURLOPT_POSTFIELDS, [
			'image_url' => $file_path,
		]);
		$server_output = curl_exec($ch);
		$message_output = json_decode($server_output);
		curl_close($ch);

		//ตรวจสอบว่ามีเออเรอหรือไม่
		if (isset($message_output->errors)) {
			if ($message_output->errors[0]->code != "insufficient_credits") {
				return array("message_output" => $message_output->errors[0]->title);
			}
		} else {
			return array("message_output" => "success", "img" => $server_output);
		}
	}
}

//เพิ่มโดยออฟ ใช้สำหรับเรียกปุ่มสำหรับ mobile 28/05/65
function get_button($action = "", $class = "", $id = "", $name = "", $type = "button")
{
	$html_button = "";
	$id = ($id) ? "id='$id'" : "";
	$name = ($name) ? "name='$name'" : "";

	if ($action == "back") {
		$html_button = "<button type='$type' class='g-btn g-btn-dark $class' $name $id data-bs-dismiss='modal'>
							<i class='fas fa-chevron-left pe-2'></i> " . lang('nav_back') . "
						</button>";
	} else if ($action == "cancel") {
		$html_button = "<button type='$type' class='g-btn g-btn-dark $class' $name $id data-bs-dismiss='modal'>&nbsp;"
			. lang('nav_cancel') .
			"&nbsp;</button>";
	} else if ($action == "send") { //ใช้สำหรับ evaluate ด้วย
		$html_button = "<button type='$type' class='g-btn g-btn-info $class' $name $id>&nbsp;"
			. lang('nav_send') .
			"&nbsp;</button>";
	} else if ($action == "save") { //ใช้สำหรับ evaluate ด้วย
		$html_button = "<button type='$type' class='g-btn g-btn-info $class' $name $id>&nbsp;"
			. lang('nav_save') .
			"&nbsp;</button>";
	} else if ($action == "confirm") { //ใช้สำหรับ evaluate ด้วย
		$html_button = "<button type='$type' class='g-btn g-btn-info $class' $name $id>&nbsp;"
			. lang('nav_confirm') .
			"&nbsp;</button>";
	} else if ($action == "approve") { //ใช้สำหรับ evaluate ด้วย
		$html_button = "<button type='$type' class='btn btn-approve-eva $class' $name $id>&nbsp;"
			. lang('nav_Pass') .
			"&nbsp;</button>";
	} else if ($action == "reject") { //ใช้สำหรับ evaluate ด้วย
		$html_button = "<button type='$type' class='btn btn-reject-eva $class' $name $id>&nbsp;"
			. lang('nav_nopass') .
			"&nbsp;</button>";
	} else if ($action == "reconsider") { //ใช้สำหรับ evaluate ด้วย
		$html_button = "<button type='$type' class='btn btn-reconsider-eva $class' $name $id>&nbsp;"
			. lang('nav_Resonsider') .
			"&nbsp;</button>";
	} else if ($action == "file") {
		$html_button = "<div class='g-btn g-btn-info border-0 btn-file px-4'>
			<i class='fas fa-paperclip'></i> " . lang('nav_attachfile') . "
			<input class='$class' type='file' name='" . $name . "[]' id='$id' multiple='multiple'>
		</div>";
	}
	return $html_button;
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

// ฟังก์ชั่นคำนวณในโปรแกรม payroll
function caltax($salary_sum = null, $result_tax, $pd_id = null)
{
	$CI = &get_instance();
	$mydb = $CI->db;
	$result_deduct = $mydb->query("SELECT sum_money_true FROM geerang_hrm.deduction WHERE pd_id = $pd_id")->row();
	$deduct_tax = 0;
	if (!empty($result_deduct)) {
		$deduct_tax = $result_deduct->sum_money_true;
	}
	if ($salary_sum) {
		$tax_cal = ($salary_sum * 12);
		$discut_person =  ($tax_cal * 0.50);  //หัก 50 % ไม่เกิน 100000 บาท 
		if ($discut_person > 100000) {
			$discut_person = 100000;
		}
		//หักลดหย่อน

		if ($deduct_tax > 0) {
			$discut_self = $deduct_tax;
		} else {
			$discut_self = 60000;
		}
		$sso = 9000;

		$sum_cal = $tax_cal - ($discut_person + $discut_self + $sso);



		$cal_tax = 0;
		foreach ($result_tax as $key => $val) {
			//    print_r($result_tax);
			if ($sum_cal >  5000000) {
				$cal_percent = 0.35;
				$cal_tax  = ($sum_cal * $cal_percent);
			} else if ($sum_cal >= $val['salary_from']  && $sum_cal < $val['salary_to']) {
				if ($val['result_text'] != 'ได้รับการยกเว้น') {
					// echo $key.$val['result_text'].$sum_cal.'<br>';
					$ar_sum = [];
					for ($i = 0; $i < $key; $i++) {
						$ar_sum[] = $result_tax[$i]['pay_step'];
					}
					// print_r($ar_sum); 
					$cal_total_tax = ($sum_cal - $val['salary_from']) . '<br>';

					$cal_percent = (str_replace('%', '', $val['result_text']) / 100);
					$cal_tax  = ($cal_total_tax * $cal_percent) + array_sum($ar_sum);
				} else {
					$cal_tax = 0;
				}
			}
		}
		return ($cal_tax > 0 ? $cal_tax / 12 : 0);
	}
}
function newCalulateTax($salary = null, $arr_all_new = 0, $result_tax, $pd_id = null, $payroll_id = null)
{
	$CI = &get_instance();
	$mydb = $CI->db;
	$result_deduct = $mydb->query("SELECT sum_money_true FROM geerang_hrm.deduction WHERE pd_id = $pd_id")->row();
	$get_start_work = $mydb->query("SELECT DATEDIFF(CURDATE(), a.start_work) as date FROM geerang_hrm.personalsecret a WHERE a.pd_id = ? AND a.company_id = ?", [$pd_id, $CI->session->userdata('company_id')])->row();
	$deduct_tax = 0;
	if (!empty($result_deduct)) {
		$deduct_tax = $result_deduct->sum_money_true;
	}
	$month = floor($get_start_work->date / 30);
	// Performance Incentives

	// $my_salary = $salary * ($month >= 12 ? 12 : $month);
	$my_salary = $salary * 12;
	$range_date = [date("Y") . '-01', date("Y") . '-12'];
	$payroll_exp = $mydb->query(
		"SELECT * FROM geerang_hrm.payroll a WHERE a.status = 'approve' AND 
								a.com_id = ? 
								AND a.payroll_range_end BETWEEN ? AND ? 
								 AND MONTH(a.payroll_range_end) < MONTH((SELECT aa.payroll_range_end FROM geerang_hrm.payroll aa WHERE aa.id = ?))
								 AND MONTH(?) >= MONTH((SELECT aa.payroll_range_end FROM geerang_hrm.payroll aa WHERE aa.id = ?)) 
								 AND FIND_IN_SET(?, a.pd_freeze_id)",
		[$CI->session->userdata('company_id'), $range_date[0] . '-01', $range_date[1] . '-01', $payroll_id, $range_date[1] . '-01', $payroll_id, $pd_id]
	)->result();

	$sum = 0;
	foreach ($payroll_exp as $key => $value) {
		$data_e = $mydb->query("SELECT
									a.*,b.price, c.tax, c.tax_sum,c.tax_before_sum  
								FROM
									geerang_hrm.payroll_freeze_field a
								LEFT JOIN geerang_hrm.payroll_freeze_salary b ON
									a.payroll_id = b.payroll_id AND b.payroll_setting_id = a.payroll_setting_id 
								LEFT JOIN geerang_hrm.payroll_freeze_personaldata d ON d.payroll_id = a.payroll_id AND b.pd_id = d.pd_id 
								LEFT JOIN geerang_hrm.payroll_tax c ON c.payroll_id = a.payroll_id AND c.idcard = d.id_card 
								WHERE a.payroll_id = ? AND a.cal_static = 2 AND b.pd_id = ?", [$value->id, $pd_id])->result();
		$payroll_exp[$key]->keep_tmp = $data_e;
		$sum += array_sum(array_map(fn ($e) => $e->price, $data_e));
	}

	$_my_salary = $my_salary;
	if ($arr_all_new) {
		$_my_salary += $arr_all_new + $sum;
	}

	$discut_person =  ($_my_salary * 0.50);  //หัก 50 % ไม่เกิน 100000 บาท 
	if ($discut_person > 100000) {
		$discut_person = 100000;
	}
	if ($deduct_tax > 0) {
		$discut_self = $deduct_tax;
	} else {
		$discut_self = 60000;
	}
	$sso = ($salary >= 15000 ? 750 : $salary * 0.05) * ($month >= 12 ? 12 : $month); // ?
	$sum_cal = $_my_salary - ($discut_person + $discut_self + $sso);

	$cal_tax = findingTaxTable($sum_cal, $result_tax);
	$base_salary = findingTaxTable($my_salary - ($discut_person + $discut_self + $sso), $result_tax);
	$cal_tax = round($cal_tax['sum']);

	// if($pd_id == 747) {
	// 	echo '<pre>';
	// 	print_r($sum_cal);
	// 	echo '<pre>';print_r($discut_person);
	// 	echo '<pre>';print_r($discut_self);
	// 	echo '<pre>';print_r($sso);
	// 	echo '<pre>';print_r($my_salary);
	// 	echo '<pre>';print_r($my_salary - ($discut_person + $discut_self + $sso));
	// 	die;
	// }
	$last_month = $payroll_exp[count($payroll_exp) - 1]->keep_tmp[0]->tax_before_sum ?? 0;
	$remove_form_last_month = $cal_tax - $last_month;
	$summary = $remove_form_last_month + ($last_month == 0 ? 0 : $base_salary['avg']);

	return [
		"avg" => $summary > 0 ? ($summary / ($last_month == 0 ? 12 : 1)) : 0,
		"sum" => ($summary > 0 ? $summary : 0),
		'before_sum' => $cal_tax ?? 0,
	];
}
function newCalulateTaxV2($salary = null, $arr_all_new = 0, $result_tax, $pd_id = null, $payroll_id = null)
{
	$CI = &get_instance();
	$mydb = $CI->db;
	$result_deduct = $mydb->query("SELECT sum_money_true FROM geerang_hrm.deduction WHERE pd_id = $pd_id")->row();
	$get_start_work = $mydb->query("SELECT DATEDIFF(CURDATE(), a.start_work) as date, b.desso FROM geerang_hrm.personalsecret a
									LEFT JOIN geerang_hrm.employee_type b ON a.employee_type_id = b.id
									WHERE a.pd_id = ? AND a.company_id = ?", [$pd_id, $CI->session->userdata('company_id')])->row();
	$current_payroll = $mydb->query("SELECT * FROM geerang_hrm.payroll a WHERE a.id = ?", [$payroll_id])->row();
	$deduct_tax = 0;
	if (!empty($result_deduct)) {
		$deduct_tax = $result_deduct->sum_money_true;
	}

	// ไปดูว่าอยู่กลุ่ใที่มีการไม่คำนวณประกันสังคมมั้ย
	$current_month = (int)date_format(date_create($current_payroll->payroll_range_end), 'n');
	$month_counting = 12 - ($current_month - 1);

	$my_salary = $salary * $month_counting;
	$month = floor($get_start_work->date / 30);
	$month = $month > 12 ? 12 : $month;

	// Performance Incentives

	// $my_salary = $salary * ($month >= 12 ? 12 : $month);
	$range_date = [date("Y") . '-01', date("Y") . '-12'];
	$payroll_exp = $mydb->query(
		"SELECT a.*, GROUP_CONCAT(a.id) as p_ids FROM geerang_hrm.payroll a WHERE (a.status = 'approve' OR a.status = 'import') AND
								a.com_id = ?
								AND a.payroll_range_end BETWEEN ? AND ?
								 AND MONTH(a.payroll_range_end) < MONTH((SELECT aa.payroll_range_end FROM geerang_hrm.payroll aa WHERE aa.id = ?))
								 AND MONTH(?) >= MONTH((SELECT aa.payroll_range_end FROM geerang_hrm.payroll aa WHERE aa.id = ?))
								 AND FIND_IN_SET((SELECT apd.id_card FROM geerang_gts.personaldocument apd WHERE apd.pd_id = ?), (SELECT GROUP_CONCAT(ab.idcard) FROM geerang_hrm.payroll_tax ab WHERE ab.payroll_id = a.id))
								 GROUP BY MONTH(a.payroll_range_end)",
		[$CI->session->userdata('company_id'), $range_date[0] . '-01', $range_date[1] . '-01', $payroll_id, $range_date[1] . '-01', $payroll_id, $pd_id]
	)->result();
	$import_only = array_filter($payroll_exp, function ($e) {
		return $e->status == 'import';
	});
	$count_more = [];
	$import_salary = 0;
	$import_commision = 0;
	foreach ($import_only as $key => $value) {
		for ($i = date_format(date_create($value->payroll_range_start), 'n'); $i <= date_format(date_create($value->payroll_range_end), 'n'); $i++) {
			// code...
			if ($i > $month_counting) continue;
			$count_more[] = $i;
		}
	}
	$count_more = count(array_unique($count_more));
	$import_salary = $salary * $count_more;

	// print_r($mydb->last_query());die;
	$month_divine = 12 - ($current_month - 1 - ((count($payroll_exp) - count($import_only)) + $count_more));
	$sum = 0;
	$sum_used_salary = 0;

	// if(count($payroll_exp) == 0) {
	// 	$data_a = $mydb->query("SELECT b.*
	// 							FROM geerang_hrm.payroll_freeze_personaldata b
	// 							WHERE b.payroll_id = ? AND b.pd_id = ?", [$payroll_id, $pd_id])->row();
	// 	$sum_used_salary = (float)$data_a->salary * $current_month;
	// }

	foreach ($payroll_exp as $key => $value) {
		if ($value->id == $payroll_id) continue;

		foreach (explode(',', $value->p_ids) as $key2 => $val) {
			if ($value->status == 'import') {
				$data_a = $mydb->query("SELECT
											a.*, a.base_salary as _salary
										FROM
											geerang_hrm.payroll_tax a
										WHERE a.payroll_id = ? AND a.idcard = (SELECT apd.id_card FROM geerang_gts.personaldocument apd WHERE apd.pd_id = ?)", [$val, $pd_id])->result();
			} else {
				$data_a = $mydb->query("SELECT
											a.*, b.salary as _salary
										FROM
											geerang_hrm.payroll_tax a
										LEFT JOIN geerang_hrm.payroll_freeze_personaldata b ON
											b.payroll_id = a.payroll_id
											AND b.id_card = a.idcard
										WHERE b.payroll_id = ? AND b.pd_id = ?", [$val, $pd_id])->result();
			}
			$sum_used_salary += array_sum(array_map(fn ($e) => $e->base_salary, $data_a));
		}


		// $sum_used_salary += $import_salary;

		$data_e = $mydb->query("SELECT
									a.*,b.price, c.tax, c.tax_sum,c.tax_before_sum
								FROM
									geerang_hrm.payroll_freeze_field a
								LEFT JOIN geerang_hrm.payroll_freeze_salary b ON
									a.payroll_id = b.payroll_id AND b.payroll_setting_id = a.payroll_setting_id
								LEFT JOIN geerang_hrm.payroll_freeze_personaldata d ON d.payroll_id = a.payroll_id AND b.pd_id = d.pd_id
								LEFT JOIN geerang_hrm.payroll_tax c ON c.payroll_id = a.payroll_id AND c.idcard = d.id_card
								WHERE a.payroll_id = ? AND a.cal_static = 2 AND a.payroll_type = 'income' AND b.pd_id = ?", [$value->id, $pd_id])->result();

		$payroll_exp[$key]->keep_tmp = $data_e;
		$sum += array_sum(array_map(fn ($e) => $e->price, $data_e));
	}

	if ($import_salary) {
		if ($sum_used_salary > $import_salary) {
			$sum += $sum_used_salary - $import_salary;
			$sum_used_salary = $import_salary;
			$_my_salary_not_count = $my_salary + $sum + $sum_used_salary;
		}
	}

	$_my_salary = $my_salary + $sum + $sum_used_salary;

	if ($arr_all_new) {
		$_my_salary_not_count = $my_salary + $sum + $sum_used_salary;
		$_my_salary += $arr_all_new;
	}

	$discut_person =  ($_my_salary * 0.50);  //หัก 50 % ไม่เกิน 100000 บาท
	if ($discut_person > 100000) {
		$discut_person = 100000;
	}
	if ($deduct_tax > 0) {
		$discut_self = (float)$deduct_tax;
	} else {
		$discut_self = 60000;
	}

	$sso = 0;
	if (!$get_start_work->desso) {
		$sso = ($salary >= 15000 ? 750 : $salary * 0.05) * $month; // ?
	}

	$sum_cal = $_my_salary - ($discut_person + $discut_self + $sso);

	$cal_tax = findingTaxTable($sum_cal, $result_tax);
	$cal_before_sum_with_commission = findingTaxTable($_my_salary_not_count - ($discut_person + $discut_self + $sso), $result_tax);
	$base_salary = findingTaxTable(($sum_used_salary + $my_salary) - ($discut_person + $discut_self + $sso), $result_tax);

	$cal_tax = round($cal_tax['sum']);

	// $last_month = 0;
	// $last_month = $payroll_exp[count($payroll_exp) - 1]->keep_tmp[0]->tax_before_sum ?? 0;
	// $remove_form_last_month = $cal_tax - $last_month;
	// $summary = $remove_form_last_month + ($last_month == 0 ? 0 : $base_salary['avg']);
	$summary = 0;
	$sum = $base_salary['sum'];
	if ($_my_salary_not_count > 0) {
		$summary = $cal_tax - $cal_before_sum_with_commission['sum'];
		if ($summary > 0) {
			$summary += $base_salary['avg'];
		} else {
			$summary = $base_salary['sum'] / ($month_divine);
			$sum = $base_salary['sum'];
		}
	} else {
		$summary = $cal_tax / ($month_divine);
		$sum = $cal_tax;
	}

	return [
		"avg" => $summary > 0 ? ($summary) : 0,
		"sum" => ($sum > 0 ? $sum : 0),
		'before_sum' => $cal_tax ?? 0,
	];
}

function findingTaxTable($salary, $result_tax)
{
	$sum_cal = $salary;
	$cal_tax = 0;
	foreach ($result_tax as $key => $val) {
		if ($sum_cal >  5000000) {
			$cal_percent = 0.35;
			$cal_tax  = (float)($sum_cal * $cal_percent);
		} else if ($sum_cal >= $val['salary_from']  && $sum_cal < $val['salary_to']) {
			if ($val['result_text'] != 'ได้รับการยกเว้น') {
				$ar_sum = [];
				for ($i = 0; $i < $key; $i++) {
					$ar_sum[] = $result_tax[$i]['pay_step'];
				}
				$cal_total_tax = ($sum_cal - (float)$val['salary_from']);
				// print_r($sum_cal . ' - ' . $val['salary_from'] . ' = ' .$cal_total_tax . ' || ');

				$cal_percent = (str_replace('%', '', $val['result_text']) / 100);
				$cal_tax  = (float)($cal_total_tax * $cal_percent) + (float)array_sum($ar_sum);
			} else {
				$cal_tax = 0;
			}
		}
	}
	$cal_tax = round($cal_tax);

	return [
		"avg" => ($cal_tax > 0 ? $cal_tax / 12 : 0),
		"sum" => ($cal_tax > 0 ? $cal_tax : 0),
	];
}

function callate($salary = null, $day = null, $c_type = null, $type = null, $num = null)
{
	// var_export(func_get_args());
	if ($c_type == 'hour') {
		$total_salary = (($salary / 60) * $num);
	} else if ($c_type == 'day') {
		$total_salary = ((($salary / $day) / 60)) * $num;
	} else if ($c_type == 'month') {
		$total_salary = (((($salary / $day) / 8) / 60)) * $num;
	}

	if ($type == 'late') {
		if ($type == 'late' && ($c_type == 'month' ||  $c_type == 'day')) {
			$data['late'] = $total_salary;
		} else {
			$data['late'] = 0;
		}
	}

	if ($type == 'early') {
		if ($type == 'early' && ($c_type == 'month' ||  $c_type == 'day')) {
			$data['early'] = $total_salary;
		} else {
			$data['early'] = 0;
		}
	}

	return $data;
}


function calabsent($salary = null, $day = null, $c_type = null, $type = null, $num = null)
{
	// var_export(func_get_args());
	if ($c_type == 'hour') {
		$total_salary = (($salary / 60) * $num);
	} else if ($c_type == 'day') {
		$total_salary = ((($salary / $day) / 60)) * $num;
	} else if ($c_type == 'month') {
		$total_salary = (((($salary / $day) / 8) / 60)) * $num;
	}

	if ($type == 'absent' && ($c_type == 'month')) {
		$data['absent'] = $total_salary;
	} else {
		$data['absent'] = 0;
	}
	return $data;
}

function calleave($salary = null, $day = null, $c_type = null, $type = null, $num = null, $list = [])
{
	// var_export(func_get_args());
	if ($c_type == 'hour') {
		$total_salary = (($salary / 60));
	} else if ($c_type == 'day') {
		$total_salary = ((($salary / $day) / 60));
	} else if ($c_type == 'month') {
		$total_salary = (((($salary / $day)) / 60));
	}
	$total = 0;
	foreach ($list as $key => $value) {
		$amount_time = $value->amount_time + (float)$value->break_time > 8 ? 8 :  $value->amount_time;
		$amount = ($value->amount * 60) * (($amount_time * 60));
		$sum = $value->amount == 1 ? $amount / $value->avg_shift : $amount;
		$total += ($total_salary / ($c_type == 'month' ? $amount_time : 1)) * ($sum / 60);
	}

	if ($type == 'leave' && ($c_type == 'month')) {
		$data['leave'] = $total;
	} else {
		$data['leave'] = 0;
	}
	return $data;
}

function calleave_outside($salary = null, $day = null, $c_type = null, $type = null, $num = null)
{
	// var_export(func_get_args());
	if ($c_type == 'hour') {
		$total_salary = (($salary / 60) * $num);
	} else if ($c_type == 'day') {
		$total_salary = ((($salary / $day) / 60)) * $num;
	} else if ($c_type == 'month') {
		$total_salary = (((($salary / $day) / 8) / 60)) * $num;
	}

	if ($type == 'leave_outside') {
		$data['leave'] = $total_salary;
	} else {
		$data['leave'] = 0;
	}
	return $data;
}



function check_ottime($salary = null, $day = null, $c_type = null, $time = null, $exc = null)
{
	$total_salary = 0;
	if ($c_type == 'hour') {
		$total_salary = (($salary / 60));
	} else if ($c_type == 'day') {
		$total_salary = ((($salary / $day) / 60));
	} else if ($c_type == 'month') {
		$total_salary = (((($salary / $day) / 8) / 60));
	}
	$data = ($total_salary * $time) * $exc;

	return $data;
}

function timeToSeconds(string $time): int
{
	$arr = explode(':', $time);
	if (count($arr) === 3) {
		return $arr[0] * 3600 + $arr[1] * 60 + $arr[2];
	}
	return $arr[0] * 60 + $arr[1];
}

function cal_off($opater = null)
{
	$result = "";

	foreach ($opater as $key => $value) {
		$temp1 = $value['num1'];
		$temp2 = $value['ope1'];
		$temp3 = $value['num2'];
		$temp4 = $value['ope2'];
		$temp5 = $value['num3'];
		$c = eval("return $temp1 $temp2 $temp3;");
		$r = eval("return $c $temp4 $temp5;");
		//   print_r("<br>" . '(' . $temp1 . $temp2 . $temp3 . ')' .$temp4 . $temp5);        
		//   print_r(" result = " . $r);          

		$result = ($value['res'] == "") ? (($r == false) ? "0.00" : $r) : ($r == false ? '0.00' : $value['res']);
		if (!($r === false) || $r) {
			return $result;
		}
	}

	return $result;
}

function check_salary($group_type = null, $salary = null, $day, $hour, $start_day = null, $endday = null)
{
	$total_salary = 0;
	$perday = 0;
	if ($group_type == 'hour') {
		$pmin = ($salary / 60);
		$arr = explode(':', $hour);
		$total_salary = ($salary * $arr[0]) + ($pmin * $arr[1]);
		$perday = $salary * 8;
	} else if ($group_type == 'day') {
		$total_salary = ($day * $salary);
		$perday = $salary;
	} else if ($group_type == 'month') {
		$total_salary = ($salary / 30) * monthDateDiff($start_day, $endday)['daydeduct'];
		$perday = $salary / 30;
	}

	$ar = [
		// 'pday' => ($pday?$pday:0),
		// 'pmin' => ($pmin?$pmin:0),
		// 'phour' => $phour,
		'perday' => $perday,
		'salary' => $salary,
		'total_salary' => $total_salary,
	];


	return $ar;
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
// check sso ออกหลายรอบในเดือนเดียวไม่ให้ตัดซ้ำ 
function checksso($pd_id = null, $month = null)
{
	$CI = &get_instance();
	$mydb = $CI->db;
	$com_id = $CI->session->userdata('company_id');
	$result = $mydb->query(" SELECT
									sum(pt.pay_support) AS sumsso,
									pt.payroll_id,
									pd.pd_id,
									(SUM(pt.base_salary) - SUM(pt.deduct_early) - SUM(pt.deduct_absent) - SUM(pt.deduct_leave) - SUM(pt.deduct_late) - SUM(pt.outcome_sumsso) + SUM(pt.income_sumsso)) AS salarySUM
								FROM
									geerang_hrm.payroll_tax pt
								LEFT JOIN geerang_gts.personaldocument pd ON pd.id_card = pt.idcard
								WHERE
									DATE_FORMAT(pt.pay_date,'%Y-%m')= '$month' 
								AND pt.company_id = $com_id
								AND pd.pd_id = '$pd_id'  
								GROUP BY pt.payroll_id
								ORDER BY pt.pay_date ASC 
        					")->result_array();
	$sum_sso = 0;
	$result_sso = [];
	foreach ($result as $row) {
		$result_sso[$row['pd_id']] = ['sumsso' => $row['sumsso'], 'salary' => $row['salarySUM']];
	}

	return $result_sso;
}
//check return 	
function check_return_sso($ar_check = null, $max_value = null, $value_check = null)
{
	$check_sum = [];
	$return_sum = 0;
	for ($i = 1; $i <= count($ar_check); $i++) {
		$check_sum[] = $ar_check[$i];
	}
	$check_sum = array_sum($check_sum);
	if ($check_sum >= $max_value) {
		$return_sum = 0;
	} else if ($check_sum < $max_value) {
		$return_sum = $max_value - $check_sum;
	}
	return $return_sum;
}

function checksso_condition()
{
}
// สิ้นสุดฟังชั่น payroll

function announce_priority($priority = null)
{
	if ($priority == 'High') {
		return 'danger';
	} else if ($priority == 'Medium') {
		return 'warning';
	} else if ($priority == 'Normal') {
		return 'success';
	}
}

function check_pms_free5user($app_id = 4)
{
	$CI = &get_instance();
	$result_order = $CI->db->order_by('package_end DESC')->get_where('geerang_gts.orders', array('application_id' => $app_id, 'company_id' => $CI->session->userdata('company_id')), 1)->row();

	if (!empty($result_order->order_id)) {
		$result = $CI->db->query("SELECT ogl.*,pg.package_type 
						FROM geerang_gts.order_get_list ogl 
						LEFT JOIN geerang_gts.package pg ON pg.package_id = ogl.package_id
						WHERE
						pg.package_type = 'FREE2'
						AND ogl.order_id = {$result_order->order_id}")->row();
		if (!empty($result)) {
			return true;
		}
	}
	return false;
}

function switchstatusbadgeshowEss($status_approve = null, $c_class = null, $id_track = null, $status_track = null)
{  // function to switch status

	$status = '';
	switch (true) {
		case ($status_approve == 'cancelled' || $status_approve == 'Cancelled'):
			$status = '<span class="g-chip font--s g-chip-small danger ' . $c_class . '">' . lang('nav_cancel') . '</span>';
			break;
		case (($status_approve == "approve" || $status_approve == "Approved") || (!empty($id_track) && $status_track == 'approve')):
			$status = '<span class="g-chip font--s g-chip-small success ' . $c_class . '">' . lang('nav_Pass') . '</span>';
			break;
		case ((($status_approve == "pending" || $status_approve == "Pending") && empty($id_track)) || (!empty($id_track) && $status_track == 'pending')):
			$status = '<span class="g-chip font--s g-chip-small warning ' . $c_class . '">' . lang('nav_wait') . '</span>';
			break;
		case ($status_approve == "reject" || $status_approve == "Rejected"):
			$status = '<span class="g-chip font--s g-chip-small danger ' . $c_class . '">' . lang('nav_nopass') . '</span>';
			break;
		default:
			$status = '';
	}
	return $status;
}


function switchstatusbadgeshowEss_mobile($status_approve = null, $c_class = null, $id_track = null, $status_track = null)
{  // function to switch status

	$status = '';
	switch (true) {
		case ($status_approve == "reject" || $status_approve == "Rejected" || $status_approve == "Cancelled" || $status_approve == 'cancelled'):
			$status = 'bg-danger';
			break;
		case (($status_approve == "approve" || $status_approve == "Approved") || (!empty($id_track) && $status_track == 'approve')):
			$status = 'bg-success';
			break;
		case ((($status_approve == "pending" || $status_approve == "Pending") && empty($id_track)) || (!empty($id_track) && $status_track == 'pending') && $status_approve != "Cancelled"):
			$status = 'bg-yellow';
			break;
		default:
			$status = '';
	}
	return $status;
}

function createMonth()
{
	// wait for change eng text to multi language
	$month = ['', lang('nav_jan'), lang('nav_feb'), lang('nav_mar'), lang('nav_apr'), lang('nav_may'), lang('nav_jun'), lang('nav_jul'), lang('nav_aug'), lang('nav_sep'), lang('nav_oct'), lang('nav_nov'), lang('nav_dec')];
	return $month;
}

function convertDateSave($date)
{
	return date('Y-m-d', strtotime($date));
};


//create by hit choke 12/01/66 
function calNumOfWork($pd_id = NULL, $com_id = NULL)
{
	$CI = &get_instance();
	$mydb = $CI->db;
	$start_work = $mydb->query("SELECT ABS(DATEDIFF(start_work,curdate())) AS count_workday, start_work
                                FROM geerang_hrm.personalsecret
                                WHERE pd_id = ?
                                AND company_id = ?", [$pd_id, $com_id])->row('start_work');

	return date_diff_string($start_work, 'now', true);
}

function date_diff_string($from, $to, $full = false)
{
	$from = new DateTime($from);
	$to = new DateTime($to);
	$diff = $to->diff($from);

	$string = array(
		'y' => lang('nav_year'),
		'w' => lang('nav_week'),
		'm' => lang('nav_month'),
		'd' => lang('nav_datea'),
	);
	foreach ($string as $k => &$v) {
		if ($diff->$k) {
			$v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? '' : '');
		} else {
			unset($string[$k]);
		}
	}

	if (!$full) $string = array_slice($string, 0, 1);
	return $string ? implode(', ', $string) : 'เริ่มวันแรก';
}

function loadWorkdayLastyear($pd_id, $com_id)
{
	try {
		$CI = &get_instance();
		$mydb = $CI->db;

		$end_lastyear = date("Y-m-d", strtotime("last year December 31st"));

		$result = $mydb->query("SELECT ABS(DATEDIFF(start_work, ?)) AS count_workday
                                            FROM geerang_hrm.personalsecret t1
                                            LEFT JOIN geerang_hrm.employee_type t2
                                            ON t1.employee_type_id = t2.id 
                                            WHERE t1.company_id = ? 
                                            AND t1.pd_id = ?", [$end_lastyear, $com_id, $pd_id]);

		if ($result->num_rows() == 0) throw new Exception("", 1);

		return $result->row('count_workday');
	} catch (\Throwable $th) {
		return 0;
	}
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
function getUpline($company_id, $element): array
{
	$CI = &get_instance();

	$obj = $element[sizeof($element) - 1];
	$result = $CI->db->get_where('geerang_gts.user_position', array('position_id' => $obj->position_id))->row();

	if (!empty($result)) {
		if ($result->position_parent) {
			$check_pd = $CI->db->get_where('geerang_gts.position_keep', array('position_id' => $result->position_parent))->row();
			$arr = array_values(array_map(function ($e) {
				return $e->pd_id;
			}, $element));
			if (!empty($check_pd) && !in_array($check_pd->pd_id, $arr)) {
				$element[] = (object) [
					'pd_id' => $check_pd->pd_id,
					'position_id' => $check_pd->position_id,
					// 'company_id' => $check_pd->user_id
				];
				$element = getUpline($company_id, $element);
				return $element;
			} else {
				return $element;
			}
		} else {
			$result->position_parent = $result->position_id;
		}
	}
	return $element;
}
function checkpdUp($company_id, $position_id)
{
	$CI = &get_instance();
	$result = $CI->db->get_where('geerang_gts.user_position', array('position_id' => $position_id))->row();
	if (!empty($result)) {
		if ($result->position_parent) {
			$check_pd = $CI->db->get_where('geerang_gts.position_keep', array('position_id' => $result->position_parent))->row();
			if (empty($check_pd)) {
				$id = checkpdUp($company_id, $result->position_parent);
				return $id;
			}
		} else {
			$result->position_parent = $result->position_id;
		}

		$CI->db->select('t2.pd_id');
		$CI->db->join('geerang_gts.personaldocument t2', 't1.pd_id = t2.pd_id', 'LEFT');
		$CI->db->from('geerang_gts.position_keep t1');
		$CI->db->where('t1.user_id', $company_id);
		$CI->db->where('t1.position_id', $result->position_parent);
		$CI->db->where('t2.pd_id!=', '');
		$CI->db->limit(1);
		$result_keep = $CI->db->get()->row();
	}
	$id = $result_keep->pd_id;
	return $id;
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

/**
 *  @param $app_module_id integer||array
 *  @param $option[sub_id] integer|| array
 *  @param $option[param]=url redirect page
 *  @param $option[param]=html,page,data,from return true:false
 *  @param $option[param]=class return d-none
 */
function modulecheck(
	$app_module_id,
	$option = [
		'param' => 'url',
		'sub_id' => null,
	]
) {
	$CI = &get_instance();
	$com_id = $CI->session->userdata('company_id');
	$CI->load->model('admin/Superadmin_model', 'admin');
	$CI->admin->packagemodule($com_id);

	$param = $option['param'] ? $option['param'] : "url";
	$paramOption1 = ['url', 'link'];
	$paramOption2 = ['page', 'html', 'from', 'data'];
	$paramOption3 = ['class'];

	$whereapp_id = '';

	if (gettype($app_module_id) == 'integer') {
		$whereapp_id = " AND  a.application_moduler_id = $app_module_id";
	}
	if (gettype($app_module_id) == 'array') {
		$appid = implode(',', $app_module_id);
		$whereapp_id = " AND  a.application_moduler_id IN ($appid)";
	}

	$check = true;


	$where = '';
	if ($option['sub_id']) {
		$sub_id = $option['sub_id'];
		if (gettype($sub_id) == 'integer') {
			$where .= " AND b.application_moduler_id = $sub_id AND b.is_open = 1";
		}
		if (gettype($sub_id) == 'array') {
			$sub_id = implode(',', $sub_id);
			$where .= " AND b.application_moduler_id IN ($sub_id) AND b.is_open = 1";
		}
	}

	$exits = $CI->db->query(
		"SELECT * 
		FROM geerang_gts.package_modules a 
		LEFT JOIN geerang_gts.package_modules_list b ON b.module_id = a.id AND b.com_id = a.com_id
		WHERE a.com_id = ? AND a.is_open = 1 $whereapp_id
		$where
		",
		[$com_id]
	)->result();

	if ($CI->session->userdata('error_text_permission')) {
		unset($_SESSION['error_text_permission']);
	}

	if (empty(($exits))) $check = false;

	if (in_array($param, $paramOption1) && !$check) {
		$CI->session->set_userdata('error_text_permission', 'Package ท่านยังไม่ได้เปิดใช้งาน Module นี้');
		redirect('/permissions/Hrmcheckpage/errorPermission', 'refresh');
	}
	if (in_array($param, $paramOption2)) {
		return 	$check;
	}


	if (in_array($param, $paramOption3)) {
		return  $check ? '' :	'd-none';
	}
}
function itemsetDnone($id, $param = 'html')
{
	$check =  modulecheck($id, ['param' =>  $param]);
	if ($param == 'html') {
		if (!$check) {
			return "style='display:none'";
		} else {
			return "style='display:flex'";
		}
	}
}

function checklogfile($logtype, $opt = [
	'application_id' => NULL,
	'application_module' => NULL,
	'fn' => NULL,
	'query' => NULL,
	'input' => NULL,
])
{
	$CI = &get_instance();

	$lastquery = $opt['query'] ? json_encode(['sql' => $opt['query']]) : null;
	$datainput = $opt['datainput'] ? json_encode($opt['datainput']) : null;

	$path = $opt['fn'] ? $opt['fn'] : $_SERVER['REQUEST_URI'];

	$http = (empty($_SERVER['HTTPS']) ? 'http' : 'https');
	$PATH_CHECK = [
		'/users/feed',
		"/users/dashboard",
		"/permissions/Hrmcheckpage",
		"/api/switchtheme",
		"/users/lifestyle/getApplication/",
		"//hrm/dashboard",
		"/hrm/dashboard",
		'hrm/import_emp/save_newimport',
		'hrm/import_emp/updatelog'
	];

	if (!in_array($_SERVER['REQUEST_URI'], $PATH_CHECK)) {
		$data = [
			'application_id'        =>  NULL,
			'application_module'    =>  NULL,
			'path_url'              => $path,
			'pd_id'                 => $CI->session->userdata('pd_id'),
			'com_id'                => $CI->session->userdata('company_id'),
			'admin_id'              => $CI->session->userdata('admin_id'),
			'fullname'              => fullname(),
			'superadmin'            => checksuperadmin(),
			'type_log'              => $logtype,
			'method_request'        => $_SERVER['REQUEST_METHOD'],
			'log_http'              => $http,
			'log_host'              => $_SERVER['HTTP_HOST'],
			'ip_address'            => get_client_ip(),
			'agent'                 => $_SERVER['HTTP_USER_AGENT'],
			'data_input'            => $datainput,
			'last_query'			=> $lastquery,
		];
		$CI->db->insert('geerang_gts.log_application', $data);
	}
}
function checksuperadmin()
{
	$CI = &get_instance();
	$data = NULL;
	$swap_id = $CI->session->userdata('encrypt_swap_from_pd_id');
	if ($swap_id) {
		$data = decrypt($swap_id);
	}
	return $data;
}
function fullname()
{
	$CI = &get_instance();
	$swap_id = checksuperadmin();
	if ($swap_id != NULL) {
		$dbget = $CI->db->get_where('geerang_gts.personaldocument', ['pd_id' => $swap_id]);
		$fname = $dbget->row('first_name');
		$lname = $dbget->row('last_name');
	} else {
		$fname = $CI->session->userdata('first_name') ? $CI->session->userdata('first_name') : $CI->session->userdata('first_name_en');
		$lname = $CI->session->userdata('last_name') ? $CI->session->userdata('last_name') : $CI->session->userdata('last_name_en');
	}

	return $fname . ' ' . $lname;
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
function div_video(
	$url,
	$opt = [
		'class' => "",
		'icon' => '',
		'text' => ""
	]
) {
	$icon = '<i class="fas fa-info-circle"></i>';
	if ($opt['icon']) {
		$icon = $opt['icon'];
	}
	$text = 'วิดีโอคู่มือสอนการใช้งาน ';
	if ($opt['text']) {
		$text .= $opt['text'];
	}
	$class = 'g-chip g-chip-small info ' . $opt['class'];
	echo "<a href='$url'><div class='$class'>$icon  $text</div></a>";
}
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

function getAutomate($com_id, $moduler = '')
{
	$CI = &get_instance();

	$filter = '';
	if ($moduler) {
		$filter .= " AND a.application_moduler_id = $moduler ";
	}

	$module =	$CI->db->query(
		"SELECT * 
		FROM geerang_gts.package_modules a
		LEFT JOIN geerang_gts.application_moduler b ON b.application_moduler_id = a.application_moduler_id 
		WHERE   a.com_id = $com_id  AND b.show_online = 1 $filter"
	)->result();
	$module = array_map(function ($e) use ($CI) {
		// $list = $CI->db->query(
		// 	"SELECT 
		// 	a.*,
		// 	b.application_rule_name,
		// 	c.* 
		// 	FROM geerang_gts.package_modules_list a
		// 	LEFT JOIN geerang_gts.application_moduler b ON b.application_moduler_id = a.application_moduler_id 
		// 	LEFT JOIN geerang_hrm.document_menu c ON c.application_module_id = b.application_moduler_id
		// 	WHERE   a.com_id = $e->com_id AND a.module_id = $e->id AND b.show_online = 1 AND b.show_menu=1  AND c.id <= 9 "
		// )->result();
		return [
			'fn' => $e->name_en,
			'name' => $e->name_th,
			'use' => (bool)$e->is_open,
			// 'list' => array_map(function ($ee) use ($e, $CI) {
			// 	if ($e->application_moduler_id == 108) {
			// 		return (object)[
			// 			'id' => (int)$ee->id,
			// 			'name' => $ee->name_th,
			// 			'type'	=> $ee->name_id,
			// 			'use'	=> (bool)$ee->is_open,
			// 		];
			// 	} else {
			// 		return (object) [
			// 			'name' => strip_tags($ee->application_rule_name),
			// 			'use' => (bool)$ee->is_open,
			// 		];
			// 	}
			// }, $list),
		];
	}, $module);
	return $module;
}
function getDownline($com_id, $position_id)
{
	$downline = checkDown($com_id, $position_id);
	$data = checkpdDown($com_id, $downline);
	return $data;
}
function checkDown($company_id, $position_id)
{
	$CI = &get_instance();
	$where = "";
	if ($company_id) {
		$where = " AND user_id = $company_id";
	}
	$query = $CI->db->query("SELECT up.position_parent as n_parent_id,up.position_id as id
							FROM
								geerang_gts.user_position up
							WHERE
								NOT up.position_id <=> up.position_parent
								$where
						GROUP BY
							n_parent_id,id");
	$result[] = $position_id;
	$result[] = buildPositionTree($query->result_array(), $position_id);
	$array = array_values(flatten($result));
	return $array;
}
function flatten(array $array, $prefix = "")
{
	$result = array();
	array_walk($array, function ($value, $key) use ($array, $prefix, &$result) {
		$path = $prefix ? "$prefix.$key" : $key;
		if (is_array($value)) {
			$result = array_merge($result, flatten($value, $path));
		} else {
			$result[$path] = $value;
		}
	});

	return $result;
}
function buildPositionTree(array $elements, $parentId = 0)
{
	$branch = array();
	foreach ($elements as $key => $element) {
		if (empty($element['n_parent_id'])) {
			$element['n_parent_id'] = 0;
		}
		if ($element['n_parent_id'] == $parentId && $element['n_parent_id'] != null) {

			$children = buildPositionTree($elements, $element['id']);
			if ($children) {
				$element['children'] = $children;
			}
			unset($element['n_parent_id']);

			if ($key == 0) {
				$branch = $element;
			} else {
				$branch[] = $element;
			}
		}
	}
	return $branch;
}
function checkpdDown($company_id, $position_id)
{
	$CI = &get_instance();
	if (!$company_id || !$position_id) return [];

	$CI->db->from('geerang_gts.user_position t1');
	$CI->db->join('geerang_gts.position_keep t2', "t1.position_id = t2.position_id ", 'LEFT');
	$CI->db->join('geerang_gts.personaldocument t3', 't3.pd_id = t2.pd_id', 'LEFT');
	$CI->db->where("t2.user_id", $company_id);
	$CI->db->where_in('t1.position_id', $position_id);
	$CI->db->where("t3.pd_id !=", '');
	$query = $CI->db->get();
	$pd_id = array();
	foreach ($query->result() as $val) {
		$pd_id[] = $val->pd_id;
	}
	$pd_id = array_unique($pd_id);
	return $pd_id;
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
function appNoti($notitype = '', int $pd_id, int $com_id, string $topic = '', string $body = '', string $url = '', array $option = [], bool $multiple = false, string $keyto = '', bool $uplineto = true)
{


	$ci = &get_instance();
	$db = $ci->db;
	$ci->load->model('AppNoti_model', 'AppNoti');
	$self_pd_id = $ci->session->userdata('pd_id');
	$upline_data = $ci->session->userdata('upline_all');


	if ($multiple === true) {
		if (!$keyto) return false;
		$ci->AppNoti->sendNotiMultiple(
			$topic,
			$body,
			$keyto,
			$com_id,
			$upline_data,
			$option
		);
	} else {

		// if (!$notitype) return false;
		$result_position = $db->query(
			"SELECT * FROM geerang_gts.position_keep a 
			LEFT JOIN geerang_gts.user_position b ON b.user_id = a.user_id AND b.position_id = a.position_id
			WHERE a.pd_id = ? AND a.user_id = ? AND b.position_id IS NOT NULL
			",
			[$pd_id, $com_id]
		)->row();
		$dept_id = $result_position->department_id;
		$where = "";
		if ($dept_id) $where .= " OR IF(a.department_id='all',TRUE,FIND_IN_SET($dept_id,a.department_id))";
		$exist = $db->query(
			"SELECT b.approve_pd_id
			FROM geerang_hrm.approve_setting  a
			LEFT JOIN geerang_hrm.approve_setting_approver b ON b.approve_setting_id = a.id
			WHERE a.company_id = ?
			AND a.type = '$notitype'
			AND (IF(a.pd_id='all',TRUE,FIND_IN_SET($pd_id,a.pd_id)) $where  )
			",
			[$com_id]
		)->result();

		if ($notitype && $exist) {
			foreach ($exist as $key => $val) {
				if ($val->approve_pd_id != 	$self_pd_id) {
					$position_id = $db->query("SELECT a.position_id FROM geerang_gts.position_keep a 
					LEFT JOIN geerang_gts.user_position b ON b.user_id = a.user_id AND b.position_id = a.position_id
					WHERE a.pd_id = ? AND a.user_id = ? AND b.position_id IS NOT NULL
					", [$val->approve_pd_id, $com_id])->row('position_id');

					$ci->AppNoti->send_noti(
						$topic,
						$body,
						$val->approve_pd_id,
						$com_id,
						$url,
						array_merge([
							'position_id' => 	$position_id,
						], $option)
					);
				}
			}
		} else {
			if ($uplineto === true) {
				foreach ($upline_data as $key => $val) {
					if ($key > 0 && $val->pd_id != 	$self_pd_id) {
						$ci->AppNoti->send_noti(
							$topic,
							$body,
							$val->pd_id,
							$com_id,
							$url,
							array_merge([
								'position_id' => $val->position_id,
							], $option)

						);
					}
				}
			}
		}
	}
}


function Avatar($firstname, $lastname)
{
	return "https://ui-avatars.com/api/?name=$firstname+$lastname&rounded=true&background=ffc000&color=fff&size=128";
}


/**
 * ? เก็บประวัติ เพิ่ม-ลบ บุคคลในตำแหน่ง
 * @param  $role Default:true | false 
 */
function logPosition(int $position_id, int $pd_id, bool $role = true, bool $deactive = false)
{
	$ci = &get_instance();
	$db = $ci->db;
	$com_id = $ci->session->userdata('company_id');


	$position_data = $db->select('up.position_name, ps.salary, eg.name as group_name')
		->from('geerang_gts.user_position up')
		->join('geerang_hrm.personalsecret ps', 'ps.company_id = up.user_id')
		->join('geerang_hrm.employee_group eg', 'eg.id = ps.employee_group_id')
		->where(array('position_id' => $position_id, 'ps.pd_id' => $pd_id))->get()->result();


	$position_log = $db->select('position_name, salary, employee_group_name')
		->from('geerang_gts.user_position_log')
		->where(array('pd_id' => $pd_id, 'com_id' => $com_id))->order_by('id', 'DESC')->limit(1)->get();

	if ($position_log->num_rows() > 0) {
		$position_log = $position_log->result();

		if ($role === false) {
			$explode = explode(',', $position_log[0]->position_name);
			$position_array = implode(",", array_diff($explode, [$position_data[0]->position_name]));
			if (empty($position_array)) {
				$position_array = null;
			}
			$msg = "ถูกนำออกจากตำแหน่ง ";
		} else {
			if (!empty($position_log[0]->position_name)) {
				$position_array = implode(',', array($position_log[0]->position_name, $position_data[0]->position_name));
			} else {
				$position_array = $position_data[0]->position_name;
			}
			$msg = "ถูกเพิ่มเข้าตำแหน่ง ";
		}
	} else {
		$position_array = $position_data[0]->position_name;
		$msg = "เริ่มงานในตำแหน่ง ";
	}
	if ($deactive) {
		$msg = 'ถูก Deavtive';
	}
	$insert_data = array(
		"pd_id" => $pd_id,
		"com_id" => $com_id,
		"position_id" => $position_id,
		"position_name" => $position_array,
		"salary" => $position_data[0]->salary,
		"comment" => $msg . $position_data[0]->position_name,
		"employee_group_name" => $position_data[0]->group_name,
		"created_date" => date("Y-m-d H:i:s"),
		"create_id" => $ci->session->userdata('pd_id'),
		'is_admin' => $ci->session->userdata('loginby'),
	);

	$db->insert('geerang_gts.user_position_log', $insert_data);
}
function getPasswordImport(int $pd_id)
{
	$ci = &get_instance();
	$db = $ci->db;
	$com_id = $ci->session->userdata('company_id');

	$idcard = $db->query(
		"SELECT * FROM geerang_gts.personaldocument WHERE pd_id = ?",
		[$pd_id]
	)->row('id_card');
	$result = $db->query("SELECT * 
	FROM geerang_hrm.employee_log_import 
	WHERE log_data IS NOT NULL AND com_id = ? ", [$com_id])->result();

	$result = array_map(function ($e) {
		return [
			'id' => $e->id,
			'data' => array_map(function ($e) {
				return $e->data;
			}, json_decode($e->log_data)),
		];
	}, $result);

	$data = [];
	foreach ($result as $key => $val) {
		foreach ($val['data'] as $k => $e) {
			if ($e->id_card == $idcard) {
				if ($e->username != 'มี Username ในระบบ,true') {
					$data = [
						'username' => $e->username,
						'password' => $e->password
					];
				}
			}
		}
	}
	if ($data) return (object)$data;
	return null;
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
function getPositionByPdId($pd_id, $com_id, $status)
{
	$CI = &get_instance();
	$db = $CI->db;

	$exist = $db->query("SELECT * FROM geerang_gts.position_keep WHERE pd_id =? AND user_id = ? AND default_position = 1 AND position_id != 0", [$pd_id, $com_id])->row();
	$filter = '';
	if ($exist) {
		$filter = " AND a.default_position = 1";
	}
	$result = $db->query(
		"SELECT * FROM geerang_gts.position_keep a
            LEFT JOIN geerang_gts.user_position b ON b.user_id = a.user_id AND a.position_id = b.position_id
            WHERE a.user_id = ? 
            AND a.pd_id = ? 
            AND a.position_id != 0 
            $filter
            ",
		[$com_id, $pd_id]
	)->result();

	$row = $result[0];
	if ($status == 'inactive') {
		$result = $db->query(
			"SELECT * FROM geerang_gts.user_position_terminate a
				WHERE a.user_id = ? 
				AND a.pd_id = ? 
				",
			[$com_id, $pd_id]
		)->result();
		$row = $result[count($result) - 1];
	}

	$b = $db->get_where('geerang_gts.branchs', ['branch_id' => $row->branch_id])->row();
	$d = $db->get_where('geerang_gts.department', ['department_id' => $row->department_id])->row();
	$s = $db->get_where('geerang_gts.sub_department', ['sub_id' => $row->sub_id])->row();

	$group = $db->query(
		"SELECT b.id,b.name 
		FROM geerang_hrm.personalsecret a 
            LEFT JOIN geerang_hrm.employee_group b ON b.id = a.employee_group_id AND a.company_id = b.com_id
            WHERE a.pd_id = ? AND a.company_id = ? ",
		[$pd_id, $com_id]
	)->row();
	$type = $db->query(
		"SELECT b.id,b.emp_type_name 
		FROM geerang_hrm.personalsecret a 
            LEFT JOIN geerang_hrm.employee_type b ON b.id = a.employee_type_id AND a.company_id = b.com_id
            WHERE a.pd_id = ? AND a.company_id = ? ",
		[$pd_id, $com_id]
	)->row();

	return (object)[
		'id' => (int)$com_id,
		'position' => (object)[
			"id" => (int)$row->position_id,
			'name' => $row->position_name ? $row->position_name : null
		],
		'depart' => (object)[
			"id" => $row->department_id ? (int)$row->department_id : null,
			'name' => $d->department_name ? $d->department_name : null
		],
		'branch' => (object) [
			"id" => (int)$row->branch_id,
			'name' => $b->branch_name ? $b->branch_name : null
		],
		'sub' => (object)[
			"id" => (int)$row->position_id,
			'name' => $s->sub_name ? $s->sub_name : null
		],
		'type' => (object) [
			"id" => (int)$type->id,
			'name' => $type->emp_type_name,
		],
		'group' => (object)[
			"id" => (int) $group->id,
			'name' =>  $group->name,
		],

	];
}
function getEmployeeById($pd_id, $com_id)
{
	$CI = &get_instance();
	$db = $CI->db;

	$result = $db->query(
		"SELECT *  FROM geerang_gts.personaldocument 
            WHERE pd_id = ?",
		[$pd_id]
	)->row();
	if (!$result) return null;
	$fname = $result->last_name_en ? $result->first_name_en : $result->first_name;
	$lname = $result->first_name_en ? $result->last_name_en : $result->last_name;

	$pathImage = Avatar($fname,  $lname);
	if ($result->picture && file_exists(FCPATH . $result->picture)) $pathImage = base_url($result->picture);
	$secret =  $db->query("SELECT * FROM geerang_hrm.personalsecret WHERE pd_id = ? AND company_id = ?", [$pd_id, $com_id])->row();

	$color = '';
	if (strtolower($secret->status) == 'active') {
		$color =	'success';
	}
	if (strtolower($secret->status) == 'inactive') {
		$color =	'red';
	}
	if (strtolower($secret->status) == 'pending') {
		$color =	'warning';
	}
	if (strtolower($secret->status) == 'blacklist') {
		$color =	'black';
	}
	$data = [
		'pd_id' => (int)$pd_id,
		'title' => (int)$result->title,
		'gender' => (int) $result->title == 1 ? 'male' : (in_array($result->title, [2, 3]) ? "female" : "unknown"),
		'fullname' => [
			'main' => $result->first_name . ' ' . $result->last_name,
			'en' => $result->first_name_en . ' ' . $result->last_name_en,
			'fname' => $result->first_name,
			'lname' => $result->last_name,
		],
		'username' => $result->username,
		'picture' =>  $pathImage,
		'secret' => [
			'_i' => (int) $secret->id,
		],
		'info' => [
			'code' => $secret->employee_code,
			'email' => $result->email,
			'mobile' => $result->phone_number,
			'status' => strtolower($secret->status),
			'color' => $color
		],
		'date' => [
			'start' => $secret->start_work,
			'end' => $secret->end_work ? $secret->end_work : null
		],
		'company' => getPositionByPdId($pd_id, $com_id, $secret->status),
	];

	return (object)$data;
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
function  getCurrentTimeAtten($pd_id, $com_id)
{
	// fix  COALESCE
	$CI = &get_instance();
	$db = $CI->db;
	$sql = "SELECT
				ROUND(
					SUM(
						IF(
							att.check_in IS NOT NULL AND att.check_out IS NOT NULL, 
							TIMESTAMPDIFF(MINUTE, att.check_in, att.check_out) 
							- IF(
								atts.first_break IS NULL, 
								COALESCE(att.minute_break, 0),
								COALESCE(TIMESTAMPDIFF(MINUTE, atts.first_break, atts.last_break), 0)
							),
							0 
						)
					) / 60
				) AS sum_time
			FROM geerang_hrm.attendances att
			LEFT JOIN geerang_hrm.attendance_setting atts ON atts.id = att.shift_id 
			WHERE att.emp_id = ? AND att.com_id = ? AND att.shift_type = 'normal' AND  date_no = CURRENT_DATE()
			GROUP BY DATE(att.date_no)
			ORDER BY att.date_no DESC
			LIMIT 1";

	$result = $db->query($sql, [$pd_id, $com_id]);
	if ($result->num_rows() == 0) return 8;
	$sumtime = $result->row('sum_time');
	if (!$sumtime)	return 8;
	return $sumtime;
}

function checkExpressionWorkingDays($exc, $start, $end, $pd_id, $com_id, $create_date = null)
{
	$CI = &get_instance();
	$db = $CI->db;
	$week_end = $start;
	$working_days = [];

	$created = !!$create_date ? " AND (a.updated_at <= '$create_date' OR a.updated_at IS NULL)" : '';

	do {
		$week_start = date('Y-m-d', strtotime('monday this week', strtotime('1 day', strtotime($week_end))));
		$week_end = date('Y-m-d', strtotime('sunday this week', strtotime('1 day', strtotime($week_end))));

		$get_attendances = $db->query("SELECT
								a.date_no, COUNT(a.a_id) as counting, SUM(IF((a.status IN ('ขาดงาน', 'ลา') OR a.status IS NULL) $created, 0, 1)) as total
							FROM
								geerang_hrm.attendances a
							WHERE
								a.com_id = ?
								AND a.emp_id = ?
								AND a.date_no BETWEEN ? AND ?
								AND a.shift_id != 0
							GROUP BY a.date_no", [$com_id, $pd_id, $week_start, $week_end])->result();
		$in = count(array_filter($get_attendances, fn ($e) => $e->total > 0));

		$is_in_pass = false;
		$exc[1] = '>=';
		$exc[2] = 1;
		if($exc[1] == '=') $is_in_pass = $in == $exc[2];
		if($exc[1] == '>=') $is_in_pass = $in >= $exc[2];
		if($exc[1] == '<=') $is_in_pass = $in <= $exc[2];
		if($exc[1] == '>') $is_in_pass = $in > $exc[2];
		if($exc[1] == '<') $is_in_pass = $in < $exc[2];

		$working_days[] = [
			'start' => $week_start,
			'end' => $week_end,
			'is_pass' => $is_in_pass,
		];
	} while($week_start < $end && $week_end < $end);
	
	return $working_days;
}

function calculateExpressionWorkingMoney ($exc, $salary_perday, $start, $end, $pd_id, $com_id, $create_date = null,$working_days = [])
{
	$cal_to = 0;
	if(count($working_days) > 0) {
		foreach ($working_days as $key => $range) {

			if($range['is_pass']) {
				foreach (array_slice($exc, 1) as $key => $value) {
					# code...
					$week_date = date('Y-m-d', strtotime("$value this week", strtotime($range['end'])));
					$cal_to += _getWorkingMinute($week_date, $com_id, $pd_id, $salary_perday, $create_date);
				}
			}
		}
	} else {
		$week_end = $start;
		do { 
			$week_start = date('Y-m-d', strtotime('monday this week', strtotime('1 day', strtotime($week_end))));
			$week_end = date('Y-m-d', strtotime('sunday this week', strtotime('1 day', strtotime($week_end))));

			foreach (array_slice($exc, 1) as $key => $value) {
				# code...
				$week_date = date('Y-m-d', strtotime("$value this week", strtotime($week_end)));
				$cal_to += _getWorkingMinute($week_date, $com_id, $pd_id, $salary_perday, $create_date);
			}

			$week_end = date('Y-m-d', strtotime('sunday this week', strtotime($week_end)));
		} while($week_start < $end && $week_end < $end);
	}
	return $cal_to;
}

function _getWorkingMinute ($week_date, $com_id, $pd_id, $salary_perday, $create_date = null)
{
	$CI = &get_instance();
	$db = $CI->db;

	$created = !!$create_date ? " AND (t1.updated_at <= '$create_date' OR t1.updated_at IS NULL)" : '';

	$hour_working = $db->query("SELECT
											t1.emp_id as pd_id,
											(( TIME_TO_SEC(IF(t1.check_out >= t1.time_out, t1.time_out, t1.check_out)) - TIME_TO_SEC(IF(t1.check_in <= t1.time_in, t1.time_in, t1.check_in)) ) / 60) 
											- IF(t1.first_break IS NOT NULL, (((TIME_TO_SEC(t1.last_break) - TIME_TO_SEC(t1.first_break)) / 60)), 0) as sum_hour
										FROM
											geerang_hrm.attendances AS t1  
										WHERE
											t1.com_id = ?
										AND time(check_in) <= time(time_in)
										AND t1.date_no = ?
										AND `status` != 'ขาดงาน'
										AND t1.emp_id = ?
										AND t1.time_in IS NOT NULL 
										AND t1.time_out IS NOT NULL
										$created
										GROUP BY
										t1.emp_id DESC ", [$com_id, $week_date, $pd_id])->row();
	if(!!$hour_working) {
		$hour = ($hour_working->sum_hour / 60);
		return (($salary_perday / 8)) * $hour;
	}
	return 0;
}