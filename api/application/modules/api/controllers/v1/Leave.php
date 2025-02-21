<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require APPPATH . 'libraries/RestAPI.php';
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE');

class Leave extends RestAPI
{
    const COMID = [389, 354];
    public function __construct()
    {
        parent::__construct();
        $this->validateAuth();
    }

    public function index_get()
    {
        try {
            self::setRes(['msg' => 'Leave API'], 200);
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }


    public function checkRemain_post()
    {
        try {
            // check_lang();
            $this->lang->load('pages', 'thai');

            $this->load->model('hrm/Dashboard_model', 'Dashboard');

            $payload = (object)$this->input->post();
            $this->save($payload);
            // if (!empty( $payload)) self::setErr("Unavaliable wait a moment.", 500);
            $valid = self::validPayload(
                $payload,
                ['emp_id', 'Employee ID', 'required'],
                ['com_id', 'Company ID', 'required'],
                ['type_id', 'Leave Type ID', 'optional'],
                ['leave_name', 'leave name', 'optional'],
            );

            if ($valid) self::setErr($valid, 403);
            $com_exist = $this->db->query('SELECT * FROM geerang_gts.users a WHERE a.id = ?', [$payload->com_id])->row();
            if (!$com_exist) self::setErr('Not found company', 200);

            $data = $this->Dashboard->getQuotaLeaveAPI($payload->emp_id, $payload->com_id, $payload->type_id);

            if ($payload->leave_name) {
                $data = array_values(array_filter($data, function ($e) use ($payload) {
                    $check = false;
                    if ($e['leave_type_name'] == $payload->leave_name) $check = true;
                    return $check;
                }));
            }

            $emp_use_leave = (array)$this->Dashboard->getEmpLeaveForAPI($payload->emp_id, $payload->com_id);

            $result = [];
            foreach ($data as $key => $val) {
                $spent_leave = 0;

                $day = 0;
                $hour = 0;
                $minute = 0;

                $remaining_leave = $val["quota"];
                $txt_collect_leave = '';
                for ($i = 0; $i < count($emp_use_leave); $i++) {
                    if ($emp_use_leave[$i]["leave_type_name"] == $val["leave_type_name"]) {
                        $spent_leave_array = convertMinuteToTimeBaseOnShiftHour($emp_use_leave[$i]["spent_leave"], $emp_use_leave[$i]["avg_amount_time"]);
                        $remaining_leave_array = convertMinuteToTimeBaseOnShiftHour($emp_use_leave[$i]["remaining_leave"], $emp_use_leave[$i]["avg_amount_time"]);
                        $spent_leave = $spent_leave_array["day"] . ' ' . $spent_leave_array["hour"] . ' ' . $spent_leave_array["minute"];

                        $day = $spent_leave_array["day"];
                        $hour = $spent_leave_array["hour"];
                        $minute = $spent_leave_array["minute"];

                        $remaining_leave = (count($remaining_leave_array) > 0 ? $remaining_leave_array["day"] . ' ' . $remaining_leave_array["hour"] . ' ' . $remaining_leave_array["minute"] : "0");
                        if ($emp_use_leave[$i]['is_collect_leave'] == 1) $txt_collect_leave = $emp_use_leave[$i]['collect_time']['text'];
                    }
                }
                if ($val['is_collect_leave']) {

                    $used = [
                        'string' => '',
                        'day' => (int) $hour,
                        'hour' => (int)0,
                        'minute' => '',
                    ];
                    $result[$key] = (object)[
                        'leave_id'          => (int)$val['id'],
                        'leave_group_id'    => (int)$val['leave_set_id'],
                        'leave_remain'      => null,
                        'leave_name'        => $val["leave_type_name"],
                        'leave_used'        => $txt_collect_leave ? $txt_collect_leave : $val['collect_time']['text'],
                        'total_quota'       => $val["quota"] . ' ' . l('hrm.leave.hour'),
                    ];
                } else {
                    $used = [
                        'string' => $spent_leave ? trim($spent_leave)  : trim($spent_leave),
                        'day'   => (int)  $day,
                        'hour'  => (int) $hour,
                        'minute' =>  (int) $minute,
                    ];
                    $result[$key] = (object)[
                        'leave_id'          => (int)$val['id'],
                        'leave_group_id'    => (int)$val['leave_set_id'],
                        'leave_name'        => $val["leave_type_name"],
                        'leave_remain'      => trim($remaining_leave),
                        'leave_used'        => $used,
                        'total_quota'       => $val["quota"] . ' ' . l('hrm.leave.day'),

                    ];
                }
            }

            self::setRes($result, 200);
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }

    public function getTypeLeave_post()
    {
        try {
            $payload = (object)$this->input->post();
            $valid = self::validPayload(
                $payload,
                ['com_id', 'Company ID', 'required'],
                ['type_name', 'Leave Type Name', 'optional'],
                ['gender', 'Gender ', 'optional|in_array:male,female,all']
            );
            $this->save($payload);

            if ($valid) self::setErr($valid, 403);

            $com_exist = $this->db->query('SELECT * FROM geerang_gts.users a WHERE a.id = ?', [$payload->com_id])->row();
            if (!$com_exist) self::setErr('Not found company', 404);

            $filter = '';
            if ($payload->type_name) {
                $exact = $this->db->get_where('geerang_hrm.leavetypes_new', ['leave_type_name' => $payload->type_name, 'com_id' => $payload->com_id])->row();
                if ($exact) $filter .= " AND a.leave_type_name = '{$payload->type_name}' ";
                if (!$exact) $filter .= " AND a.leave_type_name LIKE '%{$payload->type_name}%' ";
            }

            if ($payload->gender) {
                $filter .= " AND a.leave_gender = '{$payload->gender}' ";
            }

            $type = $this->db->query("SELECT
                                            a.leave_type_name, 
                                            a.leave_gender, 
                                            a.id 
                                        FROM
                                            geerang_hrm.leavetypes_new a
                                        WHERE
                                            a.com_id = ?
                                            $filter
                                            ", [$payload->com_id])->result();

            $type = array_map(function ($e) {
                return (object)[
                    'type_id'   => (int)$e->id,
                    'type_name' => $e->leave_type_name,
                    'gender'    => $e->leave_gender
                ];
            }, $type);

            self::setRes($type, 200);
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }
    public function checkDate_post()
    {
        $datenow = date('Y-m-d');
        try {
            $payload = (object)$this->input->post();
            $valid = self::validPayload(
                $payload,
                ['com_id', 'Company ID', 'required'],
                ['emp_id', 'Employee ID', 'required'],
                ['date', 'Date', 'required|custom:xxxx-xx-xx'],
            );
            $this->save($payload);

            if ($valid) self::setErr($valid, 403);
            $com_exist = $this->db->query('SELECT * FROM geerang_gts.users a WHERE a.id = ?', [$payload->com_id])->row();
            if (!$com_exist) self::setErr('Not found company', 200);


            $Inputdate = date('Y-m-d', strtotime($payload->date));

            if ($Inputdate < $datenow) self::setErr('The date must be greater than the current one.', 200);

            $result = $this->db->query(
                "SELECT t1.groupname ,
                    t1.keep_pd_id,
                    t3.id AS atten_id,
                    t1.id,
                    t1.id as shift_id,
                    t2.group_shift_id,
                    t2.shift_id_list
                    FROM
                        geerang_hrm.shift_group t1
                        LEFT JOIN geerang_hrm.calendar_shift t2 ON t2.group_shift_id = t1.id AND t2.company_id = t1.company_id
                        LEFT JOIN geerang_hrm.attendance_setting t3 ON FIND_IN_SET( t3.id, t2.shift_id_list ) AND t3.com_id = t2.company_id 
                    WHERE
                        t1.company_id = ? 
                        AND FIND_IN_SET( ?, t1.keep_pd_id )  AND t2.date_shift = ?
                    GROUP BY
                    t3.id
                ",
                [$payload->com_id, $payload->emp_id, $Inputdate]
            )->result();

            $att_id = [];
            foreach ($result as $value) {
                if ($value->atten_id) {
                    array_push($att_id, $value->atten_id);
                }
            }
            $res = count($att_id) > 0 ? true : false;

            $response =   [
                'date'      => [
                    'datetime' => date_format(date_create($Inputdate), "Y-m-d\TH:i:s\z"),
                    'date' => date_format(date_create($Inputdate), "d/m/Y"),
                    'time' => date_format(date_create($Inputdate), "H:i:s"),
                ],
                'can_leave' =>  $res,
            ];

            self::setRes($response, 200);
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }
    public function LeaveRequest_post()
    {
        try {
            $this->lang->load('pages', 'thai');


            $datenow = date('Y-m-d');

            $this->load->model('hrm/Leave_model', 'Leave');
            $this->load->model('AppNoti_model', 'AppNoti');

            $this->load->model('hrm/Attendance_model', 'Attendance');

            $payload = (object)$this->input->post();
            $valid = self::validPayload(
                $payload,
                ['com_id', 'Company ID', 'required'],
                ['emp_id', 'Employee ID', 'required'],
                ['type_id', 'Leave Type ID', 'required'],
                ['period', 'Period Type', 'required|in_array:day,hour'], // day,hour
                ['date', 'Date', 'required|custom:xxxx-xx-xx'],
                ['time_start', 'Time Start', 'optional|custom:xx:xx'],
                ['time_end', 'Time End', 'optional|custom:xx:xx'],
            );
            $this->save($payload);

            $Inputdate = date('Y-m-d', strtotime($payload->date));


            if ($valid) self::setErr($valid, 403);
            $com_exist = $this->db->query('SELECT * FROM geerang_gts.users a WHERE a.id = ?', [$payload->com_id])->row();
            if (!$com_exist) self::setErr('Not found company', 200);

            $personal = $this->db->query(
                "SELECT t1.employee_code,t4.position_id,t4.position_name,t2.*
                  FROM geerang_hrm.personalsecret t1
                  LEFT JOIN geerang_gts.personaldocument t2 ON t2.pd_id = t1.pd_id
                  LEFT JOIN geerang_gts.position_keep t3 ON t3.pd_id =t1.pd_id AND t3.user_id = t1.company_id
                  LEFT JOIN geerang_gts.user_position t4 ON t4.position_id = t3.position_id AND t4.user_id = t3.user_id
                  WHERE  t1.company_id = ? AND t1.status = 'active'  AND t1.pd_id = ?
                ",
                [$payload->com_id, $payload->emp_id]
            )->row();

            if (!$personal->pd_id) self::setErr('Not found Employee', 200);

            $obj = [
                (object) [
                    'pd_id' => $payload->emp_id,
                    'position_id' => $personal->position_id,
                ],
            ];
            $option = (object)[
                'pd_id'                 => $payload->emp_id,
                'title'                 => $personal->title,
                'company_id'            => $payload->com_id,
                'position_name'         => $personal->position_name,
                'position_id'           => $personal->position_id,
                'upline_all'            => getUpline($payload->com_id, $obj),
                'upline_pd_id'          => checkpdUp($payload->com_id, $personal->position_id),
                'type_id'               => $payload->type_id, //type_id
            ];


            $now = date('Y');
            $leave_type = (object)$this->Leave->leavetypeList($payload->emp_id, false, $option)[0];



            if ($Inputdate < $datenow) self::setErr('The date must be greater than the current one.', 200);
            if (strtolower($payload->period) == 'hour' && !$payload->time_start) self::setErr('Time Start Is Not Empty value', 200);
            if (strtolower($payload->period) == 'hour' && !$payload->time_end) self::setErr('Time End Is Not Empty value', 200);


            $result_amount = $this->db->query(
                "SELECT 
                a.*
                FROM geerang_hrm.employeeleaves a 
                WHERE 
                a.pd_id = ? 
                AND a.com_id = ? 
                AND a.leave_type = ? 
                AND YEAR(a.date_start) = ?
                AND (a.`status` = 'Pending' OR a.`status` = 'Approved')
              
                AND a.status_active = 'active'
                ",
                [$payload->emp_id, $payload->com_id, $payload->type_id,  $now]
            )->result();
            $amount_leave = [];
            foreach ($result_amount as $key => $val) {
                if (!in_array($val->details, ['generate'])) {
                    $amount_leave[] = $val->amount;
                }
            }
            $totalLeave = array_sum($amount_leave);

            if ($totalLeave >= $leave_type->amount_leave) self::setErr(l('leav_notice_14'), 200);


            $post = [
                'add_emp_id'        => $payload->emp_id,
                'add_com_id'        => $payload->com_id,
                'leave_date_start'  => $Inputdate,
                'leave_date_end'    => $Inputdate,
                'leave_type_new'    => strtolower($payload->period) == 'day' ? "allDay" : 'setTime',
                'leave_time_start'  => $payload->time_start,
                'leave_time_end'    => $payload->time_end,
                'leave_type'        => $payload->type_id, //type_id
                'person_leave_id'   => $leave_type->id, //id
            ];

            $result = $this->Leave->getLeaveinHoliday($post, $option);

            if ($result['status']) {
                $this->Attendance->saveApproveTrack($result['relate_id'], 'leave', $result['leave_type'], $option);
                
            }

            $empleave = $this->db->query(
                "SELECT t2.leave_type_name,t1.*
                FROM geerang_hrm.employeeleaves t1
                LEFT JOIN geerang_hrm.leavetypes_new t2 ON t2.id = t1.leave_type AND t2.com_id = t1.com_id
                WHERE t1.id =? AND t1.com_id = ?
                ",
                [$result['relate_id'][0], $payload->com_id]
            )->row();


            $detail = [
                'massage' => array_values($result)[0],
            ];
            if ($result['status']) {
                $detail = [
                    'request_id'        => (int)  $empleave->id,
                    'firstname'         => $personal->first_name,
                    'lastname'          => $personal->last_name,
                    'fullname'          => $personal->first_name . ' ' . $personal->last_name,
                    'leave_type'        => $empleave->leave_type_name,
                    'date'              => [
                        'datetime' => date_format(date_create($Inputdate), "Y-m-d\TH:i:s\z"),
                        'date' => date_format(date_create($Inputdate), "d/m/Y"),
                        'time' => date_format(date_create($Inputdate), "H:i:s"),
                    ],
                    'status_request' => [
                        'text'          => self::convertStatusLeave(strtolower($empleave->status)),
                        'status'        => strtolower($empleave->status)
                    ],
                ];
            }

            $response = [
                'request' => (bool) $result['status'] ? true : false,
                'detail'  =>  $detail,
            ];

            self::setRes($response, 200);
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }
    private function convertStatusLeave($status)
    {
        $msg = '';
        if ($status == strtolower('Pending')) {
            $msg = 'รออนุมัติ';
        } else if ($status == strtolower('Approved')) {
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

    public function LeaveRequestAll_post()
    {
        try {
            $payload = (object)$this->input->post();
            if (!in_array($payload->com_id, self::COMID)) self::setErr('Forbidden Error', 200);

            $valid = self::validPayload(
                $payload,
                ['com_id', 'Company ID', 'required'],
                ['emp_id', 'Employee ID', 'optional'],
                ['type_id', 'Leave Type ID', 'optional'],
                ['type_name', 'Leave Type name ', 'optional'],
                ['date', 'Date Request', 'optional|custom:xxxx-xx-xx'],
            );
            $this->save($payload);

            if ($valid) self::setErr($valid, 403);
            $com_exist = $this->db->query('SELECT * FROM geerang_gts.users a WHERE a.id = ?', [$payload->com_id])->row();
            if (!$com_exist) self::setErr('Not found company', 200);


            $filter = "";
            if ($payload->emp_id) {
                $filter .= " AND a.pd_id = {$payload->emp_id}";
            }
            if ($payload->type_id) {
                $filter .= " AND a.leave_type = {$payload->type_id}";
            }
            if ($payload->date) {
                $date = date('Y-m-d', strtotime($payload->date));
                $filter .= " AND DATE(a.date_start) = '{$date}' ";
            }
            if ($payload->type_name) {
                $filter .= " AND  b.leave_type_name LIKE '%{$payload->type_name}%' ";
            }

            $result  = $this->db->query(
                "SELECT 
                b.leave_type_name,
                c.first_name,
                c.last_name,
                ROUND(sum(a.amount) * AVG(a.amount_time) * 60) AS spent_leave,
                AVG(a.amount_time) AS avg_amount_time,
                a.*
                FROM geerang_hrm.employeeleaves a
                LEFT JOIN geerang_hrm.leavetypes_new b ON b.id = a.leave_type AND b.com_id = a.com_id
                LEFT JOIN geerang_gts.personaldocument c ON c.pd_id = a.pd_id
                WHERE a.com_id = ?  AND (a.details <> 'generate' OR a.details IS NULL) 
                $filter

                GROUP BY a.id
                ",
                [$payload->com_id]
            )->result();

            $res = [];
            foreach ($result as $key => $e) {
                $res[$e->pd_id] = (object)[
                    'emp_id'        => (int)$e->pd_id,
                    'firstname'    => $e->first_name,
                    'lastname'     => $e->last_name,
                    'fullname'      => $e->first_name . ' ' . $e->last_name,
                    'request'       => self::setResult($result),
                ];
            }

            self::setRes(array_values($res), 200);
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }
    private function setResult($value)
    {
        $res = [];

        foreach ($value as $key => $e) {


            $time = (object)convertMinuteToTimeBaseOnShiftHour($e->spent_leave, $e->avg_amount_time);
            $day =  $time->day ? trim($time->day) : 0;
            $hour =  $time->hour ? trim($time->hour) : 0;
            $minute =  $time->minute ? trim($time->minute) : 0;
            $res[] = (object)[
                's' => $e->spent_leave,
                'avg' => $e->avg_amount_time,
                'request_id'        => (int) $e->id,
                'leave_type_id'     => (int) $e->leave_type,
                'leave_type_name'   =>  $e->leave_type_name,
                'date_leave'   => [
                    'date_start'    => [
                        'datetime' => date_format(date_create($e->date_start), "Y-m-d\TH:i:s\z"),
                        'date' => date_format(date_create($e->date_start), "d/m/Y"),
                        'time' => date_format(date_create($e->date_start), "H:i:s"),
                    ],
                    'date_end'      => [
                        'datetime' => date_format(date_create($e->date_end), "Y-m-d\TH:i:s\z"),
                        'date' => date_format(date_create($e->date_end), "d/m/Y"),
                        'time' => date_format(date_create($e->date_end), "H:i:s"),
                    ],
                    'string' => "$day วัน $hour ชั่วโมง $minute นาที",
                    'day' => (int) $day,
                    'hour' => (int)  $hour,
                    'minute' => (int)  $minute,
                ],
                'status_request' => [
                    'text'          => self::convertStatusLeave(strtolower($e->status)),
                    'status'        => strtolower($e->status)
                ],
            ];
        }
        return $res;
    }
    public function deleteLeaveRequest_post()
    {
        try {
            $payload = (object)$this->input->post();
            if (!in_array($payload->com_id, self::COMID)) self::setErr('Forbidden Error', 200);
            $valid = self::validPayload(
                $payload,
                ['com_id', 'Company ID', 'required'],
                ['request_id', 'Leave request ID', 'required'],
            );
            $this->save($payload);


            if ($valid) self::setErr($valid, 403);
            $com_exist = $this->db->query('SELECT * FROM geerang_gts.users a WHERE a.id = ?', [$payload->com_id])->row();
            if (!$com_exist) self::setErr('Not found company', 200);

            $leave_exist = $this->db->query('SELECT * FROM geerang_hrm.employeeleaves a WHERE a.id = ?', [$payload->request_id])->row();
            if (!$leave_exist) self::setErr('Not found Request Leave ID', 200);

            $this->db->update("geerang_hrm.employeeleaves", ['status' => 'Cancelled'], ['id' => $payload->request_id, 'com_id' => $payload->com_id]);

            $resMsg = "Cancel Failed";
            if ($this->db->affected_rows() > 0) {
                $resMsg = "Cancel Success";
            }

            self::setRes($resMsg, 200);
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }
}
