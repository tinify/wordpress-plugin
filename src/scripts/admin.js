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
  if (jQuery.fn.on) {
    jQuery('table').on('click', 'button.tinypng-compress', compress_image);
  } else {
    jQuery('button.tinypng-compress').live('click', compress_image);
  }
  jQuery('button.tinypng-compress').attr('disabled', null)

  jQuery('<option>').val('tinypng_bulk_compress').text(tinypngImageCompressL10n.bulkAction).appendTo('select[name="action"]');
  jQuery('<option>').val('tinypng_bulk_compress').text(tinypngImageCompressL10n.bulkAction).appendTo('select[name="action2"]');
}).call();

