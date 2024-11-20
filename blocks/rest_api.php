<?php
namespace SIM\MANDATORY;
use SIM;

add_action( 'rest_api_init', __NAMESPACE__.'\blockRestApiInit');
function blockRestApiInit() {
	// show schedules
	register_rest_route(
		RESTAPIPREFIX.'/mandatory_content',
		'/must_read_documents',
		array(
			'methods' 				=> 'GET',
			'callback' 				=> __NAMESPACE__.'\mustReadDocuments',
			'permission_callback' 	=> '__return_true',
		)
	);
}