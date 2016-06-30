(function() {
  function compressImage(event) {
    var element = jQuery(event.target)
    var container = element.closest('.tiny-ajax-container')
    element.attr('disabled', 'disabled')
    container.find('.spinner').removeClass('hidden')
    container.find('span.dashicons').addClass('hidden')
    jQuery.ajax({
      url: ajaxurl,
      type: "POST",
      data: {
        _nonce: tinyCompress.nonce,
        action: 'tiny_compress_image_from_library',
        id: element.data('id') || element.attr('data-id')
      },
      success: function(data) {
        container.html(data)
      },
      error: function() {
        element.removeAttr('disabled')
        container.find('.spinner').addClass('hidden')
      }
    })
  }

  function updateApiKey(event) {
    event.preventDefault()
    jQuery(event.target).attr({disabled: true})
    var parent = jQuery(event.target).closest("div")

    var key = parent.find("#tinypng_api_key, #tinypng_api_key_modal").val()
    jQuery("#tinypng_api_key, #tinypng_api_key_modal").val(key) /* Replace all key input fields. */

    jQuery.ajax({
      url: ajaxurl,
      type: "POST",
      data: {
        _nonce: tinyCompress.nonce,
        action: 'tiny_settings_update_api_key',
        key: key,
      },

      success: function(json) {
        jQuery(event.target).attr({disabled: false})
        var status = JSON.parse(json)
        if (status.ok) {
          tb_remove()
          parent.find('.tiny-update-account-message').text("").hide()
          jQuery('#tiny-compress-status').load(ajaxurl + '?action=tiny_compress_status')
        } else {
          parent.find('.tiny-update-account-message').text(status.message).show()
        }
      },

      error: function() {
        jQuery(event.target).attr({disabled: false})
        parent.find('.tiny-update-account-message').text("Something went wrong, try again soon").show()
      }
    })

    return false
  }

  function createApiKey(event) {
    event.preventDefault()
    jQuery(event.target).attr({disabled: true})
    var parent = jQuery(event.target).closest("div")

    var name = jQuery("#tinypng_api_key_name").val()
    var email = jQuery("#tinypng_api_key_email").val()

    jQuery.ajax({
      url: ajaxurl,
      type: "POST",
      data: {
        _nonce: tinyCompress.nonce,
        action: 'tiny_settings_create_api_key',
        name: name,
        email: email,
      },

      success: function(json) {
        jQuery(event.target).attr({disabled: false})
        var status = JSON.parse(json)
        if (status.ok) {
          parent.find('.tiny-create-account-message').text("").hide()
          jQuery('#tiny-compress-status').load(ajaxurl + '?action=tiny_compress_status')
          jQuery("#tinypng_api_key").val(status.key)
        } else {
          parent.find('.tiny-create-account-message').text(status.message).show()
        }
      },

      error: function() {
        jQuery(event.target).attr({disabled: false})
        parent.find('.tiny-create-account-message').text("Something went wrong, try again soon").show()
      }
    })
    return false
  }

  function dismissNotice(event) {
    var element = jQuery(event.target)
    var notice = element.closest(".tiny-notice")
    element.attr('disabled', 'disabled')
    jQuery.ajax({
      url: ajaxurl,
      type: "POST",
      dataType: "json",
      data: {
        _nonce: tinyCompress.nonce,
        action: 'tiny_dismiss_notice',
        name: notice.data('name') || notice.attr('data-name')
      },
      success: function(data) {
        if (data) {
          notice.remove()
        }
      },
      error: function() {
        element.removeAttr('disabled')
      }
    })
    return false
  }

  function updateResizeSettings() {
    if (jQuery('#tinypng_sizes_0').prop('checked')) {
      jQuery('.tiny-resize-available').show()
      jQuery('.tiny-resize-unavailable').hide()
    } else {
      jQuery('.tiny-resize-available').hide()
      jQuery('.tiny-resize-unavailable').show()
    }

    var original_enabled = jQuery('#tinypng_resize_original_enabled').prop('checked')
    jQuery('#tinypng_resize_original_width, #tinypng_resize_original_height').each(function (i, el) {
      el.disabled = !original_enabled
    })
  }

  function updatePreserveSettings() {
    if (jQuery('#tinypng_sizes_0').prop('checked')) {
      jQuery('.tiny-preserve').show()
    } else {
      jQuery('.tiny-preserve').hide()
      jQuery('#tinypng_preserve_data_creation').attr('checked', false)
      jQuery('#tinypng_preserve_data_copyright').attr('checked', false)
      jQuery('#tinypng_preserve_data_location').attr('checked', false)
    }
  }

  function updateSettings() {
    updateResizeSettings()
    updatePreserveSettings()
  }

  var adminpage = ""
  if (typeof window.adminpage !== "undefined") {
    adminpage = window.adminpage
  }

  function eventOn(event, eventSelector, callback) {
    if (typeof jQuery.fn.on === "function") {
      jQuery(document).on(event, eventSelector, callback)
    } else {
      jQuery(eventSelector).live(event, callback)
    }
  }

  function changeEnterKeyTarget(selector, button) {
    eventOn('keyup keypress', selector, function(event) {
      var code = event.keyCode || event.which
      if (code == 13) {
        jQuery(button).click()
        return false
      }
    })
  }

  if (adminpage === "upload-php") {

    eventOn('click', 'button.tiny-compress', compressImage)

    if (typeof jQuery.fn.prop === "function") {
      jQuery('button.tiny-compress').prop('disabled', null)
    } else {
      jQuery('button.tiny-compress').attr('disabled', null)
    }

    jQuery('<option>').val('tiny_bulk_action').text(tinyCompress.L10nBulkAction).appendTo('select[name="action"]')
    jQuery('<option>').val('tiny_bulk_action').text(tinyCompress.L10nBulkAction).appendTo('select[name="action2"]')

  } else if (adminpage === "post-php") {

    eventOn('click', 'button.tiny-compress', compressImage)

  } else if (adminpage === "options-media-php") {

    changeEnterKeyTarget('.tiny-update-account-step1', '.tiny-account-create-key')
    changeEnterKeyTarget('.tiny-update-account-step2', '.tiny-account-update-key')

    eventOn('click', 'button.tiny-account-create-key', createApiKey)
    eventOn('click', 'button.tiny-account-update-key', updateApiKey)

    jQuery('#tiny-compress-status[data-state=pending]').load(ajaxurl + '?action=tiny_compress_status')
    jQuery('#tiny-compress-savings').load(ajaxurl + '?action=tiny_compress_savings')

    jQuery('input[name*="tinypng_sizes"], input#tinypng_resize_original_enabled').on("click", function() {
      // Unfortunately, we need some additional information to display the correct notice.
      totalSelectedSizes = jQuery('input[name*="tinypng_sizes"]:checked').length
      var image_count_url = ajaxurl + '?action=tiny_image_sizes_notice&image_sizes_selected=' + totalSelectedSizes
      if (jQuery('input#tinypng_resize_original_enabled').prop('checked') && jQuery('input#tinypng_sizes_0').prop('checked')) {
        image_count_url += '&resize_original=true'
      }
      jQuery('#tiny-image-sizes-notice').load(image_count_url)
    })

    jQuery('#tinypng_sizes_0, #tinypng_resize_original_enabled').click(updateSettings)
    updateSettings()

  }

  jQuery('.tiny-notice a.tiny-dismiss').click(dismissNotice)
  jQuery(function() {
    jQuery('.tiny-notice.is-dismissible button').unbind('click').click(dismissNotice)
  })

}).call()
