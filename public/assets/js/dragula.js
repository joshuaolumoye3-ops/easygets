(function () {
  'use strict';

  var iconToChange;

  // Initialize Dragula
  dragula([document.getElementById("dragula-left"), document.getElementById("dragula-right")]);
  dragula([document.getElementById("profile-list-left"), document.getElementById("profile-list-right")]);
  dragula([document.getElementById("dragula-event-left"), document.getElementById("dragula-event-right")])
    .on('drop', function (el) {
      console.log(el);
      iconToChange = el.querySelector('.mdi'); // Find the element with the "mdi" class

      if (iconToChange) {
        if (iconToChange.classList.contains('mdi-check')) {
          iconToChange.classList.remove('mdi-check', 'text-modern-primary');
          iconToChange.classList.add('mdi-check-all', 'text-modern-success');
        } else if (iconToChange.classList.contains('mdi-check-all')) {
          iconToChange.classList.remove('mdi-check-all', 'text-modern-success');
          iconToChange.classList.add('mdi-check', 'text-modern-primary');
        }
      }
    });
})();
