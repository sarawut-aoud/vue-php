<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require APPPATH . 'libraries/RestAPI.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE');
header("Access-Control-Allow-Headers: Content-Type, Authorization");

class Personal extends RestAPI
{
    public function __construct()
    {
        parent::__construct();

        $this->validateAuth();
    }
    public function index_get()
    {
        try {
            self::setRes(['msg' => 'Personal API'], 200);
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }
    public function getInfo_get()
    {
        try {
            $req = (object)$this->input->get();

            if (!$req->uid) self::setErr("Require Uid", 401);

            $exit = $this->db->query("SELECT * FROM db_reread.personaldocument WHERE pd_id=?", [$req->uid])->row();
            if (!$exit) self::setErr("NOT FOUND User", 404);
            $result = [
                '_i' => (int) $exit->pd_id,
                'name' => [
                    'fullname' => $exit->first_name . ' ' . $exit->last_name,
                    'nickname' => $exit->nickname,
                ],
                'avatar' => $exit->first_name && $exit->last_name ? Avatar($exit->first_name, $exit->last_name) : Avatar($exit->first_name, ''),
                'gender' => null,
                'info' => [
                    'phone' => $exit->phone ?? null,
                    'birthday' => $exit->birthday ? date('Y-m-d', strtotime($exit->birthday)) : null,
                    'email' => $exit->email ?? null,
                    'phone' => $exit->phone ?? null,
                ]
            ];

            self::setRes($result, 200);
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }
}
