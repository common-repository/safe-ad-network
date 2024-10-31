<?php
/*
 * Plugin Name: Safe Ad Network
 * Plugin URI: https://kissy.one
 * Description: This plugin is a front-end of Safe Ad Network, which is an ad network that is built for bypassing adblocks, as well as high standard of control and openness to everyone.
 * Version: 20160909a
 * Author: Kissy
 * Author URI: https://kissy.one
 * License: GPL3
 *
 * Copyright (C) 2016 Kissy
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see http://www.gnu.org/licenses/.
 */

/*
 * Disallows direct execution of this file. See https://codex.wordpress.org/Writing_a_Plugin#Names.2C_Files.2C_and_Locations.
 */
defined ( 'ABSPATH' ) or die ( 'No script kiddies please!' );

/*
 * Enables class autoloading.
 */
require_once 'autoload.php';

use safeadnetwork\Database;
use safeadnetwork\Adtag_Processor;

/*
 * When the plugin is activated, executes safeadnetwork_activation_handler function.
 */
register_activation_hook ( __FILE__, 'safeadnetwork_activation_handler' );
function safeadnetwork_activation_handler() {
	/*
	 * Creates or updates this plugin's table. See http://codex.wordpress.org/Creating_Tables_with_Plugins#Creating_or_Updating_the_Table .
	 */
	$sql = file_get_contents ( __DIR__ . DIRECTORY_SEPARATOR . 'dbDelta.sql' );
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta ( $sql );
}

/*
 * When building the admin menu, executes safeadnetwork_admin_menu_handler.
 */
add_action ( 'admin_menu', 'safeadnetwork_admin_menu_handler' );
function safeadnetwork_admin_menu_handler() {
	/*
	 * Adds a new item to the Settings menu using function plugin_options_s3lS.
	 */
	add_options_page ( 'Safe Ad Network Plugin Options', 'Safe Ad Network', 'manage_options', 'safe-ad-network-plugin-options', 'plugin_options_ewl1' );
	
	/*
	 * Registers the option (i.e., an email address associated to the SAN account). To see the value stored in the database, use
	 * select * from wp_options where option_name = 'safe_ad_network_site';
	 */
	register_setting ( 'safe-ad-network-plugin-settings-group', 'safe_ad_network_site' );
}
function plugin_options_ewl1() {
	?>
<div class="wrap">
	<h2>Safe Ad Network</h2>
	<form method="post" action="options.php">
                <?php settings_fields( 'safe-ad-network-plugin-settings-group' ); ?>
                <?php do_settings_sections( 'safe-ad-network-plugin-settings-group' ); ?>
        <table class="form-table">
			<tr valign="top">
				<th scope="row">Website ID</th>
				<td><input type="text" name="safe_ad_network_site"
					value="<?php echo esc_attr( get_option('safe_ad_network_site') ); ?>"
					required></td>
			</tr>
		</table>
		<!-- TODO: Validate. -->
    <?php submit_button(); ?>
</form>
</div>
<?php
}

/*
 * On safeadnetwork_update_event, executes safeadnetwork_update_ads.
 */
add_action ( 'safeadnetwork_update_event', 'safeadnetwork_update_ads' );
function safeadnetwork_update_ads() {
	/*
	 * Instantiates Database.
	 */
	$database = new Database ();
	
	/*
	 * Loads ads.
	 */
	$database->load_ads ();
}

/*
 * Executes safeadnetwork_template_redirect_handler just before WordPress determines which template to load.
 */
add_action ( 'template_redirect', 'safeadnetwork_template_redirect_handler' );

/*
 * Asynchronously updates ads if they have not been updated for more than three minutes. Why three minutes? The more frequently ads get updated,
 * the more precisely each ad's probability of displaying is calculated because advertisers are continuously making payments for new ads (the
 * probability is calculated based on these payments). In terms of communication
 * cost, however, too much frequency causes overload at the central SAN server from which ads are retrieved. Three minutes should balance the
 * precision and cost.
 */
function safeadnetwork_template_redirect_handler() {
	/*
	 * This is an empty file used for deciding if safeadnetwork_update_ads should be invoked.
	 */
	$control_file = sys_get_temp_dir () . DIRECTORY_SEPARATOR . 'safeadnetwork_update_ads.control';
	
	/*
	 * If the file is younger than three minutes since the last update,
	 */
	if (file_exists ( $control_file ) && filemtime ( $control_file ) > time () - 180) {
		/*
		 * Does nothing and returns.
		 */
		return;
	}
	
	/*
	 * Updates (or creates) the file.
	 */
	touch ( $control_file );
	
	/*
	 * Throws safeadnetwork_update_event one second later.
	 */
	wp_schedule_single_event ( time () + 1, 'safeadnetwork_update_event' );
}

/*
 * This is executed just before WordPress determines which template to load (i.e., at the very beginning of page processing).
 * See
 * http://wordpress.stackexchange.com/a/41351,
 * http://www.dagondesign.com/articles/wordpress-hook-for-entire-page-using-output-buffering/, and
 * http://php.net/manual/en/language.types.callable.php.
 */
add_action ( 'template_redirect', function () {
	/*
	 * Creates an instance of Adtag_Processor and starts output buffering. Executes Ad_Tag_Processor's process method when the output buffer is flushed.
	 */
	ob_start ( array (
			new Adtag_Processor (),
			'process' 
	) );
}, 0 );

/*
 * Executes ob_end_flush just before PHP shuts down execution.
 */
add_action ( 'shutdown', function () {
	if (ob_get_status ()) {
		ob_end_flush ();
	}
}, 1000 );
