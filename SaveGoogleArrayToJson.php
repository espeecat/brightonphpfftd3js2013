<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class SaveGoogleArrayToJson {

    private $array = array();
    private $xData = array();
    private $yData = array();
    private $xyData = array();

    public function __construct(Array $data) {
        $this->array = $data;
        $this->setXYData();
    }

    public function setXYData() {

        // might be able to use array_column if I get apply changes
        // in a functional way
        foreach ($this->array as $key => $value) {

            $this->yData[] = $value[2];
            $x = $value[0] . " " . $value[1] . "00"; //20130922 1400
            $xt = $this->setXDataToTime($x);
            $this->xData[] = $xt;
            $this->xyData[] = array($xt, $value[2]);
        }
    }

    public function getXData() {
        return $this->xData;
    }

    public function getYData() {
        return $this->yData;
    }

    public function getXYData() {
        return $this->xyData;
    }

// conver x data to dateTime OBject
    public function setXDataToTime($timeString) {
        $dateTime = DateTime::createFromFormat('Ymj Hi', $timeString);
        return $dateTime;
    }

//save x,y to json file
    public function setDataToJson($array) {
        return json_encode($array);
    }

    public function getDataFromJson($jsonData) {
        return json_decode($jsonData);
    }

    public function saveStringtoFile($string, $filename) {
        $fp = fopen($filename, 'w');
        fwrite($fp, $string);
        fclose($fp);
    }

    public function loadFiletoJson($filename) {
        $string = file_get_contents($filename);
        $json_a = json_decode($string, true);
        return $json_a;
    }

    public function getYMean() {
        $sum = (int) array_sum($this->yData);
        $count = (int) count($this->yData);

        $this->yMean = $sum / $count;
        return $this->yMean;
    }

    public function removeMeanfromYdata() {
        // do it with a lambda
        $zeroMean = $this->yMean;
        $func = function($value) use ($zeroMean) {
            return $value - $zeroMean;
        };
        return $this->yZeroMean = array_map($func, $this->yData);
    }

}
