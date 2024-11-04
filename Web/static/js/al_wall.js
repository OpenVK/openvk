function initGraffiti(event) {
    let canvas = null;
    const msgbox = new CMessageBox({
        title: tr("draw_graffiti"),
        body: "<div id='ovkDraw'></div>",
        close_on_buttons: false,
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

function OpenMiniature(e, photo, post, photo_id, type = "post") {
    /*
    костыли но смешные однако
    */
    e.preventDefault();

    if(u(".ovk-photo-view").length > 0) u(".ovk-photo-view-dimmer").remove();

    // Значения для переключения фоток

    let json;

    let imagesCount = 0;
    let imagesIndex = 0;

    let tempDetailsSection = [];
    
    let dialog = u(
    `<div class="ovk-photo-view-dimmer">
        <div class="ovk-photo-view">
            <div class="photo_com_title">
                <text id="photo_com_title_photos">
                    <img src="/assets/packages/static/openvk/img/loading_mini.gif">
                </text>
                <div>
                    <a id="ovk-photo-close">${tr("close")}</a>
                </div>
            </div>
            <center style="margin-bottom: 8pt; position: relative;">
                <div class="ovk-photo-slide-left"></div>
                <div class="ovk-photo-slide-right"></div>
                <img src="${photo}" style="max-width: 100%; max-height: 60vh; user-select:none;" id="ovk-photo-img">
            </center>
            <div class="ovk-photo-details">
                <img src="/assets/packages/static/openvk/img/loading_mini.gif">
            </div>
        </div>
    </div>`);
    u("body").addClass("dimmed").append(dialog);
    document.querySelector("html").style.overflowY = "hidden"
    
    let button = u("#ovk-photo-close");

    button.on("click", function(e) {
        let __closeDialog = () => {
            u("body").removeClass("dimmed");
            u(".ovk-photo-view-dimmer").remove();
            document.querySelector("html").style.overflowY = "scroll"
        };
        
        __closeDialog();
    });

    function __reloadTitleBar() {
        u("#photo_com_title_photos").last().innerHTML = imagesCount > 1 ? tr("photo_x_from_y", imagesIndex, imagesCount) : tr("photo");
    }

    function __loadDetails(photo_id, index) {
        if(tempDetailsSection[index] == null) {
            u(".ovk-photo-details").last().innerHTML = '<img src="/assets/packages/static/openvk/img/loading_mini.gif">';
            ky("/photo" + photo_id, {
                hooks: {
                    afterResponse: [
                        async (_request, _options, response) => {
                            let parser = new DOMParser();
                            let body = parser.parseFromString(await response.text(), "text/html");

                            let element = u(body.getElementsByClassName("ovk-photo-details")).last();

                            tempDetailsSection[index] = element.innerHTML;

                            if(index == imagesIndex) {
                                u(".ovk-photo-details").last().innerHTML = element.innerHTML ?? '';
                            }

                            document.querySelectorAll(".ovk-photo-details .bsdn").forEach(bsdnInitElement)
                            document.querySelectorAll(".ovk-photo-details script").forEach(scr => {
                                // stolen from #953
                                let newScr = document.createElement('script')

                                if(scr.src) {
                                    newScr.src = scr.src
                                } else {
                                    newScr.textContent = scr.textContent
                                }

                                document.querySelector(".ovk-photo-details").appendChild(newScr);
                            })
                        }
                    ]
                }
            });
        } else {
            u(".ovk-photo-details").last().innerHTML = tempDetailsSection[index];
        }
    }

    function __slidePhoto(direction) {
        /* direction = 1 - right
           direction = 0 - left */
        if(json == undefined) {
            console.log("Да подожди ты. Куда торопишься?");
        } else {
            if(imagesIndex >= imagesCount && direction == 1) {
                imagesIndex = 1;
            } else if(imagesIndex <= 1 && direction == 0) {
                imagesIndex = imagesCount;
            } else if(direction == 1) {
                imagesIndex++;
            } else if(direction == 0) {
                imagesIndex--;
            }

            let photoURL = json.body[imagesIndex - 1].url;

            u("#ovk-photo-img").last().src = photoURL;
            __reloadTitleBar();
            __loadDetails(json.body[imagesIndex - 1].id, imagesIndex);
        }
    }

    let slideLeft = u(".ovk-photo-slide-left");

    slideLeft.on("click", (e) => {
        __slidePhoto(0);
    });

    let slideRight = u(".ovk-photo-slide-right");

    slideRight.on("click", (e) => {
        __slidePhoto(1);
    });

    let data = new FormData()
    data.append('parentType', type);
    
    if(type) {
        ky.post("/iapi/getPhotosFromPost/" + (type == "post" ? post : "1_"+post), {
            hooks: {
                afterResponse: [
                    async (_request, _options, response) => {
                        json = await response.json();
    
                        imagesCount = json.body.length;
                        imagesIndex = 0;
                        // Это всё придётся правда на 1 прибавлять
                        
                        json.body.every(element => {
                            imagesIndex++;
                            if(element.id == photo_id) {
                                return false;
                            } else {
                                return true;
                            }
                        });
    
                        __reloadTitleBar();
                        __loadDetails(json.body[imagesIndex - 1].id, imagesIndex);                }
                ]
            },
            body: data
        });
    } else {
        imagesCount = 1
        __reloadTitleBar()
        __loadDetails(photo_id, imagesIndex)
    }

    return u(".ovk-photo-view-dimmer");
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
            <img draggable="false" src="${attachment_obj.preview}" alt='...'>
        </a>      
    `)

    u(document).on('click', `.post-horizontal .upload-item[data-id='${attachment_obj.id}'] .upload-delete`, (e) => {
        e.preventDefault()
        indicator.find(`.upload-item[data-id='${attachment_obj.id}']`).remove()
    })
}

// ajax не буде работать

u('#write .small-textarea').on('paste', (e) => {
    if(e.clipboardData.files.length === 1) {
        __uploadToTextarea(e.clipboardData.files[0], u(e.target).closest('#write'))
        return;
    }
})

u('#write').on('dragstart', '.post-horizontal .upload-item', (e) => {
    //e.preventDefault()
    u(e.target).addClass('currently_dragging')
    return
})

u('#write').on('dragover', '.post-horizontal .upload-item', (e) => {
    e.preventDefault()

    const target = u(e.target).closest('.upload-item')
    const current = u('.upload-item.currently_dragging')

    if(target.nodes[0].dataset.id != current.nodes[0].dataset.id) {
        target.addClass('dragged')
    }
    
    return
})

u('#write').on('dragleave dragend', '.post-horizontal .upload-item', (e) => {
    u(e.target).closest('.upload-item').removeClass('dragged')
    return
})

u('#write').on("drop", function(e) {
    e.preventDefault()

    const current = u('.upload-item.currently_dragging')

    if(e.dataTransfer.types.includes('Files')) {
        e.dataTransfer.dropEffect = 'move'
        __uploadToTextarea(e.dataTransfer.files[0], u(e.target).closest('#write'))
    } else {
        const target = u(e.target).closest('.upload-item')
        target.removeClass('dragged')
        current.removeClass('currently_dragging')

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
    horizontal_attachments.nodes.slice(0, window.openvk.max_attachments).forEach(_node => {
        horizontal_array.push(`${_node.dataset.type}${_node.dataset.id}`)
    })
    horizontal_input.nodes[0].value = horizontal_array.join(',')

    const vertical_array = []
    const vertical_input = target.find(`input[name='vertical_attachments']`)
    const vertical_attachments = target.find(`.post-vertical > a`)
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

u(document).on('click', '.post-buttons .upload-item', (e) => {
    e.preventDefault()
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
                if(repostsCount.length > 0) {
                    repostsCount.html(previousVal + 1)
                } else {
                    u('#reposts' + id).nodes[0].insertAdjacentHTML('beforeend', `(<b id='repostsCount${id}'>1</b>)`)
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
