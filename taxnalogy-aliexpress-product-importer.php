<?php
/**
 * @wordpress-plugin
 * Plugin Name: Taxnalogy Aliexpress Product Importer
 * Plugin URI: https://Taknalogy.com/taknalogy-aliexpress-product-importer-plugin
 * Description: Imports products directly from aliexpress to WooCommerce stores. It supports both simple and variable products along with their attributes. It also imports products images along with short and long description. Definitely a best free plugin for this job.
 * Version: 2.0.0
 * Author: Rab Nawaz
 * Author URI: https://taknalogy.com/author/rnawaz/
 * Requires at least: 4.0.0
 * Tested up to: 5.3.2
 * License: GPL-3.0+
 * License URI: https://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain: taxnalogy-aliexpress-product-importer
 * Domain Path: /languages
 *
 * @package Taknalogy_Ali_Importer
 * @category Core
 * @author Rab Nawaz
 */
if ( ! defined( 'ABSPATH' ) ) {
	// Exit if accessed directly.
	exit;
}
if ( ! defined( 'TAKNALOGY_ALI_IMPORTER_VERSION' ) ) {
	define( 'TAKNALOGY_ALI_IMPORTER_VERSION', '2.0.0' );
}
require_once plugin_dir_path( __FILE__ ) . 'functions.php';

add_action( 'plugins_loaded', array( 'Taknalogy_Ali_Importer', 'instance' ) );
register_activation_hook( __FILE__, array( 'Taknalogy_Ali_Importer', 'activate' ) );
register_uninstall_hook( __FILE__, array( 'Taknalogy_Ali_Importer', 'uninstall' ) );

// require_once 'functions.php';
// require_once 'inc/admin-menu.php';


final class Taknalogy_Ali_Importer {
	/**
	 * A reference to an instance of this class.
	 */
	private static $instance;

	/**
	 * The plugin directory URL.
	 *
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $plugin_url;

	/**
	 * The plugin directory path.
	 *
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $plugin_path;

	/**
	 * Returns an instance of this class.
	 */
	public static function instance() {
		if ( null == self::$instance ) {
			self::$instance             = new Taknalogy_Ali_Importer();
			$takaliepi_settings_version = get_option( 'takaliepi_settings_version' );
			if ( empty( $takaliepi_settings_version ) ) {
				takali_update_options( TAKNALOGY_ALI_IMPORTER_VERSION );
			} else {
				if ( $takaliepi_settings_version !== TAKNALOGY_ALI_IMPORTER_VERSION ) {
					takali_update_options( TAKNALOGY_ALI_IMPORTER_VERSION );
				}
			}
		}
		return self::$instance;
	}
	/**
	 * Initializes the plugin by setting filters and administration functions.
	 */
	private function __construct() {
		// error_log( 'creating instance of TaxnalogyAliImporter' );
		$this->plugin_url  = plugin_dir_url( __FILE__ );
		$this->plugin_path = plugin_dir_path( __FILE__ );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );
		spl_autoload_register( array( $this, 'classes_autoloader' ) );
		require_once 'classes/class-taxnalogy-aliexpress-main.php';
		Class_Taknalogy_Aliexpress_Main::instance();
		Takali_RestController::instance();
	}

	function classes_autoloader( $class_name ) {
		if ( false !== strpos( $class_name, 'Takali' ) ) {
			$class_file_name = str_replace( 'Takali_', '', $class_name );
			$classes_dir     = realpath( plugin_dir_path( __FILE__ ) ) . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR;
			$iti             = new RecursiveDirectoryIterator( $classes_dir );
			foreach ( new RecursiveIteratorIterator( $iti ) as $dir ) {
				if ( file_exists( $dir . DIRECTORY_SEPARATOR . $class_file_name . '.php' ) ) {
					require_once $dir . DIRECTORY_SEPARATOR . $class_file_name . '.php';
					break;
				}
			}
		}
	}
	/**
	 * Load the localisation file.
	 *
	 * @access  public
	 * @since   1.0.0
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain( 'taxnalogy-aliexpress-product-importer', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	} // End load_plugin_textdomain()
	/**
	 * Installation. Runs on activation.
	 *
	 * @access  public
	 * @since   1.0.0
	 */
	public static function activate() {

		if ( current_user_can( 'activate_plugins' ) && ! class_exists( 'WooCommerce' ) ) {
			deactivate_plugins( plugin_basename( __FILE__ ) );
			$error_message = '<p style="font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,Oxygen-Sans,Ubuntu,Cantarell,\'Helvetica Neue\',sans-serif;font-size: 13px;line-height: 1.5;color:#444;">' . esc_html__( 'This plugin requires ', 'taxnalogy-aliexpress-product-importer' ) . '<a href="' . esc_url( 'https://wordpress.org/plugins/woocommerce/' ) . '">WooCommerce</a>' . esc_html__( ' plugin to be active.', 'taxnalogy-aliexpress-product-importer' ) . '</p>';
			die( $error_message );
		}
		$plugin = isset( $_REQUEST['plugin'] ) ? sanitize_text_field( $_REQUEST['plugin'] ) : '';
		check_admin_referer( "activate-plugin_{$plugin}" );
		takali_update_options( TAKNALOGY_ALI_IMPORTER_VERSION );
	} // End activate()
	public function deactivate() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
		$plugin = isset( $_REQUEST['plugin'] ) ? sanitize_text_field( $_REQUEST['plugin'] ) : '';
		check_admin_referer( "deactivate-plugin_{$plugin}" );
	}
	public static function uninstall() {
		if ( ! current_user_can( 'delete_plugins' ) ) {
			return;
		}
	}
}