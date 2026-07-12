<?php

namespace TSJIPPY\MANDATORY;

use TSJIPPY;

if (! defined('ABSPATH')) {
    exit;
}

add_filter('tsjippy-user-management-role-description', __NAMESPACE__ . '\roleDescription', 10, 2);
/**
 * Filters the role description
 * 
 * @param string $description  The description of a user role
 * @param string $role         The role slug
 */
function roleDescription($description, $role)
{
    if ($role == 'no_man_docs') {
        return "Mandatory documents do not apply";
    }

    return $description;
}
