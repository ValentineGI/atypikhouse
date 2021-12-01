<?php
/**
 * Template part for displaying page content in page.php
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package AtypikHouse
 */

?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
	<div class="reset-container">
		<section class="text-gray-600 body-font mb-8">
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
			</div>
		</section>
	</div>
	<div class="container">
		<div class="entry-content">
			<?php
			the_content();

			wp_link_pages(
				array(
					'before' => '<div class="page-links">' . esc_html__( 'Pages:', 'atypikhouse' ),
					'after'  => '</div>',
				)
			);
			?>
		</div><!-- .entry-content -->

		<?php if ( get_edit_post_link() ) : ?>
			<footer class="entry-footer">
				<?php
				edit_post_link(
					sprintf(
						wp_kses(
							/* translators: %s: Name of current post. Only visible to screen readers */
							__( 'Edit <span class="screen-reader-text">%s</span>', 'atypikhouse' ),
							array(
								'span' => array(
									'class' => array(),
								),
							)
						),
						wp_kses_post( get_the_title() )
					),
					'<span class="edit-link">',
					'</span>'
				);
				?>
			</footer><!-- .entry-footer -->
		<?php endif; ?>
	</div>
</article><!-- #post-<?php the_ID(); ?> -->
