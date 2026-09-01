const { html, render } = await es6import_Im(import.meta.url, './render.js');

export class AttachmentsModalComponent {
    constructor({ peer, initialType = 'photo', onClose = null } = {}) {
        this.peer = peer;
        this.currentType = initialType || 'photo';
        this.viewMode = (this.currentType === 'photo' || this.currentType === 'video') ? 'grid' : 'list';
        this.onClose = onClose || (() => { });
        this.items = [];
        this.nextFrom = '';
        this.isLoading = false;
        this.isLoadingMore = false;
        this.error = null;
        this.container = null;
    }

    setType(type) {
        if (this.currentType === type) return;
        this.currentType = type;
        this.viewMode = (type === 'photo' || type === 'video') ? 'grid' : 'list';
        this.items = [];
        this.nextFrom = '';
        this.error = null;
        this.load(false);
    }

    setViewMode(mode) {
        if (this.viewMode === mode) return;
        this.viewMode = mode;
        this.update();
    }

    async load(isMore = false) {
        if (!this.peer || !this.peer.id) return;
        if (isMore) {
            if (this.isLoadingMore || !this.nextFrom) return;
            this.isLoadingMore = true;
        } else {
            this.isLoading = true;
            this.error = null;
        }
        this.update();

        try {
            const params = {
                peer_id: this.peer.id,
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
                        window.im.cached_profiles._moveToProfileCache(res.profiles || [], res.groups || []);
                    }
                }

                const newItems = res.items || [];
                if (isMore) {
                    this.items = this.items.concat(newItems);
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
            this.update();
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
                const currentId = idForItem(photo);
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

    openAudio(e, item) {
        if (e && e.preventDefault) e.preventDefault();
        const audio = item.attachment?.audio;
        if (!audio) return;

        if (typeof AudioViewer !== 'undefined' && AudioViewer.openById) {
            AudioViewer.openById(e, null, audio);
        } else if (typeof window.playAudio === 'function') {
            window.playAudio(audio);
        }
    }

    render(container) {
        this.container = container;
        this.load(false);
    }

    update() {
        if (!this.container) return;

        const tabs = [
            { id: 'photo', label: (typeof tr === 'function' ? (tr('att_tab_photos') || tr('photos_title')) : null) || 'Фотографии', icon: 'photo' },
            { id: 'video', label: (typeof tr === 'function' ? (tr('att_tab_videos') || tr('videos')) : null) || 'Видеозаписи', icon: 'video' },
            { id: 'audio', label: (typeof tr === 'function' ? (tr('att_tab_audios') || tr('audios')) : null) || 'Аудиозаписи', icon: 'audio' },
            { id: 'doc', label: (typeof tr === 'function' ? (tr('att_tab_docs') || tr('documents')) : null) || 'Файлы', icon: 'doc' },
            { id: 'link', label: (typeof tr === 'function' ? (tr('att_tab_links') || tr('links')) : null) || 'Ссылки', icon: 'link' }
        ];

        render(html`
            <div class="ovk-attachments-modal-content">
                <div class="att-modal-header-bar">
                    <div class="att-modal-tabs">
                        ${tabs.map(t => html`
                            <a class="att-modal-tab ${this.currentType === t.id ? 'active' : ''}" onClick=${() => this.setType(t.id)}>
                                ${t.label}
                            </a>
                        `)}
                    </div>
                    <div class="att-modal-view-switcher">
                        <button class="att-view-btn ${this.viewMode === 'grid' ? 'active' : ''}" title="Сетка" onClick=${() => this.setViewMode('grid')}>
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                        </button>
                        <button class="att-view-btn ${this.viewMode === 'list' ? 'active' : ''}" title="Список" onClick=${() => this.setViewMode('list')}>
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line><line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line></svg>
                        </button>
                    </div>
                </div>

                <div class="att-modal-body-scroll">
                    ${this.isLoading ? html`
                        <div class="att-modal-loader">
                            <div id="gif_loader"></div>
                            <span>${(typeof tr === 'function' ? tr('loading') : null) || 'Загрузка материалов...'}</span>
                        </div>
                    ` : this.error ? html`
                        <div class="att-modal-empty error">
                            <span>${this.error}</span>
                        </div>
                    ` : this.items.length === 0 ? html`
                        <div class="att-modal-empty">
                            <div class="att-empty-icon"></div>
                            <span>${(typeof tr === 'function' ? tr('no_attachments') : null) || 'Вложений этого типа не найдено'}</span>
                        </div>
                    ` : html`
                        ${this.viewMode === 'grid' ? this.renderGridView() : this.renderListView()}

                        ${this.nextFrom ? html`
                            <div class="att-modal-load-more">
                                <button class="button ${this.isLoadingMore ? 'loading' : ''}" disabled=${this.isLoadingMore} onClick=${() => this.load(true)}>
                                    ${this.isLoadingMore ? ((typeof tr === 'function' ? tr('loading') : null) || 'Загрузка...') : ((typeof tr === 'function' ? tr('load_more') : null) || 'Показать ещё')}
                                </button>
                            </div>
                        ` : ''}
                    `}
                </div>
            </div>
        `, this.container);
    }

    renderGridView() {
        return html`
            <div class="att-modal-grid">
                ${this.items.map(item => {
            const photo = item.attachment?.photo;
            const video = item.attachment?.video;
            const audio = item.attachment?.audio;
            const doc = item.attachment?.doc;
            const link = item.attachment?.link;
            const voice = item.attachment?.audio_message || item.attachment?.audiomessage;

            if (photo) {
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
                            <div class="att-grid-cell photo-cell" onClick=${(e) => this.openPhoto(e, item)} title="${photo.text || ''}">
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
                            e.target.src = '/assets/packages/static/openvk/img/camera_200.png';
                        }
                    }}
                                />
                                <div class="att-cell-overlay"></div>
                            </div>
                        `;
            }

            if (video) {
                const thumb = video.image?.[0]?.url || video.image?.[0]?.src || video.photo_320 || video.photo_130 || video.image_url || '/assets/packages/static/openvk/img/video_placeholder.png';
                const durStr = video.duration ? (Math.floor(video.duration / 60) + ':' + ('0' + (video.duration % 60)).slice(-2)) : '';
                return html`
                            <div class="att-grid-cell video-cell" onClick=${(e) => this.openVideo(e, item)} title="${video.title || ''}">
                                <img
                                    src="${thumb}"
                                    alt=""
                                    loading="lazy"
                                    onError=${(e) => {
                        e.target.src = '/assets/packages/static/openvk/img/video_placeholder.png';
                    }}
                                />
                                <div class="att-play-badge"></div>
                                ${durStr ? html`<span class="att-video-dur">${durStr}</span>` : ''}
                                <span class="att-grid-title">${video.title || 'Видеозапись'}</span>
                            </div>
                        `;
            }

            if (audio) {
                const durStr = audio.duration ? (Math.floor(audio.duration / 60) + ':' + ('0' + (audio.duration % 60)).slice(-2)) : '';
                return html`
                            <div class="att-grid-cell card-cell audio-card" onClick=${(e) => this.openAudio(e, item)}>
                                <div class="att-card-icon audio-icon"></div>
                                <div class="att-card-info">
                                    <strong class="att-card-name">${audio.title || 'Аудио'}</strong>
                                    <span class="att-card-sub">${audio.artist || 'Исполнитель'}</span>
                                </div>
                                ${durStr ? html`<span class="att-card-dur">${durStr}</span>` : ''}
                            </div>
                        `;
            }

            if (doc) {
                const sizeStr = doc.size ? (doc.size > 1048576 ? (doc.size / 1048576).toFixed(1) + ' МБ' : Math.round(doc.size / 1024) + ' КБ') : '';
                const ext = doc.ext || (doc.title ? doc.title.split('.').pop() : 'DOC');
                const isImageDoc = ['jpg', 'jpeg', 'png', 'gif'].includes(ext.toLowerCase());
                const previewUrl = doc.preview?.photo?.sizes?.find(s => s.type === 'm')?.src || doc.photo_130;

                return html`
                            <a href="${doc.url}" target="_blank" class="att-grid-cell card-cell doc-card" title="${doc.title}">
                                ${isImageDoc && previewUrl ? html`
                                    <img src="${previewUrl}" class="att-doc-img-preview" alt="" />
                                ` : html`
                                    <div class="att-card-icon doc-icon">
                                        <span class="att-ext-badge">${ext.toUpperCase().slice(0, 4)}</span>
                                    </div>
                                `}
                                <div class="att-card-info">
                                    <strong class="att-card-name">${doc.title || 'Документ'}</strong>
                                    <span class="att-card-sub">${sizeStr}</span>
                                </div>
                            </a>
                        `;
            }

            if (link) {
                const domain = link.url ? link.url.replace(/^https?:\/\//i, '').split('/')[0] : '';
                return html`
                            <a href="${link.url}" target="_blank" class="att-grid-cell card-cell link-card" title="${link.title || link.url}">
                                <div class="att-card-icon link-icon"></div>
                                <div class="att-card-info">
                                    <strong class="att-card-name">${link.title || link.url}</strong>
                                    <span class="att-card-sub">${domain}</span>
                                </div>
                            </a>
                        `;
            }

            if (voice) {
                const durStr = voice.duration ? (Math.floor(voice.duration / 60) + ':' + ('0' + (voice.duration % 60)).slice(-2)) : '';
                return html`
                            <div class="att-grid-cell card-cell voice-card" onClick=${() => {
                        const audioEl = new Audio(voice.link_mp3 || voice.link_ogg);
                        audioEl.play();
                    }}>
                                <div class="att-card-icon voice-icon"></div>
                                <div class="att-card-info">
                                    <strong class="att-card-name">Голосовое сообщение</strong>
                                    <span class="att-card-sub">${durStr || '0:00'}</span>
                                </div>
                            </div>
                        `;
            }

            return null;
        })}
            </div>
        `;
    }

    renderListView() {
        return html`
            <div class="att-modal-list">
                ${this.items.map(item => {
            const photo = item.attachment?.photo;
            const video = item.attachment?.video;
            const audio = item.attachment?.audio;
            const doc = item.attachment?.doc;
            const link = item.attachment?.link;
            const voice = item.attachment?.audio_message || item.attachment?.audiomessage;

            if (photo) {
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
                const dateStr = photo.date ? new Date(photo.date * 1000).toLocaleDateString() : '';
                return html`
                            <div class="att-list-row photo-row" onClick=${(e) => this.openPhoto(e, item)}>
                                <div class="att-list-thumb">
                                    <img
                                        src="${thumb}"
                                        alt=""
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
                            e.target.src = '/assets/packages/static/openvk/img/camera_200.png';
                        }
                    }}
                                    />
                                </div>
                                <div class="att-list-meta">
                                    <span class="att-list-main-title">${photo.text || 'Фотография'}</span>
                                    ${dateStr ? html`<span class="att-list-sub">${dateStr}</span>` : ''}
                                </div>
                            </div>
                        `;
            }

            if (video) {
                const thumb = video.image?.[0]?.url || video.photo_130 || '/assets/packages/static/openvk/img/video_placeholder.png';
                const durStr = video.duration ? (Math.floor(video.duration / 60) + ':' + ('0' + (video.duration % 60)).slice(-2)) : '';
                return html`
                            <div class="att-list-row video-row" onClick=${(e) => this.openVideo(e, item)}>
                                <div class="att-list-thumb video-thumb">
                                    <img
                                        src="${thumb}"
                                        alt=""
                                        onError=${(e) => {
                        e.target.src = '/assets/packages/static/openvk/img/video_placeholder.png';
                    }}
                                    />
                                    ${durStr ? html`<span class="att-thumb-dur">${durStr}</span>` : ''}
                                </div>
                                <div class="att-list-meta">
                                    <span class="att-list-main-title">${video.title || 'Видеозапись'}</span>
                                    <span class="att-list-sub">${video.description || (video.views != null ? `${video.views} просмотров` : '')}</span>
                                </div>
                            </div>
                        `;
            }

            if (audio) {
                const durStr = audio.duration ? (Math.floor(audio.duration / 60) + ':' + ('0' + (audio.duration % 60)).slice(-2)) : '';
                return html`
                            <div class="att-list-row audio-row" onClick=${(e) => this.openAudio(e, item)}>
                                <div class="att-list-icon-w audio-icon-w"></div>
                                <div class="att-list-meta">
                                    <span class="att-list-main-title"><strong>${audio.artist || 'Неизвестный'}</strong> — ${audio.title || 'Без названия'}</span>
                                </div>
                                ${durStr ? html`<span class="att-list-right-dur">${durStr}</span>` : ''}
                            </div>
                        `;
            }

            if (doc) {
                const sizeStr = doc.size ? (doc.size > 1048576 ? (doc.size / 1048576).toFixed(1) + ' МБ' : Math.round(doc.size / 1024) + ' КБ') : '';
                const ext = doc.ext || (doc.title ? doc.title.split('.').pop() : 'DOC');
                return html`
                            <div class="att-list-row doc-row">
                                <div class="att-list-icon-w doc-icon-w">
                                    <span class="att-list-ext">${ext.toUpperCase().slice(0, 4)}</span>
                                </div>
                                <div class="att-list-meta">
                                    <a href="${doc.url}" target="_blank" class="att-list-main-title doc-title-link">${doc.title || 'Документ'}</a>
                                    <span class="att-list-sub">${sizeStr}</span>
                                </div>
                                <a href="${doc.url}" target="_blank" class="att-list-action-btn doc-download-btn" title="Скачать">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                                </a>
                            </div>
                        `;
            }

            if (link) {
                const domain = link.url ? link.url.replace(/^https?:\/\//i, '').split('/')[0] : '';
                return html`
                            <a href="${link.url}" target="_blank" class="att-list-row link-row">
                                <div class="att-list-icon-w link-icon-w"></div>
                                <div class="att-list-meta">
                                    <span class="att-list-main-title">${link.title || link.url}</span>
                                    <span class="att-list-sub">${domain} ${link.description ? `— ${link.description}` : ''}</span>
                                </div>
                                <div class="att-list-action-btn link-arrow-btn">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="7" y1="17" x2="17" y2="7"></line><polyline points="7 7 17 7 17 17"></polyline></svg>
                                </div>
                            </a>
                        `;
            }

            if (voice) {
                const durStr = voice.duration ? (Math.floor(voice.duration / 60) + ':' + ('0' + (voice.duration % 60)).slice(-2)) : '0:00';
                return html`
                            <div class="att-list-row voice-row" onClick=${() => {
                        const audioEl = new Audio(voice.link_mp3 || voice.link_ogg);
                        audioEl.play();
                    }}>
                                <div class="att-list-icon-w voice-icon-w"></div>
                                <div class="att-list-meta">
                                    <span class="att-list-main-title">Голосовое сообщение</span>
                                    <span class="att-list-sub">${durStr}</span>
                                </div>
                            </div>
                        `;
            }

            return null;
        })}
            </div>
        `;
    }
}

export function openAttachmentsModal({ peer, initialType = 'photo' } = {}) {
    if (!peer || !peer.id) return;

    const modalTitle = (typeof tr === 'function' ? tr('conversation_materials') : null) || 'Материалы беседы';

    const modal = new CMessageBox({
        title: modalTitle,
        body: `<div id="ovk_attachments_modal_root"></div>`,
        buttons: [(typeof tr === 'function' ? tr('close') : null) || 'Закрыть'],
        callbacks: [() => { }]
    });

    const bodyNode = modal.getNode().find("#ovk_attachments_modal_root");
    const container = bodyNode && bodyNode.nodes ? bodyNode.nodes[0] : null;

    const rootNode = modal.getNode();
    if (rootNode && rootNode.nodes && rootNode.nodes[0]) {
        rootNode.nodes[0].style.width = "1024px";
        rootNode.nodes[0].style.maxWidth = "96vw";
    }

    const modalWindow = modal.getNode().find(".ovk-modal-window, .ovk-diag");
    if (modalWindow && modalWindow.nodes && modalWindow.nodes[0]) {
        modalWindow.nodes[0].style.maxWidth = "1024px";
        modalWindow.nodes[0].style.width = "100%";
    }

    const component = new AttachmentsModalComponent({
        peer: peer,
        initialType: initialType,
        onClose: () => modal.close()
    });

    if (container) {
        component.render(container);
    }

    return modal;
}

if (typeof window !== 'undefined') {
    window.openAttachmentsModal = openAttachmentsModal;
}
