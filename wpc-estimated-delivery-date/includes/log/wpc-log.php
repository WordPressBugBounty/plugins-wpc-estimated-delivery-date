<?php
defined( 'ABSPATH' ) || exit;

register_activation_hook( defined( 'WPCED_LITE' ) ? WPCED_LITE : WPCED_FILE, 'wpced_activate' );
register_deactivation_hook( defined( 'WPCED_LITE' ) ? WPCED_LITE : WPCED_FILE, 'wpced_deactivate' );
add_action( 'admin_init', 'wpced_check_version' );

function wpced_check_version() {
	if ( ! empty( get_option( 'wpced_version' ) ) && ( get_option( 'wpced_version' ) < WPCED_VERSION ) ) {
		wpc_log( 'wpced', 'upgraded' );
		update_option( 'wpced_version', WPCED_VERSION, false );
	}
}

function wpced_activate() {
	wpc_log( 'wpced', 'installed' );
	update_option( 'wpced_version', WPCED_VERSION, false );
}

function wpced_deactivate() {
	wpc_log( 'wpced', 'deactivated' );
}

if ( ! function_exists( 'wpc_log' ) ) {
	function wpc_log( $prefix, $action ) {
		$logs = get_option( 'wpc_logs', [] );
		$user = wp_get_current_user();

		if ( ! isset( $logs[ $prefix ] ) ) {
			$logs[ $prefix ] = [];
		}

		$logs[ $prefix ][] = [
			'time'   => current_time( 'mysql' ),
			'user'   => $user->display_name . ' (ID: ' . $user->ID . ')',
			'action' => $action
		];

		update_option( 'wpc_logs', $logs, false );
	}
}