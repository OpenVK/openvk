const { html, render } = await es6import_Im(import.meta.url, './render.js');

const AudioAttachment = ({ audio }) => {
    if (!audio) return null;
    const audioId = audio.global_id || audio.id || audio.aid || 0;
    const artist = audio.artist || audio.performer || '';
    const title = audio.title || audio.name || '';
    const duration = audio.duration || audio.length || 0;
    const durationFormatted = typeof fmtTime === 'function' ? fmtTime(duration) : `${Math.floor(duration / 60)}:${(duration % 60 < 10 ? '0' : '') + (duration % 60)}`;
    const isPlaying = window.player && window.player.current_track_id == audioId && !window.player.audioPlayer?.paused;
    let keysObj = {};
    try {
        if (audio.keys && typeof audio.keys === 'object') {
            keysObj = audio.keys;
        } else if (typeof audio.keys === 'string' && audio.keys.trim().startsWith('{')) {
            keysObj = JSON.parse(audio.keys);
        }
    } catch (e) {
        keysObj = {};
    }
    const keysStr = JSON.stringify(keysObj);
    const playUrl = audio.manifest || audio.url || '';
    const downloadUrl = audio.url || '';
    const trackName = `${artist} — ${title}`;

    return html`
        <div id="audioEmbed-${audioId}"
             data-realid="${audioId}"
             data-name="${trackName}"
             data-genre="${audio.genre_str || audio.genre || 'Other'}"
             class="audioEmbed ctx_place msg-attach-audio-player"
             data-length="${duration}"
             data-keys=${keysStr}
             data-url="${playUrl}"
             data-owner-id="${audio.owner_id || 0}">
            <audio class="audio"></audio>

            <div class="audioEntry">
                <div class="audioEntryWrapper" draggable="false">
                    <div class="playerButton">
                        <div class="playIcon ${isPlaying ? 'paused' : ''}"></div>
                    </div>

                    <div class="status">
                        <div class="mediaInfo noOverflow" title="${trackName}">
                            <div class="info">
                                <strong class="performer">
                                    <a draggable="false" href="/search?section=audios&order=listens&only_performers=on&q=${encodeURIComponent(artist)}" onClick=${(e) => e.stopPropagation()}>${artist}</a>
                                </strong>
                                <span class="tire">—</span>
                                <span draggable="false" class="title">${title}</span>
                            </div>
                        </div>
                    </div>

                    <div class="mini_timer">
                        <span class="nobold hideOnHover" data-unformatted="${duration}">${durationFormatted}</span>
                        <div class="buttons">
                            <div class="add-icon musicIcon hovermeicon" data-id="${audioId}" title="${typeof tr === 'function' ? tr('add') : 'Добавить'}" onClick=${(e) => { e.stopPropagation(); if (typeof __showAudioAddDialog === 'function') __showAudioAddDialog(Number(audioId)); }}></div>
                            ${downloadUrl ? html`<a class="download-icon musicIcon" href="${downloadUrl}" download="${artist} - ${title}.mp3" title="${typeof tr === 'function' ? tr('download') : 'Скачать'}" onClick=${(e) => e.stopPropagation()}></a>` : ''}
                        </div>
                    </div>
                </div>
                <div class="subTracks ${isPlaying ? 'shown' : ''}" draggable="false">
                    <div class="lengthTrackWrapper">
                        <div class="track lengthTrack">
                            <div class="selectableTrack">
                                <div class="selectableTrackLoadProgress">
                                    <div class="load_bar"></div>
                                </div>
                                <div class="selectableTrackSlider">
                                    <div class="slider"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="volumeTrackWrapper">
                        <div class="track volumeTrack">
                            <div class="selectableTrack">
                                <div class="selectableTrackSlider">
                                    <div class="slider"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
};

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
                                } catch (e) {}
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

        const OXYGEN_MIME = '/assets/packages/static/openvk/img/oxygen-icons/16x16';
        const tabs = [
            { id: 'photo', label: (typeof tr === 'function' ? (tr('att_tab_photos') || tr('photos_title')) : null) || 'Фотографии', iconSrc: `${OXYGEN_MIME}/mimetypes/application-x-egon.png` },
            { id: 'video', label: (typeof tr === 'function' ? (tr('att_tab_videos') || tr('videos')) : null) || 'Видеозаписи', iconSrc: `${OXYGEN_MIME}/mimetypes/application-vnd.rn-realmedia.png` },
            { id: 'audio', label: (typeof tr === 'function' ? (tr('att_tab_audios') || tr('audios')) : null) || 'Аудиозаписи', iconSrc: `${OXYGEN_MIME}/mimetypes/audio-ac3.png` },
            { id: 'doc', label: (typeof tr === 'function' ? (tr('att_tab_docs') || tr('documents')) : null) || 'Файлы', iconSrc: `${OXYGEN_MIME}/mimetypes/x-office-document.png` },
            { id: 'link', label: (typeof tr === 'function' ? (tr('att_tab_links') || tr('links')) : null) || 'Ссылки', iconSrc: `${OXYGEN_MIME}/mimetypes/application-x-srt.png` }
        ];

        render(html`
            <div class="ovk-attachments-modal-content">
                <div class="att-modal-header-bar">
                    <div class="att-modal-tabs">
                        ${tabs.map(t => html`
                            <a class="att-modal-tab ${this.currentType === t.id ? 'active' : ''}" onClick=${() => this.setType(t.id)}>
                                <img class="att-tab-icon" src="${t.iconSrc}" alt="" />
                                <span>${t.label}</span>
                            </a>
                        `)}
                    </div>
                    ${this.currentType !== 'audio' ? html`
                    <div class="att-modal-view-switcher">
                        <button class="att-view-btn ${this.viewMode === 'grid' ? 'active' : ''}" title=${tr('view_grid')} onClick=${() => this.setViewMode('grid')}>
                            <span class="mono-icon mono-icon-expand"></span>
                        </button>
                        <button class="att-view-btn ${this.viewMode === 'list' ? 'active' : ''}" title=${tr('view_list')} onClick=${() => this.setViewMode('list')}>
                            <span class="mono-icon mono-icon-list"></span>
                        </button>
                    </div>
                    ` : ''}
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
                        ${this.currentType === 'audio' ? this.renderAudioView() : (this.viewMode === 'grid' ? this.renderGridView() : this.renderListView())}

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

    renderAudioView() {
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
            <div class="att-modal-audio-list generic_audio_list">
                ${uniqueAudios.map(audio => html`<${AudioAttachment} audio=${audio} />`)}
            </div>
        `;
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
                return html`<${AudioAttachment} audio=${audio} />`;
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
                                        <img class="att-card-mime-icon" src="/assets/packages/static/openvk/img/oxygen-icons/16x16/mimetypes/x-office-document.png" alt="" />
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
                                <div class="att-card-icon link-icon">
                                    <span class="mono-icon mono-icon-link"></span>
                                </div>
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
                                    <strong class="att-card-name">${tr('voice_message')}</strong>
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
                return html`<${AudioAttachment} audio=${audio} />`;
            }

            if (doc) {
                const sizeStr = doc.size ? (doc.size > 1048576 ? (doc.size / 1048576).toFixed(1) + ' МБ' : Math.round(doc.size / 1024) + ' КБ') : '';
                const ext = doc.ext || (doc.title ? doc.title.split('.').pop() : 'DOC');
                return html`
                            <div class="att-list-row doc-row">
                                <div class="att-list-icon-w doc-icon-w">
                                    <img class="att-card-mime-icon" src="/assets/packages/static/openvk/img/oxygen-icons/16x16/mimetypes/x-office-document.png" alt="" />
                                    <span class="att-list-ext">${ext.toUpperCase().slice(0, 4)}</span>
                                </div>
                                <div class="att-list-meta">
                                    <a href="${doc.url}" target="_blank" class="att-list-main-title doc-title-link">${doc.title || 'Документ'}</a>
                                    <span class="att-list-sub">${sizeStr}</span>
                                </div>
                                <a href="${doc.url}" target="_blank" class="att-list-action-btn doc-download-btn" title=${tr('download')}>
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                                </a>
                            </div>
                        `;
            }

            if (link) {
                const domain = link.url ? link.url.replace(/^https?:\/\//i, '').split('/')[0] : '';
                return html`
                            <a href="${link.url}" target="_blank" class="att-list-row link-row">
                                <div class="att-list-icon-w link-icon-w">
                                    <span class="mono-icon mono-icon-link"></span>
                                </div>
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
                                <div class="att-card-info att-list-meta">
                                    <span class="att-list-main-title">${tr('voice_message')}</span>
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
