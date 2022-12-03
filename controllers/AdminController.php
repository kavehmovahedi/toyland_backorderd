<?php

namespace BHS\Tax\Controllers;

use \WP_User as WP_User;
use \WC_Tax as WC_Tax;
use \BHS\Jorgel\RegistrationController as RegistrationController;

class AdminController {
    public function __construct() {
        add_action('edit_user_profile', [$this, 'renderUserProfileSettings'], PHP_INT_MAX);
        add_action('show_user_profile', [$this, 'renderUserProfileSettings'], PHP_INT_MAX);

        add_action('personal_options_update', [$this, 'saveTaxFields']);
        add_action('edit_user_profile_update', [$this, 'saveTaxFields']);

        add_action('admin_init', [$this, 'settingsInit']);
        add_action('init', [$this, 'blockLoggedOut']);
        //add_filter('the_content', [$this, 'filterContent'], PHP_INT_MAX);
        add_action('wp', [$this, 'filterContent'], PHP_INT_MAX);
    }

    public function renderUserProfileSettings(WP_User $user) {
        /* echo '<h2>Tax Settings</h2>';
        $taxClasses = WC_Tax::get_tax_rate_classes();
        var_dump($taxClasses); */
        $tax_exempt = get_user_meta( $user->ID, 'tax_exempt', true ) == 'yes';
        ?>
        <h2>Tax Settings</h2>
        <table class="form-table" id="fieldset-billing">
			<tbody>
                <tr>
				    <th>
					    <label for="buyer_first_name">Buyer's first name:</label>
					</th>
					<td>
					    <input type="text" name="buyer_first_name" id="buyer_first_name" value="<?php echo get_user_meta($user->ID, 'buyer_first_name', true); ?>">
					</td>
				</tr>
                <tr>
				    <th>
					    <label for="buyer_first_name">Buyer's last name:</label>
					</th>
					<td>
                        <input type="text" name="buyer_last_name" id="buyer_last_name" value="<?php echo get_user_meta($user->ID, 'buyer_last_name', true); ?>">
					</td>
				</tr>
                <tr>
				    <th>
					    <label for="sales_rep_name">User statement for tax exempt:</label>
					</th>
					<td>
					    <?php echo $tax_exempt ? 'YES' : 'No'; ?>
					</td>
				</tr>
                <?php if( $tax_exempt ) : ?>
                <tr>
				    <th>
					    <label for="sales_rep_name">User uploaded tax exempt document:</label>
					</th>
					<td>
					    <a target="_blank" href="<?php echo esc_html(get_attachment_link( get_user_meta($user->ID, 'tax_exempt_doc', true) )); ?>">View Document</a>
					</td>
				</tr>
                <?php endif; ?>
            </tbody>
        </table>
        <input type="hidden" name="bhs_csrf" value="<?php echo wp_create_nonce('bhs_nonce'); ?>">
        <div>
            <input
                type="checkbox"
                name="pst_exempt"
                id="pst_exempt"
                value="yes"
                <?php checked( get_user_meta( $user->ID, 'pst_exempt', true ), 'yes', true); ?>>
            <label for="pst_exempt">PST Exempt</label>
        </div>

        <div>
            <input
                type="checkbox"
                name="total_tax_exempt"
                id="total_tax_exempt"
                value="yes"
                <?php checked( get_user_meta( $user->ID, 'total_tax_exempt', true ), 'yes', true); ?>>
            <label for="total_tax_exempt">This user should not pay any tax</label>
        </div>

        <h2>Store Data</h2>
        <table class="form-table" id="fieldset-billing">
			<tbody>
                <tr>
				    <th>
					    <label for="sales_rep_name">Sales Rep Name</label>
					</th>
					<td>
					    <input type="text" name="sales_rep_name" id="sales_rep_name" value="<?php echo get_user_meta( $user->ID, 'sales_rep_name', true ); ?>" class="regular-text">
					</td>
				</tr>
                <tr>
				    <th>
					    <label for="payment_terms">Payment Terms</label>
					</th>
					<td>
					    <input type="text" name="payment_terms" id="payment_terms" value="<?php echo get_user_meta( $user->ID, 'payment_terms', true ); ?>" class="regular-text">
					</td>
				</tr>
                <?php for( $i = 0; $i < 4; $i++ ) : ?>
                    <tr>
                        <th>
                            <label for="comment_<?php echo $i; ?>">Comment <?php echo $i + 1; ?></label>
                        </th>
                        <td>
                            <textarea name="comment_<?php echo $i; ?>" id="comment_<?php echo $i; ?>" rows="5" cols="30"><?php echo get_user_meta( $user->ID, 'comment_' . $i, true ); ?></textarea>
                        </td>
                    </tr>
                <?php endfor; ?>
                <?php for( $i = 0; $i < 4; $i++ ) : ?>
                    <tr>
                        <th>
                            <label for="remark_<?php echo $i; ?>">Remark <?php echo $i + 1; ?></label>
                        </th>
                        <td>
                        <textarea name="remark_<?php echo $i; ?>" id="remark_<?php echo $i; ?>" rows="5" cols="30"><?php echo get_user_meta( $user->ID, 'remark_' . $i, true ); ?></textarea>
                        </td>
                    </tr>
                <?php endfor; ?>
                <?php foreach ( RegistrationController::$EXTRA_FIELDS as $id => $data ) : ?>
                    <tr>
                        <th>
                            <label for="<?php echo $id; ?>"><?php echo $data['label']; ?></label>
                        </th>
                        <?php if( $data['type'] == 'text' ) : ?>
                            <td>
                                <input type="text" name="<?php echo $id; ?>" id="<?php echo $id; ?>" value="<?php echo get_user_meta( $user->ID, $id, true ); ?>" class="regular-text">
                            </td>
                        <?php elseif( $data['type'] == 'checkbox' ) : ?>
                            <td>
                                <input type="checkbox" name="<?php echo $id; ?>" id="<?php echo $id; ?>" value="<?php echo $data['value']; ?>" <?php checked( get_user_meta( $user->ID, $id, true ), $data['value'], true ); ?>>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
			</tbody>
        </table>
        <?php
    }

    public function saveTaxFields($user_id) {
        if( !current_user_can('edit_user', $user_id) || !wp_verify_nonce( $_POST['bhs_csrf'], 'bhs_nonce' ) ) return;

        if( isset($_POST['pst_exempt']) && $_POST['pst_exempt'] == 'yes' )
            update_user_meta( $user_id, 'pst_exempt', 'yes' );
        else
            delete_user_meta( $user_id, 'pst_exempt' );

        if( isset($_POST['total_tax_exempt']) && $_POST['total_tax_exempt'] == 'yes' )
            update_user_meta( $user_id, 'total_tax_exempt', 'yes' );
        else
            delete_user_meta( $user_id, 'total_tax_exempt' );

        update_user_meta( $user_id, 'sales_rep_name', $_POST['sales_rep_name'] );
        update_user_meta( $user_id, 'payment_terms', $_POST['payment_terms'] );

        for( $i = 0; $i < 4; $i++ ) {
            update_user_meta( $user_id, 'comment_' . $i, $_POST['comment_' . $i] );
            update_user_meta( $user_id, 'remark_' . $i, $_POST['remark_' . $i] );
        }

        // Check account activation
        $verified = get_user_meta( $user_id, 'is_approved', true ) == 'yes';
        if( !$verified && $_POST['is_approved'] == 'yes' ) {
            $user = get_user_by( 'ID', $user_id );
            $message = '<p><strong>Dear ' . $user->first_name  . ' ' . $user->last_name . '</strong></p>
                <p>Your account at ' . get_bloginfo( 'name' ) . ' has been reviewed and verified.<br/>You can now login to your account at <a href="' . esc_html( home_url() ) . '">' . esc_html( home_url() ) . '</a></p>';

            add_filter( 'wp_mail_from_name', function() { return get_bloginfo( 'name' ); } );
            wp_mail(
                $user->user_email,
                'Your account has been verified',
                $message,
                $headers = ['Content-Type: text/html; charset=UTF-8']
            );
        }
        foreach ( RegistrationController::$EXTRA_FIELDS as $id => $data ) {
            if( $data['type'] == 'text' )
                update_user_meta($user_id, $id, $_POST[$id]);
            elseif( $data['type'] == 'checkbox' ) {
                if( isset( $_POST[$id]) && $_POST[$id] == $data['value'] )
                    update_user_meta( $user_id, $id, $_POST[$id] );
                else
                    delete_user_meta( $user_id, $id );
            }
        }
    }

    public function settingsInit() {
        register_setting('reading', 'jg_settings');

        add_settings_section(
            'jg_settings_section',
            'Page View Restrictions',
            function() {},
            'reading'
        );

        add_settings_field(
            'jg_unblocked_pages_field',
            'Pages that logged out users can see',
            [$this, 'unblockedPagesField'],
            'reading',
            'jg_settings_section'
        );

        add_settings_field(
            'jg_unblocked_pages_active',
            'Block all other pages from logged out users',
            [$this, 'unblockedPagesCheckbox'],
            'reading',
            'jg_settings_section'
        );

        add_settings_field(
            'jg_block_woo_pages_active',
            'Block all woocommerce pages from logged out users',
            [$this, 'unblockWooPagesCheckbox'],
            'reading',
            'jg_settings_section'
        );
    }

    public function unblockedPagesField() {
        $setting = get_option('jg_settings', []);
        ?>
        <textarea name="jg_settings[unblocked_pages]" rows="5" cols="30"><?php echo isset( $setting['unblocked_pages'] ) ? esc_attr( $setting['unblocked_pages'] ) : ''; ?></textarea>
        <?php
    }

    public function unblockedPagesCheckbox() {
        $setting = get_option('jg_settings', []);
        $checked = isset( $setting['unblocked_active'] ) ? $setting['unblocked_active'] : false;
        ?>
        <input type="checkbox" name="jg_settings[unblocked_active]" value="yes" <?php checked($checked, 'yes', true); ?>>
        <?php
    }

    public function unblockWooPagesCheckbox() {
        $setting = get_option('jg_settings', []);
        $checked = isset( $setting['unblockwoo_active'] ) ? $setting['unblockwoo_active'] : false;
        ?>
        <input type="checkbox" name="jg_settings[unblockwoo_active]" value="yes" <?php checked($checked, 'yes', true); ?>>
        <?php
    }

    public function blockLoggedOut() {
        $options = get_option('jg_settings', ['unblocked_active' => false, 'unblockwoo_active' => false, 'unblocked_pages' => '']);
        
        if(!is_user_logged_in()) {
            if( isset($options['unblocked_active']) && $options['unblocked_active'] == 'yes' ) {
                $whitelist_pages = ['/wp-login.php', '/wp-admin/admin-ajax.php', '/', '' ];
                $allowed_pages = explode(PHP_EOL, $options['unblocked_pages']);
                $allowed_pages = array_merge( $allowed_pages, $whitelist_pages );
                $allowed_pages = array_map( 'trim', $allowed_pages );
                $home_url = home_url();

                $actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
                $link = substr( $actual_link, strlen($home_url) );

                if( !in_array($link, $allowed_pages) ) {
                    wp_redirect( home_url() );
                    exit;
                }
            }
            if( isset($options['unblockwoo_active']) && $options['unblockwoo_active'] == 'yes' && function_exists( 'is_woocommerce' ) && is_woocommerce() ) {
                wp_redirect( home_url() );
                exit;
            }
        }
    }

    public function filterContent() {
        $options = get_option('jg_settings', ['unblocked_active' => false, 'unblockwoo_active' => false, 'unblocked_pages' => '']);
        
        if( !is_user_logged_in() && isset($options['unblockwoo_active']) &&
            $options['unblockwoo_active'] == 'yes' && function_exists( 'is_woocommerce' ) && is_woocommerce() ) {
            echo '<script>window.location.href="' . wp_redirect( home_url() ) . '"</script>';
            exit;
        }
        return $content;
    }
}