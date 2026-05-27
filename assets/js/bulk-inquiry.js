/**
 * B2B Product Showcase — Bulk Inquiry Frontend JS
 *
 * Handles specs table interaction, cart management via localStorage,
 * and bulk inquiry form submission.
 */
(function($) {
    'use strict';

    var CART_KEY = 'naib_inquiry_cart';

    /**
     * Toast notification helper — replaces browser alert().
     */
    function showToast(message, type) {
        type = type || 'info';
        var $toast = $('#naib-bulk-toast');
        if (!$toast.length) {
            $toast = $('<div id="naib-bulk-toast" style="position:fixed; bottom:24px; left:50%; transform:translateX(-50%); z-index:99999; padding:10px 24px; border-radius:var(--naibabiji-b2b-border-radius); font-size:14px; color:#fff; pointer-events:none; transition:opacity 0.3s; opacity:0;"></div>');
            $('body').append($toast);
        }
        var bg = type === 'error' ? '#d63638' : '#2e7d32';
        $toast.css({ background: bg, opacity: 0 }).text(message);
        $toast.css({ opacity: 1 });
        clearTimeout($toast.data('timer'));
        $toast.data('timer', setTimeout(function() { $toast.css({ opacity: 0 }); }, 2500));
    }

    function getCart() {
        try {
            var raw = localStorage.getItem(CART_KEY);
            var cart = JSON.parse(raw);
            return (cart && cart.items && cart.items.length > 0) ? cart : { items: [] };
        } catch(e) {
            return { items: [] };
        }
    }

    function saveCart(cart) {
        cart.updated_at = new Date().toISOString();
        localStorage.setItem(CART_KEY, JSON.stringify(cart));
    }

    /**
     * Add specs to cart from the current product page.
     */
    function addSpecsToCart(specs) {
        if (!specs || specs.length === 0) return 0;

        var cart = getCart();
        var productId = parseInt($('#naib-ai-product-context').data('post-id')) || 0;
        var productName = $('.naibabiji-b2b-product-header .naibabiji-b2b-product-title').text().trim();
        var productUrl = window.location.href;
        var productImage = $('.naibabiji-b2b-featured-image img').attr('src') || '';

        // Find existing product in cart
        var existingIndex = -1;
        for (var i = 0; i < cart.items.length; i++) {
            if (cart.items[i].product_id === productId) {
                existingIndex = i;
                break;
            }
        }

        if (existingIndex >= 0) {
            // Merge specs
            var existingSpecs = cart.items[existingIndex].specs || [];
            specs.forEach(function(newSpec) {
                var found = false;
                for (var j = 0; j < existingSpecs.length; j++) {
                    if (existingSpecs[j].code === newSpec.code) {
                        existingSpecs[j].quantity += newSpec.quantity;
                        found = true;
                        break;
                    }
                }
                if (!found) {
                    existingSpecs.push(newSpec);
                }
            });
            cart.items[existingIndex].specs = existingSpecs;
        } else {
            cart.items.push({
                product_id: productId,
                product_name: productName,
                product_url: productUrl,
                product_image: productImage,
                specs: specs
            });
        }

        saveCart(cart);
        return specs.length;
    }

    /**
     * Update selected count display.
     */
    function updateSelectedCount() {
        var count = 0;
        $('.naib-spec-qty').each(function() {
            var qty = parseInt($(this).val()) || 0;
            if (qty > 0) count++;
        });
        $('.naib-specs-selected-count').text(
            naib_bulk_inquiry.i18n.selected_count.replace('{count}', count)
        );
    }

    /**
     * Open the bulk inquiry form modal
     */
    window.naib_bulk_open_form = function(cart) {
        console.log('[BulkInquiry:OpenForm] Called with cart items:', cart && cart.items ? cart.items.length : 0);
        var $modal = $('#naib-bulk-inquiry-form-modal');
        console.log('[BulkInquiry:OpenForm] Modal element found:', $modal.length > 0);
        if (!$modal.length) {
            console.warn('[BulkInquiry:OpenForm] Modal #naib-bulk-inquiry-form-modal not in DOM');
            return;
        }

        // Populate product summary
        var listHtml = '';
        cart.items.forEach(function(item) {
            var specCount = item.specs ? item.specs.length : 0;
            listHtml += '<p style="margin:2px 0;">&bull; ' + escapeHtml(item.product_name) + ' (' + specCount + ' specs)</p>';
        });
        console.log('[BulkInquiry:OpenForm] Product list HTML:', listHtml);
        $('#naib-bulk-form-product-list').html(listHtml);

        // Store cart data
        var cartJson = JSON.stringify(cart);
        console.log('[BulkInquiry:OpenForm] Cart JSON length:', cartJson.length);
        $('#naib-bulk-form-cart-data').val(cartJson);

        // Reset form
        var $form = $('#naib-bulk-inquiry-form');
        console.log('[BulkInquiry:OpenForm] Form element found:', $form.length > 0);
        if ($form.length && $form[0]) {
            $form[0].reset();
        }
        $('#naib-bulk-inquiry-form').show();
        $('#naib-bulk-inquiry-success').hide();
        $('.naib-form-status').hide();

        // Show modal — apply .active on overlay/panel (required by frontend.css)
        $modal.show();
        $modal.find('.naib-overlay').addClass('active');
        $modal.find('.naib-panel').addClass('active');
        $('body').css('overflow', 'hidden');
    };

    function escapeHtml(str) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

    // Initialize on DOM ready
    $(document).ready(function() {
        console.log('[BulkInquiry:Init] DOM ready, bulk-inquiry.js loaded');
        var hasBulkInquiryCtx = typeof naib_bulk_inquiry !== 'undefined';
        console.log('[BulkInquiry:Init] naib_bulk_inquiry defined:', hasBulkInquiryCtx);
        console.log('[BulkInquiry:Init] specs section exists:', $('.naib-bulk-specs-section').length > 0);
        console.log('[BulkInquiry:Init] modal in DOM:', $('#naib-bulk-inquiry-form-modal').length > 0);

        // ── Specs-table handlers (only when specs table is present) ──
        var $specsSection = $('.naib-bulk-specs-section');
        if ($specsSection.length && hasBulkInquiryCtx) {
            // Single spec add button
            $(document).on('click', '.naib-spec-add-btn', function() {
                var $row = $(this).closest('tr');
                var code = $(this).data('code');
                var description = $(this).data('description');
                var qty = parseInt($row.find('.naib-spec-qty').val()) || 0;

                if (qty <= 0) {
                    showToast(naib_bulk_inquiry.i18n.quantity_error, 'error');
                    return;
                }

                var count = addSpecsToCart([{ code: code, description: description, quantity: qty }]);
                showToast(naib_bulk_inquiry.i18n.added_success.replace('{count}', count), 'success');
                document.dispatchEvent(new CustomEvent('naib_cart_updated'));
            });

            // Quantity change tracking
            $(document).on('change input', '.naib-spec-qty', function() {
                updateSelectedCount();
            });

            // Bulk add all specs with qty > 0
            $('.naib-bulk-add-all-btn').on('click', function() {
                var specs = [];
                $('.naib-spec-row').each(function() {
                    var qty = parseInt($(this).find('.naib-spec-qty').val()) || 0;
                    if (qty > 0) {
                        var code = $(this).find('.naib-spec-qty').data('code');
                        var desc = $(this).find('.naib-spec-qty').data('description');
                        specs.push({ code: code, description: desc, quantity: qty });
                    }
                });

                if (specs.length === 0) {
                    showToast(naib_bulk_inquiry.i18n.quantity_error, 'error');
                    return;
                }

                var count = addSpecsToCart(specs);
                showToast(naib_bulk_inquiry.i18n.added_success.replace('{count}', count), 'success');
                document.dispatchEvent(new CustomEvent('naib_cart_updated'));
            });

            // Direct submit — add current specs and open form
            $('.naib-bulk-submit-direct-btn').on('click', function() {
                var specs = [];
                $('.naib-spec-row').each(function() {
                    var qty = parseInt($(this).find('.naib-spec-qty').val()) || 0;
                    if (qty > 0) {
                        var code = $(this).find('.naib-spec-qty').data('code');
                        var desc = $(this).find('.naib-spec-qty').data('description');
                        specs.push({ code: code, description: desc, quantity: qty });
                    }
                });

                if (specs.length > 0) {
                    addSpecsToCart(specs);
                    document.dispatchEvent(new CustomEvent('naib_cart_updated'));
                }

                var cart = getCart();
                if (!cart.items || cart.items.length === 0) {
                    showToast(naib_bulk_inquiry.i18n.cart_empty, 'error');
                    return;
                }

                naib_bulk_open_form(cart);
            });

            // Initial count
            updateSelectedCount();
        }

        // ── Form submission — AJAX handler (always registered; form modal is on all pages) ──
        $(document).on('submit', '#naib-bulk-inquiry-form', function(e) {
            console.log('[BulkInquiry:Submit] Form submit intercepted');
            e.preventDefault();

            if (!hasBulkInquiryCtx) {
                // Graceful fallback: if the JS global isn't available, submit via page reload
                var missing = [];
                if (!this.action) missing.push('action URL');
                if (missing.length) {
                    // Let the browser do a native POST — set action to admin-ajax as last resort
                    this.action = (typeof naibabiji_b2b_product_showcase !== 'undefined' && naibabiji_b2b_product_showcase.ajax_url) || window.location.href;
                    this.method = 'POST';
                    this.submit();
                    return;
                }
                return;
            }

            var $form = $(this);
            var $submitBtn = $form.find('button[type="submit"]');
            var $status = $form.find('.naib-form-status');
            var $success = $('#naib-bulk-inquiry-success');

            var email = $form.find('input[name="email"]').val();
            if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                $status.text('Please enter a valid email address.')
                    .css({ background: '#fce4e4', color: '#b71c1c' }).show();
                return;
            }

            $submitBtn.prop('disabled', true);

            $.ajax({
                url: naib_bulk_inquiry.ajax_url,
                type: 'POST',
                data: {
                    action: 'naib_submit_bulk_inquiry',
                    nonce: naib_bulk_inquiry.nonce,
                    name: $form.find('input[name="name"]').val(),
                    email: $form.find('input[name="email"]').val(),
                    whatsapp: $form.find('input[name="whatsapp"]').val(),
                    job_title: $form.find('input[name="job_title"]').val(),
                    company: $form.find('input[name="company"]').val(),
                    country: $form.find('input[name="country"]').val(),
                    message: $form.find('textarea[name="message"]').val(),
                    cart_data: $form.find('input[name="cart_data"]').val(),
                    naib_hp_field: $form.find('input[name="naib_hp_field"]').val(),
                    page_title: document.title
                },
                success: function(response) {
                    if (response.success) {
                        if (response.data && response.data.redirect_url) {
                            window.location.href = response.data.redirect_url;
                            return;
                        }
                        $form.hide();
                        $success.show();
                        $('#naib-bulk-inquiry-id').text(
                            'Inquiry #' + (response.data && response.data.id ? response.data.id : '')
                        );
                        localStorage.removeItem(CART_KEY);
                        document.dispatchEvent(new CustomEvent('naib_cart_updated'));
                    } else {
                        $status.text(response.data || (hasBulkInquiryCtx ? naib_bulk_inquiry.i18n.submit_failed : 'Submission failed.'))
                            .css({ background: '#fce4e4', color: '#b71c1c' }).show();
                    }
                },
                error: function() {
                    $status.text(hasBulkInquiryCtx ? naib_bulk_inquiry.i18n.network_error : 'Network error. Please try again.')
                        .css({ background: '#fce4e4', color: '#b71c1c' }).show();
                },
                complete: function() {
                    $submitBtn.prop('disabled', false);
                }
            });
        });

        // ── Close form modal (always registered) ──
        $(document).on('click', '.naib-bulk-form-close, #naib-bulk-inquiry-form-modal .naib-overlay', function() {
            var $modal = $('#naib-bulk-inquiry-form-modal');
            $modal.find('.naib-overlay').removeClass('active');
            $modal.find('.naib-panel').removeClass('active');
            $modal.hide();
            $('body').css('overflow', '');
        });

        // ── Auto-open form on page load (navigated from cart sidebar) ──
        function checkHashAndOpenForm() {
            if (window.location.hash === '#bulk-inquiry') {
                var cart = getCart();
                if (cart && cart.items && cart.items.length > 0) {
                    setTimeout(function() { naib_bulk_open_form(cart); }, 300);
                }
                // Clean up hash
                history.replaceState(null, '', window.location.pathname + window.location.search);
            }
        }
        checkHashAndOpenForm();
        // Also listen for hash changes (when already on product page)
        window.addEventListener('hashchange', checkHashAndOpenForm);
        console.log('[BulkInquiry:Init] All handlers registered. naib_bulk_open_form is', typeof window.naib_bulk_open_form);
    });

})(jQuery);
