<?php
namespace PISOL\REVIEW\FRONT;
?>
<?php if($custom_review_parameters): ?>
    <?php 
    $custom_parameter_rating_html = '';
    foreach($custom_review_parameters as $custom_review_parameter): 
        $label = get_post_meta($custom_review_parameter, 'label', true);
        $custom_parameter_rating_average = Review::get_custom_review_parameter_rating_average($custom_review_parameter, $product->get_id());
        $custom_parameter_rating_count = Review::get_custom_review_parameter_rating_count($custom_review_parameter, $product->get_id());

        if($custom_parameter_rating_count == 0) continue;
        ob_start();
        ?>
        <div class="pisol-review-custom-parameter">
            <strong class="pisol-review-custom-parameter-label"><?php echo esc_html($label); ?></strong>
            <div class="pisol-review-custom-parameter-rating">
                <?php echo wc_get_rating_html( $custom_parameter_rating_average,$custom_parameter_rating_count ); // WPCS: XSS ok. ?>
            </div>
        </div>

    <?php 
    $custom_parameter_rating_html .= ob_get_clean();
    endforeach; 

    echo $custom_parameter_rating_html ? sprintf('<div class="pisol-review-custom-parameters">%s</div>', $custom_parameter_rating_html) : '';
    ?>
<?php endif; ?>