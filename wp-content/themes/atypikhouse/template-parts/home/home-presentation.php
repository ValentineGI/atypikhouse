<?php if( have_rows('a_propos') ): ?>
	<?php while( have_rows('a_propos') ): the_row(); 
		$image1 = get_sub_field('image_1plan');
		$image2 = get_sub_field('image_2plan');
		?>

		<div class="reset-container">
			<div class="home-section home-section--presentation mb-8">
				<div class="container">

					<div class="grid lg:grid-cols-2 gap-8">
						<div class="lg:order-1">
							<img class="" src="<?php echo $image1; ?>" alt="<?php echo $image1; ?>" />

							<?php echo wp_get_attachment_image( $image2, 'full', false, array( 
								'class' => 'w-full align-bottom'
							) ); ?>
						</div>
						<div class="lg:order-2 grid-row">
							<div class="wysiwyg-content">
								<h2><?php the_sub_field('titre'); ?></h2>
								<?php the_sub_field('contenu'); ?>
								<?php if (get_sub_field('lien')) : ?>
								<?php $link = get_sub_field('lien'); ?>
								<a href="<?php echo $link['url']; ?>" target="_blank" class="bottom-0 left-0 h-16 w-16 btn btn-secondary-reverse btn-lg"><?php echo $link['title']; ?></a>
								<?php endif; ?>
							</div>
						</div>
						
					</div>
				</div>
			</div>
		</div>

	<?php endwhile; ?>
<?php endif; ?>