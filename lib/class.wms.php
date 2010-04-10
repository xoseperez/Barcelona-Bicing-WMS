<?php

require_once("class.bicing.php");
require_once("class.renderer.php");
require_once("common/wcts/class.wcts.php");

class wmsBicing extends bicing {

  // ******************************************************************

  function wmsDispatch() {
    
    try {

      $names = Array("request", "layers", "format", "srs", "bbox", 
        "width", "height", "query_layers", "info_format" , "x", "y", "feature_count");
      $parameters = Array();
      foreach ($names as $name) $parameters[$name] = $this->getParameter($name, false);

      switch (strtolower($parameters["request"])) {
        case "getcapabilities": $this->getCapabilities($parameters); break;
        case "getmap": $this->getMap($parameters); break;
        case "getfeatureinfo": $this->getFeatureInfo($parameters); break;
        case "getlegendgraphic": $this->getLegendGraphic($parameters); break;
        default: throw new Exception("Unknown request");
      }
      
    } catch (Exception $e) {
      array_push($this->errors, "[wmsBicing::wmsDispatch] ".$e->getMessage());
      return false;
    }

  }
  
  function getCapabilities($parameters) {
    array_push($this->errors, "[wmsBicing::getMap] Not yet implemented");
    return false;
  }
    
  function getMap($parameters) {

    try {

      // check parameters
      if (!$parameters["width"]) throw new Exception("WIDTH parameter missing");
      if (!$parameters["height"]) throw new Exception("HEIGHT parameter missing");
      if (!$parameters["format"]) throw new Exception("FORMAT parameter missing");
      if (!$parameters["bbox"]) throw new Exception("BBOX parameter missing");
      if (!$parameters["srs"]) throw new Exception("SRS parameter missing");

      // create image
      $image = new renderer($parameters["width"], $parameters["height"]);

      // create transformation object
      $wcts = new wcts();
      
      // get bbox
      $bbox = split(",", $parameters["bbox"]);
      $left = $bbox[0];
      $bottom = $bbox[1];
      $ratio_x = ($bbox[2] - $bbox[0]) / $parameters["width"];
      $ratio_y = ($bbox[3] - $bbox[1]) / $parameters["height"];
      
      // draw stations
      foreach ($this->stations as $station) {
        list($x, $y) = $wcts->ged2utm_datum($station->longitude, $station->latitude);
        $x = (int) (($x - $left) / $ratio_x);
        $y = (int) (($y - $bottom) / $ratio_y);
        $file = ( $station->bycicles > 0 ) ? "/media/Dades/srv/www/apps/geoservices/bicing/www/images/green.png" : "/media/Dades/srv/www/apps/geoservices/bicing/www/images/red.png";
        $image->draw("point", Array($x, $parameters["height"]-$y), "image", 32, false, false, $file);
      }

      // output image
      $image->output_image($parameters["format"]);

      // destroy image
      $image->close();

    } catch (Exception $e) {
      array_push($this->errors, "[wmsBicing::getMap] ".$e->getMessage());
      return false;
    }

   }
    
  function getFeatureInfo($parameters) {
    array_push($this->errors, "[wmsBicing::getMap] Not yet implemented");
    return false;
  }
    
  function getLegendGraphic($parameters) {
    array_push($this->errors, "[wmsBicing::getMap] Not yet implemented");
    return false;
  }
    
}
