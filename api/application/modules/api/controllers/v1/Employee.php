<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require APPPATH . 'libraries/RestAPI.php';
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE');

class Employee extends RestAPI
{
    const COM = [
        354,
        389,
    ];
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
            self::setRes(['msg' => 'Employee API'], 200);
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }

    public function getEmployeeByKeyword_post()
    {
        try {
            $payload = (object)$this->input->post();

            $valid = self::validPayload(
                $payload,
                ['type', 'Contact Type', 'optional'],
                ['name', 'Keyword of Employee Name', 'required'],
                ['com_id', 'Company ID', 'required'],
                ['dept', 'Department Name', 'optional'],
            );
            if ($valid) self::setErr($valid, 403);
            $this->save($payload);

            $com_exist = $this->db->query('SELECT * FROM geerang_gts.users a WHERE a.id = ?', [$payload->com_id])->row();
            if (!$com_exist) self::setErr('Not found company', 200);

            $filter = '';
            if ($payload->dept) {
                $deptname = strtolower($payload->dept);
                $filter .= " AND LOWER(e.department_name) LIKE '%{$deptname}%'";
            }
            $keyword = '"%' . $payload->name . '%"';


            $personal = $this->db->query("SELECT
                                                a.employee_code, 
                                                a.company_id,
                                                b.*
                                            FROM
                                                geerang_hrm.personalsecret a
                                            LEFT JOIN geerang_gts.personaldocument b ON
                                                b.pd_id = a.pd_id
                                            LEFT JOIN geerang_gts.position_keep c ON c.pd_id = a.pd_id AND c.user_id = a.company_id
                                            LEFT JOIN geerang_gts.user_position d ON d.position_id = c.position_id AND d.user_id = c.user_id
                                            LEFT JOIN geerang_gts.department e ON e.department_id = d.department_id AND e.company_id = c.user_id
                                            WHERE
                                                a.company_id = ? AND a.status = 'active' 
                                                AND (
                                                    b.first_name LIKE $keyword 
                                                    OR b.last_name LIKE $keyword 
                                                    OR b.nickname LIKE $keyword 
                                                    OR CONCAT(b.first_name,' ',b.last_name) LIKE $keyword 
                                                    ) 
                                                    $filter 
                                                    GROUP BY b.pd_id
                                                    ", [$payload->com_id])->result();

            $personal = array_map(function ($e) use ($payload) {

                return (object)[
                    'employee_code'     => $e->employee_code,
                    'pd_id'             => (int)$e->pd_id,
                    'title'             => (int)$e->title,
                    'firstname'         => $e->first_name,
                    'lastname'          => $e->last_name,
                    'fullname'          => $e->first_name . ' ' . $e->last_name,
                    'nickname'          => $e->nickname,
                    'picture'           => fileExists($e->picture),
                    'department'    => array_map(function ($ee) {
                        return (object)[
                            'depart_id' => (int)$ee->department_id,
                            'depart_name' => $ee->department_name,
                        ];
                    }, self::getDepartment($e)),
                    'contact' => [
                        'type'          => $payload->type,
                        'data'          => self::getContactData($e, $payload->type),
                    ],
                    'automate' => getAutomate($payload->com_id)
                ];
            }, $personal);

            self::setRes($personal, 200);
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }

    private function getContactData($data, $type)
    {
        $settype = explode(',', $type);

        if (count($settype) == 0 || !$type) {
            return [
                'email'             => $data->email ? $data->email : "",
                'tel'               => $data->phone_number ? $data->phone_number : "",
                'line'              => $data->lineid ? $data->lineid : "",
                'wechat'            => $data->wechat ? $data->wechat : "",
                'zoom'              => $data->zoom ? $data->zoom : "",
                'skype'             => $data->skype ? $data->skype : "",
                'facebook'          => $data->facebook ? $data->facebook : "",
            ];
        } else {
            $temp = [];
            foreach ($settype as $key => $value) {
                switch ($value) {
                    case 'email':
                        $temp['email'] = $data->email ? $data->email : "";
                        break;
                    case 'tel':
                        $temp['tel'] = $data->phone_number ? $data->phone_number : "";
                        break;
                    case 'line':
                        $temp['line'] = $data->lineid ? $data->lineid : "";
                        break;
                    case 'wechat':
                        $temp['wechat'] = $data->wechat ? $data->wechat : "";
                        break;
                    case 'zoom':
                        $temp['zoom'] = $data->zoom ? $data->zoom : "";
                        break;
                    case 'skype':
                        $temp['skype'] = $data->skype ? $data->skype : "";
                        break;
                    default:
                        break;
                }
            }
            return $temp;
        }
    }
    public function getEmployeeAll_get()
    {
        $response = true;
        try {
            $payload = (object)$this->input->get();

            // if (!in_array($payload->com_id, self::COM))  self::setErr("Unavaliable wait a moment.", 500);

            $valid = self::validPayload(
                $payload,
                ['com_id', 'Company ID', 'required'],
            );
            $this->save($payload);

            if ($valid) self::setErr($valid, 403);

            $com_exist = $this->db->query('SELECT * FROM geerang_gts.users a WHERE a.id = ?', [$payload->com_id])->row();

            if (!$com_exist) self::setErr('Not found company', 200);

            $personalAll = $this->db->query(
                "SELECT t1.employee_code,
                    t1.company_id,
                    t2.*
                    FROM geerang_hrm.personalsecret t1
                    LEFT JOIN geerang_gts.personaldocument t2 ON t2.pd_id = t1.pd_id
                    WHERE  t1.company_id = ? AND t1.status = 'active'  
                    ORDER BY  t1.employee_code ASC
                ",
                [$payload->com_id]
            )->result();

            $personalAll = array_map(function ($e) use ($payload) {

                return (object)[
                    'employee_code' => $e->employee_code,
                    'pd_id'         => (int) $e->pd_id,
                    'title'         => (int) $e->title,
                    'firstname'     => $e->first_name,
                    'lastname'      => $e->last_name,
                    'fullname'      => $e->first_name . ' ' . $e->last_name,
                    'picture'       => fileExists($e->picture),
                    'department'    => array_map(function ($ee) {
                        return (object)[
                            'depart_id' => (int)$ee->department_id,
                            'depart_name' => $ee->department_name,
                        ];
                    }, self::getDepartment($e)),
                    'automate' => getAutomate($payload->com_id)
                ];
            }, $personalAll);
            self::setRes($personalAll, 200);
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }
    public function getEmployeeByID_get()
    {


        try {
            $payload = (object)$this->input->get();
            $valid = self::validPayload(
                $payload,
                ['com_id', 'Company ID', 'required'],
                ['emp_id', 'Employee ID', 'required'],
            );
            if ($valid) self::setErr($valid, 403);
            $this->save($payload);

            $com_exist = $this->db->query('SELECT * FROM geerang_gts.users a WHERE a.id = ?', [$payload->com_id])->row();
            if (!$com_exist) self::setErr('Not found company', 200);

            $personal = $this->db->query(
                "SELECT t1.employee_code,
                    t1.start_work,
                    t1.company_id,
                   
                    t2.*
                  FROM geerang_hrm.personalsecret t1
                  LEFT JOIN geerang_gts.personaldocument t2 ON t2.pd_id = t1.pd_id
                  WHERE  t1.company_id = ? 
                  AND t1.status = 'active'  AND t1.pd_id = ?
                ",
                [$payload->com_id, $payload->emp_id]
            )->row();


            $start_work = date_create($personal->start_work);
            $date_now = date_create(date('Y-m-d'));
            $diff = date_diff($start_work, $date_now);



            $result = (object)[
                'employee_code' => $personal->employee_code,
                'pd_id'         => (int) $personal->pd_id,
                'title'         => (int) $personal->title,
                'title_name'    => self::TITLE_NAME[$personal->title],
                'gender'        => self::GENDER[$personal->title],
                'firstname'     => $personal->first_name,
                'lastname'      => $personal->last_name,
                'fullname'      => $personal->first_name . ' ' .  $personal->last_name,
                'picture'       => fileExists($personal->picture),
                'department'    => array_map(function ($ee) {
                    return (object)[
                        'depart_id' => (int)$ee->department_id,
                        'depart_name' => $ee->department_name,
                    ];
                }, self::getDepartment($personal)),
                'start_work'    => [
                    'datetime' => date_format($start_work, "Y-m-d\TH:i:s\z"),
                    'date' => date_format($start_work, "d/m/Y"),
                    'time' => date_format($start_work, "H:i:s"),
                ],
                'work_day'      => [
                    'year'  => (int) $diff->format('%Y'),
                    'month' => (int) $diff->format("%m"),
                    'day'   => (int) $diff->format("%a") - (int)$diff->format('%Y') * 365,
                    'total_day' => (int)$diff->format("%a"),
                ]

            ];
            self::setRes($result, 200);
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }
    private function getDepartment($e)
    {
        $result = $this->db->query(
            "SELECT 
            c.department_id,
            c.department_name
            FROM geerang_gts.position_keep a 
            LEFT JOIN geerang_gts.user_position b ON b.position_id =a.position_id AND a.user_id = b.user_id
            LEFT JOIN geerang_gts.department c ON c.department_id = b.department_id AND b.user_id = c.company_id
            WHERE a.pd_id = ? AND a.user_id = ?  AND  c.department_id IS NOT NULL
            ",
            [$e->pd_id, $e->company_id]
        )->result();
        return $result;
    }
}
