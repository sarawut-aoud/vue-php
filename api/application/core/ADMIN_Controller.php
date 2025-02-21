<?php (defined('BASEPATH')) or exit('No direct script access allowed');


class ADMIN_Controller extends CI_Controller
{
    public $data;
    public $_id;
    public $auth;
    public $rem;
    public $cut;
    public $exp;
    public $_token;

    public function __construct()
    {
        parent::__construct();

        $data['base_url'] = base_url();
        $data['site_url'] = site_url();
        $data['csrf_token_name'] = $this->security->get_csrf_token_name();
        $data['csrf_cookie_name'] = $this->config->item('csrf_cookie_name');
        $data['csrf_protection_field'] = insert_csrf_field(true);

        if (!$this->input->cookie('usertoken')  && $_SERVER['REQUEST_URI'] != '/spm/Logout' && $_SERVER['REQUEST_URI'] != '/spm/login/process') {
            if ($_SERVER['REQUEST_URI'] != "/spm/login") header("Location: /spm/login");
        }
        $this->token = ($this->input->cookie('usertoken'));
        $decode = explode('.', ($this->token));
        $decode = array_map(function ($e) {
            return base64_decode($e);
        }, $decode)[1];
        $JWT =  (object) self::jsonStringToArray($decode);
        $this->_token = $JWT;
        $this->_id = $JWT->emp_id;
        $this->auth = $JWT->auth;
        $this->rem = $JWT->rem;
        $this->cut = $JWT->cut;
        $this->exp = $JWT->exp;
        $this->ref = $JWT->ref;
        $this->base_url = base_url();

        if (
            !$this->rem == 'true' && !$this->rem
        ) {
            self::expireToken();
        }
        $this->data = $data;
    }
    private function expireToken()
    {
        if (strtotime(date('d-m-Y H:i:s')) == $this->exp) {
            delete_cookie("usertoken");
            redirect('spm/login');
        }
    }
    protected function render_view($path)
    {
        $this->another_js .= '<script src="' . base_url('assets/plugins/sortable/Sortable.min.js') . '"></script>';
        $this->another_js .= '<script src="' . base_url('assets/plugins/vue3-sortablejs/vue3-sortablejs.global.js') . '"></script>';
        $this->another_js .= '<script src="' . base_url('assets/plugins/jquery-sortable/jquery-sortable.js') . '"></script>';
        $this->data['page_content'] = $this->parser->parse_repeat($path, $this->data, TRUE);
        $this->data['another_css'] = $this->another_css;
        $this->data['another_js'] = $this->another_js;
        $this->parser->parse('spm/template/preview', $this->data);
    }
    protected function renderTemplate($path)
    {

        $this->data['page_content'] = $this->parser->parse_repeat($path, $this->data, TRUE);
        $this->data['another_css'] = $this->another_css;
        $this->data['another_js'] = $this->another_js;
        $this->parser->parse('spm/template/blank', $this->data);
    }
    protected function setJsModule($path)
    {
        $this->another_js .= '<script type="module" src="' . base_url($path)  . '?ft=' . time() . '"> </script>';
    }
    protected function  jsonStringToArray($jsonString)
    {
        $array = json_decode($jsonString, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $array;
        }
    }
    protected function getRes($status, $data = '', $code = '')
    {
        $response['status'] = $status ? $status : null;
        $response['data'] = $data ? $data : null;
        $response['code'] = $code ? $code : null;

        echo json_encode($response);
    }

    protected function setRes($status, $data, $code, $option = '')
    {
        $this->response['status'] = $status ? $status : false;
        $this->response['data'] = $data ? $data : null;
        $this->response['code'] = $code ? $code : null;

        if ($option) {
            $this->response['options'] = $option;
        }

        throw new Exception();
    }

    protected function setErr($code, $message = '')
    {
        $this->response['status'] = false;
        $this->response['message'] = $message ? $message : self::errorCode($code);
        $this->response['code'] = $code;
        throw new Exception();
    }

    protected function response($method)
    {
        echo json_encode($this->response);
    }
    private function errorCode($code): string
    {
        switch ($code) {
            case 400:
                return 'BAD REQUEST';
            case 401:
                return 'NO AUTHENRIZATION';
            case 402:
                return 'INPUT ERROR';
            case 404;
                return 'NOT FOUND';
            default:
                return 'ERROR';
        }
    }
    
}
