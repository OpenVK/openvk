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
            <div class="chat-invite-tab-wrap">
                <div id="gif_loader"></div>
                <div class="chat-invite-loading">${typeof tr === 'function' && tr("loading") ? tr("loading") : "Загрузка..."}</div>
            </div>
        `;
    }

    if (error) {
        return html`
            <div class="chat-invite-tab-wrap">
                <div class="chat-invite-error">${error}</div>
                <a class="button" onClick=${() => { window.im?.openTabByName("conversations"); }}>
                    ${typeof tr === 'function' && tr("back") ? tr("back") : "Назад"}
                </a>
            </div>
        `;
    }

    const modalTitle = tr("chat_invite_preview_title");
    const joinBtnText = isMember
        ? (tr("chat_invite_open_btn"))
        : (tr("chat_invite_accept_btn"));

    return html`
        <div class="chat-invite-tab-page">
            <!-- Header title -->
            <h2 class="chat-invite-title">
                ${modalTitle}
            </h2>

            <!-- Big square chat avatar -->
            <div class="chat-invite-avatar-box">
                <img
                    src="${photo}"
                    alt=""
                    class="chat-invite-avatar"
                    onError=${(e) => { e.target.onerror = null; e.target.src = '/assets/packages/static/openvk/img/camera_200.png'; }}
                />
            </div>

            <!-- Chat title -->
            <b class="chat-invite-name">
                ${title}
            </b>

            <!-- Members count -->
            <div class="chat-invite-members-count">
                ${membersText}
            </div>

            <!-- Square avatars grid -->
            ${(profiles && profiles.length > 0) || remainingCount > 0 ? html`
                <div class="chat-invite-grid-wrapper">
                    <div class="chat-invite-avatars-grid" style="grid-template-columns: repeat(${Math.min(6, (profiles ? profiles.length : 0) + (remainingCount > 0 ? 1 : 0))}, 38px);">
                        ${(profiles || []).map(p => {
                            const pName = `${p.first_name || ''} ${p.last_name || ''}`.trim();
                            const pAvatar = p.photo_50 || p.photo_100 || '/assets/packages/static/openvk/img/camera_50.png';
                            return html`
                                <img
                                    src="${pAvatar}"
                                    title="${pName}"
                                    alt="${pName}"
                                    class="chat-invite-member-ava"
                                    onError=${(e) => { e.target.onerror = null; e.target.src = '/assets/packages/static/openvk/img/camera_50.png'; }}
                                />
                            `;
                        })}
                        ${remainingCount > 0 ? html`
                            <div
                                class="chat-invite-more-badge"
                                title="+${remainingCount}"
                            >
                                +${remainingCount}
                            </div>
                        ` : ''}
                    </div>
                </div>
            ` : ''}

            <div class="chat-invite-action-box">
                <button
                    class="button chat-invite-join-btn ${isJoining ? 'lagged' : ''}"
                    disabled=${isJoining}
                    onClick=${onJoin}
                >
                    ${joinBtnText}
                </button>
            </div>

            ${isMember ? html`
                <div class="chat-invite-redirect-hint">
                    ${tr("chat_invite_already_member")}
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
    getName() { return tr("chat_invite_preview_title"); }
    getTabName() { return tr("chat_invite_preview_title"); }
    shouldCloseOnExit() { return true; }
    visible() { return true; }

    updateHeader(header) {
        return;
    }

    async beforeRender() {
        if (this.previewData == null && !this.error) {
            const joinCode = this.options.joinCode || this.options.code || (new URL(location.href)).searchParams.get("join") || (new URL(location.href)).searchParams.get("invite");
            if (!joinCode) {
                this.error = tr("join_chat_error");
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
                    this.error = tr("join_chat_error");
                }
            } catch (e) {
                console.error("IM | getChatPreview error:", e);
                this.error = String(e?.message || e?.error_msg || tr("join_chat_error"));
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

        const title = preview?.title || tr("chat");
        const photo = preview?.photo?.photo_200 || preview?.photo?.photo_100 || preview?.photo?.photo_50 || "/assets/packages/static/openvk/img/camera_200.png";
        const membersCount = Number(preview?.members_count || 0);
        const isMember = Boolean(preview?.is_member);
        const localChatId = Number(preview?.local_id || 0);
        const peerId = localChatId > 2000000000 ? localChatId : (2000000000 + localChatId);

        let membersText = tr("members_count", membersCount);

        let displayedProfiles = profiles ? profiles.slice(0) : [];
        let remainingCount = 0;

        if (membersCount > 12) {
            displayedProfiles = displayedProfiles.slice(0, 11);
            remainingCount = membersCount - displayedProfiles.length;
        } else if (membersCount > displayedProfiles.length) {
            if (displayedProfiles.length >= 11) {
                displayedProfiles = displayedProfiles.slice(0, 11);
            }
            remainingCount = membersCount - displayedProfiles.length;
        } else {
            displayedProfiles = displayedProfiles.slice(0, 12);
            remainingCount = 0;
        }

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
                fastError(String(err?.message || err?.error_msg || tr("join_chat_error")));
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
