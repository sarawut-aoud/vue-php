<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require APPPATH . 'libraries/RestAPI.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE');

class Authen extends RestAPI
{
    public function __construct()
    {
        parent::__construct();

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
            $payload = (object)$this->input->post();
            $valid = self::validPayload(
                $payload,
                ['email', 'Email', 'required|min:2|max:40'],
                ['password', 'Password', 'required'],
            );
            if ($valid) self::setErr($valid, 403);
            $exist = $this->db->query("SELECT * FROM swtar_reread.api_users a
            LEFT JOIN swtar_reread.personaldocument b ON b.pd_id = a.pd_id
            WHERE b.username = ?
            ", [$payload->email])->row();

            if (!password_verify(encrypt_md5_salt($payload->password), $exist->password)) {
                self::setErr('Not found user', 404);
            }
            $jwt = self::setJWT($exist->pd_id, $exist->user_type);

            self::setRes([
                'token_key' => $exist->token,
                'jwt' => $jwt
            ], 200);
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }

    public function verifyToken_post()
    {
        try {
            $payload = (object)$this->input->post();
            $valid = self::validPayload(
                $payload,
                ['token', 'Token', 'required'],
            );
            if ($valid) self::setErr($valid, 403);

            $exist = $this->db->query("SELECT * FROM swtar_reread.api_users a WHERE a.token = ?", trim($payload->token))->row();
            if (!$exist) self::setErr('Not found client', 404);

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

            $email = trim($this->input->post('email'), TRUE);
            $username = trim($this->input->post('username'), TRUE);
            $password = trim($this->input->post('password'), TRUE);
            $client = trim($this->input->post('client_name'));

            $valid = [];

            if (!$email) $valid[] = 'Please input Email';
            if (!$username) $valid[] = 'Please input Username';
            if (!$password) $valid[] = 'Please input Password';

            if ($valid) self::setErr($valid, 403);
            $exist = $this->db->query("SELECT * FROM swtar_reread.api_users a WHERE a.username = ?", $username)->row();
            if ($exist) self::setErr('Sorry, Username is unavailable', 403);

            $password = encrypt_md5_salt($password);

            $this->db->insert('swtar_reread.personaldocument', [
                'email' => $email,
                'username' => $email,
                'first_name' => $username,
                'password' => pass_secure_hash($password),
            ]);
            $last_id = $this->db->insert_id();

            $api_key = $this->encryption->encrypt($username);
            $secret_key = $this->encryption->encrypt($password);
            $token = base64_encode($api_key . ':' . $secret_key);

            $this->db->insert('swtar_reread.api_users', [
                'pd_id' => $last_id,
                'username' => $username,
                'password' => $password,
                'email'     => $email,
                'api_key' => $api_key,
                'secret_key' => $secret_key,
                'client_name' => $client,
                'token' => $token,
                'expired_date' => date('Y-m-d H:i:s', strtotime('+1 year', time()))
            ]);


            $jwt = self::setJWT($last_id);
            self::setRes([
                'msg' => "Create success",
                'token_key' => $token,
                'jwt' => $jwt
            ], 201);
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }
    private function setJWT($pd_id, $user_type = 1)
    {
        $this->load->library('Authen_Jwt');

        $payload = [
            'ui' =>  $pd_id,
            'n' => $user_type == 9 ? 'admin' : 'emp',
            'cut' => strtotime(date('d-m-Y H:i:s')),
            'exp' => strtotime('+1 day', time())
        ];

        $jwt = $this->authen_jwt->generateToken($payload);
        $this->db->insert('swtar_reread.users_token', [
            'user_id' => $payload['ui'],
            'login_type' => $payload['n'],
            'expired_at' => date('Y-m-d H:i:s', $payload['exp']),
            'token' => $jwt,
        ]);
        return $jwt;
    }
}
