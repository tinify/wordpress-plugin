(function () {
  var compress_image = function(event) {
    var element = jQuery(event.target);
    element.attr('disabled', 'disabled');
    element.closest('td').find('.spinner').css('display', 'inline');
    jQuery.post(
      ajaxurl, {
        'action': 'tinypng_compress_image',
        'id': element.data('id') || element.attr('data-id')
      }, function (response) {
        element.closest('td').html(response);
      }
    );
  }
  if (typeof jQuery.fn.on === "function") {
    jQuery('table').on('click', 'button.tinypng-compress', compress_image);
  } else {
    jQuery('button.tinypng-compress').live('click', compress_image);
  }

  if (typeof jQuery.fn.prop === "function") {
    jQuery('button.tinypng-compress').prop('disabled', null)
  } else {
    jQuery('button.tinypng-compress').attr('disabled', null)
  }

  jQuery('<option>').val('tinypng_bulk_compress').text(tinypngImageCompressL10n.bulkAction).appendTo('select[name="action"]');
  jQuery('<option>').val('tinypng_bulk_compress').text(tinypngImageCompressL10n.bulkAction).appendTo('select[name="action2"]');
}).call();
