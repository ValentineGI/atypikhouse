( function( $ ) {
	var swiper = new Swiper(".mySwiper", {
    navigation: {
      nextEl: ".swiper-button-next",
      prevEl: ".swiper-button-prev",
    },
  });

  $(".nav-toggler").each(function(_, navToggler) {
    var target = $(navToggler).data("target");
    $(navToggler).on("click", function() {
      $(target).animate({
        height: "toggle"
      });
    });
  });

  $('#primary-menu > li').addClass('lg:inline-flex lg:w-auto w-full px-3 py-2 rounded items-center justify-center hover:bg-primary hover:text-white p-3');

}( jQuery ) );
