<?php
namespace BHS\Jorgel;

use \WC_Countries as WC_Countries;
use \WP_User as WP_User;
use \WP_Error as WP_Error;

class RegistrationController {
    static $EXTRA_FIELDS = [
        'ownerFirstName'        => [
            'label'             => "Shop owner's first name",
            'type'              => 'text'
        ],
        'ownerLastName'         => [
            'label'             => "Shop owner's last name",
            'type'              => 'text'
        ],
        'shopName'              => [
            'label'             => "Shop name",
            'type'              => 'text'
        ],
        'companyName'           => [
            'label'             => "Registered company name",
            'type'              => 'text'
        ],
        'billing_phone_1'       => [
            'label'             => "Phone 1",
            'type'              => 'text'
        ],
        'billing_phone1_note'   => [
            'label'             => "Phone 1 note",
            'type'              => 'text'
        ],
        'billing_phone_2'       => [
            'label'             => 'Phone 2',
            'type'              => 'text'
        ],
        'billing_phone2_note'   => [
            'label'             => "Phone 2 note",
            'type'              => 'text'
        ],
        'fax'                   => [
            'label'             => "Fax",
            'type'              => 'text'
        ],
        'is_approved'           => [
            'label'             => 'Account approved?',
            'type'              => 'checkbox',
            'value'             => 'yes'
        ]
    ];

    public function __construct() {
        add_filter('jg_prepare_scripts', [$this, 'prepareScripts'], 1);
        add_action('jg_validate_ajax', [$this, 'validateAjaxSubmission']);
        add_action('wp_enqueue_scripts', [$this, 'registerAssets']);

        add_shortcode('jg_registration_form', [$this, 'renderRegistrationForm']);

        add_action('wp_ajax_jg_validate_user_data', [$this, 'validateUserData']);
        add_action('wp_ajax_nopriv_jg_validate_user_data', [$this, 'validateUserData']);

        add_action('wp_ajax_js_register', [$this, 'registerUser']);
        add_action('wp_ajax_nopriv_js_register', [$this, 'registerUser']);

        add_action('wp_ajax_bhsLoadStates', [$this, 'loadStates']);
        add_action('wp_ajax_nopriv_bhsLoadStates', [$this, 'loadStates']);

        add_filter( 'wp_authenticate_user', [$this, 'authenticateActivation']);

        add_filter('wp_new_user_notification_email_admin', [$this, 'filterAdminEmailSubject'], PHP_INT_MAX, 3);
        add_filter( 'manage_users_columns', [$this, 'registerUsersColumns'] );
        add_filter( 'manage_users_custom_column', [$this, 'renderColumnsData'], 10, 3 );

        add_action('pre_user_query', [$this, 'addShopNameField']);
    }

    public function registerAssets() {
        wp_register_script('jg_registration_scripts', BHS_TAX_URL . 'assets/js/dist/registration.js', [], .2, true);
    }

    public function prepareScripts($data) {
        $data['server']['url'] = admin_url( 'admin-ajax.php' );
        $data['server']['csrf'] = wp_create_nonce( 'jg_csrf' );
        return $data;
    }

    public function validateAjaxSubmission() {
        $isValid = check_ajax_referer('jg_csrf', 'csrf', false);
        if (!$isValid) {
            wp_send_json([
                'result'    => 'fail',
                'error'     => 'Your session has expired, please try refreshing the page.'
            ], 200);
        }
    }

    public function renderRegistrationForm() {
        global $woocommerce;
        $countries_obj          = new WC_Countries();
        $countries              = $countries_obj->__get('countries');
        $default_country        = $countries_obj->get_base_country();
        $default_county_states  = $countries_obj->get_states( $default_country );
        //if( is_user_logged_in() ) return;
        $scriptData = apply_filters('jg_prepare_scripts', [
            'countriesList'     => $countries,
            'defaultCountry'    => $default_country,
            'states'            => [ $default_country => $default_county_states]
        ]);

        wp_enqueue_script('jg_registration_scripts');
        wp_localize_script('jg_registration_scripts', 'jg_data', $scriptData );
        $styles = "<style>
        .MuiContainer-root input[type='email'],
        .MuiContainer-root input[type='number'],
        .MuiContainer-root input[type='text'],
        .MuiContainer-root input[type='password'],
        .MuiContainer-root textarea,
        .MuiContainer-root select {
            padding: 16.5px 14px;
            height: 1.4375em;
            border: none;
        }</style>";
        return '<div id="jg_app"></div>' . $styles;
    }

    public function validateUserData() {
        do_action('jg_validate_ajax');
        $errors = [];

        if( empty($_POST['email']) || !is_email($_POST['email']) )
            $errors['email'] = 'Please enter a valid email address';
        elseif( email_exists($_POST['email']) )
            $errors['email'] = 'This email is associated with an existing account';

        /* if( empty($_POST['password']) || strlen($_POST['password']) < 5 )
            $errors['password'] = 'Please choose a more secure password';

        if( $_POST['password'] != $_POST['password_confirmed'] )
            $errors['password_confirmed'] = 'Please make sure passwords match'; */

        if( !empty($errors) ) {
            wp_send_json([
                'result'    => 'error',
                'errors'    => $errors
            ], 200);
        }

        wp_send_json(['result' => 'success'], 200);
    }

    public function registerUser() {
        do_action('jg_validate_ajax');

        $reuired = [
            'shopName'  => 'shop name',
            'ownerFirstName'=> 'first name',
            'ownerLastName' => 'last name',
            'phone_1'   => 'phone no.',
            'country'   => 'country',
            'state'     => 'state',
            'city'      => 'city',
            'address_1' => 'address',
            'postcode'  => 'zip / postal code'
        ];
        $errors  = [];

        foreach( $reuired as $field => $label ) {
            if( !isset($_POST[$field]) || empty($_POST[$field]) ) {
                $errors[$field] = 'Please enter a valid ' . $label;
            }
        }
        if( !empty($errors) ) {
            wp_send_json([
                'result' => 'error',
                'errors' => $errors
            ], 200);
        }

        $user_id = wp_insert_user([
            'user_email'    => $_POST['email'],
            'user_pass'     => wp_generate_password( 8, true, true ), //$_POST['password'],
            'user_login'    => $_POST['email'],
            'display_name'  => $_POST['shopName'],
            'first_name'    => $_POST['ownerFirstName'],
            'last_name'     => $_POST['ownerLastName'],
            'meta_input'    => [
                'ownerFirstName'        => $_POST['ownerFirstName'],
                'ownerLastName'         => $_POST['ownerLastName'],
                'shopName'              => $_POST['shopName'],
                'companyName'           => $_POST['registeredCompanyName'],
                'billing_phone_1'       => $_POST['phone_1'],
                'billing_phone_2'       => $_POST['phone_2'],
                'billing_phone1_note'   => $_POST['phone_1_note'],
                'billing_phone2_note'   => $_POST['phone_2_note'],
                'fax'                   => $_POST['fax'],
                'is_approved'           => false,
                'billing_country'       => $_POST['country'],
                'billing_state'         => $_POST['state'],
                'billing_city'          => $_POST['city'],
                'billing_address_1'     => $_POST['address_1'],
                'billing_address_2'     => $_POST['address_2'],
                'billing_postcode'      => $_POST['postcode'],
                'tax_exempt'            => $_POST['tax_exempt']
            ]
        ]);

        if( is_wp_error($user_id) ) {
            wp_send_json([
                'result'    => 'fail',
                'error'     => $user_id->get_error_message()
            ], 200);
        }

        if( $_POST['tax_exempt'] ==  'yes' ) {
            $attachment_id = media_handle_upload('tax_doc', 0);
            update_user_meta( $user_id, 'tax_exempt_doc', $attachment_id );
        }

        wp_send_new_user_notifications($user_id, 'admin');

        wp_send_json([
            'result'    => 'success'
        ], 200);
    }

    public function authenticateActivation(WP_User $user) {
        if( in_array('administrator', (array)$user->roles) ) return $user;

        $approved = get_user_meta($user->ID, 'is_approved', true ) == 'yes';
        if( !$approved ) {
            return new WP_Error('not_verified', 'Your account is pending review. Please wait for it to be verified.');
        }
        
        return $user;
    }

    public function loadStates() {
        do_action('jg_validate_ajax');
        global $woocommerce;
        $countries_obj  = new WC_Countries();
        $countries      = $countries_obj->__get('countries');
        $states         = $countries_obj->get_states( $_POST['country'] );
        wp_send_json([
            'result'    => 'success',
            'states'    => $states
        ], 200);
    }

    public function registerUsersColumns( $columns ) {
        $columns['approved'] = 'Approved';
        $columns['shopname'] = 'Shop Name';
        return $columns;
    }

    public function renderColumnsData( $val, $column_name, $user_id ) {
        switch ($column_name) {
            case 'approved' :
                return get_user_meta( $user_id, 'is_approved', true ) == 'yes' ? '<span style="background-color:green; color:white; border-radius:4px;padding:2px 4px">Yes</span>' : '<span style="background-color:red; color:white; border-radius:4px;padding:2px 4px">No</span>';
            case 'shopname':
                return get_user_meta( $user_id, 'shopName', true );
            default:
        }
        return $val;
    }

    public function filterAdminEmailSubject($data, $user, $blogname) {
        $data['subject'] = get_user_meta($user->ID, 'shopName', true) . ' ' . $data['subject'];
    }

    function addShopNameField( $uqi ){
        global $wpdb;

        $search = '';
        if ( isset( $uqi->query_vars['search'] ) )
            $search = trim( $uqi->query_vars['search'] );
    
        if ( $search ) {
            $search = trim($search, '*');
            $the_search = '%'.$search.'%';
    
            $search_meta = $wpdb->prepare("
            ID IN ( SELECT user_id FROM {$wpdb->usermeta}
            WHERE ( ( meta_key='first_name' OR meta_key='last_name' OR meta_key='shopName'  )
                AND {$wpdb->usermeta}.meta_value LIKE '%s' )
            )", $the_search);
    
            $uqi->query_where = str_replace(
                'WHERE 1=1 AND (',
                "WHERE 1=1 AND (" . $search_meta . " OR ",
                $uqi->query_where );
        }
    }
}