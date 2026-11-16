/**
 * AAVNI Luxury E-Commerce Admin Panel JavaScript
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        // 1. Modal Trigger Handlers
        $('[data-aavni-modal]').on('click', function(e) {
            e.preventDefault();
            var target = $(this).data('aavni-modal');
            $('#' + target).addClass('is-active');
            if (target === 'aavniQuickAddModal') {
                if (typeof renderGallerySlots === 'function') {
                    renderGallerySlots('qa_gallery_slots', qaGalleryItems, 'qa_gallery_image_ids', 'qa');
                }
                // Auto-suggest SKU if empty
                if (!$('#qa_sku').val()) {
                    triggerSkuAutoGenerate('#qa_title', '#qa_cat', '#qa_sku');
                }
            }
        });

        $('.aavni-modal-close, [data-aavni-dismiss="modal"]').on('click', function(e) {
            e.preventDefault();
            $(this).closest('.aavni-modal-overlay').removeClass('is-active');
        });

        $(document).on('keydown', function(e) {
            if (e.key === 'Escape') {
                $('.aavni-modal-overlay').removeClass('is-active');
            }
        });

        // Helper: Generate a Delhivery-style AWB (DL + 10 digits + IN)
        function aavniGenerateAWB() {
            var digits = '';
            for (var i = 0; i < 10; i++) {
                digits += Math.floor(Math.random() * 10).toString();
            }
            return 'DL' + digits + 'IN';
        }

        // 2. Quick Milestone Modal Population
        $('.aavni-trigger-milestone-modal').on('click', function(e) {
            e.preventDefault();
            var orderId = $(this).data('order-id');
            var orderNumber = $(this).data('order-number');
            var currentMilestone = $(this).data('current-milestone');
            var currentStatus = $(this).data('current-status');
            var courier = $(this).data('courier');
            var awb = $(this).data('awb');
            var trackingUrl = $(this).data('tracking-url');

            $('#aavni_modal_order_id').val(orderId);
            $('#aavni_modal_order_title').text('#' + orderNumber);
            $('#aavni_modal_milestone').val(currentMilestone);
            if (currentStatus) {
                $('#aavni_modal_order_status').val(currentStatus);
            }

            // Auto-assign default courier if not set
            if (!courier || courier === '' || courier === 'Not assigned') {
                courier = 'Delhivery Surface';
            }
            $('#aavni_modal_courier').val(courier);

            // Auto-generate AWB if not already assigned
            if (!awb || awb === '' || awb === 'Not assigned') {
                awb = aavniGenerateAWB();
            }
            $('#aavni_modal_awb').val(awb);

            // Auto-fill Delhivery tracking URL if not set
            if (!trackingUrl || trackingUrl === '') {
                trackingUrl = 'https://www.delhivery.com/track/package/' + awb;
            }
            $('#aavni_modal_tracking_url').val(trackingUrl);

            // Live-update tracking URL when AWB is changed manually
            $('#aavni_modal_awb').off('input.awb_autofill').on('input.awb_autofill', function() {
                var newAwb = $(this).val().trim();
                var curUrl = $('#aavni_modal_tracking_url').val();
                if (!curUrl || curUrl.indexOf('delhivery.com/track/package/') !== -1) {
                    $('#aavni_modal_tracking_url').val(newAwb ? 'https://www.delhivery.com/track/package/' + newAwb : '');
                }
            });

            $('#aavniMilestoneModal').addClass('is-active');
        });

        // Regenerate AWB button inside milestone modal
        $(document).on('click', '#aavni_regenerate_awb_btn', function(e) {
            e.preventDefault();
            var newAwb = aavniGenerateAWB();
            $('#aavni_modal_awb').val(newAwb);
            $('#aavni_modal_tracking_url').val('https://www.delhivery.com/track/package/' + newAwb);
        });

        // ---- PIPELINE MILESTONE AJAX ENGINE ----
        // Shared function: call AJAX to set milestone, then reload the pipeline row
        function aavniSetMilestone(orderId, milestone, $row) {
            var ajaxUrl = (typeof aavniAdminParams !== 'undefined') ? aavniAdminParams.ajax_url : '/wp-admin/admin-ajax.php';
            var nonce   = (typeof aavniAdminParams !== 'undefined') ? aavniAdminParams.milestone_nonce : '';

            // Show spinner in the milestone cell
            var $cell = $row.find('.aavni-pipeline-row').closest('td');
            var originalContent = $cell.html();
            $cell.html('<div style="text-align:center; padding:10px 0; color:#A85A48; font-size:12px;">⏳ Saving...</div>');

            $.post(ajaxUrl, {
                action:    'aavni_quick_milestone_advance',
                nonce:     nonce,
                order_id:  orderId,
                milestone: milestone
            }, function(res) {
                if (res && res.success) {
                    var d = res.data;
                    // Show brief success toast
                    var toast = $('<div style="position:fixed; bottom:30px; right:30px; z-index:99999; background:#1B1A18; color:#fff; padding:10px 18px; border-radius:8px; font-size:13px; font-weight:600; box-shadow:0 4px 16px rgba(0,0,0,0.3); border-left:4px solid #A85A48;">✅ ' + d.label + ' — Milestone Updated!</div>');
                    $('body').append(toast);
                    setTimeout(function() { toast.fadeOut(400, function() { toast.remove(); }); }, 3000);
                    // Reload whole page to reflect updated pipeline row
                    window.location.reload();
                } else {
                    $cell.html(originalContent);
                    alert('Error: ' + ((res && res.data && res.data.message) ? res.data.message : 'Could not update milestone.'));
                }
            }).fail(function(xhr) {
                $cell.html(originalContent);
                var msg = 'Server error. Please try again.';
                if (xhr && xhr.responseText && xhr.responseText !== '-1' && xhr.responseText !== '0') {
                    try {
                        var json = JSON.parse(xhr.responseText);
                        if (json && json.data && json.data.message) msg = json.data.message;
                    } catch(e) {}
                }
                alert(msg);
            });
        }

        // Click a dot in the pipeline = jump to that stage
        $(document).on('click', '.aavni-pipeline-step-btn', function(e) {
            e.preventDefault();
            var $btn      = $(this);
            var orderId   = $btn.data('order-id');
            var milestone = $btn.data('milestone');
            var $row      = $btn.closest('tr');
            var label     = $btn.attr('title') || milestone;

            // Confirm before going backwards
            if (!confirm('Set milestone to: ' + label.replace('Set to: ', '').replace('✓ Current: ', '') + '?')) return;
            aavniSetMilestone(orderId, milestone, $row);
        });

        // Click the ▶ advance button = move to next stage
        $(document).on('click', '.aavni-pipeline-advance-btn', function(e) {
            e.preventDefault();
            var $btn      = $(this);
            var orderId   = $btn.data('order-id');
            var milestone = $btn.data('milestone');
            var $row      = $btn.closest('tr');
            aavniSetMilestone(orderId, milestone, $row);
        });

        // 3. Full Order Details Modal Population
        $('.aavni-trigger-order-details').on('click', function(e) {
            e.preventDefault();
            var order = $(this).data('order');
            if (!order) return;

            $('#detail_order_title').text('#' + order.number);
            $('#detail_order_status_badge').attr('class', 'aavni-badge aavni-badge-' + order.status).text(order.status_label);
            $('#detail_order_milestone_badge').attr('class', 'aavni-badge aavni-badge-' + order.milestone).text(order.milestone_label);
            $('#detail_order_date').text(order.date);
            $('#detail_order_payment').text(order.payment_method);
            $('#detail_order_total').html(order.total);

            // Customer
            $('#detail_customer_name').text(order.customer_name);
            $('#detail_customer_email').text(order.email || '—');
            $('#detail_customer_phone').text(order.phone ? '📞 ' + order.phone : '');
            $('#detail_billing_address').html(order.billing_address);
            $('#detail_shipping_address').html(order.shipping_address);

            // Courier
            $('#detail_courier').text(order.courier);
            $('#detail_awb').text(order.awb);
            if (order.tracking_url) {
                $('#detail_tracking_link_wrap').html('<a href="' + order.tracking_url + '" target="_blank" style="color:var(--aavni-admin-terracotta); font-size:12px; font-weight:600;">Track Shipment ↗</a>');
            } else {
                $('#detail_tracking_link_wrap').empty();
            }

            // Item table
            var tbody = $('#detail_items_table_body');
            tbody.empty();
            if (order.items && order.items.length > 0) {
                order.items.forEach(function(item) {
                    var tr = $('<tr></tr>');
                    tr.append('<td><strong>' + item.name + '</strong></td>');
                    tr.append('<td><code>' + item.sku + '</code></td>');
                    tr.append('<td>' + item.qty + '</td>');
                    tr.append('<td>' + item.price + '</td>');
                    tr.append('<td style="text-align:right;">' + item.total + '</td>');
                    tbody.append(tr);
                });
            }

            // Financials
            $('#detail_subtotal').html(order.subtotal);
            $('#detail_shipping').html(order.shipping_total);
            $('#detail_tax').html(order.tax_total);
            $('#detail_grand_total').html(order.total);

            // Notes list
            var notesWrap = $('#detail_notes_list');
            notesWrap.empty();
            if (order.notes && order.notes.length > 0) {
                order.notes.forEach(function(n) {
                    var noteDiv = $('<div style="padding:8px 0; border-bottom:1px solid #EAE5DE; font-size:12px;"></div>');
                    var tag = n.is_customer ? '<span style="color:#2563EB; font-weight:700;">[Customer Note]</span> ' : '<span style="color:#666;">[Private Note]</span> ';
                    noteDiv.append('<div>' + tag + n.content + '</div>');
                    noteDiv.append('<div style="font-size:11px; color:#888; margin-top:2px;">' + n.date + '</div>');
                    notesWrap.append(noteDiv);
                });
            } else {
                notesWrap.html('<em style="color:#888; font-size:12px;">No order notes recorded.</em>');
            }

            // Note form order id & invoice link
            $('#detail_note_order_id').val(order.id);
            $('#detail_print_invoice_btn').attr('href', order.invoice_url);

            $('#aavniOrderDetailsModal').addClass('is-active');
        });

        // 4. Quick Return Review Modal Population & Safe Refund Toggle
        $('.aavni-trigger-return-modal').on('click', function(e) {
            e.preventDefault();
            var returnId = $(this).data('return-id');
            var orderId = $(this).data('order-id');
            var customer = $(this).data('customer');
            var product = $(this).data('product');
            var type = $(this).data('type');
            var reason = $(this).data('reason');
            var desc = $(this).data('description');
            var status = $(this).data('status');
            var notes = $(this).data('notes');
            var refundable = parseFloat($(this).data('refundable')) || 0.0;
            var photos = $(this).data('photos') || [];

            $('#aavni_return_modal_id').val(returnId);
            $('#aavni_return_modal_title').text('Return #' + returnId + ' (Order #' + orderId + ')');
            $('#aavni_return_modal_customer').text(customer);
            $('#aavni_return_modal_product').text(product);
            $('#aavni_return_modal_type').text(type.toUpperCase());
            $('#aavni_return_modal_reason').text(reason);
            $('#aavni_return_modal_desc').text(desc || 'No additional customer remarks.');
            $('#aavni_return_modal_status').val(status);
            $('#aavni_return_modal_notes').val(notes);

            // Refund controls reset
            $('#aavni_toggle_refund_check').prop('checked', false);
            $('#aavni_refund_fields_wrap').hide();
            $('#aavni_max_refundable_display').text(refundable.toFixed(2));
            $('#aavni_return_refund_amt').val(refundable.toFixed(2)).attr('max', refundable);

            // Populate photos
            var photoContainer = $('#aavni_return_modal_photos');
            photoContainer.empty();
            if (photos && photos.length > 0) {
                photos.forEach(function(url) {
                    photoContainer.append('<a href="' + url + '" target="_blank"><img src="' + url + '" class="aavni-photo-thumb" alt="Evidence" /></a>');
                });
            } else {
                photoContainer.html('<em style="color:#888; font-size:12px;">No photo attachments uploaded.</em>');
            }

            $('#aavniReturnModal').addClass('is-active');
        });

        $('#aavni_toggle_refund_check').on('change', function() {
            if ($(this).is(':checked')) {
                $('#aavni_refund_fields_wrap').slideDown(150);
            } else {
                $('#aavni_refund_fields_wrap').slideUp(150);
            }
        });

        // 5. In-Place Edit Product Modal Population
        $('.aavni-trigger-edit-product').on('click', function(e) {
            e.preventDefault();
            var $btn = $(this);
            var prodId = $btn.data('id');
            var name = $btn.data('name');
            var sku = $btn.data('sku');
            var regPrice = $btn.data('regular-price');
            var salePrice = $btn.data('sale-price');
            var stock = $btn.data('stock');
            var stockStatus = $btn.data('stock-status');
            var cat = $btn.data('cat');
            var tags = $btn.data('tags');
            var status = $btn.data('status');
            var imageId = $btn.data('image-id');
            var imageUrl = $btn.data('image-url');
            var galleryIds = $btn.data('gallery-ids');

            var shortDesc = $('#prod_short_desc_' + prodId).text() || '';
            var fullDesc = $('#prod_desc_' + prodId).text() || '';

            $('#edit_product_id').val(prodId);
            $('#edit_modal_product_title').text('#' + prodId + ' (' + name + ')');
            $('#edit_title').val(name);
            $('#edit_sku').val(sku);
            $('#edit_reg_price').val(regPrice);
            $('#edit_sale_price').val(salePrice);
            $('#edit_stock').val(stock);
            $('#edit_stock_status').val(stockStatus || 'instock');
            $('#edit_cat').val(cat || '0');
            $('#edit_tags').val(tags);
            $('#edit_short_desc').val(shortDesc);
            $('#edit_full_desc').val(fullDesc);
            $('#edit_status').val(status || 'publish');

            // Image
            $('#edit_featured_image_id').val(imageId || '');
            if (imageUrl) {
                $('#edit_image_preview').html('<img src="' + imageUrl + '" style="width:100%; height:100%; object-fit:cover;" />');
                $('#edit_remove_image_btn').show();
            } else {
                $('#edit_image_preview').html('<span style="font-size:26px;">📷</span><span style="font-size:11px; color:#888; margin-top:4px; font-weight:600;">Main Photo</span><span style="font-size:9.5px; color:#aaa;">(Click to browse)</span>');
                $('#edit_remove_image_btn').hide();
            }

            // Gallery (1 Main + 4 Gallery slots)
            editGalleryItems = [];
            var galleryUrlsRaw = $btn.data('gallery-urls');
            var galleryUrls = galleryUrlsRaw ? String(galleryUrlsRaw).split('|') : [];
            if (galleryIds) {
                var ids = String(galleryIds).split(',').filter(Boolean);
                for (var gi = 0; gi < ids.length; gi++) {
                    if (ids[gi]) {
                        editGalleryItems.push({ id: ids[gi], url: galleryUrls[gi] || '' });
                    }
                }
            }
            renderGallerySlots('edit_gallery_slots', editGalleryItems, 'edit_gallery_image_ids', 'edit');

            $('#aavniEditProductModal').addClass('is-active');
        });

        // 6. Customer Edit Modal Population
        $('.aavni-trigger-customer-edit').on('click', function(e) {
            e.preventDefault();
            var $btn = $(this);
            var id = $btn.data('id');
            var fn = $btn.data('first-name');
            var ln = $btn.data('last-name');
            var email = $btn.data('email');
            var phone = $btn.data('phone');

            $('#edit_cust_id').val(id);
            $('#edit_cust_first_name').val(fn);
            $('#edit_cust_last_name').val(ln);
            $('#edit_cust_email').val(email);
            $('#edit_cust_phone').val(phone);

            $('#edit_cust_b_addr1').val($btn.data('b-addr1'));
            $('#edit_cust_b_addr2').val($btn.data('b-addr2'));
            $('#edit_cust_b_city').val($btn.data('b-city'));
            $('#edit_cust_b_state').val($btn.data('b-state'));
            $('#edit_cust_b_postcode').val($btn.data('b-postcode'));

            $('#edit_cust_s_addr1').val($btn.data('s-addr1'));
            $('#edit_cust_s_addr2').val($btn.data('s-addr2'));
            $('#edit_cust_s_city').val($btn.data('s-city'));
            $('#edit_cust_s_state').val($btn.data('s-state'));
            $('#edit_cust_s_postcode').val($btn.data('s-postcode'));

            $('#aavniCustomerEditModal').addClass('is-active');
        });

        // 7. WordPress Media Uploader & Direct AJAX Image Upload for Products
        // State for 4-slot product galleries
        var qaGalleryItems = [];
        var editGalleryItems = [];

        function renderGallerySlots(containerId, items, hiddenInputId, prefix) {
            var $c = $('#' + containerId);
            if (!$c.length) return;
            $c.empty();

            // Compact array to max 4 valid items
            var validItems = items.filter(function(it) { return it && it.id; }).slice(0, 4);
            if (prefix === 'qa') {
                qaGalleryItems = validItems;
            } else {
                editGalleryItems = validItems;
            }

            // Sync hidden form input
            var ids = validItems.map(function(it) { return it.id; });
            $('#' + hiddenInputId).val(ids.join(','));

            for (var i = 0; i < 4; i++) {
                var slotNumber = i + 1;
                var item = validItems[i];

                if (item && item.url) {
                    var filledHtml = $(
                        '<div class="aavni-gallery-slot is-filled" style="position:relative; width:100%; height:110px; border-radius:6px; overflow:hidden; border:1px solid #D1C7BD; background:#fff; box-shadow:0 1px 3px rgba(0,0,0,0.06);">' +
                            '<img src="' + item.url + '" style="width:100%; height:100%; object-fit:cover; display:block;" />' +
                            '<button type="button" class="aavni-remove-slot-btn" data-slot="' + i + '" title="Remove Photo ' + slotNumber + '" style="position:absolute; top:3px; right:3px; width:22px; height:22px; border-radius:50%; background:rgba(220,38,38,0.92); color:#fff; border:none; font-size:13px; font-weight:bold; cursor:pointer; display:flex; align-items:center; justify-content:center; line-height:1; z-index:2; padding:0;">&times;</button>' +
                            '<span style="position:absolute; bottom:3px; left:3px; background:rgba(0,0,0,0.65); color:#fff; font-size:9.5px; font-weight:600; padding:1px 5px; border-radius:3px;">#' + slotNumber + '</span>' +
                        '</div>'
                    );
                    $c.append(filledHtml);
                } else {
                    var emptyHtml = $(
                        '<div class="aavni-gallery-slot is-empty" data-slot="' + i + '" style="width:100%; height:110px; border-radius:6px; border:2px dashed #D1C7BD; background:#FFF; display:flex; flex-direction:column; align-items:center; justify-content:center; cursor:pointer; text-align:center; padding:4px; transition:border-color 0.2s;" title="Click to upload Photo ' + slotNumber + '">' +
                            '<span style="font-size:20px; color:#9CA3AF; line-height:1;">+</span>' +
                            '<span style="font-size:10.5px; font-weight:600; color:#4B5563; margin-top:3px;">Photo ' + slotNumber + '</span>' +
                            '<span style="font-size:9px; color:#9CA3AF;">Upload</span>' +
                        '</div>'
                    );
                    $c.append(emptyHtml);
                }
            }

            // Click empty slot: pick file directly for that slot
            $c.find('.aavni-gallery-slot.is-empty').off('click').on('click', function(e) {
                e.preventDefault();
                var targetIdx = parseInt($(this).data('slot'), 10);
                var $slotInput = $('<input type="file" accept="image/*" style="display:none;">');
                $('body').append($slotInput);
                $slotInput.on('change', function() {
                    directUploadImage(this, $('#' + prefix + '_gallery_upload_status'), function(data) {
                        var targetList = (prefix === 'qa') ? qaGalleryItems : editGalleryItems;
                        targetList[targetIdx] = { id: data.id, url: data.thumb_url };
                        renderGallerySlots(containerId, targetList, hiddenInputId, prefix);
                    });
                    $slotInput.remove();
                });
                $slotInput.trigger('click');
            });

            // Click remove button: remove that slot and re-render
            $c.find('.aavni-remove-slot-btn').off('click').on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                var removeIdx = parseInt($(this).data('slot'), 10);
                var targetList = (prefix === 'qa') ? qaGalleryItems : editGalleryItems;
                targetList.splice(removeIdx, 1);
                renderGallerySlots(containerId, targetList, hiddenInputId, prefix);
            });
        }

        // Initialize empty 4 slots for Quick Add
        renderGallerySlots('qa_gallery_slots', qaGalleryItems, 'qa_gallery_image_ids', 'qa');

        function directUploadImage(fileInput, statusEl, onUploadSuccess) {
            var file = fileInput.files ? fileInput.files[0] : null;
            if (!file) return;

            statusEl.show().html('<span style="color:#D97706;">⏳ Uploading photo...</span>');

            var formData = new FormData();
            formData.append('action', 'aavni_admin_upload_image');
            formData.append('nonce', (typeof aavniAdminParams !== 'undefined') ? aavniAdminParams.stock_nonce : '');
            formData.append('image_file', file);

            $.ajax({
                url: (typeof aavniAdminParams !== 'undefined') ? aavniAdminParams.ajax_url : '/wordpress/Aavni/wp-admin/admin-ajax.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(res) {
                    if (res && res.success) {
                        statusEl.html('<span style="color:#047857;">✓ Uploaded successfully!</span>');
                        setTimeout(function() { statusEl.fadeOut(); }, 3000);
                        onUploadSuccess(res.data);
                    } else {
                        var msg = (res && res.data && res.data.message) ? res.data.message : 'Upload failed.';
                        statusEl.html('<span style="color:#DC2626;">⚠️ ' + msg + '</span>');
                    }
                },
                error: function() {
                    statusEl.html('<span style="color:#DC2626;">⚠️ Server error during upload. Please try again.</span>');
                }
            });
        }

        function directUploadGallery(fileInput, statusEl, onComplete) {
            var files = fileInput.files;
            if (!files || !files.length) return;

            var maxFiles = Math.min(files.length, 4);
            statusEl.show().html('<span style="color:#D97706;">⏳ Uploading ' + maxFiles + ' photo(s)...</span>');
            var uploadedItems = [];
            var completedCount = 0;

            Array.from(files).slice(0, 4).forEach(function(file) {
                var formData = new FormData();
                formData.append('action', 'aavni_admin_upload_image');
                formData.append('nonce', (typeof aavniAdminParams !== 'undefined') ? aavniAdminParams.stock_nonce : '');
                formData.append('image_file', file);

                $.ajax({
                    url: (typeof aavniAdminParams !== 'undefined') ? aavniAdminParams.ajax_url : '/wordpress/Aavni/wp-admin/admin-ajax.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    success: function(res) {
                        completedCount++;
                        if (res && res.success && res.data) {
                            uploadedItems.push({ id: res.data.id, url: res.data.thumb_url });
                        }
                        if (completedCount === maxFiles) {
                            statusEl.html('<span style="color:#047857;">✓ ' + uploadedItems.length + ' photo(s) uploaded!</span>');
                            setTimeout(function() { statusEl.fadeOut(); }, 3500);
                            onComplete(uploadedItems);
                        }
                    },
                    error: function() {
                        completedCount++;
                        if (completedCount === maxFiles) {
                            statusEl.html('<span style="color:#047857;">✓ ' + uploadedItems.length + ' photo(s) uploaded!</span>');
                            setTimeout(function() { statusEl.fadeOut(); }, 3500);
                            onComplete(uploadedItems);
                        }
                    }
                });
            });
        }

        // --- Quick Add Main Photo Handlers ---
        $('#qa_image_preview').on('click', function(e) {
            e.preventDefault();
            $('#qa_direct_image_file').trigger('click');
        });

        $('#qa_browse_image_btn').on('click', function(e) {
            e.preventDefault();
            $('#qa_direct_image_file').trigger('click');
        });

        $('#qa_direct_image_file, #qa_btn_direct_image_file').on('change', function() {
            var input = this;
            directUploadImage(input, $('#qa_image_upload_status'), function(data) {
                $('#qa_featured_image_id').val(data.id);
                $('#qa_image_preview').html('<img src="' + data.thumb_url + '" style="width:100%; height:100%; object-fit:cover;" />');
                $('#qa_remove_image_btn').show();
            });
        });

        $('#qa_select_image_btn').on('click', function(e) {
            e.preventDefault();
            if (typeof wp === 'undefined' || !wp.media) {
                alert('WordPress Media Uploader is not available.');
                return;
            }

            var frame = wp.media({
                title: 'Select Main Product Photo',
                button: { text: 'Use As Main Photo' },
                multiple: false,
                library: { type: 'image' }
            });

            frame.on('select', function() {
                var attachment = frame.state().get('selection').first().toJSON();
                $('#qa_featured_image_id').val(attachment.id);
                var thumb = attachment.sizes && attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url;
                $('#qa_image_preview').html('<img src="' + thumb + '" style="width:100%; height:100%; object-fit:cover;" />');
                $('#qa_remove_image_btn').show();
            });

            frame.open();
        });

        $('#qa_remove_image_btn').on('click', function(e) {
            e.preventDefault();
            $('#qa_featured_image_id').val('');
            $('#qa_image_preview').html('<span style="font-size:26px;">📷</span><span style="font-size:11px; color:#888; margin-top:4px; font-weight:600;">Main Photo</span><span style="font-size:9.5px; color:#aaa;">(Click to browse)</span>');
            $(this).hide();
        });

        // --- Quick Add 4 Gallery Photos Handlers ---
        $('#qa_direct_gallery_file').on('change', function() {
            var input = this;
            directUploadGallery(input, $('#qa_gallery_upload_status'), function(newItems) {
                newItems.forEach(function(it) {
                    if (qaGalleryItems.length < 4) {
                        qaGalleryItems.push(it);
                    }
                });
                renderGallerySlots('qa_gallery_slots', qaGalleryItems, 'qa_gallery_image_ids', 'qa');
            });
        });

        $('#qa_select_gallery_btn').on('click', function(e) {
            e.preventDefault();
            if (typeof wp === 'undefined' || !wp.media) return;

            var frame = wp.media({
                title: 'Select Gallery Photos (Up to 4)',
                button: { text: 'Add to Gallery' },
                multiple: true,
                library: { type: 'image' }
            });

            frame.on('select', function() {
                var selection = frame.state().get('selection');
                selection.each(function(att) {
                    if (qaGalleryItems.length < 4) {
                        var item = att.toJSON();
                        var thumb = item.sizes && item.sizes.thumbnail ? item.sizes.thumbnail.url : item.url;
                        qaGalleryItems.push({ id: item.id, url: thumb });
                    }
                });
                renderGallerySlots('qa_gallery_slots', qaGalleryItems, 'qa_gallery_image_ids', 'qa');
            });

            frame.open();
        });

        // --- Edit Product Main Photo Handlers ---
        $('#edit_image_preview').on('click', function(e) {
            e.preventDefault();
            $('#edit_direct_image_file').trigger('click');
        });

        $('#edit_browse_image_btn').on('click', function(e) {
            e.preventDefault();
            $('#edit_direct_image_file').trigger('click');
        });

        $('#edit_direct_image_file, #edit_btn_direct_image_file').on('change', function() {
            var input = this;
            directUploadImage(input, $('#edit_image_upload_status'), function(data) {
                $('#edit_featured_image_id').val(data.id);
                $('#edit_image_preview').html('<img src="' + data.thumb_url + '" style="width:100%; height:100%; object-fit:cover;" />');
                $('#edit_remove_image_btn').show();
            });
        });

        $('#edit_select_image_btn').on('click', function(e) {
            e.preventDefault();
            if (typeof wp === 'undefined' || !wp.media) return;

            var frame = wp.media({
                title: 'Change Main Product Photo',
                button: { text: 'Use As Main Photo' },
                multiple: false,
                library: { type: 'image' }
            });

            frame.on('select', function() {
                var attachment = frame.state().get('selection').first().toJSON();
                $('#edit_featured_image_id').val(attachment.id);
                var thumb = attachment.sizes && attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url;
                $('#edit_image_preview').html('<img src="' + thumb + '" style="width:100%; height:100%; object-fit:cover;" />');
                $('#edit_remove_image_btn').show();
            });

            frame.open();
        });

        $('#edit_remove_image_btn').on('click', function(e) {
            e.preventDefault();
            $('#edit_featured_image_id').val('');
            $('#edit_image_preview').html('<span style="font-size:26px;">📷</span><span style="font-size:11px; color:#888; margin-top:4px; font-weight:600;">Main Photo</span><span style="font-size:9.5px; color:#aaa;">(Click to browse)</span>');
            $(this).hide();
        });

        // --- Edit Product 4 Gallery Photos Handlers ---
        $('#edit_direct_gallery_file').on('change', function() {
            var input = this;
            directUploadGallery(input, $('#edit_gallery_upload_status'), function(newItems) {
                newItems.forEach(function(it) {
                    if (editGalleryItems.length < 4) {
                        editGalleryItems.push(it);
                    }
                });
                renderGallerySlots('edit_gallery_slots', editGalleryItems, 'edit_gallery_image_ids', 'edit');
            });
        });

        $('#edit_select_gallery_btn').on('click', function(e) {
            e.preventDefault();
            if (typeof wp === 'undefined' || !wp.media) return;

            var frame = wp.media({
                title: 'Manage Gallery Photos (Up to 4)',
                button: { text: 'Add to Gallery' },
                multiple: true,
                library: { type: 'image' }
            });

            frame.on('select', function() {
                var selection = frame.state().get('selection');
                selection.each(function(att) {
                    if (editGalleryItems.length < 4) {
                        var item = att.toJSON();
                        var thumb = item.sizes && item.sizes.thumbnail ? item.sizes.thumbnail.url : item.url;
                        editGalleryItems.push({ id: item.id, url: thumb });
                    }
                });
                renderGallerySlots('edit_gallery_slots', editGalleryItems, 'edit_gallery_image_ids', 'edit');
            });

            frame.open();
        });

        // 8. Quick Stock Adjust Inline
        $('.aavni-stock-input').on('change', function() {
            var $input = $(this);
            var productId = $input.data('product-id');
            var newStock = parseInt($input.val(), 10);
            var nonce = $input.data('nonce');
            var $statusSpan = $('#stock_status_' + productId);

            $statusSpan.html('<span style="color:#666; font-size:11px;">Saving...</span>');

            $.post(ajaxurl, {
                action: 'aavni_admin_quick_stock_update',
                product_id: productId,
                stock_qty: newStock,
                _wpnonce: nonce
            }, function(res) {
                if (res && res.success) {
                    $statusSpan.html('<span style="color:#10B981; font-weight:700; font-size:11px;">✓ Saved</span>');
                    setTimeout(function() {
                        $statusSpan.empty();
                    }, 2500);
                } else {
                    $statusSpan.html('<span style="color:#EF4444; font-size:11px;">Error</span>');
                }
            }).fail(function() {
                $statusSpan.html('<span style="color:#EF4444; font-size:11px;">Failed</span>');
            });
        });

        // 9. Canvas Sales Trend Sparkline Chart
        var canvas = document.getElementById('aavniSalesTrendCanvas');
        if (canvas && canvas.getContext) {
            var ctx = canvas.getContext('2d');
            var chartData = window.aavniSalesChartData || [];

            if (chartData.length > 0) {
                var width = canvas.width = canvas.parentElement.clientWidth || 600;
                var height = canvas.height = 200;
                var padding = { top: 20, right: 30, bottom: 40, left: 60 };

                var maxVal = Math.max.apply(Math, chartData.map(function(d) { return d.revenue; })) || 1000;
                maxVal = Math.ceil(maxVal * 1.15); // Add headroom

                var plotWidth = width - padding.left - padding.right;
                var plotHeight = height - padding.top - padding.bottom;
                var stepX = plotWidth / (chartData.length - 1 || 1);

                // Draw Grid Lines & Y Labels
                ctx.strokeStyle = '#EAE5DE';
                ctx.lineWidth = 1;
                ctx.fillStyle = '#9E988F';
                ctx.font = '11px Urbanist, sans-serif';

                var gridLines = 4;
                for (var g = 0; g <= gridLines; g++) {
                    var yVal = Math.round((maxVal / gridLines) * g);
                    var yPos = padding.top + plotHeight - (plotHeight * (yVal / maxVal));

                    ctx.beginPath();
                    ctx.moveTo(padding.left, yPos);
                    ctx.lineTo(width - padding.right, yPos);
                    ctx.stroke();

                    ctx.fillText('₹' + yVal, 10, yPos + 4);
                }

                // Plot Line & Area
                var points = [];
                chartData.forEach(function(d, i) {
                    var x = padding.left + (i * stepX);
                    var y = padding.top + plotHeight - (plotHeight * (d.revenue / maxVal));
                    points.push({ x: x, y: y, label: d.date, revenue: d.revenue, orders: d.orders });
                });

                // Fill gradient under curve
                var gradient = ctx.createLinearGradient(0, padding.top, 0, height - padding.bottom);
                gradient.addColorStop(0, 'rgba(168, 90, 72, 0.28)');
                gradient.addColorStop(1, 'rgba(168, 90, 72, 0.0)');

                ctx.beginPath();
                ctx.moveTo(points[0].x, points[0].y);
                for (var p = 1; p < points.length; p++) {
                    ctx.lineTo(points[p].x, points[p].y);
                }
                ctx.lineTo(points[points.length - 1].x, height - padding.bottom);
                ctx.lineTo(points[0].x, height - padding.bottom);
                ctx.closePath();
                ctx.fillStyle = gradient;
                ctx.fill();

                // Draw Path Line
                ctx.beginPath();
                ctx.moveTo(points[0].x, points[0].y);
                for (var p = 1; p < points.length; p++) {
                    ctx.lineTo(points[p].x, points[p].y);
                }
                ctx.strokeStyle = '#A85A48';
                ctx.lineWidth = 3;
                ctx.stroke();

                // Draw Points & X Labels
                points.forEach(function(pt, idx) {
                    ctx.beginPath();
                    ctx.arc(pt.x, pt.y, 4, 0, Math.PI * 2);
                    ctx.fillStyle = '#FFFFFF';
                    ctx.fill();
                    ctx.strokeStyle = '#A85A48';
                    ctx.lineWidth = 2;
                    ctx.stroke();

                    // X-axis label
                    if (points.length <= 10 || idx % Math.ceil(points.length / 7) === 0 || idx === points.length - 1) {
                        ctx.fillStyle = '#6B655C';
                        ctx.textAlign = 'center';
                        ctx.fillText(pt.label, pt.x, height - padding.bottom + 18);
                    }
                });
            }
        }

        // 11. Storefront & Customer Content Manager
        // A. Add New Slide Button
        $('#aavni_btn_add_slide').on('click', function(e) {
            e.preventDefault();
            $('#modal_slide_index').val('-1');
            $('#aavni_slide_modal_heading').text('Add New Hero Slide');
            $('#modal_slide_title').val('');
            $('#modal_slide_tagline').val('');
            $('#modal_slide_desc').val('');
            $('#modal_slide_image_url').val('');
            $('#modal_slide_img_preview').html('<span style="font-size:11px; color:#888;">No Image</span>');
            $('#modal_slide_btn1_text').val('Shop All Collections');
            $('#modal_slide_btn1_url').val('/shop/');
            $('#modal_slide_btn2_text').val('');
            $('#modal_slide_btn2_url').val('');
            $('#modal_slide_overlay_opacity').val('35');
            $('#modal_slide_active').prop('checked', true);
            $('#modal_slide_submit_btn').text('Add Slide');
            $('#aavniSlideModal').addClass('is-active');
        });

        // B. Edit Slide Button
        $(document).on('click', '.aavni-btn-edit-slide', function(e) {
            e.preventDefault();
            var slideIndex = $(this).data('slide-index');
            var slideData = $(this).data('slide');
            if (typeof slideData === 'string') {
                try { slideData = JSON.parse(slideData); } catch(err) { slideData = {}; }
            }
            if (!slideData) return;

            $('#modal_slide_index').val(slideIndex);
            $('#aavni_slide_modal_heading').text('Edit Slide #' + (parseInt(slideIndex, 10) + 1));
            $('#modal_slide_title').val(slideData.title || '');
            $('#modal_slide_tagline').val(slideData.tagline || '');
            $('#modal_slide_desc').val(slideData.desc || '');
            $('#modal_slide_image_url').val(slideData.image_url || '');

            if (slideData.image_url) {
                $('#modal_slide_img_preview').html('<img src="' + slideData.image_url + '" style="width:100%; height:100%; object-fit:cover;" />');
            } else {
                $('#modal_slide_img_preview').html('<span style="font-size:11px; color:#888;">No Image</span>');
            }

            $('#modal_slide_btn1_text').val(slideData.btn1_text || '');
            $('#modal_slide_btn1_url').val(slideData.btn1_url || '');
            $('#modal_slide_btn2_text').val(slideData.btn2_text || '');
            $('#modal_slide_btn2_url').val(slideData.btn2_url || '');
            $('#modal_slide_overlay_opacity').val(slideData.overlay_opacity || '35');
            $('#modal_slide_active').prop('checked', parseInt(slideData.active, 10) === 1);
            $('#modal_slide_submit_btn').text('Update Slide');
            $('#aavniSlideModal').addClass('is-active');
        });

        // C. Generic WordPress Media Uploader for Storefront & Theme
        $(document).on('click', '.aavni-media-upload-btn', function(e) {
            e.preventDefault();
            if (typeof wp === 'undefined' || !wp.media) {
                alert('WordPress Media Uploader is not available.');
                return;
            }

            var $btn = $(this);
            var targetInput = $btn.data('target-input');
            var targetPreview = $btn.data('target-preview');
            var frameTitle = $btn.data('frame-title') || 'Select Image';
            var frameBtn = $btn.data('frame-btn') || 'Use This Image';

            var frame = wp.media({
                title: frameTitle,
                button: { text: frameBtn },
                multiple: false,
                library: { type: 'image' }
            });

            frame.on('select', function() {
                var attachment = frame.state().get('selection').first().toJSON();
                var fullUrl = attachment.url;
                if (targetInput && $(targetInput).length) {
                    $(targetInput).val(fullUrl).trigger('input').trigger('change');
                }
                if (targetPreview && $(targetPreview).length) {
                    $(targetPreview).html('<img src="' + fullUrl + '" style="width:100%; height:100%; object-fit:cover;" />');
                }
            });

            frame.open();
        });

        // D. Announcement Bar Live Preview Updates
        $('#announcement_text1').on('input', function() {
            $('#prev_text1').text($(this).val() || 'Free Express Shipping Across India');
        });
        $('#announcement_text2').on('input', function() {
            $('#prev_text2').text($(this).val() || 'COD Available');
        });
        $('#announcement_text3').on('input', function() {
            $('#prev_text3').text($(this).val() || 'Easy 7-Day Exchange');
        });
        $('#announcement_bg_color_picker').on('input change', function() {
            var color = $(this).val();
            $('#announcement_bg_color').val(color);
            $('#live_announcement_preview').css('background-color', color);
        });
        $('#announcement_bg_color').on('input change', function() {
            var color = $(this).val();
            $('#announcement_bg_color_picker').val(color);
            $('#live_announcement_preview').css('background-color', color);
        });
        $('#announcement_text_color_picker').on('input change', function() {
            var color = $(this).val();
            $('#announcement_text_color').val(color);
            $('#live_announcement_preview').css('color', color);
        });
        $('#announcement_text_color').on('input change', function() {
            var color = $(this).val();
            $('#announcement_text_color_picker').val(color);
            $('#live_announcement_preview').css('color', color);
        });
        // --- Auto SKU Generation & Inline Add Category System ---
        function triggerSkuAutoGenerate(titleSelector, catSelector, skuTargetSelector) {
            var title = $(titleSelector).val() || '';
            var catId = $(catSelector).val() || 0;
            var ajaxUrl = (typeof aavniAdminParams !== 'undefined') ? aavniAdminParams.ajax_url : '/wordpress/Aavni/wp-admin/admin-ajax.php';
            var nonce = (typeof aavniAdminParams !== 'undefined') ? aavniAdminParams.stock_nonce : '';

            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: {
                    action: 'aavni_admin_generate_sku',
                    nonce: nonce,
                    title: title,
                    category_id: catId
                },
                dataType: 'json',
                success: function(res) {
                    if (res && res.success && res.data && res.data.sku) {
                        $(skuTargetSelector).val(res.data.sku);
                    }
                }
            });
        }

        // Quick Add Title or Category change -> auto suggest SKU if SKU is empty or previously generated
        $('#qa_title, #qa_cat').on('change', function() {
            var curSku = $('#qa_sku').val();
            if (!curSku || curSku.indexOf('AAV-') === 0) {
                triggerSkuAutoGenerate('#qa_title', '#qa_cat', '#qa_sku');
            }
        });

        $('#qa_btn_generate_sku').on('click', function(e) {
            e.preventDefault();
            triggerSkuAutoGenerate('#qa_title', '#qa_cat', '#qa_sku');
        });

        // Edit Product SKU Generator
        $('#edit_btn_generate_sku').on('click', function(e) {
            e.preventDefault();
            triggerSkuAutoGenerate('#edit_title', '#edit_cat', '#edit_sku');
        });

        // Toggle Inline Add Category box
        $(document).on('click', '.aavni-btn-toggle-add-cat', function(e) {
            e.preventDefault();
            var targetId = $(this).data('target');
            var $box = $('#' + targetId);
            $box.slideToggle(120, function() {
                if ($box.is(':visible')) {
                    $box.find('.aavni-new-cat-name-input').focus();
                }
            });
        });

        // Cancel Inline Add Category
        $(document).on('click', '.aavni-btn-cancel-new-cat', function(e) {
            e.preventDefault();
            var $box = $(this).closest('.aavni-inline-add-cat-wrap');
            $box.slideUp(120);
            $box.find('.aavni-new-cat-name-input').val('');
            $box.find('.aavni-inline-cat-msg').hide().empty();
        });

        // Save New Category via AJAX
        $(document).on('click', '.aavni-btn-save-new-cat', function(e) {
            e.preventDefault();
            var $btn = $(this);
            var $box = $btn.closest('.aavni-inline-add-cat-wrap');
            var $input = $box.find('.aavni-new-cat-name-input');
            var $msg = $box.find('.aavni-inline-cat-msg');
            var catName = $.trim($input.val());

            if (!catName) {
                $msg.css('color', '#DC2626').text('Please enter category name.').show();
                $input.focus();
                return;
            }

            $btn.prop('disabled', true).text('Adding...');
            $msg.css('color', '#D97706').text('Creating category...').show();

            var ajaxUrl = (typeof aavniAdminParams !== 'undefined') ? aavniAdminParams.ajax_url : '/wordpress/Aavni/wp-admin/admin-ajax.php';
            var nonce = (typeof aavniAdminParams !== 'undefined') ? aavniAdminParams.stock_nonce : '';

            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: {
                    action: 'aavni_admin_add_category',
                    nonce: nonce,
                    cat_name: catName
                },
                dataType: 'json',
                success: function(res) {
                    $btn.prop('disabled', false).text('Add');
                    if (res && res.success && res.data) {
                        var termId = res.data.term_id;
                        var termName = res.data.name;
                        var termSlug = res.data.slug || '';

                        // Add option to all category dropdowns in modal and filter bar if not existing
                        $('.aavni-cat-dropdown, select[name="cat"]').each(function() {
                            var $select = $(this);
                            if ($select.find('option[value="' + termId + '"]').length === 0) {
                                $select.append($('<option>', {
                                    value: termId,
                                    text: termName,
                                    'data-slug': termSlug
                                }));
                            }
                        });

                        // Select the newly added category in the current modal's dropdown
                        var $parentCatSelect = $box.siblings('.aavni-cat-dropdown');
                        if ($parentCatSelect.length) {
                            $parentCatSelect.val(termId).trigger('change');
                        }

                        $msg.css('color', '#16A34A').text('✓ Added & Selected!').show();
                        $input.val('');
                        setTimeout(function() {
                            $box.slideUp(150);
                            $msg.hide().empty();
                        }, 1000);
                    } else {
                        var err = (res && res.data && res.data.message) ? res.data.message : 'Failed to add category.';
                        $msg.css('color', '#DC2626').text(err).show();
                    }
                },
                error: function(xhr, status, error) {
                    $btn.prop('disabled', false).text('Add');
                    $msg.css('color', '#DC2626').text('Server error. Please try again.').show();
                }
            });
        });

        // Allow Enter key in new category input to trigger Save
        $(document).on('keydown', '.aavni-new-cat-name-input', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                $(this).closest('.aavni-inline-add-cat-wrap').find('.aavni-btn-save-new-cat').trigger('click');
            }
        });
    });
})(jQuery);
