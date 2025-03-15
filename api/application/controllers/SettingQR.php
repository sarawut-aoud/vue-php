<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require APPPATH . 'libraries/RestAPI.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE');
header("Access-Control-Allow-Headers: Content-Type, Authorization");

class SettingQR extends RestAPI
{
    public function __construct()
    {
        parent::__construct();

        $this->validateAuth();
        $this->load->model('Upload_model', 'Upload');
    }
    public function index_get()
    {
        try {

            self::setRes(['msg' => 'Orders API'], 200);
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }
    public function getSetting_get()
    {
        try {
            $e = $this->db->query("SELECT * FROM swtar_reread.setting_qrcode WHERE is_active = 1 AND setdefault = 1")->row();
            $result = [
                "_i" => (int)$e->id,
                'path' => $e->picture ? '/api' . substr($e->picture, 1) : null,
                'name' => $e->qr_name ?? null,
                'is_default' => (bool) $e->setdefault,
            ];
            self::setRes($result, 200);
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }
    public function getSettingLists_get()
    {
        try {
            $result = $this->db->query("SELECT * FROM swtar_reread.setting_qrcode WHERE is_active = 1")->result();
            $result = array_map(function ($e) {
                return [
                    "_i" => (int)$e->id,
                    'path' => $e->picture ? '/api' . substr($e->picture, 1) : null,
                    'name' => $e->qr_name ?? null,
                    'is_default' => (bool) $e->setdefault,
                ];
            }, $result);
            self::setRes($result, 200);
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }
    public function getSettingById_get($id)
    {
        try {
            if (!$id) self::setErr('INPUT ERROR', 403);
            $e = $this->db->query("SELECT * FROM swtar_reread.setting_qrcode WHERE is_active = 1 AND id = ?", [$id])->row();
            if (!$e) self::setErr('NOT FOUND DATA', 404);
            $result = [
                "_i" => (int)$e->id,
                'path' => $e->picture ? '/api' . substr($e->picture, 1) : null,
                'name' => $e->qr_name ?? null,
                'is_default' => (bool) $e->setdefault,
            ];
            self::setRes($result, 200);
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }
    public function create_post()
    {
        try {
            $req = (object) $this->input->post();
            $files = $this->Upload->upload_images('qrcode');
            $_file = null;
            if (count($files) > 0) $_file = $files[0];
            $result = $this->db->query("SELECT * FROM swtar_reread.setting_qrcode WHERE is_active = 1")->result();
            $default = 0;
            if (count($result) == 0) $default = 1;
            $this->db->insert('swtar_reread.setting_qrcode', [
                'qr_name' => $req->name,
                'picture' => $_file,
                'is_active' => 1,
                'setdefault' => $default,
            ]);
            self::setRes("SUCCESS", 200);
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }
    public function setDefault_get($id)
    {
        try {
            if (!$id) self::setErr('INPUT ERROR', 403);
            $e = $this->db->query("SELECT * FROM swtar_reread.setting_qrcode WHERE is_active = 1 AND id = ?", [$id])->row();
            if (!$e) self::setErr('NOT FOUND DATA', 404);

            $this->db->update('swtar_reread.setting_qrcode', ['setdefault' => 0], ['id!=' => $id]);

            $this->db->update('swtar_reread.setting_qrcode', [
                // 'qr_name' => $req->name,
                'setdefault' => 1,
            ], ['id' => $id]);
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }
    public function remove_post($id)
    {
        try {
            if (!$id) self::setErr('INPUT ERROR', 403);
            $e = $this->db->query("SELECT * FROM swtar_reread.setting_qrcode WHERE is_active = 1 AND id = ?", [$id])->row();
            if (!$e) self::setErr('NOT FOUND DATA', 404);
            $this->db->update('swtar_reread.setting_qrcode', [
                'is_active' => 0
            ], ['id' => $id]);
            self::setRes("SUCCESS", 200);
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }
}
