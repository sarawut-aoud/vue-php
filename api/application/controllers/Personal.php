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
                    'first_name' => $exit->first_name,
                    'last_name' => $exit->last_name,
                ],
                'avatar' => $exit->first_name && $exit->last_name ? Avatar($exit->first_name, $exit->last_name) : Avatar($exit->first_name, ''),
                'gender' => (int) $exit->gender,
                'title' => (int)$exit->title,
                'info' => [
                    'phone' => $exit->phone ?? null,
                    'birthday' => $exit->birthday ? date('Y-m-d', strtotime($exit->birthday)) : null,
                    'email' => $exit->email ?? null,
                    'id_card' => $exit->id_card,
                ]
            ];

            self::setRes($result, 200);
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }
    public function getGender_get()
    {
        try {
            $result = $this->db->query("SELECT * FROM db_reread.gender")->result();
            $result = array_map(function ($e) {
                return [
                    '_i' => (int) $e->gender_id,
                    'name' => $e->gender_name,
                ];
            }, $result);
            self::setRes($result, 200);
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }
    public function updateInfo_post()
    {
        try {
            $req = (object) $this->input->post();

            $exit = $this->db->query("SELECT * FROM db_reread.personaldocument WHERE pd_id=?", [$req->pd_id])->row();
            if (!$exit) self::setErr("NOT FOUND User", 404);

            $this->db->update('db_reread.personaldocument', [
                'first_name' => $req->first_name ?? null,
                'last_name' => $req->last_name ?? null,
                'id_card' => $req->id_card ?? null,
                'title' => $req->title ?? null,
                'gender' => $req->gender ?? null,
                'nickname' => $req->nickname ?? null,
                'email' => $req->email ?? null,
                'phone' => $req->phone ?? null,
            ], ['pd_id' => $req->pd_id]);

            self::setRes(['msg' => "UPDATED SUCCESS"], 200);
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }
}
