<?php
/**
 * The template for displaying 404 pages (not found)
 *
 * @link https://codex.wordpress.org/Creating_an_Error_404_Page
 *
 * @package Fortesens
 */

get_header();
?>

	<main id="primary" class="site-main">

		<section class="error-404 not-found">
			<header class="page-header">
				<h1 class="page-title font-bold text-4xl uppercase">Erreur 404, cette page n'existe pas.</h1>
			</header><!-- .page-header -->

			<div class="page-content">
				<p>Rendez-vous sur la page d'accueil pour poursuivre votre visite.</p>
				
				<div class="mt-8">
					<a href="<?php echo home_url(); ?>" class="btn btn btn-primary">Revenir à l'accueil</a>
				</div>
			</div><!-- .page-content -->
		</section><!-- .error-404 -->

	</main><!-- #main -->

<?php
get_footer();
