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
    <!-- start container -->
    <div class="container">

        <!-- start preloader -->
        <div class="preloader">
            <div class="spinner">
                <div class="bounce1"></div>
                <div class="bounce2"></div>
                <div class="bounce3"></div>
            </div>
        </div>
        <!-- end preloader -->
        <!-- Start header -->
        <header id="header" class="site-header header-style-2">
            <nav class="navigation navbar navbar-default">
                <div class="container-fluid">
                    <div class="navbar-header sm:flex flex-row">
                        <?php the_custom_logo();
                        if ( is_front_page() && is_home() ) :
                            ?>
                            <h1 class="site-title"><a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php bloginfo( 'name' ); ?></a></h1>
                            <?php
                        else :
                            ?>
                            <p class="site-title"><a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php bloginfo( 'name' ); ?></a></p>
                            <?php
                        endif;?>
                    </div>
                    <nav id="site-navigation" class="main-navigation pt-4 sm:mt-4">		
						<div class="mobile:container">
							<?php
							wp_nav_menu(
								array(
                                    'theme_location' => 'menu-header',
                                    'menu_id'        => 'primary-menu',
                                    'menu_class'     => 'sm:flex sm:space-x-8',
                                    'container'      => 'ul',
								)
							);
							?>
						</div>
					</nav><!-- #site-navigation -->
                </div><!-- end of container -->
            </nav>
        </header>
        <!-- end of header -->
        