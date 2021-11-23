<?php if( have_rows('hebergement') ): ?>
	<?php while( have_rows('hebergement') ): the_row(); 
			$elements = get_sub_field('elements');
			// $ratings = array_map(function($element) {
			//   if ($element) return get_field('rating', $element->ID);
			// }, $elements);

			// if (!empty($ratings)) {
			//   $average_rating = number_format(array_sum($ratings) / count($ratings), 1); // get average rating with 1 decimal
			//   $average_rating = floatval($average_rating); // remove useless decimal
			//   $average_rating_int = floor( $average_rating ); // number for stars
			// }
		?>
		<?php if ($elements): ?>

			<div class="home-section home-section--hebergement grid grid-cols-2 gap-8">
				<div class="wysiwyg-content order-1">
						<h2><?php the_sub_field('titre'); ?></h2>
						<?php the_sub_field('contenu'); ?>
				</div>

				<div class="reset-container order-2">
					<div class="block-hebergement">
						<div class="container">

							<div class="py-10">

								<div class="">
									<?php foreach( $elements as $post ): 
										// Setup this post for WP functions (variable must be named $post).
										setup_postdata($post); 
										// $rating = (int) get_field('rating');
										?>
										<div class="">
											<div class="font-medium mb-6">
												<?php the_post_thumbnail(); ?>
												<h3><a href="<?php the_permalink() ?>" rel="bookmark"><?php the_title(); ?></a></h3>   
												<?php the_content(); ?>
												<div class="w-28">
													<?php echo do_shortcode('[mphb_room price="true"]'); ?>
												</div>
											</div>
										</div>
									<?php endforeach; ?>
								</div>
								<?php wp_reset_postdata(); ?>
							</div>

						</div>
					</div>
				</div>
			</div>

		<?php endif; ?>
	<?php endwhile; ?>
<?php endif; ?>