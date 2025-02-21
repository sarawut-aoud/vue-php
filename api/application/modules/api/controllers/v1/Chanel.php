<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require APPPATH . 'libraries/RestAPI.php';
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE');


class Chanel extends RestAPI
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('encryption');
    }

    public function index_get()
    {
        try {
            self::setRes(['msg' => 'Chanal API'], 200);
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }
    private  function unserializesession($data)
    {
        $vars = preg_split(
            '/([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff^|]*)\|/',
            $data,
            -1,
            PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE
        );
        for ($i = 0; $vars[$i]; $i++) $result[$vars[$i++]] = unserialize($vars[$i]);
        return $result;
    }
    public function userLoginWidget_post()
    {
        $this->validateAuth();

        try {
            $payload = (object)$this->input->post();



            $valid = self::validPayload(
                $payload,
                ['user_info', 'User Information ', 'required'],
            );
            if ($valid) self::setErr($valid, 403);

            

            if (!$payload->user_info) {
                self::setErr('Please INPUT user_info', 404);
            }

            $decode = explode('.', ($payload->user_info));
            $decode = array_map(function ($e) {
                return base64_decode($e);
            }, $decode)[1];

            $JWT =  (object) self::jsonStringToArray($decode);

            $com_exist = $this->db->query('SELECT * FROM geerang_gts.users a WHERE a.id = ?', [$JWT->com_id])->row();

            if (!$com_exist) self::setErr('Not found company', 200);

            $emp_exist = $this->db->query(
                "SELECT * FROM geerang_gts.personaldocument WHERE (pd_id = ? )",
                [$JWT->emp_id]
            )->row();
            if (!$emp_exist) self::setErr('Not found Employee', 200);


            $filter = '';
            if ($JWT->emp_id) {
                $filter .= " AND pd_id = $JWT->emp_id ";
            }

            $result = $this->db->query(
                "SELECT * FROM geerang_gts.personaldocument WHERE pd_id IS NOT NULL $filter",
                [$JWT->emp_id]
            )->row();

            $res = (object)[
                'com_id' => (int)$JWT->com_id,
                'emp_id' => (int)$result->pd_id,
                'first_name' =>   $result->first_name,
                'last_name' => $result->last_name,
                'fullname' => $result->first_name . ' ' . $result->last_name,
                'email' => $result->email,
                'tel' => $result->phone_number,
                'Automate' => getAutomate($JWT->com_id),
            ];
            self::setRes($res, 200);
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }
    private function jsonStringToArray($jsonString)
    {
        // Decode the JSON string to an associative array
        $array = json_decode($jsonString, true);

        // Check if decoding was successful
        if (json_last_error() === JSON_ERROR_NONE) {
            return $array;
        } else {
            // Handle error if JSON is invalid
            return 'Invalid JSON string: ' . json_last_error_msg();
        }
    }

    public function CheckLineID_get()
    {
        $this->validateAuth();

        try {
            $payload = (object)$this->input->get();
            $valid = self::validPayload(
                $payload,
                ['user_id', 'User Line ID', 'required'],
            );
            if ($valid) self::setErr($valid, 403);
            $this->save($payload);

            $emp_exist = $this->db->query("SELECT * 
            FROM geerang_gts.personaldocument 
            WHERE line_ai_id = ? ", [trim($payload->user_id)])->row();
            // if (!$emp_exist) self::setRes('Not found User Line ID', 200);

            if (!$emp_exist->line_ai_id) {
                $url = base_url('api/v1/chanel/login?uid=' . $payload->user_id);
                $res = [
                    'register' => (bool)false,
                    'emp_id' => null,
                    'message' => 'Not Registered',
                    'redirect' => $url,
                ];
            } else {
                $current = $this->db->query(
                    "SELECT * FROM geerang_gts.position_keep  WHERE pd_id = ? AND default_position = 1",
                    [$emp_exist->pd_id]
                )->row();

                $res = [
                    'register'      => (bool)true,
                    'message'       => 'Already Registered',
                    'redirect'      => null,
                    'user_id'       => $emp_exist->line_ai_id,
                    'emp_id'        => (int)$emp_exist->pd_id,
                    'com_id'        => (int) $current->user_id,
                    'position_id'   => (int) $current->position_id,
                ];
            }


            self::setRes($res, 200);
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }
    public function Register_post()
    {
        $this->load->model('api/Stmotp_model', 'otp');
        $this->validateAuth();

        try {
            self::setErr('Unavaliable wait a moment.', 500);

            $payload = (object)$this->input->post();
            $valid = self::validPayload(
                $payload,
                ['user_id', 'User Line ID', 'required'],
                ['com_id', 'Company ID', 'required'],
                ['emp_id', 'Employee ID', 'required'],
            );
            if ($valid) self::setErr($valid, 403);
            $this->save($payload);

            $com_exist = $this->db->query('SELECT * FROM geerang_gts.users a WHERE a.id = ?', [$payload->com_id])->row();
            if (!$com_exist) self::setErr('Not found company', 404);

            $emp_exist = $this->db->get_where('geerang_gts.personaldocument', ['pd_id' => $payload->emp_id])->row();
            if (!$emp_exist) self::setErr('Not found Employee', 404);


            $user_exist = $this->db->get_where('geerang_gts.personaldocument', ['pd_id' => $payload->emp_id])->row();

            if (!$user_exist->lineid) {
                $this->db->update('geerang_gts.personal_otp', ['status' => 'timeout', 'is_active' => 0], ['pd_id' => $payload->emp_id]);

                $exist_otp = $this->db->query(
                    "SELECT * FROM geerang_gts.personal_otp  
                        WHERE pd_id = ? AND  NOW() <= expire AND status = 'active'
                        ",
                    [$user_exist->pd_id]
                )->row();

                if (!$exist_otp) {
                    $send = $this->otp->sendOTP($user_exist->email, [
                        'pd_id' => $user_exist->pd_id,
                        'user_id' => $payload->user_id,
                    ]);
                    if (!$send['status']) self::setErr($send['msg'], 404);
                    $otp = $send['otp'];
                    $date = $send['date_expire'];
                    self::setRes((object)[
                        'otp' =>  (int)$otp,
                        'expire' => [
                            'datetime' => date_format(date_create($date), "Y-m-d\TH:i:s\z"),
                            'date' => date_format(date_create($date), "Y-m-d"),
                            'time' => date_format(date_create($date), "H:i:s"),
                        ]
                    ], 200);
                } else {
                    $res =  (object)[
                        'user_id' => trim($payload->user_id),
                        'vertify_otp' => (bool)  $exist_otp->status == 'active' ? true : false,
                    ];
                    self::setRes($res, 200);
                }
            } else {
                self::setErr('Already have a User ID', 404);
            }

            // $result = $this->db->update('geerang_gts.personaldocument', ['lineid' => trim($payload->user_id)], ['pd_id' => $payload->emp_id]);




        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }
    public function checkOtp_get()
    {
        $this->validateAuth();
        try {
            self::setErr('Unavaliable wait a moment.', 500);

            $payload = (object)$this->input->get();
            $valid = self::validPayload(
                $payload,
                ['otp', 'OTP', 'required'],
                ['emp_id', 'Employee_ID', 'required'],
                ['com_id', 'Company ID', 'required'],
            );
            if ($valid) self::setErr($valid, 403);
            $this->save($payload);

            $com_exist = $this->db->query('SELECT * FROM geerang_gts.users a WHERE a.id = ?', [$payload->com_id])->row();
            if (!$com_exist) self::setErr('Not found company', 200);

            $emp_exist = $this->db->get_where('geerang_gts.personaldocument', ['pd_id' => $payload->emp_id])->row();
            if (!$emp_exist) self::setErr('Not found Employee', 200);


            $vertify = false;

            $exist_otp = $this->db->query(
                "SELECT * FROM geerang_gts.personal_otp  
                    WHERE pd_id = ? AND  NOW() <= expire AND `status` IS NULL
                    ",
                [$payload->emp_id]
            )->row();

            $exits_line = $this->db->get_where('geerang_gts.personaldocument', ['pd_id' => $payload->emp_id])->row();
            if (!$exist_otp &&  !$exits_line->lineid) {
                $this->db->update('geerang_gts.personal_otp', ['status' => 'timeout', 'is_active' => 0], ['pd_id' => $payload->emp_id]);
                $msg = "OTP Timeout !!! ";
            } else {
                $otpid = null;
                $exist_vertify = $this->db->get_where('geerang_gts.personal_otp', ['id' =>  $otpid, 'pd_id' => $payload->emp_id])->row();
                if ($exist_vertify->status == 'active') {
                    $msg = "Have Already Vertify";
                    $vertify = true;
                } elseif ($exist_vertify->status == 'timeout') {
                    $msg = "OTP Timeout !!! ";
                } else {
                    $check_vertify = pass_secure_verify($payload->otp, $exist_otp->otp);
                    if ($check_vertify) {
                        $vertify = true;
                        $this->db->update('geerang_gts.personaldocument', ['lineid' => $exist_otp->log_lineid], ['pd_id' => $payload->emp_id]);
                        $this->db->update('geerang_gts.personal_otp', ['status' => 'active', 'is_active' => 1], ['id' =>  $otpid, 'pd_id' => $payload->emp_id]);
                        $msg = 'Vertify Success';
                    } else {
                        $msg = 'OTP is incorrect';
                    }
                }
            }

            // $result = $this->db->get_where('geerang_gts.personaldocument', ['pd_id' => $emp_exist->pd_id])->row();

            // $exist_otp = $this->db->get_where('geerang_gts.personal_otp', ['pd_id' => $result->pd_id, 'is_active' => 1, 'status' => 'active'])->row();
            // $res = [
            //     'is_otp' => (bool)$exist_otp->is_active,
            //     'otp'   => (int)decryption($exist_otp->otp_str)
            // ];
            $res = (object)[
                'verify' => (bool) $vertify,
                'message' => $msg,
            ];
            self::setRes($res, 200);
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }

    public function DeleteLine_post()
    {
        $this->validateAuth();
        $com_init = implode(',', [
            354,
            389,
            165,
        ]);
        try {
            $payload = (object)$this->input->post();
            $valid = self::validPayload(
                $payload,
                ['emp_id', 'Employee ID', 'required'],
            );
            if ($valid) self::setErr($valid, 403);
            $this->save($payload);


            $emp_exist = $this->db->get_where('geerang_gts.personaldocument', ['pd_id' => $payload->emp_id])->row();
            if (!$emp_exist) self::setErr('Not found Employee', 200);

            // $exist_com = $this->db->query(
            //     "SELECT * FROM geerang_hrm.personalsecret WHERE pd_id = ? AND company_id IN(?)",
            //     [$payload->emp_id, $com_init]
            // )->row();

            // if ($exist_com) self::setErr('Not Delete Line User ID', 404);

            $result =   $this->db->update('geerang_gts.personaldocument', ['line_ai_id' => NULL], ['pd_id' => $payload->emp_id]);
            checklogfile('update', [
                'application_id' => NULL,
                'application_module' => NULL,
                'fn' => 'API:Delete_line_post',
                'query' => $this->db->last_query(),
                'input' => $payload,
            ]);

            $res = [
                'delete' => (bool) $result,
            ];
            self::setRes($res, 200);
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }

    public function login_get()
    {
        $this->another_css .= '<link rel="stylesheet" href="' . base_url() . 'assets/plugins/vuetify/vuetify@3.0.3.css">';
        $this->another_css .= '<link href="https://cdn.jsdelivr.net/npm/@mdi/font@5.x/css/materialdesignicons.min.css" rel="stylesheet">';
        $this->another_js .= '<script src="' . base_url('/assets/js_modules/api/login.js?ft=' . time()) . '"></script>';

        $payload = (object)$this->input->get();

        $exist = $this->db->query("SELECT * 
        FROM geerang_gts.personaldocument a 
        LEFT JOIN geerang_gts.personal_otp b ON b.log_lineid = a.line_ai_id 
        WHERE a.line_ai_id = ? 
        AND b.is_active = 1 AND b.status = 'active'
        ", [$payload->uid])->row();

        if (!$exist) {
            self::render_view('api/previewLogin');
        } else {
            self::render_view('api/preview_alert');
        }
    }

    public function Verify_get()
    {
        try {
            $payload = (object)$this->input->get();
            $this->another_css .= '<link rel="stylesheet" href="' . base_url() . 'assets/plugins/vuetify/vuetify@3.0.3.css">';
            $this->another_css .= '<link href="https://cdn.jsdelivr.net/npm/@mdi/font@5.x/css/materialdesignicons.min.css" rel="stylesheet">';
            $this->another_js .= '<script src="' . base_url('/assets/js_modules/api/login.js?ft=' . time()) . '"></script>';

            $payload->pd_id = explode('.', $payload->_d)[0];
            $payload->line_id = explode('.', $payload->_d)[1];

            if (!$payload->pd_id ||  !$payload->line_id) self::setErr('Not Found data');

            $exist = $this->db->query("SELECT * 
                FROM geerang_gts.personaldocument a 
                LEFT JOIN geerang_gts.personal_otp b ON b.log_lineid = a.line_ai_id 
                WHERE a.line_ai_id = ? 
                AND b.is_active = 1 AND b.status = 'active'
                ", [decrypt($payload->line_id)])->row();
            if ($exist) redirect('api/v1/chanel/login?uid=' . decrypt($payload->line_id));

            self::render_view('api/previewVerify');
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }
    protected function render_view($path)
    {
        $this->data['base_url'] = base_url();
        $this->data['csrf_token_name'] = $this->security->get_csrf_token_name();
        $this->data['csrf_cookie_name'] = $this->config->item('csrf_cookie_name');
        $this->data['csrf_protection_field'] = insert_csrf_field(true);
        $template_name = 'logintemp';
        $this->data['page_content'] = $this->parser->parse_repeat($path, $this->data, true);
        $this->data['another_css'] = $this->another_css;
        $this->data['another_js'] = $this->another_js;
        $this->parser->parse('template/' . $template_name . '/homepage_view', $this->data);
    }

    public function loginProcess_post()
    {
        $this->load->model('Users_login_model', 'Login');
        $this->load->model('api/Stmotp_model', 'otp');

        try {
            $payload = (object)$this->input->post();
            $valid = self::validPayload(
                $payload,
                ['username', 'username', 'required'],
                ['password', 'password', 'required'],
                ['uid', 'Line user id', 'required'],
            );
            if ($valid) self::setErr($valid, 200);
            $this->save($payload);


            $exist = $this->db->query('SELECT * FROM geerang_gts.personaldocument a 
            WHERE a.username = ? ', [$payload->username])->row();
            if (!$exist) self::setErr(['Not found Username OR Password'], 200);

            $Vertify = $this->db->query("SELECT * 
            FROM geerang_gts.personaldocument a 
            LEFT JOIN geerang_gts.personal_otp b ON b.log_lineid = a.line_ai_id 
            WHERE a.line_ai_id = ? 
            AND b.is_active = 1 AND b.status = 'active'
            ", [$payload->uid])->row();
            if ($Vertify) self::setErr(['GID as been verified'], 200);

            if (!password_verify($this->Login->encrypt_md5_salt($payload->password), $exist->password)) {
                self::setErr(['Not found user'], 200);
            }

            $data = encrypt($exist->pd_id) . '.' . encrypt($payload->uid);

            $exist = $this->db->get_where('geerang_gts.personaldocument', ['pd_id' => $exist->pd_id])->row();
            if (!$exist->email) self::setRes(['email' => false, 'dialog' => true], 200);


            $this->otp->sendOTP($exist->email, [
                'pd_id' => $exist->pd_id,
                'line_id' => $payload->uid,
            ]);

            self::setRes(['email' => true, 'url' => base_url('api/v1/chanel/Verify?_d=' .   $data)], 200);
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }
    public function sendEmailProcess_post()
    {
        $this->load->model('Users_login_model', 'Login');
        $this->load->model('api/Stmotp_model', 'otp');

        try {
            $payload = (object)$this->input->post();
            $valid = self::validPayload(
                $payload,
                ['username', 'username', 'required'],
                ['password', 'password', 'required'],
                ['uid', 'Line user id', 'required'],
                ['email', 'Email', 'required'],
            );
            if ($valid) self::setErr($valid, 200);
            $this->save($payload);

            $exist = $this->db->query('SELECT * FROM geerang_gts.personaldocument a 
            WHERE a.username = ? ', [$payload->username])->row();

            if (!$exist) self::setErr(['Not found Username OR Password'], 200);
            if (!password_verify($this->Login->encrypt_md5_salt($payload->password), $exist->password)) {
                self::setRes(['Not found user'], 200);
            }

            $this->otp->sendOTP($payload->email, [
                'pd_id' => $exist->pd_id,
                'line_id' => $payload->uid,
            ]);

            $data = encrypt($exist->pd_id) . '.' . encrypt($payload->uid);
            self::setRes(base_url('api/v1/chanel/Verify?_d=' .   $data), 200);
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }
    public function verifyProcess_get()
    {

        try {
            $data = (object) $this->input->get();
            $valid = self::validPayload(
                $data,
                ['_d', 'Data', 'required'],
                ['otp', 'OTP', 'required'],
                ['email', 'Email', 'optional'],
            );
            if ($valid) self::setErr($valid, 200);

            $payload = (object)[
                'pd_id' => decrypt(explode('.', $data->_d)[0]),
                'line_id' => decrypt(explode('.', $data->_d)[1]),
                'otp' => $data->otp
            ];
            $this->save($payload);

            if (!$payload->pd_id || !$payload->line_id) self::setErr(['Not Found Data']);
            $exist = $this->db->get_where('geerang_gts.personaldocument', ['pd_id' => $payload->pd_id])->row();
            if (!$exist) self::setErr(['Not Employee Data']);

            $otp = self::checkOTP($payload);

            if ($otp->action == 3) self::setRes($otp->msg, 404);
            if ($otp->action == 2) {
                $email = $this->db->get_where('geerang_gts.personaldocument', ['pd_id' => $exist->pd_id])->row();
                if (!$email->email) {
                    self::setRes(['email' => false, 'dialog' => true, 'msg' => $otp->msg], 200);
                }
            }

            self::setRes([
                'url' => base_url('api/v1/chanel/login?uid=' . $payload->line_id),
                'email' => true
            ], 200);
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }
    private function checkOTP($payload)
    {

        $otpid = self::ForGetIDotp($payload->pd_id, $payload->otp);

        $exist_vertify = $this->db->get_where('geerang_gts.personal_otp', ['id' =>  $otpid, 'pd_id' => $payload->pd_id])->row();

        $exist_otp = $this->db->query(
            "SELECT * FROM geerang_gts.personal_otp  
                WHERE pd_id = ? AND  NOW() <= expire AND `status` IS NULL
                ",
            [$payload->pd_id]
        )->row();
        if (!$exist_otp) {
            return (object)['action' => 2, 'msg' => 'OTP Timeout !!! '];
        }

        if ($exist_vertify->status == 'active') {
            return (object)['action' => 3, 'msg' => "Have Already Vertify"];
        } else if ($exist_vertify->status == 'timeout') {
            return (object)['action' => 2, 'msg' => 'OTP Timeout !!! '];
        } else {
            $check_vertify = pass_secure_verify($payload->otp, $exist_otp->otp);
            if ($check_vertify) {
                $this->db->update('geerang_gts.personal_otp', ['status' => 'active', 'is_active' => 1], ['id' =>  $otpid, 'pd_id' => $payload->pd_id]);
                $this->db->update('geerang_gts.personaldocument', ['line_ai_id' => $payload->line_id], ['pd_id' => $payload->pd_id]);
                return (object)['action' => 1, 'msg' => 'Vertify Success'];
            } else {
                return (object)['action' => 3, 'msg' => 'OTP is incorrect'];
            }
        }
    }
    private function ForGetIDotp($emp_id, $otp)
    {
        $result = $this->db->get_where('geerang_gts.personal_otp', ['pd_id' => $emp_id])->result();
        foreach ($result as $key => $val) {
            if (decryption($val->otp_str) === $otp) {
                return $val->id;
            }
        }
    }
    public function fetchOtp_get()
    {
        $this->load->model('api/Stmotp_model', 'otp');
        try {
            $data = (object) $this->input->get();
            $valid = self::validPayload(
                $data,
                ['_d', 'Data', 'required'],
                ['email', 'Email', 'optional'],
            );
            if ($valid) self::setErr($valid, 200);

            $payload = (object)[
                'pd_id' => decrypt(explode('.', $data->_d)[0]),
                'line_id' => decrypt(explode('.', $data->_d)[1]),
            ];
            $this->save($payload);

            if (!$payload->pd_id || !$payload->line_id) self::setErr(['Not Found Data']);
            $exist = $this->db->get_where('geerang_gts.personaldocument', ['pd_id' => $payload->pd_id])->row();
            if (!$exist) self::setErr(['Not Employee Data']);

            $this->otp->sendOTP($data->email, [
                'pd_id' => $payload->pd_id,
                'line_id' => $payload->line_id,
            ]);

            self::setRes(['Success'], 200);
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }
}
