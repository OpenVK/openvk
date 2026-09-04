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

    function textToHtml(text) {
        if (!text) return '';
        const lines = String(text).split('\n');
        const htmlLines = lines.map(function (line) {
            const escaped = escapeHtml(line);
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
        let text = '';
        function walk(n) {
            if (n.nodeType === Node.TEXT_NODE) {
                text += n.nodeValue;
            } else if (n.nodeType === Node.ELEMENT_NODE) {
                const tag = n.tagName.toUpperCase();
                if (tag === 'IMG' && (n.classList.contains('emoji') || n.hasAttribute('alt'))) {
                    text += n.getAttribute('alt') || '';
                } else if (tag === 'BR') {
                    text += '\n';
                } else if (tag === 'DIV' || tag === 'P') {
                    if (text.length > 0 && !text.endsWith('\n')) text += '\n';
                    for (let i = 0; i < n.childNodes.length; i++) walk(n.childNodes[i]);
                    if (!text.endsWith('\n')) text += '\n';
                } else {
                    for (let i = 0; i < n.childNodes.length; i++) walk(n.childNodes[i]);
                }
            }
        }
        for (let i = 0; i < node.childNodes.length; i++) {
            walk(node.childNodes[i]);
        }
        if (text.endsWith('\n') && !text.endsWith('\n\n')) {
            text = text.slice(0, -1);
        }
        return text;
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
            }, options || {});

            this.savedRange = null;
            this.hiddenInput = this.options.hiddenInput;
            this._isSyncing = false;

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

            // Expose properties on DOM node for textarea compatibility
            const self = this;
            try {
                Object.defineProperty(this.el, 'value', {
                    get: function () { return self.getText(); },
                    set: function (val) { self.setText(val); },
                    configurable: true,
                });
            } catch (e) { }

            this.el.getText = function () { return self.getText(); };
            this.el.setText = function (val) { return self.setText(val); };
            this.el.insertEmoji = function (emoji) { return self.insertEmoji(emoji); };
            this.el.clear = function () { return self.clear(); };

            // If element has initial text content and no HTML
            if (this.el.childNodes.length === 1 && this.el.firstChild.nodeType === Node.TEXT_NODE) {
                const initial = this.el.textContent;
                this.setText(initial);
            }
        }

        _bindEvents() {
            const self = this;

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
            });

            document.addEventListener('selectionchange', function () {
                const sel = window.getSelection();
                if (sel && sel.rangeCount > 0) {
                    const range = sel.getRangeAt(0);
                    if (self.el.contains(range.commonAncestorContainer)) {
                        self.savedRange = range.cloneRange();
                    }
                }
            });

            this.el.addEventListener('input', function () {
                if (self._isSyncing) return;
                self._sync(false);
            });

            this.el.addEventListener('paste', function (e) {
                e.preventDefault();
                const cData = e.clipboardData || window.clipboardData;
                const text = (cData && typeof cData.getData === 'function') ? cData.getData('text/plain') : '';
                if (!text) return;

                const html = textToHtml(text);
                self.el.focus();
                const sel = window.getSelection();
                if (!sel || sel.rangeCount === 0) return;

                let range = sel.getRangeAt(0);
                if (!self.el.contains(range.commonAncestorContainer)) {
                    range = self.savedRange || document.createRange();
                    if (!self.savedRange) {
                        range.selectNodeContents(self.el);
                        range.collapse(false);
                    }
                }

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
                self.saveRange();
                self._sync(true);
            });

            this.el.addEventListener('keydown', function (e) {
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
                        // Insert clean <br>
                        e.preventDefault();
                        const sel = window.getSelection();
                        if (sel && sel.rangeCount > 0) {
                            const range = sel.getRangeAt(0);
                            range.deleteContents();
                            const br = document.createElement('br');
                            range.insertNode(br);
                            range.setStartAfter(br);
                            range.setEndAfter(br);
                            range.collapse(true);
                            sel.removeAllRanges();
                            sel.addRange(range);
                            self.saveRange();
                            self._sync(true);
                        }
                    }
                }
            });
        }

        saveRange() {
            const sel = window.getSelection();
            if (sel && sel.rangeCount > 0) {
                const range = sel.getRangeAt(0);
                if (this.el.contains(range.commonAncestorContainer)) {
                    this.savedRange = range.cloneRange();
                }
            }
        }

        insertEmoji(emoji) {
            this.el.focus();
            let range = null;
            const sel = window.getSelection();

            if (sel && sel.rangeCount > 0) {
                const currentRange = sel.getRangeAt(0);
                if (this.el.contains(currentRange.commonAncestorContainer)) {
                    range = currentRange;
                }
            }

            if (!range && this.savedRange) {
                range = this.savedRange;
                if (sel) {
                    sel.removeAllRanges();
                    sel.addRange(range);
                }
            }

            if (!range) {
                range = document.createRange();
                range.selectNodeContents(this.el);
                range.collapse(false);
                if (sel) {
                    sel.removeAllRanges();
                    sel.addRange(range);
                }
            }

            range.deleteContents();

            let hex = getEmojiHex(emoji);
            if (hex === '2764FE0F') hex = '2764';

            const img = document.createElement('img');
            img.className = 'emoji emoji_' + hex;
            img.src = BLANK_GIF;
            img.alt = emoji;
            img.draggable = false;
            img.setAttribute('contenteditable', 'false');

            range.insertNode(img);
            range.setStartAfter(img);
            range.setEndAfter(img);
            range.collapse(true);

            if (sel) {
                sel.removeAllRanges();
                sel.addRange(range);
            }

            this.saveRange();
            this._sync(true);
        }

        getText() {
            return htmlToText(this.el);
        }

        setText(text) {
            const str = String(text || '');
            if (this.getText() === str) return;
            this.el.innerHTML = textToHtml(str);
            if (this.hiddenInput && this.hiddenInput.value !== str) {
                this.hiddenInput.value = str;
            }
        }

        clear() {
            this.el.innerHTML = '';
            this._sync(true);
        }

        focus() {
            this.el.focus();
        }

        _sync(dispatchToEl) {
            if (this._isSyncing) return;
            this._isSyncing = true;
            try {
                const text = this.getText();
                if (this.hiddenInput) {
                    try {
                        const proto = this.hiddenInput.tagName === 'TEXTAREA'
                            ? HTMLTextAreaElement.prototype
                            : HTMLInputElement.prototype;
                        const nativeSetter = Object.getOwnPropertyDescriptor(proto, 'value')?.set;
                        if (nativeSetter) {
                            nativeSetter.call(this.hiddenInput, text);
                        } else {
                            this.hiddenInput.value = text;
                        }
                    } catch (e) {
                        this.hiddenInput.value = text;
                    }
                    this.hiddenInput.textContent = text;
                    this.hiddenInput.dispatchEvent(new Event('change', { bubbles: false }));
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

            // Expose helpers on textarea for backwards compatibility with OpenVK scripts
            textarea._contentEditable = instance;
            textarea._emojiEditable = instance;
            textarea.insertEmoji = function (emoji) { return instance.insertEmoji(emoji); };
            textarea.getText = function () { return instance.getText(); };
            textarea.setText = function (val) { return instance.setText(val); };
            textarea.clear = function () { return instance.clear(); };

            textarea.focus = function () {
                editable.focus();
            };

            return instance;
        }

        static initAll(root) {
            if (!ContentEditable.isSupported()) return;
            root = root || document;
            if (!root.querySelectorAll) return;

            // 1. If root is a fallback textarea
            if (root.tagName === 'TEXTAREA' && (root.classList.contains('content-editable-fallback') || root.hasAttribute('data-content-editable'))) {
                ContentEditable.enhance(root);
                return;
            }

            // 2. Enhance fallback textareas
            const textareas = root.querySelectorAll('textarea.content-editable-fallback, textarea[data-content-editable], .content-editable-wrap > textarea');
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
        const observer = new MutationObserver(function (mutations) {
            for (let i = 0; i < mutations.length; i++) {
                const m = mutations[i];
                if (m.addedNodes && m.addedNodes.length) {
                    for (let j = 0; j < m.addedNodes.length; j++) {
                        const node = m.addedNodes[j];
                        if (node.nodeType === 1) {
                            ContentEditable.initAll(node);
                        }
                    }
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
