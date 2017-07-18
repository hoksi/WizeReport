<?php

/**
 * Created by IntelliJ IDEA.
 * User: hoksi
 * Date: 2017-06-14
 * Time: 오전 4:05
 */
class Analytics extends CI_Controller
{
    public $gaAccount;

    public function __construct()
    {
        parent::__construct();

        $this->load->library('gauth');

        if (false) {
            $this->gauth = new Gauth();
        }

        $this->gaAccount = $this->gauth->getAccount();
    }

    public function Report($account_id = null, $property_id = null, $profile_id = null, $report_id = null)
    {
        if ($account_id == null) {
            $this->load->view('analytics/account', array('account' => $this->gaAccount));
        } elseif ($property_id == null) {
            if (isset($this->gaAccount[$account_id])) {
                $this->load->view('analytics/property', array(
                    'account_id' => $account_id,
                    'account_name' => $this->gaAccount[$account_id]['name'],
                    'property' => $this->gaAccount[$account_id]['properties']
                ));
            } else {
                echo 'Account Not Found!(' . $account_id . ')';
            }
        } elseif($profile_id == null) {
            if(isset($this->gaAccount[$account_id]['properties'][$property_id]['profiles'])) {
                $this->load->view('analytics/profiles', array(
                    'account_id' => $account_id,
                    'account_name' => $this->gaAccount[$account_id]['name'],
                    'property_id' => $property_id,
                    'property_name' => $this->gaAccount[$account_id]['properties'][$property_id]['name'],
                    'profiles' => $this->gaAccount[$account_id]['properties'][$property_id]['profiles']
                ));
            }
        } elseif($report_id == null) {
            $_SESSION['profile_id'] = $profile_id;
            $_SESSION['current']['start'] = '2017-03-01';
            $_SESSION['current']['end'] = '2017-03-31';
            $_SESSION['previous']['start'] = '2017-02-01';
            $_SESSION['previous']['end'] = '2017-02-28';

            redirect('tests/gareport_test');


            $this->load->view('analytics/report_list', array(
                'account_id' => $account_id,
                'account_name' => $this->gaAccount[$account_id]['name'],
                'property_id' => $property_id,
                'property_name' => $this->gaAccount[$account_id]['properties'][$property_id]['name'],
                'profile_id' => $profile_id,
                'profile_name' => $this->gaAccount[$account_id]['properties'][$property_id]['profiles'][$profile_id]['name'],
                'report_list' => $this->gauth->getResultTitles()
            ));
        } elseif($report_id) {
            echo '<xmp>';
            if(strstr($report_id, 'previous')) {
                $start = '2017-02-01';
                $end = '2017-02-28';
            } else {
                $start = '2017-03-01';
                $end = '2017-03-31';
            }
            var_dump($this->gauth->getResults($profile_id, $report_id, $start, $end));
        }

    }
}