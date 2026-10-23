(function() {
  function downloadDiagnostics() {
    try {
      jQuery('#tiny-download-diagnostics').attr('disabled', true);
      const downloadURL = `${ajaxurl}?action=tiny_download_diagnostics&security=${tinyCompress.nonce}`;
      window.location.href = downloadURL;
    } finally {
      jQuery('#tiny-download-diagnostics').attr('disabled', false);
    }
  }
  jQuery('#tiny-download-diagnostics').click(downloadDiagnostics);

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

  /**
   * Creates an account or connects an API key from the account connect view,
   * then opens the page in its container's data-create-key-next or
   * data-update-key-next. A failure is shown under the field it is about.
   */
  async function submitAccountConnect(event) {
    event.preventDefault();

    const button = event.target;
    const column = button.closest('.tiny-account-connect-column');
    const spinner = column.querySelector('.spinner');
    const action = button.dataset.tinyAction === 'create-key' ? 'create' : 'update';
    const name = document.querySelector('#tinypng_api_key_name')?.value ?? '';
    const email = document.querySelector('#tinypng_api_key_email')?.value ?? '';
    const key = document.querySelector('#tinypng_api_key').value;
    let field;

    if (action === 'update') {
      field = document.querySelector('#tinypng_api_key').closest('.tiny-field');
    } else if (!name) {
      field = document.querySelector('#tinypng_api_key_name').closest('.tiny-field');
    } else {
      field = document.querySelector('#tinypng_api_key_email').closest('.tiny-field');
    }

    column.querySelectorAll('.tiny-field').forEach((f) => f.classList.remove('tiny-field-invalid'));

    if (action === 'update' && !key) {
      field.classList.add('tiny-field-invalid');
      return;
    }

    function showError(message) {
      button.disabled = false;
      spinner.classList.remove('is-active');
      if (action === 'create') {
        field.querySelector('.tiny-field-error').textContent = message;
      }
      field.classList.add('tiny-field-invalid');
    };

    button.disabled = true;
    spinner.classList.add('is-active');

    try {
      const response = await fetch(ajaxurl, {
        method: 'POST',
        body: new URLSearchParams({
          _nonce: tinyCompress.nonce,
          action: `tiny_settings_${action}_api_key`,
          key,
          name,
          email,
        }),
      });
      if (!response.ok) {
        throw new Error(response.statusText);
      }
      const status = await response.json();
      if (status.ok) {
        window.location.href = button.closest('[data-create-key-next]').dataset[`${action}KeyNext`];
      } else {
        showError(status.message);
      }
    } catch (err) {
      showError('Something went wrong, try again soon');
    }
  }

  /**
   * Opens the next onboarding step once the account is activated from the email.
   * The account status markup has status-success once the key works.
   */
  async function continueWhenActivated() {
    const separator = ajaxurl.includes('?') ? '&' : '?';
    try {
      const html = await fetch(`${ajaxurl}${separator}action=tiny_account_status`).then(r => r.text());
      const status = new DOMParser().parseFromString(html, 'text/html');
      if (status.querySelector('.status-success')) {
        window.location.href = document.querySelector('.tiny-onboarding').dataset.activatedNext;
      }
    } finally {
      setTimeout(continueWhenActivated, 5000);
    }
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

  function showFormatOptions(event) {
    document.querySelector('#tiny-format-options').hidden = !event.target.checked;
  }

  function showResizeInputs(event) {
    document.querySelector('#tiny-resize-inputs').hidden = !event.target.checked;
  }

  /**
   * Resizing, backups and metadata only apply to the original image, so their
   * sections are hidden while the original size is not compressed.
   */
  function showOriginalSettings(event) {
    document.querySelectorAll('.tiny-resize-available').forEach(function(section) {
      section.hidden = !event.target.checked;
    });
    document.querySelector('.tiny-resize-unavailable').hidden = event.target.checked;
  }

  function showAccountConnect() {
    document.querySelector('.tiny-settings-connect').hidden = false;
    document.querySelector('#tinypng_api_key').focus();
  }

  /**
   * Replaces the account status placeholder with the status from the API.
   */
  async function loadAccountStatus() {
    const placeholder = document.querySelector('#tiny-account-status[data-state=pending]');
    if (!placeholder) {
      return;
    }

    const separator = ajaxurl.includes('?') ? '&' : '?';
    try {
      const response = await fetch(`${ajaxurl}${separator}action=tiny_account_status`);
      placeholder.outerHTML = await response.text();
    } catch (err) {
      console.error(err);
    }
  }

  /**
   * Updates the credits per image under the sizes and in the sidebar.
   */
  async function refreshCreditSummary() {
    const totalSelectedSizes = document.querySelectorAll('input[name*=tinypng_sizes]:checked').length;
    const compressWr2x = document.querySelector('#tinypng_sizes_wr2x')?.checked ?? false;
    const compressOriginal = document.querySelector('#tinypng_sizes_0')?.checked ?? false;
    const params = new URLSearchParams({
      action: 'tiny_image_sizes_notice',
      image_sizes_selected: compressWr2x ? totalSelectedSizes - 1 : totalSelectedSizes,
      _ajax_nonce: tinyCompress.nonce,
    });
    if (compressOriginal && document.querySelector('#tinypng_resize_original_enabled').checked) {
      params.set('resize_original', 'true');
    }
    if (compressWr2x) {
      params.set('compress_wr2x', 'true');
    }
    if (document.querySelector('#tiny-convert').checked) {
      params.set('convert', 'true');
    }

    const separator = ajaxurl.includes('?') ? '&' : '?';
    try {
      const html = await fetch(`${ajaxurl}${separator}${params}`).then(r => r.text());
      const summary = new DOMParser().parseFromString(html, 'text/html');
      ['#tiny-image-sizes-notice', '#tiny-credits-per-image'].forEach(function(selector) {
        document.querySelector(selector).innerHTML = summary.querySelector(selector).innerHTML;
      });
    } catch (err) {
      console.error(err);
    }
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
  case 'settings_page_tiny-onboarding':
    changeEnterKeyTarget('#tinypng_api_key_name, #tinypng_api_key_email', '[data-tiny-action=create-key]');
    changeEnterKeyTarget('#tinypng_api_key', '[data-tiny-action=update-key]');

    eventOn('click', '[data-tiny-action=create-key]', submitAccountConnect);
    eventOn('click', '[data-tiny-action=update-key]', submitAccountConnect);
    eventOn('change', '#tiny-convert', showFormatOptions);

    if (document.querySelector('[data-activated-next]')) {
      setTimeout(continueWhenActivated, 5000);
    }
    break;
  case 'settings_page_tinify':
    changeEnterKeyTarget('#tinypng_api_key_name, #tinypng_api_key_email', '[data-tiny-action=create-key]');
    changeEnterKeyTarget('#tinypng_api_key', '[data-tiny-action=update-key]');
    eventOn('click', '[data-tiny-action=create-key]', submitAccountConnect);
    eventOn('click', '[data-tiny-action=update-key]', submitAccountConnect);
    eventOn('click', '[data-tiny-action=change-account]', showAccountConnect);
    loadAccountStatus();
    eventOn('change', '#tiny-convert', showFormatOptions);
    eventOn('change', '#tinypng_resize_original_enabled', showResizeInputs);
    eventOn('change', '#tinypng_sizes_0', showOriginalSettings);
    eventOn('change', 'input[name*=tinypng_sizes], #tinypng_resize_original_enabled, #tiny-convert', refreshCreditSummary);
  }

  jQuery('.tiny-notice a.tiny-dismiss').click(dismissNotice);
  jQuery(function() {
    jQuery('.tiny-notice.is-dismissible button').unbind('click').click(dismissNotice);
  });
}).call();
