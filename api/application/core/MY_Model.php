<?php

/**
 * A base model with a series of CI Generator
 * @link http://phpcodemania.blogspot.com
 * @copyright Copyright (c) 2018, SONGCHAI SAETERN
 */
class MY_Model extends CI_Model
{

	/**
	 * Initialise the model, load database with session or default
	 */
	public function __construct()
	{
		parent::__construct();

		if ($this->session->userdata('use_default_database') == FALSE) {
			// If change new DB
			$session_new_db = $this->session->userdata('session_new_db');
			if ($session_new_db != '') {
				$this->config->load("new_database");
				$db_config = $this->config->item('new_db'); // Load default config
				$db_config['database'] = $session_new_db;   // Set new database name
				$this->db = $this->load->database($db_config, TRUE);    // Connect new database
			}
		}
		
	}
	
}
