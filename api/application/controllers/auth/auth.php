<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require APPPATH.'libraries/RestAPI.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE');

class Authen extends RestAPI
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Users_login_model', 'Login');

        // $this->validateAuth();
    }

    public function index_get()
    {
        try {
            self::setRes(['msg' => 'Authen API'], 200);
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }

    public function login_post()
    {
        try {
            $this->load->library('Authen_Jwt');
            $payload = (object)$this->input->post();
            $valid = self::validPayload($payload,
                ['username', 'Username', 'required|min:6|max:40'],
                ['password', 'Password', 'required'],
            );
            if($valid) self::setErr($valid, 403);
            $user_state = 0;
            $exist = $this->db->query('SELECT * FROM geerang_gts.personaldocument a WHERE a.username = ?', [$payload->username])->row();
            if(!$exist) {
                $user_state++;
                $exist = $this->db->query('SELECT * FROM geerang_gts.users a WHERE a.username = ?', [$payload->username])->row();
                if(!$exist) self::setErr('Not found user', 404);
            }

            if(!password_verify($this->Login->encrypt_md5_salt($payload->password), $exist->password)) {
                self::setErr('Not found user', 404);
            }

            $payload = [
                'ui' => $user_state ? $exist->user_id : $exist->pd_id,
                'n' => $user_state ? 'com' : 'emp',
                'cut' => strtotime(date('d-m-Y H:i:s')),
                'exp' => strtotime('+1 day', time())
            ];

            $jwt = $this->authen_jwt->generateToken($payload);
            $this->db->insert('geerang_gts.users_token', [
                'user_id' => $payload['ui'],
                'login_type' => $payload['n'],
                'expired_at' => date('Y-m-d H:i:s', $payload['exp']),
                'token' => $jwt,
            ]);
            
            self::setRes(['msg' => $jwt], 200);
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }

    public function verifyToken_post()
    {
        try {
            $payload = (object)$this->input->post();
            $valid = self::validPayload($payload,
                ['token', 'Token', 'required'],
            );
            if($valid) self::setErr($valid, 403);
            
            $exist = $this->db->query("SELECT * FROM geerang_gts.api_users a WHERE a.token = ?", trim($payload->token))->row();
            if(!$exist) self::setErr('Not found client', 404);
            
            self::setRes([
                'client_name' => $exist->client_name
            ], 200);
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }
    
    public function register_post()
    {
        try {
            $this->load->library('encryption');

            $username = trim($this->input->post('username'), TRUE);
            $password = trim($this->input->post('password'), TRUE);
            $client = trim($this->input->post('client_name'));
            
            $valid = [];

            if(!$username) $valid[] = 'Please input Username';
            if(!$password) $valid[] = 'Please input Password';
            if(!$client) $valid[] = 'Please input Client';

            if($valid) self::setErr($valid, 403);
            $exist = $this->db->query("SELECT * FROM geerang_gts.api_users a WHERE a.username = ?", $username)->row();
            if($exist) self::setErr(['Sorry, Username is unavailable'], 403);

            $password = $this->Login->encrypt_md5_salt($password);
            $api_key = $this->encryption->encrypt($username);
            $secret_key = $this->encryption->encrypt($password);
            $token = base64_encode($api_key.':'.$secret_key);
            
            $this->db->insert('geerang_gts.api_users', [
                'username' => $username,
                'password' => $password,
                'api_key' => $api_key,
                'secret_key' => $secret_key,
                'client_name' => $client,
                'token' => $token,
                'expired_date' => date('Y-m-d H:i:s', strtotime('+1 year', time()))
            ]);

            self::setRes([
                'msg' => "Create success",
                'token_key' => $token
            ], 201);
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }

}
