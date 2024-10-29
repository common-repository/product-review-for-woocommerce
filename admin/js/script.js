(function ($) {
    'use strict';
    jQuery(function ($) {

        function manual_review_reminder() {
            
            jQuery('.send_review_reminder').on('click', function (e) {
                e.preventDefault();

                if($(this).hasClass('review-reminder-without-concent')){
                    var userConsent = confirm('This Customer has not given permission to send them review reminder email, do you still want to send them email?');
                    if(!userConsent) return;
                }

                if($(this).hasClass('review-reminder-to-blacklisted-email')){
                    var userConsent = confirm('This Customer is in your blacklist, do you still want to send them email?');
                    if(!userConsent) return;
                }
                
                if (jQuery(this).hasClass('disabled')) return;

                var href = jQuery(this).attr('href');
                jQuery(this).addClass('processing');
                jQuery.ajax(
                    {
                        type: "GET",
                        url: href,
                        success: function (response) {
                            if (response.success) {
                                if(response.data.review_stats) {
                                    jQuery('#review-stats-'+response.data.order_id).html(response.data.review_stats);
                                }
                            } else {
                                alert(response.data.message);
                            }
                        }
                    }
                ).always(function () {
                    jQuery('.send_review_reminder').removeClass('processing');
                });
            });
        }

        manual_review_reminder();

        function remove_scheduled_reminder(){

            jQuery(document).on('click', '.remove-reminder', function (e) {
                e.preventDefault();
                var data = { 'action': 'pisol_review_remove_scheduled_reminder', 'order_id': jQuery(this).data('order_id'), '_wpnonce': jQuery(this).data('nonce') };
                jQuery.ajax(
                    {
                        type: "POST",
                        url: ajaxurl,
                        data: data,
                        success: function (response) {
                            if (response.success) {
                                if(response.data.review_stats) {
                                    jQuery('#review-stats-'+response.data.order_id).html(response.data.review_stats);
                                }
                            } else {
                                alert(response.data.message);
                            }
                        }
                    }
                );
            });
        }

        remove_scheduled_reminder();

        function add_black_listed_email(){
            jQuery(document).on('click', '#add-email-to-blacklist', function (e) {
                e.preventDefault();
                var email = jQuery('#blacklist_email').val();
                if(email){
                    var data = { 'action': 'pisol_review_add_black_listed_email', 'email': email, '_wpnonce': jQuery(this).data('nonce') };
                    jQuery.ajax(
                        {
                            type: "POST",
                            url: ajaxurl,
                            data: data,
                            success: function (response) {
                                if (response.success) {
                                    location.reload();
                                } else {
                                    alert(response.data);
                                }
                            }
                        }
                    );
                }else{
                    alert('Please enter email to blacklist');
                }
            });

            jQuery(document).on('click', '.add_to_blacklist', function (e) {
                e.preventDefault();
                    var href = jQuery(this).attr('href');
                    var button = jQuery(this);
                    button.addClass('processing');
                    jQuery.ajax(
                        {
                            type: "GET",
                            url: href,
                            success: function (response) {
                                if (response.success) {
                                    alert(response.data);
                                    location.reload();
                                } else {
                                    alert(response.data);
                                }
                            }
                        }
                    ).always(function () {
                        button.removeClass('processing');
                    });
                
            });
        }

        add_black_listed_email();

        function remove_email_from_blacklist(){
            jQuery(document).on('click', '.remove-email-from-blacklist', function (e) {
                e.preventDefault();
                var id = jQuery(this).data('id');
                var data = { 'action': 'pisol_review_remove_black_listed_email', 'id': id, '_wpnonce': jQuery(this).data('nonce') };
                jQuery.ajax(
                    {
                        type: "POST",
                        url: ajaxurl,
                        data: data,
                        success: function (response) {
                            if (response.success) {
                                jQuery('#blacklist-email-'+id).remove();
                            } else {
                                alert(response.data);
                            }
                        }
                    }
                );
            });

            jQuery(document).on('click', '.remove_from_blacklist', function (e) {
                e.preventDefault();
                    var href = jQuery(this).attr('href');
                    var button = jQuery(this);
                    button.addClass('processing');
                    jQuery.ajax(
                        {
                            type: "GET",
                            url: href,
                            success: function (response) {
                                if (response.success) {
                                    alert(response.data);
                                    location.reload();
                                } else {
                                    alert(response.data);
                                }
                            }
                        }
                    ).always(function () {
                        button.removeClass('processing');
                    });
                
            });
        }

        remove_email_from_blacklist();

        function review_parameter(){

            this.init = function(){
                this.submit();
                this.delete();
                this.edit();
            }

            this.submit = function(){
                jQuery(document).on("submit", "#review-form-action", function(e){
                    e.preventDefault();
                    var data = jQuery(this).serialize();
                    jQuery.ajax({
                        type: "POST",
                        url: ajaxurl,
                        data: data,
                        success: function(response){
                            if(response.success){
                                alert(response.data);
                                location.reload();
                            }else{
                                alert(response.data);
                            }
                        }
                    });
                });
            }

            this.delete = function(){
                jQuery(document).on("click", ".delete-review-parameter", function(e){
                    e.preventDefault();
                    var id = jQuery(this).data('id');
                    var data = { 'action': 'pisol_review_delete_review_parameter', 'review_parameter_id': id, '_wpnonce': jQuery(this).data('nonce') };
                    jQuery.ajax({
                        type: "POST",
                        url: ajaxurl,
                        data: data,
                        success: function(response){
                            if(response.success){
                                jQuery('#review-parameter-'+id).remove();
                            }else{
                                alert(response.data);
                            }
                        }
                    });
                });
            }

            this.edit = function(){
                jQuery(document).on("click", ".edit-review-parameter", function(e){
                    e.preventDefault();
                    var data = jQuery(this).data('data');
                    jQuery('#review_parameter_question').val(data.question);
                    jQuery('#review_parameter_label').val(data.label);
                    jQuery('#review_parameter_default_rating').val(data.default_rating);
                    if(data.required == 1){
                        jQuery('#review_parameter_required').attr('checked', 'checked');
                    }else{
                        jQuery('#review_parameter_required').removeAttr('checked');
                    }

                    jQuery('#review_parameter_id').val(data.id);
                    jQuery('#review_parameter_button').attr('value', 'Edit Review Parameter');
                });
            }
        }

        var review_parameter_obj = new review_parameter();
        review_parameter_obj.init();

        //check if datepicker is available
        if(jQuery().datepicker){
            jQuery("#pisol_review_to_date, #pisol_review_from_date").datepicker({
                dateFormat:'yy-mm-dd',
            });
        }

    });
})(jQuery);