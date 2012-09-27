<?php
/*
Plugin Name: NonverBlaster:hover
Plugin URI: https://github.com/jacobbuck/wp-nonverblaster-hover
Description: Play audio and video files using the NonverBlaster:hover flash player, or HTML5 audio/video fallback.
Author: Jacob Buck
Author URI: http://jacobbuck.co.nz/
Version: 1.4.4
*/

require( plugin_dir_path( __FILE__ ) . '/class-nonverblaster-hover.php' );

$nonverblaster_hover = new NonverBlaster_Hover();