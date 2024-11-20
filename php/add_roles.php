<?php
namespace SIM\MANDATORY;
use SIM;

add_filter('sim_role_description', __NAMESPACE__.'\roleDescription', 10, 2);
function roleDescription($description, $role){
    if($role == 'no_man_docs'){
		return "Mandatory documents do not apply";
	}

    return $description;
}