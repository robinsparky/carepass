<?php
get_header();  ?>
<div class="page-title-section">		
	<div class="overlay">
		<div class="container">
			<div class="row">
				<div class="col-md-6">
					<div class="page-title"><h1>
        <?php _e( "Webinars", 'care-mci' ); ?>
        <?php if( get_post_meta( get_the_ID(), 'post_description', true ) != '' ) { ?>
        <p><?php echo get_post_meta( get_the_ID(), 'post_description', true ) ; ?></p>
        <?php } ?>
        <div class="qua-separator" id=""></div>
		</h1></div>
				</div>
				<div class="col-md-6">
					<ul class="page-breadcrumb">
						<?php if ( function_exists('qt_custom_breadcrumbs') ) qt_custom_breadcrumbs();?>
					</ul>
					
				</div>
			</div>
		</div>	
	</div>
</div>
<!-- /Page Title Section ---->
<div class="page-builder">
	<div class="container">
		<div class="row">
		
			<!-- Blog Area -->
			<div class="<?php appointment_post_layout_class(); ?>" >
			<?php
                while ( have_posts() ) : 
                    the_post();
                    global $more;
                    $more = 0; 
                ?>
                <hr style="clear:left;">
				<?php 
					$videoUrl = get_post_meta( get_the_ID(), Webinar::VIDEO_META_KEY, true );
					$hasVideo = 'No';
					if( @$videoUrl ) $hasVideo = 'Yes';
					if( $hasVideo === 'Yes') {
				?>
				<h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
					<?php }
					else { ?>
				<h3><?php the_title(); }?></h3>
                <small>		
					<div>		
					<?php care_mci_get_term_links( $post->ID, 'carewebinartax' ); 
					?>
					</div>
					<details class="webinar-meta">
						<summary>Data</summary>
						<p>Video Available: <?php echo $hasVideo ?></p>
					</details>
                </small>
                <span> <?php echo the_content() ?> </span>

				<?php endwhile;
				// Previous/next page navigation.
				the_posts_pagination( array(
                    'prev_text'          => '<i class="fa fa-angle-double-left"></i>',
                    'next_text'          => '<i class="fa fa-angle-double-right"></i>',
				) );
				?>
			</div>
            <?php
                // Reset Post Data 
                wp_reset_postdata();
            ?>
			<!--Sidebar Area-->
			<div class="col-md-4">
				<?php get_sidebar(); ?>
			</div>
			<!--Sidebar Area-->
		</div>
	</div>
</div>
<?php get_footer(); ?>