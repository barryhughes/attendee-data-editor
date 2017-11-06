<?php
/**
 * Plugin name: Attendee Data Editor
 * Description: Adds tools to edit or add attendee data from the admin attendee screen, building on facilities already provided by Event Tickets Plus.
 * Version:     1.1
 * Author:      Modern Tribe, Inc (Support Team)
 * Author URI:  https://theeventscalendar.com
 * License:     GPLv3
 *
 *     Attendee Data Editor for Event Tickets Plus
 *     Copyright (C) 2018 Modern Tribe, Inc
 *
 *     This program is free software; you can redistribute it and/or modify
 *     it under the terms of the GNU General Public License as published by
 *     the Free Software Foundation; either version 2 of the License, or
 *     (at your option) any later version.
 *
 *     This program is distributed in the hope that it will be useful,
 *     but WITHOUT ANY WARRANTY; without even the implied warranty of
 *     MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *     GNU General Public License for more details.
 *
 *     You should have received a copy of the GNU General Public License along
 *     with this program; if not, write to the Free Software Foundation, Inc.,
 *     51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 */

function tribe_attendee_data_editor() {
	static $main;

	if ( empty( $main ) ) {
		Tribe__Autoloader::instance()->register_prefixes( array(
			'Tribe__Tickets__Attendee_Data_Editor__' => __DIR__ . DIRECTORY_SEPARATOR . 'src'
		) );

		$main = new Tribe__Tickets__Attendee_Data_Editor__Main( plugin_dir_url( __FILE__ ) );
	}

	return $main;
}

add_action( 'tribe_tickets_plugin_loaded', 'tribe_attendee_data_editor', 20 );