<div id="row_color-setting" class="row py-4 border-bottom align-items-center bg-primary text-light">
    <div class="col-6">
        <h2 class="mt-0 mb-0 text-light font-weight-light h4"><?php esc_html_e('Review parameters','product-review-for-woocommerce'); ?></h2>
        <small><?php esc_html_e('Create parameters that you like user to review','product-review-for-woocommerce'); ?></small>            
    </div>
    <div class="col-6 text-right">
        <a href="javascript:void(0)" class="btn btn-secondary review-parameter-action" data-action="add-review-feature"><?php esc_html_e('Add review parameters','product-review-for-woocommerce'); ?></a>
    </div>
</div>

<form id="review-form-action">
<div class="row my-3 align-items-center" >
    <div class="col-3">
        <div class="form-group">
            <input type="text" class="form-control" id="review_parameter_question" name="review_parameter_question" placeholder="<?php esc_html_e('How was the quality','product-review-for-woocommerce'); ?>">
        </div>
    </div>
    <div class="col-2">
        <div class="form-group">
            <input type="text" class="form-control" id="review_parameter_label" name="review_parameter_label" placeholder="<?php esc_html_e('Quality','product-review-for-woocommerce'); ?>">
        </div>
    </div>
    <div class="col-3">
        <div class="form-group">
            <select name="review_parameter_default_rating" id="review_parameter_default_rating" class="form-control">
                <option value=""><?php esc_html_e('Select default rating','product-review-for-woocommerce'); ?></option>
                <?php
                for($i = 1; $i <= 5; $i++){
                    echo '<option value="'.$i.'">'.$i.'</option>';
                }
                ?>
            </select>
        </div>
    </div>
    <div class="col-2">
        <div class="form-group">
            <input type="checkbox" id="review_parameter_required" name="review_parameter_required" value="1">
            <label for="review_parameter_required" class="mb-0"><?php esc_html_e('Required','product-review-for-woocommerce'); ?></label>
        </div>
    </div>
    <div class="col-2">
        <div class="form-group">
            <input type="submit" class="btn btn-primary" id="review_parameter_button" value="<?php esc_attr_e('Add new parameter','product-review-for-woocommerce'); ?>">
            <input type="hidden" name="review_parameter_id" id="review_parameter_id">
            <input type="hidden" name="action" value="pisol_review_add_edit_review_parameter">
            <?php wp_nonce_field('review_parameter_action','review_parameter_nonce'); ?>
        </div>
    </div>
</div>
</form>
<?php
$paged = absint(isset($_GET['paged']) ? $_GET['paged'] : 1);
$per_page = 40;
//get all the post of type pi_review_parameter for display header_register_callback with paging of 10 
$review_parameters = get_posts(array(
    'post_type' => 'pi_review_parameter',
    'posts_per_page' => $per_page,
    'paged' => $paged
));

if($review_parameters){
    ?>
    <div class="row">
        <div class="col-12">
            <table class="table table-striped custom-parameter-table text-center">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Question','product-review-for-woocommerce'); ?></th>
                        <th><?php esc_html_e('Label','product-review-for-woocommerce'); ?></th>
                        <th><?php esc_html_e('Default rating','product-review-for-woocommerce'); ?></th>
                        <th><?php esc_html_e('Required','product-review-for-woocommerce'); ?></th>
                        <th><?php esc_html_e('Action','product-review-for-woocommerce'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    foreach($review_parameters as $review_parameter){
                        $label = get_post_meta($review_parameter->ID, 'label', true);
                        $required = get_post_meta($review_parameter->ID, 'required', true);
                        $default_rating = get_post_meta($review_parameter->ID, 'default_rating', true);
                        $data = array(
                            'id' => $review_parameter->ID,
                            'question' => $review_parameter->post_title,
                            'label' => $label,
                            'required' => $required,
                            'default_rating' => $default_rating
                        );
                        ?>
                        <tr id="review-parameter-<?php echo esc_attr($review_parameter->ID); ?>">
                            <td><?php echo esc_html($review_parameter->post_title); ?></td>
                            <td><?php echo esc_html($label); ?></td>
                            <td><?php echo $default_rating ? esc_html($default_rating) : '-'; ?></td>
                            <td><?php echo $required ? esc_html__('Yes','product-review-for-woocommerce') : esc_html__('No','product-review-for-woocommerce'); ?></td>
                            <td>
                                <a href="javascript:void(0)" class="review-parameter-action btn btn-sm btn-primary mr-2 edit-review-parameter" data-action="edit-review-feature" data-data="<?php echo esc_attr(json_encode($data)); ?>"><?php esc_html_e('Edit','product-review-for-woocommerce'); ?></a>
                                <a href="javascript:void(0)" class="review-parameter-action btn btn-sm btn-danger mr-2 delete-review-parameter"  data-id="<?php echo esc_attr($review_parameter->ID); ?>" data-id="<?php echo esc_attr($review_parameter->ID); ?>" data-nonce="<?php echo esc_attr( wp_create_nonce('remove_review_parameter_action') ); ?>"><?php esc_html_e('Delete','product-review-for-woocommerce'); ?></a>
                            </td>
                        </tr>
                        <?php
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
}

$total_posts = new WP_Query(array(
    'post_type'      => 'pi_review_parameter',
    'posts_per_page' => $per_page,
    'paged'          => $paged,
));

$base_url = add_query_arg( array(
    'page' => 'product-review-for-woocommerce',
    'tab'  => 'custom_review',
), admin_url('admin.php') );

$base_url = $base_url . '&paged=%#%';

echo paginate_links(array(
    'base'      => $base_url,
    'total'        => $total_posts->max_num_pages,
    'current'      => $paged,
    'format'       => '?paged=%#%',
    'prev_text'    => __('&laquo; Previous'),
    'next_text'    => __('Next &raquo;'),
));