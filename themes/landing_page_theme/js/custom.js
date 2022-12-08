jQuery(document).on('ready', function(){

  //-- Smooth scrolling links
  jQuery('a.smooth')
  .click(function(event) {
    var target = jQuery(this).attr('href'),
    headerHeight = jQuery('.site-header').outerHeight();
    console.log(jQuery('.site-header').outerHeight());
    
    jQuery('html, body').animate({
      scrollTop: jQuery(target).offset().top - (headerHeight + 25)
    }, 1000, function() {
      var jQuerytarget = jQuery(target);
      jQuerytarget.focus();
      if (jQuerytarget.is(":focus")) {
        return false;
      } else {
        jQuerytarget.attr('tabindex','-1');
        jQuerytarget.focus();
      };
    });
  });


});

if(jQuery('.page_node_75').length != 0){
  var custom = jQuery('.call-to-action-sub-content:contains(custom)').html();
  custom = custom.replace(/custom/gi, '<text style="color:#f159b2">custom</text>');
  jQuery('.call-to-action-sub-content:contains(custom)').html(custom);
}