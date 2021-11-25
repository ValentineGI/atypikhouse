<?php if( have_rows('hebergement') ): ?>
	<?php while( have_rows('hebergement') ): the_row(); 
			$elements = get_sub_field('elements');
		?>
		<?php if ($elements): ?>
			<div class="reset-container">
				<div class="home-section home-section--hebergement mb-8 pt-24">
					<div class="container">
						<div class="grid lg:grid-cols-12 gap-4">	
							<div class="col-span-4 grid-row">
								<div class="wysiwyg-content">
									<h2><?php the_sub_field('titre'); ?></h2>
									<?php the_sub_field('contenu'); ?>
								</div>
							</div>
							<div class="grid grid-cols-12 col-span-8 md:items-center w-full max-w-screen-sm md:max-w-screen-md mx-auto px-4">
								<?php foreach( $elements as $post ): 
									// Setup this post for WP functions (variable must be named $post).
									setup_postdata($post); 
									// $rating = (int) get_field('rating');
									?>
									<a href="<?php the_permalink(); ?>" class="col-span-6 max-w-lg mx-auto bg-white rounded-lg shadow-lg mb-9 first:mt-40">
										<?php
										/* grab the url for the full size featured image */
										$featured_img_url = get_the_post_thumbnail_url($post->ID, 'full'); 
										?>
										<div class="grid items-end w-80 h-96 bg-cover p-3" style="background-image: url('<?php echo "$featured_img_url" ?>');">
										<!-- Begin Transparent Alert -->
											<div class="grid grid-cols-6 items-center bg-white bg-opacity-95 rounded-sm text-center p-4 mt-2">
												<span class="text-black text-lg font-semibold col-span-4">
													<?php the_title(); ?>
												</span>
												<div class="col-span-2">
													<?php echo do_shortcode('[mphb_rooms title="0" featured_image="0" gallery="0" excerpt="0" details="0" view_button="0" book_button="0"]'); ?>
												</div>
											</div>
											<!-- End Transparent Alert -->
										</div>
								</a>
								<?php endforeach; ?>
								<?php wp_reset_postdata(); ?>
							</div>
						</div>
					</div>
				</div>
		<?php endif; ?>
	<?php endwhile; ?>
<?php endif; ?>