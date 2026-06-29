<?php

namespace TSJIPPY\MANDATORY;

use TSJIPPY;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Check if a given post is mandatory for a given user
 * 
 * @param   int|\WP_Post    $post       Post id of WP_Post
 * @param   int|\WP_User    $user       user id of WP_user
 * @param   \DOMElement     $wrapper    DOM ELment  to be passed to the filter
 * 
 * @return bool                         True if must read and not yet read
 */
function shouldRead($post, $user, $wrapper='' ){

    if(is_numeric($post)){
        $postId = $post;
        $post   = get_post($postId);
    }else{
        $postId = $post->ID;
    }

    if(is_numeric($user)){
        $userId = $user;
    }else{
        $userId = $user->ID;
    }

    $family = new TSJIPPY\FAMILY\Family();
    if ($family->isChild($user->ID)) {
        return;
    }

    if($post->post_author == $userId){
        return false;
    }

    $readPages    = get_user_meta($userId, 'tsjippy_read_pages');

    //check if already read
    if (!in_array($postId, $readPages)) {
        $audience   = get_post_meta($post->ID, 'tsjippy_audience', true);

        if (!is_array($audience) && !empty($audience)) {
            $audience  = json_decode($audience, true);
        }

        // Post has not been read, check if it should be read
        $mustRead    = !empty($audience);

        /**
         * Filter if this post should be read
         * 
         * @param   bool        $mustRead   
         * @param   array       $audience   The audience targets
         * @param   int         $userId     The WP_User id
         * @param   \WP_Post    $post       The current post
         * @param   \DOMElement $parent     The top node element
        */ 
        return apply_filters('tsjippy-mandatory-should-read-mandatory-page', $mustRead, $audience, $userId, $post, $wrapper);
    }
}