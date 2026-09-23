<?php
defined( 'ABSPATH' ) || exit;

$active_tab = sanitize_key( wp_unslash( $_GET['tab'] ?? 'settings' ) );
$rules      = Wpced_Backend()->get_rules();
?>
<div class="wrap wpced-settings-wrap">
    <div class="wpced-settings-header">
        <div class="wpced-settings-header-inner">
            <div class="wpced-header-left">
                <div class="wpced-logo">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="1" y="3" width="15" height="13"></rect>
                        <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon>
                        <circle cx="5.5" cy="18.5" r="2.5"></circle>
                        <circle cx="18.5" cy="18.5" r="2.5"></circle>
                    </svg>
                </div>
                <div>
                    <h1>
                        <?php echo esc_html__( 'WPC Estimated Delivery Date', 'wpc-estimated-delivery-date' ) . ' ' . esc_html( WPCED_VERSION ); ?>
                        <?php if ( defined( 'WPCED_PREMIUM' ) ) : ?>
                            <span class="premium"><?php esc_html_e( 'Premium', 'wpc-estimated-delivery-date' ); ?></span>
                        <?php endif; ?>
                    </h1>
                    <p class="wpced-tagline">
                        <?php esc_html_e( 'Establish and personalize delivery dates and times for products in your store.', 'wpc-estimated-delivery-date' ); ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <?php if ( isset( $_GET['settings-updated'] ) && sanitize_text_field( wp_unslash( $_GET['settings-updated'] ?? '' ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
        <div class="notice notice-success is-dismissible">
            <p><?php esc_html_e( 'Settings updated.', 'wpc-estimated-delivery-date' ); ?></p>
        </div>
    <?php } ?>

    <div class="wpced-admin-nav">
        <div class="wpced-nav-container">
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=wpclever-wpced&tab=how' ) ); ?>"
               class="wpced-nav-item <?php echo $active_tab === 'how' ? 'active' : ''; ?>">
                <?php esc_html_e( 'How to use?', 'wpc-estimated-delivery-date' ); ?>
            </a>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=wpclever-wpced&tab=settings' ) ); ?>"
               class="wpced-nav-item <?php echo $active_tab === 'settings' ? 'active' : ''; ?>">
                <?php esc_html_e( 'Settings', 'wpc-estimated-delivery-date' ); ?>
            </a>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=wpclever-wpced&tab=simulator' ) ); ?>"
               class="wpced-nav-item <?php echo $active_tab === 'simulator' ? 'active' : ''; ?>">
                <?php esc_html_e( 'Simulator', 'wpc-estimated-delivery-date' ); ?>
            </a>
            <?php if ( ! defined( 'WPCED_PREMIUM' ) ) : ?>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=wpclever-wpced&tab=premium' ) ); ?>"
                   class="wpced-nav-item wpc-premium <?php echo $active_tab === 'premium' ? 'active' : ''; ?>">
                    <?php esc_html_e( 'Premium Version', 'wpc-estimated-delivery-date' ); ?>
                </a>
            <?php endif; ?>
            <?php if ( defined( 'WPCED_PREMIUM' ) ) : ?>
                <a href="<?php echo esc_url( WPCED_SUPPORT ); ?>" class="wpced-nav-item" target="_blank">
                    <?php esc_html_e( 'Support', 'wpc-estimated-delivery-date' ); ?>
                </a>
            <?php endif; ?>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=wpclever-kit' ) ); ?>"
               class="wpced-nav-item">
                <?php esc_html_e( 'Essential Kit', 'wpc-estimated-delivery-date' ); ?>
            </a>
        </div>
    </div>

    <div class="wpced-tab-content active">
        <?php if ( $active_tab === 'how' ) { ?>
            <div class="wpced-card">
                <div class="wpced-card-header">
                    <div>
                        <h2 class="wpced-card-title"><?php esc_html_e( 'How to use?', 'wpc-estimated-delivery-date' ); ?></h2>
                        <p class="wpced-card-desc"><?php esc_html_e( 'Quick instructions on how to use WPC Estimated Delivery Date.', 'wpc-estimated-delivery-date' ); ?></p>
                    </div>
                </div>
                <div class="wpced-settings-page-content-text">
                    <p>
                        <?php esc_html_e( '1. Default Rule: Under the Settings tab, configure the default delivery calculation rule that applies to all products in your store.', 'wpc-estimated-delivery-date' ); ?>
                    </p>
                    <p>
                        <?php esc_html_e( '2. Custom Rules: Add new rules to override estimated delivery dates based on product categories, tags, stock status, or shipping zones.', 'wpc-estimated-delivery-date' ); ?>
                    </p>
                    <p>
                        <?php esc_html_e( '3. Skipped Dates: Specify weekends or holidays to exclude them from the delivery time calculation.', 'wpc-estimated-delivery-date' ); ?>
                    </p>
                    <p>
                        <?php esc_html_e( '4. Shortcode: Use [wpced] to display the estimated delivery date anywhere on product pages.', 'wpc-estimated-delivery-date' ); ?>
                    </p>
                </div>
            </div>
        <?php } elseif ( $active_tab === 'settings' ) {
            $date_format            = Wpced_Backend()->get_setting( 'date_format', 'M j, Y' );
            $date_format_custom     = Wpced_Backend()->get_setting( 'date_format_custom', 'M j, Y' );
            $pos_archive            = Wpced_Backend()->get_setting( 'position_archive', apply_filters( 'wpced_default_archive_position', 'above_add_to_cart' ) );
            $pos_single             = Wpced_Backend()->get_setting( 'position_single', apply_filters( 'wpced_default_single_position', '31' ) );
            $skipped_dates          = Wpced_Backend()->get_setting( 'skipped_dates', [] );
            $skipped_advanced       = Wpced_Backend()->get_setting( 'skipped_advanced', 'no' );
            $skipped_dates_dispatch = Wpced_Backend()->get_setting( 'skipped_dates_dispatch', [] );
            $skipped_dates_delivery = Wpced_Backend()->get_setting( 'skipped_dates_delivery', [] );
            $cart_item              = Wpced_Backend()->get_setting( 'cart_item', 'no' );
            $cart_overall           = Wpced_Backend()->get_setting( 'cart_overall', 'yes' );
            $cart_overall_format    = Wpced_Backend()->get_setting( 'cart_overall_format', 'latest' );
            $order_item             = Wpced_Backend()->get_setting( 'order_item', 'no' );
            $reload_dates           = Wpced_Backend()->get_setting( 'reload_dates', 'no' );
            ?>
            <form method="post" action="options.php">
                <div class="wpced-card">
                    <div class="wpced-card-header">
                        <div>
                            <h2 class="wpced-card-title"><?php esc_html_e( 'General Settings', 'wpc-estimated-delivery-date' ); ?></h2>
                            <p class="wpced-card-desc"><?php esc_html_e( 'Configure general display options, messages, and date formatting.', 'wpc-estimated-delivery-date' ); ?></p>
                        </div>
                    </div>

                    <div class="wpced-settings-row">
                        <div class="wpced-settings-label">
                            <strong><?php esc_html_e( 'Position on archive', 'wpc-estimated-delivery-date' ); ?></strong>
                        </div>
                        <div class="wpced-settings-field">
                            <select name="wpced_settings[position_archive]">
                                <?php foreach ( Wpced_Backend()->get_archive_positions() as $key => $pos ) {
                                    echo '<option value="' . esc_attr( $key ) . '" ' . selected( $pos_archive, $key, false ) . '>' . esc_html( $pos ) . '</option>';
                                } ?>
                            </select>
                        </div>
                    </div>

                    <div class="wpced-settings-row">
                        <div class="wpced-settings-label">
                            <strong><?php esc_html_e( 'Position on single', 'wpc-estimated-delivery-date' ); ?></strong>
                        </div>
                        <div class="wpced-settings-field">
                            <select name="wpced_settings[position_single]">
                                <?php foreach ( Wpced_Backend()->get_single_positions() as $key => $pos ) {
                                    echo '<option value="' . esc_attr( $key ) . '" ' . selected( $pos_single, $key, false ) . '>' . esc_html( $pos ) . '</option>';
                                } ?>
                            </select>
                        </div>
                    </div>

                    <div class="wpced-settings-row">
                        <div class="wpced-settings-label">
                            <strong><?php esc_html_e( 'Shortcode', 'wpc-estimated-delivery-date' ); ?></strong>
                        </div>
                        <div class="wpced-settings-field">
                            <?php echo sprintf( /* translators: shortcode */ esc_html__( 'You can use shortcode %s to show the estimated delivery date for current product.', 'wpc-estimated-delivery-date' ), '<code>[wpced]</code>' ); ?>
                        </div>
                    </div>

                    <div class="wpced-settings-row">
                        <div class="wpced-settings-label">
                            <strong><?php esc_html_e( 'Show on cart items', 'wpc-estimated-delivery-date' ); ?></strong>
                        </div>
                        <div class="wpced-settings-field">
                            <select name="wpced_settings[cart_item]">
                                <option value="yes" <?php selected( $cart_item, 'yes' ); ?>><?php esc_html_e( 'Yes', 'wpc-estimated-delivery-date' ); ?></option>
                                <option value="yes_data" <?php selected( $cart_item, 'yes_data' ); ?>><?php esc_html_e( 'Yes, as an item\'s data', 'wpc-estimated-delivery-date' ); ?></option>
                                <option value="no" <?php selected( $cart_item, 'no' ); ?>><?php esc_html_e( 'No', 'wpc-estimated-delivery-date' ); ?></option>
                            </select>
                        </div>
                    </div>

                    <div class="wpced-settings-row">
                        <div class="wpced-settings-label">
                            <strong><?php esc_html_e( 'Show cart overall', 'wpc-estimated-delivery-date' ); ?></strong>
                        </div>
                        <div class="wpced-settings-field">
                            <select name="wpced_settings[cart_overall]">
                                <option value="yes" <?php selected( $cart_overall, 'yes' ); ?>><?php esc_html_e( 'Yes', 'wpc-estimated-delivery-date' ); ?></option>
                                <option value="yes_text" <?php selected( $cart_overall, 'yes_text' ); ?>><?php esc_html_e( 'Yes, as a plain text', 'wpc-estimated-delivery-date' ); ?></option>
                                <option value="no" <?php selected( $cart_overall, 'no' ); ?>><?php esc_html_e( 'No', 'wpc-estimated-delivery-date' ); ?></option>
                            </select>
                        </div>
                    </div>

                    <div class="wpced-settings-row">
                        <div class="wpced-settings-label">
                            <strong><?php esc_html_e( 'Overall date format', 'wpc-estimated-delivery-date' ); ?></strong>
                        </div>
                        <div class="wpced-settings-field">
                            <select name="wpced_settings[cart_overall_format]">
                                <option value="latest" <?php selected( $cart_overall_format, 'latest' ); ?>><?php esc_html_e( 'Latest date (default)', 'wpc-estimated-delivery-date' ); ?></option>
                                <option value="earliest" <?php selected( $cart_overall_format, 'earliest' ); ?>><?php esc_html_e( 'Earliest date', 'wpc-estimated-delivery-date' ); ?></option>
                                <option value="earliest_latest" <?php selected( $cart_overall_format, 'earliest_latest' ); ?>><?php esc_html_e( 'Earliest - Latest date', 'wpc-estimated-delivery-date' ); ?></option>
                            </select>
                        </div>
                    </div>

                    <div class="wpced-settings-row">
                        <div class="wpced-settings-label">
                            <strong><?php esc_html_e( 'Show on order items', 'wpc-estimated-delivery-date' ); ?></strong>
                        </div>
                        <div class="wpced-settings-field">
                            <select name="wpced_settings[order_item]">
                                <option value="yes" <?php selected( $order_item, 'yes' ); ?>><?php esc_html_e( 'Yes', 'wpc-estimated-delivery-date' ); ?></option>
                                <option value="no" <?php selected( $order_item, 'no' ); ?>><?php esc_html_e( 'No', 'wpc-estimated-delivery-date' ); ?></option>
                            </select>
                            <span class="description"><?php esc_html_e( 'Show the date on order items (order confirmation or emails).', 'wpc-estimated-delivery-date' ); ?></span>
                        </div>
                    </div>

                    <div class="wpced-settings-row">
                        <div class="wpced-settings-label">
                            <strong><?php esc_html_e( 'Reload dates', 'wpc-estimated-delivery-date' ); ?></strong>
                        </div>
                        <div class="wpced-settings-field">
                            <select name="wpced_settings[reload_dates]">
                                <option value="yes" <?php selected( $reload_dates, 'yes' ); ?>><?php esc_html_e( 'Yes', 'wpc-estimated-delivery-date' ); ?></option>
                                <option value="no" <?php selected( $reload_dates, 'no' ); ?>><?php esc_html_e( 'No', 'wpc-estimated-delivery-date' ); ?></option>
                            </select>
                            <span class="description"><?php esc_html_e( 'Dates will be reloaded when opening the page? If you use the cache for your site, please turn on this option.', 'wpc-estimated-delivery-date' ); ?></span>
                        </div>
                    </div>

                    <div class="wpced-settings-row">
                        <div class="wpced-settings-label">
                            <strong><?php esc_html_e( 'Date format', 'wpc-estimated-delivery-date' ); ?></strong>
                        </div>
                        <div class="wpced-settings-field">
                            <?php
                            $date_formats = [
                                'Y/m/d',
                                'd/m/Y',
                                'm/d/y',
                                'm/d/Y',
                                'Y-m-d',
                                'd-m-Y',
                                'm-d-y',
                                'Y.m.d',
                                'd.m.Y',
                                'm.d.y',
                                'F j, Y',
                                'M j, Y',
                                'jS \of F',
                                'jS F',
                                'j. F',
                                'l j. F',
                                'F jS',
                                'jS M',
                                'M jS'
                            ];
                            echo '<select name="wpced_settings[date_format]" class="wpced-date-format">';

                            foreach ( $date_formats as $df ) {
                                echo '<option value="' . esc_attr( $df ) . '" ' . selected( $date_format, $df, false ) . '>' . esc_html( current_time( $df ) ) . '</option>';
                            }

                            echo '<option value="days" ' . selected( $date_format, 'days', false ) . '>' . esc_html__( 'Days count', 'wpc-estimated-delivery-date' ) . '</option>';
                            echo '<option value="custom" ' . selected( $date_format, 'custom', false ) . '>' . esc_html__( 'Custom', 'wpc-estimated-delivery-date' ) . '</option>';

                            echo '</select> ';
                            ?>
                            <input type="text" class="text wpced-date-format-custom"
                                   name="wpced_settings[date_format_custom]"
                                   value="<?php echo esc_attr( $date_format_custom ); ?>"/>
                            <span class="wpced-date-format-preview"><?php echo sprintf( /* translators: %s: date preview */ esc_html__( 'Preview: %s', 'wpc-estimated-delivery-date' ), esc_html( current_time( $date_format_custom ) ) ); ?></span>
                            <p class="description">
                                <a href="https://wordpress.org/documentation/article/customize-date-and-time-format/"
                                   target="_blank"><?php esc_html_e( 'Documentation on date and time formatting.', 'wpc-estimated-delivery-date' ); ?></a>
                            </p>
                        </div>
                    </div>

                    <div class="wpced-settings-row">
                        <div class="wpced-settings-label">
                            <strong><?php esc_html_e( 'Message', 'wpc-estimated-delivery-date' ); ?></strong>
                        </div>
                        <div class="wpced-settings-field">
                            <div class="wpced-message-group">
                                <label class="wpced-field-sublabel"><?php esc_html_e( 'Have both minimum and maximum days', 'wpc-estimated-delivery-date' ); ?></label>
                                <input type="text" name="wpced_settings[text]" class="large-text wpced-input-full"
                                       value="<?php echo esc_attr( Wpced_Backend()->get_setting( 'text' ) ); ?>"
                                       placeholder="<?php /* translators: %s: delivery date */
                                       esc_attr_e( 'Estimated delivery dates: %s', 'wpc-estimated-delivery-date' ); ?>"/>

                                <label class="wpced-field-sublabel"><?php esc_html_e( 'Have minimum days only', 'wpc-estimated-delivery-date' ); ?></label>
                                <input type="text" name="wpced_settings[text_min]" class="large-text wpced-input-full"
                                       value="<?php echo esc_attr( Wpced_Backend()->get_setting( 'text_min' ) ); ?>"
                                       placeholder="<?php /* translators: %s: delivery date */
                                       esc_attr_e( 'Earliest estimated delivery date: %s', 'wpc-estimated-delivery-date' ); ?>"/>

                                <label class="wpced-field-sublabel"><?php esc_html_e( 'Have maximum days only', 'wpc-estimated-delivery-date' ); ?></label>
                                <input type="text" name="wpced_settings[text_max]" class="large-text wpced-input-full"
                                       value="<?php echo esc_attr( Wpced_Backend()->get_setting( 'text_max' ) ); ?>"
                                       placeholder="<?php /* translators: %s: delivery date */
                                       esc_attr_e( 'Latest estimated delivery date: %s', 'wpc-estimated-delivery-date' ); ?>"/>

                                <label class="wpced-field-sublabel"><?php esc_html_e( 'Cart item\'s data label', 'wpc-estimated-delivery-date' ); ?></label>
                                <input type="text" name="wpced_settings[text_cart_item]" class="large-text wpced-input-full"
                                       value="<?php echo esc_attr( Wpced_Backend()->get_setting( 'text_cart_item' ) ); ?>"
                                       placeholder="<?php esc_attr_e( 'Estimated delivery date', 'wpc-estimated-delivery-date' ); ?>"/>

                                <label class="wpced-field-sublabel"><?php esc_html_e( 'Cart overall', 'wpc-estimated-delivery-date' ); ?></label>
                                <input type="text" name="wpced_settings[text_cart_overall]" class="large-text wpced-input-full"
                                       value="<?php echo esc_attr( Wpced_Backend()->get_setting( 'text_cart_overall' ) ); ?>"
                                       placeholder="<?php /* translators: %s: delivery date */
                                       esc_attr_e( 'Overall estimated dispatch date: %s', 'wpc-estimated-delivery-date' ); ?>"/>
                            </div>
                            <span class="description"><?php /* translators: date */
                                esc_html_e( 'Use %s to show the date or date-range. Leave blank to use the default text and its equivalent translation in multiple languages.', 'wpc-estimated-delivery-date' ); ?></span>
                        </div>
                    </div>

                    <div class="wpced-settings-row">
                        <div class="wpced-settings-label">
                            <strong><?php esc_html_e( 'Current time', 'wpc-estimated-delivery-date' ); ?></strong>
                        </div>
                        <div class="wpced-settings-field">
                            <div class="wpced-current-time-badges">
                                <code><?php echo esc_html( current_time( 'l' ) ); ?></code>
                                <code><?php echo esc_html( current_time( 'm/d/Y' ) ); ?></code>
                                <code><?php echo esc_html( current_time( 'h:i a' ) ); ?></code>
                                <a href="<?php echo esc_url( admin_url( 'options-general.php' ) ); ?>"
                                   target="_blank"><?php esc_html_e( 'Date/time settings', 'wpc-estimated-delivery-date' ); ?></a>
                            </div>
                        </div>
                    </div>

                    <div class="wpced-settings-row">
                        <div class="wpced-settings-label">
                            <strong><?php esc_html_e( 'Extra time line', 'wpc-estimated-delivery-date' ); ?></strong>
                        </div>
                        <div class="wpced-settings-field">
                            <input type="text" name="wpced_settings[extra_time_line]" class="wpced-time-val"
                                   value="<?php echo esc_attr( Wpced_Backend()->get_setting( 'extra_time_line' ) ); ?>"
                                   readonly/>
                            <span class="description"><?php esc_html_e( 'Maximum time to consider an extra day of shipping.', 'wpc-estimated-delivery-date' ); ?></span>
                        </div>
                    </div>

                    <div class="wpced-settings-row">
                        <div class="wpced-settings-label">
                            <strong><?php esc_html_e( 'Skipped dates', 'wpc-estimated-delivery-date' ); ?></strong>
                        </div>
                        <div class="wpced-settings-field">
                            <?php if ( defined( 'WPCED_PREMIUM' ) ) : ?>
                                <select name="wpced_settings[skipped_advanced]" class="wpced-skipped-advanced">
                                    <option value="no" <?php selected( $skipped_advanced, 'no' ); ?>><?php esc_html_e( 'Simple', 'wpc-estimated-delivery-date' ); ?></option>
                                    <option value="yes" <?php selected( $skipped_advanced, 'yes' ); ?>><?php esc_html_e( 'Advanced (Dispatch & Delivery)', 'wpc-estimated-delivery-date' ); ?></option>
                                </select>
                            <?php endif; ?>

                            <div class="wpced-skipped-dates-simple <?php echo esc_attr( ( defined( 'WPCED_PREMIUM' ) && $skipped_advanced === 'yes' ) ? 'wpced-hidden' : '' ); ?>">
                                <div class="wpced-skipped-section">
                                    <span class="description"><?php esc_html_e( 'Select dates to skip in estimated (most shipping don\'t work on weekends, so you can select Saturday, Sunday and all the saturday and sundays will not be counted in calculating estimated shipping date).', 'wpc-estimated-delivery-date' ); ?></span>
                                    <div class="wpced-skipped-dates" data-context="">
                                        <?php
                                        if ( ! empty( $skipped_dates ) && is_array( $skipped_dates ) ) {
                                            $date_context = '';

                                            foreach ( $skipped_dates as $date_key => $date ) {
                                                include WPCED_DIR . 'includes/templates/date.php';
                                            }
                                        }
                                        ?>
                                    </div>
                                    <div class="wpced-add-date">
                                        <input type="button" class="button wpced-add-date-btn" data-context=""
                                               value="<?php esc_attr_e( '+ Add date', 'wpc-estimated-delivery-date' ); ?>">
                                    </div>
                                </div>
                            </div>

                            <?php if ( defined( 'WPCED_PREMIUM' ) ) : ?>
                                <div class="wpced-skipped-dates-advanced <?php echo esc_attr( $skipped_advanced === 'yes' ? '' : 'wpced-hidden' ); ?>">
                                    <div class="wpced-skipped-section">
                                        <h4><?php esc_html_e( 'Dispatch', 'wpc-estimated-delivery-date' ); ?></h4>
                                        <span class="description"><?php esc_html_e( 'Days when your warehouse/store does NOT process or dispatch orders (e.g. weekends). Orders placed on these days will be processed on the next available dispatch day.', 'wpc-estimated-delivery-date' ); ?></span>
                                        <div class="wpced-skipped-dates" data-context="dispatch">
                                            <?php
                                            if ( ! empty( $skipped_dates_dispatch ) && is_array( $skipped_dates_dispatch ) ) {
                                                $date_context = 'dispatch';

                                                foreach ( $skipped_dates_dispatch as $date_key => $date ) {
                                                    include WPCED_DIR . 'includes/templates/date.php';
                                                }
                                            }
                                            ?>
                                        </div>
                                        <div class="wpced-add-date">
                                            <input type="button" class="button wpced-add-date-btn" data-context="dispatch"
                                                   value="<?php esc_attr_e( '+ Add date', 'wpc-estimated-delivery-date' ); ?>">
                                        </div>
                                    </div>

                                    <div class="wpced-skipped-section">
                                        <h4><?php esc_html_e( 'Delivery', 'wpc-estimated-delivery-date' ); ?></h4>
                                        <span class="description"><?php esc_html_e( 'Days when the carrier does NOT deliver packages to buyers (e.g. Sundays). These days will be skipped when counting shipping/transit days.', 'wpc-estimated-delivery-date' ); ?></span>
                                        <div class="wpced-skipped-dates" data-context="delivery">
                                            <?php
                                            if ( ! empty( $skipped_dates_delivery ) && is_array( $skipped_dates_delivery ) ) {
                                                $date_context = 'delivery';

                                                foreach ( $skipped_dates_delivery as $date_key => $date ) {
                                                    include WPCED_DIR . 'includes/templates/date.php';
                                                }
                                            }
                                            ?>
                                        </div>
                                        <div class="wpced-add-date">
                                            <input type="button" class="button wpced-add-date-btn" data-context="delivery"
                                                   value="<?php esc_attr_e( '+ Add date', 'wpc-estimated-delivery-date' ); ?>">
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="wpced-card">
                    <div class="wpced-card-header">
                        <div>
                            <h2 class="wpced-card-title"><?php esc_html_e( 'Rules', 'wpc-estimated-delivery-date' ); ?></h2>
                            <p class="wpced-card-desc">
                                <?php esc_html_e( 'Rules are checked from top to bottom. The first matched rule will be applied.', 'wpc-estimated-delivery-date' ); ?>
                            </p>
                        </div>
                        <div class="wpced-card-header-actions">
                            <a href="#" class="wpced-action-tool-btn wpced_expand_all">
                                <span class="dashicons dashicons-arrow-down-alt2"></span> <?php esc_html_e( 'Expand All', 'wpc-estimated-delivery-date' ); ?>
                            </a>
                            <a href="#" class="wpced-action-tool-btn wpced_collapse_all">
                                <span class="dashicons dashicons-arrow-up-alt2"></span> <?php esc_html_e( 'Collapse All', 'wpc-estimated-delivery-date' ); ?>
                            </a>
                            <button type="button" class="wpced-import-export-btn wpclever_export"
                                    data-key="wpced_rules"
                                    data-name="rules">
                                <span class="dashicons dashicons-database-export"></span>
                                <?php esc_html_e( 'Import / Export', 'wpc-estimated-delivery-date' ); ?>
                            </button>
                        </div>
                    </div>

                    <div class="wpced-settings">
                        <div class="wpced-items-wrapper">
                            <div class="wpced-items">
                                <?php
                                // variables for rule.php
                                $product_id   = 0;
                                $is_variation = false;

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
                                unset( $rules['default'] );

                                foreach ( $rules as $key => $rule ) {
                                    include WPCED_DIR . 'includes/templates/rule.php';
                                }
                                ?>
                            </div>
                        </div>
                        <div class="wpced-add-rule wpced-item-new"
                             data-product_id="<?php echo esc_attr( $product_id ); ?>"
                             data-is_variation="<?php echo esc_attr( $is_variation ? 'true' : 'false' ); ?>">
                            <span>+</span> <?php esc_html_e( 'Add rule', 'wpc-estimated-delivery-date' ); ?>
                        </div>
                    </div>
                </div>

                <div class="wpced-submit-row">
                    <?php
                    settings_fields( 'wpced_settings' );
                    submit_button( '', 'primary', 'submit', false );

                    if ( function_exists( 'wpc_last_saved' ) ) {
                        wpc_last_saved( Wpced_Backend()->get_settings() );
                    }
                    ?>
                </div>
            </form>
        <?php } elseif ( $active_tab == 'premium' ) { ?>
            <div class="wpced-card">
                <div class="wpced-card-header">
                    <div>
                        <h2 class="wpced-card-title"><?php esc_html_e( 'Premium Version', 'wpc-estimated-delivery-date' ); ?></h2>
                        <p class="wpced-card-desc"><?php esc_html_e( 'Unlock powerful features with WPC Estimated Delivery Date Premium.', 'wpc-estimated-delivery-date' ); ?></p>
                    </div>
                </div>
                <div class="wpced-settings-page-content-text">
                    <p><?php esc_html_e( 'Get the Premium Version just $29!', 'wpc-estimated-delivery-date' ); ?>
                        <a href="https://wpclever.net/downloads/wpc-estimated-delivery-date/?utm_source=pro&utm_medium=wpced&utm_campaign=wporg"
                           target="_blank">https://wpclever.net/downloads/wpc-estimated-delivery-date/</a>
                    </p>
                    <p><strong><?php esc_html_e( 'Extra features for Premium Version:', 'wpc-estimated-delivery-date' ); ?></strong></p>
                    <ul class="wpced-mb-0">
                        <li>- <?php esc_html_e( 'Use the combined source.', 'wpc-estimated-delivery-date' ); ?></li>
                        <li>- <?php esc_html_e( 'Add custom skipped dates.', 'wpc-estimated-delivery-date' ); ?></li>
                        <li>- <?php esc_html_e( 'Get the lifetime update & premium support.', 'wpc-estimated-delivery-date' ); ?></li>
                    </ul>
                </div>
            </div>
        <?php } elseif ( $active_tab === 'simulator' ) { ?>
            <?php Wpced_Backend()->render_simulator(); ?>
        <?php } ?>
    </div>
</div>
