(function() {
  function updateProgressBar(amountOptimized) {
    var totalToOptimize = parseInt(jQuery('div.progressbar').data('amount-to-optimize'));

    var optimizedSoFar = parseInt(jQuery("#optimized-so-far").text());;
    jQuery("#optimized-so-far").html(amountOptimized + optimizedSoFar);

    var percentage = Math.round((amountOptimized + optimizedSoFar) / totalToOptimize * 100, 2) + "%";
    jQuery('div.progressbar-progress').css('width', percentage);
    jQuery('div.progressbar span#percentage').html("(" + percentage + ")");

    var amountToOptimize = parseInt(jQuery("#optimizable-image-sizes").html())
    jQuery("#optimizable-image-sizes").html(amountToOptimize - amountOptimized)
  }

  function updateSavings(amountOptimized) {
    var imagesSizedOptimized = parseInt(jQuery("#optimized-image-sizes").text());
    jQuery("#optimized-image-sizes").html(imagesSizedOptimized + amountOptimized);

    jQuery.ajax({
      url: ajaxurl,
      type: "POST",
      dataType: "json",
      data: {
        _nonce: tinyCompress.nonce,
        action: 'tiny_get_optimization_statistics',
        json: true
      },
      success: function(data) {
        jQuery("#unoptimized-library-size").html(data['unoptimized-library-size']);
        jQuery("#optimized-library-size").html(data['optimized-library-size']);
        var size = jQuery('div.savings-chart').data('full-circle-size')
        var percentageFactor = size / 100;
        var percentage = percentageFactor * parseFloat(data['savings-percentage']);
        jQuery(".savings-chart svg circle.main").css("stroke-dasharray", "" + percentage + " " + size)
        jQuery("#savings-percentage").html(data['savings-percentage'] + "%");
      },
      error: function(xhr, textStatus, errorThrown) {
        console.log(errorThrown);
      }
    })
  }

  function handleCancellation() {
    jQuery('form button').hide()
    jQuery('div.progressbar-progress').css('animation', 'none');
  }

  function updateViewAfterSuccess(row, amountOptimized) {
    row.find('.status').addClass('success')

    if (amountOptimized == 0) {
      row.find('.status').html(tinyCompress.L10nNoActionTaken)
    } else {
      row.find('.status').html(amountOptimized + " " + tinyCompress.L10nCompressed)
      updateProgressBar(amountOptimized);
      updateSavings(amountOptimized);
    }
  }

  function bulkOptimizationCallback(error, data, items, i) {
    if (window.optimizationCancelled) {
      handleCancellation();
    }

    var row = jQuery('#media-items tr').eq(parseInt(i)+1)

    if (data.thumbnail) {
      var img = jQuery('<img class="pinkynail">')
      img.attr("src", data.thumbnail)
      row.children('td.thumbnail').html(img)
    }

    if (error) {
      row.find('.status').addClass('failed')
      row.find('.status').html(tinyCompress.L10nInternalError + "<br>" + error.toString())
      row.find('.status').attr("title", error.toString())
    } else if (data.error) {
      row.find('.status').addClass('failed')
      row.find('.status').html(tinyCompress.L10nError + "<br>" + data.error)
      row.find('.status').attr("title", data.error)
    } else if (data.failed > 0) {
      row.find('.status').addClass('failed')
      row.find('.status').html("<span class=\"icon dashicons dashicons-warning error\"></span><span class=\"message\">" + tinyCompress.L10nLatestError + ": " + data.message + "</span>");
      row.find('.status').attr("title", data.message)
    } else {
      updateViewAfterSuccess(row, parseInt(data.success))
    }

    if (!data.initial_total_size) data.initial_total_size = '-';
    if (!data.optimized_total_size) data.optimized_total_size = '-';
    if (!data.savings || data.savings == 0) {
      data.savings = '-';
    } else {
      data.savings += '%';
    }

    row.find('.image-sizes-optimized').html(data.image_sizes_optimized);
    row.find('.initial-total-size').html(data.initial_total_size);
    row.find('.optimized-total-size').html(data.optimized_total_size);
    row.find('.savings').html(data.savings);

    if (items[++i]) {
      bulk_optimization_item(items, i)
    } else {
      var message = jQuery('<div class="updated"><p></p></div>');
      message.find('p').html(tinyCompress.L10nAllDone)
      message.insertAfter(jQuery("#tiny-bulk-optimization h1"))
      jQuery("#tiny-bulk-optimization form div.spinner").css('display', 'none');
      jQuery('div.progressbar-progress').css('width', '100%');
      jQuery('form button').hide()

      jQuery('div.progressbar-progress').css('animation', 'none');
    }
  }

  function bulk_optimization_item(items, i) {
    if (window.optimizationCancelled) {
      return;
    }

    var item = items[i]
    var row = jQuery('#media-items tr').eq(parseInt(i)+1)
    row.find('.status').removeClass('todo')
    row.find('.savings').html(tinyCompress.L10nCompressing)
    jQuery.ajax({
      url: ajaxurl,
      type: "POST",
      dataType: "json",
      data: {
        _nonce: tinyCompress.nonce,
        action: 'tiny_compress_image',
        id: items[i].ID,
        json: true
      },
      success: function(data) { bulkOptimizationCallback(null, data, items, i)},
      error: function(xhr, textStatus, errorThrown) { bulkOptimizationCallback(errorThrown, {}, items, i) }
    })
    jQuery('#tiny-progress span').html(i + 1)
  }

  function bulk_optimization(items) {
    window.optimizationCancelled = false;
    updateProgressBar(0);
    updateSavings(0);

    var list = jQuery('#media-items tbody')
    var row

    jQuery("#tiny-bulk-optimization form div.spinner").css('display', 'inline-block');
    for (var i = 0; i < items.length; i++) {
      row = jQuery('<tr class="media-item"><td class="thumbnail" /><td class="name" /><td class="image-sizes-optimized" /><td class="initial-total-size" /><td class="optimized-total-size" /><td class="savings" /><td class="status todo" /></tr>')
      row.find('.status').html(tinyCompress.L10nWaiting)
      row.find('.name').html(items[i].post_title)
      list.append(row)
    }
    bulk_optimization_item(items, 0)
  }

  function cancelOptimization() {
    window.optimizationCancelled = true;
    jQuery("#tiny-bulk-optimization form div.spinner").css('display', 'none');
    jQuery(jQuery('#media-items tr td.status.todo')).html(tinyCompress.L10nCancelled)
    jQuery("form button > span").removeClass('active')
    jQuery("form button > span.cancelling").addClass('active')
  }

  jQuery("button").click(function(event) {
    if (jQuery(jQuery(event.target).find("span.cancel")).hasClass('active')) {
      event.preventDefault();
      cancelOptimization();
    }
  });

  jQuery("button").hover(function(event) {
    if (jQuery(jQuery(event.target).find("span.optimizing")).hasClass('active')) {
      window.lastActiveButton = jQuery("form button > span.active")
      lastActiveButton.removeClass('active')
      jQuery("form button > span.cancel").addClass('active')
    }
  }, function(event) {
    if (jQuery(jQuery(event.target).find("span.cancel")).hasClass('active')) {
      window.lastActiveButton.addClass('active')
      jQuery("form button > span.cancel").removeClass('active')
    }
  });

  window.startBulkOptimization = bulk_optimization
  updateSavings(0);

}).call()
