<?php

namespace TSJIPPY\MANDATORY;

use TSJIPPY;
use function TSJIPPY\addElement as addElement;

/**
 * Register Mandatory Pages Overview block
 */
add_action( 'init', function () {
    // Mandatory pages list
    register_block_type(
        'tsjippy-mandatory/mandatory-pages',
        array(
            'title'           => __( 'Must Read Documents List', '%TEXTDOMAIN%' ),
            'attributes'      => array(
                'excludeHeading'   => array(
                    'label'   => __( 'Exclude the heading', '%TEXTDOMAIN%' ),
                    'type'    => 'boolean',
                    'default' => false,
                ),
                'hide_when_empty'   => array(
                    'label'   => __( 'Do not show when empty', '%TEXTDOMAIN%' ),
                    'type'    => 'boolean',
                    'default' => false,
                )
            ),
            'render_callback' => function ( $attributes ) {
                $html   = mustReadDocuments(excludeHeading: $attributes['excludeHeading']);
                if(empty($html)){
                    if(!$attributes['hide_when_empty']){
                        return "<div>You have no mandatroy documents to read</div>";
                    }

                    if(($_REQUEST['action'] ?? $_REQUEST['context'] ?? '') == 'edit'){
                        return "<div>You have no mandatroy documents to read, this will not show outside the block editor</div>";
                    }
                } 

                return $html;
            },
            'supports'        => array(
                'autoRegister' => true,
            ),
            'icon'  => 'unordered list'
        )
    );

    // Register the audience meta
    register_post_meta('', "tsjippy_audience", array(
        'show_in_rest'      => true,
        'single'            => false,
        'type'              => 'string',
        'sanitize_callback' => 'sanitize_text_field'
    ));
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


/**
 * Get an unordered list of documents to read
 * @param  int        $userId          The user id to check
 * @param  bool       $excludeHeading  Whether to include a heading for
 * @param  bool       $echo            Whether to echo return the html
 * @return string                      HTML unordered list
 */
function mustReadDocuments($userId = '', $excludeHeading = false, $echo = false)
{
    $mandatoryReading    = apply_filters('tsjippy-mandatory-must-read', false, $userId);
    if (!is_user_logged_in() || !$mandatoryReading) {
        return '';
    }

    wp_enqueue_script('tsjippy_mandatory_script');

    if (!is_numeric($userId)) {
        $userId = get_current_user_id();
    }

    // skip if user has the no mandatory pages role
    $user    = get_userdata($userId);
    if (in_array('no_man_docs', $user->roles)) {
        return '';
    }

    //Get all the pages with an audience meta key
    $posts = get_posts(
        array(
            'orderby'     => 'post_name',
            'order'       => 'asc',
            'post_type'   => 'any',
            'post_status' => 'publish',
            'meta_key'    => "tsjippy_audience",
            'numberposts' => -1,                // all posts
        )
    );

    $wrapper    = addElement('div', '', ['class' => 'read-list-wrapper', 'id' => 'personalinfo']);

    if (!$excludeHeading) {
        addElement('h3', $wrapper, ['id' => 'read-list-title'], 'Important Reading for You Today');
    }

    $ul    = addElement('ul', $wrapper, ['id' => 'must-read-list']);

    $count = 0;

    //Loop over the pages while building the html
    foreach ($posts as $post) {
        // We do not have to read them if we are the author
        if($post->post_author == $userId){
            continue;
        }

        //check if already read
        if (shouldRead($post, $userId, $wrapper)){
            $li = addElement('li', $ul);
            addElement('a', $li, ['href' =>  get_permalink($post->ID)], $post->post_title);

            $count++;
        }
    }

    if (empty($count)) {
        if (str_contains($_SERVER['REQUEST_URI'], 'wp-admin/post.php') || str_contains($_SERVER['REQUEST_URI'], 'wp-json')) {
            return 'Mandatory pages block<br>This will show empty as you have not pages to read';
        }
        return '';
    }

    // Do not add the button on cron
    if (wp_doing_cron()) {
        return $wrapper->ownerDocument->saveHTML();;
    }

    $text    = 'Mark all pages as read';
    if ($userId != get_current_user_id()) {
        $text .=  " for {$user->display_name}";
    }

    addElement('button', $wrapper, ['type' => 'button', 'class' => 'button small mark-all-as-read', 'data-user-id' => $userId], $text);

    if($echo){
        // phpcs:ignore
        echo $wrapper->ownerDocument->saveHTML();
    }else{
        return $wrapper->ownerDocument->saveHTML();
    }
}