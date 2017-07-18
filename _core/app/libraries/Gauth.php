<?php

/**
 * Created by IntelliJ IDEA.
 * User: hoksi
 * Date: 2017-06-13
 * Time: 오후 9:45
 */
class Gauth
{
    protected $client;
    protected $analytics;
    protected $login_url;
    protected $default_url;
    protected $gaAccount;
    protected $gaReports;
    protected $isCaching;
    protected $gaResultType;
    protected $session;

    public function __construct()
    {
        $ci = &get_instance();

        $this->session = &$ci->session;

        $this->client = new Google_Client();

        $this->client->setApplicationName('WizeReport');
        $this->client->setAuthConfig(SiteConfig::googleKeyfile);
        $this->default_url = 'analytics/report';
        $this->login_url = 'welcome/login';
        $this->analytics = null;
        $this->gaResultType = null;
        $this->isCaching = false;

        if ($this->isCaching) {
            $this->gaAccount = isset($_SESSION['ga_account']) ? $_SESSION['ga_account'] : null;
            $this->gaReports = isset($_SESSION['ga_reports']) ? $_SESSION['ga_reports'] : null;
        } else {
            unset($_SESSION['ga_reports']);
            $this->gaAccount = null;
            $this->gaReports = null;
        }
    }

    public function login()
    {
        $this->client->setScopes(['https://www.googleapis.com/auth/analytics.readonly https://www.googleapis.com/auth/userinfo.email']);
        $auth_url = $this->client->createAuthUrl();

        redirect(filter_var($auth_url, FILTER_SANITIZE_URL));
    }

    public function auth($code)
    {
        $this->client->setScopes(['https://www.googleapis.com/auth/analytics.readonly']);
        $this->client->authenticate($code);
        $access_token = $this->client->getAccessToken();

        if ($access_token) {
            //get user email address
            $google_oauth = new Google_Service_Oauth2($this->client);
            $google_userinfo = $google_oauth->userinfo->get();

            $_SESSION['google_userinfo'] = $google_userinfo;
            $_SESSION['google_token'] = $access_token;

            if (isset($_SESSION['go_url'])) {
                unset($_SESSION['go_url']);
                redirect($_SESSION['go_url']);
            } else {
                redirect($this->default_url);
            }
        } else {
            redirect($this->login_url);
        }

    }

    public function setLoginUrl($url)
    {
        $this->login_url = $url;

        return $this;
    }

    public function setDefaultUrl($url)
    {
        $this->default_url = $url;

        return $this;
    }

    public function getAnalytics()
    {
        if ($this->isExpireToken()) {
            $_SESSION['google_token'] = null;
        }

        if ($this->analytics === null) {
            $this->client->setScopes(['https://www.googleapis.com/auth/analytics.readonly']);
            $access_token = $_SESSION['google_token'];

            if ($access_token) {
                $this->client->setAccessToken($access_token);
                $this->analytics = new Google_Service_Analytics($this->client);
            } else {
                $ci = &get_instance();
                $_SESSION['go_url'] = site_url($ci->uri->uri_string());

                redirect($this->login_url);
            }
        }

        return $this->analytics;
    }

    public function getAccount()
    {
        if (empty($this->gaAccount) || $this->isCaching === false) {
            $analytics = $this->getAnalytics();

            $accounts = $analytics->management_accounts->listManagementAccounts();
            if (count($accounts->getItems()) > 0) {
                foreach ($accounts->getItems() as $item) {
                    $this->gaAccount[$item->getId()] = array(
                        'id' => $item->getId(),
                        'name' => $item->getName()
                    );

                    $this->getProperties($item->getId());
                }

                $_SESSION['ga_account'] = $this->gaAccount;
            } else {
                throw new Exception('No accounts found for this user.');
            }
        }

        return $this->gaAccount;
    }

    public function getProperties($accountId)
    {
        if (!isset($this->gaAccount[$accountId]['properties'])) {
            $analytics = $this->getAnalytics();

            $properties = $analytics->management_webproperties
                ->listManagementWebproperties($accountId);

            if (count($properties->getItems()) > 0) {
                $this->gaAccount[$accountId]['properties'] = array();
                foreach ($properties->getItems() as $item) {
                    $this->gaAccount[$accountId]['properties'][$item->getId()] = array(
                        'id' => $item->getId(),
                        'name' => $item->name,
                        'defaultProfileId' => $item->defaultProfileId,
                        'level' => $item->level,
                        'selfLink' => $item->selfLink,
                        'websiteUrl' => $item->websiteUrl,
                        'created' => $item->created,
                        'updated' => $item->updated,
                    );

                    $this->getProfiles($accountId, $item->getId());
                }
            } else {
                throw new Exception('No properties found for this user.');
            }
        }

        return $this->gaAccount[$accountId]['properties'];
    }

    public function getProfiles($accountId, $propertyId)
    {
        if (!isset($this->gaAccount[$accountId]['properties'][$propertyId]['profiles'])) {
            $analytics = $this->getAnalytics();

            $profiles = $analytics->management_profiles
                ->listManagementProfiles($accountId, $propertyId);

            if (count($profiles->getItems()) > 0) {
                $this->gaAccount[$accountId]['properties'][$propertyId]['profiles'] = array();
                foreach ($profiles->getItems() as $item) {
                    $this->gaAccount[$accountId]['properties'][$propertyId]['profiles'][$item->getId()] = array(
                        'id' => $item->getId(),
                        'name' => $item->name,
                        'botFilteringEnabled' => $item->botFilteringEnabled,
                        'eCommerceTracking' => $item->eCommerceTracking,
                        'selfLink' => $item->selfLink,
                        'currency' => $item->currency,
                        'defaultPage' => $item->defaultPage,
                        'enhancedECommerceTracking' => $item->enhancedECommerceTracking,
                        'excludeQueryParameters' => $item->excludeQueryParameters,
                        'timezone' => $item->timezone,
                        'type' => $item->type,
                        'websiteUrl' => $item->websiteUrl,
                        'created' => $item->created,
                        'updated' => $item->updated,
                    );
                }
            } else {
                throw new Exception('No views (profiles) found for this user.');
            }
        }

        return $this->gaAccount[$accountId]['properties'][$propertyId]['profiles'];
    }

    public function getResults($profileId, $type, $start = null, $end = null)
    {
        $gaParams = $this->gatGaResultTypeParams($type);

        if ($gaParams !== false) {
            $start = $start ? $start : date('Y-m-01');
            $end = $end ? $end : 'today';

            $key = md5($profileId . $type . $start . $end);

            if (!isset($this->gaReports[$key]) || $this->isCaching == false) {
                $analytics = $this->getAnalytics();

                $report = $analytics->data_ga->get(
                    'ga:' . $profileId,
                    $start,
                    $end,
                    $gaParams['metrics'],
                    $gaParams['opt']
                );

                if (isset($report->rows)) {
                    $this->gaReports[$key] = $report->rows;
                    $_SESSION['ga_reports'] = $this->gaReports;
                } else {
                    $this->gaReports[$key] = false;
                }

            }

            return $this->gaReports[$key];
        } else {
            throw new Exception('Report type Not Found!(' . $type . ')');
        }
    }

    public function getResultTitles()
    {
        if (!isset($_SESSION['ga_result_title'])) {
            foreach (SiteConfig::$gaResultType as $key => $ritem) {
                $_SESSION['ga_result_title'][$key] = $ritem['name'];
            }
        }

        return $_SESSION['ga_result_title'];
    }

    protected function isExpireToken()
    {
        return ($_SESSION['google_token']['created'] + $_SESSION['google_token']['expires_in'] - time()) <= 0;
    }

    protected function gatGaResultTypeParams($type)
    {
        return (isset(SiteConfig::$gaResultType[$type]) ? SiteConfig::$gaResultType[$type] : false);
    }
}