<?php get_header(); ?>

    <main id="primary" class="site-main text-lg">

		<?php
		while ( have_posts() ) :
			the_post();

			get_template_part('template-parts/home/home-slider');	
			get_template_part('template-parts/home/home-presentation');
			get_template_part('template-parts/home/home-hebergement');
            
		endwhile; // End of the loop.
		?>

	</main><!-- #main -->

<?php get_footer(); ?>