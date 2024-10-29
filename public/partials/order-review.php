<?php 
use PISOL\REVIEW\FRONT\ReviewForm as FrontReviewForm;
use PISOL\REVIEW\ADMIN\ReviewStats;


?>
<h2><?php echo esc_html__( 'Review orders', 'product-review-for-woocommerce' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h2>

	<table class="shop_table shop_table_responsive my_account_orders">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Order', 'product-review-for-woocommerce' ); ?></th>
				<th><?php esc_html_e( 'Review', 'product-review-for-woocommerce' ); ?></th>
			</tr>
		</thead>

		<tbody>
			<?php
            if(empty($has_orders)){
                echo '<tr><td colspan="2">'.esc_html__('No orders to review', 'product-review-for-woocommerce').'</td></tr>';
            }else{
                foreach ( $customer_orders->orders as $customer_order ) :
                    $order      = wc_get_order( $customer_order ); 

                    if(ReviewStats::isReviewClosed($order)){
                        continue;
                    }

                    $review_url = FrontReviewForm::get_review_link($order->get_id());
                    ?>
                    <tr class="order">
                        <td class="order-number">
                            <a href="<?php echo esc_url( $order->get_view_order_url() ); ?>">
                                <?php echo esc_html( _x( '#', 'hash before order number', 'product-review-for-woocommerce' ) . $order->get_order_number() ); ?>
                            </a>
                        </td>
                        <td class="review">
                            <a href="<?php echo esc_url( FrontReviewForm::get_review_link($order->get_id()) ); ?>" class="button" target="_blank">
                                <?php echo esc_html__( 'Review products', 'product-review-for-woocommerce' ); ?>
                            </a>
                        </td>
                    </tr>
                    <?php
                endforeach; 
             } ?>
		</tbody>
	</table>

<?php if ( 1 < $customer_orders->max_num_pages ) : ?>
		<div class="woocommerce-pagination woocommerce-pagination--without-numbers woocommerce-Pagination">
			<?php if ( 1 !== $current_page ) : ?>
				<a class="woocommerce-button woocommerce-button--previous woocommerce-Button woocommerce-Button--previous button<?php echo esc_attr( $wp_button_class ); ?>" href="<?php echo esc_url( wc_get_endpoint_url( $endpoint, $current_page - 1 ) ); ?>"><?php esc_html_e( 'Previous', 'woocommerce' ); ?></a>
			<?php endif; ?>

			<?php if ( intval( $customer_orders->max_num_pages ) !== $current_page ) : ?>
				<a class="woocommerce-button woocommerce-button--next woocommerce-Button woocommerce-Button--next button<?php echo esc_attr( $wp_button_class ); ?>" href="<?php echo esc_url( wc_get_endpoint_url( $endpoint, $current_page + 1 ) ); ?>"><?php esc_html_e( 'Next', 'woocommerce' ); ?></a>
			<?php endif; ?>
		</div>
<?php endif; ?>