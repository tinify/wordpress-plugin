(function() {
  function compressImage(event) {
    var element = jQuery(event.target);
    var container = element.closest('div.tiny-ajax-container');
    element.attr('disabled', 'disabled');
    container.find('span.spinner').removeClass('hidden');
    container.find('span.dashicons').remove();
    jQuery.ajax({
      url: ajaxurl,
      type: 'POST',
      data: {
        _nonce: tinyCompress.nonce,
        action: 'tiny_compress_image_from_library',
        id: element.data('id') || element.attr('data-id')
      },
      success: function(data) {
        container.html(data);
      },
      error: function() {
        element.removeAttr('disabled');
        container.find('span.spinner').addClass('hidden');
      }
    });
  }

  function compressImageSelection() {
    const queryParams = new URLSearchParams(window.location.search);
    const action = queryParams.get('action');
    jQuery('span.auto-compress').each(function(index, element) {
      if (action === 'tiny_bulk_mark_compressed') {
        jQuery(element).siblings('button.tiny-mark-as-compressed').click()
      }
      if (action === 'tiny_bulk_action') {
        jQuery(element).siblings('button.tiny-compress').click()
      }
    });
  }

  function watchCompressingImages() {
    if (jQuery('.details-container[data-status="compressing"]').length > 0) {
      statusCheckIntervalId = setInterval(checkCompressingImages, 5000);
    }
  }

  function checkCompressingImages() {
    jQuery('.details-container[data-status="compressing"]').each(function(index, element) {
      element = jQuery(element);
      var container = element.closest('div.tiny-ajax-container');
      jQuery.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
          _nonce: tinyCompress.nonce,
          action: 'tiny_get_compression_status',
          id: element.attr('data-id')
        },
        success: function(data) {
          container.html(data);
          if (jQuery('.details-container[data-status="compressing"]').length === 0) {
            clearInterval(statusCheckIntervalId);
          }
        },
        error: function() {
          element.removeAttr('disabled');
          container.find('span.spinner').addClass('hidden');
        }
      });
    });
  }

  /**
   * Marks an image attachment as compressed without actually compressing it.
   *
   * This function sends an AJAX request to mark an image as compressed by creating
   * fake compression metadata. This is useful for images that are already optimized
   * or when you want to skip compression for specific images while still marking
   * them as processed in the system.
   *
   * @param {string} attachmentID - The WordPress attachment ID of the image to mark as compressed.
   * @returns {Promise<string>} - string of html displaying the updated compressions.
   */
  function markAsCompressed(attachmentID) {
    return new Promise((resolve, reject) => {
      jQuery.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
          _nonce: tinyCompress.nonce,
          action: 'tiny_mark_image_as_compressed',
          id: attachmentID,
        },
        success: function (data) {
          resolve(data);
        },
        error: function (err) {
          reject(err);
        },
      });
    });
  }

  async function onClickButtonMarkAsCompressed(event) {
    const element = jQuery(event.target);
    var container = element.closest('div.tiny-ajax-container');
    element.attr('disabled', 'disabled');
    container.find('span.spinner').removeClass('hidden');
    container.find('span.dashicons').remove();
    const attachmentID = element.data('id') || element.attr('data-id');
    try {
      const result = await markAsCompressed(attachmentID);
      container.html(result);
    } finally {
      element.removeAttr('disabled');
      container.find('span.spinner').addClass('hidden');
    }
  }

  function toggleChangeKey(event) {
    jQuery('div.tiny-account-status div.update').toggle();
    jQuery('div.tiny-account-status div.status').toggle();
    jQuery('div.tiny-account-status div.upgrade').toggle();
    return false;
  }

  function submitKey(event) {
    event.preventDefault();
    jQuery(event.target).attr({disabled: true}).addClass('loading');

    var action;
    var parent = jQuery(event.target).closest('div');
    var key;
    var email;
    var name;

    if (jQuery(event.target).data('tiny-action') === 'update-key') {
      action = 'update';
      key = parent.find('#tinypng_api_key').val();
    } else if (jQuery(event.target).data('tiny-action') === 'create-key') {
      action = 'create';
      name = parent.find('#tinypng_api_key_name').val();
      email = parent.find('#tinypng_api_key_email').val();
    } else {
      return false;
    }

    jQuery.ajax({
      url: ajaxurl,
      type: 'POST',
      data: {
        _nonce: tinyCompress.nonce,
        action: 'tiny_settings_' + action + '_api_key',
        key: key,
        name: name,
        email: email
      },
      success: function(json) {
        var status = jQuery.parseJSON(json);

        if (status.ok) {
          var target = jQuery('#tiny-account-status');
          if (target.length) {
            jQuery.get(ajaxurl + (ajaxurl.indexOf( '?' ) > 0 ? '&' : '?') + 'action=tiny_account_status', function(data) {
              jQuery(event.target).attr({disabled: false}).removeClass('loading');
              target.replaceWith(data);
            });
          }
          jQuery('div.tiny-notice[data-name="setting"]').remove();
        } else {
          jQuery(event.target).attr({disabled: false}).removeClass('loading');
          parent.addClass('failure');
          parent.find('p.message').text(status.message).show();
        }
      },
      error: function() {
        jQuery(event.target).attr({disabled: false}).removeClass('loading');
        parent.addClass('failure');
        parent.find('p.message').text('Something went wrong, try again soon').show();
      }
    });

    return false;
  }

  function dismissNotice(event) {
    var element = jQuery(event.target);
    var notice = element.closest('.tiny-notice');
    element.attr('disabled', 'disabled');
    jQuery.ajax({
      url: ajaxurl,
      type: 'POST',
      dataType: 'json',
      data: {
        _nonce: tinyCompress.nonce,
        action: 'tiny_dismiss_notice',
        name: notice.data('name') || notice.attr('data-name')
      },
      success: function(data) {
        if (data) {
          notice.remove();
        }
      },
      error: function() {
        element.removeAttr('disabled');
      }
    });
    return false;
  }

  function updateResizeSettings() {
    if (propOf('#tinypng_sizes_0', 'checked')) {
      jQuery('.tiny-resize-available').show();
      jQuery('.tiny-resize-unavailable').hide();
    } else {
      jQuery('.tiny-resize-available').hide();
      jQuery('.tiny-resize-unavailable').show();
    }

    var original_enabled = propOf('#tinypng_resize_original_enabled', 'checked');
    jQuery('#tinypng_resize_original_width, #tinypng_resize_original_height').each(function (i, el) {
      el.disabled = !original_enabled;
    });
  }

  function updatePreserveSettings() {
    if (propOf('#tinypng_sizes_0', 'checked')) {
      jQuery('.tiny-preserve').show();
    } else {
      jQuery('.tiny-preserve').hide();
      jQuery('#tinypng_preserve_data_creation').attr('checked', false);
      jQuery('#tinypng_preserve_data_copyright').attr('checked', false);
      jQuery('#tinypng_preserve_data_location').attr('checked', false);
    }
  }

  function updateSettings() {
    updateResizeSettings();
    updatePreserveSettings();
  }

  var adminpage = '';
  if (typeof window.adminpage !== 'undefined') {
    adminpage = window.adminpage;
  }

  var statusCheckIntervalId;

  function eventOn(event, eventSelector, callback) {
    if (typeof jQuery.fn.on === 'function') {
      jQuery(document).on(event, eventSelector, callback);
    } else {
      jQuery(eventSelector).live(event, callback);
    }
  }

  function propOf(selector, property) {
    if (typeof jQuery.fn.prop === 'function') {
      /* Added in 1.6. Before jQuery 1.6, the .attr() method sometimes took
         property values into account. */
      return jQuery(selector).prop(property);
    } else {
      return jQuery(selector).attr(property);
    }
  }

  function setPropOf(selector, property, value) {
    if (typeof jQuery.fn.prop === 'function') {
      /* Added in 1.6. Before jQuery 1.6, the .attr() method sometimes took
         property values into account. */
      jQuery(selector).prop(property, value);
    } else {
      jQuery(selector).attr(property, value);
    }
  }

  function changeEnterKeyTarget(selector, button) {
    eventOn('keyup keypress', selector, function(event) {
      var code = event.keyCode || event.which;
      if (code === 13) {
        jQuery(button).click();
        return false;
      }
    });
  }

  switch (adminpage) {
  case 'upload-php':
    eventOn('click', 'button.tiny-compress', compressImage);
    eventOn('click', 'button.tiny-mark-as-compressed', onClickButtonMarkAsCompressed);

    setPropOf('button.tiny-compress', 'disabled', null);
    
    compressImageSelection();
    watchCompressingImages();

    jQuery('<option>').val('tiny_bulk_action').text(tinyCompress.L10nBulkAction).appendTo('select[name=action]');
    jQuery('<option>').val('tiny_bulk_action').text(tinyCompress.L10nBulkAction).appendTo('select[name=action2]');
    jQuery('<option>').val('tiny_bulk_mark_compressed').text(tinyCompress.L10nBulkMarkCompressed).appendTo('select[name=action]');
    jQuery('<option>').val('tiny_bulk_mark_compressed').text(tinyCompress.L10nBulkMarkCompressed).appendTo('select[name=action2]');
    break;
  case 'post-php':
    eventOn('click', 'button.tiny-compress', compressImage);
    break;
  case 'settings_page_tinify':
    changeEnterKeyTarget('div.tiny-account-status create', '[data-tiny-action=create-key]');
    changeEnterKeyTarget('div.tiny-account-status update', '[data-tiny-action=update-key]');

    eventOn('click', '[data-tiny-action=create-key]', submitKey);
    eventOn('click', '[data-tiny-action=update-key]', submitKey);
    eventOn('click', '#change-key', toggleChangeKey);
    eventOn('click', '#cancel-change-key', toggleChangeKey);

    var target = jQuery('#tiny-account-status[data-state=pending]');
    if (target.length) {
      jQuery.get(ajaxurl + (ajaxurl.indexOf( '?' ) > 0 ? '&' : '?') + 'action=tiny_account_status', function(data) {
        target.replaceWith(data);
      });
    }

    eventOn('click', 'input[name*=tinypng_sizes], #tinypng_resize_original_enabled', function() {
      /* Unfortunately, we need some additional information to display
         the correct notice. */
      var totalSelectedSizes = jQuery('input[name*=tinypng_sizes]:checked').length;
      var compressWr2x = propOf('#tinypng_sizes_wr2x', 'checked');
      if (compressWr2x) {
        totalSelectedSizes--;
      }

      var image_count_url = ajaxurl + (ajaxurl.indexOf( '?' ) > 0 ? '&' : '?') + 'action=tiny_image_sizes_notice&image_sizes_selected=' + totalSelectedSizes;
      if (propOf('#tinypng_resize_original_enabled', 'checked') && propOf('#tinypng_sizes_0', 'checked')) {
        image_count_url += '&resize_original=true';
      }
      if (compressWr2x) {
        image_count_url += '&compress_wr2x=true';
      }
      jQuery('#tiny-image-sizes-notice').load(image_count_url);
    });

    eventOn('click', '#tinypng_auto_compress_enabled', function() {
      updateSettings();
    });

    jQuery('#tinypng_sizes_0, #tinypng_resize_original_enabled').click(updateSettings);
    updateSettings();
  }

  jQuery('.tiny-notice a.tiny-dismiss').click(dismissNotice);
  jQuery(function() {
    jQuery('.tiny-notice.is-dismissible button').unbind('click').click(dismissNotice);
  });


  function onConvertChange(e) {
    const newValue = e.target.checked;
    if (newValue) {
      jQuery('#tinypng_convert_convert_to').removeAttr('disabled');
    } else {
      jQuery('#tinypng_convert_convert_to').attr('disabled', true);
    }
  }
  jQuery('#tinypng_conversion_convert').on('change', onConvertChange);
}).call();
