<?php

/**
 * Created by IntelliJ IDEA.
 * User: hoksi
 * Date: 2017-06-06
 * Time: 오후 1:19
 */
class Auth extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();

        if(false) {
            $this->gauth = new Gauth();
        }
    }

    public function _remap()
    {
        $this->load->library('gauth');

        $this->gauth->setDefaultUrl('analytics/report')->auth($this->input->get('code'));
    }
}