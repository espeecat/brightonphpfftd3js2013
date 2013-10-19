<?php
/**
 * Run this to connect to google GA and create json file of time series data
 * google2json.php 
 * This file connects to Google Analytics using the 
 * libs/google-api-php-client
 * 
 * You will need to set up a google Service email account
 * and use the Google private key provided.
 * 
 * See: http://stackoverflow.com/questions/10057928/oauth-2-0-with-google-analytics-api-v3
 * See: http://productforums.google.com/forum/#!topic/analytics/dRuAr1K4waI
 *   To get the profile ID. Double check this as it changes.
 * 
 * Jason Bailey Brighton PHP.
 * 
 */
date_default_timezone_set('Europe/London'); //not set in my php.ini oops 
// set paths for project
$google = "libs\google-api-php-client\src";
$path = $google;
set_include_path(get_include_path() . PATH_SEPARATOR . $path);
// include google API
require_once 'Google_Client.php';
require_once 'contrib/Google_AnalyticsService.php';
// GA to Json
require_once('SaveGoogleArrayToJson.php');

echo "file to get time series data from GA<br />";
$keyfile = 'mykey/ebce3xxxxxxxxxxxx1c6bb-privatekey.p12';
// Initialise the Google Client object
$client = new Google_Client();
$client->setApplicationName('Brighton PHP talk');
$client->setAssertionCredentials(
        new Google_AssertionCredentials(
        '3xxxxxxxxx0@developer.gserviceaccount.com', array('https://www.googleapis.com/auth/analytics.readonly'), file_get_contents($keyfile)
        )
);
$client->setClientId('3xxxxxxxx0.apps.googleusercontent.com');
$client->setAccessType('offline_access');
$analytics = new Google_AnalyticsService($client);

$analytics_id = 'ga:73020373';  // http://productforums.google.com/forum/#!topic/analytics/dRuAr1K4waI
// get data for the last 2 weeks
$lastWeek = date('Y-m-d', strtotime('-2 week'));
$today = date('Y-m-d');
// Test connection
try {
    $results = $analytics->data_ga->get($analytics_id, $lastWeek, $today, 'ga:visits');
    echo '<b>Number of visits this week:</b> ';
    echo $results['totalsForAllResults']['ga:visits'];
} catch (Exception $e) {
    echo 'There was an error : - ' . $e->getMessage();
}

// getting hourly data
/*
 * http://ga-dev-tools.appspot.com/explorer/?dimensions
 * 
 * Use GA dev tools to experiment with metrics and dimensions
 * 
 */

$metrics = "ga:visits"; // number of visits
$dimensions = "ga:date,ga:hour"; // by days, hours
$sort = "ga:date,ga:hour"; // order by date, time
$optParams = array('dimensions' => $dimensions, 'sort' => $sort);
try {
    $results = $analytics->data_ga->get($analytics_id, $lastWeek, $today, $metrics, $optParams);
} catch (Exception $e) {
    echo 'There was an error : - ' . $e->getMessage();
}
$data = $results ['rows']; // This is the time series data we want
// Save to Json
$saveGA = new SaveGoogleArrayToJson($data);
$saveGA->setXYData();
$x = $saveGA->getXData();
$y = $saveGA->getYData();

/**
 * y.json this is the time series data we want to analyse 
 * zero-mean-y.json this is the same data with the mean
 * value removed.
 */

$jx = $saveGA->setDataToJson($x);
$jy = $saveGA->setDataToJson($y);
echo '<br />producing y.json<br />';
$saveGA->saveStringtoFile($jy, 'y.json'); //Time series data in Json
// get zero mean
$saveGA->getYMean();
$zeroMean = $saveGA->removeMeanfromYdata();
$zM = $saveGA->setDataToJson($zeroMean);
echo 'producing zero-mean-y.json. see timeseries.html<br />';
$saveGA->saveStringtoFile($zM, 'zero-mean-y.json');  //Mean removed

$path = "libs";
set_include_path(get_include_path() . PATH_SEPARATOR . $path);

require_once 'fft/FFT.class.php';

// load json data into array
$string = file_get_contents('y.json');
$data = json_decode($string);

?>

