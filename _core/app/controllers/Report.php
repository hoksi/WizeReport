<?php

/**
 * Created by IntelliJ IDEA.
 * User: hoksi
 * Date: 2017-06-20
 * Time: 오전 6:42
 */
class Report extends CI_Controller
{
    protected $profile_id;

    public function __construct()
    {
        parent::__construct();

        if (isset($_SESSION['profile_id'])) {
            $this->profile_id = $_SESSION['profile_id'];

            $this->load->library('gauth');
            $this->gauth = new Gauth();
        } else {
            redirect('welcome/login');
        }
    }

    public function index()
    {
        $this->overall_summary();
    }

    public function overall_summary() {

    }
}