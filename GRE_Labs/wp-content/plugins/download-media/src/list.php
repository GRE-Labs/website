<?php

namespace JDD;

defined( 'ABSPATH' ) || die();

/**
 * Plugin handling the list media library.
 *
 * @since 1.2
 */
class DownloadMedia_List {

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

  /**
   * The output directory of the zip files
   *
   * @var string
   */
  private $output_dir = '';

  /**
   * The Zip path
   *
   * @var string
   */
  private $zip_path = '';

  /**
   * Errors object used to display error to user
   *
   * @var \WP_Error
   */
  private $errors;

  public function __construct() {

    $this->capability_download = apply_filters( 'download_media_download_cap', $this->capability_download );
    $this->output_dir = apply_filters( 'download_media_output_dir', dirname(__DIR__) . '/tmp/' );

    if ( !file_exists($this->output_dir) ) {
      mkdir($this->output_dir, 0700);
    }

    add_filter( 'media_row_actions', array( $this, 'add_download_link_to_media_list_view' ), 10, 2 );
    add_filter( 'attachment_fields_to_edit', array( $this, 'add_download_link_to_edit_media_modal_fields_area' ), 10, 2 );
    add_action( 'attachment_submitbox_misc_actions', array( $this, 'add_download_link_to_media_list_edit' ), 90 );

    // For the bulk action dropdowns.
    add_action( 'admin_head-upload.php', array( $this, 'add_bulk_actions_via_javascript' ) );
    add_action( 'admin_action_bulk_download_media', array( $this, 'bulk_action_handler' ) ); // Top drowndown.
    add_action( 'admin_action_bulk_all_download_media', array( $this, 'bulk_action_handler' ) ); // Top drowndown.
    add_action( 'admin_action_-1', array( $this, 'bulk_action_handler' ) ); // Bottom dropdown.
    add_action( 'admin_notices', array( $this, 'admin_notice_error' ) );
  }

  public function add_download_link_to_media_list_view($actions, $post){
    if( ! current_user_can( $this->capability_download ) ) {
      return $actions;
    }

    $actions['download_media'] = $this->generate_link_for_media($post);
    return $actions;
  }

  public function add_download_link_to_media_list_edit($post){
    if( ! current_user_can( $this->capability_download ) ) {
      return;
    }

    echo '<div class="misc-pub-section misc-pub-regenerate-thumbnails">';
		echo $this->generate_link_for_media($post, 'button-secondary button-large');
		echo '</div>';
  }

  public function add_download_link_to_edit_media_modal_fields_area( $form_fields, $post ) {
    if( ! current_user_can( $this->capability_download ) ) {
      return $form_fields;
    }

    $form_fields['download_media'] = array(
      'label'         => '',
      'input'         => 'html',
      'html'          => $this->generate_link_for_media($post, 'button-secondary button-large'),
      'show_in_modal' => true,
      'show_in_edit'  => false,
    );
    return $form_fields;
  }

  public function generate_link_for_media($post, $class = ''){
    $title = apply_filters('the_title', $post->post_title);
    return '<a download="' . esc_attr( $title ) . '" href="' . esc_attr( wp_get_attachment_url( $post->ID, 'full' ) ) . '" title="' . esc_attr( __( 'Download this media', 'download-media' ) ) . '" class="' . $class . '">' . _x( 'Download this media', 'action for a single media', 'download-media' ) . '</a>';
  }

  public function add_bulk_actions_via_javascript() {
    if ( ! current_user_can( $this->capability_download ) || ! class_exists('ZipArchive') ) {
      return;
    }

    ?>
    <script type="text/javascript">
      jQuery(document).ready(function ($) {
        $('select[name^="action"] option:last-child').before(
          $('<option/>')
            .attr('value', 'bulk_download_media')
            .text('<?php echo esc_js( _x( 'Download the selected medias', 'bulk actions dropdown', 'download-media' ) ); ?>')
        );
        $('select[name^="action"] option:last-child').before(
          $('<option/>')
            .attr('value', 'bulk_all_download_media')
            .text('<?php echo esc_js( _x( 'Download all medias', 'bulk actions dropdown', 'download-media' ) ); ?>')
        );
      });
    </script>
    <?php
  }

  private function handle_zip_initiation() {
    if( ! class_exists('ZipArchive') ){
      $this->errors->add( 'zip_archive_class', __('The ZipArchive PHP Library isn\'t installed.' , 'download-media') );
      $this->display_bulk_error( $this->errors );
    }

    $zip = new \ZipArchive();
    $this->zip_path = $this->output_dir . DIRECTORY_SEPARATOR . $this->prefix . time() . ".zip";

    if ( $zip->open( $this->zip_path, \ZipArchive::CREATE ) !== true ) {
      /* translators: %s: Generated name of the zipfile */
      $this->errors->add( 'open_zip', sprintf( __('Can\'t open the zip file %s.' , 'download-media'), $this->zip_path ) );
      $this->display_bulk_error( $this->errors );
    }

    return $zip;
  }

  private function add_medias_to_zip(array $medias, \ZipArchive &$zip) {
    foreach($medias as $media){
      $title = get_the_title($media);
      $media_path = get_attached_file((int) $media);
      $extension = pathinfo($media_path);
      $extension = $extension['extension'];
      $filename = $title . '.' . $extension;

      if(file_exists($media_path)) {
        if(is_readable($media_path)) {
          $zip->addFile($media_path, $filename);
        }else{
          $this->errors->add( 'is_not_readable', sprintf( __('The file %s isn\'t readable.' , 'download-media'), $filename ) );
        }
      }else{
        $this->errors->add( 'file_doesnt_exist', sprintf( __('The file %s doesn\'t exist.' , 'download-media'), $filename ) );
      }
    }
  }

  private function handle_zip_output(\ZipArchive $zip, $zip_path) {
    if ( $this->errors->has_errors() ) {
      $this->display_bulk_error( $this->errors );
    }

    if( $zip->close() !== true){
      $this->errors->add( 'cant_create_zip', __('Something wrong appened when trying to create the ZIP file.' , 'download-media') );
      $this->display_bulk_error( $this->errors );
    }

    if(file_exists($this->zip_path)) {
      header('Content-Description: File Transfer');
      header('Content-Type: application/octet-stream');
      header('Content-Disposition: attachment; filename="' . basename($this->zip_path) . '"');
      header('Expires: 0');
      header('Cache-Control: must-revalidate');
      header('Pragma: public');
      header('Content-Length: ' . filesize($this->zip_path));
      flush(); // Flush system output buffer
      readfile($this->zip_path);
      exit();
    }else{
      $this->errors->add( 'cant_download_zip', __('Something wrong appened when trying to download the ZIP file.' , 'download-media') );
      $this->display_bulk_error( $this->errors );
    }

    exit();
  }

  public function bulk_action_handler() {
    
    if ( empty( $_REQUEST['action'] ) || empty( $_REQUEST['action2'] ) ) {
      return;
    }

    if ( ('bulk_download_media' !== $_REQUEST['action'] && 'bulk_download_media' !== $_REQUEST['action2'] ) && ( 'bulk_all_download_media' !== $_REQUEST['action'] && 'bulk_all_download_media' !== $_REQUEST['action2'] ) ) {
      return;
    }

    check_admin_referer( 'bulk-media' );
    
    if( $_REQUEST['action'] === 'bulk_all_download_media' ){
      $_REQUEST['media'] = get_posts([
        'post_type' => 'attachment',
        'posts_per_page' => -1,
        'fields' => 'ids'
      ]);
    }

    if ( empty( $_REQUEST['media'] ) || ! is_array( $_REQUEST['media'] ) ) {
      return;
    }

    $this->errors = new \WP_Error();

    $zip = $this->handle_zip_initiation();

    $this->add_medias_to_zip($_REQUEST['media'], $zip);

    $this->handle_zip_output($zip, $this->zip_path);
  }

  public function display_bulk_error( \WP_Error $errors ){
    set_transient( 'download_media_error_notice', $errors );
    wp_safe_redirect( admin_url( 'upload.php' ) );
    die;
  }

  public function admin_notice_error() {
    $error = get_transient('download_media_error_notice');
    if(is_wp_error($error)):
      $error_messages = $error->get_error_messages();
      delete_transient('download_media_error_notice');

      foreach($error_messages as $error_message): ?>
      <div class="notice notice-error is-dismissible">
        <p><?php echo $error_message; ?></p>
      </div>
      <?php
      endforeach;
    endif;
  }
}
