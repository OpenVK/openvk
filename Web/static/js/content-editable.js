(function (window) {
    'use strict';

    const BLANK_GIF = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';

    let emojiSeqRegex;
    try {
        emojiSeqRegex = new RegExp('[\\p{RGI_Emoji}]', 'gv');
    } catch (e1) {
        try {
            emojiSeqRegex = new RegExp('(?:\\p{Regional_Indicator}{2}|[\\p{Extended_Pictographic}\\p{Emoji_Presentation}](?:[\\uFE00-\\uFE0F]|\\p{Emoji_Modifier})?(?:\\u200D[\\p{Extended_Pictographic}\\p{Emoji_Presentation}](?:[\\uFE00-\\uFE0F]|\\p{Emoji_Modifier})?)*)', 'gu');
        } catch (e2) {
            // Fallback for older engines without \p regex property support
            emojiSeqRegex = /(?:[\uD83C-\uDBFF][\uDC00-\uDFFF]|[\u2600-\u27BF]|\u23E9|\u23EA|\u23EB|\u23EC|\u23F0|\u23F3|\u25B6|\u25C0)/g;
        }
    }

    function getEmojiHex(emoji) {
        if (typeof window.encode_emoji === 'function') return window.encode_emoji(emoji);
        let hex = '';
        for (let i = 0; i < emoji.length; i++) {
            hex += emoji.charCodeAt(i).toString(16).padStart(4, '0').toUpperCase();
        }
        return hex;
    }

    function escapeHtml(str) {
        return (str || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    const mentionRegex = /\[([a-zA-Z0-9_]+(?:\|[^\]]*)?)\]/g;

    function textToHtml(text) {
        if (!text) return '';
        // Normalize Windows CRLF and classic Mac CR to standard \n
        const str = String(text).replace(/\r\n/g, '\n').replace(/\r/g, '\n');
        // Fast-path for plain text without newlines, emoji sequences, or mention brackets
        if (!str.includes('\n') && !str.match(/[\uD800-\uDFFF\u2600-\u27BF]/) && !str.includes('[')) {
            return escapeHtml(str);
        }

        const lines = str.split('\n');
        const htmlLines = lines.map(function (line) {
            let escaped = escapeHtml(line);
            escaped = escaped.replace(mentionRegex, function (fullMatch, innerContent) {
                let displayText = fullMatch;
                if (innerContent && innerContent.includes('|')) {
                    displayText = innerContent.substring(innerContent.indexOf('|') + 1) || innerContent;
                } else if (innerContent) {
                    if (innerContent === 'all' || innerContent === 'online') {
                        displayText = '@' + innerContent;
                    } else {
                        displayText = innerContent;
                    }
                }
                return '<span class="mention-token" contenteditable="false" data-mention="' + fullMatch + '" style="color: var(--link, #2b587a); user-select: all; cursor: default;">' + displayText + '</span>';
            });
            return escaped.replace(emojiSeqRegex, function (emoji) {
                let hex = getEmojiHex(emoji);
                if (hex === '2764FE0F') hex = '2764';
                return '<img class="emoji emoji_' + hex + '" src="' + BLANK_GIF + '" alt="' + escapeHtml(emoji) + '" draggable="false" contenteditable="false" />';
            });
        });
        return htmlLines.join('<br>');
    }

    function htmlToText(node) {
        if (!node) return '';
        // Fast-path: if node has no element children, it is purely text nodes!
        if (!node.firstElementChild) {
            return (node.textContent || '').replace(/\r\n/g, '\n').replace(/\r/g, '\n');
        }

        let text = '';
        function walk(n) {
            if (n.nodeType === Node.TEXT_NODE) {
                text += n.nodeValue.replace(/\r/g, '');
            } else if (n.nodeType === Node.ELEMENT_NODE) {
                const tag = n.tagName.toUpperCase();
                if (n.classList && (n.classList.contains('mention-token') || n.hasAttribute('data-mention'))) {
                    text += n.getAttribute('data-mention') || n.textContent || '';
                } else if (tag === 'IMG') {
                    if (n.classList.contains('emoji') || n.hasAttribute('alt')) {
                        text += n.getAttribute('alt') || '';
                    }
                } else if (tag === 'BR') {
                    text += '\n';
                } else if (tag === 'DIV' || tag === 'P' || tag === 'LI' || tag === 'TR' || tag === 'BLOCKQUOTE') {
                    if (text.length > 0 && !text.endsWith('\n')) text += '\n';
                    const len = n.childNodes.length;
                    for (let i = 0; i < len; i++) walk(n.childNodes[i]);
                    if (!text.endsWith('\n')) text += '\n';
                } else {
                    const len = n.childNodes.length;
                    for (let i = 0; i < len; i++) walk(n.childNodes[i]);
                }
            }
        }
        const len = node.childNodes.length;
        for (let i = 0; i < len; i++) {
            walk(node.childNodes[i]);
        }
        if (text.endsWith('\n') && !text.endsWith('\n\n')) {
            text = text.slice(0, -1);
        }
        return text.replace(/\r\n/g, '\n').replace(/\r/g, '\n');
    }

    // Shared global selection listener to track caret position efficiently
    let _globalSelectionBound = false;
    function _ensureGlobalSelectionListener() {
        if (_globalSelectionBound || typeof document === 'undefined') return;
        _globalSelectionBound = true;
        document.addEventListener('selectionchange', function () {
            const active = ContentEditable.lastFocused;
            if (!active || !active.el) return;
            const sel = window.getSelection();
            if (sel && sel.rangeCount > 0) {
                const range = sel.getRangeAt(0);
                if (active.el.contains(range.commonAncestorContainer)) {
                    active.savedRange = range.cloneRange();
                }
            }
        }, { passive: true });
    }

    class ContentEditable {
        constructor(el, options) {
            if (el._contentEditable) return el._contentEditable;

            this.el = el;
            this.options = Object.assign({
                submitOnEnter: false,
                hiddenInput: null,
                placeholder: '',
                onSubmit: null,
                singleLine: false,
            }, options || {});

            this.savedRange = null;
            this.hiddenInput = this.options.hiddenInput;
            this._isSyncing = false;
            this._cachedText = null;
            this._isDirty = true;
            this._syncTimer = null;

            this._initDOM();
            this._bindEvents();

            this.el._contentEditable = this;
            this.el._emojiEditable = this; // Backwards alias
            ContentEditable.instances.set(this.el, this);
        }

        _initDOM() {
            this.el.setAttribute('contenteditable', 'true');
            this.el.setAttribute('role', 'textbox');
            this.el.setAttribute('aria-multiline', 'true');
            this.el.classList.add('content-editable');

            // Disable Firefox object resizing controls on inline images
            if (typeof document !== 'undefined' && document.execCommand) {
                try {
                    document.execCommand("enableObjectResizing", false, false);
                } catch (e) { }
            }

            const placeholder = this.options.placeholder || this.el.getAttribute('data-placeholder') || this.el.getAttribute('placeholder');
            if (placeholder) {
                this.el.setAttribute('data-placeholder', placeholder);
            }

            // Look for hidden input if not provided
            if (!this.hiddenInput) {
                const form = this.el.closest ? (this.el.closest('form') || this.el.parentElement) : this.el.parentElement;
                if (form) {
                    const name = this.el.getAttribute('data-name');
                    if (name) {
                        this.hiddenInput = form.querySelector('textarea[name="' + name + '"], input[name="' + name + '"]');
                    }
                }
            }

            // If inside a form, flush sync on submit
            const form = this.el.closest ? this.el.closest('form') : this.el.parentElement;
            if (form) {
                form.addEventListener('submit', () => {
                    this._flushSync(false);
                }, { capture: true });
            }

            // Expose properties on DOM node for textarea compatibility
            const self = this;
            try {
                Object.defineProperty(this.el, 'value', {
                    get: function () { return self.getText(); },
                    set: function (val) { self.setText(val); },
                    configurable: true,
                });
                Object.defineProperty(this.el, 'selectionStart', {
                    get: function () {
                        const sel = window.getSelection();
                        if (!sel || sel.rangeCount === 0) return 0;
                        const range = sel.getRangeAt(0);
                        if (!self.el.contains(range.commonAncestorContainer)) return 0;
                        const preRange = document.createRange();
                        preRange.selectNodeContents(self.el);
                        preRange.setEnd(range.startContainer, range.startOffset);
                        return htmlToText(preRange.cloneContents()).length;
                    },
                    configurable: true,
                });
                Object.defineProperty(this.el, 'selectionEnd', {
                    get: function () {
                        const sel = window.getSelection();
                        if (!sel || sel.rangeCount === 0) return 0;
                        const range = sel.getRangeAt(0);
                        if (!self.el.contains(range.commonAncestorContainer)) return 0;
                        const preRange = document.createRange();
                        preRange.selectNodeContents(self.el);
                        preRange.setEnd(range.endContainer, range.endOffset);
                        return htmlToText(preRange.cloneContents()).length;
                    },
                    configurable: true,
                });
            } catch (e) { }

            this.el.getText = function () { return self.getText(); };
            this.el.setText = function (val) { return self.setText(val); };
            this.el.insertEmoji = function (emoji) { return self.insertEmoji(emoji); };
            this.el.insertHTML = function (html) { return self.insertHTML(html); };
            this.el.insertLineBreak = function () { return self.insertLineBreak(); };
            this.el.clear = function () { return self.clear(); };
            this.el.editableFocus = function (obj, after, noCollapse) { return self.editableFocus(obj, after, noCollapse); };
            this.el.setSelectionRange = function (start, end) {
                self.editableFocus(null, true);
            };

            // If element has initial text content and no HTML
            if (this.el.childNodes.length === 1 && this.el.firstChild.nodeType === Node.TEXT_NODE) {
                const initial = this.el.textContent;
                this.setText(initial);
            }
        }

        _bindEvents() {
            const self = this;
            _ensureGlobalSelectionListener();

            this.el.addEventListener('focus', function () {
                ContentEditable.lastFocused = self;
                const write = self.el.closest ? self.el.closest('#write') : null;
                if (write) {
                    write.classList.add('expanded-textarea');
                    self.el.classList.add('expanded-textarea');
                    const postButtons = write.querySelector('.post-buttons');
                    if (postButtons) postButtons.style.display = 'block';
                }
            });

            // VK smart click: clicking an emoji or mention positions the caret before or after it based on click offset
            this.el.addEventListener('mousedown', function (e) {
                if (e.target && e.target.tagName === 'IMG' && (e.target.classList.contains('emoji') || e.target.hasAttribute('alt'))) {
                    self.editableFocus(e.target, e.offsetX > 8);
                    e.preventDefault();
                    return;
                }
                const mentionEl = e.target.closest ? e.target.closest('.mention-token') : null;
                if (mentionEl) {
                    const rect = mentionEl.getBoundingClientRect();
                    const isAfter = (e.clientX - rect.left) > (rect.width / 2);
                    self.editableFocus(mentionEl, isAfter);
                    e.preventDefault();
                    return;
                }
                const write = self.el.closest ? self.el.closest('#write') : null;
                if (write) {
                    write.classList.add('expanded-textarea');
                    self.el.classList.add('expanded-textarea');
                    const postButtons = write.querySelector('.post-buttons');
                    if (postButtons) postButtons.style.display = 'block';
                }
            });

            this.el.addEventListener('click', function () {
                const write = self.el.closest ? self.el.closest('#write') : null;
                if (write) {
                    write.classList.add('expanded-textarea');
                    self.el.classList.add('expanded-textarea');
                    const postButtons = write.querySelector('.post-buttons');
                    if (postButtons) postButtons.style.display = 'block';
                }
            });

            this.el.addEventListener('blur', function () {
                self.saveRange();
                self._flushSync(false);
                if (self.hiddenInput) {
                    self.hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
                }
            });

            this.el.addEventListener('input', function () {
                self._isDirty = true;
                self._cachedText = null;
                if (self._isSyncing) return;
                self._scheduleSync();
            });

            // Clean paste with native Undo support (Ctrl+Z)
            this.el.addEventListener('paste', function (e) {
                e.preventDefault();
                const cData = e.clipboardData || window.clipboardData;
                let text = (cData && typeof cData.getData === 'function') ? cData.getData('text/plain') : '';
                if (!text) return;

                // Normalize Windows CRLF and CR to standard \n to prevent double line breaks
                text = text.replace(/\r\n/g, '\n').replace(/\r/g, '\n');

                const html = textToHtml(text);
                self.insertHTML(html);
            });

            this.el.addEventListener('keydown', function (e) {
                if (e.key === 'Backspace' || e.key === 'Delete') {
                    const sel = window.getSelection();
                    if (sel && sel.rangeCount > 0 && sel.isCollapsed) {
                        const range = sel.getRangeAt(0);
                        let targetSpan = null;
                        if (e.key === 'Backspace') {
                            if (range.startContainer.nodeType === Node.ELEMENT_NODE && range.startOffset > 0) {
                                const prevNode = range.startContainer.childNodes[range.startOffset - 1];
                                if (prevNode && prevNode.classList && prevNode.classList.contains('mention-token')) {
                                    targetSpan = prevNode;
                                }
                            } else if (range.startContainer.nodeType === Node.TEXT_NODE && range.startOffset === 0) {
                                let prev = range.startContainer.previousSibling;
                                if (prev && prev.classList && prev.classList.contains('mention-token')) {
                                    targetSpan = prev;
                                }
                            }
                        } else if (e.key === 'Delete') {
                            if (range.startContainer.nodeType === Node.ELEMENT_NODE && range.startOffset < range.startContainer.childNodes.length) {
                                const nextNode = range.startContainer.childNodes[range.startOffset];
                                if (nextNode && nextNode.classList && nextNode.classList.contains('mention-token')) {
                                    targetSpan = nextNode;
                                }
                            } else if (range.startContainer.nodeType === Node.TEXT_NODE && range.startOffset === range.startContainer.length) {
                                let next = range.startContainer.nextSibling;
                                if (next && next.classList && next.classList.contains('mention-token')) {
                                    targetSpan = next;
                                }
                            }
                        }
                        if (targetSpan) {
                            e.preventDefault();
                            targetSpan.remove();
                            self._isDirty = true;
                            self._cachedText = null;
                            self._scheduleSync();
                            return;
                        }
                    }
                }

                if (e.key === 'Enter') {
                    const isSingleLine = self.options.singleLine
                        || self.el.getAttribute('data-single-line') === 'true'
                        || (self.hiddenInput && self.hiddenInput.getAttribute('data-single-line') === 'true');
                    if (isSingleLine) {
                        e.preventDefault();
                        return;
                    }

                    if (self.options.submitOnEnter && !e.shiftKey && !e.ctrlKey) {
                        e.preventDefault();
                        self._flushSync(false);
                        if (typeof self.options.onSubmit === 'function') {
                            self.options.onSubmit(e);
                        } else {
                            const form = self.el.closest ? self.el.closest('form') : self.el.parentElement;
                            if (form) {
                                const submitBtn = form.querySelector('[type="submit"], .button_yes, button.button');
                                if (submitBtn) submitBtn.click();
                                else form.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
                            }
                        }
                        return;
                    }

                    if (e.shiftKey || !self.options.submitOnEnter) {
                        e.preventDefault();
                        self.insertLineBreak();
                    }
                }
            });
        }

        // VK-style editableFocus: positions caret before/after specific element, or at end/start of editable
        editableFocus(obj, after, noCollapse) {
            if (!this.el) return;
            this.el.focus();
            const sel = window.getSelection();
            if (!sel) return;
            const range = document.createRange();
            if (obj) {
                range.selectNode(obj);
            } else {
                range.selectNodeContents(this.el);
            }
            if (!noCollapse) {
                range.collapse(!after);
            }
            sel.removeAllRanges();
            sel.addRange(range);
            this.savedRange = range.cloneRange();
        }

        saveRange() {
            const sel = window.getSelection();
            if (sel && sel.rangeCount > 0) {
                const range = sel.getRangeAt(0);
                if (this.el.contains(range.commonAncestorContainer)) {
                    this.savedRange = range.cloneRange();
                    return this.savedRange;
                }
            }
            return null;
        }

        restoreRange() {
            const sel = window.getSelection();
            if (!sel) return null;
            let range = null;
            if (sel.rangeCount > 0) {
                const currentRange = sel.getRangeAt(0);
                if (currentRange && currentRange.commonAncestorContainer && this.el.contains(currentRange.commonAncestorContainer)) {
                    range = currentRange;
                }
            }
            if (!range && this.savedRange) {
                if (this.savedRange.commonAncestorContainer && this.el.contains(this.savedRange.commonAncestorContainer)) {
                    range = this.savedRange;
                    try {
                        sel.removeAllRanges();
                        sel.addRange(range);
                    } catch (e) { }
                } else {
                    this.savedRange = null;
                }
            }
            if (!range) {
                range = document.createRange();
                range.selectNodeContents(this.el);
                range.collapse(false);
                try {
                    sel.removeAllRanges();
                    sel.addRange(range);
                } catch (e) { }
            }
            return range;
        }

        // Native insertHTML using document.execCommand with fallback: preserves Undo stack (Ctrl+Z)
        insertHTML(html) {
            this.el.focus();
            this.restoreRange();
            let success = false;
            try {
                success = document.execCommand('insertHTML', false, html);
            } catch (e) { }

            if (!success) {
                const sel = window.getSelection();
                if (sel && sel.rangeCount > 0 && sel.getRangeAt(0).commonAncestorContainer && this.el.contains(sel.getRangeAt(0).commonAncestorContainer)) {
                    const range = sel.getRangeAt(0);
                    range.deleteContents();
                    const frag = range.createContextualFragment(html);
                    const last = frag.lastChild;
                    range.insertNode(frag);
                    if (last) {
                        range.setStartAfter(last);
                        range.setEndAfter(last);
                        range.collapse(true);
                        sel.removeAllRanges();
                        sel.addRange(range);
                    }
                } else {
                    const frag = document.createRange().createContextualFragment(html);
                    this.el.appendChild(frag);
                    const newRange = document.createRange();
                    newRange.selectNodeContents(this.el);
                    newRange.collapse(false);
                    if (sel) {
                        try {
                            sel.removeAllRanges();
                            sel.addRange(newRange);
                        } catch (e) { }
                    }
                }
            }
            this.saveRange();
            this._isDirty = true;
            this._cachedText = null;
            this._scheduleSync();
            this.el.dispatchEvent(new Event('input', { bubbles: true }));
        }

        // Line break with native Undo support (Ctrl+Z) and trailing sentinel handling
        insertLineBreak() {
            this.el.focus();
            this.restoreRange();
            let success = false;
            try {
                success = document.execCommand('insertLineBreak');
            } catch (e) { }

            if (!success) {
                const sel = window.getSelection();
                if (sel && sel.rangeCount > 0) {
                    const range = sel.getRangeAt(0);
                    range.deleteContents();

                    const br = document.createElement('br');
                    range.insertNode(br);

                    // Trailing sentinel if at end of container
                    let next = br.nextSibling;
                    while (next && next.nodeType === Node.TEXT_NODE && next.nodeValue === '') {
                        next = next.nextSibling;
                    }
                    if (!next) {
                        const sentinel = document.createElement('br');
                        br.parentNode.appendChild(sentinel);
                    }

                    range.setStartAfter(br);
                    range.setEndAfter(br);
                    range.collapse(true);
                    sel.removeAllRanges();
                    sel.addRange(range);
                }
            }

            this.saveRange();
            this._isDirty = true;
            this._cachedText = null;
            this._scheduleSync();
            this.el.dispatchEvent(new Event('input', { bubbles: true }));
        }

        insertEmoji(emoji) {
            let hex = getEmojiHex(emoji);
            if (hex === '2764FE0F') hex = '2764';
            const imgHtml = '<img class="emoji emoji_' + hex + '" src="' + BLANK_GIF + '" alt="' + escapeHtml(emoji) + '" draggable="false" contenteditable="false" />';
            this.insertHTML(imgHtml);
        }

        getText() {
            if (!this._isDirty && this._cachedText !== null) {
                return this._cachedText;
            }
            this._cachedText = htmlToText(this.el);
            this._isDirty = false;
            return this._cachedText;
        }

        setText(text) {
            const str = String(text || '').replace(/\r\n/g, '\n').replace(/\r/g, '\n');
            if (this.getText() === str) {
                if (!str && this.el.innerHTML !== '') {
                    this.el.innerHTML = '';
                    this.savedRange = null;
                    this._cachedText = '';
                    this._isDirty = false;
                    this._sync(true);
                }
                return;
            }
            this.el.innerHTML = textToHtml(str);
            this._cachedText = str;
            this._isDirty = false;
            if (!str) {
                this.savedRange = null;
            }
            if (this.hiddenInput && this.hiddenInput.value !== str) {
                this.hiddenInput.value = str;
            }
            this._sync(true);
        }

        clear() {
            this.el.innerHTML = '';
            this.savedRange = null;
            this._cachedText = '';
            this._isDirty = false;
            this._sync(true);
        }

        focus() {
            this.editableFocus(null, true);
        }

        _scheduleSync() {
            if (this._syncTimer) return;
            if (typeof requestAnimationFrame !== 'undefined') {
                this._syncTimer = requestAnimationFrame(() => {
                    this._syncTimer = null;
                    this._flushSync(false);
                });
            } else {
                this._syncTimer = setTimeout(() => {
                    this._syncTimer = null;
                    this._flushSync(false);
                }, 16);
            }
        }

        _flushSync(dispatchToEl) {
            if (this._syncTimer) {
                if (typeof cancelAnimationFrame !== 'undefined') {
                    cancelAnimationFrame(this._syncTimer);
                } else {
                    clearTimeout(this._syncTimer);
                }
                this._syncTimer = null;
            }
            this._sync(dispatchToEl);
        }

        _sync(dispatchToEl) {
            if (this._isSyncing) return;
            this._isSyncing = true;
            try {
                const text = this.getText();
                if (this.hiddenInput) {
                    const proto = this.hiddenInput.tagName === 'TEXTAREA'
                        ? HTMLTextAreaElement.prototype
                        : HTMLInputElement.prototype;
                    const nativeSetter = Object.getOwnPropertyDescriptor(proto, 'value')?.set;
                    if (nativeSetter) {
                        try { nativeSetter.call(this.hiddenInput, text); } catch (e) { this.hiddenInput.value = text; }
                    } else {
                        this.hiddenInput.value = text;
                    }
                }
                if (dispatchToEl) {
                    this.el.dispatchEvent(new Event('input', { bubbles: true }));
                }
            } finally {
                this._isSyncing = false;
            }
        }

        static isSupported() {
            if (typeof window === 'undefined' || typeof document === 'undefined') return false;
            try {
                const div = document.createElement('div');
                if (!('contentEditable' in div)) return false;
                div.contentEditable = 'true';
                if (div.contentEditable !== 'true') return false;
                if (typeof window.getSelection !== 'function') return false;
                if (typeof document.createRange !== 'function') return false;
                if (typeof Object.defineProperty !== 'function') return false;
                return true;
            } catch (e) {
                return false;
            }
        }

        /**
         * Enhances/replaces a textarea with a high-performance ContentEditable element.
         * The textarea is hidden and kept synchronized for form submissions and legacy APIs.
         */
        static enhance(textarea, options) {
            if (!ContentEditable.isSupported()) return null;
            if (!textarea || textarea._contentEnhanced) return textarea ? textarea._contentEditable : null;

            options = options || {};

            let wrap = textarea.closest ? textarea.closest('.content-editable-wrap') : null;
            if (!wrap && textarea.parentElement && textarea.parentElement.classList.contains('content-editable-wrap')) {
                wrap = textarea.parentElement;
            }
            if (!wrap) {
                wrap = document.createElement('div');
                wrap.className = 'content-editable-wrap';
                wrap.style.cssText = 'position: relative; width: 100%;';
                if (textarea.parentNode) {
                    textarea.parentNode.insertBefore(wrap, textarea);
                }
                wrap.appendChild(textarea);
            }

            const editable = document.createElement('div');
            const cleanClasses = (textarea.className || '').replace(/\bcontent-editable-fallback\b/g, '').trim();
            editable.className = 'content-editable ' + (cleanClasses || 'small-textarea');
            if (textarea.id) {
                editable.setAttribute('data-target-id', textarea.id);
            }
            if (textarea.style && textarea.style.cssText) {
                editable.style.cssText = textarea.style.cssText;
            }
            editable.style.display = '';

            const placeholder = textarea.getAttribute('placeholder') || '';
            if (placeholder) {
                editable.setAttribute('data-placeholder', placeholder);
            }

            const dataSubmit = textarea.getAttribute('data-submit-on-enter');
            const submitOnEnter = options.submitOnEnter !== undefined
                ? !!options.submitOnEnter
                : (dataSubmit === 'true' || dataSubmit === '1');

            const dataSingle = textarea.getAttribute('data-single-line');
            const singleLine = options.singleLine !== undefined
                ? !!options.singleLine
                : (dataSingle === 'true' || dataSingle === '1');
            if (singleLine) {
                editable.setAttribute('data-single-line', 'true');
            }

            // Insert editable right before textarea in DOM
            wrap.insertBefore(editable, textarea);

            // Hide textarea (it now serves as form target and fallback storage)
            textarea.style.display = 'none';
            textarea._contentEnhanced = true;

            const instance = new ContentEditable(editable, Object.assign({
                hiddenInput: textarea,
                placeholder: placeholder,
                submitOnEnter: submitOnEnter,
                singleLine: singleLine,
            }, options));

            if (textarea.value) {
                instance.setText(textarea.value);
            }

            if (textarea.classList.contains('expanded-textarea') || (wrap && wrap.closest && wrap.closest('#write.expanded-textarea'))) {
                editable.classList.add('expanded-textarea');
            }

            // Proxy textarea properties and helpers for full backwards compatibility
            try {
                const proto = textarea.tagName === 'TEXTAREA' ? HTMLTextAreaElement.prototype : HTMLInputElement.prototype;
                const nativeValDesc = Object.getOwnPropertyDescriptor(proto, 'value');
                Object.defineProperty(textarea, 'value', {
                    get: function () {
                        return instance.getText();
                    },
                    set: function (val) {
                        instance.setText(val);
                        if (nativeValDesc && nativeValDesc.set) {
                            try { nativeValDesc.set.call(textarea, val); } catch (e) { }
                        }
                    },
                    configurable: true
                });

                Object.defineProperty(textarea, 'selectionStart', {
                    get: function () { return editable.selectionStart; },
                    configurable: true
                });
                Object.defineProperty(textarea, 'selectionEnd', {
                    get: function () { return editable.selectionEnd; },
                    configurable: true
                });
            } catch (e) { }

            textarea._contentEditable = instance;
            textarea._emojiEditable = instance;
            textarea.insertEmoji = function (emoji) { return instance.insertEmoji(emoji); };
            textarea.insertHTML = function (html) { return instance.insertHTML(html); };
            textarea.insertLineBreak = function () { return instance.insertLineBreak(); };
            textarea.getText = function () { return instance.getText(); };
            textarea.setText = function (val) { return instance.setText(val); };
            textarea.clear = function () { return instance.clear(); };
            textarea.editableFocus = function (obj, after, noCollapse) { return instance.editableFocus(obj, after, noCollapse); };
            textarea.focus = function () { editable.focus(); };
            textarea.setSelectionRange = function (start, end) { instance.editableFocus(null, true); };

            return instance;
        }

        static initAll(root) {
            if (!ContentEditable.isSupported()) return;
            root = root || document;
            if (!root.querySelectorAll) return;

            // Skip if root is inside an already active editable or wrap
            if (root.closest && root.closest('.content-editable, .content-editable-wrap')) return;

            // 1. If root is a fallback textarea
            if (root.tagName === 'TEXTAREA' && (root.classList.contains('content-editable-fallback') || root.hasAttribute('data-content-editable'))) {
                ContentEditable.enhance(root);
                return;
            }

            // 2. Enhance fallback textareas
            const textareas = root.querySelectorAll('textarea.content-editable-fallback, textarea[data-content-editable]');
            for (let i = 0; i < textareas.length; i++) {
                ContentEditable.enhance(textareas[i]);
            }

            // 3. Initialize pre-rendered .content-editable divs (e.g. from Preact)
            const editables = root.querySelectorAll('.content-editable');
            for (let i = 0; i < editables.length; i++) {
                const el = editables[i];
                if (!el._contentEditable && el.tagName !== 'TEXTAREA') {
                    const submitOnEnter = el.dataset
                        ? (el.dataset.submitOnEnter === 'true' || el.dataset.submitOnEnter === '1')
                        : (el.getAttribute('data-submit-on-enter') === 'true' || el.getAttribute('data-submit-on-enter') === '1');
                    new ContentEditable(el, { submitOnEnter: !!submitOnEnter });
                }
            }
        }
    }

    ContentEditable.instances = typeof WeakMap !== 'undefined' ? new WeakMap() : {
        get: function (k) { return k._ce_instance; },
        set: function (k, v) { k._ce_instance = v; }
    };
    ContentEditable.lastFocused = null;
    ContentEditable.textToHtml = textToHtml;
    ContentEditable.htmlToText = htmlToText;

    window.ContentEditable = ContentEditable;
    window.EmojiEditable = ContentEditable; // Compatibility alias

    // Track focus for stickers.js integration
    document.addEventListener('focusin', function (e) {
        if (e.target && e.target.classList && (e.target.classList.contains('content-editable') || e.target.classList.contains('emoji-editable'))) {
            const inst = ContentEditable.instances.get(e.target);
            if (inst) ContentEditable.lastFocused = inst;
        }
    });

    // Auto-init on DOMContentLoaded
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            ContentEditable.initAll();
        });
    } else {
        ContentEditable.initAll();
    }

    // Auto-observe dynamic DOM insertions (AJAX pages, comments, modals)
    if (typeof MutationObserver !== 'undefined') {
        let _mutationBatch = [];
        let _mutationScheduled = false;

        function _processMutationBatch() {
            _mutationScheduled = false;
            const nodesToInit = _mutationBatch;
            _mutationBatch = [];

            for (let i = 0; i < nodesToInit.length; i++) {
                const node = nodesToInit[i];
                if (node.isConnected) {
                    ContentEditable.initAll(node);
                }
            }
        }

        const observer = new MutationObserver(function (mutations) {
            let hasCandidate = false;
            for (let i = 0; i < mutations.length; i++) {
                const m = mutations[i];
                // CRITICAL: Skip mutations inside any content-editable or its wrap (zero typing overhead)
                if (m.target && m.target.closest && m.target.closest('.content-editable, .content-editable-wrap')) {
                    continue;
                }

                if (m.addedNodes && m.addedNodes.length) {
                    for (let j = 0; j < m.addedNodes.length; j++) {
                        const node = m.addedNodes[j];
                        if (node.nodeType === 1) { // ELEMENT_NODE
                            if (node.closest && node.closest('.content-editable, .content-editable-wrap')) {
                                continue;
                            }
                            if (node.tagName === 'TEXTAREA' ||
                                (node.classList && (node.classList.contains('content-editable') || node.classList.contains('content-editable-fallback'))) ||
                                (node.querySelector && node.querySelector('textarea.content-editable-fallback, textarea[data-content-editable], .content-editable'))) {
                                _mutationBatch.push(node);
                                hasCandidate = true;
                            }
                        }
                    }
                }
            }

            if (hasCandidate && !_mutationScheduled) {
                _mutationScheduled = true;
                if (typeof requestAnimationFrame !== 'undefined') {
                    requestAnimationFrame(_processMutationBatch);
                } else {
                    setTimeout(_processMutationBatch, 0);
                }
            }
        });

        if (document.body) {
            observer.observe(document.body, { childList: true, subtree: true });
        } else {
            document.addEventListener('DOMContentLoaded', function () {
                if (document.body) {
                    observer.observe(document.body, { childList: true, subtree: true });
                }
            });
        }
    }

})(window);
