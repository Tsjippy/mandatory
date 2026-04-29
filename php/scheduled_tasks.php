<?php
namespace TSJIPPY\MANDATORY;
use TSJIPPY;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action('init', function(){
	//add action for use in scheduled task
	add_action( 'read_reminder_action', __NAMESPACE__.'\readReminder' );
});

function scheduleTasks(){
    $freq   = SETTINGS['reminder-freq'] ?? false;
    if($freq){
		TSJIPPY\scheduleTask('read_reminder_action', $freq);
	}
}

/**
 * Send an e-mail to remind people to read their mandatory content
 */
function readReminder(){
	//Change the user to the adminaccount otherwise get_users will not work
	wp_set_current_user(1);
	
	$users = TSJIPPY\getUserAccounts();
	foreach($users as $user){
		$html = mustReadDocuments($user->ID);
		
		//Only continue if there are documents to read
		if(!empty($html)){
			$to = $user->user_email;
				
			//Skip if not valid email
			if(str_contains($to,'.empty')){
				continue;
			}

			//Send e-mail
			$readReminder    = new ReadReminder($user, $html);
			$readReminder->filterMail();
								
			wp_mail( $user->user_email, $readReminder->subject, $readReminder->message);
		}
	}
}

// Remove scheduled tasks upon module deactivatio
add_action('tsjippy_module_mandatory_deactivated', __NAMESPACE__.'\moduleDeActivated');
function moduleDeActivated(){
	wp_clear_scheduled_hook( 'read_reminder_action' );
}