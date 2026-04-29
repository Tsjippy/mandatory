<?php
namespace TSJIPPY\MANDATORY;
use TSJIPPY;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'wp_enqueue_scripts', __NAMESPACE__.'\registerMandatoryScripts');

function registerMandatoryScripts(){
    wp_register_style('tsjippy_mandatory_style', TSJIPPY\pathToUrl(PLUGINPATH.'css/mandatory.min.css'), array(), PLUGINVERSION);
    wp_register_script('tsjippy_mandatory_script', TSJIPPY\pathToUrl(PLUGINPATH.'js/mandatory.min.js'), array('tsjippy_formsubmit_script'), PLUGINVERSION,true);
}