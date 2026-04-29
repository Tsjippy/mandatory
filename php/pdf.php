<?php
namespace TSJIPPY\MANDATORY;
use TSJIPPY;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action('tsjippy-pdf-before-fullscreen', __NAMESPACE__.'\markPdfPageAsRed');
        
function markPdfPageAsRed($postId){
    /* IF PEOPLE HAVE TO READ IT, MARK AS READ */
    $audience	= get_post_meta($postId, "audience", true);
    
    if(!empty($audience)){
        //Get current user id
        $userId = get_current_user_id();
        
        markAsRead($userId, $postId);
    }
}
        