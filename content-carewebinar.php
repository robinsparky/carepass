<div id="post-<?php the_ID(); ?>" <?php post_class('blog-lg-area-left'); ?>>
	<div class="media">						
	<?php //appointment_aside_meta_content(); ?>
		<div class="media-body">
			<?php // Check Image size for fullwidth template
				 appointment_post_thumbnail('','img-responsive');
				 appointment_post_meta_content();
				 $ok = false;
				 //Only roles defined in option called 'care_roles_that_watch' can view a webinar
				 if (is_user_logged_in()) {
					 $currentUser = wp_get_current_user();
					 $rolesWatch = esc_attr( get_option( 'care_roles_that_watch' ) );
					 $rolesWatchArr = explode( ",", $rolesWatch );
					 foreach( $rolesWatchArr as $roleName ) {
						 if( in_array( $roleName, $currentUser->roles ) ) {
							 $ok = true;
							 break;
						 }
					 }
				 }
				 $postid = get_the_ID();
				 $videoUrl = get_post_meta( $postid, Webinar::VIDEO_META_KEY, true ); 
				 error_log( __FILE__ . ": Postid='$postid'; Video Url='$videoUrl'");
				?>
				<h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
				
				<video id="<?php the_ID(); ?>" data-name="<?php the_Title()?>" width="600"  
					poster="http://passdevel.care4nurses.org/wp-content/uploads/Mix-Logo-3.jpg">
					<source src="<?php echo $videoUrl ?>" type="video/mp4">
					Your browser does not support HTML5 video.
				</video>
				<?php 
					if( $ok ) {
				?>
				<div class="webinar-buttons">
					<button type="button" id="play">Play</button>
					<!-- <button type="button" id="restart">Restart</button> -->
					<!-- <button type="button" id="makebig">Big</button>
					<button type="button" id="makesmall">Small</button>
					<button type="button" id="makenormal">Normal</button> -->
					<button type="button" id="full-screen">Full-Screen</button>
				</div>

				<ul class="webinar-controls">
					<li><label for="progress">Progress</label><progress class="webinar-progress" id="progress" value="0"></progress></li>
					<li><label for="seek-bar">Seek</label><input type="range" id="seek-bar"  list="webinar-tickmarks"></li>
					<li><label for="volume-bar">Volume</label><input type="range" id="volume-bar" min="0.0" max="1.0" step="0.1"></li>
				</ul>
				<datalist id="webinar-tickmarks">
					<option value="0" label="0%">
					<option value="10">
					<option value="20">
					<option value="30">
					<option value="40">
					<option value="50" label="50%">
					<option value="60">
					<option value="70">
					<option value="80">
					<option value="90">
					<option value="100" label="100%">
				</datalist>

				<?php
				}
				// call editor content of post/page	
				the_content( __('Read More', 'appointment' ) );
				wp_link_pages( );
			   ?>
		</div>
	 </div>
	 <div id='care-resultmessage'></div>
</div>