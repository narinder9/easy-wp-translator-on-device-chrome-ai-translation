/******/ (() => { // webpackBootstrap
/******/ 	"use strict";

;// ./assets/js/src/lib/confirmation-modal.js
/**
 * @package EasyWPTranslator
 */

var languagesList = jQuery('.post_lang_choice');

// Dialog box for alerting the user about a risky changing.
var initializeConfirmationModal = function initializeConfirmationModal() {
  // We can't use underscore or lodash in this common code because it depends of the context classic or block editor.
  // Classic editor underscore is loaded, Block editor lodash is loaded.
  var __ = wp.i18n.__;

  // Create dialog container.
  var dialogContainer = jQuery('<div/>', {
    id: 'ewt-dialog',
    style: 'display:none;'
  }).text(__('Are you sure you want to change the language of the current content?', 'easy-wp-translator'));

  // Put it after languages list dropdown.
  // PHPCS ignore dialogContainer is a new safe HTML code generated above.
  languagesList.after(dialogContainer); // phpcs:ignore WordPressVIPMinimum.JS.HTMLExecutingFunctions.after

  var dialogResult = new Promise(function (confirm, cancel) {
    var confirmDialog = function confirmDialog(what) {
      // phpcs:ignore PEAR.Functions.FunctionCallSignature.Indent
      switch (what) {
        // phpcs:ignore PEAR.Functions.FunctionCallSignature.Indent
        case 'yes':
          // Confirm the new language.
          languagesList.data('old-value', languagesList.children(':selected').first().val());
          confirm();
          break;
        case 'no':
          // Revert to the old language.
          languagesList.val(languagesList.data('old-value'));
          cancel('Cancel');
          break;
      }
      dialogContainer.dialog('close'); // phpcs:ignore PEAR.Functions.FunctionCallSignature.Indent
    }; // phpcs:ignore PEAR.Functions.FunctionCallSignature.Indent

    // Initialize dialog box in the case a language is selected but not added in the list.
    var dialogOptions = {
      autoOpen: false,
      modal: true,
      draggable: false,
      resizable: false,
      title: __('Change language', 'easy-wp-translator'),
      minWidth: 600,
      maxWidth: '100%',
      open: function open(event, ui) {
        // Change dialog box position for rtl language
        if (jQuery('body').hasClass('rtl')) {
          jQuery(this).parent().css({
            right: jQuery(this).parent().css('left'),
            left: 'auto'
          });
        }
      },
      close: function close(event, ui) {
        // When we're closing the dialog box we need to cancel the language change as we click on Cancel button.
        confirmDialog('no');
      },
      buttons: [{
        text: __('OK', 'easy-wp-translator'),
        click: function click(event) {
          confirmDialog('yes');
        }
      }, {
        text: __('Cancel', 'easy-wp-translator'),
        click: function click(event) {
          confirmDialog('no');
        }
      }]
    };

    // jQuery UI >= 1.12 is available in WP 6.2+ (our minimum version)
    Object.assign(dialogOptions, {
      classes: {
        'ui-dialog': 'ewt-confirmation-modal'
      }
    });
    dialogContainer.dialog(dialogOptions);
  });
  return {
    dialogContainer: dialogContainer,
    dialogResult: dialogResult
  };
};
var initializeLanguageOldValue = function initializeLanguageOldValue() {
  // Keep the old language value to be able to compare to the new one and revert to it if necessary.
  languagesList.attr('data-old-value', languagesList.children(':selected').first().val());
};
;// ./assets/js/src/lib/metabox-autocomplete.js
/**
 * @package EasyWPTranslator
 */

// Translations autocomplete input box.
function initMetaboxAutoComplete() {
  jQuery('.tr_lang').each(function () {
    var tr_lang = jQuery(this).attr('id').substring(8);
    var td = jQuery(this).parent().parent().siblings('.ewt-edit-column');
    jQuery(this).autocomplete({
      minLength: 0,
      source: ajaxurl + '?action=ewt_posts_not_translated' + '&post_language=' + jQuery('.post_lang_choice').val() + '&translation_language=' + tr_lang + '&post_type=' + jQuery('#post_type').val() + '&_ewt_nonce=' + jQuery('#_ewt_nonce').val(),
      select: function select(event, ui) {
        jQuery('#htr_lang_' + tr_lang).val(ui.item.id);
        // ui.item.link is built and come from server side and is well escaped when necessary
        td.html(ui.item.link); // phpcs:ignore WordPressVIPMinimum.JS.HTMLExecutingFunctions.html
      }
    });

    // when the input box is emptied
    jQuery(this).on('blur', function () {
      if (!jQuery(this).val()) {
        jQuery('#htr_lang_' + tr_lang).val(0);
        // Value is retrieved from HTML already generated server side
        td.html(td.siblings('.hidden').children().clone()); // phpcs:ignore WordPressVIPMinimum.JS.HTMLExecutingFunctions.html
      }
    });
  });
}
;// ./assets/js/src/classic-editor.js
/**
 * @package EasyWPTranslator
 */




// tag suggest in metabox
jQuery(function ($) {
  $.ajaxPrefilter(function (options, originalOptions, jqXHR) {
    var lang = $('.post_lang_choice').val();
    if ('string' === typeof options.data && -1 !== options.url.indexOf('action=ajax-tag-search') && lang) {
      options.data = 'lang=' + lang + '&' + options.data;
    }
  });
});

// overrides tagBox.get
jQuery(function ($) {
  // overrides function to add the language
  tagBox.get = function (id) {
    var tax = id.substr(id.indexOf('-') + 1);

    // add the language in the $_POST variable
    var data = {
      action: 'get-tagcloud',
      lang: $('.post_lang_choice').val(),
      tax: tax
    };
    $.post(ajaxurl, data, function (r, stat) {
      if (0 == r || 'success' != stat) {
        r = wpAjax.broken;
      }

      // @see code from WordPress core https://github.com/WordPress/WordPress/blob/5.2.2/wp-admin/js/tags-box.js#L291
      // @see wp_generate_tag_cloud function which generate the escaped HTML https://github.com/WordPress/WordPress/blob/a02b5cc2a8eecb8e076fbb7cf4de7bd2ec8a8eb1/wp-includes/category-template.php#L966-L975
      r = $('<div />').addClass('the-tagcloud').attr('id', 'tagcloud-' + tax).html(r); // phpcs:ignore WordPressVIPMinimum.JS.HTMLExecutingFunctions.html
      $('a', r).on('click', function () {
        tagBox.flushTags($(this).closest('.inside').children('.tagsdiv'), this);
        return false;
      });
      var tagCloud = $('#tagcloud-' + tax);
      // add an if else condition to allow modifying the tags outputted when switching the language
      var v = tagCloud.css('display');
      if (v) {
        // See the comment above when r variable is created.
        $('#tagcloud-' + tax).replaceWith(r); // phpcs:ignore WordPressVIPMinimum.JS.HTMLExecutingFunctions.replaceWith
        $('#tagcloud-' + tax).css('display', v);
      } else {
        // See the comment above when r variable is created.
        $('#' + id).after(r); // phpcs:ignore WordPressVIPMinimum.JS.HTMLExecutingFunctions.after
      }
    });
  };
});
jQuery(function ($) {
  // collect taxonomies - code partly copied from WordPress
  var taxonomies = new Array();
  $('.categorydiv').each(function () {
    var this_id = $(this).attr('id'),
      taxonomyParts,
      taxonomy;
    taxonomyParts = this_id.split('-');
    taxonomyParts.shift();
    taxonomy = taxonomyParts.join('-');
    taxonomies.push(taxonomy); // store the taxonomy for future use

    // add our hidden field in the new category form - for each hierarchical taxonomy
    // to set the language when creating a new category
    // html code inserted come from html code itself.
    $('#' + taxonomy + '-add-submit').before(
    // phpcs:ignore WordPressVIPMinimum.JS.HTMLExecutingFunctions.before
    $('<input />').attr('type', 'hidden').attr('id', taxonomy + '-lang').attr('name', 'term_lang_choice').attr('value', $('.post_lang_choice').val()));
  });

  // Initialize current language to be able to compare if it changes.
  initializeLanguageOldValue();

  // ajax for changing the post's language in the languages metabox
  $('.post_lang_choice').on('change', function (event) {
    // Initialize the confirmation dialog box.
    var confirmationModal = initializeConfirmationModal();
    var dialog = confirmationModal.dialogContainer;
    var dialogResult = confirmationModal.dialogResult;
    // The selected option in the dropdown list.
    var selectedOption = event.target;
    if ($(this).data('old-value') !== selectedOption.value && !isEmptyPost()) {
      dialog.dialog('open');
    } else {
      dialogResult = Promise.resolve();
    }
    dialogResult.then(function () {
      var data = {
        action: 'ewt_post_lang_choice',
        lang: selectedOption.value,
        post_type: $('#post_type').val(),
        taxonomies: taxonomies,
        post_id: $('#post_ID').val(),
        _ewt_nonce: $('#_ewt_nonce').val()
      };
      $.post(ajaxurl, data, function (response) {
        // Target a non existing WP HTML id to avoid a conflict with WP ajax requests.
        var res = wpAjax.parseAjaxResponse(response, 'ewt-ajax-response');
        $.each(res.responses, function () {
          switch (this.what) {
            case 'translations':
              // translations fields
              // Data is built and come from server side and is well escaped when necessary
              $('.translations').html(this.data); // phpcs:ignore WordPressVIPMinimum.JS.HTMLExecutingFunctions.html
              initMetaboxAutoComplete();
              break;
            case 'taxonomy':
              // categories metabox for posts
              var tax = this.data;
              // @see wp_terms_checklist https://github.com/WordPress/WordPress/blob/5.2.2/wp-admin/includes/template.php#L175
              // @see https://github.com/WordPress/WordPress/blob/5.2.2/wp-admin/includes/class-walker-category-checklist.php#L89-L111
              $('#' + tax + 'checklist').html(this.supplemental.all); // phpcs:ignore WordPressVIPMinimum.JS.HTMLExecutingFunctions.html
              // @see wp_popular_terms_checklist https://github.com/WordPress/WordPress/blob/5.2.2/wp-admin/includes/template.php#L236
              $('#' + tax + 'checklist-pop').html(this.supplemental.populars); // phpcs:ignore WordPressVIPMinimum.JS.HTMLExecutingFunctions.html
              // @see wp_dropdown_categories https://github.com/WordPress/WordPress/blob/5.5.1/wp-includes/category-template.php#L336
              // which is called by EWT_Admin_Classic_Editor::post_lang_choice to generate supplemental.dropdown
              $('#new' + tax + '_parent').replaceWith(this.supplemental.dropdown); // phpcs:ignore WordPressVIPMinimum.JS.HTMLExecutingFunctions.replaceWith
              $('#' + tax + '-lang').val($('.post_lang_choice').val()); // hidden field
              break;
            case 'pages':
              // parent dropdown list for pages
              // @see wp_dropdown_pages https://github.com/WordPress/WordPress/blob/5.2.2/wp-includes/post-template.php#L1186-L1208
              // @see https://github.com/WordPress/WordPress/blob/5.2.2/wp-includes/class-walker-page-dropdown.php#L88
              $('#parent_id').html(this.data); // phpcs:ignore WordPressVIPMinimum.JS.HTMLExecutingFunctions.html
              break;
            case 'flag':
              // flag in front of the select dropdown
              // Data is built and come from server side and is well escaped when necessary
              $('.ewt-select-flag').html(this.data); // phpcs:ignore WordPressVIPMinimum.JS.HTMLExecutingFunctions.html
              break;
            case 'permalink':
              // Sample permalink
              var div = $('#edit-slug-box');
              if ('-1' != this.data && div.children().length) {
                // @see get_sample_permalink_html https://github.com/WordPress/WordPress/blob/5.2.2/wp-admin/includes/post.php#L1425-L1454
                div.html(this.data); // phpcs:ignore WordPressVIPMinimum.JS.HTMLExecutingFunctions.html
              }
              break;
          }
        });

        // Creates an event once the language has been successfully changed.
        var onPostLangChoice = new CustomEvent("onPostLangChoice", {
          detail: {
            lang: JSON.parse(selectedOption.options[selectedOption.options.selectedIndex].getAttribute('data-lang'))
          }
        });
        document.dispatchEvent(onPostLangChoice);
      });
    }, function () {} // Do nothing when promise is rejected by clicking the Cancel dialog button.
    );
    function isEmptyPost() {
      var title = $('input#title').val();
      var content = $('textarea#content').val();
      var excerpt = $('textarea#excerpt').val();
      return !title && !content && !excerpt;
    }
  });

  // Listen to `onPostLangChoice` to perform actions after the language has been changed.
  document.addEventListener('onPostLangChoice', function (e) {
    // Update the old language with the new one to be able to compare it in the next changing.
    initializeLanguageOldValue();

    // Modifies the language in the tag cloud.
    $('.tagcloud-link').each(function () {
      var id = $(this).attr('id');
      tagBox.get(id);
    });

    // Modifies the text direction.
    var dir = e.detail.lang.is_rtl ? 'rtl' : 'ltr';
    $('body').removeClass('ewt-dir-rtl').removeClass('ewt-dir-ltr').addClass('ewt-dir-' + dir);
    $('#content_ifr').contents().find('html').attr('lang', e.detail.lang.locale).attr('dir', dir);
    $('#content_ifr').contents().find('body').attr('dir', dir);

    // Refresh media libraries.
    ewt.media.resetAllAttachmentsCollections();
  });
  initMetaboxAutoComplete();
});

/**
 *
 * @namespace ewt
 */
var ewt = window.ewt || {};

/**
 *
 * @namespace ewt.media
 */
_.extend(ewt, {
  media: {}
});

/**
 *
 * @alias ewt.media
 * @memberOf ewt
 * @namespace
 */
var media = _.extend(ewt.media, /** @lends ewt.media.prototype */
{
  /**
   * TODO: Find a way to delete references to Attachments collections that are not used anywhere else.
   *
   * @type {wp.media.model.Attachments}
   */
  attachmentsCollections: [],
  /**
   * Imitates { @see wp.media.query } but log all Attachments collections created.
   *
   * @param {Object} [props]
   * @return {wp.media.model.Attachments}
   */
  query: function query(props) {
    var attachments = ewt.media.query.delegate(props);
    ewt.media.attachmentsCollections.push(attachments);
    return attachments;
  },
  resetAllAttachmentsCollections: function resetAllAttachmentsCollections() {
    this.attachmentsCollections.forEach(function (attachmentsCollection) {
      /**
       * First reset the { @see wp.media.model.Attachments } collection.
       * Then, if it is mirroring a { @see wp.media.model.Query } collection,
       * refresh this one too, so it will fetch new data from the server,
       * and then the wp.media.model.Attachments collection will synchronize with the new data.
       */
      attachmentsCollection.reset();
      if (attachmentsCollection.mirroring) {
        attachmentsCollection.mirroring._hasMore = true;
        attachmentsCollection.mirroring.reset();
      }
    });
  }
});
if ('undefined' !== typeof wp && 'undefined' !== typeof wp.media) {
  /**
   *
   * @memberOf ewt.media
   */
  media.query = _.extend(media.query, /** @lends ewt.media.query prototype */
  {
    /**
     * @type Function References WordPress { @see wp.media.query } constructor
     */
    delegate: wp.media.query
  });

  // Substitute WordPress media query shortcut with our decorated function.
  wp.media.query = media.query;
}
/******/ })()
;