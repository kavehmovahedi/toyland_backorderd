<?php
namespace BHS\Tax\Controllers;

class WooController {
    public function __construct() {
       /*  add_filter( 'woocommerce_product_get_tax_class', [$this, 'calc_tax'], 10, 2 );
        add_filter( 'woocommerce_product_variation_get_tax_class', [$this, 'calc_tax'], 10, 2 ); */

        add_filter( 'woocommerce_price_ex_tax_amount', [$this, 'calc_tax'], PHP_INT_MAX, 4 );

        add_action( 'woocommerce_cart_calculate_fees', [$this, 'cart_tax_calc'], 20, 1 );

        add_filter('woocommerce_billing_fields', [$this, 'billingFields'], PHP_INT_MAX);
        add_filter('woocommerce_shipping_fields', [$this, 'addShippingFields']);

        add_action('woocommerce_before_order_notes', [$this, 'taxExemptFileUpload']);
        add_action('woocommerce_checkout_process', [$this, 'validateTaxExempt']);
        add_action('woocommerce_checkout_update_order_meta', [$this, 'saveTaxExemptFile']);

        //add_action( 'woocommerce_admin_order_data_after_shipping_address', [$this, 'adminExtraFields'], 10, 1 );

        add_action('wp_ajax_attach_exempt', [$this, 'handleDocExemp']);
        add_action('wp_ajax_nopriv_attach_exempt', [$this, 'handleDocExemp']);

        add_action('wp_enqueue_scripts', [$this, 'loadAssets'], PHP_INT_MAX);

        add_action('woocommerce_product_meta_end', [$this, 'extraMeta']);
        add_filter('woocommerce_cart_item_price', [$this, 'backorderZeroPrice'], PHP_INT_MAX, 3);
        add_filter('woocommerce_cart_item_subtotal', [$this, 'backorderZeroPrice'], PHP_INT_MAX, 3);

        add_action('woocommerce_before_calculate_totals', [$this, 'setBackorderPrice'], PHP_INT_MAX, 1);

        add_action('after_setup_theme', [$this, 'add80Square']);
        add_action('woocommerce_after_add_to_cart_button', [$this, 'addGoBackButton'], 10 );
        add_action('woocommerce_after_shop_loop', [$this, 'addJumpToPage'], 10);

        add_filter('woodmart_product_gallery_settings', [$this, 'setVerticalImageCount'], PHP_INT_MAX);
    }

    public function calc_tax( $tax_amount, $key, $rate, $price ) {
        if( !is_user_logged_in() ) return $tax_amount;

        $user    = wp_get_current_user();
        $gst_ex  = get_user_meta($user->ID, 'pst_exempt', true) == 'yes';
        $total_ex= get_user_meta($user->ID, 'total_tax_exempt', true) == 'yes';

        if ( $total_ex )
            $tax_amount = 0;
        elseif( $gst_ex && strpos($rate['label'], 'PST') !== false )
            $tax_amount = 0;

        return $tax_amount;
    }

    public function cart_tax_calc( $cart ) {
        if( !is_user_logged_in() ) return;
        if ( is_admin() && ! defined( 'DOING_AJAX' ) ) return;

        $user    = wp_get_current_user();
        $total_ex= get_user_meta($user->ID, 'total_tax_exempt', true) == 'yes';

        if( $total_ex ) {
            WC()->customer->set_is_vat_exempt( true );
        }
        else {
            WC()->customer->set_is_vat_exempt( false );
        }
    }

    public function billingFields($fields) {
        $fields['billing_phone_1'] = [
            'label'         => 'Phone 1',
            'placeholder'   => 'Phone 1',
            'required'      => true,
            'class'         => array('form-row-wide'),
            'clear'         => true
        ];

        $fields['billing_phone1_note'] = [
            'label'         => 'Phone 1 Notes',
            'placeholder'   => 'Phone 1 Notes',
            'required'      => false,
            'class'         => array('form-row-wide'),
            'clear'         => true
        ];

        $fields['billing_phone_2'] = [
            'label'         => 'Phone 2',
            'placeholder'   => 'Phone 2',
            'required'      => false,
            'class'         => array('form-row-wide'),
            'clear'         => true
        ];

        $fields['billing_phone2_note'] = [
            'label'         => 'Phone 2 Notes',
            'placeholder'   => 'Phone 2 Notes',
            'required'      => false,
            'class'         => array('form-row-wide'),
            'clear'         => true
        ];

        $fields['fax'] = [
            'label'         => 'Fax',
            'placeholder'   => 'Fax',
            'required'      => false,
            'class'         => array('form-row-wide'),
            'clear'         => true
        ];

        unset($fields['billing_company']);
        unset($fields['billing_phone']);

        return $fields;
    }

    public function addShippingFields($fields) {
        // Shipping fields
        $fields['buyer_first_name'] = [
            'label'         => 'Buyer First Name',
            'placeholder'   => '',
            'required'      => false,
            'class'         => array('form-row-wide'),
            'clear'         => true
        ];
    
        $fields['buyer_last_name'] = [
            'label'         => 'Buyer Last Name',
            'placeholder'   => '',
            'required'      => false,
            'class'         => array('form-row-wide'),
            'clear'         => true
        ];

        $fields['tax_exempt'] = [
            'label'         => 'Do you have Tax Exemption?',
            'required'      => false,
            'class'         => array('form-row-wide'),
            'clear'         => true,
            'options'       => [
                'no'    => 'No',
                'yes'   => 'Yes'
            ],
            'id'            => 'tax_exempt',
            'type'          => 'select'
        ];

        return $fields;
    }

    public function handleDocExemp() {
        check_ajax_referer( 'upload_csrf', 'csrf' );

        $file = media_handle_upload('doc', 0);
        if( !is_wp_error($file) ) {
            wp_send_json([
                'result'    => 'success',
                'doc_id'    => $file
            ], 200);
        }
        else {
            wp_send_json([
                'result'    => 'error',
                'error'     => $file->get_error_message()
            ], 200 );
        }
    }

    public function taxExemptFileUpload() {
        ?>
        <p id="exepmt_upload" class="form-row form-row-wide woocommerce-validated">
            <label for="exempt_uploader">Please upload Tax Exemption Document</label>
            <br/>
            <input type="file" name="exempt_uploader" id="exempt_uploader"/>
            <input type="hidden" name="bhs_csrf" id="bhs_csrf" value="<?php echo wp_create_nonce( 'upload_csrf' ); ?>"/>
            <input type="hidden" name="exempt_doc" id="exempt_doc" value=""/>
        </p>
        <script>
            const _bhs_url = "<?php echo admin_url('admin-ajax.php'); ?>";
            document.addEventListener('DOMContentLoaded', () => {
                const chk = document.querySelector('#tax_exempt')
                const upl = document.querySelector('#exempt_uploader')
                const cnt = document.querySelector('#exepmt_upload')
                const btn = document.querySelector('#place_order')
                var upled = false
                upl.required = chk.value == 'yes'
                cnt.style.display = (chk.value == 'yes' ? 'block' : 'none')
                chk.addEventListener('change', () => {
                    if( !upled ) {
                        upl.required = chk.value == 'yes'
                        cnt.style.display = (chk.value == 'yes' ? 'block' : 'none')
                    }
                })

                upl.addEventListener('change', () => {
                    if (event.target.files) {
                        document.querySelector('#place_order').disabled = true
                        let fd = new FormData()
                        fd.append('action', 'attach_exempt')
                        fd.append('csrf', document.querySelector('#bhs_csrf').value )
                        fd.append('doc', event.target.files[0])
                        fetch(_bhs_url, {
                            method: 'POST',
                            body: fd
                        })
                        .then( res => res.json() )
                        .then( res => {
                            if( res.result == 'success' ) {
                                document.querySelector('#exempt_doc').value = res.doc_id
                                let p = document.createElement('p')
                                p.innerHTML = 'Thank you! Your document was uploaded successfully.'
                                cnt.append(p)
                                upl.style.display = 'none'
                            }
                            else {
                                alert(res.error)
                            }
                        })
                        .catch( e => alert(e) )
                        .finally( () => document.querySelector('#place_order').disabled = false)
                    }
                })
            })
        </script>
        <?
    }

    public function validateTaxExempt() {
        if( $_REQUEST['tax_exempt'] == 'yes' && (!isset($_REQUEST['exempt_doc']) || empty($_REQUEST['exempt_doc']) ) )
            wc_add_notice( 'Please attach your tax exemption documents' );
    }

    public function saveTaxExemptFile($order_id) {
        $order = new \WC_Order($order_id);
        $user_id = $order->get_customer_id();

        if( $_REQUEST['tax_exempt'] == 'yes' ) {
            update_post_meta($order_id, 'tax_exempt_doc', $_REQUEST['exempt_doc']);
            update_user_meta($user_id, 'tax_exempt_doc', $_REQUEST['exempt_doc']);
        }

        update_user_meta($user_id, 'buyer_first_name', $_REQUEST['buyer_first_name']);
        update_user_meta($user_id, 'buyer_last_name', $_REQUEST['buyer_last_name']);

        update_post_meta($order_id, 'buyer_first_name', $_REQUEST['buyer_first_name']);
        update_post_meta($order_id, 'buyer_last_name', $_REQUEST['buyer_last_name']);

        update_post_meta($order_id, 'tax_exempt', $_REQUEST['tax_exempt']);
    }

    public function adminExtraFields($order) {
        //$user_id = $order->get_customer_id();
        $hasTaxExcempt = get_post_meta( $order->get_id(), 'tax_exempt', true );
        ?>
            <p><strong>Buyer First Name</strong><?php echo get_post_meta( $order->get_id(), 'buyer_first_name', true ); ?></p>
            <p><strong>Buyer Last Name</strong><?php echo get_post_meta( $order->get_id(), 'buyer_last_name', true ); ?></p>
            <p><strong>Has tax exempt?</strong><?php echo ucfirst($hasTaxExcempt); ?></p>
            <?php if( $hasTaxExcempt == 'yes' ) : ?>
            <p><strong>Tax exempt file:</strong>
                <a target="_blank" href="<?php echo wp_get_attachment_url( get_post_meta( $order->get_id(), 'tax_exempt_doc', true ) ); ?>">Open in new tab</a>
            </p>
        <?php 
        endif;
    }

    public function loadAssets() {
        $theme = wp_get_theme();
        if ( 'Woodmart' == $theme->name || 'Woodmart' == $theme->parent_theme ) {
            wp_dequeue_script( 'wd-add-to-cart-all-types' );
            wp_enqueue_script('add-to-cart-fix', BHS_TAX_URL . '/assets/js/dist/woodmart_fix.js', [], null, true);
        }
    }

    public function extraMeta() {
        global $product;

        //var_dump($product);
        $upc = get_post_meta($product->get_id(), 'upc_code', true);
        $qty = get_post_meta($product->get_id(), '_wqm_product_quantity', true);
        $attr_upc = $product->get_attribute('upc_code');
        if( !empty( $upc ) || !empty( $attr_upc )) {
            echo '<span class="upc_code_wrapper"><span class="meta-label">UPC Code: </span><span>' . ($attr_upc ? $attr_upc : $upc) . '</span></span>';
        }
        if( !empty($qty) && is_array($qty) and isset( $qty['min']) ) {
            echo '<span class="upc_code_wrapper"><span class="meta-label">Min. Order Qty.: </span><span>' . $qty['min'] . '</span></span>';
        }
    }

    public function backorderZeroPrice($price, $cart_item, $cart_item_key) {
        if( $cart_item['data']->stock_status == 'onbackorder' && (int)(get_post_meta($cart_item['data']->get_id(), 'quantity_on_boat', true)) <= 0) return 0;
        return $price;
    }

    public function setBackorderPrice($cart) {
        // Avoiding hook repetition (when using price calculations for example | optional)
        if ( did_action( 'woocommerce_before_calculate_totals' ) >= 2 )
            return;

        // Loop through cart items
        foreach ( $cart->get_cart() as $cart_item ) {
            if( $cart_item['data']->stock_status == 'onbackorder' && (int)(get_post_meta($cart_item['data']->get_id(), 'quantity_on_boat', true) ) <= 0 )
                $cart_item['data']->set_price( 0 );
        }
    }

    public function add80Square() {
        add_image_size('jg_shop_thumbnail', 80, 80, false);
    }

    public function addGoBackButton() {
        if( is_product() && str_starts_with($_SERVER['HTTP_REFERER'], home_url()) ) {
            echo ' <button type="button" style="flex:inherit" onclick="history.back();"> Go back </button> ';
        }
    }

    public function addJumpToPage() {
        $total   = isset( $total ) ? $total : wc_get_loop_prop( 'total_pages' );
        if( $total > 5 ) :
            add_action('woocommerce_after_shop_loop', [$this, 'closeDiv'], 11);
            add_action('wp_footer', [$this, 'addPaginationScripts']);
        ?>
        <div style="display:flex;flex-direction: row-reverse;align-items: end;gap: 1rem;">
            <div class="jg_jumpto_page" style="display: inline-flex;">
                <input type="number" id="jg_jump" min="0" max="<?php echo $total; ?>">
                <button class="button" id="jg_jumptopage">Go</button>
            </div>
        <?php
        endif;
    }

    public function closeDiv() {
        echo '</div>';
    }

    public function setVerticalImageCount( $data ) {
        $data['thumbs_slider']['items']['vertical_items'] = 5;
        return $data;
    }

    public function addPaginationScripts() {
        $base    = isset( $base ) ? $base : esc_url_raw( str_replace( 999999999, '%#%', remove_query_arg( 'add-to-cart', get_pagenum_link( 999999999, false ) ) ) );
        ?>
        <script>
            let jgButton, jgInput
            const jgBaseUrl = "<?php echo $base; ?>"
            function reloadPagination() {
                jgButton = document.querySelector('#jg_jumptopage')
                jgInput = document.querySelector('#jg_jump')
                jgButton.addEventListener('click', function() {
                    event.preventDefault()
                    event.stopPropagation()
                    let newUrl = jgBaseUrl.replace('%#%', jgInput.value)
                    window.location.assign(newUrl)
                })
            }
            jQuery(document.body).on("pjax:complete", reloadPagination)
            jQuery(document).ready(reloadPagination)
        </script>
        <?php
    }
}