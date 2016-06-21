(function() {
  function updateProgressBar(successFullCompressions) {
    var totalToOptimize = parseInt(jQuery('div.progressbar').data('amount-to-optimize'));

    var optimizedSoFar = parseInt(jQuery("#optimized-so-far").text());;
    jQuery("#optimized-so-far").html(successFullCompressions + optimizedSoFar);

    var percentage = Math.round((successFullCompressions + optimizedSoFar) / totalToOptimize * 100, 1) + "%";
    jQuery('div.progressbar-progress').css('width', percentage);
    jQuery('div.progressbar span#percentage').html("(" + percentage + ")");

    var numberToOptimize = parseInt(jQuery("#optimizable-image-sizes").html())
    jQuery("#optimizable-image-sizes").html(numberToOptimize - successFullCompressions)
  }

  function updateSavings(successFullCompressions, successFullSaved, newHumanReadableLibrarySize) {

    var imagesSizedOptimized = parseInt(jQuery("#optimized-image-sizes").text()) + successFullCompressions;
    var initialLibraryBytes = parseInt(jQuery("#unoptimized-library-size").data("bytes"));
    var optimizedLibraryBytes = parseInt(jQuery("#optimized-library-size").data("bytes")) + successFullSaved;
    var percentage = (1 - optimizedLibraryBytes / initialLibraryBytes)
    var chartSize = jQuery('div.savings-chart').data('full-circle-size')

    jQuery("#optimized-image-sizes").html(imagesSizedOptimized);
    jQuery("#optimized-library-size").attr("data-bytes", optimizedLibraryBytes);
    jQuery("#optimized-library-size").html(newHumanReadableLibrarySize);
    jQuery("#savings-percentage").html(Math.round(percentage * 1000) / 10 + "%");
    jQuery(".savings-chart svg circle.main").css("stroke-dasharray", "" + (chartSize * percentage) + " " + chartSize)

  }

  function handleCancellation() {
    jQuery('form button').hide()
    jQuery('div.progressbar-progress').css('animation', 'none');
  }

  function updateViewAfterSuccess(row, data) {
    var successFullCompressions = parseInt(data.success)
    var successFullSaved = parseInt(data.size_change)
    var newHumanReadableLibrarySize = data.human_readable_library_size
    row.find('.status').addClass('success')
    if (successFullCompressions == 0) {
      row.find('.status').html(tinyCompress.L10nNoActionTaken)
    } else {
      row.find('.status').html(successFullCompressions + " " + tinyCompress.L10nCompressed)
      updateProgressBar(successFullCompressions);
      updateSavings(successFullCompressions, successFullSaved, newHumanReadableLibrarySize);
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
      // This recalculates all statistics
      updateViewAfterSuccess(row, data)
    }

    if (!data.initial_total_size) {
        data.initial_total_size = '-';
    }
    if (!data.optimized_total_size) {
        data.optimized_total_size = '-';
    }
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
      bulkOptimizeItem(items, i)
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

  function bulkOptimizeItem(items, i) {
    if (window.optimizationCancelled) {
      return;
    }

    var currentLibraryBytes = parseInt(jQuery("#optimized-library-size").data("bytes"))

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
        action: 'tiny_compress_image_for_bulk',
        id: items[i].ID,
        current_size: currentLibraryBytes
      },
      success: function(data) { bulkOptimizationCallback(null, data, items, i)},
      error: function(xhr, textStatus, errorThrown) { bulkOptimizationCallback(errorThrown, {}, items, i) }
    })
    jQuery('#tiny-progress span').html(i + 1)
  }

  function prepareBulkOptimization(items) {
      window.allBulkOptimizationItems = items;
  }

  function startBulkOptimization(items) {
    window.optimizationCancelled = false;
    var list = jQuery('#media-items tbody')
    var row
    jQuery("#tiny-bulk-optimization form div.spinner").css('display', 'inline-block');
    for (var i = 0; i < items.length; i++) {
      row = jQuery('<tr class="media-item"><td class="thumbnail" /><td class="name" /><td class="image-sizes-optimized" /><td class="initial-total-size" /><td class="optimized-total-size" /><td class="savings" /><td class="status todo" /></tr>')
      row.find('.status').html(tinyCompress.L10nWaiting)
      row.find('.name').html(items[i].post_title)
      list.append(row)
    }
    bulkOptimizeItem(items, 0)
  }

  function cancelOptimization() {
    window.optimizationCancelled = true;
    jQuery("#tiny-bulk-optimization form div.spinner").css('display', 'none');
    jQuery(jQuery('#media-items tr td.status.todo')).html(tinyCompress.L10nCancelled)
    jQuery("form button > span").removeClass('active')
    jQuery("form button > span.cancelling").addClass('active')
  }

  jQuery("button").click(function(event) {
    if (jQuery(jQuery(event.target).find("span.start-optimizing")).hasClass('active')) {
      event.preventDefault();
      jQuery("form button > span.start-optimizing").removeClass('active')
      jQuery("form button > span.optimizing").addClass('active')
      startBulkOptimization(window.allBulkOptimizationItems);
    }
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

  window.bulkOptimizationAutorun = startBulkOptimization
  window.bulkOptimization = prepareBulkOptimization

}).call()
