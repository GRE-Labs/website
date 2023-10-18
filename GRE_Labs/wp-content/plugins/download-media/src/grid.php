<?php

namespace JDD;

defined( 'ABSPATH' ) || die();

/**
 * Plugin handling the grid media library.
 *
 * @since 1.2
 */
class DownloadMedia_Grid {
  /**
   * This plugin's version number. Used for busting caches.
   *
   * @var string
   */
  public $version = '1.4.2';

  /**
   * The capability required to download a media.
   * Please don't change this directly. Use the "download_media_download_cap" filter instead.
   *
   * @var string
   */
  public $capability_download = 'upload_files';

  /**
   * This plugin's prefix
   *
   * @var string
   */
  private $prefix = 'dm_';

  public function __construct() {
    $this->capability_download = apply_filters( 'download_media_download_cap', $this->capability_download );

    add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
    add_action( 'wp_ajax_generate_link_for_media', array( $this, 'generate_link_for_media' ) );
  }

  function admin_enqueue_scripts($hook_suffix){
    if($hook_suffix === 'upload.php' && ( ( isset($_GET['mode']) && $_GET['mode'] === 'grid' ) || !isset($_GET['mode']) ) ){
      wp_enqueue_style( $this->prefix . 'style', plugin_dir_url(__DIR__) . DIRECTORY_SEPARATOR . 'dist' . DIRECTORY_SEPARATOR . 'style.css', array(), $this->version );
      wp_enqueue_script( $this->prefix . 'script', plugin_dir_url(__DIR__) . DIRECTORY_SEPARATOR . 'dist' . DIRECTORY_SEPARATOR . 'script.js', array( 'jquery' ), $this->version, true );
      wp_localize_script( $this->prefix . 'script', 'dm_var' , array(
        'admin_url' => admin_url('admin-ajax.php'),
        'action' => 'generate_link_for_media'
      ) );
    }
  }

  public function generate_link_for_media(){
    $post_id = (int) $_GET['id'];

    if ( $post_id === 0 ){
      echo json_encode( array() );
      die;
    }

    $post = get_post($post_id);
    $title = apply_filters('the_title', $post->post_title);
    $url = wp_get_attachment_url( $post->ID, 'full' );
    $pathinfo = pathinfo($url);
    $extension = $pathinfo["extension"];

    echo json_encode( array(
      'title' => $title . "." . $extension,
      'url' => $url,
    ) );
    die;
  }
}
