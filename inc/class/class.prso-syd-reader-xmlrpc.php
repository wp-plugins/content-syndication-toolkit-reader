<?php
/**
* Class PrsoSyndReaderXMLRPC
* 
* Handles all actions required to setup Content Syndication Toolkit Reader XMLRPC API
* 
* @author	Ben Moody
*/
class PrsoSyndReaderXMLRPC {
	
	private $class_config 	= array(); 								//Global options array for plugin
	protected $plugin_path	= PRSOSYNDTOOLKITREADER__PLUGIN_DIR;
	public $api_results		= array();
	
	private $xml_rpc_url	= NULL; 	//Full URL to Content Syndication Toolkit Master Endpoint
	private $username		= NULL;		//Content Syndication Toolkit Master Username
	private $password		= NULL;		//Content Syndication Toolkit Master Password
	
	private $push_webhook_var 		= PRSOSYNDTOOLKITREADER__WEBHOOK_PARAM;			//URL Param used by master server to init content push on client
	private $api_method_get_posts 	= 'pcst.getSyndicationPosts';	//Custom XMLRPC method used to get posts from master server
	private $imported_posts 		= array(); 						//Cache of all posts successfully imported
	
	function __construct( $config = array() ) {
		
		//Cache plugin config options
		$this->class_config = $config;
		
		//Extract and cache params we need in this class from options
		if( isset($config['xmlrpc']['url'], $config['xmlrpc']['username'], $config['xmlrpc']['password']) ) {
			
			$this->xml_rpc_url 	= trailingslashit(esc_url($config['xmlrpc']['url'])) . 'xmlrpc.php';
			$this->username 	= esc_attr($config['xmlrpc']['username']);
			$this->password 	= $config['xmlrpc']['password'];
			
		}
		
		//Add custom query var for our push notification webhook
		add_filter( 'query_vars', array($this, 'add_push_webhook_query_var') ); 
		
		//Detect webhook push requests
		add_action( 'parse_request', array($this, 'detect_webhook_push_request') );
		
		//Add filter to access post before import into wp
		add_filter( 'wp_import_post_data_raw', array($this, 'filter_post_before_import'), 10, 1 );
		
		//Add action to carry out tasks everytime a post is imported into wp
		add_action( 'wp_import_insert_post', array($this, 'post_imported_successfully'), 10, 4 );
		
		//Add action to rollback import on error
		add_action( 'pcst_rollback_import', array($this, 'rollback_post_import'), 10, 3 );
		
	}
	
	/**
	* api_request
	* 
	* Handles any request made to the Syndication Toolkit Master wordpress install via cURL
	* 
	* @param	string	$method
	* @param	array	$params
	* @return	mixed	$results
	* @access 	public
	* @author	Ben Moody
	*/
	public function api_request( $method, $params ) {
		
		//Init vars
		$client			= NULL;
		$results		= NULL;
		
		$request 		= NULL;
		$ch				= NULL;
		$results		= NULL;
		$response_code	= NULL;
		$errorno		= NULL;
		$error 			= NULL;
		
		//include Incutio XML-RPC Library from wordpress core
		require( PRSOSYNDTOOLKITREADER__XMLRPC_LIB );
		
		$client = new IXR_Client( $this->xml_rpc_url );
		
		if(!$client->query( $method, $params, $this->username, $this->password )) { 
			
			return array(
				'errorCode' => $client->getErrorCode(),
				'errorMsg'	=> $client->getErrorMessage()
			);
			   
        }
        
        $results = $client->getResponse();
		
		return $results;
		
	}
	
	public function rollback_post_import( $error_msg, $post_type, $post_title ) {
		
		//Loop cache of imported posts and delete all with post type 'post', leave cats, terms, media
		if( !empty($this->imported_posts) ) {
			foreach( $this->imported_posts as $post_id => $post_type ) {
				
				if( $post_type == 'post' ) {
				
					wp_delete_post( $post_id, TRUE );
					
				}
				
			}
		}
		
		//Send admin an error email
		$this->send_admin_email( "{$error_msg} {$post_type} {$post_title}." );
		
		die;
		
	}
	
	/**
	* filter_post_before_import
	* 
	* @Called By Filter: 'wp_import_post_data_raw'
	*
	* Filters post data just before the import script adds the new post to wp.
	* Overrides the author id of each imported post and sets it to that provided in the plugin options
	* Also detects if the master server is useing the custom post type 'prso_synd_toolkit' and converts it to 'post'
	* 
	* @param	array	$post
	* @return	array	$post
	* @access 	public
	* @author	Ben Moody
	*/
	public function filter_post_before_import( $post ) {
		
		//Init vars
		$author_id = 1;
		
		if( isset($this->class_config['import_options']['author_id']) ) {
			$author_id = $this->class_config['import_options']['author_id'];
		}
		
		//Set post author
		$post['post_author'] = $author_id;
		
		//Detect plugin cutsom post type and change to post
		if( $post['post_type'] == 'prso_synd_toolkit' ) {
			$post['post_type'] = 'post';
		}
		
		return $post;
	}
	
	/**
	* post_imported_successfully
	* 
	* @Called By Action: 'wp_import_insert_post'
	*
	* Called when the import script has successfully imported a single post, runs for each post imported
	* 
	* After a post is imported the method updates the plugin option to record the publish date of the last
	* post successfully imported. This is passed to the master api when a request for new posts is made.
	* Thus the master api knows to only send the client posts published AFTER this date.
	* 
	* @access 	public
	* @author	Ben Moody
	*/
	public function post_imported_successfully( $post_id, $original_post_ID, $postdata, $post ) {
		
		//Update the plugin option tracking the post date of the last succesfully imported post
		update_option( PRSOSYNDTOOLKITREADER__LAST_IMPORT_OPTION, $post['post_date'] ); 
		
		//Cache post id in class var for use if we need to undo import
		$this->imported_posts[$post_id] = $post['post_type'];
		
	}
	
	/**
	* add_push_webhook_query_var
	* 
	* @Called By Filter: 'query_vars'
	*
	* Adds a custom query var to wordpress for the push notification url param
	* 
	* @param	array	$vars
	* @return	array	$vars
	* @access 	public
	* @author	Ben Moody
	*/
	public function add_push_webhook_query_var( $vars ) {
		
		//Add our push notification webhook
		$vars[] = $this->push_webhook_var;
		
		return $vars;
	}
	
	/**
	* detect_webhook_push_request
	*
	* @Called By Action: 'parse_request'
	* 
	* Detects our custom push webhook query var and init the get syndication posts method
	* 
	* @param	array	name
	* @access 	public
	* @author	Ben Moody
	*/
	public function detect_webhook_push_request( $query ) {
		
		//Detect if our custom push webhook is in the request
		if( isset($query->query_vars[ $this->push_webhook_var ]) && ($query->query_vars[ $this->push_webhook_var ] == 'true') ) {
			
			//Init request to Content Syndication Toolkit Master to get posts
			$this->get_syndication_posts();
			
		}
		
		
	}
	
	/**
	* get_syndication_posts
	* 
	* @Called By $this->detect_webhook_push_request()
	*
	* Performs the actions required to get sydication posts via an xmlrpc api request
	* Then format the data, add the posts along with categories, tags, and media
	* 
	* @access 	public
	* @author	Ben Moody
	*/
	private function get_syndication_posts() {
		
		//Init vars
		$params 	= array();
		$results	= NULL;
		
		//Add any metyhod specific params in $params[3] as array
		$params = array(
			'last_date' => get_option( PRSOSYNDTOOLKITREADER__LAST_IMPORT_OPTION ) //Get plugin option stating the end date of the last post pull & pass as a param
		);
		
		//Make api request
		$results = $this->api_request( $this->api_method_get_posts, $params );
		
		//Check for error
		if( $this->is_api_request_error($results) ) {
			//Shh, silence :)
			return;
		}
		
		//Init import
		$this->init_post_import( $results );
		
		//PrsoSyndToolkitReader::plugin_error_log( $results );
		
		exit();
		
	}
	
	/**
	* init_post_import
	* 
	* @Called By $this->get_syndication_posts()
	*
	* Performs two main tasks.
	*
	* 1. 	Loops images sizes provided by master api, loops and adds them to the client wp install
	*		Ready to be used during the image import phase
	*
	* 2. 	Init the importer class, first importing all attachments, then importing posts
	*		Note if there is an error when importing an email is sent to the blog admin email
	* 
	* @access 	private
	* @author	Ben Moody
	*/
	private function init_post_import( $results ) {
		
		require ( ABSPATH . 'wp-admin/includes/post.php' );
		require ( ABSPATH . 'wp-admin/includes/image.php' );
		
		//Setup our custom image sizes as provided by the master server
		if( isset($results['image_sizes']) ) {
			foreach( $results['image_sizes'] as $image_name => $img_data ) {
				
				if( isset($img_data['width'], $img_data['height'], $img_data['crop']) ) {
					add_image_size( 'pcsr-' . $image_name, $img_data['width'], $img_data['height'], $img_data['crop'] );
				}
				
			}
		}
		
		//Include importer class
		$importer_inc 	= $this->plugin_path . 'inc/class/class.importer.php';
		if( file_exists($importer_inc) ) {
		
			require_once( $importer_inc );
			$Importer = new WP_Import();
			
			//Import attachments first
			if( isset($results['posts'], $results['attachments']) ) {
				
				$import_result = $Importer->import( $results['attachments'] );
				
				if( is_wp_error($import_result) ) {
					$this->send_admin_email( $import_result->get_error_message() );
					die;
				}
				
				//No errors, lets import the posts
				$import_result = $Importer->import( $results['posts'] );
				
				if( is_wp_error($import_result) ) {
					$this->send_admin_email( $import_result->get_error_message() );
					die;
				}
				
			}
			
			
		}
		
	}
	
	/**
	 * Determine if a post exists based on title, content, and date
	 *
	 * @since 2.0.0
	 *
	 * @param string $title Post title
	 * @param string $content Optional post content
	 * @param string $date Optional post date
	 * @return int Post ID if post exists, 0 otherwise.
	 */
	public static function post_exists($title, $content = '', $date = '') {
		global $wpdb;
	
		$post_title = wp_unslash( sanitize_post_field( 'post_title', $title, 0, 'db' ) );
		$post_content = wp_unslash( sanitize_post_field( 'post_content', $content, 0, 'db' ) );
		$post_date = wp_unslash( sanitize_post_field( 'post_date', $date, 0, 'db' ) );
	
		$query = "SELECT ID FROM $wpdb->posts WHERE 1=1";
		$args = array();
	
		if ( !empty ( $date ) ) {
			$query .= ' AND post_date = %s';
			$args[] = $post_date;
		}
	
		if ( !empty ( $title ) ) {
			$query .= ' AND post_title = %s';
			$args[] = $post_title;
		}
	
		if ( !empty ( $content ) ) {
			$query .= 'AND post_content = %s';
			$args[] = $post_content;
		}
	
		if ( !empty ( $args ) )
			return (int) $wpdb->get_var( $wpdb->prepare($query, $args) );
	
		return 0;
	}
	
	/**
	* is_api_request_error
	* 
	* Simple helper to check for any errors returned from api request
	* Also sends the wordpress admin an email with a copy of the api error message
	* 
	* @param	array	result
	* @return	bool
	* @access 	private
	* @author	Ben Moody
	*/
	private function is_api_request_error( $result ) {
		
		//Check for error code
		if( isset($result['errorCode']) ) {
			
			//Check if server error
			if( strpos($result['errorMsg'], 'HTTP status code was not 200') ) {
				
				$this->send_admin_email(
					_x( 'Problem contacting the server. Please confirm your API Username and Password are correct.', 'text', PRSOSYNDTOOLKITREADER__DOMAIN )
				);
				
			} else {
				
				$this->send_admin_email($result['errorMsg']);
				
			}
			
			return TRUE;
		}
		
		return FALSE;
	}
	
	/**
	* send_admin_email
	* 
	* Sends an error warning email to the wordpress admin
	* 
	* @access 	private
	* @author	Ben Moody
	*/
	private function send_admin_email( $errorMsg ) {
		
		PrsoSyndToolkitReader::send_admin_email( $errorMsg );
		
	}
	
}
?>