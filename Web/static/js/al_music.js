window.savedAudiosPages = {}

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

    get linkedInlinePlayer() {
        if(!this.__linked_player_id) {
            return null;
        }

        return u('#' + this.__linked_player_id)
    }

    async init(input_context) {
        let context = Object.assign({
            url: location.pathname
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
            const time = this.audioPlayer.currentTime;
            const ps = ((time * 100) / this.currentTrack.length).toFixed(3)
            this.uiPlayer.find(".time").html(fmtTime(time))
            this.__updateTime(time)

            if (ps <= 100) {
                this.uiPlayer.find(".track .selectableTrack .slider").attr('style', `left:${ ps}%`);

                if(this.linkedInlinePlayer) {
                    this.linkedInlinePlayer.find(".subTracks .lengthTrackWrapper .slider").attr('style', `left:${ ps}%`)
                    this.linkedInlinePlayer.find('.mini_timer .nobold').html(fmtTime(time))
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
        
        this.audioPlayer.onended = (e) => {
            e.preventDefault()

            if(!this.nextTrack && window.player.context.playedPages.indexOf(1) == -1) {
                this.loadContext(1, false)
                this.setTrack(this.__findByIndex(0).id)
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
                // todo rifictir
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

        this.listen_coef = 0.0
        this.current_track_id = id
        const c_track = this.currentTrack
        if(!c_track) {
            makeError('Error playing audio: track not found')
            return
        }
        
        const protData = {
            "org.w3.clearkey": {
                "clearkeys": c_track.keys
            }
        };

        u('.nowPlaying').removeClass('nowPlaying')
        if(this.isAtAudiosPage()) {
            u(`.audioEmbed[data-realid='${id}'] .audioEntry`).addClass('nowPlaying')
        }

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

    play() {
        if(!this.currentTrack) {
            return
        }

        document.querySelectorAll('audio').forEach(el => el.pause())

        this.audioPlayer.play()
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

    playPreviousTrack() {
        if(!this.currentTrack || !this.previousTrack) {
            return
        }

        this.setTrack(this.previousTrack.id)
        if(!this.currentTrack.available || this.currentTrack.withdrawn) {
            if(!this.previousTrack) {
                return
            }
            
            this.playPreviousTrack()
        }

        this.play()
    }
    
    playNextTrack() {
        if(!this.currentTrack || !this.nextTrack) {
            return
        }

        this.setTrack(this.nextTrack.id)
        if(!this.currentTrack.available || this.currentTrack.withdrawn) {
            if(!this.nextTrack) {
                return
            }

            this.playNextTrack()
        }

        this.play()
    }

    // fake shuffle
    shuffle() {
        this.tracks.sort(() => Math.random() - 0.59)
        this.setTrack(this.tracks.at(0).id)
    }

    isAtAudiosPage() {
        return u('.bigPlayer').length > 0
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
        navigator.mediaSession.setActionHandler('play', () => { 
            window.player.play()
        });
        navigator.mediaSession.setActionHandler('pause', () => { 
            window.player.pause() 
        });
        navigator.mediaSession.setActionHandler('previoustrack', () => { window.player.playPreviousTrack() });
        navigator.mediaSession.setActionHandler('nexttrack', () => { window.player.playNextTrack() });
        navigator.mediaSession.setActionHandler("seekto", (details) => {
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
        } else {
            prev_button.removeClass('lagged')
            prev_button.attr('data-title', ovk_proc_strtr(escapeHtml(this.previousTrack.name), 50))
        }

        if(!this.nextTrack) {
            next_button.addClass('lagged')
        } else {
            next_button.removeClass('lagged')
            next_button.attr('data-title', ovk_proc_strtr(escapeHtml(this.nextTrack.name), 50))
        }

        if(!this.audioPlayer.paused) {
            this.uiPlayer.find('.playButton').addClass('pause')
            if(this.linkedInlinePlayer) {
                this.linkedInlinePlayer.find('.playerButton .playIcon').addClass('paused')
            }
        } else {
            this.uiPlayer.find('.playButton').removeClass('pause')
            if(this.linkedInlinePlayer) {
                this.linkedInlinePlayer.find('.playerButton .playIcon').removeClass('paused')
            }
        }

        this.uiPlayer.find('.trackInfo .trackName span').html(escapeHtml(_c.name))
        this.uiPlayer.find('.trackInfo .trackPerformers').html('')
        const performers = _c.performer.split(', ')
        const lastPerformer = performers[performers.length - 1]
        performers.forEach(performer => {
            this.uiPlayer.find('.trackInfo .trackPerformers').append(
                `<a href='/search?section=audios&order=listens&only_performers=on&q=${encodeURIComponent(performer.escapeHtml())}'>${performer.escapeHtml()}${(performer != lastPerformer ? ', ' : '')}</a>`)
        })
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
}

document.addEventListener("DOMContentLoaded", async () => {
    await window.player.init(window.__current_page_audio_context)
})

u(document).on('click', '.audioEntry .playerButton > .playIcon', (e) => {
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
        }

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
        window.player.setTrack(id)
    }

    if(window.player.audioPlayer.paused) {
        window.player.play()

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

u(document).on('click', '.bigPlayer .playButton', (e) => {
    if(window.player.audioPlayer.paused) {
        window.player.play()
    } else {
        window.player.pause()
    }
})

u(document).on('click', '.bigPlayer .backButton', (e) => {
    window.player.playNextTrack()
})

u(document).on('click', '.bigPlayer .nextButton', (e) => {
    window.player.playPreviousTrack()
})

u(document).on("click", ".bigPlayer .elapsedTime", (e) => {
    if(window.player.current_track_id == 0)
        return

    const res = window.player.timeType == 0 ? 1 : 0
    window.player.timeType = res
    window.player.__updateTime(window.player.audioPlayer.currentTime)
})

u(document).on("click", ".bigPlayer .additionalButtons .repeatButton", (e) => {
    if(window.player.current_track_id == 0)
        return

    const targ = u(e.target)
    targ.toggleClass("pressed")

    if(targ.hasClass("pressed"))
        window.player.audioPlayer.loop = true
    else
        window.player.audioPlayer.loop = false
})

u(document).on("click", ".bigPlayer .additionalButtons .shuffleButton", (e) => {
    if(window.player.current_track_id == 0)
        return

    window.player.shuffle()
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

    if(!window.player) {
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

u(document).on("mousemove click mouseup", ".bigPlayer .trackPanel .selectableTrack, .audioEntry .subTracks .lengthTrackWrapper .selectableTrack", (e) => {
    if(window.player.isAtAudiosPage() && window.player.current_track_id == 0)
        return

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

u(document).on("mouseout", ".bigPlayer .trackPanel .selectableTrack, .audioEntry .subTracks .lengthTrackWrapper .selectableTrack", (e) => {
    if(window.player.isAtAudiosPage() && window.player.current_track_id == 0)
        return

    u(e.target).closest('.selectableTrack').parent().find('.tip_result').remove()
})

u(document).on("mousemove click mouseup", ".bigPlayer .volumePanelTrack .selectableTrack, .audioEntry .subTracks .volumeTrack .selectableTrack", (e) => {
    if(window.player.isAtAudiosPage() && window.player.current_track_id == 0)
        return

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

u(document).on("mouseout", ".bigPlayer .volumePanelTrack .selectableTrack, .audioEntry .subTracks .volumeTrack .selectableTrack", (e) => {
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
                        
                        window.savedAudiosPages[page] = null
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

$(document).on("click", ".musicIcon.add-icon-group", async (ev) => {
    let   current_tab = 'club';
    const id = Number(ev.target.dataset.id)
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

$(document).on("click", "#__audioAttachment", (e) => {
    const form = e.target.closest("form")
    let body = `
        <div class="searchBox">
            <input name="query" type="text" maxlength="50" placeholder="${tr("header_search")}">
            <select name="perf">
                <option value="by_name">${tr("by_name")}</option>
                <option value="by_performer">${tr("by_performer")}</option>
            </select>
        </div>

        <div class="audiosInsert"></div>
    `
    MessageBox(tr("select_audio"), body, [tr("close")], [Function.noop])

    document.querySelector(".ovk-diag-body").style.padding = "0"
    document.querySelector(".ovk-diag-cont").style.width = "580px"
    document.querySelector(".ovk-diag-body").style.height = "335px"

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
            let id = el.dataset.prettyid
            const is_attached = (u(form).find(`.post-vertical .vertical-attachment[data-id='${id}']`)).length > 0
            
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

    $(".searchBox input").on("change", async (e) => {
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

    $(".searchBox select").on("change", async (e) => {
        searcher.clearContainer()
        searcher.searchType = e.currentTarget.value

        $(".searchBox input").trigger("change")
        return;
    })

    u(".audiosInsert").on("click", ".attachAudio", (ev) => {
        const id = ev.currentTarget.dataset.attachmentdata
        const is_attached = u(form).find(`.post-vertical .vertical-attachment[data-id='${id}']`).length > 0

        // 04.11.2024 19:03
        if(is_attached) {
            u(form).find(`.post-vertical .vertical-attachment[data-id='${id}']`).remove()
            u(ev.currentTarget).find("span").html(tr("attach_audio"))
        } else {
            if(u(form).find(`.upload-item`).length > window.openvk.max_attachments) {
                makeError(tr('too_many_attachments'), 'Red', 10000, 1)
                return    
            }

            u(ev.currentTarget).find("span").html(tr("detach_audio"))

            const header = u(ev.currentTarget).closest('.audio_attachment_header')
            const player = header.find('.player_part')
            u(form).find(".post-vertical").append(`
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

u(document).on("click", ".audiosContainer .paginator a", (e) => {
    e.preventDefault()
    let url = new URL(e.currentTarget.href)
    let page = url.searchParams.get("p")

    function searchNode(id) {
        let node = document.querySelector(`.audioEmbed[data-realid='${id}'] .audioEntry`)

        if(node != null) {
            node.classList.add("nowPlaying")
        }
    }

    if(window.savedAudiosPages[page] != null) {
        history.pushState({}, "", e.currentTarget.href)
        document.querySelector(".audiosContainer").innerHTML = window.savedAudiosPages[page].innerHTML
        searchNode(window.player.currentTrack != null ? window.player.currentTrack.id : 0)

        return
    }

    e.currentTarget.parentNode.classList.add("lagged")
    $.ajax({
        type: "GET",
        url: e.currentTarget.href,
        success: (response) => {
            let domparser = new DOMParser()
            let result = domparser.parseFromString(response, "text/html")

            document.querySelector(".audiosContainer").innerHTML = result.querySelector(".audiosContainer").innerHTML
            history.pushState({}, "", e.currentTarget.href)
            window.savedAudiosPages[page] = result.querySelector(".audiosContainer")
            searchNode(window.player.currentTrack != null ? window.player.currentTrack.id : 0)

            if(!window.player.context["playedPages"].includes(page)) {
                window.player.loadContext(page)
            }
        }
    })
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
