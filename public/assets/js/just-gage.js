// Define gauges globally to maintain access
let g1, g2, g3, g4, g5, g6, g7, g8, g9, g10;

// Helper function for random integers
function getRandomInt(min, max) {
  return Math.floor(Math.random() * (max - min + 1)) + min;
}

// Wait for DOM to be fully loaded
document.addEventListener("DOMContentLoaded", function () {
  // Initialize all gauges
  g1 = new JustGage({
    id: "g1",
    value: getRandomInt(0, 100),
    min: 0,
    max: 100,
    valueFontColor: '#898f96',
    title: "Big Fella",
    label: "pounds",
    textRenderer: function (val) {
      return val;
    }
  });

  g2 = new JustGage({
    id: "g2",
    value: 72,
    min: 0,
    max: 100,
    donut: true,
    gaugeWidth: 0.6,
    counter: true,
    hideInnerShadow: true,
    valueFontColor: '#898f96',
  });

  g3 = new JustGage({
    id: 'g3',
    value: 65,
    min: 0,
    max: 100,
    symbol: '%',
    pointer: true,
    gaugeWidth: 0.6,
    valueFontColor: '#898f96',
    customSectors: {
      ranges: [{
        color: '#ff0000',
        lo: 50,
        hi: 100
      }, {
        color: '#00ff00',
        lo: 0,
        hi: 50
      }]
    },
    counter: true
  });

  g4 = new JustGage({
    id: 'g4',
    value: 45,
    min: 0,
    max: 100,
    symbol: '%',
    pointer: true,
    valueFontColor: '#898f96',
    pointerOptions: {
      toplength: -15,
      bottomlength: 10,
      bottomwidth: 12,
      color: '#8e8e93',
      stroke: '#ffffff',
      stroke_width: 3,
      stroke_linecap: 'round'
    },
    gaugeWidth: 0.6,
    counter: true
  });

  g5 = new JustGage({
    id: 'g5',
    value: 40,
    min: 0,
    max: 100,
    symbol: '%',
    donut: true,
    pointer: true,
    valueFontColor: '#898f96',
    gaugeWidth: 0.4,
    pointerOptions: {
      toplength: 10,
      bottomlength: 10,
      bottomwidth: 8,
      color: '#000'
    },
    customSectors: {
      ranges: [{
        color: "#ff0000",
        lo: 50,
        hi: 100
      }, {
        color: "#00ff00",
        lo: 0,
        hi: 50
      }]
    },
    counter: true
  });

  g6 = new JustGage({
    id: 'g6',
    value: 70,
    min: 0,
    max: 100,
    symbol: '%',
    pointer: true,
    valueFontColor: '#898f96',
    pointerOptions: {
      toplength: 8,
      bottomlength: -20,
      bottomwidth: 6,
      color: '#8e8e93'
    },
    gaugeWidth: 0.1,
    counter: true
  });

  g7 = new JustGage({
    id: 'g7',
    value: 65,
    min: 0,
    max: 100,
    reverse: true,
    valueFontColor: '#898f96',
    gaugeWidth: 0.6,
    customSectors: {
      ranges: [{
        color: '#ff0000',
        lo: 50,
        hi: 100
      }, {
        color: '#00ff00',
        lo: 0,
        hi: 50
      }]
    },
    counter: true
  });

  g8 = new JustGage({
    id: 'g8',
    value: 45,
    min: 0,
    max: 500,
    reverse: true,
    valueFontColor: '#898f96',
    gaugeWidth: 0.6,
    counter: true
  });

  g9 = new JustGage({
    id: 'g9',
    value: 25000,
    min: 0,
    max: 100000,
    humanFriendly: true,
    reverse: true,
    valueFontColor: '#898f96',
    gaugeWidth: 1.3,
    customSectors: {
      ranges: [{
        color: "#ff0000",
        lo: 50000,
        hi: 100000
      }, {
        color: "#00ff00",
        lo: 0,
        hi: 50000
      }]
    },
    counter: true
  });

  g10 = new JustGage({
    id: 'g10',
    value: 90,
    min: 0,
    max: 100,
    symbol: '%',
    reverse: true,
    valueFontColor: '#898f96',
    gaugeWidth: 0.1,
    counter: true
  });

  // Set up the refresh interval for g1
  setInterval(function () {
    g1.refresh(getRandomInt(50, 100));
  }, 2500);

  // Add event listeners for refresh buttons
  const g2RefreshBtn = document.getElementById('g2_refresh');
  if (g2RefreshBtn) {
    g2RefreshBtn.addEventListener('click', function () {
      g2.refresh(getRandomInt(0, 100));
    });
  }

  const gaugeRefreshBtn = document.getElementById('gauge_refresh');
  if (gaugeRefreshBtn) {
    gaugeRefreshBtn.addEventListener('click', function () {
      g3.refresh(getRandomInt(0, 100));
      g4.refresh(getRandomInt(0, 100));
      g5.refresh(getRandomInt(0, 100));
      g6.refresh(getRandomInt(0, 100));
      g7.refresh(getRandomInt(0, 100));
      g8.refresh(getRandomInt(0, 100));
      g9.refresh(getRandomInt(0, 100));
      g10.refresh(getRandomInt(0, 100));
    });
  }
});