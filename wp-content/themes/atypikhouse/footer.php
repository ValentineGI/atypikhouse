<?php
/**
 * The template for displaying the footer
 *
 * Contains the closing of the #content div and all content after.
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package atypikhouse
 */

$informations = get_field('informations', 'options');
$newsletter_link = get_field('newsletter_link', 'options');
?>
	</div>
	<footer id="colophon" >
		<div class="site-footer p-16 bg-primary">
			<div class="container text-center lg:text-left">
				<div class="sm:flex flex-row justify-between items-center">
					<div class="footer-logo"><img src="<?php echo get_template_directory_uri(); ?>/img/logo-atypikhouse-white.svg" alt="Atypik House"></div>
					<div class="inline-block lg:block">
						<?php if ( have_rows('socials', 'options') ) : ?>
						<ul class="socials list-none m-0 flex space-x-4 max-w-xs mx-auto items-center">
							<?php while( have_rows('socials', 'options') ) : the_row(); 
								$socials = get_field('socials', 'options');
								$keys = array_keys($socials);
							?>

							<?php foreach ($keys as $key) { if (!get_sub_field($key)) continue; ?>
								<li class="hover:opacity-80 <?php echo $key; ?>">
									<a target="_blank" href="<?php the_sub_field($key); ?>"><?php echo $key; ?></a>
								</li>
							<?php } ?>
					
						<?php endwhile; ?>
						</ul>
						<?php endif; ?>
					</div>
				</div>
				<div class="lg:flex justify-between">
					<div class="site-info"><?php echo $informations; ?></div><!-- .site-info -->
		
					<div class="footer-links my-8 lg:m-0">
						<?php
							wp_nav_menu(
								array(
									'theme_location' => 'menu-footer',
									'container' => 'ul',
									'menu_class' => 'list-none mx-0 mb-6'
								)
							);
						?>			
					</div>

					<?php if ($newsletter_link) { ?>
					<div class="footer-newsletter inline-block lg:block">
						<p>Lettre d'information</p>
						<a href="<?php echo $newsletter_link; ?>" target="_blank" class="btn btn-secondary-reverse btn-lg">S'inscrire</a>
					</div>
					<?php } ?>
				</div>
			</div><!-- .container -->
		</div>
		<div class="site-footer bg-secondary p-16">
			<div class="container text-center lg:text-left">
				<div class="lg:flex justify-between">
					<div class="my-8 lg:m-0">
						<?php
							wp_nav_menu(
								array(
									'theme_location' => 'menu-copyright',
									'container' => 'ul',
									'menu_class' => 'list-none mx-0'
								)
							);
						?>			
					</div>
				</div>
			</div><!-- .container -->
		</div>
	</footer><!-- #colophon -->
</div><!-- #page -->

<?php wp_footer(); ?>

</body>
</html>
