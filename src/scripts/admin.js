(function() {

  function compress_image(event) {
    var element = jQuery(event.target);
    element.attr('disabled', 'disabled');
    element.closest('td').find('.spinner').css('display', 'inline')
    jQuery.post(
      ajaxurl, {
        'action': 'tiny_compress_image',
        'id': element.data('id') || element.attr('data-id')
      }, function (response) {
        element.closest('td').html(response);
      }
    );
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

    jQuery('<option>').val('tiny_bulk_compress').text(tinyCompressL10n.bulkAction).appendTo('select[name="action"]')
    jQuery('<option>').val('tiny_bulk_compress').text(tinyCompressL10n.bulkAction).appendTo('select[name="action2"]')
  }

  if (adminpage === "options-media-php") {
    jQuery("#tiny-compress-status").load(ajaxurl + "?action=tiny_compress_status")
  }
}).call();
