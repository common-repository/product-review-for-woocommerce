<?php 

namespace PISOL\REVIEW\ADMIN;

class Access{
    static function getCapability(){
        $capability = 'manage_woocommerce';
        
        return (string)apply_filters('pisol_review_access', $capability);
    }

}