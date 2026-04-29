<?php
namespace TSJIPPY\MANDATORY;
use TSJIPPY;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter('tsjippy_role_description', __NAMESPACE__.'\roleDescription', 10, 2);
function roleDescription($description, $role){
    if($role == 'no_man_docs'){
		return "Mandatory documents do not apply";
	}

    return $description;
}