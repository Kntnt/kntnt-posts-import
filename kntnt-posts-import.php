<?php

/**
 * Plugin main file.
 *
 * @wordpress-plugin
 * Plugin Name:       Kntnt Posts Import
 * Plugin URI:        https://www.kntnt.com/
 * GitHub Plugin URI: https://github.com/Kntnt/kntnt-post-import
 * Description:       Provides a tool to import posts with images, attachments, author, terms and metadata exported with Kntnt Posts Export.
 * Version:           0.2.2
 * Author:            Thomas Barregren
 * Author URI:        https://www.kntnt.com/
 * License:           GPL-3.0+
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.txt
 */


namespace Kntnt\Posts_Import;

// Uncomment following line to debug this plugin except the Importer class.
define( 'KNTNT_POSTS_IMPORT_DEBUG', true );

set_error_handler( function ( $severity, $message, $file, $line ) {
    if ( ! ( error_reporting() & $severity ) ) {
        return;
    }
    throw new \ErrorException( $message, 0, $severity, $file, $line );
} );

require 'autoload.php';

//include 'KNTNT.php';

defined( 'WPINC' ) && new Plugin;