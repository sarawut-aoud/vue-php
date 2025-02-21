<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require APPPATH . 'libraries/RestAPI.php';
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE');

class Users extends RestAPI
{

    const TITLE_NAME = [
        '', 'นาย', 'นาง', 'นางสาว'
    ];
    const GENDER = [
        0 => 'ไม่ระบุ',
        1 => 'Male',
        2 => 'Female',
        3 => 'Female',
    ];
    public function __construct()
    {
        parent::__construct();
        $this->validateAuth();
    }

    public function index_get()
    {
        try {
            self::setRes(['msg' => 'Users API'], 200);
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }

    public function getUserProfile_get()
    {
        try {
            $payload = (object)$this->input->get();

            $valid = self::validPayload(
                $payload,
                ['emp_id', 'Employee ID', 'required'],
                ['com_id', 'Company ID', 'required'],
            );
            if ($valid) self::setErr($valid, 403);
            $this->save($payload);

            $com_exist = $this->db->query('SELECT * FROM geerang_gts.users a WHERE a.id = ?', [$payload->com_id])->row();
            if (!$com_exist) self::setErr('Not found company', 200);

            $emp_exist = $this->db->get_where('geerang_gts.personaldocument', ['pd_id' => $payload->emp_id])->row();
            if (!$emp_exist) self::setErr('Not found Employee', 200);

            $personal = $this->db->query("SELECT
                                        a.employee_code, 
                                        b.*
                                    FROM
                                        geerang_hrm.personalsecret a
                                    LEFT JOIN geerang_gts.personaldocument b ON
                                        b.pd_id = a.pd_id
                                    WHERE
                                        a.company_id = ? AND a.status = 'active' AND b.pd_id = ?
                                         ", [$payload->com_id, $payload->emp_id])->row();
            $result = (object)[
                'pd_id'         => (int) $personal->pd_id,
                'title'         => (int) $personal->title,
                'title_name'    => self::TITLE_NAME[$personal->title],
                'gender'        => self::GENDER[$personal->title],
                'nickname'      => $personal->nickname ? $personal->nickname : "",
                'firstname'     => $personal->first_name,
                'lastname'      => $personal->last_name,
                'fullname'      => $personal->first_name . ' ' .  $personal->last_name,
                'mobile'        => $personal->phone_number ? $personal->phone_number : "",
                'email'         => $personal->email ? $personal->email : "",
                'redirect'      => [
                    'view' => base_url('/users/profile'),
                    'edit' => base_url('users/profile/edit/' . encrypt($personal->pd_id))
                ],
                'Automate'      => getAutomate($payload->com_id)

            ];

            self::setRes($result, 200);
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }

    public function editUserProfile_post()
    {
        try {

            $payload = (object)$this->input->post();

            $Inarray = self::inArrayString($payload->param, [
                'first_name',
                'last_name',
                'tel',
                'facebook',
                'line',
                'email',
                'nickname'
            ]);
            if ($Inarray) self::setErr($Inarray, 403);
            $valid = self::validPayload(
                $payload,
                ['emp_id', 'Employee ID', 'required'],
                ['com_id', 'Company ID', 'required'],
                ['param', 'Parameter', 'required|is_array'],
                ['value', 'Value', 'required|is_array'],
            );
            if ($valid) self::setErr($valid, 403);

            $com_exist = $this->db->query('SELECT * FROM geerang_gts.users a WHERE a.id = ?', [$payload->com_id])->row();
            if (!$com_exist) self::setErr('Not found company', 200);

            $emp_exist = $this->db->get_where('geerang_gts.personaldocument', ['pd_id' => $payload->emp_id])->row();
            if (!$emp_exist) self::setErr('Not found Employee', 200);

            $where = [
                'pd_id' => $payload->emp_id,
            ];
            $setdata = self::setparameter($payload->param, $payload->value);

            if ($setdata['email']) {
                if (!filter_var($setdata['email'], FILTER_VALIDATE_EMAIL)) {
                    self::setErr('The email address is not valid.', 200);
                }
            }

            $result = $this->db->update('geerang_gts.personaldocument', $setdata, $where);
            checklogfile('update', [
                'application_id' => NULL,
                'application_module' => NULL,
                'fn' => 'API:editUserProfile_post ',
                'query' => $this->db->last_query(),
                'input' => $payload,
            ]);
            if (!$result) self::setErr('Internal Server Error,', 500);

            self::setRes(['No Content'], 200);
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }
    private function setparameter($param, $value)
    {
        $data = [];
        $convertParam = [
            'first_name'    => 'first_name',
            'last_name'     => 'last_name',
            'tel'           => 'phone_number',
            'facebook'      => 'facebook',
            'line'          => 'lineid',
            'nickname'      => "nickname",
            'email'         => "email"

        ];
        // $param = array_values(array_unique($param));
        foreach ($param as $key => $val) {
            $data[$convertParam[$val]] = trim($value[$key]);
        }

        return $data;
    }
    private function inArrayString($post, $field)
    {
        $text = '';
        foreach ($post as $key => $val) {
            if (!in_array($val, $field)) {
                $label =  implode(',', $field);
                $text = "Please input In $label";
            }
        }
        return   $text;
    }
}
