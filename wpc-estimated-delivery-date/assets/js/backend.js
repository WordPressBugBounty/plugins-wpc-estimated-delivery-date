'use strict';

(function ($) {
    var wpced_timer = 0;

    $(function () {
        // ready
        init_terms();
        init_sortable();
        init_zone();
        init_date();
        init_time();
        init_scheduled();
        init_date_format();
        init_skipped_advanced();
        init_apply();
        init_condition_terms();
        init_simulator();
    });

    $(document).on('keyup change keypress', '.wpced-rule-name-input', function () {
        let $this = $(this), value = $this.val();

        if (value !== '') {
            $this.closest('.wpced-rule').find('.wpced-item-name-key').text(value);
        } else {
            $this.closest('.wpced-rule').find('.wpced-item-name-key').text('#' + $this.data('key'));
        }
    });

    $(document).on('change', '.wpced-date-format', function () {
        init_date_format();
    });

    $(document).on('keyup change', '.wpced-date-format-custom', function () {
        let value = $(this).val();

        if (value !== '') {
            if (wpced_timer != null) {
                clearTimeout(wpced_timer);
            }

            wpced_timer = setTimeout(date_format_preview, 300);
        } else {
            $('.wpced-date-format-preview').html('');
        }
    });

    $(document).on('woocommerce_variations_added woocommerce_variations_loaded',
        function () {
            init_scheduled();
        });

    $(document).on('change', '.wpced_apply', function () {
        var $item = $(this).closest('.wpced-item');
        init_apply_label($item);
        init_apply($item);
        init_terms();
    });

    $(document).on('change', '.wpced_apply_val', function () {
        var apply = $(this).closest('.wpced-item').find('.wpced_apply').val();
        init_apply_label($(this).closest('.wpced-item'));
        $(this).data(apply, $(this).val().join());
    });

    $(document).on('change', '.wpced_apply_condition_select', function () {
        init_condition_item($(this).closest('.wpced_apply_condition'));
    });

    $(document).on('click touch', '.wpced_apply_condition_remove', function (e) {
        e.preventDefault();
        $(this).closest('.wpced_apply_condition').remove();
    });

    $(document).on('click touch', '.wpced_new_apply_condition', function (e) {
        e.preventDefault();
        var $this = $(this),
            rule_key = $this.data('rule_key'),
            name = $this.data('name') || '',
            $conditions_wrap = $this.closest('.wpced_apply_combined').find('.wpced_apply_conditions');

        $.post(ajaxurl, {
            action: 'wpced_add_apply_condition',
            rule_key: rule_key,
            name: name,
        }, function (response) {
            $conditions_wrap.append(response);
            init_condition_terms($conditions_wrap.find('.wpced_apply_condition:last-child'));
        });
    });

    $(document).on('change', '.wpced_zone', function () {
        init_zone();
    });

    $(document).on('change', '.wpced-date-type', function () {
        init_date();
    });

    $(document).on('change', '.wpced-select-enable', function () {
        init_single($(this));
    });

    $(document).on('click touch', '.wpced-item-header', function (e) {
        if (($(e.target).closest('.wpced-item-duplicate').length === 0) &&
            ($(e.target).closest('.wpced-item-remove').length === 0) &&
            ($(e.target).closest('.wpced-item-summary, .wpced_summary_btn').length === 0)) {
            $(this).closest('.wpced-item').toggleClass('active');
        }
    });

    $(document).on('click touch', '.wpced_expand_all', function (e) {
        e.preventDefault();
        $('.wpced-item').addClass('active');
    });

    $(document).on('click touch', '.wpced_collapse_all', function (e) {
        e.preventDefault();
        $('.wpced-item').removeClass('active');
    });

    $(document).on('click touch', '.wpced-item-remove', function () {
        var r = confirm(
            'Do you want to remove this role? This action cannot undo.');

        if (r == true) {
            $(this).closest('.wpced-item').remove();
        }
    });

    $(document).on('click touch', '.wpced-item-new', function () {
        let $this = $(this), product_id = $this.data('product_id'),
            is_variation = $this.data('is_variation'),
            $rules = $this.closest('.wpced-settings').find('.wpced-rules');

        $this.prop('disabled', true);
        $rules.addClass('wpced-items-loading');

        $.post(ajaxurl, {
            action: 'wpced_add_rule',
            product_id: product_id,
            is_variation: is_variation,
        }, function (response) {
            $rules.append(response);
            $this.prop('disabled', false);
            $rules.find('.wpced-item:last-child').addClass('active');
            $rules.removeClass('wpced-items-loading');
            init_terms();
            init_scheduled();
        });
    });

    $(document).on('click touch', '.wpced-item-duplicate', function () {
        let $this = $(this), product_id = $this.data('product_id'),
            is_variation = $this.data('is_variation'),
            $rules = $this.closest('.wpced-rules'),
            $rule = $this.closest('.wpced-rule'),
            rule_data = $rule.find('input, select, button, textarea').serialize() ||
                0;

        $rules.addClass('wpced-items-loading');

        $.post(ajaxurl, {
            action: 'wpced_add_rule',
            product_id: product_id,
            is_variation: is_variation,
            rule_data: rule_data,
        }, function (response) {
            var $newRule = $(response).addClass('active').insertAfter($rule);
            $rules.removeClass('wpced-items-loading');
            init_terms();
            init_scheduled();
            // Re-initialize selectWoo for all condition term selects in the new rule
            init_condition_terms($newRule);
        });
    });

    // Rule Summary Modal
    $(document).on('click touch', '.wpced-item-summary', function (e) {
        e.preventDefault();
        e.stopPropagation();

        var $item = $(this).closest('.wpced-item');
        var key = $item.find('.wpced-rule-name-input').data('key') || '';
        var ruleName = $item.find('.wpced-rule-name-input').val() || ('#' + key);

        // Check if this rule is an override inside a product
        var isOverride = $item.closest('.wpced-single-product').length > 0 ||
            $item.closest('.wpced-variation-settings').length > 0 ||
            $item.closest('#woocommerce-product-data').length > 0 ||
            parseInt($(this).data('product_id'), 10) > 0 ||
            parseInt($item.find('.wpced-item-duplicate').data('product_id'), 10) > 0;

        // Apply for (only for global rules, not product override)
        var applyVal = 'all';
        var applyText = '';
        var applyDetails = [];

        if (!isOverride) {
            var $applySelect = $item.find('.wpced_apply');
            applyVal = $applySelect.val() || 'all';
            applyText = $applySelect.find('option:selected').text().trim() || 'All products';

            if (applyVal === 'stock') {
                var cmp = $item.find('.wpced_apply_compare option:selected').text().trim();
                var num = $item.find('.wpced_apply_number').val();
                applyDetails.push(cmp + ' ' + num);
            } else if (applyVal === 'combined') {
                $item.find('.wpced_apply_condition').each(function () {
                    var $c = $(this);
                    var cType = $c.find('.wpced_apply_condition_select option:selected').text().trim();
                    var cVal = $c.find('.wpced_apply_condition_select').val();
                    var cDetail = '';

                    if (cVal === 'stock') {
                        var cCmp = $c.find('.wpced_apply_condition_compare option:selected').text().trim();
                        var cNum = $c.find('.wpced_apply_condition_number').val();
                        cDetail = cCmp + ' ' + cNum;
                    } else if (['instock', 'outofstock', 'backorder'].indexOf(cVal) === -1) {
                        var terms = [];
                        $c.find('.wpced_apply_condition_terms option:selected').each(function () {
                            terms.push($(this).text().trim());
                        });
                        if (terms.length) {
                            cDetail = terms.join(', ');
                        }
                    }

                    if (cType) {
                        applyDetails.push('<span class="wpced-sum-type">' + cType + '</span>' + (cDetail ? ': <span class="wpced-sum-value">' + cDetail + '</span>' : ''));
                    }
                });
            } else if (['all', 'instock', 'outofstock', 'backorder'].indexOf(applyVal) === -1) {
                // Taxonomy terms
                $item.find('.wpced_apply_terms .wpced_terms option:selected').each(function () {
                    applyDetails.push($(this).text().trim());
                });
            }
        }

        // Zone & Method
        var zoneText = $item.find('.wpced_zone option:selected').text().trim() || 'All zones';
        var methodText = $item.find('.wpced_method option:selected').text().trim() || 'All methods';

        // Delivery Days
        var minDays = $item.find('.wpced_min').val();
        var maxDays = $item.find('.wpced_max').val();
        var scheduled = $item.find('.wpced_scheduled').val() || '';

        var daysText = '';
        if (minDays !== '' && maxDays !== '') {
            daysText = minDays + ' - ' + maxDays + ' working days';
        } else if (minDays !== '') {
            daysText = 'Min ' + minDays + ' working days';
        } else if (maxDays !== '') {
            daysText = 'Max ' + maxDays + ' working days';
        } else {
            daysText = 'Not configured';
        }

        // Build HTML
        var html = '<div class="wpced-sum-section">';
        html += '<div class="wpced-sum-status active"><span class="wpced-sum-dot"></span> Active</div>';
        if (key) {
            html += '<div class="wpced-sum-badge">#' + key + '</div>';
        }
        html += '</div>';

        html += '<div class="wpced-sum-section">';
        html += '<div class="wpced-sum-label">Estimated Delivery Window</div>';
        html += '<div class="wpced-sum-detail"><strong class="wpced-sum-type">' + daysText + '</strong>';
        if (scheduled) {
            html += ' <span class="wpced-sum-scheduled">(Scheduled from: ' + scheduled + ')</span>';
        }
        html += '</div></div>';

        if (!isOverride) {
            html += '<div class="wpced-sum-section">';
            html += '<div class="wpced-sum-label">Apply for</div>';
            if (applyVal === 'combined') {
                html += '<div class="wpced-sum-detail"><strong class="wpced-sum-type">' + applyText + '</strong></div>';
                if (applyDetails.length > 0) {
                    html += '<div class="wpced-sum-conditions">';
                    for (var j = 0; j < applyDetails.length; j++) {
                        var cPrefix = j > 0 ? '<span class="wpced-sum-relation">AND</span> ' : '';
                        html += '<div class="wpced-sum-condition-item">' + cPrefix + applyDetails[j] + '</div>';
                    }
                    html += '</div>';
                }
            } else {
                html += '<div class="wpced-sum-detail"><strong class="wpced-sum-type">' + applyText + '</strong>';
                if (applyDetails.length > 0) {
                    html += ' (' + applyDetails.join(', ') + ')';
                }
                html += '</div>';
            }
            html += '</div>';
        }

        html += '<div class="wpced-sum-section">';
        html += '<div class="wpced-sum-label">Shipping Constraints</div>';
        html += '<div class="wpced-sum-detail">';
        html += '<span><strong>Zone:</strong> ' + zoneText + '</span> &bull; ';
        html += '<span><strong>Method:</strong> ' + methodText + '</span>';
        html += '</div></div>';

        if ($('#wpced-summary-modal').length === 0) {
            $('body').append('<div id="wpced-summary-modal"><div class="wpced-summary-content"></div></div>');
        }

        $('#wpced-summary-modal').attr('title', ruleName).find('.wpced-summary-content').html(html);
        $('#wpced-summary-modal').dialog({
            modal: true,
            width: 520,
            dialogClass: 'wpc-dialog wpced-dialog wpced-summary-dialog',
            open: function () {
                $('.ui-widget-overlay').bind('click', function () {
                    $('#wpced-summary-modal').dialog('close');
                });
            },
            buttons: {
                'Close': function () {
                    $(this).dialog('close');
                }
            }
        });
    });

    $(document).on('click touch', '.wpced-add-date-btn', function () {
        let $this = $(this),
            context = $this.data('context') || '',
            $section = $this.closest('.wpced-skipped-section');

        let $container = $section.length
            ? $section.find('.wpced-skipped-dates[data-context="' + context + '"]')
            : $this.closest('.wpced-settings-field, td').find('.wpced-skipped-dates[data-context="' + context + '"]');

        if (!$container.length) {
            $container = $('.wpced-skipped-dates[data-context="' + context + '"]');
        }

        $this.prop('disabled', true);

        $.post(ajaxurl, {
            action: 'wpced_add_date',
            context: context,
        }, function (response) {
            $container.append(response);
            init_date();
            $this.prop('disabled', false);
        });
    });

    $(document).on('click touch', '.wpced-date-remove', function (e) {
        $(this).closest('.wpced-skipped-date').remove();
        e.preventDefault();
    });

    $(document).on('change', '.wpced-skipped-advanced', function () {
        init_skipped_advanced();
    });

    $(document).on('click touch', '.wpced-update-dates', function (e) {
        var order_id = $('#post_ID').val();

        var data = {
            action: 'wpced_get_order_dates',
            nonce: wpced_vars.nonce,
            order_id: order_id,
        };

        $('#wpced_update_dates_dialog').addClass('loading');
        $('#wpced_update_dates_dialog').dialog({
            minWidth: 460,
            modal: true,
            dialogClass: 'wpc-dialog',
            open: function () {
                $('.ui-widget-overlay').bind('click', function () {
                    $('#wpced_update_dates_dialog').dialog('close');
                });
            },
        });

        $.post(ajaxurl, data, function (response) {
            $('#wpced_update_dates_dialog').html(response);
            $('#wpced_update_dates_dialog').removeClass('loading');
        });

        e.preventDefault();
    });

    $(document).on('click touch', '.wpced-order-items-save-btn', function (e) {
        var order_id = $('.wpced-order-items').data('id');
        var order_dates = [];

        $('#wpced_update_dates_dialog').addClass('loading');

        $('.wpced-order-item-date-val').each(function () {
            var $this = $(this), id = $this.data('id'), date = $this.val();

            order_dates.push({id: id, date: date});
        });

        var data = {
            action: 'wpced_save_order_dates',
            nonce: wpced_vars.nonce,
            order_id: order_id,
            order_dates: order_dates,
        };

        $.post(ajaxurl, data, function (response) {
            $('#wpced_update_dates_dialog').dialog('close');
            $('#woocommerce-order-items').trigger('wc_order_items_reload');
        });

        e.preventDefault();
    });

    function init_sortable() {
        $('.wpced-rules').sortable({
            handle: '.wpced-item-move',
        });
    }

    function init_single($select) {
        let state = $select.val(), $single = $select.closest('.wpced-settings').find('.wpced-single-product');

        if (state === 'override') {
            $single.show();
        } else {
            $single.hide();
        }
    }

    function init_apply_label($item) {
        let apply = $item.find('.wpced_apply').val(),
            $applyVal = $item.find('.wpced_apply_val'),
            apply_val = ($applyVal.length && $applyVal.val()) ? $applyVal.val().join() : '',
            apply_label = '';

        if (apply === 'all' || $item.hasClass('wpced-item-default')) {
            apply_label = 'all';
        } else if (apply === 'combined') {
            apply_label = 'combined';
        } else if (apply === 'instock' || apply === 'outofstock' || apply === 'backorder') {
            apply_label = apply;
        } else {
            apply_label = apply + ': ' + apply_val;
        }

        $item.find('.wpced-item-name-apply').html(apply_label);
    }

    function init_zone() {
        $('.wpced_zone').each(function () {
            var $this = $(this);
            var zone = $this.val();
            var $methods = $this.closest('.wpced-item').find('.wpced_method');

            if (zone === '' || zone === 'none' || zone === 'all') {
                $methods.val('all').trigger('change').prop('disabled', true);
            } else {
                $methods.prop('disabled', false);
                $methods.find('option').attr('disabled', 'disabled');
                $methods.find('option[data-zone="' + zone + '"]').removeAttr('disabled');
                $methods.find('option[value="all"]').removeAttr('disabled');
                $methods.find('option[value="none"]').removeAttr('disabled');
            }
        });
    }

    function init_scheduled() {
        $('.wpced_scheduled:not(.wpced_dpk_init)').wpcdpk({clearButton: true}).addClass('wpced_dpk_init');
    }

    function init_date_format() {
        if ($('.wpced-date-format').val() === 'custom') {
            $('.wpced-date-format-custom').show();
            $('.wpced-date-format-preview').show();
        } else {
            $('.wpced-date-format-custom').hide();
            $('.wpced-date-format-preview').hide();
        }
    }

    function init_date() {
        $('.wpced-date-type').each(function () {
            var $parent = $(this).closest('.wpced-skipped-date');
            var $val = $parent.find('.wpced-date-val');
            var type = $(this).val();

            if (type === 'cus' || type === 'range') {
                $val.show();

                // Destroy existing datepicker instance if type changed
                if ($val.data('wpcdpk')) {
                    $val.data('wpcdpk').destroy();
                    $val.removeClass('wpced_dpk_init wpced_dpk_range wpced_dpk_cus');
                }

                if (type === 'range' && !$val.hasClass('wpced_dpk_range')) {
                    $val.wpcdpk({range: true, multipleDatesSeparator: ' - '}).addClass('wpced_dpk_init wpced_dpk_range');
                } else if (type === 'cus' && !$val.hasClass('wpced_dpk_cus')) {
                    $val.wpcdpk().addClass('wpced_dpk_init wpced_dpk_cus');
                }
            } else {
                $val.hide();
            }
        });
    }

    function init_time() {
        $('.wpced-time-val:not(.wpced_dpk_init)').wpcdpk({
            timepicker: true, onlyTimepicker: true, clearButton: true,
        }).addClass('wpced_dpk_init');
    }

    function init_terms() {
        $('.wpced_terms').each(function () {
            var $this = $(this);
            var apply = $this.closest('.wpced-item').find('.wpced_apply').val();

            if (apply === 'all' || apply === 'instock' || apply === 'outofstock' ||
                apply === 'backorder' || apply === 'combined') {
                $this.closest('.wpced-item').find('.hide_if_apply_all').hide();
            } else if (apply === 'stock') {
                $this.closest('.wpced-item').find('.hide_if_apply_all').hide();
                $this.closest('.wpced-item').find('.show_if_apply_stock').show();
            } else {
                $this.closest('.wpced-item').find('.hide_if_apply_all').hide();
                $this.closest('.wpced-item').find('.show_if_apply_terms').show();
            }

            $this.selectWoo({
                ajax: {
                    url: ajaxurl, dataType: 'json', delay: 250, data: function (params) {
                        return {
                            q: params.term, action: 'wpced_search_term', taxonomy: apply,
                        };
                    }, processResults: function (data) {
                        var options = [];

                        if (data) {
                            $.each(data, function (index, text) {
                                options.push({id: text[0], text: text[1]});
                            });
                        }
                        return {
                            results: options,
                        };
                    }, cache: true,
                }, minimumInputLength: 1,
            });

            if ($this.data(apply) !== undefined && $this.data(apply) !== '') {
                $this.val(String($this.data(apply)).split(',')).change();
            } else {
                $this.val([]).change();
            }
        });
    }

    function date_format_preview() {
        $('.wpced-date-format-preview').html('...');
        wpced_timer = null;

        $.post(ajaxurl, {
            action: 'wpced_date_format_preview',
            date_format: $('.wpced-date-format-custom').val(),
        }, function (response) {
            $('.wpced-date-format-preview').html(response);
        });
    }

    function init_skipped_advanced() {
        if ($('.wpced-skipped-advanced').val() === 'yes') {
            $('.wpced-skipped-dates-simple').addClass('wpced-hidden').hide();
            $('.wpced-skipped-dates-advanced').removeClass('wpced-hidden').show();
        } else {
            $('.wpced-skipped-dates-simple').removeClass('wpced-hidden').show();
            $('.wpced-skipped-dates-advanced').addClass('wpced-hidden').hide();
        }
    }

    function init_apply($item) {
        if (typeof $item !== 'undefined') {
            var apply = $item.find('.wpced_apply').val();
            $item.find('.hide_if_apply_all').hide();
            $item.find('.wpced_apply_stock').hide();
            $item.find('.wpced_apply_terms').hide();
            $item.find('.wpced_apply_combined').addClass('wpced-hidden');

            if (apply === 'stock') {
                $item.find('.wpced_apply_stock').show();
            } else if (apply === 'combined') {
                $item.find('.wpced_apply_combined').removeClass('wpced-hidden');
            } else if (apply !== 'all' && apply !== 'instock' && apply !== 'outofstock' && apply !== 'backorder') {
                $item.find('.show_if_apply_terms').show();
            }
        } else {
            $('.wpced-item').each(function () {
                init_apply($(this));
            });
        }
    }

    function init_condition_item($condition) {
        var type = $condition.find('.wpced_apply_condition_select').val();
        var nonTermTypes = ['instock', 'outofstock', 'backorder', 'stock'];

        // Update data-taxonomy so selectWoo uses current value
        $condition.find('.wpced_apply_condition_terms').attr('data-taxonomy', type);

        if (type === 'stock') {
            $condition.find('.wpced_apply_condition_stock_wrap').removeClass('wpced-hidden');
            $condition.find('.wpced_apply_condition_terms_wrap').addClass('wpced-hidden');
        } else if (nonTermTypes.indexOf(type) !== -1) {
            $condition.find('.wpced_apply_condition_stock_wrap').addClass('wpced-hidden');
            $condition.find('.wpced_apply_condition_terms_wrap').addClass('wpced-hidden');
        } else {
            $condition.find('.wpced_apply_condition_stock_wrap').addClass('wpced-hidden');
            $condition.find('.wpced_apply_condition_terms_wrap').removeClass('wpced-hidden');
            init_condition_terms($condition);
        }
    }

    function init_condition_terms($scope) {
        var $selects;

        if (typeof $scope !== 'undefined') {
            $selects = $scope.find('.wpced_apply_condition_terms');
        } else {
            $selects = $('.wpced_apply_condition_terms');
        }

        $selects.each(function () {
            var $this = $(this);
            // Always read taxonomy from the sibling select (not cached data attr)
            var taxonomy = $this.closest('.wpced_apply_condition').find('.wpced_apply_condition_select').val();

            if (!taxonomy || ['instock', 'outofstock', 'backorder', 'stock'].indexOf(taxonomy) !== -1) {
                return;
            }

            // Destroy existing selectWoo instance before reinit so taxonomy is fresh
            if ($this.hasClass('select2-hidden-accessible')) {
                $this.selectWoo('destroy');
            }

            $this.selectWoo({
                ajax: {
                    url: ajaxurl,
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        // Use $this captured from outer scope — $(this) inside ajax callback is jqXHR
                        var currentTaxonomy = $this.closest('.wpced_apply_condition').find('.wpced_apply_condition_select').val();
                        return {
                            q: params.term,
                            action: 'wpced_search_term',
                            taxonomy: currentTaxonomy,
                        };
                    },
                    processResults: function (data) {
                        var options = [];

                        if (data) {
                            $.each(data, function (index, text) {
                                options.push({id: text[0], text: text[1]});
                            });
                        }

                        return {results: options};
                    },
                    cache: false,
                },
                minimumInputLength: 1,
            });
        });
    }

    // ──── Delivery Simulator ────────────────────────────────────

    function init_simulator() {
        // Initialize product search selectWoo
        $('#wpced-sim-product').each(function () {
            var $this = $(this);
            if (!$this.hasClass('select2-hidden-accessible')) {
                $this.selectWoo({
                    ajax: {
                        url: ajaxurl,
                        dataType: 'json',
                        delay: 250,
                        data: function (params) {
                            return {
                                term: params.term,
                                action: 'woocommerce_json_search_products_and_variations',
                                security: typeof woocommerce_admin_meta_boxes !== 'undefined' ?
                                    woocommerce_admin_meta_boxes.search_products_nonce :
                                    (typeof wpced_vars !== 'undefined' ? wpced_vars.nonce : '')
                            };
                        },
                        processResults: function (data) {
                            var terms = [];
                            if (data) {
                                $.each(data, function (id, text) {
                                    terms.push({ id: id, text: text });
                                });
                            }
                            return { results: terms };
                        },
                        cache: true
                    },
                    minimumInputLength: 1
                });
            }
        });

        // Initialize datetime picker on simulator datetime input
        $('#wpced-sim-datetime').each(function () {
            var $this = $(this);
            if ($.fn.wpcdpk && !$this.hasClass('wpced_dpk_init')) {
                $this.wpcdpk({
                    timepicker: true
                }).addClass('wpced_dpk_init');
            }
        });
    }

    // Set to current time button
    $(document).on('click', '#wpced-sim-now-btn', function (e) {
        e.preventDefault();
        var now = new Date();
        var month = String(now.getMonth() + 1).padStart(2, '0');
        var day = String(now.getDate()).padStart(2, '0');
        var year = now.getFullYear();
        var hours = now.getHours();
        var minutes = String(now.getMinutes()).padStart(2, '0');
        var ampm = hours >= 12 ? 'pm' : 'am';
        hours = hours % 12;
        hours = hours ? hours : 12;
        var hoursStr = String(hours).padStart(2, '0');
        var formatted = month + '/' + day + '/' + year + ' ' + hoursStr + ':' + minutes + ' ' + ampm;

        $('#wpced-sim-datetime').val(formatted).trigger('change');
    });

    // Shipping zone change in simulator: filter methods
    $(document).on('change', '#wpced-sim-zone', function () {
        var selectedZone = $(this).val();
        var $methodSelect = $('#wpced-sim-method');

        if (selectedZone === 'all') {
            $methodSelect.find('option').show().prop('disabled', false);
        } else {
            $methodSelect.find('option').each(function () {
                var $opt = $(this);
                var optVal = $opt.val();
                var optZone = $opt.data('zone');

                if (optVal === 'all' || String(optZone) === String(selectedZone)) {
                    $opt.show().prop('disabled', false);
                } else {
                    $opt.hide().prop('disabled', true);
                }
            });

            // Reset method if selected method does not belong to selected zone
            var currentMethodVal = $methodSelect.val();
            var $selectedOption = $methodSelect.find('option:selected');
            if (currentMethodVal !== 'all' && String($selectedOption.data('zone')) !== String(selectedZone)) {
                $methodSelect.val('all');
            }
        }
    });

    // Run Simulation button
    $(document).on('click', '#wpced-sim-run', function (e) {
        e.preventDefault();
        var $btn = $(this);
        var $spinner = $('#wpced-sim-spinner');
        var $results = $('#wpced-sim-results');
        var productId = $('#wpced-sim-product').val();
        var datetime = $('#wpced-sim-datetime').val();
        var zone = $('#wpced-sim-zone').val();
        var method = $('#wpced-sim-method').val();

        if (!productId) {
            alert('Please select a product to simulate.');
            $('#wpced-sim-product').selectWoo('open');
            return;
        }

        $btn.prop('disabled', true);
        $spinner.addClass('is-active');
        $results.removeClass('wpced_hide').html(
            '<div class="wpced-sim-loading"><span class="spinner is-active"></span> Calculating estimated delivery dates and evaluating rules...</div>'
        );

        $.post(ajaxurl, {
            action: 'wpced_simulate',
            nonce: wpced_vars.nonce,
            product_id: productId,
            datetime: datetime,
            zone: zone,
            method: method
        }, function (response) {
            $btn.prop('disabled', false);
            $spinner.removeClass('is-active');

            if (!response.success) {
                var errMsg = response.data && response.data.message ? response.data.message : 'Simulation failed.';
                $results.html('<div class="wpced-sim-empty-state"><span class="dashicons dashicons-warning"></span><p>' + errMsg + '</p></div>');
                return;
            }

            var data = response.data;
            var html = '';

            // Handle disabled state
            if (data.status === 'disabled') {
                html += '<div class="wpced-sim-product-card">';
                html += '  <img src="' + data.product.image + '" class="wpced-sim-product-img" alt="" />';
                html += '  <div class="wpced-sim-product-details">';
                html += '    <h3 class="wpced-sim-product-title">';
                html += '      <a href="' + data.product.edit_url + '" target="_blank">' + data.product.name + '</a>';
                html += '      <span class="wpced-sim-badge wpced-sim-badge-inactive">#' + data.product.id + '</span>';
                html += '    </h3>';
                html += '    <div class="wpced-sim-product-meta">';
                html += '      <div class="wpced-sim-meta-row">';
                html += '        <span><strong>SKU:</strong> ' + data.product.sku + '</span>';
                html += '        <span><strong>Type:</strong> ' + data.product.type + '</span>';
                html += '        <span><strong>Stock:</strong> ' + data.product.stock + ' (' + data.product.stock_qty + ')</span>';
                html += '      </div>';
                html += '    </div>';
                html += '  </div>';
                html += '</div>';
                html += '<div class="wpced-sim-empty-state">';
                html += '  <span class="dashicons dashicons-warning"></span>';
                html += '  <p><strong>Delivery date calculation is disabled for this product</strong> (Settings set to "Disable").</p>';
                html += '</div>';
                $results.html(html);
                return;
            }

            // 1. Product Card
            html += '<div class="wpced-sim-product-card">';
            html += '  <img src="' + data.product.image + '" class="wpced-sim-product-img" alt="" />';
            html += '  <div class="wpced-sim-product-details">';
            html += '    <h3 class="wpced-sim-product-title">';
            html += '      <a href="' + data.product.edit_url + '" target="_blank">' + data.product.name + '</a>';
            html += '      <span class="wpced-sim-badge wpced-sim-badge-inactive">#' + data.product.id + '</span>';
            var sourceClass = data.source_type === 'product' ? 'wpced-sim-source-product' : 'wpced-sim-source-global';
            var sourceLabel = data.source_type === 'product' ? 'Product Override Rules' : 'Global Rules';
            html += '      <span class="wpced-sim-source-tag ' + sourceClass + '">' + sourceLabel + '</span>';
            html += '    </h3>';
            html += '    <div class="wpced-sim-product-meta">';
            html += '      <div class="wpced-sim-meta-row">';
            html += '        <span><strong>SKU:</strong> ' + data.product.sku + '</span>';
            html += '        <span><strong>Type:</strong> ' + data.product.type + '</span>';
            html += '        <span><strong>Stock Status:</strong> ' + data.product.stock + '</span>';
            html += '        <span><strong>Stock Qty:</strong> ' + data.product.stock_qty + '</span>';
            html += '      </div>';
            html += '      <div class="wpced-sim-meta-row">';
            html += '        <span><strong>Simulated Time:</strong> ' + data.simulated_time.weekday + ', ' + data.simulated_time.datetime + ' (Week ' + data.simulated_time.week + ')</span>';
            html += '      </div>';
            html += '    </div>';
            html += '  </div>';
            html += '</div>';

            // 2. Delivery Result Hero Banner
            var calc = data.calculation;
            html += '<div class="wpced-sim-hero">';
            html += '  <div class="wpced-sim-hero-top">';
            html += '    <span class="wpced-sim-hero-tag"><span class="dashicons dashicons-yes-alt"></span> Calculated Estimated Delivery</span>';
            html += '    <span class="wpced-sim-hero-source">Winning Rule: ' + data.applied_rule.name + '</span>';
            html += '  </div>';
            html += '  <div class="wpced-sim-hero-main">';
            html += '    <div class="wpced-sim-hero-preview">' + calc.delivery_text_preview + '</div>';
            html += '    <div class="wpced-sim-hero-sub">';
            html += '      <span><span class="dashicons dashicons-calendar-alt"></span> <strong>Dispatch Date:</strong> ' + calc.dispatch_date_formatted + '</span>';
            if (calc.min_days !== null && calc.max_days !== null) {
                html += '      <span><span class="dashicons dashicons-clock"></span> <strong>Transit:</strong> ' + calc.min_days + ' &ndash; ' + calc.max_days + ' working days</span>';
            } else if (calc.min_days !== null) {
                html += '      <span><span class="dashicons dashicons-clock"></span> <strong>Min transit:</strong> ' + calc.min_days + ' working days</span>';
            } else if (calc.max_days !== null) {
                html += '      <span><span class="dashicons dashicons-clock"></span> <strong>Max transit:</strong> ' + calc.max_days + ' working days</span>';
            }
            if (calc.cutoff && calc.cutoff.is_triggered) {
                html += '      <span><span class="dashicons dashicons-warning"></span> <strong>Cut-off Applied:</strong> ' + calc.cutoff.cutoff_time + '</span>';
            }
            html += '    </div>';
            html += '  </div>';
            html += '</div>';

            // 3. Calculation Details Table
            html += '<div class="wpced-sim-section-header">';
            html += '  <h3><span class="dashicons dashicons-clipboard"></span> Calculation Breakdown</h3>';
            html += '</div>';
            html += '<div class="wpced-sim-table-wrap">';
            html += '  <table class="wpced-sim-table">';
            html += '    <thead>';
            html += '      <tr>';
            html += '        <th>Parameter</th>';
            html += '        <th>Value</th>';
            html += '        <th>Note</th>';
            html += '      </tr>';
            html += '    </thead>';
            html += '    <tbody>';
            html += '      <tr>';
            html += '        <td><strong>Order Simulated Date/Time</strong></td>';
            html += '        <td>' + data.simulated_time.datetime + ' (' + data.simulated_time.weekday + ')</td>';
            html += '        <td>Simulated moment of order placement</td>';
            html += '      </tr>';

            if (calc.cutoff && calc.cutoff.has_cutoff) {
                var cutoffStatus = calc.cutoff.is_triggered ?
                    '<span class="wpced-sim-badge wpced-sim-badge-warning">Triggered (Past cut-off)</span>' :
                    '<span class="wpced-sim-badge wpced-sim-badge-active">Within cut-off</span>';
                html += '      <tr' + (calc.cutoff.is_triggered ? ' class="wpced-sim-row-highlight"' : '') + '>';
                html += '        <td><strong>Daily Cut-off Time</strong></td>';
                html += '        <td>' + calc.cutoff.cutoff_time + ' ' + cutoffStatus + '</td>';
                html += '        <td>' + (calc.cutoff.note || 'Order placed before daily cut-off time.') + '</td>';
                html += '      </tr>';
            }

            html += '      <tr>';
            html += '        <td><strong>Warehouse Dispatch Date</strong></td>';
            html += '        <td><strong>' + calc.dispatch_date_formatted + '</strong></td>';
            html += '        <td>Day when shipping commences after skipped/holiday dates</td>';
            html += '      </tr>';

            if (calc.dispatch_skipped && calc.dispatch_skipped.length > 0) {
                html += '      <tr class="wpced-sim-row-highlight">';
                html += '        <td><strong>Dispatch Skipped Dates</strong></td>';
                html += '        <td><span class="wpced-sim-badge wpced-sim-badge-overridden">' + calc.dispatch_skipped.length + ' skipped day(s)</span></td>';
                html += '        <td>' + calc.dispatch_skipped.join(', ') + '</td>';
                html += '      </tr>';
            }

            if (calc.delivery_skipped && calc.delivery_skipped.length > 0) {
                html += '      <tr class="wpced-sim-row-highlight">';
                html += '        <td><strong>Delivery Skipped Dates</strong></td>';
                html += '        <td><span class="wpced-sim-badge wpced-sim-badge-overridden">' + calc.delivery_skipped.length + ' skipped day(s)</span></td>';
                html += '        <td>' + calc.delivery_skipped.join(', ') + '</td>';
                html += '      </tr>';
            }

            html += '      <tr>';
            html += '        <td><strong>Estimated Delivery Window</strong></td>';
            html += '        <td><strong>' + calc.date_range_display + '</strong></td>';
            html += '        <td>Calculated based on active rule transit days</td>';
            html += '      </tr>';
            html += '    </tbody>';
            html += '  </table>';
            html += '</div>';

            // 4. Evaluated Rules Breakdown
            if (data.evaluated_rules && data.evaluated_rules.length > 0) {
                html += '<div class="wpced-sim-section-header">';
                html += '  <h3><span class="dashicons dashicons-list-view"></span> Evaluated Delivery Rules (' + data.evaluated_rules.length + ')</h3>';
                html += '</div>';
                html += '<div class="wpced-sim-actions-list">';

                $.each(data.evaluated_rules, function (i, rule) {
                    var cardClass = rule.status === 'applied' ? 'is-active-card' :
                        (rule.status === 'overridden' ? 'is-overridden-card' : 'is-inactive-card');
                    var badgeClass = rule.status === 'applied' ? 'wpced-sim-badge-active' :
                        (rule.status === 'overridden' ? 'wpced-sim-badge-overridden' : 'wpced-sim-badge-inactive');

                    html += '<div class="wpced-sim-action-card ' + cardClass + '">';
                    html += '  <div class="wpced-sim-action-main">';
                    html += '    <div class="wpced-sim-action-title">';
                    html += '      <span>' + rule.name + '</span>';
                    html += '      <span class="wpced-sim-rule-key">#' + rule.key + '</span>';
                    html += '    </div>';
                    html += '    <div class="wpced-sim-action-meta">';
                    html += '      <span><strong>Apply for:</strong> ' + rule.apply_summary + '</span>';
                    html += '      <span><strong>Delivery days:</strong> ' + rule.min + ' - ' + rule.max + ' days</span>';
                    if (rule.scheduled && rule.scheduled !== '—') {
                        html += '      <span><strong>Scheduled:</strong> ' + rule.scheduled + '</span>';
                    }
                    html += '    </div>';
                    html += '  </div>';
                    html += '  <div class="wpced-sim-action-status-wrap">';
                    html += '    <span class="wpced-sim-badge ' + badgeClass + '">' + rule.status_label + '</span>';
                    if (rule.status_reason) {
                        html += '    <div class="wpced-sim-reason">' + rule.status_reason + '</div>';
                    }
                    html += '  </div>';
                    html += '</div>';
                });

                html += '</div>';
            }

            $results.html(html);
        });
    });

    // Reset button
    $(document).on('click', '#wpced-sim-reset', function (e) {
        e.preventDefault();
        $('#wpced-sim-product').val(null).trigger('change');
        $('#wpced-sim-zone').val('all').trigger('change');
        $('#wpced-sim-method').val('all');
        $('#wpced-sim-now-btn').trigger('click');
        $('#wpced-sim-results').addClass('wpced_hide').empty();
    });
})(jQuery);
