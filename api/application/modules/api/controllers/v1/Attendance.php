<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require APPPATH . 'libraries/RestAPI.php';
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE');

class Attendance extends RestAPI
{
    public $data;
    public function __construct()
    {
        $data = [];
        parent::__construct();
        $this->data = $data;
    }

    public function index_get()
    {
        try {
            self::setRes(['msg' => 'Attendance API'], 200);
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }

    public function checkShift_get()
    {
        $this->validateAuth();

        $this->load->model('hrm/Calendar_model', 'calendar');

        try {
            $payload = (object)$this->input->get();
            $valid = self::validPayload(
                $payload,
                ['com_id', 'Company ID', 'required'],
                ['emp_id', 'Employee ID', 'required'],
                ['date', 'Date', 'required'],
            );
            if ($valid) self::setErr($valid, 403);
            $this->save($payload);
            // if ($payload->admin != 'tar') self::setErr('Wait a Minute', 500);
            $com_exist = $this->db->query('SELECT * FROM geerang_gts.users a WHERE a.id = ?', [$payload->com_id])->row();
            if (!$com_exist) self::setErr('Not found company', 200);


            $pd_exist = $this->db->query('SELECT * FROM geerang_gts.personaldocument a WHERE a.pd_id = ?', [$payload->emp_id])->row();
            if (!$pd_exist) self::setErr('Not found Employee', 200);

            $req = (object)[
                'com_id' => $payload->com_id,
                'pd_id'  => $payload->emp_id,
                'date'  => $payload->date,
            ];


            $shift = $this->calendar->getShiftDateAPI($req);

            if (isset($shift['status'])) self::setErr($shift['message'], 200);

            $personal = $this->calendar->employeeListAPI($req);
            if (!$personal) self::setErr("ไม่พบพนักงานแผนกเดียวกันที่สามารถสลับกะได้", 200);
            $myshift = array_map(function ($e) {
                return (object)[
                    'shift_id'       => (int)$e->id,
                    'shift_name'     => $e->attendance_name,
                ];
            }, $shift);

            $swapshift = [];
            foreach ($personal as $key => $value) {
                $swap = self::setShift($shift, $req, $value->pd_id);
                if (count($swap) > 0) {
                    $swapshift[] = [
                        'emp_id'        => (int)$value->pd_id,
                        'first_name'    => $value->first_name,
                        'last_name'     => $value->last_name,
                        'fullname'      => $value->first_name . ' ' . $value->last_name,
                        'shift_swap'    => array_filter($swap, function ($ee) use ($myshift) {
                            $temp = array_map(fn ($e) => $e->shift_id, $myshift);
                            if (in_array($ee->shift_id,  $temp)) return false;
                            return true;
                        }),
                    ];
                }
            }
            $swapshift = array_values(array_filter($swapshift, function ($e) {
                if (count($e['shift_swap']) > 0) return  true;
                return false;
            }));

            $res = [
                'myshift' =>   $myshift,
                'swapshift' => $swapshift,
                'message' => count($swapshift) == 0 ? 'ไม่มีพนักงานที่สามารถสลับกะได้' : "",
                'isSwap' => count($swapshift) > 0 ? true : false,
            ];

            self::setRes($res, 200);
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }
    private function setShift($shift, $req, $pd_id)
    {
        $error = 0;
        $temp = [];
        $_err = [];
        foreach ($shift as $key => $value) {
            $req_temp  =  (object)[
                'date'          => $req->date,
                'com_id'        => $req->com_id,
                'pd_id_swap'    => $pd_id,
                'shift_id'      => $value->id
            ];

            $shiftdata = $this->calendar->getShiftSwapAPI($req_temp);


            if (isset($shiftdata['status']) && $shiftdata['status'] == false) {
                $error++;
                $_err = [
                    'msg' => $shiftdata['msg']

                ];
            } else {
                $temp =  array_map(function ($e) {
                    $e = (object)$e;
                    return (object)[
                        'shift_id' => (int)$e->shift_id,
                        'shift_name' => trim($e->name),
                    ];
                },  $shiftdata['shiftgroup']);
            }
        }

        if ($error > 0) {
            // return $_err;
            return [];
        } else {
            return $temp;
        }
    }

    public  function shiftChange_post()
    {
        $this->load->model('hrm/Attendance_model', 'Attendance');
        $this->load->model('hrm/Calendar_model', 'calendar');
        $this->validateAuth();

        try {
            $payload = (object)$this->input->post();
            $valid = self::validPayload(
                $payload,
                ['com_id', 'Company ID', 'required'],
                ['emp_id', 'Employee ID', 'required'],
                ['emp_id_to', 'Employee ID', 'required'],
                ['shift_id', 'Shift ID', 'required'],
                ['shift_id_to', 'Shift ID', 'required'],
                ['date', 'Date', 'required'],
                ['reason', 'Reason shiftChange', 'required']
            );

            if ($valid) self::setErr($valid, 403);
            $this->save($payload);

            $com_exist = $this->db->query('SELECT * FROM geerang_gts.users a WHERE a.id = ?', [$payload->com_id])->row();
            if (!$com_exist) self::setErr('Not found company', 200);

            $pd_exist = $this->db->query('SELECT * FROM geerang_gts.personaldocument a WHERE a.pd_id = ?', [$payload->emp_id])->row();
            if (!$pd_exist) self::setErr('Not found Employee', 200);

            $pd_to_exist = $this->db->query('SELECT * FROM geerang_gts.personaldocument a WHERE a.pd_id = ?', [$payload->emp_id_to])->row();
            if (!$pd_to_exist) self::setErr('Not found Employee', 200);


            $shift_exist = $this->db->query("SELECT * FROM geerang_hrm.shift_group a WHERE a.id = ? ", [$payload->shift_id])->row();
            if (!$shift_exist) self::setErr('Not found Shift Group', 200);


            $shift_to_exist = $this->db->query("SELECT * FROM geerang_hrm.shift_group a WHERE a.id = ? ", [$payload->shift_id_to])->row();
            if (!$shift_to_exist) self::setErr('Not found Shift Group', 200);

            $req = (object)[
                'com_id'        => $payload->com_id,
                'pd_id'         => $payload->emp_id,
                'pd_id_swap'    => $payload->emp_id_to,
                'shift_id'      => $payload->shift_id,
                'shift_id_to'   => $payload->shift_id_to,
                'date'          => $payload->date,
                'reason'        => $payload->reason,
            ];

            $result = $this->calendar->saveswapAPi($req);

            if ($result['status']) {
                $insert_id[] = $result['insert_id'];
                $this->Attendance->saveApproveTrack($insert_id, 'swap');
                self::setRes($result['msg'], 200);
            } else {
                self::setRes($result['msg'], 200);
            }
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }

    public function getShiftrequest_get()
    {
        $this->validateAuth();

        try {
            $payload = (object)$this->input->get();
            $valid = self::validPayload(
                $payload,
                ['com_id', 'Company ID', 'required'],
                ['emp_id', 'Employee ID', 'required'],
                ['date', 'Date', 'optional'],
                ['shift_id', 'Shift ID', 'optional'],
                ['shift_name', 'Shift Name', 'optional'],
            );
            if ($valid) self::setErr($valid, 403);
            $this->save($payload);

            $com_exist = $this->db->query('SELECT * FROM geerang_gts.users a WHERE a.id = ?', [$payload->com_id])->row();
            if (!$com_exist) self::setErr('Not found company', 200);


            $pd_exist = $this->db->query('SELECT * FROM geerang_gts.personaldocument a WHERE a.pd_id = ?', [$payload->emp_id])->row();
            if (!$pd_exist) self::setErr('Not found Employee', 200);

            $filter = '';
            if ($payload->shift_id) {
                $filter .= " AND a.shift_id = {$payload->shift_id}";
            }
            if ($payload->shift_name) {
                $filter .= " AND ( b.attendance_name LIKE '%{$payload->shift_name}%' OR c.attendance_name LIKE '%{$payload->shift_name}%' )";
            }


            $result = $this->db->query(
                "SELECT 
                b.attendance_name as name1,
                c.attendance_name as name2,
                a.*
                FROM 
                geerang_hrm.shift_swap a 
                LEFT JOIN geerang_hrm.attendance_setting b ON b.id = a.shift_id AND b.com_id = a.company_id
                LEFT JOIN geerang_hrm.attendance_setting c ON c.id = a.shift_id AND c.com_id = a.company_id
                WHERE 
                a.company_id = ?
                $filter
                ",
                [$payload->com_id]
            )->result();

            $res = array_map(function ($e) {

                $primary = $this->db->get_where('geerang_gts.personaldocument', ['pd_id' => $e->pd_id_primary])->row();
                $second = $this->db->get_where('geerang_gts.personaldocument', ['pd_id' => $e->pd_id_secondary])->row();
                return (object)[
                    'id' => (int) $e->id,
                    'myshift' => [
                        'id' => (int)$e->shift_id,
                        'name' => $e->name1,
                        'date' => date('Y-m-d', strtotime($e->date_swap_from)),
                        'employee' => [
                            'emp_id' => (int)$primary->pd_id,
                            'first_name' => $primary->first_name,
                            'last_name' => $primary->last_name,
                            'fullname' => $primary->first_name . ' ' . $primary->last_name,
                        ],
                    ],
                    'shift_to' => [
                        'id' => (int)$e->shift_id_to,
                        'name' => $e->name2,
                        'date' => date('Y-m-d', strtotime($e->date_swap_to)),
                        'employee' => [
                            'emp_id' => (int)$second->pd_id,
                            'first_name' => $second->first_name,
                            'last_name' => $second->last_name,
                            'fullname' => $second->first_name . ' ' . $second->last_name,
                        ],
                    ],
                    'date_request'   => [
                        'datetime'  => date_format(date_create($e->create_date), "Y-m-d\TH:i:s\z"),
                        'date'      => date_format(date_create($e->create_date), "d/m/Y"),
                        'time'      => date_format(date_create($e->create_date), "H:i:s"),
                    ],
                    'status_request' => [
                        'text'          => self::convertStatusLeave(strtolower($e->status_approve)),
                        'status'        => strtolower($e->status_approve)
                    ],

                ];
            }, $result);

            self::setRes($res, 200);
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }
    private function convertStatusLeave($status)
    {
        $msg = '';
        if ($status == strtolower('Pending')) {
            $msg = 'รออนุมัติ';
        } else if ($status == strtolower('Approved') || $status == strtolower('Approve')) {
            $msg = 'อนุมัติ';
        } else if ($status == strtolower('Rejected')) {
            $msg = 'ปฏิเสธ';
        } else if ($status == strtolower('Cancellation Requested')) {
            $msg = 'ขอการยกเลิก';
        } else if ($status == strtolower('Cancelled')) {
            $msg = 'ยกเลิก';
        } else if ($status == strtolower('Processing')) {
            $msg = 'กำลังดำเนินการ';
        }
        return $msg;
    }

    public function requestCheckInout_post()
    {
        $this->validateAuth();
        try {
            $payload = (object)$this->input->post();

            $valid = self::validPayload(
                $payload,
                ['com_id', 'Company ID', 'required'],
                ['emp_id', 'Employee ID', 'required'],
            );

            $date = date('Y-m-d');
            $status = true;

            if ($valid) self::setErr($valid, 403);
            $this->save($payload);

            $com_exist = $this->db->query('SELECT * FROM geerang_gts.users a WHERE a.id = ?', [$payload->com_id])->row();
            if (!$com_exist) self::setErr('Not found company', 200);


            $pd_exist = $this->db->query('SELECT * FROM geerang_gts.personaldocument a WHERE a.pd_id = ?', [$payload->emp_id])->row();
            if (!$pd_exist) self::setErr('Not found Employee', 200);


            $exist_att = $this->db->get_where('geerang_hrm.attendances', ['emp_id' => $payload->emp_id, 'com_id' => $payload->com_id, 'date_no' => $date])->row();

            if (!$exist_att->shift_id) {
                self::setErr('No work shifts.', 200);
                $status = false;
            }

            $exist_time = $this->db->query(
                "SELECT * FROM geerang_hrm.attendances 
                WHERE emp_id = ? AND com_id = ? AND DATE(date_no) = ? 
                AND (time_in IS NULL OR time_out IS NULL )
                ",
                [$payload->emp_id, $payload->com_id, $date]
            );
            if (!$exist_time) {
                self::setErr('Have Already Time in and Time out', 200);
                $status = false;
            }

            $exist_leave = self::checkLeavetoday($payload->emp_id, $payload->com_id, $date);
            if ($exist_leave) {
                self::setErr('There is a request for leave.', 200);
                $status = false;
            }

            $encrypt = encrypt($payload->emp_id) . '.';
            $encrypt .= encrypt($payload->com_id);

            $url = '';
            if ($status) {
                $url = base_url('/api/v1/attendance/preview/' . $encrypt);
            }

            $res = (object)[
                'redirect' => $url,
                'Ischeckin' => $status,

            ];
            self::setRes($res, 200);
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }
    private function checkLeavetoday($pd_id, $com_id, $datenow)
    {
        $attendance_chk = $this->db->get_where('geerang_hrm.attendances', ['emp_id' => $pd_id, 'com_id' => $com_id, 'date_no' => $datenow])->num_rows();
        $status = 0;
        if ($attendance_chk > 0) {
            $leave_chk = $this->db->query("SELECT
                                                *
                                            FROM
                                                geerang_hrm.employeeleaves
                                            WHERE
                                                pd_id = ?
                                                AND com_id = ?
                                                AND (
                                                    (model_leave = 1 AND date_start <= CURDATE() AND date_end >= CURDATE()) OR 
                                                    (model_leave = 2 AND date_start <= CURRENT_TIMESTAMP() AND date_end >= CURRENT_TIMESTAMP())
                                                )
                                            ORDER BY
                                                id DESC", [$pd_id, $com_id])->row();
            if ($leave_chk) {
                $status = in_array($leave_chk->status_cancle_leave, ['Pending']) || in_array($leave_chk->status, ['Approved']) ? 1 : 0;
            }
        }
        return $status;
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
    public function preview_get($encryptdata)
    {
        $this->load->model('hrm/Attendance_model', 'Attendance');
        $this->load->model('hrm/Dashboard_model', 'Dashboard');
        $this->lang->load('pages', 'thai');

        try {

            if (count(explode('.', $encryptdata)) != 2) self::setErr('Not found data', 404);

            $urldata = explode('.', $encryptdata);
            $com_id = (int) checkEncryptData($urldata[1]);
            $pd_id = (int)checkEncryptData($urldata[0]);
            $this->session->set_userdata([
                'pd_id'         => $pd_id,
                'company_id'    => $com_id,
            ]);

            $com_exist = $this->db->query('SELECT * FROM geerang_gts.users a WHERE a.id = ?', [$com_id])->row();
            if (!$com_exist) self::setErr('Not found company', 200);

            $pd_exist = $this->db->query('SELECT * FROM geerang_gts.personaldocument a WHERE a.pd_id = ?', [$pd_id])->row();
            if (!$pd_exist) self::setErr('Not found Employee', 200);


            $personal = $this->db->get_where('geerang_gts.personaldocument', ['pd_id' => $pd_id])->row();
            $this->data['fullname'] = $personal->first_name . ' ' . $personal->last_name;

            $post = (object)[
                'pd_id' => $pd_id,
                'com_id' => $com_id,
            ];

            $this->data['checkUnlimitOtWithoutCheckout'] = (array) $this->Attendance->checkUnlimitOtWithoutCheckout($post);
            $this->data["attendance_dashboard"] = $this->Attendance->getAttendanceDashboard($post);
            $this->data["attendance_latest"] = $this->Dashboard->attendanceList_latest($post);


            $this->another_js =  "<script src='" . base_url('assets/js/webcamjs/webcam.min.js') . "'></script>";
            $this->another_js .= '<script  defer src="' . base_url('assets/plugins/face-api/face-api.js') . '"></script>';

            $this->another_js .=  "<script src='" . base_url('assets/js_modules/api/checkin.js?ft=' . time()) . "'></script>";
            $this->another_js .= '<script src="' . base_url('assets/js_modules/users/feedCheckin.js?ft=' . time()) . '"></script>';


            $this->another_css = "<link rel='stylesheet' href='" . base_url('/assets/css/_var_main_light.css') . "'>";
            $this->another_css .= "<link rel='stylesheet' href='" . base_url('/assets/css/main.css?ft=' . time()) . "'>";

            $this->render_view('api/previewCheckin', $this->data);
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }
}
