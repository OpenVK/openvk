class playersSearcher {
    constructor(context_type, context_id) {
        this.context_type = context_type
        this.context_id = context_id
        this.searchType = "by_name"
        this.query = ""
        this.page = 1
        this.successCallback = () => {}
        this.errorCallback = () => {}
        this.beforesendCallback = () => {}
        this.clearContainer = () => {}
    }

    execute() {
        $.ajax({
            type: "POST",
            url: "/audios/context",
            data: {
                context: this.context_type,
                hash: u("meta[name=csrf]").attr("value"),
                page: this.page,
                query: this.query,
                context_entity: this.context_id,
                type: this.searchType,
                returnPlayers: 1,
            },
            beforeSend: () => {
                this.beforesendCallback()
            },
            error: () => {
                this.errorCallback()
            },
            success: (response) => {
                this.successCallback(response, this)
            }
        })
    }

    movePage(page) {
        this.page = page
        this.execute()
    }
}

window.player = new class {
    context = {
        object: {},
        pagesCount: 0,
        count: 0,
        playedPages: [],
    }

    __linked_player_id = null
    current_track_id = 0
    tracks = []

    get timeType() {
        return localStorage.getItem('audio.timeType') ?? 0
    }

    set timeType(value) {
        localStorage.setItem('audio.timeType', value)
    }

    get audioPlayer() {
        return this.__realAudioPlayer
    }

    get linkedInlinePlayer() {
        if(!this.__linked_player_id) {
            return null;
        }

        return u('#' + this.__linked_player_id)
    }

    get ajaxPlayer() {
        return u('#ajax_audio_player')
    }

    get uiPlayer() {
        return u('.bigPlayer')
    }

    get currentTrack() {
        return this.__findTrack(this.current_track_id)
    }

    get previousTrack() {
        const current = this.__findTrack(this.current_track_id, true)
        return this.__findByIndex(current - 1)
    }

    get nextTrack() {
        const current = this.__findTrack(this.current_track_id, true)
        return this.__findByIndex(current + 1) 
    }

    async init(input_context) {
        let context = Object.assign({
            url: location.pathname + location.search
        }, input_context)
        this.context.object = !input_context ? null : context
        this.__realAudioPlayer = document.createElement("audio")
        this.dashPlayer = dashjs.MediaPlayer().create()

        await this.loadContext(input_context ? context.page : 0)
        this.initEvents()
        this.__setMediaSessionActions()
    }

    initEvents() {
        this.audioPlayer.ontimeupdate = () => {
            const current_track = this.currentTrack
            if(!current_track) {
                return
            }
            
            const time = this.audioPlayer.currentTime
            const ps = ((time * 100) / current_track.length).toFixed(3)
            this.uiPlayer.find(".time").html(fmtTime(time))
            this.__updateTime(time)

            if (ps <= 100) {
                this.uiPlayer.find(".track .selectableTrack .slider").attr('style', `left:${ ps}%`);

                if(this.linkedInlinePlayer) {
                    this.linkedInlinePlayer.find(".subTracks .lengthTrackWrapper .slider").attr('style', `left:${ ps}%`)
                    this.linkedInlinePlayer.find('.mini_timer .nobold').html(fmtTime(time))
                }

                if(this.ajaxPlayer) {
                    this.ajaxPlayer.find('#aj_player_track_length .slider').attr('style', `left:${ ps}%`)
                    this.ajaxPlayer.find('#aj_player_track_name #aj_time').html(fmtTime(time))
                }
            }
        }

        this.audioPlayer.onvolumechange = () => {
            const volume = this.audioPlayer.volume;
            const ps = Math.ceil((volume * 100) / 1);

            if (ps <= 100) {
                this.uiPlayer.find(".volumePanel .selectableTrack .slider").attr('style', `left:${ ps}%`);
                
                if(this.linkedInlinePlayer) {
                    this.linkedInlinePlayer.find(".subTracks .volumeTrackWrapper .slider").attr('style', `left:${ ps}%`)
                }

                if(this.ajaxPlayer) {
                    this.ajaxPlayer.find('#aj_player_volume .slider').attr('style', `left:${ ps}%`)
                }
            }
            
            localStorage.setItem('audio.volume', volume)
        }

        this.audioPlayer.onprogress = (e) => {
            u('.loaded_chunk').remove()

            const buffered = this.audioPlayer.buffered
            if (buffered.length > 0) {
                this.listen_coef += 1.25
                const end = buffered.end(buffered.length - 1)
                const percentage = (end / window.player.audioPlayer.duration) * 100
                if(this.uiPlayer.length > 0) {
                    this.uiPlayer.find('.track .selectableTrackLoadProgress .load_bar').attr('style', `width:${percentage.toFixed(2)}%`)
                }

                if(this.linkedInlinePlayer) {
                    this.linkedInlinePlayer.find('.lengthTrackWrapper .selectableTrackLoadProgress .load_bar').attr('style', `width:${percentage.toFixed(2)}%`)
                }
            }

            if(window.player.listen_coef > 10) {
                this.__countListen()
                window.player.listen_coef = -10
            }
        }
        
        this.audioPlayer.onended = async (e) => {
            e.preventDefault()

            if(!this.nextTrack && window.player.context.playedPages.indexOf(1) == -1) {
                this.loadContext(1, false)
                await this.setTrack(this.__findByIndex(0).id)
            } else {
                this.playNextTrack()
            }

            if(this.linkedInlinePlayer && this.connectionType) {
                const parent = this.linkedInlinePlayer.closest(this.connectionType)
                const real_current = parent.find(`.audioEmbed[data-realid='${this.current_track_id}']`)
                u('.audioEntry .playerButton .playIcon.paused').removeClass('paused')
                u('.audioEntry .subTracks.shown').removeClass('shown')
                real_current.find('.subTracks').addClass('shown')
                this.linkPlayer(real_current)
            }
        }

        this.audioPlayer.volume = Number(localStorage.getItem('audio.volume') ?? 1)
    }

    async loadContext(page = 1, after = true) {
        if(!this.context.object) {
            return
        }
        
        const form_data = new FormData
        switch(this.context.object.name) {
            case 'entity_audios':
                form_data.append('context', this.context.object.name)
                form_data.append('context_entity', this.context.object.entity_id)
                break
            case 'playlist_context':
                form_data.append('context', this.context.object.name)
                form_data.append('context_entity', this.context.object.entity_id)
                break
            case 'classic_search_context':
                // tidi riwriti
                form_data.append('context', this.context.object.name)
                form_data.append('context_entity', JSON.stringify({
                    'order': this.context.object.order,
                    'invert': this.context.object.invert,
                    'genre': this.context.object.genre,
                    'only_performers': this.context.object.only_performers,
                    'with_lyrics': this.context.object.with_lyrics,
                    'query': this.context.object.query,
                }))
                break
            case 'alone_audio':
                form_data.append('context', this.context.object.name)
                form_data.append('context_entity', this.context.object.entity_id)
        }

        form_data.append('page', page)
        form_data.append("hash", u("meta[name=csrf]").attr("value"))
        this.context.playedPages.push(page)

        const req = await fetch('/audios/context', {
            method: 'POST',
            body: form_data
        })
        const res = await req.json()
        if(!res.success) {
            makeError(tr("unable_to_load_queue"))
            return
        }
        this.context.pagesCount = res.pagesCount
        this.context.count = res.count
        this.__appendTracks(res.items, after)
    }

    linkPlayer(node) {
        this.__linked_player_id = node.attr('id')
        u(this.audioPlayer).trigger('volumechange')
    }

    async setTrack(id) {
        if(!this.tracks || this.tracks.length < 1) {
            makeError('Context is not loaded yet', 'Red', 5000, 1489)
            return
        }

        if(window.__current_page_audio_context && (!this.context.object || this.context.object.url != location.pathname + location.search)) {
            console.log('Audio | Resetting context because of ajax :3')
            
            this.__renewContext()
            await this.loadContext(window.__current_page_audio_context.page ?? 1)
            if(!isNaN(parseInt(location.hash.replace('#', '')))) {
                const adp = parseInt(location.hash.replace('#', ''))
                await this.loadContext(adp)
            } else if((new URL(location.href)).searchParams.p) {
                const adp = (new URL(location.href)).searchParams.p
                await this.loadContext(adp)
            }

            this.__updateFace()
        }

        this.listen_coef = 0.0
        const old_id = this.current_track_id
        this.current_track_id = id
        const c_track = this.currentTrack
        if(!c_track) {
            this.current_track_id = old_id
            makeError('Error playing audio: track not found')
            return
        }
        
        const protData = {
            "org.w3.clearkey": {
                "clearkeys": c_track.keys
            }
        };

        u('.nowPlaying').removeClass('nowPlaying')
        this.__highlightActiveTrack()

        navigator.mediaSession.setPositionState({
            duration: this.currentTrack.length
        })
        this.__updateMediaSession()
        this.dashPlayer.initialize(this.audioPlayer, c_track.url, false);
        this.dashPlayer.setProtectionData(protData)

        if(!this.nextTrack && Math.max(...this.context["playedPages"]) < this.context["pagesCount"]) {
            await this.loadContext(Number(Math.max(...this.context["playedPages"])) + 1, true)
        }

        if(!this.previousTrack && (Math.min(...this.context["playedPages"]) > 1)) {
            await this.loadContext(Math.min(...this.context["playedPages"]) - 1, false)
        }

        this.is_closed = false
        this.__updateFace()
        u(this.audioPlayer).trigger('volumechange')
    }

    switchTracks(id1, id2) {
        const first_audio = this.__findTrack(id1)
        const first_audio_index = this.__findTrack(id1, true)
        const second_audio = this.__findTrack(id2)
        const second_audio_index = this.__findTrack(id2, true)

        this.tracks[first_audio_index] = second_audio
        this.tracks[second_audio_index] = first_audio
        this.__updateFace()
    }

    appendTrack(object, after = true) {
        this.__appendTracks([object], after)
    }

    hasTrackWithId(id) {
        return this.__findTrack(id, true) != -1
    }

    async play() {
        if(!this.currentTrack) {
            return
        }

        document.querySelectorAll('audio').forEach(el => el.pause())

        await this.audioPlayer.play()
        this.__setFavicon()
        this.__updateFace()
        navigator.mediaSession.playbackState = "playing"
    }

    pause() {
        if(!this.currentTrack) {
            return
        }

        this.audioPlayer.pause()
        this.__setFavicon('paused')
        this.__updateFace()
        navigator.mediaSession.playbackState = "paused"
    }

    async playPreviousTrack() {
        if(!this.currentTrack || !this.previousTrack) {
            return
        }

        await this.setTrack(this.previousTrack.id)
        if(!this.currentTrack.available || this.currentTrack.withdrawn) {
            if(!this.previousTrack) {
                return
            }
            
            this.playPreviousTrack()
        }

        await this.play()
    }
    
    async playNextTrack() {
        if(!this.currentTrack || !this.nextTrack) {
            return
        }

        await this.setTrack(this.nextTrack.id)
        if(!this.currentTrack.available || this.currentTrack.withdrawn) {
            if(!this.nextTrack) {
                return
            }

            this.playNextTrack()
        }

        await this.play()
    }

    // fake shuffle
    async shuffle() {
        this.tracks.sort(() => Math.random() - 0.59)
        await this.setTrack(this.tracks.at(0).id)
        await this.play()
    }

    isAtAudiosPage() {
        return u('.bigPlayer').length > 0
    }

    // Добавляем ощущение продуманности.
    __highlightActiveTrack() {
        u(`.audiosContainer .audioEmbed[data-realid='${this.current_track_id}'] .audioEntry, .audios_padding .audioEmbed[data-realid='${this.current_track_id}'] .audioEntry`).addClass('nowPlaying')
    }

    __renewContext() {
        let context = Object.assign({
            url: location.pathname + location.search
        }, window.__current_page_audio_context)
        this.pause()
        this.__resetContext()
        this.context.object = context
    }

    __resetContext() {
        this.context = {
            object: {},
            pagesCount: 0,
            count: 0,
            playedPages: [],
        }
        this.tracks = []
        //this.__realAudioPlayer = document.createElement("audio")
        this.listen_coef = 0
    }

    async _handlePageTransition() {
        console.log('Audio | Switched page :3')
        const state = this.isAtAudiosPage()
        if(!state) {
            // AJAX audio player
            if(this.is_closed) {
                return
            }

            this.ajaxPlayer.removeClass('hidden')
            if(this.ajaxPlayer.length > 0) {
                return
            } else {
                if(this.audioPlayer.paused) {
                    return
                }
                this.ajCreate()
                this.__updateFace()
            }
        } else {
            this.ajClose(false)
            this.is_closed = false
            if(this.tracks.length < 1) {
                if(window.__current_page_audio_context) {
                    await this.init(window.__current_page_audio_context)
                }
            }
        }
        
        this.__linked_player_id = null
        if(this.currentTrack) {
            this.__updateFace()
        }
        this.__highlightActiveTrack()
        u(this.audioPlayer).trigger('volumechange')
    }

    __setFavicon(state = 'playing') {
        if(state == 'playing') {
            document.querySelector('link[rel="icon"], link[rel="shortcut icon"]').setAttribute("href", "/assets/packages/static/openvk/img/favicons/favicon24_paused.png")
        } else {
            document.querySelector('link[rel="icon"], link[rel="shortcut icon"]').setAttribute("href", "/assets/packages/static/openvk/img/favicons/favicon24_playing.png")
        }
    }

    __findTrack(id, return_index = false) {
        if(return_index) {
            return this.tracks.indexOf(this.tracks.find(item => item.id == id))
        }

        return this.tracks.find(item => item.id == id)
    }

    __findByIndex(index) {
        return this.tracks[index]
    }

    __setMediaSessionActions() {
        navigator.mediaSession.setActionHandler('play', async () => { 
            await window.player.play()
        });
        navigator.mediaSession.setActionHandler('pause', () => { 
            window.player.pause() 
        });
        navigator.mediaSession.setActionHandler('previoustrack', async () => { await window.player.playPreviousTrack() });
        navigator.mediaSession.setActionHandler('nexttrack', async () => { await window.player.playNextTrack() });
        navigator.mediaSession.setActionHandler("seekto", async (details) => {
            window.player.audioPlayer.currentTime = details.seekTime
        });
    }

    __appendTracks(list, after = true) {
        if(after) {
            this.tracks = this.tracks.concat(list)
        } else {
            this.tracks = list.concat(this.tracks)
        }
    }

    __updateFace() {
        // Во второй раз перепутал next и back, но фиксить смысла уже нет.
        const _c = this.currentTrack
        const prev_button = this.uiPlayer.find('.nextButton')
        const next_button = this.uiPlayer.find('.backButton')

        if(!this.previousTrack) {
            prev_button.addClass('lagged')
            if(this.ajaxPlayer.length > 0) {
                this.ajaxPlayer.find('#aj_player_previous').addClass('lagged')
            }
        } else {
            prev_button.removeClass('lagged')
            prev_button.attr('data-title', ovk_proc_strtr(escapeHtml(this.previousTrack.name), 50))
            if(this.ajaxPlayer.length > 0) {
                this.ajaxPlayer.find('#aj_player_previous').removeClass('lagged')
            }
        }

        if(!this.nextTrack) {
            next_button.addClass('lagged')
            if(this.ajaxPlayer.length > 0) {
                this.ajaxPlayer.find('#aj_player_next').addClass('lagged')
            }
        } else {
            next_button.removeClass('lagged')
            next_button.attr('data-title', ovk_proc_strtr(escapeHtml(this.nextTrack.name), 50))
            if(this.ajaxPlayer.length > 0) {
                this.ajaxPlayer.find('#aj_player_next').removeClass('lagged')
            }
        }

        if(!this.audioPlayer.paused) {
            this.uiPlayer.find('.playButton').addClass('pause')
            if(this.linkedInlinePlayer) {
                this.linkedInlinePlayer.find('.playerButton .playIcon').addClass('paused')
            }
            if(this.ajaxPlayer.length > 0) {
                this.ajaxPlayer.find('#aj_player_play_btn').addClass('paused')
            }
        } else {
            this.uiPlayer.find('.playButton').removeClass('pause')
            if(this.linkedInlinePlayer) {
                this.linkedInlinePlayer.find('.playerButton .playIcon').removeClass('paused')
            }
            if(this.ajaxPlayer.length > 0) {
                this.ajaxPlayer.find('#aj_player_play_btn').removeClass('paused')
            }
        }

        if(_c) {
            this.uiPlayer.find('.trackInfo .trackName span').html(escapeHtml(_c.name))
            this.uiPlayer.find('.trackInfo .trackPerformers').html('')
            const performers = _c.performer.split(', ')
            const lastPerformer = performers[performers.length - 1]
            performers.forEach(performer => {
                this.uiPlayer.find('.trackInfo .trackPerformers').append(
                    `<a href='/search?section=audios&order=listens&only_performers=on&q=${encodeURIComponent(performer.escapeHtml())}'>${performer.escapeHtml()}${(performer != lastPerformer ? ', ' : '')}</a>`)
            })
        } else {
            this.uiPlayer.find('.trackInfo .trackName span').html(tr('track_noname'))
            this.uiPlayer.find('.trackInfo .trackPerformers').html(`<a>${tr('track_unknown')}</a>`)
        }

        if(this.ajaxPlayer.length > 0) {
            this.ajaxPlayer.find('#aj_player_track_title b').html(escapeHtml(_c.performer))
            this.ajaxPlayer.find('#aj_player_track_title span').html(escapeHtml(_c.name))
        }

        u(`.tip_result`).remove()
    }

    __updateTime(new_time) {
        this.uiPlayer.find(".trackInfo .time").html(fmtTime(new_time))
        if(this.timeType == 1) {
            this.uiPlayer.find(".trackInfo .elapsedTime").html(fmtTime(this.currentTrack.length))
        } else {
            this.uiPlayer.find(".trackInfo .elapsedTime").html(getRemainingTime(this.currentTrack.length, new_time))
        }
    }

    __updateMediaSession() {
        const album = document.querySelector(".playlistBlock")
        const cur = this.currentTrack
        navigator.mediaSession.metadata = new MediaMetadata({
            title: escapeHtml(cur.name),
            artist: escapeHtml(cur.performer),
            album: album == null ? "OpenVK Audios" : escapeHtml(album.querySelector(".playlistInfo h4").innerHTML),
            artwork: [{ src: album == null ? "/assets/packages/static/openvk/img/song.jpg" : album.querySelector(".playlistCover img").src }],
        })
    }

    async __countListen() {
        let playlist = 0
        if(!this.listen_coef) {
            return false
        }

        if(this.context.object && this.context.object.name == 'playlist_context') {
            playlist = this.context.object.entity_id
        }

        const form_data = new FormData
        form_data.append('hash', u("meta[name=csrf]").attr("value"))
        form_data.append('playlist', playlist)
        if(this.context.object) {
            form_data.append('listened_from', this.context.object.url)
        }

        const req = await fetch(`/audio${this.currentTrack.id}/listen`, {
            method: 'POST',
            body: form_data
        })
        const res = await req.json()
        if(res.success) {
            console.log('Listen is counted')
        } else {
            console.log('Listen is not counted ! ! !')
        }
    }

    ajClose(pause = true) {
        this.is_closed = true
        if(pause) {
            this.pause()
        }
       
        u('#ajax_audio_player').addClass('hidden')
    }

    ajReveal() {
        this.is_closed = false
        if(u('#ajax_audio_player').length == 0) {
            this.ajCreate()
        }
        u('#ajax_audio_player').removeClass('hidden')
    }

    ajCreate() {
        const previous_time_x = localStorage.getItem('audio.lastX') ?? 100
        const previous_time_y = localStorage.getItem('audio.lastY') ?? scrollY
        const miniplayer_template = u(`
            <div id='ajax_audio_player' class='ctx_place'>
                <div id='aj_player'>
                    <div id='aj_player_internal_controls'>
                        <div id='aj_player_play'>
                            <div id='aj_player_play_btn'></div>
                        </div>
                        <div id='aj_player_track'>
                            <div id='aj_player_track_name'>
                                <a id='aj_player_track_title' class='noOverflow' style='width: 300px;'>
                                    <b>Unknown</b>
                                    —
                                    <span>Untitled</span>
                                </a>

                                <span id='aj_time'>00:00</span>
                            </div>
                            <div id='aj_player_track_length'>
                                <div class="selectableTrack">
                                    <div style='width: 97%;position: relative;'>
                                        <div class="slider"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div id='aj_player_volume'>
                            <div class="selectableTrack">
                                <div style='width: 75%;position: relative;'>
                                    <div class="slider"></div>
                                </div>
                            </div>
                        </div>
                        <div id='aj_player_buttons'>
                            <div id='aj_player_previous'></div>
                            <div id='aj_player_repeat'></div>
                            <div id='aj_player_next'></div>
                        </div>
                    </div>
                    <div id='aj_player_close_btn'></div>
                </div>
            </div>
        `)
        u('body').append(miniplayer_template)
        miniplayer_template.attr('style', `left:${previous_time_x}px;top:${previous_time_y}px`)
        miniplayer_template.find('#aj_player_close_btn').on('click', (e) => {
            this.ajClose()
        })
        miniplayer_template.find('#aj_time').on('click', (e) => {
            if(window.player && window.player.context && window.player.context.object) {
                window.router.route(window.player.context.object.url)
            }
        })
        $('#ajax_audio_player').draggable({
            cursor: 'grabbing', 
            containment: 'window',
            cancel: '#aj_player_track .selectableTrack, #aj_player_volume .selectableTrack, #aj_player_buttons',
            stop: function(e) {
                if(window.player.ajaxPlayer.length > 0) {
                    const left = parseInt(window.player.ajaxPlayer.nodes[0].style.left)
                    const top  = parseInt(window.player.ajaxPlayer.nodes[0].style.top)

                    localStorage.setItem('audio.lastX', left)
                    localStorage.setItem('audio.lastY', top)
                }
            }
        })
    }
}

document.addEventListener("DOMContentLoaded", async () => {
    await window.player.init(window.__current_page_audio_context)
})

u(document).on('click', '.audioEntry .playerButton > .playIcon', async (e) => {
    const audioPlayer = u(e.target).closest('.audioEmbed')
    const id = Number(audioPlayer.attr('data-realid'))
    if(!window.player) {
        return
    }

    if(!window.player.hasTrackWithId(id) && !window.player.isAtAudiosPage()) {
        let _nodes = null
        if(u(e.target).closest('.attachments').length > 0) {
            window.player.connectionType = '.attachments'
            _nodes = u(e.target).closest('.attachments').find('.audioEmbed').nodes
        } else if(u(e.target).closest('.content_list').length > 0) {
            window.player.connectionType = '.content_list'
            _nodes = u(e.target).closest('.content_list').find('.audioEmbed').nodes
        } else if(u(e.target).closest('.generic_audio_list').length > 0) {
            window.player.connectionType = '.generic_audio_list'
            _nodes = u(e.target).closest('.generic_audio_list').find('.audioEmbed').nodes
        } else if(u(e.target).closest('.audiosInsert').length > 0) {
            window.player.connectionType = '.audiosInsert'
            _nodes = u(e.target).closest('.audiosInsert').find('.audioEmbed').nodes
        }

        window.player.tracks = []
        _nodes.forEach(el => {
            const tempAudio = u(el)
            const name = tempAudio.attr('data-name').split(' — ')
            window.player.appendTrack({
                'id': Number(tempAudio.attr('data-realid')),
                'available': true, // , судя по всему
                'keys': JSON.parse(tempAudio.attr('data-keys')),
                'length': Number(tempAudio.attr('data-length')),
                'url': tempAudio.attr('data-url'),
                'name': name[1],
                'performer': name[0]
            })
        })
    }

    if(window.player.current_track_id != id) {
        await window.player.setTrack(id)
    }

    if(window.player.audioPlayer.paused) {
        await window.player.play()

        if(!window.player.isAtAudiosPage()) {
            u('.audioEntry .playerButton .playIcon.paused').removeClass('paused')
            u(e.target).addClass('paused')
        }
    } else {
        window.player.pause()
    }
    
    if(window.player.isAtAudiosPage()) {
        
    } else {
        window.player.linkPlayer(audioPlayer)
        u('.audioEntry .subTracks.shown').removeClass('shown')
        audioPlayer.find('.subTracks').addClass('shown')
    }
})

u(document).on('click', '.bigPlayer .playButton, #ajax_audio_player #aj_player_play_btn', async (e) => {
    if(window.player.audioPlayer.paused) {
        await window.player.play()
    } else {
        window.player.pause()
    }
})

u(document).on('click', '.bigPlayer .backButton, #ajax_audio_player #aj_player_next', async (e) => {
    await window.player.playNextTrack()
})

u(document).on('click', '.bigPlayer .nextButton, #ajax_audio_player #aj_player_previous', async (e) => {
    await window.player.playPreviousTrack()
})

u(document).on("click", ".bigPlayer .elapsedTime", (e) => {
    if(window.player.current_track_id == 0)
        return

    const res = window.player.timeType == 0 ? 1 : 0
    window.player.timeType = res
    window.player.__updateTime(window.player.audioPlayer.currentTime)
})

u(document).on("click", ".bigPlayer .additionalButtons .repeatButton, #ajax_audio_player #aj_player_repeat", (e) => {
    if(window.player.current_track_id == 0)
        return

    const targ = u(e.target)
    targ.toggleClass("pressed")

    if(targ.hasClass("pressed"))
        window.player.audioPlayer.loop = true
    else
        window.player.audioPlayer.loop = false
})

u(document).on("click", ".bigPlayer .additionalButtons .shuffleButton", async (e) => {
    if(window.player.current_track_id == 0)
        return

    await window.player.shuffle()
})

u(document).on("click", ".bigPlayer .additionalButtons .deviceButton", (e) => {
    if(window.player.current_track_id == 0)
        return

    e.target.classList.toggle("pressed")
    window.player.audioPlayer.muted = e.target.classList.contains("pressed")
})

u(document).on('keydown', (e) => {
    if(document.activeElement.closest('.page_header')) {
        return
    }

    if(!window.player || !window.player.isAtAudiosPage()) {
        return
    }

    if([32, 37, 39, 107, 109].includes(e.keyCode)) {
        if(window.messagebox_stack.length > 0)
            return

        e.preventDefault()
    }

    const arrow_click_offset = 3
    const volume_offset = 0.1
    switch(e.keyCode) {
        case 32:
            window.player.listen_coef -= 0.1
            window.player.uiPlayer.find('.playButton').trigger('click')
            break
        case 37:
            window.player.listen_coef -= 0.5
            window.player.audioPlayer.currentTime = window.player.audioPlayer.currentTime - arrow_click_offset
            break
        case 39:
            window.player.listen_coef -= 0.5
            window.player.audioPlayer.currentTime = window.player.audioPlayer.currentTime + arrow_click_offset
            break
        case 107:
            window.player.audioPlayer.volume = Math.max(Math.min(window.player.audioPlayer.volume + volume_offset, 1), 0)
            break
        case 109:
            window.player.audioPlayer.volume = Math.max(Math.min(window.player.audioPlayer.volume - volume_offset, 1), 0)
            break
        default:
            //console.log(e.keyCode)
            break
    }
})

u(document).on('keyup', (e) => {
    if(document.activeElement.closest('.page_header')) {
        return
    }

    if(!window.player || !window.player.isAtAudiosPage()) {
        return
    }

    if([87, 65, 83, 68, 82, 77].includes(e.keyCode)) {
        if(window.messagebox_stack.length > 0)
            return

        e.preventDefault()
    }

    switch(e.keyCode) {
        case 87:
        case 65:
            window.player.playPreviousTrack()
            break
        case 83:
        case 68:
            window.player.playNextTrack()
            break
        case 82:
            document.querySelector(".bigPlayer .additionalButtons .repeatButton").click()
            break
        case 77:
            document.querySelector(".bigPlayer .additionalButtons .deviceButton").click()
            break
    }
})

u(document).on("mousemove click mouseup", ".bigPlayer .trackPanel .selectableTrack, .audioEntry .subTracks .lengthTrackWrapper .selectableTrack, #aj_player_track_length .selectableTrack", (e) => {
    if(window.player.isAtAudiosPage() && window.player.current_track_id == 0)
        return

    if(u('.ui-draggable-dragging').length > 0) {
        return
    }

    function __defaultAction(i_time) {
        window.player.listen_coef -= 0.5
        window.player.audioPlayer.currentTime = i_time
    }

    const taggart = u(e.target).closest('.selectableTrack')
    const parent  = taggart.parent()
    const rect = taggart.nodes[0].getBoundingClientRect()
    const width = e.clientX - rect.left
    const time = Math.ceil((width * window.player.currentTrack.length) / (rect.right - rect.left))
    if(e.type == "mousemove") {
        let buttonsPresseed = _bsdnUnwrapBitMask(e.buttons)
        if(buttonsPresseed[0])
            __defaultAction(time)
    }

    if(e.type == 'click' || e.type == 'mouseup') {
        __defaultAction(time)
    }

    if(parent.find('.tip_result').length < 1) {
        parent.append(`<div class='tip_result'></div>`)
    }

    parent.find('.tip_result').html(fmtTime(time)).attr('style', `left:min(${width - 15}px, 315.5px)`)
})

u(document).on("mouseout", ".bigPlayer .trackPanel .selectableTrack, .audioEntry .subTracks .lengthTrackWrapper .selectableTrack, #aj_player_track_length .selectableTrack", (e) => {
    if(window.player.isAtAudiosPage() && window.player.current_track_id == 0)
        return

    u(e.target).closest('.selectableTrack').parent().find('.tip_result').remove()
})

u(document).on("mousemove click mouseup", ".bigPlayer .volumePanelTrack .selectableTrack, .audioEntry .subTracks .volumeTrack .selectableTrack, #aj_player_volume .selectableTrack", (e) => {
    if(window.player.isAtAudiosPage() && window.player.current_track_id == 0)
        return
    
    if(u('.ui-draggable-dragging').length > 0) {
        return
    }

    function __defaultAction(i_volume) {
        window.player.audioPlayer.volume = i_volume
    }

    const rect = u(e.target).closest(".selectableTrack").nodes[0].getBoundingClientRect();
    const taggart = u(e.target).closest('.selectableTrack')
    const parent  = taggart.parent()
    const width = e.clientX - rect.left
    const volume = Math.max(0, (width * 1) / (rect.right - rect.left))
    if(e.type == "mousemove") {
        let buttonsPresseed = _bsdnUnwrapBitMask(e.buttons)
        if(buttonsPresseed[0])
            __defaultAction(volume)
    }

    if(e.type == 'click' || e.type == 'mouseup') {
        __defaultAction(volume)
    }

    if(parent.find('.tip_result').length < 1) {
        parent.append(`<div class='tip_result'></div>`)
    }

    parent.find('.tip_result').html((volume * 100).toFixed(0) + '%').attr('style', `left:${width - 15}px`)
})

u(document).on("mouseout", ".bigPlayer .volumePanelTrack .selectableTrack, .audioEntry .subTracks .volumeTrack .selectableTrack, #aj_player_volume .selectableTrack", (e) => {
    if(window.player.isAtAudiosPage() && window.player.current_track_id == 0)
        return
    
    u(e.target).closest('.selectableTrack').parent().find('.tip_result').remove()
})

u(document).on('dragstart', '.audiosContainer .audioEmbed', (e) => {
    u(e.target).closest('.audioEmbed').addClass('currently_dragging')
    return
})

u(document).on('dragover', '.audiosContainer .audioEmbed', (e) => {
    e.preventDefault()

    const target = u(e.target).closest('.audioEmbed')
    const current = u('.audioEmbed.currently_dragging')

    if(current.length < 1) {
        return
    }

    if(target.nodes[0].dataset.id != current.nodes[0].dataset.id) {
        target.addClass('dragged')
    }
    
    return
})

u(document).on('dragend', '.audiosContainer .audioEmbed', (e) => {
    //console.log(e)
    u(e.target).closest('.audioEmbed').removeClass('dragged')
    return
})

// TODO: write changes on server side (audio.reorder)
u(document).on("drop", '.audiosContainer', function(e) {
    const current = u('.audioEmbed.currently_dragging')
    if(e.dataTransfer.types.includes('Files')) {
        e.preventDefault()
        e.dataTransfer.dropEffect = 'move'
    } else if(e.dataTransfer.types.length < 1 || e.dataTransfer.types.includes('text/uri-list')) {
        e.preventDefault()

        u('.audioEmbed.currently_dragging').removeClass('currently_dragging')
        const target = u(e.target).closest('.audioEmbed')
        const first_id  = Number(current.attr('data-realid'))
        const second_id = Number(target.attr('data-realid'))

        const first_html = target.nodes[0].outerHTML
        const second_html = current.nodes[0].outerHTML

        current.nodes[0].outerHTML = first_html
        target.nodes[0].outerHTML = second_html

        window.player.switchTracks(first_id, second_id)
    } 
})

u(document).on('contextmenu', '.bigPlayer, .audioEmbed, #ajax_audio_player', (e) => {
    e.preventDefault()

    u('#ctx_menu').remove()
    const ctx_type = u(e.target).closest('.bigPlayer, #ajax_audio_player').length > 0 ? 'main_player' : 'mini_player'
    const parent = e.target.closest('.ctx_place')
    if(!parent) {
        return
    }

    const rect = parent.getBoundingClientRect()
    let x, y;
    let rx = rect.x + window.scrollX, ry = rect.y + window.scrollY
    x = e.pageX - rx
    y = e.pageY - ry

    const ctx_u = u(`
        <div id='ctx_menu' style='top:${y}px;left:${x}px;' data-type='ctx_type'>
            <a id='audio_ctx_copy'>${tr('copy_link_to_audio')}</a>
            ${ctx_type == 'main_player' ? `
            <a id='audio_ctx_repeat' ${window.player.audioPlayer.loop ? `class='pressed'` : ''}>${tr('repeat_tip')}</a>
            <a id='audio_ctx_shuffle'>${tr('shuffle_tip')}</a>
            <a id='audio_ctx_mute' ${window.player.audioPlayer.muted ? `class='pressed'` : ''}>${tr('mute_tip_noun')}</a>
            ` : ''}
            ${ctx_type == 'mini_player' ? `
            <a id='audio_ctx_play_next'>${tr('audio_ctx_play_next')}</a>    
            ` : ''}
            <a id='audio_ctx_add_to_group'>${tr('audio_ctx_add_to_group')}</a>
            <a id='audio_ctx_add_to_playlist'>${tr('audio_ctx_add_to_playlist')}</a>
            ${ctx_type == 'main_player' ? `
            <a id='audio_ctx_clear_context'>${tr('audio_ctx_clear_context')}</a>` : ''}
            ${ctx_type == 'main_player' ? `<a href='https://github.com/mrilyew' target='_blank'>BigPlayer v1.1 by MrIlyew</a>` : ''}
        </div>
    `)
    u(parent).append(ctx_u)
    ctx_u.find('#audio_ctx_copy').on('click', async (e) => {
        if(ctx_type == 'main_player') {
            if(window.player.current_track_id == 0) {
                makeError(tr('copy_link_to_audio_error_not_selected_track'), 'Red', 4000, 80)
                return
            }

            const url = location.origin + `/audio${window.openvk.current_id}_${window.player.current_track_id}`
            await copyToClipboard(url)
        } else {
            const url = location.origin + `/audio${window.openvk.current_id}_${u(e.target).closest('.audioEmbed').attr('data-realid')}`
            await copyToClipboard(url)
        }
    })
    ctx_u.find('#audio_ctx_repeat').on('click', () => {
        if(window.player.current_track_id == 0) {
            return
        }

        if(!window.player.audioPlayer.loop) {
            window.player.audioPlayer.loop = true
            window.player.uiPlayer.find('.repeatButton').addClass('pressed')
        } else {
            window.player.audioPlayer.loop = false
            window.player.uiPlayer.find('.repeatButton').removeClass('pressed')
        }
    })
    ctx_u.find('#audio_ctx_shuffle').on('click', async () => {
        if(window.player.current_track_id == 0) {
            return
        }

        await window.player.shuffle()
    })
    ctx_u.find('#audio_ctx_mute').on('click', async () => {
        if(window.player.current_track_id == 0) {
            return
        }

        window.player.uiPlayer.find('.deviceButton').toggleClass('pressed')
        window.player.audioPlayer.muted = window.player.uiPlayer.find('.deviceButton').hasClass('pressed')
    })
    ctx_u.find('#audio_ctx_add_to_group').on('click', async () => {
        if(ctx_type == 'main_player') {
            if(window.player.current_track_id == 0) {
                return
            }

            __showAudioAddDialog(window.player.current_track_id)
        } else {
            __showAudioAddDialog(Number(u(e.target).closest('.audioEmbed').attr('data-realid')))
        }
    })
    ctx_u.find('#audio_ctx_add_to_playlist').on('click', async () => {
        if(ctx_type == 'main_player') {
            if(window.player.current_track_id == 0) {
                return
            }
            
            __showAudioAddDialog(window.player.current_track_id, 'playlist')
        } else {
            __showAudioAddDialog(Number(u(e.target).closest('.audioEmbed').attr('data-realid')), 'playlist')
        }
    })
    ctx_u.find('#audio_ctx_play_next').on('click', (ev) => {
        const current_id = window.player.current_track_id
        const move_id = Number(u(e.target).closest('.audioEmbed').attr('data-realid'))
        if(current_id == 0) {
            return
        }
        
        if(current_id == move_id) {
            return
        }

        const current_index = window.player.__findTrack(current_id, true)
        const next_track = window.player.__findTrack(move_id)
        const next_track_player = u(`.audioEmbed[data-realid='${window.player.nextTrack.id}']`)
        const moving_track_player = u(`.audioEmbed[data-realid='${move_id}']`)

        window.player.tracks.splice(current_index + 1, 0, next_track)
        if(next_track_player.length > 0 && moving_track_player.length > 0) {
            next_track_player.nodes[0].outerHTML = moving_track_player.nodes[0].outerHTML + next_track_player.nodes[0].outerHTML
            moving_track_player.remove()
        }
    })
    ctx_u.find('#audio_ctx_clear_context').on('click', (ev) => {
        const old_url = window.player.context.object.url
        window.player.pause()
        window.player.__resetContext()
        window.player.__updateFace()
        window.router.route(old_url)
    })
})

u(document).on("click", ".musicIcon.edit-icon", (e) => {
    const player = e.target.closest(".audioEmbed")
    const id = Number(player.dataset.realid)
    const performer = e.target.dataset.performer
    const name = e.target.dataset.title
    const genre = player.dataset.genre
    const lyrics = e.target.dataset.lyrics
    
    MessageBox(tr("edit_audio"), `
        <div>
            ${tr("performer")}
            <input name="performer" maxlength="256" type="text" value="${performer}">
        </div>

        <div style="margin-top: 11px">
            ${tr("audio_name")}
            <input name="name" maxlength="256" type="text" value="${name}">
        </div>

        <div style="margin-top: 11px">
            ${tr("genre")}
            <select name="genre"></select>
        </div>

        <div style="margin-top: 11px">
            ${tr("lyrics")}
            <textarea name="lyrics" maxlength="5000" style="max-height: 200px;">${lyrics ?? ""}</textarea>
        </div>

        <div style="margin-top: 11px">
            <label><input type="checkbox" name="explicit" ${e.currentTarget.dataset.explicit == 1 ? "checked" : ""}>${tr("audios_explicit")}</label><br>
            <label><input type="checkbox" name="searchable" ${e.currentTarget.dataset.searchable == 1 ? "checked" : ""}>${tr("searchable")}</label>
            <hr>
            <a id="_fullyDeleteAudio">${tr("fully_delete_audio")}</a>
        </div>
    `, [tr("save"), tr("cancel")], [
        function() {
            const t_name   = $(".ovk-diag-body input[name=name]").val();
            const t_perf   = $(".ovk-diag-body input[name=performer]").val();
            const t_genre  = $(".ovk-diag-body select[name=genre]").val();
            const t_lyrics = $(".ovk-diag-body textarea[name=lyrics]").val();
            const t_explicit = document.querySelector(".ovk-diag-body input[name=explicit]").checked;
            const t_unlisted = document.querySelector(".ovk-diag-body input[name=searchable]").checked;

            $.ajax({
                type: "POST",
                url: `/audio${id}/action?act=edit`,
                data: {
                    name: t_name,
                    performer: t_perf,
                    genre: t_genre,
                    lyrics: t_lyrics,
                    unlisted: Number(t_unlisted),
                    explicit: Number(t_explicit),
                    hash: u("meta[name=csrf]").attr("value")
                },
                success: (response) => {
                    if(response.success) {
                        const perf = player.querySelector(".performer a")
                        perf.innerHTML = escapeHtml(response.new_info.performer)
                        perf.setAttribute("href", "/search?q=&section=audios&order=listens&only_performers=on&q="+response.new_info.performer)
                        
                        e.target.setAttribute("data-performer", escapeHtml(response.new_info.performer))
                        e.target.setAttribute("data-title", escapeHtml(response.new_info.name))
                        e.target.setAttribute("data-lyrics", response.new_info.lyrics_unformatted)
                        e.target.setAttribute("data-explicit", Number(response.new_info.explicit))
                        e.target.setAttribute("data-searchable", Number(!response.new_info.unlisted))
                        player.setAttribute("data-genre", response.new_info.genre)
                        
                        let name = player.querySelector(".title")
                        name.innerHTML = escapeHtml(response.new_info.name)
                        
                        if(response.new_info.lyrics_unformatted != "") {
                            if(player.querySelector(".lyrics") != null) {
                                player.querySelector(".lyrics").innerHTML = response.new_info.lyrics
                                player.querySelector(".title").classList.add("withLyrics")
                            } else {
                                player.insertAdjacentHTML("beforeend", `
                                    <div class="lyrics">
                                        ${response.new_info.lyrics}
                                    </div>
                                `)
    
                                player.querySelector(".title").classList.add("withLyrics")
                            }
                        } else {
                            $(player.querySelector(".lyrics")).remove()
                            player.querySelector(".title").classList.remove("withLyrics")
                        }

                        if(Number(response.new_info.explicit) == 1) {
                            if(!player.querySelector(".mediaInfo .explicitMark"))
                                player.querySelector(".mediaInfo").insertAdjacentHTML("beforeend", `
                                    <div class="explicitMark"></div>
                                `)
                        } else {
                            $(player.querySelector(".mediaInfo .explicitMark")).remove()
                        }

                        let url = new URL(location.href)
                        let page = "1"

                        if(url.searchParams.p != null)
                            page = String(url.searchParams.p)
                    } else
                        fastError(response.flash.message)
                }
            });
        },

        Function.noop
    ]);

    window.openvk.audio_genres.forEach(elGenre => {
        document.querySelector(".ovk-diag-body select[name=genre]").insertAdjacentHTML("beforeend", `
            <option value="${elGenre}" ${elGenre == genre ? "selected" : ""}>${elGenre}</option>
        `)
    })

    u(".ovk-diag-body #_fullyDeleteAudio").on("click", (e) => {
        MessageBox(tr('confirm'), tr('confirm_deleting_audio'), [tr('yes'), tr('no')], [() => {
            $.ajax({
                type: "POST",
                url: `/audio${id}/action?act=delete`,
                data: {
                    hash: u("meta[name=csrf]").attr("value")
                },
                success: (response) => {
                    u("body").removeClass("dimmed");
                    u(".ovk-diag-cont").remove();
                    document.querySelector("html").style.overflowY = "scroll"

                    if(response.success)
                        u(player).remove()
                    else
                        fastError(response.flash.message)
                }
            });
        }, () => {Function.noop}])
    })
})

u(document).on("click", ".title.withLyrics", (e) => {
    const parent = e.currentTarget.closest(".audioEmbed")

    parent.querySelector(".lyrics").classList.toggle("showed")
})

$(document).on("click", ".musicIcon.remove-icon", (e) => {
    e.stopImmediatePropagation()

    const id = e.currentTarget.dataset.id
    if(e.detail > 1 || e.altKey) {
        const player = e.target.closest('.audioEmbed')
        player.querySelector('.add-icon-group').click()
        return
    }

    let formdata = new FormData()
    formdata.append("hash", u("meta[name=csrf]").attr("value"))

    ky.post(`/audio${id}/action?act=remove`, {
        hooks: {
            beforeRequest: [
                (_request) => {
                    e.target.classList.add("lagged")
                }
            ],
            afterResponse: [
                async (_request, _options, response) => {
                    let json = await response.json()

                    if(json.success) {
                        e.target.classList.remove("remove-icon")
                        e.target.classList.add("add-icon")
                        e.target.classList.remove("lagged")

                        let withd = e.target.closest(".audioEmbed.withdrawn")

                        if(withd != null)
                            u(withd).remove()
                    } else
                        fastError(json.flash.message)
                }
            ]
        }, body: formdata
    })
})

$(document).on("click", ".musicIcon.remove-icon-group", (e) => {
    e.stopImmediatePropagation()
    
    let id = e.currentTarget.dataset.id
    
    let formdata = new FormData()
    formdata.append("hash", u("meta[name=csrf]").attr("value"))
    formdata.append("club", e.currentTarget.dataset.club)

    ky.post(`/audio${id}/action?act=remove_club`, {
        hooks: {
            beforeRequest: [
                (_request) => {
                    e.currentTarget.classList.add("lagged")
                }
            ],
            afterResponse: [
                async (_request, _options, response) => {
                    let json = await response.json()

                    if(json.success)
                        $(e.currentTarget.closest(".audioEmbed")).remove()
                    else
                        fastError(json.flash.message)
                }
            ]
        }, body: formdata
    })
})

function __showAudioAddDialog(id, current_tab = 'club') {
    const body = `
        <div id='_addAudioAdditional'>
            <div id='_tabs'>
                <div class="mb_tabs">
                    <div class="mb_tab" data-name='club'>
                        <a>
                            ${tr('to_club')}
                        </a>
                    </div>
                    <div class="mb_tab" data-name='playlist'>
                        <a>
                            ${tr('to_playlist')}
                        </a>
                    </div>
                </div>
            </div>
            <span id='_tip'>${tr('add_audio_limitations')}</span>
            <div id='_content'></div>
        </div>
    `

    MessageBox(tr("add_audio"), body, [tr("cancel"), tr("add")], [Function.noop, () => {
        const ids = []
        u('#_content .entity_vertical_list_item').nodes.forEach(item => {
            const _checkbox = item.querySelector(`input[type='checkbox'][name='add_to']`)
            if(_checkbox.checked) {
                ids.push(item.dataset.id)
            }
        })

        if(ids.length < 1 || ids.length > 10) {
            return
        }

        switch(current_tab) {
            case 'club':
                $.ajax({
                    type: "POST",
                    url: `/audio${id}/action?act=add_to_club`,
                    data: {
                        hash: u("meta[name=csrf]").attr("value"),
                        clubs: ids.join(',')
                    },
                    success: (response) => {
                        if(!response.success)
                            fastError(response.flash.message)
                        else
                            NewNotification(tr("audio_was_successfully_added"), '')
                    }
                })

                break
            case 'playlist':
                $.ajax({
                    type: "POST",
                    url: `/audio${id}/action?act=add_to_playlist`,
                    data: {
                        hash: u("meta[name=csrf]").attr("value"),
                        playlists: ids.join(',')
                    },
                    success: (response) => {
                        if(!response.success)
                            fastError(response.flash.message)
                        else
                            NewNotification(tr("audio_was_successfully_added"), '')
                    }
                })

                break
        }
    }])

    u(".ovk-diag-body").attr('style', 'padding:0px;height: 260px;')

    async function switchTab(tab = 'club') {
        current_tab = tab
        u(`#_addAudioAdditional .mb_tab`).attr('id', 'ki')
        u(`#_addAudioAdditional .mb_tab[data-name='${tab}']`).attr('id', 'active')

        switch(tab) {
            case 'club':
                u("#_content").html(`<div class='entity_vertical_list mini'></div>`)
                if(window.openvk.writeableClubs == null) {
                    u('.entity_vertical_list').append(`<div id='gif_loader'></div>`)

                    try {
                        window.openvk.writeableClubs = await API.Groups.getWriteableClubs()
                    } catch (e) {
                        u("#_content").html(tr("no_access_clubs"))
            
                        return
                    }

                    u('.entity_vertical_list #gif_loader').remove()
                }
                
                window.openvk.writeableClubs.forEach(el => {
                    u("#_content .entity_vertical_list").append(`
                    <label class='entity_vertical_list_item with_third_column' data-id='${el.id}'>
                        <div class='first_column'>
                            <a href='/club${el.id}' class='avatar'>
                                <img src='${el.avatar}' alt='avatar'>
                            </a>

                            <div class='info'>
                                <b class='noOverflow' value="${el.id}">${ovk_proc_strtr(escapeHtml(el.name), 100)}</b>
                            </div>
                        </div>

                        <div class='third_column'>
                            <input type='checkbox' name='add_to'>
                        </div>
                    </label>
                    `)
                })
                break
            case 'playlist':
                const per_page = 10
                let page = 0
                u("#_content").html(`<div class='entity_vertical_list mini'></div>`)

                async function recievePlaylists(s_page) {
                    res = await fetch(`/method/audio.searchAlbums?auth_mechanism=roaming&query=&limit=10&offset=${s_page * per_page}&from_me=1`)
                    res = await res.json()

                    return res
                }

                function appendPlaylists(response) {
                    response.items.forEach(el => {
                        u("#_content .entity_vertical_list").append(`
                        <label class='entity_vertical_list_item with_third_column' data-id='${el.owner_id}_${el.id}'>
                            <div class='first_column'>
                                <a href='/playlist${el.owner_id}_${el.id}' class='avatar'>
                                    <img src='${el.cover_url}' alt='cover'>
                                </a>

                                <div class='info'>
                                    <b class='noOverflow' value="${el.owner_id}_${el.id}">${ovk_proc_strtr(escapeHtml(el.title), 100)}</b>
                                </div>
                            </div>

                            <div class='third_column'>
                                <input type='checkbox' name='add_to'>
                            </div>
                        </label>
                        `)
                    })

                    if(response.count > per_page * page) {
                        u("#_content .entity_vertical_list").append(`<a id='_pladdwinshowmore'>${tr('show_more')}</a>`)
                    }
                }

                if(window.openvk.writeablePlaylists == null) {
                    u('.entity_vertical_list').append(`<div id='gif_loader'></div>`)

                    try {
                        res = await recievePlaylists(page)
                        page += 1
                        window.openvk.writeablePlaylists = res.response

                        if(!window.openvk.writeablePlaylists || window.openvk.writeablePlaylists.count < 1) {
                            throw new Error
                        }
                    } catch (e) {
                        u("#_content").html(tr("no_access_playlists"))
            
                        return
                    }

                    u('.entity_vertical_list #gif_loader').remove()
                }

                appendPlaylists(window.openvk.writeablePlaylists)
                
                u('#_addAudioAdditional').on('click', '#_pladdwinshowmore', async (e) => {
                    e.target.outerHTML = ''

                    res = await recievePlaylists(page)
                    page += 1

                    appendPlaylists(res.response)
                })
                break
        }
    }

    switchTab(current_tab)

    u("#_addAudioAdditional").on("click", ".mb_tab a", async (e) => {
        await switchTab(u(e.target).closest('.mb_tab').attr('data-name'))
    })

    u("#_addAudioAdditional").on("click", "input[name='add_to']", async (e) => {
        if(u(`input[name='add_to']:checked`).length > 10) {
            e.preventDefault()
        }
    })
}
$(document).on("click", ".musicIcon.add-icon-group", async (ev) => {
    const id = Number(ev.target.dataset.id)
    __showAudioAddDialog(id)
})

$(document).on("click", ".musicIcon.add-icon", (e) => {
    const id = e.currentTarget.dataset.id
    if(e.detail > 1 || e.altKey) {
        const player = e.target.closest('.audioEmbed')
        player.querySelector('.add-icon-group').click()
        return
    }

    let formdata = new FormData()
    formdata.append("hash", u("meta[name=csrf]").attr("value"))

    ky.post(`/audio${id}/action?act=add`, {
        hooks: {
            beforeRequest: [
                (_request) => {
                    e.target.classList.add("lagged")
                }
            ],
            afterResponse: [
                async (_request, _options, response) => {
                    let json = await response.json()

                    if(json.success) {
                        e.target.classList.remove("add-icon")
                        e.target.classList.add("remove-icon")
                        e.target.classList.remove("lagged")
                    } else
                       fastError(json.flash.message)
                }
            ]
        }, body: formdata
    })
})

$(document).on("click", "#_deletePlaylist", (e) => {
    let id = e.currentTarget.dataset.id

    MessageBox(tr("warning"), tr("sure_delete_playlist"), [tr("yes"), tr("no")], [() => {
        $.ajax({
            type: "POST",
            url: `/playlist${id}/action?act=delete`,
            data: {
                hash: u("meta[name=csrf]").attr("value"),
            },
            beforeSend: () => {
                e.currentTarget.classList.add("lagged")
            },
            success: (response) => {
                if(response.success) {
                    window.router.route("/playlists" + response.id)
                } else {
                    fastError(response.flash.message)
                }
            }
        })
    }, Function.noop])
})

function showAudioAttachment(type = 'form', form = null)
{
    const msg = new CMessageBox({
        title: tr("select_audio"),
        body: `
        <div class="searchBox">
            <input name="query" type="text" maxlength="50" placeholder="${tr("header_search")}">
            <select name="perf">
                <option value="by_name">${tr("by_name")}</option>
                <option value="by_performer">${tr("by_performer")}</option>
            </select>
        </div>

        <div class="audiosInsert"></div>
        `,
        buttons: [tr('close')],
        callbacks: [Function.noop],
    })
    msg.getNode().find('.ovk-diag-body').attr('style', 'padding:0px;height:335px')
    msg.getNode().attr('style', 'width:580px')
    let searcher = new playersSearcher("entity_audios", 0)
    searcher.successCallback = (response, thisc) => {
        let domparser = new DOMParser()
        let result = domparser.parseFromString(response, "text/html")

        let pagesCount = result.querySelector("input[name='pagesCount']").value
        let count = Number(result.querySelector("input[name='count']").value)

        if(count < 1) {
            document.querySelector(".audiosInsert").innerHTML = thisc.context_type == "entity_audios" ? tr("no_audios_thisuser") : tr("no_results")
            return
        }

        result.querySelectorAll(".audioEmbed").forEach(el => {
            let id = 0
            if(type == 'form') {
                id = el.dataset.prettyid
            } else {
                id = el.dataset.realid
            }
            let is_attached = false
            if(type == 'form') {
                is_attached = (u(form).find(`.post-vertical .vertical-attachment[data-id='${id}']`)).length > 0
            } else {
                is_attached = (u(form).find(`.PE_audios .vertical-attachment[data-id='${id}']`)).length > 0
            }
            
            document.querySelector(".audiosInsert").insertAdjacentHTML("beforeend", `
                <div class='audio_attachment_header' style="display: flex;width: 100%;">
                    <div class='player_part' style="width: 72%;">${el.outerHTML}</div>
                    <div class="attachAudio" data-attachmentdata="${id}">
                        <span>${is_attached ? tr("detach_audio") : tr("attach_audio")}</span>
                    </div>
                </div>
            `)
        })

        u("#loader").remove()
        u('#show_more').remove()

        if(thisc.page < pagesCount) {
            document.querySelector(".audiosInsert").insertAdjacentHTML("beforeend", `
            <div id="show_more" data-pagesCount="${pagesCount}" data-page="${thisc.page + 1}" class="showMore">
                <span>${tr("show_more_audios")}</span>
            </div>`)
        }
    }

    searcher.errorCallback = () => {
        fastError("Error when loading players.")
    }

    searcher.beforesendCallback = () => {
        document.querySelector(".audiosInsert").insertAdjacentHTML("beforeend", `<img id="loader" src="/assets/packages/static/openvk/img/loading_mini.gif">`)
    }

    searcher.clearContainer = () => {
        document.querySelector(".audiosInsert").innerHTML = ""
    }

    searcher.movePage(1)

    u(".audiosInsert").on("click", "#show_more", async (e) => {
        u(e.target).closest('#show_more').addClass('lagged')
        searcher.movePage(Number(e.currentTarget.dataset.page))
    })

    u(".searchBox input").on("change", async (e) => {
        if(e.currentTarget.value === document.querySelector(".searchBox input").value) {
            searcher.clearContainer()

            if(e.currentTarget.value == "") {
                searcher.context_type = "entity_audios"
                searcher.context_id = 0
                searcher.query = ""

                searcher.movePage(1)

                return
            }

            searcher.context_type = "search_context"
            searcher.context_id = 0
            searcher.query = e.currentTarget.value

            searcher.movePage(1)
            return;
        }
    })

    u(".searchBox select").on("change", async (e) => {
        searcher.clearContainer()
        searcher.searchType = e.currentTarget.value

        $(".searchBox input").trigger("change")
        return;
    })

    u(".audiosInsert").on("click", ".attachAudio", (ev) => {
        const id = ev.currentTarget.dataset.attachmentdata
        let is_attached = false
        if(type == 'form') {
            is_attached = u(form).find(`.post-vertical .vertical-attachment[data-id='${id}']`).length > 0
        } else {
            is_attached = u(form).find(`.PE_audios .vertical-attachment[data-id='${id}']`).length > 0
        }

        // 04.11.2024 19:03
        // 30.11.2024 19:03
        if(is_attached) {
            if(type == 'form') {
                u(form).find(`.post-vertical .vertical-attachment[data-id='${id}']`).remove()
            } else {
                u(form).find(`.PE_audios .vertical-attachment[data-id='${id}']`).remove()
            }
            u(ev.currentTarget).find("span").html(tr("attach_audio"))
        } else {
            if(type == 'form' && u(form).find(`.upload-item`).length > window.openvk.max_attachments) {
                makeError(tr('too_many_attachments'), 'Red', 10000, 1)
                return    
            }

            u(ev.currentTarget).find("span").html(tr("detach_audio"))

            const header = u(ev.currentTarget).closest('.audio_attachment_header')
            const player = header.find('.player_part')
            u(form).find(type == 'form' ? ".post-vertical" : '.PE_audios').append(`
                <div class="vertical-attachment upload-item" data-type='audio' data-id="${ev.currentTarget.dataset.attachmentdata}">
                    <div class='vertical-attachment-content'>
                        ${player.html()}
                    </div>
                    <div class='vertical-attachment-remove'>
                        <div id='small_remove_button'></div>
                    </div>
                </div>
            `)
        }
    })
}

$(document).on("click", "#__audioAttachment", (e) => {
    const form = e.target.closest("form")
    showAudioAttachment('form', form)
})

$(document).on("click", ".audioEmbed.processed .playerButton", (e) => {
    const msg = new CMessageBox({
        title: tr('error'),
        body: tr('audio_embed_processing'),
        unique_name: 'processing_notify',
        buttons: [tr('ok')],
        callbacks: [Function.noop]
    })
})

$(document).on("click", ".audioEmbed.withdrawn", (e) => {
    const msg = new CMessageBox({
        title: tr('error'),
        body: tr('audio_embed_withdrawn'),
        unique_name: 'withdrawn_notify',
        buttons: [tr('ok')],
        callbacks: [Function.noop]
    })
})

$(document).on("click", ".musicIcon.report-icon", (e) => {
    MessageBox(tr("report_question"), `
        ${tr("going_to_report_audio")}
        <br/>${tr("report_question_text")}
        <br/><br/><b> ${tr("report_reason")}</b>: <input type='text' id='uReportMsgInput' placeholder='${tr("reason")}' />`, [tr("confirm_m"), tr("cancel")], [(function() {
        
        res = document.querySelector("#uReportMsgInput").value;
        xhr = new XMLHttpRequest();
        xhr.open("GET", "/report/" + e.target.dataset.id + "?reason=" + res + "&type=audio", true);
        xhr.onload = (function() {
        if(xhr.responseText.indexOf("reason") === -1)
            MessageBox(tr("error"), tr("error_sending_report"), ["OK"], [Function.noop]);
        else
           MessageBox(tr("action_successfully"), tr("will_be_watched"), ["OK"], [Function.noop]);
        });
        xhr.send(null)
    }),

    Function.noop])
})

$(document).on("click", ".addToPlaylist", (e) => {
    let audios = document.querySelector("input[name='audios']") 
    let id = e.currentTarget.dataset.id

    if(!audios.value.includes(id + ",")) {
        document.querySelector("input[name='audios']").value += (id + ",")
        e.currentTarget.querySelector("span").innerHTML = tr("remove_from_playlist")
    } else {
        document.querySelector("input[name='audios']").value = document.querySelector("input[name='audios']").value.replace(id + ",", "")
        e.currentTarget.querySelector("span").innerHTML = tr("add_to_playlist")
    }
})

$(document).on("click", "#bookmarkPlaylist, #unbookmarkPlaylist", (e) => {
    let target = e.currentTarget
    let id = target.id

    $.ajax({
        type: "POST",
        url: `/playlist${e.currentTarget.dataset.id}/action?act=${id == "unbookmarkPlaylist" ? "unbookmark" : "bookmark"}`,
        data: {
            hash: u("meta[name=csrf]").attr("value"),
        },
        beforeSend: () => {
            e.currentTarget.classList.add("lagged")
        },
        success: (response) => {
            if(response.success) {
                e.currentTarget.setAttribute("id", id == "unbookmarkPlaylist" ? "bookmarkPlaylist" : "unbookmarkPlaylist")
                e.currentTarget.innerHTML = id == "unbookmarkPlaylist" ? tr("bookmark") : tr("unbookmark")
                e.currentTarget.classList.remove("lagged")
            } else
                fastError(response.flash.message)
        }
    })
})

u(document).on('click', '.upload_container_element #small_remove_button', (e) => {
    if(u('.uploading').length > 0) {
        return
    }

    const element = u(e.target).closest('.upload_container_element')
    const element_index = Number(element.attr('data-index'))
    
    element.remove()
    window.__audio_upload_page.files_list[element_index] = null

    if(u('#lastStep .upload_container_element').length < 1) {
        window.__audio_upload_page.showFirstPage()
    }
})

u(document).on('click', `#upload_container #uploadMusic`, async (e) => {
    const current_upload_page = location.href
    let error = null
    let end_redir = ''
    u('#lastStepButtons').addClass('lagged')
    for(const elem of u('#lastStepContainers .upload_container_element').nodes) {
        if(!elem) {
            return
        }
        const elem_u = u(elem)
        const index = elem.dataset.index
        const file  = window.__audio_upload_page.files_list[index]
        if(!file || !index) {
            return
        }

        elem_u.addClass('lagged').find('.upload_container_name').addClass('uploading')
        // Upload process
        const fd = serializeForm(elem)
        fd.append('blob', file.file)
        fd.append('ajax', 1)
        fd.append('hash', window.router.csrf)
        
        const result = await fetch(current_upload_page, {
            method: 'POST',
            body: fd,
        })
        const result_text = await result.json()
        if(result_text.success) {
            end_redir = result_text.redirect_link
        } else {
            makeError(escapeHtml(result_text.flash.message))
        }
        await sleep(6000)
        elem_u.remove()
    }

    if(!end_redir) {
        u('#lastStepButtons').removeClass('lagged')
        window.__audio_upload_page.showFirstPage()
        return
    }

    if(current_upload_page == location.href) {
        window.router.route(end_redir)
    }
})

u(document).on("drop", "#upload_container", function (e) {
    e.preventDefault()
    e.dataTransfer.dropEffect = 'move';

    document.getElementById("audio_input").files = e.dataTransfer.files
    u("#audio_input").trigger("change")
})

u(document).on('click', '#_playlistAppendTracks', (e) => {
    // 1984
    showAudioAttachment('playlist', u('.PE_wrapper').nodes[0])
})

u(document).on('drop', '.PE_audios .vertical-attachment', (e) => {
    const current = u('.upload-item.currently_dragging')
    if(e.dataTransfer.types.length < 1 || e.dataTransfer.types.includes('text/uri-list')) {
        e.preventDefault()

        const target = u(e.target).closest('.upload-item')
        u('.dragged').removeClass('dragged')
        current.removeClass('currently_dragging')

        if(!current.closest('.vertical-attachment').length < 1 && target.closest('.vertical-attachment').length < 1
         || current.closest('.vertical-attachment').length < 1 && !target.closest('.vertical-attachment').length < 1) {
            return
        }

        const first_html = target.nodes[0].outerHTML
        const second_html = current.nodes[0].outerHTML 

        current.nodes[0].outerHTML = first_html
        target.nodes[0].outerHTML = second_html
    }
})

u(document).on("change", `input[name='cover']`, (e) => {
    const file = e.target.files[0]
    if(!file.type.startsWith("image/")) {
        makeError(tr("not_a_photo"))
        return
    }

    const image = URL.createObjectURL(file)
    u(".playlistCover img").attr('src', image).attr('style', 'display:block')
})

u(document).on("drop", `.playlistCover`, (e) => {
    e.preventDefault()
    e.dataTransfer.dropEffect = 'move';

    document.querySelector(`input[name='cover']`).files = e.dataTransfer.files
    u(`input[name='cover']`).trigger("change")
})

u(document).on('click', '.PE_end #playlist_create, .PE_end #playlist_edit', async (e) => {
    const ids = []
    u('.PE_audios .vertical-attachment').nodes.forEach(vatch => {
        ids.push(vatch.dataset.id)
    })
    if(!ids || ids.length < 1) {
        makeError(tr('error_playlist_creating_too_small'), 'Red', 5000, 77)
        return
    }

    u(e.target).addClass('lagged')
    const fd = serializeForm(u('.PE_playlistEditPage').nodes[0])
    fd.append('hash', window.router.csrf)
    fd.append('ajax', 1)
    fd.append('audios', ids)
    const req = await fetch(location.href, {
        method: 'POST',
        body: fd,
    })
    const req_json = await req.json()
    if(req_json.success) {
        window.router.route(req_json.redirect)
    } else {
        makeError(req_json.flash.message)
    }
    u(e.target).removeClass('lagged')
})
