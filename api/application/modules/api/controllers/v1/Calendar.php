<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require APPPATH . 'libraries/RestAPI.php';
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE');

class Calendar extends RestAPI
{
    public function __construct()
    {
        parent::__construct();
        $this->validateAuth();
    }

    public function index_get()
    {
        try {
            self::setRes(['msg' => 'Calendar API'], 200);
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }
    public function getEvents_post()
    {
        try {
            $this->load->model('hrm/Calendar_model', 'Calendar');

            $payload = (object)$this->input->post();
            $valid = self::validPayload(
                $payload,
                ['com_id', 'Company ID', 'required'],
                ['emp_id', 'Employee ID', 'required'],
                ['date_start', 'Date Start', 'required|custom:xxxx-xx-xx'],
                ['date_end', 'Date End', 'required|custom:xxxx-xx-xx'],
                ['type', 'Event Type', 'optional'],
            );
            $this->save($payload);

            $datestart = date('Y-m-d', strtotime($payload->date_start));
            $dateend = date('Y-m-d', strtotime($payload->date_end));

            $resultDate = getDatesFromRange($datestart, $dateend);
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
            if (!$personal) self::setErr('Not found Employee', 200);

            $response = [];

            $post = [
                'month'         => date('m'),
                'year'          => date('Y'),
                'date_start'    => $datestart,
                'date_end'      => $dateend,
            ];
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
            ];

            // $result_att = $this->calendar->getshiftpdbymonth($post, $option);
            $events = [];
            $result_event = self::getEvent($payload);
            $result_att = self::getAttendance($payload);

            if (strtolower($payload->type) == 'event' || !$payload->type) {
                foreach ($result_event as $key => $val) {
                    $events[] = [
                        'date'          => $val->start,
                        'topic'         => $val->title,
                        'time'          => date('H:i', strtotime($val->start)) . ' - ' . date('H:i', strtotime($val->end)),
                        'time_start'    => date('H:i', strtotime($val->start)),
                        'time_end'      => date('H:i', strtotime($val->end)),
                        'status'        => null
                    ];
                }
            }

            if (strtolower($payload->type) == 'attendance' || !$payload->type) {
                foreach ($result_att as $key => $val) {
                    $events[] = [
                        'date'          => $val->start,
                        'topic'         => $val->title,
                        'time'          => date('H:i', strtotime($val->start)) . ' - ' . date('H:i', strtotime($val->end)),
                        'time_start'    => date('H:i', strtotime($val->start)),
                        'time_end'      => date('H:i', strtotime($val->end)),
                        'status'        => $val->status
                    ];
                }
            }

            foreach ($resultDate as $value) {
                $check = self::filterEvent($events, $value);
                if ($check->status) {
                    $response[] = [
                        'date'              => [
                            'datetime' => date_format(date_create($value), "Y-m-d\TH:i:s\z"),
                            'date' => date_format(date_create($value), "d/m/Y"),
                            'time' => date_format(date_create($value), "H:i:s"),
                        ],

                        'total_event'       => (int) $check->total,
                        'events'            => $check->event,
                    ];
                }
            }

            self::setRes($response, 200);
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }
    private function filterEvent($event, $date)
    {

        $result = [];
        foreach ($event as $key => $val) {
            $val = (object)$val;
            $dateEvent = date('Y-m-d', strtotime($val->date));
            if ($date == $dateEvent) {
                unset($val->date);
                $result[$dateEvent][] = $val;
            }
        }

        $_temp = [];
        foreach ($result as $key => $val) {
            $_temp = $val;
        }

        $obj = (object)[
            'total' => $result ? (int) count($result[$date]) : 0,
            'event' => $_temp,
            'status' => !empty($result[$date]) ? true : false,
        ];
        return $obj;
    }
    private function getEvent($payload)
    {
        $params = [];
        $datestart = date('Y-m-d', strtotime($payload->date_start));
        $dateend = date('Y-m-d', strtotime($payload->date_end));

        $sql = 'SELECT 
                a.id, a.detail, a.name, a.date_start, a.date_end, a.color, a.is_all_day FROM geerang_gts.calendar_event a
                                    LEFT JOIN geerang_gts.calendar_event_invited b ON
                                        b.event_id = a.id AND b.is_remove = 0 WHERE a.is_visible = 1';
        if ($payload->emp_id) {
            $sql .= ' AND (a.create_by = ? OR (b.pd_id = ? AND b.status = "accept" AND b.is_remove = 0)) 
           AND (a.date_start >= ? AND a.date_start <= ? ) AND (a.date_end >= ? AND a.date_end <= ? ) GROUP BY a.id';
            $params = [$payload->emp_id, $payload->emp_id, $datestart,  $dateend, $datestart,  $dateend];
        } else {
            $sql .= ' AND a.from_com_id = ? AND (a.date_start >= ? AND a.date_start <= ? ) AND (a.date_end >= ? AND a.date_end <= ? ) GROUP BY a.id';
            $params = [$payload->com_id,  $datestart,  $dateend, $datestart,  $dateend];
        }

        $result = $this->db->query($sql, $params)->result();

        $result = array_map(function ($e) {
            return (object)[
                'event_id' => $e->id,
                'description' => $e->detail,
                'title' => $e->name,
                'start' => $e->date_start,
                'end' => $e->date_end,
                'backgroundColor' => $e->color,
                'textColor' => '#FFF',
                'allDay' => (bool) $e->is_all_day,
                'module' => 'event_calendar'
            ];
        }, $result);

        return $result;
    }
    private function getAttendance($payload)
    {
        $company_id = $payload->com_id;
        $pd_id = $payload->emp_id;

        $datestart = date('Y-m-d', strtotime($payload->date_start));
        $dateend = date('Y-m-d', strtotime($payload->date_end));

        $result = $this->db->query(
            "SELECT *
            FROM geerang_hrm.attendances 
            WHERE com_id = ? AND emp_id = ? AND status_active = ? AND date_no BETWEEN ? AND ?",
            [$company_id, $pd_id, 'active', $datestart, $dateend]
        )->result();

        $result = array_map(function ($e) {
            return (object)[
                'event_id' => $e->a_id,
                'title' => $e->attendance_name,
                'start' => date('Y-m-d H:i:s', strtotime($e->check_in)),
                'end' => date('Y-m-d H:i:s', strtotime($e->check_out)),
                'status' => $e->status,
                'module' => 'event_attendance'
            ];
        }, $result);


        return $result;
    }
}
