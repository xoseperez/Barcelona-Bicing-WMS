<?php

require_once("class.curl.php");
require_once("class.station.php");

class bicing implements Iterator {

  private $index = 0;

  protected $stations = Array();
  protected $errors = Array();
  
  function __construct() {
    
    try {

      if (!$this->load()) throw new Exception("Unable to get data from www.bicing.com.");
      return true;
    
    } catch (Exception $e) {
      array_push($this->errors, "[bicing::__contruct] ".$e->getMessage());
      return false;
    }
    
  }
  
  function __destruct() {
    
    $this->stations = null;

  }
    
  function getErrors() { return $this->errors; }
  
  function load() {
  
    try {           

      // Get CURL connection objectbyanchor
      $c = new CurlRequest();
      
      // Descarregar la pàgina
      $params = array('url' => 'http://www.bicing.com/localizaciones/localizaciones.php',
        'host' => '',
        'header' => '',
        'method' => 'GET',
        'referer' => '',
        'cookie' => '',
        'post_fields' => '',
        'timeout' => 20
      );

      $c->init($params);
      $result = $c->exec();
      if ($result['curl_error']) throw new Exception($result['curl_error']);
      if ($result['http_code']!='200') throw new Exception("HTTP Code = ".$result['http_code']);
      if (!$result['body']) throw new Exception("Body of file is empty");

      // Parsejar-la per obtenir el KML
      $inici = strpos($result['body'], "<kml");
      $final = strpos($result['body'], "</kml>", $inici);
      $kml = substr($result['body'], $inici, $final - $inici + 6);

      // Parsejar el KML per obtenir coordenades i # bicings lliures i espais lliures
      $kml = utf8_encode($kml);
      $kml = str_replace("<br />", ",", $kml);
      $kml_object = simplexml_load_string($kml);
      $kml_stations = $kml_object->Document->Placemark;

      // Guardar els resultats
      $stations = Array();
      foreach ($kml_stations as $kml_station) {
        $station = new station();
        $description = $kml_station->description;
        $sdesc = simplexml_load_string($description);
        $station->name = $sdesc->div[0];
        $bicis = $sdesc->div[2];
        list($station->bycicles, $station->freeplaces) = split(",", $bicis);
        $coordinates = $kml_station->Point->coordinates;
        list($station->longitude,$station->latitude) = split(",", $coordinates);  
        array_push($this->stations, $station);
      }
      
      $this->index = 0;
      return true;
      
    } catch (Exception $e) {
      array_push($this->errors, "[bicing::load] ".$e->getMessage());
      return false;
    }
  
  }  
   
  // ******************************************************************
  
  function count() {
    return count($this->stations);
  }
  
  function moveto($index) {
    if ($index>=0 && $index<count($this->stations)) {
      $this->index = $index;
      return true;
    }
    return false;
  }
  
  function next() {
    $this->index++;
  }
  
  function rewind() {
    $this->index = 0;
  }
  
  function valid() {
    return ($this->index >= 0 && $this->index < count($this->stations));
  }
  
  function key() {
    return $this->index;
  }
  
  function current() {
    if ($this->valid()) return $this->stations[$this->index];
    return false;
  }
  
  // ******************************************************************
  
  function getParameter($name, $default = null) {
    reset($_REQUEST);
    while (list($key, $value) = each($_REQUEST)) {
      if (strcasecmp($key, $name) == 0) return $value;
    }
    return $default;
  }

}
