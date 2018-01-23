<?php

add_action('admin_menu', 'dos_create_menu');
add_action('admin_init', 'dos_register_settings');

add_action('add_attachment', 'dos_storage_upload', 100, 1);

add_action('admin_enqueue_scripts', 'dos_styles');
add_action('admin_enqueue_scripts', 'dos_scripts');

add_action('wp_ajax_dos_test_connection', 'dos_test_connection');

add_action('dos_file_delete', 'dos_file_delete');
add_action('dos_schedule_upload', 'dos_file_upload');