<?php

use Mpdf\Tag\Tr;

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require APPPATH . 'libraries/RestAPI.php';
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE');

class Approve extends RestAPI
{
    public $data;
    public function __construct()
    {
        $data = [];
        parent::__construct();
        $this->data = $data;
        $this->validateAuth();
    }

    public function index_get()
    {
        try {
            self::setRes(['msg' => 'Approve API'], 200);
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }
    private function convertStatus($status)
    {
        $msg = '';
        if ($status == strtolower('Pending')) {
            $msg = 'รออนุมัติ';
        } else if ($status == strtolower('Approved') || $status == strtolower('Approve')) {
            $msg = 'อนุมัติ';
        } else if ($status == strtolower('Rejected') || $status == strtolower('Reject')) {
            $msg = 'ปฏิเสธ';
        } else if ($status == strtolower('Cancellation Requested')) {
            $msg = 'ขอการยกเลิก';
        } else if ($status == strtolower('Cancelled')) {
            $msg = 'ยกเลิก';
        } else if ($status == strtolower('Processing')) {
            $msg = 'กำลังดำเนินการ';
        } else if ($status == strtolower('waiting')) {
            $msg = 'แบบร่างเอกสาร/รอส่งอีเมลล์';
        }
        return $msg;
    }
    private function formatStatus($status)
    {
        return [
            'text'          => self::convertStatus(strtolower($status)),
            'status'        => strtolower($status)
        ];
    }
    private function formatDate($day)
    {
        $temp  = (object)date_parse($day);
        $format = $temp->hour ? "Y-m-d\TH:i:s\z" : "d/m/Y";
        return [
            'datetime'  => date_format(date_create($day), $format),
            'date'      => date_format(date_create($day), "d/m/Y"),
            'time'      =>  $temp->hour ? date_format(date_create($day), "H:i:s") : "00:00:00",
        ];
    }
    private function formatEmp($pd_id, bool $multi = false): array
    {
        if ($multi) {

            $result = $this->db->query("SELECT * FROM geerang_gts.personaldocument WHERE pd_id IN ({$pd_id}) ")->result();
            $data = [];
            foreach ($result as $key => $val) {
                $data[] = (object)[
                    'first_name' => $val->first_name,
                    'last_name' => $val->last_name,
                    'fullname'  => $val->first_name . ' ' . $val->last_name,
                ];
            }
            return $data;
        }
        $result = $this->db->get_where('geerang_gts.personaldocument', ['pd_id' => $pd_id])->row();
        return [
            'first_name' => $result->first_name,
            'last_name' => $result->last_name,
            'fullname'  => $result->first_name . ' ' . $result->last_name,
        ];
    }
    public function getTypeApprove_get()
    {
        try {
            $payload = (object)$this->input->get();
            $valid = self::validPayload(
                $payload,
                ['com_id', 'Company ID', 'required'],
            );
            if ($valid) self::setErr($valid, 403);
            $this->save($payload);

            $com_exist = $this->db->query('SELECT * FROM geerang_gts.users a WHERE a.id = ?', [$payload->com_id])->row();
            if (!$com_exist) self::setErr('Not found company', 200);

            $result = $this->db->query(
                "SELECT 
                a.application_rule_name,
                a.application_moduler_id
                FROM geerang_gts.application_moduler a
                WHERE 
              a.application_moduler_group_id  = 104 AND a.show_online =1  AND a.show_emp = 1
                ",
            )->result();
            $result = array_map(function ($e) {
                return (object)[
                    'id' => (int)$e->application_moduler_id,
                    'name' => strip_tags($e->application_rule_name)
                ];
            }, $result);
            self::setRes($result, 200);
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }

    public function getListapprove_get()
    {
        try {
            $payload = (object)$this->input->get();
            $valid = self::validPayload(
                $payload,
                ['com_id', 'Company ID', 'required'],
                ['emp_id', 'Employee ID', 'required'],
                ['type_id', 'Type Approve ID', 'optional'],
                ['type_name', 'Type Name Approve', 'optional'],
                ['position_id', 'Position ID', 'optional']
            );
            if ($valid) self::setErr($valid, 403);
            $this->save($payload);

            $com_exist = $this->db->query('SELECT * FROM geerang_gts.users a WHERE a.id = ?', [$payload->com_id])->row();
            if (!$com_exist) self::setErr('Not found company', 200);


            $pd_exist = $this->db->query('SELECT * FROM geerang_gts.personaldocument a WHERE a.pd_id = ?', [$payload->emp_id])->row();
            if (!$pd_exist) self::setErr('Not found Employee', 200);


            $filter = '';
            if ($payload->position_id) {
                $pos_exist = $this->db->get_where('geerang_gts.user_position', ['position_id' => $payload->position_id, 'user_id' => $payload->com_id])->row();
                if (!$pos_exist) self::setErr('Not found Position', 200);
                $filter .= " AND a.position_id IN({$payload->position_id})";
            }

            if ($payload->type_id) {
                $type_exist = $this->db->query(
                    "SELECT 
                    a.application_rule_name
                    FROM geerang_gts.application_moduler a
                    WHERE  a.application_moduler_group_id  = 104 AND a.show_online =1  AND a.show_emp = 1 AND a.application_moduler_id = ?",
                    [$payload->type_id]
                )->row();

                if (!$type_exist) self::setErr('Not found Type Approve ID');
            }

            $result = self::Setdata($payload, $filter);

            self::setRes($result, 200);
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }
    private function Setdata($payload, $filter = '')
    {

        $result = $this->db->query(
            "SELECT *
            FROM geerang_gts.position_keep a
            LEFT JOIN geerang_gts.user_position b ON b.user_id = a.user_id AND b.position_id = a.position_id
            LEFT JOIN geerang_gts.personaldocument c ON c.pd_id = a.pd_id 
            WHERE a.pd_id = ? AND a.user_id= ?  $filter 
            AND a.position_id != 0
            GROUP BY a.position_id",
            [$payload->emp_id, $payload->com_id]
        )->result();

        $first_position =   $result[0]->position_id;
        $result = array_map(function ($e) use ($payload, $first_position) {
            $filter = '';
            if ($payload->type_id) {
                $filter .= " AND a.application_moduler_id IN ({$payload->type_id})";
            }
            if ($payload->type_name) {
                $filter .= " AND a.application_rule_name LIKE '%{$payload->type_name}%' ";
            }
            $existCheck = $this->db->query(
                "SELECT 
                a.application_rule_name,
                b.*
                FROM geerang_gts.application_moduler a
                LEFT JOIN geerang_gts.user_position_rule b ON b.application_moduler_id = a.application_moduler_id
                WHERE 
                b.user_id = ? AND b.position_id =? AND a.application_moduler_group_id  = 104 AND a.show_online =1  AND a.show_emp = 1
                $filter
                ",
                [$e->user_id, $e->position_id]
            )->result();

            return (object)[
                'emp_id'        => (int)$e->pd_id,
                'first_name'    => $e->first_name,
                'last_name'     => $e->last_name,
                'fullname'      => $e->first_name . ' ' . $e->last_name,
                'com_id'        => (int)$e->user_id,
                'position_id'   => (int)$e->position_id,
                'position_name' => $e->position_name,
                'type'       => array_map(function ($ee) use ($e, $payload, $first_position) {
                    return (object) [
                        'id'        => (int) $ee->application_moduler_id,
                        'type'      => strip_tags($ee->application_rule_name),
                        'IsUse'     => (bool)$ee->is_read,
                        'data'      => self::getdata($ee->application_moduler_id, $first_position, $e->pd_id, $payload),
                    ];
                }, $existCheck),
            ];
        }, $result);
        return $result;
    }
    private function getdata($moduler, $position_id, $pd_id, $post): object
    {
        $this->load->model('api/Getnoti_model', 'noti');
        $this->load->model('hrm/Dashboard_model', 'Dashboard');
        $this->load->model('hrm/Attendance_model', 'Attendance');

        $downline = getDownline($post->com_id, $position_id);
        // $downline = array_diff($downline, [$pd_id]);
        if (count($downline) == 0) {
            array_push($downline, $pd_id);
        }
        $this->session->set_userdata([
            'company_id'    => $post->com_id,
            'pd_id'         => $post->emp_id,
            'loginby'       => 'employee',
            'downline_pd_id' =>   $downline,
        ]);
        $data = [];
        $row = 0;
        switch ($moduler) {
            case 15: {
                    $data = $this->noti->badgeleave($post->com_id, $post->emp_id, 'employee',  $downline);
                    $row = count($data);

                    $data = array_map(function ($e) {
                        return (object)[
                            'id'             => (int)$e->id,
                            'reason'         => $e->details,
                            'multiemp'       => (bool)false,
                            'employee'       => self::formatEmp($e->pd_id),
                            'date_request'   => self::formatDate($e->created_at),
                            'status_request' => self::formatStatus($e->status),
                        ];
                    }, $data);
                }
                break;
            case 55: {
                    $data = $this->noti->badgeapprove($post->com_id, $post->emp_id, 'employee',  $downline);
                    $row = count($data);
                    $data = array_map(function ($e) {
                        return (object)[
                            'id' => (int)$e->id,
                            'reason' => $e->reason,
                            'multiemp'       => (bool)false,
                            'employee'       => self::formatEmp($e->pd_id),
                            'date_request'   => self::formatDate($e->created_at),
                            'status_request' => self::formatStatus($e->status_work),
                        ];
                    }, $data);
                }
                break;
            case 70: {
                    $data = array_filter($this->Attendance->otapprove_attendance(), function ($e) {
                        $check = false;
                        if ($e->status_approve == 'pending') {
                            $check = true;
                        }
                        return $check;
                    });
                    $row = count($data);
                    $data = array_map(function ($e) {
                        $e = (object)$e;
                        return (object)[
                            'id'             => (int)$e->id,
                            'reason'         => $e->comment,
                            'multiemp'       => (bool)false,
                            'employee'       => self::formatEmp($e->pd_id),
                            'date_request'   => self::formatDate(date('Y-m-d', strtotime($e->datetime))),
                            'status_request' => self::formatStatus($e->status_approve),
                        ];
                    }, $data);
                }
                break;
            case 71: {

                    $data = array_filter($this->Attendance->swapapprove_attendance(), function ($e) {
                        $check = false;
                        if ($e->status_approve == 'pending') {
                            $check = true;
                        }
                        return $check;
                    });
                    $row = count($data);
                    $data = array_map(function ($e) {
                        $e = (object)$e;
                        return (object)[
                            'id'             => (int)$e->id,
                            'reason'         => $e->comment,
                            'multiemp'       => (bool)false,
                            'employee'       => self::formatEmp($e->pd_id_primary),
                            'date_request'   => self::formatDate($e->create_date),
                            'status_request' => self::formatStatus($e->status_approve),
                        ];
                    }, $data);
                }
                break;
            case 79: {
                    $data = $this->noti->payroll_approve($post->com_id, $post->emp_id, 'employee');

                    $row = count($data);
                    $data = array_map(function ($e) {
                        return (object)[
                            'id'                => (int)$e->id,
                            'reason'            => $e->payroll_name,
                            'multiemp'          => (bool)true,
                            'employee'          => self::formatEmp($e->pd_freeze_id, true),
                            'date_request'      => self::formatDate($e->create_date),
                            'status_request'    => self::formatStatus($e->status),
                        ];
                    }, $data);
                }
                break;
            case 83: {
                    $data = $this->noti->eva($post->com_id, $post->emp_id, 'employee');
                    $row = count($data);
                    $data = array_map(function ($e) {
                        $_status = $e->approve_result ? $e->approve_result : "pending";


                        return (object)[
                            'id'                => (int)$e->id,
                            'reason'            => '',
                            'multiemp'          => (bool)true,
                            'employee'          => self::formatEmp($e->group_pd_id, true),
                            'date_request'      => self::formatDate($e->create_at),
                            'status_request'    => self::formatStatus($_status),
                        ];
                    }, $data);
                }
                break;
            case 106: {
                    $data = array_filter($this->Attendance->loadLeaveOutApprove(), function ($e) {
                        $check = false;
                        if ($e->status == 'pending') {
                            $check = true;
                        }
                        return $check;
                    });
                    $row = count($data);
                    $data = array_map(function ($e) {
                        return (object)[
                            'id'                => (int)$e->id,
                            'reason'            => $e->comment,
                            'multiemp'          => (bool)false,
                            'employee'          => self::formatEmp($e->pd_id),
                            'date_request'      => self::formatDate($e->created_at),
                            'status_request'    => self::formatStatus($e->status),
                        ];
                    }, $data);
                }
                break;
            case 107: {

                    $row = count($data);
                    $data = array_filter($this->Attendance->loadLeaveOutEssApprove(), function ($e) {
                        $check = false;
                        if ($e->status == 'pending') {
                            $check = true;
                        }
                        return $check;
                    });
                    $row = count($data);
                    $data = array_map(function ($e) {
                        return (object)[
                            'id'                => (int)$e->id,
                            'reason'            => $e->reason,
                            'multiemp'          => (bool)false,
                            'employee'          => self::formatEmp($e->pd_id),
                            'date_request'      => self::formatDate($e->created_at),
                            'status_request'    => self::formatStatus($e->status),
                        ];
                    }, $data);
                }
                break;
            case 110: {
                    $this->load->model('hrm/Document_control_model', 'document_con');
                    $row =  $this->document_con->notiAprove_document(['all' => true]);

                    $result = $this->document_con->notiAprove_document(['json' => true]);
                    $data = array_map(function ($e) {
                        $e = (object)$e;
                        if ($e->name_id) {
                            $requestdata = $this->document_con->get_DocumentApprove(['type' => $e->name_id]);
                            $requestdata = array_values(array_filter($requestdata, function ($e) {
                                $check = false;
                                if ($e->status_approve == 'pending') {
                                    $check = true;
                                }
                                return $check;
                            }));
                            return (object)[
                                'document_id'  => (int) $e->id,
                                'document_name' => $e->name_th,
                                'document_request'  => array_map(function ($ee) {
                                    return (object)[
                                        'id'                => (int)$ee->doc_id,
                                        'reason'            => '',
                                        'multiemp'          => (bool)false,
                                        'employee'          => self::formatEmp($ee->pd_id),
                                        'date_request'      => self::formatDate($ee->create_at),
                                        'status_request'    => self::formatStatus($ee->status_approve),
                                    ];
                                }, $requestdata),
                            ];
                        }
                    }, $result);
                    $data = array_filter($data);
                }
                break;
            case 156: {
                    $data = $this->Dashboard->get_approve_certificate('pending');
                    $row = count($data);
                    $data = array_map(function ($e) {
                        $e = (object)$e;
                        $type = $e->show_salary == 'yes' ? "รับรองเงินเดือน" : "รับรองการทำงาน";
                        return (object)[
                            'id'             => (int)$e->certificate_work_id,
                            'reason'         => $e->reason,
                            'multiemp'       => (bool)false,
                            'employee'       => self::formatEmp($e->pd_id),
                            'date_request'   => self::formatDate($e->create_date),
                            'status_request' => self::formatStatus($e->status),
                            'type'           => $type
                        ];
                    }, $data);
                }
                break;
            case 162: {
                    $data = $this->noti->count_face($post->com_id, $post->emp_id, 'employee',  $downline);
                    $row = count($data);
                    $data = array_map(function ($e) {
                        return (object)[
                            'id' => (int)$e->logs_id,
                            'multiemp'       => (bool)true,
                            'employee'       => self::formatEmp($e->pd_id),
                            'date_request'   => self::formatDate($e->created_at),
                            'status_request' => self::formatStatus($e->status),
                        ];
                    }, $data);
                }
                break;
            case 181: {
                    $data = $this->noti->badgeCancelLeave($post->com_id, $post->emp_id, 'employee',  $downline);
                    $row = count($data);
                    $data = array_map(function ($e) {
                        return (object)[
                            'id' => (int)$e->id,
                            'employee'       => self::formatEmp($e->pd_id),
                            'date_request'   => self::formatDate($e->created_at),
                            'status_request' => self::formatStatus($e->status_cancle_leave),
                        ];
                    }, $data);
                }
                break;
        }
        return (object)[
            'waiting' => $row,
            'request' => $data,
        ];
    }
    public  function acceptApprove_post()
    {
        $requestEsign = [
            110, 156
        ];
        try {
            // if (1 == 1)  self::setErr("Unavaliable wait a moment.", 500);
            $payload = (object)$this->input->post();
            $valid = self::validPayload(
                $payload,
                ['com_id', 'Company ID', 'required'],
                ['emp_id', 'Employee ID', 'required'],
                ['type_id', 'Type  Approve ID', 'required'],
                ['request_id', 'Request Approve Id', 'required'],
                ['status', 'Status Approve', 'required|in_array:approve,reject'],
                ['esign_id', 'Electronic Signature ID', 'optional'],
                ['comment', 'Approve Comment', 'optional'],
                ['document_id', "Document Type ID", 'optional'],
            );

            if ($valid) self::setErr($valid, 403);
            $this->save($payload);



            $com_exist = $this->db->query('SELECT * FROM geerang_gts.users a WHERE a.id = ?', [$payload->com_id])->row();
            if (!$com_exist) self::setErr('Not found company', 200);


            $pd_exist = $this->db->query('SELECT * FROM geerang_gts.personaldocument a WHERE a.pd_id = ?', [$payload->emp_id])->row();
            if (!$pd_exist) self::setErr('Not found Employee', 200);


            $type_exist = $this->db->query(
                "SELECT 
                    a.application_moduler_id,
                    a.application_rule_name
                    FROM geerang_gts.application_moduler a
                    WHERE  a.application_moduler_group_id  = 104 AND a.show_online =1  AND a.show_emp = 1 AND a.application_moduler_id = ?",
                [$payload->type_id]
            )->row();

            if (!$type_exist) self::setErr('Not found Type Approve ID');

            if (in_array($payload->type_id, $requestEsign)) {
                if (!$payload->esign_id) self::setErr('Please input Electronic Signature ID When Approve Document', 200);
                $sign_exist = $this->db->get_where('geerang_hrm.e_sign', ['pd_id' => $payload->emp_id,  'sign_id' => $payload->esign_id, 'status' => "active"])->row();
                if (!$sign_exist) self::setErr('Not found Electronic Signature ID', 200);
            }

            if ($payload->type_id == 110) {
                if (!$payload->document_id) self::setErr('Please Input Document Type ID.', 200);
                $exist_doc = $this->db->query(
                    "SELECT * FROM geerang_hrm.document_menu WHERE id =?  AND is_active = 'active'",
                    [$payload->document_id]
                )->row();
                if (!$exist_doc) self::setErr('Not found Type Document ', 200);
            }


            if (self::ExistApprove($payload)) self::setErr('Not found Data for Approve', 200);

            $res = (object)self::acceptdata($payload);

            self::setRes($res, 200);
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }
    private function ExistApprove($payload)
    {
        $valid = false;
        $req = (object)[
            'com_id' => $payload->com_id,
            'emp_id' => $payload->emp_id,
            'type_id' => $payload->type_id,
        ];
        $defualt = $this->db->get_where('geerang_gts.position_keep', ['default_position' => 1,  'pd_id' => $payload->emp_id, 'user_id' => $payload->com_id])->row();

        $or_where = '';
        if ($defualt->default_position == 1) {
            $or_where .= " AND a.default_position = 1";
        }

        $position = $this->db->query(
            "SELECT * 
            FROM geerang_gts.position_keep a 
            LEFT JOIN geerang_gts.user_position b ON b.position_id = a.position_id AND b.user_id = a.user_id
            WHERE a.user_id = ? AND a.pd_id = ?   $or_where
            GROUP BY a.position_id 
            ",
            [$payload->com_id, $payload->emp_id]
        )->result();

        $first_position = $position[0]->position_id;
        $filter = " AND a.position_id = $first_position";

        $result = self::Setdata($req, $filter)[0]->type[0]->data->request;

        if (!$result) $valid = true;
        return  $valid;
    }
    private function acceptdata($payload)
    {
        $this->load->model('hrm/Evaluate_model', 'Evaluate');
        $this->load->model('hrm/Attendance_model', 'Attendance');
        $this->load->model('hrm/Leave_model', 'leaves');
        $this->load->model('hrm/Dashboard_model', 'Dashboard');
        $this->load->model('AppNoti_model', 'AppNoti');


        $this->session->set_userdata([
            'pd_id' => $payload->emp_id,
            'company_id' => $payload->com_id,
            'first_name' => $this->db->get_where('geerang_gts.personaldocument', ['pd_id' => $payload->emp_id])->row('first_name'),
            'last_name' => $this->db->get_where('geerang_gts.personaldocument', ['pd_id' => $payload->emp_id])->row('last_name'),
        ]);
        $status = false;
        $msg = 'Failed';
        $msgErr = "This request has been approved.";

        $noti = [];
        $comment = $payload->comment ? $payload->comment : '';
        switch ($payload->type_id) {
            case 15: {

                    $status = true;
                    $msg = "Success";
                    $approvestatus = [
                        'approve' => "Approved",
                        'reject' => "Rejected",
                    ];

                    $exist = $this->db->get_where('geerang_hrm.employeeleaves', ['id' => $payload->request_id, 'com_id' => $payload->com_id, 'status' => 'Pending'])->row();
                    if (!$exist) self::setErr($msgErr, 200);


                    $leaveset = $this->db->get_where('geerang_hrm.leavetypes_set', ['id' => $exist->personal_leave_id])->row();

                    $req = [
                        "emp_id"                => $exist->pd_id,
                        "leave_date_start"      => $exist->date_start,
                        "leave_date_end"        => $exist->date_end,
                        "leave_without_pay"     => $leaveset->leave_without_pay,
                        "emp_leave_id"          => $exist->id,
                        "details"               => $comment,
                        "status_from"           => "Pending",
                        "status_to"             => $approvestatus[$payload->status],
                    ];

                    $this->leaves->manageLeave($req);
                    $text =  $payload->status == 'approve' ? "ได้รับการอนุมัติ" : "ไม่ได้รับการอนุมัติ";

                    $noti = [
                        'sendnoti'  => true,
                        'pd_id' => $exist->pd_id,
                        'topic' => 'อนุมัติการลา',
                        'content'   => 'การอนุมัติการลาของคุณ' .  $text,
                        'url'   => 'dashboard/approveList#leaveApproveModal',
                        'type'  => "approveholidayresult"
                    ];
                }
                break;
            case 55: {
                    $status = true;
                    $msg = "Success";

                    $exist = $this->db->get_where("geerang_hrm.attendance_log", ['status_work' => "Pending", 'id' => $payload->request_id, 'com_id' => $payload->com_id])->row();

                    if (!$exist) self::setErr($msgErr, 200);
                    $approvestatus = [
                        'approve' => "Approved",
                        'reject' => "Rejected",
                    ];
                    $req = [
                        'a_id' =>   $exist->a_id,
                        'status_work' => $approvestatus[$payload->status],
                        'approve_comment' =>  $comment,
                        'id' =>  $exist->id,  // att_log_id,
                        'attendance_type_id' =>  $exist->attendance_type,
                        'date_attendace' => $exist->log_date_in,
                        'time_in_attendance' => $exist->log_time_in,
                        'date_out_attendace' => $exist->log_date_out,
                        'time_out_attendance' =>  $exist->log_time_out,
                    ];
                    $this->Attendance->saveApproveAttendance($req);
                    $text =  $payload->status == 'approve' ? "ได้รับการอนุมัติ" : "ไม่ได้รับการอนุมัติ";
                    $noti = [
                        'sendnoti'  => true,
                        'pd_id' =>  $exist->pd_id,
                        'topic' => 'อนุมัติยื่นเข้า-ออกงาน',
                        'content'   => 'การอนุมัติการยื่นขออเข้า-ออกงานของคุณ' .  $text,
                        'url'   => '#',
                        'type'  => "approveattendance"
                    ];
                }
                break;
            case 70: {
                    $status = true;
                    $msg = "Success";
                    $approvestatus = [
                        'approve' => "Approved",
                        'reject' => "Rejected",
                    ];

                    $data = array_filter($this->Attendance->otapprove_attendance(), function ($e) use ($payload) {
                        $check = false;
                        if ($e->status_approve == 'pending' && $e->id == $payload->request_id) {
                            $check = true;
                        }
                        return $check;
                    })[0];

                    $exist = $this->db->get_where('geerang_hrm.ot_request', ['id' => $payload->request_id, 'company_id' => $payload->com_id, 'status_approve' => 'pending'])->row();
                    if (!$exist) self::setErr($msgErr, 200);

                    $req = [
                        'status_approve' => $approvestatus[$payload->status],
                        'approve_comment' => $comment,
                        'app_shift_id' => $data->shift_id,
                        'date_attendace' => $data->datetime,
                        'emp_id' => "",
                        'id' => $data->id,
                        'pd_id' => $data->pd_id,
                    ];

                    $result = $this->Attendance->saveApproveOT($req);

                    $text =  $payload->status == 'approve' ? "ได้รับการอนุมัติ" : "ไม่ได้รับการอนุมัติ";
                    $noti = [
                        'sendnoti'  => true,
                        'pd_id' => $exist->pd_id,
                        'topic' => 'การอนุมัติทำงานล่วงเวลา (OT)',
                        'content'   => 'การอนุมัติทำงานล่วงเวลา (OT)' .  $text,
                        'url'   => 'dashboard/approveList#OTApproveModal',
                        'type'  => "approveot"
                    ];
                }
                break;
            case 71: {

                    $status = true;
                    $msg = "Success";
                    $approvestatus = [
                        'approve' => "Approved",
                        'reject' => "Rejected",
                    ];

                    $exist = $this->db->get_where('geerang_hrm.shift_swap', ['status_approve' => "pending", 'id' => $payload->request_id, 'company_id' => $payload->com_id])->row();

                    if (!$exist) self::setErr($msgErr, 200);

                    $req = [
                        'id' => $exist->id,
                        'approve_comment' => $comment,
                        'status_approve' => $approvestatus[$payload->status],
                    ];

                    $this->Attendance->saveApproveSWAP($req);
                    $text =  $payload->status == 'approve' ? "ได้รับการอนุมัติ" : "ไม่ได้รับการอนุมัติ";
                    $personal  = $this->db->get_where('geerang_gts.personaldocument', ['pd_id' => $exist->pd_id_secondary])->row();
                    $noti = [
                        'sendnoti'  => true,
                        'pd_id' =>  $exist->pd_id_primary,
                        'topic' => 'การอนุมัติสลับกะงานของคุณกับ' .  $personal->first_name . ' ' . $personal->last_name,
                        'content'   => 'การอนุมัติสลับกะงาน' .  $text,
                        'url'   => 'dashboard/approveList#swapApproveModal',
                        'type'  => "swap"
                    ];
                }
                break;
            case 79: {
                    $exist_approve = $this->db->query("SELECT COUNT(a.id) as c FROM geerang_hrm.payroll_approve_group a WHERE a.payroll_id = ? AND (a.status_approve = 'approve')", [$payload->request_id])->row();
                    if ($exist_approve->c == 0) self::setErr("Can't Approve Payroll", 200);

                    $data = $this->noti->payroll_approve($payload->com_id, $payload->emp_id, 'employee')[0];

                    $status = true;
                    $msg = "Success";
                    $approvestatus = [
                        'approve'   => "approve",
                        'reject'    => "reject",
                    ];


                    $exist_track = $this->db->get_where('geerang_hrm.payroll_approve_track', ['approve_id' => $payload->emp_id, 'status_payroll' => 'approve', 'payroll_id' => $data->payroll_id])->row();

                    if ($exist_track->current_track != 1) self::setErr("Finished or wait for the next person?", 200);
                    if ($exist_track->status == 'approve') self::setErr("It's already been approved.", 200);

                    $req = (object)[
                        'payroll_id' => $data->payroll_id,
                        'track_id'  => $exist_track->id,
                        'data_confirm' => $approvestatus[$payload->status],
                    ];
                    self::confirmpayrolltrack($req);
                    $text =  $payload->status == 'approve' ? "ได้รับการอนุมัติ" : "ไม่ได้รับการอนุมัติ";

                    $noti = [
                        'sendnoti'  => false,

                    ];
                }
                break;
            case 83: {
                    $status = true;
                    $msg = "Success";
                    $approvestatus = [
                        'approve' => "Approve",
                        'reject' => "Reject",
                    ];

                    $exist = $this->db->get_where('geerang_hrm.evaluate_approve_result', ['id_evaform' => $payload->request_id, 'approve_result' => NULL])->row();

                    if (!$exist) self::setErr($msgErr, 200);

                    $req = [
                        'id_evaform' => encrypt($payload->request_id),
                        'eva_dc_revision' => 0,
                        'Eva_name' =>  $exist->eva_name,
                        'Approve_results' => $approvestatus[$payload->status]
                    ];
                    $result_approve = $this->Evaluate->approve_result($req);
                    $text =  $payload->status == 'approve' ? "การอนุมัติ" : "ไม่การอนุมัติ";
                    $noti = [
                        'sendnoti'  => true,
                        'pd_id'     =>  $result_approve['create_by'][0]->pd_id,
                        'topic'     => 'แบบประเมิน',
                        'content'   => 'ผู้อนุมัติแบบประเมินได้ทำการ' .  $text,
                        'url'       => 'evaluate#modal_show_report_eva',
                        'type'      => "evaluateapprove"
                    ];
                }
                break;
            case 106: {

                    $status = true;
                    $msg = "Success";
                    $approvestatus = [
                        'approve' => "true",
                        'reject' => "false",
                    ];

                    $exist = $this->db->get_where('geerang_hrm.leave_outside', ['id' => $payload->request_id, 'com_id' => $payload->com_id, 'status' => 'pending'])->row();
                    if (!$exist) self::setErr($msgErr, 200);

                    $data = array_filter($this->Attendance->loadLeaveOutApprove(), function ($e) use ($payload) {
                        $check = false;
                        if ($e->status == 'pending' && $e->id == $payload->request_id) {
                            $check = true;
                        }
                        return $check;
                    })[0];

                    $req = [
                        'is_quota' => 0,
                        'leaveout_id' => $payload->request_id,
                        'add_emp_id' => $data->pd_id,
                        'is_approve' => $approvestatus[$payload->status],
                        'leave_date_start' => date('Y-m-d', strtotime($data->time_out)),
                        'leave_time_start' => date('H:i:s', strtotime($data->time_out)),
                        'leave_date_end' => date('Y-m-d', strtotime($data->time_in)),
                        'leave_time_end' => date('H:i:s', strtotime($data->time_in)),
                        'leave_type' => '',
                        'add_details' => '',
                        'is_money' => 0,
                        'approve_comment' => $comment,

                    ];

                    $this->leaves->saveApproveLeaveOut($req);
                    $text =  $payload->status == 'approve' ? "ได้รับการอนุมัติ" : "ไม่ได้รับการอนุมัติ";
                    $noti = [
                        'sendnoti'  => true,
                        'pd_id' =>  $data->pd_id,
                        'topic' => 'การอนุมัติออกงานระหว่างกะงาน',
                        'content'   => 'การอนุมัติออกงานระหว่างกะงานของคุณ' . $text,
                        'url'   => '',
                        'type'  => "approveLeaveOutside"
                    ];
                }
                break;
            case 107: {
                    $status = true;
                    $msg = "Success";
                    $exist = $this->db->get_where("geerang_hrm.leave_outside_log", ['status' => "pending", 'id' => $payload->request_id, 'com_id' => $payload->com_id])->row();
                    if (!$exist) self::setErr($msgErr, 200);

                    $approvestatus = [
                        'approve' => "Approved",
                        'reject' => "Rejected",
                    ];
                    $req = [
                        'id' => $payload->request_id,
                        'approve_comment' => $comment,
                        'status' => $approvestatus[$payload->status],
                    ];
                    $this->Attendance->saveApproveLeaveOutEss($req);

                    $text =  $payload->status == 'approve' ? "ได้รับการอนุมัติ" : "ไม่ได้รับการอนุมัติ";
                    $noti = [
                        'sendnoti'  => true,
                        'pd_id' =>  $exist->pd_id,
                        'topic' => 'อนุมัติการเข้าออกลาระหว่างวัน',
                        'content' => 'อนุมัติการเข้าออกลาระหว่างวันของคุณ ' . $text,
                        'url'   => '',
                        'type'  => ""
                    ];
                }
                break;
            case 110: {

                    $status = true;
                    $msg = "Success";

                    $approvestatus = [
                        'approve' => "Approved",
                        'reject' => "Rejected",
                    ];

                    $req = [
                        'id_relate'         => $payload->request_id,
                        'id'                => $payload->request_id,
                        'status'            => $approvestatus[$payload->status],
                        'status_approve'    => $approvestatus[$payload->status],
                        'approve_comment'   => $comment,
                        'sign'              => encrypt($payload->esign),
                    ];

                    $exist_type = $this->db->query(
                        "SELECT * FROM geerang_hrm.document_menu WHERE id =?  AND is_active = 'active'",
                        [$payload->document_id]
                    )->row();

                    $type = $exist_type->name_id;
                    $exist_document = $this->db->get_where('geerang_hrm.document_approve', [
                        'type' => $type,
                        'doc_id' => $payload->request_id,
                        'com_id' => $payload->com_id,
                        'pd_id' => $payload->emp_id
                    ])->row();

                    if (!$exist_document) self::setErr('You are not affiliated with this document.', 200);

                    if ($type == 'resign') {
                        $this->Attendance->saveApproveResign($req);
                    } else {
                        $this->Attendance->saveApproveType($req, $type);
                    }

                    $personal = $this->db->get_where('geerang_gts.personaldocument', ['pd_id' => $payload->emp_id])->row();

                    $document = $this->db->get_where('geerang_hrm.document_approve', [
                        'type' => $type,
                        'doc_id' => $payload->request_id,
                        'com_id' => $payload->com_id,
                    ])->result();

                    $text =  $payload->status == 'approve' ? "ได้ทำการอนุมัติ" : "ได้ปฏิเสธการอนุมัติ";
                    $noti = [
                        'sendnoti'  => true,
                        'pd_id'     => $document[0]->pd_id,
                        'topic'     => 'เอกสารองค์กร : ' . $exist_type->name_th,
                        'content'   => $personal->first_name . ' ' . $personal->last_name . $text,
                        'url'       => '#',
                        'type'      => "approvedocument"
                    ];
                }
                break;
            case 156: {
                    $status = true;
                    $msg = "Success";
                    $approvestatus = [
                        'approve' => "approve",
                        'reject' => "reject",
                    ];

                    $exist = $this->db->get_where('geerang_hrm.certificate_work', ['certificate_work_id' => $payload->request_id, 'company_id' => $payload->com_id, 'status' => 'pending'])->row();
                    if (!$exist) self::setErr($msgErr, 200);


                    $req = [
                        'encrypt_sign_id' => encrypt($payload->esign_id),
                        'encrypt_certificate_work_id' => encrypt($payload->request_id),
                        'expiration_date' => 30,
                        'status' => $approvestatus[$payload->status],
                        'approver_comment' => $comment,
                    ];

                    $this->Dashboard->approve_certificate_work($req);
                    $text =  $payload->status == 'approve' ? "ได้รับการอนุมัติ" : "ไม่ได้รับการอนุมัติ";

                    $noti = [
                        'sendnoti'  => true,
                        'pd_id' => $exist->requester_pd_id,
                        'topic' => 'อนุมัติหนังสือรับรอง',
                        'content'   => 'การอนุมัติหนังสือรับรองของคุณ' .  $text,
                        'url'   => '',
                        'type'  => "approvecertificate"
                    ];
                }
                break;
            case 162: {
                    $status = true;
                    $msg = "Success";

                    $exist = $this->db->get_where('geerang_gts.personal_face_logs', ['status' => "pending", 'logs_id' => $payload->request_id, 'requester_company_id' => $payload->com_id])->row();
                    if (!$exist) self::setErr($msgErr, 200);

                    $approvestatus = [
                        'approve' => "approve",
                        'reject' => "reject",
                    ];

                    $req = (object)[
                        'status' => $approvestatus[$payload->status],
                        'logs' => $payload->request_id,
                        'emp_id' => $payload->emp_id,
                    ];
                    self::approveFace($req);
                    $text =  $payload->status == 'approve' ? "ได้รับการอนุมัติ" : "ไม่ได้รับการอนุมัติ";
                    $noti = [
                        'sendnoti'  => true,
                        'pd_id'     =>  $exist->pd_id,
                        'topic'     => 'อนุมัติการเปลี่ยนใบหน้า',
                        'content'   => 'อนุมัติการเปลี่ยนใบหน้า ' . $text,
                        'url'       => '#',
                        'type'      => "approve_face"
                    ];
                }
                break;
            case 181: {
                    $status = true;
                    $msg = "Success";

                    $exist = $this->db->get_where('geerang_hrm.employeeleaves', [
                        'id'                    => $payload->request_id,
                        'com_id'                => $payload->com_id,
                        'status_cancle_leave'   => 'Pending'
                    ])->row();

                    if (!$exist) self::setErr($msgErr, 200);

                    $approvestatus = [
                        'approve' => "Approved",
                        'reject' => "Rejected",
                    ];
                    $leaveset = $this->db->get_where('geerang_hrm.leavetypes_set', ['id' => $exist->personal_leave_id])->row();
                    $req = [
                        'emp_id'            => $exist->pd_id,
                        'status_to'         =>  $approvestatus[$payload->status],
                        'emp_leave_id'      => $exist->id,
                        'leave_date_start'  => $exist->date_start,
                        'leave_date_end'    => $exist->date_end,
                        'leave_without_pay' => $leaveset->leave_without_pay,
                        'details'           => $exist->details,
                        'status_from'       => 'Pending'
                    ];

                    $this->leaves->manageCancleLeave($req);
                    $text =  $payload->status == 'approve' ? "ได้รับการอนุมัติ" : "ไม่ได้รับการอนุมัติ";
                    $noti = [
                        'sendnoti'  => true,
                        'pd_id'     =>  $exist->pd_id,
                        'topic'     => 'อนุมัติยกเลิกการลา',
                        'content'   => 'การอนุมัติยกเลิกการลาของคุณ ' . $text,
                        'url'       => 'dashboard/approveList#leaveApproveModal',
                        'type'      => "approveholidayresult"
                    ];
                }
                break;
        }

        $person = $this->db->get_where('geerang_gts.personaldocument', ['pd_id' => $payload->emp_id])->row();
        $fullname = $person->first_name . ' ' . $person->last_name;
        $row = (object)$noti;
        if ($row->sendnoti) {
            $this->AppNoti->send_noti(
                $row->topic,
                $row->content . " โดย" . $fullname,
                $row->pd_id,
                $payload->com_id,
                $row->url,
                [
                    'type' => $row->type
                ]
            );
        }

        unset($_SESSION['pd_id']);
        unset($_SESSION['company_id']);
        unset($_SESSION['first_name']);
        unset($_SESSION['last_name']);
        return [
            'status' => $status,
            'message' => strtoupper($payload->status) . ' ' . $msg,
        ];
    }

    private function approveFace($payload)
    {
        $status = $payload->status == 'approve' ? 'success' : 'exist_reject';

        $setting = $this->db->query('SELECT * FROM geerang_hrm.approve_setting a WHERE a.type = "face"')->row();
        $approve = $this->db->query('SELECT * FROM geerang_hrm.approve_setting_track a WHERE a.id_relate = ? AND a.type = "face"', [$payload->logs])->result();
        $push = [];
        $is_end = false;

        $log = $this->db->query('SELECT * FROM geerang_gts.personal_face_logs a WHERE a.logs_id = ?', [$payload->logs])->row();

        if (!$approve) {
            $this->db->update(
                'geerang_gts.personal_face_logs',
                [
                    'status' => $status,
                    'approver_pd_id' => $payload->emp_id
                ],
                ['logs_id' => $payload->logs]
            );

            foreach ($approve as $key => $value) {
                array_push($push, [
                    'id' => $value->id,
                    'status' => $payload->status,
                ]);
            }
            $is_end = true;
        } else {
            $is_last_approve = false;
            $in = 0;
            foreach ($approve as $key => $value) {
                $_status = $payload->status == 'reject' ? 'reject' : $value->status;
                if (($value->approve_pd_id == $payload->emp_id || ($value->approve_module == 'org' && $key == 0)) && $payload->status != 'reject' && $key + 1 == $value->approve_sort) {
                    $in = $key + 1;
                    $_status = 'approve';
                }

                if ($key == count($approve) - 1 && ($value->approve_pd_id == $payload->emp_id || ($value->approve_module == 'org' && $key == 0))) {
                    $this->db->update(
                        'geerang_gts.personal_face_logs',
                        [
                            'status' => $status,
                            'approver_pd_id' => $payload->emp_id
                        ],
                        ['logs_id' => $payload->logs]
                    );
                    $is_end = true;
                }

                array_push($push, [
                    'id' => $value->id,
                    'status' => $_status,
                    'current_track' => $in == $key ? 1 : 0,
                ]);
            }
        }

        $this->db->update_batch('geerang_hrm.approve_setting_track', $push, 'id');
    }
    private function confirmpayrolltrack($post)
    {
        $this->load->model('hrm/Payroll_model', 'Payroll');


        $pd_id = $post->emp_id;
        $com_id = $post->com_id;
        if ($post->data_confirm) {

            if ($post->data_confirm == 'approve') {
                $tracks = $this->db->query("SELECT * FROM geerang_hrm.payroll_approve_track a WHERE a.payroll_id = ? AND a.status_payroll = 'approve' ORDER BY a.id ASC", [$post->payroll_id])->result();
                $index = array_search($pd_id, array_map(fn ($e) => $e->approve_id, $tracks));

                $is_continue = true;
                if (count($tracks) == $index + 1) $is_continue = false;

                if ($is_continue) {

                    $this->db->update('geerang_hrm.payroll_approve_track', array('current_track' => 0), array('payroll_id' => $post->payroll_id));
                    if (!empty($tracks)) {
                        $ar_pd_id = [];
                        for ($i = 0; $i < count($tracks); $i++) {
                            $ar_pd_id[] = $tracks[$i]->approve_id;
                        }
                        $index = array_search($pd_id, $ar_pd_id);

                        if ($ar_pd_id[$index] == end($ar_pd_id)) { // ถ้าตัวที่ค้นหาหรือส่งค่ามาเป็นตัวสุดท้าย
                            $next_value = $ar_pd_id[0];  // ให้ค่าตัวถัดไปเป็น ค่า array ของ key ตัวแรก
                        } else {
                            $next_value = $ar_pd_id[$index + 1]; // .ให้ค่าตัวถัดไปเป็น ค่า array ของ key ของตัวถัดไป
                        }
                    }
                    $alert_pd[] = $next_value;

                    $this->db->update('geerang_hrm.payroll_approve_track', array('status' => 'approve'), array('id' => $post->track_id));
                    $this->db->update('geerang_hrm.payroll_approve_track', array('current_track' => 1), array('payroll_id' => $post->payroll_id, 'approve_id' => $next_value, 'status_payroll' => 'approve'));

                    $subject = l('hrm.payroll.pls_check_payroll');
                    $comment = l('hrm.payroll.sent_payroll_approve');
                    $this->db->insert('geerang_hrm.payroll_track_comment', array('track_id' => $post->track_id, 'payroll_id' => $post->payroll_id, 'comment' => $comment, 'date_time' => date("Y-m-d H:i:s")));
                    $result =   $this->db->update('geerang_hrm.payroll_approve_track', array('status' => 'approve', 'current_track' => 0, 'datetime' => date("Y-m-d H:i:s")), array('id' => $post->track_id));
                } else {
                    $data = $this->getPayrollViewInfo($post->payroll_id);
                    $emp = $data['data'];
                    $fields = $data['fields'];
                    $this->load->config('preallocate');

                    $payload = [];

                    $payroll_d = $this->db->query('SELECT * FROM geerang_hrm.payroll a WHERE a.id = ?', [$post->payroll_id])->row();
                    $title = $this->config->item('titleth');
                    foreach ($emp as $key => $value) {
                        $value = (object)$value;
                        $pdDetail = $this->Payroll->get_pddetail($value->pd_id);
                        $address = [$pdDetail['address'], $this->Payroll->get_location($pdDetail['province_id'], $pdDetail['amphoe_id'], $pdDetail['district_id']), $pdDetail['zipcode']];

                        $choice_tax = '1';
                        if ($value->c_position_type == 'outsource') {
                            $choice_tax = '4';
                        }

                        $payload[] = [
                            'company_id' => $com_id,
                            'payroll_id' => $post->payroll_id,
                            'pay_date' => $payroll_d->payroll_range_end,
                            'titlename' => (!empty($title[$pdDetail['title']]) ? $title[$pdDetail['title']] : ''),
                            'name' => $pdDetail['first_name'],
                            'surname' => $pdDetail['last_name'],
                            'idcard' => $pdDetail['id_card'],
                            'salary' => $value->total_income - $value->total_outcome,
                            'tax' => $value->outcome['tax'],
                            'num_order' => $key + 1,
                            'other' => 0,
                            "pay_support" => $value->outcome['sso'],
                            "pay_sso_com" => $value->tax_sso_support,
                            "company_group" => 0,
                            "address" => (!empty($address) ? implode(' ', $address) : ''),
                            "site" => $com_id,
                            "payment_method" => $value->payment_method,
                            "base_salary" => $value->salary,
                            "deduct_early" => $value->outcome['early'],
                            "deduct_absent" => $value->outcome['absent'],
                            "deduct_late" => $value->outcome['late'],
                            "deduct_leave" => $value->outcome['leave'],
                            "income_sumsso" => $value->sum_insso,
                            "income_sumtax" => $value->sum_intax,
                            "outcome_sumsso" => $value->sum_outsso,
                            "outcome_sumtax" => $value->sum_outtax,
                            "ot_sum" => $value->ot_sum,
                            "incomecal_sum" => array_sum($value->new_field['income']),
                            "deductcal_sum" => array_sum($value->new_field['outcome']),
                            "grand_total_incomesalary" => $value->total_income,
                            "grand_total_deductsalary" => $value->total_outcome,
                            "grand_total_salary" => $value->total_income - $value->total_outcome,
                            "choice_tax" => $choice_tax,
                            "deduct_discp" => $value->outcome['discpt'],
                            'pvd_money' => $value->outcome['pvd'],
                            // "pvd_money" => ($post['pvd_money'][$key] > 0 ? $post['pvd_money'][$key] : null)
                        ];
                    }

                    $subject = l('nav_Pass');
                    $comment = l('nav_m_payroll');
                    $approve_all = $this->db->get_where('geerang_hrm.payroll_approve_track', array('payroll_id' => $post->payroll_id))->result_array();
                    foreach ($approve_all as $row) {
                        $alert_pd[] = $row['approve_id'];
                    }

                    $this->db->insert_batch('geerang_hrm.payroll_tax', $payload);
                    $this->db->update('geerang_hrm.payroll', array('status' => 'approve', 'status_check_approve' => '1'), array('id' => $post->payroll_id));
                    $this->db->insert('geerang_hrm.payroll_track_comment', array('track_id' => $post->track_id, 'payroll_id' => $post->payroll_id, 'comment' => $comment, 'date_time' => date("Y-m-d H:i:s")));
                    $result = $this->db->update('geerang_hrm.payroll_approve_track', array('status' => 'approve', 'datetime' => date("Y-m-d H:i:s")), array('id' => $post->track_id));
                }
            }
            if ($post->data_confirm == 'reconsider') {
                //คืนค่า crrent approve
                $this->db->update('geerang_hrm.payroll_approve_track', array('current_track' => 0), array('payroll_id' => $post->payroll_id));
                // get ค่าคนที่สร้าง 
                $creator = $this->db->get_where('geerang_hrm.payroll_approve_track', array('payroll_id' => $post->payroll_id, 'status_payroll' => 'create'))->row_array();
                $this->db->update('geerang_hrm.payroll_approve_track', array('status' => 'edit', 'current_track' => 1), array('id' => $creator['id']));

                //ส่งถึงทุกคนให้ทราบ
                $alert_pd[] = $creator['approve_id'];
                $approve = $this->db->get_where('geerang_hrm.payroll_approve_track', array('payroll_id' => $post->payroll_id, 'status_payroll' => 'approve'))->result_array();
                foreach ($approve as $row) {
                    $alert_pd[] = $row['approve_id'];
                }

                $this->db->update('geerang_hrm.payroll_approve_group', array('status_approve' => 'waiting'), array('payroll_id' => $post->payroll_id));
                $this->db->update('geerang_hrm.payroll', array('status' => 'edit'), array('id' => $post->payroll_id));

                $comment = l('hrm.payroll.pls_reconsider_check');
                $subject = l('hrm.payroll.sent_reconsider');
                $this->db->insert('geerang_hrm.payroll_track_comment', array('track_id' => $post->track_id, 'payroll_id' => $post->payroll_id, 'comment' => $comment, 'date_time' => date("Y-m-d H:i:s")));
                $result =   $this->db->update('geerang_hrm.payroll_approve_track', array('status' => 'pending', 'datetime' => date("Y-m-d H:i:s")), array('payroll_id' => $post->payroll_id, 'status_payroll' => 'approve'));
            }
            if ($post->data_confirm == 'reject') {
                //คืนค่า crrent approve
                $this->db->update('geerang_hrm.payroll_approve_track', array('current_track' => 0), array('payroll_id' => $post->payroll_id));
                // get ค่าคนที่สร้าง 
                $creator = $this->db->get_where('geerang_hrm.payroll_approve_track', array('payroll_id' => $post->payroll_id, 'status_payroll' => 'create'))->row_array();
                $this->db->update('geerang_hrm.payroll_approve_track', array('status' => 'edit', 'current_track' => 1), array('id' => $creator['id']));

                //ส่งถึงทุกคนให้ทราบ
                $alert_pd[] = $creator['approve_id'];
                $approve = $this->db->get_where('geerang_hrm.payroll_approve_track', array('payroll_id' => $post->payroll_id, 'status_payroll' => 'approve'))->result_array();
                foreach ($approve as $row) {
                    $alert_pd[] = $row['approve_id'];
                }

                $this->db->update('geerang_hrm.payroll', array('status' => 'reject'), array('id' => $post->payroll_id));

                $comment = l('hrm.payroll.pls_check_payroll');
                $subject = l('hrm.payroll.reject_all');
                $this->db->insert('geerang_hrm.payroll_track_comment', array('track_id' => $post->id, 'payroll_id' => $post->payroll_id, 'comment' => $comment, 'date_time' => date("Y-m-d H:i:s")));
                $result =   $this->db->update('geerang_hrm.payroll_approve_track', array('status' => 'pending', 'datetime' => date("Y-m-d H:i:s")), array('payroll_id' => $post->payroll_id, 'status_payroll' => 'approve'));
            }
        }
    }
    private function getPayrollViewInfo($is_return_data = null)
    {
        $this->load->model('hrm/Payroll_model', 'Payroll');

        $encrypt_id =  $is_return_data;
        $emp_id_search = null;

        $pvd = $this->Payroll->get_pvd();
        $field_set =  $this->Payroll->getfieldFreeze($encrypt_id);

        $field_set = array_map(function ($e) {
            return [
                '_f' => (int)$e['id'],
                '_fs' => (int)$e['payroll_setting_id'],
                'field_name' => $e['field_name'],
                'field_subname' => strlen($e['field_subname']) > 0 ? $e['field_subname'] : null,
                'field_category' => $e['field_category'],
                'payroll_type' => $e['payroll_type'],
                'typeselect' => $e['typeselect'],
                'is_cal' => [
                    'sso' => (bool)$e['cal_sso'],
                    'tax' => (bool)$e['cal_tax'],
                    'c_tax' => (int)$e['choice_tax'],
                    'static' => (int)$e['cal_static']
                ],
            ];
        }, $field_set);

        $fields = [
            'income' => array_values(array_filter($field_set, fn ($e) => $e['payroll_type'] == 'income')),
            'outcome' => array_values(array_filter($field_set, fn ($e) => $e['payroll_type'] == 'outcome')),
        ];

        $taxes = $this->Payroll->getsettingtax();

        $_info = $this->Payroll->PatrollListView($encrypt_id);

        $approve_track = $this->Payroll->getapprovetrack($encrypt_id);
        $my_turn = array_values(array_filter($approve_track, fn ($e) => $e['approve_id'] == $this->session->userdata('pd_id') && $e['status_payroll'] == 'approve'));
        $my_turn = $my_turn[0]['current_track'] != 1 || $this->session->userdata('loginby') == 'company' || $_info->status == 'approve' ? false : true;

        $in_income = [];
        $in_outcome = [];
        $discpt = $this->Payroll->get_discpt($encrypt_id);
        if ($discpt) $in_outcome[] = 'discpt';
        if ($_info->late_set == 'fix') $in_outcome[] = 'late_fix';
        if (count($pvd) > 0) $in_outcome[] = 'pvd';


        $tracks = $this->Payroll->gettrackcoment($encrypt_id);
        // echo '<pre>';print_r($approve_track);die;
        $approve_track = array_map(function ($e) use ($tracks) {
            return [
                '_i' => (int)$e['id'],
                'position' => $e['position_name'],
                'pd_id' => (int)$e['pd_id'],
                'name' => $e['first_name'] . ' ' . $e['last_name'],
                'picture' => fileExists($e['picture']),
                'status' => $e['status'],
                'type' => $e['status_payroll'] == 'create' ? 'ผู้สร้าง' : 'ผู้อนุมัติ',
                'date' => date_format(date_create($e['datetime']), 'D,d M Y H:i:s'),
                'track' => array_values(array_filter($tracks, fn ($el) => $el['track_id'] == $e['id'])),
            ];
        }, $approve_track);

        $g_approvers = $this->Payroll->getapprove();

        $info = [
            'name' => $_info->payroll_name,
            'amount' => (int)$_info->amount_employee,
            'workday' => (int)$_info->count_workday,
            'late_set' => $_info->last_set,
            'range' => [
                'start' => $_info->payroll_range_start,
                'end' => $_info->payroll_range_end,
            ],
            'date' => [
                'start' => $_info->payroll_start_date,
                'end' => $_info->payroll_end_date,
            ],
            'approvers' => array_map(function ($e) {
                $e = (object)$e;
                return [
                    'picture' => fileExists($e->picture),
                    'name' => $e->first_name . ' ' . $e->last_name,
                    'position' => $e->position_name,
                    'pd_id' => (int)$e->pd_id,
                ];
            }, $g_approvers),
            'late_set' => $_info->late_set,
            'status' => $_info->status,
            'status_txt' => $_info->status == 'reject' ? 'Reject' : ($_info->status == 'approve' ? 'Approve' : ''),
            'status_check_approve' => (int)$_info->status_check_approve,
            'is_pvd' => count($pvd) > 0
        ];
        $types = explode(',', $_info->emp_type_id);
        if (count($types) > 0) {
            $types = $this->db->query("SELECT * FROM geerang_hrm.employee_type a WHERE a.id IN ?", [$types])->result();
            $info['types'] = array_map(function ($e) {
                return [
                    'name' => $e->emp_type_name,
                    'detail' => $e->detail
                ];
            }, $types);
        }
        $branchs = explode(',', $_info->branch_id);
        if (count($branchs) > 0) {
            $types = $this->db->query("SELECT * FROM geerang_gts.branchs a WHERE a.branch_id IN ?", [$branchs])->result();
            $info['branchs'] = array_map(function ($e) {
                return [
                    'name' => $e->branch_name,
                    'no' => $e->branch_no
                ];
            }, $types);
        }
        $groups = explode(',', $_info->emp_group_id);
        if (count($groups) > 0) {
            $types = $this->db->query("SELECT * FROM geerang_hrm.employee_group a WHERE a.id IN ?", [$groups])->result();
            $info['groups'] = array_map(function ($e) {
                return [
                    'name' => $e->name,
                    'type' => $e->position_type,
                ];
            }, $types);
        }

        $emp_freeze = $this->Payroll->employeeFreeze($encrypt_id, [
            'emp_id_search' => $emp_id_search,
            'has_limit' => !$is_return_data
        ]);

        $emp_freeze_lazy = $this->Payroll->employeeFreeze($encrypt_id, [
            // 'emp_id_search' => [203, 1115, 1168],
            'has_offset' => true
        ]);
        $emp_freeze_lazy = array_map(function ($e) {
            return [
                'id' => (int)$e['id'],
                'employee_code' => $e['employee_code'],
                'pd_id' => (int)$e['pd_id']
            ];
        }, $emp_freeze_lazy);

        $emp_freeze = array_map(function ($e) use ($encrypt_id, $_info, $fields, $taxes) {
            $personal_data = $this->db->query("SELECT a.pd_id, a.start_work, a.end_work, a.status FROM geerang_hrm.personalsecret a WHERE a.pd_id = ? AND a.company_id = ?", [$e['pd_id'], $this->session->userdata('company_id')])->row();
            $e['id'] = (int)$e['id'];
            $e['pd_id'] = (int)$e['pd_id'];
            $e['start_work'] = $personal_data->start_work;
            $e['end_work'] = $personal_data->end_work;
            $e['status'] = $personal_data->status;
            $e['picture'] = fileExists($e['picture']);
            $e['n_comment'] = count($this->db->query("SELECT * FROM geerang_hrm.payroll_comment a WHERE a.freeze_id = ?", [$e['id']])->result());
            $e['_deduct'] = $this->Payroll->caldaypersalary($e['salary'], $e['c_position_set'], $e['c_position_type'], $e['c_day'], $e['c_hour']);
            $_approve = $this->db->query('SELECT a.*, b.first_name, b.last_name, b.picture FROM geerang_hrm.payroll_approve_group a LEFT JOIN geerang_gts.personaldocument b ON b.pd_id = a.approve_pd_id WHERE a.pd_id = ? AND a.payroll_id = ?', [$e['pd_id'], $encrypt_id])->result();
            $e['approve'] = array_map(function ($e) {
                return [
                    '_i' => (int)$e->approve_pd_id,
                    'name' => $e->first_name . ' ' . $e->last_name,
                    'picture' => fileExists($e->picture),
                    'status' => $e->status_approve,
                    'status_txt' => l('gts.status.' . $e->status_approve),
                    'datetime' => date_format(date_create($e->datetime), 'D,d M Y H:i:s')
                ];
            }, $_approve);

            $start_work_compare = $_info->payroll_range_start;
            if (betweenDates($personal_data->start_work, $_info->payroll_range_start, $_info->payroll_range_end) && $personal_data->status == 'active') {
                $start_work_compare = $personal_data->start_work;
            }
            $end_work_compare = $_info->payroll_range_end;
            if (betweenDates($personal_data->end_work, $_info->payroll_range_start, $_info->payroll_range_end) && $personal_data->status == 'active') {
                $end_work_compare = $personal_data->end_work;
            }

            $e['totalday'] = monthDateDiff($start_work_compare, $end_work_compare);
            $e['_sw_compare'] = $start_work_compare;
            $e['_ew_compare'] = $end_work_compare;

            $tsalary = check_salary($e['c_position_type'], $e['salary'], $e['c_day'], $e['c_hour'], $start_work_compare, $end_work_compare);

            $e['ot1'] = $e['c_ot1'] ? round(check_ottime($tsalary['salary'], $e['c_position_set'], $e['c_position_type'], timeToSeconds($e['c_ot1']), 1), 2) : '0.00';
            $e['ot15'] = $e['c_ot15'] ? round(check_ottime($tsalary['salary'], $e['c_position_set'], $e['c_position_type'], timeToSeconds($e['c_ot15']), 1), 2) : '0.00';
            $e['ot2'] = $e['c_ot2'] ? round(check_ottime($tsalary['salary'], $e['c_position_set'], $e['c_position_type'], timeToSeconds($e['c_ot2']), 1), 2) : '0.00';
            $e['ot25'] = $e['c_ot25'] ? round(check_ottime($tsalary['salary'], $e['c_position_set'], $e['c_position_type'], timeToSeconds($e['c_ot25']), 1), 2) : '0.00';
            $e['ot3'] = $e['c_ot3'] ? round(check_ottime($tsalary['salary'], $e['c_position_set'], $e['c_position_type'], timeToSeconds($e['c_ot3']), 1), 2) : '0.00';
            $e['ot_sum_money'] = $e['ot_sum_money'] ? round($e['ot_sum_money'], 2) : '0.00';
            $e['ot_sum'] = $e['ot1'] + $e['ot15'] + $e['ot2'] + $e['ot25'] + $e['ot3'] + $e['ot_sum_money'];

            $e['salary'] = $_info->cal_salary == 'no' ? 0 : round($e['salary'], 2);
            $e['c_d_allowance'] = round($e['c_d_allowance'], 2);
            $e['new_field'] = [
                'income' => $this->Payroll->excuteCondition($e, [
                    'payroll_type' => 'income',
                    'payroll_id' => $e['payroll_id'],
                    'range' => [
                        'start' => $_info->payroll_range_start,
                        'end' => $_info->payroll_range_end
                    ]
                ]),
                'outcome' => $this->Payroll->excuteCondition($e, [
                    'payroll_type' => 'outcome',
                    'payroll_id' => $e['payroll_id'],
                    'range' => [
                        'start' => $_info->payroll_range_start,
                        'end' => $_info->payroll_range_end
                    ]
                ]),
            ];
            $sum_pvd = $e['pvd_money'] ?? 0;
            $in_static_new = 0;
            $in_static = 0;
            $insso_static = 0;
            $out_static_new = 0;
            $out_static = 0;
            $outsso_static = 0;
            // echo '<pre>';print_r($e);die;
            // echo '<pre>';print_r($fields);die;
            $e['detail_pvd'] = [
                ['field_name' => l('nav_salaryb'), 'price' => number_format($e['salary'], 2), 'pvd_deduct' => number_format($e['pvd_deduct'], 0), 'price_deduct' => ($e['pvd_money'] > 0 ? number_format($e['pvd_money'], 2) : 0)]
            ];
            if ($e['pvd_field_set']) {
                foreach (explode(',', $e['pvd_field_set']) ?? [] as $key => $value) {
                    if ($e['new_field']['income'][$value] >= 0) {
                        $w_a_field = array_values(array_filter($fields['income'], fn ($e) => $e['_fs'] == $value))[0];
                        $pvd = ($e['new_field']['income'][$value] * ($e['pvd_deduct'] / 100));
                        $sum_pvd += $pvd;
                        $e['detail_pvd'][] = ['field_name' => $w_a_field['field_name'], 'price' => number_format($e['new_field']['income'][$value], 2), 'pvd_deduct' => number_format($e['pvd_deduct'], 0), 'price_deduct' => number_format($pvd, 2)];
                    }
                }
            }

            $e['new_field']['income'] = array_values($e['new_field']['income']);
            $e['new_field']['outcome'] = array_values($e['new_field']['outcome']);
            $e['input_field'] = $e['new_field'];

            $e['outcome'] = [
                'late' => 0,
                'early' => 0,
                'absent' => ($e['c_absent_money'] ? round($e['c_absent_money'], 2) : round(calabsent($tsalary['salary'], $e['c_position_set'], $e['c_position_type'], 'absent', ($e['c_absent'] ? timeToSeconds($e['c_absent']) : '0'))['absent'], 2)),
                'leave' => round(calleave($tsalary['salary'], $e['c_position_set'], $e['c_position_type'], 'leave', ($e['c_leave'] ? timeToSeconds($e['c_leave']) : '0'))['leave'], 2),
                'leave_outside' => round($e['c_leave_outside_money'], 2),
                'tax' => 0,
                'sso' => 0,
                'pvd' => (float)$sum_pvd,
            ];
            if ($_info->late_set == 'fix') {
                if (date("Y-m-d") > '2023-02-20') $e['outcome']['late'] = (float)$e['c_late_money'];
                else $e['outcome']['late'] = round(callate($tsalary['salary'], $e['c_position_set'], $e['c_position_type'], 'late', ($e['c_late'] ? timeToSeconds($e['c_late']) : '0'))['late'], 2);
            }
            if (date("Y-m-d") > '2023-02-20') $e['outcome']['early'] = (float)$e['c_early_money'];
            else $e['outcome']['early'] = round(callate($tsalary['salary'], $e['c_position_set'], $e['c_position_type'], 'early', ($e['c_early'] ? timeToSeconds($e['c_early']) : '0'))['early'], 2);

            $sum_intax = 0;
            $sum_insso = 0;
            foreach ($fields['income'] as $key => $value) {

                if ($value['is_cal']['static'] == 2) $in_static_new += $e['new_field']['income'][$key];
                else $in_static += $e['new_field']['income'][$key];

                if ($value['is_cal']['sso']) {
                    $sum_insso += $e['new_field']['income'][$key];
                    if ($value['is_cal']['static'] == 1) $insso_static += $e['new_field']['income'][$key];
                }
                if ($value['is_cal']['tax']) {
                    $sum_intax += $e['new_field']['income'][$key];
                }
            }
            $sum_outtax = 0;
            $sum_outsso = 0;
            foreach ($fields['outcome'] as $key => $value) {

                if ($value['is_cal']['static'] == 2) $out_static_new += $e['new_field']['outcome'][$key];
                else $out_static += $e['new_field']['outcome'][$key];

                if ($value['is_cal']['sso']) {
                    $sum_outsso += $e['new_field']['outcome'][$key];
                    if ($value['is_cal']['static'] == 1) $outsso_static += $e['new_field']['outcome'][$key];
                }
                if ($value['is_cal']['tax']) {
                    $sum_outtax += $e['new_field']['outcome'][$key];
                }
            }

            $discpt = $this->db->query("SELECT * FROM geerang_hrm.payroll_discp_freeze WHERE payroll_id = ? AND pd_id = ?", [$encrypt_id, $e['pd_id']])->row();
            $e['outcome']['discpt'] = 0;
            if ($discpt) {
                $e['outcome']['discpt'] = (float)$discpt->money;
                if ($discpt->detax) $sum_outtax += $discpt->money;
                if ($discpt->desso) $sum_outsso += $discpt->money;
            }

            $e['sum_intax'] = $sum_intax;
            $e['sum_insso'] = $sum_insso;
            $e['sum_outtax'] = $sum_outtax;
            $e['sum_outsso'] = $sum_outsso;

            $sum_total_sso = $e['salary'] + $sum_insso - $sum_outsso - $e['outcome']['absent'] - $e['outcome']['late'] - $e['outcome']['early'] - $e['outcome']['leave'] - $e['outcome']['leave_outside'];
            $sum_total_tax = $e['salary'] + $sum_intax + $e['ot_sum']  + $e['c_d_allowance'] - $sum_outtax  - $e['outcome']['absent'] - $e['outcome']['late'] - $e['outcome']['early'] - $e['outcome']['leave'] - $e['outcome']['leave_outside'];

            $cal_sso_emp = 0;

            if ($sum_total_sso > 15000) {
                $cal_sso_emp = 15000;
            } else {
                $cal_sso_emp = $sum_total_sso;
            }
            $sso  = ($e['c_con_emp_pay'] ? $e['c_con_emp_pay'] / 100 : 0.05);
            $max_sso = ($sso * 15000); //check max sso
            $lass_sso = ($sso * 1650); //check lass sso 
            $tax_sso = ($cal_sso_emp * $sso);

            $result_sso = checksso($e['pd_id'], date("Y-m", strtotime($_info->payroll_end_date)));
            $check_sso = 0;
            if ($result_sso) {
                if ($result_sso[$e['pd_id']]['sumsso'] < $max_sso) {

                    $sum_sso_cal = $sum_total_sso + $result_sso[$e['pd_id']]['salary'];
                    $cal_sso_emp_cal = 0;
                    if ($sum_sso_cal > 15000) {
                        $cal_sso_emp_cal = 15000;
                    } else {
                        $cal_sso_emp_cal = $sum_sso_cal;
                    }
                    $tax_sso   = (($cal_sso_emp_cal * $sso * 1) - ($result_sso[$e['pd_id']]['sumsso'] * 1));
                    $check_sso = $tax_sso;
                } else if ($result_sso[$e['pd_id']]['sumsso'] >= $max_sso) {
                    $tax_sso   = 0;
                    $check_sso = 0;
                }
            }


            $cal_sso_com = 0;
            if ($sum_total_sso > 15000) {
                $cal_sso_com = 15000;
            } else {
                $cal_sso_com = $sum_total_sso;
            }
            $sso_supot  = ($e['c_con_com_pay'] ? $e['c_con_com_pay'] / 100 : 0.05);
            $tax_sso_support = ($cal_sso_com * $sso_supot);
            if ($tax_sso_support >= 750) {
                $tax_sso_support = 750;
            } else if ($tax_sso_support <= 83) {
                if ($tax_sso_support > 1) {
                    $tax_sso_support = "83.00";
                } else {
                    $tax_sso_support = "0.00";
                }
            } else {
                $tax_sso_support = round($tax_sso_support);
            }

            $tax_total = 0;
            $tax_cal_sum = newCalulateTax($e['salary'] + ($insso_static - $outsso_static), ($in_static_new - $out_static_new), $taxes, $e['pd_id'], $e['payroll_id']);
            $e['tax_cal_sum'] = $tax_cal_sum;
            if ($tax_cal_sum > 0) {
                $tax_total = $tax_cal_sum['avg'];
            }

            if ($e['detax'] == '1') { //เช็คจากกลุ่มพนักงาน ไม่คำนวณ tax
                $tax_total = "0.00";
            }

            if ($e['desso'] == '1' || $sum_total_sso == 0) { //เช็คจากกลุ่มพนักงาน ไม่คำนวณ sso
                $tax_sso = "0.00";
            }
            //check old success payroll
            $payroll_tax = $this->db->query('SELECT * FROM geerang_hrm.payroll_tax a LEFT JOIN geerang_gts.personaldocument b ON a.idcard = b.id_card  WHERE a.payroll_id = ? AND b.pd_id = ?', [$encrypt_id, $e['pd_id']])->row();
            if ($payroll_tax) {
                $tax_total = $payroll_tax->tax;
                $tax_sso = $payroll_tax->pay_support;
            }

            $e['tax_sso_support'] = (float)$tax_sso_support;
            $e['outcome']['tax'] = (float)$tax_total;
            $e['outcome']['sso'] = (float)$tax_sso;


            $e['total_income'] = $e['salary'] + $e['ot_sum'] + array_sum($e['new_field']['income']);
            $e['total_outcome'] = $e['outcome']['tax'] + $e['outcome']['sso'] + $e['outcome']['absent'] + $e['outcome']['late'] + $e['outcome']['early'] + $e['outcome']['leave'] + $e['outcome']['leave_outside'] + array_sum($e['new_field']['outcome']) + $discpt->money + $e['outcome']['pvd'];

            $e['input_page'] = 1;
            $e['show_loading'] = false;
            return $e;
        }, $emp_freeze);


        if ($is_return_data) return [
            'approvers' => $approve_track,
            'info' => $info,
            'fields' => $fields,
            'data' => $emp_freeze,
            'lazy_data' => $emp_freeze_lazy
        ];
    }
}
