<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require APPPATH . 'libraries/RestAPI.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE');
header("Access-Control-Allow-Headers: Content-Type, Authorization");

class Category extends RestAPI
{
    public function __construct()
    {
        parent::__construct();

        // $this->validateAuth();
    }
    public function index_get()
    {
        try {
            self::setRes(['msg' => 'Category API'], 200);
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }
    public function getList_get()
    {
        try {

            $result = $this->db->query("SELECT * FROM db_reread.category WHERE is_active = 1 ")->result();

            $result = array_map(function ($e) {
                return [
                    "_i" => (int) $e->id,
                    'name' => $e->cate_name,
                    'no' => $e->cate_no,
                ];
            }, $result);
            self::setRes($result, 200);
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }
}
