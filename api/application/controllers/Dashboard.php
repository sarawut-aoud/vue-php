<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require APPPATH . 'libraries/RestAPI.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE');
header("Access-Control-Allow-Headers: Content-Type, Authorization");

class Dashboard extends RestAPI
{
    public function __construct()
    {
        parent::__construct();
        $this->validateAuth();
    }
    public function index_get()
    {
        try {

            self::setRes(['msg' => 'Dashboard API'], 200);
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }
    public function getData_get()
    {
        try {

            $user = $this->db->query("SELECT COUNT(*) as user 
            FROM swtar_reread.personaldocument a  
            LEFT JOIN swtar_reread.api_users b ON b.pd_id = a.pd_id 
            WHERE b.user_type != 9
            ")->row('user');
            $sum = $this->db->query("SELECT SUM(total_price) as sums FROM swtar_reread.orders_payment WHERE status = 'completed'")->row('sums');
            $result = [
                'user' => (int) $user,
                'summary' => round($sum, 2),
            ];

            self::setRes($result, 200);
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }
    public function getSetting_get()
    {
        try {
            $result = $this->db->get("swtar_reread.setting_delivery")->result();
            $result = array_map(function ($e) {
                return [
                    '_i' => (int)$e->id,
                    'amount' => (int)$e->amount,
                    'price' => (float)$e->price,
                    'operater' => $e->operater
                ];
            }, $result);
            self::setRes($result, 200);
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }
    public function saveSetting_post()
    {
        try {
            $req = (object) $this->input->post();
            $this->db->insert('swtar_reread.setting_delivery', [
                'amount' => $req->amount,
                'price' => $req->price,
                'operater' => $req->operator
            ]);
            self::setRes("SUCCESS", 200);
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }
}
