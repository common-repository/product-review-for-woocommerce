<div class="pisol-review-rating-count-container">
    <strong class="pi-average-rating"><?php echo $average; ?></strong>
    <div class="woocommerce-review-rating">
        <?php echo wc_get_rating_html( $average, $count ); // WPCS: XSS ok. ?>
    </div>
    <strong class="pi-review-count"><?php echo $reviews_count; ?> <?php echo esc_html( _n( 'review', 'reviews', $reviews_count, 'product-review-for-woocommerce' ) ); ?></strong>
</div>