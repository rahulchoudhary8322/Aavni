/**
 * AAVNI Shiprocket Logistics Integration Client
 * Production-ready AJAX & UI Controller
 */

(function($) {
    'use strict';

    var currentOrderId = null;
    var currentOrderData = null;

    $(document).ready(function() {
        initSettingsHandlers();
        initOrderModalHandlers();
        initBulkActions();
    });

    /**
     * 1. Settings Screen Handlers
     */
    function initSettingsHandlers() {
        // Test Connection
        $('#aavni_sr_btn_test_conn').on('click', function(e) {
            e.preventDefault();
            var $btn = $(this);
            var $msg = $('#aavni_sr_conn_result');

            $btn.prop('disabled', true).html('<span class="sr-spin">⚙️</span> Connecting to Shiprocket API v1...');
            $msg.hide().removeClass('sr-alert-success sr-alert-error');

            $.post(aavniShiprocketParams.ajax_url, {
                action: 'aavni_sr_test_connection',
                nonce: aavniShiprocketParams.nonce
            }, function(res) {
                $btn.prop('disabled', false).html('⚡ Test Connection Now');
                $msg.show();
                if (res && res.success) {
                    $msg.addClass('sr-alert-success').html(res.data.message + '<br><small>Verified at: ' + res.data.last_connected + '</small>');
                    $('#aavni_sr_badge_status').html('<span class="aavni-badge aavni-badge-delivered">Connected &amp; Verified</span>');
                } else {
                    var err = (res && res.data && res.data.message) ? res.data.message : 'Authentication failed. Please verify credentials.';
                    $msg.addClass('sr-alert-error').html('❌ ' + err);
                    $('#aavni_sr_badge_status').html('<span class="aavni-badge aavni-badge-cancelled">Connection Failed</span>');
                }
            }).fail(function(xhr) {
                $btn.prop('disabled', false).html('⚡ Test Connection Now');
                $msg.show().addClass('sr-alert-error').html('❌ Server communication error: ' + xhr.statusText);
            });
        });

        // Fetch Pickups
        $('#aavni_sr_btn_fetch_pickups').on('click', function(e) {
            e.preventDefault();
            var $btn = $(this);
            var $select = $('#aavni_shiprocket_pickup_location');

            $btn.prop('disabled', true).html('<span class="sr-spin">⚙️</span> Fetching...');

            $.post(aavniShiprocketParams.ajax_url, {
                action: 'aavni_sr_fetch_pickups',
                nonce: aavniShiprocketParams.nonce
            }, function(res) {
                $btn.prop('disabled', false).html('🔄 Refresh List');
                if (res && res.success && res.data.locations) {
                    var curVal = $select.val();
                    $select.empty().append('<option value="">-- Select Configured Pickup Address --</option>');
                    res.data.locations.forEach(function(loc) {
                        var code = loc.pickup_location || loc.pickup_location_id || '';
                        var label = code + ' (' + (loc.city || '') + ' - ' + (loc.pin_code || '') + ')';
                        var isSel = (code === curVal) ? 'selected' : '';
                        $select.append('<option value="' + code + '" ' + isSel + '>' + label + '</option>');
                    });
                    alert('Successfully fetched ' + res.data.locations.length + ' pickup location(s) from Shiprocket!');
                } else {
                    alert('Could not fetch locations: ' + ((res && res.data && res.data.message) ? res.data.message : 'Unknown error'));
                }
            }).fail(function() {
                $btn.prop('disabled', false).html('🔄 Refresh List');
                alert('Network error while fetching pickup locations.');
            });
        });

        // Clear Diagnostic Logs
        $('#aavni_sr_btn_clear_logs').on('click', function(e) {
            e.preventDefault();
            if (!confirm('Are you sure you want to clear the Shiprocket diagnostic logs?')) return;
            var $btn = $(this);
            $.post(aavniShiprocketParams.ajax_url, {
                action: 'aavni_sr_clear_logs',
                nonce: aavniShiprocketParams.nonce
            }, function(res) {
                if (res && res.success) {
                    $('#aavni_sr_logs_box').html('<div style="color:#888;">No recent Shiprocket logs recorded.</div>');
                }
            });
        });
    }

    /**
     * 2. Order Breakdown Details Modal Integration
     */
    function initOrderModalHandlers() {
        // Intercept modal open to populate Shiprocket card
        $(document).on('click', '.aavni-trigger-order-details', function() {
            var rawOrder = $(this).attr('data-order');
            if (!rawOrder) return;

            try {
                currentOrderData = JSON.parse(rawOrder);
            } catch (e) {
                console.error('Invalid order payload JSON', e);
                return;
            }

            currentOrderId = currentOrderData.id;
            updateShiprocketPanel(currentOrderData);
        });

        // Check Serviceability Button
        $(document).on('click', '#sr_btn_check_serviceability', function(e) {
            e.preventDefault();
            if (!currentOrderId) return;

            var $btn = $(this);
            var $msg = $('#sr_serviceability_msg');
            var $select = $('#sr_courier_select');

            var weight = $('#sr_pkg_weight').val() || 0.45;
            var l = $('#sr_pkg_l').val() || 30;
            var b = $('#sr_pkg_b').val() || 25;
            var h = $('#sr_pkg_h').val() || 4;

            $btn.prop('disabled', true).html('<span class="sr-spin">⚙️</span> Checking Serviceability...');
            $msg.hide().removeClass('sr-alert-success sr-alert-error');

            $.post(aavniShiprocketParams.ajax_url, {
                action: 'aavni_sr_check_serviceability',
                nonce: aavniShiprocketParams.nonce,
                order_id: currentOrderId,
                weight: weight,
                length: l,
                breadth: b,
                height: h
            }, function(res) {
                $btn.prop('disabled', false).html('🔍 Check Courier Rates &amp; Serviceability');
                $msg.show();

                if (res && res.success && res.data.couriers) {
                    $select.empty().append('<option value="">Select Serviceable Courier (' + res.data.couriers.length + ' Available)...</option>');
                    res.data.couriers.forEach(function(c) {
                        var optLabel = c.name + ' [' + c.mode + '] — ₹' + c.rate + ' (ETD: ' + (c.etd || '2-4 days') + ')';
                        $select.append('<option value="' + c.id + '" data-name="' + c.name + '">' + optLabel + '</option>');
                    });
                    $select.show();
                    $msg.addClass('sr-alert-success').html('✓ Destination pincode (' + res.data.delivery_pincode + ') is serviceable by ' + res.data.couriers.length + ' courier partners.');
                } else {
                    $select.hide();
                    var err = (res && res.data && res.data.message) ? res.data.message : 'Pincode is currently not serviceable.';
                    $msg.addClass('sr-alert-error').html('⚠️ ' + err);
                }
            }).fail(function() {
                $btn.prop('disabled', false).html('🔍 Check Courier Rates &amp; Serviceability');
                $msg.show().addClass('sr-alert-error').html('Network error checking serviceability.');
            });
        });

        // Create Shipment Button
        $(document).on('click', '#sr_btn_create_shipment', function(e) {
            e.preventDefault();
            if (!currentOrderId) return;

            var courierId = $('#sr_courier_select').val();
            var weight = $('#sr_pkg_weight').val();
            var l = $('#sr_pkg_l').val();
            var b = $('#sr_pkg_b').val();
            var h = $('#sr_pkg_h').val();

            if (!confirm('Are you sure you want to create a real Shiprocket shipment for Order #' + (currentOrderData ? currentOrderData.number : currentOrderId) + '?')) {
                return;
            }

            var $btn = $(this);
            var $msg = $('#sr_panel_action_msg');

            $btn.prop('disabled', true).html('<span class="sr-spin">⚙️</span> Creating Shipment...');
            $msg.hide().removeClass('sr-alert-success sr-alert-error');

            $.post(aavniShiprocketParams.ajax_url, {
                action: 'aavni_sr_create_shipment',
                nonce: aavniShiprocketParams.nonce,
                order_id: currentOrderId,
                courier_id: courierId,
                weight: weight,
                length: l,
                breadth: b,
                height: h
            }, function(res) {
                $btn.prop('disabled', false).html('🚀 Create Shipment');
                $msg.show();

                if (res && res.success) {
                    $msg.addClass('sr-alert-success').html('✓ ' + res.data.message);
                    if (res.data.shipment) {
                        currentOrderData.shiprocket = res.data.shipment;
                        updateShiprocketPanel(currentOrderData);
                    }
                } else {
                    var err = (res && res.data && res.data.message) ? res.data.message : 'Failed to create shipment.';
                    $msg.addClass('sr-alert-error').html('❌ ' + err);
                }
            }).fail(function() {
                $btn.prop('disabled', false).html('🚀 Create Shipment');
                $msg.show().addClass('sr-alert-error').html('Server error while creating shipment.');
            });
        });

        // Assign AWB Button
        $(document).on('click', '#sr_btn_assign_awb', function(e) {
            e.preventDefault();
            if (!currentOrderId) return;

            var courierId = $('#sr_courier_select').val();
            var $btn = $(this);
            var $msg = $('#sr_panel_action_msg');

            $btn.prop('disabled', true).html('<span class="sr-spin">🏷️</span> Assigning AWB...');
            $msg.hide().removeClass('sr-alert-success sr-alert-error');

            $.post(aavniShiprocketParams.ajax_url, {
                action: 'aavni_sr_assign_awb',
                nonce: aavniShiprocketParams.nonce,
                order_id: currentOrderId,
                courier_id: courierId
            }, function(res) {
                $btn.prop('disabled', false).html('🏷️ Assign AWB');
                $msg.show();

                if (res && res.success) {
                    $msg.addClass('sr-alert-success').html('✓ ' + res.data.message);
                    if (res.data.awb) {
                        if (!currentOrderData.shiprocket) currentOrderData.shiprocket = {};
                        currentOrderData.shiprocket.awb = res.data.awb;
                        currentOrderData.shiprocket.courier = res.data.courier;
                        currentOrderData.shiprocket.status = 'AWB_ASSIGNED';
                        updateShiprocketPanel(currentOrderData);
                    }
                } else {
                    var err = (res && res.data && res.data.message) ? res.data.message : 'Failed to assign AWB.';
                    $msg.addClass('sr-alert-error').html('❌ ' + err);
                }
            }).fail(function() {
                $btn.prop('disabled', false).html('🏷️ Assign AWB');
                $msg.show().addClass('sr-alert-error').html('Server error assigning AWB.');
            });
        });

        // Book Pickup Button
        $(document).on('click', '#sr_btn_book_pickup', function(e) {
            e.preventDefault();
            if (!currentOrderId) return;

            var $btn = $(this);
            var $msg = $('#sr_panel_action_msg');

            $btn.prop('disabled', true).html('<span class="sr-spin">📦</span> Booking Pickup...');
            $msg.hide().removeClass('sr-alert-success sr-alert-error');

            $.post(aavniShiprocketParams.ajax_url, {
                action: 'aavni_sr_request_pickup',
                nonce: aavniShiprocketParams.nonce,
                order_id: currentOrderId
            }, function(res) {
                $btn.prop('disabled', false).html('📦 Book Warehouse Pickup');
                $msg.show();

                if (res && res.success) {
                    $msg.addClass('sr-alert-success').html('✓ ' + res.data.message);
                } else {
                    var err = (res && res.data && res.data.message) ? res.data.message : 'Pickup booking failed.';
                    $msg.addClass('sr-alert-error').html('❌ ' + err);
                }
            }).fail(function() {
                $btn.prop('disabled', false).html('📦 Book Warehouse Pickup');
                $msg.show().addClass('sr-alert-error').html('Server error requesting pickup.');
            });
        });

        // Sync Tracking Button
        $(document).on('click', '#sr_btn_sync_tracking', function(e) {
            e.preventDefault();
            if (!currentOrderId) return;

            var $btn = $(this);
            var $msg = $('#sr_panel_action_msg');

            $btn.prop('disabled', true).html('<span class="sr-spin">🔄</span> Refreshing...');
            $msg.hide().removeClass('sr-alert-success sr-alert-error');

            $.post(aavniShiprocketParams.ajax_url, {
                action: 'aavni_sr_sync_tracking',
                nonce: aavniShiprocketParams.nonce,
                order_id: currentOrderId
            }, function(res) {
                $btn.prop('disabled', false).html('🔄 Refresh Tracking');
                $msg.show();

                if (res && res.success) {
                    $msg.addClass('sr-alert-success').html('✓ ' + res.data.message);
                    if (res.data.status) {
                        $('#sr_panel_status_badge').text(res.data.status).show();
                    }
                } else {
                    var err = (res && res.data && res.data.message) ? res.data.message : 'Tracking refresh failed.';
                    $msg.addClass('sr-alert-error').html('⚠️ ' + err);
                }
            }).fail(function() {
                $btn.prop('disabled', false).html('🔄 Refresh Tracking');
                $msg.show().addClass('sr-alert-error').html('Server error syncing tracking.');
            });
        });

        // Cancel Shipment Button
        $(document).on('click', '#sr_btn_cancel_shipment', function(e) {
            e.preventDefault();
            if (!currentOrderId) return;

            if (!confirm('Are you sure you want to cancel this shipment in Shiprocket?')) return;

            var $btn = $(this);
            var $msg = $('#sr_panel_action_msg');

            $btn.prop('disabled', true).html('<span class="sr-spin">✕</span> Cancelling...');
            $msg.hide().removeClass('sr-alert-success sr-alert-error');

            $.post(aavniShiprocketParams.ajax_url, {
                action: 'aavni_sr_cancel_shipment',
                nonce: aavniShiprocketParams.nonce,
                order_id: currentOrderId
            }, function(res) {
                $btn.prop('disabled', false).html('✕ Cancel Shipment');
                $msg.show();

                if (res && res.success) {
                    $msg.addClass('sr-alert-success').html('✓ ' + res.data.message);
                    if (!currentOrderData.shiprocket) currentOrderData.shiprocket = {};
                    currentOrderData.shiprocket.status = 'CANCELED';
                    updateShiprocketPanel(currentOrderData);
                } else {
                    var err = (res && res.data && res.data.message) ? res.data.message : 'Failed to cancel shipment.';
                    $msg.addClass('sr-alert-error').html('❌ ' + err);
                }
            }).fail(function() {
                $btn.prop('disabled', false).html('✕ Cancel Shipment');
                $msg.show().addClass('sr-alert-error').html('Server error cancelling shipment.');
            });
        });
    }

    /**
     * Update the Shiprocket panel controls based on order data
     */
    function updateShiprocketPanel(data) {
        var sr = data.shiprocket || {};
        var $panel = $('#aavni_sr_order_panel');
        var $badge = $('#sr_panel_status_badge');
        var $identCard = $('#sr_panel_identifiers');
        var $createBtn = $('#sr_btn_create_shipment');
        var $awbBtn = $('#sr_btn_assign_awb');
        var $labelBtn = $('#sr_btn_print_label');
        var $pickupBtn = $('#sr_btn_book_pickup');
        var $manifestBtn = $('#sr_btn_print_manifest');
        var $syncBtn = $('#sr_btn_sync_tracking');
        var $cancelBtn = $('#sr_btn_cancel_shipment');

        // Package dimensions
        $('#sr_pkg_weight').val(sr.weight || 0.45);
        $('#sr_pkg_l').val(sr.length || 30);
        $('#sr_pkg_b').val(sr.breadth || 25);
        $('#sr_pkg_h').val(sr.height || 4);

        $('#sr_panel_action_msg').hide().empty();
        $('#sr_serviceability_msg').hide().empty();

        if (sr.shipment_id) {
            $badge.text(sr.status || 'CREATED').show();
            $identCard.show();
            $('#sr_info_shipment_id').text(sr.shipment_id);
            $('#sr_info_order_id').text(sr.order_id || '—');
            $('#sr_info_courier').text(sr.courier || 'Pending Assignment');
            $('#sr_info_awb').text(sr.awb || 'Pending');

            $createBtn.hide();
            $syncBtn.show();
            $cancelBtn.show();

            if (sr.awb) {
                $awbBtn.hide();
                $pickupBtn.show();
                if (sr.label_url) {
                    $labelBtn.attr('href', sr.label_url).show();
                } else {
                    $labelBtn.attr('href', aavniShiprocketParams.ajax_url + '?action=aavni_sr_get_label&order_id=' + data.id + '&nonce=' + aavniShiprocketParams.nonce).show();
                }

                if (sr.manifest_url) {
                    $manifestBtn.attr('href', sr.manifest_url).show();
                } else {
                    $manifestBtn.hide();
                }
            } else {
                $awbBtn.show();
                $labelBtn.hide();
                $pickupBtn.hide();
                $manifestBtn.hide();
            }
        } else {
            $badge.text('NOT CREATED').show();
            $identCard.hide();
            $createBtn.show();
            $awbBtn.hide();
            $labelBtn.hide();
            $pickupBtn.hide();
            $manifestBtn.hide();
            $syncBtn.hide();
            $cancelBtn.hide();
        }
    }

    /**
     * 3. Bulk Shipment Actions
     */
    function initBulkActions() {
        // Toggle select all
        $(document).on('change', '#aavni_select_all_orders', function() {
            var isChecked = $(this).is(':checked');
            $('.aavni-order-cb').prop('checked', isChecked);
            updateBulkBar();
        });

        // Individual checkbox change
        $(document).on('change', '.aavni-order-cb', function() {
            updateBulkBar();
        });

        function updateBulkBar() {
            var count = $('.aavni-order-cb:checked').length;
            var $bar = $('#aavni_bulk_shipping_bar');
            if (count > 0) {
                $('#aavni_bulk_selected_count').text(count);
                $bar.show();
            } else {
                $bar.hide();
                $('#aavni_select_all_orders').prop('checked', false);
            }
        }

        // Bulk Actions handler
        $(document).on('click', '.btn-bulk-action', function(e) {
            e.preventDefault();
            var actionType = $(this).data('action-type');
            var selectedIds = [];
            $('.aavni-order-cb:checked').each(function() {
                selectedIds.push($(this).val());
            });

            if (selectedIds.length === 0) {
                alert('Please select at least one order.');
                return;
            }

            var promptMsg = 'Run bulk action [' + actionType.replace('_', ' ').toUpperCase() + '] on ' + selectedIds.length + ' order(s)?';
            if (!confirm(promptMsg)) return;

            var $btn = $(this);
            var origText = $btn.html();
            $btn.prop('disabled', true).html('<span class="sr-spin">⚙️</span> Processing...');

            $.post(aavniShiprocketParams.ajax_url, {
                action: 'aavni_sr_bulk_action',
                nonce: aavniShiprocketParams.nonce,
                bulk_type: actionType,
                order_ids: selectedIds
            }, function(res) {
                $btn.prop('disabled', false).html(origText);
                if (res && res.success) {
                    var out = 'Bulk processing completed!\n\n';
                    if (res.data.results) {
                        res.data.results.forEach(function(r) {
                            out += 'Order #' + r.order_id + ': ' + (r.success ? '✓ ' : '❌ ') + r.message + '\n';
                        });
                    }
                    alert(out);
                    window.location.reload();
                } else {
                    alert('Bulk processing error: ' + ((res && res.data && res.data.message) ? res.data.message : 'Unknown error'));
                }
            }).fail(function() {
                $btn.prop('disabled', false).html(origText);
                alert('Server error executing bulk action.');
            });
        });
    }

})(jQuery);

