<?php if( have_rows('hebergement') ): ?>
  <?php while( have_rows('hebergement') ): the_row(); 
      $elements = get_sub_field('elements');
      // $ratings = array_map(function($element) {
      //   if ($element) return get_field('rating', $element->ID);
      // }, $elements);

      // if (!empty($ratings)) {
      //   $average_rating = number_format(array_sum($ratings) / count($ratings), 1); // get average rating with 1 decimal
      //   $average_rating = floatval($average_rating); // remove useless decimal
      //   $average_rating_int = floor( $average_rating ); // number for stars
      // }
    ?>
    <?php if ($elements): ?>

      <div class="home-section home-section--hebergement grid grid-cols-2 gap-8">
        <div class="wysiwyg-content order-1">
            <h2><?php the_sub_field('titre'); ?></h2>
            <?php the_sub_field('contenu'); ?>
        </div>

        <div class="reset-container order-2">
          <div class="block-hebergement">
            <div class="container">

              <div class="py-10">

                <div class="">
                  <?php foreach( $elements as $post ): 
                    // Setup this post for WP functions (variable must be named $post).
                    setup_postdata($post); 
                    // $rating = (int) get_field('rating');
                    ?>
                    <div class="">
                      <div class="font-medium mb-6">
                        <h3><?php the_title(); ?></h3>
                        <?php echo do_shortcode('[mphb_room title="true" featured_image="true"]'); ?>
                        <div class=""><?php the_content(); ?></div>
                        <?php //mphb_tmpl_the_room_type_default_price(); ?>
                      </div>
                      <div class="flex justify-start items-center pt-8">
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
                <?php wp_reset_postdata(); ?>
              </div>

            </div>
          </div>
        </div>
      </div>

    <?php endif; ?>
  <?php endwhile; ?>
<?php endif; ?>