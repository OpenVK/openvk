function initGraffiti(event) {
    let canvas = null;
    const msgbox = new CMessageBox({
        title: tr("draw_graffiti"),
        body: "<div id='ovkDraw'></div>",
        close_on_buttons: false,
        warn_on_exit: true,
        buttons: [tr("save"), tr("cancel")],
        callbacks: [function() {
            canvas.getImage({includeWatermark: false}).toBlob(blob => {
                let fName = "Graffiti-" + Math.ceil(performance.now()).toString() + ".jpeg";
                let image = new File([blob], fName, {type: "image/jpeg", lastModified: new Date().getTime()});
                
                __uploadToTextarea(image, u(event.target).closest('#write'))
            }, "image/jpeg", 0.92);
            
            canvas.teardown();
            msgbox.close()
        }, async function() {
            const res = await msgbox.__showCloseConfirmationDialog()
            if(res === true) {
                canvas.teardown()
                msgbox.close()
            }
        }]
    })
    
    let watermarkImage = new Image();
    watermarkImage.src = "/assets/packages/static/openvk/img/logo_watermark.gif";
    
    msgbox.getNode().attr("style", "width: 750px;");
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

u(document).on('click', '.menu_toggler', (e) => {
    const post_buttons = $(e.target).closest('.post-buttons')
    const wall_attachment_menu = post_buttons.find('#wallAttachmentMenu')
    if(wall_attachment_menu.is('.hidden')) {
        wall_attachment_menu.css({ opacity: 0 });
        wall_attachment_menu.toggleClass('hidden').fadeTo(250, 1);
    } else {
        wall_attachment_menu.fadeTo(250, 0, function () {
            $(this).toggleClass('hidden');
        });
    }
})

$(document).on("click", ".post-like-button", function(e) {
    e.preventDefault();
    
    var thisBtn = u(this).first();
    var link    = u(this).attr("href");
    var heart   = u(".heart", thisBtn);
    var counter = u(".likeCnt", thisBtn);
    var likes   = counter.text() === "" ? 0 : counter.text();
    var isLiked = heart.attr("id") === 'liked';
    
    ky.post(link)
    heart.attr("id", isLiked ? '' : 'liked');
    counter.text(parseInt(likes) + (isLiked ? -1 : 1));
    if (counter.text() === "0") {
        counter.text("");
    }
    
    return false;
});

u(document).on("input", "textarea", function(e) {
    var boost             = 5;
    var textArea          = e.target;
    textArea.style.height = "5px";
    var newHeight = textArea.scrollHeight;
    textArea.style.height = newHeight + boost + "px";
    return;
    
    // revert to original size if it is larger (possibly changed by user)
    // textArea.style.height = (newHeight > originalHeight ? (newHeight + boost) : originalHeight) + "px";
});

async function OpenMiniature(e, photo, post, photo_id, type = "post") {
    /*
    костыли но смешные однако
    */
    e.preventDefault();

    // Значения для переключения фоток

    const albums_per_page = 20
    let json;
    let offset = type == 'album' ? (Number((new URL(location.href)).searchParams.get('p') ?? 1) - 1) * albums_per_page : 0
    let shown_offset = 0

    let imagesCount = 0;
    let currentImageid = '0_0';

    const photo_viewer = new CMessageBox({
        title: '',
        custom_template: u(`
        <div class="ovk-photo-view-dimmer">
            <div class="ovk-photo-view">
                <div class="photo_com_title">
                    <text id="photo_com_title_photos">
                        <img src="/assets/packages/static/openvk/img/loading_mini.gif">
                    </text>
                    <div>
                        <a id="ovk-photo-close">${tr("close")}</a>
                    </div>
                </div>
                <div class='photo_viewer_wrapper'>
                    <div class="ovk-photo-slide-left"></div>
                    <div class="ovk-photo-slide-right"></div>
                    <img src="${photo}" id="ovk-photo-img">
                </div>
                <div class="ovk-photo-details">
                    <img src="/assets/packages/static/openvk/img/loading_mini.gif">
                </div>
            </div>
        </div>`)
    })

    photo_viewer.getNode().find("#ovk-photo-close").on("click", function(e) {
        photo_viewer.close()
    });

    function __getIndex(photo_id = null) {
        return Object.keys(json.body).findIndex(item => item == (photo_id ?? currentImageid)) + 1
    }

    function __getByIndex(id) {
        const ids = Object.keys(json.body)
        const _id  = ids[id - 1]

        return json.body[_id]
    }

    function __reloadTitleBar() {
        photo_viewer.getNode().find("#photo_com_title_photos").last().innerHTML = imagesCount > 1 ? tr("photo_x_from_y", shown_offset, imagesCount) : tr("photo");
    }

    async function __loadDetails(photo_id) {
        if(json.body[photo_id].cached == null) {
            photo_viewer.getNode().find(".ovk-photo-details").last().innerHTML = '<img src="/assets/packages/static/openvk/img/loading_mini.gif">';
            const photo_url     = `/photo${photo_id}`
            const photo_page    = await fetch(photo_url)
            const photo_text    = await photo_page.text()
            const parser        = new DOMParser
            const body          = parser.parseFromString(photo_text, "text/html")
            const details       = body.querySelector('.ovk-photo-details')
            json.body[photo_id].cached = details ? details.innerHTML : ''
            if(photo_id == currentImageid) {
                photo_viewer.getNode().find(".ovk-photo-details").last().innerHTML = details ? details.innerHTML : ''
            }

            photo_viewer.getNode().find(".ovk-photo-details .bsdn").nodes.forEach(bsdnInitElement)
        } else {
            photo_viewer.getNode().find(".ovk-photo-details").last().innerHTML = json.body[photo_id].cached
        }
    }

    async function __slidePhoto(direction) {
        /* direction = 1 - right
           direction = 0 - left */
        if(json == undefined) {
            console.log("Да подожди ты. Куда торопишься?");
        } else {
            let current_index = __getIndex()
            if(current_index >= imagesCount && direction == 1) {
                shown_offset   = 1
                current_index  = 1
            } else if(current_index <= 1 && direction == 0) {
                shown_offset   += imagesCount - 1
                current_index  = imagesCount
            } else if(direction == 1) {
                shown_offset  += 1
                current_index += 1
            } else if(direction == 0) {
                shown_offset -= 1
                current_index -= 1
            }

            currentImageid = __getByIndex(current_index)
            if(!currentImageid) {
                if(type == 'album') {
                    if(direction == 1) {
                        offset += albums_per_page
                    } else {
                        offset -= albums_per_page
                    }
                    
                    await __loadContext(type, post, true, direction == 0)
                } else {
                    return
                }
            }
            currentImageid = currentImageid.id
            let photoURL = json.body[currentImageid].url;

            photo_viewer.getNode().find("#ovk-photo-img").last().src = ''
            photo_viewer.getNode().find("#ovk-photo-img").last().src = photoURL;
            __reloadTitleBar();
            __loadDetails(json.body[currentImageid].id);
        }
    }

    async function __loadContext(type, id, ref = false, inverse = false) {
        if(type == 'post' || type == 'comment') {
            const form_data = new FormData()
            form_data.append('parentType', type);
    
            const endpoint_url = `/iapi/getPhotosFromPost/${type == "post" ? id : "1_"+id}`
            const fetcher = await fetch(endpoint_url, {
                method: 'POST',
                body: form_data,
            })
            json = await fetcher.json()
            imagesCount = Object.entries(json.body).length
        } else {
            const params = {
                'offset': offset,
                'count': albums_per_page,
                'owner_id': id.split('_')[0],
                'album_id': id.split('_')[1],
                'photo_sizes': 1
            }

            const result = await window.OVKAPI.call('photos.get', params)
            const converted_items = {}

            result.items.forEach(item => {
                const id = item.owner_id + '_' + item.id
                converted_items[id] = {
                    'url': item.src_xbig,
                    'id': id,
                }
            })
            imagesCount = result.count

            if(!json)
                json = {'body': []}
            
            if(!inverse) {
                json.body = Object.assign(converted_items, json.body)
            } else {
                json.body = Object.assign(json.body, converted_items)
            }
        }

        currentImageid = photo_id
    }

    photo_viewer.getNode().find(".ovk-photo-slide-left").on("click", (e) => {
        __slidePhoto(0);
    })
    photo_viewer.getNode().find(".ovk-photo-slide-right").on("click", (e) => {
        __slidePhoto(1);
    })
    
    if(!type) {
        imagesCount = 1
        json = {
            'body': {}
        }

        json.body[photo_id] = {
            'id': photo_id,
            'url': photo
        }
        currentImageid = photo_id
        
        __reloadTitleBar()
        __loadDetails(photo_id)
    } else {
        await __loadContext(type, post)
        shown_offset = offset + __getIndex()

        __reloadTitleBar();
        __loadDetails(json.body[currentImageid].id);
    }

    return photo_viewer.getNode()
}

u("#write > form").on("keydown", function(event) {
    if(event.ctrlKey && event.keyCode === 13)
        this.submit();
});

function reportPhoto(photo_id) {
    uReportMsgTxt  = tr("going_to_report_photo");
    uReportMsgTxt += "<br/>"+tr("report_question_text");
    uReportMsgTxt += "<br/><br/><b>"+tr("report_reason")+"</b>: <input type='text' id='uReportMsgInput' placeholder='" + tr("reason") + "' />"

    MessageBox(tr("report_question"), uReportMsgTxt, [tr("confirm_m"), tr("cancel")], [
        (function() {
            res = document.querySelector("#uReportMsgInput").value;
            xhr = new XMLHttpRequest();
            xhr.open("GET", "/report/" + photo_id + "?reason=" + res + "&type=photo", true);
            xhr.onload = (function() {
            if(xhr.responseText.indexOf("reason") === -1)
                MessageBox(tr("error"), tr("error_sending_report"), ["OK"], [Function.noop]);
            else
                MessageBox(tr("action_successfully"), tr("will_be_watched"), ["OK"], [Function.noop]);
            });
            xhr.send(null);
            }),
        Function.noop
    ]);
}

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
                    <textarea id="new_content">${text.dataset.text}</textarea>
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
                    post.querySelector(".post-author-name").innerHTML = result.author.name.escapeHtml()
                    post.querySelector(".really_text").setAttribute("data-text", result.new_text)
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

async function __uploadToTextarea(file, textareaNode) {
    const MAX_FILESIZE = window.openvk.max_filesize_mb*1024*1024
    let filetype = 'photo'
    if(file.type.startsWith('video/')) {
        filetype = 'video'
    }

    if(!file.type.startsWith('image/') && !file.type.startsWith('video/')) {
        fastError(tr("only_images_accepted", escapeHtml(file.name)))
        throw new Error('Only images accepted')
    }

    if(file.size > MAX_FILESIZE) {
        fastError(tr("max_filesize", window.openvk.max_filesize_mb))
        throw new Error('Big file')
    }

    const horizontal_count = textareaNode.find('.post-horizontal > a').length
    if(horizontal_count > window.openvk.max_attachments) {
        fastError(tr("too_many_photos"))
        throw new Error('Too many attachments')
    }

    const form_data = new FormData
    form_data.append('photo_0', file)
    form_data.append('count', 1)
    form_data.append("hash", u("meta[name=csrf]").attr("value"))
    
    if(filetype == 'photo') {
        const temp_url = URL.createObjectURL(file)
        const rand = random_int(0, 1000)
        textareaNode.find('.post-horizontal').append(`<a id='temp_filler${rand}' class="upload-item"><img src='${temp_url}'></a>`)
        
        const res = await fetch(`/photos/upload`, {
            method: 'POST',
            body: form_data
        })
        const json_response = await res.json()
        if(!json_response.success) {
            u(`#temp_filler${rand}`).remove()
            fastError((tr("error_uploading_photo") + json_response.flash.message))
            return
        }

        json_response.photos.forEach(photo => {
            __appendToTextarea({
                'type': 'photo',
                'preview': photo.url,
                'id': photo.pretty_id,
                'fullsize_url': photo.link,
            }, textareaNode)
        })
        u(`#temp_filler${rand}`).remove()
        URL.revokeObjectURL(temp_url)
    } else {
        return
    }
}

async function __appendToTextarea(attachment_obj, textareaNode) {
    const form = textareaNode.find('.post-buttons')
    const indicator = textareaNode.find('.post-horizontal')

    indicator.append(`
        <a draggable="true" href='/${attachment_obj.type}${attachment_obj.id}' class="upload-item" data-type='${attachment_obj.type}' data-id="${attachment_obj.id}">
            <span class="upload-delete">×</span>
            ${attachment_obj.type == 'video' ? `<div class='play-button'><div class='play-button-ico'></div></div>` : ''}
            <img draggable="false" src="${attachment_obj.preview}" alt='...'>
        </a>      
    `)
}

// ajax не буде работать

u('#write .small-textarea').on('paste', (e) => {
    if(e.clipboardData.files.length === 1) {
        __uploadToTextarea(e.clipboardData.files[0], u(e.target).closest('#write'))
        return;
    }
})

u('#write').on('dragstart', '.post-horizontal .upload-item, .post-vertical .upload-item', (e) => {
    //e.preventDefault()
    //console.log(e)
    u(e.target).closest('.upload-item').addClass('currently_dragging')
    return
})

u('#write').on('dragover', '.post-horizontal .upload-item, .post-vertical .upload-item', (e) => {
    e.preventDefault()

    const target = u(e.target).closest('.upload-item')
    const current = u('.upload-item.currently_dragging')

    if(target.nodes[0].dataset.id != current.nodes[0].dataset.id) {
        target.addClass('dragged')
    }
    
    return
})

u('#write').on('dragleave dragend', '.post-horizontal .upload-item, .post-vertical .upload-item', (e) => {
    //console.log(e)
    u(e.target).closest('.upload-item').removeClass('dragged')
    return
})

u('#write').on("drop", function(e) {
    const current = u('.upload-item.currently_dragging')
    //console.log(e)
    if(e.dataTransfer.types.includes('Files')) {
        e.preventDefault()

        e.dataTransfer.dropEffect = 'move'
        __uploadToTextarea(e.dataTransfer.files[0], u(e.target).closest('#write'))
    } else if(e.dataTransfer.types.length < 1 || e.dataTransfer.types.includes('text/uri-list')) { 
        e.preventDefault()

        const target = u(e.target).closest('.upload-item')
        u('.dragged').removeClass('dragged')
        current.removeClass('currently_dragging')
        //console.log(target)
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

u('#write > form').on('submit', (e) => {
    const target = u(e.target)
    const horizontal_array = []
    const horizontal_input = target.find(`input[name='horizontal_attachments']`)
    const horizontal_attachments = target.find(`.post-horizontal > a`)
    horizontal_attachments.nodes.forEach(_node => {
        horizontal_array.push(`${_node.dataset.type}${_node.dataset.id}`)
    })
    horizontal_input.nodes[0].value = horizontal_array.join(',')

    const vertical_array = []
    const vertical_input = target.find(`input[name='vertical_attachments']`)
    const vertical_attachments = target.find(`.post-vertical > .vertical-attachment`)
    vertical_attachments.nodes.forEach(_node => {
        vertical_array.push(`${_node.dataset.type}${_node.dataset.id}`)
    })
    vertical_input.nodes[0].value = vertical_array.join(',')
})

// !!! PHOTO PICKER !!!
u(document).on("click", "#__photoAttachment", async (e) => {
    const photos_per_page = 23
    const form = u(e.target).closest('form') 
    const club = Number(e.currentTarget.dataset.club ?? 0)
    const msg = new CMessageBox({
        title: tr('select_photo'),
        body: `
        <div class='attachment_selector'>
            <div class="topGrayBlock display_flex_row">
                <label>
                    <input type="file" multiple accept="image/*" id="__pickerQuickUpload" style="display:none">
                    <input type="button" class="button" value="${tr("upload_button")}" onclick="__pickerQuickUpload.click()">
                </label>
                
                <select id="albumSelect">
                    <option value="0">${tr("all_photos")}</option>
                </select>
            </div>
            <div id='attachment_insert'>
                <div id='attachment_insert_count'>
                    <h4>${tr("is_x_photos", 0)}</h4>
                </div>
                <div class="photosList album-flex"></div>
            </div>
        </div>
        `,
        buttons: [tr('close')],
        callbacks: [Function.noop]
    })

    msg.getNode().attr('style', 'width: 630px;')
    msg.getNode().find('.ovk-diag-body').attr('style', 'height:335px;padding:0px;')

    async function __recievePhotos(page, album = 0) {
        u('#gif_loader').remove()
        u('#attachment_insert').append(`<div id='gif_loader'></div>`)
        const insert_place = u('#attachment_insert .photosList')
        let photos = null

        try {
            if(album == 0) {
                photos = await window.OVKAPI.call('photos.getAll', {'owner_id': window.openvk.current_id, 'photo_sizes': 1, 'count': photos_per_page, 'offset': page * photos_per_page})
            } else {
                photos = await window.OVKAPI.call('photos.get', {'owner_id': window.openvk.current_id, 'album_id': album, 'photo_sizes': 1, 'count': photos_per_page, 'offset': page * photos_per_page})
            }
        } catch(e) {
            u("#attachment_insert_count h4").html(tr("is_x_photos", -1))
            u("#gif_loader").remove()
            insert_place.html("Invalid album")
            return
        }

        u("#attachment_insert_count h4").html(tr("is_x_photos", photos.count))
        u("#gif_loader").remove()
        const pages_count = Math.ceil(Number(photos.count) / photos_per_page)
        photos.items.forEach(photo => {
            const is_attached = (form.find(`.upload-item[data-type='photo'][data-id='${photo.owner_id}_${photo.id}']`)).length > 0
            insert_place.append(`
                <a class="album-photo${is_attached ? ' selected' : ''}" data-attachmentdata="${photo.owner_id}_${photo.id}" data-preview="${photo.photo_130}" href="/photo${photo.owner_id}_${photo.id}">
                    <img class="album-photo--image" src="${photo.photo_130}" alt="...">
                </a>
            `)
        })

        if(page < pages_count - 1) {
            insert_place.append(`
            <div id="show_more" data-pagesCount="${pages_count}" data-page="${page + 1}">
                <span>${tr('show_more')}</span>
            </div>`)
        }
    }

    // change album
    u('.ovk-diag-body .attachment_selector').on("change", ".topGrayBlock #albumSelect", (ev) => {
        u("#attachment_insert .photosList").html('')

        __recievePhotos(0, ev.target.value)
    })

    // next page
    u(".ovk-diag-body .attachment_selector").on("click", "#show_more", async (ev) => {
        const target = u(ev.target).closest('#show_more')
        target.addClass('lagged')
        await __recievePhotos(Number(target.nodes[0].dataset.page), u(".topGrayBlock #albumSelect").nodes[0].value)
        target.remove()
    })

    // add photo
    u(".ovk-diag-body .attachment_selector").on("click", ".album-photo", async (ev) => {
        ev.preventDefault()
        
        const target = u(ev.target).closest('.album-photo')
        const dataset = target.nodes[0].dataset
        const is_attached = (form.find(`.upload-item[data-type='photo'][data-id='${dataset.attachmentdata}']`)).length > 0
        if(is_attached) {
            (form.find(`.upload-item[data-type='photo'][data-id='${dataset.attachmentdata}']`)).remove()
            target.removeClass('selected')
        } else {
            if(form.find(`.upload-item`).length + 1 > window.openvk.max_attachments) {
                makeError(tr('too_many_attachments'), 'Red', 10000, 1)
                return
            }

            target.addClass('selected')
            __appendToTextarea({
                'type': 'photo',
                'preview': dataset.preview,
                'id': dataset.attachmentdata,
                'fullsize_url': dataset.preview,
            }, form)
        }
    })

    // "upload" button
    u(".ovk-diag-body #__pickerQuickUpload").on('change', (ev) => {
        for(file of ev.target.files) {
            try {
                __uploadToTextarea(file, form)
            } catch(e) {
                makeError(e.message)
                return
            }
        }

        msg.close()
    })

    __recievePhotos(0)
    if(!window.openvk.photoalbums) {
        window.openvk.photoalbums = await window.OVKAPI.call('photos.getAlbums', {'owner_id': club != 0 ? Math.abs(club) * -1 : window.openvk.current_id})
    }
    window.openvk.photoalbums.items.forEach(item => {
        u('.ovk-diag-body #albumSelect').append(`<option value="${item.vid}">${ovk_proc_strtr(escapeHtml(item.title), 20)}</option>`)
    })
})

u(document).on('click', '#__videoAttachment', async (e) => {
    const per_page = 10
    const form = u(e.target).closest('form') 
    const msg = new CMessageBox({
        title: tr('selecting_video'),
        body: `
        <div class='attachment_selector'>
            <div class="topGrayBlock display_flex_row">
                <a id='__fast_video_upload' href="/videos/upload"><input class='button' type='button' value='${tr("upload_button")}'></a>
                
                <input type="search" id="video_query" maxlength="20" placeholder="${tr("header_search")}">
            </div>
            <div id='attachment_insert'>
                <div class="videosInsert"></div>
            </div>
        </div>
        `,
        buttons: [tr('close')],
        callbacks: [Function.noop]
    })

    msg.getNode().attr('style', 'width: 630px;')
    msg.getNode().find('.ovk-diag-body').attr('style', 'height:335px;padding:0px;')
    
    async function __recieveVideos(page, query = '') {
        u('#gif_loader').remove()
        u('#attachment_insert').append(`<div id='gif_loader'></div>`)
        const insert_place = u('#attachment_insert .videosInsert')
        let videos = null

        try {
            if(query == '') {
                videos = await window.OVKAPI.call('video.get', {'owner_id': window.openvk.current_id, 'extended': 1, 'count': per_page, 'offset': page * per_page})
            } else {
                videos = await window.OVKAPI.call('video.search', {'q': escapeHtml(query), 'extended': 1, 'count': per_page, 'offset': page * per_page})
            }
        } catch(e) {
            u("#gif_loader").remove()
            insert_place.html("Err")
            return
        }

        u("#gif_loader").remove()
        const pages_count = Math.ceil(Number(videos.count) / per_page)
        videos.items.forEach(video => {
            const pretty_id = `${video.owner_id}_${video.id}`
            const is_attached = (form.find(`.upload-item[data-type='video'][data-id='${video.owner_id}_${video.id}']`)).length > 0
            let author_name = ''

            const profiles = videos.profiles
            const groups = videos.groups

            if(video['owner_id'] > 0) {
                const profile = profiles.find(prof => prof.id == video['owner_id'])
                if(profile) {  
                    author_name = profile['first_name'] + ' ' + profile['last_name']
                }
            } else {
                const group = groups.find(grou => grou.id == Math.abs(video['owner_id']))
                if(group) {
                    author_name = group['name']
                }
            }

            insert_place.append(`
            <div class="content video_list" style="padding: unset;" data-preview='${video.image[0].url}' data-attachmentdata="${pretty_id}">
                <table>
                    <tbody>
                        <tr>
                            <td valign="top">
                                <a href="/video${pretty_id}">
                                    <div class="video-preview">
                                        <img src="${video.image[0].url}" alt="${escapeHtml(video.title)}">
                                    </div>
                                </a>
                            </td>
                            <td valign="top" style="width: 100%">
                                <a href="/video${pretty_id}">
                                    <b class='video-name'>
                                        ${ovk_proc_strtr(escapeHtml(video.title), 50)}
                                    </b>
                                </a>
                                <br>
                                <p>
                                    <span class='video-desc'>${ovk_proc_strtr(escapeHtml(video.description ?? ""), 140)}</span>
                                </p>
                                <span><a href="/id${video.owner_id}" target="_blank">${ovk_proc_strtr(escapeHtml(author_name ?? ""), 100)}</a></span>
                            </td>
                            <td valign="top" class="action_links">
                                <a class="profile_link" id="__attach_vid">${!is_attached ? tr("attach") : tr("detach")}</a>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            `)
        })

        if(page < pages_count - 1) {
            insert_place.append(`
            <div id="show_more" data-pagesCount="${pages_count}" data-page="${page + 1}">
                <span>${tr('show_more')}</span>
            </div>`)
        }

        if(query != '') {
            highlightText(query, '.videosInsert', ['.video-name', '.video-desc'])
        }
    }

    u(".ovk-diag-body #video_query").on('change', (ev) => {
        if(ev.target.value == u(".ovk-diag-body #video_query").nodes[0].value) {
            u('#attachment_insert .videosInsert').html('')
            __recieveVideos(0, u(".ovk-diag-body #video_query").nodes[0].value)
        }
    })

    // next page
    u(".ovk-diag-body .attachment_selector").on("click", "#show_more", async (ev) => {
        const target = u(ev.target).closest('#show_more')
        target.addClass('lagged')
        await __recieveVideos(Number(target.nodes[0].dataset.page), u(".topGrayBlock #video_query").nodes[0].value)
        target.remove()
    })

    // add video
    u(".ovk-diag-body .attachment_selector").on("click", "#__attach_vid", async (ev) => {
        ev.preventDefault()
        
        const target = u(ev.target).closest('.content')
        const button = target.find('#__attach_vid')
        const dataset = target.nodes[0].dataset
        const is_attached = (form.find(`.upload-item[data-type='video'][data-id='${dataset.attachmentdata}']`)).length > 0
        if(is_attached) {
            (form.find(`.upload-item[data-type='video'][data-id='${dataset.attachmentdata}']`)).remove()
            button.html(tr('attach'))
        } else {
            if(form.find(`.upload-item`).length + 1 > window.openvk.max_attachments) {
                makeError(tr('too_many_attachments'), 'Red', 10000, 1)
                return
            }

            button.html(tr('detach'))
            __appendToTextarea({
                'type': 'video',
                'preview': dataset.preview,
                'id': dataset.attachmentdata,
                'fullsize_url': dataset.preview,
            }, form)
        }
    })

    u(".ovk-diag-body .attachment_selector").on('click', '#__fast_video_upload', (ev) => {
        ev.preventDefault()
        showFastVideoUpload(form)
    })

    __recieveVideos(0)
})

// __audioAttachment -> al_music.js, 1318

u(document).on('click', '#__notesAttachment', async (e) => {
    const per_page = 10
    const form = u(e.target).closest('form') 
    const msg = new CMessageBox({
        title: tr('select_note'),
        body: `
        <div class='attachment_selector'>
            <div id='attachment_insert' style='height: 325px;'>
                <div class="notesInsert"></div>
            </div>
        </div>
        `,
        buttons: [tr("create_note"), tr('close')],
        callbacks: [() => {
            window.location.assign('/notes/create')
        }, Function.noop]
    })

    msg.getNode().attr('style', 'width: 340px;')
    msg.getNode().find('.ovk-diag-body').attr('style', 'height:335px;padding:0px;')
    
    async function __recieveNotes(page) {
        u('#gif_loader').remove()
        u('#attachment_insert').append(`<div id='gif_loader'></div>`)
        const insert_place = u('#attachment_insert .notesInsert')
        let notes = null

        try {
            notes = await window.OVKAPI.call('notes.get', {'user_id': window.openvk.current_id, 'count': per_page, 'offset': per_page * page})
        } catch(e) {
            u("#gif_loader").remove()
            insert_place.html("Err")
            return
        }

        u("#gif_loader").remove()
        const pages_count = Math.ceil(Number(notes.count) / per_page)
        notes.notes.forEach(note => {
            is_attached = (form.find(`.upload-item[data-type='note'][data-id='${note.owner_id}_${note.id}']`)).length > 0
            insert_place.append(`
                <div class='display_flex_row _content' data-attachmentdata="${note.owner_id}_${note.id}" data-name='${escapeHtml(note.title)}'>
                    <div class="notes_titles" style='width: 73%;'>
                        <div class="written">
                            <a href="${note.view_url}">${escapeHtml(note.title)}</a>

                            <small>
                                <span>${ovk_proc_strtr(escapeHtml(strip_tags(note.text)), 100)}</span>
                            </small>
                        </div>
                    </div>
                    <div class="attachAudio" id='__attach_note'>
                        <span>${is_attached ? tr("detach") : tr("attach")}</span>
                    </div>
                </div>
            `)
        })

        if(page < pages_count - 1) {
            insert_place.append(`
            <div id="show_more" data-pagesCount="${pages_count}" data-page="${page + 1}">
                <span>${tr('show_more')}</span>
            </div>`)
        }
    }

    // next page
    u(".ovk-diag-body .attachment_selector").on("click", "#show_more", async (ev) => {
        const target = u(ev.target).closest('#show_more')
        target.addClass('lagged')
        await __recieveNotes(Number(target.nodes[0].dataset.page))
        target.remove()
    })

    // add note
    u(".ovk-diag-body .attachment_selector").on("click", "#__attach_note", async (ev) => {
        if(u(form).find(`.upload-item`).length > window.openvk.max_attachments) {
            makeError(tr('too_many_attachments'), 'Red', 10000, 1)
            return    
        }

        const target = u(ev.target).closest('._content')
        const button = target.find('#__attach_note')
        const dataset = target.nodes[0].dataset
        const is_attached = (form.find(`.upload-item[data-type='note'][data-id='${dataset.attachmentdata}']`)).length > 0
        if(is_attached) {
            (form.find(`.upload-item[data-type='note'][data-id='${dataset.attachmentdata}']`)).remove()
            button.html(tr('attach'))
        } else {
            if(form.find(`.upload-item`).length + 1 > window.openvk.max_attachments) {
                makeError(tr('too_many_attachments'), 'Red', 10000, 1)
                return
            }

            button.html(tr('detach'))
            form.find('.post-vertical').append(`
                <div class="vertical-attachment upload-item" draggable="true" data-type='note' data-id="${dataset.attachmentdata}">
                    <div class='vertical-attachment-content' draggable="false">
                        <div class="attachment_note">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 8 10"><polygon points="0 0 0 10 8 10 8 4 4 4 4 0 0 0"/><polygon points="5 0 5 3 8 3 5 0"/></svg>
                            
                            <div class='attachment_note_content'>
                                <span class="attachment_note_text">${tr('note')}</span>
                                <span class="attachment_note_name">${ovk_proc_strtr(dataset.name, 66)}</span>
                            </div>
                        </div>
                    </div>
                    <div class='vertical-attachment-remove'>
                        <div id='small_remove_button'></div>
                    </div>
                </div>
            `)
        }
    })

    __recieveNotes(0)
})

function showFastVideoUpload(node) {
    let current_tab = 'file'
    const msg = new CMessageBox({
        title: tr('upload_video'),
        close_on_buttons: false,
        unique_name: 'video_uploader',
        body: `
        <div id='_fast_video_upload'>
            <div id='_tabs'>
                <div class="mb_tabs">
                    <div class="mb_tab" data-name='file'>
                        <a>
                            ${tr('video_file_upload')}
                        </a>
                    </div>
                    <div class="mb_tab" data-name='youtube'>
                        <a>
                            ${tr('video_youtube_upload')}
                        </a>
                    </div>
                </div>
            </div>
            <div id='__content'></div>
        </div>
        `,
        buttons: [tr('close'), tr('upload_button')],
        callbacks: [() => {msg.close()}, async () => {
            const video_name = u(`#_fast_video_upload input[name='name']`).nodes[0].value
            const video_desc = u(`#_fast_video_upload textarea[name='desc']`).nodes[0].value
            let   append_result = null

            if(video_name.length < 1) {
                u(`#_fast_video_upload input[name='name']`).nodes[0].focus()
                return
            }

            const form_data  = new FormData
            switch(current_tab) {
                default:
                case 'file':
                    const video_file = u(`#_fast_video_upload input[name='blob']`).nodes[0]
                    if(video_file.files.length < 1) {
                        return
                    }

                    const video_blob = video_file.files[0]
                    form_data.append('ajax', '1')
                    form_data.append('name', video_name)
                    form_data.append('desc', video_desc)
                    form_data.append('blob', video_blob)
                    form_data.append('unlisted', 1)
                    form_data.append("hash", u("meta[name=csrf]").attr("value"))

                    window.messagebox_stack[1].getNode().find('.ovk-diag-action button').nodes[1].classList.add('lagged')
                    const fetcher = await fetch(`/videos/upload`, {
                        method: 'POST',
                        body: form_data
                    })
                    const fetcher_results = await fetcher.json()
                    append_result = fetcher_results

                    break
                case 'youtube':
                    const video_url = u(`#_fast_video_upload input[name='link']`).nodes[0]
                    const video_link = video_url.value
                    if(video_link.length < 1) {
                        u(`#_fast_video_upload input[name='link']`).nodes[0].focus()
                        return
                    }

                    form_data.append('ajax', '1')
                    form_data.append('name', video_name)
                    form_data.append('desc', video_desc)
                    form_data.append('link', video_link)
                    form_data.append('unlisted', 1)
                    form_data.append("hash", u("meta[name=csrf]").attr("value"))

                    window.messagebox_stack[1].getNode().find('.ovk-diag-action button').nodes[1].classList.add('lagged')
                    const fetcher_yt = await fetch(`/videos/upload`, {
                        method: 'POST',
                        body: form_data
                    })
                    const fetcher_yt_results = await fetcher_yt.json()
                    append_result = fetcher_yt_results

                    break
            }

            if(append_result.payload) {
                append_result = append_result.payload
                const preview = append_result.image[0]
                __appendToTextarea({
                    'type': 'video',
                    'preview': preview.url,
                    'id': append_result.owner_id + '_' + append_result.id,
                    'fullsize_preview': preview.url,
                }, node)

                window.messagebox_stack.forEach(msg_ => {
                    msg_.close()
                })
            } else {
                fastError(append_result.flash.message)
                msg.close()
            }
        }]
    })

    msg.getNode().find('.ovk-diag-body').attr('style', 'padding:0px;height: 161px;')
    async function __switchTab(tab_name) {
        current_tab = tab_name
        u(`#_fast_video_upload .mb_tab`).attr('id', 'ki')
        u(`#_fast_video_upload .mb_tab[data-name='${current_tab}']`).attr('id', 'active')
        
        switch(current_tab) {
            case 'file':
                msg.getNode().find('#__content').html(`
                    <table cellspacing="7" cellpadding="0" width="80%" border="0" align="center">
                        <tbody>
                            <tr>
                                <td width="120" valign="top"><span class="nobold">${tr('info_name')}:</span></td>
                                <td><input type="text" name="name" /></td>
                            </tr>
                            <tr>
                                <td width="120" valign="top"><span class="nobold">${tr('description')}:</span></td>
                                <td><textarea name="desc"></textarea></td>
                            </tr>
                            <tr>
                                <td width="120" valign="top"><span class="nobold">${tr('video')}:</span></td>
                                <td>
                                    <label class="button" style="">
                                        ${tr('browse')}
                                        <input type="file" id="blob" name="blob" style="display: none;" accept="video/*" />
                                    </label>
                                    <span id="filename" style="margin-left: 7px;"></span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                `)
                break
            case 'youtube':
                msg.getNode().find('#__content').html(`
                    <table cellspacing="7" cellpadding="0" width="80%" border="0" align="center">
                        <tbody>
                            <tr>
                                <td width="120" valign="top"><span class="nobold">${tr('info_name')}:</span></td>
                                <td><input type="text" name="name" /></td>
                            </tr>
                            <tr>
                                <td width="120" valign="top"><span class="nobold">${tr('description')}:</span></td>
                                <td><textarea name="desc"></textarea></td>
                            </tr>
                            <tr>
                                <td width="120" valign="top"><span class="nobold">${tr('video_link_to_yt')}:</span></td>
                                <td>
                                    <input type="text" name="link" placeholder="https://www.youtube.com/watch?v=9FWSRQEqhKE" />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                `)
                break
        }
    }

    u('#_fast_video_upload').on('click', '.mb_tab', (e) => {
        __switchTab(u(e.target).closest('.mb_tab').nodes[0].dataset.name)
    })

    u('#_fast_video_upload').on('change', '#blob', (e) => {
        u('#_fast_video_upload #filename').html(escapeHtml(e.target.files[0].name))
        u(`#_fast_video_upload input[name='name']`).nodes[0].value = escapeHtml(e.target.files[0].name)
    })

    __switchTab('file')
}

u(document).on('click', `.post-horizontal .upload-item .upload-delete`, (e) => {
    e.preventDefault()
    u(e.target).closest('.upload-item').remove()
})

u(document).on('click', `.post-vertical .vertical-attachment #small_remove_button`, (e) => {
    e.preventDefault()
    u(e.target).closest('.vertical-attachment').remove()
})

u(document).on('click', '.post-buttons .upload-item', (e) => {
    e.preventDefault()
})

u(document).on('click', '.post.post-nsfw .post-content', (e) => {
    e.preventDefault()
    e.stopPropagation()
    
    u(e.target).closest('.post-nsfw').removeClass('post-nsfw')
})

async function repost(id, repost_type = 'post') {
    const repostsCount = u(`#repostsCount${id}`)
    const previousVal  = repostsCount.length > 0 ? Number(repostsCount.html()) : 0;

    MessageBox(tr('share'), `
        <div class='display_flex_column' style='gap: 1px;'>
            <b>${tr('auditory')}</b>
            
            <div class='display_flex_column'>
                <label>
                    <input type="radio" name="repost_type" value="wall" checked>
                    ${tr("in_wall")}
                </label>
                
                <label>
                    <input type="radio" name="repost_type" value="group">
                    ${tr("in_group")}
                </label>

                <select name="selected_repost_club" style='display:none;'></select>
            </div>

            <b>${tr('your_comment')}</b>

            <input type='hidden' id='repost_attachments'>
            <textarea id='repostMsgInput' placeholder='...'></textarea>

            <div id="repost_signs" class='display_flex_column' style='display:none;'>
                <label><input type='checkbox' name="asGroup">${tr('post_as_group')}</label>
                <label><input type='checkbox' name="signed">${tr('add_signature')}</label>
            </div>
        </div>
    `, [tr('send'), tr('cancel')], [
        async () => {
            const message  = u('#repostMsgInput').nodes[0].value
            const type     = u(`input[name='repost_type']:checked`).nodes[0].value
            let club_id = 0
            try {
                club_id = parseInt(u(`select[name='selected_repost_club']`).nodes[0].selectedOptions[0].value)
            } catch(e) {}

            const as_group = u(`input[name='asGroup']`).nodes[0].checked
            const signed   = u(`input[name='signed']`).nodes[0].checked
            const attachments = u(`#repost_attachments`).nodes[0].value

            const params = {}
            switch(repost_type) {
                case 'post':
                    params.object = `wall${id}`
                    break
                case 'photo':
                    params.object = `photo${id}`
                    break
                case 'video':
                    params.object = `video${id}`
                    break
            }

            params.message = message
            if(type == 'group' && club_id != 0) {
                params.group_id = club_id
            }

            if(as_group) {
                params.as_group = Number(as_group)
            }
            
            if(signed) {
                params.signed = Number(signed)
            }

            if(attachments != '') {
                params.attachments = attachments
            }

            try {
                res = await window.OVKAPI.call('wall.repost', params)

                if(u('#reposts' + id).length > 0) {
                    if(repostsCount.length > 0) {
                        repostsCount.html(previousVal + 1)
                    } else {
                        u('#reposts' + id).nodes[0].insertAdjacentHTML('beforeend', `(<b id='repostsCount${id}'>1</b>)`)
                    }
                }

                NewNotification(tr('information_-1'), tr('shared_succ'), null, () => {window.location.assign(`/wall${res.pretty_id}`)});
            } catch(e) {
                console.error(e)
                fastError(e.message)
            }
        },
        Function.noop
    ]);
    
    u('.ovk-diag-body').attr('style', 'padding: 14px;')
    u('.ovk-diag-body').on('change', `input[name='repost_type']`, (e) => {
        const value = e.target.value

        switch(value) {
            case 'wall':
                u('#repost_signs').attr('style', 'display:none')
                u(`select[name='selected_repost_club']`).attr('style', 'display:none')
                break
            case 'group':
                u('#repost_signs').attr('style', 'display:flex')
                u(`select[name='selected_repost_club']`).attr('style', 'display:block')
                break
        }
    })
    
    if(!window.openvk.writeableClubs) {
        window.openvk.writeableClubs = await window.OVKAPI.call('groups.get', {'filter': 'admin', 'count': 100})
    }

    window.openvk.writeableClubs.items.forEach(club => {
        u(`select[name='selected_repost_club']`).append(`<option value='${club.id}'>${ovk_proc_strtr(escapeHtml(club.name), 100)}</option>`)
    })

    if(window.openvk.writeableClubs.items.length < 1) {
        u(`input[name='repost_type'][value='group']`).attr('disabled', 'disabled')
    }
}

$(document).on("click", "#add_image", (e) => {
    let isGroup = e.currentTarget.closest(".avatar_block").dataset.club != null
    let group = isGroup ? e.currentTarget.closest(".avatar_block").dataset.club : 0

    let body = `
    <div id="avatarUpload">
        <p>${isGroup == true ? tr('groups_avatar') : tr('friends_avatar')}</p>
        <p>${tr('formats_avatar')}</p><br>

        <label class="button" style="margin-left:45%;user-select:none" id="uploadbtn">
            ${tr("browse")}
            <input accept="image/*" type="file" id="_avaInput" name="blob" hidden style="display: none;">
        </label>

        <br><br>

        <p>${tr('troubles_avatar')}</p>
        <p>${tr('webcam_avatar')}</p>
    </div>
    `

    let msg = MessageBox(tr('uploading_new_image'), body, [
        tr('cancel')
    ], [
        (function() {
            u("#tmpPhDelF").remove();
        }),
    ]);

    msg.attr("style", "width: 600px;");
    document.querySelector(".ovk-diag-body").style.padding = "13px"

    $("#avatarUpload input").on("change", (ev) => {
        let image = URL.createObjectURL(ev.currentTarget.files[0])
        $(".ovk-diag-body")[0].innerHTML = `
            <span>${!isGroup ? tr("selected_area_user") : tr("selected_area_club")}</span>

            <p style="margin-bottom: 10px;">${tr("selected_area_rotate")}</p>

            <div class="cropper-image-cont" style="max-height: 274px;">
                <img src="${image}" id="temp_uploadPic">

                <div class="rotateButtons">
                    <div class="_rotateLeft hoverable"></div>
                    <div class="_rotateRight hoverable"></div>
                </div>
            </div>

            <label style="margin-top: 14px;display: block;">
                <input id="publish_on_wall" type="checkbox" checked>${tr("publish_on_wall")}
            </label>
        `

        document.querySelector(".ovk-diag-action").insertAdjacentHTML("beforeend", `
            <button class="button" style="margin-left: 4px;" id="_uploadImg">${tr("upload_button")}</button>
        `)
        
        const image_div = document.getElementById('temp_uploadPic');
        const cropper = new Cropper(image_div, {
            aspectRatio: NaN,
            zoomable: true,
            minCropBoxWidth: 150,
            minCropBoxHeight: 150,
            dragMode: 'move',
            background: false,
            center: false,
            guides: false,
            modal: true,
            viewMode: 2,
            cropstart(event) {
                document.querySelector(".cropper-container").classList.add("moving")
            },
            cropend(event) {
                document.querySelector(".cropper-container").classList.remove("moving")
            },
        });

        msg.attr("style", "width: 487px;");

        document.querySelector("#_uploadImg").onclick = (evv) => {
            cropper.getCroppedCanvas({
                fillColor: '#fff',
                imageSmoothingEnabled: false,
                imageSmoothingQuality: 'high',
            }).toBlob((blob) => {
                document.querySelector("#_uploadImg").classList.add("lagged")
                let formdata = new FormData()
                formdata.append("blob", blob)
                formdata.append("ajax", 1)
                formdata.append("on_wall", Number(document.querySelector("#publish_on_wall").checked))
                formdata.append("hash", u("meta[name=csrf]").attr("value"))
        
                $.ajax({
                    type: "POST",
                    url: isGroup ? "/club" + group + "/al_avatar" : "/al_avatars",
                    data: formdata,
                    processData: false,
                    contentType: false,
                    error: (response) => {
                        fastError(response.flash.message)
                    },
                    success: (response) => {
                        document.querySelector("#_uploadImg").classList.remove("lagged")
                        u("body").removeClass("dimmed");
                        document.querySelector("html").style.overflowY = "scroll"
                        u(".ovk-diag-cont").remove();

                        if(!response.success) {
                            fastError(response.flash.message)
                            return
                        }
                        
                        document.querySelector("#bigAvatar").src = response.url
                        document.querySelector("#bigAvatar").parentNode.href = "/photo" + response.new_photo
                    
                        document.querySelector(".add_image_text").style.display = "none"
                        document.querySelector(".avatar_controls").style.display = "block"
                    }
                })
            })
        }

        $(".ovk-diag-body ._rotateLeft").on("click", (e) => {
            cropper.rotate(90)
        })

        $(".ovk-diag-body ._rotateRight").on("click", (e) => {
            cropper.rotate(-90)
        })
    })

    $(".ovk-diag-body #_takeSelfie").on("click", (e) => {
        $("#avatarUpload")[0].style.display = "none"

        $(".ovk-diag-body")[0].insertAdjacentHTML("beforeend", `
            <div id="_takeSelfieFrame" style="text-align: center;">
                <video style="max-width: 100%;max-height: 479px;"></video>
                <canvas id="_tempCanvas" style="position: absolute;">
            </div>
        `)

        let video = document.querySelector("#_takeSelfieFrame video")

        if(!navigator.mediaDevices) {
            // ех вот бы месседжбоксы были бы классами
            u("body").removeClass("dimmed");
            document.querySelector("html").style.overflowY = "scroll"
            u(".ovk-diag-cont").remove();

            fastError(tr("your_browser_doesnt_support_webcam"))

            return
        }

        navigator.mediaDevices
        .getUserMedia({ video: true, audio: false })
        .then((stream) => {
            video.srcObject = stream;
            video.play()

            window._cameraStream = stream
        })
        .catch((err) => {
            u("body").removeClass("dimmed");
            document.querySelector("html").style.overflowY = "scroll"
            u(".ovk-diag-cont").remove();

            fastError(err)
        });
        
        function __closeConnection() {
            window._cameraStream.getTracks().forEach(track => track.stop())
        }

        document.querySelector(".ovk-diag-action").insertAdjacentHTML("beforeend", `
            <button class="button" style="margin-left: 4px;" id="_takeSnap">${tr("take_snapshot")}</button>
        `)

        document.querySelector(".ovk-diag-action button").onclick = (evv) => {
            __closeConnection()
        }

        document.querySelector("#_takeSnap").onclick = (evv) => {
            let canvas = document.getElementById('_tempCanvas')
            let context = canvas.getContext('2d')

            canvas.setAttribute("width", video.clientWidth)
            canvas.setAttribute("height", video.clientHeight)
            context.drawImage(video, 0, 0, video.clientWidth, video.clientHeight);
            canvas.toBlob((blob) => {
                $("#_takeSnap").remove()
                
                let file = new File([blob], "snapshot.jpg", {type: "image/jpeg", lastModified: new Date().getTime()})
                let dt = new DataTransfer();
                dt.items.add(file);

                $("#_avaInput")[0].files = dt.files
                $("#_avaInput").trigger("change")
                $("#_takeSelfieFrame").remove()

                __closeConnection()
            })
        }
    })
})

$(document).on("click", ".avatarDelete", (e) => {
    let isGroup = e.currentTarget.closest(".avatar_block").dataset.club != null
    let group = isGroup ? e.currentTarget.closest(".avatar_block").dataset.club : 0

    let body = `
        <span>${tr("deleting_avatar_sure")}</span>
    `

    let msg = MessageBox(tr('deleting_avatar'), body, [
        tr('yes'),
        tr('no')
    ], [
        (function() {
            let formdata = new FormData()
            formdata.append("hash", u("meta[name=csrf]").attr("value"))

            $.ajax({
                type: "POST",
                url: isGroup ? "/club" + group + "/delete_avatar" : "/delete_avatar",
                data: formdata,
                processData: false,
                contentType: false,
                beforeSend: () => {
                    document.querySelector(".avatarDelete").classList.add("lagged")
                },
                error: (response) => {
                    fastError(response.flash.message)
                },
                success: (response) => {
                    if(!response.success) {
                        fastError(response.flash.message)
                        return
                    }

                    document.querySelector(".avatarDelete").classList.remove("lagged")
                    
                    u("body").removeClass("dimmed");
                    document.querySelector("html").style.overflowY = "scroll"
                    u(".ovk-diag-cont").remove()

                    document.querySelector("#bigAvatar").src = response.url
                    document.querySelector("#bigAvatar").parentNode.href = response.new_photo ? ("/photo" + response.new_photo) : "javascript:void(0)"
                    
                    if(!response.has_new_photo) {
                        document.querySelector(".avatar_controls").style.display = "none"
                        document.querySelector(".add_image_text").style.display = "block"
                    }
                }
            })
        }),
        (function() {
            u("#tmpPhDelF").remove();
        }),
    ]);
})

u(document).on('click', '#__sourceAttacher', (e) => {
    MessageBox(tr('add_source'), `
        <div id='source_flex_kunteynir'>
            <span>${tr('set_source_tip')}</span>
            <!-- давай, копируй ссылку и переходи по ней -->
            <input type='text' maxlength='400' placeholder='https://www.youtube.com/watch?v=lkWuk_nzzVA'>
        </div>
    `, [tr('cancel')], [
        () => {Function.noop}
    ])

    __removeDialog = () => {
        u("body").removeClass("dimmed");
        document.querySelector("html").style.overflowY = "scroll"
        u(".ovk-diag-cont").remove()
    }

    u('.ovk-diag-action').append(`
        <button class='button' id='__setsrcbutton'>${tr('set_source')}</button>
    `)

    u('.ovk-diag-action #__setsrcbutton').on('click', async (ev) => {
        // Consts
        const _u_target        = u(e.target)
        const nearest_textarea = _u_target.closest('#write')
        const source_output    = nearest_textarea.find(`input[name='source']`)
        const source_input     = u(`#source_flex_kunteynir input[type='text']`)
        const source_value     = source_input.nodes[0].value ?? ''
        if(source_value.length < 1) {
            return
        }

        ev.target.classList.add('lagged')

        // Checking link
        const __checkCopyrightLinkRes = await fetch(`/method/wall.checkCopyrightLink?auth_mechanism=roaming&link=${encodeURIComponent(source_value)}`)
        const checkCopyrightLink = await __checkCopyrightLinkRes.json()
        
        // todo переписать блять мессенджбоксы чтоб они классами были
        if(checkCopyrightLink.error_code) {
            __removeDialog()
            switch(checkCopyrightLink.error_code) {
                default:
                case 3102:
                    fastError(tr('error_adding_source_regex'))
                    return
                case 3103:
                    fastError(tr('error_adding_source_long'))
                    return
                case 3104:
                    fastError(tr('error_adding_source_sus'))
                    return
            }
        }

        // Making indicator
        __removeDialog()
        source_output.attr('value', source_value)
        nearest_textarea.find('.post-source').html(`
            <span>${tr('source')}: <a target='_blank' href='${source_value.escapeHtml()}'>${ovk_proc_strtr(source_value.escapeHtml(), 50)}</a></span>
            <div id='remove_source_button'></div>
        `)

        nearest_textarea.find('.post-source #remove_source_button').on('click', () => {
            nearest_textarea.find('.post-source').html('')
            source_output.attr('value', 'none')
        })
    })

    u('.ovk-diag-body').attr('style', `padding:8px;`)
    u('.ovk-diag-cont').attr('style', 'width: 325px;')
    u('#source_flex_kunteynir input').nodes[0].focus()
})
