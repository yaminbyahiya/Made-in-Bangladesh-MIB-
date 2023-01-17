'use strict';

(function($) {
  var woobt_timeout = null;

  $(function() {
    woobt_settings();

    // options page
    woobt_options();

    // arrange
    woobt_arrange();

    // button
    woobt_button();

    // default products
    woobt_default_products();
  });

  $(document).on('click touch', '#woobt_search_settings_btn', function(e) {
    // open search settings popup
    e.preventDefault();

    var title = $('#woobt_search_settings').attr('data-title');

    $('#woobt_search_settings').
        dialog({
          minWidth: 540,
          title: title,
          modal: true,
          dialogClass: 'wpc-dialog',
          open: function() {
            $('.ui-widget-overlay').bind('click', function() {
              $('#woobt_search_settings').dialog('close');
            });
          },
        });
  });

  $(document).on('click touch', '#woobt_search_settings_update', function(e) {
    // save search settings
    e.preventDefault();

    $('#woobt_search_settings').addClass('woobt_search_settings_updating');

    var data = {
      action: 'woobt_update_search_settings',
      limit: $('.woobt_search_limit').val(),
      sku: $('.woobt_search_sku').val(),
      id: $('.woobt_search_id').val(),
      exact: $('.woobt_search_exact').val(),
      sentence: $('.woobt_search_sentence').val(),
      same: $('.woobt_search_same').val(),
      types: $('.woobt_search_types').val(),
    };

    $.post(ajaxurl, data, function(response) {
      $('#woobt_search_settings').removeClass('woobt_search_settings_updating');
    });
  });

  $(document).on('change', 'select.woobt_change_price', function() {
    woobt_options();
  });

  $(document).on('change', 'select.woobt_atc_button', function() {
    woobt_button();
  });

  $(document).on('change', 'select.woobt_default', function() {
    woobt_default_products();
  });

  // set optional
  $(document).on('click touch', '#woobt_custom_qty', function() {
    if ($(this).is(':checked')) {
      $('.woobt_tr_show_if_custom_qty').show();
      $('.woobt_tr_hide_if_custom_qty').hide();
      $('#woobt_sync_qty').prop('checked', false);
    } else {
      $('.woobt_tr_show_if_custom_qty').hide();
      $('.woobt_tr_hide_if_custom_qty').show();
    }
  });

  // search input
  $(document).on('keyup', '#woobt_keyword', function() {
    if ($('#woobt_keyword').val() != '') {
      $('#woobt_loading').show();

      if (woobt_timeout != null) {
        clearTimeout(woobt_timeout);
      }

      woobt_timeout = setTimeout(woobt_ajax_get_data, 300);

      return false;
    }
  });

  // actions on search result items
  $(document).on('click touch', '#woobt_results li', function() {
    $(this).children('.woobt-remove').html('×');
    $('#woobt_selected ul').append($(this));
    $('#woobt_results').hide();
    $('#woobt_keyword').val('');
    woobt_get_ids();
    woobt_arrange();

    return false;
  });

  // change qty of each item
  $(document).on('keyup change click', '#woobt_selected input', function() {
    woobt_get_ids();

    return false;
  });

  // actions on selected items
  $(document).on('click touch', '#woobt_selected .woobt-remove', function() {
    $(this).parent().remove();
    woobt_get_ids();

    return false;
  });

  // hide search result box if click outside
  $(document).on('click touch', function(e) {
    if ($(e.target).closest($('#woobt_results')).length == 0) {
      $('#woobt_results').hide();
    }
  });

  function woobt_settings() {
    // hide search result box by default
    $('#woobt_results').hide();
    $('#woobt_loading').hide();

    // show or hide limit
    if ($('#woobt_custom_qty').is(':checked')) {
      $('.woobt_tr_show_if_custom_qty').show();
      $('.woobt_tr_hide_if_custom_qty').hide();
      $('#woobt_sync_qty').prop('checked', false);
    } else {
      $('.woobt_tr_show_if_custom_qty').hide();
      $('.woobt_tr_hide_if_custom_qty').show();
    }
  }

  function woobt_options() {
    if ($('select.woobt_change_price').val() == 'yes_custom') {
      $('.woobt_change_price_custom').show();
    } else {
      $('.woobt_change_price_custom').hide();
    }
  }

  function woobt_button() {
    if ($('select.woobt_atc_button').val() == 'separate') {
      $('select.woobt_show_this_item').
          val('yes').
          trigger('change').
          prop('disabled', 'disabled');
    } else {
      $('select.woobt_show_this_item').prop('disabled', false);
    }
  }

  function woobt_default_products() {
    if ($('select.woobt_default').val() != 'none') {
      $('.woobt_show_if_default_products').show();
    } else {
      $('.woobt_show_if_default_products').hide();
    }
  }

  function woobt_arrange() {
    $('#woobt_selected ul').sortable({
      handle: '.woobt-move',
      update: function(event, ui) {
        woobt_get_ids();
      },
    });
  }

  function woobt_get_ids() {
    var woobt_ids = new Array();

    $('#woobt_selected li').each(function() {
      if (!$(this).hasClass('woobt_default')) {
        woobt_ids.push($(this).attr('data-id') + '/' +
            $(this).find('.price input').val() + '/' +
            $(this).find('.qty input').val());
      }
    });

    if (woobt_ids.length > 0) {
      $('#woobt_ids').val(woobt_ids.join(','));
    } else {
      $('#woobt_ids').val('');
    }
  }

  function woobt_ajax_get_data() {
    // ajax search product
    woobt_timeout = null;

    var data = {
      action: 'woobt_get_search_results',
      woobt_keyword: $('#woobt_keyword').val(),
      woobt_id: $('#woobt_id').val(),
      woobt_ids: $('#woobt_ids').val(),
    };

    $.post(ajaxurl, data, function(response) {
      $('#woobt_results').show();
      $('#woobt_results').html(response);
      $('#woobt_loading').hide();
    });
  }
})(jQuery);