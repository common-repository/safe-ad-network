<?php
/*
 * See https://developer.wordpress.org/plugins/the-basics/uninstall-methods/#uninstall-php.
 */
/*
 * If uninstall is not called from WordPress, exits.
 */
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) )
{
   exit();
}

$option_name = 'safe_ad_network_site';

delete_option( $option_name );

/*
 * For site options in Multisite. This is essentially the same as delete_option() but works network wide when using WP Multisite.
 */
delete_site_option( $option_name );

/*
 * Drops the database table.
 */
global $wpdb;
$wpdb->query( "DROP TABLE IF EXISTS safe_ad_network_ads" );
