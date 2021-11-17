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
		<a class="skip-link screen-reader-text" href="#primary"><?php esc_html_e( 'Skip to content', 'fortesens' ); ?></a>
		<div class="reset-container">
			<header id="masthead" class="site-header pt-4 sm:pt-8 pb-4">
				<div class="container">

					<div class="sm:flex flex-row justify-between items-center">
						<div class="site-branding">
							<?php the_custom_logo(); ?>
						</div><!-- .site-branding -->
                        <nav id="site-navigation" class="main-navigation sm:ml-14">
                            <div class="mobile:container">
                                <button class="menu-toggle btn btn-primary-dark btn-sm mobile:mt-2" aria-controls="primary-menu" aria-expanded="false"><?php esc_html_e( 'Menu', 'fortesens' ); ?></button>
                                <?php
                                wp_nav_menu(
                                    array(
                                        'theme_location' => 'menu-header',
                                        'menu_id'        => 'primary-menu',
                                        'menu_class'		 => 'sm:flex sm:space-x-8',
                                        'container' => 'ul',
                                    )
                                );
                                ?>
                            </div>
                        </nav><!-- #site-navigation -->
					</div>
				</div>
			</header><!-- #masthead -->
		</div>
        