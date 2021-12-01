<?php
/**
 * The template for displaying archive pages
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package Atypik House
 */

get_header();
?>
<div class="reset-container">
	<section class="text-gray-600 body-font mb-8">
		<div class="container">
			<main id="primary" class="site-main">

				<?php if ( have_posts() ) : ?>

					<header class="page-header">
						<div class="grid items-center w-full h-96 bg-cover bg-center p-3" style="background-image: url('<?php echo get_template_directory_uri(); ?>/img/header_img.jpg');">
							<div class="rounded-sm text-center p-4 mt-2">
								<div class="text-white text-4xl font-bold">
									<?php
									the_archive_title( '<h1 class="page-title">', '</h1>' );
									the_archive_description( '<div class="archive-description">', '</div>' );
									?>
								</div>
							</div>
						</div> 
					</header><!-- .page-header -->

					<?php
					/* Start the Loop */
					while ( have_posts() ) :
						the_post();

						/*
						* Include the Post-Type-specific template for the content.
						* If you want to override this in a child theme, then include a file
						* called content-___.php (where ___ is the Post Type name) and that will be used instead.
						*/
						get_template_part( 'template-parts/content', get_post_type() );

					endwhile;

					atypikhouse_posts_pagination();

				else :

					get_template_part( 'template-parts/content', 'none' );

				endif;
				?>

			</main><!-- #main -->
		</div>
	</section>
</div>
<?php
get_footer();
