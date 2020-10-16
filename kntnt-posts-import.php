<?php

/**
 * Plugin main file.
 *
 * @wordpress-plugin
 * Plugin Name:       Kntnt Posts Import
 * Plugin URI:        https://www.kntnt.com/
 * GitHub Plugin URI: https://github.com/Kntnt/kntnt-post-import
 * Description:       Provides a tool for importing pages and posts.
 * Version:           0.1.0
 * Author:            Thomas Barregren
 * Author URI:        https://www.kntnt.com/
 * License:           GPL-3.0+
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.txt
 */


namespace Kntnt\Posts_Import;

// Uncomment following line to debug this plugin.
define( 'KNTNT_POSTS_IMPORT_DEBUG', true );

require 'autoload.php';

defined( 'WPINC' ) && new Plugin;