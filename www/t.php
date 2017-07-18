<?php
session_start();
error_reporting(-1);
ini_set('display_errors', 1);

// Load the Google API PHP Client Library.
require_once '../_core/vendor/autoload.php';

$analytics = initializeAnalytics();
$profile = getFirstProfileId($analytics);
$results = getResults($analytics, $profile);
printResults($results);

function initializeAnalytics()
{
    // Creates and returns the Analytics Reporting service object.

    // Use the developers console and download your service account
    // credentials in JSON format. Place them in this directory or
    // change the key file location if necessary.
    $KEY_FILE_LOCATION = '../_core/app/data/client_id.json';

    // Create and configure a new client object.
    $client = new Google_Client();

    $client->setApplicationName("WizeReport");
    $client->setAuthConfig($KEY_FILE_LOCATION);
    $client->setScopes(['https://www.googleapis.com/auth/analytics.readonly']);
//    $client->authenticate($_SESSION['google_code']);
    $access_token = $_SESSION['google_token'];

    if($access_token) {
        $client->setAccessToken($access_token);
        $analytics = new Google_Service_Analytics($client);

        return $analytics;
    } else {
        $_SESSION['go_url'] = '/t.php';

        var_dump($_SESSION);
        // header('Location: /welcome/login');
    }

}

function getFirstProfileId($analytics) {
    // Get the user's first view (profile) ID.

    // Get the list of accounts for the authorized user.
    $accounts = $analytics->management_accounts->listManagementAccounts();

    if (count($accounts->getItems()) > 0) {
        $items = $accounts->getItems();

        foreach($items as $item) {
            echo $item->getName(), '<br/>';
        }

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
                return $items[0]->getId();

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

function getResults($analytics, $profileId) {
    // Calls the Core Reporting API and queries for the number of sessions
    // for the last seven days.
    return $analytics->data_ga->get(
        'ga:' . $profileId,
        '7daysAgo',
        'today',
        'ga:sessions');
}

function printResults($results) {
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

