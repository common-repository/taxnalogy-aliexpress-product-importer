<?php
/**
 * Class to handle extension requests
 *
 * @author Rab Nawaz
 */

if ( ! class_exists( 'Takali_Settings' ) ) {

	class Takali_Settings {
		private static $instance;
		public static function instance() {
			if ( null == self::$instance ) {
				self::$instance = new Takali_Settings();
			}
			return self::$instance;
		}
		/**
		 * Initializes the plugin by setting filters and administration functions.
		 */
		private function __construct() {
			// error_log( 'Takali_Settings is called' );
			$this->load();
		}
		private $settings;
		private $auto_commit = true;

		public function auto_commit( $auto_commit = true ) {
			$this->auto_commit = $auto_commit;
		}

		public function get_all() {
			return $this->settings;
		}
		public function load() {
			$this->settings = array_merge( takali_default_settings(), get_option( 'takaliepi_settings', array() ) );
		}

		public function commit() {
			update_option( 'takaliepi_settings', $this->settings );
		}

		public function to_string() { }

		public function from_string( $str ) { }


		public function get( $setting, $default = '' ) {
			return isset( $this->settings[ $setting ] ) ? $this->settings[ $setting ] : $default;
		}

		public function reset() {
			$this->settings = array_merge( get_option( 'takaliepi_settings', array() ), takali_default_settings() );
			update_option( 'takaliepi_settings', $this->settings );
		}

		public function set( $setting, $value ) {

			$this->settings[ $setting ] = $value;

			if ( $this->auto_commit ) {
				$this->commit();
			}
		}

		public function del( $setting ) {
			if ( isset( $this->settings[ $setting ] ) ) {
				unset( $this->settings[ $setting ] );

				if ( $this->auto_commit ) {
					$this->commit();
				}
			}
		}
	}
}