<?php
use PISOL\REVIEW\FRONT\ReviewForm;
use PISOL\REVIEW\FRONT\Review;
use PISOL\REVIEW\ADMIN\ReviewStats;

if ( ! defined( 'ABSPATH' ) ) exit; 

if(isset($_GET['review_key'])){
    $review_key = sanitize_text_field($_GET['review_key']);
}else{
    wp_die(esc_html__('Invalid review key', 'product-review-for-woocommerce'));
}

$order = ReviewForm::is_valid_order($review_key);

if($order === false) {
    wp_die(esc_html__('Invalid order', 'product-review-for-woocommerce'));
}

if(isset($_GET['submitted'])){
    wp_die(esc_html__('Review submitted successfully','product-review-for-woocommerce'), esc_html__('Review submitted successfully','product-review-for-woocommerce'), array('response' => 200));
}

if(ReviewStats::isReviewClosed($order)){
    wp_die(esc_html__('Review is already submitted','product-review-for-woocommerce'), esc_html__('Review is already submitted','product-review-for-woocommerce'), array('response' => 400));
}

if(!Review::review_possible($order)){
    wp_die(esc_html__('There are no product in this order to review','product-review-for-woocommerce'), esc_html__('Review is already submitted','product-review-for-woocommerce'), array('response' => 400));
}

$default_rating = ReviewForm::get_default_rating();

$logo_url = ReviewForm::get_logo_url();
$logo_alignment = ReviewForm::get_logo_alignment();

$min_char_length = ReviewForm::get_min_char_length();
$max_char_length = ReviewForm::get_max_char_length();

$display_names = ReviewForm::get_display_names( $order );
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html(ReviewForm::get_form_title()); ?></title>
    <?php wp_head(); ?>
</head>
<body>
    <div class="pisol-header">
        <?php if($logo_url){ ?>
            <div class="pisol-logo">
                <img src="<?php echo esc_url($logo_url); ?>" alt="logo" class="logo-image <?php echo esc_attr($logo_alignment); ?>">
            </div>
        <?php } ?>
    </div>
    <div class="pisol-container">
        <h1 class="form-title"><?php echo esc_html(ReviewForm::get_form_title()); ?></h1>
        <p class="form-description"><?php echo esc_html(ReviewForm::get_form_description()); ?></p>
        <form action="<?php echo esc_url(admin_url('admin-ajax.php')); ?>" method="post" id="pisol-submit-review-form">
        <input type="hidden" name="action" value="pisol_submit_review">
        <input type="hidden" name="referal_url" value="<?php echo esc_url(home_url('/')); ?>">
        <input type="hidden" name="review_key" value="<?php echo esc_attr($review_key); ?>">
        <?php wp_nonce_field('pisol_submit_review'); ?>
        <div class="error general-error"></div>
        <?php 
            $products = ReviewForm::get_products($order);
            foreach($products as $product_id){ 
                $product = wc_get_product($product_id);

                if(!$product->get_reviews_allowed()) continue;
            ?>
            <div class="pisol-item">
                <div class="row-title-rating">
                    <h3><a href="<?php echo esc_url($product->get_permalink()); ?>" target="_blank" class="product-link"><?php echo esc_html($product->get_name()); ?></a></h3>
                    <div class="rating-container">
                        <input type="radio" name="rating[<?php echo esc_attr($product_id); ?>]" value="5" id="rating-5-<?php echo esc_attr($product_id); ?>" class="rating-input" <?php checked(5, $default_rating); ?>>
                        <label for="rating-5-<?php echo esc_attr($product_id); ?>" class="rating-label">★</label>

                        <input type="radio" name="rating[<?php echo esc_attr($product_id); ?>]" value="4" id="rating-4-<?php echo esc_attr($product_id); ?>" class="rating-input" <?php checked(4, $default_rating); ?>>
                        <label for="rating-4-<?php echo esc_attr($product_id); ?>" class="rating-label">★</label>

                        <input type="radio" name="rating[<?php echo esc_attr($product_id); ?>]" value="3" id="rating-3-<?php echo esc_attr($product_id); ?>" class="rating-input" <?php checked(3, $default_rating); ?>>
                        <label for="rating-3-<?php echo esc_attr($product_id); ?>" class="rating-label">★</label>

                        <input type="radio" name="rating[<?php echo esc_attr($product_id); ?>]" value="2" id="rating-2-<?php echo esc_attr($product_id); ?>" class="rating-input" <?php checked(2, $default_rating); ?>>
                        <label for="rating-2-<?php echo esc_attr($product_id); ?>" class="rating-label">★</label>

                        <input type="radio" name="rating[<?php echo esc_attr($product_id); ?>]" value="1" id="rating-1-<?php echo esc_attr($product_id); ?>" class="rating-input" <?php checked(1, $default_rating); ?>>
                        <label for="rating-1-<?php echo esc_attr($product_id); ?>" class="rating-label">★</label>
                    </div>
                </div>
                <div class="error rating-error-<?php echo esc_attr($product_id); ?>"></div>
                <?php
                    //get custom review parameters
                    $custom_review_parameters = ReviewForm::get_custom_review_parameters($product_id);
                    if(!empty($custom_review_parameters)){
                        ?>
                        <div class="pi-review-parameters-container">
                        <?php
                    }
                    foreach($custom_review_parameters as $parameter){
                        $post = get_post($parameter);
                        
                        if($post){
                            $question = $post->post_title;
                            $label = get_post_meta($post->ID, 'label', true);
                            $required = get_post_meta($post->ID, 'required', true);
                            $parameter_default_rating = get_post_meta($post->ID, 'default_rating', true);
                            //var_dump($question);
                            ?>
                            <div class="review-parameter">
                                <h4><?php echo esc_html($question); ?>
                                    <?php if($required){ ?>
                                        <span class="required">*</span>
                                    <?php } ?>
                                </h4>
                                <div class="rating-container">
                                    <input type="radio" name="parameter_rating[<?php echo esc_attr($product_id); ?>][<?php echo esc_attr($post->ID); ?>]" value="5" id="rating-5-<?php echo esc_attr($product_id); ?>-<?php echo esc_attr($post->ID); ?>" class="rating-input" <?php checked(5, $parameter_default_rating); ?>>
                                    <label for="rating-5-<?php echo esc_attr($product_id); ?>-<?php echo esc_attr($post->ID); ?>" class="rating-label">★</label>

                                    <input type="radio" name="parameter_rating[<?php echo esc_attr($product_id); ?>][<?php echo esc_attr($post->ID); ?>]" value="4" id="rating-4-<?php echo esc_attr($product_id); ?>-<?php echo esc_attr($post->ID); ?>" class="rating-input" <?php checked(4, $parameter_default_rating); ?>>
                                    <label for="rating-4-<?php echo esc_attr($product_id); ?>-<?php echo esc_attr($post->ID); ?>" class="rating-label">★</label>

                                    <input type="radio" name="parameter_rating[<?php echo esc_attr($product_id); ?>][<?php echo esc_attr($post->ID); ?>]" value="3" id="rating-3-<?php echo esc_attr($product_id); ?>-<?php echo esc_attr($post->ID); ?>" class="rating-input" <?php checked(3, $parameter_default_rating); ?>>
                                    <label for="rating-3-<?php echo esc_attr($product_id); ?>-<?php echo esc_attr($post->ID); ?>" class="rating-label">★</label>

                                    <input type="radio" name="parameter_rating[<?php echo esc_attr($product_id); ?>][<?php echo esc_attr($post->ID); ?>]" value="2" id="rating-2-<?php echo esc_attr($product_id); ?>-<?php echo esc_attr($post->ID); ?>" class="rating-input" <?php checked(2, $parameter_default_rating); ?>>
                                    <label for="rating-2-<?php echo esc_attr($product_id); ?>-<?php echo esc_attr($post->ID); ?>" class="rating-label">★</label>

                                    <input type="radio" name="parameter_rating[<?php echo esc_attr($product_id); ?>][<?php echo esc_attr($post->ID); ?>]" value="1" id="rating-1-<?php echo esc_attr($product_id); ?>-<?php echo esc_attr($post->ID); ?>" class="rating-input" <?php checked(1, $parameter_default_rating); ?>>
                                    <label for="rating-1-<?php echo esc_attr($product_id); ?>-<?php echo esc_attr($post->ID); ?>" class="rating-label">★</label>
                                </div>
                                <div class="error parameter-rating-error-<?php echo esc_attr($product_id); ?>-<?php echo esc_attr($post->ID); ?>"></div>
                            </div>
                            <?php
                        }
                    }
                    if(!empty($custom_review_parameters)){
                        ?>
                        </div>
                        <?php
                    }
                ?>
                <textarea name="review[<?php echo esc_attr($product_id); ?>]" id="" cols="30" rows="10" placeholder="<?php echo esc_attr(ReviewForm::get_form_review_placeholder()); ?>" class="review-textarea" data-min-char-length="<?php echo esc_attr($min_char_length); ?>"  data-max-char-length="<?php echo esc_attr($max_char_length); ?>" data-product-id="<?php echo esc_attr($product_id); ?>"></textarea>
    
                <div class="char-counter-system">
                <span class="char-counter-container"><?php esc_html_e('Character Count','product-review-for-woocommerce'); ?>: <span class="char-count counter-for-product-<?php echo esc_attr($product_id); ?>">0</span></span>
                <?php if($min_char_length){ ?>
                    <span class="min-counter-container"><?php esc_html_e('Min needed','product-review-for-woocommerce'); ?>: <span class="min-count" ><?php echo esc_html($min_char_length); ?></span></span>
                <?php } ?>
                <?php if($max_char_length){ ?>
                    <span class="max-counter-container"><?php esc_html_e('Max allowed','product-review-for-woocommerce'); ?>: <span class="max-count"><?php echo esc_html($max_char_length); ?></span></span>
                <?php } ?>
                </div>

                <div class="error review-error-<?php echo esc_attr($product_id); ?>"></div>
            </div>
        <?php } ?>

        <div class="pisol-display-name-container">
            <h3><?php echo esc_html(get_option('pisol_review_select_name_text', 'Display name')); ?></h3>
            <div class="display-name-container">
                <?php 
                $count = 0;
                foreach($display_names as $display_name){ ?>
                    <input type="radio" name="display_name" value="<?php echo esc_attr($display_name); ?>" id="display-name-<?php echo esc_attr($display_name); ?>" class="display-name-input" <?php echo ($count == 0 ? 'checked' : ''); ?>>
                    <label for="display-name-<?php echo esc_attr($display_name); ?>" class="display-name-label">
                    <?php echo esc_html($display_name); ?>
                    </label>
                <?php 
                $count++;
                } ?>
            </div>
        </div>
        <button type="submit" class="submit-button"><?php echo esc_html(ReviewForm::get_form_submit_text()); ?></button>
        </form>
    </div>
</body>
</html>

