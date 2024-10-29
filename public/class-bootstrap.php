<?php 

namespace PISOL\REVIEW\FRONT;

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
        ReviewForm::get_instance();
        ReviewDisplay::get_instance();
        MyAccount::get_instance();
        ReviewPermission::get_instance();
        BlackListDB::get_instance();
    }
}

Bootstrap::get_instance();