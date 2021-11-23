<?php if( have_rows('a_propos') ): ?>
	<?php while( have_rows('a_propos') ): the_row(); 
		$image1 = get_sub_field('image_1plan');
		$image2 = get_sub_field('image_2plan');
		?>

		<div class="reset-container">
			<div class="home-section home-section--presentation mb-8">
			<div class="container">
				<!-- probleme ici avec les cols-12 -->
				<div class="grid lg:grid-cols-12 gap-4">
					<div class="grid grid-cols-12 col-span-7 md:items-center w-full max-w-screen-sm md:max-w-screen-md mx-auto px-4">
						<div class="col-span-12 md:col-span-auto md:col-start-1 md:col-end-9 md:row-start-1 md:row-end-1">
							<img class="" src="<?php echo $image2; ?>" alt="<?php echo $image2; ?>" />
						</div>
						<div class="col-span-12 md:col-span-auto md:col-start-5 md:col-end-13 md:row-start-1 md:row-end-1 -mt-8 md:mt-0 relative z-10 px-4 md:px-0">
							<img class="h-full" src="<?php echo $image1; ?>" alt="<?php echo $image1; ?>" />
						</div>		
					</div>
					<div class="col-span-5 lg:order-2 grid-row">
						<div class="wysiwyg-content">
							<h2><?php the_sub_field('titre'); ?></h2>
							<?php the_sub_field('contenu'); ?>
							<?php if (get_sub_field('lien')) : ?>
							<?php $link = get_sub_field('lien'); ?>
							<a href="<?php echo $link['url']; ?>" target="_blank" class="btn btn-secondary-reverse btn-lg mt-8"><?php echo $link['title']; ?></a>
							<?php endif; ?>
						</div>
					</div>
				</div>
			</div>
		</div>

	<?php endwhile; ?>
<?php endif; ?>