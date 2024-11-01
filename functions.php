<?php
add_action( 'wp_ajax_tak_ajax_ali_dash_admin', 'tak_ajax_ali_dash_admin' );
function tak_ajax_ali_dash_admin() {

	if ( is_user_logged_in() && ( current_user_can( 'editor' ) || current_user_can( 'administrator' ) ) ) {
		$data_in   = json_decode( file_get_contents( 'php://input' ), true );
		$settings  = Takali_Settings::instance();
		$dash_ctlr = Takali_DashboardController::instance();

		if ( $_REQUEST['command'] === 'settings' ) {
			echo $dash_ctlr->get_settings();
			die();
		} elseif ( $_REQUEST['command'] === 'default' ) {
			$settings->reset();
			echo $dash_ctlr->get_settings();
			die();
		} elseif ( $_REQUEST['command'] === 'update' ) {
			$dash_ctlr->update_settings( $data_in, $settings, $_REQUEST['command'] );
			echo json_encode(
				array(
					'success' => true,
					'action'  => 'noupdate',
				)
			);
			die();
		} elseif ( $_REQUEST['command'] === 'updatekey' ) {
			$dash_ctlr->update_settings( $data_in, $settings, $_REQUEST['command'] );
			echo json_encode(
				array(
					'success' => true,
					'action'  => 'keyupdate',
				)
			);
			die();
		}
	} else {
		echo json_encode(
			array(
				'success' => false,
				'reason'  => 'not authorized',
			)
		);
		die();
	}
}
/**
 * Enqueue a script in the WordPress admin.
 *
 * @param int $hook Hook suffix for the current admin page.
 */
function tapi_enqueue_tak_ali_importer_script( $hook ) {
	if ( 'toplevel_page_tapi_taknalogy_ali_importer_dashboard' != $hook ) {
		return;
	}
	wp_enqueue_script( 'tak_ali_script', plugin_dir_url( __FILE__ ) . 'assets/js/script.js', array(), '1.0' );
	// wp_enqueue_script( 'tak_ali_scriptv2', plugin_dir_url( __FILE__ ) . 'assets/js/scriptv2.js', array(), '1.0' );
	wp_enqueue_style( 'tak_ali_style', plugin_dir_url( __FILE__ ) . 'assets/css/style.css', array(), '1.0' );

	/* bootstrap */
	wp_enqueue_style( 'takali-bootstrap-style', plugin_dir_url( __FILE__ ) . '/assets/js/bootstrap/css/bootstrap.min.css', array(), '1.0' );
	wp_enqueue_script( 'takali-bootstrap-js', plugin_dir_url( __FILE__ ) . '/assets/js/bootstrap/js/bootstrap.min.js', array( 'jquery' ), '1.0' );
}
add_action( 'admin_enqueue_scripts', 'tapi_enqueue_tak_ali_importer_script' );

function tapi_tak_ali_exporter_action_links( $actions, $post_object ) {
	if ( get_post_meta( $post_object->ID, '_tak_review_url', true ) ) {
		$actions['aliexpress_page'] = "<a target='_blank' class='tak_aliexpress_page' href='" . get_post_meta( $post_object->ID, '_tak_review_url', true ) . "'>" . __( '' . get_post_meta( $post_object->ID, '_tak_review_sup', true ) . '', 'taxnalogy-aliexpress-product-importer' ) . '</a>';
	}
	return $actions;
}
add_filter( 'post_row_actions', 'tapi_tak_ali_exporter_action_links', 10, 2 );

function tapi_register_custom_menu_page() {
	$page = add_menu_page(
		'Taxnalogy Aliexpress Product Importer Dashboard',
		'Tak Ali Importer',
		'edit_others_posts',
		'tapi_taknalogy_ali_importer_dashboard',
		'tapi_taknalogy_ali_importer_dashboard'
	);
}
add_action( 'admin_menu', 'tapi_register_custom_menu_page' );
function tapi_taknalogy_ali_importer_dashboard() {
	include plugin_dir_path( __FILE__ ) . 'templates/tapi-admin-menu.php';
}
function takali_update_options( $version ) {

	update_option( 'takaliepi_settings_version', $version );
	$options                 = get_option( 'takaliepi_settings' );
	$default_opt             = takali_default_settings();
	$default_opt['shopurl']  = get_site_url();
	$default_opt['auth_key'] = md5( uniqid( rand(), true ) );
	if ( is_array( $options ) && $options ) {
		$options = array_merge( $default_opt, $options );
		update_option( 'takaliepi_settings', $options );
	} else {
		add_option( 'takaliepi_settings', $default_opt );
	}
}
function takali_default_settings() {

	return $default_settings = array(
		'price_multi'                 => 5,
		'sale_multi'                  => 2.5,
		'category'                    => 123,
		// 'name' => 'Pets',
		// 'id'   => 17,
		// ),
		'download_images'             => 1,
		'import_product_images_limit' => 12,
		'import_short_description'    => 1,
		'import_long_description'     => 1,
		'import_attributes'           => 1,
		'default_product_status'      => 'publish',
		'manage_stock'                => 1,
		'use_random_stock'            => 1,
		'random_stock_min'            => 5,
		'random_stock_max'            => 100,
		'use_sku_number'              => 0,
		'taknalogy_reviews'           => 'yes',
		'taknalogy_reviews_widget'    => 'yes',
		'import_language'             => 'en',
		'local_currency'              => 'USD',
		'account_type'                => 'aliexpress',
	);
}
function tak_get_web_page( $url, $cookiesIn = '' ) {
		$options = array(
			CURLOPT_RETURNTRANSFER => true,     // return web page
			CURLOPT_HEADER         => true,     // return headers in addition to content
			CURLOPT_FOLLOWLOCATION => true,     // follow redirects
			CURLOPT_ENCODING       => '',       // handle all encodings
			CURLOPT_AUTOREFERER    => true,     // set referer on redirect
			CURLOPT_CONNECTTIMEOUT => 120,      // timeout on connect
			CURLOPT_TIMEOUT        => 120,      // timeout on response
			CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
			CURLINFO_HEADER_OUT    => true,
			CURLOPT_SSL_VERIFYPEER => false,     // Validate SSL Cert
			CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
			CURLOPT_COOKIE         => $cookiesIn,
		);

		$ch = curl_init( $url );
		curl_setopt_array( $ch, $options );
		$rough_content = curl_exec( $ch );
		$err           = curl_errno( $ch );
		$errmsg        = curl_error( $ch );
		$header        = curl_getinfo( $ch );
		curl_close( $ch );

		$header_content = substr( $rough_content, 0, $header['header_size'] );
		$body_content   = trim( str_replace( $header_content, '', $rough_content ) );
		$pattern        = '#Set-Cookie:\\s+(?<cookie>[^=]+=[^;]+)#m';
		preg_match_all( $pattern, $header_content, $matches );
		$cookiesOut = implode( '; ', $matches['cookie'] );

		$header['errno']   = $err;
		$header['errmsg']  = $errmsg;
		$header['headers'] = $header_content;
		$header['content'] = $body_content;
		$header['cookies'] = $cookiesOut;
		return $header;
}
