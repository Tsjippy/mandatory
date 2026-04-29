<?php
namespace TSJIPPY\MANDATORY;

/**
 * Plugin Name:  		Tsjippy Mandatory Pages
 * Description:  		This plugin adds the possibility to make certain posts and pages mandatory. That means people have to mark the content as read. If they do not do so they will be reminded to read it until they do. A "I have read this" button will be automatically added to the e-mail if it is send by mailchimp. Adds one shortcode 'must_read_documents', which displays the pages to be read as links. Use like this <code>[must_read_documents]</code>.
 * Version:      		1.0.0
 * Author:       		Ewald Harmsen
 * AuthorURI:			harmseninnigeria.nl
 * Requires at least:	6.3
 * Requires PHP: 		8.3
 * Tested up to: 		6.9
 * Plugin URI:			https://github.com/Tsjippy/mandatory
 * Tested:				6.9
 * TextDomain:			tsjippy
 * Requires Plugins:	tsjippy-shared-functionality
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 *
 * @author Ewald Harmsen
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$pluginData = get_plugin_data(__FILE__, false, false);

// Define constants
define(__NAMESPACE__ .'\PLUGIN', plugin_basename(__FILE__));
define(__NAMESPACE__ .'\PLUGINPATH', __DIR__.'/');
define(__NAMESPACE__ .'\PLUGINVERSION', $pluginData['Version']);
define(__NAMESPACE__ .'\PLUGINSLUG', str_replace('tsjippy-', '', basename(__FILE__, '.php')));
define(__NAMESPACE__ .'\SETTINGS', get_option('tsjippy_'.PLUGINSLUG.'_settings', []));

// run on activation
register_activation_hook( __FILE__, function(){
} );

// run on deactivation
register_deactivation_hook( __FILE__, function(){
} );