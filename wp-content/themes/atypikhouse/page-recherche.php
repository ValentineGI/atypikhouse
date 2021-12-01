<?php
/**
 * Template Name: Page de recherche
 */

get_header();
?>
<div class="reset-container">
  	<section class="text-gray-600 body-font">
		<div class="container">
			<div class="grid lg:grid-cols-3 gap-16">
				<div class="col-span-1">
					<main id="primary" class="site-main">

						<?php
						while (have_posts()) :
							the_post();

							get_template_part('template-parts/content', 'page');

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