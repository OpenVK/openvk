import { html, render } from './render.js';
import { ChatGeneralForm } from './messages.js';
import { openAttachmentsModal } from './attachments_modal.js';
import { imLog } from '../logger.js';

export function getAppLocale() {
    if (window.openvk && window.openvk.locale) {
        const raw = window.openvk.locale.split(';')[0].split('.')[0].replace('_', '-');
        if (raw) return raw;
    }
    if (typeof tr === "function") {
        const raw = tr("__locale");
        if (raw && !raw.startsWith("@")) {
            const tag = raw.split(";")[0].split(".")[0].replace("_", "-");
            if (tag) return tag;
        }
    }
    if (window.openvk && window.openvk.lang) {
        return window.openvk.lang;
    }
    const htmlLang = document.documentElement?.lang;
    if (htmlLang) return htmlLang;
    return "ru-RU";
}

export function is24HourFormat() {
    const override = localStorage.getItem("tw.im.24h");
    if (override !== null) {
        return override === "1";
    }
    const loc = getAppLocale().toLowerCase();
    if (loc.startsWith("ru") || loc.startsWith("uk") || loc.startsWith("be") || loc.startsWith("kk")) {
        return true;
    }
    return true;
}

export function getTimeFormatOptions(withSeconds = false) {
    const is24 = is24HourFormat();
    const opts = {
        hour: "2-digit",
        minute: "2-digit",
        hour12: !is24,
        hourCycle: is24 ? "h23" : "h12",
    };
    if (withSeconds) {
        opts.second = "2-digit";
    }
    return opts;
}

export function formatTime(date, withSeconds = false) {
    if (!date) return "";
    const d = (date instanceof Date) ? date : new Date(typeof date === "number" && date < 10000000000 ? date * 1000 : date);
    if (isNaN(d.getTime())) return "";
    return d.toLocaleTimeString(getAppLocale(), getTimeFormatOptions(withSeconds));
}

export function formatDate(date, options = {}) {
    if (!date) return "";
    const d = (date instanceof Date) ? date : new Date(typeof date === "number" && date < 10000000000 ? date * 1000 : date);
    if (isNaN(d.getTime())) return "";
    return d.toLocaleDateString(getAppLocale(), options);
}

export const PeerAvatar = ({ peer, className = "", loading = "lazy", saved_messages_ava = true, orig_ava = true, size = "mid", onClick = null }) => {
    if (!peer) {
        return html`<img class="${className}" src="/assets/packages/static/openvk/img/im/chat_meaningless.jpg" loading="${loading}" onClick=${onClick} />`;
    }

    if (peer.id === window.im.state.getId()) {
        if (!saved_messages_ava && !orig_ava) {
            return html`<div class="${className}" style="display:block;width:52px;height:52px;" onClick=${onClick}></div>`;
        }

        if (!orig_ava) {
            return html`<img class="${className}" src=${ChatGeneralForm.SAVED_MESSAGES_AVATAR} loading="${loading}" onClick=${onClick} />`;
        }
    }

    if (peer.supposed_type === 'chat' && !peer.has_custom_avatar) {
        const avatars = peer.getMosaicAvatars() || [];
        const cell0 = avatars[0] || null;
        const cell1 = avatars[1] || null;
        const cell2 = avatars[2] || null;
        const cell3 = avatars[3] || null;

        if (avatars.length == 1 || (!cell0 && !cell1 && !cell2 && !cell3)) {
            return html`<img class="${className}" src="/assets/packages/static/openvk/img/im/chat_meaningless.jpg" loading="${loading}" onClick=${onClick} />`;
        }

        if (avatars.length == 2) {
            // "object-position: left;" для парных аватарочек ^_^
            return html`
            <div class="chat_table_avatar chat_table_avatar_double ${className}" onClick=${onClick}>
                ${cell0 ? html`<img style="object-position: left;" class="chat_table_avatar_cell" src="${cell0}" loading="${loading}" />` : ''}
                ${cell1 ? html`<img style="object-position: right;" class="chat_table_avatar_cell" src="${cell1}" loading="${loading}" />` : ''}
            </div>
            `;
        }

        return html`
            <div class="chat_table_avatar chat_table_avatar_more3 ${className}">
                ${cell0 ? html`<img style="height:50%;width: 50%;" class="chat_table_avatar_cell" src="${cell0}" loading="${loading}" />` : ''}
                ${cell1 ? html`<img style="height:50%;width: 50%;" class="chat_table_avatar_cell" src="${cell1}" loading="${loading}" />` : ''}
                ${cell2 ? html`<img style="height:50%;width: 50%;" class="chat_table_avatar_cell" src="${cell2}" loading="${loading}" />` : ''}
                ${cell3 ? html`<img style="height:50%;width: 50%;" class="chat_table_avatar_cell" src="${cell3}" loading="${loading}" />` : ''}
            </div>
        `;
    }

    const src = peer.getAvatar(size, orig_ava == false);
    return html`<img class="${className}" src=${src} loading="${loading}" onClick=${onClick} />`;
};

export const PeerTab = ({ conv, active, page }) => {
    return html`
        <div class="messages--peers-tab${active ? ' selected' : ''} ${!conv.isRead() ? 'unread' : ''}">
            <a onClick=${() => window.im?.messenger.selectConversation(conv)}>${conv.peer.getName(true, true)}</a>
            <span class="messages--peers-tab-counter">+${conv.unread_count}</span>
            <span class="messages--peers-tab-close" onClick=${() => window.im?.messenger.closeChat(conv, page)}>
                <div class="cross ${active ? "white" : ""}"></div>
            </span>
        </div>
    `;
};

export const PeerTabsView = ({ had_more_one_tab, tabs, currentChat, page, convo }) => {
    //if (tabs.length < 2 && had_more_one_tab) { return html`` }

    return html`
        <div class="messages--peers-header-wrap">
            <div class="messages--peers-tabs">
                ${tabs.map((tab, idx) => html`
                    <${PeerTab} conv=${tab} active=${idx === currentChat} page=${page} />
                `)}
            </div>
            <${PinnedMessageBar} convo=${convo} />
        </div>
    `;
};

export const PinnedMessageBar = ({ convo }) => {
    if (!convo || !convo.hasPinned()) return null;
    const pinMsg = convo.getPinnedMessageObject();
    if (!pinMsg) return null;

    let senderName = tr("pinned_message");
    try {
        const currentUserId = window.openvk ? window.openvk.current_id : window.im?.state?.getId();
        const senderId = pinMsg.from_id || (pinMsg.data ? pinMsg.data.from_id : null);
        const isMine = (pinMsg.isMine && pinMsg.isMine()) || (senderId && senderId === currentUserId);

        if (isMine) {
            senderName = tr("you");
        } else {
            const sender = pinMsg.sender || (window.im?.cached_profiles && window.im.cached_profiles._findCachedProfileByIdEvenIfNotCached(senderId));
            if (sender && typeof sender.getName === 'function') {
                senderName = sender.getName(false, true) || sender.getName(false);
            } else {
                senderName = tr("pinned_message");
            }
        }
    } catch (e) {
        console.error(e);
    }

    let textPreview = "";
    try {
        if (pinMsg.data && pinMsg.data.text) {
            textPreview = pinMsg.data.text;
        } else if (typeof pinMsg.getText === 'function') {
            textPreview = pinMsg.getText(true);
        }
    } catch (e) {
        textPreview = "";
    }

    if (!textPreview || textPreview.length === 0) {
        const atts = typeof pinMsg.getAttachments === 'function' ? pinMsg.getAttachments() : [];
        if (atts && atts.length > 0) {
            textPreview = "[" + (typeof tr === 'function' ? (tr("attachment") || "Вложение") : "Вложение") + "]";
        } else {
            textPreview = "...";
        }
    }

    const canUnpin = Boolean(
        (convo.peer && typeof convo.peer.can === 'function' && convo.peer.can("pin")) ||
        (convo.peer && typeof convo.peer.isAdmin === 'function' && convo.peer.isAdmin()) ||
        (pinMsg && typeof pinMsg.can === 'function' && pinMsg.can("pin"))
    );

    const handleClick = (e) => {
        e.preventDefault();
        window.im.messenger.showPinnedModal(convo);
    };

    const handleUnpin = (e) => {
        e.stopPropagation();
        window.im.messenger.unpinMessage(convo);
    };

    const titleText = (typeof tr === 'function' ? tr("pinned_message") : null) || "Закреплённое сообщение";
    const unpinTitle = (typeof tr === 'function' ? tr("unpin_message") : null) || "Открепить сообщение";
    const previewTrimmed = typeof ovk_proc_strtr === 'function' ? ovk_proc_strtr(String(textPreview), 65) : String(textPreview).substring(0, 65);

    return html`
        <div class="messenger-pinned-bar" onClick=${handleClick}>
            <div class="messenger-pinned-bar--content">
                <div class="messenger-pinned-bar--title">${titleText}</div>
                <div class="messenger-pinned-bar--text">
                    <b>${senderName}:</b> <span>${previewTrimmed}</span>
                </div>
            </div>
            ${canUnpin ? html`
                <div class="messenger-pinned-bar--close" onClick=${handleUnpin} title="${unpinTitle}">
                    <div class="cross"></div>
                </div>
            ` : ""}
        </div>
    `;
};

export const ActionsBar = ({ selectedMessages, count, onDelete, onUnselect, onReply, onForwardClick, onViewers }) => {
    if (count === 0) return null;
    let canDeleteThemAll = true;
    let canForward = count < 500;

    selectedMessages.forEach(msg => {
        if (!msg || typeof msg.can !== "function" || msg.can("delete") == false) {
            canDeleteThemAll = false;
        }
        if (!msg || typeof msg.can !== "function" || msg.can("forward") == false) {
            canForward = false;
        }
    });

    const firstMsg = selectedMessages && selectedMessages.length === 1 ? selectedMessages[0] : null;
    const canReply = firstMsg && (typeof firstMsg.can === "function" ? firstMsg.can("reply") : !firstMsg.isDeleted());
    const canViewers = firstMsg && (typeof firstMsg.can === "function" ? firstMsg.can("viewers") : false);

    return html`
        <div class="messages--actions shown">
            <div>
                <div class="message-tab-counter message-tab"><a onClick=${onUnselect}>${tr("selected_messages", count)}</a></div>
            </div>
            <div>
                ${canForward == true && html`
                <div class="message-tab"><a onClick=${onForwardClick}>${tr("forward_messages")}</a></div>
                `}
                ${count === 1 && canReply && html`
                    <div class="message-tab"><a onClick=${onReply}>${tr("reply_to_message")}</a></div>
                `}
                ${count === 1 && canViewers && html`
                    <div class="message-tab"><a onClick=${() => { if (onViewers) onViewers(firstMsg); else window.im?.messenger?.view?.onViewersButtonClick(null, firstMsg); }}>${tr("message_viewers") || "Кто прочитал"}</a></div>
                `}
                ${canDeleteThemAll == true && html`
                <div class="message-tab"><a onClick=${onDelete}>${tr("delete_message")}</a></div>
                `}
            </div>
        </div>
    `;
};

export const AttachmentMenu = () => {
    return html`
        <div>
            <a class="menu_toggler">${tr('attach')}</a>
            <div id="wallAttachmentMenu" class="up_direction hidden">
                <a class="header menu_toggler">${tr('attach')}</a>
                <div class="_wrap">
                    <a id="__photoAttachment">
                        <img src="/assets/packages/static/openvk/img/oxygen-icons/16x16/mimetypes/application-x-egon.png" />
                        ${tr('photo')}
                    </a>
                    <a id="__videoAttachment">
                        <img src="/assets/packages/static/openvk/img/oxygen-icons/16x16/mimetypes/application-vnd.rn-realmedia.png" />
                        ${tr('video')}
                    </a>
                    <a id="__audioAttachment">
                        <img src="/assets/packages/static/openvk/img/oxygen-icons/16x16/mimetypes/audio-ac3.png" />
                        ${tr('audio')}
                    </a>
                    <a id="__documentAttachment">
                        <img src="/assets/packages/static/openvk/img/oxygen-icons/16x16/mimetypes/application-octet-stream.png" />
                        ${tr('document')}
                    </a>
                    <a onClick=${(e) => typeof initGraffiti !== 'undefined' && initGraffiti(e)}>
                        <img src="/assets/packages/static/openvk/img/oxygen-icons/16x16/actions/draw-brush.png" />
                        ${tr('graffiti')}
                    </a>
                </div>
            </div>
        </div>
  `;
};

export const WriteBar = ({ convo }) => {
    if (!convo) return null;
    let cls = ["messenger-app-status"];
    const a = typeof convo.getActivityMsg === "function" ? convo.getActivityMsg() : ["", []];
    const isTyping = a && a[1] && a[1].length > 0;
    let content = "";
    let barType = "";

    if (isTyping) {
        content = a[0];
        barType = "is-typing";
        cls.push("shown");
    } else if (convo.peer && typeof convo.peer.getOfflineBarString === "function") {
        const offlineMsg = convo.peer.getOfflineBarString();
        if (offlineMsg) {
            content = offlineMsg;
            barType = "is-offline";
            cls.push("shown");
        }
    }

    /*if (!content) {
        return null;
    }*/

    return html`
        <div class="${cls.join(' ')}">
            <div class="write-bar ${barType}">
                ${content}
            </div>
        </div>
    `;
};

export const getReplySnippet = (msg) => {
    if (!msg) return "";
    let text = "";
    if (typeof msg.getText === 'function') {
        text = msg.getText(true) || "";
    } else if (msg.data?.text) {
        text = msg.data.text;
    }

    text = text.replace(/[\r\n]+/g, ' ').trim();

    if (!text && msg.data?.attachments) {
        let atts = msg.data.attachments;
        if (!Array.isArray(atts)) {
            atts = typeof atts === 'object' ? Object.values(atts) : [atts];
        }
        if (atts.length > 0 && atts[0]) {
            const t = atts[0].type;
            if (t === 'photo') text = '[' + (tr('attachment_photo') || 'Фотография') + ']';
            else if (t === 'video') text = '[' + (tr('attachment_video') || 'Видеозапись') + ']';
            else if (t === 'audio') text = '[' + (tr('attachment_audio') || 'Аудиозапись') + ']';
            else if (t === 'doc') text = '[' + (tr('attachment_doc') || 'Документ') + ']';
            else if (t === 'sticker') text = '[' + (tr('attachment_sticker') || 'Стикер') + ']';
            else text = '[' + (tr('attachment') || 'Вложение') + ']';
        }
    }

    return text;
};

export function getEmojiHex(emoji) {
    if (typeof encode_emoji === 'function') return encode_emoji(emoji);
    let hex = '';
    for (let i = 0; i < emoji.length; i++) {
        hex += emoji.charCodeAt(i).toString(16).padStart(4, '0').toUpperCase();
    }
    return hex;
}

export function getDisplayRecentSmiles() {
    const DEFAULT_SMILES = ['😊', '😃', '😉', '😄', '👍', '❤', '🔥', '😂'];
    let recents = [];
    if (typeof getRecentSmiles === 'function') {
        recents = getRecentSmiles();
    } else {
        try {
            recents = JSON.parse(localStorage.getItem('recent_smiles') || '[]');
        } catch (e) { }
    }
    const list = (recents || []).filter(s => s && s.trim()).slice(0, 8);
    for (const def of DEFAULT_SMILES) {
        if (list.length >= 8) break;
        if (!list.includes(def)) list.push(def);
    }
    return list.slice(0, 8);
}

export function onRecentSmileClick(s, e) {
    if (e && e.preventDefault) e.preventDefault();
    const target = document.querySelector('#write .content-editable')
        || (window.ContentEditable && window.ContentEditable.lastFocused ? window.ContentEditable.lastFocused.el : null)
        || document.querySelector('#write .small-textarea')
        || document.querySelector('.content-editable')
        || document.querySelector('.small-textarea');

    if (target) {
        if (typeof target.insertEmoji === 'function') {
            target.insertEmoji(s);
        } else if (target._contentEditable) {
            target._contentEditable.insertEmoji(s);
        } else {
            const start = typeof target.selectionStart !== 'undefined' ? target.selectionStart : target.value.length;
            const end = typeof target.selectionEnd !== 'undefined' ? target.selectionEnd : target.value.length;
            const val = target.value;
            target.value = val.substring(0, start) + s + val.substring(end);
            target.selectionStart = target.selectionEnd = start + s.length;
            target.focus();
            target.dispatchEvent(new Event('input', { bubbles: true }));
            target.dispatchEvent(new Event('change', { bubbles: true }));
        }
    }
    if (typeof addSmile === 'function') {
        addSmile(s);
    }
    if (typeof updateRecentSmilesInPicker === 'function') {
        updateRecentSmilesInPicker();
    }
    if (typeof window.updateRecentSmilesBar === 'function') {
        window.updateRecentSmilesBar();
    }
}

if (typeof window !== 'undefined' && !window._imRecentSmilesInit) {
    window._imRecentSmilesInit = true;
    window.updateRecentSmilesBar = () => {
        const bars = document.querySelectorAll('.im-recent-smiles-bar');
        if (!bars || bars.length === 0) return;
        const smiles = getDisplayRecentSmiles();
        bars.forEach(bar => {
            bar.innerHTML = smiles.map(s => {
                const hex = getEmojiHex(s);
                return `<span class="im-recent-smile-btn" title="${s}" data-emoji="${s}"><span class="emoji emoji_${hex}">${s}</span></span>`;
            }).join('');
        });
    };

    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.im-recent-smile-btn');
        if (btn && btn.closest('.im-recent-smiles-bar')) {
            const emoji = btn.dataset.emoji || btn.getAttribute('title');
            if (emoji) {
                onRecentSmileClick(emoji, e);
            }
        }
    });
}

export const InputArea = ({ editMsg, replyTo, onRemoveReply, onSend, onKeyPress, currentDraft, onInput, togglePeerInfo, clickOnReply, convo, forwarded_msg, onRemoveForward }) => {
    const is_editing = editMsg != null;
    const current_user = window.im.state.getOperator();
    const corresponder = window.im.state.getCurrentConvo();
    const isForwarded = forwarded_msg && forwarded_msg.length && forwarded_msg.length > 0;
    const recentSmiles = getDisplayRecentSmiles();
    const cls = [
        "messenger-app-end",
        (replyTo || editMsg || isForwarded) ? 'm-selected' : '',
        convo.hasScrollPosition() && (!editMsg && !replyTo) ? "m-mountain m-mountain-fatal" : "",
    ]

    return html`
    <div class="${cls.join(" ")}">
        ${replyTo && html`
            <div class="input-reply input-m" onclick=${(e) => {
                if (!e.target.closest('.input-close')) {
                    clickOnReply(replyTo);
                }
            }}>
                <div class="input-reply-content">
                    <span class="input-type">${tr("reply_to", replyTo.sender ? replyTo.sender.getName() : "")}:</span>
                    <span class="input-reply-text">${getReplySnippet(replyTo)}</span>
                </div>
                <span class="input-close" onClick=${(e) => {
                e.stopPropagation();
                onRemoveReply();
            }}><div class="cross"></div></span>
            </div>
        `}
        ${editMsg && html`
            <div class="input-edit input-m" onclick=${(e) => {
                if (!e.target.closest('.input-close')) {
                    clickOnReply(editMsg);
                }
            }}>
                <div class="input-reply-content">
                    <span class="input-type">${tr("edit_of_message")}:</span>
                    <span class="input-reply-text">${getReplySnippet(editMsg)}</span>
                </div>
                <span class="input-close" onClick=${(e) => {
                e.stopPropagation();
                window.im.messenger.cancelEdit();
            }}><div class="cross"></div></span>
            </div>
        `}
        ${isForwarded ? html`
            <div class="input-forward input-m">
                <span aria-label="link" class="input-type">${tr("forwarded_messages_noun", forwarded_msg.length)}</span>
                <span class="input-close" onClick=${onRemoveForward}><div class="cross"></div></span>
            </div>`
            : ""}
        <div class="messenger-mountain" onClick=${(e) => { window.im.messenger.view.scrollToEndOfChat(e, convo) }}>
            ${tr("viewing_old_messages")}
        </div>
        <div class="post-buttons">
            <div class="model_content_textarea messenger-app--input has_emoji_picker expanded-textarea" id="write">
                <img class="ava" src=${current_user.getAvatar("mid", false)} alt=${current_user.getName()} />
                <div class="messenger-app--input---messagebox">
                    <div class="textareas has_emoji_picker">
                        ${(typeof window !== 'undefined' && window.ContentEditable && typeof window.ContentEditable.isSupported === 'function' && window.ContentEditable.isSupported()) ? html`
                            <div
                                class="small-textarea content-editable"
                                contenteditable="true"
                                role="textbox"
                                aria-multiline="true"
                                data-placeholder=${tr('enter_message')}
                                onInput=${onInput}
                                onKeyDown=${onKeyPress}
                                ref=${(el) => {
                if (el && !el._contentEditable && window.ContentEditable) {
                    new window.ContentEditable(el, { submitOnEnter: true, placeholder: tr('enter_message') });
                    if (currentDraft && el.getText() !== currentDraft) {
                        el.setText(currentDraft);
                    }
                } else if (el && el._contentEditable && currentDraft != null && el.getText() !== currentDraft) {
                    el.setText(currentDraft);
                }
            }}
                            ></div>
                        ` : html`
                            <textarea
                                class="small-textarea"
                                placeholder=${tr('enter_message')}
                                value=${currentDraft}
                                onInput=${onInput}
                                onKeyDown=${onKeyPress}
                            ></textarea>
                        `}
                        <div class="emoji_picker_entrypoint"></div>
                    </div>
                    <div class="post-horizontal"></div>
                    <div class="post-vertical"></div>
                    <div class="input--messagebox-buttons">
                        <div class="input--messagebox-left">
                            <button class="button" onClick=${onSend}>${!is_editing ? tr('send') : tr('edit_action_lr')}</button>
                            <div class="im-recent-smiles-bar">
                                ${recentSmiles.map(s => html`
                                    <span
                                        class="im-recent-smile-btn"
                                        title="${s}"
                                        data-emoji="${s}"
                                        onClick=${(e) => onRecentSmileClick(s, e)}
                                    >
                                        <span class="emoji emoji_${getEmojiHex(s)}">${s}</span>
                                    </span>
                                `)}
                            </div>
                        </div>
                        <${AttachmentMenu} />
                    </div>
                </div>
                <${PeerAvatar}
                    peer=${replyTo ? replyTo.sender : corresponder.peer}
                    className="ava ava2"
                    loading="eager"
                    saved_messages_ava=${false}
                    orig_ava=${false}
                    onClick=${() => { window.im.openTabByName("contact") }} />
            </div>
        </div>
    </div>
  `;
};

export const ConversationItem = ({ conv, isForward = false, page = null }) => {
    const last_msg = conv.last_message;
    const peer = conv.peer;
    const has_activity = conv.hasActivity();
    const cls1 = ["crp-entry"];
    if (last_msg && (last_msg.data.from_id == peer.id || peer.isSavedMessages() == true)) {
        cls1.push("crp-entry-replied-same");
    }
    if (!conv.isRead()) {
        cls1.push("unread");
    }

    // здесь появился соблазн добавить && peer.data.members_count > 3 чтобы число участников показывалось только если в беседе много людей
    // с одной стороны по названию или аватарке и так понятно, что это беседа, но название и аватарка могут быть изменены
    const d = last_msg != null && has_activity == false;
    let last_sender_ava = "";
    if (last_msg && last_msg.sender) {
        try {
            last_sender_ava = last_msg.sender.getAvatar("mid", false);
        } catch (e) {
            console.error(e);
        }
    }
    return html`
        <div class="${cls1.join(' ')}" onClick=${() => window.im?.messenger.onConversationsClick(conv, isForward, page)}>
        <div style="display: flex;">
            <div class="crp-entry--image">
                <${PeerAvatar} peer=${peer} orig_ava=${false} />
            </div>
            <div class="crp-entry--info">
                <a>${ovk_proc_strtr(peer.getName(true), 30)}</a>
                <div class="crp-entry--excess">
                    ${peer.supposed_type == "chat" && peer.data.members_count ? html`<span>${tr("members_count", peer.data.members_count)}</span>` : ""}
                    ${last_msg && html`<span>${last_msg.getDate(2)}</span>`}
                </div>
            </div>
        </div>
        <div class="crp-entry--message">
            ${d && html`
            <div class="crp-entry--message---av">
                <img src="${last_sender_ava}" />
            </div>
            <div class="crp-entry--message---text">
                <span dangerouslySetInnerHTML=${{ __html: last_msg.getText(false, true, true) }} />
            </div>`}
            ${has_activity == true && html`
                <div class="crp-entry--message---av"></div>
                <div class="crp-entry--message---text">
                    ${(conv.getActivityMsg()[0] || "")}
                </div>
            `}
        </div>
        <div class="unread-msgs-count">+${conv.unread_count}</div>
        </div>
    `;
};

export const ConversationListView = ({ conversations, hasMore, onLoadMore, onCreateChat, onSearch, isForward, page, unreadMode }) => {
    const is_group = window.im.state.is_group;
    const total_convs = window.im.conversations ? Number(window.im.conversations.total_convs || conversations.length) : conversations.length;

    return html`
        <div id="conversations-top-buttons">
            ${!isForward ? html`
            <div id="conversations-search-bar">
                <input class="search_input cool" type="text" placeholder="${tr('search_messages')}" onChange=${onSearch} />
            </div>
            ${!is_group ? html`
                <input type="button" class="button excess" value="${tr('saved_messages') || 'Избранное'}" onClick=${() => {
                    const myId = window.openvk ? window.openvk.current_id : window.im?.state?.getId();
                    if (myId) {
                        window.im.messenger.selectConversationByPeerId(myId);
                    }
                }} />
                <input type="button" class="button" value="${tr('create_chat')}" onClick=${onCreateChat} />
            ` : ""}
            ` : html`
            <b>${tr("forward_messages_msg")}</b>
            <a>${tr("cancel")}</a>
            `}
        </div>
        <div class="crp-list">
            ${conversations.length > 0 ? conversations.map((conv) => html`<${ConversationItem} conv=${conv} isForward=${isForward} page=${page} />`) : html`<${ConversationsListError} unreadMode=${unreadMode} is_group=${is_group} />`}
            ${hasMore && html`
            <div onClick=${onLoadMore} id="show_more" class="crp-load-more">
                ${tr('show_next')}
            </div>
            `}
        </div>
        <div class="crp-bottom">
            <div class="crp-bottom--count">
                ${total_convs > 0 ? tr("conversations_count_title", total_convs) : ""}
            </div>
            <div class="crp-bottom--actions">
                ${isForward ? html`
                    <a onClick=${() => { window.im.messenger.onConversationsClick(window.openvk.current_id, isForward, page); }}>${tr("saved_messages")}</a>
                ` : html`
                ${!unreadMode ? html`
                    <a onClick=${() => { window.im.conversations.toggleMode("unread") }}>${tr("conversations_show_unread")}</a> |<span> </span>
                ` : html`
                    <a onClick=${() => { window.im.conversations.toggleMode("all") }}>${tr("conversations_show_all")}</a> |<span> </span>
                `}
                <a onclick=${() => { window.im.openTabByName("settings") }}>${tr("messenger_tab_settings")}</a> |<span> </span>
                <a onClick=${(event) => { imSwitchCurrent(event) }}>${tr("messenger_switch_current")}</a> |<span> </span>
                ${is_group ? html`<a onClick=${() => { window.im.openTabByName("important") }}>${tr("important_messages") || "Важное"}</a>` : "" }
                `}
            </div>
        </div>
    `;
};

export const MessagesNewInterfaceBanner = ({ onClose }) => {
    return html`
        <div class="im-new-interface-banner">
            <div class="im-new-interface-banner--mascot">
                <img src="/assets/packages/static/openvk/img/im/im_new_banner.png?v=1" alt="" />
            </div>
            <div class="im-new-interface-banner--content">
                <div class="im-new-interface-banner--title">${tr('messages_new_interface_title')}</div>
                <div class="im-new-interface-banner--text">
                    ${tr('messages_new_interface_text')}
                </div>
                <div class="im-new-interface-banner--actions">
                    <button class="button im-new-interface-banner--close-btn" onClick=${onClose}>${tr('messages_new_interface_hide')}</button>
                </div>
            </div>
        </div>
    `;
};

export const TabBar = ({ tabs, activeTab, onTabSelect }) => {
    let activeTabName = "";

    try {
        activeTabName = activeTab ? activeTab.getPageId() : "";
    } catch (e) {
        console.error(e);
    }

    const showContactButton = activeTabName == "messenger";
    let contactText = tr('about_peer');
    try {
        if (activeTabName == "messenger" && window.im.messenger.getCurrentChat().peer.supposed_type == "chat") {
            contactText = tr("about_peer_chat");
        }
    } catch (e) {
        console.error(e);
    }
    const showFriendsButton = !window.im?.state?.is_group && activeTabName != "friends";
    const showSettingsButton = false;
    const showSpecActions = showSettingsButton || showContactButton || showFriendsButton;

    const showBanner = !window.im?.state?.isFastchat && activeTabName === "conversations" && localStorage.getItem("tw.im.hide_new_interface_banner") !== "1";

    const handleDismissBanner = (e) => {
        if (e && e.preventDefault) e.preventDefault();
        localStorage.setItem("tw.im.hide_new_interface_banner", "1");
        if (window.im && window.im.updateTabs) {
            window.im.updateTabs();
        }
    };

    const sortedTabs = (tabs || []).slice(0).sort((a, b) => {
        if (a.getPageId() === "important") return 1;
        if (b.getPageId() === "important") return -1;
        return 0;
    });

    return html`
        <div class="messenger-app--tabbar-wrap">
            ${showBanner ? html`<${MessagesNewInterfaceBanner} onClose=${handleDismissBanner} />` : ""}
            <div class="messenger-app--global-tabs tabs">
                <div class="inner-tabs">
                    ${sortedTabs.map((tab) => html`
                    <a data-tab="${tab.getId()}"
                        id="${tab.isActive() ? 'activetabs' : ''}"
                        class="tab ${tab.getPageId() === 'important' ? 'tab-important' : ''}"
                        onClick=${() => onTabSelect(tab)}>
                        ${tab.getName()}
                    </a>
                    `)}
                </div>
                <div class="${showSpecActions == false ? 'hidden' : ''}" id="spec-actions">
                    ${showContactButton ? html`
                        <a onclick=${() => { window.im.openTabByName("contact") }}>${contactText}</a>
                        <span class="tab-divider">|</span>
                    ` : ''}
                    ${showSettingsButton ? html`
                        <a onclick=${() => { window.im.openTabByName("settings") }}>${tr("messenger_tab_settings")}</a>
                        <span class="tab-divider">|</span> 
                    ` : ""}
                    ${showFriendsButton ? html`<a onclick=${() => { window.im.openTabByName("friends") }}>${tr('to_friendslist')}</a>` : ""}
                </div>
            </div>
        </div>
    `;
};

export const PeerWindow = ({ fromConvo, convo, togglePeerInfo }) => {
    const peer = convo?.peer || convo;
    if (!peer) return null;

    const isChat = peer.supposed_type == "chat";
    const canEditTitle = isChat && (typeof peer.can !== 'function' || peer.can("update_title"));
    const supposed_type = peer.supposed_type;
    const isOnline = peer.online == 1;
    const avatar = peer.getAvatar ? peer.getAvatar("big") : "";
    const is_from_chat = fromConvo?.supposed_type == "chat" && peer.supposed_type != "chat";
    const is_club_related = peer.supposed_type == "club" || window.im.state.getOperator()?.supposed_type == "club";
    const members = peer.members ? peer.members.items : null;
    const currentUserId = window.openvk ? window.openvk.current_id : window.im.state.getId();
    const isChatAdmin = peer.isAdmin ? peer.isAdmin() : false;
    const membersCount = peer.members?.total_count || peer.data?.members_count || (members ? members.length : 0);

    const updateContactTab = () => {
        if (window.im.getTab("contact")?.render_class) {
            window.im.getTab("contact").render_class.update();
        }
    };

    const saveChatTitle = async () => {
        const newTitle = (peer._tempTitle ?? '').trim();
        peer._titleEditing = false;
        if (newTitle && newTitle !== (peer.getName ? peer.getName() : peer.name)) {
            await peer.updateTitle(newTitle);
        } else {
            updateContactTab();
        }
    };

    const cancelChatTitle = (e) => {
        if (e) e.preventDefault();
        peer._titleEditing = false;
        peer._tempTitle = null;
        updateContactTab();
    };

    return html`
    <div class="peer-window">
        <div class="peer-back" onClick=${(e) => { window.im.messenger.view.togglePeerInfo() }}>${tr("back")}</div>
        <div class="peer-side">
            <div class="peer-info">
                <div class="peer-avatar sliding-thing-wrapper ${!peer.hasAvatar() ? "no-avatar" : ""}">
                    <${PeerAvatar} saved_messages_ava=${false} peer=${peer} orig_ava=${true} size="big" />
                    <a onClick=${(event) => { window.OpenChatAvatar ? window.OpenChatAvatar(event, peer) : null }} class="avatar-opener sliding-thing">
                        <div class="lupa"></div>
                    </a>
                </div>
                <div class="peer-name">
                    <div class="peer-name-1">
                        ${isChat && peer._titleEditing ? html`
                            <div class="peer-title-edit-wrap">
                                <input
                                    type="text"
                                    class="peer-title-input"
                                    value=${peer._tempTitle ?? (peer.getName ? peer.getName() : (peer.name || ''))}
                                    onInput=${(e) => { peer._tempTitle = e.target.value; }}
                                    onKeyDown=${(e) => {
                if (e.key === "Enter") {
                    e.preventDefault();
                    saveChatTitle();
                } else if (e.key === "Escape") {
                    e.preventDefault();
                    cancelChatTitle(e);
                }
            }}
                                />
                                <button class="button peer-title-save-btn" onClick=${saveChatTitle}>${tr("save")}</button>
                                <a class="peer-title-cancel-btn" onClick=${cancelChatTitle} title="${tr('cancel')}"><span class="chats-close-icon"></span></a>
                            </div>
                        ` : html`
                            <a
                                class="peer-link ${isChat && canEditTitle ? 'peer-title-editable' : ''}"
                                href=${!isChat && peer.getPageUrl ? peer.getPageUrl() : '#'}
                                title=${isChat && canEditTitle ? (tr("change_chat_title") || "Нажмите, чтобы изменить название") : ""}
                                onClick=${(e) => {
                e.preventDefault();
                if (isChat) {
                    if (canEditTitle) {
                        peer._titleEditing = true;
                        peer._tempTitle = peer.getName ? peer.getName() : (peer.name || '');
                        updateContactTab();
                        setTimeout(() => {
                            const input = document.querySelector(".peer-title-input");
                            if (input) {
                                input.focus();
                                input.select();
                            }
                        }, 50);
                    }
                } else {
                    const url = peer.getPageUrl ? peer.getPageUrl() : null;
                    if (url) {
                        if (window.router && typeof window.router.route === 'function') {
                            window.router.route(url);
                        } else {
                            location.href = url;
                        }
                    }
                }
            }}
                            >
                                ${peer.getName ? peer.getName() : (peer.name || '')}
                            </a>
                        `}

                        <div class="peer-status">
                            ${peer.supposed_type == "chat" ? html`
                                <span>${tr("members_count", membersCount)}</span>
                            ` : html`
                                <span dangerouslySetInnerHTML=${{ __html: peer.getOnlineStatusString ? peer.getOnlineStatusString() : '' }} />
                            `}
                        </div>
                    </div>

                    <div class="peer-actions-1">
                        ${isChat && peer.can("update_avatar") ? html`
                            <a onClick=${(e) => { window.updateChatAvatar ? window.updateChatAvatar(e, peer) : null }}>${tr("change_chat_avatar")}</a>
                        ` : ""}
                        <a onClick=${(e) => {
            window.im.openTabByName("search", true, {
                "q": "",
                "peer_id": peer.id
            });
        }}>${tr("convo_search_messages")}</a>
                        <a onClick=${(e) => {
            e.preventDefault();
            openAttachmentsModal({ peer: peer, initialType: 'photo' });
        }}>${tr("conversation_materials") || "Материалы беседы"}</a>
                        ${convo && typeof convo.hasPinned === 'function' && convo.hasPinned() ? html`
                            <a onClick=${(e) => { window.im.messenger.viewPinned(e, convo); }}>${tr("chat_view_pinned_single")}</a>
                        ` : ""}
                        <a onClick=${(e) => {
            e.preventDefault();
            new CMessageBox({
                title: tr("clear_history") || "Очистить историю",
                body: tr("clear_history_confirm") || "Вы действительно хотите удалить всю историю сообщений в этом диалоге? Это действие нельзя отменить.",
                buttons: [tr("yes"), tr("no")],
                callbacks: [async () => {
                    try {
                        await window.OVKAPI.call("messages.deleteConversation", {
                            peer_id: peer.id
                        });
                        if (peer._chunks) {
                            peer._chunks.chunks = [];
                            peer._chunks._map = new Map();
                            peer._chunks._messagesInited = true;
                            peer._chunks._invalidateCache();
                        }
                        if (convo) {
                            convo.last_message = null;
                            convo._last_message = null;
                        }
                        window.im.openTabByName("messenger");
                        window.im.messenger.update();
                        if (window.im.conversations) {
                            window.im.conversations.update();
                        }
                        if (window.im?.event_handler && typeof window.im.event_handler.updateGlobalUnreadCounter === 'function') {
                            window.im.event_handler.updateGlobalUnreadCounter();
                        }
                    } catch (err) {
                        fastError(String(err));
                    }
                }, () => { }]
            });
        }}>${tr("clear_history") || "Очистить историю сообщений"}</a>
                        ${is_from_chat === true ? html`
                            <div class="chat-actions-usr">
                                <a><b>${tr("convo_action_kick")}</b></a>
                            </div>
                        ` : ""}
                        ${(is_club_related && peer.isClubMessagesBlocked()) ? html`
                            <div class="chat-actions-usr" onClick="${async (e) => { await peer.toggleClubMessagesBlockness(e, "enable"); }}">
                                <a>${tr("group_allow_messages")}</a>
                            </div>
                        ` : ""}
                        ${(is_club_related && !peer.isClubMessagesBlocked()) ? html`
                            <div class="chat-actions-usr" onClick="${async (e) => { await peer.toggleClubMessagesBlockness(e, "disable"); }}">
                                <a>${tr("group_deny_messages")}</a>
                            </div>
                        ` : ""}
                        ${window.im.state.is_debug ? html`
                            <a onClick=${(e) => { fastError(`<textarea>${JSON.stringify(peer.data, null, 4)}</textarea>`); }}>JSON</a>
                        ` : ""}
                    </div>
                </div>
            </div>
            <div class="peer-actions-container">
                <${PeerInviteLinkSection} peer=${peer} />

                ${peer.supposed_type == "chat" ? html`
                    <div class="peer-members-section">
                        <div class="chat-tab-2-header">
                            <b>${tr("participants") || "Участники"} (${membersCount})</b>
                            <div class="chat-header-actions">
                                ${peer.can("invite_new") ? html`
                                    <a onClick=${(e) => {
                    window.im.openTabByName("friends", true, {
                        "referrer": "add_new",
                        "convo_id": peer.id
                    })
                }}>${tr("chat_add_members_ext")}</a>
                                ` : ""}
                                ${peer.can("leave_chat") ? (!peer.isILeft() ? html`
                                    <a onClick=${(e) => {
                    e.preventDefault();
                    new CMessageBox({
                        title: tr("leave_chat") || "Покинуть чат",
                        body: tr("leave_chat_confirm") || "Вы действительно хотите покинуть эту беседу?",
                        buttons: [tr("yes"), tr("no")],
                        callbacks: [async () => {
                            try {
                                await window.OVKAPI.call("messages.removeChatUser", {
                                    "peer_id": peer.id,
                                    "user_id": currentUserId
                                });
                                window.im.openTabByName("messenger");
                                window.im.messenger.update();
                            } catch (err) {
                                fastError(String(err));
                            }
                        }, () => { }]
                    });
                }}>${tr("leave_chat")}</a>
                                ` : html`
                                    <a>${tr("return_to_chat")}</a>
                                `) : ""}
                            </div>
                        </div>
                        <div class="chat-members-list">
                            ${members && members.length > 0 ? members.map(item => {
                    const memberId = item.member_id || item.id;
                    const profile = item.profile || {};
                    const isClub = memberId < 0;
                    const memberObj = (profile && (profile.first_name || profile.name)) ? new ChatGeneralForm(profile) : null;
                    const name = memberObj ? memberObj.getName() : (profile.first_name ? `${profile.first_name} ${profile.last_name}`.trim() : (profile.name || `id${memberId}`));
                    const defaultAva = isClub ? "/assets/packages/static/openvk/img/community_100.png" : "/assets/packages/static/openvk/img/camera_200.png";
                    const avatarSrc = (memberObj && memberObj.getAvatar("mid")) || profile.photo_50 || profile.photo_100 || profile.photo_200 || defaultAva;
                    const profileUrl = isClub ? `/club${Math.abs(memberId)}` : `/id${memberId}`;
                    const isModerator = (item.is_moderator === true || item.is_moderator === 1 || (item.is_admin && !item.is_owner)) && !(item.is_owner === true || item.is_owner === 1);
                    const isOwner = (item.is_owner === true || item.is_owner === 1 || (peer.data?.admin_id == memberId && !isModerator)) && !isModerator;
                    const isAdmin = item.is_admin === true || item.is_admin === 1 || isOwner;
                    const isSelf = memberId == currentUserId;
                    const canKick = (item.can_kick || isChatAdmin) && !isSelf && !isOwner;
                    const currentMember = (members || []).find(m => (m.member_id || m.id) == currentUserId);
                    const isCurrentOwner = (currentMember && (currentMember.is_owner === true || currentMember.is_owner === 1)) || (peer.data?.admin_id == currentUserId && (!currentMember || !currentMember.is_moderator)) || isChatAdmin;
                    const canManageModerator = isCurrentOwner && !isSelf && !isOwner;

                    const toggleModerator = (e) => {
                        e.preventDefault();
                        const confirmBody = isModerator
                            ? (tr("remove_moderator_confirm", escapeHtml(name)) || `Вы действительно хотите снять полномочия модератора с ${escapeHtml(name)}?`)
                            : (tr("set_moderator_confirm", escapeHtml(name)) || `Вы действительно хотите назначить ${escapeHtml(name)} модератором этой беседы?`);

                        new CMessageBox({
                            title: tr("confirmation"),
                            body: confirmBody,
                            buttons: [tr("yes"), tr("no")],
                            callbacks: [async () => {
                                try {
                                    const method = isModerator ? "messages.removeChatModerator" : "messages.setChatModerator";
                                    await window.OVKAPI.call(method, {
                                        "peer_id": peer.id,
                                        "user_id": memberId
                                    });
                                    if (peer.members) {
                                        peer.members = null;
                                    }
                                    await peer.checkMembers();
                                    if (window.im.getTab("contact") && window.im.getTab("contact").render_class) {
                                        window.im.getTab("contact").render_class.update();
                                    }
                                } catch (err) {
                                    fastError(String(err));
                                }
                            }, () => { }]
                        });
                    };

                    let roleText = "";
                    if (isOwner) {
                        roleText = tr("chat_owner");
                    } else if (isModerator) {
                        roleText = tr("chat_moderator");
                    } else if (isAdmin) {
                        roleText = tr("chat_admin");
                    }

                    let onlineText = "";
                    if (memberObj && !isClub) {
                        onlineText = memberObj.getOnlineStatusString();
                    } else if (!isClub) {
                        if (profile.online == 1) {
                            onlineText = tr("online") || "Онлайн";
                        } else if (profile.last_seen && profile.last_seen.time) {
                            const d = new Date(profile.last_seen.time * 1000);
                            onlineText = d.toLocaleDateString();
                        }
                    }

                    return html`
                                    <div class="chat-member-item" key=${memberId}>
                                        <div class="inf">
                                            <a href=${profileUrl}>
                                                <img class="chat-member-ava" src=${avatarSrc} onError=${(e) => { e.target.src = defaultAva; }} alt="" />
                                            </a>
                                            <div class="chat-member-info">
                                                <a class="chat-member-name" href=${profileUrl}>${name}</a>
                                                ${roleText ? html`<span class="chat-member-badge">${roleText}</span>` : ""}
                                                ${onlineText ? html`<span class="chat-member-online">${onlineText}</span>` : ""}
                                            </div>
                                        </div>
                                        <div class="chat-member-actions">
                                            ${!isSelf && html`
                                                <a class="chat-member-action-btn" onClick=${async () => {
                                await window.im.messenger.selectConversationByPeerId(memberId);
                            }}>${tr("write_message") || "Написать сообщение"}</a>
                                            `}
                                            ${canManageModerator && html`
                                                <a class="chat-member-action-mod ${isModerator ? 'active' : ''}" title=${isModerator ? (tr("remove_moderator") || "Снять полномочия модератора") : (tr("set_as_moderator") || "Назначить модератором")} onClick=${toggleModerator}>
                                                    <span class="chat-mod-star-icon"></span>
                                                </a>
                                            `}
                                            ${canKick && html`
                                                <a class="chat-member-action-kick" title=${tr("remove_from_chat") || "Исключить"} onClick=${(e) => {
                                e.preventDefault();
                                new CMessageBox({
                                    title: tr("confirmation"),
                                    body: tr("kick_confirm", escapeHtml(name)),
                                    buttons: [tr("yes"), tr("no")],
                                    callbacks: [async () => {
                                        try {
                                            await window.OVKAPI.call("messages.removeChatUser", {
                                                "peer_id": peer.id,
                                                "user_id": memberId
                                            });
                                            if (peer.members) {
                                                peer.members = null;
                                            }
                                            await peer.checkMembers();
                                            if (window.im.getTab("contact") && window.im.getTab("contact").render_class) {
                                                window.im.getTab("contact").render_class.update();
                                            }
                                        } catch (err) {
                                            fastError(String(err));
                                        }
                                    }, () => { }]
                                });
                            }}><span class="chats-close-icon"></span></a>
                                            `}
                                        </div>
                                    </div>
                                `;
                }) : html`<div style="padding: 10px; color: #888;">${tr("loading") || "Загрузка..."}</div>`}
                        </div>
                    </div>
                ` : ""}
            </div>
        </div>
    </div>
    `;
};

export const PeerInfoView = ({ page, convo, togglePeerInfo }) => {
    const peer = convo.peer;

    return html`
        <div onClick=${(e) => { togglePeerInfo() }} class="messages--peers-header-peer-name">
            <img class="ava" src="${peer.getAvatar()}" />
            <span>${peer.getName()}</span>
        </div>
    `;
}

export const PeerInviteLinkSection = ({ peer }) => {
    if (!peer || peer.supposed_type !== 'chat') return null;

    if (!peer._inviteLinkState) {
        peer._inviteLinkState = {
            link: null,
            isLoading: false
        };
    }

    const fetchInviteLink = async (reset = 0) => {
        peer._inviteLinkState.isLoading = true;
        if (window.im.getTab("contact")?.render_class) {
            window.im.getTab("contact").render_class.update();
        }

        try {
            const res = await window.OVKAPI.call("messages.getInviteLink", {
                peer_id: peer.id,
                reset: reset
            });
            peer._inviteLinkState.link = (res && res.link) ? res.link : (res && res.response ? res.response.link : null);
        } catch (e) {
            console.error("Failed to get invite link:", e);
        } finally {
            peer._inviteLinkState.isLoading = false;
            if (window.im.getTab("contact")?.render_class) {
                window.im.getTab("contact").render_class.update();
            }
        }
    };

    const copyInviteLink = () => {
        if (peer._inviteLinkState.link) {
            navigator.clipboard.writeText(peer._inviteLinkState.link).then(() => {
                fastError(tr("link_copied") || "Ссылка скопирована в буфер обмена!");
            }).catch(console.error);
        }
    };

    return html`
        <div class="peer-invite-section">
            <div class="chat-tab-2-header">
                <b>${tr("convo_invite_link") || "Ссылка для приглашения"}</b>
            </div>
            <div class="peer-invite-body">
                ${peer._inviteLinkState.link ? html`
                    <div class="peer-invite-input-wrap">
                        <input type="text" readonly class="peer-invite-input" value="${peer._inviteLinkState.link}" onClick=${(e) => e.target.select()} />
                        <button class="button" onClick=${copyInviteLink}>${tr("copy") || "Скопировать"}</button>
                    </div>
                    <div class="peer-invite-reset">
                        <a onClick=${() => fetchInviteLink(1)}>${tr("reset_invite_link") || "Сбросить ссылку"}</a>
                    </div>
                ` : html`
                    <button class="button" disabled=${peer._inviteLinkState.isLoading} onClick=${() => fetchInviteLink(0)}>
                        ${peer._inviteLinkState.isLoading ? (tr("loading") || "Загрузка...") : (tr("get_invite_link") || "Получить ссылку для приглашения")}
                    </button>
                `}
            </div>
        </div>
    `;
};

export const PeerAttachmentsSection = ({ peer }) => {
    if (!peer || !peer.id) return null;

    if (!peer._attState) {
        peer._attState = {
            type: 'photo',
            items: [],
            isLoading: false,
            hasLoaded: false
        };
    }

    const loadAttachments = async (type) => {
        peer._attState.type = type;
        peer._attState.isLoading = true;
        peer._attState.hasLoaded = true;
        if (window.im.getTab("contact")?.render_class) {
            window.im.getTab("contact").render_class.update();
        }

        try {
            const res = await window.OVKAPI.call('messages.getHistoryAttachments', {
                peer_id: peer.id,
                media_type: type,
                count: 30,
                extended: 1
            });
            peer._attState.items = (res && res.items) || [];
        } catch (e) {
            console.error("Failed to load attachments:", e);
            peer._attState.items = [];
        } finally {
            peer._attState.isLoading = false;
            if (window.im.getTab("contact")?.render_class) {
                window.im.getTab("contact").render_class.update();
            }
        }
    };

    if (!peer._attState.hasLoaded) {
        loadAttachments(peer._attState.type);
    }

    const currentType = peer._attState.type;
    const items = peer._attState.items || [];
    const isLoading = peer._attState.isLoading;
    const isGrid = currentType === 'photo' || currentType === 'video';

    return html`
        <div class="peer-attachments-section">
            <div class="chat-tab-2-header">
                <b>${tr("attachments") || "Вложения"}</b>
                <div class="chat-header-actions">
                    <a class="peer-att-open-modal-btn" onClick=${(e) => {
            e.preventDefault();
            openAttachmentsModal({ peer: peer, initialType: currentType });
        }}>${tr("open_all_materials") || "Показать все"}</a>
                </div>
            </div>
            <div class="peer-att-tabs">
                <a class="peer-att-tab ${currentType === 'photo' ? 'active' : ''}" onClick=${() => loadAttachments('photo')}>${tr('photos') || 'Фото'}</a>
                <a class="peer-att-tab ${currentType === 'video' ? 'active' : ''}" onClick=${() => loadAttachments('video')}>${tr('videos') || 'Видео'}</a>
                <a class="peer-att-tab ${currentType === 'audio' ? 'active' : ''}" onClick=${() => loadAttachments('audio')}>${tr('audios') || 'Аудио'}</a>
                <a class="peer-att-tab ${currentType === 'doc' ? 'active' : ''}" onClick=${() => loadAttachments('doc')}>${tr('documents') || 'Файлы'}</a>
                <a class="peer-att-tab ${currentType === 'link' ? 'active' : ''}" onClick=${() => loadAttachments('link')}>${tr('links') || 'Ссылки'}</a>
            </div>
            <div class="peer-att-content">
                ${isLoading ? html`
                    <div class="peer-att-loader"><img src="/assets/packages/static/openvk/img/loading_mini.gif" alt="..." /></div>
                ` : (items.length === 0 ? html`
                    <div class="peer-att-empty">${tr('no_attachments') || 'Нет вложений этого типа'}</div>
                ` : (isGrid ? html`
                    <div class="peer-att-grid">
                        ${items.map(item => {
            const photo = item.attachment?.photo;
            const video = item.attachment?.video;
            if (photo) {
                const thumb = photo.sizes?.find(s => s.type === 'm' || s.type === 'x' || s.type === 's')?.url
                    || photo.sizes?.find(s => s.type === 'm' || s.type === 'x' || s.type === 's')?.src
                    || photo.sizes?.[0]?.url
                    || photo.sizes?.[0]?.src
                    || photo.photo_604
                    || photo.photo_130
                    || photo.photo_75
                    || photo.url
                    || photo.image_url
                    || '/assets/packages/static/openvk/img/camera_200.png';
                return html`
                                    <div class="peer-att-grid-item" onClick=${(e) => {
                        if (typeof PhotoViewer !== 'undefined') {
                            PhotoViewer.openById(e, `photo${photo.owner_id}_${photo.id}`);
                        }
                    }}>
                                        <img src="${thumb}" alt="" />
                                    </div>
                                `;
            }
            if (video) {
                const thumb = video.image?.[0]?.url || video.image?.[0]?.src || video.photo_320 || video.photo_130 || video.image_url || '/assets/packages/static/openvk/img/video_placeholder.png';
                const durStr = video.duration ? (Math.floor(video.duration / 60) + ':' + ('0' + (video.duration % 60)).slice(-2)) : '';
                return html`
                                    <div class="peer-att-grid-item video-item" onClick=${(e) => {
                        if (typeof VideoViewer !== 'undefined') {
                            VideoViewer.openById(`${video.owner_id}_${video.id}`, {}, e);
                        }
                    }}>
                                        <img src="${thumb}" alt="" />
                                        ${durStr ? html`<span class="peer-att-video-dur">${durStr}</span>` : ''}
                                    </div>
                                `;
            }
            return null;
        })}
                    </div>
                ` : html`
                    <div class="peer-att-list">
                        ${items.map(item => {
            const audio = item.attachment?.audio;
            const doc = item.attachment?.doc;
            const link = item.attachment?.link;

            if (audio) {
                return html`
                                    <div class="peer-att-list-row audio-row">
                                        <div class="peer-att-icon audio-icon"></div>
                                        <div class="peer-att-list-meta">
                                            <span class="peer-att-author">${audio.artist || 'Неизвестный'}</span> — <span class="peer-att-title">${audio.title || 'Без названия'}</span>
                                        </div>
                                    </div>
                                `;
            }
            if (doc) {
                const sizeStr = doc.size ? (doc.size > 1048576 ? (doc.size / 1048576).toFixed(1) + ' МБ' : Math.round(doc.size / 1024) + ' КБ') : '';
                return html`
                                    <div class="peer-att-list-row doc-row">
                                        <div class="peer-att-icon doc-icon"></div>
                                        <div class="peer-att-list-meta">
                                            <a href="${doc.url}" target="_blank" class="peer-att-link-title">${doc.title || 'Документ'}</a>
                                            <span class="peer-att-sub">${sizeStr}</span>
                                        </div>
                                    </div>
                                `;
            }
            if (link) {
                return html`
                                    <div class="peer-att-list-row link-row">
                                        <div class="peer-att-icon link-icon"></div>
                                        <div class="peer-att-list-meta">
                                            <a href="${link.url}" target="_blank" class="peer-att-link-title">${link.title || link.url}</a>
                                            <span class="peer-att-sub">${link.description || link.url}</span>
                                        </div>
                                    </div>
                                `;
            }
            return null;
        })}
                    </div>
                `))}
            </div>
        </div>
    `;
};

export const ConversationsListError = ({ unreadMode, is_group }) => {
    let text = tr("zero_conversations_error");
    if (unreadMode) { text = tr("zero_unread_conversations_error"); }
    if (is_group) { text = tr("zero_conversations_error_club"); }

    return html`
        <div class="conversations_error_page">
            <span>${text}</span>
        </div>
    `
}

export const ErrorConversation = ({ }) => {
    return html`
        <div>
            <span>Select a convo</span>
        </div>
    `
}

export const TopicConversationChat = ({ chat_id }) => {
    return html`
    <div id="chat-topic" style="margin-top: 140px;">
        <span class="t1">${tr("topic_going_in_chat")}</span>
        <div class="chat-topic-preview">
            <img src="{$chat->getPhotoURL("miniscule")}" alt="chat" />
            <div>
                <b style="display: block;">{$chat->getTitle()}</b>
                <span>сколько-то участников</span>
            </div>
        </div>
        <div class="t3">
            <input value="${tr("chat_join")}" class="button" type="button" onClick=${(event) => openChatTopic(event, chat_id)} />
        </div>
    </div>
    `;
}
