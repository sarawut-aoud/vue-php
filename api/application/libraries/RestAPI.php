<?php


defined('BASEPATH') or exit('No direct script access allowed');

class RestAPI extends REST_Controller
{

    protected $special_api = false;

    const HTTP_CODE = [
        200 => 'SUCCESS', // get or udpate
        201 => 'CREATED', // create , insert
        202 => 'APPECPTED',
        400 => 'BAD REQUEST',
        401 => 'NO AUTH',
        403 => 'INPUT ERROR',
        404 => 'NOT FOUND', // ไม่มีข้อมูลในระบบ
        500 => 'INTERNAL SERVER ERROR'
    ];

    public function __construct()
    {
        parent::__construct();
        if (json_decode($this->input->raw_input_stream, true)) {
            $_POST = json_decode($this->input->raw_input_stream, true);
        }
        $this->_run_middlewares();
    }

    protected function setSpecialApi()
    {
        $this->special_api = true;
    }

    protected function validateAuth($payload = [])
    {

        try {
            $input = $this->input->request_headers();
            // $header = $input ?? $payload;
            $bearer = $input['authorization'];

            $xapikey = $input['x-api-key'];
            if (!empty($bearer)) {
                if (preg_match('/Bearer\s(\S+)/', $bearer, $matches)) {
                    $token = $matches[1];
                }
            }

            if ((!$token || !$bearer) || ($xapikey && $bearer)) {
                $this->response(['msg' => 'Unauthentication'], 401);
                header('Content-Type: application/json');

                echo json_encode([
                    'status' => 'NO AUTH',
                    'success' => false,
                    'code' => 401,
                    'msg' => 'Unauthorization',
                ]);
                exit(1);
            }

            $exist = $this->db->query('SELECT * FROM swtar_reread.api_users a WHERE a.token = ? AND a.expired_date >= CURRENT_TIMESTAMP() ', [$token])->row();
       
            if (!$exist) {
                $this->response(['msg' => 'Unauthentication'], 401);
                header('Content-Type: application/json');

                echo json_encode([
                    'status' => 'NO AUTH',
                    'success' => false,
                    'code' => 401,
                    'msg' => 'Unauthorization',
                ]);
                exit(1);
            }
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }

    protected function setRes($data, $code = 200)
    {

        $RES = [
            'status' => self::HTTP_CODE[(int)$code],
            'success' => true,
            'code' => $code,
            'data' => $data ?? []
        ];

        throw new Exception(json_encode($RES));
    }

    protected function setErr($data, $code = 200)
    {

        $RES = [
            'status' => self::HTTP_CODE[(int)$code],
            'success' => false,
            'code' => $code,
            'msg' => $data ?? []
        ];

        throw new Exception(json_encode($RES));
    }

    protected function sendResponse($payload, $method = '')
    {
        $data = json_decode($payload->getMessage());
        $data->from = $method;
        $this->response($data, $data->code);
    }

    protected function validPayload($payload, ...$data)
    {
        $valid = [];
        $payload = (array)$payload;
        $all_key = array_map(function ($e) {
            return $e[0];
        }, $data);

        foreach ($data as $key => $rule) {
            $rules = explode('|', $rule[2]);
            if (!$payload[$rule[0]] && !in_array('optional', $rules)) {
                $valid[] = "Please input {$rule[1]}";
                continue;
            }
            if (in_array('optional', $rules)) {
                $_data = $this->input->post($rule[0]) ?? $this->input->get($rule[0]);
                if (!$_data) continue;
            }
            foreach ($rules as $key => $_r) {
                $_val = explode(':', $_r);
                $_valid = self::_valid($payload[$rule[0]], $_val[0], $rule[1], array_splice($_val, 1));
                if ($_valid) $valid[] = $_valid;
            }
        }

        // if(in_array('CLEAR', $valid)) $valid = [];
        return $valid;
    }

    private function _valid($value, $rules, $label, $rule_value = null)
    {
        switch ($rules) {
            case 'required':
                if (!$value || $value == 'null') return "Please input $label.";
                break;
            case 'min':
                if (strlen($value) < $rule_value[0]) return "$label must more than $rule_value[0].";
                break;
            case 'max':
                if (strlen($value) > $rule_value[0]) return "$label must less than $rule_value[0].";
                break;
            case 'numeric':
                $regex = '/^(?:-(?:[1-9](?:\d{0,2}(?:,\d{3})+|\d*))|(?:0|(?:[1-9](?:\d{0,2}(?:,\d{3})+|\d*))))(?:.\d+|)$/';
                if (!preg_match($regex, $value)) return "$label isn't numeric.";
                break;
            case 'is_array':
                if (!is_array($value)) return "$label isn't array.";
                break;
            case 'in_array':
                $_filter = explode(',', $rule_value[0]);
                if (!in_array($value, $_filter)) return "'$value' isn't option in $label.";
                break;
            case 'custom':
                $_val = false;
                if (count($rule_value) > 1) $rule_value = [join(':', $rule_value)];
                preg_match_all('/[^a-zA-Z0-9\s]/', join('', $rule_value), $match1);
                preg_match_all('/[^a-zA-Z0-9\s]/', (string)$value, $match2);
                $current_index_split = 0;

                if ($rule_value[0]) {
                    if (strlen($rule_value[0]) != strlen((string)$value)) $_val = true;
                    for ($i = 0; $i < strlen($rule_value[0]); $i++) {
                        if ($_val) continue;
                        $split = $match1[0][$current_index_split];
                        if ($rule_value[0][$i] != 'x' && $value[$i] != $split) {
                            $_val = true;
                            break;
                        }
                        if ($value[$i] == $split) {
                            $current_index_split++;
                        }
                    }
                }

                if ($_val) return "'$value' isn't format like $rule_value[0].";
                break;

            case 'optional':
            default:
                break;
        }
    }

    protected function middleware()
    {
        return array();
    }

    protected function _run_middlewares()
    {
        $this->load->helper('inflector');
        $middlewares = $this->middleware();
        foreach ($middlewares as $middleware) {
            $middlewareArray = explode('|', str_replace(' ', '', $middleware));
            $middlewareName = $middlewareArray[0];
            $runMiddleware = true;
            if (isset($middlewareArray[1])) {
                $options = explode(':', $middlewareArray[1]);
                $type = $options[0];
                $methods = explode(',', $options[1]);
                if ($type == 'except') {
                    if (in_array($this->router->method, $methods)) {
                        $runMiddleware = false;
                    }
                } else if ($type == 'only') {
                    if (!in_array($this->router->method, $methods)) {
                        $runMiddleware = false;
                    }
                }
            }
            $filename = ucfirst(camelize($middlewareName)) . 'Middleware';
            if ($runMiddleware == true) {
                if (file_exists(APPPATH . 'middlewares/' . $filename . '.php')) {
                    require APPPATH . 'middlewares/' . $filename . '.php';
                    $ci = &get_instance();
                    $object = new $filename($this, $ci);
                    $object->run();
                    $this->middlewares[$middlewareName] = $object;
                } else {
                    if (ENVIRONMENT == 'development') {
                        show_error('Unable to load middleware: ' . $filename . '.php');
                    } else {
                        show_error('Sorry something went wrong.');
                    }
                }
            }
        }
    }
}
