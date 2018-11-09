(function() {
  var parallelCompressions = 5;

  function updateProgressBar(successFullCompressions) {
    var totalToOptimize = parseInt(jQuery('div#compression-progress-bar').data('number-to-optimize'), 10);

    var optimizedSoFar = parseInt(jQuery('#optimized-so-far').text(), 10);
    jQuery('#optimized-so-far').html(successFullCompressions + optimizedSoFar);

    var percentage = '100%';
    if (totalToOptimize > 0) {
      percentage = Math.round((successFullCompressions + optimizedSoFar) / totalToOptimize * 100, 1) + '%';
    }
    jQuery('div#compression-progress-bar #progress-size').css('width', percentage);
    jQuery('div#compression-progress-bar #percentage').html('(' + percentage + ')');

    var numberToOptimize = parseInt(jQuery('#optimizable-image-sizes').html(), 10);
    jQuery('#optimizable-image-sizes').html(numberToOptimize - successFullCompressions);
  }

  function updateSavings(successFullCompressions, successFullSaved, newHumanReadableLibrarySize) {
    window.currentLibraryBytes = window.currentLibraryBytes + successFullSaved;

    var imagesSizedOptimized = parseInt(jQuery('#optimized-image-sizes').text(), 10) + successFullCompressions;
    var initialLibraryBytes = parseInt(jQuery('#unoptimized-library-size').data('bytes'), 10);
    var percentage = (1 - window.currentLibraryBytes / initialLibraryBytes);
    var chartSize = jQuery('div#optimization-chart').data('full-circle-size');

    jQuery('#optimized-image-sizes').html(imagesSizedOptimized);
    jQuery('#optimized-library-size').attr('data-bytes', window.currentLibraryBytes);
    jQuery('#optimized-library-size').html(newHumanReadableLibrarySize);
    jQuery('#savings-percentage').html(Math.round(percentage * 1000) / 10 + '%');
    jQuery('div#optimization-chart svg circle.main').css('stroke-dasharray', '' + (chartSize * percentage) + ' ' + chartSize);
  }

  function handleCancellation() {
    jQuery('div#bulk-optimization-actions').hide();
    jQuery('div.progress').css('animation', 'none');
  }

  function updateRowAfterCompression(row, data) {
    var successFullCompressions = parseInt(data.success, 10);
    var successFullSaved = parseInt(data.size_change, 10);
    var newHumanReadableLibrarySize = data.human_readable_library_size;
    if (successFullCompressions === 0) {
      row.addClass('no-action');
      row.find('.status').html('<span class="icon dashicons dashicons-no alert"></span>' + tinyCompress.L10nNoActionTaken).attr('data-status', 'no-action-taken');
    } else {
      row.addClass('success');
      row.find('.status').html('<span class="icon dashicons dashicons-yes success"></span>' + successFullCompressions + ' ' + tinyCompress.L10nCompressed).attr('data-status', 'compressed');
      updateProgressBar(successFullCompressions);
      updateSavings(successFullCompressions, successFullSaved, newHumanReadableLibrarySize);
    }
  }

  function bulkOptimizationCallback(error, data, items, i) {
    if (window.optimizationCancelled) {
      handleCancellation();
    }

    var row = jQuery('#optimization-items tr').eq(parseInt(i, 10)+1);

    if (error) {
      row.addClass('failed');
      row.find('.status').html(tinyCompress.L10nInternalError + '<br>' + error.toString());
      row.find('.status').attr('title', error.toString());
      row.find('.status').attr('data-status', 'error');
      data = {};
    } else if (data == null) {
      row.addClass('failed');
      row.find('.status').html(tinyCompress.L10nError);
      row.find('.status').attr('data-status', 'error');
      data = {};
    } else if (data.error) {
      row.addClass('failed');
      row.find('.status').html(tinyCompress.L10nError + '<br>' + data.error);
      row.find('.status').attr('title', data.error);
      row.find('.status').attr('data-status', 'error');
    } else if (data.failed > 0) {
      row.addClass('failed');
      row.find('.status').html('<span class=\'icon dashicons dashicons-no error\'></span><span class=\'message\'>' + tinyCompress.L10nLatestError + ': ' + data.message + '</span>');
      row.find('.status').attr('title', data.message);
      row.find('.status').attr('data-status', 'error');
    } else {
      updateRowAfterCompression(row, data);
    }

    row.find('.name').html(items[i].post_title + '<button class=\'toggle-row\' type=\'button\'><span class=\'screen-reader-text\'>' + tinyCompress.L10nShowMoreDetails + '</span></button>');

    if (!data.image_sizes_optimized) {
        data.image_sizes_optimized = '-';
    }
    if (!data.initial_total_size) {
        data.initial_total_size = '-';
    }
    if (!data.optimized_total_size) {
        data.optimized_total_size = '-';
    }
    if (!data.savings || data.savings === 0) {
      data.savings = '-';
    } else {
      data.savings += '%';
    }

    row.find('.thumbnail').html(data.thumbnail);
    row.find('.sizes-optimized').html(data.image_sizes_optimized);
    row.find('.initial-size').html(data.initial_total_size);
    row.find('.optimized-size').html(data.optimized_total_size);
    row.find('.savings').html(data.savings);

    var totalToOptimize = jQuery('td.status[data-status="waiting"],td.status[data-status="compressing"]').length;
    var nextImage;

    if (jQuery('td.status[data-status="waiting"]').length > 0) {
      nextImage = jQuery('tr.media-item').index(jQuery('td.status[data-status="waiting"]:first').parents('tr'));
    }
    if (nextImage !== undefined && items[nextImage]) {
      if (!window.optimizationCancelled) {
        drawSomeRows(items, 1);
      }
      bulkOptimizeItem(items, nextImage);
    } else if (totalToOptimize === 0) {
      var message = jQuery('<div class=\'updated\'><p></p></div>');
      message.find('p').html(tinyCompress.L10nAllDone);
      message.insertAfter(jQuery('#tiny-bulk-optimization h1'));
      jQuery('div#optimization-spinner').css('display', 'none');
      jQuery('div#bulk-optimization-actions').hide();
      jQuery('div.progress').css('animation', 'none');
    }
  }

  function bulkOptimizeItem(items, i) {
    if (window.optimizationCancelled) {
      return;
    }

    var row = jQuery('#optimization-items tr').eq(parseInt(i, 10)+1);
    row.find('.status').removeClass('todo');
    row.find('.status').html('<span class="icon spinner"></span>' + tinyCompress.L10nCompressing).attr('data-status', 'compressing');
    jQuery.ajax({
      url: ajaxurl,
      type: 'POST',
      dataType: 'json',
      data: {
        _nonce: tinyCompress.nonce,
        action: 'tiny_compress_image_for_bulk',
        id: items[i].ID,
        current_size: window.currentLibraryBytes
      },
      success: function(data) { bulkOptimizationCallback(null, data, items, i); },
      error: function(xhr, textStatus, errorThrown) { bulkOptimizationCallback(errorThrown, null, items, i, parallelCompressions); }
    });
    jQuery('#tiny-progress span').html(i + 1);
  }

  function prepareBulkOptimization(items) {
    window.allBulkOptimizationItems = items;
    updateProgressBar(0);
  }

  function startBulkOptimization(items) {
    window.optimizationCancelled = false;
    window.totalRowsDrawn = 0;
    window.currentLibraryBytes = parseInt(jQuery('#optimized-library-size').data('bytes'), 10);

    jQuery('div.progress').css('animation', 'progress-bar 80s linear infinite');
    jQuery('div#optimization-spinner').css('display', 'inline-block');
    updateProgressBar(0);
    drawSomeRows(items, 5 + parallelCompressions);

    for (var i = 0; i < parallelCompressions; i++) {
      if (items.length >= i+1) {
        bulkOptimizeItem(items, i);
      }
    }
  }

  function drawSomeRows(items, rowsToDraw) {
    var list = jQuery('#optimization-items tbody');
    var row;
    for (var drawNow = window.totalRowsDrawn; drawNow < Math.min( rowsToDraw + window.totalRowsDrawn, items.length); drawNow++) {
      row = jQuery('<tr class=\'media-item\'>' +
          '<th class=\'thumbnail\' />' +
          '<td class=\'column-primary name\' />' +
          '<td class=\'column-author initial-size\' data-colname=\'' + tinyCompress.L10nInitialSize + '\' ></>' +
          '<td class=\'column-author optimized-size\' data-colname=\'' + tinyCompress.L10nCurrentSize + '\' ></>' +
          '<td class=\'column-author savings\' data-colname=\'' + tinyCompress.L10nSavings + '\' ></>' +
          '<td class=\'column-author status todo\' data-colname=\'' + tinyCompress.L10nStatus + '\' />' +
        '</tr>');
      row.find('.status').html(tinyCompress.L10nWaiting).attr('data-status', 'waiting');
      row.find('.name').html(items[drawNow].post_title);
      list.append(row);
    }
    window.totalRowsDrawn = drawNow;
  }

  function cancelOptimization() {
    window.optimizationCancelled = true;
    jQuery('div#optimization-spinner').css('display', 'none');
    jQuery(jQuery('#optimization-items tr td.status.todo')).html(tinyCompress.L10nCancelled).attr('data-status', 'cancelled');
    jQuery('div#bulk-optimization-actions input').removeClass('visible');
    jQuery('div#bulk-optimization-actions input#id-cancelling').addClass('visible');
  }

  jQuery('.tiny-bulk-optimization .upgrade-account-notice a#hide-warning').click(function() {
    jQuery('.tiny-bulk-optimization .upgrade-account-notice').hide();
    jQuery('.tiny-bulk-optimization .optimize').children().show();
  });

  jQuery('div#bulk-optimization-actions input').click(function() {
    if ((jQuery(this).attr('id') === 'id-start') && jQuery(this).hasClass('visible')) {
      jQuery('div#bulk-optimization-actions input#id-start').removeClass('visible');
      jQuery('div#bulk-optimization-actions input#id-optimizing').addClass('visible');
      startBulkOptimization(window.allBulkOptimizationItems);
    }
    if ((jQuery(this).attr('id') === 'id-cancel') && jQuery(this).hasClass('visible')) {
      cancelOptimization();
    }
  });

  jQuery('div#bulk-optimization-actions input').hover(function() {
    if ((jQuery(this).attr('id') === 'id-optimizing') && jQuery(this).hasClass('visible')) {
      window.lastActiveButton = jQuery('div#bulk-optimization-actions input.visible');
      window.lastActiveButton.removeClass('visible');
      jQuery('div#bulk-optimization-actions input#id-cancel').addClass('visible');
    }
  }, function() {
    if ((jQuery(this).attr('id') === 'id-cancel') && jQuery(this).hasClass('visible')) {
      window.lastActiveButton.addClass('visible');
      jQuery('div#bulk-optimization-actions input#id-cancel').removeClass('visible');
    }
  });

  function attachToolTipEventHandlers() {
    var tooltip = '#tiny-bulk-optimization div.tooltip';
    var tip = 'div.tip';
    var toolTipTimeout = null;
    jQuery(tooltip).mouseleave(function(){
      var that = this;
      toolTipTimeout = setTimeout(function() {
        if (jQuery(that).find(tip).is(':visible')) {
          jQuery(tooltip).find(tip).hide();
        }
      }, 100);
    });
    jQuery(tooltip).mouseenter(function(){
      jQuery(this).find(tip).show();
      clearTimeout(toolTipTimeout);
    });
    jQuery(tooltip).find(tip).mouseenter(function(){
      clearTimeout(toolTipTimeout);
    });
  }

  attachToolTipEventHandlers();

  window.bulkOptimizationAutorun = startBulkOptimization;
  window.bulkOptimization = prepareBulkOptimization;

}).call();
