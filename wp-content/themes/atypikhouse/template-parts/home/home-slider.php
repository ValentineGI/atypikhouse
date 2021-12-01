<?php if( have_rows('slider') ): ?>
<div class="reset-container relative z-10">
	<div class="container">
		<div class="swiper mySwiper img-as-bg bg-blue-400">
			<div class="swiper-wrapper">
				<?php while( have_rows('slider') ): the_row(); 
						$image = get_sub_field('image');
						?>
						<div class="swiper-slide">  
							<div class="container h-full">
								<div class="slide-content sm:ml-8 flex flex-col h-full">
									<div>
										<h2 class="slide-content__title"><?php the_sub_field('texte'); ?></h2>
									</div>
									<div class="w-full h-32 flex items-end">
										<?php if (get_sub_field('lien')) : ?>
										<?php $link = get_sub_field('lien'); ?>
										<a href="<?php echo $link['url']; ?>" target="_blank" class="h-16 w-16 btn btn-secondary-reverse btn-lg"><?php echo $link['title']; ?></a>
										<?php endif; ?>
									</div>
								</div>
							</div>
							
							<img class="slide-bg" src="<?php echo $image; ?>" alt="<?php echo $image; ?>" />					

						</div>
				<?php endwhile; ?>
			</div>
			<div class="swiper-button-next"></div>
			<div class="swiper-button-prev"></div>
		</div>
		<!-- Swiper -->
	</div>
</div>
<?php endif; ?>