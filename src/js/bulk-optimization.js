(function() {
  const POLL_INTERVAL = 2000;
  const POLL_WINDOW = 10;
  let pending = [];
  let stoppedEarly = false;

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
      const rowResultElement = row.find('.status');
      rowResultElement.attr('data-status', 'compressed');
      const icon = '<span class="icon dashicons dashicons-yes success"></span>';
      
      let successHTML = '';
      if (data.image_sizes_compressed > 0) {
        successHTML += `<p>${icon} ${data.image_sizes_compressed} ${tinyCompress.L10nCompressed}</p>`;
      }

      if (data.image_sizes_converted > 0) {
        successHTML += `<p>${icon} ${data.image_sizes_converted} ${tinyCompress.L10nConverted}</p>`;
      }

      rowResultElement.html(successHTML);

      updateProgressBar(successFullCompressions);
      updateSavings(successFullCompressions, successFullSaved, newHumanReadableLibrarySize);
    }
  }

  function bulkOptimizationCallback(data, items, i) {
    var row = jQuery('#optimization-items tr').eq(parseInt(i, 10)+1);

    if (data.failed > 0) {
      row.addClass('failed');
      row.find('.status').html('<span class=\'icon dashicons dashicons-no error\'></span><span class=\'message\'>' + tinyCompress.L10nLatestError + ': ' + data.message + '</span>');
      row.find('.status').attr('title', data.message);
      row.find('.status').attr('data-status', 'error');
    } else {
      updateRowAfterCompression(row, data);
    }

    row.find('.name').html(items[i].post_title + '<button class=\'toggle-row\' type=\'button\'><span class=\'screen-reader-text\'>' + tinyCompress.L10nShowMoreDetails + '</span></button>');

    if (!data.image_sizes_compressed) {
        data.image_sizes_compressed = '-';
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
    row.find('.sizes-compressed').html(data.image_sizes_compressed);
    row.find('.initial-size').html(data.initial_total_size);
    row.find('.optimized-size').html(data.optimized_total_size);
    row.find('.savings').html(data.savings);
  }

  function finishOptimization(message) {
    if (message) {
      const notice = jQuery('<div class=\'updated\'><p></p></div>');
      notice.find('p').text(message);
      notice.insertAfter(jQuery('#tiny-bulk-optimization h2'));
    }
    jQuery('div#optimization-spinner').css('display', 'none');
    handleCancellation();
  }

  // The queue works through the images in the order they are listed, so only
  // the first few waiting ones need asking about.
  function pollStatus(items) {
    const batch = pending.slice(0, POLL_WINDOW);
    drawSomeRows(items, batch[batch.length - 1] + 1);
    jQuery.post(ajaxurl, {
      _nonce: tinyCompress.nonce,
      action: 'tiny_bulk_queue_status',
      ids: batch.map(function(i) { return items[i].ID; }).join(','),
      current_size: window.currentLibraryBytes
    }, function(response) {
      const data = response.data;
      let finished = 0;
      const waiting = batch.filter(function(i) {
        const item = data.items[items[i].ID] || {};
        if (item.status === 'done' || item.status === 'failed') {
          bulkOptimizationCallback(item.result, items, i);
          finished++;
        } else if (data.running && (item.status === 'queued' || item.status === 'processing')) {
          return true;
        } else {
          jQuery('#optimization-items tr').eq(i + 1).find('.status').html(tinyCompress.L10nCancelled).attr('data-status', 'cancelled');
          stoppedEarly = true;
        }
        return false;
      });
      pending = waiting.concat(pending.slice(batch.length));

      if (pending.length > 0 && (data.running || finished > 0)) {
        setTimeout(pollStatus, POLL_INTERVAL, items);
      } else {
        finishOptimization(window.optimizationCancelled || stoppedEarly ? null : tinyCompress.L10nAllDone);
      }
    }, 'json').fail(function(xhr) {
      if (xhr.status === 403) {
        finishOptimization(tinyCompress.L10nInternalError);
      } else {
        setTimeout(pollStatus, POLL_INTERVAL, items);
      }
    });
  }

  function prepareBulkOptimization(items, running) {
    window.allBulkOptimizationItems = items;
    updateProgressBar(0);
    if (running) {
      startBulkOptimization(items, true);
    }
  }

  function startBulkOptimization(items, running) {
    window.optimizationCancelled = false;
    window.totalRowsDrawn = 0;
    window.currentLibraryBytes = parseInt(jQuery('#optimized-library-size').data('bytes'), 10);
    pending = items.map(function(item, i) { return i; });
    stoppedEarly = false;

    jQuery('div#bulk-optimization-actions input').removeClass('visible');
    jQuery('div#bulk-optimization-actions input#id-optimizing').addClass('visible');
    jQuery('div#bulk-optimization-actions p.optimization-buttons_notice').text(tinyCompress.L10nBackgroundNotice);
    jQuery('div.progress').css('animation', 'progress-bar 80s linear infinite');
    jQuery('div#optimization-spinner').css('display', 'inline-block');
    updateProgressBar(0);

    if (running) {
      pollStatus(items);
      return;
    }

    jQuery.post(ajaxurl, {
      _nonce: tinyCompress.nonce,
      action: 'tiny_bulk_queue_start'
    }, function() {
      pollStatus(items);
    }, 'json').fail(function() {
      finishOptimization(tinyCompress.L10nInternalError);
    });
  }

  function drawSomeRows(items, end) {
    const list = jQuery('#optimization-items tbody');
    const start = window.totalRowsDrawn;
    end = Math.min(end, items.length);
    for (let i = start; i < end; i++) {
      const tableRow = `<tr class="media-item">
        <td class="thumbnail" />
        <td class="column-primary name">${items[i].post_title}</th>
        <td class="column-author initial-size" data-colname="${tinyCompress.L10nInitialSize}" />
        <td class="column-author optimized-size" data-colname="${tinyCompress.L10nCurrentSize}" />
        <td class="column-author savings" data-colname="${tinyCompress.L10nSavings}" />
        <td class="column-author status" data-testid="bulk-item-status-${i}" data-colname="${tinyCompress.L10nStatus}" data-status="waiting">${tinyCompress.L10nWaiting}</td>
      </tr>`;
      list.append(tableRow);
    }
    window.totalRowsDrawn = Math.max(start, end);
  }

  async function cancelOptimization() {
    try {
      window.optimizationCancelled = true;
      jQuery('div#optimization-spinner').css('display', 'none');
      jQuery('div#bulk-optimization-actions input').removeClass('visible');
      jQuery('div#bulk-optimization-actions input#id-cancelling').addClass('visible');
      await jQuery.post(ajaxurl, {
        _nonce: tinyCompress.nonce,
        action: 'tiny_bulk_queue_cancel',
      });
    } catch (err) {
      // Cancel failed, revert state
      window.optimizationCancelled = true;
      jQuery('div#optimization-spinner').css('display', 'inline-block');
      jQuery('div#bulk-optimization-actions input').removeClass('visible');
      jQuery('div#bulk-optimization-actions input#id-optimizing').addClass('visible');
      jQuery('div#bulk-optimization-actions p.optimization-buttons_notice').text(tinyCompress.L10nInternalError);
    }
  }

  jQuery('.tiny-bulk-optimization .upgrade-account-notice a#hide-warning').click(function() {
    jQuery('.tiny-bulk-optimization .upgrade-account-notice').hide();
    jQuery('.tiny-bulk-optimization .optimize').children().show();
  });

  jQuery('div#bulk-optimization-actions input').click(function() {
    if ((jQuery(this).attr('id') === 'id-start') && jQuery(this).hasClass('visible')) {
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
