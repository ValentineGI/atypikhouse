<?php
/*
Template Name: Contact
*/
?>
<?php

get_header();
?>

<div class="reset-container">
  <section class="contact">
      <div class="container">
	  	<?php
		/* grab the url for the full size featured image */
		$featured_img_url = get_the_post_thumbnail_url($post->ID, 'full'); 
		?>
		<div class="grid items-center w-full h-96 bg-cover bg-center p-3" style="background-image: url('<?php echo "$featured_img_url" ?>');">
			<div class="rounded-sm text-center p-4 mt-2">
				<div class="titre text-4xl font-bold ">
					<?php the_title(); ?>
				</div>
			</div>
		</div> 
		<div class="relative shadow-md p-10 my-10">    
			<h3 class="text-center text-3xl py-7 ">Envoyer un message</h3>
			<?php echo do_shortcode('[contact-form-7 id="255" title="Formulaire de contact"]'); ?>
		</div> 
		<div class="relative shadow-md p-10 my-10">    
			<h3 class="text-center text-3xl py-7">Map</h3>
		</div> 

		

      </div>     
  </section>
</div>

<?php
get_footer();
