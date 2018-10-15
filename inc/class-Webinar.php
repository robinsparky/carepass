<?php
//TODO: use namespaces
//namespace Care\Custom;

/** 
 * Data and functions for the Webinar Custom Post Type
 * @class  Webinar
 * @package Care
 * @version 1.0.0
 * @since   0.1.0
*/
class Webinar extends BaseCustomMediaPostType {
	
	const CUSTOM_POST_TYPE     = 'carewebinar';
	const CUSTOM_POST_TYPE_TAX = 'carewebinartax';
	const CUSTOM_POST_TYPE_TAG = 'webinartag';

	const VIDEO_META_KEY = '_care_webinar_video_key';
	const CURRICULUM_META_KEY = '_care_webinar_curriculum_key';
	const JS_OBJECT = 'care_webinar_media_obj';
	
	static private $AllWebinars = array();

    //Only emit on these page
	private $hooks = array('post.php', 'post-new.php');
	private $curriculum;
	private $log;
	
	/**
	 * Retrieve All PASS Webinars and meta data from db
	 * @param $force Boolean to force fresh retrieval from database
	 */
	static public function getWebinarDefinitions( $force = false ) {
		$loc = __CLASS__ . '::' . __FUNCTION__;

		$map = array( self::VIDEO_META_KEY => 'video'
					, self::CURRICULUM_META_KEY => 'curriculum'
				);

		global $wpdb;
		if( $force || count( self::$AllWebinars ) < 1 ) {
			$sql = "SELECT p.ID as id, p.post_title as name, pm.meta_key as 'key', pm.meta_value as val
					FROM {$wpdb->prefix}postmeta pm
					inner join {$wpdb->prefix}posts p on p.ID = pm.post_id
					where p.post_type = '%s' 
					order by p.post_title;";
			$query = $wpdb->prepare( $sql, self::CUSTOM_POST_TYPE );
			$result = $wpdb->get_results( $query, ARRAY_A );

			$retval = array();
			$ids = array();
			foreach( $result as $row ) {
				// error_log("$loc-->row:");
				// error_log( print_r($row, true ) );
				if( in_array( $row['id'], $ids ) ) {
					foreach( $retval as &$c ) {
						if( $row['id'] ===  $c['id'] ) {
							if( array_key_exists($row['key'], $map ) ) {
								$c[$map[$row['key']]] = $row['val'];
							}
						}
					}
				}
				else {
					array_push( $ids, $row['id'] );
					$webinar = array();
					$webinar['id'] = $row['id'];
					$webinar['name'] = $row['name'];
					if( array_key_exists($row['key'], $map ) ){
						$webinar[$map[$row['key']]] = $row['val'];
					}
					array_push( $retval, $webinar );
				}
				// error_log("$loc-->retval:");
				// error_log( print_r( $retval, true ) );
			}
			self::$AllWebinars = $retval;
		}

		return self::$AllWebinars;
	}

	public function __construct() {
		$loc = __CLASS__ . '::' . __FUNCTION__;

		$this->mediaMetaBoxId = 'care_webinar_video_meta_box';
		$this->curriculum = array( 'essential' => 'Essential', 'recommended' =>'Recommended' );
		$this->log = new BaseLogger( true );
	}

	public function register() {
        $loc = __CLASS__ . '::' . __FUNCTION__;
		$this->log->error_log( $loc );

		add_action( 'init', array( $this, 'customPostType') ); 
		add_action( 'init', array( $this, 'customTaxonomy' ) );

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue') );
			
		add_filter( 'manage_' . self::CUSTOM_POST_TYPE . '_posts_columns', array( $this, 'addColumns' ), 5 );
		add_action( 'manage_' . self::CUSTOM_POST_TYPE . '_posts_custom_column', array( $this, 'getColumnValues'), 5, 2 );
		
		//Required actions for meta box
		add_action( 'add_meta_boxes', array( $this, 'metaBoxes' ), 5 );
		add_action( 'save_post', array( $this, 'curriculumSave' ), 5 ) ;
		add_action( 'save_post', array( $this, 'videoSave' ), 5 );

	}
	
	public function enqueue( $hook ) {
        $loc = __CLASS__ . '::' . __FUNCTION__;
        $this->log->error_log( "$loc --> $hook" ); 

        //Make sure we loading js for these pages
        if( in_array( $hook, $this->hooks ) ) {
            //Enqueue WP media js
            wp_enqueue_media();

            wp_register_script( 'care-video-uploader'
                            , get_stylesheet_directory_uri() . '/js/care-webinar-media-uploader.js'
                            , array('jquery') );
	
            wp_localize_script( 'care-video-uploader', self::JS_OBJECT, $this->get_data() );

            wp_enqueue_script( 'care-video-uploader' );
		}
	}

	public function customPostType() {
		$loc = __CLASS__ . '::' . __FUNCTION__;
		$this->log->error_log( $loc );

		$labels = array( 'name' => 'PASS Webinars'
						, 'singular_name' => 'Webinar'
						, 'add_new' => 'Add Webinar'
						, 'add_new_item' => 'New Webinar'
						, 'new_item' => 'New Webinar'
						, 'edit_item' => 'Edit Webinar'
						, 'view_item' => 'View Webinar'
						, 'all_items' => 'All Webinars'
						, 'menu_name' => 'PASS Webinars'
						, 'search_items'=>'Search Webinars'
						, 'not_found' => 'No Webinars found'
						, 'not_found_in_trash'=> 'No Webinars found in Trash' );

		$args = array( 'labels' => $labels
					, 'menu_position' => 7
					, 'menu_icon' => 'dashicons-video-alt2'
					, 'exclude_from_search' => false
					, 'has_archive' => true
					, 'publicly_queryable' => true
					, 'query_var' => true
					, 'capability_type' => 'post'
					, 'hierarchical' => false
					, 'rewrite' => array( 'slug' => self::CUSTOM_POST_TYPE )
					, 'supports' => array( 'title', 'editor', 'thumbnail', 'excerpt', 'revisions' ) 
					, 'public' => true );
		register_post_type( self::CUSTOM_POST_TYPE, $args );
	}
	
	public function customTaxonomy() {
        $loc = __CLASS__ . '::' . __FUNCTION__;
		$this->log->error_log( $loc );
		
			//hierarchical
		$labels = array( 'name' => 'Webinar Categories'
						, 'singular_name' => 'Webinar Category'
						, 'search_items' => 'Search Webinar Category'
						, 'all_items' => 'All Webinar Categories'
						, 'parent_item' => 'ParentWebinar Category'
						, 'parent_item_colon' => 'Parent Webinar Category:'
						, 'edit_item' => 'Edit Webinar Category'
						, 'update_item' => 'Update Webinar Category'
						, 'add_new_item' => 'Add New Webinar Category'
						, 'new_item_name' => 'New Webinar Category'
						, 'menu_name' => 'Webinar Categories'
						);

		$args = array( 'hierarchical' => true
					 , 'labels' => $labels
					 , 'show_ui' => true
					 , 'show_admin_column' => true
					 , 'query_var' => true
					 , 'rewrite' => array( 'slug' => self::CUSTOM_POST_TYPE_TAX )
					);

		register_taxonomy( self::CUSTOM_POST_TYPE_TAX
						 , array( self::CUSTOM_POST_TYPE )
						 , $args );

		//NOT hierarchical
		register_taxonomy( self::CUSTOM_POST_TYPE_TAG
						 , self::CUSTOM_POST_TYPE
						 , array( 'label' => 'Webinar Tags'
								, 'rewrite' => array( 'slug' => self::CUSTOM_POST_TYPE_TAG )
								, 'hierarchical' => false
						));
	}

	// Add Webinar columns
	public function addColumns( $columns ) {
        $loc = __CLASS__ . '::' . __FUNCTION__;
		$this->log->error_log( $loc );
		
		// column vs displayed title
		$newColumns = array();
		$newColumns['cb'] = $columns['cb'];
		$newColumns['title'] = __('Title', CARE_TEXTDOMAIN );
		$newColumns['taxonomy-carewebinartax'] = __('Category', CARE_TEXTDOMAIN );
		$newColumns['webinar_video'] = __( 'Video', CARE_TEXTDOMAIN  );
		$newColumns['date'] = __('Date', CARE_TEXTDOMAIN );
		return $newColumns;
	}

	// Populate the Webinar columns with values
	public function getColumnValues( $column_name, $postID ) {
		$loc = __CLASS__ . '::' . __FUNCTION__;
		$this->log->error_log( $loc );

		if( $column_name === 'webinar_curriculum' ){
			$val = get_post_meta( $postID, self::CURRICULUM_META_KEY, TRUE );
			echo $this->curriculum[$val];
		}
		elseif( $column_name === 'webinar_video' ){
			$url = get_post_meta( $postID, self::VIDEO_META_KEY, TRUE );
			
			if( @$url  ) {
				echo "<a href='$url' target='_blank'>Watch Video</a>";
			}
			else {
				echo "Nothing selected";
			}
		}
	}
		
	/* 
	================================================
		Meta Boxes
	================================================
	*/
	public function metaBoxes() {

		/* Curriculum meta box */
		add_meta_box( 'care_webinar_curriculum_meta_box' //id
					, 'Curriculum' //Title
					, array( $this, 'curriculumCallback' ) //Callback
					, self::CUSTOM_POST_TYPE //mixed: screen et cpt name, or ???
					, 'side' //context: normal, side, 
					, 'default' // priority: low, high, default
						// array callback args
					);
					
		/* Webinar Video Meta Box */
		add_meta_box( $this->mediaMetaBoxId //id
					, 'Video' //Title
					, array( $this, 'videoCallback' ) //Callback
					, self::CUSTOM_POST_TYPE //mixed: screen et cpt name, or ???
					, 'side' //context: normal, side, 
					, 'default' // priority: low, high, default
						// array callback args
					);

	}
	
	public function curriculumCallback( $post ) {
        $loc = __CLASS__ . '::' . __FUNCTION__;
		$this->log->error_log( $loc );

		wp_nonce_field( 'curriculumSave' //action
					  , 'care_webinar_curriculum_nonce');

		$actual = get_post_meta( $post->ID, self::CURRICULUM_META_KEY, true );
		$this->log->error_log("$loc --> current value='$actual'");

		//Now echo the html desired
		$this->log->error_log( $this->curriculum, "Curriculum options:" );
		echo'<select name="care_webinar_curriculum_field">';
		foreach( $this->curriculum as $key => $val ) {
			$disp = esc_attr($val);
			$value = esc_attr($key);
			$sel = '';
			if($actual === $key) $sel = 'selected';
			echo "<option value='$value' $sel>$disp</option>";
		}
		echo '</select>';
	}

	public function curriculumSave( $post_id ) {
        $loc = __CLASS__ . '::' . __FUNCTION__;
		$this->log->error_log($loc);

		if( ! isset( $_POST['care_webinar_curriculum_nonce'] ) ) {
			$this->log->error_log("$loc --> no nonce");
			return;
		}

		if( ! wp_verify_nonce( $_POST['care_webinar_curriculum_nonce'] , 'curriculumSave'  )) {
			$this->log->error_log("$loc --> bad nonce");
			return;
		}

		if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			$this->log->error_log("$loc --> doing autosave");
			return;
		}

		if( ! current_user_can( 'edit_post', $post_id ) ) {
			$this->log->error_log("$loc --> cannot edit post");
			return;
		}

		if( ! isset( $_POST['care_webinar_curriculum_field'] ) ) {
			$this->log->error_log("$loc --> no curriculum field");
			return;
		}

		$my_data = sanitize_text_field( $_POST['care_webinar_curriculum_field'] );
		$this->log->error_log("$loc --> returned value='$my_data'");

		update_post_meta( $post_id, self::CURRICULUM_META_KEY, $my_data );
	}
	
	public function videoCallback( $post ) {
        $loc = __CLASS__ . '::' . __FUNCTION__;
		$this->log->error_log($loc);

		wp_nonce_field( 'videoSave' //action
					  , 'care_webinar_video_nonce');

		$actual = get_post_meta( $post->ID, self::VIDEO_META_KEY, true );
		$this->log->error_log("$loc --> current url='$actual'");

		//Now echo the html desired
		echo $this->mediaSelectorEmitter( $actual );
		$markup = sprintf( "<div class='%s'><span>Nothing Selected</span></div>", $this->mediaContainer );
		if( @$actual  ) {
			$markup = sprintf( "<div class='%s'><video id='temp' controls width:'200'><source src='%s' type='video/mp4'>HTML5 video not supported.</video></div>"
							 , $this->mediaContainer, $actual);
		}
		echo $markup;
	}

	public function videoSave( $post_id ) {
        $loc = __CLASS__ . '::' . __FUNCTION__;
		$this->log->error_log($loc);

		if( ! isset( $_POST['care_webinar_video_nonce'] ) ) {
			$this->log->error_log("$loc --> no nonce");
			return;
		}

		if( ! wp_verify_nonce( $_POST['care_webinar_video_nonce'] , 'videoSave'  )) {
			$this->log->error_log("$loc --> bad nonce");
			return;
		}

		if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			$this->log->error_log("$loc --> doing autosave");
			return;
		}

		if( ! current_user_can( 'edit_post', $post_id ) ) {
			$this->log->error_log("$loc --> cannot edit post");
			return;
		}

		if( ! isset( $_POST['care-media-selection'] ) ) {
			$this->log->error_log("$loc --> no video field");
			return;
		}
		else {
			$url = $_POST['care-media-selection'];
			$this->log->error_log( "$loc --> video url='$url'" );
		}

		$this->log->error_log( $_POST, "$loc: _POST" );

		$video_data = sanitize_text_field( $_POST['care-media-selection'] );
		$this->log->error_log("$loc --> sanitized video url='$video_data'");

		update_post_meta( $post_id, self::VIDEO_META_KEY, $video_data );
	}

} //end class

if( class_exists('Webinar') ) {
	$care_webinar = new Webinar();
	$care_webinar->register();
}
