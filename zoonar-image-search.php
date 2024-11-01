<?php
define('ZOONAR_IMAGE_BASE_URL', 'https://www.zoonar.express');

// Need to require these files
if( !function_exists('media_handle_upload') ) 
  {
  require_once(ABSPATH . "wp-admin" . '/includes/image.php');
  require_once(ABSPATH . "wp-admin" . '/includes/file.php');
  require_once(ABSPATH . "wp-admin" . '/includes/media.php');
  require_once(ABSPATH . '/wp-includes/pluggable.php');
  }

defined( 'ABSPATH' ) or die( 'All your base are belong to us!' );
/*
Plugin Name: Zoonar Image Search
Plugin URI: https://www.zoonar.express/zoonar-image-search
Description: Find inexpensive royalty free photos from Zoonar. Purchase licenses directly and import image files conveniently into wordpress media gallery. Yay!
Text Domain: zoonar-image-search
Version: 1.0.0
Author: Zoonar GmbH / Marcel Horbach
Author URI: https://www.zoonar.express
License: GPL2
*/

/*  Copyright 2021  Zoonar GmbH / Marcel Horbach (email: mhorbach@zoonar.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

define( "ZOONAR_VERSION", "1.0.0");
define( "ZOONAR_PLUGIN_NAME", "Zoonar Image Search");

if( isset( $_POST['action'] ) )
  {
  if( $_POST['action'] == 'send_to_gallery' )
    zoonar_send_to_gallery();
  if( $_POST['action'] == 'get_gallery_images' )
    zoonar_get_gallery_images();
  }

function zoonar_send_to_gallery()
  {
  $request_status = 'done';

  if( isset( $_POST['at'] ) && isset( $_POST['zat'] ) && isset( $_POST['aat'] ) && is_numeric( $_POST['id'] ) )
    {
    $sanitized_at  = zoonar_sanitize_key( $_POST['at'] );
    $sanitized_zat = zoonar_sanitize_key( $_POST['zat'] );
    $sanitized_aat = zoonar_sanitize_key( $_POST['aat'] );
    $sanitited_id  = intval( $_POST['id'] );

    $request_body  = array('at'=>$sanitized_at, 'zat'=>$sanitized_zat, 'aat'=>$sanitized_aat, 'id'=>$sanitited_id);

    // get download token
    $args = Array('method'=>'POST', 'headers' => array( 'Content-type: application/x-www-form-urlencoded' ), 'body'=>$request_body);
    $token_response = wp_remote_post( ZOONAR_IMAGE_BASE_URL . '/api/user/get_download_token', $args );
    
    if( isset( $token_response['response']['code'] ) && $token_response['response']['code'] == 200 )
      {
      $json = json_decode( $token_response['body'], 1 );
      if( $json['result']['key'] )
        {
        $url = ZOONAR_IMAGE_BASE_URL . '/api/user/media/' . $json['result']['key'];
        $tmp = download_url( $url );
        $file_array = array( 'name' => 'zoonar_' . $sanitited_id . '.jpg', 'tmp_name' => $tmp );
         
        if( is_wp_error( $tmp ) )
          {
          @unlink( $file_array[ 'tmp_name' ] );
          $request_status = 'download_error';
          }

        $id = media_handle_sideload( $file_array, 0 );
        if ( is_wp_error( $id ) )
          {
          @unlink( $file_array['tmp_name'] );
          $request_status = 'upload_error';
          }
         
        wp_update_post( array( 'ID' => $id, 'post_title' => $json['result']['title'], 'post_name' => 'zoonar_' . $sanitited_id ) );
        }
      }
    else
      {
      $request_status = 'error';
      }
    }
  else
    {
    $request_status = 'error';
    }

  $result = array();
  $result['status'] = $request_status;

  header("Content-Type: application/json; charset=utf-8");
  header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
  header("Cache-Control: post-check=0, pre-check=0", false);
  header("Pragma: no-cache");
  header("Expires: Wed, 24 Dec 2008 14:12:00 GMT");
  echo json_encode($result);
  exit();
  }

function zoonar_get_gallery_images()
  {
  global $wpdb;
  $result = array('images'=>array() );
  $images = $wpdb->get_results("select post_name from $wpdb->posts where `post_type`='attachment' and `post_name` like 'zoonar_%'", "ARRAY_N");
  
  if( is_array($images) && count($images))
    {
    foreach( $images as $image )
      {
      $tmp = str_replace( 'zoonar_', '', trim($image[0]) );
      list( $id ) = explode("-", $tmp);
      $img_id = intval( $id );
      if( $img_id > 0 && ! in_array( $img_id, $result["images"] ) )
        $result["images"][] = $img_id;
      }
    }

  header("Content-Type: application/json; charset=utf-8");
  header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
  header("Cache-Control: post-check=0, pre-check=0", false);
  header("Pragma: no-cache");
  header("Expires: Wed, 24 Dec 2008 14:12:00 GMT");
  echo json_encode($result);
  exit();
  }

function zoonar_plugin_setup_menu()
  {
  add_menu_page( 'Zoonar Image Search', 'Zoonar Images', 'manage_options', 'zoonar-image-search', 'show_zoonar_page', plugins_url('zoonar-image-search') . '/images/zoonar-favicon.png' );
  }
 
function show_zoonar_page()
  {
  wp_enqueue_script( 'zoonar-image-search-js', plugins_url( '/js/zoonar_image_search.js', __FILE__ ));
  require(dirname(__FILE__) . "/html/show_overview.inc.php");
  }
 
function zoonar_sanitize_key( $key ) 
  {
  $raw_key = $key;
  //$key     = strtolower( $key );
  $key     = preg_replace( '/[^a-zA-Z0-9]/', '', $key );

  /**
   * Filters a sanitized key string.
   *
   * @since 3.0.0
   *
   * @param string $key     Sanitized key.
   * @param string $raw_key The key prior to sanitization.
   */
  return apply_filters( 'sanitize_key', $key, $raw_key );
}

add_action( 'admin_menu', 'zoonar_plugin_setup_menu' );
