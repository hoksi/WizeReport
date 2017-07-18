<?php
use Widop\GoogleAnalytics\Query;
use Widop\GoogleAnalytics\Client;
use Widop\HttpAdapter\CurlHttpAdapter;
use Widop\GoogleAnalytics\Service;

/**
 * Created by IntelliJ IDEA.
 * User: hoksi
 * Date: 2017-06-06
 * Time: 오후 1:22
 */
class Ga_test extends hoksi\Toast
{
    protected $query;
    protected $token;
    protected $client;
    protected $service;

    public function __construct()
    {
        parent::__construct();

        $this->initGA('ga:131038668')->initClient()->initService();

    }

    protected function initGA($profileId)
    {
        $this->query = new Query($profileId);

        return $this;
    }

    protected function initOption()
    {

        $this->query->setStartDate(new \DateTime('-2months'));
        $this->query->setEndDate(new \DateTime());

        // See https://developers.google.com/analytics/devguides/reporting/core/dimsmets
        $this->query->setMetrics(array('ga:visits', 'ga:bounces'));
        $this->query->setDimensions(array('ga:browser', 'ga:city'));

        // See https://developers.google.com/analytics/devguides/reporting/core/v3/reference#sort
        $this->query->setSorts(array('ga:country', 'ga:browser'));

        // See https://developers.google.com/analytics/devguides/reporting/core/v3/reference#filters
        $this->query->setFilters(array('ga:browser=~^Firefox'));

        // See https://developers.google.com/analytics/devguides/reporting/core/v3/reference#segment
        $this->query->setSegment('gaid::10');

        // Default values :)
        $this->query->setStartIndex(1);
        $this->query->setMaxResults(10000);
        $this->query->setPrettyPrint(false);
        $this->query->setCallback(null);

        return $this;
    }

    protected function initClient()
    {
        $httpAdapter = new CurlHttpAdapter();

        $this->client = new Client(SiteConfig::clientId, APPPATH.'data/client_secret.json', $httpAdapter);
        $this->token = $this->client->getAccessToken();

        return $this;
    }

    protected function initService()
    {
        $this->service = new Service($this->client);
    }

    public function testGetResponse()
    {
        $response = $service->query($this->query);

        var_dump($response);

        $this->_assert_true(false);
    }
}