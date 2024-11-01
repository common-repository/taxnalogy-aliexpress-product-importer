<?php
/**
 * Main class to wrap functionality
 */
final class Class_Taknalogy_Aliexpress_Main {
	/**
	 * A reference to an instance of this class.
	 */
	private static $instance;
	/**
	 * Returns an instance of this class.
	 */
	public static function instance() {
		if ( null == self::$instance ) {
			self::$instance = new Class_Taknalogy_Aliexpress_Main();
		}
		return self::$instance;
	}
	/**
	 * Initializes the plugin by setting filters and administration functions.
	 */
	private function __construct() {
		add_action( 'wp_ajax_tak_ajax_ali_importer', array( $this, 'tak_ajax_ali_importer' ) );
	}


	function tak_ajax_ali_importer() {
		if ( isset( $_POST ) && isset( $_POST['form_data'] ) ) {
			parse_str( $_POST['form_data'], $input_form );
			$nonce = $input_form['_wpnonce'];
			if ( is_user_logged_in() && ( current_user_can( 'editor' ) || current_user_can( 'administrator' ) ) ) {
				if ( wp_verify_nonce( $nonce, 'get_product_form' ) && ( $_POST['form_name'] === 'get_product_form' ) && ( ! empty( $input_form['url'] ) ) ) {
					$url                = strip_tags(
						stripslashes(
							filter_var( $input_form['url'], FILTER_VALIDATE_URL )
						)
					);
					$regular_multiplier = isset( $input_form['regular_multiplier'] ) ? sanitize_text_field( $input_form['regular_multiplier'] ) : 0;
					$delivery_charge    = isset( $input_form['delivery_charge'] ) ? sanitize_text_field( $input_form['delivery_charge'] ) : 0;
					$sale_multiplier    = isset( $input_form['sale_multiplier'] ) ? sanitize_text_field( $input_form['sale_multiplier'] ) : 0;
					if ( ! empty( $input_form['product_onsale'] ) ) {
						$response = $this->get_product_from_ali( $url, $regular_multiplier, $delivery_charge, $sale_multiplier );
					} else {
						$response = $this->get_product_from_ali( $url, $regular_multiplier, $delivery_charge );
					}
					if ( $response['post_id'] !== '00000' ) {
						update_post_meta( $response['post_id'], '_tak_review_url', strtok( $url, '?' ) );
						update_post_meta( $response['post_id'], '_tak_review_sup', 'aliexpress' );
						update_post_meta( $response['post_id'], '_tak_review_active', 'yes' );
					}
					$response['product_type'] = __( $response['product_type'], 'taxnalogy-aliexpress-product-importer' );
					$response['status']       = __( $response['status'], 'taxnalogy-aliexpress-product-importer' );
					$this->send_response_back(
						$response['post_id'],
						'product_created',
						null,
						null,
						$response
					);
				} elseif ( wp_verify_nonce( $nonce, 'get_product_images' ) && ( $_POST['form_name'] === 'get_product_images' ) && ( ! empty( $input_form['post_id'] ) ) ) {
					if ( $_POST['form_btn'] == 'get_images' || $_POST['cust_form_btn'] == 'get_images' ) {
						$post_id  = sanitize_text_field( $input_form['post_id'] );
						$out_come = $this->create_product_images( $post_id, get_post_meta( $post_id, '_tak_json_data', true ) );
						if ( $out_come == 'gallery_created' ) {
							$msg = __( 'Images created successfully', 'taxnalogy-aliexpress-product-importer' );
						} elseif ( $out_come == 'gallery_found' ) {
							$msg = __( 'Images found', 'taxnalogy-aliexpress-product-importer' );
						} else {
							$msg = __( 'Images creation failed', 'taxnalogy-aliexpress-product-importer' );
						}
						$this->send_response_back( $input_form['post_id'], $out_come, $input_form['created_product_url'], null, $msg );
					} elseif ( $_POST['form_btn'] == 'create_attributes' || $_POST['cust_form_btn'] == 'create_attributes' ) {
						$post_id = sanitize_text_field( $input_form['post_id'] );
						if ( $this->create_product_attributes( $post_id, get_post_meta( $post_id, '_tak_json_data', true ) ) ) {
							$this->send_response_back( $input_form['post_id'], 'product_attributes_created', $input_form['created_product_url'], null, __( 'Attributes created successfully', 'taxnalogy-aliexpress-product-importer' ) );
						} else {
							$this->send_response_back( $input_form['post_id'], 'product_attributes_failed', $input_form['created_product_url'], null, __( 'Attributes creation failed', 'taxnalogy-aliexpress-product-importer' ) );
						}
					} elseif ( $_POST['form_btn'] == 'create_variation_data' || $_POST['cust_form_btn'] == 'create_variation_data' ) {
						$post_id                   = isset( $input_form['post_id'] ) ? sanitize_text_field( $input_form['post_id'] ) : 0;
						$regular_multiplier_hidden = isset( $input_form['regular_multiplier_hidden'] ) ? sanitize_text_field( $input_form['regular_multiplier_hidden'] ) : 0;
						$delivery_charge_hidden    = isset( $input_form['delivery_charge_hidden'] ) ? sanitize_text_field( $input_form['delivery_charge_hidden'] ) : 0;
						$sale_multiplier_hidden    = isset( $input_form['sale_multiplier_hidden'] ) ? sanitize_text_field( $input_form['sale_multiplier_hidden'] ) : 0;
						if ( $input_form['product_onsale_hidden'] === 'true' ) {
							$variation_list = $this->extract_variation_data(
								$post_id,
								get_post_meta( $post_id, '_tak_json_data', true ),
								$regular_multiplier_hidden,
								$delivery_charge_hidden,
								$sale_multiplier_hidden
							);
						} else {
							$variation_list = $this->extract_variation_data(
								$post_id,
								get_post_meta( $post_id, '_tak_json_data', true ),
								$regular_multiplier_hidden,
								$delivery_charge_hidden
							);
						}
						if ( ! empty( $variation_list ) ) {
							update_post_meta( $post_id, '_tak_variation_list', $variation_list );
							$this->send_response_back( $post_id, 'product_variations_data_created', $input_form['created_product_url'], null, __( 'Variation data created successfully', 'taxnalogy-aliexpress-product-importer' ) );
						} else {
							$this->send_response_back( $post_id, 'product_variations_data_failed', $input_form['created_product_url'], null, __( 'Variation data creation failed', 'taxnalogy-aliexpress-product-importer' ) );
						}
					} elseif ( $_POST['form_btn'] == 'submit_save_var' || $_POST['cust_form_btn'] == 'submit_save_var' ) {
						$post_id            = isset( $input_form['post_id'] ) ? sanitize_text_field( $input_form['post_id'] ) : 0;
						$outcome['outcome'] = true;
						try {
							$outcome = $this->save_variations( $post_id );
						} catch ( Exception $e ) {
							$outcome = false;
						}
						if ( $outcome['outcome'] ) {
							$this->send_response_back( $post_id, 'product_variations_saved', $input_form['created_product_url'], null, __( 'Variations created successfully', 'taxnalogy-aliexpress-product-importer' ) );
						} else {
							$this->send_response_back( $post_id, 'product_variations_failed', $input_form['created_product_url'], null, $outcome['message'] );
						}
					}
				}
			} else {
				die();
			}
		}
	}

	function send_response_back( $post_id, $action, $purl, $variation_list = null, $message = null ) {
		$response = array(
			'action'         => $action,
			'id'             => $post_id,
			'purl'           => $purl,
			'variation_list' => $variation_list,
			'message'        => $message,
		);
		wp_send_json_success( $response );
	}


	function get_product_from_ali( $purl, $regular_multiplier, $delivery_charge, $sale_multiplier = null ) {

		$opts     = array(
			'http' => array(
				'timeout'    => 60,
				'method'     => 'GET',
				'header'     => "Accept-language: en\r\n",
				'User-Agent' => "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/39.0.2171.95 Safari/537.36\r\n",
			),
		);
		$context  = stream_context_create( $opts );
		$html     = ( @file_get_contents( $purl, false, $context ) );
		$response = array();
		if ( $html === false ) {
			$response['outcome'] = 'error';
		}
		$args               = array(
			'post_type'  => 'product',
			'meta_query' => array(
				array(
					'key'     => '_tak_review_url',
					'value'   => strtok( $purl, '?' ),
					'compare' => '=',
				),
			),
		);
		$response['status'] = 'invalid';
		$query              = new WP_Query( $args );
		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$response ['post_id']      = get_the_ID();
				$response ['product_type'] = ucwords( ( get_the_terms( get_the_ID(), 'product_type' ) )[0]->name ) . ' product';
				$response ['status']       = 'found';
				break;
			}
		}
		try {
			$doc = new DOMDocument();
			libxml_use_internal_errors( true );
			if ( ! empty( $html ) ) {
				$doc->loadHTML( $html );
			} else {
				 throw new Exception( 'cannot retrieve product ' );
			}
			libxml_clear_errors();
			$xpath = new DOMXpath( $doc );
		} catch ( Exception $e ) {
			return $response = array(
				'post_id'      => '00000',
				'product_type' => 'error',
				'status'       => $e->getMessage(),
			);
		}
		
		foreach ( ( $xpath->query( '//body//script[string-length(text()) > 1]' ) ) as $query_result ) {
			if ( preg_match( '#^window.runParams#i', trim( $query_result->textContent ) ) === 1 ) {
				$raw              = trim( $query_result->textContent );
				$raw              = preg_replace( '/(window.runParams)[ \t]+[=][ \t]+[{][ \t\n\r]+(data:)/', '', $raw );
				$raw              = preg_replace( '/[, \t\r\n]+(csrfToken:).[\s\S]*/', '', $raw );
				$json_data        = json_decode( $raw, false );
				$description_url  = $json_data->descriptionModule->descriptionUrl;
				$description_html = ( file_get_contents( $description_url, false, $context ) );
				if ( ( ! empty( $response['status'] ) ) && $response['status'] == 'found' ) {
					update_post_meta( $response['post_id'], '_tak_json_data', $json_data );
					return $response;
				}
				try {
					if ( empty( $json_data->skuModule->productSKUPropertyList ) ) {
						return $this->create_simple_product( $json_data, $description_html, $regular_multiplier, $delivery_charge, $sale_multiplier );
						// error_log( 'product is simple' );
					} else {
						return $this->create_variable_product( $json_data, $description_html, $regular_multiplier, $delivery_charge, $sale_multiplier );
						// error_log( 'product is variable' );
					}
				} catch ( exception $e ) {
					return $response = array(
						'post_id'      => '00000',
						'product_type' => 'error',
						'status'       => $e->getMessage(),
					);
				}
			}
		}
		echo '<div id="message" class="updated success"><p>' . __( 'Product import was successful', 'taxnalogy-aliexpress-product-importer' ) . '</p></div>';
		exit;
	}
	/**
	 * Creates variable bbase product
	 */
	function create_variable_product( $json_data, $description_html, $regular_multiplier, $delivery_charge, $sale_multiplier = null ) {

		$product = new WC_Product_Variable();
		$product->set_status( 'draft' );
		$product->set_name( substr( sanitize_text_field( $json_data->pageModule->title ), 0, 34 ) );
		$product->set_short_description( sanitize_text_field( $json_data->pageModule->title ) );
		$product->set_description( $description_html );
		$post_id = $product->save();
		update_post_meta( $post_id, '_tak_json_data', $json_data );
		$response = array(
			'post_id'      => $post_id,
			'product_type' => 'Variable product',
			'status'       => 'created',
		);
		return $response;
	}
	/**
	 * Creates feature and product gallery images
	 */
	function create_product_images( $post_id, $json_data ) {

		$product = wc_get_product( $post_id );
		if ( empty( $product->get_gallery_image_ids() ) ) {
			$image_list = array();
			foreach ( $json_data->imageModule->imagePathList as $glist ) {
				$image_id = $this->create_image_attachement_from_url( $glist, $post_id );
				if ( $image_id ) {
					array_push( $image_list, $image_id );
				} else {
					return 'gallery_error';
				}
			}
			$product->set_image_id( $image_list[0] );
			$product->set_gallery_image_ids( $image_list );
			$post_id = $product->save();
			return 'gallery_created';
		} else {
			return 'gallery_found';
		}
	}
	/**
	 * Creates product attributes
	 */
	function create_product_attributes( $post_id, $json_data ) {

		foreach ( $json_data->specsModule->props as $prop ) {
			$attr                = $this->create_attribute( $prop->attrName, $prop->attrValue );
			$thedata             = array(
				'pa_' . $prop->attrName => array(
					'name'         => wc_sanitize_taxonomy_name( $prop->attrName ),
					'is_visible'   => '1',
					'is_variation' => '0',
					'is_taxonomy'  => '0',
					'value'        => implode( '|', preg_split( '/[,;|]/', $prop->attrValue, -1, PREG_SPLIT_NO_EMPTY ) ),
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
		return true;
	}
	/**
	 * creates variation attributes
	 */
	function extract_variation_data( $post_id, $json_data, $regular_multiplier, $delivery_charge, $sale_multiplier = null ) {
		// $product = wc_get_product( $post_id );
		if ( empty( $json_data->skuModule->productSKUPropertyList ) ) {
			return false;
		}

		$sku_list = array();
		foreach ( $json_data->skuModule->productSKUPropertyList as $sku_prop ) {
			$sku_variable = '';
			foreach ( $sku_prop->skuPropertyValues as $sku_values ) {
				if ( ! empty( $sku_values->propertyValueDisplayName ) ) {
					$sku_variable = $sku_values->propertyValueDisplayName . '|' . $sku_variable;
				}
				$sku_list[ $sku_values->propertyValueId ] = array(
					'image' => isset( $sku_values->skuPropertyImagePath ) ? $sku_values->skuPropertyImagePath : '',
					'name'  => $sku_prop->skuPropertyName,
					'value' => $sku_values->propertyValueDisplayName,
				);
			}
			$attr                = $this->create_attribute( $sku_prop->skuPropertyName, $sku_variable );
			$thedata             = array(
				'name'         => wc_sanitize_taxonomy_name( $sku_prop->skuPropertyName ),
				'is_visible'   => '1',
				'is_variation' => '1',
				'is_taxonomy'  => '0',
				'value'        => implode(
					'|',
					preg_split( '/[,;|]/', $sku_variable, -1, PREG_SPLIT_NO_EMPTY )
				),
			);
				
			error_log(print_r( $attr, true));
			error_log(print_r( $thedata, true));
			$_product_attributes = get_post_meta( $post_id, '_product_attributes', true );
			if ( empty( $_product_attributes ) ) {
				update_post_meta( $post_id, '_product_attributes', array( 'pa_' . $sku_prop->skuPropertyName => $thedata ) );
			} else {
				$_product_attributes[ 'pa_' . $sku_prop->skuPropertyName ] = $thedata;
				update_post_meta( $post_id, '_product_attributes', $_product_attributes );
			}
		}
		$image_unique = array();
		foreach ( $json_data->skuModule->skuPriceList as $sku_price ) {
			if ( ! empty( $sku_price->skuVal->skuActivityAmount ) ) {
				$price = $sku_price->skuVal->skuActivityAmount->value;
			} else {
				$price = $sku_price->skuVal->skuAmount->value;
			}
			$variation_list[ $sku_price->skuId ] = array(
				// 'variation'     => $sku_price->skuPropIds,
				'regular_price' => round( ( ( $price * $regular_multiplier ) + $delivery_charge ), 2 ),
				'sale_price'    => is_null( $sale_multiplier ) ? '' : round( ( ( $price * $sale_multiplier ) + $delivery_charge ), 2 ),
				'sku'           => $sku_price->skuId,
				'stock_qty'     => $sku_price->skuVal->inventory,
			);
			foreach ( ( explode( ',', $sku_price->skuPropIds ) ) as $sku_prop_id ) {
				if ( ! empty( $sku_list[ $sku_prop_id ] ['image'] ) ) {
					$variation_list[ $sku_price->skuId ]['image']        = $sku_list[ $sku_prop_id ] ['image'];
					$image_unique[ $sku_list[ $sku_prop_id ] ['image'] ] = $sku_list[ $sku_prop_id ] ['image'];
				}
				$variation_list[ $sku_price->skuId ]['props'][ $sku_list[ $sku_prop_id ]['name'] ] = $sku_list[ $sku_prop_id ]['value'];
			}
		}
		foreach ( $image_unique as $key => $value ) {
			$image_id = $this->create_image_attachement_from_url( $value );
			if ( $image_id ) {
				$image_unique[ $key ] = $image_id; // create_image_attachement_from_url( $value );
			} else {
				return false;
			}
		}
		foreach ( $variation_list as $key => $value ) {
			if ( ! empty( $variation_list[ $key ]['image'] ) ) {
				$variation_list[ $key ]['image'] = $image_unique[ $variation_list[ $key ]['image'] ];
			}
		}
		// replace $json_data with variation list here

		error_log( print_r( $variation_list, true));

		return $variation_list;
	}
	/**
	 * This function creates variation from data created previously
	 */
	function save_variations( $post_id, $var_list = '' ) {
		$response['outcome'] = false;
		$product             = wc_get_product( $post_id );
		$variation_list      = get_post_meta( $post_id, '_tak_variation_list', true );
		if ( empty( $variation_list ) ) {
			$response['message'] = __( 'Variation data not found', 'taxnalogy-aliexpress-product-importer' );
			return $response;
		}
		foreach ( $variation_list as $var_list ) {
			$args  = array(
				'post_type'  => 'product_variation',
				'meta_query' => array(
					array(
						'key'     => '_sku',
						'value'   => $var_list['sku'],
						'compare' => '=',
					),
				),
			);
			$query = new WP_Query( $args );
			if ( $query->have_posts() ) {
				while ( $query->have_posts() ) {
					$query->the_post();
					$response['message'] = __( 'Sku Conflict ' . get_the_ID(), 'taxnalogy-aliexpress-product-importer' );
					break;
				}
				return $response;
			}
			$variation_post = array(
				'post_title'  => $product->get_title(),
				'post_name'   => $product->get_title(), // 'product-' . $post_id . '-variation',
				'post_status' => 'publish',
				'post_parent' => $post_id,
				'post_type'   => 'product_variation',
			// 'guid'        => $product->get_permalink(),
			);
			// Creating the product variation
			$variation_id = wp_insert_post( $variation_post );
			// Get an instance of the WC_Product_Variation object
			$variation = new WC_Product_Variation( $variation_id );
			foreach ( $var_list['props'] as $vkey => $vvalue ) {
				$taxonomy  = 'pa_' . wc_sanitize_taxonomy_name( $vkey );
				$term_slug = get_term_by( 'name', $vvalue, $taxonomy )->slug; // Get the term slug
				// Get the post Terms names from the parent variable product.
				$post_term_names = wp_get_post_terms( $post_id, $taxonomy, array( 'fields' => 'names' ) );
				// Check if the post term exist and if not we set it in the parent variable product.
				if ( ! in_array( $vvalue, $post_term_names ) ) {
					wp_set_post_terms( $post_id, $vvalue, $taxonomy, true );
				}
				// Set/save the attribute data in the product variation
				update_post_meta( $variation_id, 'attribute_' . wc_sanitize_taxonomy_name( $vkey ), $vvalue );
			}
			// set variation image
			/*
			$update_attachment = array(
			'ID'          => $var_list['image'],
			'post_parent' => $variation->get_id(),
			);
			wp_insert_attachment( $update_attachment );
			*/
			$variation->set_image_id( $var_list['image'] ); // create_image_attachement_from_url( $var_list['image'], $variation->get_id() ) );
			// Prices
			if ( empty( $var_list['sale_price'] ) ) {
				$variation->set_price( $var_list['regular_price'] );
			} else {
				$variation->set_price( $var_list['sale_price'] );
				$variation->set_sale_price( $var_list['sale_price'] );
			}
			$variation->set_regular_price( $var_list['regular_price'] );
			// sku
			$variation->set_sku( $var_list['sku'] );
			// Stock
			if ( ! empty( $var_list['stock_qty'] ) ) {
				$variation->set_stock_quantity( $var_list['stock_qty'] );
				$variation->set_manage_stock( true );
				$variation->set_stock_status( '' );
			} else {
				$variation->set_manage_stock( false );
			}
			$variation->set_weight( '' ); // weight (reseting)
			$variation->save(); // Save the data
			// $variation->variable_product_sync();
		}
		delete_post_meta( $post_id, '_tak_json_data' );
		delete_post_meta( $post_id, '_tak_variation_list' );

		WC_Product_Variable::sync( $post_id );

		// wc_delete_product_transients( '_transient_wc_var_prices_' . $post_id );
		// wc_delete_product_transients( '_transient_wc_product_children_' . $post_id );

		// $product->variable_product_sync( $post_id );
		$response['outcome'] = true;
		$response['message'] = __( 'Variations Created Successfully', 'taxnalogy-aliexpress-product-importer' );
		return $response;
	}

	/**
	 * @since 1.0.0
	 *
	 * @param string        $raw_name Name of attribute to create.
	 * @param array(string) $terms          Terms to create for the attribute.
	 * @return array
	 * Thanks to Claudio Sanches [ https://github.com/claudiosanches ]
	 */
	function create_attribute( $raw_name = 'size', $v ) {

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
	function create_image_attachement_from_url( $url, $parent_post_id = '' ) {

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
		$attach_data = wp_generate_attachment_metadata( $attach_id, $file_path );
		// Assign metadata to attachment
		wp_update_attachment_metadata( $attach_id, $attach_data );

		return $attach_id;
	}
	/**
	 * Creates a simple product
	 */
	function create_simple_product( $json_data, $description_html, $regular_multiplier, $delivery_charge, $sale_multiplier = null ) {

		// https://www.aliexpress.com/item/32875890342.html

		$product = new WC_Product_Simple();
		$product->set_status( 'draft' );
		$product->set_name( substr( sanitize_text_field( $json_data->pageModule->title ), 0, 34 ) );
		$product->set_short_description( sanitize_text_field( $json_data->pageModule->title ) );
		$product->set_description( $description_html );
		if ( ! empty( $json_data->skuModule->skuPriceList[0]->skuVal->skuActivityAmount ) ) {
			$price = $json_data->skuModule->skuPriceList[0]->skuVal->skuActivityAmount->value;
		} else {
			$price = $json_data->skuModule->skuPriceList[0]->skuVal->skuAmount->value;
		}
		$product->set_sku( $json_data->skuModule->skuPriceList[0]->skuId );
		$product->set_regular_price( round( ( ( $price * $regular_multiplier ) + $delivery_charge ), 2 ) );
		if ( ! is_null( $sale_multiplier ) ) {
			$product->set_sale_price( round( ( ( $price * $sale_multiplier ) + $delivery_charge ), 2 ) );
			$product->set_price( round( ( ( $price * $sale_multiplier ) + $delivery_charge ), 2 ) );
		} else {
			$product->set_price( round( ( ( $price * $regular_multiplier ) + $delivery_charge ), 2 ) );
		}
		$product->set_manage_stock( true );
		$product->set_stock_quantity( $json_data->skuModule->skuPriceList[0]->skuVal->inventory );
		$post_id = $product->save();
		update_post_meta( $post_id, '_tak_json_data', $json_data );
		$response = array(
			'post_id'      => $post_id,
			'product_type' => 'Simple product',
			'status'       => 'created',
		);
		return $response;
	}
}
