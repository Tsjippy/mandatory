<?php

namespace TSJIPPY\MANDATORY;

use TSJIPPY;

if (! defined('ABSPATH')) {
    exit;
}

// Make mark as read rest api publicy available
add_filter('tsjippy-allowed-rest-api-urls', function ($urls) {
    $urls[]    = TSJIPPY\RESTAPIPREFIX . '/mandatory_content';

    return $urls;
});

add_action('rest_api_init', __NAMESPACE__ . '\restApiInit');
function restApiInit()
{
    //Route to update mark as read from mailchimp
    register_rest_route(
        TSJIPPY\RESTAPIPREFIX . '/mandatory_content',
        '/mark_as_read_public',
        array(
            'methods' => 'GET',
            'callback' => __NAMESPACE__ . '\markAsReadFromEmail',
            'permission_callback' => '__return_true',
            'args'                    => array(
                'post-id'        => array(
                    'required'    => true,
                    'validate_callback' => function ($postId) {
                        return is_numeric($postId);
                    }
                ),
                'email'        => array(
                    'required'    => true
                )
            )
        )
    );

    // Mark as read from website
    register_rest_route(
        TSJIPPY\RESTAPIPREFIX . '/mandatory_content',
        '/mark_as_read',
        array(
            'methods' => 'POST',
            'callback' => function () {
                $userId = (int) $_POST['user-id'];
                $postId = (int) $_POST['post-id'];

                markAsRead($userId, $postId);

                return "Succesfully marked this page as read";
            },
            'permission_callback' => '__return_true',
            'args'                    => array(
                'post-id'        => array(
                    'required'    => true,
                    'validate_callback' => function ($postId) {
                        return is_numeric($postId);
                    }
                ),
                'user-id'        => array(
                    'required'    => true,
                    'validate_callback' => function ($userId) {
                        return is_numeric($userId);
                    }
                )
            )
        )
    );

    // Mark all as read
    register_rest_route(
        TSJIPPY\RESTAPIPREFIX . '/mandatory_content',
        '/mark_all_as_read',
        array(
            'methods'     => 'POST',
            'callback'     => function ($wpRestRequest) {
                $userId = $wpRestRequest->get_param('user-id');

                return markAllAsRead($userId);
            },
            'permission_callback' => '__return_true',
            'args'                    => array(
                'user-id'        => array(
                    'required'    => true,
                    'validate_callback' => function ($userId) {
                        return is_numeric($userId);
                    }
                )
            )
        )
    );
}

add_filter('tsjippy-mailchimp-before-send', __NAMESPACE__ . '\beforeMailchimpSend', 10, 2);
function beforeMailchimpSend($mailContent, $post)
{
    $audience   = get_post_meta($post->ID, "tsjippy_audience", true);
    if (!is_array($audience) && !empty($audience)) {
        $audience  = json_decode($audience, true);
    }

    ///add button if mandatory message
    if (!empty($audience['everyone'])) {
        $url            = TSJIPPY\SITEURL . "/wp-json/" . TSJIPPY\RESTAPIPREFIX . "/mandatory_content/mark_as_read_public?email=*|EMAIL|*&post-id={$post->ID}";
        $style            = "color: white; background-color: #bd2919; border-radius: 3px; text-align: center; margin-right: 10px; padding: 5px 10px;";
        $mailContent    .= "<br><a href='$url' style='$style'>I have read this</a>";
    }

    return $mailContent;
}

/**
 * Rest Request to mark a page as read over e-mail
 * Also add a button to mark the post as read
 */
function markAsReadFromEmail(\WP_REST_Request $request)
{
    $email        = $request['email'];
    $postId        = $request['post-id'];

    //only continue if valid email and numeric post-id
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $userId        = get_user_by('email', $email)->ID;

        //no user, check secundairy email
        if (!is_numeric($userId)) {
            $userId = get_users(['meta_key' => 'tsjippy_email', 'meta_value' => $email])[0]->ID;
        }

        $title    = get_the_title($postId);

        if (!is_numeric($userId)) {
            $message    = "We could not find an user with the e-mail '$email'";
            $type        = 'Error';
        } elseif (empty($title)) {
            $message    = "We could not find the page";
            $type        = 'Error';
        } else {
            //add current page
            add_user_meta($userId, 'tsjippy_read_pages', $postId);

            $message    = "Succesfully marked '" . get_the_title($postId) . "' as read. ";
            $type        = 'Success';
        }

        wp_redirect(home_url("?message=$message&type=$type"));
        exit();
    }
}

/**
 * Rest Request to mark a page as read
 */
function markAsRead($userId, $postId)
{
    add_user_meta($userId, 'tsjippy_read_pages', $postId);
}



/**
 * Rest Request to mark all pages as read
 *
 * @param    int                $userId        the user id to mark as read for
 * @param    array|string    $audience    array of audience targets to mark as read for or 'all' for all. Default 'everyone'
 */
function markAllAsRead($userId, $audience = ['everyone'])
{
    //Get all the pages with an audience meta key
    $pages = get_posts(
        array(
            'post_type'   => 'any',
            'post_status' => 'publish',
            'meta_key'    => "tsjippy_audience",
            'numberposts' => -1,                // all posts
        )
    );

    foreach ($pages as $page) {
        add_user_meta($userId, 'tsjippy_read_pages', $page->ID);
    }

    return "Succesfully marked all pages as read for " . get_userdata($userId)->display_name;
}
