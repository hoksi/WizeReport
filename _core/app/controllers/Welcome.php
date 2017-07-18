<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Welcome extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        echo '<a href="/welcome/login">WizeReport</a>';
    }

    public function login()
    {
        $this->load->library('gauth');

        $this->gauth->login();
    }


}
