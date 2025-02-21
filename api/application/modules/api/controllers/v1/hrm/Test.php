<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require APPPATH.'libraries/RestAPI.php';
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE');

class Test extends RestAPI
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('AppNoti_model', 'AppNoti');
        $this->load->model('Sendmail_model', 'SendMail');
        $this->validateAuth();
    }

    public function index_get()
    {
        try {
            self::setRes(['msg' => 'Notification API'], 200);
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }

    
}
