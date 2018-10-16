<div id="post-<?php the_ID(); ?>" <?php post_class('blog-lg-area-left'); ?>>
	<div class="media">						
	<?php appointment_aside_meta_content(); ?>
		<div class="media-body">
			<?php // Check Image size for fullwidth template
				 appointment_post_thumbnail('','img-responsive');
				 appointment_post_meta_content();
				 $ok = false;
				 if (is_user_logged_in()) {
					 $currentUser = wp_get_current_user();
					 error_log( print_r($currentUser->roles, true ) );
					 if( in_array('um_member', $currentUser->roles )) {
						 $ok = true;
						 error_log($ok);
					 }
				 }
				 $postid = get_the_ID();
				 $videoUrl = get_post_meta( $postid, Webinar::VIDEO_META_KEY, true ); 
				 error_log( __FILE__ . ": Postid='$postid'; Video Url='$videoUrl'");
				?>
				<h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
				
				<video id="<?php the_ID(); ?>" data-name="<?php the_Title()?>" width="600"  
					poster="https://www.care4nurses.org/wp-content/uploads/Banner-PASS-expanded.jpg">
					<source src="<?php echo $videoUrl ?>" type="video/mp4">
					Your browser does not support HTML5 video.
				</video>
				<?php 
					if( $ok ) {
				?>
				<div style="text-align:center">
					<progress id="progress" value="0"></progress>
				</div>
				<div style="text-align:center">
					<button id="play">Play/Pause</button>
					<button id="restart">Restart</button>
					<button id="makebig">Big</button>
					<button id="makesmall">Small</button>
					<button id="makenormal">Normal</button>
				</div>

				<div style="text-align:center">
					<div>Volume<input id="volume" type="range" width="25"></div>
				</div>

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