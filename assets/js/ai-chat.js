/**
 * AI Chat Widget Logic (SPEC v2 Aligned)
 */
(function($) {
    'use strict';

    class AiChatWidget {
        constructor() {
            this.container = null;
            this.messagesArea = null;
            this.input = null;
            this.history = [];
            this.requestId = this.generateUUID();
            this.isWaiting = false;
            this.cachedNonce = null;  // Dynamically fetched nonce (cache-safe)

            this.init();
        }

        init() {
            // Only render if we have a valid product ID
            if (!this.getCurrentPostId()) {
                return;
            }
            this.render();
            this.bindEvents();
        }

        render() {
            const overlay = $('<div class="naib-overlay naib-ai-overlay"></div>');
            const container = $(`
                <div class="naib-panel naib-ai-chat-container">
                    <div class="naib-panel__header">
                        <h3>Industrial AI Support</h3>
                        <button type="button" class="naib-panel__close close-chat">&times;</button>
                    </div>
                    <div class="naib-panel__body naib-ai-messages"></div>
                    <div class="naib-panel__footer naib-ai-input-area" style="padding: 20px; background: white; border-top: 1px solid rgba(0,0,0,0.05); display: flex; gap: 10px;">
                        <textarea class="naib-input" placeholder="Ask about this product..." style="height: 40px; resize: none;"></textarea>
                        <button class="naib-ai-send-btn naib-b2b-btn naib-b2b-btn--primary" style="width: 40px; height: 40px; padding: 0; border-radius: 50%;">➤</button>
                    </div>
                </div>
            `);

            $('body').append(overlay).append(container);
            this.overlay      = overlay;
            this.container    = container;
            this.messagesArea = container.find('.naib-ai-messages');
            this.input        = container.find('textarea');

            // Place launcher next to inquiry button if available, else fallback to floating
            const inquiryBtn = $('.naibabiji-b2b-inquiry-button').first();
            if (inquiryBtn.length) {
                inquiryBtn.wrap('<div class="naib-ai-btn-row"></div>');
                const launcher = $('<button class="naib-b2b-btn naib-b2b-btn--ai naib-ai-chat-launcher naib-ai-chat-launcher--inline">🤖 AI Support</button>');
                inquiryBtn.parent().append(launcher);
                this.launcher = launcher;
            } else {
                const position = naibabiji_ai_chat_vars.float_position || 'bottom-right';
                const launcher = $(`<div class="naib-ai-chat-launcher naib-ai-chat-launcher--float naib-ai-pos-${position}" title="AI Support">💬</div>`);
                $('body').append(launcher);
                this.launcher = launcher;
            }

            this.addMessage('ai', 'Hello! How can I assist you with this product today?');
        }

        bindEvents() {
            $('body').on('click', '.naib-ai-chat-launcher', () => this.openDialog());
            $('body').on('click', '.naib-ai-overlay', () => this.closeDialog());

            this.container.on('click', '.close-chat', () => this.closeDialog());
            this.container.on('click', '.naib-ai-send-btn', () => this.handleSendMessage());

            this.input.on('keypress', (e) => {
                if (e.which === 13 && !e.shiftKey) {
                    e.preventDefault();
                    this.handleSendMessage();
                }
            });
        }

        openDialog() {
            this.overlay.addClass('active');
            this.container.addClass('active');
        }

        closeDialog() {
            this.overlay.removeClass('active');
            this.container.removeClass('active');
        }

        handleSendMessage() {
            const query = this.input.val().trim();
            if (!query || this.isWaiting) return;

            this.addMessage('user', query);
            this.input.val('');
            this.isWaiting = true;

            this.showTypingIndicator();

            this.getNonce().then((nonce) => {
                const data = {
                    action: 'naib_ai_chat',
                    nonce: nonce,
                    message: query,
                    request_id: this.requestId,
                    post_id: this.getCurrentPostId(),
                    history: this.history
                };
                this.performChatRequest(data);
            }).catch(() => {
                this.removeTypingIndicator();
                this.isWaiting = false;
                this.addMessage('ai', 'Unable to verify security token. Please refresh the page and try again.');
            });
        }

        /**
         * Fetch a fresh nonce from the server.
         * Caches the nonce in memory for the session to avoid redundant requests.
         */
        getNonce() {
            if (this.cachedNonce) {
                return Promise.resolve(this.cachedNonce);
            }
            return $.ajax({
                url: naibabiji_ai_chat_vars.ajax_url,
                type: 'POST',
                data: { action: 'naib_ai_get_nonce' },
            }).then((response) => {
                if (response.success && response.data && response.data.nonce) {
                    this.cachedNonce = response.data.nonce;
                    return this.cachedNonce;
                }
                return Promise.reject('Failed to get nonce');
            });
        }

        performChatRequest(data, retryCount = 0) {
            const maxRetries = 4;
            const delays = [1000, 2000, 4000, 8000]; // Exponential backoff

            $.ajax({
                url: naibabiji_ai_chat_vars.ajax_url,
                type: 'POST',
                data: data,
                success: (response) => {
                    // If nonce is invalid, refresh it and retry once
                    if (!response.success && response.data && typeof response.data === 'string' && response.data.indexOf('Security') !== -1 && retryCount === 0) {
                        this.cachedNonce = null;
                        this.getNonce().then((freshNonce) => {
                            data.nonce = freshNonce;
                            this.performChatRequest(data, maxRetries); // Skip normal retry, re-do with fresh nonce
                        });
                        return;
                    }

                    // Retry on "processing" status (409) or specific 402/429 if transient
                    if (response.data && response.data.error && response.data.error.code === 'processing' && retryCount < maxRetries) {
                        const delay = delays[retryCount] + Math.random() * 500;
                        setTimeout(() => this.performChatRequest(data, retryCount + 1), delay);
                        return;
                    }

                    this.removeTypingIndicator();
                    this.isWaiting = false;

                    if (response.success && response.data.status === 'ok') {
                        const content = response.data.message;
                        this.addMessage('ai', content);
                        this.history.push({ role: 'user', content: data.message });
                        this.history.push({ role: 'assistant', content: content });
                        
                        this.requestId = this.generateUUID(); // Fresh ID for next session
                    } else if (response.data && response.data.status === 'fallback') {
                        this.addMessage('ai', response.data.message);
                        this.history.push({ role: 'user', content: data.message });
                        this.showLeadForm();
                    } else {
                        const errMsg = (response.data && response.data.message) ? response.data.message : 'System busy, please retry.';
                        const errCode = (response.data && response.data.error && response.data.error.code) ? response.data.error.code : 'UNKNOWN';
                        
                        this.addMessage('ai', errMsg + ' (Error Code: ' + errCode + ')');

                        // Automatically fallback to inquiry form for specific configuration or quota errors
                        if (errCode === 'config_missing' || errCode === 'api_error_402' || 
                            errMsg.indexOf('AI config missing') !== -1 || 
                            errMsg.indexOf('Check quota') !== -1) {
                            setTimeout(() => this.showLeadForm(), 1500);
                        }
                    }
                },
                error: (xhr) => {
                    if (retryCount < maxRetries) {
                        const delay = delays[retryCount] + Math.random() * 500;
                        setTimeout(() => this.performChatRequest(data, retryCount + 1), delay);
                    } else {
                        this.removeTypingIndicator();
                        this.isWaiting = false;
                        this.history.push({ role: 'user', content: data.message });
                        this.addMessage('ai', 'Connection timeout. Our service is currently busy. Please leave your contact details below and we will get back to you shortly.');
                        this.showLeadForm();
                    }
                }
            });
        }

        addMessage(role, content) {
            const msgObj = $(`<div class="naib-ai-msg ${role}"></div>`);
            
            if (role === 'ai') {
                msgObj.html(this.formatMarkdown(content));
            } else {
                msgObj.text(content);
            }
            
            this.messagesArea.append(msgObj);
            this.scrollBottom();
        }

        formatMarkdown(text) {
            if (!text) return '';
            
            // 1. Basic HTML Escaping for security
            let html = text
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;');

            // 2. Bold: **text** -> <strong>text</strong>
            html = html.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');

            // 3. Lists and Paragraphs
            const lines = html.split('\n');
            let processed = [];
            let inList = false;

            lines.forEach(line => {
                const trimmed = line.trim();
                const listMatch = trimmed.match(/^[\*\-]\s+(.*)/);

                if (listMatch) {
                    if (!inList) {
                        processed.push('<ul class="naib-ai-list">');
                        inList = true;
                    }
                    processed.push(`<li>${listMatch[1]}</li>`);
                } else {
                    if (inList) {
                        processed.push('</ul>');
                        inList = false;
                    }
                    if (trimmed) {
                        processed.push(`<p>${trimmed}</p>`);
                    }
                }
            });

            if (inList) processed.push('</ul>');

            return processed.join('');
        }

        showTypingIndicator() {
            const indicator = $(`
                <div class="naib-ai-msg ai typing-indicator-msg" style="width: 50px;">
                    <div class="typing-indicator">
                        <span></span><span></span><span></span>
                    </div>
                </div>
            `);
            this.messagesArea.append(indicator);
            this.scrollBottom();
        }

        removeTypingIndicator() {
            this.messagesArea.find('.typing-indicator-msg').remove();
        }

        showLeadForm() {
            // Check if the new global inquiry modal is available
            if (window.naib_trigger_inquiry_modal && $('#naibabiji-b2b-inquiry-modal').length) {
                this.closeDialog();
                const productId = this.getCurrentPostId();
                const productTitle = $('.naibabiji-b2b-product-title').first().text();
                window.naib_trigger_inquiry_modal(productId, productTitle);
                return;
            }

            if (this.container.find('.naib-ai-lead-form').length) return;

            // Disable chat input while lead form is shown
            this.container.find('.naib-ai-input-area').hide();

            const form = $(`
                <div class="naib-ai-lead-form">
                    <p>Our team will follow up with you shortly. Please leave your contact details.</p>
                    <input type="text" class="naib-lead-contact" placeholder="Email / Phone / WeChat" maxlength="100" />
                    <button class="naib-lead-submit">Submit</button>
                    <div class="naib-lead-status"></div>
                </div>
            `);

            this.messagesArea.append(form);
            this.scrollBottom();

            const historySnapshot = this.history.slice();
            const firstUserMsg = historySnapshot.length ? historySnapshot[0].content : '';

            form.on('click', '.naib-lead-submit', () => {
                const contact = form.find('.naib-lead-contact').val().trim();
                if (!contact) {
                    form.find('.naib-lead-status').text('Please enter your contact info.');
                    return;
                }

                form.find('.naib-lead-submit').prop('disabled', true).text('Sending...');

                this.getNonce().then((nonce) => {
                    $.ajax({
                        url: naibabiji_ai_chat_vars.ajax_url,
                        type: 'POST',
                        data: {
                            action:     'naib_ai_save_lead',
                            nonce:      nonce,
                            contact:    contact,
                            message:    firstUserMsg,
                            product_id: this.getCurrentPostId(),
                            history:    JSON.stringify(historySnapshot)
                        },
                        success: (response) => {
                            if (response.success) {
                                form.html('<p class="naib-lead-thanks">&#10003; Thank you! We\'ll be in touch soon.</p>');
                                this.container.find('.naib-ai-input-area').show();
                            } else {
                                form.find('.naib-lead-status').text('Submission failed. Please try again.');
                                form.find('.naib-lead-submit').prop('disabled', false).text('Submit');
                            }
                        },
                        error: () => {
                            form.find('.naib-lead-status').text('Network error. Please try again.');
                            form.find('.naib-lead-submit').prop('disabled', false).text('Submit');
                        }
                    });
                });
            });
        }

        scrollBottom() {
            this.messagesArea.animate({ scrollTop: this.messagesArea[0].scrollHeight }, 300);
        }

        getCurrentPostId() {
            // Priority 1: Marker injected via hook (Most reliable)
            const marker = $('#naib-ai-product-context');
            const p1 = (marker.length && marker.data('post-id')) ? marker.data('post-id') : null;

            // Priority 2: Localized variable from backend
            const p2 = (typeof naibabiji_ai_chat_vars !== 'undefined' && naibabiji_ai_chat_vars.current_post_id)
                ? naibabiji_ai_chat_vars.current_post_id : null;

            // Priority 3: Body class
            const p3 = $('article.post').attr('id')?.replace('post-', '')
                || $('body').attr('class').match(/postid-(\d+)/)?.[1]
                || null;

            return p1 || p2 || p3 || 0;
        }

        generateUUID() {
            return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
                var r = Math.random() * 16 | 0, v = c == 'x' ? r : (r & 0x3 | 0x8);
                return v.toString(16);
            });
        }
    }

    $(document).ready(() => {
        new AiChatWidget();
    });

})(jQuery);
