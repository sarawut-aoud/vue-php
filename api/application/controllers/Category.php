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

            $result = $this->db->query("SELECT * FROM swtar_reread.category WHERE is_active = 1 ")->result();

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
    public function remove_post($id)
    {
        try {

            if (!$id) self::setRes("INPUT ERROR", 401);
            $result = $this->db->query("SELECT * FROM swtar_reread.category WHERE is_active = 1 AND id = ?", [$id])->row();
            if (!$result)  self::setRes("NOT FOUND", 404);
            $this->db->update('swtar_reread.category', ['is_active' => 0], ['id' => $id]);
            self::setRes("SUCCESS", 200);
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }
    public function update_post()
    {
        try {
            $req = (object) $this->input->post();
            $exit = $this->db->query("SELECT * FROM swtar_reread.category WHERE id = ? AND is_active =1 ", [$req->id])->row();
            if (!$exit) self::setRes("NOT FOUND", 404);

            $exit = $this->db->query("SELECT * FROM swtar_reread.category WHERE cate_name = ? AND is_active =1 AND id != ?", [$req->name, $req->id])->row();
            if ($exit) self::setRes("Category Name Not Dupilcate", 404);
            $this->db->update('swtar_reread.category', [
                'cate_name' => $req->name,
                'cate_no' => $req->no
            ], ['id' => $req->id]);
            self::setRes("SUCCESS", 200);
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }
    public function create_post()
    {
        try {
            $req = (object) $this->input->post();

            $exit = $this->db->query("SELECT * FROM swtar_reread.category WHERE cate_name = ? AND is_active =1 ", [$req->name])->row();
            if ($exit) self::setRes("Category Name Not Dupilcate", 404);
            $this->db->insert('swtar_reread.category', [
                'cate_name' => $req->name,
                'cate_no' => $req->no
            ]);
            self::setRes("SUCCESS", 200);
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }
    public function getListById_get($id)
    {
        try {

            if (!$id) self::setRes("INPUT ERROR", 401);
            $result = $this->db->query("SELECT * FROM swtar_reread.category WHERE is_active = 1 AND id = ?", [$id])->row();
            if (!$result)  self::setRes("NOT FOUND", 404);
            $data =
                [
                    "_i" => (int) $result->id,
                    'name' => $result->cate_name,
                    'no' => $result->cate_no,
                ];
            self::setRes($data, 200);
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }
}
