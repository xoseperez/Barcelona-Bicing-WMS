<?php

//************************************************************************
//
// DB2P Server
// ============================================
//
// Copyright (c) 2005 by Xose (xose at eldiariblau dot net)
// http://www.eldiariblau.net
//
// Image support class
//
// This program is free software. You can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 2 of the License.
//
//************************************************************************

class renderer {

  //************************************************************************
  // Class variables
  //************************************************************************

  var $image;
  var $mark;
  var $mark_file;
  
  //************************************************************************
  // Constructor
  //************************************************************************

  function renderer($width, $height, $transparent = "0,0,0") {
    $this->image = $this->_create_transparent_image($width, $height, $transparent);
  }
  
  //************************************************************************
  // Public functions
  //************************************************************************

  function draw($type, $points, $symbol, $size, $color = null, $outlinecolor = null, $file = "") {

      // get color
      if ($color) {
        if (!is_array($color)) $color = split(",",$color);
        $color = imagecolorallocate($this->image, $color[0], $color[1], $color[2]);
      }
      
      // get outline color
      if ($outlinecolor) {
        if (!is_array($outlinecolor)) $outlinecolor = split(",",$outlinecolor);
        
        $outlinecolor = imagecolorallocate($this->image, $outlinecolor[0], $outlinecolor[1], $outlinecolor[2]);
      }

      // draw element
      switch ("$type.$symbol") {
        case 'point.rectangle':
          return $this->_draw_rectangle($points, $size, $color, $outlinecolor);
          break;
        case 'point.triangle':
          return $this->_draw_triangle($points, $size, $color, $outlinecolor);
          break;
        case 'point.circle':
          return $this->_draw_circle($points, $size, $color, $outlinecolor);
          break;
        case 'point.image':
          return $this->_draw_image($points, $size, $file);
          break;
        default:
          return false;
      }

  }

  function output_image($format) {

    @imagetruecolortopalette($this->image, true, 256);
    
    header("Content-type:".$format);
    switch ($format) {
      case "image/png": imagepng($this->image); break;
      case "image/gif": imagegif($this->image); break;
      case "image/jpeg": imagejpeg($this->image); break;
      default: return false;
    }
    
    return true;

  }

  function close() {
    if ($this->image) imagedestroy($this->image);
    if ($this->mark) imagedestroy($this->mark);
  }

  //************************************************************************
  // Symbol functions
  //************************************************************************

  function _draw_rectangle($points, $size, $color, $outlinecolor) {

    // point offset
    $offset = (int) ($size / 2);
    if ($outlinecolor) {
      $sizeout = $size + 2;
      $offsetout = (int) ($sizeout / 2);
    }

    // for each x/y pair
    for ($i=0; $i<count($points); $i+=2){
      if ($outlinecolor) {
        @imagefilledrectangle($this->image,
          $points[$i] - $offsetout, $points[$i+1] - $offsetout,
          $points[$i] + $sizeout-1, $points[$i+1] + $sizeout-1, $outlinecolor);
      }
      @imagefilledrectangle($this->image,
        $points[$i] - $offset, $points[$i+1] - $offset,
        $points[$i] + $size, $points[$i+1] + $size, $color);
    }
    return true;

  }

  function _draw_circle($points, $size, $color, $outlinecolor) {
    // for each x/y pair
    for ($i=0; $i<count($points); $i+=2){
      if ($outlinecolor) @imagefilledellipse($this->image, $points[$i], $points[$i+1], $size+2, $size+2,$outlinecolor);
      @imagefilledellipse($this->image, $points[$i], $points[$i+1], $size, $size, $color);
      
    }
    return true;

  }

  function _draw_triangle($points, $size, $color, $outlinecolor) {

    // for each x/y pair
    for ($i=0; $i<count($points); $i+=2) {
      if ($outlinecolor) {
        $sizeout = $size + 2;
        $polygon = Array();
        $polygon[] = (int) $points[$i];
        $polygon[] = (int) ($points[$i+1] - $sizeout);
        $polygon[] = (int) ($points[$i] + $sizeout * 0.866);
        $polygon[] = (int) ($points[$i+1] + $sizeout * 0.5);
        $polygon[] = (int) ($points[$i] - $sizeout * 0.866);
        $polygon[] = (int) ($points[$i+1] + $sizeout * 0.5);
        @imagefilledpolygon($this->image, $polygon, 3, $outlinecolor);      
      }
      $polygon = Array();
      $polygon[] = (int) $points[$i];
      $polygon[] = (int) ($points[$i+1] - $size);
      $polygon[] = (int) ($points[$i] + $size * 0.866);
      $polygon[] = (int) ($points[$i+1] + $size * 0.5);
      $polygon[] = (int) ($points[$i] - $size * 0.866);
      $polygon[] = (int) ($points[$i+1] + $size * 0.5);
      @imagefilledpolygon($this->image, $polygon, 3, $color);
    }
    return true;

  }

  function _draw_image($points, $size, $file) {

    if ($this->mark_file != $file) {
      $info = @getimagesize($file);
      $this->mark = null;
      switch ($info[2]) {
        case 1: $this->mark = @imagecreatefromgif($file); break;
        case 2: $this->mark = @imagecreatefromjpeg($file); break;
        case 3: $this->mark = @imagecreatefrompng($file); break;
        default: return false;
      }
      $this->mark_file = $file;

    }
    
    if ($this->mark) {

      // calculate image output size and offset
      $mark_width = @imagesx($this->mark);
      $mark_height = @imagesy($this->mark);
      if ($size == -1) {
        $width = $mark_width;
        $height = $mark_height;
      } else {
        if ($mark_width > $mark_height) {
          $width = $size;
          $height = (int) ( $size * $mark_height / $mark_width);
        } else {
          $height = $size;
          $width = (int) ( $size * $mark_width / $mark_height);
        }
      }
      $offset_x = (int) ($width / 2);
      $offset_y = (int) ($height / 2);
      
      // output image
      for ($i=0; $i<count($points); $i+=2)
        @imagecopyresampled($this->image, $this->mark,
          $points[$i] - $offset_x, $points[$i+1] - $offset_y, 0, 0,
          $width, $height, $mark_width, $mark_height);
      return true;

    }

    return false;

  }

  //************************************************************************
  // Private functions
  //************************************************************************

  function _create_transparent_image($width, $height, $transparent) {
    $image = @imagecreatetruecolor($width, $height);
    $color = split(",", $transparent);
    $color = @imagecolorallocate($image, $color[0], $color[1], $color[2]);
    @imagefill($image, 0, 0, $color);
    @imagecolortransparent($image, $color);
    return $image;
  }

}
