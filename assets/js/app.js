import '../css/app.scss';

// We need bootstrap collapse
import collapse from 'bootstrap/js/src/collapse';
import moment from 'moment';

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
  convertDates();
});

function convertDates() {
  Array.from(document.querySelectorAll('[data-processor="localdate"]')).forEach(function(element) {
    const value = element.dataset.value;
    const targetFormat = element.dataset.targetFormat;
    const convertedDate = new Date(value);
    const language = element.dataset.language || 'en';

    element.textContent = moment(convertedDate).locale(language).format(targetFormat);
  });
}