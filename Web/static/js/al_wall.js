function humanFileSize(bytes, si) {
    var thresh = si ? 1000 : 1024;
    if(Math.abs(bytes) < thresh) {
        return bytes + ' B';
    }
    var units = si
        ? ['kB','MB','GB','TB','PB','EB','ZB','YB']
        : ['KiB','MiB','GiB','TiB','PiB','EiB','ZiB','YiB'];
    var u = -1;
    do {
        bytes /= thresh;
        ++u;
    } while(Math.abs(bytes) >= thresh && u < units.length - 1);
    return bytes.toFixed(1)+' '+units[u];
}

function trim(string) {
    var newStr = string.substring(0, 10);
    if(newStr.length !== string.length)
        newStr += "…";
    
    return newStr;
}

function trimNum(string, num) {
    var newStr = string.substring(0, num);
    if(newStr.length !== string.length)
        newStr += "…";

    return newStr;
}

function handleUpload(id) {
    console.warn("блять...");
    
    u("#post-buttons" + id + " .postFileSel").not("#" + this.id).each(input => input.value = null);
    
    var indicator = u("#post-buttons" + id + " .post-upload");
    var file      = this.files[0];
    if(typeof file === "undefined") {
        indicator.attr("style", "display: none;");
    } else {
        u("span", indicator.nodes[0]).text(trim(file.name) + " (" + humanFileSize(file.size, false) + ")");
        indicator.attr("style", "display: block;");
    }

    document.querySelector("#post-buttons" + id + " #wallAttachmentMenu").classList.add("hidden");
}

function initGraffiti(id) {
    let canvas = null;
    let msgbox = MessageBox(tr("draw_graffiti"), "<div id='ovkDraw'></div>", [tr("save"), tr("cancel")], [function() {
        canvas.getImage({includeWatermark: false}).toBlob(blob => {
            let fName = "Graffiti-" + Math.ceil(performance.now()).toString() + ".jpeg";
            let image = new File([blob], fName, {type: "image/jpeg", lastModified: new Date().getTime()});
            let trans = new DataTransfer();
            trans.items.add(image);
            
            let fileSelect = document.querySelector("#post-buttons" + id + " input[name='_pic_attachment']");
            fileSelect.files = trans.files;
            
            u(fileSelect).trigger("change");
            u("#post-buttons" + id + " #write textarea").trigger("focusin");
        }, "image/jpeg", 0.92);
        
        canvas.teardown();
    }, function() {
        canvas.teardown();
    }]);
    
    let watermarkImage = new Image();
    watermarkImage.src = "/assets/packages/static/openvk/img/logo_watermark.gif";
    
    msgbox.attr("style", "width: 750px;");
    canvas = LC.init(document.querySelector("#ovkDraw"), {
        backgroundColor: "#fff",
        imageURLPrefix: "/assets/packages/static/openvk/js/node_modules/literallycanvas/lib/img",
        watermarkImage: watermarkImage,
        imageSize: {
            width: 640,
            height: 480
        }
    });
}

$(document).on("click", ".post-like-button", function(e) {
    e.preventDefault();
    
    var thisBtn = u(this).first();
    var link    = u(this).attr("href");
    var heart   = u(".heart", thisBtn);
    var counter = u(".likeCnt", thisBtn);
    var likes   = counter.text() === "" ? 0 : counter.text();
    var isLiked = heart.attr("id") === 'liked';
    
    ky(link);
    heart.attr("id", isLiked ? '' : 'liked');
    counter.text(parseInt(likes) + (isLiked ? -1 : 1));
    if (counter.text() === "0") {
        counter.text("");
    }
    
    return false;
});

function setupWallPostInputHandlers(id) {
    u("#wall-post-input" + id).on("paste", function(e) {
        if(e.clipboardData.files.length === 1) {
            var input = u("#post-buttons" + id + " input[name=_pic_attachment]").nodes[0];
            input.files = e.clipboardData.files;
            
            u(input).trigger("change");
        }
    });
    
    u("#wall-post-input" + id).on("input", function(e) {
        var boost             = 5;
        var textArea          = e.target;
        textArea.style.height = "5px";
        var newHeight = textArea.scrollHeight;
        textArea.style.height = newHeight + boost + "px";
        return;
        
        // revert to original size if it is larger (possibly changed by user)
        // textArea.style.height = (newHeight > originalHeight ? (newHeight + boost) : originalHeight) + "px";
    });
}

u("#write > form").on("keydown", function(event) {
    if(event.ctrlKey && event.keyCode === 13)
        this.submit();
});

var tooltipClientTemplate = Handlebars.compile(`
    <table>
        <tr>
            <td width="54" valign="top">
                <img src="{{img}}" width="54" />
            </td>
            <td width="1"></td>
            <td width="150" valign="top">
                <text>
                    {{app_tr}}: <b>{{name}}</b>
                </text><br/>
                <a href="{{url}}">${tr("learn_more")}</a>
            </td>
        </tr>
    </table>
`);

var tooltipClientNoInfoTemplate = Handlebars.compile(`
    <table>
        <tr>
            <td width="150" valign="top">
                <text>
                    {{app_tr}}: <b>{{name}}</b>
                </text><br/>
            </td>
        </tr>
    </table>
`);

tippy(".client_app", {
    theme: "light vk",
    content: "⌛",
    allowHTML: true,
    interactive: true,
    interactiveDebounce: 500,

    onCreate: async function(that) {
        that._resolvedClient = null;
    },

    onShow: async function(that) {
        let client_tag = that.reference.dataset.appTag;
        let client_name = that.reference.dataset.appName;
        let client_url = that.reference.dataset.appUrl;
        let client_img = that.reference.dataset.appImg;
        
        if(client_name != "") {
            let res = {
                'name':   client_name,
                'url':    client_url,
                'img':    client_img,
                'app_tr': tr("app") 
            };
    
            that.setContent(tooltipClientTemplate(res));
        } else {
            let res = {
                'name': client_tag,
                'app_tr': tr("app") 
            };
    
            that.setContent(tooltipClientNoInfoTemplate(res));
        }
    }
});

function addNote(textareaId, nid)
{
    if(nid > 0) {
        note.value = nid
        let noteObj = document.querySelector("#nd"+nid)
    
        let nortd = document.querySelector("#post-buttons"+textareaId+" .post-has-note");
        nortd.style.display = "block"
    
        nortd.innerHTML = `${tr("note")} ${escapeHtml(noteObj.dataset.name)}`
    } else {
        note.value = "none"

        let nortd = document.querySelector("#post-buttons"+textareaId+" .post-has-note");
        nortd.style.display = "none"

        nortd.innerHTML = ""
    }

    u("body").removeClass("dimmed");
    u(".ovk-diag-cont").remove();
}

async function attachNote(id)
{
    let notes = await API.Wall.getMyNotes()
    let body  = ``

    if(notes.closed < 1) {
        body = `${tr("notes_closed")}`
    } else {
        if(notes.items.length < 1) {
            body = `${tr("no_notes")}`
        } else {
            body = `
                ${tr("select_or_create_new")}
                <div id="notesList">`

            if(note.value != "none") {
                body += `
                <div class="ntSelect" onclick="addNote(${id}, 0)">
                    <span>${tr("do_not_attach_note")}</span>
                </div>`
            }

            for(const note of notes.items) {
                body += `
                    <div data-name="${note.name}" class="ntSelect" id="nd${note.id}" onclick="addNote(${id}, ${note.id})">
                        <span>${escapeHtml(note.name)}</span>
                    </div>
                `
            }
         
            body += `</div>`
        }    
    }

    let frame = MessageBox(tr("select_note"), body, [tr("cancel")], [Function.noop]);

    document.querySelector(".ovk-diag-body").style.padding = "10px"
}

async function showArticle(note_id) {
    u("body").addClass("dimmed");
    let note = await API.Notes.getNote(note_id);
    u("#articleAuthorAva").attr("src", note.author.ava);
    u("#articleAuthorName").text(note.author.name);
    u("#articleAuthorName").attr("href", note.author.link);
    u("#articleTime").text(note.created);
    u("#articleLink").attr("href", note.link);
    u("#articleText").html(`<h1 class="articleView_nameHeading">${note.title}</h1>` + note.html);
    u("body").removeClass("dimmed");
    u("body").addClass("article");
}

// Оконный плеер

$(document).on("click", "#videoOpen", async (e) => {
    e.preventDefault()

    document.getElementById("ajloader").style.display = "block"

    if(document.querySelector(".ovk-fullscreen-dimmer") != null) {
        u(".ovk-fullscreen-dimmer").remove()
    }

    let target   = e.currentTarget
    let videoId  = target.dataset.id
    let videoObj = null;

    try {
        videoObj = await API.Video.getVideo(Number(videoId))
    } catch(e) {
        console.error(e)
        document.getElementById("ajloader").style.display = "none"
        MessageBox(tr("error"), tr("video_access_denied"), [tr("cancel")], [
        function() {
            Function.noop
        }]);
        return 0;
    }

    document.querySelector("html").style.overflowY = "hidden"

    let player = null;

    if(target.dataset.dontload == null) {
        document.querySelectorAll("video").forEach(vid => vid.pause())
        if(videoObj.type == 0) {
            if(videoObj.isProcessing) {
                player = `
                    <span class="gray">${tr("video_processing")}</span>
                `
            } else {
                player = `
                <div class="bsdn media" data-name="${videoObj.title}" data-author="${videoObj.name}">
                    <video class="media" src="${videoObj.url}"></video>
                </div>`
            }
        } else {
            player = videoObj.embed
        }
    } else {
        player = ``
    }


    let dialog = u(
        `
        <div class="ovk-fullscreen-dimmer">
            <div class="ovk-fullscreen-player">
                ${videoObj.prevVideo != null ?
                `<div class="right-arrow" id="videoOpen" data-id="${videoObj.prevVideo}">
                    <img src="/assets/packages/static/openvk/img/right_arr.png" draggable="false">
                </div>` : ""}
                ${videoObj.nextVideo != null ? `
                <div class="left-arrow" id="videoOpen" data-id="${videoObj.nextVideo}" style="margin-left: 820px;">
                    <img src="/assets/packages/static/openvk/img/left_arr.png" draggable="false">
                </div>` : ""}
                <div class="inner-player">
                    <div class="top-part">
                        <span class="top-part-name">${escapeHtml(videoObj.title)}</span>
                        <div class="top-part-buttons">
                            <span class="clickable" id="minimizePlayer" data-name="${videoObj.title}" data-id="${videoObj.id}">${tr("hide_player")}</span>
                            <span>|</span>
                            <span class="clickable" id="closeFplayer">${tr("close_player")}</span>
                        </div>
                        <div class="top-part-player-subdiv">
                            ${target.dataset.dontload == null ?`
                            <div class="fplayer">
                                ${player}
                            </div>` : ""}
                        </div>
                        <div class="top-part-bottom-buttons">
                            <span class="clickable" id="showComments" data-id="${videoObj.id}" data-owner="${videoObj.owner}" data-pid="${videoObj.pretty_id}">${tr("show_comments")}</span>
                            <span>|</span>
                            <span class="clickable" id="gotopage" data-id="/video${videoObj.pretty_id}">${tr("to_page")}</span>
                            ${ videoObj.type == 0 && videoObj.isProcessing == false ? `<span>|</span>
                            <a class="clickable" href="${videoObj.url}" download><span class="clickable">${tr("download_video")}</span></a>` : ""}
                        </div>
                    </div>
                </div>
                <div class="bottom-part">
                    <div class="left_block">
                        <div class="description" style="margin-bottom: 5px;">
                            <span>${videoObj.description != null ? escapeHtml(videoObj.description) : "(" + tr("no_description") + ")"}</span>
                        </div>
                        <div class="bottom-part-info" style="display: flex;">
                            <span class="gray">${tr("added")} ${videoObj.published}&nbsp;</span><span>|</span>
                            <div class="like_wrap" style="float:unset;">
                                <a href="/video${videoObj.pretty_id}/like?hash=${encodeURIComponent(u("meta[name=csrf]").attr("value"))}" class="post-like-button" data-liked="${videoObj.has_like ? 1 : 0}" data-likes="${videoObj.likes}">
                                    <div class="heart" id="${videoObj.has_like ? "liked" : ""}"></div>
                                    <span class="likeCnt" style="margin-top: -2px;">${videoObj.likes > 0 ? videoObj.likes : ""}</span>
                                </a>
                            </div>
                        </div>
                        <div id="vidComments"></div>
                    </div>
                    <div class="right_block">
                        <div class="views">
                            <!--prosmoters are not implemented((-->
                            <span class="gray">${tr("x_views", 0)}</span>
                        </div>
                        
                        <div class="v_author">
                            <span class="gray">${tr("video_author")}:</span><br>
                            <a href="/id${videoObj.owner}"><span style="color:unset;">${videoObj.author}</span></a>
                        </div>
                        <div class="actions" style="margin-top: 10px;margin-left: -3px;">
                        ${videoObj.canBeEdited ? `
                            <a href="/video${videoObj.pretty_id}/edit" class="profile_link" style="display:block;width:96%;font-size: 13px;">
                                ${tr("edit")}
                            </a>
                            <a href="/video${videoObj.pretty_id}/remove" class="profile_link" style="display:block;width:96%;font-size: 13px;">
                                ${tr("delete")}
                            </a>`
                         : ""}
                            <a id="shareVideo" class="profile_link" id="shareVideo" data-owner="${videoObj.owner}" data-vid="${videoObj.virtual_id}" style="display:block;width:96%;font-size: 13px;">
                                ${tr("share")}
                            </a>
                         </div>
                    </div>
                </div>
            </div>
        </div>`);

    u("body").addClass("dimmed").append(dialog);

    if(target.dataset.dontload != null) {
        let oldPlayer = document.querySelector(".miniplayer-video .fplayer")
        let newPlayer = document.querySelector(".top-part-player-subdiv")

        document.querySelector(".top-part-player-subdiv")

        newPlayer.append(oldPlayer)
    }

    if(videoObj.type == 0 && videoObj.isProcessing == false) {
        bsdnInitElement(document.querySelector(".fplayer .bsdn"))
    }

    document.getElementById("ajloader").style.display = "none"
    u(".miniplayer").remove()
})

$(document).on("click", "#closeFplayer", async (e) => {
    u(".ovk-fullscreen-dimmer").remove();
    document.querySelector("html").style.overflowY = "scroll"
    u("body").removeClass("dimmed")
})

$(document).on("click", "#minimizePlayer", async (e) => {
    let targ = e.currentTarget

    let player    = document.querySelector(".fplayer")

    let dialog = u(`
        <div class="miniplayer">
            <span class="miniplayer-name">${escapeHtml(trimNum(targ.dataset.name, 26))}</span>
            <div class="miniplayer-actions">
                <img src="/assets/packages/static/openvk/img/miniplayer_open.png" id="videoOpen" data-dontload="true" data-id="${targ.dataset.id}">
                <img src="/assets/packages/static/openvk/img/miniplayer_close.png" id="closeMiniplayer">
            </div>
            <div class="miniplayer-video">
            
            </div>
        </div>
    `);

    u("body").append(dialog);
    $('.miniplayer').draggable({cursor: "grabbing", containment: "body", cancel: ".miniplayer-video"});

    let newPlayer = document.querySelector(".miniplayer-video")
    newPlayer.append(player)

    document.querySelector(".miniplayer").style.top = window.scrollY;
    document.querySelector("#closeFplayer").click()
})

$(document).on("click", "#closeMiniplayer", async (e) => {
    u(".miniplayer").remove()
})

$(document).on("mouseup", "#gotopage", async (e) => {
    if(e.originalEvent.which === 1) {
        location.href = e.currentTarget.dataset.id
    } else if (e.originalEvent.which === 2) { 
        window.open(e.currentTarget.dataset.id, '_blank')
    }

})

$(document).keydown(function(e) {
    if(document.querySelector(".top-part-player-subdiv .bsdn") != null && document.activeElement.tagName == "BODY") {
        let video = document.querySelector(".top-part-player-subdiv video")

        switch(e.keyCode) {
            // Пробел вроде
            case 32:
                document.querySelector(".top-part-player-subdiv .bsdn_teaserButton").click()
                break
            // Стрелка вниз, уменьшение громкости
            case 40:
                oldVolume = video.volume

                if(oldVolume - 0.1 > 0) {
                    video.volume = oldVolume - 0.1
                } else {
                    video.volume = 0
                }

                break;
            // Стрелка вверх, повышение громкости
            case 38:
                oldVolume = video.volume

                if(oldVolume + 0.1 < 1) {
                    video.volume = oldVolume + 0.1
                } else {
                    video.volume = 1
                }

                break
            // стрелка влево, отступ на 2 секунды назад
            case 37:
                oldTime = video.currentTime
                video.currentTime = oldTime - 2
                break
            // стрелка вправо, отступ на 2 секунды вперёд
            case 39:
                oldTime = document.querySelector(".top-part-player-subdiv video").currentTime
                document.querySelector(".top-part-player-subdiv video").currentTime = oldTime + 2
                break
        }
    }
});

$(document).keyup(function(e) {
    if(document.querySelector(".top-part-player-subdiv .bsdn") != null && document.activeElement.tagName == "BODY") {
        let video = document.querySelector(".top-part-player-subdiv video")

        switch(e.keyCode) {
            // Escape, закрытие плеера
            case 27:
                document.querySelector("#closeFplayer").click()
                break
            // Блять, я перепутал лево и право, пиздец я долбаёб конечно
            // Ну короче стрелка влево
            case 65:
                if(document.querySelector(".right-arrow") != null) {
                    document.querySelector(".right-arrow").click()
                } else {
                    console.info("No left arrow bro")
                }
                break
            // Фуллскрин
            case 70:
                document.querySelector(".top-part-player-subdiv .bsdn_fullScreenButton").click()
                break
            // стрелка вправо
            case 68:
                if(document.querySelector(".left-arrow") != null) {
                    document.querySelector(".left-arrow").click()
                } else {
                    console.info("No right arrow bro")
                }
                break;
            // S: Показать инфо о видео (не комментарии)
            case 83:
                document.querySelector(".top-part-player-subdiv #showComments").click()
                break
            // Мут (M)
            case 77:
                document.querySelector(".top-part-player-subdiv .bsdn_soundIcon").click()
                break;
            // Escape, выход из плеера
            case 192:
                document.querySelector(".top-part-buttons #minimizePlayer").click()
                break
            // Бля не помню сори
            case 75:
                document.querySelector(".top-part-player-subdiv .bsdn_playButton").click()
                break
            // Home, переход в начало видосика
            case 36:
                video.currentTime = 0
                break
            // End, переход в конец видосика
            case 35:
                video.currentTime = video.duration
                break;
        }
    }
});

$(document).on("click", "#showComments", async (e) => {
    if(document.querySelector(".bottom-part").style.display == "none" || document.querySelector(".bottom-part").style.display == "") {
        if(document.getElementById("vidComments").innerHTML == "") {
            let xhr = new XMLHttpRequest
            xhr.open("GET", "/video"+e.currentTarget.dataset.pid)
            xhr.onloadstart = () => {
                document.getElementById("vidComments").innerHTML = `<img src="/assets/packages/static/openvk/img/loading_mini.gif">`
            }

            xhr.timeout = 10000;

            xhr.onload = () => {
                let parser = new DOMParser();
                let body   = parser.parseFromString(xhr.responseText, "text/html");
                let comms  = body.getElementById("comments")
                let commsHTML = comms.innerHTML.replace("expand_wall_textarea(11)", "expand_wall_textarea(999)")
                                                .replace("wall-post-input11", "wall-post-input999")
                                                .replace("post-buttons11", "post-buttons999")
                                                .replace("toggleMenu(11)", "toggleMenu(999)")
                                                .replace("toggleMenu(11)", "toggleMenu(999)")
                                                .replace(/ons11/g, "ons999")
                document.getElementById("vidComments").innerHTML = commsHTML
            }

            xhr.onerror = () => {
                document.getElementById("vidComments").innerHTML = `<span>${tr("comments_load_timeout")}</span>`
            }

            xhr.ontimeout = () => {
                document.getElementById("vidComments").innerHTML = `<span>${tr("comments_load_timeout")}</span>`
            };

            xhr.send()
        }

        document.querySelector(".bottom-part").style.display = "flex"
        e.currentTarget.innerHTML = tr("close_comments")
    } else {
        document.querySelector(".bottom-part").style.display = "none"
        e.currentTarget.innerHTML = tr("show_comments")
    }
})

$(document).on("click", "#shareVideo", async (e) => {
    let owner_id   = e.currentTarget.dataset.owner
    let virtual_id = e.currentTarget.dataset.vid
    let body = `
        <b>${tr('auditory')}:</b> <br/>
        <input type="radio" name="type" onchange="signs.setAttribute('hidden', 'hidden');document.getElementById('groupId').setAttribute('hidden', 'hidden')" value="0" checked>${tr("in_wall")}<br/>
        <input type="radio" name="type" onchange="signs.removeAttribute('hidden');document.getElementById('groupId').removeAttribute('hidden')" value="1" id="group">${tr("in_group")}<br/>
        <select style="width:50%;" id="groupId" name="groupId" hidden>
        </select><br/>
        <b>${tr('your_comment')}:</b> 
        <textarea id='uRepostMsgInput'></textarea>
        <div id="signs" hidden>
        <label><input onchange="signed.checked ? signed.checked = false : null" type="checkbox" id="asgroup" name="asGroup">${tr('post_as_group')}</label><br>
        <label><input onchange="asgroup.checked = true" type="checkbox" id="signed" name="signed">${tr('add_signature')}</label>
        </div>
    `
    MessageBox(tr("share_video"), body, [tr("share"), tr("cancel")], [
        (async function() {
            let type   = $('input[name=type]:checked').val()
            let club   = document.getElementById("groupId").value

            let asGroup = document.getElementById("asgroup").checked
            let signed  = document.getElementById("signed").checked

            let repost = null;

            try {
                repost = await API.Video.shareVideo(Number(owner_id), Number(virtual_id), Number(type), uRepostMsgInput.value, Number(club), signed, asGroup)
                NewNotification(tr('information_-1'), tr('shared_succ_video'), null, () => {window.location.href = "/wall" + repost.pretty_id});
            } catch(e) {
                console.log("tudu")
            }
        }), (function() {
                Function.noop
        })], false);

    try {
        clubs = await API.Groups.getWriteableClubs();
        for(const el of clubs) {
            document.getElementById("groupId").insertAdjacentHTML("beforeend", `<option value="${el.id}">${escapeHtml(el.name)}</option>`)
        }
    } catch(rejection) {
        console.error(rejection)
        document.getElementById("group").setAttribute("disabled", "disabled")
    }
})