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

  function save_api_key() {
    jQuery('.api-error').hide()
    var key = jQuery(tinypng_api_key_modal).val()

    jQuery.ajax({
      url: ajaxurl,
      type: "POST",
      data: {
        _nonce: tinyCompress.nonce,
        action: 'tiny_save_api_key',
        key: key
      },
      success: function(data) {
        console.log(data)
        if (data == "valid0")
          location.reload();
        else {
          jQuery('.api-error').show()
        }
      },
      error: function() {
        console.log("Failure")
      }
    })
  }

  function create_api_key() {
    var name = jQuery(tinypng_api_key_name).val()
    var mail = jQuery(tinypng_api_key_mail).val()
    var redirect = jQuery(location).attr('href');
    var alias = "WordPress plugin for " + jQuery(tinypng_api_key_alias).val()
    jQuery.ajax({
      url: ajaxurl,
      type: "POST",
      data: {
        _nonce: tinyCompress.nonce,
        action: 'tiny_create_api_key',
        name: name,
        mail: mail,
        redirect: redirect,
        alias: alias
      },
      success: function(data) {
        location.reload();
      },
      error: function() {
        console.log("Failure")
      }
    })
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

  function eventOn(parentSelector, event, eventSelector, callback) {
    if (typeof jQuery.fn.on === "function") {
      jQuery(parentSelector).on(event, eventSelector, callback)
    } else {
      jQuery(eventSelector).live(event, callback)
    }
  }

  if (adminpage === "upload-php") {
    eventOn('table', 'click', 'button.tiny-compress', compress_image)

    if (typeof jQuery.fn.prop === "function") {
      jQuery('button.tiny-compress').prop('disabled', null)
    } else {
      jQuery('button.tiny-compress').attr('disabled', null)
    }

    jQuery('<option>').val('tiny_bulk_optimization').text(tinyCompress.L10nBulkAction).appendTo('select[name="action"]')
    jQuery('<option>').val('tiny_bulk_optimization').text(tinyCompress.L10nBulkAction).appendTo('select[name="action2"]')
  } else if (adminpage === "post-php") {
    eventOn('div.postbox-container div.tiny-compress-images', 'click', 'button.tiny-compress', compress_image)
  } else if (adminpage === "options-media-php") {
    eventOn('div', 'click', 'button.tinypng-create-api-key', create_api_key)
    eventOn('div', 'click', 'button.tinypng-save-api-key', save_api_key)
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
