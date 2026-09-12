const { ChatGeneralForm } = await es6import_Im(import.meta.url, '../components/messages.js');
const { AudioAttachment } = await es6import_Im(import.meta.url, '../components/message.js');
const { html, render } = await es6import_Im(import.meta.url, '../components/render.js');
const { IMPage } = await es6import_Im(import.meta.url, './page.js');

export class MaterialsPage extends IMPage {
    constructor() {
        super();
        this.currentType = 'photo';
        this.items = [];
        this.nextFrom = '';
        this.isLoading = false;
        this.isLoadingMore = false;
        this.error = null;
        this.peer = null;
    }

    static getPageId() { return "materials"; }
    getName() { return tr("messenger_tab_materials"); }
    shouldCloseOnExit() { return true; }
    visible() { return true; }

    getPeer() {
        let peer = this.peer || this.options?.peer;
        if (peer && peer.peer) {
            peer = peer.peer;
        }
        if (!peer || !peer.id) {
            const peerId = (typeof peer === 'number' ? peer : peer?.id) || this.options?.peer_id;
            if (peerId && window.im?.cached_profiles?._findProfile) {
                peer = window.im.cached_profiles._findProfile(peerId);
            }
            if (!peer) {
                const cur = window.im?.state?.getCurrentConvo();
                peer = cur?.peer || cur;
            }
        }
        return peer;
    }

    async beforeRender(container) {
        this.addLoadSkeleton(container, true);
        this.peer = this.getPeer();
        this.currentType = this.options?.initialType || this.options?.media_type || 'photo';
        this.items = [];
        this.nextFrom = '';
        this.error = null;
        await this.load(false);
        this.removeLoadSkeleton(container);
    }

    setType(type) {
        if (this.currentType === type) return;
        this.currentType = type;
        this.items = [];
        this.nextFrom = '';
        this.error = null;
        this.load(false);
    }

    async load(isMore = false) {
        const peer = this.getPeer();
        if (!peer || !peer.id) return;

        if (isMore) {
            if (this.isLoadingMore || !this.nextFrom) return;
            this.isLoadingMore = true;
        } else {
            this.isLoading = true;
            this.error = null;
        }
        if (this.container) {
            this.render(this.container);
        }

        try {
            const params = {
                peer_id: peer.id,
                media_type: this.currentType,
                count: 40,
                photo_sizes: 1,
                extended: 1
            };
            if (isMore && this.nextFrom) {
                params.start_from = this.nextFrom;
            }

            const res = await window.OVKAPI.call('messages.getHistoryAttachments', params);

            if (res) {
                if (res.profiles || res.groups) {
                    if (window.im?.cached_profiles?._moveToProfileCache) {
                        window.im.cached_profiles._moveToProfileCache(res.profiles || [], res.groups || [], false);
                    }
                }

                const newItems = res.items || [];
                if (isMore) {
                    this.items = this.items.concat(newItems);
                    if (this.currentType === 'audio' && window.player && window.player.connectionType === '.generic_audio_list') {
                        newItems.forEach(it => {
                            const a = it.attachment?.audio;
                            if (!a) return;
                            const aId = Number(a.global_id || a.id || a.aid || 0);
                            if (!window.player.hasTrackWithId(aId)) {
                                let keys = {};
                                try {
                                    if (a.keys && typeof a.keys === 'object') keys = a.keys;
                                    else if (typeof a.keys === 'string' && a.keys.trim().startsWith('{')) keys = JSON.parse(a.keys);
                                } catch (e) { }
                                window.player.appendTrack({
                                    'id': aId,
                                    'available': true,
                                    'keys': keys,
                                    'length': Number(a.duration || a.length || 0),
                                    'url': a.manifest || a.url || '',
                                    'name': a.title || a.name || '',
                                    'performer': a.artist || a.performer || ''
                                });
                            }
                        });
                    }
                } else {
                    this.items = newItems;
                }
                this.nextFrom = res.next_from || '';
            }
        } catch (e) {
            console.error("Failed to load history attachments:", e);
            if (!isMore) {
                this.error = String(e?.message || e);
            }
        } finally {
            this.isLoading = false;
            this.isLoadingMore = false;
            if (this.container) {
                this.render(this.container);
            }
        }
    }

    onCancel(e) {
        if (e && e.preventDefault) e.preventDefault();
        const peer = this.getPeer();
        if (window.im) {
            const myTab = (window.im.tabs || []).find(t => t.render_class === this);
            if (myTab) {
                myTab.close();
            }
            window.im.openTabByName("contact", false, { peer: peer });
        }
    }

    openPhoto(e, item) {
        if (e && e.preventDefault) e.preventDefault();
        const photo = item.attachment?.photo;
        if (!photo) return;

        const photoItems = this.items
            .map(it => it.attachment?.photo)
            .filter(Boolean);

        if (typeof PhotoViewer !== 'undefined') {
            const viewer = new PhotoViewer();
            viewer.context.not_load_comments = true;
            viewer.loadAlbumContext({
                count: photoItems.length,
                items: photoItems
            }).then(() => {
                viewer.open();
                viewer.setMode("tg");
                const currentId = (typeof idForItem === 'function') ? idForItem(photo) : `${photo.owner_id}_${photo.id}`;
                viewer.afterOpen(currentId);
            });
        }
    }

    openVideo(e, item) {
        if (e && e.preventDefault) e.preventDefault();
        const video = item.attachment?.video;
        if (!video) return;

        const videoId = typeof idForItem === 'function' ? idForItem(video) : `${video.owner_id}_${video.id}`;

        if (typeof VideoViewer !== 'undefined') {
            VideoViewer.openById(videoId, {}, e);
        }
    }

    async render(container) {
        this.getNode().addClass("page-other");

        const cancelText = tr('back').toLowerCase();

        render(html`
            <div id="materials-page-im">
                <div class="mb_tabs display_flex_row display_flex_space_between materials-subtabs">
                    <div class="display_flex_row">
                        <div class="mb_tab" id=${this.currentType === 'photo' ? 'active' : ''}>
                            <a onClick=${(e) => { e.preventDefault(); this.setType('photo'); }}>${tr('att_tab_photos')}</a>
                        </div>
                        <div class="mb_tab" id=${this.currentType === 'video' ? 'active' : ''}>
                            <a onClick=${(e) => { e.preventDefault(); this.setType('video'); }}>${tr('att_tab_videos')}</a>
                        </div>
                        <div class="mb_tab" id=${this.currentType === 'audio' ? 'active' : ''}>
                            <a onClick=${(e) => { e.preventDefault(); this.setType('audio'); }}>${tr('att_tab_audios')}</a>
                        </div>
                        <div class="mb_tab" id=${this.currentType === 'doc' ? 'active' : ''}>
                            <a onClick=${(e) => { e.preventDefault(); this.setType('doc'); }}>${tr('att_tab_docs')}</a>
                        </div>
                        <div class="mb_tab" id=${this.currentType === 'link' ? 'active' : ''}>
                            <a onClick=${(e) => { e.preventDefault(); this.setType('link'); }}>${tr('att_tab_links')}</a>
                        </div>
                    </div>
                    <div>
                        <a class="materials-cancel-link" onClick=${(e) => this.onCancel(e)}>${cancelText}</a>
                    </div>
                </div>

                <div class="materials-page-content">
                    ${this.isLoading ? html`
                        <div class="materials-loader">
                            <img src="/assets/packages/static/openvk/img/loading_mini.gif" alt="..." />
                            <div>${tr('loading')}</div>
                        </div>
                    ` : this.error ? html`
                        <div class="materials-error">
                            <span>${this.error}</span>
                        </div>
                    ` : this.items.length === 0 ? html`
                        <div class="materials-empty">
                            <span>${tr('no_attachments')}</span>
                        </div>
                    ` : html`
                        ${this.currentType === 'photo' ? this.renderPhotos() : ''}
                        ${this.currentType === 'video' ? this.renderVideos() : ''}
                        ${this.currentType === 'audio' ? this.renderAudios() : ''}
                        ${this.currentType === 'doc' ? this.renderDocs() : ''}
                        ${this.currentType === 'link' ? this.renderLinks() : ''}

                        ${this.nextFrom ? html`
                            <div class="materials-load-more">
                                <button class="button ${this.isLoadingMore ? 'lagged' : ''}" disabled=${this.isLoadingMore} onClick=${() => this.load(true)}>
                                    ${this.isLoadingMore ? tr('loading') : tr('load_more')}
                                </button>
                            </div>
                        ` : ''}
                    `}
                </div>
            </div>
        `, container);
    }

    renderPhotos() {
        return html`
            <div class="materials-photos-grid">
                ${this.items.map(item => {
            const photo = item.attachment?.photo;
            if (!photo) return null;

            const thumb = (Array.isArray(photo.sizes) && photo.sizes.find(s => s.type === 'm' || s.type === 'x' || s.type === 's')?.url)
                || (Array.isArray(photo.sizes) && photo.sizes.find(s => s.type === 'm' || s.type === 'x' || s.type === 's')?.src)
                || photo.photo_130
                || photo.photo_604
                || photo.photo_75
                || (Array.isArray(photo.sizes) && photo.sizes[0]?.url)
                || (Array.isArray(photo.sizes) && photo.sizes[0]?.src)
                || photo.src
                || photo.src_big
                || photo.url
                || photo.orig_photo?.url
                || '/assets/packages/static/openvk/img/camera_200.png';

            return html`
                        <div class="materials-photo-cell" onClick=${(e) => this.openPhoto(e, item)} title="${photo.text || ''}">
                            <img
                                src="${thumb}"
                                alt=""
                                loading="lazy"
                                onError=${(e) => {
                    const current = e.target.src;
                    const fallbacks = [
                        photo.url,
                        photo.photo_604,
                        photo.photo_1280,
                        photo.orig_photo?.url,
                        photo.src_big,
                        photo.src_original,
                        Array.isArray(photo.sizes) ? photo.sizes[photo.sizes.length - 1]?.url : null
                    ].filter(Boolean);
                    const next = fallbacks.find(u => u && !current.includes(u));
                    if (next) {
                        e.target.src = next;
                    } else {
                        e.target.onerror = null;
                        e.target.src = '/assets/packages/static/openvk/img/camera_200.png';
                    }
                }}
                            />
                        </div>
                    `;
        })}
            </div>
        `;
    }

    renderVideos() {
        return html`
            <div class="materials-videos-grid">
                ${this.items.map(item => {
            const video = item.attachment?.video;
            if (!video) return null;

            const thumb = video.image?.[0]?.url || video.image?.[0]?.src || video.photo_320 || video.photo_130 || video.image_url || '/assets/packages/static/openvk/img/thumbnail_gone.jpg';
            const durStr = video.duration ? (Math.floor(video.duration / 60) + ':' + ('0' + (video.duration % 60)).slice(-2)) : '';

            return html`
                        <div class="materials-video-cell" onClick=${(e) => this.openVideo(e, item)} title="${video.title || ''}">
                            <img
                                src="${thumb}"
                                alt=""
                                loading="lazy"
                                onError=${(e) => {
                    e.target.onerror = null;
                    e.target.src = '/assets/packages/static/openvk/img/thumbnail_gone.jpg';
                }}
                            />
                            <div class="materials-play-badge"></div>
                            ${durStr ? html`<span class="materials-video-dur">${durStr}</span>` : ''}
                            <span class="materials-video-title">${video.title || 'Видеозапись'}</span>
                        </div>
                    `;
        })}
            </div>
        `;
    }

    renderAudios() {
        const seenIds = new Set();
        const uniqueAudios = [];
        this.items.forEach(item => {
            const audio = item.attachment?.audio;
            if (!audio) return;
            const audioId = Number(audio.global_id || audio.id || audio.aid || 0);
            if (!audioId || seenIds.has(audioId)) return;
            seenIds.add(audioId);
            uniqueAudios.push(audio);
        });

        return html`
            <div class="materials-audio-list generic_audio_list">
                ${uniqueAudios.map(audio => html`<${AudioAttachment} audio=${audio} />`)}
            </div>
        `;
    }

    renderDocs() {
        return html`
            <div class="materials-docs-list">
                ${this.items.map(item => {
            const doc = item.attachment?.doc;
            if (!doc) return null;
            const sizeStr = doc.size ? (doc.size > 1048576 ? (doc.size / 1048576).toFixed(1) + ' МБ' : Math.round(doc.size / 1024) + ' КБ') : '';
            const ext = doc.ext || (doc.title ? doc.title.split('.').pop() : 'DOC');
            return html`
                        <div class="materials-list-row">
                            <div class="materials-list-icon">
                                <img src="/assets/packages/static/openvk/img/oxygen-icons/16x16/mimetypes/x-office-document.png" alt="" />
                            </div>
                            <div class="materials-list-meta">
                                <a href="${doc.url}" target="_blank" class="materials-link-title">${doc.title || 'Документ'}</a>
                                <span class="materials-item-sub">${sizeStr}</span>
                            </div>
                            <a href="${doc.url}" target="_blank" class="button materials-download-btn" title=${tr('download')}>${tr('download')}</a>
                        </div>
                    `;
        })}
            </div>
        `;
    }

    renderLinks() {
        return html`
            <div class="materials-links-list">
                ${this.items.map(item => {
            const link = item.attachment?.link;
            if (!link) return null;
            const domain = link.url ? link.url.replace(/^https?:\/\//i, '').split('/')[0] : '';
            return html`
                        <div class="materials-list-row">
                            <div class="materials-list-icon">
                                <span class="mono-icon mono-icon-link"></span>
                            </div>
                            <div class="materials-list-meta">
                                <a href="${link.url}" target="_blank" class="materials-link-title">${link.title || link.url}</a>
                                <span class="materials-item-sub">${domain} ${link.description ? `— ${link.description}` : ''}</span>
                            </div>
                        </div>
                    `;
        })}
            </div>
        `;
    }
}

export function openAttachmentsModal({ peer, initialType = 'photo' } = {}) {
    if (window.im) {
        window.im.openTabByName("materials", true, { peer: peer, initialType: initialType });
    }
}

if (typeof window !== 'undefined') {
    window.openAttachmentsModal = openAttachmentsModal;
}
