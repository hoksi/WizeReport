<?php

/**
 * Created by IntelliJ IDEA.
 * User: hoksi
 * Date: 2017-06-06
 * Time: 오후 4:36
 */
class GaPain_test extends hoksi\Toast
{
    protected $analytics;
    protected $profileId;

    public function __construct()
    {
        parent::__construct();
    }


    public function testGaResult()
    {
        var_dump($this->initializeAnalytics()->getFirstProfileId()->getResults());

        $this->assertTrue(false);
    }

    protected function initializeAnalytics()
    {
        // Start a session to persist credentials.
        session_start();

        // Creates and returns the Analytics Reporting service object.

        // Use the developers console and download your service account
        // credentials in JSON format. Place them in this directory or
        // change the key file location if necessary.

        // Create and configure a new client object.
        $client = new Google_Client();
        $client->setApplicationName("Hello Analytics Reporting");
        $client->setAuthConfig(APPPATH . 'data/client_secret.json');
        $client->setScopes(['https://www.googleapis.com/auth/analytics.readonly']);
        $this->analytics = new Google_Service_Analytics($client);

        return $this;
    }

    function getFirstProfileId()
    {
        // Get the user's first view (profile) ID.

        // Get the list of accounts for the authorized user.
        $accounts = $this->analytics->management_accounts->listManagementAccounts();

        if (count($accounts->getItems()) > 0) {
            $items = $accounts->getItems();
            $firstAccountId = $items[0]->getId();

            // Get the list of properties for the authorized user.
            $properties = $analytics->management_webproperties
                ->listManagementWebproperties($firstAccountId);

            if (count($properties->getItems()) > 0) {
                $items = $properties->getItems();
                $firstPropertyId = $items[0]->getId();

                // Get the list of views (profiles) for the authorized user.
                $profiles = $analytics->management_profiles
                    ->listManagementProfiles($firstAccountId, $firstPropertyId);

                if (count($profiles->getItems()) > 0) {
                    $items = $profiles->getItems();

                    // Return the first view (profile) ID.
                    $this->profileId = $items[0]->getId();

                    return $this;

                } else {
                    throw new Exception('No views (profiles) found for this user.');
                }
            } else {
                throw new Exception('No properties found for this user.');
            }
        } else {
            throw new Exception('No accounts found for this user.');
        }
    }

    function getResults()
    {
        // Calls the Core Reporting API and queries for the number of sessions
        // for the last seven days.
        return $this->analytics->data_ga->get(
            'ga:' . $this->profileId,
            '7daysAgo',
            'today',
            'ga:sessions');
    }

    function printResults($results)
    {
        // Parses the response from the Core Reporting API and prints
        // the profile name and total sessions.
        if (count($results->getRows()) > 0) {

            // Get the profile name.
            $profileName = $results->getProfileInfo()->getProfileName();

            // Get the entry for the first entry in the first row.
            $rows = $results->getRows();
            $sessions = $rows[0][0];

            // Print the results.
            print "First view (profile) found: $profileName\n";
            print "Total sessions: $sessions\n";
        } else {
            print "No results found.\n";
        }
    }

}