<?php
class PrsoSyndToolkitReader {
	
	protected static $class_config 				= array();
	protected $current_screen					= NULL;
	protected $plugin_ajax_nonce				= 'prso_synd_toolkit_reader-ajax-nonce';
	protected $plugin_path						= PRSOSYNDTOOLKITREADER__PLUGIN_DIR;
	protected $plugin_url						= PRSOSYNDTOOLKITREADER__PLUGIN_URL;
	protected $plugin_textdomain				= PRSOSYNDTOOLKITREADER__DOMAIN;
	
	function __construct( $config = array() ) {
		
		//Cache plugin congif options
		self::$class_config = $config;
		
		//Set textdomain
		add_action( 'after_setup_theme', array($this, 'plugin_textdomain') );
		
		//Init plugin
		add_action( 'init', array($this, 'init_plugin') );
		add_action( 'admin_init', array($this, 'admin_init_plugin') );
		//add_action( 'current_screen', array($this, 'current_screen_init_plugin') );
		
	}
	
	/**
	 * Attached to activate_{ plugin_basename( __FILES__ ) } by register_activation_hook()
	 * @static
	 */
	public static function plugin_activation( $network_wide ) {
		
	}

	/**
	 * Attached to deactivate_{ plugin_basename( __FILES__ ) } by register_deactivation_hook()
	 * @static
	 */
	public static function plugin_deactivation( ) {
		
	}
	
	/**
	 * Setup plugin textdomain folder
	 * @public
	 */
	public function plugin_textdomain() {
		
		load_plugin_textdomain( $this->plugin_textdomain, FALSE, $this->plugin_path . '/languages/' );
		
	}
	
	/**
	* init_plugin
	* 
	* Used By Action: 'init'
	* 
	*
	* @access 	public
	* @author	Ben Moody
	*/
	public function init_plugin() {
		
		//Init vars
		$options 		= self::$class_config;
		
		if( is_admin() ) {
		
			//PLUGIN OPTIONS FRAMEWORK -- comment out if you dont need options
			$this->load_redux_options_framework();
			
		}
		
		//Setup XMLRPC API for Syndication Toolkit Reader plugin
		$plugin_setup_inc 	= $this->plugin_path . 'inc/class/class.prso-syd-reader-xmlrpc.php';
		if( file_exists($plugin_setup_inc) ) {
			require_once( $plugin_setup_inc );
			new PrsoSyndReaderXMLRPC( $options );
		}
		
	}
	
	/**
	* admin_init_plugin
	* 
	* Used By Action: 'admin_init'
	* 
	*
	* @access 	public
	* @author	Ben Moody
	*/
	public function admin_init_plugin() {
		
		//Init vars
		$options 		= self::$class_config;
		
		if( is_admin() ) {
			
			//Enqueue admin scripts
			add_action( 'admin_enqueue_scripts', array($this, 'enqueue_admin_scripts') );
			
			//Action to Pull Content from master server
			add_action( 'wp_ajax_pcst-pull-content', array($this, 'pull_content_from_master'), 10 );
			
			//Action to reset the last import post date index
			add_action( 'wp_ajax_pcst-reset-index', array($this, 'pull_reset_last_post_index'), 10 );
			
		}
		
	}
	
	/**
	* pull_content_from_master
	* 
	* @Ajax Call 'wp_ajax_pcst-pull-content'
	* 
	* Makes a pull request to the pull webhook, parses the server responce and returns the
	* approriate user message to the ajax request
	*
	* @access 	public
	* @author	Ben Moody
	*/
	public function pull_content_from_master() {
		
		//Init vars
		$http_request 	= NULL;
		$request_url	= NULL;
		$response		= NULL;
		$output			= NULL;
		
		//Security check first
		check_ajax_referer( 'pcst-admin-ajax', 'ajaxNonce' );
		
		//Form request url with params
		$request_url = add_query_arg( PRSOSYNDTOOLKITREADER__WEBHOOK_PARAM, 'true', get_home_url() );
		
		//Make a pull request and get the body output
		$response = wp_remote_request( $request_url, array('timeout' => 300) );
		
		//Check response code
		if( isset($response['response']['code']) ) {
			
			if( (int) $response['response']['code'] !== 200 ) {
			
				wp_send_json_error( _x( 'Problem contacting server. Please check API Username and Password.', 'text', PRSOSYNDTOOLKITREADER__DOMAIN ) );
				
			} else {
				
				//Check for media import error
				if( strpos($response['body'], 'Media') ) {
				
					wp_send_json_error( _x( 'Problem with importing Media from server. Please wait and try again.', 'text', PRSOSYNDTOOLKITREADER__DOMAIN ) );
					
				} elseif(strpos($response['body'], 'All done')) {
					
					wp_send_json_success( _x( 'Post Pull Completed', 'text', PRSOSYNDTOOLKITREADER__DOMAIN ) );
					
				}
				
			}
			
		} else {
			
			wp_send_json_error( _x( 'Problem contacting server. Please check API Username and Password.', 'text', PRSOSYNDTOOLKITREADER__DOMAIN ) );
			
		}
		
	}
	
	public function pull_reset_last_post_index() {
		
		//Security check first
		check_ajax_referer( 'pcst-admin-ajax', 'ajaxNonce' );
		
		//Delete last pull date option
		if( delete_option( PRSOSYNDTOOLKITREADER__LAST_IMPORT_OPTION ) ) {
			wp_send_json_success( _x( 'Index has been Reset', 'text', PRSOSYNDTOOLKITREADER__DOMAIN ) );
		} else {
			wp_send_json_error( _x( 'It appears that the index has already been reset.', 'text', PRSOSYNDTOOLKITREADER__DOMAIN ) );
		}
		
	}
	
	/**
	* current_screen_init_plugin
	* 
	* Used By Action: 'current_screen'
	* 
	* Detects current view and decides if plugin should be activated
	*
	* @access 	public
	* @author	Ben Moody
	*/
	public function current_screen_init_plugin() {
		
		//Init vars
		$options 		= self::$class_config;
		
		if( is_admin() ) {
		
			//Confirm we are on an active admin view
			if( $this->is_active_view() ) {
		
				//Carry out view specific actions here
				
			}
			
		}
		
	}
	
	/**
	* load_redux_options_framework
	* 
	* Loads Redux options framework as well as the unique config file for this plugin
	*
	* NOTE!!!!
	*			You WILL need to make sure some unique constants as well as the class
	*			name in the plugin config file 'inc/ReduxConfig/ReduxConfig.php'
	*
	* @access 	public
	* @author	Ben Moody
	*/
	protected function load_redux_options_framework() {
		
		//Init vars
		$framework_inc 		= $this->plugin_path . 'inc/ReduxFramework/ReduxCore/framework.php';
		$framework_config	= $this->plugin_path . 'inc/ReduxConfig/ReduxConfig.php';
		
		//Try and load redux framework
		if ( !class_exists('ReduxFramework') && file_exists($framework_inc) ) {
			require_once( $framework_inc );
		}
		
		//Try and load redux config for this plugin
		if ( file_exists($framework_config) ) {
			require_once( $framework_config );
		}
		
	}
	
	/**
	* is_active_view
	* 
	* Detects if current admin view has been set as 'active_post_type' in
	* plugin config options array.
	* 
	* @var		array	self::$class_config
	* @var		array	$active_views
	* @var		obj		$screen
	* @var		string	$current_screen
	* @return	bool	
	* @access 	protected
	* @author	Ben Moody
	*/
	protected function is_active_view() {
		
		//Init vars
		$options 		= self::$class_config;
		$active_views	= array();
		$screen			= get_current_screen();
		$current_screen	= NULL;
		
		//Cache all views plugin will be active on
		$active_views = $this->get_active_views( $options );
		
		//Cache the current view
		if( isset($screen) ) {
		
			//Is this an attachment screen (base:upload or post_type:attachment)
			if( ($screen->id === 'attachment') || ($screen->id === 'upload') ) {
				$current_screen = 'attachment';
			} else {
				
				//Cache post type for all others
				$current_screen = $screen->post_type;
				
			}
			
			//Cache current screen in class protected var
			$this->current_screen = $current_screen;
		}
		
		//Finaly lets check if current view is an active view for plugin
		if( in_array($current_screen, $active_views) ) {
			return TRUE;
		} else {
			return FALSE;
		}
		
	}
	
	/**
	* get_active_views
	* 
	* Interates over plugin config options array merging all
	* 'active_post_type' values into single array
	* 
	* @param	array	$options
	* @var		array	$active_views
	* @return	array	$active_views
	* @access 	private
	* @author	Ben Moody
	*/
	protected function get_active_views( $options = array() ) {
		
		//Init vars
		$active_views = array();
		
		//Loop options and cache each active post view
		foreach( $options as $option ) {
			if( isset($option['active_post_types']) ) {
				$active_views = array_merge($active_views, $option['active_post_types']);
			}
		}
		
		return $active_views;
	}
	
	/**
	 * Helper to set all actions for plugin
	 */
	protected function set_admin_actions() {
		
		
		
	}
	
	/**
	 * Helper to enqueue all scripts/styles for admin views
	 */
	public function enqueue_admin_scripts() {
		
		//Init vars
		$js_inc_path 	= $this->plugin_url . 'inc/js/';
		$css_inc_path 	= $this->plugin_url . 'inc/css/';
		
		//Enqueue admin ajax script
		wp_register_script( 'pcst-admin-ajax',
			$js_inc_path . 'admin_ajax_actions.js',
			array( 'jquery' ),
			'1.0',
			TRUE
		);
		wp_enqueue_script( 'pcst-admin-ajax' );
		
		//Localize vars
		$this->localize_script();
		
	}
	
	/**
	* localize_script
	* 
	* Helper to localize all vars required for plugin JS.
	* 
	* @var		string	$object
	* @var		array	$js_vars
	* @access 	private
	* @author	Ben Moody
	*/
	protected function localize_script() {
		
		//Init vars
		$object 	= 'PrsoPluginFrameworkVars';
		$js_vars	= array();
		
		//Localize vars for ajax requests
		
		if( is_admin() ) {
			//Ajax request url
			$js_vars['ajaxUrl'] = admin_url( 'admin-ajax.php' );
		
			//Cache request nonce
			$js_vars['ajaxNonce'] = wp_create_nonce( 'pcst-admin-ajax' );
		
			wp_localize_script( 'pcst-admin-ajax', $object, $js_vars );
		}
		
		//wp_localize_script( '', $object, $js_vars );
	}
	
	public static function plugin_error_log( $var ) {
		
		ini_set( 'log_errors', 1 );
		ini_set( 'error_log', PRSOSYNDTOOLKITREADER__PLUGIN_DIR . '/debug.log' );
		
		if( !is_string($var) ) {
			error_log( print_r($var, true) );
		} else {
			error_log( $var );
		}
		
	}
	
	/**
	* send_admin_email
	* 
	* Sends an error warning email to the wordpress admin
	* 
	* @access 	private
	* @author	Ben Moody
	*/
	public static function send_admin_email( $error_msg, $error_type = 'pull_error', $admin_email = NULL ) {
		
		//Init vars
		$inc_templates = PRSOSYNDTOOLKITREADER__PLUGIN_DIR . "inc/templates/email/{$error_type}.php";
		
		$subject = NULL;
		$headers = array();
		$message = NULL;
		
		if( file_exists($inc_templates) ) {
		
			//send admin an email to let them know
			if( empty($admin_email) ) {
				$admin_email = get_option( 'admin_email' );
			}
			
			//Set email content
			$subject = _x( 'WP Content Syndication Toolkit', 'text', PRSOSYNDTOOLKITREADER__DOMAIN );
			
			ob_start();
				include_once( $inc_templates );
			$message = ob_get_contents();
			ob_end_clean();
			
			//Send Email to admin
			wp_mail( $admin_email, $subject, $message );
			
			//Error log
			PrsoSyndToolkitReader::plugin_error_log( $message );
			
			return;
		}
		
	}
	
}



