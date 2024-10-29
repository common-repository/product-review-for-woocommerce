<?php
use PISOL\REVIEW\FRONT\Review;
/**
 * Review Comments Template
 *
 * Closing li is left out on purpose!.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/review.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 2.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$image = Review::get_profile_image($comment);
$description = Review::get_description($comment);
$customer_name = Review::get_customer_name($comment);
$comment_date = Review::get_comment_date($comment);
$verified_tag = Review::get_verified_tag($comment);

?>
<li <?php comment_class(); ?> id="li-comment-<?php comment_ID(); ?>">

	<div id="comment-<?php comment_ID(); ?>" class="comment_container">

            <div class="profile-image">
                <?php echo wp_kses_post($image); ?>
            </div>
            
            <div class="comment-description">
                <?php echo wp_kses_post($description); ?>
            </div>

			<footer>
				<div class="meta-details">
					<strong class="customer-name">
						- <?php echo wp_kses_post($customer_name); ?>
					</strong>
					<span class="comment-date">
						<?php echo wp_kses_post($comment_date); ?>
					</span>
				</div>

				<div class="rating-verified">
					<div class="rating-star">
						<?php echo wp_kses_post(Review::get_rating_stars($comment)); ?>
					</div>
					<?php if(!empty($verified_tag)): ?>
						<?php echo wp_kses_post($verified_tag); ?>
					<?php endif; ?>
				</div>
			</footer>
			<?php
			/**
			 * The woocommerce_review_before_comment_meta hook.
			 *
			 * @hooked woocommerce_review_display_rating - 10
			 */
			//do_action( 'woocommerce_review_before_comment_meta', $comment );

			/**
			 * The woocommerce_review_meta hook.
			 *
			 * @hooked woocommerce_review_display_meta - 10
			 */
			//do_action( 'woocommerce_review_meta', $comment );

			//do_action( 'woocommerce_review_before_comment_text', $comment );

			/**
			 * The woocommerce_review_comment_text hook
			 *
			 * @hooked woocommerce_review_display_comment_text - 10
			 */
			//do_action( 'woocommerce_review_comment_text', $comment );
			
			do_action( 'woocommerce_review_after_comment_text', $comment );
			
			?>

	</div>
