<?php

function dos_register_settings() {

  register_setting('dos_settings', 'dos_endpoint');
  register_setting('dos_settings', 'dos_container');
  register_setting('dos_settings', 'dos_secret');
  register_setting('dos_settings', 'dos_key');
  register_setting('dos_settings', 'dos_storage_url');
  register_setting('dos_settings', 'dos_storage_path');
  register_setting('dos_settings', 'dos_uploads_path');
  register_setting('dos_settings', 'dos_storage_file_only');
  register_setting('dos_settings', 'dos_storage_file_delete');
  register_setting('dos_settings', 'dos_lazy_upload');
  register_setting('dos_settings', 'dos_filter');
  register_setting('dos_settings', 'dos_debug');

}
