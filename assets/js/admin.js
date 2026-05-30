/**
 * B2B Product Showcase - Admin JavaScript
 */

(function($) {
    'use strict';
    
    var adminData = window.naibabiji_b2b_product_showcase_admin || {};
    var adminStrings = adminData.strings || {};

    /**
     * Product Gallery Management
     */
    function initProductGallery() {
        var $addButton = $('#naibabiji-b2b-add-gallery-images');
        var $galleryContainer = $('#naibabiji-b2b-gallery-images');
        
        if (!$addButton.length) return;
        
        // Add image button click event
        $addButton.on('click', function(e) {
            e.preventDefault();
            
            // Create media library instance
            var mediaUploader = wp.media({
                title: 'Select Product Images',
                button: {
                    text: 'Add to Gallery'
                },
                multiple: true,
                library: {
                    type: 'image'
                }
            });
            
            // Callback after selecting images
            mediaUploader.on('select', function() {
                var attachments = mediaUploader.state().get('selection').toJSON();
                
                attachments.forEach(function(attachment) {
                    addGalleryImage(attachment);
                });
            });
            
            mediaUploader.open();
        });
        
        // Add image to gallery display
        function addGalleryImage(attachment) {
            var thumbnailUrl = attachment.sizes && attachment.sizes.thumbnail ? 
                              attachment.sizes.thumbnail.url : attachment.url;
            
            var imageHtml = `
                <div class="naibabiji-b2b-gallery-item" data-id="${attachment.id}">
                    <img src="${thumbnailUrl}" alt="${attachment.alt || ''}">
                    <button type="button" class="naibabiji-b2b-remove-image" title="Remove Image">×</button>
                    <input type="hidden" name="naibabiji_b2b_product_gallery[]" value="${attachment.id}">
                </div>
            `;
            
            $galleryContainer.append(imageHtml);
        }
        
        // Remove image event
        $(document).on('click', '.naibabiji-b2b-remove-image', function(e) {
            e.preventDefault();
            $(this).closest('.naibabiji-b2b-gallery-item').remove();
        });
        
        // Initialize drag and drop sorting
        if ($.fn.sortable) {
            $galleryContainer.sortable({
                items: '.naibabiji-b2b-gallery-item',
                placeholder: 'naibabiji-b2b-gallery-placeholder',
                tolerance: 'pointer'
            });
        }
    }
    
    /**
     * Settings Page Functions
     */
    function initSettingsPage() {
        // Color picker
        if ($.fn.wpColorPicker) {
            $('.color-picker').wpColorPicker();
        }
        
        // Image size preview
        $('#thumbnail_width, #thumbnail_height').on('input', function() {
            var width = $('#thumbnail_width').val() || 300;
            var height = $('#thumbnail_height').val() || 300;
            
            $('.thumbnail-preview').css({
                'width': Math.min(width / 2, 150) + 'px',
                'height': Math.min(height / 2, 150) + 'px'
            });
        });
        
        // Settings reset confirmation
        $('.reset-settings').on('click', function(e) {
            if (!confirm('Are you sure you want to reset all settings to default values? This action cannot be undone.')) {
                e.preventDefault();
            }
        });
        
        // Real-time preview of inquiry button text
        $('#inquiry_button_text').on('input', function() {
            var text = $(this).val() || adminStrings.default_inquiry_text || 'Get Quote';
            $('.inquiry-button-preview').text(text);
        });
    }
    
    /**
     * Product List Page Enhancements
     */
    function initProductListPage() {
        // Quick edit functionality
        $('.editinline').on('click', function() {
            var postId = $(this).closest('tr').attr('id').replace('post-', '');
            
            // Delayed execution, waiting for WordPress quick edit interface to load
            setTimeout(function() {
                var $quickEdit = $('#edit-' + postId);
                if ($quickEdit.length) {
                    // Add custom fields to quick edit
                    addQuickEditFields($quickEdit, postId);
                }
            }, 100);
        });
        
        function addQuickEditFields($quickEdit, postId) {
            // Custom quick edit fields can be added here
            // For example, inquiry button settings, etc.
        }
        
        // Bulk action enhancements
        $('#doaction, #doaction2').on('click', function(e) {
            var action = $(this).prev('select').val();
            
            if (action === 'trash' || action === 'delete') {
                var checkedPosts = $('input[name="post[]"]:checked').length;
                if (checkedPosts > 0) {
                    if (!confirm(`Are you sure you want to delete ${checkedPosts} selected products?`)) {
                        e.preventDefault();
                    }
                }
            }
        });
    }
    
    /**
     * General Functions
     */
    function initCommonFeatures() {
        // Tooltips
        if ($.fn.tooltip) {
            $('[data-tooltip]').tooltip({
                content: function() {
                    return $(this).data('tooltip');
                }
            });
        }
        
        // Confirmation dialog
        $('.confirm-action').on('click', function(e) {
            var message = $(this).data('confirm') || 'Are you sure you want to perform this action?';
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
        
        // Copy to clipboard functionality
        $('.copy-to-clipboard').on('click', function(e) {
            e.preventDefault();
            
            var target = $(this).data('target');
            var text = target ? $(target).val() : ($(this).data('copy') || $(this).prev('input').val());
            
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text).then(function() {
                    showNotice('Copied to clipboard', 'success');
                });
            } else {
                // Fallback solution
                var $temp = $('<textarea>');
                $('body').append($temp);
                $temp.val(text).select();
                document.execCommand('copy');
                $temp.remove();
                showNotice('Copied to clipboard', 'success');
            }
        });
    }
    
    /**
     * Shortcode Generator Logic
     */
    var ShortcodeGenerator = {
        state: {
            limit: 8,
            columns: 3,
            category: '',
            show_excerpt: true,
            show_category: true,
            show_view_details: true,
            show_inquiry: true
        },

        init: function() {
            this.$container = $('.naibabiji-b2b-generator-wrap');
            if (!this.$container.length) return;

            this.$result = $('#naibabiji-b2b-generated-shortcode');
            this.$inputs = this.$container.find('input, select');

            this.bindEvents();
            this.syncState();
            this.render();
        },

        bindEvents: function() {
            var self = this;
            this.$inputs.on('change input', function() {
                self.syncState();
                self.render();
            });
        },

        syncState: function() {
            this.state = {
                limit: $('#gen_limit').val(),
                columns: $('#gen_columns').val(),
                category: $('#gen_category').val(),
                show_excerpt: $('#gen_show_excerpt').is(':checked'),
                show_category: $('#gen_show_category').is(':checked'),
                show_view_details: $('#gen_show_view_details').is(':checked'),
                show_inquiry: $('#gen_show_inquiry').is(':checked')
            };
        },

        render: function() {
            var shortcode = '[naibabiji_b2b_products';
            var params = [];

            if (this.state.limit && this.state.limit != 8) params.push(`limit="${this.state.limit}"`);
            if (this.state.columns && this.state.columns != 3) params.push(`columns="${this.state.columns}"`);
            if (this.state.category) params.push(`category="${this.state.category}"`);
            if (!this.state.show_excerpt) params.push('show_excerpt="false"');
            if (!this.state.show_category) params.push('show_category="false"');
            if (!this.state.show_view_details) params.push('show_view_details="false"');
            if (!this.state.show_inquiry) params.push('show_inquiry="false"');

            if (params.length > 0) shortcode += ' ' + params.join(' ');
            shortcode += ']';

            this.$result.val(shortcode);
        }
    };

    /**
     * Display Notification Message
     */
    function showNotice(message, type) {
        type = type || 'info';
        
        var $notice = $(`
            <div class="notice notice-${type} is-dismissible">
                <p>${message}</p>
                <button type="button" class="notice-dismiss">
                    <span class="screen-reader-text">Dismiss this notice.</span>
                </button>
            </div>
        `);
        
        $('.wrap h1').first().after($notice);
        
        // Auto disappear
        setTimeout(function() {
            $notice.fadeOut(function() {
                $(this).remove();
            });
        }, 3000);
        
        // Manual close
        $notice.find('.notice-dismiss').on('click', function() {
            $notice.fadeOut(function() {
                $(this).remove();
            });
        });
    }

    /**
     * Form Validation
     */
    function initFormValidation() {
        // Settings page form validation
        var $settingsForm = $('#b2b-settings-form');
        if (!$settingsForm.length) return;

        $settingsForm.on('submit', function(e) {
            var isValid = true;
            var errors = [];
            
            // Validate thumbnail dimensions
            var thumbWidth = parseInt($('#thumbnail_width').val());
            var thumbHeight = parseInt($('#thumbnail_height').val());
            
            if (thumbWidth < 100 || thumbWidth > 1000) {
                errors.push('Thumbnail width must be between 100-1000 pixels');
                isValid = false;
            }
            
            if (thumbHeight < 100 || thumbHeight > 1000) {
                errors.push('Thumbnail height must be between 100-1000 pixels');
                isValid = false;
            }
            
            // Validate products per page
            var postsPerPage = parseInt($('#posts_per_page').val());
            if (postsPerPage < 1 || postsPerPage > 100) {
                errors.push('Products per page must be between 1-100');
                isValid = false;
            }
            
            if (!isValid) {
                e.preventDefault();
                showNotice('Form validation failed:<br>' + errors.join('<br>'), 'error');
            }
        });
    }
    
    /**
     * Settings Tab Navigation
     */
    function initSettingsTabs() {
        $('.naib-tabs .naib-tab').on('click', function(e) {
            e.preventDefault();
            var target = $(this).attr('href').substring(1);

            $('.naib-tabs .naib-tab').removeClass('active');
            $(this).addClass('active');

            $('.naib-settings-tab').removeClass('active');
            $('#tab-' + target).addClass('active');
        });

        if (window.location.hash === '#ai') {
            $('.naib-tabs .naib-tab[href="#ai"]').trigger('click');
        }
    }

    /**
     * Inquiry Mode Toggle (Settings → Inquiry tab)
     */
    function initInquiryModeToggle() {
        var $mode = $('#naibabiji_b2b_product_inquiry_mode');
        if (!$mode.length) return;

        function toggleFields(mode) {
            if (mode === 'form') {
                $('.naib-b2b-form-only').closest('tr').fadeIn();
                $('.naib-b2b-external-only').closest('tr').hide();
            } else {
                $('.naib-b2b-form-only').closest('tr').hide();
                $('.naib-b2b-external-only').closest('tr').fadeIn();
            }
        }

        toggleFields($mode.val());
        $mode.on('change', function() {
            toggleFields($(this).val());
        });
    }

    /**
     * Redirect URL Toggle (Settings → Inquiry tab)
     */
    function initRedirectUrlToggle() {
        var $redirect = $('#naibabiji_b2b_product_inquiry_redirect_enabled');
        if (!$redirect.length) return;

        function toggleRedirectUrl() {
            if ($redirect.is(':checked')) {
                $('.naib-b2b-redirect-url').closest('tr').fadeIn();
            } else {
                $('.naib-b2b-redirect-url').closest('tr').hide();
            }
        }

        toggleRedirectUrl();
        $redirect.on('change', toggleRedirectUrl);
        $('#naibabiji_b2b_product_inquiry_mode').on('change', function() {
            if ($(this).val() === 'form') {
                toggleRedirectUrl();
            }
        });
    }

    /**
     * AI License Key Verification
     */
    function initAiLicenseVerify() {
        var $keyInput = $('#naib_ai_license_key');
        var $verifyBtn = $('#naib_ai_verify_btn');
        var $unbindBtn = $('#naib_ai_unbind_btn');
        var $status = $('#naib_ai_verify_status');
        var $usageContainer = $('#naib_ai_usage_container');
        var $usageText = $('#naib_ai_usage_text');
        var $usageBar = $('#naib_ai_usage_bar');
        var $expiryText = $('#naib_ai_expiry_text');

        if (!$verifyBtn.length) return;

        function updateUsageUI(data) {
            if (!data) return;
            var used = data.quota_used || 0;
            var total = data.quota_total || 0;
            var expiry = data.expires_at || 'N/A';

            $usageText.text(used.toLocaleString() + ' / ' + total.toLocaleString());

            var percent = total > 0 ? (used / total) * 100 : 0;
            $usageBar.css('width', Math.min(percent, 100) + '%');

            if (percent > 90) $usageBar.css('background', '#dc3232');
            else if (percent > 70) $usageBar.css('background', '#dba617');
            else $usageBar.css('background', '#46b450');

            $expiryText.text(expiry);
            $usageContainer.fadeIn();
        }

        $verifyBtn.on('click', function() {
            var key = $keyInput.val();

            if (!key) {
                alert(adminData.strings.enter_license || 'Please enter a license key first.');
                return;
            }

            $verifyBtn.prop('disabled', true).text(adminData.strings.verifying || 'Verifying...');
            $status.text('').css('color', 'inherit');
            $usageContainer.hide();

            $.post(ajaxurl, {
                action: 'naib_ai_verify_license',
                license_key: key,
                nonce: adminData.verify_nonce
            }, function(response) {
                $verifyBtn.prop('disabled', false).text(adminData.strings.verify_license || 'Verify License');
                if (response.success) {
                    $status.text(adminData.strings.license_valid || '✓ Valid').css('color', '#46b450');
                    updateUsageUI(response.data);
                    $unbindBtn.show();
                } else {
                    $status.text('✗ ' + (response.data.message || 'Verification failed')).css('color', '#dc3232');
                    $unbindBtn.hide();
                }
            }).fail(function() {
                $verifyBtn.prop('disabled', false).text(adminData.strings.verify_license || 'Verify License');
                $status.text('✗ Network error').css('color', '#dc3232');
            });
        });

        $unbindBtn.on('click', function() {
            if (!confirm(adminData.strings.deactivate_confirm || 'Are you sure you want to deactivate and unbind this license from this domain?')) return;

            $unbindBtn.prop('disabled', true).text(adminData.strings.deactivating || 'Deactivating...');

            $.post(ajaxurl, {
                action: 'naib_ai_unbind_license',
                nonce: adminData.verify_nonce
            }, function(response) {
                $unbindBtn.prop('disabled', false).text(adminData.strings.deactivate_license || 'Deactivate License');
                if (response.success) {
                    alert(response.data.message || adminData.strings.deactivate_done || 'License deactivated successfully.');
                    $status.text(adminData.strings.license_unbound || 'Unbound').css('color', '#999');
                    $usageContainer.hide();
                    $unbindBtn.hide();
                } else {
                    alert(response.data.message || adminData.strings.deactivate_failed || 'Deactivation failed');
                }
            });
        });

        if ($keyInput.val()) {
            $unbindBtn.show();
        }
    }

    /**
     * Initialize All Functions
     */
    function init() {
        initProductGallery();
        initSettingsPage();
        initProductListPage();
        initCommonFeatures();
        ShortcodeGenerator.init();
        initFormValidation();
        initSettingsTabs();
        initInquiryModeToggle();
        initRedirectUrlToggle();
        initAiLicenseVerify();

        // Trigger custom event
        $(document).trigger('b2b_admin_ready');
    }
    
    // Initialize when document is ready
    $(document).ready(init);
    
    // Expose some methods for external use
    window.B2BProductAdmin = {
        init: init,
        showNotice: showNotice
    };
    
})(jQuery);