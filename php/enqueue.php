<?php
namespace SIM\MANDATORY;
use SIM;

add_action( 'wp_enqueue_scripts', __NAMESPACE__.'\registerMandatoryScripts');

function registerMandatoryScripts(){
    wp_register_style('sim_mandatory_style', SIM\pathToUrl(MODULE_PATH.'css/mandatory.min.css'), array(), MODULE_VERSION);
    wp_register_script('sim_mandatory_script', SIM\pathToUrl(MODULE_PATH.'js/mandatory.min.js'), array('sim_formsubmit_script'), MODULE_VERSION,true);
}