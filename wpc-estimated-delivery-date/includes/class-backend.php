<?php
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Wpced_Backend' ) ) {
    class Wpced_Backend {
        protected static $instance = null;
        protected static $settings = [];
        protected static $rules = [];
        protected static $zones = [];
        protected static $methods = [];
        protected static $archive_pos = [];
        protected static $single_pos = [];
        protected static $base_rule = [
                'name'          => '',
                'apply'         => 'all',
                'apply_compare' => 'equal',
                'apply_number'  => '0',
                'apply_val'     => [],
                'zone'          => 'all',
                'method'        => 'all',
                'min'           => '5',
                'max'           => '10',
                'scheduled'     => ''
        ];

        public static function instance() {
            if ( is_null( self::$instance ) ) {
                self::$instance = new self();
            }

            return self::$instance;
        }

        public function __construct() {
            self::$settings = (array) get_option( 'wpced_settings', [] );
            self::$rules    = (array) get_option( 'wpced_rules', [] );

            // Init
            add_action( 'init', [ $this, 'init' ] );

            // Enqueue
            add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );

            // Settings
            add_action( 'admin_init', [ $this, 'register_settings' ] );
            add_filter( 'pre_update_option', [ $this, 'last_saved' ], 10, 2 );
            add_action( 'admin_menu', [ $this, 'admin_menu' ] );

            // Links
            add_filter( 'plugin_action_links', [ $this, 'action_links' ], 10, 2 );
            add_filter( 'plugin_row_meta', [ $this, 'row_meta' ], 10, 2 );

            // Single Product
            add_filter( 'woocommerce_product_data_tabs', [ $this, 'product_data_tabs' ] );
            add_action( 'woocommerce_product_data_panels', [ $this, 'product_data_panels' ] );
            add_action( 'woocommerce_process_product_meta', [ $this, 'process_product_meta' ] );

            // Variation
            add_action( 'woocommerce_product_after_variable_attributes', [
                    $this,
                    'variation_settings'
            ], 99, 3 );
            add_action( 'woocommerce_save_product_variation', [ $this, 'save_variation_settings' ], 99, 2 );

            // WPC Variation Duplicator
            add_action( 'wpcvd_duplicated', [ $this, 'duplicate_variation' ], 99, 2 );

            // WPC Variation Bulk Editor
            add_action( 'wpcvb_bulk_update_variation', [ $this, 'bulk_update_variation' ], 99, 2 );

            // Export
            add_filter( 'woocommerce_product_export_meta_value', [ $this, 'export_process' ], 10, 3 );

            // Import
            add_filter( 'woocommerce_product_import_pre_insert_product_object', [
                    $this,
                    'import_process'
            ], 10, 2 );

            // AJAX
            add_action( 'wp_ajax_wpced_add_rule', [ $this, 'ajax_add_rule' ] );
            add_action( 'wp_ajax_wpced_add_date', [ $this, 'ajax_add_date' ] );
            add_action( 'wp_ajax_wpced_search_term', [ $this, 'ajax_search_term' ] );
            add_action( 'wp_ajax_wpced_date_format_preview', [ $this, 'ajax_date_format_preview' ] );
            add_action( 'wp_ajax_wpced_add_apply_condition', [ $this, 'ajax_add_apply_condition' ] );
            add_action( 'wp_ajax_wpced_simulate', [ $this, 'ajax_simulate' ] );

            // Backend Order
            add_action( 'woocommerce_order_item_add_action_buttons', [ $this, 'add_action_buttons' ] );
            add_action( 'wp_ajax_wpced_get_order_dates', [ $this, 'ajax_get_order_dates' ] );
            add_action( 'wp_ajax_wpced_save_order_dates', [ $this, 'ajax_save_order_dates' ] );
        }

        function init() {
            // load text-domain
            load_plugin_textdomain( 'wpc-estimated-delivery-date', false, basename( WPCED_DIR ) . '/languages/' );

            self::$archive_pos = apply_filters( 'wpced_archive_positions', [
                    'under_title'       => esc_html__( 'Under title', 'wpc-estimated-delivery-date' ),
                    'under_rating'      => esc_html__( 'Under rating', 'wpc-estimated-delivery-date' ),
                    'under_price'       => esc_html__( 'Under price', 'wpc-estimated-delivery-date' ),
                    'above_add_to_cart' => esc_html__( 'Above add to cart', 'wpc-estimated-delivery-date' ),
                    'under_add_to_cart' => esc_html__( 'Under add to cart', 'wpc-estimated-delivery-date' ),
                    'none'              => esc_html__( 'None (hide it)', 'wpc-estimated-delivery-date' ),
            ] );
            self::$single_pos  = apply_filters( 'wpced_single_positions', [
                    '6'  => esc_html__( 'Under title', 'wpc-estimated-delivery-date' ),
                    '11' => esc_html__( 'Under price & rating', 'wpc-estimated-delivery-date' ),
                    '21' => esc_html__( 'Under excerpt', 'wpc-estimated-delivery-date' ),
                    '31' => esc_html__( 'Under add to cart', 'wpc-estimated-delivery-date' ),
                    '41' => esc_html__( 'Under meta', 'wpc-estimated-delivery-date' ),
                    '51' => esc_html__( 'Under sharing', 'wpc-estimated-delivery-date' ),
                    '0'  => esc_html__( 'None (hide it)', 'wpc-estimated-delivery-date' ),
            ] );
        }

        public static function get_settings() {
            return apply_filters( 'wpced_get_settings', self::$settings );
        }

        public static function get_setting( $name, $default = false ) {
            if ( isset( self::$settings[ $name ] ) && ( self::$settings[ $name ] !== '' ) ) {
                $setting = self::$settings[ $name ];
            } else {
                $setting = get_option( 'wpced_' . $name, $default );
            }

            return apply_filters( 'wpced_get_setting', $setting, $name, $default );
        }

        public static function get_rules() {
            return apply_filters( 'wpced_get_rules', self::$rules );
        }

        public static function get_archive_positions() {
            return self::$archive_pos;
        }

        public static function get_single_positions() {
            return self::$single_pos;
        }

        public function product_data_tabs( $tabs ) {
            $tabs['wpced'] = [
                    'label'  => esc_html__( 'Estimated Delivery Date', 'wpc-estimated-delivery-date' ),
                    'target' => 'wpced_settings'
            ];

            return $tabs;
        }

        public function product_data_panels() {
            global $post, $thepostid, $product_object;

            if ( $product_object instanceof WC_Product ) {
                $product_id = $product_object->get_id();
            } elseif ( is_numeric( $thepostid ) ) {
                $product_id = $thepostid;
            } elseif ( $post instanceof WP_Post ) {
                $product_id = $post->ID;
            } else {
                $product_id = 0;
            }

            if ( ! $product_id ) {
                ?>
                <div id="wpced_settings" class="wpced-product-settings panel woocommerce_options_panel">
                    <p style="padding: 0 12px; color: #c9356e"><?php esc_html_e( 'Product wasn\'t returned.', 'wpc-estimated-delivery-date' ); ?></p>
                </div>
                <?php
                return;
            }

            echo '<div id="wpced_settings" class="wpced-product-settings panel woocommerce_options_panel">';
            self::settings_form( $product_id );
            echo '</div>';
        }

        function variation_settings( $loop, $variation_data, $variation ) {
            $variation_id = absint( $variation->ID );
            ?>
            <div class="form-row form-row-full wpced-variation-settings">
                <label><?php esc_html_e( 'WPC Estimated Delivery Date', 'wpc-estimated-delivery-date' ); ?></label>
                <div class="wpced-variation-wrap wpced-variation-wrap-<?php echo esc_attr( $variation_id ); ?>">
                    <?php self::settings_form( $variation_id, true ); ?>
                </div>
            </div>
            <?php
        }

        function settings_form( $product_id, $is_variation = false ) {
            $enable = get_post_meta( $product_id, 'wpced_enable', true ) ?: 'global';
            $rules  = get_post_meta( $product_id, 'wpced_rules', true ) ?: [];
            ?>
            <div class='wpced-settings'>
                <div class="wpced-select-wrapper">
                    <label>
                        <span><?php esc_html_e( 'Estimated Delivery Date', 'wpc-estimated-delivery-date' ); ?></span>
                        <select name="<?php echo esc_attr( $is_variation ? 'wpced_enable_v[' . $product_id . ']' : 'wpced_enable' ); ?>"
                                class="wpced-select-enable">
                            <option value="global" <?php selected( $enable, 'global' ); ?>><?php esc_html_e( 'Global', 'wpc-estimated-delivery-date' ); ?></option>
                            <?php if ( $is_variation ) { ?>
                                <option value="parent" <?php selected( $enable, 'parent' ); ?>><?php esc_html_e( 'Parent', 'wpc-estimated-delivery-date' ); ?></option>
                            <?php } ?>
                            <option value="disable" <?php selected( $enable, 'disable' ); ?>><?php esc_html_e( 'Disable', 'wpc-estimated-delivery-date' ); ?></option>
                            <option value="override" <?php selected( $enable, 'override' ); ?>><?php esc_html_e( 'Override', 'wpc-estimated-delivery-date' ); ?></option>
                        </select> </label>
                </div>
                <div class="wpced-single-product"
                     style="display: <?php echo esc_attr( $enable === 'override' ? 'block' : 'none' ); ?>;">
                    <div class="wpced-items-wrapper">
                        <div class="wpced-items">
                            <?php
                            if ( ! isset( $rules['default'] ) ) {
                                $key  = 'default';
                                $rule = [];
                            } else {
                                $key  = 'default';
                                $rule = $rules['default'];
                            }

                            include WPCED_DIR . 'includes/templates/rule.php';
                            ?>
                        </div>
                        <div class="wpced-items wpced-rules">
                            <?php
                            if ( isset( $rules['default'] ) ) {
                                unset( $rules['default'] );
                            }

                            foreach ( $rules as $key => $rule ) {
                                include WPCED_DIR . 'includes/templates/rule.php';
                            }
                            ?>
                        </div>
                    </div>
                    <div class="wpced-items-new">
                        <input type="button" class="button wpced-item-new"
                               data-product_id="<?php echo esc_attr( $product_id ); ?>"
                               data-is_variation="<?php echo esc_attr( $is_variation ? 'true' : 'false' ); ?>"
                               value="<?php esc_attr_e( '+ Add rule', 'wpc-estimated-delivery-date' ); ?>">
                    </div>
                </div>
            </div>
            <?php
        }

        public function process_product_meta( $post_id ) {
            if ( isset( $_POST['wpced_enable'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified by WooCommerce via woocommerce_process_product_meta hook before this callback is invoked
                update_post_meta( $post_id, 'wpced_enable', sanitize_text_field( wp_unslash( $_POST['wpced_enable'] ?? '' ) ) );
            }

            if ( isset( $_POST['wpced_rules'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified by WooCommerce via woocommerce_process_product_meta hook before this callback is invoked
                update_post_meta( $post_id, 'wpced_rules', Wpced_Helper()->sanitize_array( wp_unslash( $_POST['wpced_rules'] ?? '' ) ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized via Wpced_Helper()->sanitize_array() which recursively applies sanitize_post_field()
            } else {
                delete_post_meta( $post_id, 'wpced_rules' );
            }
        }

        function save_variation_settings( $post_id ) {
            if ( isset( $_POST['wpced_enable_v'][ $post_id ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified by WooCommerce via woocommerce_save_product_variation hook before this callback is invoked
                update_post_meta( $post_id, 'wpced_enable', sanitize_text_field( wp_unslash( $_POST['wpced_enable_v'] ?? '' )[ $post_id ] ) );
            } else {
                delete_post_meta( $post_id, 'wpced_enable' );
            }

            if ( isset( $_POST['wpced_rules_v'][ $post_id ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified by WooCommerce via woocommerce_save_product_variation hook before this callback is invoked
                update_post_meta( $post_id, 'wpced_rules', Wpced_Helper()->sanitize_array( wp_unslash( $_POST['wpced_rules_v'] ?? '' )[ $post_id ] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized via Wpced_Helper()->sanitize_array() which recursively applies sanitize_post_field()
            } else {
                delete_post_meta( $post_id, 'wpced_rules' );
            }
        }

        function duplicate_variation( $old_variation_id, $new_variation_id ) {
            if ( $enable = get_post_meta( $old_variation_id, 'wpced_enable', true ) ) {
                update_post_meta( $new_variation_id, 'wpced_enable', $enable );
            }

            if ( $rules = get_post_meta( $old_variation_id, 'wpced_rules', true ) ) {
                update_post_meta( $new_variation_id, 'wpced_rules', $rules );
            }
        }

        function bulk_update_variation( $variation_id, $fields ) {
            if ( ! empty( $fields['wpced_enable_v'] ) && ( $fields['wpced_enable_v'] !== 'wpcvb_no_change' ) ) {
                update_post_meta( $variation_id, 'wpced_enable', sanitize_text_field( $fields['wpced_enable_v'] ) );
            }

            if ( ! empty( $fields['wpced_enable_v'] ) && ( $fields['wpced_enable_v'] === 'override' ) && ! empty( $fields['wpced_rules_v'] ) ) {
                update_post_meta( $variation_id, 'wpced_rules', Wpced_Helper()->sanitize_array( $fields['wpced_rules_v'] ) );
            }
        }

        function export_process( $value, $meta, $product ) {
            if ( $meta->key === 'wpced_rules' ) {
                $ids = get_post_meta( $product->get_id(), 'wpced_rules', true );

                if ( ! empty( $ids ) && is_array( $ids ) ) {
                    return json_encode( $ids );
                }
            }

            return $value;
        }

        function import_process( $object, $data ) {
            if ( isset( $data['meta_data'] ) ) {
                foreach ( $data['meta_data'] as $meta ) {
                    if ( $meta['key'] === 'wpced_rules' ) {
                        $object->update_meta_data( 'wpced_rules', json_decode( $meta['value'], true ) );
                        break;
                    }
                }
            }

            return $object;
        }

        function register_settings() {
            // settings
            register_setting( 'wpced_settings', 'wpced_settings', [
                    'type'              => 'array',
                    'sanitize_callback' => [ 'Wpced_Helper', 'sanitize_array' ],
            ] );
            register_setting( 'wpced_settings', 'wpced_rules', [
                    'type'              => 'array',
                    'sanitize_callback' => [ 'Wpced_Helper', 'sanitize_array' ],
            ] );
        }

        function last_saved( $value, $option ) {
            if ( $option == 'wpced_settings' ) {
                $value['_last_saved']    = current_time( 'timestamp' );
                $value['_last_saved_by'] = get_current_user_id();
            }

            return $value;
        }

        public function admin_menu() {
            add_submenu_page( 'wpclever', 'WPC Estimated Delivery Date', 'Estimated Delivery Date', 'manage_options', 'wpclever-wpced', [
                    $this,
                    'admin_menu_content'
            ] );
        }

        public function admin_menu_content() {
            include WPCED_DIR . 'includes/templates/settings.php';
        }

        function action_links( $links, $file ) {
            static $plugin;

            if ( ! isset( $plugin ) ) {
                $plugin = plugin_basename( WPCED_FILE );
            }

            if ( $plugin === $file ) {
                $settings = '<a href="' . esc_url( admin_url( 'admin.php?page=wpclever-wpced&tab=settings' ) ) . '">' . esc_html__( 'Settings', 'wpc-estimated-delivery-date' ) . '</a>';
                //$links['wpc-premium']       = '<a href="' . esc_url( admin_url( 'admin.php?page=wpclever-wpced&tab=premium' ) ) . '" style="color: #c9356e">' . esc_html__( 'Premium Version', 'wpc-estimated-delivery-date' ) . '</a>';
                array_unshift( $links, $settings );
            }

            return (array) $links;
        }

        function row_meta( $links, $file ) {
            static $plugin;

            if ( ! isset( $plugin ) ) {
                $plugin = plugin_basename( WPCED_FILE );
            }

            if ( $plugin === $file ) {
                $row_meta = [
                        'support' => '<a href="' . esc_url( WPCED_DISCUSSION ) . '" target="_blank">' . esc_html__( 'Community support', 'wpc-estimated-delivery-date' ) . '</a>',
                ];

                return array_merge( $links, $row_meta );
            }

            return (array) $links;
        }

        public function ajax_add_rule() {
            $key          = Wpced_Helper()->generate_key();
            $product_id   = absint( sanitize_text_field( wp_unslash( $_POST['product_id'] ?? 0 ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- read-only AJAX handler that only renders UI template, does not write to DB
            $is_variation = wc_string_to_bool( sanitize_text_field( wp_unslash( $_POST['is_variation'] ?? 'no' ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- read-only AJAX handler that only renders UI template, does not write to DB
            $rule_name    = $is_variation ? 'wpced_rules_v' : 'wpced_rules';
            $rule_data = wp_unslash( $_POST['rule_data'] ?? '' ); // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- read-only AJAX handler; URL-encoded string decoded via parse_str() then sanitized via Wpced_Helper()->sanitize_array()
            $rule_arr  = [];

            if ( ! empty( $rule_data ) ) {
                $form_rule = [];
                parse_str( $rule_data, $form_rule );
                $form_rule = Wpced_Helper()->sanitize_array( $form_rule );

                if ( isset( $form_rule[ $rule_name ] ) && is_array( $form_rule[ $rule_name ] ) ) {
                    $rule_arr = reset( $form_rule[ $rule_name ] );

                    if ( $is_variation && is_array( $rule_arr ) ) {
                        $rule_arr = reset( $rule_arr );
                    }
                }
            }

            if ( ! empty( $key ) ) {
                $active = true;
                $rule   = array_merge( self::$base_rule, $rule_arr );
                include WPCED_DIR . 'includes/templates/rule.php';
            }

            wp_die();
        }

        public function ajax_add_apply_condition() {
            $rule_key       = sanitize_text_field( wp_unslash( $_POST['rule_key'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- read-only AJAX handler that only renders apply condition UI, does not write to DB
            $name           = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- read-only AJAX handler that only renders apply condition UI, does not write to DB
            $condition_key  = Wpced_Helper()->generate_key();
            $condition_item = [];

            echo self::render_apply_condition( $rule_key, $name, $condition_key, $condition_item ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML is fully escaped internally by render_apply_condition() using esc_attr() and esc_html() on all dynamic values

            wp_die();
        }

        public function ajax_add_date() {
            $date         = [];
            $date_context = sanitize_text_field( wp_unslash( $_POST['context'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- read-only AJAX handler that only renders date template, does not write to DB
            include WPCED_DIR . 'includes/templates/date.php';

            wp_die();
        }

        public function enqueue_scripts( $hook ) {
            $screen = get_current_screen();

            // Only enqueue on the plugin settings page or product add/edit pages.
            if ( ! str_contains( $hook, 'wpced' ) && ! ( $screen && $screen->post_type === 'product' && in_array( $screen->base, [ 'post', 'post-new' ], true ) ) ) {
                return;
            }

            // hint
            wp_enqueue_style( 'hint', WPCED_URI . 'assets/css/hint.css', [], WPCED_VERSION );

            // wpcdpk
            wp_enqueue_style( 'wpcdpk', WPCED_URI . 'assets/libs/wpcdpk/css/datepicker.css', [], WPCED_VERSION );
            wp_enqueue_script( 'wpcdpk', WPCED_URI . 'assets/libs/wpcdpk/js/datepicker.js', [ 'jquery' ], WPCED_VERSION, true );

            wp_enqueue_style( 'wpced-backend', WPCED_URI . 'assets/css/backend.css', [ 'woocommerce_admin_styles' ], WPCED_VERSION );
            wp_enqueue_script( 'wpced-backend', WPCED_URI . 'assets/js/backend.js', [
                    'jquery',
                    'jquery-ui-sortable',
                    'wc-enhanced-select',
                    'jquery-ui-dialog',
                    'selectWoo'
            ], WPCED_VERSION, true );
            wp_localize_script( 'wpced-backend', 'wpced_vars', [
                    'nonce' => wp_create_nonce( 'wpced-security' )
            ] );
        }

        function ajax_search_term() {
            $return = [];

            $args = [
                    'taxonomy'   => sanitize_text_field( wp_unslash( $_REQUEST['taxonomy'] ?? '' ) ),
                    'orderby'    => 'id',
                    'order'      => 'ASC',
                    'hide_empty' => false,
                    'fields'     => 'all',
                    'name__like' => sanitize_text_field( wp_unslash( $_REQUEST['q'] ?? '' ) ),
            ];

            $terms = get_terms( $args );

            if ( count( $terms ) ) {
                foreach ( $terms as $term ) {
                    $return[] = [ $term->slug, $term->name ];
                }
            }

            wp_send_json( $return );
        }

        function ajax_date_format_preview() {
            /* translators: %s: date preview */
            echo sprintf( esc_html__( 'Preview: %s', 'wpc-estimated-delivery-date' ), esc_html( current_time( sanitize_text_field( wp_unslash( $_POST['date_format'] ?? '' ) ) ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- read-only AJAX handler that only previews date format, does not write to DB
            wp_die();
        }

        function ajax_get_order_dates() {
            if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'wpced-security' ) || ! current_user_can( 'manage_woocommerce' ) ) {
                die( esc_html__( 'Permissions check failed!', 'wpc-estimated-delivery-date' ) );
            }

            $order_id = absint( wp_unslash( $_POST['order_id'] ?? 0 ) );

            if ( $order_id && ( $order = wc_get_order( $order_id ) ) ) {
                echo '<ul class="wpced-order-items" data-id="' . esc_attr( $order_id ) . '">';

                $order_items = $order->get_items( 'line_item' );

                foreach ( $order_items as $order_item ) {
                    echo '<li class="wpced-order-item" data-id="' . esc_attr( $order_item->get_id() ) . '">';
                    echo '<div class="wpced-order-item-name">' . esc_html( $order_item->get_name() ) . '</div>';
                    echo '<div class="wpced-order-item-date"><input type="text" data-id="' . esc_attr( $order_item->get_id() ) . '" class="text large-text wpced-order-item-date-val" value="' . esc_attr( wp_strip_all_tags( $order_item->get_meta( '_wpced_date' ) ) ) . '"/></div>';
                    echo '</li>';
                }

                echo '</ul>';
                echo '<div class="wpced-order-items-save"><button class="button button-primary wpced-order-items-save-btn">' . esc_html__( 'Save', 'wpc-estimated-delivery-date' ) . '</button></div>';
            }

            wp_die();
        }

        function ajax_save_order_dates() {
            if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'wpced-security' ) || ! current_user_can( 'manage_woocommerce' ) ) {
                die( esc_html__( 'Permissions check failed!', 'wpc-estimated-delivery-date' ) );
            }

            $order_id    = absint( wp_unslash( $_POST['order_id'] ?? 0 ) );
            $order_dates = Wpced_Helper()->sanitize_array( wp_unslash( $_POST['order_dates'] ?? [] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized via Wpced_Helper()->sanitize_array() which recursively applies sanitize_post_field()

            if ( ! empty( $order_dates ) ) {
                foreach ( $order_dates as $order_date ) {
                    $order_date      = array_merge( [ 'id' => 0, 'date' => '' ], $order_date );
                    $order_item_date = wp_kses_post( apply_filters( 'wpced_update_order_item_date', '<div class="wpced"><div class="wpced-inner">' . wp_strip_all_tags( $order_date['date'] ) . '</div></div>' ) );
                    wc_update_order_item_meta( $order_date['id'], '_wpced_date', $order_item_date );
                }
            }

            echo absint( $order_id );

            wp_die();
        }

        function add_action_buttons() {
            echo '<button type="button" class="button wpced-update-dates">' . esc_html__( 'Update delivery dates', 'wpc-estimated-delivery-date' ) . '</button>';
            echo '<div class="wpced-update-dates-dialog" id="wpced_update_dates_dialog" style="display: none" title="' . esc_attr__( 'Update delivery dates', 'wpc-estimated-delivery-date' ) . '"></div>';
        }

        public static function get_base_rule() {
            return self::$base_rule;
        }

        /**
         * Render HTML for a single combined apply condition item.
         *
         * @param string $rule_key       The rule key.
         * @param string $name           The name prefix (e.g. '_v[123]' for variations).
         * @param string $condition_key  Unique key for this condition.
         * @param array  $condition_item Saved condition data.
         * @return string HTML output.
         */
        public static function render_apply_condition( $rule_key, $name, $condition_key, $condition_item = [] ) {
            if ( empty( $condition_key ) ) {
                $condition_key = Wpced_Helper()->generate_key();
            }

            $condition_apply         = ! empty( $condition_item['apply'] ) ? $condition_item['apply'] : 'instock';
            $condition_apply_val     = ! empty( $condition_item['apply_val'] ) ? (array) $condition_item['apply_val'] : [];
            $condition_apply_compare = ! empty( $condition_item['apply_compare'] ) ? $condition_item['apply_compare'] : 'equal';
            $condition_apply_number  = ! empty( $condition_item['apply_number'] ) ? $condition_item['apply_number'] : '0';
            $input_name_base         = 'wpced_rules' . $name . '[' . $rule_key . '][apply_conditions][' . $condition_key . ']';

            $taxonomies = get_object_taxonomies( 'product', 'objects' );

            ob_start();
            ?>
            <div class="wpced_apply_condition">
                <span class="wpced_apply_condition_remove">&times;</span>
                <select class="wpced_apply_condition_select"
                        name="<?php echo esc_attr( $input_name_base . '[apply]' ); ?>">
                    <option value="instock" <?php selected( $condition_apply, 'instock' ); ?>><?php esc_html_e( 'In stock', 'wpc-estimated-delivery-date' ); ?></option>
                    <option value="outofstock" <?php selected( $condition_apply, 'outofstock' ); ?>><?php esc_html_e( 'Out of stock', 'wpc-estimated-delivery-date' ); ?></option>
                    <option value="backorder" <?php selected( $condition_apply, 'backorder' ); ?>><?php esc_html_e( 'On backorder', 'wpc-estimated-delivery-date' ); ?></option>
                    <option value="stock" <?php selected( $condition_apply, 'stock' ); ?>><?php esc_html_e( 'Stock quantity', 'wpc-estimated-delivery-date' ); ?></option>
                    <?php foreach ( $taxonomies as $taxonomy ) {
                        echo '<option value="' . esc_attr( $taxonomy->name ) . '" ' . selected( $condition_apply, $taxonomy->name, false ) . '>' . esc_html( $taxonomy->label ) . '</option>';
                    } ?>
                </select>
                <span class="wpced_apply_condition_stock_wrap<?php echo esc_attr( $condition_apply === 'stock' ? '' : ' wpced-hidden' ); ?>">
                    <select class="wpced_apply_condition_compare"
                            name="<?php echo esc_attr( $input_name_base . '[apply_compare]' ); ?>">
                        <option value="equal" <?php selected( $condition_apply_compare, 'equal' ); ?>><?php esc_html_e( 'Equal to', 'wpc-estimated-delivery-date' ); ?></option>
                        <option value="not_equal" <?php selected( $condition_apply_compare, 'not_equal' ); ?>><?php esc_html_e( 'Not equal to', 'wpc-estimated-delivery-date' ); ?></option>
                        <option value="greater" <?php selected( $condition_apply_compare, 'greater' ); ?>><?php esc_html_e( 'Greater than', 'wpc-estimated-delivery-date' ); ?></option>
                        <option value="greater_equal" <?php selected( $condition_apply_compare, 'greater_equal' ); ?>><?php esc_html_e( 'Greater or equal to', 'wpc-estimated-delivery-date' ); ?></option>
                        <option value="less" <?php selected( $condition_apply_compare, 'less' ); ?>><?php esc_html_e( 'Less than', 'wpc-estimated-delivery-date' ); ?></option>
                        <option value="less_equal" <?php selected( $condition_apply_compare, 'less_equal' ); ?>><?php esc_html_e( 'Less or equal to', 'wpc-estimated-delivery-date' ); ?></option>
                    </select>
                    <input type="number" step="any"
                           class="wpced_apply_condition_number"
                           name="<?php echo esc_attr( $input_name_base . '[apply_number]' ); ?>"
                           value="<?php echo esc_attr( $condition_apply_number ); ?>"/>
                </span>
                <span class="wpced_apply_condition_terms_wrap<?php echo esc_attr( in_array( $condition_apply, [ 'instock', 'outofstock', 'backorder', 'stock' ] ) ? ' wpced-hidden' : '' ); ?>">
                    <select class="wpced_apply_condition_terms" multiple="multiple"
                            name="<?php echo esc_attr( $input_name_base . '[apply_val][]' ); ?>"
                            data-taxonomy="<?php echo esc_attr( $condition_apply ); ?>">
                        <?php
                        if ( ! empty( $condition_apply_val ) && ! in_array( $condition_apply, [ 'instock', 'outofstock', 'backorder', 'stock' ] ) ) {
                            foreach ( $condition_apply_val as $t ) {
                                if ( $term = get_term_by( 'slug', $t, $condition_apply ) ) {
                                    echo '<option value="' . esc_attr( $t ) . '" selected>' . esc_html( $term->name ) . '</option>';
                                }
                            }
                        }
                        ?>
                    </select>
                </span>
            </div>
            <?php
            return ob_get_clean();
        }

        public static function get_zones() {
            $zones            = WC_Shipping_Zones::get_zones();
            self::$zones      = self::get_zones_array( $zones );
            $non_covered_zone = WC_Shipping_Zones::get_zone_by( "zone_id", 0 );

            if ( is_object( $non_covered_zone ) ) {
                $non_covered_zone_name = $non_covered_zone->get_zone_name();
                $non_covered_zone_id   = $non_covered_zone->get_id();

                if ( ! empty( $non_covered_zone_name ) ) {
                    self::$zones[ $non_covered_zone_id ] = $non_covered_zone_name;
                }
            }

            return self::$zones;
        }

        public static function get_zones_array( $zones ) {
            $zs = [];

            foreach ( $zones as $zone ) {
                $zone_obj = new WC_Shipping_Zone( $zone['zone_id'] );
                $methods  = $zone_obj->get_shipping_methods( true );

                if ( count( $methods ) > 0 ) {
                    $zs[ $zone['zone_id'] ] = $zone['zone_name'];
                }
            }

            return $zs;
        }

        public static function get_methods() {
            self::$methods = [];
            $zones         = self::get_zones();

            foreach ( $zones as $zone_id => $zone ) {
                $methods = self::get_zone_methods( $zone_id );

                foreach ( $methods as $method ) {
                    self::$methods[ $method->instance_id ] = [
                            'zone'  => $zone_id,
                            'name'  => $method->id,
                            'title' => $method->title
                    ];
                }
            }

            return self::$methods;
        }

        public static function get_zone_methods( $zone_id ) {
            $zone_obj = new WC_Shipping_Zone( $zone_id );
            $methods  = $zone_obj->get_shipping_methods( true );

            return $methods;
        }

        public function render_simulator() {
            $zones           = self::get_zones();
            $methods         = self::get_methods();
            $extra_time_line = self::get_setting( 'extra_time_line' );
            ?>
            <div class="wpced-card">
                <div class="wpced-card-header">
                    <div>
                        <h2 class="wpced-card-title">
                            <span class="dashicons dashicons-calculator"></span>
                            <?php esc_html_e( 'Delivery Simulator', 'wpc-estimated-delivery-date' ); ?>
                        </h2>
                        <p class="wpced-card-desc">
                            <?php esc_html_e( 'Select a product and simulation parameters (date & time, shipping zone, shipping method) to test and evaluate which delivery rule is matched and the calculated delivery dates.', 'wpc-estimated-delivery-date' ); ?>
                        </p>
                    </div>
                </div>

                <div class="wpced-sim-grid" id="wpced-simulator-form">
                    <div class="wpced-sim-section">
                        <h3 class="wpced-sim-section-title">
                            <span class="dashicons dashicons-products"></span>
                            <?php esc_html_e( 'Target Product & Context', 'wpc-estimated-delivery-date' ); ?>
                        </h3>
                        <div class="wpced-sim-row wpced-sim-row-product">
                            <label for="wpced-sim-product"><?php esc_html_e( 'Product', 'wpc-estimated-delivery-date' ); ?></label>
                            <select id="wpced-sim-product" class="wc-product-search"
                                    data-placeholder="<?php esc_attr_e( 'Search for a product or variation...', 'wpc-estimated-delivery-date' ); ?>"
                                    data-action="woocommerce_json_search_products_and_variations">
                            </select>
                        </div>
                        <div class="wpced-sim-row">
                            <label for="wpced-sim-zone"><?php esc_html_e( 'Shipping Zone', 'wpc-estimated-delivery-date' ); ?></label>
                            <select id="wpced-sim-zone">
                                <option value="all"><?php esc_html_e( 'All zones (Any)', 'wpc-estimated-delivery-date' ); ?></option>
                                <?php
                                if ( ! empty( $zones ) ) {
                                    foreach ( $zones as $zone_id => $zone_name ) {
                                        echo '<option value="' . esc_attr( $zone_id ) . '">' . esc_html( $zone_name ) . '</option>';
                                    }
                                }
                                ?>
                            </select>
                        </div>
                        <div class="wpced-sim-row">
                            <label for="wpced-sim-method"><?php esc_html_e( 'Shipping Method', 'wpc-estimated-delivery-date' ); ?></label>
                            <select id="wpced-sim-method">
                                <option value="all"><?php esc_html_e( 'All methods (Any)', 'wpc-estimated-delivery-date' ); ?></option>
                                <?php
                                if ( ! empty( $methods ) ) {
                                    foreach ( $methods as $method_id => $method ) {
                                        echo '<option value="' . esc_attr( $method_id ) . '" data-zone="' . esc_attr( $method['zone'] ) . '">' . esc_html( $method['title'] ) . '</option>';
                                    }
                                }
                                ?>
                            </select>
                        </div>
                    </div>

                    <div class="wpced-sim-section">
                        <h3 class="wpced-sim-section-title">
                            <span class="dashicons dashicons-clock"></span>
                            <?php esc_html_e( 'Simulated Date & Time', 'wpc-estimated-delivery-date' ); ?>
                        </h3>
                        <div class="wpced-sim-row">
                            <label for="wpced-sim-datetime"><?php esc_html_e( 'Date & Time', 'wpc-estimated-delivery-date' ); ?></label>
                            <div class="wpced-sim-datetime-wrap">
                                <input type="text" id="wpced-sim-datetime" class="wpced_date_time_input"
                                       value="<?php echo esc_attr( current_time( 'm/d/Y h:i a' ) ); ?>"
                                       placeholder="MM/DD/YYYY HH:MM AM/PM" />
                                <button type="button" id="wpced-sim-now-btn" class="wpced-action-tool-btn" title="<?php esc_attr_e( 'Set to current server time', 'wpc-estimated-delivery-date' ); ?>">
                                    <span class="dashicons dashicons-update"></span> <?php esc_html_e( 'Now', 'wpc-estimated-delivery-date' ); ?>
                                </button>
                            </div>
                        </div>
                        <div class="wpced-sim-row">
                            <label><?php esc_html_e( 'Server Current Time', 'wpc-estimated-delivery-date' ); ?></label>
                            <div class="wpced-current-time-badges">
                                <code><?php echo esc_html( current_time( 'm/d/Y h:i a' ) ); ?></code>
                                <code><?php echo esc_html( current_time( 'l' ) ); ?></code>
                                <code><?php echo esc_html__( 'Week No.', 'wpc-estimated-delivery-date' ) . ' ' . esc_html( current_time( 'W' ) ); ?></code>
                            </div>
                        </div>
                        <?php if ( ! empty( $extra_time_line ) ) : ?>
                            <div class="wpced-sim-row">
                                <label><?php esc_html_e( 'Configured Cut-off Time', 'wpc-estimated-delivery-date' ); ?></label>
                                <div>
                                    <code><?php echo esc_html( $extra_time_line ); ?></code>
                                    <span class="description"><?php esc_html_e( '(Orders placed after this daily time advance dispatch to next working day)', 'wpc-estimated-delivery-date' ); ?></span>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="wpced-sim-actions">
                    <button type="button" id="wpced-sim-run" class="button button-primary">
                        <span class="dashicons dashicons-controls-play"></span>
                        <?php esc_html_e( 'Run Simulation', 'wpc-estimated-delivery-date' ); ?>
                    </button>
                    <button type="button" id="wpced-sim-reset" class="button">
                        <?php esc_html_e( 'Reset', 'wpc-estimated-delivery-date' ); ?>
                    </button>
                    <span id="wpced-sim-spinner" class="spinner"></span>
                </div>

                <div id="wpced-sim-results" class="wpced-sim-results wpced_hide"></div>
            </div>
            <?php
        }

        public function ajax_simulate() {
            check_ajax_referer( 'wpced-security', 'nonce' );

            if ( ! current_user_can( 'manage_options' ) ) {
                wp_send_json_error( [ 'message' => esc_html__( 'Unauthorized permission.', 'wpc-estimated-delivery-date' ) ] );
            }

            $product_id   = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
            $datetime_str = isset( $_POST['datetime'] ) ? sanitize_text_field( wp_unslash( $_POST['datetime'] ) ) : '';
            $zone_input   = isset( $_POST['zone'] ) ? sanitize_text_field( wp_unslash( $_POST['zone'] ) ) : 'all';
            $method_input = isset( $_POST['method'] ) ? sanitize_text_field( wp_unslash( $_POST['method'] ) ) : 'all';

            if ( ! $product_id ) {
                wp_send_json_error( [ 'message' => esc_html__( 'Please select a valid product.', 'wpc-estimated-delivery-date' ) ] );
            }

            $product = wc_get_product( $product_id );
            if ( ! $product ) {
                wp_send_json_error( [ 'message' => esc_html__( 'Product not found.', 'wpc-estimated-delivery-date' ) ] );
            }

            $timestamp = $datetime_str ? strtotime( $datetime_str ) : current_time( 'U' );
            if ( ! $timestamp ) {
                $timestamp = current_time( 'U' );
            }

            $sim_time = [
                'date'      => date_i18n( 'm/d/Y', $timestamp ),
                'datetime'  => date_i18n( 'm/d/Y h:i a', $timestamp ),
                'time'      => date_i18n( 'h:i a', $timestamp ),
                'day'       => (int) date_i18n( 'd', $timestamp ),
                'weekday'   => date_i18n( 'l', $timestamp ),
                'week'      => (int) date_i18n( 'W', $timestamp ),
                'timestamp' => $timestamp,
            ];

            $is_variation = $product->is_type( 'variation' );
            $parent_id    = $is_variation ? $product->get_parent_id() : 0;
            $product_name = $product->get_name();
            $product_sku  = $product->get_sku() ?: '—';
            $product_type = $product->get_type();
            $edit_url     = get_edit_post_link( $is_variation ? $parent_id : $product_id );
            $image_id     = $product->get_image_id();
            if ( ! $image_id && $is_variation ) {
                $parent_prod = wc_get_product( $parent_id );
                if ( $parent_prod ) {
                    $image_id = $parent_prod->get_image_id();
                }
            }
            $image_url = $image_id ? wp_get_attachment_image_url( $image_id, 'thumbnail' ) : wc_placeholder_img_src( 'thumbnail' );

            // Product stock status and quantity
            $is_in_stock    = $product->is_in_stock();
            $stock_status   = $product->get_stock_status();
            $managing_stock = $product->managing_stock();
            $stock_quantity = $product->get_stock_quantity();

            // Check product delivery settings (wpced_enable)
            if ( $is_variation ) {
                $v_enable = get_post_meta( $product_id, 'wpced_enable', true ) ?: 'parent';
                if ( $v_enable === 'parent' ) {
                    $enable = get_post_meta( $parent_id, 'wpced_enable', true ) ?: 'global';
                } else {
                    $enable = $v_enable;
                }
            } else {
                $enable = get_post_meta( $product_id, 'wpced_enable', true ) ?: 'global';
            }

            if ( $enable === 'disable' ) {
                wp_send_json_success( [
                    'status'         => 'disabled',
                    'product'        => [
                        'id'         => $product_id,
                        'name'       => $product_name,
                        'sku'        => $product_sku,
                        'type'       => $product_type,
                        'image'      => $image_url,
                        'edit_url'   => $edit_url,
                        'stock'      => $stock_status,
                        'stock_qty'  => $stock_quantity !== null ? $stock_quantity : '—',
                        'enable'     => 'disable',
                    ],
                    'simulated_time' => $sim_time,
                    'message'        => esc_html__( 'Estimated delivery date calculation is disabled for this product.', 'wpc-estimated-delivery-date' ),
                ] );
            }

            // Retrieve delivery rules
            $source_type = 'global';
            if ( $enable === 'override' ) {
                $source_type = 'product';
                if ( $is_variation && ( ( get_post_meta( $product_id, 'wpced_enable', true ) ?: 'parent' ) === 'override' ) ) {
                    $rules = (array) get_post_meta( $product_id, 'wpced_rules', true ) ?: [];
                } else {
                    $rules = (array) get_post_meta( $is_variation ? $parent_id : $product_id, 'wpced_rules', true ) ?: [];
                }
            } else {
                $rules = self::get_rules();
            }

            $default_rule = $rules['default'] ?? self::get_base_rule();
            unset( $rules['default'] );

            $evaluated_rules = [];
            $applied_rule    = null;

            // Loop through custom rules in defined priority order
            if ( ! empty( $rules ) ) {
                foreach ( $rules as $rule_key => $raw_rule ) {
                    $rule = array_merge( self::get_base_rule(), $raw_rule );
                    $rule['key'] = $rule_key;

                    $apply         = ! empty( $rule['apply'] ) ? $rule['apply'] : 'all';
                    $apply_val     = ! empty( $rule['apply_val'] ) ? (array) $rule['apply_val'] : [];
                    $apply_compare = ! empty( $rule['apply_compare'] ) ? $rule['apply_compare'] : 'equal';
                    $apply_number  = ! empty( $rule['apply_number'] ) ? (float) $rule['apply_number'] : 0;
                    $r_zone        = ! empty( $rule['zone'] ) ? $rule['zone'] : 'all';
                    $r_method      = ! empty( $rule['method'] ) ? $rule['method'] : 'all';

                    $matches = true;
                    $fail_reasons = [];

                    // 1. Zone condition
                    if ( $r_zone !== 'all' && $zone_input !== 'all' && ( (string) $r_zone !== (string) $zone_input ) ) {
                        $matches = false;
                        $fail_reasons[] = sprintf(
                            /* translators: 1: required zone, 2: selected zone */
                            esc_html__( 'Zone mismatch: requires zone %1$s, simulated with %2$s', 'wpc-estimated-delivery-date' ),
                            $r_zone,
                            $zone_input
                        );
                    }

                    // 2. Method condition
                    if ( $matches && $r_method !== 'all' && $method_input !== 'all' && ( (string) $r_method !== (string) $method_input ) ) {
                        $matches = false;
                        $fail_reasons[] = sprintf(
                            /* translators: 1: required method, 2: selected method */
                            esc_html__( 'Method mismatch: requires method %1$s, simulated with %2$s', 'wpc-estimated-delivery-date' ),
                            $r_method,
                            $method_input
                        );
                    }

                    // 3. Stock status / conditions
                    if ( $matches ) {
                        if ( $apply === 'instock' && ! $is_in_stock ) {
                            $matches = false;
                            $fail_reasons[] = esc_html__( 'Product is not in stock (rule requires In stock)', 'wpc-estimated-delivery-date' );
                        } elseif ( $apply === 'outofstock' && $is_in_stock ) {
                            $matches = false;
                            $fail_reasons[] = esc_html__( 'Product is in stock (rule requires Out of stock)', 'wpc-estimated-delivery-date' );
                        } elseif ( $apply === 'backorder' && ! $product->is_on_backorder() ) {
                            $matches = false;
                            $fail_reasons[] = esc_html__( 'Product is not on backorder (rule requires On backorder)', 'wpc-estimated-delivery-date' );
                        } elseif ( $apply === 'stock' ) {
                            if ( ! $managing_stock ) {
                                $matches = false;
                                $fail_reasons[] = esc_html__( 'Product does not manage stock quantity', 'wpc-estimated-delivery-date' );
                            } else {
                                $stock_match = false;
                                switch ( $apply_compare ) {
                                    case 'equal':
                                        $stock_match = ( (float) $stock_quantity === $apply_number );
                                        break;
                                    case 'not_equal':
                                        $stock_match = ( (float) $stock_quantity !== $apply_number );
                                        break;
                                    case 'greater':
                                        $stock_match = ( (float) $stock_quantity > $apply_number );
                                        break;
                                    case 'greater_equal':
                                        $stock_match = ( (float) $stock_quantity >= $apply_number );
                                        break;
                                    case 'less':
                                        $stock_match = ( (float) $stock_quantity < $apply_number );
                                        break;
                                    case 'less_equal':
                                        $stock_match = ( (float) $stock_quantity <= $apply_number );
                                        break;
                                }
                                if ( ! $stock_match ) {
                                    $matches = false;
                                    $fail_reasons[] = sprintf(
                                        /* translators: 1: actual stock, 2: compare operator, 3: expected value */
                                        esc_html__( 'Stock quantity %1$s failed comparison: %2$s %3$s', 'wpc-estimated-delivery-date' ),
                                        $stock_quantity,
                                        $apply_compare,
                                        $apply_number
                                    );
                                }
                            }
                        } elseif ( $apply === 'combined' ) {
                            $combined_conditions = ! empty( $rule['apply_conditions'] ) ? (array) $rule['apply_conditions'] : [];
                            foreach ( $combined_conditions as $c_idx => $c_item ) {
                                $c_apply         = ! empty( $c_item['apply'] ) ? $c_item['apply'] : 'instock';
                                $c_apply_val     = ! empty( $c_item['apply_val'] ) ? (array) $c_item['apply_val'] : [];
                                $c_apply_compare = ! empty( $c_item['apply_compare'] ) ? $c_item['apply_compare'] : 'equal';
                                $c_apply_number  = ! empty( $c_item['apply_number'] ) ? (float) $c_item['apply_number'] : 0;

                                if ( $c_apply === 'instock' && ! $is_in_stock ) {
                                    $matches = false;
                                    $fail_reasons[] = sprintf( esc_html__( 'Combined condition #%d: Product is not in stock', 'wpc-estimated-delivery-date' ), $c_idx + 1 );
                                    break;
                                }
                                if ( $c_apply === 'outofstock' && $is_in_stock ) {
                                    $matches = false;
                                    $fail_reasons[] = sprintf( esc_html__( 'Combined condition #%d: Product is in stock', 'wpc-estimated-delivery-date' ), $c_idx + 1 );
                                    break;
                                }
                                if ( $c_apply === 'backorder' && ! $product->is_on_backorder() ) {
                                    $matches = false;
                                    $fail_reasons[] = sprintf( esc_html__( 'Combined condition #%d: Product is not on backorder', 'wpc-estimated-delivery-date' ), $c_idx + 1 );
                                    break;
                                }
                                if ( $c_apply === 'stock' ) {
                                    if ( ! $managing_stock ) {
                                        $matches = false;
                                        $fail_reasons[] = sprintf( esc_html__( 'Combined condition #%d: Product does not manage stock', 'wpc-estimated-delivery-date' ), $c_idx + 1 );
                                        break;
                                    }
                                    $c_stock_match = false;
                                    if ( $c_apply_compare === 'equal' && ( (float) $stock_quantity === $c_apply_number ) ) $c_stock_match = true;
                                    if ( $c_apply_compare === 'not_equal' && ( (float) $stock_quantity !== $c_apply_number ) ) $c_stock_match = true;
                                    if ( $c_apply_compare === 'greater' && ( (float) $stock_quantity > $c_apply_number ) ) $c_stock_match = true;
                                    if ( $c_apply_compare === 'greater_equal' && ( (float) $stock_quantity >= $c_apply_number ) ) $c_stock_match = true;
                                    if ( $c_apply_compare === 'less' && ( (float) $stock_quantity < $c_apply_number ) ) $c_stock_match = true;
                                    if ( $c_apply_compare === 'less_equal' && ( (float) $stock_quantity <= $c_apply_number ) ) $c_stock_match = true;
                                    if ( ! $c_stock_match ) {
                                        $matches = false;
                                        $fail_reasons[] = sprintf( esc_html__( 'Combined condition #%d: Stock quantity comparison failed', 'wpc-estimated-delivery-date' ), $c_idx + 1 );
                                        break;
                                    }
                                }
                                if ( ! in_array( $c_apply, [ 'instock', 'outofstock', 'backorder', 'stock' ], true ) ) {
                                    if ( ( substr( $c_apply, 0, 3 ) === 'pa_' ) && $is_variation ) {
                                        $attrs = $product->get_attributes();
                                        if ( empty( $attrs[ $c_apply ] ) || ! in_array( $attrs[ $c_apply ], $c_apply_val, true ) ) {
                                            $matches = false;
                                            $fail_reasons[] = sprintf( esc_html__( 'Combined condition #%d: Attribute %s did not match', 'wpc-estimated-delivery-date' ), $c_idx + 1, $c_apply );
                                            break;
                                        }
                                    } elseif ( ! has_term( $c_apply_val, $c_apply, $is_variation ? $parent_id : $product_id ) ) {
                                        $matches = false;
                                        $fail_reasons[] = sprintf( esc_html__( 'Combined condition #%d: Taxonomy %s did not match', 'wpc-estimated-delivery-date' ), $c_idx + 1, $c_apply );
                                        break;
                                    }
                                }
                            }
                        } elseif ( ! in_array( $apply, [ 'all', 'instock', 'outofstock', 'backorder', 'stock', 'combined' ], true ) ) {
                            if ( ( substr( $apply, 0, 3 ) === 'pa_' ) && $is_variation ) {
                                $attrs = $product->get_attributes();
                                if ( empty( $attrs[ $apply ] ) || ! in_array( $attrs[ $apply ], $apply_val, true ) ) {
                                    $matches = false;
                                    $fail_reasons[] = sprintf( esc_html__( 'Attribute %s value does not match', 'wpc-estimated-delivery-date' ), $apply );
                                }
                            } elseif ( ! has_term( $apply_val, $apply, $is_variation ? $parent_id : $product_id ) ) {
                                $matches = false;
                                $fail_reasons[] = sprintf( esc_html__( 'Product does not match taxonomy %s terms', 'wpc-estimated-delivery-date' ), $apply );
                            }
                        }
                    }

                    $status        = 'not_matched';
                    $status_label  = esc_html__( 'Not Matched', 'wpc-estimated-delivery-date' );
                    $status_reason = implode( '; ', $fail_reasons );

                    if ( $matches ) {
                        if ( $applied_rule === null ) {
                            $applied_rule  = $rule;
                            $status        = 'applied';
                            $status_label  = esc_html__( 'Applied', 'wpc-estimated-delivery-date' );
                            $status_reason = esc_html__( 'Highest priority rule matching all conditions.', 'wpc-estimated-delivery-date' );
                        } else {
                            $status        = 'overridden';
                            $status_label  = esc_html__( 'Overridden', 'wpc-estimated-delivery-date' );
                            $status_reason = sprintf(
                                /* translators: %s: winning rule key */
                                esc_html__( 'Conditions matched, but higher-priority rule #%s was already applied.', 'wpc-estimated-delivery-date' ),
                                $applied_rule['key']
                            );
                        }
                    }

                    $evaluated_rules[] = [
                        'key'           => $rule_key,
                        'name'          => ! empty( $rule['name'] ) ? $rule['name'] : ( '#' . $rule_key ),
                        'status'        => $status,
                        'status_label'  => $status_label,
                        'status_reason' => $status_reason,
                        'apply_summary' => self::format_rule_apply_summary( $rule ),
                        'min'           => $rule['min'],
                        'max'           => $rule['max'],
                        'scheduled'     => ! empty( $rule['scheduled'] ) ? $rule['scheduled'] : '—',
                    ];
                }
            }

            // Fallback to default rule if no custom rules matched
            if ( $applied_rule === null ) {
                $applied_rule = array_merge( self::get_base_rule(), $default_rule );
                $applied_rule['key'] = 'default';

                $evaluated_rules[] = [
                    'key'           => 'default',
                    'name'          => esc_html__( 'Default Rule', 'wpc-estimated-delivery-date' ),
                    'status'        => 'applied',
                    'status_label'  => esc_html__( 'Applied (Default)', 'wpc-estimated-delivery-date' ),
                    'status_reason' => esc_html__( 'No custom rules matched; fallback to default rule.', 'wpc-estimated-delivery-date' ),
                    'apply_summary' => esc_html__( 'All products (Storewide default)', 'wpc-estimated-delivery-date' ),
                    'min'           => $applied_rule['min'],
                    'max'           => $applied_rule['max'],
                    'scheduled'     => ! empty( $applied_rule['scheduled'] ) ? $applied_rule['scheduled'] : '—',
                ];
            }

            // Calculate simulated delivery dates using the winning rule
            $calc = $this->simulate_dates_calculation( $applied_rule, $timestamp );

            wp_send_json_success( [
                'status'          => 'success',
                'source_type'     => $source_type,
                'product'         => [
                    'id'          => $product_id,
                    'name'        => $product_name,
                    'sku'         => $product_sku,
                    'type'        => $product_type,
                    'image'       => $image_url,
                    'edit_url'    => $edit_url,
                    'stock'       => $stock_status,
                    'stock_qty'   => $stock_quantity !== null ? $stock_quantity : '—',
                    'enable'      => $enable,
                ],
                'simulated_time'  => $sim_time,
                'applied_rule'    => [
                    'key'           => $applied_rule['key'],
                    'name'          => ! empty( $applied_rule['name'] ) ? $applied_rule['name'] : ( '#' . $applied_rule['key'] ),
                    'apply_summary' => self::format_rule_apply_summary( $applied_rule ),
                    'min'           => $applied_rule['min'],
                    'max'           => $applied_rule['max'],
                    'scheduled'     => ! empty( $applied_rule['scheduled'] ) ? $applied_rule['scheduled'] : '—',
                ],
                'calculation'     => $calc,
                'evaluated_rules' => $evaluated_rules,
            ] );
        }

        public function simulate_dates_calculation( $rule, $sim_unix ) {
            $current_unix         = $sim_unix;
            $current_time         = date_i18n( 'h:i a', $sim_unix );
            $current_date         = date_i18n( 'm/d/Y', $sim_unix );
            $current_weekday      = date_i18n( 'w', $sim_unix );
            $extra_time_line      = self::get_setting( 'extra_time_line' );
            $scheduled            = ! empty( $rule['scheduled'] ) ? $rule['scheduled'] : '';

            $cutoff_info = [
                'has_cutoff'   => ! empty( $extra_time_line ),
                'cutoff_time'  => $extra_time_line ?: '',
                'is_triggered' => false,
                'note'         => '',
            ];

            $dispatch_skipped_log = [];
            $delivery_skipped_log = [];
            $current_date_skipped = false;

            // Check scheduled start date
            if ( ! empty( $scheduled ) && ( strtotime( $scheduled ) > strtotime( $current_date ) ) ) {
                $current_unix         = strtotime( $scheduled );
                $current_date         = date_i18n( 'm/d/Y', $current_unix );
                $current_weekday      = date_i18n( 'w', $current_unix );
                $current_date_skipped = true;
            }

            // Check skipped dates for dispatch start
            $j = 1;
            while ( Wpced_Frontend()->check_skipped( $current_date, $current_weekday, 'dispatch' ) && ( $j <= 100 ) ) {
                $dispatch_skipped_log[] = date_i18n( 'M j, Y (l)', $current_unix );
                $current_unix         += 86400;
                $current_date         = date_i18n( 'm/d/Y', $current_unix );
                $current_weekday      = date_i18n( 'w', $current_unix );
                $current_date_skipped = true;
                $j++;
            }

            // Cut-off time check
            if ( ! empty( $extra_time_line ) && apply_filters( 'wpced_apply_extra_time_for_skipped_date', ! $current_date_skipped ) ) {
                if ( strtotime( $current_date . ' ' . $current_time ) > strtotime( $current_date . ' ' . $extra_time_line ) ) {
                    $cutoff_info['is_triggered'] = true;
                    $cutoff_info['note']         = sprintf(
                        /* translators: 1: current time, 2: cutoff time */
                        esc_html__( 'Simulated time (%1$s) is after cut-off time (%2$s). Dispatch date advanced by 1 day.', 'wpc-estimated-delivery-date' ),
                        $current_time,
                        $extra_time_line
                    );

                    $current_unix    += 86400;
                    $current_date    = date_i18n( 'm/d/Y', $current_unix );
                    $current_weekday = date_i18n( 'w', $current_unix );

                    while ( Wpced_Frontend()->check_skipped( $current_date, $current_weekday, 'dispatch' ) && ( $j <= 100 ) ) {
                        $dispatch_skipped_log[] = date_i18n( 'M j, Y (l)', $current_unix );
                        $current_unix    += 86400;
                        $current_date    = date_i18n( 'm/d/Y', $current_unix );
                        $current_weekday = date_i18n( 'w', $current_unix );
                        $j++;
                    }
                }
            }

            $dispatch_date_unix = $current_unix;
            $dispatch_date_str  = Wpced_Frontend()->format_date( $dispatch_date_unix );

            // Calculate min & max delivery dates
            $min_days = isset( $rule['min'] ) && $rule['min'] !== '' ? absint( $rule['min'] ) : null;
            $max_days = isset( $rule['max'] ) && $rule['max'] !== '' ? absint( $rule['max'] ) : null;

            $calc_delivery = function( $days, $start_unix ) use ( &$delivery_skipped_log ) {
                if ( $days === 0 ) {
                    return $start_unix;
                }
                $u     = $start_unix;
                $avail = [];
                $i     = 1;
                while ( ( count( $avail ) < $days ) && ( $i <= 100 ) ) {
                    $u      += 86400;
                    $c_date = date_i18n( 'm/d/Y', $u );
                    $c_week = date_i18n( 'w', $u );
                    if ( ! Wpced_Frontend()->check_skipped( $c_date, $c_week, 'delivery' ) ) {
                        $avail[] = $u;
                    } else {
                        $skip_label = date_i18n( 'M j, Y (l)', $u );
                        if ( ! in_array( $skip_label, $delivery_skipped_log, true ) ) {
                            $delivery_skipped_log[] = $skip_label;
                        }
                    }
                    $i++;
                }
                return ! empty( $avail ) ? end( $avail ) : $u;
            };

            $min_time_unix      = null;
            $max_time_unix      = null;
            $min_time_str       = '';
            $max_time_str       = '';
            $date_range_display = '';

            if ( $min_days !== null ) {
                $min_time_unix      = $calc_delivery( $min_days, $dispatch_date_unix );
                $min_time_str       = Wpced_Frontend()->format_date( $min_time_unix );
                $date_range_display = $min_time_str;
            }

            if ( $max_days !== null ) {
                $max_time_unix = $calc_delivery( $max_days, $dispatch_date_unix );
                $max_time_str  = Wpced_Frontend()->format_date( $max_time_unix );
                if ( $min_days !== null ) {
                    $date_range_display .= ' - ' . $max_time_str;
                } else {
                    $date_range_display = $max_time_str;
                }
            }

            // Build delivery text preview
            if ( $min_days !== null && $max_days === null ) {
                $default_text = esc_html__( 'Earliest estimated delivery date: %s', 'wpc-estimated-delivery-date' );
                $delivery_tpl = self::get_setting( 'text_min', $default_text ) ?: $default_text;
            } elseif ( $min_days === null && $max_days !== null ) {
                $default_text = esc_html__( 'Latest estimated delivery date: %s', 'wpc-estimated-delivery-date' );
                $delivery_tpl = self::get_setting( 'text_max', $default_text ) ?: $default_text;
            } else {
                $default_text = esc_html__( 'Estimated delivery dates: %s', 'wpc-estimated-delivery-date' );
                $delivery_tpl = self::get_setting( 'text', $default_text ) ?: $default_text;
            }

            $delivery_text_preview = sprintf( $delivery_tpl, $date_range_display );

            return [
                'dispatch_date_unix'      => $dispatch_date_unix,
                'dispatch_date_formatted' => $dispatch_date_str,
                'min_days'                => $min_days,
                'max_days'                => $max_days,
                'min_time_unix'           => $min_time_unix,
                'min_time_formatted'      => $min_time_str,
                'max_time_unix'           => $max_time_unix,
                'max_time_formatted'      => $max_time_str,
                'date_range_display'      => $date_range_display,
                'delivery_text_preview'   => $delivery_text_preview,
                'cutoff'                  => $cutoff_info,
                'dispatch_skipped'        => $dispatch_skipped_log,
                'delivery_skipped'        => $delivery_skipped_log,
            ];
        }

        public static function format_rule_apply_summary( $rule ) {
            $apply = ! empty( $rule['apply'] ) ? $rule['apply'] : 'all';

            switch ( $apply ) {
                case 'all':
                    return esc_html__( 'All products', 'wpc-estimated-delivery-date' );
                case 'instock':
                    return esc_html__( 'In stock products', 'wpc-estimated-delivery-date' );
                case 'outofstock':
                    return esc_html__( 'Out of stock products', 'wpc-estimated-delivery-date' );
                case 'backorder':
                    return esc_html__( 'On backorder products', 'wpc-estimated-delivery-date' );
                case 'stock':
                    $cmp = $rule['apply_compare'] ?? 'equal';
                    $num = $rule['apply_number'] ?? 0;
                    return sprintf( esc_html__( 'Stock quantity %s %s', 'wpc-estimated-delivery-date' ), $cmp, $num );
                case 'combined':
                    $cnt = ! empty( $rule['apply_conditions'] ) ? count( (array) $rule['apply_conditions'] ) : 0;
                    return sprintf( esc_html__( 'Combined (%d conditions)', 'wpc-estimated-delivery-date' ), $cnt );
                default:
                    $tax_obj = get_taxonomy( $apply );
                    $label   = $tax_obj ? $tax_obj->label : $apply;
                    $terms   = ! empty( $rule['apply_val'] ) ? (array) $rule['apply_val'] : [];
                    return $label . ( ! empty( $terms ) ? ': ' . implode( ', ', $terms ) : '' );
            }
        }
    }

    function Wpced_Backend() {
        return Wpced_Backend::instance();
    }

    Wpced_Backend();
}
