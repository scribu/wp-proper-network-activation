<?php
/*
Plugin Name: Proper Network Activation
Version: 1.0.3
Description: Use the network activation feature of WP MultiSite without problems
Author: scribu
Author URI: http://scribu.net/
Plugin URI: http://scribu.net/wordpress/proper-network-activation
Network: true
Text Domain: proper-network-activation
Domain Path: /lang

Copyright (C) 2010 Cristi Burcă (scribu@gmail.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of the License, or
( at your option ) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

class Proper_Network_Activation {

	const AJAX_KEY = 'pna';

	static function init() {
		add_action( 'activated_plugin',  array( __CLASS__, 'queue' ), 10, 2 );
		add_action( 'deactivated_plugin',  array( __CLASS__, 'queue' ), 10, 2 );

		add_action( 'network_admin_notices', array( __CLASS__, 'admin_notices' ) );

		add_action( 'wp_ajax_' . self::AJAX_KEY, array( __CLASS__, 'ajax_response' ) );

		add_action( 'wpmu_new_blog', array( __CLASS__, 'setup' ) );
	}

	static function queue( $plugin, $network_wide = null ) {
		if ( !$network_wide )
			return;

		list( $action ) = explode( '_', current_filter(), 2 );

		$action = str_replace( 'activated', 'activate', $action );

		if ( !has_filter( $action . '_' . $plugin ) && !has_filter( $action . '_plugin' ) )
			return;

		$queue = get_site_option( "network_{$action}_queue", array() );
		if ( !in_array( $plugin, $queue ) ) {
			$queue[] = $plugin;
			update_site_option( "network_{$action}_queue", $queue );
		}
	}

	static function admin_notices() {
		if ( 'plugins-network' != get_current_screen()->id )
			return;

		$messages = array(
			'activate' => __( 'Performing network activation(s): %s / %s sites', 'proper-network-activation' ),
			'deactivate' => __( 'Performing network deactivation(s): %s / %s sites', 'proper-network-activation' ),
		);

		$action = false;
		foreach ( array_keys( $messages ) as $key )
			if ( isset( $_REQUEST[ $key ] ) || isset( $_REQUEST[ $key . '-multi' ] ) ) {
				$action = $key;
				break;
			}

		if ( !$action )
			return;

		$queue = get_site_option( "network_{$action}_queue", array() );

		if ( empty( $queue ) )
			return;

		$total = get_blog_count();

		$message = sprintf( $messages[ $action ],
			"<span id='pna-count-current'>0</span>",
			"<span id='pna-count-total'>$total</span>"
		);

		echo "<div class='updated'><p id='pna'>$message</p></div>";
?>
<script type="text/javascript">
jQuery(document).ready(function($) {
	var ajax_url = '<?php echo $ajax_url; ?>',
		_action = '<?php echo $action; ?>',
		total = <?php echo $total; ?>,
		offset = 0,
		count = 5;

	var $display = $('#pna-count-current');

	function done() {
		var data = {
			action: 'pna',
			_action: _action,
			done: 1
		}

		$.post(ajaxurl, data, jQuery.noop);
	}

	function call_again() {
		var data = {
			action: 'pna',
			_action: _action,
			offset: offset
		}

		if ( offset > total ) {
			done();
			$display.html(total);
			$('#pna').parent('div').fadeOut('slow');
			return;
		}

		$.post(ajaxurl, data, function(response) {
			$display.html(offset);

			offset += count;
			call_again();
		});
	}

	call_again();
});
</script>
<?php
	}

	static function ajax_response() {
		$action = $_POST['_action'];

		if ( isset( $_POST['done'] ) ) {
			delete_site_option( "network_{$action}_queue", array() );
			die(1);
		}

		$offset = (int) $_POST['offset'];

		$queue = get_site_option( "network_{$action}_queue", array() );

		global $wpdb;

		$blogs = $wpdb->get_col( $wpdb->prepare( "
			SELECT blog_id
			FROM {$wpdb->blogs}
			WHERE site_id = %d
			AND blog_id <> %d
			AND spam = '0'
			AND deleted = '0'
			AND archived = '0'
			ORDER BY registered DESC
			LIMIT %d, 5
		", $wpdb->siteid, $wpdb->blogid, $offset ) );

		foreach ( $blogs as $blog_id ) {
			switch_to_blog( $blog_id );

			foreach ( $queue as $plugin ) {
				self::do_action( $action, $plugin );
			}
		}

		die(1);
	}

	static function setup( $blog_id ) {
		switch_to_blog( $blog_id );

		foreach ( array_keys( get_site_option( 'active_sitewide_plugins' ) ) as $plugin )
			self::do_action( 'activate', $plugin );

		restore_current_blog();
	}

	private static function do_action( $action, $plugin ) {
		do_action( $action . '_' . $plugin, false );
		do_action( $action . '_plugin', $plugin, false );
	}
}

Proper_Network_Activation::init();

