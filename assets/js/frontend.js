/**
 * B2B Product Showcase Frontend JavaScript
 */

(function($) {
    'use strict';

    // Initialize all features
    $(document).ready(function() {
        initProductImageSwitcher();
        initProductTabs();
        initProductGalleryLightbox();
        initProductFilters();
        initProductCardHover();
        initSmoothScrolling();
        initLazyLoading();
        initProductSearch();
        initGallerySlider(); // Added slider functionality
        initInquiryForm();
        initContactForm();
    });

    /**
     * Product image switcher functionality - Enhanced version with transition animation
     */
    function initProductImageSwitcher() {
        // Wait for DOM to fully load
        $(document).ready(function() {
            // Use event delegation to ensure dynamically added elements also respond
            $(document).on('click', '.naibabiji-b2b-gallery-thumb', function(e) {
                e.preventDefault();
                
                var $thumb = $(this);
                var newImageUrl = $thumb.data('image');
                
                if (!newImageUrl) {
                    return;
                }
                
                // Remove active state from other thumbnails
                $('.naibabiji-b2b-gallery-thumb').removeClass('active');
                
                // Add active state to current thumbnail
                $thumb.addClass('active');
                
                // Try multiple selectors to find the main image and wrapper
                var $featuredImage = $('.naibabiji-b2b-featured-image');
                var $wrapper = $('.naibabiji-b2b-featured-image-wrapper');
                
                // If first selector didn't find it, try other possible selectors
                if ($featuredImage.length === 0) {
                    $featuredImage = $('.naibabiji-b2b-product-featured-image img');
                }
                if ($featuredImage.length === 0) {
                    $featuredImage = $('.naibabiji-b2b-featured-image-wrapper img');
                }
                if ($wrapper.length === 0) {
                    $wrapper = $featuredImage.parent();
                }
                
                if ($featuredImage.length > 0) {
                    var currentSrc = $featuredImage.attr('src');
                    
                    // If image is the same, no need to switch
                    if (currentSrc === newImageUrl) {
                        return;
                    }
                    
                    // Add loading state
                    $featuredImage.addClass('loading');
                    $wrapper.addClass('loading');
                    
                    // Preload new image
                    var img = new Image();
                    img.onload = function() {
                        // Fade out current image
                        $featuredImage.css({
                            'opacity': '0.3',
                            'transition': 'opacity 0.15s ease-in-out'
                        });
                        
                        // Short delay for fade out effect
                        setTimeout(function() {
                            // Clear all possible interfering attributes
                            $featuredImage.removeAttr('srcset');
                            $featuredImage.removeAttr('sizes');
                            
                            // Update image source
                            $featuredImage.attr('src', newImageUrl);
                            
                            // Fade in new image
                            $featuredImage.css({
                                'opacity': '1',
                                'transition': 'opacity 0.2s ease-out'
                            });
                            
                            // Remove loading state after transition
                            setTimeout(function() {
                                $featuredImage.removeClass('loading');
                                $wrapper.removeClass('loading');
                            }, 200);
                        }, 150);
                    };
                    img.onerror = function() {
                        // Even if loading failed, update image anyway
                        $featuredImage.removeAttr('srcset');
                        $featuredImage.removeAttr('sizes');
                        $featuredImage.attr('src', newImageUrl);
                        $featuredImage.css({
                            'opacity': '1',
                            'transition': 'opacity 0.2s ease-out'
                        });
                        $featuredImage.removeClass('loading');
                        $wrapper.removeClass('loading');
                    };
                    img.src = newImageUrl;
                } else {
                    // Try to directly replace the whole image container
                    var $container = $('.naibabiji-b2b-product-featured-image, .naibabiji-b2b-featured-image-wrapper');
                    if ($container.length > 0) {
                        $container.fadeOut(150, function() {
                            $container.html('<img src="' + newImageUrl + '" alt="产品图片" class="naibabiji-b2b-featured-image">');
                            $container.fadeIn(150);
                        });
                    }
                }
            });
            
            // Set first thumbnail as active
            setTimeout(function() {
                $('.naibabiji-b2b-gallery-thumb').first().addClass('active');
            }, 100);
        });
    }

    /**
     * 产品图册滑块功能 - 根据屏幕宽度动态调整显示的缩略图数量
     */
    function initGallerySlider() {
        const galleryContainer = document.querySelector('.naibabiji-b2b-gallery-thumbnails');
        if (!galleryContainer) return;
    
        const galleryThumbs = galleryContainer.querySelectorAll('.naibabiji-b2b-gallery-thumb');
        
        // 如果只有一张图片，不需要滑块
        if (galleryThumbs.length <= 1) return;
        
        // 检查屏幕宽度，确定最大可见缩略图数量
        let maxVisible = 5; // PC端默认显示5张
        const screenWidth = window.innerWidth;
        
        if (screenWidth <= 480) {
            maxVisible = 3; // Display 3 thumbnails on small mobile screens
        } else if (screenWidth <= 768) {
            maxVisible = 4; // Display 4 thumbnails on tablets
        }
        
        // If thumbnail count doesn't exceed maximum visible count, slider is not needed
        if (galleryThumbs.length <= maxVisible) {
            // Ensure all thumbnails are visible
            galleryThumbs.forEach(thumb => {
                thumb.style.display = 'inline-block';
            });
            return;
        }
        
        // Check if navigation buttons already exist, if not create them
        let prevButton = galleryContainer.querySelector('.naibabiji-b2b-gallery-nav.prev');
        let nextButton = galleryContainer.querySelector('.naibabiji-b2b-gallery-nav.next');
        
        if (!prevButton) {
            prevButton = document.createElement('button');
            prevButton.className = 'naibabiji-b2b-gallery-nav prev';
            prevButton.setAttribute('aria-label', 'Previous');
            prevButton.innerHTML = '‹';
            galleryContainer.appendChild(prevButton);
        }
        
        if (!nextButton) {
            nextButton = document.createElement('button');
            nextButton.className = 'naibabiji-b2b-gallery-nav next';
            nextButton.setAttribute('aria-label', 'Next');
            nextButton.innerHTML = '›';
            galleryContainer.appendChild(nextButton);
        }
        
        // 为缩略图容器添加滑块样式
        galleryContainer.classList.add('naibabiji-b2b-gallery-slider');
        galleryContainer.style.position = 'relative';
        let currentIndex = 0;
    
        // 初始化显示状态
        updateGalleryDisplay();
    
        // 添加导航按钮事件
        if (prevButton) {
            prevButton.addEventListener('click', () => {
                if (currentIndex > 0) {
                    currentIndex--;
                    updateGalleryDisplay();
                }
            });
        }
    
        if (nextButton) {
            nextButton.addEventListener('click', () => {
                if (currentIndex < galleryThumbs.length - maxVisible) {
                    currentIndex++;
                    updateGalleryDisplay();
                }
            });
        }
    
        // Keyboard navigation support
        document.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowLeft' && prevButton && !prevButton.disabled) {
                prevButton.click();
            } else if (e.key === 'ArrowRight' && nextButton && !nextButton.disabled) {
                nextButton.click();
            }
        });
    
        // Update thumbnail display
        function updateGalleryDisplay() {
            galleryThumbs.forEach((thumb, index) => {
                if (index >= currentIndex && index < currentIndex + maxVisible) {
                    thumb.style.display = 'block';
                } else {
                    thumb.style.display = 'none';
                }
            });
    
            // Update navigation button status
            if (prevButton) {
                prevButton.disabled = currentIndex === 0;
                prevButton.style.opacity = currentIndex === 0 ? '0.5' : '1';
            }
            if (nextButton) {
                const isLastPage = currentIndex >= galleryThumbs.length - maxVisible;
                nextButton.disabled = isLastPage;
                nextButton.style.opacity = isLastPage ? '0.5' : '1';
            }
        }
    }

    // Add window resize listener to reinitialize slider when screen size changes
    window.addEventListener('resize', function() {
        // Use debounce function to avoid frequent triggering
        clearTimeout(window.resizeTimer);
        window.resizeTimer = setTimeout(function() {
            initGallerySlider();
        }, 250);
    });

    /**
     * Product tabs functionality
     */
    function initProductTabs() {
        $('.naibabiji-b2b-tab-link').on('click', function(e) {
            e.preventDefault();
            
            var $link = $(this);
            var targetId = $link.attr('href');
            
            // Remove all active states
            $('.naibabiji-b2b-tab-item').removeClass('active');
            $('.naibabiji-b2b-tab-panel').removeClass('active');
            
            // Add current active state
            $link.closest('.naibabiji-b2b-tab-item').addClass('active');
            $(targetId).addClass('active');
            
            // Smooth scroll to tab content
            $('html, body').animate({
                scrollTop: $('.naibabiji-b2b-product-tabs').offset().top - 100
            }, 300);
        });
    }

    /**
     * Product Gallery Lightbox Effect
     */
    function initProductGalleryLightbox() {
        // Check if lightbox library exists
        if (typeof $.fn.lightbox !== 'undefined') {
            $('[data-lightbox]').lightbox({
                resizeDuration: 200,
                wrapAround: true,
                albumLabel: 'Image %1 / %2'
            });
        } else {
            // Simple image viewer
            $('[data-lightbox]').on('click', function(e) {
                e.preventDefault();
                var imageUrl = $(this).attr('href');
                var $overlay = $('<div class="naibabiji-b2b-lightbox-overlay"></div>');
                var $image = $('<img src="' + imageUrl + '" class="naibabiji-b2b-lightbox-image">');
                var $close = $('<button class="naibabiji-b2b-lightbox-close">&times;</button>');
                
                $overlay.append($image, $close);
                $('body').append($overlay);
                
                $overlay.fadeIn(300);
                
                $close.on('click', function() {
                    $overlay.fadeOut(300, function() {
                        $overlay.remove();
                    });
                });
                
                $overlay.on('click', function(e) {
                    if (e.target === this) {
                        $overlay.fadeOut(300, function() {
                            $overlay.remove();
                        });
                    }
                });
            });
        }
    }

    /**
     * Product Filter Functionality
     */
    function initProductFilters() {
        $('.filter-link').on('click', function(e) {
            var $link = $(this);
            
            // Add loading state
            $link.addClass('loading');
            
            // Remove other active states
            $('.filter-link').removeClass('active');
            $link.addClass('active');
            
            // AJAX filtering logic can be added here
            setTimeout(function() {
                $link.removeClass('loading');
            }, 500);
        });
    }

    /**
     * Product Card Hover Effect
     */
    function initProductCardHover() {
        $('.naibabiji-b2b-product-card').hover(
            function() {
                $(this).addClass('naibabiji-b2b-fade-in');
            },
            function() {
                $(this).removeClass('naibabiji-b2b-fade-in');
            }
        );
    }

    /**
     * Smooth Scrolling
     */
    function initSmoothScrolling() {
        $('a[href^="#"]').on('click', function(e) {
            var target = $(this.getAttribute('href'));
            
            if (target.length) {
                e.preventDefault();
                $('html, body').stop().animate({
                    scrollTop: target.offset().top - 100
                }, 600);
            }
        });
    }

    /**
     * Lazy Loading for Images
     */
    function initLazyLoading() {
        if ('IntersectionObserver' in window) {
            var imageObserver = new IntersectionObserver(function(entries, observer) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        var img = entry.target;
                        img.src = img.dataset.src;
                        img.classList.remove('lazy');
                        imageObserver.unobserve(img);
                    }
                });
            });

            document.querySelectorAll('img[data-src]').forEach(function(img) {
                imageObserver.observe(img);
            });
        }
    }

    /**
     * Product Search Functionality
     */
    function initProductSearch() {
        var $searchInput = $('.naibabiji-b2b-product-search');
        var $searchResults = $('.naibabiji-b2b-search-results');
        var searchTimeout;

        $searchInput.on('input', function() {
            var query = $(this).val().trim();
            
            clearTimeout(searchTimeout);
            
            if (query.length < 2) {
                $searchResults.hide();
                return;
            }
            
            searchTimeout = setTimeout(function() {
                performSearch(query);
            }, 300);
        });

        function performSearch(query) {
            // AJAX search logic can be added here.
        }
    }

    /**
     * Responsive Handling
     */
    function handleResponsive() {
        var windowWidth = $(window).width();
        
        // Special handling for mobile devices
        if (windowWidth <= 768) {
            // Mobile optimization
            $('.naibabiji-b2b-product-card').off('mouseenter mouseleave');
        }
    }

    // Re-handle responsive when window size changes
    $(window).on('resize', function() {
        handleResponsive();
    });

    // Initialize responsive handling
    handleResponsive();

    /**
     * Utility Functions
     */
    function debounce(func, wait, immediate) {
        var timeout;
        return function() {
            var context = this, args = arguments;
            var later = function() {
                timeout = null;
                if (!immediate) func.apply(context, args);
            };
            var callNow = immediate && !timeout;
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
            if (callNow) func.apply(context, args);
        };
    }

    function throttle(func, limit) {
        var inThrottle;
        return function() {
            var args = arguments;
            var context = this;
            if (!inThrottle) {
                func.apply(context, args);
                inThrottle = true;
                setTimeout(function() {
                    inThrottle = false;
                }, limit);
            }
        };
    }

    // Add simple lightbox styles (if no external library)
    if (typeof $.fn.lightbox === 'undefined') {
        $('<style>')
            .prop('type', 'text/css')
            .html(`
                .naibabiji-b2b-lightbox-overlay {
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: rgba(0, 0, 0, 0.9);
                    display: none;
                    z-index: 9999;
                    justify-content: center;
                    align-items: center;
                }
                .naibabiji-b2b-lightbox-image {
                    max-width: 90%;
                    max-height: 90%;
                    object-fit: contain;
                }
                .naibabiji-b2b-lightbox-close {
                    position: absolute;
                    top: 20px;
                    right: 30px;
                    background: none;
                    border: none;
                    color: white;
                    font-size: 40px;
                    cursor: pointer;
                    z-index: 10000;
                }
                .naibabiji-b2b-lightbox-close:hover {
                    opacity: 0.7;
                }
            `)
            .appendTo('head');
    }

    /**
     * Inquiry Form Modal Logic
     */
    function initInquiryForm() {
        const $modal = $('#naibabiji-b2b-inquiry-modal');
        if (!$modal.length) return;

        const $form = $('#naibabiji-b2b-inquiry-form');
        const $success = $('#naibabiji-b2b-inquiry-success');
        const $status = $('.naib-form-status');
        const $submitBtn = $form.find('button[type="submit"]');

        // Global function to trigger modal (can be used by AI Chat)
        window.naib_trigger_inquiry_modal = function(productId, productTitle) {
            $('#naib-modal-product-id').val(productId || 0);
            $('#naib-modal-product-title').text(productTitle || 'General Inquiry');
            
            $form.show();
            $success.hide();
            $status.hide().removeClass('error').text('');
            
            $modal.find('.naib-overlay, .naib-panel').addClass('active');
            $('body').css('overflow', 'hidden'); // Prevent scrolling
        };

        // Click on inquiry button
        $(document).on('click', '.naibabiji-b2b-trigger-inquiry-form', function(e) {
            e.preventDefault();
            const productId = $(this).data('product-id');
            const productTitle = $(this).data('product-title');
            window.naib_trigger_inquiry_modal(productId, productTitle);
        });

        // Close modal
        $modal.on('click', '.naib-panel__close, .naib-overlay, .naibabiji-b2b-modal-close-btn', function() {
            $modal.find('.naib-overlay, .naib-panel').removeClass('active');
            $('body').css('overflow', '');
        });

        // Form submission
        $form.on('submit', function(e) {
            e.preventDefault();
            
            // Frequency limiting (simple local check)
            const lastSent = localStorage.getItem('naib_b2b_last_inquiry');
            const now = Date.now();
            if (lastSent && (now - lastSent < 60000)) { // 1 minute
                $status.addClass('error').text('Please wait a moment before sending another inquiry.').show();
                return;
            }

            $submitBtn.prop('disabled', true).find('.btn-spinner').show();
            $status.hide();

            // Prepare data: merge fields into contact/message structure if needed
            // The handler expects 'contact' and 'message'. 
            // We'll construct 'contact' from Name/Email/WhatsApp/Company/Country.
            const formData = $form.serializeArray();
            let dataObj = {};
            formData.forEach(item => {
                if (item.name.endsWith('[]')) {
                    const cleanName = item.name.replace('[]', '');
                    if (!dataObj[cleanName]) dataObj[cleanName] = [];
                    dataObj[cleanName].push(item.value);
                } else {
                    dataObj[item.name] = item.value;
                }
            });

            // Construct rich contact info
            let richContact = `Name: ${dataObj.name || ''}\nEmail: ${dataObj.email || ''}`;
            if (dataObj.whatsapp) richContact += `\nWhatsApp: ${dataObj.whatsapp}`;
            if (dataObj.job_title) richContact += `\nJob Title: ${dataObj.job_title}`;
            if (dataObj.company) richContact += `\nCompany: ${dataObj.company}`;
            if (dataObj.country) richContact += `\nCountry: ${dataObj.country}`;

            const finalData = {
                action: 'naib_ai_save_lead',
                nonce: dataObj.nonce,
                product_id: dataObj.product_id,
                source: dataObj.source,
                contact: richContact,
                message: dataObj.message || '',
                name: dataObj.name || '',
                email: dataObj.email || '',
                whatsapp: dataObj.whatsapp || '',
                job_title: dataObj.job_title || '',
                company: dataObj.company || '',
                country: dataObj.country || ''
            };

            $.ajax({
                url: typeof naibabiji_b2b_product_showcase !== 'undefined' ? naibabiji_b2b_product_showcase.ajax_url : '/wp-admin/admin-ajax.php',
                type: 'POST',
                data: finalData,
                success: function(response) {
                    if (response.success) {
                        if (response.data && response.data.redirect_url) {
                            window.location.href = response.data.redirect_url;
                            return;
                        }
                        $form.hide();
                        $success.fadeIn();
                        localStorage.setItem('naib_b2b_last_inquiry', Date.now());
                    } else {
                        $status.addClass('error').text(response.data || 'Submission failed. Please try again.').show();
                    }
                },
                error: function() {
                    $status.addClass('error').text('Network error. Please try again later.').show();
                },
                complete: function() {
                    $submitBtn.prop('disabled', false).find('.btn-spinner').hide();
                }
            });
        });
    }

    /**
     * Email validation helper
     */
    function isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    /**
     * Inline Contact Form Logic
     * Handles [naibabiji_b2b_contact_form] shortcode form submission
     * with email validation, nonce refresh for cached pages, and rate limiting.
     */
    function initContactForm() {
        var $form = $('#naibabiji-b2b-contact-form');
        if (!$form.length) return;

        var $success = $('#naibabiji-b2b-contact-success');
        var $status = $form.find('.naib-form-status');
        var $submitBtn = $form.find('button[type="submit"]');

        // Client-side email validation on blur
        $form.on('blur', 'input[type="email"]', function() {
            var $input = $(this);
            var $error = $input.closest('.naib-field-group').find('.naib-field-error');
            if ($input.val() && !isValidEmail($input.val())) {
                $input.addClass('error');
                $error.text(naibabiji_b2b_contact_form_i18n && naibabiji_b2b_contact_form_i18n.invalid_email
                    ? naibabiji_b2b_contact_form_i18n.invalid_email
                    : 'Please enter a valid email address.');
                $error.addClass('visible');
            } else {
                $input.removeClass('error');
                $error.removeClass('visible');
            }
        });

        // Clear error on typing
        $form.on('input', 'input[type="email"]', function() {
            $(this).removeClass('error');
            $(this).closest('.naib-field-group').find('.naib-field-error').removeClass('visible');
        });

        $form.on('submit', function(e) {
            e.preventDefault();

            // Client-side email format check
            var $emailField = $form.find('input[name="email"]');
            if ($emailField.length && $emailField.val() && !isValidEmail($emailField.val())) {
                $emailField.addClass('error');
                var $emailError = $emailField.closest('.naib-field-group').find('.naib-field-error');
                $emailError.text(naibabiji_b2b_contact_form_i18n && naibabiji_b2b_contact_form_i18n.invalid_email
                    ? naibabiji_b2b_contact_form_i18n.invalid_email
                    : 'Please enter a valid email address.');
                $emailError.addClass('visible');
                $emailField.focus();
                return;
            }

            // Frequency limiting
            var lastSent = localStorage.getItem('naib_b2b_last_contact');
            var now = Date.now();
            if (lastSent && (now - lastSent < 60000)) {
                var rateLimitMsg = naibabiji_b2b_contact_form_i18n && naibabiji_b2b_contact_form_i18n.rate_limit
                    ? naibabiji_b2b_contact_form_i18n.rate_limit
                    : 'Please wait a moment before sending another message.';
                $status.addClass('error').text(rateLimitMsg).show();
                return;
            }

            $submitBtn.prop('disabled', true).find('.btn-spinner').show();
            $status.hide();

            var formData = $form.serializeArray();
            var dataObj = {};
            formData.forEach(function(item) {
                dataObj[item.name] = item.value;
            });

            // Build contact string from individual fields
            var richContact = 'Name: ' + (dataObj.name || '') + '\nEmail: ' + (dataObj.email || '');
            if (dataObj.whatsapp) richContact += '\nWhatsApp: ' + dataObj.whatsapp;
            if (dataObj.job_title) richContact += '\nJob Title: ' + dataObj.job_title;
            if (dataObj.company) richContact += '\nCompany: ' + dataObj.company;
            if (dataObj.country) richContact += '\nCountry: ' + dataObj.country;

            var finalData = {
                action: 'naib_ai_save_lead',
                nonce: dataObj.nonce,
                product_id: 0,
                source: 'contact_form',
                contact: richContact,
                message: dataObj.message || '',
                name: dataObj.name || '',
                email: dataObj.email || '',
                whatsapp: dataObj.whatsapp || '',
                job_title: dataObj.job_title || '',
                company: dataObj.company || '',
                country: dataObj.country || '',
                page_title: document.title
            };

            submitWithNonceRetry(finalData, $form, $success, $status, $submitBtn);
        });

        /**
         * Submit with automatic nonce refresh for cached pages
         */
        function submitWithNonceRetry(data, $form, $success, $status, $submitBtn) {
            $.ajax({
                url: typeof naibabiji_b2b_product_showcase !== 'undefined' ? naibabiji_b2b_product_showcase.ajax_url : '/wp-admin/admin-ajax.php',
                type: 'POST',
                data: data,
                success: function(response) {
                    if (response.success) {
                        if (response.data && response.data.redirect_url) {
                            window.location.href = response.data.redirect_url;
                            return;
                        }
                        $form.hide();
                        $success.fadeIn();
                        localStorage.setItem('naib_b2b_last_contact', Date.now());
                    } else {
                        // If nonce expired, try to refresh and retry
                        if (response.data && typeof response.data === 'string' && response.data.indexOf('nonce') !== -1) {
                            refreshNonceAndRetry(data, $form, $success, $status, $submitBtn);
                        } else {
                            $status.addClass('error').text(response.data || 'Submission failed. Please try again.').show();
                        }
                    }
                },
                error: function(xhr) {
                    // 403 likely means nonce expired (cached page)
                    if (xhr.status === 403) {
                        refreshNonceAndRetry(data, $form, $success, $status, $submitBtn);
                    } else {
                        var errorMsg = naibabiji_b2b_contact_form_i18n && naibabiji_b2b_contact_form_i18n.network_error
                            ? naibabiji_b2b_contact_form_i18n.network_error
                            : 'Network error. Please try again later.';
                        $status.addClass('error').text(errorMsg).show();
                    }
                },
                complete: function() {
                    $submitBtn.prop('disabled', false).find('.btn-spinner').hide();
                }
            });
        }

        /**
         * Fetch a fresh nonce and retry the submission
         */
        function refreshNonceAndRetry(originalData, $form, $success, $status, $submitBtn) {
            $.ajax({
                url: (typeof naibabiji_b2b_product_showcase !== 'undefined' ? naibabiji_b2b_product_showcase.ajax_url : '/wp-admin/admin-ajax.php'),
                type: 'POST',
                data: {
                    action: 'naib_ai_get_nonce'
                },
                success: function(response) {
                    if (response.success && response.data && response.data.nonce) {
                        originalData.nonce = response.data.nonce;
                        $submitBtn.prop('disabled', true).find('.btn-spinner').show();
                        submitWithNonceRetry(originalData, $form, $success, $status, $submitBtn);
                    } else {
                        var errorMsg = naibabiji_b2b_contact_form_i18n && naibabiji_b2b_contact_form_i18n.retry_failed
                            ? naibabiji_b2b_contact_form_i18n.retry_failed
                            : 'Security verification failed. Please reload the page and try again.';
                        $status.addClass('error').text(errorMsg).show();
                    }
                },
                error: function() {
                    var errorMsg = naibabiji_b2b_contact_form_i18n && naibabiji_b2b_contact_form_i18n.retry_failed
                        ? naibabiji_b2b_contact_form_i18n.retry_failed
                        : 'Security verification failed. Please reload the page and try again.';
                    $status.addClass('error').text(errorMsg).show();
                }
            });
        }
    }

})(jQuery);
