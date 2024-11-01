<?php

/**
 * Class to handle extension requests
 *
 * @author Rab Nawaz
 */
if ( ! class_exists( 'Takali_ProductService' ) ) {

	class Takali_ProductService {
		private static $instance;
		public static function instance() {
			if ( null == self::$instance ) {
				self::$instance = new Takali_ProductService();
			}
			return self::$instance;
		}
		/**
		 * Initializes the plugin by setting filters and administration functions.
		 */
		private function __construct() {
		}
		public function create_product( $data, $settings ) {
			if ( count( $data['sku']['skuList'] ) > 1 ) {
				$post_id = $this->create_variable_product( $data, $settings );
				if ( $settings->get( 'download_images' ) ) {
					$this->create_product_images( $post_id, $data, $settings );
				}
				if ( $settings->get( 'import_attributes' ) ) {
					$this->create_product_attributes( $post_id, $data, $settings );
				}
				return $this->extract_variation_data( $post_id, $data, $settings );
			} else {
				$post_id = $this->create_simple_product( $data, $settings );
				if ( $settings->get( 'download_images' ) ) {
					$this->create_product_images( $post_id, $data, $settings );
				}
				if ( $settings->get( 'import_attributes' ) ) {
					$this->create_product_attributes( $post_id, $data, $settings );
				}
			}
		}
		private function extract_variation_data( $post_id, $data, $settings ) {
			$_product_attributes = get_post_meta( $post_id, '_product_attributes', true );
			if ( empty( $_product_attributes ) ) {
				$_product_attributes = array();
			}
			foreach ( $data['sku']['skuAttList'] as $key => $sku_prop ) {
				$this->create_attribute( $key, $sku_prop );
				$thedata = array(
					'name'         => wc_sanitize_taxonomy_name( $key ),
					'is_visible'   => '1',
					'is_variation' => '1',
					'is_taxonomy'  => '0',
					'value'        => implode(
						'|',
						preg_split( '/[,;|]/', $sku_prop, -1, PREG_SPLIT_NO_EMPTY )
					),
				);
				// if ( empty( $_product_attributes ) ) {
				// update_post_meta( $post_id, '_product_attributes', array( 'pa_' . $key => $thedata ) );
				// } else {
				$_product_attributes[ 'pa_' . $key ] = $thedata;
				update_post_meta( $post_id, '_product_attributes', $_product_attributes );
				// }
			}
			foreach ( $data['sku']['skuList'] as $key => $value ) {
				if ( ! empty( $data['sku']['skuList'][ $key ]['image'] ) ) {
					$image_id = $this->create_image_attachement_from_url( $data['sku']['skuList'][ $key ]['image'] );
					if ( $image_id ) {
						$data['sku']['skuList'][ $key ]['image'] = $image_id;
					} else {
						$data['sku']['skuList'][ $key ]['image'] = false;
					}
				}
			}
			$variation_list = array();
			foreach ( $data['sku']['skuPriceList'] as $sku_price ) {
				$variation_list[ $sku_price['sku'] ] = array(
					'stock_qty' => $this->getStockValue( $sku_price['stock'], $settings ),
				);
				foreach ( ( explode( ',', $sku_price['ids'] ) ) as $sku_prop_id ) {
					if ( ! empty( $data['sku']['skuList'][ $sku_prop_id ] ['image'] ) ) {
						$variation_list[ $sku_price['sku'] ]['image'] = $data['sku']['skuList'][ $sku_prop_id ] ['image'];
					}
					$variation_list[ $sku_price['sku'] ]['props']   [ $data['sku']['skuList'][ $sku_prop_id ]['name'] ] = $data['sku']['skuList'][ $sku_prop_id ]['value'];
				}
				$price_multi = ( array_key_exists( 'settings', $data ) ) ? $data['settings']['priceMultiplier'] : $settings->get( 'price_multi' );
				$sale_multi  = ( array_key_exists( 'settings', $data ) ) ? $data['settings']['saleMultiplier'] : $settings->get( 'sale_multi' );
				$orig_price  = $sku_price['origPrice'] + $data['logis'];
				$sale_price  = ( $orig_price === $sku_price['price'] ) ? 0 : $sku_price['price'] + $data['logis'];
				$variation_list[ $sku_price['sku'] ]['regular_price'] = round( ( $orig_price * $price_multi ), 2 );
				$variation_list[ $sku_price['sku'] ]['sale_price']    = round( ( $sale_price * $sale_multi ), 2 );
				$variation_list[ $sku_price['sku'] ]['sku']           = $sku_price['sku'];
			}
			update_post_meta( $post_id, '_tak_var_data', $variation_list );
			// WC_Product_Variable::sync( $post_id );
			return array( 'post_id' => $post_id );
			// $this->save_variations( $post_id, $variation_list, $settings );
		}
		public function save_variations( $post_id, $variation_list, $settings ) {
			$product = wc_get_product( $post_id );
			foreach ( $variation_list as $var_list ) {

				$variation_post = array(
					'post_title'  => $product->get_title(),
					'post_name'   => $product->get_title(), // 'product-' . $post_id . '-variation',
					'post_status' => 'Publish', // $settings->get( 'default_product_status' ),
					'post_parent' => $post_id,
					'post_type'   => 'product_variation',
				// 'guid'        => $product->get_permalink(),
				);
				// Creating the product variation
				$variation_id = wp_insert_post( $variation_post );
				// Get an instance of the WC_Product_Variation object
				$variation = new WC_Product_Variation( $variation_id );
				foreach ( $var_list['props'] as $vkey => $vvalue ) {
					$taxonomy = 'pa_' . wc_sanitize_taxonomy_name( $vkey );
					// $term_slug = get_term_by( 'name', $vvalue, $taxonomy )->slug; // Get the term slug
					// Get the post Terms names from the parent variable product.
					$post_term_names = wp_get_post_terms( $post_id, $taxonomy, array( 'fields' => 'names' ) );

					// error_log( print_r( $post_term_names, true ) );

					// Check if the post term exist and if not we set it in the parent variable product.
					if ( ! in_array( $vvalue, $post_term_names ) ) {
						wp_set_post_terms( $post_id, $vvalue, $taxonomy, true );
					}
					// Set/save the attribute data in the product variation
					update_post_meta( $variation_id, 'attribute_' . wc_sanitize_taxonomy_name( $vkey ), $vvalue );
					// update_post_meta( $variation_id, 'attribute_'.$attribute, $term_name );
				}
				// set variation image
				/*
				$update_attachment = array(
				'ID'          => $var_list['image'],
				'post_parent' => $variation->get_id(),
				);
				wp_insert_attachment( $update_attachment );
				*/
				if ( isset( $var_list['image'] ) && $var_list['image'] ) {
					$variation->set_image_id( $var_list['image'] ); // create_image_attachement_from_url( $var_list['image'], $variation->get_id() ) );
				}
				// Prices
				if ( $var_list['sale_price'] > 0 ) {
					$variation->set_sale_price( $var_list['sale_price'] );
					$variation->set_price( $var_list['sale_price'] );
				} else {
					$variation->set_price( $var_list['regular_price'] );
				}
				$variation->set_regular_price( $var_list['regular_price'] );
				if ( $settings->get( 'use_sku_number' ) === 1 ) {
					$variation->set_sku( $var_list['sku'] );
				}
				if ( $settings->get( 'manage_stock' ) === 1 ) {
					$variation->set_stock_quantity( $var_list['stock_qty'] );
					$variation->set_manage_stock( true );
					$variation->set_stock_status( '' );
				} else {
					$variation->set_manage_stock( false );
				}
				$variation->set_weight( '' ); // weight (reseting)
				$variation->save(); // Save the data
				// $variation->variable_product_sync();
				// $variation_id
				// $variation->variable_product_sync( $variation_id );
			}
			wc_delete_product_transients( $post_id );
			// wc_delete_product_transients( '_transient_wc_var_prices_' . $post_id );
			// wc_delete_product_transients( '_transient_wc_product_children_' . $post_id );
			// $product->variable_product_sync( $post_id );
			delete_post_meta( $post_id, '_tak_var_data' );

			//error_log( print_r( $settings->get( 'default_product_status' ), true ) );
			/*
			wp_update_post(
				array(
					'ID'          => $post_id,
					'post_status' => 'Draft';, // $settings->get( 'default_product_status' ),
				)
			);
			*/
			$product->set_status( $settings->get( 'default_product_status' ) );
			$product->save(); // Save the data
			return true;
		}
		private function create_variable_product( $data, $settings ) {

			// $price_multi       = ( array_key_exists( 'settings', $data ) ) ? $data['settings']['priceMultiplier'] : $settings->get( 'price_multi' );
			// $sale_multi        = ( array_key_exists( 'settings', $data ) ) ? $data['settings']['saleMultiplier'] : $settings->get( 'sale_multi' );
			$category          = ( array_key_exists( 'settings', $data ) ) ? $data['settings']['category'] : $settings->get( 'category' );
			$short_description = ( $settings->get( 'import_short_description' ) === 1 ) ? $data['shortDescription'] : '';
			$long_description  = ( $settings->get( 'import_long_description' ) === 1 ) ? $this->get_long_description( $data['longDescriptionUrl'] ) : '';

			$product = new WC_Product_Variable();
			$product->set_status( 'Publish' );

			// $product->set_status( $settings->get( 'default_product_status' ) );
			$product->set_name( sanitize_text_field( $data['title'] ) );
			$product->set_short_description( $short_description );
			$product->set_description( $long_description );
			$product->set_category_ids( array( $category ) );
			$post_id = $product->save();
			update_post_meta( $post_id, '_tak_aliexpress_id', $data['id'] );
			update_post_meta( $post_id, '_tak_review_url', $data['url'] );
			update_post_meta( $post_id, '_tak_review_sup', 'aliexpress' );
			update_post_meta( $post_id, '_tak_review_active', $settings->get( 'taknalogy_reviews' ) );
			update_post_meta( $post_id, '_tak_review_widget', $settings->get( 'taknalogy_reviews_widget' ) );
			update_post_meta( $post_id, '_tak_last_update_timestamp', current_datetime() );

			// error_log( print_r( current_datetime(), true ) );
			return $post_id;
		}

		private function create_simple_product( $data, $settings ) {

			$price_multi       = ( array_key_exists( 'settings', $data ) ) ? $data['settings']['priceMultiplier'] : $settings->get( 'price_multi' );
			$sale_multi        = ( array_key_exists( 'settings', $data ) ) ? $data['settings']['saleMultiplier'] : $settings->get( 'sale_multi' );
			$category          = ( array_key_exists( 'settings', $data ) ) ? $data['settings']['category'] : $settings->get( 'category' )['id'];
			$short_description = ( $settings->get( 'import_short_description' ) === 1 ) ? $data['shortDescription'] : '';
			$long_description  = ( $settings->get( 'import_long_description' ) === 1 ) ? $this->get_long_description( $data['longDescriptionUrl'] ) : '';

			$orig_price = $data['sku']['skuPriceList'][0]['origPrice'] + $data['logis'];
			$sale_price = ( $orig_price === $data['sku']['skuPriceList'][0]['price'] ) ? 0 : $data['sku']['skuPriceList'][0]['price'] + $data['logis'];
			$stock      = $this->getStockValue( $data['sku']['skuPriceList'][0]['stock'], $settings );
			$sku        = $data['sku']['skuPriceList'][0]['sku'];

			$product = new WC_Product_Simple();
			$product->set_status( 'Publish' );

			// $product->set_status( $settings->get( 'default_product_status' ) );
			$product->set_name( sanitize_text_field( $data['title'] ) );
			$product->set_short_description( $short_description );
			$product->set_description( $long_description );
			$product->set_category_ids( array( $category ) );

			if ( $sale_price > 0 ) {
				$product->set_regular_price( round( ( $orig_price * $price_multi ), 2 ) ); // To be sure
				$product->set_sale_price( round( ( $sale_price * $sale_multi ), 2 ) );
				$product->set_price( round( ( $sale_price * $sale_multi ), 2 ) );
			} else {
				$product->set_regular_price( round( ( $orig_price * $price_multi ), 2 ) ); // To be sure
				$product->set_price( round( ( $orig_price * $price_multi ), 2 ) );
			}

			if ( $settings->get( 'use_sku_number' ) === 1 ) {
				$product->set_sku( $sku );
			}
			if ( $settings->get( 'manage_stock' ) === 1 ) {
				$product->set_manage_stock( true );
				$product->set_stock_quantity( $stock );
			}

			$post_id = $product->save();
			update_post_meta( $post_id, '_tak_aliexpress_id', $data['id'] );
			update_post_meta( $post_id, '_tak_review_url', $data['url'] );
			update_post_meta( $post_id, '_tak_review_sup', 'aliexpress' );
			update_post_meta( $post_id, '_tak_review_active', $settings->get( 'taknalogy_reviews' ) );
			update_post_meta( $post_id, '_tak_review_widget', $settings->get( 'taknalogy_reviews_widget' ) );
			update_post_meta( $post_id, '_tak_last_update_timestamp', current_datetime() );

			$product->set_status( $settings->get( 'default_product_status' ) );
			$product->save(); // Save the data
			return $post_id;
		}
		private function create_product_attributes( $post_id, $data, $settings ) {

			if ( ! empty( $data['attributes'] ) ) {
				foreach ( $data['attributes'] as $prop ) {
					$this->create_attribute( $prop['attrName'], $prop['attrValue'] );
					$thedata             = array(
						'pa_' . $prop['attrName'] => array(
							'name'         => wc_sanitize_taxonomy_name( $prop['attrName'] ),
							'is_visible'   => '1',
							'is_variation' => '0',
							'is_taxonomy'  => '0',
							'value'        => implode( '|', preg_split( '/[,;|]/', $prop['attrValue'], -1, PREG_SPLIT_NO_EMPTY ) ),
						),
					);
					$_product_attributes = get_post_meta( $post_id, '_product_attributes', true );
					// Updating the Post Meta
					if ( empty( $_product_attributes ) ) {
						update_post_meta( $post_id, '_product_attributes', $thedata );
					} else {
						update_post_meta( $post_id, '_product_attributes', array_merge( $_product_attributes, $thedata ) );
					}
				}
			}
			return true;
		}
		private function create_product_images( $post_id, $data, $settings ) {
			$product     = wc_get_product( $post_id );
			$image_count = 0;
			if ( empty( $product->get_gallery_image_ids() ) ) {
				$image_list = array();
				foreach ( $data['imagePathList'] as $glist ) {
					$image_id = $this->create_image_attachement_from_url( $glist, $post_id );
					if ( $image_id ) {
						array_push( $image_list, $image_id );
					} else {
						return 'gallery_error';
					}
					$image_count = $image_count + 1;
					if ( $image_count >= $settings->get( 'import_product_images_limit' ) ) {
						break;
					}
				}
				$product->set_image_id( $image_list[0] );
				unset( $image_list[0] );
				$product->set_gallery_image_ids( $image_list );
				$post_id = $product->save();
				return true;
			} else {
				return true;
			}
		}
		private function get_long_description( $data ) {
			if ( $data != '' ) {
				$opts    = array(
					'http' => array(
						'timeout'    => 60,
						'method'     => 'GET',
						'header'     => "Accept-language: en\r\n",
						'User-Agent' => "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/39.0.2171.95 Safari/537.36\r\n",
					),
				);
				//$context = stream_context_create( $opts );
				//$data_desc = file_get_contents( $data, false, $context );
				$data_desc = tak_get_web_page( $data );
				return $data_desc['content'];
			} else {
				return '';
			}
		}
		/**
		 * @since 1.0.0
		 *
		 * @param string        $raw_name Name of attribute to create.
		 * @param array(string) $terms          Terms to create for the attribute.
		 * @return array
		 * Thanks to Claudio Sanches [ https://github.com/claudiosanches ]
		 */
		private function create_attribute( $raw_name = 'size', $v ) {

			$terms = preg_split( '/[,;|]/', $v, -1, PREG_SPLIT_NO_EMPTY );

			// error_log( print_r($terms, true ));
			global $wpdb, $wc_product_attributes;
			// Make sure caches are clean.
			// delete_transient( 'wc_attribute_taxonomies' );
			// WC_Cache_Helper::incr_cache_prefix( 'woocommerce-attributes' );
			// These are exported as labels, so convert the label to a name if possible first.
			$attribute_labels = wp_list_pluck( wc_get_attribute_taxonomies(), 'attribute_label', 'attribute_name' );
			$attribute_name   = array_search( $raw_name, $attribute_labels, true );
			if ( ! $attribute_name ) {
				$attribute_name = wc_sanitize_taxonomy_name( $raw_name );
			}
			$attribute_id = wc_attribute_taxonomy_id_by_name( $attribute_name );
			if ( ! $attribute_id ) {
				$taxonomy_name = wc_attribute_taxonomy_name( $attribute_name );
				// Degister taxonomy which other tests may have created...
				// unregister_taxonomy( $taxonomy_name );
				$attribute_id = wc_create_attribute(
					array(
						'name'         => $raw_name,
						'slug'         => $attribute_name,
						'type'         => 'select',
						'order_by'     => 'menu_order',
						'has_archives' => 1,
					)
				);
				if ( is_wp_error( $attribute_id ) ) {
					$attribute_id = wc_create_attribute(
						array(
							'name'         => $raw_name,
							'slug'         => $attribute_name . '-jackit',
							'type'         => 'select',
							'order_by'     => 'menu_order',
							'has_archives' => 1,
						)
					);
				}
				if ( is_wp_error( $attribute_id ) ) {
					return false;
				}
				// Register as taxonomy.
				register_taxonomy(
					$taxonomy_name,
					apply_filters( 'woocommerce_taxonomy_objects_' . $taxonomy_name, array( 'product' ) ),
					apply_filters(
						'woocommerce_taxonomy_args_' . $taxonomy_name,
						array(
							'labels'       => array(
								'name' => $raw_name,
							),
							'hierarchical' => false,
							'show_ui'      => false,
							'query_var'    => true,
							'rewrite'      => false,
						)
					)
				);
				// Set product attributes global.
				$wc_product_attributes = array();
				foreach ( wc_get_attribute_taxonomies() as $taxonomy ) {
					$wc_product_attributes[ wc_attribute_taxonomy_name( $taxonomy->attribute_name ) ] = $taxonomy;
				}
			}
			$attribute = wc_get_attribute( $attribute_id );
			if ( is_wp_error( $attribute ) ) {
				return false;
			}
			$return = array(
				'attribute_name'     => $attribute->name,
				'attribute_taxonomy' => $attribute->slug,
				'attribute_id'       => $attribute_id,
				'term_ids'           => array(),
			);
			foreach ( $terms as $term ) {
				$result = term_exists( $term, $attribute->slug );
				if ( ! $result ) {
					$result = wp_insert_term( $term, $attribute->slug );
					if ( is_wp_error( $result ) ) {
						return false;
					}
					$return['term_ids'][] = $result['term_id'];
				} else {
					$return['term_ids'][] = $result['term_id'];
				}
			}
			return $return;
		}
		/**
		 * Create an image attachment from a url
		 */
		private function create_image_attachement_from_url( $url, $parent_post_id = '' ) {

			if ( ! class_exists( 'WP_Http' ) ) {
				include_once ABSPATH . WPINC . '/class-http.php';
			}
			$http     = new WP_Http();
			$response = $http->request( $url, array( 'timeout' => 60 ) );
			if ( is_wp_error( $response ) || ( $response['response']['code'] != 200 ) ) {
				return false;
			}
			$upload = wp_upload_bits( basename( $url ), null, $response['body'] );
			if ( ! empty( $upload['error'] ) ) {
				return false;
			}
			$file_path        = $upload['file'];
			$file_name        = basename( $file_path );
			$file_type        = wp_check_filetype( $file_name, null );
			$attachment_title = str_replace( '-', ' ', sanitize_file_name( pathinfo( $file_name, PATHINFO_FILENAME ) ) );
			$wp_upload_dir    = wp_upload_dir();
			$post_info        = array(
				'guid'           => $wp_upload_dir['url'] . '/' . $file_name,
				'post_mime_type' => $file_type['type'],
				'post_title'     => $attachment_title,
				'post_content'   => '',
				'post_status'    => 'inherit',
			);
			// Create the attachment
			$attach_id = wp_insert_attachment( $post_info, $file_path, $parent_post_id );
			// Include image.php
			// require_once( ABSPATH . 'wp-admin/includes/image.php' );
			// Define attachment metadata
			include_once ABSPATH . 'wp-admin/includes/image.php';
			$attach_data = wp_generate_attachment_metadata( $attach_id, $file_path );
			// Assign metadata to attachment
			wp_update_attachment_metadata( $attach_id, $attach_data );

			return $attach_id;
		}
		private function getStockValue( $stock, $settings ) {
			$upper_stock_value = $stock;
			$lower_stock_value = 0;
			if ( $stock < $settings->get( 'random_stock_min' ) ) {
				return 0;
			} else {
				if ( $upper_stock_value > $settings->get( 'random_stock_max' ) ) {
					$upper_stock_value = $settings->get( 'random_stock_max' );
				}
				return rand( $lower_stock_value, $upper_stock_value );
			}
		}
	}
}
