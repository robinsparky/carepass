<?php
//TODO: use namespaces
//namespace Care\Custom;

/** 
 * Data and functions for the Course Custom Post Type
 * @class  Course
 * @package Care
 * @version 1.0.0
 * @since   0.1.0
*/
class Course extends BaseCustomMediaPostType {
	
	const CUSTOM_POST_TYPE = 'carecourse';

	const VIDEO_META_KEY      = '_care_course_video_key';
	const PRICE_META_KEY      = '_care_course_price_key';
	const CURRICULUM_META_KEY = '_care_course_curriculum_key';
	const DURATION_META_KEY   = '_care_xourse_duration_key';

	const JS_OBJECT = 'care_course_media_obj';

	static public $AllCourses = array();
	
    //Only emit on this page
	private $hooks = array('post.php', 'post-new.php');
	private $curriculum;

	private $log;

	//Retrieve Care Courses from db
	static public function getCourseDefinitions( $force = false ) {
		$loc = __CLASS__ . '::' . __FUNCTION__;

		global $wpdb;
		if( $force || count( self::$AllCourses ) < 1 ) {
			self::$AllCourses = $wpdb->get_results( "select `ID` as id, `post_title` as name "
												  . "from `wp_posts` "
												  . "where `post_type` = '" . self::CUSTOM_POST_TYPE . "'; "
												  , ARRAY_A );
		}
		
		return self::$AllCourses;
	}

	public function __construct() {
		$loc = __CLASS__ . '::' . __FUNCTION__;
		$this->log = new BaseLogger( true );

		$this->mediaMetaBoxId = 'care_course_video_meta_box';
		$this->curriculum = array( 'recommended' =>'Recommended', 'essential' => 'Essential' );
	}

	public function register() {
        $loc = __CLASS__ . '::' . __FUNCTION__;
		$this->log->error_log( $loc );

		add_action( 'init', array( $this, 'customPostType') ); 
		add_action( 'init', array( $this, 'customTaxonomy' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue') );
			
		add_filter( 'manage_' . self::CUSTOM_POST_TYPE . '_posts_columns', array( $this, 'addColumns' ), 10 );
		add_action( 'manage_' . self::CUSTOM_POST_TYPE . '_posts_custom_column', array( $this, 'getColumnValues'), 10, 2 );
		add_filter( 'manage_edit-' .self::CUSTOM_POST_TYPE . '_sortable_columns', array( $this, 'sortableColumns') );
		add_action( 'pre_get_posts', array( $this, 'orderby' ) );
		
		//Required actions for meta boxes
		add_action( 'add_meta_boxes', array( $this, 'metaBoxes' ) );
		add_action( 'save_post', array( $this,'curriculumSave') ) ;
		add_action( 'save_post', array( $this, 'videoSave') );
		add_action( 'save_post', array( $this, 'priceSave') );
		add_action( 'save_post', array( $this, 'durationSave') );

	}
	
	public function enqueue( $hook ) {
        $loc = __CLASS__ . '::' . __FUNCTION__;
        $this->log->error_log( "$loc --> $hook" ); 

        //Make sure we are rendering the "user-edit" page
        if( in_array( $hook, $this->hooks ) ) {
            //Enqueue WP media js
            wp_enqueue_media();

            wp_register_script( 'care-media-uploader'
                            , get_stylesheet_directory_uri() . '/js/care-course-media-uploader.js'
                            , array('jquery') );
    
            wp_localize_script( 'care-media-uploader', self::JS_OBJECT, $this->get_data() );

            wp_enqueue_script( 'care-media-uploader' );
		}
	}

	public function customPostType() {
		$loc = __CLASS__ . '::' . __FUNCTION__;
		$this->log->error_log( $loc );

		$labels = array( 'name' => 'Care Courses'
					   , 'singular_name' => 'Care Course'
					   , 'add_new' => 'Add Care Course'
					   , 'add_new_item' => 'New Care Course'
					   , 'new_item' => 'New Care Course'
					   , 'edit_item' => 'Edit Care Course'
					   , 'view_item' => 'View Care Course'
					   , 'all_items' => 'All Care Courses'
					   , 'menu_name' => 'Care Courses'
					   , 'search_items'=>'Search Care Courses'
					   , 'not_found' => 'No Care Courses found'
					   , 'not_found_in_trash'=> 'No Care Courses found in Trash'
					   , 'parent_item_colon' => 'Parent Care Course:' );
		$args = array( 'labels' => $labels
					 //, 'taxonomies' => array( 'category', 'post_tag' )
					 , 'menu_position' => 6
					 , 'menu_icon' => 'dashicons-welcome-learn-more'
					 , 'exclude_from_search' => false
					 , 'has_archive' => true
					 , 'publicly_queryable' => true
					 , 'query_var' => true
					 , 'capability_type' => 'post'
					 , 'hierarchical' => false
					 , 'rewrite' => array( 'slug' => self::CUSTOM_POST_TYPE )
					 //, 'supports' => array( 'title', 'editor', 'author', 'thumbnail', 'comments', 'excerpt', 'revisions', 'custom-fields' ) 
					 , 'supports' => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'revisions' ) 
					 , 'public' => true );
		register_post_type( self::CUSTOM_POST_TYPE, $args );
	}

	// Add Course columns
	public function addColumns( $columns ) {
        $loc = __CLASS__ . '::' . __FUNCTION__;
		$this->log->error_log( $columns, $loc );

		// column vs displayed title
		$newColumns['cb'] = $columns['cb'];
		$newColumns['title'] = __('Title', CARE_TEXTDOMAIN );
		$newColumns['taxonomy-coursecategory'] = __('Category', CARE_TEXTDOMAIN );
		$newColumns['course_price'] = __('Price', CARE_TEXTDOMAIN );
		$newColumns['course_duration'] = __('Duration', CARE_TEXTDOMAIN );
		$newColumns['course_curriculum'] = __( 'Curriculum', CARE_TEXTDOMAIN );
		$newColumns['course_video'] = __( 'Video', CARE_TEXTDOMAIN  );
		$newColumns['date'] = __('Date', CARE_TEXTDOMAIN );
		return $newColumns;
	}

	public function sortableColumns ( $columns ) {
		$columns['course_price'] = 'priceOfCourse';
		$columns['taxonomy-coursecategory'] = 'categorySort';
		return $columns;
	}

	public function orderby ( $query ) {

		if( ! is_admin() || ! $query->is_main_query() ) {
			return;
		  }
		  
		if ( 'priceOfCourse' === $query->get( 'orderby') ) {
			$query->set( 'orderby', 'meta_value' );
			$query->set( 'meta_key', self::PRICE_META_KEY );
			$query->set( 'meta_type', 'numeric' );
		}
		elseif( 'categorySort' === $query->get( 'orderby' ) ) {
			$query->set( 'orderby', 'coursecategory' );
		}
	}

	// Populate the Course columns with values
	public function getColumnValues( $column_name, $postID ) {
        $loc = __CLASS__ . '::' . __FUNCTION__;
		$this->log->error_log( "$loc --> $column_name, $postID" );

		if( $column_name === 'course_curriculum' ){
			$val = get_post_meta( $postID, self::CURRICULUM_META_KEY, TRUE );
			echo $this->curriculum[$val];
		}
		elseif( $column_name === 'course_video' ) {
			$url = get_post_meta( $postID, self::VIDEO_META_KEY, TRUE );
			
			if( @$url  ) {
				echo "<a href='$url' target='_blank'>Watch Video</a>";
			}
			else {
				echo "Nothing selected";
			}
		}
		elseif( $column_name === 'course_price' ) {
			$price = get_post_meta( $postID, self::PRICE_META_KEY, TRUE );
			if( !is_numeric( $price ) ) $price = 0.00;

			echo '$' . number_format( $price, 2 );
		}
		elseif( $column_name === 'course_duration' ) {
			$duration = get_post_meta( $postID, self::DURATION_META_KEY, TRUE );
			if( !is_numeric( $duration ) ) $duration = 0;

			echo number_format( $duration, 0 ) . ' hours';
		}

	}

	public function customTaxonomy() {
        $loc = __CLASS__ . '::' . __FUNCTION__;
		$this->log->error_log( $loc );
		
			//hierarchical
		$labels = array( 'name' => 'Course Categories'
						, 'singular_name' => 'Course Category'
						, 'search_items' => 'Search Course Category'
						, 'all_items' => 'All Course Categories'
						, 'parent_item' => 'Parent Course Category'
						, 'parent_item_colon' => 'Parent Course Category:'
						, 'edit_item' => 'Edit Course Category'
						, 'update_item' => 'Update Course Category'
						, 'add_new_item' => 'Add New Course Category'
						, 'new_item_name' => 'New Course Category'
						, 'menu_name' => 'Course Categories'
		);

		$args = array( 'hierarchical' => true
					, 'labels' => $labels
					, 'show_ui' => true
					, 'show_admin_column' => true
					, 'query_var' => true
					, 'rewrite' => array( 'slug' => 'coursecategory' )
		);

		register_taxonomy( 'coursecategory', array( self::CUSTOM_POST_TYPE ), $args );

		//NOT hierarchical
		register_taxonomy( 'coursetag', self::CUSTOM_POST_TYPE, array( 'label' => 'Course Tags'
															         , 'rewrite' => array( 'slug' => 'coursetag' )
															         , 'hierarchical' => false
		));
	}
		
	/* 
	================================================
		Meta Boxes
	================================================
	*/
	public function metaBoxes() {
        $loc = __CLASS__ . '::' . __FUNCTION__;
		$this->log->error_log( $loc );
		
		/* Curriculum meta box */
		add_meta_box( 'care_course_curriculum_meta_box' //id
					, 'Curriculum' //Title
					, array( $this, 'curriculumCallback' ) //Callback
					, self::CUSTOM_POST_TYPE //mixed: screen et cpt name, or ???
					, 'normal' //context: normal, side, 
					, 'high' // priority: low, high, default
						// array callback args
					);
					
		/* Video Meta Box */
		add_meta_box( $this->mediaMetaBoxId //id
					, 'Video' //Title
					, array( $this, 'videoCallback' ) //Callback
					, self::CUSTOM_POST_TYPE //mixed: screen et cpt name, or ???
					, 'normal' //context: normal, side, 
					, 'high' // priority: low, high, default
						// array callback args
					);
		
		add_meta_box( 'care_course_price_meta_box'
					, 'Price' //Title
					, array( $this, 'priceCallBack' ) //Callback
					, self::CUSTOM_POST_TYPE //mixed: screen cpt name or ???
					, 'normal' //context: normal, side
					, 'high' // priority: low, high, default
					// array callback args
				);
				
		add_meta_box( 'care_course_duration_meta_box'
		, 'Duration' //Title
		, array( $this, 'durationCallBack' ) //Callback
		, self::CUSTOM_POST_TYPE //mixed: screen cpt name or ???
		, 'normal' //context: normal, side
		, 'high' // priority: low, high, default
		// array callback args
	);

	}
	
	public function curriculumCallback( $post ) {
        $loc = __CLASS__ . '::' . __FUNCTION__;
		$this->log->error_log($loc);

		wp_nonce_field( 'curriculumSave' //action
					  , 'care_course_curriculum_nonce');

		$actual = get_post_meta( $post->ID, self::CURRICULUM_META_KEY, true );
		$this->log->error_log("$loc --> actual='$actual'");

		//Now echo the html desired
		$this->log->error_log("Curriculum options:");
		$this->log->error_log( print_r($this->curriculum, true ) );
		echo'<select name="care_course_curriculum_field">';
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

		if( ! isset( $_POST['care_course_curriculum_nonce'] ) ) {
			$this->log->error_log("$loc --> no nonce");
			return;
		}

		if( ! wp_verify_nonce( $_POST['care_course_curriculum_nonce'] , 'curriculumSave'  )) {
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

		if( ! isset( $_POST['care_course_curriculum_field'] ) ) {
			$this->log->error_log("$loc --> no curriculum field");
			return;
		}

		$my_data = sanitize_text_field( $_POST['care_course_curriculum_field'] );
		$this->log->error_log("$loc --> my_data=$my_data");

		update_post_meta( $post_id, self::CURRICULUM_META_KEY, $my_data );
	}
	
	public function videoCallback( $post ) {
        $loc = __CLASS__ . '::' . __FUNCTION__;
		$this->log->error_log($loc);

		wp_nonce_field( 'videoSave' //action
					  , 'care_course_video_nonce');

		$actual = get_post_meta( $post->ID, self::VIDEO_META_KEY, true );
		$this->log->error_log("$loc --> actual=$actual");

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

		if( ! isset( $_POST['care_course_video_nonce'] ) ) {
			$this->log->error_log("$loc --> no nonce");
			return;
		}

		if( ! wp_verify_nonce( $_POST['care_course_video_nonce'] , 'videoSave'  )) {
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
			error_log( "$loc --> video url='$url'" );
		}

		$video_data = sanitize_text_field( $_POST['care-media-selection'] );
		$this->log->error_log("$loc --> sanitized video url='$video_data'");

		update_post_meta( $post_id, self::VIDEO_META_KEY, $video_data );
	}
	
	public function priceCallBack( $post ) {
        $loc = __CLASS__ . '::' . __FUNCTION__;
		$this->log->error_log($loc);

		wp_nonce_field( 'coursePriceSave' //action
					  , 'care_course_price_nonce');

		$actual = get_post_meta( $post->ID, self::PRICE_META_KEY, true );
		if( !@$actual ) $actual = 0.00;
		$this->log->error_log("$loc --> actual=$actual");

		//Now echo the html desired
		$markup = sprintf( '<input type="number" name="care_course_price" value="%d" step="0.10">'
							, (float) $actual);
		
		echo $markup;
	}
	
	public function priceSave( $post_id ) {
        $loc = __CLASS__ . '::' . __FUNCTION__;
		$this->log->error_log($loc);

		if( ! isset( $_POST['care_course_price_nonce'] ) ) {
			$this->log->error_log("$loc --> no nonce");
			return;
		}

		if( ! wp_verify_nonce( $_POST['care_course_price_nonce'] , 'coursePriceSave'  )) {
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

		$price = 0.00;
		if( ! isset( $_POST['care_course_price'] ) ) {
			$this->log->error_log("$loc --> no price field");
			return;
		}
		else {
			$price = $_POST['care_course_price'];
		}

		if( !is_numeric( $price ) ) $price = 0.00;

		$this->log->error_log("$loc --> price='$price'");

		update_post_meta( $post_id, self::PRICE_META_KEY, $price );
	}
	
	
	public function durationCallBack( $post ) {
        $loc = __CLASS__ . '::' . __FUNCTION__;
		$this->log->error_log($loc);

		wp_nonce_field( 'courseDurationSave' //action
					  , 'care_course_duration_nonce');

		$actual = get_post_meta( $post->ID, self::DURATION_META_KEY, true );
		if( !@$actual ) $actual = 0;
		$this->log->error_log("$loc --> actual=$actual");

		//Now echo the html desired
		$markup = sprintf( '<input type="number" name="care_course_duration" value="%d" step="1">'
							, (int) $actual);
		
		echo $markup;
	}
	
	public function durationSave( $post_id ) {
        $loc = __CLASS__ . '::' . __FUNCTION__;
		$this->log->error_log($loc);

		if( ! isset( $_POST['care_course_duration_nonce'] ) ) {
			$this->log->error_log("$loc --> no nonce");
			return;
		}

		if( ! wp_verify_nonce( $_POST['care_course_duration_nonce'] , 'courseDurationSave'  )) {
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

		$duration = 0;
		if( ! isset( $_POST['care_course_duration'] ) ) {
			$this->log->error_log("$loc --> no duration field");
			return;
		}
		else {
			$duration = $_POST['care_course_duration'];
		}

		if( !is_numeric( $duration ) ) $duration = 0;
		$this->log->error_log("$loc --> duration='$duration'");
		update_post_meta( $post_id, self::DURATION_META_KEY, $duration );
	}
} //end class

if( class_exists('Course') ) {
	$care_course = new Course();
	$care_course->register();
}
