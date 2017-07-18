<?php

/**
 * Created by IntelliJ IDEA.
 * User: hoksi
 * Date: 2017-06-20
 * Time: 오후 7:03
 */
class Gareport_test extends \hoksi\Toast
{
    public function __construct()
    {
        parent::__construct();

        if (false) {
            $this->gareport = new Gareport();
        } else {
            $this->load->library('gareport');
        }

        $this->whiteList = array('testVisitorParticipation');

        $this->gareport
            ->setCurrent(2017, 4, 1, 2017, 4, 30)
            ->setPrevious(2017, 3, 1);
    }

    public function testCreateClass()
    {
        $this->assertTrue(get_class($this->gareport) == 'Gareport');
    }

    public function testSetDateRange()
    {
        $current = $this->gareport->getCurrent();
        $previous = $this->gareport->getPrevious();

        $this->assertTrue($current['end'] == '2017-04-30' && $previous['end'] == '2016-03-30');
    }

    public function testOverallSummary()
    {
        $report = $this->gareport->overallSummary();
        $this->assertTrue($report['방문수'] == 18430);

        $this->debug($report);
    }

    public function testCampaign()
    {
        $report = $this->gareport->campaign();

        $this->assertTrue(isset($report['광고캠페인방문수']) && $report['광고캠페인방문수'] == 1631);
    }

    public function testCampaignAll()
    {
        $report = $this->gareport->campaingnAll();

        $this->assertTrue(isset($report[0]['캠페인']) && $report[0]['캠페인'] == 'facebook_jimicam_0418');
    }

    public function testKeywordNew()
    {
        $report = $this->gareport->keywordNew();

        $this->assertTrue(false);

        $this->debug($report);
    }

    public function testKeywordAll()
    {
        $report = $this->gareport->keywordAll();

        $this->assertTrue(false);

        $this->debug($report);
    }

    public function testNaverKeyword()
    {
        $report = $this->gareport->NaverKeyword();

        $this->assertTrue(false);

        $this->debug($report);
    }

    public function testProduct()
    {
        $report = $this->gareport
            ->setCurrent(2017, 4, 1, 2017, 4, 30)
            ->product();

        $this->assertTrue(false);

        $this->debug($report);
    }

    public function testVisitStat()
    {
        $report = $this->gareport
            ->setCurrent(2017, 4, 1, 2017, 4, 30)
            ->visitStat();

        $this->assertTrue(false);

        $this->debug($report);
    }

    public function testVisitStatSearch()
    {
        $report = $this->gareport
            ->setCurrent(2017, 4, 1, 2017, 4, 30)
            ->visitStatSearch();

        $this->assertTrue(false);

        $this->debug($report);
    }

    public function testVisitStatLink()
    {
        $report = $this->gareport
            ->setCurrent(2017, 4, 1, 2017, 4, 30)
            ->visitStatLink();

        $this->assertTrue(false);

        $this->debug($report);
    }

    public function testVisitStatSocial()
    {
        $report = $this->gareport
            ->setCurrent(2017, 6, 1, 2017, 6, 30)
            ->visitStatSocial();

        $this->assertTrue(false);

        $this->debug($report);
    }

    public function testVisitorParticipation()
    {
        $report = $this->gareport
            ->setCurrent(2017, 6, 1, 2017, 6, 30)
            ->visitorParticipation();

        $this->assertTrue(false);

        $this->debug($report);
    }

    protected function debug($data)
    {
        echo '<xmp>';
        print_r($data);
        echo '</xmp>';
    }
}