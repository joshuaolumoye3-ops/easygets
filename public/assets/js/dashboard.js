(function ($) {
  'use strict';
  $.fn.andSelf = function () {
    return this.addBack.apply(this, arguments);
  }
  $(function () {

    //check all boxes in order status 
    $("#check-all").click(function () {
      $(".form-check-input").prop('checked', $(this).prop('checked'));
    });

    if ($("#transaction-history").length) {
      const doughnutChartCanvas = document.getElementById('transaction-history');
      const textCenter = {
        id: 'textCenter',
        beforeDatasetsDraw(chart, args, pluginOptions) {
          const { ctx, data } = chart;

          ctx.save();
          ctx.font = '18px sans-serif';
          ctx.fillStyle = 'white';
          ctx.textAlign = 'center';
          ctx.textBaseline = 'middle';
          ctx.fillText("$1200", chart.getDatasetMeta(0).data[0].x, chart.getDatasetMeta(0).data[0].y - 20);
          ctx.font = 'normal 12px sans-serif';
          ctx.fillText('Total', chart.getDatasetMeta(0).data[0].x, chart.getDatasetMeta(0).data[0].y + 10);
          ctx.restore();
        }
      }
      new Chart(doughnutChartCanvas, {
        type: 'doughnut',
        data: {
          labels: ["Paypal", "Stripe", "Cash"],
          datasets: [{
            data: [55, 25, 20],
            backgroundColor: [
              "#111111", "#00d25b", "#ffab00",
            ],
            borderColor: "#191c24"
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: true,
          resizeDebuounce: 100,
          cutout: '72%',
          aspectRatio: 2.5,
          elements: {
            arc: {
              borderWidth: 4
            }
          },
          plugins: {
            legend: {
              display: false
            },
            tooltip: {
              enabled: true
            }
          },
        },
        plugins: [textCenter],
        layout: {
          padding: {
            top: 10,
            right: 10,
            bottom: 10,
            left: 10
          },
          minWidth: 250,  // Minimum width in pixels
          minHeight: 250  // Minimum height in pixels
        }
      });
    }
    if ($('#owl-carousel-basic').length) {
      $('#owl-carousel-basic').owlCarousel({
        loop: true,
        margin: 10,
        dots: false,
        nav: true,
        autoplay: true,
        autoplayTimeout: 4500,
        navText: ["<i class='mdi mdi-chevron-left'></i>", "<i class='mdi mdi-chevron-right'></i>"],
        responsive: {
          0: {
            items: 1
          },
          600: {
            items: 1
          },
          1000: {
            items: 1
          }
        }
      });
    }
    if ($('#audience-map').length) {
      $('#audience-map').vectorMap({
        map: 'world_mill_en',
        backgroundColor: 'transparent',
        panOnDrag: true,
        focusOn: {
          x: 0.5,
          y: 0.5,
          scale: 1,
          animate: true
        },
        series: {
          regions: [{
            scale: ['#3d3c3c', '#f2f2f2'],
            normalizeFunction: 'polynomial',
            values: {

              "BZ": 75.00,
              "US": 56.25,
              "AU": 15.45,
              "GB": 25.00,
              "RO": 10.25,
              "GE": 33.25
            }
          }]
        }
      });
    }
    if ($('#owl-carousel-rtl').length) {
      $('#owl-carousel-rtl').owlCarousel({
        loop: true,
        margin: 10,
        dots: false,
        nav: true,
        rtl: isrtl,
        autoplay: true,
        autoplayTimeout: 4500,
        navText: ["<i class='mdi mdi-chevron-right'></i>", "<i class='mdi mdi-chevron-left'></i>"],
        responsive: {
          0: {
            items: 1
          },
          600: {
            items: 1
          },
          1000: {
            items: 1
          }
        }
      });
    }
    if ($("#currentBalanceCircle").length) {
      var bar = new ProgressBar.Circle(currentBalanceCircle, {
        color: '#ccc',
        // This has to be the same size as the maximum width to
        // prevent clipping
        strokeWidth: 12,
        trailWidth: 12,
        trailColor: '#0d0d0d',
        easing: 'easeInOut',
        duration: 1400,
        text: {
          autoStyleContainer: false,
        },
        from: { color: '#d53f3a', width: 12 },
        to: { color: '#d53f3a', width: 12 },
        // Set default step function for all animate calls
        step: function (state, circle) {
          circle.path.setAttribute('stroke', state.color);
          circle.path.setAttribute('stroke-width', state.width);

          var value = Math.round(circle.value() * 100);
          circle.setText('');

        }
      });

      bar.text.style.fontSize = '1.5rem';

      bar.animate(0.4);  // Number from 0.0 to 1.0
    }
  });


})(jQuery);
