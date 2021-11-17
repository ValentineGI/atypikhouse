<?php if( have_rows('presentation') ): ?>
  <?php while( have_rows('presentation') ): the_row(); 
    $image = get_sub_field('image');
    $video = get_sub_field('video');
    ?>

    <div class="reset-container">
      <div class="home-section home-section--presentation mb-8">
        <div class="container">

          <div class="mt-10 grid col-start-2 lg:grid-cols-2 gap-8">
            <div class="lg:order-2 grid-row">
              <div class="wysiwyg-content">
                <h2><?php the_sub_field('titre'); ?></h2>
                <?php the_sub_field('contenu'); ?>
              </div>
            </div>
            <div class="lg:order-1 lg:reset-container-left">

              <?php if ($video) : ?>
                <div class="custom-video">
              <?php endif; ?>

              <?php echo wp_get_attachment_image( $image, 'full', false, array(
                'class' => 'w-full align-bottom'
              ) ); ?>

              <?php if ($video) : ?>
                  <div class="hide" style="display: none;"><?php echo $video; ?></div>
                </div>
              <?php endif; ?>
            </div>
          </div>

        </div>
      </div>
    </div>

  <?php endwhile; ?>
<?php endif; ?>