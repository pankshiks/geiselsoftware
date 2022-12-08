// JavaScript Document
jQuery(document).ready(function ($) {
  if($(".greybg a.inlinelink").length > 0){
    $(".greybg a.inlinelink").click(function() {
        var getId = $(this).attr('href');
        $([document.documentElement, document.body]).animate({
            scrollTop: $(getId).offset().top - 75
        }, "fast");
    });
  }

  //-- Add class to body after the page is loaded 
  setTimeout(function(){
    $('body').addClass('latestart');
  },2500);
  
  
  //-- Header sizing on scroll
  $(window).on('scroll', function() {
    changeHeader();   
  });
  
  //-- Change header on scroll
  changeHeader();
  
  function changeHeader()
  {
    if ($(window).scrollTop() > 20)
    {
      $('body').addClass('scrolled');
    }
    else
    {
      $('body').removeClass('scrolled');
    }
  };
  
  
  //-- Show / hide mobilemenu
  $('.btn-mobilemenu').on('click', function(e){
    e.preventDefault();
    
    $('body').toggleClass('showmenu');
    
  });
  
  $('.btn-closemenu, .overlay').on('click', function(e){
    e.preventDefault();
    
    $('body').removeClass('showmenu');
    
  });
  
  
  
  //-- Element animations
  $(window).on('load', function() { 
    if($('[data-sr-id]').length > 0){
      $('[data-sr-id]').removeAttr('data-sr-id').removeAttr('style');
    }
  var animlength = 900;
  var transit = 'cubic-bezier(0.190, 1.000, 0.220, 1.000)';
  
  var elemAnim = {
    duration: animlength,
    delay    : 100,
    distance : '0px',
    easing   : transit,
    scale    : 1
  };
  
  var elemAnimd1 = {
    duration: animlength,
    delay    : 200,
    distance : '0px',
    easing   : transit,
    scale    : 1
  };
  
  var elemAnimd2 = {
    duration: animlength,
    delay    : 300,
    distance : '0px',
    easing   : transit,
    scale    : 1
  };
  var elemAnimd3 = {
    duration: animlength,
    delay    : 400,
    distance : '0px',
    easing   : transit,
    scale    : 1
  };  

  var elemAnimd4 = {
    duration: animlength,
    delay    : 100,
    distance : '0px',
    easing   : transit,
    scale    : 1,
    // viewFactor : 0.001
  };

  window.sr = ScrollReveal();
  sr.reveal('.animated', elemAnim);
  sr.reveal('.animated-d1', elemAnimd1);
  sr.reveal('.animated-d2', elemAnimd2);
  sr.reveal('.animated-d3', elemAnimd3);
  sr.reveal('.animated-d4', elemAnimd4);
  });
  
  
  //-- Lighbox
  /*
  if($('.lighbox').length != 0)
  {
    $('.lighbox').tosrus({
      
    });
  } */
  
  //-- Project Lightbox Galery
  /*
     // We're using juicebox not lightGallery, so turned this off. -geis
  if($('.project-gallery').length != 0)
  {
    $('.project-gallery').lightGallery({
      selector: 'a',
      thumbWidth: 130,
      thumbContHeight: 110,	
      toogleThumb: false,
      download: false
    }); 
  }
  */
  
  //-- Testimonials Slider
  if($('.testimonials').length != 0)
  {
    $('.testimonials').owlCarousel({
      singleItem: true,
      stopOnHover: true,
      pagination: true,
      autoPlay: true,
    });
  }
   
  //-- Blog Masonry
  if ($('#blogstream').length != 0)
  {
    var msnry;
    var container = document.querySelector('#blogstream');
    imagesLoaded( container, function() {
      msnry = new Masonry( container, {
        columnWidth: '.gridsizer',
        itemSelector: '.bpost',
        gutter: 15,
        isFitWidth: true
      });
    });
  }



  //-- Smoothscroll
  $('.smooth').on('click', function (e) {
    e.preventDefault();
    var link = $(this).attr('href');

    smoothScroll($(link));
  });


  //-- Smooth scrolling function
  var smoothScroll = function (element)
  {
    var target = element;
    var headerheight;
    
    if ($('.topbar').css('position') == 'fixed') {
      headerheight = $('.topbar').outerHeight();
    }
    else
    {
      headerheight = $('.navbar-default').outerHeight();
    }

    if (target.length) {

      $('html,body').animate({
        scrollTop: target.offset().top - headerheight
      }, 1000);

      return false;
    }
  };


// $('.path-node-183 .contentblock').addClass('col-sm-12');
// $('.path-node-186 .contentblock').addClass('col-sm-12');
// $('.path-node-180 .contentblock').addClass('col-sm-12');
// $('.path-node-187 .contentblock').addClass('col-sm-12');

});

if (top.location.href === 'https://geisel.software/government#featured-content')
{
jQuery('html, body').animate({
scrollTop: jQuery("#featured-content").offset().top - 65
}, 2000);
}