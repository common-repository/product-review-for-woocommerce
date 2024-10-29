(function ($) {
    'use strict';
    jQuery(function ($) {
        $('body').on('click','.load-more-review', function(e) {
            e.preventDefault();
            var button = $(this);
            var page = button .data('page');
            var productId = button .data('product-id');
            $('#comments').addClass('pi-review-loading');
            button.prop('disabled', true);
            $.ajax({
                url: pisol_review_params.ajax_url,
                type: 'POST',
                data: {
                    action: 'load_reviews_by_page',
                    page: page,
                    product_id: productId
                },
                success: function(response) {
                
                    if(pisol_review_params.review_display == 'append') {
                        $('.commentlist').append(response.data.reviews);
                        if(response.data.next == 0){
                            button.hide();
                        }else{
                            button.data('page', response.data.next);
                        }
                    }else{
                        $('.commentlist').html(response.data.reviews);

                        if(response.data.next == 0){
                            $('.pi-next-review').hide();
                        }else{
                            $('.pi-next-review').data('page', response.data.next).show();
                        }

                        if(response.data.prev == 0){
                            $('.pi-previous-review').hide();
                        }else{
                            $('.pi-previous-review').data('page', response.data.prev).show();
                        }
                    }

                    
                },
                error: function() {
                    console.log('Failed to load reviews.');
                }
            }).always(function() {
                $('#comments').removeClass('pi-review-loading');
                button.prop('disabled', false);
            });
        });
    });
})(jQuery);