<?php if( have_rows('slider') ): ?>
<div class="reset-container">
	<!-- Swiper -->
	<div class="swiper mySwiper img-as-bg">
		<div class="swiper-wrapper">
			<?php while( have_rows('slider') ): the_row(); 
					$image = get_sub_field('image');
					?>
					<div class="swiper-slide">  
						<div class="container">
							<div class="slide-content sm:ml-8">
								<h2 class="slide-content__title"><?php the_sub_field('texte'); ?></h2>
								<?php if (get_sub_field('lien')) : ?>
								<?php $link = get_sub_field('lien'); ?>
								<a href="<?php echo $link['url']; ?>" target="_blank" class="absolute bottom-0 left-0 h-16 w-16 btn btn-secondary-reverse btn-lg"><?php echo $link['title']; ?></a>
								<?php endif; ?>
							</div>
						</div>
						
						<img class="slide-bg" src="<?php echo $image; ?>" alt="<?php echo $image; ?>" />
						
						

					</div>
			<?php endwhile; ?>
		</div>
		<div class="swiper-button-next"></div>
		<div class="swiper-button-prev"></div>
	</div>
</div>
<?php endif; ?>