function fmtTime(time) {
    const mins = String(Math.floor(time / 60)).padStart(2, '0');
    const secs = String(Math.floor(time % 60)).padStart(2, '0');
    return `${ mins}:${ secs}`;
}

function fastError(message) {
    MessageBox(tr("error"), message, [tr("ok")], [Function.noop])
}

function getElapsedTime(fullTime, time) {
    let timer = fullTime - time

    return "-" + fmtTime(timer)
}

window.savedAudiosPages = {}

class bigPlayer {
    tracks = {
        currentTrack: null,
        nextTrack: null,
        previousTrack: null,
        tracks: []
    }

    context = {
        context_type: null,
        context_id: 0,
        pagesCount: 0,
        playedPages: [],
        object: [],
    }

    nodes = {
        dashPlayer: null,
        audioPlayer: null,
        thisPlayer: null,
        playButtons: null,
    }

    timeType = 0

    constructor(context, context_id, page = 1) {
        this.context["context_type"] = context
        this.context["context_id"] = context_id
        this.context["playedPages"].push(Number(page))

        this.nodes["thisPlayer"] = document.querySelector(".bigPlayer")
        this.nodes["thisPlayer"].classList.add("lagged")

        this.player = () => { return this.nodes["thisPlayer"].querySelector("audio.audio") }
        this.nodes["playButtons"] = this.nodes["thisPlayer"].querySelector(".playButtons")
        this.nodes["dashPlayer"] = dashjs.MediaPlayer().create()

        let formdata = new FormData()
        formdata.append("context", context)
        formdata.append("context_entity", context_id)
        formdata.append("hash", u("meta[name=csrf]").attr("value"))
        formdata.append("page", page)
    
        ky.post("/audios/context", {
            hooks: {
                afterResponse: [
                    async (_request, _options, response) => {
                        if(response.status !== 200) {
                            fastError(tr("unable_to_load_queue"))
                            return
                        }

                        let contextObject = await response.json()

                        if(!contextObject.success) {
                            fastError(tr("unable_to_load_queue"))
                            return
                        }

                        this.nodes["thisPlayer"].classList.remove("lagged")
                        this.tracks["tracks"] = contextObject["items"]
                        this.context["pagesCount"] = contextObject["pagesCount"]

                        if(localStorage.lastPlayedTrack != null && this.tracks["tracks"].find(item => item.id == localStorage.lastPlayedTrack) != null) {
                            this.setTrack(localStorage.lastPlayedTrack)
                            this.pause()
                        }

                        console.info("Context is successfully loaded")
                    }
                ]
            }, 
            body: formdata
        })

        u(this.nodes["playButtons"].querySelector(".playButton")).on("click", (e) => {
            if(this.player().paused)
                this.play()
            else
                this.pause()
        })

        u(this.player()).on("timeupdate", (e) => {
            const time = this.player().currentTime;
            const ps = Math.ceil((time * 100) / this.tracks["currentTrack"].length);
            this.nodes["thisPlayer"].querySelector(".time").innerHTML = fmtTime(time)
            this.timeType == 0 ? this.nodes["thisPlayer"].querySelector(".elapsedTime").innerHTML = getElapsedTime(this.tracks["currentTrack"].length, time)
                : null

            if (ps <= 100)
                this.nodes["thisPlayer"].querySelector(".selectableTrack .slider").style.left = `${ ps}%`;

        })

        u(this.player()).on("volumechange", (e) => {
            const volume = this.player().volume;
            const ps = Math.ceil((volume * 100) / 1);

            if (ps <= 100)
                this.nodes["thisPlayer"].querySelector(".volumePanel .selectableTrack .slider").style.left = `${ ps}%`;
            
            localStorage.volume = volume
        })

        u(".bigPlayer .track > div").on("click mouseup", (e) => {
            if(this.tracks["currentTrack"] == null)
                return

            let rect  = this.nodes["thisPlayer"].querySelector(".selectableTrack").getBoundingClientRect();
            
            const width = e.clientX - rect.left;
            const time = Math.ceil((width * this.tracks["currentTrack"].length) / (rect.right - rect.left));

            this.player().currentTime = time;
        })

        u(".bigPlayer .trackPanel .track").on("mousemove", (e) => {
            if(this.tracks["currentTrack"] == null)
                return

            let rect  = this.nodes["thisPlayer"].querySelector(".selectableTrack").getBoundingClientRect();
            
            const width = e.clientX - rect.left;
            const time = Math.ceil((width * this.tracks["currentTrack"].length) / (rect.right - rect.left));

            document.querySelector(".bigPlayer .track .timeTip").style.display = "block"
            document.querySelector(".bigPlayer .track .timeTip").innerHTML = fmtTime(time)
            document.querySelector(".bigPlayer .track .timeTip").style.left = `min(${width - 15}px, 315.5px)`
        })

        u(".bigPlayer .trackPanel .track").on("mouseleave", (e) => {
            if(this.tracks["currentTrack"] == null)
                return

            document.querySelector(".bigPlayer .track .timeTip").style.display = "none"
        })

        u(".bigPlayer .volumePanel > div").on("click mouseup mousemove", (e) => {
            if(this.tracks["currentTrack"] == null)
                return

            if(e.type == "mousemove") {
                let buttonsPresseed = _bsdnUnwrapBitMask(e.buttons)
                if(!buttonsPresseed[0])
                    return;
            }

            let rect  = this.nodes["thisPlayer"].querySelector(".volumePanel .selectableTrack").getBoundingClientRect();
            
            const width = e.clientX - rect.left;
            const volume = (width * 1) / (rect.right - rect.left);

            this.player().volume = volume;
        })

        u(".bigPlayer .elapsedTime").on("click", (e) => {
            if(this.tracks["currentTrack"] == null)
                return

            this.timeType == 0 ? (this.timeType = 1) : (this.timeType = 0)

            localStorage.playerTimeType = this.timeType

            this.nodes["thisPlayer"].querySelector(".elapsedTime").innerHTML = this.timeType == 1 ? 
                fmtTime(this.tracks["currentTrack"].length) 
                : getElapsedTime(this.tracks["currentTrack"].length, this.player().currentTime)
        })

        u(".bigPlayer .additionalButtons .repeatButton").on("click", (e) => {
            if(this.tracks["currentTrack"] == null)
                return

            e.currentTarget.classList.toggle("pressed")

            if(e.currentTarget.classList.contains("pressed"))
                this.player().loop = true
            else
                this.player().loop = false
        })

        u(".bigPlayer .additionalButtons .shuffleButton").on("click", (e) => {
            if(this.tracks["currentTrack"] == null)
                return

            this.tracks["tracks"].sort(() => Math.random() - 0.5)
            this.setTrack(this.tracks["tracks"].at(0).id)
        })

        // хз что она делала в самом вк, но тут сделаем вид что это просто мут музыки
        u(".bigPlayer .additionalButtons .deviceButton").on("click", (e) => {
            if(this.tracks["currentTrack"] == null)
                return

            e.currentTarget.classList.toggle("pressed")

            if(e.currentTarget.classList.contains("pressed"))
                this.player().muted = true
            else
                this.player().muted = false
        })

        u(".bigPlayer .arrowsButtons .nextButton").on("click", (e) => {
            this.showPreviousTrack()
        })

        u(".bigPlayer .arrowsButtons .backButton").on("click", (e) => {
            this.showNextTrack()
        })

        u(document).on("keydown", (e) => {
            if(["ArrowUp", "ArrowDown", "ArrowLeft", "ArrowRight"].includes(e.key)) {
                e.preventDefault()

                if(document.querySelector(".ovk-diag-cont") != null)
                    return
            }

            switch(e.key) {
                case "ArrowUp":
                    this.player().volume = Math.min(0.99, this.player().volume + 0.1)
                    break
                case "ArrowDown":
                    this.player().volume = Math.max(0, this.player().volume - 0.1)
                    break
                case "ArrowLeft":
                    this.player().currentTime = this.player().currentTime - 3
                    break
                case "ArrowRight":
                    this.player().currentTime = this.player().currentTime + 3
                    break
            }
        })

        u(document).on("keyup", (e) => {
            if([32, 87, 65, 83, 68].includes(e.keyCode)) {
                e.preventDefault()
                
                if(document.querySelector(".ovk-diag-cont") != null)
                    return
            }

            switch(e.keyCode) {
                case 32:
                    if(this.player().paused)
                        this.play()
                    else 
                        this.pause()

                    break
                case 87:
                case 65:
                    this.showPreviousTrack()
                    break
                case 83:
                case 68:
                    this.showNextTrack()
                    break
            }
        })

        u(this.player()).on("ended", (e) => {
            e.preventDefault()

            this.showNextTrack()
        })

        if(localStorage.volume != null && localStorage.volume < 1 && localStorage.volume > 0)
            this.player().volume = localStorage.volume
        else
            this.player().volume = 0.75

        if(localStorage.playerTimeType == 'null' || localStorage.playerTimeType == null)
            this.timeType = 0
        else
            this.timeType = localStorage.playerTimeType

        
        navigator.mediaSession.setActionHandler('play', () => { this.play() });
        navigator.mediaSession.setActionHandler('pause', () => { this.pause() });
        navigator.mediaSession.setActionHandler('previoustrack', () => { this.showPreviousTrack() });
        navigator.mediaSession.setActionHandler('nexttrack', () => { this.showNextTrack() });
        navigator.mediaSession.setActionHandler("seekto", (details) => {
            this.player().currentTime = details.seekTime;
        });
    }

    play() {
        if(this.tracks["currentTrack"] == null)
            return

        document.querySelectorAll('audio').forEach(el => el.pause());
        document.querySelector(`.audioEmbed[data-realid='${this.tracks["currentTrack"].id}'] .audioEntry .playerButton .playIcon`) != null ? document.querySelector(`.audioEmbed[data-realid='${this.tracks["currentTrack"].id}'] .audioEntry .playerButton .playIcon`).classList.add("paused") : void(0)
        this.player().play()
        this.nodes["playButtons"].querySelector(".playButton").classList.add("pause")
        document.querySelector('link[rel="icon"], link[rel="shortcut icon"]').setAttribute("href", "/assets/packages/static/openvk/img/favicons/favicon24_paused.png")
    
        navigator.mediaSession.playbackState = "playing"
    }
    
    pause() {
        if(this.tracks["currentTrack"] == null) {
            return
        }

        document.querySelector(`.audioEmbed[data-realid='${this.tracks["currentTrack"].id}'] .audioEntry .playerButton .playIcon`) != null ? document.querySelector(`.audioEmbed[data-realid='${this.tracks["currentTrack"].id}'] .audioEntry .playerButton .playIcon`).classList.remove("paused") : void(0)
        this.player().pause()
        this.nodes["playButtons"].querySelector(".playButton").classList.remove("pause")
        document.querySelector('link[rel="icon"], link[rel="shortcut icon"]').setAttribute("href", "/assets/packages/static/openvk/img/favicons/favicon24_playing.png")
    
        navigator.mediaSession.playbackState = "paused"
    }

    showPreviousTrack() {
        if(this.tracks["currentTrack"] == null || this.tracks["previousTrack"] == null) {
            return
        }

        this.setTrack(this.tracks["previousTrack"])
    }

    showNextTrack() {
        if(this.tracks["currentTrack"] == null || this.tracks["nextTrack"] == null) {
            return
        }
        
        this.setTrack(this.tracks["nextTrack"])
    }

    updateButtons() {
        // перепутал некст и бек.
        let prevButton = this.nodes["thisPlayer"].querySelector(".nextButton")
        let nextButton = this.nodes["thisPlayer"].querySelector(".backButton")

        if(this.tracks["previousTrack"] == null) {
            prevButton.classList.add("lagged")
        } else {
            prevButton.classList.remove("lagged")
        }

        if(this.tracks["nextTrack"] == null) {
            nextButton.classList.add("lagged")
        } else {
            nextButton.classList.remove("lagged")
        }
    }

    setTrack(id) {
        if(this.tracks["tracks"] == null) {
            console.info("Context is not loaded yet. Wait please")
            return 0;
        }

        document.querySelectorAll(".audioEntry.nowPlaying").forEach(el => el.classList.remove("nowPlaying"))
        let obj = this.tracks["tracks"].find(item => item.id == id)

        if(obj == null) {
            fastError("No audio in context")
            return
        }

        this.nodes["thisPlayer"].querySelector(".trackInfo span").innerHTML = escapeHtml(obj.name) 
        this.nodes["thisPlayer"].querySelector(".trackInfo b").innerHTML = escapeHtml(obj.performer)
        this.nodes["thisPlayer"].querySelector(".trackInfo .time").innerHTML = fmtTime(obj.length)
        this.tracks["currentTrack"] = obj

        let indexOfCurrentTrack = this.tracks["tracks"].indexOf(obj) ?? 0
        this.tracks["nextTrack"] = this.tracks["tracks"].at(indexOfCurrentTrack + 1) != null ? this.tracks["tracks"].at(indexOfCurrentTrack + 1).id : null

        if(indexOfCurrentTrack - 1 >= 0)
            this.tracks["previousTrack"] = this.tracks["tracks"].at(indexOfCurrentTrack - 1).id
        else
            this.tracks["previousTrack"] = null

        if(this.tracks["nextTrack"] == null && Math.max(...this.context["playedPages"]) < this.context["pagesCount"]
            || this.tracks["previousTrack"] == null && (Math.min(...this.context["playedPages"]) > 1)) {
            
            // idk how it works
            let lesser = this.tracks["previousTrack"] == null ? (Math.min(...this.context["playedPages"]) > 1)
                        : Math.max(...this.context["playedPages"]) > this.context["pagesCount"]
            
            let formdata = new FormData()
            formdata.append("context", this.context["context_type"])
            formdata.append("context_entity", this.context["context_id"])
            formdata.append("hash", u("meta[name=csrf]").attr("value"))

            if(lesser)
                formdata.append("page", Math.min(...this.context["playedPages"]) - 1)
            else
                formdata.append("page", Number(Math.max(...this.context["playedPages"])) + 1)
            
            ky.post("/audios/context", {
                hooks: {
                    afterResponse: [
                        async (_request, _options, response) => {
                            let newArr = await response.json()

                            if(lesser)
                                this.tracks["tracks"] = newArr["items"].concat(this.tracks["tracks"])
                            else
                                this.tracks["tracks"] = this.tracks["tracks"].concat(newArr["items"])

                            this.context["playedPages"].push(Number(newArr["page"]))

                            if(lesser)
                                this.tracks["previousTrack"] = this.tracks["tracks"].at(this.tracks["tracks"].indexOf(obj) - 1).id
                            else
                                this.tracks["nextTrack"] = this.tracks["tracks"].at(indexOfCurrentTrack + 1) != null ? this.tracks["tracks"].at(indexOfCurrentTrack + 1).id : null
                            
                            this.updateButtons()
                            console.info("Context is successfully loaded")
                        }
                    ]
                }, 
                body: formdata
            })
        }

        if(this.tracks["currentTrack"].available == false || this.tracks["currentTrack"].withdrawn)
            this.showNextTrack()

        this.updateButtons()

        const protData = {
            "org.w3.clearkey": {
                "clearkeys": obj.keys
            }
        };

        this.nodes["dashPlayer"].initialize(this.player(), obj.url, false);
        this.nodes["dashPlayer"].setProtectionData(protData);

        this.play()

        document.querySelector(`.audioEmbed[data-realid='${this.tracks["currentTrack"].id}'] .audioEntry`) != null ? 
            document.querySelector(`.audioEmbed[data-realid='${this.tracks["currentTrack"].id}'] .audioEntry`).classList.add("nowPlaying") :
            null

        document.querySelectorAll(`.audioEntry .playerButton .playIcon.paused`).forEach(el => el.classList.remove("paused"))
        localStorage.lastPlayedTrack = this.tracks["currentTrack"].id

        if(this.timeType == 1)
            this.nodes["thisPlayer"].querySelector(".elapsedTime").innerHTML = fmtTime(this.tracks["currentTrack"].length)

        let tempThisTrack = this.tracks["currentTrack"]
        // если трек слушали больше 10 сек.
        setTimeout(() => {
            if(tempThisTrack.id != this.tracks["currentTrack"].id)
                return;

            $.ajax({
                type: "POST",
                url: `/audio${id}/listen`,
                data: {
                    hash: u("meta[name=csrf]").attr("value"),
                },
                success: (response) => {
                    if(response.success)
                        console.info("Listen is counted.")
                    else
                        console.info("Listen is not counted.")
                }
            })
        }, "10000")

        let album = document.querySelector(".playlistBlock")

        navigator.mediaSession.metadata = new MediaMetadata({
            title: obj.name,
            artist: obj.performer,
            album: album == null ? "OpenVK Audios" : album.querySelector(".playlistInfo h4").innerHTML,
            artwork: [{ src: album == null ? "/assets/packages/static/openvk/img/song.jpg" : album.querySelector(".playlistCover img").src }],
        });

        navigator.mediaSession.setPositionState({
            duration: this.tracks["currentTrack"].length
        })
    }
}

document.addEventListener("DOMContentLoaded", function() {
    if(document.querySelector(".bigPlayer") != null) {
        let context = document.querySelector("input[name='bigplayer_context']")

        if(!context)
            return
        
        let type = context.dataset.type
        let entity = context.dataset.entity
        window.player = new bigPlayer(type, entity, context.dataset.page)

        let bigplayer = document.querySelector('.bigPlayerDetector')

        let bigPlayerObserver = new IntersectionObserver(entries => {
            entries.forEach(x => {
                if(x.isIntersecting) {
                    document.querySelector('.bigPlayer').classList.remove("floating")
                    document.querySelector('.bigPlayerDetector').style.marginTop = "0px"
                } else {
                    document.querySelector('.bigPlayer').classList.add("floating")
                    document.querySelector('.bigPlayerDetector').style.marginTop = "46px"
                }
            });
        }, {
            root: null,
            rootMargin: "0px",
            threshold: 0
        });

        if(bigplayer != null)
            bigPlayerObserver.observe(bigplayer);
}})

function initPlayer(id, keys, url, length) {
    document.querySelector(`#audioEmbed-${ id}`).classList.add("inited")
    const audio = document.querySelector(`#audioEmbed-${ id} .audio`);
    const playButton = u(`#audioEmbed-${ id} .playerButton > .playIcon`);
    const trackDiv = u(`#audioEmbed-${ id} .track > div > div`);
    const volumeSpan = u(`#audioEmbed-${ id} .volume span`);
    const rect = document.querySelector(`#audioEmbed-${ id} .selectableTrack`).getBoundingClientRect();
    
    const playerObject = document.querySelector(`#audioEmbed-${ id}`)

    if(document.querySelector(".bigPlayer") != null) {
        playButton.on("click", () => {
            if(window.player.tracks["tracks"] == null)
                return

            if(window.player.tracks["currentTrack"] == null || window.player.tracks["currentTrack"].id != playerObject.dataset.realid) {
                window.player.setTrack(playerObject.dataset.realid)
                playButton.addClass("paused")
            } else
                document.querySelector(".bigPlayer .playButton").click()
        })

        return
    }
    
    const protData = {
        "org.w3.clearkey": {
            "clearkeys": keys
        }
    };

    const player = dashjs.MediaPlayer().create();
    player.initialize(audio, url, false);
    player.setProtectionData(protData);

    playButton.on("click", () => {
        if (audio.paused) {
            document.querySelectorAll('audio').forEach(el => el.pause());
            audio.play();
        } else {
            audio.pause();
        }
    });

    u(audio).on("timeupdate", () => {
        const time = audio.currentTime;
        const ps = Math.ceil((time * 100) / length);
        volumeSpan.html(fmtTime(Math.floor(time)));
        if (ps <= 100)
            trackDiv.nodes[0].style.width = `${ ps}%`;
    });

    const playButtonImageUpdate = () => {
        if (!audio.paused) {
            playButton.addClass("paused")
            document.querySelector('link[rel="icon"], link[rel="shortcut icon"]').setAttribute("href", "/assets/packages/static/openvk/img/favicons/favicon24_paused.png")
        } else {
            playButton.removeClass("paused")
            document.querySelector('link[rel="icon"], link[rel="shortcut icon"]').setAttribute("href", "/assets/packages/static/openvk/img/favicons/favicon24_playing.png")
        }
        
        if(!$(`#audioEmbed-${ id}`).hasClass("havePlayed")) {
            $(`#audioEmbed-${ id}`).addClass("havePlayed")

            $(`#audioEmbed-${ id} .track`).toggle()

            $.post(`/audio${playerObject.dataset.realid}/listen`, {
                hash: u("meta[name=csrf]").attr("value")
            });
        }
    };

    u(audio).on("play", playButtonImageUpdate);
    u(audio).on(["pause", "ended", "suspended"], playButtonImageUpdate);

    u(`#audioEmbed-${ id} .track > div`).on("click", (e) => {
        let rect  = document.querySelector("#audioEmbed-" + id + " .selectableTrack").getBoundingClientRect();
        const width = e.clientX - rect.left;
        const time = Math.ceil((width * length) / (rect.right - rect.left));

        audio.currentTime = time;
    });
}

$(document).on("click", ".musicIcon.edit-icon", (e) => {
    let player = e.currentTarget.closest(".audioEmbed")
    let id = Number(player.dataset.realid)
    let performer = e.currentTarget.dataset.performer
    let name = e.currentTarget.dataset.title
    let genre = player.dataset.genre
    let lyrics = e.currentTarget.dataset.lyrics
    
    MessageBox(tr("edit"), `
        <div>
            ${tr("performer")}
            <input name="performer" maxlength="40" type="text" value="${performer}">
        </div>

        <div style="margin-top: 11px">
            ${tr("audio_name")}
            <input name="name" maxlength="40" type="text" value="${name}">
        </div>

        <div style="margin-top: 11px">
            ${tr("genre")}
            <select name="genre"></select>
        </div>

        <div style="margin-top: 11px">
            ${tr("lyrics")}
            <textarea name="lyrics" maxlength="500">${lyrics ?? ""}</textarea>
        </div>

        <div style="margin-top: 11px">
            <label><input type="checkbox" name="explicit" ${e.currentTarget.dataset.explicit == 1 ? "checked" : ""}>${tr("audios_explicit")}</label><br>
            <label><input type="checkbox" name="searchable" ${e.currentTarget.dataset.searchable == 1 ? "checked" : ""}>${tr("searchable")}</label>
            <hr>
            <a id="_fullyDeleteAudio">${tr("fully_delete_audio")}</a>
        </div>
    `, [tr("ok"), tr("cancel")], [
        function() {
            let t_name   = $(".ovk-diag-body input[name=name]").val();
            let t_perf   = $(".ovk-diag-body input[name=performer]").val();
            let t_genre  = $(".ovk-diag-body select[name=genre]").val();
            let t_lyrics = $(".ovk-diag-body textarea[name=lyrics]").val();
            let t_explicit = document.querySelector(".ovk-diag-body input[name=explicit]").checked;
            let t_unlisted = document.querySelector(".ovk-diag-body input[name=searchable]").checked;

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
                        let perf = player.querySelector(".performer a")
                        perf.innerHTML = escapeHtml(response.new_info.performer)
                        perf.setAttribute("href", "/search?query=&type=audios&sort=id&only_performers=on&query="+response.new_info.performer)
                        
                        e.currentTarget.setAttribute("data-performer", escapeHtml(response.new_info.performer))
                        
                        let name = player.querySelector(".title")
                        name.innerHTML = escapeHtml(response.new_info.name)

                        e.currentTarget.setAttribute("data-title", escapeHtml(response.new_info.name))
                        
                        if(player.querySelector(".lyrics") != null) {
                            player.querySelector(".lyrics").innerHTML = response.new_info.lyrics
                            player.querySelector(".title").classList.ad
                        } else {
                            player.insertAdjacentHTML("beforeend", `
                                <div class="lyrics" n:if="!empty($audio->getLyrics())">
                                    ${response.new_info.lyrics}
                                </div>
                            `)
                        }

                        e.currentTarget.setAttribute("data-lyrics", response.new_info.lyrics_unformatted)
                        e.currentTarget.setAttribute("data-explicit", Number(response.new_info.explicit))
                        e.currentTarget.setAttribute("data-searchable", Number(response.new_info.unlisted))
                        player.setAttribute("data-genre", response.new_info.genre)

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
        u("body").removeClass("dimmed");
        document.querySelector("html").style.overflowY = "scroll"

        u(".ovk-diag-cont").remove();

        $.ajax({
            type: "POST",
            url: `/audio${id}/action?act=delete`,
            data: {
                hash: u("meta[name=csrf]").attr("value")
            },
            success: (response) => {
                if(response.success)
                    u(player).remove()
                else
                    fastError(response.flash.message)
            }
        });
    })
})

$(document).on("click", ".title.withLyrics", (e) => {
    let parent = e.currentTarget.closest(".audioEmbed")

    parent.querySelector(".lyrics").classList.toggle("showed")
})

$(document).on("click", ".musicIcon.remove-icon", (e) => {
    let id = e.currentTarget.dataset.id

    let formdata = new FormData()
    formdata.append("hash", u("meta[name=csrf]").attr("value"))

    ky.post(`/audio${id}/action?act=remove`, {
        hooks: {
            beforeRequest: [
                (_request) => {
                    e.currentTarget.classList.add("lagged")
                }
            ],
            afterResponse: [
                async (_request, _options, response) => {
                    let json = await response.json()

                    if(json.success) {
                        e.currentTarget.classList.remove("remove-icon")
                        e.currentTarget.classList.add("add-icon")
                        e.currentTarget.classList.remove("lagged")

                        let withd = e.currentTarget.closest(".audioEmbed.withdrawn")

                        if(withd != null)
                            u(withd).remove()
                    } else
                        fastError(json.flash.message)
                }
            ]
        }, body: formdata
    })
})

$(document).on("click", ".musicIcon.add-icon", (e) => {
    let id = e.currentTarget.dataset.id

    let formdata = new FormData()
    formdata.append("hash", u("meta[name=csrf]").attr("value"))

    ky.post(`/audio${id}/action?act=add`, {
        hooks: {
            beforeRequest: [
                (_request) => {
                    e.currentTarget.classList.add("lagged")
                }
            ],
            afterResponse: [
                async (_request, _options, response) => {
                    let json = await response.json()

                    if(json.success) {
                        e.currentTarget.classList.remove("add-icon")
                        e.currentTarget.classList.add("remove-icon")
                        e.currentTarget.classList.remove("lagged")
                    } else
                       fastError(json.flash.message)
                }
            ]
        }, body: formdata
    })
})

$(document).on("click", "#_deletePlaylist", (e) => {
    let id = e.currentTarget.dataset.id

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
                window.location.assign("/playlists" + response.id)
            } else {
                fastError(response.flash.message)
            }
        }
    })
})

$(document).on("click", "#_audioAttachment", (e) => {
    let form = e.currentTarget.closest("form")
    let body = `
        <div class="searchBox">
            <input name="query" type="text" placeholder="${tr("header_search")}">
        </div>

        <div class="audiosInsert"></div>
    `
    MessageBox(tr("select_audio"), body, [tr("ok")], [Function.noop])

    document.querySelector(".ovk-diag-body").style.padding = "0"
    document.querySelector(".ovk-diag-cont").style.width = "580px"
    document.querySelector(".ovk-diag-body").style.height = "335px"

    async function insertAudios(page, query = "") {
        document.querySelector(".audiosInsert").insertAdjacentHTML("beforeend", `<img id="loader" src="/assets/packages/static/openvk/img/loading_mini.gif">`)

        $.ajax({
            type: "POST",
            url: "/audios/context",
            data: {
                context: query == "" ? "entity_audios" : "search_context",
                hash: u("meta[name=csrf]").attr("value"),
                page: page,
                query: query == "" ? null : query,
                context_entity: 0,
                returnPlayers: 1,
            },
            success: (response) => {
                let domparser = new DOMParser()
                let result = domparser.parseFromString(response, "text/html")

                let pagesCount = result.querySelector("input[name='pagesCount']").value
                let count = Number(result.querySelector("input[name='count']").value)

                if(count < 1) {
                    document.querySelector(".audiosInsert").innerHTML = tr("no_results")
                    return
                }

                result.querySelectorAll(".audioEmbed").forEach(el => {
                    let id = el.dataset.prettyid
                    let name = el.dataset.name
                    let isAttached = (form.querySelector("input[name='audios']").value.includes(`${id},`))
                    document.querySelector(".audiosInsert").insertAdjacentHTML("beforeend", `
                        <div style="display: table;width: 100%;clear: both;">
                            <div style="width: 72%;float: left;">${el.outerHTML}</div>
                            <div class="attachAudio" data-attachmentdata="${id}" data-name="${name}">
                                <span>${isAttached ? tr("detach_audio") : tr("attach_audio")}</span>
                            </div>
                        </div>
                    `)
                })

                u("#loader").remove()

                if(page < pagesCount) {
                    document.querySelector(".audiosInsert").insertAdjacentHTML("beforeend", `
                    <div id="showMoreAudios" data-pagesCount="${pagesCount}" data-page="${page + 1}" style="width: 100%;text-align: center;background: #d5d5d5;height: 22px;padding-top: 9px;cursor:pointer;">
                        <span>more...</span>
                    </div>`)
                }
            }
        })
    }

    insertAudios(1)

    $(".audiosInsert").on("click", "#showMoreAudios", (e) => {
        u(e.currentTarget).remove()
        insertAudios(Number(e.currentTarget.dataset.page))
    })

    $(".searchBox input").on("change", async (e) => {
        await new Promise(r => setTimeout(r, 1000));

        if(e.currentTarget.value === document.querySelector(".searchBox input").value) {
            document.querySelector(".audiosInsert").innerHTML = ""
            insertAudios(1, e.currentTarget.value)
            return;
        }
    })

    function insertAttachment(id) {
        let audios = form.querySelector("input[name='audios']") 

        if(!audios.value.includes(id + ",")) {
            if(audios.value.split(",").length > 10) {
                NewNotification(tr("error"), tr("max_attached_audios"))
                return false
            }

            form.querySelector("input[name='audios']").value += (id + ",")

            return true
        } else {
            form.querySelector("input[name='audios']").value = form.querySelector("input[name='audios']").value.replace(id + ",", "")

            return false
        }
    }

    $(".audiosInsert").on("click", ".attachAudio", (ev) => {
        if(!insertAttachment(ev.currentTarget.dataset.attachmentdata)) {
            u(`.post-has-audios .post-has-audio[data-id='${ev.currentTarget.dataset.attachmentdata}']`).remove()
            ev.currentTarget.querySelector("span").innerHTML = tr("attach_audio")
        } else {
            ev.currentTarget.querySelector("span").innerHTML = tr("detach_audio")

            form.querySelector(".post-has-audios").insertAdjacentHTML("beforeend", `
                <div class="post-has-audio" id="unattachAudio" data-id="${ev.currentTarget.dataset.attachmentdata}">
                    <span>${ovk_proc_strtr(escapeHtml(ev.currentTarget.dataset.name), 40)}</span>
                </div>
            `)

            u(`#unattachAudio[data-id='${ev.currentTarget.dataset.attachmentdata}']`).on("click", (e) => {
                let id = ev.currentTarget.dataset.attachmentdata
                form.querySelector("input[name='audios']").value = form.querySelector("input[name='audios']").value.replace(id + ",", "")

                u(e.currentTarget).remove()
            })
        }
    })
})

$(document).on("click", ".audioEmbed.processed", (e) => {
    MessageBox(tr("error"), tr("audio_embed_processing"), [tr("ok")], [Function.noop])
})

$(document).on("click", ".audioEmbed.withdrawn", (e) => {
    MessageBox(tr("error"), tr("audio_embed_withdrawn"), [tr("ok")], [Function.noop])
})

$(document).on("click", ".musicIcon.report-icon", (e) => {
    MessageBox(tr("report_question"), `
        ${tr("going_to_report_audio")}
        <br/>${tr("report_question_text")}
        <br/><br/><b> ${tr("report_reason")}</b>: <input type='text' id='uReportMsgInput' placeholder='${tr("reason")}' />`, [tr("confirm_m"), tr("cancel")], [(function() {
        
        res = document.querySelector("#uReportMsgInput").value;
        xhr = new XMLHttpRequest();
        xhr.open("GET", "/report/" + e.currentTarget.dataset.id + "?reason=" + res + "&type=audio", true);
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

$(document).on("click", ".audiosContainer .paginator a", (e) => {
    e.preventDefault()
    let url = new URL(e.currentTarget.href)
    let page = url.searchParams.get("p")

    if(window.savedAudiosPages[page] != null) {
        history.pushState({}, "", e.currentTarget.href)
        document.querySelector(".audiosContainer").innerHTML = window.savedAudiosPages[page].innerHTML
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

            if(window.player.context["playedPages"].indexOf(page) == -1) {
                $.ajax({
                    type: "POST",
                    url: "/audios/context",
                    data: {
                        context: window.player["context"].context_type,
                        context_entity: window.player["context"].context_id,
                        hash: u("meta[name=csrf]").attr("value"),
                        page: page
                    },
                    success: (response_2) => {
                        window.player.tracks["tracks"] = window.player.tracks["tracks"].concat(response_2["items"])
                        window.player.context["playedPages"].push(page)
                        console.info("Page is switched")
                    }
                })
            }
        }
    })

    let node = document.querySelector(`.audioEmbed[data-realid='${window.player["tracks"].currentTrack != null ? window.player["tracks"].currentTrack.id : 0}'] .audioEntry`)
                
    if(node != null) {
        node.classList.add("nowPlaying")
    }
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

function getPlayers(page = 1, query = "", playlist = 0) {
    $.ajax({
        type: "POST",
        url: "/audios/context",
        data: {
            context: query == "" ? (playlist == 0 ? "entity_audios" : "playlist_context") : "search_context",
            hash: u("meta[name=csrf]").attr("value"),
            page: page,
            context_entity: playlist,
            query: query,
            returnPlayers: 1,
        },
        beforeSend: () => {
            document.querySelector(".playlistAudiosContainer").insertAdjacentHTML("beforeend", `<img id="loader" src="/assets/packages/static/openvk/img/loading_mini.gif">`)
            
            if(document.querySelector(".showMoreAudiosPlaylist") != null)
                document.querySelector(".showMoreAudiosPlaylist").style.display = "none"
        },
        error: () => {
            fastError("Error when loading players")
        },
        success: (response) => {
            let domparser = new DOMParser()
            let result = domparser.parseFromString(response, "text/html")
            let pagesCount = Number(result.querySelector("input[name='pagesCount']").value)
            let count = Number(result.querySelector("input[name='count']").value)

            result.querySelectorAll(".audioEmbed").forEach(el => {
                let id = Number(el.dataset.realid)
                let isAttached = (document.querySelector("input[name='audios']").value.includes(`${id},`))

                document.querySelector(".playlistAudiosContainer").insertAdjacentHTML("beforeend", `
                    <div id="newPlaylistAudios">
                        <div style="width: 78%;float: left;">
                            ${el.outerHTML}
                        </div>
                        <div class="attachAudio addToPlaylist" data-id="${id}" style="width: 22%;">
                            <span>${isAttached ? tr("remove_from_playlist") : tr("add_to_playlist")}</span>
                        </div>
                    </div>
                `)
            })

            if(count < 1)
                document.querySelector(".playlistAudiosContainer").insertAdjacentHTML("beforeend", `
                    ${tr("no_results")}
                `)

            if(Number(page) >= pagesCount)
                u(".showMoreAudiosPlaylist").remove()
            else {
                if(document.querySelector(".showMoreAudiosPlaylist") != null) {
                    document.querySelector(".showMoreAudiosPlaylist").setAttribute("data-page", page + 1)
                    document.querySelector(".showMoreAudiosPlaylist").style.display = "block"
                } else {
                    document.querySelector(".playlistAudiosContainer").parentNode.insertAdjacentHTML("beforeend", `
                        <div class="showMoreAudiosPlaylist" data-page="2">
                            ${tr("show_more_audios")}
                        </div>
                    `)
                }
            }

            u("#loader").remove()
        }
    })
}

$(document).on("click", ".showMoreAudiosPlaylist", (e) => {
    getPlayers(Number(e.currentTarget.dataset.page), "", e.currentTarget.dataset.playlist != null ? Number(e.currentTarget.dataset.playlist) : 0)
})

$(document).on("change", "input#playlist_query", async (e) => {
    e.preventDefault()
    await new Promise(r => setTimeout(r, 500));

    if(e.currentTarget.value === document.querySelector("input#playlist_query").value) {
        document.querySelector(".playlistAudiosContainer").innerHTML = ""
        getPlayers(1, e.currentTarget.value)
        return
    } else
        return
})
