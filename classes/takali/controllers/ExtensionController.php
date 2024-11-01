<?php

/**
 * Class to handle extension requests
 *
 * @author Rab Nawaz
 */
if ( ! class_exists( 'Takali_ExtensionController' ) ) {

	class Takali_ExtensionController {
		private static $instance;
		public static function instance() {
			if ( null == self::$instance ) {
				self::$instance = new Takali_ExtensionController();
			}
			return self::$instance;
		}
		/**
		 * Initializes the plugin by setting filters and administration functions.
		 */
		private function __construct() {
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
			$exten_data = array(
				'outcome'     => 'success',
				'categories'  => $categories,
				'price_multi' => $sett->get( 'price_multi' ),
				'sale_multi'  => $sett->get( 'sale_multi' ),
			);
			return $exten_data;
		}
	}
}
