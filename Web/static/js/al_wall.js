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

u(".post-like-button").on("click", function(e) {
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

$(document).on("click", "#editPost", (e) => {
    let post = e.currentTarget.closest("table")
    let content = post.querySelector(".text")
    let text = content.querySelector(".really_text")

    if(content.querySelector("textarea") == null) {
        content.insertAdjacentHTML("afterbegin", `
            <div class="editMenu">
                <div id="wall-post-input999"> 
                    <textarea id="new_content">${text.innerHTML.replace(/(<([^>]+)>)/gi, '')}</textarea>
                    <input type="button" class="button" value="${tr("save")}" id="endEditing">
                    <input type="button" class="button" value="${tr("cancel")}" id="cancelEditing">
                </div>
                ${e.currentTarget.dataset.nsfw != null ? `
                    <div class="postOptions">
                        <label><input type="checkbox" id="nswfw" ${e.currentTarget.dataset.nsfw == 1 ? `checked` : ``}>${tr("contains_nsfw")}</label>
                    </div>
                ` : ``}
                ${e.currentTarget.dataset.fromgroup != null ? `
                <div class="postOptions">
                    <label><input type="checkbox" id="fromgroup" ${e.currentTarget.dataset.fromgroup == 1 ? `checked` : ``}>${tr("post_as_group")}</label>
                </div>
            ` : ``}
            </div>
        `)

        u(content.querySelector("#cancelEditing")).on("click", () => {post.querySelector("#editPost").click()})
        u(content.querySelector("#endEditing")).on("click", () => {
            let nwcntnt = content.querySelector("#new_content").value
            let type = "post"

            if(post.classList.contains("comment")) {
                type = "comment"
            }

            let xhr = new XMLHttpRequest()
            xhr.open("POST", "/wall/edit")

            xhr.onloadstart = () => {
                content.querySelector(".editMenu").classList.add("loading")
            }

            xhr.onerror = () => {
                MessageBox(tr("error"), "unknown error occured", [tr("ok")], [() => {Function.noop}])
            }

            xhr.ontimeout = () => {
                MessageBox(tr("error"), "Try to refresh page", [tr("ok")], [() => {Function.noop}])
            }

            xhr.onload = () => {
                let result = JSON.parse(xhr.responseText)

                if(result.error == "no") {
                    post.querySelector("#editPost").click()
                    content.querySelector(".really_text").innerHTML = result.new_content
    
                    if(post.querySelector(".editedMark") == null) {
                        post.querySelector(".date").insertAdjacentHTML("beforeend", `
                            <span class="edited editedMark">(${tr("edited_short")})</span>
                        `)
                    }

                    if(e.currentTarget.dataset.nsfw != null) {
                        e.currentTarget.setAttribute("data-nsfw", result.nsfw)

                        if(result.nsfw == 0) {
                            post.classList.remove("post-nsfw")
                        } else {
                            post.classList.add("post-nsfw")
                        }
                    }

                    if(e.currentTarget.dataset.fromgroup != null) {
                        e.currentTarget.setAttribute("data-fromgroup", result.from_group)
                    }

                    post.querySelector(".post-avatar").setAttribute("src", result.author.avatar)
                    post.querySelector(".post-author-name").innerHTML = result.author.name
                } else {
                    MessageBox(tr("error"), result.error, [tr("ok")], [Function.noop])
                    post.querySelector("#editPost").click()
                }
            }

            xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
            xhr.send("postid="+e.currentTarget.dataset.id+
                    "&newContent="+nwcntnt+
                    "&hash="+encodeURIComponent(u("meta[name=csrf]").attr("value"))+
                    "&type="+type+
                    "&nsfw="+(content.querySelector("#nswfw") != null ? content.querySelector("#nswfw").checked : 0)+
                    "&fromgroup="+(content.querySelector("#fromgroup") != null ? content.querySelector("#fromgroup").checked : 0))
        })

        u(".editMenu").on("keydown", (e) => {
            if(e.ctrlKey && e.keyCode === 13)
                content.querySelector("#endEditing").click()
        });

        text.style.display = "none"
        setupWallPostInputHandlers(999)
    } else {
        u(content.querySelector(".editMenu")).remove()
        text.style.display = "block"
    }
})
