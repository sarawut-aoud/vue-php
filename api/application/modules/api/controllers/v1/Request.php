<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require APPPATH . 'libraries/RestAPI.php';
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE');

class Request extends RestAPI
{
    public function __construct()
    {
        parent::__construct();
        $this->validateAuth();
        $this->type = '';
    }

    public function index_get()
    {
        try {
            self::setRes(['msg' => 'Request API'], 200);
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }

    const SETTYPE = [
        'leave',
        'attendance',
        'ot',
        'swapshift',
        'duringday',
        'leaveduring',
        'certwark',
        'certsalary',
        'all'
    ];
    public function checkStatus_post()
    {

        $settype = implode(',', self::SETTYPE);
        try {
            $payload = (object)$this->input->post();
            $valid = self::validPayload(
                $payload,
                ['com_id', 'Company ID', 'required'],
                ['emp_id', 'Employee ID', 'required'],
                ['date_req', 'Request Date', 'optional|custom:xxxx-xx-xx'],
                ['type', 'Request Type', "optional|in_array:{$settype}"],
            );
            if ($valid) self::setErr($valid, 403);

            $com_exist = $this->db->query('SELECT * FROM geerang_gts.users a WHERE a.id = ?', [$payload->com_id])->row();
            if (!$com_exist) self::setErr('Not found company', 404);

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
            if (!$personal) self::setErr('Not found Employee', 404);


            $date_req = date('Y-m-d', strtotime($payload->date_req));
            $type = explode(":", $payload->type)[0];

            $this->type =  $type;

            $response = [
                'requet_date'       => [
                    'datetime' => date_format(date_create($date_req), "Y-m-d\TH:i:s\z"),
                    'date' => date_format(date_create($date_req), "d/m/Y"),
                    'time' => date_format(date_create($date_req), "H:i:s"),
                ],
                'fullname'          => $personal->first_name . ' ' . $personal->last_name,
                'firstname'         => $personal->first_name,
                'lastname'          => $personal->last_name,
                'detail'            => self::getStatus($type, $payload),
            ];

            self::setRes($response, 200);
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }

    private function getStatus($type, $post)
    {
        // $type_id = (int)explode(":", $post->type)[1];
        $type_id = (int)2;
        $date_req = date('Y-m-d', strtotime($post->date_req));

        $result = [];
        switch ($type) {
            case "leave":
                $row = self::getLeave($post, $date_req, $type_id);
                array_push($result, $row);
                break;
            case "attendance":
                $row = self::getAtt($post, $date_req, $type_id);
                array_push($result, $row);
                break;
            case "ot":
                $row = self::getOt($post, $date_req, $type_id);
                array_push($result, $row);
                break;
            case "swapshift":
                $topic = '';
                $status = '';
                break;
            case "duringday":
                $topic = '';
                $status = '';
                break;
            case "leaveduring":
                $topic = '';
                $status = '';
                break;
            default:

                foreach (self::getLeave($post, $date_req, $type_id) as $key => $val) {
                    array_push($result, $val);
                }

                foreach (self::getAtt($post, $date_req, $type_id) as $key => $value) {
                    array_push($result, $value);
                }
        }


        return $result;
    }
    protected function formatdata($request, $detail, $status, $status_text, $type)
    {
        return [
            'request'       => (bool) $request ? $request : false,
            'type'          => self::TextConvert(strtolower($type)),
            'detail'        => $detail ? $detail : null,
            'status'        => $status ? $status : "",
            'status_text'   => $status_text ? $status_text : ""
        ];
    }
    private function getLeave($post, $date_req, $type_id)
    {
        $filter = "";
        if ($type_id && self::checktype($post)) {
            $filter .= " AND b.leave_type = $type_id";
        }
        $row = $this->db->query(
            "SELECT
            a.leave_type_name,
            a.id ,
            b.`status`
            FROM
                geerang_hrm.leavetypes_new a 
            LEFT JOIN geerang_hrm.employeeleaves b ON b.leave_type = a.id AND b.com_id = a.com_id
            WHERE
                a.com_id = ?
                AND b.pd_id = ?
                AND DATE(b.created_at) = ?
                $filter
                 ",
            [$post->com_id,  $post->emp_id, $date_req]
        )->result();

        $data = [];
        $data = array_map(function ($e) use ($post) {
            $request = (bool) $e->id ? true : false;
            $leave_type_name = $this->db->query("SELECT
                a.leave_type_name, a.leave_gender, a.id  FROM geerang_hrm.leavetypes_new a 
                WHERE a.com_id = ? AND a.id= ?", [$post->com_id, $e->leave_type])->row('leave_type_name');
            return self::formatdata($request, $leave_type_name, $e->status, self::convertStatusLeave(strtolower($e->status)), 'leave');
        }, $row);

        return $data;
    }
    private function getAtt($post, $date_req, $type_id)
    {
        $filter = '';
        if ($type_id  && self::checktype($post)) {
            $filter .= " AND attendance_type = $type_id";
        }
        $row = $this->db->query(
            "SELECT * FROM geerang_hrm.attendance_log WHERE DATE(created_at)= ? AND pd_id = ? AND com_id = ?  $filter",
            [$date_req, $post->emp_id, $post->com_id]
        )->result();


        $data = [];
        $data = array_map(function ($e) {
            $typename = $e->attendance_type == 2 ? 'ยื่นเข้างาน' : 'ยื่นออกงาน';
            return self::formatdata(true, $typename, $e->status_work, self::convertStatusAtt(strtolower($e->status_work)), 'attendance');
        }, $row);

        return $data;
    }
    private function getOt($post, $date_req, $type_id)
    {
        $filter = "";
        if ($type_id && self::checktype($post)) {
            $filter .= "";
        }

        $data = [];

        $data = array_map(function ($e) {
            $typename = $e->attendance_type == 2 ? 'ยื่นเข้างาน' : 'ยื่นออกงาน';
            return self::formatdata(true, $typename, $e->status_work, self::convertStatusAtt(strtolower($e->status_work)), 'ot');
        }, []);

        return $data;
    }

    private function convertStatusLeave($status)
    {
        $msg = '';
        if ($status == strtolower('Pending')) {
            $msg = 'รออนุมัติ';
        } else if ($status == 'Approved') {
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
    private function convertStatusAtt($status)
    {
        $msg = '';
        if ($status == strtolower('Pending')) {
            $msg = 'รออนุมัติ';
        } else if ($status == 'Approved') {
            $msg = 'อนุมัติ';
        } else if ($status == strtolower('Rejected')) {
            $msg = 'ปฏิเสธ';
        } else if ($status == strtolower('Cancelled')) {
            $msg = 'ยกเลิก';
        }
        return $msg;
    }
    private function checktype($post)
    {
        return in_array(strtolower(explode(":", $post->type)[0]), self::SETTYPE) ? true : false;
    }
    private function TextConvert($type)
    {
        $msg = '';
        switch ($type) {
            case "leave":
                $msg = 'การขอลา';
                break;
            case "attendance":
                $msg = 'การยื่นเข้า - ออกงาน';
                break;
            case "ot":
                $msg = 'การขอทำงานล่วงเวลา(ot)';
                break;
            case "swapshift":
                $msg = 'การขอสลับกะการทำงาน';
                break;
            case "duringday":
                $msg = 'การขอยื่นเข้า-ออกระหว่างวัน';
                break;
            case "leaveduring":
                $msg = 'การยื่นออกระหว่างวัน';
                break;
        }
        return $msg;
    }
}
