<?php

use Aws\S3\S3Client;
use League\Flysystem\AdapterInterface;
use League\Flysystem\AwsS3v3\AwsS3Adapter;
use League\Flysystem\Filesystem;
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

/**
 * Creates settings page and sets default options
 */
function dos_settings_page() {

  // Default settings
  if ( get_option('upload_path') == 'wp-content' . DIRECTORY_SEPARATOR . 'uploads' || get_option('upload_path') == null  ) {
    update_option('upload_path', WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'uploads');
  }

  if ( get_option('dos_endpoint') == null ) {
    update_option('dos_endpoint', 'https://ams3.digitaloceanspaces.com');
  }

  if ( get_option('dos_filter') == null ) {
    update_option('dos_filter', '*');
  }

  if ( get_option('dos_storage_path') == null ) {
    update_option('dos_storage_path', '/');
  }

  include_once('code/settings_page.php');

}

/**
 * Adds menu item for plugin
 */
function dos_create_menu(){

  add_options_page(
    'DO Spaces Sync',
    'DO Spaces Sync',
    'manage_options',
    __FILE__,
    'dos_settings_page'
  );

}

/**
 * Creates storage instance and returns it
 * 
 * @param  boolean $test
 * @return instance
 */
function __DOS( $test = false ) {

  if ( $test ) {

    // dos_key
    if ( isset( $_POST['dos_key'] ) ) {
      $dos_key = $_POST['dos_key'];
    } else { 
      $dos_key = get_option('dos_key');
    }

    // dos_secret
    if ( isset( $_POST['dos_secret'] ) ) {
      $dos_secret = $_POST['dos_secret'];
    } else {
      $dos_secret = get_option('dos_secret');
    }

    // dos_endpoint
    if ( isset( $_POST['dos_endpoint'] ) ) {
      $dos_endpoint = $_POST['dos_endpoint'];
    } else {
      $dos_endpoint = get_option('dos_endpoint');
    }

    // dos_container
    if ( isset( $_POST['dos_container'] ) ) {
      $dos_container = $_POST['dos_container'];
    } else {
      $dos_container = get_option('dos_container');
    }

  } else {
    $dos_key = get_option('dos_key');
    $dos_secret = get_option('dos_secret');
    $dos_endpoint = get_option('dos_endpoint');
    $dos_container = get_option('dos_container');
  }

  $client = S3Client::factory([
    'credentials' => [
      'key'    => $dos_key,
      'secret' => $dos_secret,
    ],
    'endpoint' => $dos_endpoint,
    'region' => 'ams3',
    'version' => 'latest',
  ]);

  $connection = new AwsS3Adapter($client, $dos_container);
  $filesystem = new Filesystem($connection);

  return $filesystem;

}

/**
 * Displays formatted message
 *
 * @param string $message
 * @param bool $errormsg = false
 */
function dos_show_message( $message, $errormsg = false ) {

  if ($errormsg) {

    echo '<div id="message" class="error">';

  } else {

    echo '<div id="message" class="updated fade">';

  }

  echo "<p><strong>$message</strong></p></div>";

}

/**
 * Tests connection to container
 */
function dos_test_connection() {

  try {
    
    $filesystem = __DOS( true );
    $filesystem->write('test.txt', 'test');
    $filesystem->delete('test.txt');
    dos_show_message(__('Connection is successfully established. Save the settings.', 'dos'));

    exit();

  } catch (Exception $e) {

    dos_show_message( __('Connection is not established.','dos') . ' : ' . $e->getMessage() . ($e->getCode() == 0 ? '' : ' - ' . $e->getCode() ), true);
    exit();

  }

}

/**
 * Trims an absolute path to relative
 *
 * @param string $file Full url path. Example /var/www/example.com/wm-content/uploads/2015/05/simple.jpg
 * @return string Short path. Example 2015/05/simple.jpg
 */
function dos_filepath( $file ) {

  $dir = get_option('upload_path');
  $file = str_replace($dir, '', $file);
  $file = get_option('dos_storage_path') . $file;
  $file = str_replace('\\', '/', $file);
  $file = str_replace('//', '/', $file);
  $file = str_replace(' ', '%20', $file);
  //$file = ltrim($file, '/');

  return $file;
}

/**
 * Returns data as a string
 *
 * @param mixed $data
 * @return string
 */
function dos_dump( $data ) {

  ob_start();
  print_r($data);
  $content = ob_get_contents();
  ob_end_clean();

  return $content;

}

/**
 * Uploads a file to storage
 * 
 * @param  string *Full path to upload file
 * @param  int Number of attempts to upload the file
 * @param  bool *Delete the file from the server after unloading
 * @return bool Successful load returns true, false otherwise
 */
function dos_file_upload( $pathToFile, $attempt = 0, $del = false ) {

  if (get_option('dos_debug') == 1) {

    $log = new Katzgrau\KLogger\Logger(
      plugin_dir_path(__FILE__) . '/logs', Psr\Log\LogLevel::DEBUG,
      array('prefix' => __FUNCTION__ . '_' . time() . '_', 'extension' => 'log')
    );

    if ($attempt > 0) {
      $log->notice('Attempt № ' . $attempt);
    }

  }

  try {

    if ( get_option('dos_debug') == 1 and isset($log) ) {

      $log->info("Path to thumbnail: " . $pathToFile);

      if ( dos_check_for_sync($pathToFile) ) {

        $log->info('File ' . $pathToFile . ' will be uploaded.');

      } else {

        $log->info('File ' . $pathToFile . ' does not fit the mask.');

      }
    }

    if ( ( is_readable($pathToFile) ) and ( dos_check_for_sync($pathToFile) ) ) {
      
      $filesystem = __DOS();

      $filesystem->put( dos_filepath($pathToFile), file_get_contents($pathToFile), [
        'visibility' => AdapterInterface::VISIBILITY_PUBLIC
      ]);

      if (get_option('dos_debug') == 1 and isset($log)) {
        $log->info("Instance - OK");
        $log->info("Name ObJ: " . dos_filepath($pathToFile));
        $log->info("Size: " . dos_dump($object->getSize()));
      }
      
    }

    return true;

  } catch (Exception $e) {

    if ( get_option('dos_debug') == 1 and isset($log) ) {
      $log->error($e->getCode() . ' :: ' . $e->getMessage());
    }

    if ( $attempt < 3 ) {
      wp_schedule_single_event(time() + 5, 'dos_schedule_upload', array($pathToFile, ++$attempt));
    }

    return false;

  }

}

/**
 * Deletes a file from local filesystem 
 * 
 * @param  string $file Absolute path to file
 * @param  integer $attempt Number of attempts to upload the file
 */
function dos_file_delete( $file, $attempt = 0 ) {

  if (file_exists($file)) {

    if (is_writable($file)) {

      if (get_option('dos_debug') == 1) {

        $log = new Katzgrau\KLogger\Logger(plugin_dir_path(__FILE__) . '/logs', Psr\Log\LogLevel::DEBUG,
          array('prefix' => __FUNCTION__ . '_', 'extension' => 'log'));

      }

      unlink($file);

      if (get_option('dos_debug') == 1 and isset($log)) {
        $log->info("File " . $file . ' deleted');
      }

    } elseif ($attempt < 3) {

      wp_schedule_single_event(time() + 10, 'dos_file_delete', array($file, ++$attempt));

    }

  }

}

/**
 * Upload files to storage
 *
 * @param int $postID Id upload file
 * @return bool
 */
function dos_storage_upload( $postID ) {

  if ( wp_attachment_is_image($postID) == false ) {

    $file = get_attached_file($postID);

    if ( get_option('dos_debug') == 1 ) {

      $log = new Katzgrau\KLogger\Logger(plugin_dir_path(__FILE__) . '/logs', Psr\Log\LogLevel::DEBUG,
        array('prefix' => __FUNCTION__ . '_', 'extension' => 'log'));
      $log->info('Starts unload file');
      $log->info('File path: ' . $file);
      //$log->info("MetaData: \n" . dos_dump($meta));

    }

    if ( get_option('dos_lazy_upload') == 1 ) {

      wp_schedule_single_event( time(), 'dos_schedule_upload', array($file));

    } else {

      dos_file_upload($file);

    }


  }

  return true;

}

/**
 * Deletes the file from storage
 * @param string $file Full path to file
 * @return string
 */
function dos_storage_delete( $file ) {

  try {

    if (get_option('dos_debug') == 1) {
      $log = new Katzgrau\KLogger\Logger(plugin_dir_path(__FILE__) . '/logs', Psr\Log\LogLevel::DEBUG,
        array('prefix' => __FUNCTION__ . '_', 'extension' => 'log'));
    }

    $filesystem = __DOS();

    $filesystem->delete( dos_filepath($file) );
    dos_file_delete($file);

    if (get_option('dos_debug') == 1 and isset($log)) {
      $log->info("Delete file:\n" . $file);
    }

    return $file;

  } catch (Exception $e) {

    return $file;

  }

}

/**
 * Uploads thumbnails using data from $metadata and adds schedule processes
 * @param array $metadata
 * @return array Returns $metadata array without changes
 */
function dos_thumbnail_upload( $metadata ) {

  if (get_option('dos_debug') == 1) {

    $log = new Katzgrau\KLogger\Logger(plugin_dir_path(__FILE__) . '/logs', Psr\Log\LogLevel::DEBUG,
      array('prefix' => __FUNCTION__ . '_', 'extension' => 'log'));
    $log->debug("Metadata dump:\n" . dos_dump($metadata));

  }

  if ( isset($metadata['file']) ) {

    $upload_dir = wp_upload_dir();
    $path = $upload_dir['path'] . DIRECTORY_SEPARATOR . basename($metadata['file']);

    if ( get_option('dos_lazy_upload') == 1 ) {

      wp_schedule_single_event(time() + 2, 'dos_schedule_upload', array($path, 0, true));

      if (get_option('dos_debug') == 1 and isset($log)) {
        $log->info("Add schedule. File - " . $path);
      }

    } else {

      dos_file_upload($path, 0, true);
      if (get_option('dos_debug') == 1 and isset($log)) {
        $log->info("Upload file - " . $path);
      }

    }
    
    if ( isset($metadata['sizes']) ) {

      foreach ( $metadata['sizes'] as $thumb ) {

        if ( isset($thumb['file']) ) {

          $path = $upload_dir['path'] . DIRECTORY_SEPARATOR . $thumb['file'];

          if ( get_option('dos_lazy_upload') == 1 ) {

            wp_schedule_single_event(time() + 2, 'dos_schedule_upload', array($path, 0, true));

            if ( get_option('dos_debug') == 1 and isset($log)) {
              $log->info("Add schedule. File - " . $path);
            }

          } else {

            dos_file_upload($path, 0, true);

            if (get_option('dos_debug') == 1 and isset($log)) {
              $log->info("Upload file - " . $path);
            }

          }

        }

      }

    }

  }

  if ( get_option('dos_debug') == 1 and isset($log) ) {

    $log->debug("Schedules dump: " . dos_dump(_get_cron_array()));

  }

  return $metadata;

}

/**
 * Checks directory for files. If there is no files returns true, otherwise null.
 * @param $dir
 * @return bool|null
 */
function dos_is_dir_empty( $dir ) {

  if ( !is_readable($dir) ) {

    return null;

  }

  return ( count( scandir($dir) ) == 2 );

}

/**
 * @param string $pattern
 * @param int $flags = 0
 *
 * @return array|false
 */
function dos_glob_recursive( $pattern, $flags = 0 ) {

  $files = glob($pattern, $flags);
  foreach (glob(dirname($pattern) . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR | GLOB_NOSORT) as $dir) {
    $files = array_merge($files, dos_glob_recursive($dir . DIRECTORY_SEPARATOR . basename($pattern), $flags));
  }

  return $files;

}

/**
 * Returns an array list of files in a directory $dir
 * @param string $dir
 * @return array
 */
function dos_get_files_arr( $dir ) {

  get_option('dos_filter') != '' ?
    $filter = trim(get_option('dos_filter')) :
    $filter = '*';

  $dir = rtrim($dir, '/');

  return array_filter(dos_glob_recursive($dir . DIRECTORY_SEPARATOR . '{' . $filter . '}', GLOB_BRACE), 'is_file');

}

/**
 * Faster search in an array with a large number of files
 * @param string $needle
 * @param array $haystack
 * @return bool
 */
function dos_in_array( $needle, $haystack ) {

  $flipped_haystack = array_flip($haystack);
  if (isset($flipped_haystack[$needle])) {
    return true;
  }

  return false;

}

/**
 * Checks if the file falls under the mask specified in the settings.
 * @param string @path Full path to file
 * @return bool
 */
function dos_check_for_sync( $path ) {

  get_option('dos_filter') != '' ?
    $mask = trim(get_option('dos_filter')) :
    $mask = '*';

  if (get_option('dos_debug') == 1) {

    $log = new Katzgrau\KLogger\Logger(plugin_dir_path(__FILE__) . '/logs', Psr\Log\LogLevel::DEBUG,
      array('prefix' => __FUNCTION__ . '_', 'extension' => 'log'));
    $log->info('File path: ' . $path);
    $log->info('Short path: ' . dos_filepath($path));
    $log->info('File mask: ' . $mask);

  }

  $dir = dirname($path);
  if (get_option('dos_debug') == 1 and isset($log)) {

    $log->info('Directory: ' . $dir);

  }

  $files = glob($dir . DIRECTORY_SEPARATOR . '{' . $mask . '}', GLOB_BRACE);
  if (get_option('dos_debug') == 1 and isset($log)) {
    $log->debug("Files dump (full name):\n" . dos_dump($files));
  }

  $count = count($files) - 1;
  for ($i = 0; $i <= $count; $i++) {
    $files[$i] = dos_filepath($files[$i]);
  }

  if (get_option('dos_debug') == 1 and isset($log)) {
    $log->debug("Files dump (full name):\n" . dos_dump($files));
  }

  //$result = in_array(dos_filepath($path), $files,true);
  $result = dos_in_array(dos_filepath($path), $files);
  if (get_option('dos_debug') == 1 and isset($log)) {
    $result ? $log->info('Path found in files') : $log->info('Path not found in files');
  }

  return $result;

}

/**
 * Includes
 */
include_once('code/styles.php');
include_once('code/scripts.php');
include_once('code/settings.php');
include_once('code/actions.php');
include_once('code/filters.php');