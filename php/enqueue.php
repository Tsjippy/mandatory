<?php

namespace TSJIPPY\MANDATORY;

use TSJIPPY;

if (! defined('ABSPATH')) {
    exit;
}

add_action('wp_enqueue_scripts', __NAMESPACE__ . '\registerMandatoryScripts', 20);
/**
 * Registeres the CSS and JS
 */
function registerMandatoryScripts()
{
    wp_register_style('tsjippy_mandatory_style', TSJIPPY\pathToUrl(PLUGINPATH . 'css/mandatory.min.css'), array(), PLUGINVERSION);

    $deps   = SCRIPT_DEBUG ? [  
        '@tsjippy/form_submit_functions',
        "@tsjippy/show_loader", 
        "@tsjippy/display_message"
    ] :
    [];

    $deps[] = "@tsjippy/nonce_script";
    wp_register_script_module('@tsjippy/mandatory_script', TSJIPPY\pathToUrl(PLUGINPATH . 'js/mandatory' . TSJIPPY\JSEXTENSION), $deps, PLUGINVERSION);
}
