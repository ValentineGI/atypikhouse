<?php
/**
 * Template Name: Page de recherche
 */

get_header();
?>
<div class="reset-container">
  	<section class="text-gray-600 body-font">
		<div class="container">
			<?php
			/* grab the url for the full size featured image */
			$featured_img_url = get_the_post_thumbnail_url($post->ID, 'full'); 
			?>
			<div class="grid items-center w-full h-96 bg-cover bg-center p-3" style="background-image: url('<?php echo "$featured_img_url" ?>');">
				<div class="rounded-sm text-center p-4 mt-2">
					<div class="text-white text-4xl font-bold">
						<?php the_title(); ?>
					</div>
				</div>
			</div> 
			<div class="recherche">
				<div class="container">
					<main id="primary" class="site-main">

						<?php
						while (have_posts()) :
							the_post();

							get_template_part('template-parts/content', 'resultatrecherche');

							// If comments are open or we have at least one comment, load up the comment template.
							if (comments_open() || get_comments_number()) :
								comments_template();
							endif;

						endwhile; // End of the loop.
						?>

					</main><!-- #main -->

				</div>
			</div>
		</div>
	</section>
</div>
<?php
get_footer();