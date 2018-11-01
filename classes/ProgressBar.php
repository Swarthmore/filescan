<?php
/**
 * Created by PhpStorm.
 * User: tony
 * Date: 11/1/18
 * Time: 11:39 AM
 */

/**
 * ProgressBar Class
 * @param $width
 * @param $completed_color
 * @param $incomplete_color
 */

class ProgressBar {

  public function __construct($width, $completed_color, $incomplete_color)
  {
    $this->width = $width;
    $this->completed_color = $completed_color;
    $this->incomplete_color = $incomplete_color;
  }

  /**
   * @return string
   */
  function draw() {
    $output = '';

    return $output;
  }

}