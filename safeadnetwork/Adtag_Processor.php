<?php

namespace safeadnetwork;

use safeadnetwork\Database;

/*
 * A string processor that replaces <!--#safead tags with ad tags and embeds iframe beacons at the end of <body>.
 */
class Adtag_Processor
{
   private $database;

   function __construct()
   {
      /*
       * Makes an instance of Database.
       */
      $this->database = new Database();
   }

   public function process( $content )
   {
      while ( ( $start = strpos( $content, '<!--#safead', $start ) ) !== false )
      {
         $end = strpos( $content, '-->', $start ) + strlen( '-->' );
         $spot_start = strpos( $content, 'spot="', $start ) + strlen( 'spot="' );
         $spot_end = strpos( $content, '"', $spot_start );
         $spot = substr( $content, $spot_start, ( $spot_end - $spot_start ) );

         $tags = $this->database->get_tags( $spot );

         $ad = $tags[ 'ad' ];
         $beacon = $tags[ 'beacon' ];

         /*
          * If no tags found, the ad tag is replaced with an empty string. Ads are retrieved from the central SAN server when a page is loaded, and once ads are
          * retrieved, the next retrieval happens three minutes later. If nothing shows up, please reload the page, wait three minutes, and then reload the page
          * again.
          */
         $content = substr_replace( $content, $ad, $start, $end - $start );
         $start = $start + strlen( $ad );

         /*
          * Inserts iframes of spot beacons just before </body. If there is no tags found, nothing is inserted.
          */
         $body_end = strpos( $content, '</body', 0 );
         $content = substr_replace( $content, $beacon, $body_end, 0 );
      }

      /*
       * Returns the result.
       */
      return $content;
   }

}
