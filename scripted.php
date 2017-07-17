<?php
/*
Plugin Name: Scripted.com Writing Marketplace
Plugin URI: https://Scripted.com/
Description: Get great writers and manage your Scripted account from WordPress!
Author: Scripted.com
Version: 1.9
Author URI: https://Scripted.com/
*/

class WP_Scripted {
	
	function WP_Scripted() {
		add_action( 'plugins_loaded', array( $this, 'init' ), 8 );
                
	}	
	function init() {
		$this->includes();
	}
	function includes() {            
            define( 'SCRIPTED_FILE_PATH', dirname( __FILE__ ) );
            define( 'SCRIPTED_FILE_URL',  __FILE__  );
            define( 'SCRIPTED_ICON',  plugins_url('images/favicon.ico',SCRIPTED_FILE_URL)  );
            define( 'SCRIPTED_LOGO',  plugins_url('images/logo.png',SCRIPTED_FILE_URL)  );
            define( 'SCRIPTED_END_POINT',  'https://api.scripted.com'  );
            
            
            require_once( SCRIPTED_FILE_PATH . '/admin/settings.php' );
            require_once( SCRIPTED_FILE_PATH . '/admin/jobs.php' );
             
	}
	function scripteActivePlugin() {
            if (!get_option( '_scripted_api_key' )) {
                add_option( '_scripted_api_key', '', '', 'no' );
            } 
            if (!get_option( '_scripted_business_id' )) {
                add_option( '_scripted_business_id', '', '', 'no' );
            } 
        }
	public function scripted_deactivePlugin() {
		 delete_option( '_scripted_api_key' ); 
		 delete_option( '_scripted_business_id' ); 
	}
}

$script = new WP_Scripted();

// Activation
register_activation_hook( __FILE__, array( $script,'scripteActivePlugin'));
register_deactivation_hook( __FILE__, array( $script,'scripted_deactivePlugin' ));
?>
