<?php

namespace TSJIPPY\MANDATORY;

use TSJIPPY;
use function TSJIPPY\addElement as addElement;

if (! defined('ABSPATH')) {
    exit;
}

// add to account dashboard
add_action('tsjippy-user-management-dashboard-warnings', __NAMESPACE__ . '\dashboardWarnings', 20);
function dashboardWarnings($userId)
{
    mustReadDocuments($userId, false, true);
}

add_shortcode("tsjippy_must_read_documents", __NAMESPACE__ . '\mustReadDocuments');

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

    //Get all the pages this user already read
    $readPages        = get_user_meta($userId, 'tsjippy_read_pages');

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

    //Loop over the pages while building the html
    foreach ($posts as $post) {
        // We do not have to read them if we are the author
        if($post->post_author == $userId){
            continue;
        }

        //check if already read
        if (!in_array($post->ID, $readPages)) {
            $audience   = get_post_meta($post->ID, 'tsjippy_audience', true);

            if (!is_array($audience) && !empty($audience)) {
                $audience  = json_decode($audience, true);
            }

            // Post has not been read, check if it should be read
            $mustRead    = true;

            /**
             * Filter if this post should be read
             * 
             * @param   bool        $mustRead   
             * @param   array       $audience   The audience targets
             * @param   int         $userId     The WP_User id
             * @param   \WP_Post    $post       The current post
             * @param   \DOMElement $parent     The top node element
            */ 
            $mustRead    = apply_filters('tsjippy-mandatory-should-read-mandatory-page', $mustRead, $audience, $userId, $post, $wrapper);

            if ($mustRead) {
                $li = addElement('li', $ul);
                addElement('a', $li, ['href' =>  get_permalink($post->ID)], $post->post_title);
            }
        }
    }

    if (empty($posts)) {
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

    addElement('button', $wrapper, ['type' => 'button', 'class' => 'button small mark-all-as-read', 'data-user-id' => '$userId'], $text);

    if($echo){
        // phpcs:ignore
        echo $wrapper->ownerDocument->saveHTML();
    }else{
        return $wrapper->ownerDocument->saveHTML();
    }
}
