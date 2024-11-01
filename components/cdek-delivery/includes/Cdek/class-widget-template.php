<?php

namespace WBCR\Delivery\Cdek;

use WBCR\Delivery\Cdek\Cdek;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * Add endpoint /wdcd-cdek-tmpl/ for example: https://site.dev/wdcd-cdek-tmpl/
 */
class Template_Bridge {
	public function __construct() {
		add_action( 'init', function () {
			add_rewrite_endpoint( 'wdcd-cdek-tmpl', EP_ROOT );
		} );

		add_action( 'wp_loaded', function () {
			$rules = get_option( 'rewrite_rules' );
			if ( ! isset( $rules['wdcd-cdek-tmpl(/(.*))?/?$'] ) ) {
				flush_rewrite_rules( $hard = false );
			}
		} );

		add_action( 'template_redirect', array( $this, 'request' ) );
	}

	function request() {
		$call = get_query_var( 'wdcd-cdek-tmpl', false );
		if ( $call === false ) {
			return;
		}

		header( 'Access-Control-Allow-Origin: *' );
		$files = scandir( $D = WDCD_PLUGIN_DIR . '/includes/Cdek/scripts/tpl' );
		unset( $files[0] );
		unset( $files[1] );

		$arTPL = array();

		foreach ( $files as $filesname ) {
			$file_tmp = explode( '.', $filesname );

			$arTPL[ strtolower( $file_tmp[0] ) ] = file_get_contents( $D . '/' . $filesname );
		}

		echo str_replace( array( '\r', '\n', '\t', "\n", "\r", "\t" ), '', json_encode( $arTPL ) );

		exit;
	}
}

new Template_Bridge;
