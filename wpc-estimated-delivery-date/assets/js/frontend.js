(function ($) {
    'use strict';

    // Server time (UTC seconds) and the moment it was received, the countdown never uses the visitor's clock
    var wpcedSyncServer = parseInt(wpced_vars.server_time, 10) || 0;
    var wpcedSyncClient = Date.now();
    var wpcedLastRefresh = {};
    var WPCED_REFRESH_COOLDOWN = 5000;

    function wpcedSyncTime(serverTime) {
        serverTime = parseInt(serverTime, 10);

        if (serverTime > 0) {
            wpcedSyncServer = serverTime;
            wpcedSyncClient = Date.now();
        }
    }

    function wpcedServerNow() {
        return wpcedSyncServer + (Date.now() - wpcedSyncClient) / 1000;
    }

    // Keep in sync with Wpced_Frontend::format_countdown() in PHP
    function wpcedFormatCountdown(seconds, format) {
        var useDays = format.indexOf('{d}') !== -1;
        var useHours = format.indexOf('{h}') !== -1 || format.indexOf('{hh}') !== -1;
        var useMins = format.indexOf('{m}') !== -1 || format.indexOf('{mm}') !== -1;
        var days = 0, hours = 0, minutes = 0;

        seconds = Math.max(0, Math.floor(seconds));

        if (useDays) {
            days = Math.floor(seconds / 86400);
            seconds -= days * 86400;
        }

        if (useHours) {
            hours = Math.floor(seconds / 3600);
            seconds -= hours * 3600;
        }

        if (useMins) {
            minutes = Math.floor(seconds / 60);
            seconds -= minutes * 60;
        }

        var pad = function (n) {
            return n < 10 ? '0' + n : String(n);
        };

        var map = {
            '{d}': days,
            '{h}': hours,
            '{hh}': pad(hours),
            '{m}': minutes,
            '{mm}': pad(minutes),
            '{s}': seconds,
            '{ss}': pad(seconds),
        };

        return format.replace(/\{(?:d|hh|h|mm|m|ss|s)\}/g, function (token) {
            return map[token];
        });
    }

    // Ask the server for fresh messages of the expired countdowns, this rolls the date over to the next dispatch day
    function wpcedRefreshCountdowns(expired) {
        var items = [];
        var now = Date.now();

        $.each(expired, function (key, item) {
            if (now - (wpcedLastRefresh[key] || 0) >= WPCED_REFRESH_COOLDOWN) {
                wpcedLastRefresh[key] = now;
                items.push(item);
            }
        });

        if (!items.length) {
            return;
        }

        $.post(wpced_vars.wc_ajax_url.toString().replace('%%endpoint%%', 'wpced_countdown_refresh'), {items: items}, function (response) {
            if (!response || !response.dates) {
                return;
            }

            wpcedSyncTime(response.server_time);

            $.each(response.dates, function (key, html) {
                $('.wpced-countdown').filter(function () {
                    return $(this).attr('data-product') + '|' + $(this).attr('data-context') === key;
                }).each(function () {
                    var $wrapper = $(this).closest('.wpced');

                    if ($wrapper.length && !$wrapper.closest('.order_item').length) {
                        $wrapper.replaceWith(html);
                    }
                });
            });
        });
    }

    function wpcedTickCountdowns() {
        var $countdowns = $('.wpced-countdown');

        if (!$countdowns.length) {
            return;
        }

        var now = wpcedServerNow();
        var expired = {};

        $countdowns.each(function () {
            var $el = $(this);
            var target = parseFloat($el.attr('data-target'));

            if (isNaN(target)) {
                return;
            }

            var text = wpcedFormatCountdown(Math.ceil(target - now), $el.attr('data-format') || '{h}h {m}m {s}s');

            if ($el.text() !== text) {
                $el.text(text);
            }

            // wait 1s after the cut-off, so the server surely sees it as passed
            if (target - now <= -1) {
                var product = $el.attr('data-product');
                var context = $el.attr('data-context') || 'product';

                if (product) {
                    expired[product + '|' + context] = {id: product, context: context};
                }
            }
        });

        if (!$.isEmptyObject(expired)) {
            wpcedRefreshCountdowns(expired);
        }
    }

    $(function () {
        wpcedTickCountdowns();
        setInterval(wpcedTickCountdowns, 1000);
        document.addEventListener('visibilitychange', wpcedTickCountdowns);
    });

    $(function () {
        // ready, maybe need to reload dates
        if (wpced_vars.reload_dates) {
            let ids = [];

            $('.wpced').each(function () {
                let id = $(this).attr('data-id');

                if (typeof id !== typeof undefined && id !== false) {
                    ids.push(id);
                }
            });

            if (ids.length > 0) {
                // request reloaded dates
                var data = {
                    action: 'wpced_reload_dates', ids: ids, nonce: wpced_vars.nonce,
                };

                $.post(wpced_vars.wc_ajax_url.toString().replace('%%endpoint%%', 'wpced_reload_dates'), data, function (response) {
                    if (!$.isEmptyObject(response)) {
                        wpcedSyncTime(response.wpced_server_time);

                        $.each(response, function (key, value) {
                            if (key === 'wpced_server_time') {
                                return;
                            }

                            $('.' + key).each(function () {
                                if (!$(this).closest('.order_item').length) {
                                    $(this).replaceWith(value);
                                }
                            });
                        });
                    }
                });
            }
        }
    });

    $(document).on('found_variation', function (e, t) {
        if (t.wpced_enable !== undefined) {
            var pid = $(e['target']).closest('.variations_form').data('product_id');

            if (t.wpced_enable === 'disable') {
                $('.wpced-' + pid).html('');
            } else if (t.wpced_enable === 'override' && t.wpced_date !== undefined && t.wpced_date !== '') {
                $('.wpced-' + pid).replaceWith(wpced_decode_entities(t.wpced_date));
            } else {
                if (t.wpced_date !== '') {
                    $('.wpced-' + pid).replaceWith(wpced_decode_entities(t.wpced_date));
                } else {
                    var variable_date = $('.wpced-variable-' + pid).data('wpced');

                    if (variable_date !== undefined) {
                        $('.wpced-' + pid).replaceWith(wpced_decode_entities(variable_date));
                    }
                }
            }

            $(document.body).trigger('wpced_found_variation', [t]);
        }
    });

    $(document).on('reset_data', function (e) {
        var pid = $(e['target']).closest('.variations_form').data('product_id');
        var variable_date = $('.wpced-variable-' + pid).data('wpced');

        if (variable_date !== undefined) {
            $('.wpced-' + pid).replaceWith(wpced_decode_entities(variable_date));
        }

        $(document.body).trigger('wpced_reset_data');
    });

    $(document).on('updated_shipping_method', function () {
        $(document.body).trigger('wc_update_cart');
    });
})(jQuery);

function wpced_decode_entities(encodedString) {
    var textArea = document.createElement('textarea');
    textArea.innerHTML = encodedString;

    return textArea.value;
}