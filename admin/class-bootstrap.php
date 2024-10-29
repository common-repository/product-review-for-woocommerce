<?php 

namespace PISOL\REVIEW\ADMIN;

if ( ! defined( 'WPINC' ) ) {
	die;
}

class Bootstrap{

    static $instance = null;

    static function get_instance(){
        if(is_null(self::$instance)){
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct(){
        Menu::get_instance();
        ReviewReminder::get_instance();
        CustomFields::get_instance();
        ReviewEmailSetting::get_instance();
        ManualReminder::get_instance();
        ReviewStats::get_instance();
        ReviewForm::get_instance();
        AutoReminder::get_instance();
        ReviewDisplay::get_instance();
        MyAccount::get_instance();
        BlackList::get_instance();
        PastOrderReminder::get_instance();
        CustomReview::get_instance();
    }
}

Bootstrap::get_instance();