<?php

namespace TSJIPPY\MANDATORY;

use TSJIPPY;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Adds a message to the content that it is mandaory.
 * Also add a button to mark the post as read
 * @param  string $content    post content
 * @return string $content    post content
 */
add_filter('the_content', __NAMESPACE__ . '\markAsReadButton');
function markAsReadButton($content)
{
    if (!is_user_logged_in()) {
        return $content;
    }

    $postId     = get_the_ID();
    $userId     = get_current_user_id();

    //People should read this, and have not read it yet
    if (shouldRead($postId, $userId)) {
        wp_enqueue_style('tsjippy_mandatory_style');
        wp_enqueue_script('tsjippy_mandatory_script');
        
        $message = '<p class="mandatory-content-warning">
            This is mandatory content.<br>
            Make sure you have clicked the "I have read this" button after reading.
        </p>';

        $content     = $message . $content;
        $content    .= "<div class='mandatory-content-button'>";
            $content    .= "<button class='mark-as-read button' data-post-id='$postId' data-user-id='$userId'>I have read this</button>";
        $content    .= "</div>";
    }

    return $content;
}
