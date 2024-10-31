<?php

namespace safeadnetwork;

class Database {
	/*
	 * The central SAN server's api for retrieving ads.
	 */
	const GET_ADS_URL = 'https://kissy.one/api/getAds';
	
	/*
	 * Dictionary files for creating random file names.
	 */
	private $web2a;
	private $web2;
	
	public function __construct() {
		/*
		 * Opens the dictionary files.
		 */
		$this->web2a = file ( __DIR__ . DIRECTORY_SEPARATOR . 'web2a' );
		$this->web2 = file ( __DIR__ . DIRECTORY_SEPARATOR . 'web2' );
		
		/*
		 * This must be here. Otherwise, when multisite mode is enabled, an error occurs in phpunit testing. See
		 * https://codex.wordpress.org/Class_Reference/wpdb#Using_the_.24wpdb_Object.
		 */
		global $wpdb;		
	}
	
	/*
	 * Gets ads from the central SAN server.
	 */
	public function load_ads() {
		/*
		 * If Multisite support is enabled,
		 */
		if (is_multisite ()) {
			/*
			 * Gets an array of sites.
			 */
			$sites = wp_get_sites ();
			
			/*
			 * Loops through them.
			 */
			foreach ( $sites as $site ) {
				/*
				 * Executes load_ads_blog.
				 */
				$this->load_ads_blog ( $site ['blog_id'] );
			}
		} else {
			/*
			 * Executes load_ads_blog and returns the result.
			 */
			return $this->load_ads_blog ();
		}
	}
	
	/*
	 * Externalized for testability.
	 */
	public function get_ads($san_site_id) {
		return file_get_contents ( self::GET_ADS_URL . '?site=' . $san_site_id );
	}
	private function load_ads_blog($blog_id = null) {
		/*
		 * If Multisite support is enabled,
		 */
		if (is_multisite ()) {
			/*
			 * Switches to the given blog.
			 */
			switch_to_blog ( $blog_id );
		}
		
		/*
		 * Gets the SAN site ID from wp_options table.
		 */
		$san_site_id = get_option ( 'safe_ad_network_site' );
		
		/*
		 * Gets ads.
		 */
		$ads_json = $this->get_ads ( $san_site_id );
		
		/*
		 * Decodes them to an array.
		 */
		$ads_array = json_decode ( $ads_json );
		
		/*
		 * Gets the global $wpdb object;
		 */
		global $wpdb;
		
		/*
		 * Gets the current rows.
		 */
		$rows = $wpdb->get_results ( $wpdb->prepare ( "select imagefile from safe_ad_network_ads where site = %s", $san_site_id ), OBJECT );
		
		/*
		 * Deletes image files.
		 */
		foreach ( $rows as $row ) {
			unlink ( $row->imagefile );
		}
		
		/*
		 * Delete the current rows.
		 */
		$wpdb->query ( $wpdb->prepare ( 'delete from safe_ad_network_ads where site = %s', $san_site_id ) );
		
		/*
		 * Loops through the elements.
		 */
		foreach ( $ads_array as $ad ) {
			/*
			 * Gets the image type.
			 */
			$image_type = substr ( substr ( $ad->image, strlen ( 'data:image/' ) ), 0, 3 );
			
			/*
			 * Makes a random image file name.
			 */
			$filename = self::get_image_filename ( $ad->width, $ad->height, $image_type );
			
			/*
			 * Gets the upload directory.
			 */
			$upload_dir = wp_upload_dir ();
			
			/*
			 * If the directory does not exist, creates it. If failure occurs, returns.
			 */
			if (! file_exists ( $upload_dir ['path'] ) && ! mkdir ( $upload_dir ['path'], 0777, true )) {
				error_log ( 'Failed to create directory ' . $upload_dir ['path'] );
				return;
			}
			
			/*
			 * Saves the image file. If fails, returns.
			 */
			if (file_put_contents ( $upload_dir ['path'] . DIRECTORY_SEPARATOR . $filename, base64_decode ( substr ( $ad->image, strpos ( $ad->image, 'base64,' ) + strlen ( 'base64,' ) ) ) ) == false) {
				error_log ( 'Failed to save image file ' . $upload_dir ['path'] . DIRECTORY_SEPARATOR . $filename );
				return;
			}
			
			/*
			 * Inserts the ad to the table.
			 */
			if ($wpdb->insert ( 'safe_ad_network_ads', array (
					'site' => $ad->site,
					'spot' => $ad->spot,
					'campaign' => $ad->campaign,
					'imageurl' => $upload_dir ['url'] . '/' . $filename,
					'imagefile' => $upload_dir ['path'] . DIRECTORY_SEPARATOR . $filename,
					'destination' => $ad->destination,
					'probability' => $ad->probability,
					'width' => $ad->width,
					'height' => $ad->height,
					'beacon' => $ad->beacon 
			) ) == false) {
				error_log ( 'Failed to insert row into table safe_ad_network_ads' );
				return;
			}
		}
	}
	
	/*
	 * Generates a random image file name.
	 */
	private function get_image_filename($width, $height, $image_type) {
		/*
		 * Selects a random line from web2a.
		 */
		$line = rtrim ( $this->web2a [rand ( 0, count ( $this->web2a ) - 1 )] );
		
		/*
		 * Adds a random line from web2.
		 */
		$line = $line . '-' . rtrim ( $this->web2 [rand ( 0, count ( $this->web2 ) - 1 )] );
		
		/*
		 * Replaces all white spaces with '-' and lowers the case.
		 */
		$line = strtolower ( str_replace ( ' ', '-', $line ) );
		
		/*
		 * Adds the size and extension, then returns the result.
		 */
		return $line . '-' . $width . 'x' . $height . '.' . $image_type;
	}
	
	/*
	 * Externalized for testability.
	 */
	public function get_ad_rows($spot_id) {
		/*
		 * Selects rows that are not deprecated. If selects deprecated rows, there may be a user who sees a broken link icon image on every ad retrieval interval.
		 * This is because ad retrieval erases deprecated images. It is a better experience to see nothing, rather than to see that icon. Maybe this problem
		 * occurs only accessing the web server on localhost. In a typical non-localhost circumstance, it takes some time to retrieve ads from the central server,
		 * so image deletion should happen after the user got an ad image. Nonetheless, that image is a deprecated ad. It is better to show nothing than showing a
		 * deprecated ad.
		 */
		global $wpdb;
		return $wpdb->get_results ( $wpdb->prepare ( "select * from safe_ad_network_ads where spot = %s and rowts > now() - interval 3 minute order by probability desc", $spot_id ), OBJECT );
	}
	public function get_tags($spot_id) {
		/*
		 * $wpdb refers to the global $wpdb object.
		 */
		global $wpdb;
		
		/*
		 * Gets rows of ads.
		 */
		$ad_rows = $this->get_ad_rows ( $spot_id );
		
		/*
		 * Makes a random number between 0 and 1 for picking up a row according to its probability of showing.
		 */
		$random = mt_rand () / mt_getrandmax ();
		
		foreach ( $ad_rows as $row ) {
			/*
			 * Adds the probability.
			 */
			$sum_probability += $row->probability;
			
			/*
			 * If the random number is smaller than the sum,
			 */
			if ($random <= $sum_probability) {
				/*
				 * Creates an ad tag of this row.
				 */
				$ad = '<a href="' . $row->destination . '"><img style="width:' . $row->width . 'px;height:' . $row->height . 'px;" src="' . $row->imageurl . '"></a>';
				$beacon = '<script src="' . $row->beacon . '?spot=' . $row->spot . '&campaign=' . $row->campaign . '" async></script>';
				
				/*
				 * Returns the tags.
				 */
				return array (
						'ad' => $ad,
						'beacon' => $beacon 
				);
			}
		}
	}
}
