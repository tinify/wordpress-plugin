(function() {
  function generateDashboardWidget(element) {
    var element = jQuery(element)
    var container = element.find('.inside')
    jQuery.ajax({
      url: ajaxurl,
      type: 'POST',
      data: {
        _nonce: tinyCompress.nonce,
        action: 'tiny_get_optimization_statistics',
        id: '#tinypng_dashboard_widget'
      },
      success: function(data) {
        if (data == 0) {
          container.append("<p> An error occured. </p>")
        } else {
          var stats = jQuery.parseJSON(data);
          var savings = savingsPercentage(stats);
          var libraryOptimized = optimizedPercentage(stats);
          updateWidgetContent(savings, libraryOptimized, stats, container);
        }
      },
      error: function() {
        container.append("<h2> An error occured. </h2>")
      }
    })
  }

  function updateWidgetContent(savingsPercentage, libraryOptimized, stats, container) {
    addPercentageToChart(savingsPercentage);
    showContent(libraryOptimized, stats);
    var chart = widgetChart(savingsPercentage);
    var style = widgetChartStyle(chart);

    // .append not supported in IE8
    try {
      jQuery('#widget-style').append(style);
    } catch(err) {

    }
    jQuery('#optimization-chart').removeClass('hidden');
    jQuery('#widget-spinner').attr('class', 'hidden');
  }

  function addPercentageToChart(percentage) {
    jQuery('.widget-percentage').find('span').html(percentage)
  }

  function showContent(percentage, stats) {
    if ( percentage == 0 ) {
      jQuery('#tinypng_dashboard_widget').addClass('not-optimized')
    } else if ( percentage == 100 ) {
      jQuery('#tinypng_dashboard_widget').addClass('full-optimized')
    } else {
      jQuery('#widget-half-optimized').find('.compressions-remaining').html( stats['optimized-image-sizes'] + '/' + (stats['optimized-image-sizes'] + stats['available-unoptimised-sizes']))
      jQuery('#tinypng_dashboard_widget').addClass('half-optimized')
    }
  }

  function widgetChart(percentage) {
    chart = {};
    chart['size'] = 180;
    chart['radius'] = chart['size'] / 2 * 0.9;
    chart['main-radius'] = chart['radius'] * 0.88;
    chart['circle-size'] = 2 * Math.PI * chart['main-radius'];
    chart['dash-array-size'] = percentage / 100 * chart['circle-size'];
    return chart
  }

  function optimizedPercentage(stats) {
    if ( 0 != stats['unoptimized-library-size'] ) {
      return Math.round((stats['optimized-image-sizes'] / (stats['optimized-image-sizes'] + stats['available-unoptimised-sizes']) * 100), 0);
    } else {
      return 0
    }
  }

  function savingsPercentage(stats) {
    if ( 0 != stats['unoptimized-library-size'] ) {
      return Math.round( 100 - ( stats['optimized-library-size'] / stats['unoptimized-library-size'] * 100 ), 1 );
    } else {
      return 0
    }
  }

  function widgetChartStyle(chart) {
    style = " #optimization-chart svg circle.main {" +
            " stroke-dasharray:" + chart['dash-array-size'] + ' ' + chart['circle-size'] + ";}"+
            " @keyframes shwoosh {" +
              " from { stroke-dasharray: 0 " + chart['circle-size'] + "}" +
              " to { stroke-dasharray:" + chart['dash-array-size'] + " " + chart['circle-size'] + "}}"
    return style
  }

  // Check if widget is loaded
  if (jQuery('#tinypng_dashboard_widget').length) {
    generateDashboardWidget('#tinypng_dashboard_widget')
  }
}).call()
