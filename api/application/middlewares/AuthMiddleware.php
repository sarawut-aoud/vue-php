<?php
/**
 * Author: https://github.com/davinder17s
 * Email: davinder17s@gmail.com
 * Repository: https://github.com/davinder17s/codeigniter-middleware
 */

class AuthMiddleware {
    protected $controller;
    protected $ci;
    public $roles = array();

    public function __construct($controller, $ci)
    {
        $this->controller = $controller;
        $this->ci = $ci;
    }

    public function run(){


        try {
            // show_error('ok');
            // $this->controller->set('ok');
        } catch (Exception $e) {
        }
        // $this->roles = array('somehting', 'view', 'edit');
    }
}