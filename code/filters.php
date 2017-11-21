<?php

add_filter('wp_generate_attachment_metadata', 'dos_thumbnail_upload', 100, 1);

if ( get_option('dos_storage_file_delete') == 1 ) {
  add_filter('wp_delete_file', 'dos_storage_delete', 10, 1);
}