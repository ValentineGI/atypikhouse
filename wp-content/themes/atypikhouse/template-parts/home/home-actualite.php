<?php
    $recentPosts = new WP_Query();
    $recentPosts->query('showposts=3');
?>
<div class="reset-container">
  <section class="text-gray-600 body-font home-section--actualites">
      <div class="container">
        <div class="container px-5 py-24 mx-auto max-w-7x1">
            <div class="flex flex-wrap w-full mb-4 p-4">
                <div class="w-full mb-6 lg:mb-0">
                    <h2 class="sm:text-4xl text-5xl title-font mb-2 text-black">Actualités</h1>
                </div>
            </div>    
        <div class="flex flex-wrap -m-4">             
        <?php while ($recentPosts->have_posts()) : $recentPosts->the_post(); ?> 
            <a href="<?php the_permalink(); ?>">
                <div class="xl:w-1/3 md:w-1/3 p-4">     
                    <div class="">
                        <?php the_post_thumbnail('thumbnail', array('class' => 'lg:h-60 xl:h-56 md:h-64 sm:h-72 xs:h-72 h-72 w-full object-cover object-center mb-6')); ?>
                        <p class="text-lg text-gray-400 pt-2 "><?php the_category();?></p>
                        <h4 class="text-xl font-medium pt-2"><?php the_date(); ?></p></h4>
                        <a href="<?php the_permalink(); ?>"><h3 class="text-3xl text-gray-900 font-medium title-font mb-4 pt-2 hover:text-primary"><?php the_title(); ?></h3></a>
                    </div>
                </div>
            </a>
            <?php endwhile; ?>
        </div> 
        </div>
      </div>     
  </section>
</div>