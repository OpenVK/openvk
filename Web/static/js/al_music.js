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

    constructor(context, context_id, page = 1) {
        this.context["context_type"] = context
        this.context["context_id"] = context_id
        this.context["playedPages"].push(page)

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
                        console.info("Context is successfully loaded")
                    }
                ]
            }, 
            body: formdata
        })

        u(this.nodes["playButtons"].querySelector(".playButton")).on("click", (e) => {
            if(this.player().paused) {
                this.play()
            } else {
                this.pause()
            }
        })

        u(this.player()).on("timeupdate", (e) => {
            const time = this.player().currentTime;
            const ps = Math.ceil((time * 100) / this.tracks["currentTrack"].length);
            this.nodes["thisPlayer"].querySelector(".time").innerHTML = fmtTime(time)
            this.nodes["thisPlayer"].querySelector(".elapsedTime").innerHTML = getElapsedTime(this.tracks["currentTrack"].length, time)

            if (ps <= 100)
                this.nodes["thisPlayer"].querySelector(".selectableTrack .slider").style.left = `${ ps}%`;

        })

        u(this.player()).on("volumechange", (e) => {
            const volume = this.player().volume;
            const ps = Math.ceil((volume * 100) / 1);

            if (ps <= 100)
                this.nodes["thisPlayer"].querySelector(".volumePanel .selectableTrack .slider").style.left = `${ ps}%`;
        })

        u(".bigPlayer .track > div").on("click mouseup", (e) => {
            if(this.tracks["currentTrack"] == null) {
                return
            }

            let rect  = this.nodes["thisPlayer"].querySelector(".selectableTrack").getBoundingClientRect();
            
            const width = e.clientX - rect.left;
            const time = Math.ceil((width * this.tracks["currentTrack"].length) / (rect.right - rect.left));

            this.player().currentTime = time;
        })

        u(".bigPlayer .volumePanel > div").on("click mouseup", (e) => {
            if(this.tracks["currentTrack"] == null) {
                return
            }

            let rect  = this.nodes["thisPlayer"].querySelector(".volumePanel .selectableTrack").getBoundingClientRect();
            
            const width = e.clientX - rect.left;
            const volume = (width * 1) / (rect.right - rect.left);

            this.player().volume = volume;
        })

        u(".bigPlayer .additionalButtons .repeatButton").on("click", (e) => {
            if(this.tracks["currentTrack"] == null) {
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

        u(document).on("keydown", (e) => {
            switch(e.key) {
                case "ArrowUp":
                    e.preventDefault()
                    this.player().volume = Math.min(0.99, this.player().volume + 0.1)
                    break
                case "ArrowDown":
                    e.preventDefault()
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
            switch(e.keyCode) {
                case 32:
                    e.preventDefault()
                    if(this.player().paused)
                        this.play()
                    else 
                        this.pause()

                    break
                case 87:
                case 65:
                    e.preventDefault()
                    this.showPreviousTrack()
                    break
                case 83:
                case 68:
                    e.preventDefault()
                    this.showNextTrack()
                    break
            }
        })

        u(this.player()).on("ended", (e) => {
            e.preventDefault()

            this.showNextTrack()
        })

        this.player().volume = 0.75
    }

    play() {
        if(this.tracks["currentTrack"] == null) {
            return
        }

        document.querySelectorAll('audio').forEach(el => el.pause());
        document.querySelector(`.audioEmbed[data-realid='${this.tracks["currentTrack"].id}'] .audioEntry .playerButton .playIcon`) != null ? document.querySelector(`.audioEmbed[data-realid='${this.tracks["currentTrack"].id}'] .audioEntry .playerButton .playIcon`).classList.add("paused") : void(0)
        this.player().play()
        this.nodes["playButtons"].querySelector(".playButton").classList.add("pause")
    }
    
    pause() {
        if(this.tracks["currentTrack"] == null) {
            return
        }

        document.querySelector(`.audioEmbed[data-realid='${this.tracks["currentTrack"].id}'] .audioEntry .playerButton .playIcon`) != null ? document.querySelector(`.audioEmbed[data-realid='${this.tracks["currentTrack"].id}'] .audioEntry .playerButton .playIcon`).classList.remove("paused") : void(0)
        this.player().pause()
        this.nodes["playButtons"].querySelector(".playButton").classList.remove("pause")
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

        if(indexOfCurrentTrack - 1 >= 0) {
            this.tracks["previousTrack"] = this.tracks["tracks"].at(indexOfCurrentTrack - 1).id
        } else {
            this.tracks["previousTrack"] = null
        }

        if(this.tracks["nextTrack"] == null && Math.max(this.context["playedPages"]) < this.context["pagesCount"]
            || this.tracks["previousTrack"] == null && (Math.min(this.context["playedPages"]) > 1)) {
            let formdata = new FormData()
            formdata.append("context", this.context["context_type"])
            formdata.append("context_entity", this.context["context_id"])
            formdata.append("hash", u("meta[name=csrf]").attr("value"))

            let lesser = Math.max(this.context["playedPages"]) > 1
            if(lesser) {
                formdata.append("page", Number(Math.min(this.context["playedPages"])) - 1)
            } else {
                formdata.append("page", Number(Math.max(this.context["playedPages"])) + 1)
            }

            ky.post("/audios/context", {
                hooks: {
                    afterResponse: [
                        async (_request, _options, response) => {
                            let newArr = await response.json()

                            if(lesser) {
                                this.tracks["tracks"] = newArr["items"].concat(this.tracks["tracks"])
                            } else {
                                this.tracks["tracks"] = this.tracks["tracks"].concat(newArr["items"])
                            }

                            this.context["playedPages"].push(newArr["page"])

                            if(lesser) {
                                this.tracks["previousTrack"] = this.tracks["tracks"].at(this.tracks["tracks"].indexOf(obj) - 1).id
                            } else {
                                this.tracks["nextTrack"] = this.tracks["tracks"].at(indexOfCurrentTrack + 1) != null ? this.tracks["tracks"].at(indexOfCurrentTrack + 1).id : null
                            }

                            this.updateButtons()
                            console.info("Context is successfully loaded")
                        }
                    ]
                }, 
                body: formdata
            })
        }

        if(this.tracks["currentTrack"].available == false || this.tracks["currentTrack"].withdrawn) {
            this.showNextTrack()
        }

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
            if(window.player.tracks["tracks"] == null) {
                return
            }

            if(window.player.tracks["currentTrack"] == null || window.player.tracks["currentTrack"].id != playerObject.dataset.realid) {
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

$(document).on("click", "#bookmarkPlaylist", (e) => {

})

$(document).on("click", "#unbookmarkPlaylist", (e) => {
    
})
