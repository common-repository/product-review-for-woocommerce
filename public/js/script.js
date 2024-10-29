(function ($) {
    'use strict';
    jQuery(function ($) {

        function review_submission() {
            
            jQuery('#pisol-submit-review-form').on('submit', function (e) {
                e.preventDefault();
                var form = jQuery(this);
                var data = form.serialize();
                var action = form.attr('action');
                jQuery('.pisol-container').addClass('processing');
                jQuery('.submit-button').prop('disabled', true);
                jQuery.ajax(
                    {
                        type: "POST",
                        url: action,
                        data: data,
                        success: function (response) {
                            if (response.success) {
                               jQuery('.pisol-container').html(response.data.message);
                            } else {
                                var errors = response.data;
                                $('.error').html('');
                                for (var field in errors) {
                                    if (errors.hasOwnProperty(field)) {
                                        $('.' + field).html(errors[field]);
                                    }
                                }
                            }
                        }
                    }
                ).always(function () {
                    jQuery('.pisol-container').removeClass('processing');
                    jQuery('.submit-button').prop('disabled', false);
                });
            });
        }

        review_submission();


        function word_count() {
            $('.review-textarea').on('input', function() {
                var product_id = jQuery(this).data('product-id');
                var $this = $(this);
                var charCount = $this.val().length;
                
                $(".counter-for-product-"+product_id).text(charCount);
                
            });
        }

        word_count();

    });
})(jQuery);