<?php

/**
 * Class to handle extension requests
 *
 * @author Rab Nawaz
 */
if ( ! class_exists( 'Takali_RestController' ) ) {

	class Takali_RestController {
		private static $instance;
		public static function instance() {
			if ( null == self::$instance ) {
				self::$instance = new Takali_RestController();
			}
			return self::$instance;
		}
		/**
		 * Initializes the plugin by setting filters and administration functions.
		 */
		private function __construct() {
			add_action( 'rest_api_init', array( $this, 'tak_rest_api_init_admin' ) );
			// add_action( 'rest_api_init', array( $this, 'tak_rest_api_init_update' ) );
		}
		public function tak_rest_api_init_admin() {
			register_rest_route(
				'tak/v1',
				'bandh',
				array(
					'methods'  => 'POST',
					'callback' => array( $this, 'tak_rest_ali_ext_admin' ),
				)
			);
		}

		function tak_rest_ali_ext_admin() {
			$data_in  = json_decode( file_get_contents( 'php://input' ), true );
			$settings = Takali_Settings::instance();
			if ( $data_in['command'] === 'connect' ) {
				// echo json_encode( array( 'outcome' => 'success' ) );
				$ext_ctlr = Takali_ExtensionController::instance();
				return $ext_ctlr->get_settings();
			}
			if ( $data_in['authkey'] !== $settings->get( 'auth_key' ) ) {
				return array( 'outcome' => 'failed' );
			}
			if ( $data_in['command'] === 'settings' ) {
				$ext_ctlr = Takali_ExtensionController::instance();
				return $ext_ctlr->get_settings();
			} elseif ( $data_in['command'] === 'createProduct' ) {
				$pc = Takali_ProductService::instance();
				if ( $data_in['step'] === '1' ) {
					//error_log( 'step 1' );
					$outcome = $pc->create_product( $data_in['data'], $settings );
					return array(
						'outcome' => 'created',
						'data'    => $outcome,
					);
				} else {
					//error_log( 'step 2' );

					$var_data = get_post_meta( $data_in['data'], '_tak_var_data', true );
					//error_log( print_r( $data_in, true ) );
					$pc->save_variations( $data_in['data'], $var_data, $settings );
					return array( 'outcome' => 'created' );
				}
			}
			return array( 'outcome' => 'no matching action found.' );
		}
	}
}
