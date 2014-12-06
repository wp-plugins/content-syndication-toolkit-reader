<?php
/*
 * Plugin Name: Content Syndication Toolkit Reader
 * Plugin URI: 
 * Description: Allows clients to subscribe to content created using the "Content Syndication Toolkit" plugin.
 * Author: Benjamin Moody
 * Version: 1.0.2
 * Author URI: http://www.benjaminmoody.com
 * License: GPL2+
 * Text Domain: prso_synd_toolkit_reader_plugin
 * Domain Path: /languages/
 */

//Define plugin constants
define( 'PRSOSYNDTOOLKITREADER__MINIMUM_WP_VERSION', '3.0' );
define( 'PRSOSYNDTOOLKITREADER__VERSION', '1.0.2' );
define( 'PRSOSYNDTOOLKITREADER__DOMAIN', 'prso_synd_toolkit_reader_plugin' );

//Plugin admin options will be available in global var with this name, also is database slug for options
define( 'PRSOSYNDTOOLKITREADER__OPTIONS_NAME', 'prso_synd_toolkit_reader_options' );
define( 'PRSOSYNDTOOLKITREADER__LAST_IMPORT_OPTION', 'prso_synd_toolkit_last_import' );

define( 'PRSOSYNDTOOLKITREADER__WEBHOOK_PARAM', 'pcst_push_webhook' );

define( 'PRSOSYNDTOOLKITREADER__PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'PRSOSYNDTOOLKITREADER__PLUGIN_URL', plugin_dir_url( __FILE__ ) );

define( 'PRSOSYNDTOOLKITREADER__XMLRPC_LIB', ABSPATH . WPINC . '/class-IXR.php' );

//Include plugin classes
require_once( PRSOSYNDTOOLKITREADER__PLUGIN_DIR . 'class.prso-content-synd-toolkit-reader.php'               );

//Set Activation/Deactivation hooks
register_activation_hook( __FILE__, array( 'PrsoSyndToolkitReader', 'plugin_activation' ) );
register_deactivation_hook( __FILE__, array( 'PrsoSyndToolkitReader', 'plugin_deactivation' ) );


prso_src_set_init();
function prso_src_set_init() {
	
	//Init vars
	global $prso_synd_toolkit_reader_options;
	
	//Set plugin config
	$config_options = array(
		'xmlrpc' => array(
			'url'		=>	NULL,
			'username'	=>	NULL,
			'password'	=>	NULL
		),
		'import_options' => array(
			'author_id' => 1
		)
	);
	
	//Cache plugin options array
	$prso_synd_toolkit_reader_options = get_option( PRSOSYNDTOOLKITREADER__OPTIONS_NAME );
	
	//Cache API URL
	if( isset($prso_synd_toolkit_reader_options['api-url']) ) {
		$config_options['xmlrpc']['url'] =  esc_url( $prso_synd_toolkit_reader_options['api-url'] );
	}
	
	//Cache API Login details
	if( isset($prso_synd_toolkit_reader_options['api-password']['username'], $prso_synd_toolkit_reader_options['api-password']['password']) ) {
	
		$config_options['xmlrpc']['username'] = sanitize_user($prso_synd_toolkit_reader_options['api-password']['username']);
		$config_options['xmlrpc']['password'] = $prso_synd_toolkit_reader_options['api-password']['password'];
		
	}
	
	//Cache Post Author option
	if( isset($prso_synd_toolkit_reader_options['post-author']) ) {
	
		$config_options['import_options']['author_id'] = (int) $prso_synd_toolkit_reader_options['post-author'];
		
	}
	
	//Instatiate plugin class and pass config options array
	new PrsoSyndToolkitReader( $config_options );
		
}