<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require APPPATH . 'libraries/RestAPI.php';
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE');

class Company extends RestAPI
{

    public function __construct()
    {
        parent::__construct();
        $this->validateAuth();
    }

    public function index_get()
    {
        try {
            self::setRes(['msg' => 'Company API'], 200);
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }

    public function getBranchs_get()
    {
        try {
            $payload = (object)$this->input->get();
            $valid = self::validPayload(
                $payload,
                ['com_id', 'Company ID', 'required'],
                ['branch_id', 'Branch ID', 'optional'],
                ['branch_name', 'Branch Name', 'optional']
            );
            if ($valid) self::setErr($valid, 403);
            $this->save($payload);

            $com_exist = $this->db->query('SELECT * FROM geerang_gts.users a WHERE a.id = ?', [$payload->com_id])->row();
            if (!$com_exist) self::setErr('Not found company', 200);

            $filter = '';
            if ($payload->branch_id) {
                $filter .= " AND branch_id IN ($payload->branch_id)";
            }
            if ($payload->branch_name) {
                $filter .= " AND branch_name LIKE '%$payload->branch_id%'";
            }

            $result = $this->db->query(
                "SELECT * 
                FROM geerang_gts.branchs
                WHERE company_id = ?
                $filter
                ",
                [$payload->com_id]
            )->result();

            if (!$result) self::setErr('Not found Branchs data', 200);

            $result = array_map(function ($e) {
                return (object)[
                    'branch_id'       => (int)$e->branch_id,
                    'branch_name'     => $e->branch_name
                ];
            }, $result);

            self::setRes($result, 200);
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }
    public function getDepartments_get()
    {
        try {
            $payload = (object)$this->input->get();
            $valid = self::validPayload(
                $payload,
                ['com_id', 'Company ID', 'required'],
                ['depart_id', 'Department ID', 'optional'],
                ['depart_name', 'Department Name', 'optional'],
                ['branch_id', 'Branch ID', 'optional'],
            );
            if ($valid) self::setErr($valid, 403);
            $this->save($payload);

            $com_exist = $this->db->query('SELECT * FROM geerang_gts.users a WHERE a.id = ?', [$payload->com_id])->row();
            if (!$com_exist) self::setErr('Not found company', 200);


            $filter = "";
            if ($payload->depart_id) {
                $filter .= " AND department_id IN ({$payload->depart_id})";
            }

            if ($payload->depart_name) {
                $filter .= " AND department_name LIKE '%$payload->depart_id%' ";
            }

            if ($payload->branch_id) {
                $filter .= " AND branch_id IN ({$payload->branch_id}) ";
            }

            $result = $this->db->query(
                "SELECT *
                FROM geerang_gts.department
                WHERE company_id = ?
                $filter
                ",
                [$payload->com_id]
            )->result();


            if (!$result) self::setErr('Not found Departments data', 200);

            $result = array_map(function ($e) {
                return (object)[
                    'department_id'       => (int)$e->department_id,
                    'department_name'     => $e->department_name,
                    'branch_id'           => (int)$e->branch_id
                ];
            }, $result);

            self::setRes($result, 200);
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }
    public function getSubdepartments_get()
    {
        try {
            $payload = (object)$this->input->get();
            $valid = self::validPayload(
                $payload,
                ['com_id', 'Company ID', 'required'],
                ['sub_id', 'Subdepartment ID', 'optional'],
                ['sub_name', 'Subdepartment Name', 'optional'],
                ['depart_id', 'Department ID', 'optional'],
            );
            if ($valid) self::setErr($valid, 403);
            $this->save($payload);

            $com_exist = $this->db->query('SELECT * FROM geerang_gts.users a WHERE a.id = ?', [$payload->com_id])->row();
            if (!$com_exist) self::setErr('Not found company', 200);


            $filter = "";
            if ($payload->sub_id) {
                $filter .= " AND sub_id IN ($payload->sub_id)";
            }
            if ($payload->depart_id) {
                $filter .= " AND department_id IN ($payload->depart_id)";
            }
            if ($payload->sub_name) {
                $filter .= " AND sub_name LIKE '%$payload->sub_name%' ";
            }


            $result = $this->db->query(
                "SELECT * 
                FROM geerang_gts.sub_department
                WHERE company_id = ?
                $filter
                ",
                [$payload->com_id]
            )->result();

            if (!$result) self::setErr('Not found Sub-Departments data', 200);

            $result = array_map(function ($e) {
                return (object)[
                    'sub_id'       => (int)$e->sub_id,
                    'sub_name'     => $e->sub_name,
                    'department_id' => (int)$e->department_id
                ];
            }, $result);

            self::setRes($result, 200);
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }
    public function getPositions_get()
    {
        try {
            $payload = (object)$this->input->get();
            $valid = self::validPayload(
                $payload,
                ['com_id', 'Company ID', 'required'],
                ['position_id', 'Position ID', 'optional'],
                ['position_name', 'Position Name', 'optional'],
                ['is_show', 'Is Show Position', 'optional|in_array:all,empty,unempty'],
            );
            if ($valid) self::setErr($valid, 403);
            $this->save($payload);

            $com_exist = $this->db->query('SELECT * FROM geerang_gts.users a WHERE a.id = ?', [$payload->com_id])->row();
            if (!$com_exist) self::setErr('Not found company', 200);

            $exist_data = $this->db->get_where('geerang_gts.user_position', ['user_id' => $payload->com_id])->result();
            if (!$exist_data) self::setErr('Not found Positions data', 200);


            $filter = "";
            if ($payload->position_id) {
                $filter .= " AND t1.position_id IN ($payload->position_id) ";
            }
            if ($payload->position_name) {
                $filter .= " AND t1.position_name LIKE '%$payload->position_name%' ";
            }
            if ($payload->is_show === "empty") {
                $filter .= "  AND t2.pd_id IS NULL";
            }
            if ($payload->is_show === "unempty") {
                $filter .= "  AND t2.pd_id IS NOT NULL";
            }

            $result = $this->db->query(
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
                LEFT JOIN geerang_gts.department t4 on t4.department_id  = t1.department_id  and t1.user_id  = t4.company_id 
                LEFT JOIN geerang_gts.sub_department t5 on t5.sub_id  = t1.sub_id  and t5.company_id  = t1.sub_id 
                WHERE
                    t1.user_id = ?
                    $filter 
                 ",
                [$payload->com_id]
            )->result();


            $result = array_map(function ($e) {
                $exist_position =   self::ExistPosition($e->user_id);
                $empty = true;
                if ($e->pd_id || in_array($e->position_id, $exist_position)) {
                    $empty = false;
                }
                return (object)[
                    'position_id'       => (int)$e->position_id,
                    'position_name'     => $e->position_name,
                    'branch_id'         => (int)$e->branch_id,
                    'department_id'     => (int)$e->department_id,
                    'sub_id'            => (int)$e->sub_id,
                    'isEmpty'           => (bool)   $empty
                ];
            }, $result);


            self::setRes($result, 200);
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }
    public function getGroupEmployees_get()
    {
        try {
            $payload = (object)$this->input->get();
            $valid = self::validPayload(
                $payload,
                ['com_id', 'Company ID', 'required'],
                ['group_id', 'Group Employee ID', 'optional'],
                ['group_name', 'Group Employee name', 'optional'],
            );
            if ($valid) self::setErr($valid, 403);
            $this->save($payload);

            $com_exist = $this->db->query('SELECT * FROM geerang_gts.users a WHERE a.id = ?', [$payload->com_id])->row();
            if (!$com_exist) self::setErr('Not found company', 200);


            $filter = "";
            if ($payload->group_id) {
                $filter .= " AND id  IN($payload->group_id) ";
            }

            if ($payload->group_name) {
                $filter .= " AND emp_type_name LIKE '%$payload->group_name%' ";
            }

            $result = $this->db->query(
                "SELECT *
                FROM geerang_hrm.employee_type
                WHERE com_id = ?
                $filter
                ",
                [$payload->com_id]
            )->result();

            if (!$result) self::setErr('Not found GroupEmployee data', 200);

            $result = array_map(function ($e) {
                return (object)[
                    'group_id'          => (int)$e->id,
                    'group_name'        => $e->emp_type_name,
                ];
            }, $result);

            self::setRes($result, 200);
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }

    public function getTypeEmployees_get()
    {
        try {
            $payload = (object)$this->input->get();
            $valid = self::validPayload(
                $payload,
                ['com_id', 'Company ID', 'required'],
                ['type_id', 'Type Employee ID', 'optional'],
                ['type_name', 'Type Employee Name', 'optional'],
            );
            if ($valid) self::setErr($valid, 403);
            $this->save($payload);

            $com_exist = $this->db->query('SELECT * FROM geerang_gts.users a WHERE a.id = ?', [$payload->com_id])->row();
            if (!$com_exist) self::setErr('Not found company', 200);


            $filter = "";
            if ($payload->type_id) {
                $filter .= " AND id IN ($payload->type_id) ";
            }
            if ($payload->type_name) {
                $filter .= " AND name LIKE '%$payload->type_name%' ";
            }

            $result = $this->db->query(
                "SELECT *
                FROM geerang_hrm.employee_group
                WHERE com_id = ? 
                $filter
                ",
                [$payload->com_id]
            )->result();


            if (!$result) self::setErr('Not found GroupEmployee data', 200);

            $result = array_map(function ($e) {
                return (object)[
                    'type_id'          => (int)$e->id,
                    'type_name'        => $e->name,
                ];
            }, $result);

            self::setRes($result, 200);
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }
    public function getPositionEmpty_get()
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
                LEFT JOIN geerang_gts.department t4 on t4.department_id  = t1.department_id  and t1.user_id  = t4.company_id 
                LEFT JOIN geerang_gts.sub_department t5 on t5.sub_id  = t1.sub_id  and t5.company_id  = t1.sub_id 
                WHERE
                    t1.user_id = ?
                    AND t2.pd_id IS NULL",
                [$payload->com_id]
            )->result();


            $result = array_map(function ($e) {

                return (object)[
                    'position_id'   => (int) $e->position_id,
                    'position_name' => $e->position_name,
                    'branch_id'     => (int)$e->branch_id,
                    'branch_name'   => $e->branch_name,
                    'depart_id'     => (int)$e->department_id,
                    'depart_name'   => $e->department_name,
                    'sub_id'        => (int)$e->sub_id,
                    'sub_name'      => $e->sub_name ? $e->sub_name : ""
                ];
            }, $result);

            self::setRes($result, 200);
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }
    private function ExistPosition($com_id)
    {
        $result = $this->db->get_where('geerang_hrm.document_salary', ['com_id' => $com_id, 'is_change' => "0"])->result();
        $result2 = $this->db->get_where('geerang_hrm.document_change', ['com_id' => $com_id, 'status_approve !=' => "approve"])->result();

        $obj = [];
        foreach ($result as $key => $value) {
            if ($value->new_position_id) {
                $obj[] = $value->new_position_id;
            }
        }
        foreach ($result2 as $key => $value) {
            if ($value->position_id) {
                $obj[] = $value->position_id;
            }
        }
        return $obj;
    }
}
