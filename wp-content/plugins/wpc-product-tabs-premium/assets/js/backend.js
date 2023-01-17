'use strict';

(function($) {
  $(function() {
    woost_arrange();
    woost_overwrite();
  });

  $(document).on('click touch', '.woost-tab-new', function() {
    var woost_new = $(this);
    var woost_type = $('.woost-tab-type').val();
    var woost_editor = 'woost_editor_' + Date.now().toString();

    if ((
        (
            woost_type == 'description'
        ) && $('.woost-tabs .woost-tab-description').length
    ) || (
        (
            woost_type == 'additional_information'
        ) && $('.woost-tabs .woost-tab-additional_information').length
    ) || (
        (
            woost_type == 'reviews'
        ) && $('.woost-tabs .woost-tab-reviews').length
    )) {
      alert('Already has this tab!');
      return false;
    }

    woost_new.prop('disabled', true);

    var data = {
      action: 'woost_add_tab',
      editor: woost_editor,
      type: woost_type,
    };

    $.post(ajaxurl, data, function(response) {
      $('.woost-tabs').append(response);

      if (woost_type == 'custom') {
        wp.editor.initialize(woost_editor, {
          mediaButtons: true,
          tinymce: {
            wpautop: true,
            plugins: 'charmap colorpicker compat3x directionality fullscreen hr image lists media paste tabfocus textcolor wordpress wpautoresize wpdialogs wpeditimage wpemoji wpgallery wplink wptextpattern wpview',
            toolbar1: 'formatselect bold italic | bullist numlist | blockquote | alignleft aligncenter alignright | link unlink | wp_more | spellchecker',
          },
          quicktags: true,
        });
      }

      woost_arrange();
      woost_new.prop('disabled', false);
    });
  });

  $(document).on('click touch', '.woost-tab-remove', function() {
    var r = confirm('Do you want to remove this tab? This action cannot undo.');

    if (r == true) {
      $(this).closest('.woost-tab').remove();
    }
  });

  $(document).on('click touch', '.woost-tab-header', function(e) {
    if (($(e.target).closest('.woost-tab-duplicate').length === 0) &&
        ($(e.target).closest('.woost-tab-remove').length === 0)) {
      $(this).closest('.woost-tab').toggleClass('active');
    }
  });

  $(document).on('keyup change keypress', '.woost-tab-title-input', function() {
    let $this = $(this), value = $this.val();
    $this.closest('.woost-tab').find('.woost-tab-title').text(value);
  });

  $(document).on('change', 'input[name="woost_overwrite"]', function() {
    woost_overwrite();
  });

  function woost_arrange() {
    $('.woost-tabs').sortable({
      handle: '.woost-tab-move',
    });
  }

  function woost_overwrite() {
    if ($('#woost_settings').length) {
      var val = $('input[name="woost_overwrite"]:checked').val();

      if (val === 'overwrite' || val === 'prepend' || val === 'append') {
        $('.woost-tabs').show();
        $('.woost-tabs-new').show();
      } else {
        $('.woost-tabs').hide();
        $('.woost-tabs-new').hide();
      }
    }
  }
})(jQuery);