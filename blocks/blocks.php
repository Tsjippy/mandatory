<?php

namespace TSJIPPY\MANDATORY;

use TSJIPPY;

/**
 * Register Mandatory Pages Overview block
 */
add_action( 'init', function () {
    register_block_type(
        'tsjippy-mandatory/mandatory-pages',
        array(
            'title'           => __( 'Must Read Documents List', 'tsjippy' ),
            'attributes'      => array(
                'excludeHeading'   => array(
                    'label'   => __( 'Exclude the heading', 'tsjippy' ),
                    'type'    => 'boolean',
                    'default' => false,
                ),
                'hide_when_empty'   => array(
                    'label'   => __( 'Do not show when empty', 'tsjippy' ),
                    'type'    => 'boolean',
                    'default' => false,
                )
            ),
            'render_callback' => function ( $attributes ) {
                $html   = mustReadDocuments(excludeHeading: $attributes['excludeHeading']);
                if(empty($html) && !$attributes['hide_when_empty']){
                    return "<div>You have no mandatroy documents to read</div>";
                }

                return $html;
            },
            'supports'        => array(
                'autoRegister' => true,
            ),
        )
    );
} );

/**
 * Register Mandatory Page Settings
 */
// Load the js file to filter all blocks
add_action('enqueue_block_editor_assets', __NAMESPACE__ . '\blockAssets');
function blockAssets()
{
    wp_enqueue_script(
        'tsjippy-mandatory-block',
        TSJIPPY\pathToUrl(PLUGINPATH . 'blocks/mandatory-settings/build/index.js'),
        ['wp-blocks', 'wp-dom', 'wp-dom-ready', 'wp-edit-post'],
        PLUGINVERSION
    );
}

// register custom meta tag field
add_action('init',  __NAMESPACE__ . '\blockInit');
function blockInit()
{
    register_post_meta('', "tsjippy_audience", array(
        'show_in_rest'      => true,
        'single'            => false,
        'type'              => 'string',
        'sanitize_callback' => 'sanitize_text_field'
    ));
}