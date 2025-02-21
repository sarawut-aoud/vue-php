<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require APPPATH . 'libraries/RestAPI.php';
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE');

class Documents extends RestAPI
{
    public function __construct()
    {
        parent::__construct();
    }
    public function index_get()
    {
        try {
            self::setRes(['msg' => 'Documents API'], 200);
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }
    private function formatLink($type, $payload, $id, $format)
    {
        $mix = encrypt($type) . '.';
        $mix .= encrypt($payload->com_id) . ".";
        $mix .= encrypt($payload->emp_id) . ".";
        $mix .= encrypt($id) . ".";
        $mix .= encrypt($format);
        return base_url('api/v1/documents/preview/' . ($mix));
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
    public function preview_get($path)
    {
        $this->load->model('api/Setpreview_pdf', 'pdf');
        try {
            $item = explode('.', urldecode($path));

            if (count($item) != 5) self::setErr('URL Error', 200);

            $type = checkEncryptData($item[0]);
            $com_id = checkEncryptData($item[1]);
            $emp_id = checkEncryptData($item[2]);
            $id = checkEncryptData($item[3]);
            $format = strtoupper(checkEncryptData($item[4]));
            $format = $format ? $format : 'I';

            $req = (object)[
                'pd_id' => $emp_id,
                'com_id' => $com_id,
                'type'  => $type,
            ];
            if ($type == 'cert') {
                $this->pdf->export_pdf_certificate($req,  $format, $item[3]);
            } else {
                $this->pdf->ExpordPdf($req,  $format,  $id);
            }
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
    public function getESignature_get()
    {
        $this->validateAuth();
        try {
            $payload = (object)$this->input->get();
            $valid = self::validPayload(
                $payload,
                ['com_id', 'Company ID', 'required'],
                ['emp_id', 'Employee ID', 'required'],
                ['pin', 'Pin Authen', 'required|max:6'],
            );
            if ($valid) self::setErr($valid, 403);
            $this->save($payload);


            $com_exist = $this->db->query('SELECT * FROM geerang_gts.users a WHERE a.id = ?', [$payload->com_id])->row();
            if (!$com_exist) self::setErr('Not found company', 200);

            $pd_exist = $this->db->query('SELECT * FROM geerang_gts.personaldocument a WHERE a.pd_id = ?', [$payload->emp_id])->row();
            if (!$pd_exist) self::setErr('Not found Employee', 200);

            $vertify_pin =  pass_secure_verify($payload->pin, $pd_exist->pin_code);

            if (!$vertify_pin) self::setErr('Pin Incorrect', 200);
            $result = $this->db->get_where('geerang_hrm.e_sign', ['pd_id' => $payload->emp_id, 'status' => "active"])->result();

            $result = array_map(function ($e) {
                return (object)[
                    'id'        => (int)$e->sign_id,
                    'file'      => fileExists($e->file_location, 'content'),
                ];
            }, $result);

            self::setRes($result, 200);
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }
    private function checkRole($pd_id, $com_id, $module)
    {
        $res_ = true;
        $msg = '';

        $position_id = $this->db->query(
            "SELECT * FROM geeran_gts.position_keep a
            LEFT JOIN geerang_gts.user_position b ON b.user_id = a.user_id AND a.position_id = b.position_id
            WHERE a.pd_id = ? AND a.user_id = ? AND a.default_position = 1",
            [$pd_id, $com_id]
        )->row();

        $result = $this->db->query(
            "SELECT * FROM geerang_gts.user_position_rule WHERE position_id  = ? AND user_id = ?",
            [$position_id, $com_id]
        )->row();


        if (!$result) {
            $res_ = false;
            $msg = "";
        }
        return [
            'status' =>  $res_,
            'msg' => $msg
        ];
    }
    public function getRequests_get()
    {
        $this->validateAuth();
        try {
            $payload = (object)$this->input->get();
            $valid = self::validPayload(
                $payload,
                ['com_id', 'Company ID', 'required'],
                ['emp_id', 'Employee ID', 'required'],
                ['type_id', 'Type Document ID', 'optional'],
                ['date', 'Date', 'optional']
            );

            if ($valid) self::setErr($valid, 403);
            $this->save($payload);

            $com_exist = $this->db->query('SELECT * FROM geerang_gts.users a WHERE a.id = ?', [$payload->com_id])->row();
            if (!$com_exist) self::setErr('Not found company', 200);


            if ($payload->type_id) {
                $type_exist = $this->db->query("SELECT * FROM geerang_hrm.document_menu WHERE id = ?", [$payload->type_id])->row();
                if (!$type_exist  || $type_exist->id > 9) self::setErr('Not Type Document ', 200);
            }


            $filter = "";
            if ($payload->type_id) {
                $filter .= " AND id = $payload->type_id";
            }

            $result = $this->db->query(
                "SELECT * FROM geerang_hrm.document_menu WHERE id <= 9  $filter ",
                [$payload->type_id]
            )->result();

            $payload->loginby = 'employee';
            $result = array_map(function ($e) use ($payload) {
                $type = $e->name_id;
                return (object)[
                    'id'        => (int) $e->id,
                    'name'      => $e->name_th,
                    'requsts'   => self::getdata($type, $payload),
                ];
            }, $result);

            self::setRes($result, 200);
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }
    private function getdata($type, $payload, $status = "")
    {
        $this->load->model('hrm/Document_control_model', 'doccontrol');

        switch (true) {
            case ($type == 'discript'): {

                    $post = (object)[
                        'com_id' => $payload->com_id,
                        'pd_id' => $payload->emp_id,
                        'loginby' => "employee"
                    ];
                    $result = $this->doccontrol->get_discipt($post);
                    $result = array_map(function ($e) use ($payload) {
                        return (object)[
                            'id'       => (int)$e->doc_id,
                            'link'     => self::formatLink('discript', $payload, $e->doc_id, 'i'),
                            'download'  => self::formatLink('discript', $payload, $e->doc_id, 'd'),
                            'date_request' => self::formatDate($e->create_date),
                            'status_request' => [
                                'text'          => self::convertStatus(strtolower($e->status_approve)),
                                'status'        => strtolower($e->status_approve)
                            ],
                        ];
                    }, $result);
                    break;
                }
            case ($type == 'change'): {
                    $post = (object)[
                        'com_id' => $payload->com_id,
                        'pd_id' => $payload->emp_id,
                        'loginby' => "employee"
                    ];
                    $result = $this->doccontrol->get_change($post);
                    $result = array_map(function ($e) use ($payload) {
                        return (object)[
                            'id'       => (int)$e->doc_id,
                            'link'     => self::formatLink('change', $payload, $e->doc_id, 'i'),
                            'download'  => self::formatLink('change', $payload, $e->doc_id, 'd'),
                            'date_request' => self::formatDate($e->create_date),
                            'status_request' => [
                                'text'          => self::convertStatus(strtolower($e->status_approve)),
                                'status'        => strtolower($e->status_approve)
                            ],
                        ];
                    }, $result);
                    break;
                }
            case ($type == 'payroll'): {
                    $post = (object)[
                        'com_id' => $payload->com_id,
                        'pd_id' => $payload->emp_id,
                        'loginby' => "employee"
                    ];
                    $result = $this->doccontrol->Get_DocumentSalary($post)['data'];

                    $result = array_map(function ($e) use ($payload) {
                        return (object)[
                            'id'       => (int)$e->doc_id,
                            'link'     => self::formatLink('payroll', $payload, $e->doc_id, 'i'),
                            'download'  => self::formatLink('payroll', $payload, $e->doc_id, 'd'),
                            'date_request' => self::formatDate($e->create_date),
                            'status_request' => [
                                'text'          => self::convertStatus(strtolower($e->status_approve)),
                                'status'        => strtolower($e->status_approve)
                            ],
                        ];
                    }, $result);
                    break;
                }
            case ($type == 'contact'): {
                    $post = (object)[
                        'com_id' => $payload->com_id,
                        'pd_id' => $payload->emp_id
                    ];

                    $temp = $this->doccontrol->previewContact($post)['data'];
                    $result = array_map(function ($e) use ($payload) {
                        return (object)[
                            'id'       => (int)$e->doc_id,
                            'link'     => self::formatLink('contact', $payload, $e->doc_id, 'i'),
                            'download'  => self::formatLink('contact', $payload, $e->doc_id, 'd'),
                            'date_request' => self::formatDate($e->create_at),
                            'status_request' => [
                                'text'          => self::convertStatus(strtolower($e->status_approve)),
                                'status'        => strtolower($e->status_approve)
                            ],
                        ];
                    },  $temp);
                    break;
                }
            case ($type == 'certsalary' || $type == 'certwork'): {
                    $filter = '';
                    if ($payload->date) {
                        $date = date('Y-m-d', $payload->date);
                        $filter .= " AND DATE(create_date) = '$payload->date' ";
                    }

                    if ($type == 'certsalary') {
                        $filter .= " AND show_salary =  'yes' ";
                    } else {
                        $filter .= " AND show_salary =  'no' ";
                    }

                    $temp = $this->db->query(
                        "SELECT * FROM geerang_hrm.certificate_work WHERE company_id = ?  AND requester_pd_id  = ?  $filter",
                        [$payload->com_id, $payload->emp_id]
                    )->result();

                    $result = array_map(function ($e) use ($payload) {
                        return (object)[
                            'id'       => (int)$e->certificate_work_id,
                            'link'     => self::formatLink('cert', $payload, $e->certificate_work_id, 'i'),
                            'download'  => self::formatLink('cert', $payload, $e->certificate_work_id, 'd'),
                            'date_request' => self::formatDate($e->create_date),
                            'status_request' => [
                                'text'          => self::convertStatus(strtolower($e->status)),
                                'status'        => strtolower($e->status)
                            ],
                        ];
                    },  $temp);
                    break;
                }
            case ($type == 'resign'): {
                    $post = (object)[
                        'com_id' => $payload->com_id,
                        'pd_id' => $payload->emp_id
                    ];

                    $result =    $this->doccontrol->get_docResign($post);
                    $result = array_map(function ($e) use ($payload) {
                        return (object)[
                            'id'       => (int)$e->doc_id,
                            'link'     => self::formatLink('resign', $payload, $e->doc_id, 'i'),
                            'download'  => self::formatLink('resign', $payload, $e->doc_id, 'd'),
                            'date_request' => self::formatDate($e->create_date),
                            'status_request' => [
                                'text'          => self::convertStatus(strtolower($e->status_approve)),
                                'status'        => strtolower($e->status_approve)
                            ],
                        ];
                    },  $result);

                    break;
                }
            case ($type == 'probation'): {
                    $post = (object)[
                        'com_id' => $payload->com_id,
                        'pd_id' => $payload->emp_id,
                        'loginby' => "employee"
                    ];
                    $result = $this->doccontrol->getTableProbation($post)['data'];

                    $result = array_map(function ($e) use ($payload) {
                        return (object)[
                            'id'       => (int)$e->doc_id,
                            'link'     => self::formatLink('probation', $payload, $e->doc_id, 'i'),
                            'download'  => self::formatLink('probation', $payload, $e->doc_id, 'd'),
                            'date_request' => self::formatDate($e->create_date),
                            'status_request' => [
                                'text'          => self::convertStatus(strtolower($e->status_approve)),
                                'status'        => strtolower($e->status_approve)
                            ],
                        ];
                    }, $result);
                    break;
                }
            case ($type == 'secret'): {
                    $post = (object)[
                        'com_id' => $payload->com_id,
                        'pd_id' => $payload->emp_id,
                        'loginby' => "employee"
                    ];
                    $result = $this->doccontrol->previewSecret($post)['data'];

                    $result = array_map(function ($e) use ($payload) {
                        return (object)[
                            'id'       => (int)$e->doc_id,
                            'link'     => self::formatLink('secret', $payload, $e->doc_id, 'i'),
                            'download'  => self::formatLink('secret', $payload, $e->doc_id, 'd'),
                            'date_request' => self::formatDate($e->create_date),
                            'status_request' => [
                                'text'          => self::convertStatus(strtolower($e->status_approve)),
                                'status'        => strtolower($e->status_approve)
                            ],
                        ];
                    }, $result);
                    break;
                }
        }
        return $result;
    }
    public function documentType_get()
    {
        $this->validateAuth();
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

            // $res = getAutomate($payload->com_id, 108)[0];

            $result = $this->db->query(
                "SELECT * FROM geerang_hrm.document_menu WHERE id <= 9"
            )->result();

            $result = array_map(function ($e) {

                $bool = $this->db->get_where('geerang_gts.position_rule_list', ['application_moduler_id' => $e->application_module_id])->row();
                $use = $bool->is_create;
                if (!$bool) {
                    $use = true;
                }
                return (object)[
                    'id'    => (int)$e->id,
                    'name'  => $e->name_th,
                    'use'   => (bool) $use,
                ];
            }, $result);

            self::setRes($result, 200);
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }
    public function getTemplate_get()
    {
        $this->validateAuth();
        try {
            $payload = (object)$this->input->get();
            $valid = self::validPayload(
                $payload,
                ['com_id', 'Company ID', 'required'],
                ['type_id', 'Type Document ID', 'optional'],
                ['type_name', 'Type Document Name', 'optional']
            );
            if ($valid) self::setErr($valid, 403);
            $this->save($payload);

            $com_exist = $this->db->query('SELECT * FROM geerang_gts.users a WHERE a.id = ?', [$payload->com_id])->row();
            if (!$com_exist) self::setErr('Not found company', 200);

            if ($payload->type_id) {
                $type_exist = $this->db->query("SELECT * FROM geerang_hrm.document_menu WHERE id = ?", [$payload->type_id])->row();
                if (!$type_exist  || $type_exist->id > 9) self::setErr('Not Type Document ', 200);
            }

            $filter = "";
            if ($payload->type_id) {
                $filter .= " AND id = $payload->type_id";
            }

            $result = $this->db->query(
                "SELECT * FROM geerang_hrm.document_menu WHERE id <= 9  $filter ",
                [$payload->type_id]
            )->result();

            $result = array_map(function ($e) use ($payload) {
                $template = self::getTemplate($e->name_id, $payload);
                if ($template) {
                    return (object)[
                        'id'        => (int) $e->id,
                        'name'      => $e->name_th,
                        'template'  => $template,
                    ];
                }
            }, $result);


            self::setRes(array_values(array_filter($result)), 200);
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }
    public function getTemplate($type, $payload)
    {
        $this->load->model('hrm/Document_control_model', 'doccontrol');
        switch (true) {
            case ($type == 'payroll'): {
                    $result = $this->doccontrol->DefualtFormAjustSalary(1, $payload)['data'];

                    $result = array_map(function ($e) {
                        return (object)[
                            'id' => (int)$e->id,
                            'template_name' => $e->body_name
                        ];
                    }, $result);
                    break;
                }
            case ($type == 'contact'): {
                    $result = $this->db->select('t2.template_name ,t2.id')
                        ->join('geerang_hrm.document_contact_template t2', 't2.doc_body_id = t1.id', 'left')
                        ->get_where('geerang_hrm.document_contact_body t1', array('t1.com_id' => $payload->com_id, 't2.template_show' => 'active'))->result();
                    $result = array_map(function ($e) {
                        return (object)[
                            'id' => (int)$e->id,
                            'template_name' => $e->template_name
                        ];
                    }, $result);
                    break;
                }
            case ($type == 'probation'): {
                    $result = $this->db->get_where('geerang_hrm.document_probation_body', ['com_id' => $payload->com_id, 'is_active' => 'active'])->result();

                    $result = [
                        'pass' => array_values(array_filter(array_map(function ($e) {
                            if ($e->type_probation === 'pass') {
                                return (object)[
                                    'id' => (int)$e->id,
                                    'template_name' => $e->body_name
                                ];
                            }
                        }, $result))),
                        'unpass' => array_values(array_filter(array_map(function ($e) {
                            if ($e->type_probation === 'unpass') {
                                return (object)[
                                    'id' => (int)$e->id,
                                    'template_name' => $e->body_name
                                ];
                            }
                        }, $result))),
                    ];

                    break;
                }
            default:
                $result = [];
        }

        return $result;
    }
    public function requestCert_post()
    {
        $this->load->model('hrm/Dashboard_model', 'dashboard');
        $this->load->model('hrm/Attendance_model', 'Attendance');

        $this->validateAuth();
        try {
            $payload = (object)$this->input->post();
            $valid = self::validPayload(
                $payload,
                ['com_id', 'Company ID', 'required'],
                ['emp_id', 'Employee ID', 'required'],
                ['type', 'Certificate Type', 'required|in_array:salary,work'],
                ['reason', 'Reason', 'required'],
            );
            if ($valid) self::setErr($valid, 403);
            $this->save($payload);

            $com_exist = $this->db->query('SELECT * FROM geerang_gts.users a WHERE a.id = ?', [$payload->com_id])->row();
            if (!$com_exist) self::setErr('Not found company', 200);

            $pd_exist = $this->db->query('SELECT * FROM geerang_gts.personaldocument a WHERE a.pd_id = ?', [$payload->emp_id])->row();
            if (!$pd_exist) self::setErr('Not found Employee', 200);

            $post = (object) [
                'pd_id'         => $payload->emp_id,
                'first_name'    => $pd_exist->first_name,
                'last_name'     => $pd_exist->last_name,
                'com_id'        => $payload->com_id,
                'show_salary'   => $payload->type == 'salary' ? "yes" : "no",
                'reason'        => $payload->reason,
            ];
            $result =   $this->dashboard->savecertificate_work_API($post);
            if (!$result['status']) self::setErr("Can't Request Document Certificate " . $payload->type, 200);

            if ($result['status']) {
                $this->Attendance->saveApproveTrack($result['relate_id'], 'certificate');
                $res = (object)[
                    'message' => $result['msg']
                ];

                self::setRes($res, 200);
            }
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }
    public function requestResign_post()
    {
        $this->load->model('hrm/Document_control_model', 'doccontrol');

        $this->validateAuth();
        try {
            $payload = (object)$this->input->post();
            $valid = self::validPayload(
                $payload,
                ['com_id', 'Company ID', 'required'],
                ['emp_id', 'Employee ID', 'required'],
                ['date', 'Resignation Date ', 'required'],
                ['reason', 'Reason', 'required'],
                ['esign_id', 'Electronic Signature ID', 'required'],
            );
            if ($valid) self::setErr($valid, 403);
            $this->save($payload);

            $com_exist = $this->db->query('SELECT * FROM geerang_gts.users a WHERE a.id = ?', [$payload->com_id])->row();
            if (!$com_exist) self::setErr('Not found company', 200);

            $pd_exist = $this->db->query('SELECT * FROM geerang_gts.personaldocument a WHERE a.pd_id = ?', [$payload->emp_id])->row();
            if (!$pd_exist) self::setErr('Not found Employee', 200);

            if (date('Y-m-d', strtotime($payload->date)) <= date('Y-m-d')) self::setErr("Date Resignation less than current", 200);
            $post = (object) [
                'pd_id'         => $payload->emp_id,
                'com_id'        => $payload->com_id,
                'sign_location' => encrypt($payload->esign_id),
                'detail_resign' => $payload->reason,
                'date_endresign' => $payload->date,
            ];
            $result =   $this->doccontrol->save_resign($post);
            if (!$result) self::setErr("Can't Request Document Resignation", 200);

            if ($result != '') {
                $this->db->update('geerang_hrm.document_resign', ['status_approve' => 'pending'], ['doc_id' => $result]);
                self::setRes([
                    'message' => "Successfuly"
                ], 200);
            }
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }
    private function ForCheckEmp($array, $com_id): array
    {
        $msg = '';
        $status = true;
        $error = 0;
        $error2 = [];
        if (count($array) != 4) {
            return [
                'status' => false,
                'msg'   => "There must be 4 people involved."
            ];;
        }

        foreach (array_count_values($array) as $key => $val) {
            if ($val > 1) {
                $error2[] = $key;
            }
        }
        if (count($error2) > 0) {
            $pd_id = $error2[0];
            $result = $this->db->get_where('geerang_gts.personaldocument', ['pd_id' => $pd_id])->row();
            return [
                'status' => false,
                'msg'   => [
                    'message' => "The same person cannot be duplicated.",
                    'emlpoyee'  => [
                        'emp_id' => (int)$pd_id,
                        'first_name' => $result->first_name,
                        'last_name' => $result->last_name,
                        'fullname'  => $result->first_name . ' ' . $result->last_name,
                    ],
                ],
            ];
        }


        foreach ($array as $key => $val) {
            $exist = $this->db->query(
                "SELECT * FROM geerang_gts.personaldocument a
                LEFT JOIN geerang_hrm.personalsecret b ON a.pd_id =b.pd_id 
                WHERE b.company_id = ? AND a.pd_id = ?  AND b.status = 'active'
                ",
                [$com_id, $val]
            )->row();
            if (!$exist || !$val) {
                $error++;
                $msg .= "Not Found Employee ID $val ";
            }
        }
        if ($error > 0) {
            $status = false;
        }
        return [
            'status' => $status,
            'msg'   => $msg
        ];
    }
    public function requestContact_post()
    {
        $this->load->model('hrm/Document_control_model', 'doccontrol');

        $this->validateAuth();
        try {
            $payload = (object)$this->input->post();
            $valid = self::validPayload(
                $payload,
                ['com_id', 'Company ID', 'required'],
                ['emp_id', 'Employee ID', 'required'],
                ['template_id', 'Template ID ', 'required'],
                ['type_contact', 'Type Contact', 'required|in_array:always,temporary'],
                ['is_now', 'Effective', 'required|in_array:0,1'],
                ['involved_id', 'Involved people', 'required|is_array'],
                ['datestart', 'Date Start Contact', 'optional|custom:xxxx-xx-xx'],
                ['dateend', 'Date End Contact', 'optional|custom:xxxx-xx-xx'],
            );


            if ($valid) self::setErr($valid, 403);
            $this->save($payload);

            $com_exist = $this->db->query('SELECT * FROM geerang_gts.users a WHERE a.id = ?', [$payload->com_id])->row();
            if (!$com_exist) self::setErr('Not found company', 200);

            $pd_exist = $this->db->query('SELECT * FROM geerang_gts.personaldocument a WHERE a.pd_id = ?', [$payload->emp_id])->row();
            if (!$pd_exist) self::setErr('Not found Employee', 200);

            $relate = self::ForCheckEmp($payload->involved_id, $payload->com_id);
            if (!$relate['status']) self::setErr($relate['msg'], 200);


            $exist_template =  $this->db->select('t2.template_name ,t2.id')
                ->join('geerang_hrm.document_contact_template t2', 't2.doc_body_id = t1.id', 'left')
                ->get_where('geerang_hrm.document_contact_body t1', array('t1.com_id' => $payload->com_id, 't2.id' => $payload->template_id, 't2.template_show' => 'active'))->row();

            if (!$exist_template) self::setErr('Not found Template ', 200);


            if ($payload->type_contact == 'always') {
                if (!$payload->datestart) self::setErr('Please input Date Start', 200);
                if (date('Y-m-d', strtotime($payload->datestart)) <= date('Y-m-d')) self::setErr("Date Start less than current", 200);
            }


            if ($payload->type_contact == 'temporary') {
                if (!$payload->datestart || !$payload->dateend) self::setErr('Please input Date Start or Date End', 200);
                if (date('Y-m-d', strtotime($payload->datestart)) <= date('Y-m-d')) self::setErr("Date Start less than current", 200);
                if (date('Y-m-d', strtotime($payload->datestart)) <= date('Y-m-d', strtotime($payload->dateend))) self::setErr("Date End cannot less than Date Start", 200);
            }

            $info = $this->doccontrol->information($payload->involved_id[0]);

            $position = $this->db->query(
                "SELECT * FROM geerang_gts.position_keep a
                LEFT JOIN geerang_gts.user_position b ON b.user_id = a.user_id AND a.position_id = b.position_id
                WHERE a.pd_id = ? AND a.user_id = ? LIMIT 1",
                [$payload->involved_id[0], $payload->com_id]
            )->row();

            $post =  [
                'pd_id'             => $payload->emp_id,
                'com_id'            => $payload->com_id,
                'detail_resign'     => $payload->reason,
                'template'          => $payload->template_id,
                'datenow'           => date('Y-m-d'),
                'dateTodo'          => $payload->datestart ? $payload->datestart : NULL,
                'dateToend'         => $payload->dateend ? $payload->dateend : NULL,
                'age'               => $info->age,
                'home'              => $info->home,
                'road'              => $info->road,
                'moo'               => $info->moo,
                'tumbon'            => $info->tumbon,
                'amphoe'            => $info->amphoe,
                'province'          => $info->province,
                'position'          => $position->position_id,
                'type_contact'      => $payload->type_contact,
                'contactnow'        => $payload->is_now == 1 ? 'now' : "notnow",
                'status_reminder'   => NULL,
                'check_reminder'    => NULL,
                'emp_id'            => $payload->involved_id[0],
                'emy_id'            => $payload->involved_id[1],
                'attestor_first'    => $payload->involved_id[2],
                'attestor_second'   => $payload->involved_id[3],
            ];

            $result =   $this->doccontrol->createContact($post);

            if ($result != '') {
                self::setRes([
                    'message' => "Successfuly"
                ], 200);
            }
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }

    private function getPositionEmpty($com_id, $position_id): bool
    {
        $position = $this->db->query(
            "SELECT
                                t1.position_id ,
                                t1.branch_id ,
                                t1.department_id ,
                                t1.sub_id ,
                                t1.user_id,
                                t1.position_name ,
                                t1.position_parent ,
                                t1.position_active ,
                                t2.keep_id ,
                                t2.pd_id ,
                                t3.branch_name,
                                t4.department_name ,
                                t5.sub_name 
                            FROM
                                geerang_gts.user_position t1
                            LEFT JOIN geerang_gts.position_keep t2 on t1.position_id = t2.position_id
                            LEFT JOIN geerang_gts.branchs t3 on t3.branch_id = t1.branch_id AND t3.company_id = t1.user_id
                            left join geerang_gts.department t4 on t4.department_id  = t1.department_id  and t1.user_id  = t4.company_id 
                            left join geerang_gts.sub_department t5 on t5.sub_id  = t1.sub_id  and t5.company_id  = t1.sub_id 
                            WHERE
                                t1.user_id = ?
                                AND t1.position_id = ?
                                AND t2.pd_id IS NULL",
            [$com_id, $position_id]
        )->row();
        if (!$position) return false;
        return true;
    }
    public function requestChangepayroll_post()
    {
        $this->load->model('hrm/Document_control_model', 'doccontrol');
        $this->load->model('hrm/Employee_model', 'Employees');

        $this->validateAuth();
        try {
            $payload = (object)$this->input->post();

            $valid = self::validPayload(
                $payload,
                ['com_id', 'Company ID', 'required'],
                ['emp_id', 'Employee ID', 'required'],
                ['template_id', 'Template ID ', 'required'],
                ['salary_change', 'Salary to Change', 'required|numeric'],
                ['change_position', 'Want to change position', 'required|in_array:0,1'],
                ['position_id', 'Position ID', 'optional'],
                ['reason', 'reason', 'optional'],
                ['date', 'Effective Date', 'required|custom:xxxx-xx-xx'],
                ['involved_id', 'Involved people', 'required|is_array'],
            );

            if ($valid) self::setErr($valid, 403);
            $this->save($payload);

            $com_exist = $this->db->query('SELECT * FROM geerang_gts.users a WHERE a.id = ?', [$payload->com_id])->row();
            if (!$com_exist) self::setErr('Not found company', 200);

            $pd_exist = $this->db->query('SELECT * FROM geerang_gts.personaldocument a WHERE a.pd_id = ?', [$payload->emp_id])->row();
            if (!$pd_exist) self::setErr('Not found Employee', 200);

            $relate = self::ForCheckEmp($payload->involved_id, $payload->com_id);
            if (!$relate['status']) self::setErr($relate['msg'], 200);

            if ($payload->change_position == 1) {
                if (!$payload->position_id) self::setErr('Please input Position ID', 200);
            }


            if (date('Y-m-d', strtotime($payload->datestart)) <= date('Y-m-d')) self::setErr("Date Start less than current", 200);


            $result_template = $this->doccontrol->DefualtFormAjustSalary(1, $payload)['data'];
            $exist_template = array_filter($result_template, function ($e) use ($payload) {
                $check = false;
                if ($e->id == $payload->template_id) {
                    $check = true;
                }
                return $check;
            })[0];
            if (!$exist_template) self::setErr('Please found Template', 200);

            if ($payload->position_id) {
                $exist_position = $this->getPositionEmpty($payload->com_id, $payload->position_id);
                if (!$exist_position)  self::setErr('This position already has someone.', 200);
            }


            $position = $this->db->query(
                "SELECT * FROM geerang_gts.position_keep a
                LEFT JOIN geerang_gts.user_position b ON b.user_id = a.user_id AND a.position_id = b.position_id
                WHERE a.pd_id = ? AND a.user_id = ? LIMIT 1",
                [$payload->involved_id[0], $payload->com_id]
            )->row();

            $secret = $this->db->get_where('geerang_hrm.personalsecret',  [$payload->involved_id[0], $payload->com_id])->row();

            $post =  [
                'emp_id'            => $payload->emp_id,
                'com_id'            => $payload->com_id,
                'position_id'       => $position->position_id,
                'department_id'     => $position->department_id,
                'salary_base'       => $secret->salary,
                'salary_change'     => $payload->salary_change,
                'bodycontent'       => $payload->reason,
                'dateperform'       => $payload->date,
                'template_id'       => $payload->template_id,
                'is_newposition'    => $payload->change_position == 1 ? 1 : 0,
                'new_position_id'   => $position->position_id,
                'pd_id'             => $payload->involved_id[0],
                'mng_id'            => $payload->involved_id[1],
                'empfirst_id'       => $payload->involved_id[2],
                'empsecond_id'      => $payload->involved_id[3],
            ];

            $result = $this->doccontrol->save_DocSalary($post);
            if (!$result) self::setErr("Can't Request Document Changepayroll", 200);
            if ($result != '') {
                self::setRes([
                    'message' => "Successfuly"
                ], 200);
            }
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }
    public function requestProbation_post()
    {
        $this->load->model('hrm/Document_control_model', 'doccontrol');
        $this->validateAuth();
        try {
            $payload = (object)$this->input->post();
            $valid = self::validPayload(
                $payload,
                ['com_id', 'Company ID', 'required'],
                ['emp_id', 'Employee ID', 'required'],
                ['template_id', 'Template ID ', 'required'],
                ['type_probation', 'Type Probation', 'required|in_array:pass,unpass'],
                ['date', 'Effective Date', 'required|custom:xxxx-xx-xx'],
                ['group_emp_id', 'Group Employee ID', 'required'],
                ['group_emp_id_to', 'Group Employee to Change ID', 'required'],
                ['involved_id', 'Involved people', 'required|is_array'],
            );

            if ($valid) self::setErr($valid, 403);
            $this->save($payload);

            $com_exist = $this->db->query('SELECT * FROM geerang_gts.users a WHERE a.id = ?', [$payload->com_id])->row();
            if (!$com_exist) self::setErr('Not found company', 200);

            $pd_exist = $this->db->query('SELECT * FROM geerang_gts.personaldocument a WHERE a.pd_id = ?', [$payload->emp_id])->row();
            if (!$pd_exist) self::setErr('Not found Employee', 200);

            $relate = self::ForCheckEmp($payload->involved_id, $payload->com_id);
            if (!$relate['status']) self::setErr($relate['msg'], 200);

            if (date('Y-m-d', strtotime($payload->datestart)) <= date('Y-m-d')) self::setErr("Date Start less than current", 200);

            $exist_template = $this->db->get_where('geerang_hrm.document_probation_body', ['id' => $payload->template_id, 'type_probation' => $payload->type_probation,  'com_id' => $payload->com_id, 'is_active' => 'active'])->row();
            if (!$exist_template) self::setErr('Not found Template', 200);


            $exist_group_emp = $this->db->get_where('geerang_hrm.employee_type', ['id' => $payload->group_emp_id, 'com_id' => $payload->com_id])->row();
            if (!$exist_group_emp) self::setErr('Not found Group Employee', 200);

            $exist_group_emp_to = $this->db->get_where('geerang_hrm.employee_type', ['id' => $payload->group_emp_id_to, 'com_id' => $payload->com_id])->row();
            if (!$exist_group_emp_to) self::setErr('Not found Group Employee to change', 200);



            $position = $this->db->query(
                "SELECT * FROM geerang_gts.position_keep a
                RIGHT JOIN geerang_gts.user_position b ON b.user_id = a.user_id AND b.position_id = a.position_id
                WHERE a.pd_id = ? AND a.user_id = ? LIMIT 1",
                [$payload->involved_id[0], $payload->com_id]
            )->row();

            $secret = $this->db->get_where('geerang_hrm.personalsecret', ['company_id' => $payload->com_id, 'pd_id' => $payload->involved_id[0]])->row();

            $post =  [
                'emp_id'            => $payload->emp_id,
                'com_id'            => $payload->com_id,
                'position_id'       => $position->position_id,
                'position_name'     => $position->position_name,
                'department_id'     => $position->department_id,
                'department_name'   => $this->db->get_where('geerang_gts.department', ['department_id' => $position->department_id, 'company_id' => $payload->com_id])->row('department_name'),
                'dateperform'       => $payload->date,
                'datenow'           => date('Y-m-d'),
                'date_startwork'    => $secret->start_work,
                'type_probation'    => $payload->type_probation,
                'emptype_id'        => $payload->type_probation,
                'emptype_id_change' => $payload->group_emp_id_to,
                'template_id'       => $payload->template_id,
                'pd_id'             => $payload->involved_id[0],
                'mng_id'            => $payload->involved_id[1],
                'empfirst_id'       => $payload->involved_id[2],
                'empsecond_id'      => $payload->involved_id[3],
            ];

            $result = $this->doccontrol->save_Docprobation($post);
            if (!$result) self::setErr("Can't Request Document Probation", 200);
            if ($result != '') {
                self::setRes([
                    'message' => "Successfuly"
                ], 200);
            }
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }
    public function requestSecret_post()
    {
        $this->load->model('hrm/Document_control_model', 'doccontrol');
        $this->validateAuth();
        try {
            $payload = (object)$this->input->post();
            $valid = self::validPayload(
                $payload,
                ['com_id', 'Company ID', 'required'],
                ['emp_id', 'Employee ID', 'required'],
                ['date', 'Date Start Contract', 'required|custom:xxxx-xx-xx'],
                ['involved_id', 'Involved people', 'required|is_array'],
            );

            if ($valid) self::setErr($valid, 403);
            $this->save($payload);

            $com_exist = $this->db->query('SELECT * FROM geerang_gts.users a WHERE a.id = ?', [$payload->com_id])->row();
            if (!$com_exist) self::setErr('Not found company', 200);

            $pd_exist = $this->db->query('SELECT * FROM geerang_gts.personaldocument a WHERE a.pd_id = ?', [$payload->emp_id])->row();
            if (!$pd_exist) self::setErr('Not found Employee', 200);

            $relate = self::ForCheckEmp($payload->involved_id, $payload->com_id);
            if (!$relate['status']) self::setErr($relate['msg'], 200);

            if (date('Y-m-d', strtotime($payload->date)) <= date('Y-m-d')) self::setErr("Date Start less than current", 200);

            $post =  [
                'pd_id'             => $payload->emp_id,
                'com_id'            => $payload->com_id,
                'dateTodo'          => $payload->date,
                'emp_id'            => $payload->involved_id[0],
                'emy_id'            => $payload->involved_id[1],
                'attestor_first'    => $payload->involved_id[2],
                'attestor_second'   => $payload->involved_id[3],
            ];

            $result = $this->doccontrol->createSecret($post);
            if (!$result) self::setErr("Can't Request Document Secret", 200);
            if ($result != '') {
                self::setRes([
                    'message' => "Successfuly"
                ], 200);
            }
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }
    public function getPenaltytype_get()
    {
        $this->validateAuth();

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

            $data = $this->db->query("SELECT * FROM geerang_hrm.dicsip_type WHERE `status` = 'Active' AND company_id = ? AND book_type='noti' ", [$payload->com_id])->result();
            if (!$data) {
                self::createType($payload->com_id);
                $data = $this->db->query("SELECT * FROM geerang_hrm.dicsip_type WHERE `status` = 'Active' AND company_id = ? AND book_type='noti' ", [$payload->com_id])->result();
            }


            $data = array_map(function ($e) {
                return (object)[
                    'id' => (int)$e->dicsp_id,
                    'name' => $e->dicsp_name
                ];
            }, $data);

            self::setRes($data, 200);
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }
    private function createType($com_id)
    {
        $data = array(
            array(
                'dicsp_name' => 'ตักเตือนด้วยวาจา',
                'dicsip_order' => '03',
                'company_id' => $com_id,
                'book_type' => 'noti',

            ),
            array(
                'dicsp_name' => 'ตักเตือนเป็นลายลักษณ์อักษร',
                'dicsip_order' => '04',
                'company_id' => $com_id,
                'book_type' => 'noti',
            ),
            array(
                'dicsp_name' => 'พักงาน',
                'dicsip_order' => '05',
                'company_id' => $com_id,
                'book_type' => 'noti',

            ),
            array(
                'dicsp_name' => 'ลดค่าจ้าง',
                'dicsip_order' => '06',
                'company_id' => $com_id,
                'book_type' => 'noti',
            ),
            array(
                'dicsp_name' => 'ออกโดยไม่จ่ายค่าชดเชย',
                'dicsip_order' => '07',
                'company_id' => $com_id,
                'book_type' => 'noti',
            ),
        );
        $this->db->insert_batch('geerang_hrm.dicsip_type', $data);
    }
    public function getGuiltytype_get()
    {
        $this->validateAuth();

        $this->load->model('hrm/Mistake_type_model', 'mistake_type');
        try {
            $data = $this->mistake_type->load();
            $data = array_map(function ($e) {
                $e = (object)$e;
                return (object)[
                    'id' => (int)$e->mistake_id,
                    'name' => $e->mistake_name
                ];
            }, $data);
            self::setRes($data, 200);
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }
    private function ForCheckMistake($mistake)
    {
        $result = [];
        $this->load->model('hrm/Mistake_type_model', 'mistake_type');
        $data = $this->mistake_type->load();
        foreach ($data as $key => $val) {
            $val = (object)$val;
            $result[] = [
                'mistake_id' => $val->mistake_id,
                'is_check'   => in_array($val->mistake_id, $mistake) ? 1 : 0
            ];
        }
        return $result;
    }
    private  function ForCheckdate($array, $money)
    {
        $error = [];
        $data = [];

        foreach (array_count_values($array) as $key => $val) {
            if ($val > 1) {
                $error[] = $key;
            }
        }
        if (count($error) > 1) {
            return [
                'status' => false,
                'data' =>  $data,
            ];
        }

        foreach ($array as $key => $val) {
            $data[] = [
                'date' => $val,
                'money' => round((float)$money / (count($array)), 2),
                'desso' => 0,
                'detax' => 0,
            ];
        }
        return [
            'status' => true,
            'data' => $data,
        ];
    }
    private function ForCheckMistakeError($array)
    {
        $error = [];
        $error2 = [];
        foreach ($array as $key => $val) {
            $exist =  $this->db->get_where('geerang_hrm.mistake_type', ['mistake_id' => $val])->row();
            if (!$exist) {
                $error[] = $val;
            }
        }
        $status = true;
        $msg = '';
        foreach (array_count_values($array) as $key => $val) {
            if ($val > 1) {
                $error2[] = $key;
            }
        }

        if (count($error2) > 1) {
            $status = false;
            $msg = "The same Guilty id cannot be duplicated.";
        }
        if (count($error) > 1) {
            $status = false;
            foreach ($error as $val) {
                $msg .= "Not found Guilty id $val";
            }
        }

        return ['status' => $status, 'msg' => $msg];
    }
    public function requestDiscipline_post()
    {
        $this->load->model('hrm/Document_control_model', 'doccontrol');
        $this->validateAuth();
        try {
            $payload = (object)$this->input->post();
            $valid = self::validPayload(
                $payload,
                ['com_id', 'Company ID', 'required'],
                ['emp_id', 'Employee ID', 'required'],
                ['date', 'Date Guilty', 'required|custom:xxxx-xx-xx'],
                ['time', 'Time Guilty', 'required'],
                ['penalty_id', 'Penalty Type ID', 'required'],
                ['guilty_id', 'Guilty Type ID', 'required|is_array'],
                ['draff_text', 'Draff message in Document', 'optional'],
                ['is_stopwork', 'Is Stop Work', 'optional|in_array:0,1'],
                ['datestart_stopwork', 'Date Start Stop Work', 'optional|custom:xxxx-xx-xx'],
                ['dateend_stopwork', 'Date End Stop Work', 'optional|custiom:xxxx-xx-xx'],
                ['is_stopsalary', 'Is Stop Salary', 'optional|in_array:0,1'],
                ['datestart_stopsalary', 'Date Start Stop Salary', 'optional|custom:xxxx-xx-xx'],
                ['dateend_stopsalary', 'Date End Stop Salary', 'optional|custiom:xxxx-xx-xx'],
                ['is_deduc', 'Is Deduction of Money From work', 'optional|in_array:0,1'],
                ['deduc_momey', 'Amount Money Deduction', 'optional|numeric'],
                ['date_in_month', 'Date In Month', 'optional|is_array'],
                ['is_deactive', 'Is Deactive', 'optional|in_array:0,1'],
                ['date_deactive', 'Date Deactive', 'optional|custom:xxxx-xx-xx'],
                ['is_compensation', 'Is Compensation', 'optional|in_array:0,1'],
                ['involved_id', 'Involved people', 'required|is_array'],
            );

            if ($valid) self::setErr($valid, 403);
            $this->save($payload);

            $com_exist = $this->db->query('SELECT * FROM geerang_gts.users a WHERE a.id = ?', [$payload->com_id])->row();
            if (!$com_exist) self::setErr('Not found company', 200);

            $pd_exist = $this->db->query('SELECT * FROM geerang_gts.personaldocument a WHERE a.pd_id = ?', [$payload->emp_id])->row();
            if (!$pd_exist) self::setErr('Not found Employee', 200);

            $relate = self::ForCheckEmp($payload->involved_id, $payload->com_id);
            if (!$relate['status']) self::setErr($relate['msg'], 200);

            if (date('Y-m-d', strtotime($payload->date)) >= date('Y-m-d')) self::setErr("Date Guilty must be less than current.", 200);

            if ($payload->time) {
                $regex = preg_match("/^(?:2[0-3]|[01][0-9]):[0-5][0-9]$/", $payload->time);
                if (!$regex) self::setErr("input Time isn't format like xx:xx ", 200);
            }

            $exist_penalty = $this->db->get_where('geerang_hrm.dicsip_type', ['company_id' => $payload->com_id, 'dicsp_id' => $payload->penalty_id])->row();
            if (!$exist_penalty) self::setErr("Not found Penalty ID", 200);

            $exist_mistake = self::ForCheckMistakeError($payload->guilty_id);

            if (!$exist_mistake['status']) self::setErr($exist_mistake['msg'], 200);

            if ($payload->is_stopwork == 1) {
                if (!$payload->datestart_stopwork || !$payload->dateend_stopwork) self::setErr('Please input Date Start or Date End in stopwork.', 200);
                if (date('Y-m-d', strtotime($payload->datestart_stopwork)) <= date('Y-m-d')) self::setErr("Date Start stopwork less than current", 200);
                if (date('Y-m-d', strtotime($payload->datestart_stopwork)) <= date('Y-m-d', strtotime($payload->dateend_stopwork))) self::setErr("Date End stopwork cannot less than Date Start stopwork", 200);
            }

            if ($payload->is_stopsalary == 1) {
                if (!$payload->datestart_stopsalary || !$payload->dateend_stopsalary) self::setErr('Please input Date Start or Date End in stop pay salary', 200);
                if (date('Y-m-d', strtotime($payload->datestart_stopsalary)) <= date('Y-m-d')) self::setErr("Date Start stop pay salary less than current", 200);
                if (date('Y-m-d', strtotime($payload->datestart_stopsalary)) <= date('Y-m-d', strtotime($payload->dateend_stopsalary))) self::setErr("Date End stop pay salary cannot less than Date Start stop pay salary", 200);
            }


            if ($payload->is_deduc == 1) {
                $exist_date = self::ForCheckdate($payload->date_in_month, $payload->deduc_momey);
                if (!$exist_date['status']) self::setErr('The same Date in Month cannot be duplicated.', 200);
            }

            $deactive = null;
            if ($payload->is_deactive == 1) {
                if (!$payload->date_deactive) self::setErr("Please input Date deactive ", 200);
                if (date('Y-m-d', strtotime($payload->date_deactive)) <= date('Y-m-d')) self::setErr("Date deactive less than current", 200);

                $deactive = [
                    'deactivetype' => date('Y-m-d', strtotime($payload->date_deactive)) == date('Y-m-d') ? "resignation_now" : "resignation_after",
                    'date'         => $payload->date_deactive
                ];
            }


            $post['data'] =  [
                'emp_id'            => $payload->emp_id,
                'com_id'            => $payload->com_id,
                'date_perform'      => $payload->date,
                'time_perfrom'      => $payload->time,
                'discp_type_id'     => $payload->penalty_id,
                'discp_other'       => NULL,
                'discp_daff'        => $payload->draff_text,
                'stopwork_start'    => $payload->datestart_stopwork,
                'stopwork_end'      => $payload->dateend_stopwork,
                'datepay_start'     => $payload->datestart_stopsalary,
                'datepay_end'       => $payload->dateend_stopsalary,
                'deduction_month'   => $payload->date_in_month ? count($payload->date_in_month) : NULL,
                'compensation_status' => $payload->is_deactive == 0 ? 'n' : 'y',
                'compensation_pay'  => $payload->is_deactive == 0 ? 0 : $payload->is_compensation,
                'change_pay'        => NULL,
                'change_position'   => NULL,
                'deactive'          =>   $deactive,
                'mistake'           => self::ForCheckMistake($payload->guilty_id),
                'deduction_stage'   =>   $exist_date['data'] ?   $exist_date['data'] : [],
                'approve_pd_id'     => array_map(function ($e) {
                    return ['pd_id' => $e];
                }, $payload->involved_id),
                'pd_id'         => $payload->involved_id[0]
            ];

            $result = $this->doccontrol->save_discipt($post);

            if (!$result['status']) self::setErr("Can't Request Document Discipline", 200);
            if ($result['status']) {
                self::setRes([
                    'message' => "Successfuly"
                ], 200);
            }
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }
    public function requestChangeEmployment_post()
    {
        $this->load->model('hrm/Document_control_model', 'doccontrol');
        $this->validateAuth();
        try {
            $payload = (object)$this->input->post();
            $valid = self::validPayload(
                $payload,
                ['com_id', 'Company ID', 'required'],
                ['emp_id', 'Employee ID', 'required'],
                ['date', 'Date Effective', 'required|custom:xxxx-xx-xx'],
                ['comment', 'Comment', 'required'],
                ['is_change', 'Is Change position or Salary', 'required|in_array:position,salary'],
                ['salary_after', 'Salary After Adjustment', 'optional|numeric'],
                ['is_newsalary', 'Is New Salary Or According to time', 'optional|in_array:new,time'],
                ['datestart', ' Date Start new Salary', 'optional|custom:xxxx-xx-xx'],
                ['dateend', ' Date End new Salary', 'optional|custom:xxxx-xx-xx'],
                ['position_id', 'New Position ID', 'optional'],
                ['involved_id', 'Involved people', 'required|is_array'],
            );

            if ($valid) self::setErr($valid, 403);
            $this->save($payload);

            $com_exist = $this->db->query('SELECT * FROM geerang_gts.users a WHERE a.id = ?', [$payload->com_id])->row();
            if (!$com_exist) self::setErr('Not found company', 200);

            $pd_exist = $this->db->query('SELECT * FROM geerang_gts.personaldocument a WHERE a.pd_id = ?', [$payload->emp_id])->row();
            if (!$pd_exist) self::setErr('Not found Employee', 200);

            $relate = self::ForCheckEmp($payload->involved_id, $payload->com_id);
            if (!$relate['status']) self::setErr($relate['msg'], 200);

            if (date('Y-m-d', strtotime($payload->date)) <= date('Y-m-d')) self::setErr("Date  less than current", 200);


            if ($payload->is_change === 'position') {
                if (!$payload->position_id) self::setErr('Please input Position ID', 200);
                $exist_position = $this->getPositionEmpty($payload->com_id, $payload->position_id);
                if (!$exist_position)  self::setErr('This position already has someone.', 200);
            }


            if ($payload->is_change === 'salary') {
                if (!$payload->is_newsalary) self::setErr('Is New Salary Or According to time', 200);
                if ($payload->is_newsalary === 'time') {
                    if (!$payload->datestart || !$payload->dateend) self::setErr('Please input Date Start new Salary or Date End new Salary ', 200);
                    if (date('Y-m-d', strtotime($payload->datestart)) <= date('Y-m-d')) self::setErr("Date Start new Salary less than current", 200);
                    if (date('Y-m-d', strtotime($payload->datestart)) <= date('Y-m-d', strtotime($payload->dateend))) self::setErr("Date End new Salary cannot less than Date Start", 200);
                }
            }

            $position = $this->db->query(
                "SELECT * FROM geerang_gts.position_keep a
                RIGHT JOIN geerang_gts.user_position b ON b.user_id = a.user_id AND b.position_id = a.position_id
                WHERE a.pd_id = ? AND a.user_id = ? LIMIT 1",
                [$payload->involved_id[0], $payload->com_id]
            )->row();

            $secret = $this->db->get_where('geerang_hrm.personalsecret', ['company_id' => $payload->com_id, 'pd_id' => $payload->involved_id[0]])->row();

            $newPosition = null;
            if ($payload->position_id) {
                $newPosition = $this->db->get_where('geeerang_gts.user_position', ['user_id' => $payload->com_id, 'position_id' => $payload->position_id])->row();
            }

            $post['data'] = [
                'emp_id'            => $payload->emp_id,
                'com_id'            => $payload->com_id,
                'pd_id'             => $payload->involved_id[0],
                'datenow'           => date('Y-m-d'),
                'date_perform'      => $payload->date,
                'change_type'       => $payload->is_change == 'position' ? 'position' : "payroll",
                'othercomment'      => trim($payload->commnet),
                'payold'            => $secret->salary,
                'pay_new'           => $payload->salary_after,
                'checktimepay'      => $payload->is_newsalary == 'new' ? 1 : 2,
                'pay_datestart'     => $payload->datestart,
                'pay_dateend'       => $payload->dateend,
                'position_old'      => $position->position_id,
                'department_old'    => $position->department_id,
                'subdepart_old'     => $position->sub_id,
                'position_new'      => $payload->position_id,
                'department_new'    => $newPosition->department_id,
                'subdepart_new'     => $newPosition->sub_id,
                'branch_new'        => $newPosition->branch_id,
                'position_parent'   => $newPosition->position_parent,
                'approve_pd_id'     => array_map(function ($e) {
                    return ['pd_id' => $e];
                }, $payload->involved_id),
            ];

            $result = $this->doccontrol->save_change($post);
            if (!$result['status']) self::setErr("Can't Request Document ChangeEmployment", 200);
            if ($result['status']) {
                self::setRes([
                    'message' => "Successfuly"
                ], 200);
            }
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }

    public function SendDocument_post()
    {
        $this->validateAuth();
        try {
            $payload = (object)$this->input->post();
            $valid = self::validPayload(
                $payload,
                ['com_id', 'Company ID', 'required'],
                ['emp_id', 'Employee ID', 'required'],
                ['type_id', 'Document Type ID', 'optional']
            );

            $com_exist = $this->db->query('SELECT * FROM geerang_gts.users a WHERE a.id = ?', [$payload->com_id])->row();
            if (!$com_exist) self::setErr('Not found company', 200);

            $pd_exist = $this->db->query('SELECT * FROM geerang_gts.personaldocument a WHERE a.pd_id = ?', [$payload->emp_id])->row();
            if (!$pd_exist) self::setErr('Not found Employee', 200);
            if ($payload->type_id) {
                $type_exist = $this->db->query("SELECT * FROM geerang_hrm.document_menu WHERE id = ?", [$payload->type_id])->row();
                if (!$type_exist  || $type_exist->id > 9) self::setErr('Not Type Document ', 200);
            }



            if ($valid) self::setErr($valid, 403);
            $this->save($payload);
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }
}
