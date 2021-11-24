<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="profile" href="https://gmpg.org/xfn/11">
	<link rel="preconnect" href="https://fonts.gstatic.com">
	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<div id="page" class="site">
	<div class="container">
		<a class="skip-link screen-reader-text" href="#primary"><?php esc_html_e( 'Skip to content', 'atypikhouse' ); ?></a>
		<div class="reset-container">
			<header id="masthead" class="site-header pt-4 sm:pt-8 pb-4">
				<div class="container">
					<nav class="flex items-center p-3 flex-wrap">						
						<div class="site-branding">
							<?php the_custom_logo(); ?>
						</div><!-- .site-branding -->
						<button class="text-gray inline-flex p-3 hover:bg-gray-900 rounded lg:hidden ml-auto hover:text-white outline-none nav-toggle" data-target="#navigation">
							<i class="material-icons">menu</i>
						</button>
						<div class="hidden top-navbar w-full lg:inline-flex lg:flex-grow lg:w-auto lg:pl-8" id="navigation" >
							<?php
								wp_nav_menu(
									array(
										'theme_location'  => 'menu-header',
										'menu_id'         => 'primary-menu',
										'menu_class'	  => 'lg:inline-flex lg:flex-row lg:ml-auto lg:w-auto w-full lg:items-center items-start flex flex-col lg:h-auto',
									)
								);
							?>	
						</div>
					</nav>
				</div>
			</header><!-- #masthead -->
		</div>
        