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
		<div class="reset-container">
			<header id="masthead" class="site-header pt-4 sm:pt-8 pb-4">
				<div class="container">
					<nav class="flex items-center p-3 flex-wrap">						
						<div class="site-branding">
							<?php the_custom_logo(); ?>
						</div><!-- .site-branding -->
						<button class="text-gray inline-flex p-3 hover:bg-primary hover:text-white rounded lg:hidden ml-auto hover:text-white outline-none nav-toggler" data-target="#navigation">
							<i class="material-icons"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-menu"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg></i>
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
						<?php get_template_part('template-parts/header-links'); ?>
					</nav>
					
				</div>
			</header><!-- #masthead -->
		</div>
	</div>
        