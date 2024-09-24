(function($) {
  'use strict';

  $(function() {
    // ready, maybe need to reload dates
    if (wpced_vars.reload_dates) {
      let ids = [];

      $('.wpced').each(function() {
        let id = $(this).attr('data-id');

        if (typeof id !== typeof undefined && id !== false) {
          ids.push(id);
        }
      });

      if (ids.length > 0) {
        // request reloaded dates
        var data = {
          action: 'wpced_reload_dates', ids: ids, nonce: wpced_vars.nonce,
        };

        $.post(wpced_vars.ajax_url, data, function(response) {
          if (!$.isEmptyObject(response)) {
            $.each(response, function(key, value) {
              $('.' + key).each(function() {
                if (!$(this).closest('.order_item').length) {
                  $(this).replaceWith(value);
                }
              });
            });
          }
        });
      }
    }
  });

  $(document).on('found_variation', function(e, t) {
    var pid = $(e['target']).closest('.variations_form').data('product_id');

    if (t.wpced_enable === 'disable') {
      $('.wpced-' + pid).html('');
    } else {
      if (t.wpced_date !== undefined) {
        $('.wpced-' + pid).replaceWith(wpced_decode_entities(t.wpced_date));
      } else {
        var variable_date = $('.wpced-variable-' + pid).data('wpced');

        if (variable_date !== undefined) {
          $('.wpced-' + pid).replaceWith(wpced_decode_entities(variable_date));
        }
      }
    }

    $(document.body).trigger('wpced_found_variation');
  });

  $(document).on('reset_data', function(e) {
    var pid = $(e['target']).closest('.variations_form').data('product_id');
    var variable_date = $('.wpced-variable-' + pid).data('wpced');

    if (variable_date !== undefined) {
      $('.wpced-' + pid).replaceWith(wpced_decode_entities(variable_date));
    }

    $(document.body).trigger('wpced_reset_data');
  });

  $(document).on('updated_shipping_method', function() {
    $(document.body).trigger('wc_update_cart');
  });
})(jQuery);

function wpced_decode_entities(encodedString) {
  var textArea = document.createElement('textarea');
  textArea.innerHTML = encodedString;

  return textArea.value;
}