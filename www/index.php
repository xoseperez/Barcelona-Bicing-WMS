<?php

require_once("../lib/class.wms.php");

function showErrors($errors) {
  echo "<pre>";
  foreach ($errors as $error) echo $error."\n";
  echo "</pre>";
}

try {           

  $bicing = new wmsBicing();
  if (!$bicing->valid()) {
    showErrors($bicing->getErrors());
  } else {
    $bicing->wmsDispatch();
  }

} catch (Exception $e) {
  showErrors(Array($e->getMessage()));
}

$bicing = null;
