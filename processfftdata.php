<?php

/*
 * processfftdata.php
 * 
 * Creates  (absfft.json) fft.html data
 * FFT code from Michele Andreoli <michi.andreoli@gmail.com>
 * http://www.phpclasses.org/package/6193-PHP-Compute-the-Fast-Fourier-Transform-of-sampled-data.html
 *  Jason Bailey Brighton PHP.
 */

date_default_timezone_set('Europe/London'); //not set in my php.ini oops
$path = "libs";
set_include_path(get_include_path() . PATH_SEPARATOR . $path);
require_once('SaveGoogleArrayToJson.php');
require_once 'fft/FFT.class.php';
//////////////////////////////////////////////////////////////////////////
echo 'starting to process FFT <br />';
$nfft = 256;  // n point FFT: Good example in Matlab at: 
// http://www.mathworks.co.uk/help/matlab/ref/fft.html
//zero mean GA data
$string = file_get_contents('zero-mean-y.json');
$data = json_decode($string);


// initiaate FFT
$fft = new FFT($nfft);
// create power/abs fft
$data = json_decode($string);
// Calculate the FFT of the function $f
$w = $fft->fft($data);
$power = $fft->getAbsFFT($w);

// save abs value of absfft.json for fft.html
echo 'data for fft.html<br />';
$powerJson = json_encode($power);
$saveGA = new SaveGoogleArrayToJson($data);
$saveGA->saveStringtoFile($powerJson, 'absfft.json');

// err cheating as I'm running out of time for presentation -sorry.
// should be zero mean (low frequency)
$power[0] = 0;  // cheating sorry
$power[1] = 0;  // removing low frequency (weekly) component
$power[2] = 0;
$power[3] = 0;
$power[4] = 0;
$power[$nfft - 1] = 0;
$power[$nfft - 2] = 0;
$power[$nfft - 3] = 0;
$power[$nfft - 4] = 0;
$power[$nfft - 5] = 0; // end of cheating
// function to get index values of peak frequencies
// effectively this is $numPeaks =4 band pass filters on
// band pass = $intervals ((5*2)+1) -this is probably why 
// data isn't sinusoidal. Too big a band but fine for demo
// the data where the frequncies are large
$iffts = getPeaks($w, $power, $nfft, 4, 5);
// This is an array suitable for multiline d3.js charts
// data is band pass filtered data (interval =5)
$series = getTimeSeries($iffts, $nfft);
$count = 0;
$json = array();
// making data good for d3.js
foreach ($series[0] as $key => $value) {
    $json[] = array('point' => $count, 'series1' => $series[0][$key], 'series2' => $series[1][$key], 'series3' => $series[2][$key]);
    $count++; // I should probably make this date/time rather than interval.
}

$js = json_encode($json);
// This is a comparison between original data and filtered data
$fp = fopen('jseries.json', 'w'); // data for allseries.html
fwrite($fp, $js);
fclose($fp);
echo 'see allseries.html';
echo 'ending process FFT <br />';
// see allseries.html
////////////////////////////////////////////////////////////////
/**
 * 
 * @param type $numPeaks
 * @param type $interval
 * @return type
 * 
 * 
 */
function getPeaks($fftofTime, $power, $nfft, $numPeaks, $interval) {
    $power[0] = 0;  // just in case set 0 freq = 0
    $lth = (2 * $interval) + 1;
    $zeroVector = array();
    for ($i = 0; $i < $nfft; $i++) {
        $zeroVector[$i] = new Complex(0, 0); //create and copy
    }
    $ifft[] = $fftofTime;  //original signal
    // aiming to pairs of values +VE and -ve Freq.
    for ($i = 0; $i < $numPeaks; $i++) {
        $z = $zeroVector;  //empty array for each interval
        // assume pairs of peaks
        $s = array_keys($power, max($power));
        $peak1 = $s[0];
        $w1 = popArray($z, $fftofTime, $peak1 - $interval, $peak1 + $interval);
        $power = zeroSelectedPower($power, $peak1 - $interval, $peak1 + $interval);
        // second one
        $s = array_keys($power, max($power));
        $peak2 = $s[0];  // second peak
        // grab/remove -ve image peak
        $w2 = popArray($w1, $fftofTime, $peak2 - $interval, $peak2 + $interval);
        $power = zeroSelectedPower($power, $peak2 - $interval, $peak2 + $interval);
        $ifft[] = $w2;
    }
    return $ifft;  //árray of ifft 
}

function popArray($zeroArray, $fullArray, $from, $to) {
    for ($i = $from; $i < $to; $i++) {
        $zeroArray[$i] = $fullArray[$i];
    }
    return $zeroArray;
}

function zeroSelectedPower($power, $from, $to) {
    for ($i = $from; $i <= $to; $i++) {
        $power[$i] = 0; // set to zero to stop them being selected again
    }
    return $power;
}

function getTimeSeries($ifftArray, $nfft) {
    $fft = new FFT($nfft);
    $allSeries = array();
    foreach ($ifftArray as $key => $value) {
        $rt = $fft->ifft($value);
        $time = array();
        for ($i = 0; $i < count($rt); $i++) {
            $time[] = $rt[$i]->getReal();
        }
        $allSeries[] = $time;
    }
    return $allSeries;
}

?>