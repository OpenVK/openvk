function fmtTime(time) {
    const mins = String(Math.floor(time / 60)).padStart(2, '0');
    const secs = String(Math.floor(time % 60)).padStart(2, '0');
    return `${ mins}:${ secs}`;
}

function initPlayer(id, keys, url, length) {
    document.querySelector(`#audioEmbed-${ id}`).classList.add("inited")
    const audio = document.querySelector(`#audioEmbed-${ id} .audio`);
    const playButton = u(`#audioEmbed-${ id} .playerButton > .playIcon`);
    const trackDiv = u(`#audioEmbed-${ id} .track > div > div`);
    const volumeSpan = u(`#audioEmbed-${ id} .volume span`);
    const rect = document.querySelector(`#audioEmbed-${ id} .selectableTrack`).getBoundingClientRect();

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
        if ($(`#audioEmbed-${ id} .claimed`).length === 0) {
            console.log(id);
        }

        if (!audio.paused) {
            playButton.addClass("paused")
            $.post(`/audio${ id}/listen`, {
                hash: u("meta[name=csrf]").attr("value")
            });
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
        console.log(width, length, rect.right, rect.left, time);
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
    `, [tr("ok"), tr("cancel")], [
        function() {
            let t_name   = $(".ovk-diag-body input[name=name]").val();
            let t_perf   = $(".ovk-diag-body input[name=performer]").val();
            let t_genre  = $(".ovk-diag-body select[name=genre]").val();
            let t_lyrics = $(".ovk-diag-body textarea[name=lyrics]").val();

            $.ajax({
                type: "POST",
                url: `/audio${id}/action?act=edit`,
                data: {
                    name: t_name,
                    performer: t_perf,
                    genre: t_genre,
                    lyrics: t_lyrics,
                    hash: u("meta[name=csrf]").attr("value")
                },
                success: (response) => {
                    if(response.success) {
                        let perf = player.querySelector(".performer a")
                        perf.innerHTML = response.new_info.performer
                        perf.setAttribute("href", "/search?query=&type=audios&sort=id&only_performers=on&query="+response.new_info.performer)
                        
                        e.currentTarget.setAttribute("data-performer", response.new_info.performer)
                        let name = player.querySelector(".title")
                        name.innerHTML = escapeHtml(response.new_info.name)

                        e.currentTarget.setAttribute("data-title", response.new_info.name)
                        
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
                        player.setAttribute("data-genre", response.new_info.genre)
                        console.log(response)
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
