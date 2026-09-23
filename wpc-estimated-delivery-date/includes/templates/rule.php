<?php
/**
 * @var $key
 * @var $rule
 * @var $product_id
 * @var $is_variation
 */

defined( 'ABSPATH' ) || exit;

if ( empty( $key ) ) {
	$key = Wpced_Helper()->generate_key();
}

$default      = $key === 'default';
$hide_default = $default ? 'wpced-item-line-hide' : '';
$name         = '';

if ( $is_variation ) {
	$name = '_v[' . $product_id . ']';
}

$rule = array_merge( Wpced_Backend()->get_base_rule(), $rule );
?>
<div class="<?php echo esc_attr( $default ? 'active wpced-item wpced-rule wpced-item-' . $key : 'wpced-item wpced-rule wpced-item-' . $key ); ?>">
    <div class="wpced-item-header">
        <span class="wpced-item-move ui-sortable-handle hint--top" aria-label="<?php esc_attr_e( 'Drag to reorder', 'wpc-estimated-delivery-date' ); ?>"><span class="dashicons dashicons-menu"></span></span>
        <span class="wpced-item-name"><span
                    class="wpced-item-name-key"><?php echo esc_html( ! empty( $rule['name'] ) ? $rule['name'] : '#' . $key ); ?></span><span
                    class="wpced-item-name-apply"><?php echo esc_html( $rule['apply'] === 'all' ? 'all' : ( $rule['apply'] === 'combined' ? 'combined' : $rule['apply'] . ': ' . implode( ',', (array) $rule['apply_val'] ) ) ); ?></span></span>
		<?php if ( ! $default ) { ?>
            <span class="wpced-item-summary wpced_summary_btn hint--top"
                  data-product_id="<?php echo esc_attr( $product_id ); ?>"
                  data-is_variation="<?php echo esc_attr( $is_variation ? 'true' : 'false' ); ?>"
                  aria-label="<?php esc_attr_e( 'Summary', 'wpc-estimated-delivery-date' ); ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="16" y1="13" x2="8" y2="13"></line>
                    <line x1="16" y1="17" x2="8" y2="17"></line>
                    <polyline points="10 9 9 9 8 9"></polyline>
                </svg>
            </span>
            <span class="wpced-item-duplicate wpced_duplicate_btn hint--top" data-product_id="<?php echo esc_attr( $product_id ); ?>"
                  data-is_variation="<?php echo esc_attr( $is_variation ? 'true' : 'false' ); ?>"
                  aria-label="<?php esc_attr_e( 'Duplicate', 'wpc-estimated-delivery-date' ); ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                    <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                </svg>
            </span>
            <span class="wpced-item-remove wpced_remove_btn hint--top"
                  aria-label="<?php esc_attr_e( 'Remove', 'wpc-estimated-delivery-date' ); ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </span>
		<?php } ?>
    </div>
    <div class="wpced-item-content">
		<?php if ( ! $default ) { ?>
            <div class="wpced-item-line">
                <div class="wpced-item-label">
					<?php esc_html_e( 'Name', 'wpc-estimated-delivery-date' ); ?>
                </div>
                <div class="wpced-item-input">
                    <label>
                        <input type="text" value="<?php echo esc_attr( $rule['name'] ); ?>"
                               name="<?php echo esc_attr( 'wpced_rules' . $name . '[' . $key . '][name]' ); ?>"
                               data-key="<?php echo esc_attr( $key ); ?>" class="wpced-rule-name-input"/>
                    </label>
                    <span><?php esc_html_e( 'For management use only.', 'wpc-estimated-delivery-date' ); ?></span>
                </div>
            </div>
		<?php } ?>
        <div class="wpced-item-line wpced-item-apply <?php echo esc_attr( $hide_default ); ?>">
            <div class="wpced-item-label">
				<?php esc_html_e( 'Apply for', 'wpc-estimated-delivery-date' ); ?>
            </div>
            <div class="wpced-item-input">
                <label>
                    <select class="wpced_apply"
                            name="<?php echo esc_attr( 'wpced_rules' . $name . '[' . $key . '][apply]' ); ?>">
                        <option value="all" <?php selected( $rule['apply'], 'all' ); ?>><?php esc_attr_e( 'All products', 'wpc-estimated-delivery-date' ); ?></option>
                        <option value="instock" <?php selected( $rule['apply'], 'instock' ); ?>><?php esc_html_e( 'In stock', 'wpc-estimated-delivery-date' ); ?></option>
                        <option value="outofstock" <?php selected( $rule['apply'], 'outofstock' ); ?>><?php esc_html_e( 'Out of stock', 'wpc-estimated-delivery-date' ); ?></option>
                        <option value="backorder" <?php selected( $rule['apply'], 'backorder' ); ?>><?php esc_html_e( 'On backorder', 'wpc-estimated-delivery-date' ); ?></option>
                        <option value="stock" <?php selected( $rule['apply'], 'stock' ); ?>><?php esc_html_e( 'Stock quantity', 'wpc-estimated-delivery-date' ); ?></option>
                        <option value="combined" disabled><?php esc_html_e( 'Combined (Premium)', 'wpc-estimated-delivery-date' ); ?></option>
						<?php
						$taxonomies = get_object_taxonomies( 'product', 'objects' );

						foreach ( $taxonomies as $taxonomy ) {
							echo '<option value="' . esc_attr( $taxonomy->name ) . '" ' . selected( $rule['apply'], $taxonomy->name, false ) . '>' . esc_html( $taxonomy->label ) . '</option>';
						}
						?>
                    </select> </label>
                <div class="wpced_apply_stock hide_if_apply_all show_if_apply_stock">
					<?php
					echo '<select class="wpced_apply_compare" name="' . esc_attr( 'wpced_rules' . $name . '[' . $key . '][apply_compare]' ) . '">';
					echo '<option value="equal" ' . selected( $rule['apply_compare'], 'equal', false ) . '>' . esc_html__( 'Equal to', 'wpc-estimated-delivery-date' ) . '</option>';
					echo '<option value="not_equal" ' . selected( $rule['apply_compare'], 'not_equal', false ) . '>' . esc_html__( 'Not equal to', 'wpc-estimated-delivery-date' ) . '</option>';
					echo '<option value="greater" ' . selected( $rule['apply_compare'], 'greater', false ) . '>' . esc_html__( 'Greater than', 'wpc-estimated-delivery-date' ) . '</option>';
					echo '<option value="greater_equal" ' . selected( $rule['apply_compare'], 'greater_equal', false ) . '>' . esc_html__( 'Greater or equal to', 'wpc-estimated-delivery-date' ) . '</option>';
					echo '<option value="less" ' . selected( $rule['apply_compare'], 'less', false ) . '>' . esc_html__( 'Less than', 'wpc-estimated-delivery-date' ) . '</option>';
					echo '<option value="less_equal" ' . selected( $rule['apply_compare'], 'less_equal', false ) . '>' . esc_html__( 'Less or equal to', 'wpc-estimated-delivery-date' ) . '</option>';
					echo '</select>';
					echo '<input type="number" step="any" class="wpced_apply_number" name="' . esc_attr( 'wpced_rules' . $name . '[' . $key . '][apply_number]' ) . '" value="' . esc_attr( $rule['apply_number'] ) . '"/>';
					?>
                </div>
                <div class="wpced_apply_terms hide_if_apply_all show_if_apply_terms">
                    <label>
                        <select class="wpced_terms wpced_apply_val" multiple="multiple"
                                name="<?php echo esc_attr( 'wpced_rules' . $name . '[' . $key . '][apply_val][]' ); ?>"
                                data-<?php echo esc_attr( $rule['apply'] ); ?>="<?php echo esc_attr( implode( ',', (array) $rule['apply_val'] ) ); ?>">
							<?php if ( is_array( $rule['apply_val'] ) && ! empty( $rule['apply_val'] ) ) {
								foreach ( $rule['apply_val'] as $t ) {
									if ( $term = get_term_by( 'slug', $t, $rule['apply'] ) ) {
										echo '<option value="' . esc_attr( $t ) . '" selected>' . esc_html( $term->name ) . '</option>';
									}
								}
							} ?>
                        </select> </label>
                </div>
            </div>
        </div>
        <div class="wpced-item-line <?php echo esc_attr( $hide_default ); ?>">
            <div class="wpced-item-label">
				<?php esc_html_e( 'Shipping zone', 'wpc-estimated-delivery-date' ); ?>
            </div>
            <div class="wpced-item-input">
				<?php
				$zones = Wpced_Backend()->get_zones();

				if ( ! empty( $zones ) ) {
					echo '<select class="wpced_zone" name="' . esc_attr( 'wpced_rules' . $name . '[' . $key . '][zone]' ) . '">';

					echo '<option value="all">' . esc_html__( 'All zones', 'wpc-estimated-delivery-date' ) . '</option>';

					foreach ( $zones as $zone_id => $zone_name ) {
						echo '<option value="' . esc_attr( $zone_id ) . '" ' . selected( $rule['zone'], $zone_id, false ) . '>' . esc_html( $zone_name ) . '</option>';
					}

					echo '</select>';
				}
				?>
            </div>
        </div>
        <div class="wpced-item-line <?php echo esc_attr( $hide_default ); ?>">
            <div class="wpced-item-label">
				<?php esc_html_e( 'Shipping method', 'wpc-estimated-delivery-date' ); ?>
            </div>
            <div class="wpced-item-input">
				<?php
				$methods = Wpced_Backend()->get_methods();

				if ( ! empty( $methods ) ) {
					echo '<select class="wpced_method" name="' . esc_attr( 'wpced_rules' . $name . '[' . $key . '][method]' ) . '">';

					echo '<option value="all">' . esc_html__( 'All methods', 'wpc-estimated-delivery-date' ) . '</option>';

					foreach ( $methods as $method_id => $method ) {
						echo '<option value="' . esc_attr( $method_id ) . '" data-zone="' . esc_attr( $method['zone'] ) . '" ' . selected( $rule['method'], $method_id, false ) . '>' . esc_html( $method['title'] ) . '</option>';
					}

					echo '</select>';
				}
				?>
            </div>
        </div>
        <div class="wpced-item-line">
            <div class="wpced-item-label">
				<?php esc_html_e( 'Minimum', 'wpc-estimated-delivery-date' ); ?>
            </div>
            <div class="wpced-item-input">
                <label>
                    <input type="number" value="<?php echo esc_attr( $rule['min'] ); ?>" class="wpced_min"
                           name="<?php echo esc_attr( 'wpced_rules' . $name . '[' . $key . '][min]' ); ?>"/>
                </label> <span><?php esc_html_e( 'days', 'wpc-estimated-delivery-date' ); ?></span>
            </div>
        </div>
        <div class="wpced-item-line">
            <div class="wpced-item-label">
				<?php esc_html_e( 'Maximum', 'wpc-estimated-delivery-date' ); ?>
            </div>
            <div class="wpced-item-input">
                <label>
                    <input type="number" value="<?php echo esc_attr( $rule['max'] ); ?>" class="wpced_max"
                           name="<?php echo esc_attr( 'wpced_rules' . $name . '[' . $key . '][max]' ); ?>"/>
                </label> <span><?php esc_html_e( 'days', 'wpc-estimated-delivery-date' ); ?></span>
            </div>
        </div>
        <div class="wpced-item-line">
            <div class="wpced-item-label">
				<?php esc_html_e( 'Scheduled delivery date', 'wpc-estimated-delivery-date' ); ?>
            </div>
            <div class="wpced-item-input">
                <label>
                    <input type="text" value="<?php echo esc_attr( $rule['scheduled'] ); ?>"
                           name="<?php echo esc_attr( 'wpced_rules' . $name . '[' . $key . '][scheduled]' ); ?>"
                           class="wpced_scheduled" readonly/>
                </label>
                <p class="description"><?php esc_html_e( 'You can schedule a date when the delivery will be conducted in the future and the estimated delivery dates will be calculated based on this.', 'wpc-estimated-delivery-date' ); ?></p>
            </div>
        </div>
    </div>
</div>
