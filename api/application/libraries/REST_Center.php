<?php


defined('BASEPATH') or exit('No direct script access allowed');

class REST_Center extends REST_Controller
{

    const HTTP_CODE = [
        200 => 'SUCCESS', // get or udpate
        201 => 'CREATED', // create , insert
        202 => 'APPECPTED',
        400 => 'BAD REQUEST',
        401 => 'NO AUTH',
        403 => 'INPUT ERROR',
        404 => 'NOT FOUND', // ไม่มีข้อมูลในระบบ
        500 => 'INTERNAL SERVER ERROR'
    ];

    public function __construct()
    {
        parent::__construct();
    }
   
    protected function setRes($data, $code = 200)
    {
        $RES = [
            'status' => self::HTTP_CODE[(int)$code],
            'success' => true,
            'code' => $code,
            'data' => $data ?? []
        ];

        throw new Exception(json_encode($RES));
    }

    protected function setErr($data, $code = 200)
    {

        $RES = [
            'status' => self::HTTP_CODE[(int)$code],
            'success' => false,
            'code' => $code,
            'msg' => $data ?? []
        ];

        throw new Exception(json_encode($RES));
    }

    protected function sendResponse($payload, $method = '')
    {
        $data = json_decode($payload->getMessage());
        $data->from = $method;
        $this->response($data, $data->code);
    }
}
