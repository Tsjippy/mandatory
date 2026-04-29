<?php
namespace TSJIPPY\MANDATORY;
use TSJIPPY;
use TSJIPPY\ADMIN;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ReadReminder extends ADMIN\MailSetting{

    public object $user;
    public string $html;

    public function __construct(object $user, string $html='') {
        // call parent constructor
		parent::__construct( 'read_reminder', PLUGINSLUG);

        $this->addUser($user);

        $this->replaceArray['%pages_to_read%']    = $html;

        $this->defaultSubject    = "Please read some website content";

        $this->defaultMessage    = 'Hi %first_name%,<br><br>';
		$this->defaultMessage   .= '%pages_to_read%';
        $this->defaultMessage   .= '<br>';
        $this->defaultMessage   .= 'Please read it as soon as possible.<br>';
        $this->defaultMessage   .= 'Mark as read by clicking on the button on the bottom of each page';
    }
}
