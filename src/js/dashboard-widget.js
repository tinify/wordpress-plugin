(function() {
  function generateDashboardWidget(element) {
    var element = jQuery(element)
    jQuery('.chart').addClass('hidden')
    var container = element.find('.inside')
    // Adding a class to the widget element so that classes are only used in the stylesheet
    jQuery('#tinypng_dashboard_widget').addClass('tiny_dashboard_widget')
    attachHandlers(container);
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

  function attachHandlers(container) {
   checkIfSmallContainer(container);
   jQuery(window).on('resize', function(){checkIfSmallContainer(container)});
  }

  function checkIfSmallContainer(container) {
     if (jQuery(container).width() < 400) {
        jQuery(container).addClass('mobile')
     } else if (jQuery(container).width() < 490 && jQuery(container).width() >= 400) {
        jQuery(container).addClass('tablet')
        jQuery(container).removeClass('mobile')
     } else if (jQuery(container).width() >= 490) {
        jQuery(container).removeClass('tablet').removeClass('mobile')
     }
  }

  function updateWidgetContent(savingsPercentage, libraryOptimized, stats, container) {
    addPercentageToChart(savingsPercentage);
    showContent(libraryOptimized, stats);
    var chart = widgetChart(savingsPercentage);
    var style = widgetChartStyle(chart);

    // .append not supported in IE8
    try {
      jQuery('.inside style').append(style);
    } catch(err) {

    }
    jQuery('#optimization-chart').removeClass('hidden');
    jQuery('#widget-spinner').attr('class', 'hidden');
  }

  function addPercentageToChart(percentage) {
    jQuery('#savings-percentage').find('span').html(percentage)
  }

  function showContent(percentage, stats) {
    if ( 0 == stats['uploaded-images'] + stats['available-unoptimised-sizes'] ) {
      jQuery('#tinypng_dashboard_widget').addClass('no-images-uploaded')
    } else if ( percentage == 0 ) {
       jQuery('#tinypng_dashboard_widget').addClass('not-optimized')
    } else if ( percentage == 100 ) {
      jQuery('#tinypng_dashboard_widget').addClass('full-optimized')
    } else {
      jQuery("#uploaded-images").html( stats['uploaded-images'] )
      jQuery("#unoptimised-sizes").html( stats['available-unoptimised-sizes'] )
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
    jQuery('#optimization-chart svg circle.main').css('stroke-dasharray', chart['dash-array-size'] + ' ' + chart['circle-size'])
    style =
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
