<?php get_header(); ?>

    <main id="primary" class="site-main">

		<?php
		while ( have_posts() ) :
			the_post();

			get_template_part('template-parts/home/home-slider');	
			get_template_part('template-parts/home/home-recherche');
			get_template_part('template-parts/home/home-presentation');
			get_template_part('template-parts/home/home-hebergement');
			get_template_part('template-parts/home/home-actualite');
            
		endwhile; // End of the loop.
		?>

	</main><!-- #main -->

<?php get_footer(); ?>