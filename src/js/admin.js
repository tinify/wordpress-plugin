(function () {
  async function restoreBackup(attachmentId) {
    return jQuery.ajax({
      url: ajaxurl,
      type: 'POST',
      data: {
        _nonce: tinyCompress.nonce,
        action: 'tiny_restore_backup',
        id: attachmentId,
      },
    });
  }

  jQuery(document).on('click', 'a[data-dialog-id]', function (e) {
    e.preventDefault();
    const dialog = document.getElementById(jQuery(e.currentTarget).data('dialog-id'));
    if (dialog) {
      dialog.showModal();
    }
  });

  jQuery(document).on('click', '[data-dialog-close]', function (e) {
    e.currentTarget.closest('dialog').close();
  });

  jQuery(document).on('click', 'dialog.tiny-dialog', function (e) {
    const dialog = e.currentTarget;
    if (e.target !== dialog) {
      return;
    }
    const rect = dialog.getBoundingClientRect();
    const inside = e.clientX >= rect.left && e.clientX <= rect.right &&
      e.clientY >= rect.top && e.clientY <= rect.bottom;
    if (!inside) {
      dialog.close();
    }
  });

  jQuery(document).on('click', '.tiny-restore-dialog button[value="submit"]', async function (e) {
    const confirmButton = e.currentTarget;
    const dialog = confirmButton.closest('dialog');
    const detailsDialog = dialog.parentElement.closest('dialog');
    const container = dialog.closest('.tiny-ajax-container');
    const spinner = dialog.querySelector('.spinner');

    confirmButton.disabled = true;
    if (spinner) {
      spinner.style.visibility = 'visible';
    }
    try {
      const result = await restoreBackup(container.dataset.tinyMediaId);
      dialog.close();
      if (detailsDialog) {
        detailsDialog.close();
      }
      container.innerHTML = result;
    } catch (err) {
      const errorEl = dialog.querySelector('.tiny-dialog-error');
      if (errorEl) {
        errorEl.textContent = err.responseText || 'Failed to restore backup.';
        errorEl.hidden = false;
      }
      confirmButton.disabled = false;
      if (spinner) {
        spinner.style.visibility = 'hidden';
      }
    }
  });

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
   * Posts the account connect request for the clicked button and opens next
   * when it worked. Returns the error message when it did not.
   */
  async function postAccountConnect(button, params, next) {
    const spinner = button.closest('.tiny-actions').querySelector('.spinner');
    button.disabled = true;
    spinner.classList.add('is-active');

    let message;
    try {
      const response = await fetch(ajaxurl, {
        method: 'POST',
        body: new URLSearchParams({ _nonce: tinyCompress.nonce, ...params }),
      });
      if (!response.ok) {
        throw new Error(response.statusText);
      }
      const status = await response.json();
      if (status.ok) {
        window.location.href = next;
        return null;
      }
      message = status.message;
    } catch (err) {
      message = 'Something went wrong, try again soon';
    }

    button.disabled = false;
    spinner.classList.remove('is-active');
    return message;
  }

  /**
   * Creates an account and opens data-create-key-next. A failure is shown
   * under the name field when it is empty, otherwise under the email field.
   */
  async function createAccount(event) {
    event.preventDefault();

    const button = event.target;
    const nameInput = document.querySelector('#tinypng_api_key_name');
    const emailInput = document.querySelector('#tinypng_api_key_email');
    const nameField = nameInput.closest('.tiny-field');
    const emailField = emailInput.closest('.tiny-field');

    nameField.classList.remove('tiny-field-invalid');
    emailField.classList.remove('tiny-field-invalid');

    const message = await postAccountConnect(button, {
      action: 'tiny_settings_create_api_key',
      name: nameInput.value,
      email: emailInput.value,
    }, button.closest('[data-create-key-next]').dataset.createKeyNext);

    if (message) {
      const field = nameInput.value ? emailField : nameField;
      field.querySelector('.tiny-field-error').textContent = message;
      field.classList.add('tiny-field-invalid');
    }
  }

  /**
   * Connects the API key in the key field and opens data-update-key-next.
   */
  async function connectApiKey(event) {
    event.preventDefault();

    const button = event.target;
    const keyInput = document.querySelector('#tinypng_api_key');
    const field = keyInput.closest('.tiny-field');

    field.classList.remove('tiny-field-invalid');
    if (!keyInput.value) {
      field.classList.add('tiny-field-invalid');
      return;
    }

    const message = await postAccountConnect(button, {
      action: 'tiny_settings_update_api_key',
      key: keyInput.value,
    }, button.closest('[data-update-key-next]').dataset.updateKeyNext);

    if (message) {
      field.classList.add('tiny-field-invalid');
    }
  }

  /**
   * Disconnects the account and opens data-update-key-next. The server
   * deletes the API key when it gets an empty one.
   */
  async function disconnectApiKey(event) {
    event.preventDefault();

    const button = event.target;
    const message = await postAccountConnect(button, {
      action: 'tiny_settings_update_api_key',
      key: '',
    }, button.closest('[data-update-key-next]').dataset.updateKeyNext);

    if (message) {
      window.alert(message);
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

    eventOn('click', '[data-tiny-action=create-key]', createAccount);
    eventOn('click', '[data-tiny-action=update-key]', connectApiKey);
    eventOn('change', '#tiny-convert', showFormatOptions);

    if (document.querySelector('[data-activated-next]')) {
      setTimeout(continueWhenActivated, 5000);
    }
    break;
  case 'settings_page_tinify':
    changeEnterKeyTarget('#tinypng_api_key_name, #tinypng_api_key_email', '[data-tiny-action=create-key]');
    changeEnterKeyTarget('#tinypng_api_key', '[data-tiny-action=update-key]');
    eventOn('click', '[data-tiny-action=create-key]', createAccount);
    eventOn('click', '[data-tiny-action=update-key]', connectApiKey);
    eventOn('click', '[data-tiny-action=clear-key]', disconnectApiKey);
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
