<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require APPPATH.'libraries/RestAPI.php';
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE');

class Notification extends RestAPI
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

    public function send_post()
    {
        try {
            $payload = (object)$this->input->post();
            $valid = self::validPayload($payload,
                ['pd_id', 'Employee ID', 'required'],
                ['noti_type', 'Notification Process Type', 'required'],
                ['noti_category', 'Notication Category', 'required']
            );
            if($valid) self::setErr($valid, 403);
            
            $exist = $this->db->query('SELECT * FROM geerang_gts.personaldocument a WHERE a.pd_id = ?',[$payload->pd_id])->row();
            if(!$exist) self::setErr('Not found employee', 404);

            if(in_array('app_noti', $payload->noti_category)) {
                $this->AppNoti->send_noti(
                    'ทดสอบการส่ง Notification',
                    '',
                    $payload->pd_id,
                    327,
                    ''
                );
            }
            if(in_array('email', $payload->noti_category)) {
                $mailsend       = 'noreply@geerang.com';
				$mailto         = $exist->email;
				$subject        = 'ส่งเมลน้า';
				$msg = array();
				$msg['link'] = base_url();
				$msg['fullname'] = $exist->firstname . ' ' . $exist->lastname;
				$bodyhtml       = '';
				$bodyhtml = $this->SendMail->emailTemplate([
					'title' => $subject,
					'subtitle' => 'อยากส่งเฉยๆ',
					'content' => $bodyhtml
				], 'https://img.freepik.com/free-vector/forgot-password-concept-illustration_114360-1095.jpg?w=1480&t=st=1689309646~exp=1689310246~hmac=0d74bf322b6bc9134eae51ab0df6a1a0880f2889aa193344b1c5f72cef257120');
				$mail = $this->sendEventmail($mailsend, $mailto, $subject, $bodyhtml);
            }

            self::setRes(['msg' => 'Send Success'], 200);
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }

    private function sendEventmail($mailsend, $mailto, $subject, $bodyhtml)
	{
		$data = array();
		$data['email'] = $mailto;
		$data['message'] = $bodyhtml;
		$data['mailsend'] = $mailsend;
		$result = $this->SendMail->sendtomail($subject, $data);
		return $result;
	}
}
