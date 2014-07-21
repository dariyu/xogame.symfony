<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class MY_Log extends CI_Log {

    function MY_Log(){

        parent::__construct();

    }

    function write_log($level = 'error', $msg, $php_error = FALSE)
	{		
		error_log($level.': '.$msg);		
        return true;

    }

}