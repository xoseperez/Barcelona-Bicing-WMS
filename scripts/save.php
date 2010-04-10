<?php

require_once("../lib/class.bicing.php");
require_once("../lib/class.wcts.php");
require_once("../lib/class.mysql.php");

function showErrors($errors) {
  echo "<pre>";
  foreach ($errors as $error) echo $error."\n";
  echo "</pre>";
}

function getMeasure(&$db) {
  $id = false;
  $sql = "INSERT INTO measures () VALUES ();";
  if ($db->query($sql)) $id = $db->get_id();
  return $id;
}

function getStation(&$db, $name, $x, $y) {
    
  $id = false;
  $hid = $x."|".$y;
  
  // Try to get station id
  $sql = "SELECT id FROM stations WHERE hid='$hid';";
  if ($rs = $db->query($sql)) {
    if ($record = $db->fetch_array($rs)) {
      $id = $record["id"];
    } else {
      $sql = sprintf("INSERT INTO stations (hid, name, utmx, utmy) VALUES ('$hid', '%s', $x, $y);", mysql_real_escape_string($name));
      if ($db->query($sql)) $id = $db->get_id();
    }
    $db->free($rs);
  }
  
  return $id;
      
}

try {           

  $bicing = new bicing();
  if ($bicing->valid() == 0) {
    showErrors($bicing->getErrors());
  } else {

    // create transformation object
    $wcts = new wcts();
    
    // create db object
    $db = new database("localhost", "bicing", "bicing", "b3c3ng");
    if (!$db->connect()) throw new Exception("Could not connect to database");

    // get measure
    $measure_id = getMeasure($db);

    // walk results
    foreach($bicing as $station) {

      // get UTM
      list($x, $y) = $wcts->ged2utm_datum($station->longitude, $station->latitude);
      $x = (int) $x;
      $y = (int) $y;
      
      // get (and put) station id
      $station_id = getStation($db, $station->name, $x, $y);
      
      // insert data
      $sql = "INSERT INTO data (measure, station, bycicles, freeplaces) VALUES ($measure_id, $station_id, ".$station->bycicles.", ".$station->freeplaces.");";
      $db->query($sql);

    }
    
    $db->close();


  }

} catch (Exception $e) {
  showErrors(Array($e->getMessage()));
}

$bicing = null;
