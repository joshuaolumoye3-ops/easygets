(function () {
  'use strict';

  // Helper function to create progress bars
  function createProgressBar(selector, colorFrom, colorTo, animateValue) {
    const element = document.querySelector(selector);
    if (element) {
      const bar = new ProgressBar.Circle(element, {
        color: 'gray',
        strokeWidth: 4,
        trailWidth: 4,
        easing: 'easeInOut',
        duration: 1400,
        text: {
          autoStyleContainer: false
        },
        from: {
          color: colorFrom,
          width: 4
        },
        to: {
          color: colorTo,
          width: 4
        },
        step: function (state, circle) {
          circle.path.setAttribute('stroke', state.color);
          circle.path.setAttribute('stroke-width', state.width);

          const value = Math.round(circle.value() * 100);
          if (value === 0) {
            circle.setText('');
          } else {
            circle.setText(value);
          }
        }
      });

      bar.text.style.fontSize = '1rem';
      bar.animate(animateValue); // Number from 0.0 to 1.0
    }
  }

  // Initialize progress bars
  createProgressBar('#circleProgress1', '#0090e7', '#0090e7', 0.34);
  createProgressBar('#circleProgress2', '#219499', '#219499', 0.54);
  createProgressBar('#circleProgress3', '#00d25b', '#00d25b', 0.45);
  createProgressBar('#circleProgress4', '#8f5fe8', '#8f5fe8', 0.27);
  createProgressBar('#circleProgress5', '#ffab00', '#ffab00', 0.67);
  createProgressBar('#circleProgress6', '#fc424a', '#fc424a', 0.95);
})();
