<?php

class station {

  private $_properties = array(
    'number' => null,
    'name' => null,
    'longitude' => null,
    'latitude' => null,
    'bycicles' => null,
    'freeplaces' => null
  );

  function __get($propertyName) {
    
    if(!array_key_exists($propertyName, $this->_properties)) throw new Exception("Invalid property name! ($propertyName)");

    if(method_exists($this, 'get' . $propertyName)) {
      return call_user_func(array($this, 'get' . $propertyName));
    } else {
      return $this->_properties[$propertyName];
    }
  
  }

  function __set($propertyName, $value) {
    
    if(!array_key_exists($propertyName, $this->_properties)) throw new Exception("Invalid property name! ($propertyName)");

    if(method_exists ($this, 'set' . $propertyName)) {
      return call_User_func(array($this, 'set'.$propertyName),$value);
    } else {
      $this->_properties[$propertyName] = $value;
    }

  }

}

