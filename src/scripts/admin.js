(function() {

  function compress_image(event) {
    var element = jQuery(event.target)
    element.attr('disabled', 'disabled')
    element.closest('td').find('.spinner').removeClass('hidden')
    jQuery.ajax({
      url: ajaxurl,
      type: "POST",
      data: {
        _nonce: tinyCompress.nonce,
        action: 'tiny_compress_image',
        id: element.data('id') || element.attr('data-id')
      },
      success: function(data) {
        element.closest('td').html(data)
      },
      error: function() {
        element.removeAttr('disabled')
        element.closest('td').find('.spinner').addClass('hidden')
      }
    })
  }

  function dismiss_notice(event) {
    var element = jQuery(event.target)
    element.attr('disabled', 'disabled')
    jQuery.ajax({
      url: ajaxurl,
      type: "POST",
      dataType: "json",
      data: {
        _nonce: tinyCompress.nonce,
        action: 'tiny_dismiss_notice',
        name: element.data('name') || element.attr('data-name')
      },
      success: function(data) {
        if (data) {
          element.closest('div').remove()
        }
      },
      error: function() {
        element.removeAttr('disabled')
      }
    })
    return false
  }

  function bulk_compress_callback(error, data, items, i) {
      var row = jQuery(jQuery('#tiny-images').children("div")[i])

      if (data.thumbnail) {
        var img = jQuery('<img class="pinkynail">')
        img.attr("src", data.thumbnail)
        row.prepend(img)
      }
      if (error) {
        row.find('.bar').addClass('failed')
        row.find('.percent').html(tinyCompress.L10nError)
        row.find('.percent').attr("title", error.toString())
      } else if (data.error) {
        row.find('.bar').addClass('failed')
        row.find('.percent').html(tinyCompress.L10nInternalError)
        row.find('.percent').attr("title", data.error)
      } else if (data.failed > 0) {
        row.find('.bar').addClass('failed')
        row.find('.bar').css('width', '100%')
        row.find('.percent').html(
            data.failed + " " + tinyCompress.L10nOutOf + " " + (data.success + data.failed))
      } else {
        row.find('.bar').css('width', '100%')
        row.find('.percent').html(
            data.success + " " + tinyCompress.L10nOutOf + " " + (data.success + data.failed))
      }
      if (data.status) {
        jQuery('#tiny-status span').html(data.status)
      }
      if (items[++i]) {
        bulk_compress_item(items, i)
      } else {
        var message = jQuery('<div class="updated"><p></p></div>');
        message.find('p').html(tinyCompress.L10nAllDone)
        message.insertAfter(jQuery("#tiny-bulk-compress h2"))
      }
  }

  function bulk_compress_item(items, i) {
    var item = items[i]
    var row = jQuery(jQuery('#tiny-images').children("div")[i])
    row.find('.percent').html(tinyCompress.L10nCompressing)
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
      success: function(data) { bulk_compress_callback(null, data, items, i)},
      error: function(xhr, textStatus, errorThrown) { bulk_compress_callback(errorThrown, {}, items, i) }
    })
    jQuery('#tiny-progress span').html(i + 1)
  }

  function bulk_compress(items) {
    var list = jQuery('#tiny-images')
    for (var i = 0; i < items.length; i++) {
      row = jQuery('<div class="media-item"><div class="progress"><div class="percent"></div><div class="bar"></div></div><div class="filename"></div></div>')
      row.find('.percent').html(tinyCompress.L10nWaiting)
      row.find('.filename').html(items[i].post_title)
      list.append(row)
    }
    bulk_compress_item(items, 0)
  }

  if (adminpage === "upload-php") {
    if (typeof jQuery.fn.on === "function") {
      jQuery('table').on('click', 'button.tiny-compress', compress_image)
    } else {
      jQuery('button.tiny-compress').live('click', compress_image)
    }

    if (typeof jQuery.fn.prop === "function") {
      jQuery('button.tiny-compress').prop('disabled', null)
    } else {
      jQuery('button.tiny-compress').attr('disabled', null)
    }

    jQuery('<option>').val('tiny_bulk_compress').text(tinyCompress.L10nBulkAction).appendTo('select[name="action"]')
    jQuery('<option>').val('tiny_bulk_compress').text(tinyCompress.L10nBulkAction).appendTo('select[name="action2"]')
  }

  if (adminpage === "options-media-php") {
    jQuery('#tiny-compress-status').load(ajaxurl + '?action=tiny_compress_status')
  }

  jQuery('a.tiny-dismiss').click(dismiss_notice)

  window.tinyBulkCompress = bulk_compress
}).call()
