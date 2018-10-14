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
	const CUSTOM_POST_TYPE_TAX = 'coursecategory';
	const CUSTOM_POST_TYPE_TAG = 'coursetag';

	const VIDEO_META_KEY      = '_care_course_video_key';
	const PRICE_META_KEY      = '_care_course_price_key';
	const CURRICULUM_META_KEY = '_care_course_curriculum_key';
	const DURATION_META_KEY   = '_care_course_duration_key';
	const NEEDS_APPROVAL_META_KEY = '_care_course_needs_approval_key';

	const JS_OBJECT = 'care_course_media_obj';

	static private $AllCourses = array();
	
    //Only emit on this page
	private $hooks = array('post.php', 'post-new.php');
	private $curriculum;
	private $needsApproval;

	private $log;

	
	/**
	 * Retrieve All Care Courses and meta data from db
	 * @param $force Boolean to force fresh retrieval from database
	 */
	static public function getCourseDefinitions( $force = false ) {
		$loc = __CLASS__ . '::' . __FUNCTION__;

		$map = array( self::PRICE_META_KEY => 'price'
					, self::NEEDS_APPROVAL_META_KEY => 'needsapproval'
					, self::DURATION_META_KEY => 'duration'
					, self::VIDEO_META_KEY => 'video'
					, self::CURRICULUM_META_KEY => 'curriculum'
				);

		global $wpdb;
		if( $force || count( self::$AllCourses ) < 1 ) {
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
					$course = array();
					$course['id'] = $row['id'];
					$course['name'] = $row['name'];
					if( array_key_exists($row['key'], $map ) ){
						$course[$map[$row['key']]] = $row['val'];
					}
					array_push( $retval, $course );
				}
				// error_log("$loc-->retval:");
				// error_log( print_r( $retval, true ) );
			}
			self::$AllCourses = $retval;
		}

		return self::$AllCourses;
	}

	/**
	 * Retrive the price for a course
	 * @param $courseId The ID of the care course
	 * @return The price of the specified course
	 */
	static public function getCoursePrice( $courseId ) {

		global $wpdp;

		$sql = "SELECT pm.meta_value as price 
				FROM {$wpdb->prefix}postmeta pm 
				inner join {$wpdb->prefix}posts p on p.ID = pm.post_id 
				where p.post_type = '%s' 
				and pm.meta_key = '%s'
				and pm.post_id = %d;";

		$query = $wpdb->prepare($sql, self::CUSTOM_POST_TYPE, self::PRICE_META_KEY, $courseId );
		$result = $wpdb->get_var( $query );
		if( !is_numeric( $result ) ) $result = 0.00;
		return $result;
	}

	/**
	 * Retrive the 'needs approval' field for a course
	 * @param $courseId The ID of the care course
	 * @return String with a value of 'yes' or 'no'
	 */
	static public function getCourseNeedsApproval( $courseId ) {

		global $wpdp;

		$sql = "SELECT pm.meta_value as price 
				FROM {$wpdb->prefix}postmeta pm 
				inner join {$wpdb->prefix}posts p on p.ID = pm.post_id 
				where p.post_type = '%s' 
				and pm.meta_key = '%s'
				and pm.post_id = %d;";

		$query = $wpdb->prepare($sql, self::CUSTOM_POST_TYPE, self::NEEDS_APPROVAL_META_KEY, $courseId );
		$result = $wpdb->get_var( $query );
		if( $result !== 'yes' ) $result = 'no';
		return $result;
	}

	public function __construct() {
		$loc = __CLASS__ . '::' . __FUNCTION__;
		$this->log = new BaseLogger( true );

		$this->mediaMetaBoxId = 'care_course_video_meta_box';
		$this->curriculum = array( 'recommended' =>'Recommended', 'essential' => 'Essential' );
		$this->needsApproval = array('no' => 'No', 'yes' => 'Yes');
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
		add_action( 'save_post', array( $this, 'approvalSave') );

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

		$labels = array( 'name' => 'PASS Courses'
					   , 'singular_name' => 'PASS Course'
					   , 'add_new' => 'Add Course'
					   , 'add_new_item' => 'New Course'
					   , 'new_item' => 'New Course'
					   , 'edit_item' => 'Edit Course'
					   , 'view_item' => 'View Course'
					   , 'all_items' => 'All Courses'
					   , 'menu_name' => 'PASS Courses'
					   , 'search_items'=>'Search Courses'
					   , 'not_found' => 'No PASS Courses found'
					   , 'not_found_in_trash'=> 'No PASS Courses found in Trash'
					   , 'parent_item_colon' => 'Parent PASS Course:' );
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
		$newColumns['course_approval'] = __('Needs Approval', CARE_TEXTDOMAIN );
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
        $loc = __CLASS__ . '::' . __FUNCTION__;
		$this->log->error_log( $loc );

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
		elseif( $column_name = 'course_approval' ) {
			$needsApproval = get_post_meta( $postID, self::NEEDS_APPROVAL_META_KEY, TRUE );
			$result = 'No';
			foreach( $this->needsApproval as $value => $name ) {
				if( $needsApproval === $value ) {
					$result = $name;
				}
			}
			echo $result;
		}

	}

	public function customTaxonomy() {
        $loc = __CLASS__ . '::' . __FUNCTION__;
		$this->log->error_log( $loc );
		
			//hierarchical
		$labels = array( 'name' => 'PASS Course Categories'
						, 'singular_name' => 'PASS Course Category'
						, 'search_items' => 'Search Course Category'
						, 'all_items' => 'All PASS Course Categories'
						, 'parent_item' => 'Parent PASS Course Category'
						, 'parent_item_colon' => 'Parent PASS Course Category:'
						, 'edit_item' => 'Edit PASS Course Category'
						, 'update_item' => 'Update PASS Course Category'
						, 'add_new_item' => 'Add New PASS Course Category'
						, 'new_item_name' => 'New PASS Course Category'
						, 'menu_name' => 'PASS Course Categories'
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
						 , array( 'label' => 'PASS Course Tags'
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

		add_meta_box( 'care_course_needs_approval_meta_box'
					, 'Needs Approval' //Title
					, array( $this, 'approvalCallBack' ) //Callback
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

	
	public function approvalCallBack( $post ) {
        $loc = __CLASS__ . '::' . __FUNCTION__;
		$this->log->error_log($loc);

		wp_nonce_field( 'needsApprovalSave' //action
					  , 'care_needs_approval_nonce');

		$actual = get_post_meta( $post->ID, self::NEEDS_APPROVAL_META_KEY, true );
		if( !@$actual ) $actual = 'no';
		$this->log->error_log("$loc --> actual=$actual");

		//Now echo the html desired
		$markup = '';
		foreach( $this->needsApproval as $value => $name ) {
			if( $actual === $value ) {
				$markup .= sprintf( '&nbsp;<input type="radio" name="care_course_need_approval" value="%s" checked>%s'
								  , $value, $name);
			}
			else {
				$markup .= sprintf( '&nbsp;<input type="radio" name="care_course_need_approval" value="%s">%s'
								  , $value, $name);
				}
			}
		
		echo $markup;
	}
	
	public function approvalSave( $post_id ) {
        $loc = __CLASS__ . '::' . __FUNCTION__;
		$this->log->error_log($loc);

		if( ! isset( $_POST['care_needs_approval_nonce'] ) ) {
			$this->log->error_log("$loc --> no nonce");
			return;
		}

		if( ! wp_verify_nonce( $_POST['care_needs_approval_nonce'] , 'needsApprovalSave'  )) {
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

		$needsApproval = 'no';
		if( ! isset( $_POST['care_course_need_approval'] ) ) {
			$this->log->error_log("$loc --> no approval field");
			return;
		}
		else {
			$needsApproval = $_POST['care_course_need_approval'];
		}

		if( empty( $needsApproval ) ) $needsApproval = 'no';
		if( ! in_array( $needsApproval , array_keys($this->needsApproval ) ) ) {
			$needsApproval = 'no';
		}
		$this->log->error_log("$loc --> duration='$needsApproval'");
		update_post_meta( $post_id, self::NEEDS_APPROVAL_META_KEY, $needsApproval );
	}

} //end class

if( class_exists('Course') ) {
	$care_course = new Course();
	$care_course->register();
}
