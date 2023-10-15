function fmtTime(time) {
    const mins = String(Math.floor(time / 60)).padStart(2, '0');
    const secs = String(Math.floor(time % 60)).padStart(2, '0');
    return `${ mins}:${ secs}`;
}

function fastError(message) {
    MessageBox(tr("error"), message, [tr("ok")], [Function.noop])
}

// нихуя я тут насрал
class bigPlayer {
    contextObject = []
    player = null
    currentTrack = null
    dashPlayer = null

    constructor(context, context_id, page = 1) {
        this.context = context
        this.context_id = context_id
        this.playerNode = document.querySelector(".bigPlayer")

        this.playerNode.classList.add("lagged")

        this.performer = this.playerNode.querySelector(".trackInfo b")
        this.name = this.playerNode.querySelector(".trackInfo span")
        this.time = this.playerNode.querySelector(".trackInfo .time")
        this.player = () => { return this.playerNode.querySelector("audio.audio") }
        this.playButtons = this.playerNode.querySelector(".playButtons")

        this.dashPlayer = dashjs.MediaPlayer().create()

        let formdata = new FormData()
        formdata.append("context", context)
        formdata.append("context_entity", context_id)
        formdata.append("hash", u("meta[name=csrf]").attr("value"))
        formdata.append("page", page)
    
        ky.post("/audios/context", {
            hooks: {
                afterResponse: [
                    async (_request, _options, response) => {
                        this.contextObject = await response.json()
                        this.playerNode.classList.remove("lagged")
                        console.info("Context is successfully loaded")
                    }
                ]
            }, 
            body: formdata
        })

        u(this.playButtons.querySelector(".playButton")).on("click", (e) => {
            if(this.player().paused) {
                this.play()
            } else {
                this.pause()
            }
        })

        u(this.player()).on("timeupdate", (e) => {
            const time = this.player().currentTime;
            const ps = Math.ceil((time * 100) / this.currentTrack.length);
            this.time.innerHTML = fmtTime(time)

            if (ps <= 100)
                this.playerNode.querySelector(".selectableTrack .slider").style.left = `${ ps}%`;

        })

        u(this.player()).on("volumechange", (e) => {
            const volume = this.player().volume;
            const ps = Math.ceil((volume * 100) / 1);

            if (ps <= 100)
                this.playerNode.querySelector(".volumePanel .selectableTrack .slider").style.left = `${ ps}%`;
        })

        u(".bigPlayer .track > div").on("click mouseup", (e) => {
            if(this.currentTrack == null) {
                return
            }

            let rect  = this.playerNode.querySelector(".selectableTrack").getBoundingClientRect();
            
            const width = e.clientX - rect.left;
            const time = Math.ceil((width * this.currentTrack.length) / (rect.right - rect.left));

            this.player().currentTime = time;
        })

        u(".bigPlayer .volumePanel > div").on("click mouseup", (e) => {
            if(this.currentTrack == null) {
                return
            }

            let rect  = this.playerNode.querySelector(".volumePanel .selectableTrack").getBoundingClientRect();
            
            const width = e.clientX - rect.left;
            const volume = (width * 1) / (rect.right - rect.left);

            this.player().volume = volume;
        })

        u(".bigPlayer .additionalButtons .repeatButton").on("click", (e) => {
            if(this.currentTrack == null) {
                return
            }

            e.currentTarget.classList.toggle("pressed")

            if(e.currentTarget.classList.contains("pressed")) {
                this.player().loop = true
            } else {
                this.player().loop = false
            }
        })

        u(".bigPlayer .arrowsButtons .nextButton").on("click", (e) => {
            this.showPreviousTrack()
        })

        u(".bigPlayer .arrowsButtons .backButton").on("click", (e) => {
            this.showNextTrack()
        })

        u(this.player()).on("ended", (e) => {
            e.preventDefault()

            this.showNextTrack()
        })

        this.player().volume = 0.75
    }

    play() {
        if(this.currentTrack == null) {
            return
        }

        document.querySelectorAll('audio').forEach(el => el.pause());
        document.querySelector(`.audioEmbed[data-realid='${this.currentTrack.id}'] .audioEntry .playerButton .playIcon`) != null ? document.querySelector(`.audioEmbed[data-realid='${this.currentTrack.id}'] .audioEntry .playerButton .playIcon`).classList.add("paused") : void(0)
        this.player().play()
        this.playButtons.querySelector(".playButton").classList.add("pause")
    }
    
    pause() {
        if(this.currentTrack == null) {
            return
        }

        document.querySelector(`.audioEmbed[data-realid='${this.currentTrack.id}'] .audioEntry .playerButton .playIcon`) != null ? document.querySelector(`.audioEmbed[data-realid='${this.currentTrack.id}'] .audioEntry .playerButton .playIcon`).classList.remove("paused") : void(0)
        this.player().pause()
        this.playButtons.querySelector(".playButton").classList.remove("pause")
    }

    showPreviousTrack() {
        if(this.currentTrack == null || this.previousTrack == null) {
            return
        }

        this.setTrack(this.previousTrack)
    }

    showNextTrack() {
        if(this.currentTrack == null || this.nextTrack == null) {
            return
        }
        
        this.setTrack(this.nextTrack)
    }

    updateButtons() {
        // перепутал некст и бек.
        let prevButton = this.playerNode.querySelector(".nextButton")
        let nextButton = this.playerNode.querySelector(".backButton")

        if(this.previousTrack == null) {
            prevButton.classList.add("lagged")
        } else {
            prevButton.classList.remove("lagged")
        }

        if(this.nextTrack == null) {
            nextButton.classList.add("lagged")
        } else {
            nextButton.classList.remove("lagged")
        }
    }

    setTrack(id) {
        if(this.contextObject["items"] == null) {
            console.info("Context is not loaded yet. Wait please")
            return 0;
        }

        document.querySelectorAll(".audioEntry.nowPlaying").forEach(el => el.classList.remove("nowPlaying"))
        let obj = this.contextObject["items"].find(item => item.id == id)

        if(obj == null) {
            fastError("No audio in context")
            return
        }

        this.name.innerHTML = escapeHtml(obj.name) 
        this.performer.innerHTML = escapeHtml(obj.performer)
        this.time.innerHTML = fmtTime(obj.length)
        this.currentTrack = obj

        let indexOfCurrentTrack = this.contextObject["items"].indexOf(obj) ?? 0
        this.nextTrack = this.contextObject["items"].at(indexOfCurrentTrack + 1) != null ? this.contextObject["items"].at(indexOfCurrentTrack + 1).id : null

        if(indexOfCurrentTrack - 1 >= 0) {
            this.previousTrack = this.contextObject["items"].at(indexOfCurrentTrack - 1).id
        } else {
            this.previousTrack = null
        }

        if(this.nextTrack == null && this.contextObject.page < this.contextObject.pagesCount
            || this.previousTrack == null && (this.contextObject.page > 1)) {
            let formdata = new FormData()
            formdata.append("context", this.context)
            formdata.append("context_entity", this.context_id)
            formdata.append("hash", u("meta[name=csrf]").attr("value"))

            let lesser = this.contextObject.page > 1
            if(lesser) {
                formdata.append("page", Number(this.contextObject["page"]) - 1)
            } else {
                formdata.append("page", Number(this.contextObject["page"]) + 1)
            }

            ky.post("/audios/context", {
                hooks: {
                    afterResponse: [
                        async (_request, _options, response) => {
                            let newArr = await response.json()

                            if(lesser) {
                                this.contextObject["items"] = newArr["items"].concat(this.contextObject["items"])
                            } else {
                                this.contextObject["items"] = this.contextObject["items"].concat(newArr["items"])
                            }

                            this.contextObject["page"] = newArr["page"]

                            if(lesser) {
                                this.previousTrack = this.contextObject["items"].at(this.contextObject["items"].indexOf(obj) - 1).id
                            } else {
                                this.nextTrack = this.contextObject["items"].at(indexOfCurrentTrack + 1) != null ? this.contextObject["items"].at(indexOfCurrentTrack + 1).id : null
                            }

                            this.updateButtons()
                            console.info("Context is successfully loaded")
                        }
                    ]
                }, 
                body: formdata
            })
        }

        if(this.currentTrack.available == false || this.currentTrack.withdrawn) {
            this.showNextTrack()
        }

        this.updateButtons()

        const protData = {
            "org.w3.clearkey": {
                "clearkeys": obj.keys
            }
        };

        this.dashPlayer.initialize(this.player(), obj.url, false);
        this.dashPlayer.setProtectionData(protData);

        this.play()

        document.querySelector(`.audioEmbed[data-realid='${this.currentTrack.id}'] .audioEntry`) != null ? 
            document.querySelector(`.audioEmbed[data-realid='${this.currentTrack.id}'] .audioEntry`).classList.add("nowPlaying") :
            null

        document.querySelectorAll(`.audioEntry .playerButton .playIcon.paused`).forEach(el => el.classList.remove("paused"))
    }

    getCurrentTrack() {
        return this.currentTrack
    }
}

document.addEventListener("DOMContentLoaded", function() {
    if(document.querySelector(".bigPlayer") != null) {
        let context = document.querySelector("input[name='bigplayer_context']")
        let type = context.dataset.type
        let entity = context.dataset.entity
        
        window.player = new bigPlayer(type, entity, context.dataset.page)
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
            if(window.player.contextObject == null) {
                return
            }

            if(window.player.getCurrentTrack() == null || window.player.getCurrentTrack().id != playerObject.dataset.realid) {
                window.player.setTrack(playerObject.dataset.realid)
            } else {
                document.querySelector(".bigPlayer .playButton").click()
            }
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

        if(audio.paused) {
            playButtonImageUpdate()
        }
    });

    const playButtonImageUpdate = () => {
        if (!audio.paused) {
            playButton.addClass("paused")
            /*$.post(`/audio${ id}/listen`, {
                hash: u("meta[name=csrf]").attr("value")
            });*/
        } else {
            playButton.removeClass("paused")
        }
        
        if(!$(`#audioEmbed-${ id}`).hasClass("havePlayed")) {
            $(`#audioEmbed-${ id}`).addClass("havePlayed")

            $(`#audioEmbed-${ id} .track`).toggle()
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
            ${tr("audio_name")}
            <input name="name" maxlength="40" type="text" value="${name}">
        </div>

        <div style="margin-top: 11px">
            ${tr("performer")}
            <input name="performer" maxlength="40" type="text" value="${performer}">
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
            <label><input type="checkbox" name="explicit" ${e.currentTarget.dataset.explicit == 1 ? "checked" : ""} maxlength="500">${tr("audios_explicit")}</label>
        </div>
    `, [tr("ok"), tr("cancel")], [
        function() {
            let t_name   = $(".ovk-diag-body input[name=name]").val();
            let t_perf   = $(".ovk-diag-body input[name=performer]").val();
            let t_genre  = $(".ovk-diag-body select[name=genre]").val();
            let t_lyrics = $(".ovk-diag-body textarea[name=lyrics]").val();
            let t_explicit = document.querySelector(".ovk-diag-body input[name=explicit]").checked;

            $.ajax({
                type: "POST",
                url: `/audio${id}/action?act=edit`,
                data: {
                    name: t_name,
                    performer: t_perf,
                    genre: t_genre,
                    lyrics: t_lyrics,
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
                        player.setAttribute("data-genre", response.new_info.genre)
                    } else {
                        MessageBox(tr("error"), response.flash.message, [tr("ok")], [Function.noop])
                    }
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
                        if(withd != null) {
                            u(withd).remove()
                        }
                    } else {
                        MessageBox(tr("error"), json.flash.message, [tr("ok")], [Function.noop])
                    }
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
                    } else {
                        MessageBox(tr("error"), json.flash.message, [tr("ok")], [Function.noop])
                    }
                }
            ]
        }, body: formdata
    })
})

$(document).on("click", "#_audioAttachment", (e) => {
    let body = `
        я ещё не сделал
    `
    MessageBox(tr("select_audio"), body, [tr("ok")], [Function.noop])
})

$(document).on("click", ".audioEmbed.lagged", (e) => {
    MessageBox(tr("error"), tr("audio_embed_processing"), [tr("ok")], [Function.noop])
})

$(document).on("click", ".audioEmbed.withdrawn", (e) => {
    MessageBox(tr("error"), tr("audio_embed_withdrawn"), [tr("ok")], [Function.noop])
})

$(document).on("click", ".musicIcon.report-icon", (e) => {
    MessageBox(tr("report_question"), `
        ${tr("going_to_report_video")}
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

$(document).on("click", "#bookmarkPlaylist", (e) => {

})

$(document).on("click", "#unbookmarkPlaylist", (e) => {
    
})

$(document).on("click", ".audiosContainer .paginator a", (e) => {
    e.preventDefault()

    e.currentTarget.parentNode.classList.add("lagged")

    ky(e.currentTarget.href, {
        hooks: {
            afterResponse: [
                async (_request, _options, response) => {
                    let text = await response.text()
                    let domparse = (new DOMParser()).parseFromString(text, "text/html")

                    document.querySelector(".audiosContainer").innerHTML = domparse.querySelector(".audiosContainer").innerHTML
                    history.pushState(null, null, e.currentTarget.href)

                    let playingId = window.player.currentTrack["id"] ?? 0
                    let maybePlayer = document.querySelector(`.audioEmbed[data-realid='${playingId}'] .audioEntry`)
                    console.log(playingId)

                    if(maybePlayer != null) {
                        maybePlayer.classList.add("nowPlaying")
                    }
                }
            ]
        }
    })

    let url = new URL(location.href)
    let lesser = Number(url.searchParams.get("p")) < window.player.contextObject["page"]

    let formdata = new FormData()
    formdata.append("context", window.player.context)
    formdata.append("context_entity", window.player.context_id)
    formdata.append("hash", u("meta[name=csrf]").attr("value"))
    formdata.append("page", Number(url.searchParams.get("p")) + (lesser ? -1 : 1))

    ky.post("/audios/context", {
        hooks: {
            afterResponse: [
                async (_request, _options, response) => {
                    let newArr = await response.json()
                    let indexOfCurrentTrack = window.player.contextObject["items"].indexOf(window.player.currentTrack) ?? 0

                    if(lesser) {
                        window.player.contextObject["items"] = newArr["items"].concat(window.player.contextObject["items"])
                    } else {
                        window.player.contextObject["items"] = window.player.contextObject["items"].concat(newArr["items"])
                    }

                    window.player.contextObject["page"] = newArr["page"]
                    
                    if(lesser) {
                        window.player.previousTrack = window.player.contextObject["items"].at(window.player.contextObject["items"].indexOf(obj) - 1).id
                     } else {
                        window.player.nextTrack = window.player.contextObject["items"].at(indexOfCurrentTrack + 1) != null ? window.player.contextObject["items"].at(indexOfCurrentTrack + 1).id : null
                    }
                    
                    window.player.updateButtons()
                    console.info("Context is successfully loaded")
                }
            ]
        }, 
        body: formdata
    })
})
