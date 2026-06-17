<?php

namespace TSJIPPY\MANDATORY;

use TSJIPPY;

if (! defined('ABSPATH')) {
    exit;
}

add_action('init', function () {
    $freq   = SETTINGS['reminder-freq'] ?? false;
    if ($freq) {
        TSJIPPY\scheduleTask('tsjippy-read-reminder', $freq, __NAMESPACE__, 'readReminder');
    }
});

/**
 * Send an e-mail to remind people to read their mandatory content
 */
function readReminder()
{

    $users = TSJIPPY\getUserAccounts();
    foreach ($users as $user) {
        $html = mustReadDocuments($user->ID);

        //Only continue if there are documents to read
        if (!empty($html)) {
            $to = $user->user_email;

            //Skip if not valid email
            if (str_contains($to, '.empty')) {
                continue;
            }

            //Send e-mail
            $readReminder    = new ReadReminder($user, $html);
            $readReminder->filterMail();

            wp_mail($user->user_email, $readReminder->subject, $readReminder->message);
        }
    }
}
