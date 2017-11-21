<?php

/**
 * Function registration css files
 */
function dos_styles() {

  wp_enqueue_style('dos-flexboxgrid', plugins_url('../assets/styles/flexboxgrid.min.css', __FILE__) );
  wp_enqueue_style('dos-core-css', plugins_url('../assets/styles/core.css', __FILE__) );

}