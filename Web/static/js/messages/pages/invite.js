const { ChatGeneralForm } = await es6import_Im(import.meta.url, '../components/messages.js');
const { html, render } = await es6import_Im(import.meta.url, '../components/render.js');
const { IMPage } = await es6import_Im(import.meta.url, './page.js');

export const ChatInvitePreviewView = ({
    title,
    photo,
    membersCount,
    membersText,
    profiles,
    remainingCount,
    isMember,
    isLoading,
    isJoining,
    error,
    onJoin
}) => {
    if (isLoading) {
        return html`
            <div class="chat-invite-tab-wrap" style="padding: 60px 20px; text-align: center;">
                <div id="gif_loader"></div>
                <div style="margin-top: 10px; color: var(--nobold, #777); font-size: 13px;">${typeof tr === 'function' && tr("loading") ? tr("loading") : "Загрузка..."}</div>
            </div>
        `;
    }

    if (error) {
        return html`
            <div class="chat-invite-tab-wrap" style="padding: 60px 20px; text-align: center;">
                <div style="color: #d00; font-size: 14px; margin-bottom: 12px;">${error}</div>
                <a class="button" onClick=${() => { window.im?.openTabByName("conversations"); }}>
                    ${typeof tr === 'function' && tr("back") ? tr("back") : "Назад"}
                </a>
            </div>
        `;
    }

    const modalTitle = (typeof tr === 'function' ? tr("chat_invite_preview_title") : null) || "Приглашение в беседу";
    const joinBtnText = isMember
        ? ((typeof tr === 'function' ? tr("chat_invite_open_btn") : null) || "Перейти к беседе")
        : ((typeof tr === 'function' ? (tr("chat_invite_accept_btn") || tr("chat_invite_join_btn")) : null) || "Принять приглашение");

    return html`
        <div class="chat-invite-tab-page" style="padding: 36px 20px; max-width: 480px; margin: 0 auto; text-align: center;">
            <!-- Header title -->
            <h2 style="font-size: 18px; font-weight: bold; margin: 0 0 22px; color: var(--color-text, #222); letter-spacing: -0.2px;">
                ${modalTitle}
            </h2>

            <!-- Big square chat avatar -->
            <div class="chat-invite-avatar-box" style="margin-bottom: 16px; display: flex; justify-content: center;">
                <img
                    src="${photo}"
                    alt=""
                    style="width: 120px; height: 120px; object-fit: cover; border: 2px solid var(--bg-slightly-border, #d3d9de); border-radius: 0; display: block; box-shadow: 0 1px 3px rgba(0,0,0,0.08);"
                    onError=${(e) => { e.target.src = '/assets/packages/static/openvk/img/camera_200.png'; }}
                />
            </div>

            <!-- Chat title -->
            <b style="font-size: 16px; color: var(--color-text, #222); display: block; margin-bottom: 4px; word-break: break-word;">
                ${title}
            </b>

            <!-- Members count -->
            <div style="font-size: 12px; color: var(--nobold, #777); margin-bottom: 22px;">
                ${membersText}
            </div>

            <!-- Square avatars grid -->
            ${profiles && profiles.length > 0 ? html`
                <div class="chat-invite-grid-wrapper" style="display: flex; align-items: center; justify-content: center; margin-bottom: 28px;">
                    <div class="chat-invite-avatars-grid" style="display: grid; grid-template-columns: repeat(${Math.min(6, profiles.length)}, 38px); gap: 4px;">
                        ${profiles.slice(0, 12).map(p => {
        const pName = `${p.first_name || ''} ${p.last_name || ''}`.trim();
        const pAvatar = p.photo_50 || p.photo_100 || '/assets/packages/static/openvk/img/camera_50.png';
        return html`
                                <img
                                    src="${pAvatar}"
                                    title="${pName}"
                                    alt="${pName}"
                                    style="width: 38px; height: 38px; object-fit: cover; border: 1px solid var(--bg-slightly-border, #d3d9de); border-radius: 0; display: block;"
                                    onError=${(e) => { e.target.src = '/assets/packages/static/openvk/img/camera_50.png'; }}
                                />
                            `;
    })}
                    </div>
                    ${remainingCount > 0 ? html`
                        <span class="chat-invite-more-badge" style="font-size: 15px; font-weight: bold; color: var(--nobold, #555); margin-left: 10px; user-select: none;">
                            +${remainingCount}
                        </span>
                    ` : ''}
                </div>
            ` : ''}

            <div style="margin-top: 10px;">
                <button
                    class="button ${isJoining ? 'lagged' : ''}"
                    style="width: 100%; max-width: 340px; padding: 9px 20px; font-size: 13px; font-weight: 500; margin: 0 auto; display: block; background-position: 0 0px;"
                    disabled=${isJoining}
                    onClick=${onJoin}
                >
                    ${joinBtnText}
                </button>
            </div>

            ${isMember ? html`
                <div style="font-size: 12px; color: #5b88bd; margin-top: 12px;">
                    ${(typeof tr === 'function' ? tr("chat_invite_already_member") : null) || "Вы уже являетесь участником этой беседы"}
                </div>
            ` : ''}
        </div>
    `;
};

export class ChatInvitePreviewPage extends IMPage {
    constructor() {
        super();
        this.previewData = null;
        this.isLoading = false;
        this.isJoining = false;
        this.error = null;
    }

    static getPageId() { return "chat_invite"; }
    getName() { return (typeof tr === 'function' ? tr("chat_invite_preview_title") : null) || "Приглашение в беседу"; }
    getTabName() { return (typeof tr === 'function' ? tr("chat_invite_preview_title") : null) || "Приглашение в беседу"; }
    shouldCloseOnExit() { return true; }
    visible() { return true; }

    updateHeader(header) {
        return;
    }

    async beforeRender() {
        if (this.previewData == null && !this.error) {
            const joinCode = this.options.joinCode || this.options.code || (new URL(location.href)).searchParams.get("join") || (new URL(location.href)).searchParams.get("invite");
            if (!joinCode) {
                this.error = (typeof tr === 'function' ? tr("join_chat_error") : null) || "Ссылка приглашения не указана.";
                return;
            }

            this.isLoading = true;
            try {
                const res = await window.OVKAPI.call("messages.getChatPreview", {
                    link: joinCode,
                    fields: "photo_50,photo_100,photo_200,first_name,last_name"
                });

                if (res && (res.preview || res.response?.preview)) {
                    this.previewData = res.preview ? res : res.response;
                } else {
                    this.error = (typeof tr === 'function' ? tr("join_chat_error") : null) || "Не удалось загрузить информацию о беседе.";
                }
            } catch (e) {
                console.error("IM | getChatPreview error:", e);
                this.error = String(e?.message || e?.error_msg || (typeof tr === 'function' ? tr("join_chat_error") : null) || "Не удалось загрузить информацию о беседе.");
            } finally {
                this.isLoading = false;
            }
        }
    }

    async render(container) {
        this.getNode().addClass("page-other");

        const preview = this.previewData?.preview;
        const profiles = this.previewData?.profiles || [];
        const joinCode = this.options.joinCode || this.options.code || (new URL(location.href)).searchParams.get("join") || (new URL(location.href)).searchParams.get("invite");

        const title = preview?.title || (typeof tr === 'function' ? tr("chat") : null) || "Беседа";
        const photo = preview?.photo?.photo_200 || preview?.photo?.photo_100 || preview?.photo?.photo_50 || "/assets/packages/static/openvk/img/camera_200.png";
        const membersCount = Number(preview?.members_count || 0);
        const isMember = Boolean(preview?.is_member);
        const localChatId = Number(preview?.local_id || 0);
        const peerId = localChatId > 2000000000 ? localChatId : (2000000000 + localChatId);

        let membersText = "";
        if (membersCount === 1) {
            membersText = "1 участник";
        } else if (membersCount >= 2 && membersCount <= 4) {
            membersText = `${membersCount} участника`;
        } else {
            membersText = `${membersCount} участников`;
        }
        if (typeof tr === 'function') {
            if (membersCount === 1 && tr("friends_online_count_one")) {
                membersText = tr("friends_online_count_one", 1).replace("онлайн", "").trim();
            } else if (membersCount >= 2 && membersCount <= 4 && tr("friends_online_count_few")) {
                membersText = tr("friends_online_count_few", membersCount).replace("онлайн", "").trim();
            } else if (tr("friends_online_count_many")) {
                membersText = tr("friends_online_count_many", membersCount).replace("онлайн", "").trim();
            }
        }

        const displayedProfiles = profiles.slice(0, 12);
        const remainingCount = Math.max(0, membersCount - displayedProfiles.length);

        const handleJoin = async () => {
            if (this.isJoining) return;
            this.isJoining = true;
            this.update();

            try {
                if (!isMember) {
                    const joinRes = await window.OVKAPI.call("messages.joinChatByInviteLink", {
                        link: joinCode
                    });
                    const cid = joinRes?.chat_id || (joinRes?.peer_id ? (joinRes.peer_id - 2000000000) : localChatId);
                    const targetPeerId = cid > 2000000000 ? cid : (2000000000 + cid);
                    await window.im.messenger.selectConversationByPeerId(targetPeerId);
                } else {
                    await window.im.messenger.selectConversationByPeerId(peerId);
                }

                // Clean URL parameters
                try {
                    const curUrl = new URL(location.href);
                    curUrl.searchParams.delete("join");
                    curUrl.searchParams.delete("invite");
                    history.replaceState(null, "", curUrl.pathname + curUrl.search);
                } catch (e) { }

                // Close this invite tab
                const myTab = window.im?.tabs?.find(t => t.render_class === this);
                if (myTab) {
                    myTab.close();
                }
            } catch (err) {
                console.error("IM | joinChatByInviteLink error:", err);
                this.isJoining = false;
                this.update();
                fastError(String(err?.message || err?.error_msg || (typeof tr === 'function' ? tr("join_chat_error") : null) || "Не удалось присоединиться к беседе."));
            }
        };

        render(html`
            <${ChatInvitePreviewView}
                title=${title}
                photo=${photo}
                membersCount=${membersCount}
                membersText=${membersText}
                profiles=${displayedProfiles}
                remainingCount=${remainingCount}
                isMember=${isMember}
                isLoading=${this.isLoading}
                isJoining=${this.isJoining}
                error=${this.error}
                onJoin=${handleJoin}
            />
        `, container);
    }
}
