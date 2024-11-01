<?php

/**
 * Class to handle extension requests
 *
 * @author Rab Nawaz
 */
if ( ! class_exists( 'Takali_DashboardController' ) ) {

	class Takali_DashboardController {
		private static $instance;
		public static function instance() {
			if ( null == self::$instance ) {
				self::$instance = new Takali_DashboardController();
			}
			return self::$instance;
		}
		/**
		 * Initializes the plugin by setting filters and administration functions.
		 */
		private function __construct() {
		}

		public function update_settings( $data, $settings, $command ) {
			$stt = $settings->get_all();
			if ( $command === 'update' ) {
				foreach ( $stt as $key => $item ) {
					if ( ( $item ) == 1 && ( ! isset( $data[ $key ] ) ) ) {
						$stt[ $key ] = 0;
					} elseif ( isset( $data[ $key ] ) ) {
						$stt[ $key ] = $data[ $key ];
					}
				}
				$stt['taknalogy_reviews']        = ( $stt['taknalogy_reviews'] === 1 ) ? 'yes' : 'no';
				$stt['taknalogy_reviews_widget'] = ( $stt['taknalogy_reviews_widget'] === 1 ) ? 'yes' : 'no';
			} elseif ( $command === 'updatekey' ) {
				$stt['shopurl']  = $data['shopurl'];
				$stt['auth_key'] = $data['auth_key'];
			}
			update_option( 'takaliepi_settings', $stt );
		}
		public function get_settings() {
			$sett               = Takali_Settings::instance();
			$args               = array(
				'taxonomy'   => 'product_cat',
				'number'     => 0,
				'orderby'    => 'slug',
				'order'      => 'ASC',
				'hide_empty' => 0,
			);
			$product_categories = get_terms( $args );
			$categories         = array();
			foreach ( $product_categories as $cat ) {
				array_push(
					$categories,
					array(
						'name'    => $cat->name,
						'id'      => $cat->term_id,
						'default' => ( ( $sett->get( 'category' ) == $cat->term_id ) ? 1 : 0 ),
					)
				);
			}
			$exten_data = array_merge(
				array(
					'success' => true,
					'action'  => 'update',
				),
				array( 'categories' => $categories ),
				$sett->get_all()
			);
			return json_encode( $exten_data );
		}
	}
}