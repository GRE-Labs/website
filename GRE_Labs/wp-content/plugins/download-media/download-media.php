<?php
/*
Plugin Name: Download Media
Plugin URI: https://wordpress.org/plugins/download-media/
Description: Allows medias in the media library to be direclty download one by one or in bulk.
Author: Jean-David Daviet
Version: 1.4.2
Author URI: https://jeandaviddaviet.fr
Text Domain: download-media
*/

namespace JDD;

defined( 'ABSPATH' ) || die();

require_once __DIR__ . '/src/list.php';
require_once __DIR__ . '/src/grid.php';

/**
 * Main plugin class.
 *
 * @since 1.0.0
 */
class DownloadMedia {
  /**
   * This plugin's version number. Used for busting caches.
   *
   * @var string
   */
  public $version = '1.4.2';

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
   * The capability required to access this plugin's settings.
   * Please don't change this directly. Use the "download_media_settings_cap" filter instead.
   *
   * @var string
   */
  public $capability_settings = 'manage_options';

  /**
   * The intervals in seconds for the cron jobs
   *
   * @var array
   */
  public $cron_intervals = array();


  /**
   * The name of the cron hook
   *
   * @var string
   */
  private $cron_hook_name = 'dm_cron_hook';

  public function __construct() {
    register_activation_hook( __FILE__, array( $this , 'activate' ) );
    register_deactivation_hook( __FILE__, array( $this , 'deactivate' ) );

    // Allow people to change what capability is required to use this plugin.
    $this->capability_settings = apply_filters( 'download_media_settings_cap', $this->capability_settings );
    $this->output_dir = apply_filters( 'download_media_output_dir', dirname(__FILE__) . '/tmp/' );

    // Set the duration of the intervals in seconds
    $this->cron_intervals[$this->prefix . 'daily'] = apply_filters( 'download_media_cron_daily_second', array( 'interval' => 60 * 60 * 24, 'display' => esc_html__('Day', 'download-media') ) );
    $this->cron_intervals[$this->prefix . 'weekly'] = apply_filters( 'download_media_cron_weekly_second', array( 'interval' => 60 * 60 * 24 * 7, 'display' => esc_html__('Week', 'download-media') ) );
    $this->cron_intervals[$this->prefix . 'monthly'] = apply_filters( 'download_media_cron_monthly_second', array( 'interval' => 60 * 60 * 24 * 30, 'display' => esc_html__('Month', 'download-media') ) );
    $this->cron_intervals = apply_filters( 'download_media_cron_intervals', $this->cron_intervals );

    add_action('admin_init', array( $this, 'settings_init' ) );
    add_action('admin_menu', array( $this, 'register_settings_page' ) );

    add_filter( 'update_option_download_media_should_delete', array( $this, 'update_option_download_media_should_delete' ), 10, 2 );
    add_filter( 'update_option_download_media_recurrence', array( $this, 'update_option_download_media_recurrence' ), 10, 2 );
    add_filter( 'cron_schedules', array( $this, 'add_cron_interval' ) );
    add_action( $this->cron_hook_name, array( $this, 'cron_exec' ) );
  }

  public function register_settings_page() {
    add_options_page(
      'Download Media Settings Page',
      'Download Media',
      $this->capability_settings,
      'download-media',
      array( $this, 'display_download_media_setting_page_callback' ) );
  }

  public function scan_for_zip_files($dir){
    $scan_plugin_dir = scandir($dir);
    return preg_grep ('#^' . $this->prefix . '.*\.zip$#', $scan_plugin_dir);
  }

  public function display_download_media_setting_page_callback() {
    if ( ! current_user_can( $this->capability_settings ) ) {
      return;
    }

    $found_zips = $this->scan_for_zip_files($this->output_dir);

    if ((isset($_POST['action']) && $_POST['action'] === 'delete') &&
      (isset($_POST['_wpnonce']) && wp_verify_nonce($_POST['_wpnonce'], 'delete_zip_files'))) {

        $errors = $this->delete_all_zip_files($found_zips);
        $this->display_settings_error($errors);
        if ( ! $errors->has_errors() ) {
          $found_zips = array();
        }
      }
    ?>
    <div class="wrap">
      <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

      <form action="<?php echo admin_url( 'options.php'); ?>" method="post">
      <?php
        do_settings_sections( 'download-media' );
        settings_fields( 'download_media_section1' );
        submit_button( 'Save Settings' );
      ?>
      </form>

        <?php $found_zips_count = count($found_zips);
        if ( $found_zips_count ) : ?><form action="<?php echo admin_url( 'options-general.php?page=download-media'); ?>" method="post"><?php endif; ?>
          <h2><?php _e( 'Download Media Zip Files Deletion' , 'download-media' ); ?></h2>
          <p><?php if ( ! $found_zips_count ) :
            _e( 'There are no zip files to delete.', 'download-media' );
          else:
            printf( _n( 'There is %s zip file to delete.',  'There are %s zip files to delete.', $found_zips_count, 'download-media' ), $found_zips_count );
          endif; ?></p>
          <?php if ( $found_zips_count ) : ?>
          <table class="form-table" role="presentation">
            <tbody>
              <tr>
                <th scope="row"><?php _e( 'Delete all the generated zip files now' , 'download-media' ); ?></th>
                <td><input type="submit" name="submit" id="submit" class="button" value="<?php _e( 'Delete files now' , 'download-media' ); ?>"></td>
              </tr>
            </tbody>
          </table>
          <input type="hidden" name="action" value="delete">
          <?php wp_nonce_field('delete_zip_files'); ?>
        </form>
        <?php endif; ?>
    </div>
  <?php
  }

  public function settings_init()
  {
      add_settings_section(
          'download_media_section1',
          'Download Media Settings',
          '__return_false',
          'download-media'
      );

      add_settings_field(
          'download_media_should_delete',
          __('Should the zip files be deleted automatically ?', 'download-media'),
          array( $this, 'should_delete_cb' ),
          'download-media',
          'download_media_section1'
      );
      register_setting('download_media_section1', 'download_media_should_delete');

      add_settings_field(
          'download_media_recurrence',
          __('The zip files should be deleted every:', 'download-media'),
          array( $this, 'recurrence_cb' ),
          'download-media',
          'download_media_section1'
      );
      register_setting('download_media_section1', 'download_media_recurrence');
  }

  public function should_delete_cb(){
    $setting = get_option('download_media_should_delete', 1);
    ?>
    <p>
      <label><input name="download_media_should_delete" type="radio" value="1" <?php checked((int) $setting, 1); ?>> <?php _e( 'Yes' ); ?></label>
      <br />
      <label><input name="download_media_should_delete" type="radio" value="0" <?php checked((int) $setting, 0); ?>> <?php _e( 'No' ); ?></label>
    </p>
    <?php
  }

  public function recurrence_cb(){
    $setting = get_option('download_media_recurrence', $this->prefix . 'weekly');
    foreach($this->cron_intervals as $key_interval => $interval):
      $display = $interval['display'];
    ?>
    <p>
      <label><input name="download_media_recurrence" type="radio" value="<?php echo $key_interval; ?>" <?php checked($setting, $key_interval); ?>> <?php echo $display; ?></label>
    </p>
    <?php
    endforeach;
  }

  public function update_option_download_media_recurrence($old, $new){
    $timestamp = wp_next_scheduled( $this->cron_hook_name );
    wp_unschedule_event( $timestamp, $this->cron_hook_name );

    if( ! (int) get_option('download_media_should_delete') ) {
      return;
    }

    if ( ! wp_next_scheduled( $this->cron_hook_name ) ) {
      wp_schedule_event( time() + $this->cron_intervals[$new]['interval'] , $new, $this->cron_hook_name );
    }
  }

  public function update_option_download_media_should_delete($old, $new){
    $timestamp = wp_next_scheduled( $this->cron_hook_name );
    wp_unschedule_event( $timestamp, $this->cron_hook_name );

    $download_media_recurrence = isset($_POST['download_media_recurrence']) ? $_POST['download_media_recurrence'] : 1;

    if( (int) get_option('download_media_should_delete') ) {
      if ( ! wp_next_scheduled( $this->cron_hook_name )  ) {
        wp_schedule_event( time() + $this->cron_intervals[$download_media_recurrence]['interval'] , $download_media_recurrence, $this->cron_hook_name );
      }
    }
  }

  public function delete_all_zip_files($found_zips){
    $errors = new \WP_Error();
    if ( is_array($found_zips) && count($found_zips) > 0 ) {
      foreach($found_zips as $found_zip){
        $filename = $this->output_dir . DIRECTORY_SEPARATOR . $found_zip;

        if(file_exists($filename)) {
          if(is_writable(dirname($filename))) {
            unlink( $filename );
          }else{
            $errors->add( 'is_not_deletable', sprintf( __('The file %s isn\'t deletable' , 'download-media'), $filename ) );
          }
        }else{
          $errors->add( 'file_doesnt_exist', sprintf( __('The file %s doesn\'t exist.' , 'download-media'), $filename ) );
        }
      }
    }
    return $errors;
  }

  public function display_settings_error(\WP_Error $errors){
    if ( $errors->has_errors() ) {
      add_settings_error( 'download_media_message', 'download_media_message', $errors->get_error_message(), 'error' );
    } else {
      add_settings_error( 'download_media_message', 'download_media_message', __( 'Zip files successfully deleted.', 'download-media' ), 'success' );
    }
    settings_errors( 'download_media_message' );
  }

  public function cron_exec(){
    $found_zips = $this->scan_for_zip_files($this->output_dir);
    $this->delete_all_zip_files($found_zips);
  }

  public function add_cron_interval( $schedules ) {
    $schedules = array_merge($schedules, $this->cron_intervals);
    return $schedules;
  }

  public function activate() {
    add_option('download_media_should_delete', 1, '', false);
    add_option('download_media_recurrence', $this->prefix . 'weekly', '', false);

    if ( ! wp_next_scheduled( $this->cron_hook_name ) ) {
      wp_schedule_event( time(), $this->prefix . 'weekly', $this->cron_hook_name );
    }
  }

  public function deactivate() {
    delete_option('download_media_should_delete');
    delete_option('download_media_recurrence');

    $timestamp = wp_next_scheduled( $this->cron_hook_name );
    wp_unschedule_event( $timestamp, $this->cron_hook_name );
  }
}

new DownloadMedia();
new DownloadMedia_List();
new DownloadMedia_Grid();
