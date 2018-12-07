import '../css/app.scss';

// Don't load bootstrap js for now, let's see if we need it later
//require('bootstrap');

import $ from 'jquery';

$(document).ready(function() {
  // hamburger menu toggle foo
  $("#menu-toggle").click(function(e) {
      e.preventDefault();
      $("#main-wrapper").toggleClass("toggled");
  });
  $(window).resize(function() {
    if($(window).width()<=768){
      $("#main-wrapper").removeClass("toggled");
    }else{
      $("#main-wrapper").addClass("toggled");
    }
  });
});