(function() {
  function check_wp_version(version) {
    return parseFloat(tinyCompress.wpVersion) >= version
  }

  function compress_image(event) {
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
        action: 'tiny_compress_image',
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

  function update_api_key() {
  //   jQuery('.tinypng-api-key-message.invalid-key').hide()
  //   jQuery('.tinypng-api-key-message.save-error').hide()
  //   var key = jQuery("#tinypng_api_key").val()
  //
  //   jQuery.ajax({
  //     url: ajaxurl,
  //     type: "POST",
  //     data: {
  //       _nonce: tinyCompress.nonce,
  //       action: 'tiny_update_api_key',
  //       key: key
  //     },
  //     success: function(json) {
  //       var data = JSON.parse(json)
  //       if(data.valid) {
  //         location.reload()
  //       } else {
  //           jQuery('.tinypng-api-key-message.invalid-key').show()
  //         }
  //     },
  //     error: function() {
  //       jQuery('.tinypng-api-key-message.save-error').show()
  //     }
  //   })
  //   return false
  }

  function create_api_key() {
    jQuery('.tinypng-api-key-message.success').hide()
    jQuery('.tinypng-api-key-message.already-registered').hide()
    jQuery('.tinypng-api-key-message.error').hide()
    jQuery('.tinypng-api-key-message.invalid-form').hide()
    var name = jQuery("#tinypng_api_key_name").val()
    var email = jQuery("#tinypng_api_key_email").val()
    var identifier = "WordPress plugin for " + jQuery("#tinypng_api_key_identifier").val()
    var link = jQuery(location).attr('href')
    if (name == '' || email == '') {
      jQuery('.tinypng-api-key-message.invalid-form').show()
      return false
    }
    jQuery.ajax({
      url: ajaxurl,
      type: "POST",
      data: {
        _nonce: tinyCompress.nonce,
        action: 'tiny_create_api_key',
        name: name,
        email: email,
        identifier: identifier,
        link: link,
      },
      success: function(json) {
        var data = JSON.parse(json)

        if (data.created){
          jQuery('.tinypng-api-key-message.success').show()
        } else if (data.exists) {
           jQuery('.tinypng-api-key-message.already-registered').show()
         } else {
            jQuery('.tinypng-api-key-message.error').show()
            jQuery('.tinypng-error-message').text(data.message)
         }
      },
      error: function() {
        jQuery('.tinypng-api-key-message.error').show()
      }
    })
    return false
  }

  function dismiss_notice(event) {
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

  function update_resize_settings() {
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

  function update_preserve_settings() {
    if (jQuery('#tinypng_sizes_0').prop('checked')) {
      jQuery('.tiny-preserve').show()
    } else {
      jQuery('.tiny-preserve').hide()
      jQuery('#tinypng_preserve_data_creation').attr('checked', false)
      jQuery('#tinypng_preserve_data_copyright').attr('checked', false)
      jQuery('#tinypng_preserve_data_location').attr('checked', false)
    }
  }

  function update_settings() {
    update_resize_settings()
    update_preserve_settings()
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

  function changeEnterKeyTarget(forDiv, toButton) {
    jQuery(forDiv).bind("keyup keypress", function(e) {
    var code = e.keyCode || e.which
    if (code == 13) {
      jQuery(toButton).click()
      return false
    }
    })
  }

  if (adminpage === "upload-php") {
    eventOn('click', 'button.tiny-compress', compress_image)

    if (typeof jQuery.fn.prop === "function") {
      jQuery('button.tiny-compress').prop('disabled', null)
    } else {
      jQuery('button.tiny-compress').attr('disabled', null)
    }

    jQuery('<option>').val('tiny_bulk_optimization').text(tinyCompress.L10nBulkAction).appendTo('select[name="action"]')
    jQuery('<option>').val('tiny_bulk_optimization').text(tinyCompress.L10nBulkAction).appendTo('select[name="action2"]')
  } else if (adminpage === "post-php") {
    eventOn('click', 'button.tiny-compress', compress_image)
  } else if (adminpage === "options-media-php") {
    changeEnterKeyTarget('.tiny-update-account-step1', '.tiny-account-create-key')
    changeEnterKeyTarget('.tiny-update-account-step2', '.tiny-account-save-key')
    eventOn('click', 'button.tiny-account-create-key', create_api_key)
    eventOn('click', 'button.tiny-account-save-key', update_api_key)
    jQuery('#tiny-compress-status').load(ajaxurl + '?action=tiny_compress_status')
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

    jQuery('#tinypng_sizes_0, #tinypng_resize_original_enabled').click(update_settings)
    update_settings()
  }

  jQuery('.tiny-notice a.tiny-dismiss').click(dismiss_notice)
  jQuery(function() {
    jQuery('.tiny-notice.is-dismissible button').unbind('click').click(dismiss_notice)
  })
}).call()
