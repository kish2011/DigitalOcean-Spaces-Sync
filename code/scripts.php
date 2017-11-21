<?php

/**
 * Function registration js files
 */
function dos_scripts() {

  wp_enqueue_script('dos-core-js', plugins_url('../assets/scripts/core.js', __FILE__), array('jquery'), '1.4.0', true);

}