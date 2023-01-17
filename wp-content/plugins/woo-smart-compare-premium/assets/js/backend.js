'use strict';

(function($) {
  $(function() {
    woosc_button_icon();
    woosc_button_action();

    $('.woosc_color_picker').wpColorPicker();
    $('.woosc_icon_picker').fontIconPicker();

    $('.woosc-fields').sortable({
      handle: '.label',
    });

    $('.woosc-attributes').sortable({
      handle: '.label',
    });
  });

  $(document).on('change', 'select.woosc_button_action', function() {
    woosc_button_action();
  });

  $(document).on('change', 'select.woosc_button_icon', function() {
    woosc_button_icon();
  });

  function woosc_button_icon() {
    var button_icon = $('select.woosc_button_icon').val();

    if (button_icon !== 'no') {
      $('.woosc-show-if-button-icon').show();
    } else {
      $('.woosc-show-if-button-icon').hide();
    }
  }

  function woosc_button_action() {
    var action = $('select.woosc_button_action').val();

    $('.woosc_button_action_hide').hide();
    $('.woosc_button_action_' + action).show();
  }
})(jQuery);