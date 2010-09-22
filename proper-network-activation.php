<?php
/*
Plugin Name: Proper Network Activation
Version: 1.0
Description: Use the network activation feature of WP MultiSite without problems
Author: scribu
Author URI: http://scribu.net/
Plugin URI: http://scribu.net/wordpress/proper-network-activation
Network: true

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

	static function init() {
		add_action( 'activated_plugin',  array( __CLASS__, 'action' ) );
		add_action( 'deactivated_plugin',  array( __CLASS__, 'action' ) );

		add_action('wpmu_new_blog', array(__CLASS__, 'setup'));
	}

	static function action( $plugin ) {
		if ( !is_plugin_active_for_network( $plugin ) )
			return;

		list( $action ) = explode( '_', current_filter(), 2 );

		$action = str_replace( 'activated', 'activate', $action );

		foreach ( self::get_active_blogs() as $blog_id ) {
			switch_to_blog( $blog_id );

			if ( in_array( $plugin, (array) get_option( 'active_plugins' ) ) )
				continue;

			self::do_action( $action, $plugin );
		}

		restore_current_blog();
	}

	static function setup( $blog_id ) {
		switch_to_blog( $blog_id );

		foreach ( array_keys( get_site_option( 'active_sitewide_plugins' ) ) as $plugin )
			self::do_action( 'activate', $plugin );

		restore_current_blog();
	}

	private static function do_action( $action, $plugin ) {
		do_action( $action . '_' . $plugin );
		do_action( $action . '_plugin', $plugin );
	}

	private static function get_active_blogs() {
		global $wpdb;

		return $wpdb->get_col( "
			SELECT blog_id
			FROM $wpdb->blogs
			WHERE site_id = '{$wpdb->siteid}'
			AND deleted = 0
		" );
	}
}

Proper_Network_Activation::init();

