<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require APPPATH . 'libraries/RestAPI.php';
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE');


class Chanal extends RestAPI
{
    public function __construct()
    {
        parent::__construct();
        $this->validateAuth();
    }

    public function index_get()
    {
        try {
            self::setRes(['msg' => 'Chanal API'], 200);
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }
    public function userLoginWidget_get()
    {
        $this->load->model('Sendmail_model', 'Sendmail_model');

        try {
            $payload = (object)$this->input->get();

            $valid = self::validPayload(
                $payload,
                ['com_id', 'Company ID', 'required'],
                ['emp_id', 'Employee ID', 'optional'],
                ['user_id', 'Line User  ID', 'optional'],
            );
            if ($valid) self::setErr($valid, 403);
            $this->save($payload);

            $com_exist = $this->db->query('SELECT * FROM geerang_gts.users a WHERE a.id = ?', [$payload->com_id])->row();
            if (!$com_exist) self::setErr('Not found company', 404);

            $emp_exist = $this->db->query(
                "SELECT * FROM geerang_gts.personaldocument WHERE (pd_id = ? OR lineid = ?)",
                [$payload->emp_id, $payload->user_id]
            )->row();
            if (!$emp_exist) self::setErr('Not found Employee', 404);

            $result = $this->db->query(
                "SELECT * FROM geerang_gts.personaldocument WHERE (pd_id = ? OR lineid = ?)",
                [$payload->emp_id, $payload->user_id]
            )->row();
            $otp = false;
            if (!$otp) {



                $date = date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s') . "+15minutes"));

                $res_otp = (object)[
                    'otp' => (int)generateOTP(),
                    'expire' => [
                        'datetime' => date_format(date_create($date), "Y-m-d\TH:i:s\z"),
                        'date' => date_format(date_create($date), "d/m/Y"),
                        'time' => date_format(date_create($date), "H:i:s"),
                    ],
                ];
                self::setRes($res_otp, 200);
            }


            $res = (object)[
                'emp_id' => (int)$result->pd_id,
                'user_id' => $result->lineid,
                'first_name' =>   $result->first_name,
                'last_name' => $result->last_name,
                'fullname' => $result->first_name . ' ' . $result->last_name,
                'email' => $result->email,
                'tel' => $result->phone_number,
                'Automate' => [
                    'Attendence' => true,
                    'Leaves' => true,
                ],
            ];
            self::setRes($res, 200);
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }
    public function Check_Line_ID_get()
    {

        try {
            $payload = (object)$this->input->get();
            $valid = self::validPayload(
                $payload,
                ['user_id', 'User Line ID', 'required'],
            );
            if ($valid) self::setErr($valid, 403);
            $this->save($payload);

            $emp_exist = $this->db->query("SELECT * FROM geerang_gts.personaldocument WHERE lineid = ?", [$payload->user_id])->row();
            if (!$emp_exist) self::setErr('Not found User Line ID', 404);


            $result = $this->db->query("SELECT * FROM geerang_gts.personaldocument WHERE lineid = ?", [$payload->user_id])->row();

            $res =  (object)[
                'user_id' => $result->lineid,
            ];
            self::setRes($res, 200);
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }
    public function Register_post()
    {
        try {
            $payload = (object)$this->input->post();
            $valid = self::validPayload(
                $payload,
                ['user_id', 'User Line ID', 'required'],
                ['com_id', 'Company ID', 'required'],
                ['emp_id', 'Employee ID', 'required'],
            );
            if ($valid) self::setErr($valid, 403);
            $this->save($payload);

            $com_exist = $this->db->query('SELECT * FROM geerang_gts.users a WHERE a.id = ?', [$payload->com_id])->row();
            if (!$com_exist) self::setErr('Not found company', 404);

            $emp_exist = $this->db->get_where('geerang_gts.personaldocument', ['pd_id' => $payload->emp_id])->row();
            if (!$emp_exist) self::setErr('Not found Employee', 404);


            $user_exist = $this->db->get_where('geerang_gts.personaldocument', ['pd_id' => $payload->emp_id])->row();

            if ($user_exist->lineid) self::setErr('Already have a User ID', 404);

            $result = $this->db->update('geerang_gts.personaldocument', ['lineid' => trim($payload->user_id)], ['pd_id' => $payload->emp_id]);


            $res =  (object)[
                'user_id' => trim($payload->user_id),
                'regis_status' => (bool)  $result,
            ];
            self::setRes($res, 200);
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }
    public function checkOtp_get()
    {

        try {
            $payload = (object)$this->input->get();
            $valid = self::validPayload(
                $payload,
                ['otp', 'OTP', 'required'],
            );
            if ($valid) self::setErr($valid, 403);
            $this->save($payload);

            $res = [];
            self::setRes($res, 200);
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }
    public function deleteLineUserID_post()
    {
        $com_init = implode(',', [
            354,
            389,
        ]);
        try {
            $payload = (object)$this->input->post();
            $valid = self::validPayload(
                $payload,
                ['emp_id', 'Employee ID', 'required'],
            );
            if ($valid) self::setErr($valid, 403);
            $this->save($payload);


            $emp_exist = $this->db->get_where('geerang_gts.personaldocument', ['pd_id' => $payload->emp_id])->row();
            if (!$emp_exist) self::setErr('Not found Employee', 404);

            $exist_com = $this->db->query(
                "SELECT * FROM geerang_hrm.personalsecret WHERE pd_id = ? AND com_id IN(?)",
                [$payload->emp_id, $com_init]
            )->row();

            if (!$exist_com) self::setErr('Not Delete Line User ID', 404);

            $result =   $this->db->update('geerang_gts.personaldocument', ['lineid' => NULL], ['pd_id' => $payload->emp_id]);

            $res = [ 
                'delete' => (bool) $result,
            ];
            self::setRes($res, 200);
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }
    private function SendOTP($from, $email, $subject, $opt = [
        'pd_id' => '',
        'com_id' => '',
        'fullname' => ''
    ])
    {
        $mailsend       =  $from;
        $mailto         =  $email;
        $subject        = $subject;
        $headtext       = 'เรียนคุณ';


        $content = '<div style="font-size: 16px;">
                              ' . $headtext  . '
                            </div>
                            <br>
                            Verification
Please use the verification below to sign in.

If you didn’t request this, you can ignore this email.

Thanks,
GEERANG & MIND AI team
                            <center>
                                <div style="font-size: 18px;">
                                    คลิกปุ่มเพื่อยืนยันตัวตนในการใช้งาน 
                                </div>
                                <br>
                                <div>
                                    <a href=""
                                        style="color:white;border-radius: 12px;background-color: rgb(60, 157, 255);padding: 1rem;text-decoration: none">
                                        Verify Now
                                    </a>
                                </div>
                            </center>
                            <div style=" width: 100%;">
                                <br><span>GEERANG & MIND AI team</span>
                            </div>';

        $sendmessage =  $this->Sendmail_model->emailTemplate([
            'title' => 'ยืนยันผู้ใช้งาน',
            'subtitle' => '',
            'content' =>  $content,
        ], 'https://img.freepik.com/free-vector/enter-otp-concept-illustration_114360-7967.jpg?t=st=1708532336~exp=1708535936~hmac=33d7b4fcdd956e161397eb328330fc911334ae7c5343c39b48ea959f1fbb9787&w=826');
        $this->sendEventmail($mailsend, $mailto, $subject, $sendmessage);
    }
    private function sendEventmail($mailsend, $mailto, $subject, $bodyhtml, $uploadfile = "")
    {
        $datamail = array(
            'email' => $mailto, 'message' => $bodyhtml, 'mailsend' => $mailsend, 'uploadfile' => $uploadfile
        );
        $result = $this->Sendmail_model->sendtomail($subject, $datamail);
        return $result;
    }
}
