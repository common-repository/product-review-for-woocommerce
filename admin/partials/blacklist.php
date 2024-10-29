<?php
$page_url = admin_url( 'admin.php?page=product-review-for-woocommerce&tab=blacklist' );
?>
<div id="row_color-setting" class="row py-4 border-bottom align-items-center bg-primary text-light">
    <div class="col-12">
        <h2 class="mt-0 mb-0 text-light font-weight-light h4"><?php esc_html_e('Blacklist','product-review-for-woocommerce'); ?></h2>
        <small><?php esc_html_e('Review reminder email will not be send to this email id','product-review-for-woocommerce'); ?></small>            
    </div>
</div>

<div class="row py-3">
    <div class="col-4">
        <input type="text" class="form-control" id="blacklist_email" placeholder="<?php esc_html_e('Blacklist email id','product-review-for-woocommerce'); ?>">
    </div>
    <div class="col-2">
        <button type="button" class="btn btn-secondary" id="add-email-to-blacklist" data-nonce="<?php echo esc_attr( wp_create_nonce( 'add-email-to-blacklist' )); ?>"><?php esc_html_e('Add email','product-review-for-woocommerce'); ?></button>
    </div>
    
    <div class="col-6">
    <form action="<?php echo esc_url($page_url); ?>" method="GET">
    <div class="row">
    <div class="col-8">
        <input type="text" class="form-control" name="search" placeholder="<?php esc_html_e('Search email id','product-review-for-woocommerce'); ?>" value="<?php echo esc_attr(isset($_GET['search']) ? $_GET['search'] : ''); ?>">
        <input type="hidden" name="page" value="product-review-for-woocommerce">
        <input type="hidden" name="tab" value="blacklist">
        <input type="hidden" name="pageno" value="<?php echo esc_attr(isset($_GET['pageno']) ? absint($_GET['pageno']) : 1); ?>">
    </div>
    <div class="col-4">
        <button type="submit" class="btn btn-secondary"><?php esc_html_e('Search email','product-review-for-woocommerce'); ?></button>
    </div>
    </div>
    </form>
    </div>
</div>

<div class="row py-4">
    <div class="col-12">
        <table class="table">
            <tr>
                <th><?php esc_html_e('Blacklisted email id','product-review-for-woocommerce'); ?></th>
                <th><?php esc_html_e('Delete','product-review-for-woocommerce'); ?></th>
            </tr>
            <?php
            if(empty($emails)){
                ?>
                <tr>
                    <td colspan="2" class="text-center"><?php esc_html_e('No email id','product-review-for-woocommerce'); ?></td>
                </tr>
                <?php
            }else{
                foreach($emails as $email){
                    ?>
                    <tr id="blacklist-email-<?php echo esc_html($email['id']); ?>">
                        <td><?php echo esc_html($email['email']); ?></td>
                        <td>
                            <a href="#" data-id="<?php echo esc_html($email['id']); ?>" class="btn btn-danger remove-email-from-blacklist" data-nonce="<?php echo esc_attr( wp_create_nonce( 'delete-email-from-blacklist' )); ?>"><?php esc_html_e('Remove','product-review-for-woocommerce'); ?></a>
                        </td>
                    </tr>
                    <?php
                }
            }
            ?>
        </table>
    </div>
</div>
<div class="row py-4">
    <div class="col-12">
    <nav>
    <ul class="pagination">
    <?php if($prev_page > 0): 
        $previous_page_variable = [
            'pageno' => $prev_page,
            'search' => isset($_GET['search']) ? sanitize_text_field($_GET['search']) : ''
        ];
        ?>
        <li class="page-item">
        <a class="page-link" href="<?php echo esc_url(add_query_arg( $previous_page_variable, $page_url )); ?>">&laquo; <?php esc_html_e('Previous','product-review-for-woocommerce'); ?></a>
        </li>
    <?php endif; ?>

    <?php for($i = 1; $i <= $pages; $i++): 
        $paging_page_variable = [
            'pageno' => $i,
            'search' => isset($_GET['search']) ? sanitize_text_field($_GET['search']) : ''
        ];
        ?>
        <li class="page-item">
        <a class="page-link" href="<?php echo esc_url(add_query_arg( $paging_page_variable, $page_url )); ?>" class="<?php if($i == $page_no) echo 'active'; ?>">
            <?php echo esc_html($i); ?>
        </a>
        </li>
    <?php endfor; ?>

    <?php if($next_page > 0): 
        $next_page_variable = [
            'pageno' => $next_page,
            'search' => isset($_GET['search']) ? sanitize_text_field($_GET['search']) : ''
        ];
        ?>
        <li class="page-item">
        <a class="page-link" href="<?php echo esc_url(add_query_arg( $next_page_variable, $page_url )); ?>"><?php esc_html_e('Next','product-review-for-woocommerce'); ?> &raquo;</a>
        </li>
    <?php endif; ?>
    </ul>
    </nav>
    </div> 
</div> 

