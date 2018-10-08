<?php
get_header();  ?>
<div class="page-title-section">		
	<div class="overlay">
		<div class="container">
			<div class="row">
				<div class="col-md-6">
					<div class="page-title"><h1>
        <?php _e( "Courses", 'CARE_TEXTDOMAIN' ); ?>
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
				<h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                <small>				
					<?php care_mci_get_term_links( $post->ID, 'coursecategory' ); 
					
					$price = get_post_meta( get_the_ID(), Course::PRICE_META_KEY, true );
					$duration = get_post_meta( get_the_ID(), Course::DURATION_META_KEY, true );
					$needsApproval = get_post_meta( get_the_ID(), Course::NEEDS_APPROVAL_META_KEY, true );
					$instructions = $needsApproval === 'yes' ? 'Note: Requires case manager approval' : '';
					?>
					<div class="coursemeta" style="float:right">
					<span>Price: $<?php echo $price ?></span>
					&nbsp;<span>Duration: <?php echo $duration ?> hours</span>
					&nbsp;<span><?php echo $instructions ?></span>
					</div>
                </small>
                <?php the_excerpt() ?> 

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
			<!--/Sidebar Area-->
		</div>
	
	</div>
</div>
<?php get_footer(); ?>