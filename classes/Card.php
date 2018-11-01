<?php
/**
 * Created by PhpStorm.
 * User: tony
 * Date: 11/1/18
 * Time: 11:36 AM
 */

class Card {

  var $color;
  var $text_color;

  function __construct($color, $text_color)
  {
    $this->color = $color;
    $this->text_color = $text_color;
  }
}