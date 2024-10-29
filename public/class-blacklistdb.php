<?php 
namespace PISOL\REVIEW\FRONT;

class BlackListDB {

    static $instance = null;
    static $db_version = '1.0'; // Set your database version
    static $table_name = 'pisol_review_blacklist_email';
    static $db_version_variable = 'pisol_review_blacklist_email_db_version';

    static function get_instance(){
        if(is_null(self::$instance)){
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        global $wpdb;
        self::$table_name = $wpdb->prefix . 'pisol_review_blacklist_email';

        // Hook into plugin activation
        register_activation_hook( __FILE__, [ $this, 'create_table_on_activation' ] );

        // Check and update the database on every plugin load
        add_action( 'plugins_loaded', [ $this, 'check_and_update_table' ] );
    }

    public function create_table_on_activation() {
        $this->create_or_update_table();
    }

    public function check_and_update_table() {
        // Check if the table exists and version is up to date
        if ( get_option( self::$db_version_variable ) != self::$db_version || $this->table_does_not_exist() ) {
            $this->create_or_update_table();
            update_option( self::$db_version_variable, self::$db_version );
        }
    }

    private function table_does_not_exist() {
        global $wpdb;
        self::$table_name = $wpdb->prefix . 'pisol_review_blacklist_email';
        return $wpdb->get_var( "SHOW TABLES LIKE '".self::$table_name."'" ) != self::$table_name;
    }

    private function create_or_update_table() {
        global $wpdb;
        self::$table_name = $wpdb->prefix . 'pisol_review_blacklist_email';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE ".self::$table_name." (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            email varchar(255) NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY email (email)
        ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
    }

    public static function is_email_blacklisted( $email ) {
        global $wpdb;
        self::$table_name = $wpdb->prefix . 'pisol_review_blacklist_email';
        $result = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM ".self::$table_name." WHERE email = %s",
            $email
        ));
        return $result > 0;
    }

    public static function add_email_to_blacklist( $email ) {
        global $wpdb;
        self::$table_name = $wpdb->prefix . 'pisol_review_blacklist_email';
        // Ensure the email is not already in the blacklist
        if ( !self::is_email_blacklisted( $email ) ) {
            $wpdb->insert(
                self::$table_name,
                [ 'email' => $email ],
                [ '%s' ]
            );
        }
    }

    public static function get_blacklisted_emails( $page_number = 1, $per_page = 10 ) {
        global $wpdb;
        self::$table_name = $wpdb->prefix . 'pisol_review_blacklist_email';
        $offset = ( $page_number - 1 ) * $per_page;
        $query = $wpdb->prepare(
            "SELECT id, email FROM ".self::$table_name." ORDER BY id ASC LIMIT %d OFFSET %d",
            $per_page, $offset
        );
        return $wpdb->get_results( $query, ARRAY_A );
    }

    public static function search_blacklisted_emails( $search = '', $page_number = 1, $per_page = 10 ) {
        global $wpdb;
        self::$table_name = $wpdb->prefix . 'pisol_review_blacklist_email';
        $offset = ( $page_number - 1 ) * $per_page;

        // Prepare the base query
        $query = "SELECT id, email FROM ".self::$table_name;

        // If a search term is provided, add a WHERE clause
        if (!empty($search)) {
            $search = '%' . $wpdb->esc_like($search) . '%'; // Sanitize and prepare the search term
            $query .= $wpdb->prepare(" WHERE email LIKE %s", $search);
        }

        // Add ORDER BY, LIMIT, and OFFSET
        $query .= $wpdb->prepare(" ORDER BY id ASC LIMIT %d OFFSET %d", $per_page, $offset);

        // Execute the query and return the results
        return $wpdb->get_results($query, ARRAY_A);
    }

    public static function remove_email_by_id( $id ) {
        global $wpdb;
        self::$table_name = $wpdb->prefix . 'pisol_review_blacklist_email';
        $wpdb->delete(
            self::$table_name,
            [ 'id' => $id ],
            [ '%d' ]
        );
    }

    public static function remove_email_id( $email ) {
        global $wpdb;
        self::$table_name = $wpdb->prefix . 'pisol_review_blacklist_email';
        $wpdb->delete(
            self::$table_name,
            [ 'email' => $email ],
            [ '%s' ]
        );
    }

    public static function get_email_count($search = '') {
        global $wpdb;
        self::$table_name = $wpdb->prefix . 'pisol_review_blacklist_email';
    
        $query = "SELECT COUNT(*) FROM " . self::$table_name;
    
        if (!empty($search)) {
            $search = '%' . $wpdb->esc_like($search) . '%'; // Sanitize and prepare the search term
            $query .= $wpdb->prepare(" WHERE email LIKE %s", $search);
        }
    
        $count = $wpdb->get_var($query);
    
        return $count;
    }
    
}

