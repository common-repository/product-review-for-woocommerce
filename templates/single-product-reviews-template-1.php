<?php
namespace PISOL\REVIEW\FRONT;

defined( 'ABSPATH' ) || exit;

global $product;

if ( ! comments_open() ) {
	return;
}

$each_rating_count = Review::get_star_rating_counts($product->get_id());
?>
<div id="reviews" class="woocommerce-Reviews">
	<div id="comments">
		<h2 class="woocommerce-Reviews-title">
			<?php
			$count = $product->get_review_count();
			if ( $count && wc_review_ratings_enabled() ) {
				/* translators: 1: reviews count 2: product name */
				$reviews_title = sprintf( esc_html( _n( '%1$s review for %2$s', '%1$s reviews for %2$s', $count, 'product-review-for-woocommerce' ) ), esc_html( $count ), '<span>' . get_the_title() . '</span>' );
				echo wp_kses_post(apply_filters( 'woocommerce_reviews_title', $reviews_title, $count, $product )); // WPCS: XSS ok.
			} else {
				esc_html_e( 'Reviews', 'product-review-for-woocommerce' );
			}
			?>
		</h2>

		<?php if ( get_option( 'woocommerce_enable_review_rating' ) === 'yes' && ( $count || $count > 0 ) ) : 
			$average = $product->get_average_rating();
			$reviews_count = $product->get_review_count();
			$custom_review_parameters = ReviewForm::get_custom_review_parameters($product->get_id());
			//var_dump($each_rating_count);
			?>
			<div class="pi-review-stats-container">
				<?php ReviewDisplay::show_review_count_stats($product); ?>
				<?php ReviewDisplay::show_custom_parameter_review($product); ?>
				<?php ReviewDisplay::show_rating_stats($product); ?>
			</div>
		<?php endif; ?>

		<?php if ( have_comments() ) : ?>
			<ol class="commentlist">
				<?php wp_list_comments( apply_filters( 'woocommerce_product_review_list_args', array( 'callback' => 'woocommerce_comments' ) ) ); ?>
			</ol>

			<?php if(get_option('pisol_review_load_more', 1)){ ?>
				<div class="review-load-container" style="padding:20px; text-align:center;">
					<?php if(get_option('pisol_review_loaded_review', 'append') == 'replace'){ ?>
						<a href="#review_form_wrapper" class="load-more-review button pi-previous-review" style="display:none;" data-page="0" data-product-id="<?php echo esc_attr(get_the_ID()); ?>">&larr; <?php echo esc_html(get_option('pisol_review_previous', 'Previous')); ?></a>

						<a href="#review_form_wrapper" class="load-more-review button pi-next-review" data-page="2" data-product-id="<?php echo esc_attr(get_the_ID()); ?>"><?php echo esc_html(get_option('pisol_review_next', 'Next')); ?> &rarr;</a>

					<?php }else{ ?>
						<a href="#review_form_wrapper" class="load-more-review button" data-page="2" data-product-id="<?php echo esc_attr(get_the_ID()); ?>"><?php echo esc_html(get_option('pisol_review_load_more_text', 'Load more reviews')); ?></a>
					<?php } ?>
				</div>
			<?php }else{ ?>
			<?php
			if ( get_comment_pages_count() > 1 && get_option( 'page_comments' ) ) :
				echo '<nav class="woocommerce-pagination review-pager">';
				paginate_comments_links(
					apply_filters(
						'woocommerce_comment_pagination_args',
						array(
							'prev_text' => is_rtl() ? '&rarr;' : '&larr;',
							'next_text' => is_rtl() ? '&larr;' : '&rarr;',
							'type'      => 'list',
						)
					)
				);
				echo '</nav>';
			endif;
			?>
			<?php } ?>
		<?php else : ?>
			<p class="woocommerce-noreviews"><?php esc_html_e( 'There are no reviews yet.', 'product-review-for-woocommerce' ); ?></p>
		<?php endif; ?>
	</div>
	<?php if(Review::product_review_form()): ?>
	<?php if ( get_option( 'woocommerce_review_rating_verification_required' ) === 'no' || wc_customer_bought_product( '', get_current_user_id(), $product->get_id() ) ) : ?>
		<div id="review_form_wrapper">
			<div id="review_form">
				<?php
				$commenter    = wp_get_current_commenter();
				$comment_form = array(
					/* translators: %s is product title */
					'title_reply'         => have_comments() ? esc_html__( 'Add a review', 'product-review-for-woocommerce' ) : sprintf( esc_html__( 'Be the first to review &ldquo;%s&rdquo;', 'product-review-for-woocommerce' ), get_the_title() ),
					/* translators: %s is product title */
					'title_reply_to'      => esc_html__( 'Leave a Reply to %s', 'product-review-for-woocommerce' ),
					'title_reply_before'  => '<span id="reply-title" class="comment-reply-title">',
					'title_reply_after'   => '</span>',
					'comment_notes_after' => '',
					'label_submit'        => esc_html__( 'Submit', 'product-review-for-woocommerce' ),
					'logged_in_as'        => '',
					'comment_field'       => '',
				);

				$name_email_required = (bool) get_option( 'require_name_email', 1 );
				$fields              = array(
					'author' => array(
						'label'    => __( 'Name', 'product-review-for-woocommerce' ),
						'type'     => 'text',
						'value'    => $commenter['comment_author'],
						'required' => $name_email_required,
					),
					'email'  => array(
						'label'    => __( 'Email', 'product-review-for-woocommerce' ),
						'type'     => 'email',
						'value'    => $commenter['comment_author_email'],
						'required' => $name_email_required,
					),
				);

				$comment_form['fields'] = array();

				foreach ( $fields as $key => $field ) {
					$field_html  = '<p class="comment-form-' . esc_attr( $key ) . '">';
					$field_html .= '<label for="' . esc_attr( $key ) . '">' . esc_html( $field['label'] );

					if ( $field['required'] ) {
						$field_html .= '&nbsp;<span class="required">*</span>';
					}

					$field_html .= '</label><input id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" type="' . esc_attr( $field['type'] ) . '" value="' . esc_attr( $field['value'] ) . '" size="30" ' . ( $field['required'] ? 'required' : '' ) . ' /></p>';

					$comment_form['fields'][ $key ] = $field_html;
				}

				$account_page_url = wc_get_page_permalink( 'myaccount' );
				if ( $account_page_url ) {
					/* translators: %s opening and closing link tags respectively */
					$comment_form['must_log_in'] = '<p class="must-log-in">' . sprintf( esc_html__( 'You must be %1$slogged in%2$s to post a review.', 'product-review-for-woocommerce' ), '<a href="' . esc_url( $account_page_url ) . '">', '</a>' ) . '</p>';
				}

				if ( wc_review_ratings_enabled() ) {
					$comment_form['comment_field'] = '<div class="comment-form-rating"><label for="rating">' . esc_html__( 'Your rating', 'product-review-for-woocommerce' ) . ( wc_review_ratings_required() ? '&nbsp;<span class="required">*</span>' : '' ) . '</label><select name="rating" id="rating" required>
						<option value="">' . esc_html__( 'Rate&hellip;', 'product-review-for-woocommerce' ) . '</option>
						<option value="5">' . esc_html__( 'Perfect', 'product-review-for-woocommerce' ) . '</option>
						<option value="4">' . esc_html__( 'Good', 'product-review-for-woocommerce' ) . '</option>
						<option value="3">' . esc_html__( 'Average', 'product-review-for-woocommerce' ) . '</option>
						<option value="2">' . esc_html__( 'Not that bad', 'product-review-for-woocommerce' ) . '</option>
						<option value="1">' . esc_html__( 'Very poor', 'product-review-for-woocommerce' ) . '</option>
					</select></div>';
				}

				$comment_form['comment_field'] .= '<p class="comment-form-comment"><label for="comment">' . esc_html__( 'Your review', 'product-review-for-woocommerce' ) . '&nbsp;<span class="required">*</span></label><textarea id="comment" name="comment" cols="45" rows="8" required></textarea></p>';

				comment_form( apply_filters( 'woocommerce_product_review_comment_form_args', $comment_form ) );
				?>
			</div>
		</div>
	<?php else : ?>
		<p class="woocommerce-verification-required"><?php esc_html_e( 'Only logged in customers who have purchased this product may leave a review.', 'product-review-for-woocommerce' ); ?></p>
	<?php endif; ?>
	
	<?php endif; ?>

	<div class="clear"></div>
</div>
