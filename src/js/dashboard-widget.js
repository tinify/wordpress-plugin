(function() {

  function generateDashboardWidget(element) {
    var element = jQuery(element)
    var container = element.find('div.inside')
    jQuery.ajax({
      url: ajaxurl,
      type: 'POST',
      data: {
        _nonce: tinyCompress.nonce,
        action: 'tiny_widget_get_optimization_statistics',
        id: '#tinypng_dashboard_widget'
      },
      success: function(data) {
        if (data == 0) {
          container.append("<p> An error occured. </p>")
        } else {
          var stats = jQuery.parseJSON(data)
          var percentage = 0;
          var username = stats["username"];
          var bulkOptimizationLink = "<a href='" + stats["link"] + "'>bulk optimization page.</a>"

          if ( 0 != stats['unoptimized-library-size'] ) {
            percentage = Math.round((stats['optimized-image-sizes'] / (stats['optimized-image-sizes'] + stats['available-unoptimised-sizes']) * 100), 0);
          }

          var chart = widgetChart(percentage);
          var text = widgetText(percentage, username, bulkOptimizationLink);
          var backgroundImage = widgetBackground(percentage);
          var style = widgetChartStyle(chart);

          container.append("<div class='media-library-optimized'><p>" + text + "</p></div>")
          container.append("<div class='panda-background " + backgroundImage +"'></div>")
          container.append(style)
          container.append("<div id='optimization-chart' class='widget-chart' data-full-circle-size='" + chart['circle-size'] + "' data-percentage-factor='" + chart['main-radius'] + "'><svg width='" + chart['size'] + "' height='" + chart['size'] + "'><circle class='main' transform='rotate(-90, " + chart['center'] + "," + chart['center'] + ")' r='" + chart['main-radius'] + "' cx='" + chart['center'] + "' cy='" + chart['center'] + "'></circle><circle class='inner' r='"+ chart['inner-radius'] +"' cx='" + chart['center'] + "' cy='" + chart['center'] + "'></circle></svg><div class='value'><div class='percentage widget-percentage' id='savings-percentage'>" + percentage + "%</div><div class='label widget-label'>optimized</div></div></div>");
        }
      },
      error: function() {
        // container.append("<h2> An error occured. </h2>")
      }
    })
  }

  function widgetBackground(percentage) {
    var widgetBackground = "";
    if ( percentage == 0 ) {
      widgetBackground = "panda-waiting";
    } else if ( percentage == 100 ) {
      widgetBackground = "panda-laying";
    } else {
      widgetBackground = "panda-eating";
    }
    return widgetBackground
  }

  function widgetText(percentage, username, link) {
    var output = "Hi " + username;
    if ( percentage == 0 ) {
      output += ", you haven’t compressed any images in your media library. If you like you can to optimize your whole library in one go with the " + link;
    } else if ( percentage == 100 ) {
      output += " great job! Your entire library is optimized!";
    } else {
      output += ", you are doing good. " + percentage + "% of your media library is optimized. If you like you can to optimize the remainder of your library with the " + link;
    }
    return output
  }

  function widgetChart(percentage) {
    chart = {};
    chart['size'] = 180;
    chart['radius'] = chart['size'] / 2 * 0.9;
    chart['main-radius'] = chart['radius'] * 0.88;
    chart['center'] = chart['size'] / 2;
    chart['stroke'] = chart['radius'] / 2;
    chart['dash-stroke'] = chart['radius'] / 4;
    chart['inner-radius'] = chart['radius'] - chart['stroke'] / 2;
    chart['circle-size'] = 2 * Math.PI * chart['main-radius'];
    chart['dash-array-size'] = percentage / 100 * chart['circle-size'];
    return chart
  }

  function widgetChartStyle(chart) {
    style = "<style>" +
            " #optimization-chart svg circle.main {" +
            " stroke-width:" + chart['dash-stroke'] + ";" +
            " stroke-dasharray:" + chart['dash-array-size'] + ' ' + chart['circle-size'] + ";}"+
            " #optimization-chart svg circle.main { stroke: #7acb44; }" +
            " #optimization-chart div.chart div.value {min-width:" + chart['size'] + ";}" +
            " @keyframes shwoosh {" +
              " from { stroke-dasharray: 0 " + chart['circle-size'] + "}" +
              " to { stroke-dasharray:" + chart['dash-array-size'] + " " + chart['circle-size'] + "}}" +
            "</style>"
    return style
  }

  // Check if widget is loaded
  if (jQuery('#tinypng_dashboard_widget').length) {
    generateDashboardWidget('#tinypng_dashboard_widget')
  }
})
