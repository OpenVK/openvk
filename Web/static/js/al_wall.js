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

async function OpenVideo(video_arr = [], init_player = true)
{
    CMessageBox.toggleLoader()
    const video_owner = video_arr[0]
    const video_id    = video_arr[1]
    let video_api     = null
    try {
        video_api   = await window.OVKAPI.call('video.get', {'videos': `${video_owner}_${video_id}`, 'extended': 1})
    
        if(!video_api.items || !video_api.items[0]) {
            throw new Error('Not found')
        }
    } catch(e) {
        CMessageBox.toggleLoader()
        fastError(e.message)

        return
    }

    // TODO: video lists
    const video_object = video_api.items[0]
    const pretty_id    = `${video_object.owner_id}_${video_object.id}`
    const author       = find_author(video_object.owner_id, video_api.profiles, video_api.groups)
    let player_html = ''
    if(init_player) {
        if(video_object.platform == 'youtube') {
            const video_url = new URL(video_object.player)
            const video_id = video_url.pathname.replace('/', '')
            player_html = `
            <iframe
               width="600"
               height="340"
               src="https://www.youtube-nocookie.com/embed/${video_id}"
               frameborder="0"
               sandbox="allow-same-origin allow-scripts allow-popups"
               allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture"
               allowfullscreen></iframe>
            `
        } else {
            if(!video_object.is_processed) {
                player_html = `<span class='gray'>${tr('video_processing')}</span>`
            } else {
                const author_name = `${author.first_name} ${author.last_name}`
                player_html = `
                    <div class='bsdn media' data-name="${escapeHtml(video_object.title)}" data-author="${escapeHtml(author_name)}">
                        <video class='media' src='${video_object.player}'></video>
                    </div>
                `
            }
        }
    }

    const msgbox = new CMessageBox({
        title: '...',
        close_on_buttons: false,
        warn_on_exit: true,
        custom_template: u(`
        <div class="ovk-photo-view-dimmer">
            <div class="ovk-modal-player-window">
                <div id="ovk-player-part">
                    <div class='top-part'>
                        <b>${escapeHtml(video_object.title)}</b>

                        <div class='top-part-buttons'>
                            <a id='__modal_player_minimize' class='hoverable_color'>${tr('hide_player')}</a>
                            |
                            <a id='__modal_player_close' class='hoverable_color'>${tr('close')}</a>
                        </div>
                    </div>
                    <div class='center-part'>
                        ${player_html}
                    </div>
                    <div class='bottom-part'>
                        <a id='__toggle_comments' class='hoverable_color'>${tr('show_comments')}</a>
                        |
                        <a href='/video${pretty_id}' class='hoverable_color'>${tr('to_page')}</a>
                    </div>
                </div>
                <div id="ovk-player-info"></div>
            </div>
        </div>
        `)
    })

    if(video_object.platform != 'youtube' && video_object.is_processed) {
        bsdnInitElement(msgbox.getNode().find('.bsdn').nodes[0])
    }

    msgbox.getNode().find('#ovk-player-part #__modal_player_close').on('click', (e) => {
        msgbox.close()
    })

    msgbox.getNode().find('#__toggle_comments').on('click', async (e) => {
        if(msgbox.getNode().find('#ovk-player-info').hasClass('shown')) {
            msgbox.getNode().find('#__toggle_comments').html(tr('show_comments'))
        } else {
            msgbox.getNode().find('#__toggle_comments').html(tr('close_comments'))
        }

        msgbox.getNode().find('#ovk-player-info').toggleClass('shown')
        if(msgbox.getNode().find('#ovk-player-info').html().length < 1) {
            u('#ovk-player-info').html(`<div id='gif_loader'></div>`)

            const fetcher = await fetch(`/video${pretty_id}`)
            const fetch_r = await fetcher.text()
            const dom_parser = new DOMParser
            const results =  u(dom_parser.parseFromString(fetch_r, 'text/html'))
            const details = results.find('.ovk-vid-details')
            details.find('.media-page-wrapper-description b').remove()

            u('#ovk-player-info').html(details.html())
            bsdnHydrate()
        }
    })

    msgbox.getNode().find('#__modal_player_minimize').on('click', (e) => {
        e.preventDefault()

        const miniplayer = u(`
            <div class='miniplayer'>
                <div class='miniplayer-head'>
                    <b>${escapeHtml(video_object.title)}</b>
                    <div class='miniplayer-head-buttons'>
                        <div id='__miniplayer_return'></div>
                        <div id='__miniplayer_close'></div>
                    </div>
                </div>
                <div class='miniplayer-body'></div>
            </div>
        `)
        msgbox.hide()

        u('body').append(miniplayer)
        miniplayer.find('.miniplayer-body').nodes[0].append(msgbox.getNode().find('.center-part > *').nodes[0])
        miniplayer.attr('style', `left:100px;top:${scrollY}px;`)
        miniplayer.find('#__miniplayer_return').on('click', (e) => {
            msgbox.reveal()
            msgbox.getNode().find('.center-part').nodes[0].append(miniplayer.find('.miniplayer-body > *').nodes[0])
            u('.miniplayer').remove()
        })

        miniplayer.find('#__miniplayer_close').on('click', (e) => {
            msgbox.close()
            u('.miniplayer').remove()
        })

        $('.miniplayer').draggable({cursor: 'grabbing', containment: 'body', cancel: '.miniplayer-body'})
        $('.miniplayer').resizable({
            maxHeight: 2000,
            maxWidth: 3000,
            minHeight: 150,
            minWidth: 200
        })
    })

    CMessageBox.toggleLoader()
}

u(document).on('click', '#videoOpen', (e) => {
    e.preventDefault()

    try {
        const target = e.target.closest('#videoOpen')
        const vid = target.dataset.id
        const split = vid.split('_')

        OpenVideo(split)
    } catch(ec) {
        return
    }
})

u(document).on("keydown", "#write > form", function(event) {
    if(event.ctrlKey && event.keyCode === 13)
        u(event.target).closest('form').find(`input[type='submit']`).nodes[0].click()
});

u(document).on('keydown', '.edit_menu #write', (e) => {
    if(e.ctrlKey && e.keyCode === 13)
        e.target.closest('.edit_menu').querySelector('#__edit_save').click()
})

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

function reportVideo(video_id) {
    uReportMsgTxt  = tr("going_to_report_video");
    uReportMsgTxt += "<br/>"+tr("report_question_text");
    uReportMsgTxt += "<br/><br/><b>"+tr("report_reason")+"</b>: <input type='text' id='uReportMsgInput' placeholder='" + tr("reason") + "' />"

    MessageBox(tr("report_question"), uReportMsgTxt, [tr("confirm_m"), tr("cancel")], [
        (function() {
            res = document.querySelector("#uReportMsgInput").value;
            xhr = new XMLHttpRequest();
            xhr.open("GET", "/report/" + video_id + "?reason=" + res + "&type=video", true);
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

tippy.delegate("body", {
    target: '.client_app',
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

tippy.delegate('body', {
    animation: 'up_down',
    target: `.post-like-button[data-type]:not([data-likes="0"])`,
    theme: "special vk",
    content: "⌛",
    allowHTML: true,
    interactive: true,
    interactiveDebounce: 500,

    onCreate: async function(that) {
        that._likesList = null;
    },

    onShow: async function(that) {
        const id  = that.reference.dataset.id
        const type = that.reference.dataset.type
        let final_type = type
        if(type == 'post') {
            final_type = 'wall'
        }

        if(!that._likesList) {
            that._likesList = await window.OVKAPI.call('likes.getList', {'extended': 1, 'count': 6, 'type': type, 'owner_id': id.split('_')[0], 'item_id': id.split('_')[1]})
        }

        const final_template = u(`
            <div style='margin: -6px -10px;'>
                <div class='like_tooltip_wrapper'>
                    <a href="/${final_type}/${id}/likes" class='like_tooltip_head'>
                        <span>${tr('liked_by_x_people', that._likesList.count)}</span>
                    </a>

                    <div class='like_tooltip_body'>
                        <div class='like_tooltip_body_grid'></div>
                    </div>
                </div>
            </div>
        `)

        that._likesList.items.forEach(item => {
            final_template.find('.like_tooltip_body .like_tooltip_body_grid').append(`
                <a href='/id${item.id}'><img src='${item.photo_50}' alt='.'></a>
            `)
        })
        that.setContent(final_template.nodes[0].outerHTML)
    }
})

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

u(document).on("click", "#editPost", async (e) => {
    const target = u(e.target)
    const post = target.closest("table")
    const content = post.find(".post-content")
    const edit_place = post.find('.post-edit')
    const id = post.attr('data-id').split('_')

    let type = 'post'
    if(post.hasClass('comment')) {
        type = 'comment'
    }

    if(post.hasClass('editing')) {
        post.removeClass('editing')
        return
    }

    if(edit_place.html() == '') {
        target.addClass('lagged')
        const params = {}
        if(type == 'post') {
            params['posts'] = post.attr('data-id')
        } else {
            params['owner_id'] = 1
            params['comment_id'] = id[1]
        }

        const api_req = await window.OVKAPI.call(`wall.${type == 'post' ? 'getById' : 'getComment'}`, params)
        const api_post = api_req.items[0]
        
        edit_place.html(`
            <div class='edit_menu'>
                <form id="write">
                    <textarea placeholder="${tr('edit')}" name="text" style="width: 100%;resize: none;" class="expanded-textarea small-textarea">${api_post.text}</textarea>
                    
                    <div class='post-buttons'>
                        <div class="post-horizontal"></div>
                        <div class="post-vertical"></div>
                        <div class="post-repost"></div>
                        <div class="post-source"></div>

                        <div class='post-opts'>
                            ${type == 'post' ? `<label>
                                <input type="checkbox" name="nsfw" ${api_post.is_explicit ? 'checked' : ''} /> ${tr('contains_nsfw')}
                            </label>` : ''}

                            ${api_post.owner_id < 0 && api_post.can_pin ? `<label>
                                <input type="checkbox" name="as_group" ${api_post.from_id < 0 ? 'checked' : ''} /> ${tr('post_as_group')}
                            </label>` : ''}
                        </div>

                        <input type="hidden" id="source" name="source" value="none" />

                        <div class='edit_menu_buttons'>
                            <input class='button' type='button' id='__edit_save' value='${tr('save')}'>
                            <input class='button' type='button' id='__edit_cancel' value='${tr('cancel')}'>

                            <div style="float: right; display: flex; flex-direction: column;">
                                <a class='menu_toggler'>
                                    ${tr('attach')}
                                </a>
                                
                                <div id="wallAttachmentMenu" class="hidden">
                                    <a class="header menu_toggler">
                                        ${tr('attach')}
                                    </a>
                                    <a id="__photoAttachment">
                                        <img src="/assets/packages/static/openvk/img/oxygen-icons/16x16/mimetypes/application-x-egon.png" />
                                        ${tr('photo')}
                                    </a>
                                    <a id="__videoAttachment">
                                        <img src="/assets/packages/static/openvk/img/oxygen-icons/16x16/mimetypes/application-vnd.rn-realmedia.png" />
                                        ${tr('video')}
                                    </a>
                                    <a id="__audioAttachment">
                                        <img src="/assets/packages/static/openvk/img/oxygen-icons/16x16/mimetypes/audio-ac3.png" />
                                        ${tr('audio')}
                                    </a>
                                    ${type == 'post' ? `<a id="__notesAttachment">
                                        <img src="/assets/packages/static/openvk/img/oxygen-icons/16x16/mimetypes/application-x-srt.png" />
                                        ${tr('note')}
                                    </a>
                                    <a id='__sourceAttacher'>
                                        <img src="/assets/packages/static/openvk/img/oxygen-icons/16x16/actions/insert-link.png" />
                                        ${tr('source')}
                                    </a>` : ''}
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>`)

        if(api_post.copyright) {
            edit_place.find('.post-source').html(`
                <span>${tr('source')}: <a>${escapeHtml(api_post.copyright.link)}</a></span>
                <div id='remove_source_button'></div>
            `)

            edit_place.find('.post-source #remove_source_button').on('click', (e) => {
                edit_place.find('.post-source').html('')
                edit_place.find(`input[name='source']`).attr('value', 'remove')
            })
        }

        if(api_post.copy_history && api_post.copy_history.length > 0) {
            edit_place.find('.post-repost').html(`
                <span>${tr('has_repost')}.</span>
            `)
        }

        // horizontal attachments
        api_post.attachments.forEach(att => {
            const type = att.type
            const aid = att[type].owner_id + '_' + att[type].id

            if(type == 'video' || type == 'photo') {
                let preview = ''

                if(type == 'photo') {
                    preview = att[type].sizes[1].url
                } else {
                    preview = att[type].image[0].url
                }

                __appendToTextarea({
                    'type': type,
                    'preview': preview,
                    'id': aid
                }, edit_place)
            } else if(type == 'poll') {
                __appendToTextarea({
                    'type': type,
                    'alignment': 'vertical',
                    'html': tr('poll'),
                    'id': att[type].id,
                    'undeletable': true,
                }, edit_place) 
            } else {
                const found_block = post.find(`div[data-att_type='${type}'][data-att_id='${aid}']`)
                __appendToTextarea({
                    'type': type,
                    'alignment': 'vertical',
                    'html': found_block.html(),
                    'id': aid,
                }, edit_place)
            }
        })

        target.removeClass('lagged')

        edit_place.find('.edit_menu #__edit_save').on('click', async (ev) => {
            const text_node = edit_place.find('.edit_menu textarea')
            const nsfw_mark = edit_place.find(`.edit_menu input[name='nsfw']`)
            const as_group = edit_place.find(`.edit_menu input[name='as_group']`)
            const copyright = edit_place.find(`.edit_menu input[name='source']`)
            const collected_attachments = collect_attachments(edit_place.find('.post-buttons')).join(',')
            const params = {}
            
            params['owner_id'] = id[0]
            params['post_id'] = id[1]
            params['message'] = text_node.nodes[0].value

            if(nsfw_mark.length > 0) {
                params['explicit'] = Number(nsfw_mark.nodes[0].checked)
            }
            
            params['attachments'] = collected_attachments
            if(collected_attachments.length < 1) {
                params['attachments'] = 'remove'
            }

            if(as_group.length > 0 && as_group.nodes[0].checked) {
                params['from_group'] = 1
            }

            if(copyright.nodes[0].value != 'none') {
                params['copyright'] = copyright.nodes[0].value
            }

            u(ev.target).addClass('lagged')
            // больше двух запросов !
            try {
                if(type == 'post') {
                    await window.OVKAPI.call('wall.edit', params)
                } else {
                    params['comment_id'] = id[1]
                    await window.OVKAPI.call('wall.editComment', params)
                }
            } catch(e) {
                fastError(e.message)
                u(ev.target).removeClass('lagged')
                return
            }
            
            const new_post_html = await (await fetch(`/iapi/getPostTemplate/${id[0]}_${id[1]}?type=${type}`, {
                'method': 'POST'
            })).text()
            u(ev.target).removeClass('lagged')
            post.removeClass('editing')
            post.nodes[0].outerHTML = u(new_post_html).last().outerHTML

            bsdnHydrate()
        })
    
        edit_place.find('.edit_menu #__edit_cancel').on('click', (e) => {
            post.removeClass('editing')
        })
    }

    post.addClass('editing')
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
        textareaNode.find('.post-horizontal').append(`<a id='temp_filler${rand}' class="upload-item lagged"><img src='${temp_url}'></a>`)
        
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

    if(attachment_obj.alignment == 'vertical') {
        textareaNode.find('.post-vertical').append(`
            <div class="vertical-attachment upload-item" draggable="true" data-type='${attachment_obj.type}' data-id="${attachment_obj.id}">
                <div class='vertical-attachment-content' draggable="false">
                    ${attachment_obj.html}
                </div>
                <div class='${attachment_obj.undeletable ? 'lagged' : ''} vertical-attachment-remove'>
                    <div id='small_remove_button'></div>
                </div>
            </div>
        `)

        return
    }
    
    indicator.append(`
        <a draggable="true" href='/${attachment_obj.type}${attachment_obj.id}' class="upload-item" data-type='${attachment_obj.type}' data-id="${attachment_obj.id}">
            <span class="upload-delete">×</span>
            ${attachment_obj.type == 'video' ? `<div class='play-button'><div class='play-button-ico'></div></div>` : ''}
            <img draggable="false" src="${attachment_obj.preview}" alt='...'>
        </a>      
    `)
}

u(document).on('paste', '#write .small-textarea', (e) => {
    if(e.clipboardData.files.length === 1) {
        __uploadToTextarea(e.clipboardData.files[0], u(e.target).closest('#write'))
        return;
    }
})

u(document).on('dragstart', '#write .post-horizontal .upload-item, .post-vertical .upload-item', (e) => {
    //e.preventDefault()
    //console.log(e)
    u(e.target).closest('.upload-item').addClass('currently_dragging')
    return
})

u(document).on('dragover', '#write .post-horizontal .upload-item, .post-vertical .upload-item', (e) => {
    e.preventDefault()

    const target = u(e.target).closest('.upload-item')
    const current = u('.upload-item.currently_dragging')

    if(current.length < 1) {
        return
    }

    if(target.nodes[0].dataset.id != current.nodes[0].dataset.id) {
        target.addClass('dragged')
    }
    
    return
})

u(document).on('#write dragleave dragend', '.post-horizontal .upload-item, .post-vertical .upload-item', (e) => {
    //console.log(e)
    u(e.target).closest('.upload-item').removeClass('dragged')
    return
})

u(document).on("drop", '#write', function(e) {
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

u(document).on('submit', '#write > form', (e) => {
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
        
        if(pages_count < 1) {
            insert_place.append(query == '' ? tr('no_videos') : tr('no_videos_results'))
        }

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

        if(notes.count < 1) {
            insert_place.append(tr('no_notes'))    
        }

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
                                <span class="attachment_note_name">${ovk_proc_strtr(escapeHtml(dataset.name), 66)}</span>
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
    
    if(window.openvk.current_id == 0) {
        return
    }
    
    u(e.target).closest('.post-nsfw').removeClass('post-nsfw')
})

u(document).on('focusin', '#write', (e) => {
    const target = u(e.target).closest('#write')
    target.find('.post-buttons').attr('style', 'display:block')
    target.find('.small-textarea').addClass('expanded-textarea')
})

async function repost(id, repost_type = 'post') {
    const repostsCount = u(`#repostsCount${id}`)
    const previousVal  = repostsCount.length > 0 ? Number(repostsCount.html()) : 0;

    const msg = new CMessageBox({
        title: tr('share'),
        unique_name: 'repost_modal',
        body: `
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
        `,
        buttons: [tr('send'), tr('cancel')],
        callbacks: [
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
        ]
    });
    
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

async function __processPaginatorNextPage(page)
{
    const container = u('.scroll_container')
    const container_node = '.scroll_node'
    const parser = new DOMParser

    const replace_url = new URL(location.href)
    replace_url.searchParams.set('p', page)
    /*replace_url.searchParams.set('al', 1)
    replace_url.searchParams.set('hash', u("meta[name=csrf]").attr("value"))*/

    const new_content = await fetch(replace_url.href)
    const new_content_response = await new_content.text()
    const parsed_content = parser.parseFromString(new_content_response, 'text/html')

    const nodes = parsed_content.querySelectorAll(container_node)
    nodes.forEach(node => {
        container.append(node)
    })

    u(`.paginator:not(.paginator-at-top)`).html(parsed_content.querySelector('.paginator:not(.paginator-at-top)').innerHTML)
    if(u(`.paginator:not(.paginator-at-top)`).nodes[0].closest('.scroll_container')) {
        container.nodes[0].append(u(`.paginator:not(.paginator-at-top)`).nodes[0].parentNode)
    }
    
    if(window.player) {
        window.player.loadContextPage(page)
    }

    if(typeof __scrollHook != 'undefined') {
        __scrollHook(page)
    }
}

const showMoreObserver = new IntersectionObserver(entries => {
    entries.forEach(async x => {
        if(x.isIntersecting) {
            if(Number(localStorage.getItem('ux.auto_scroll') ?? 1) == 0) {
                return
            }

            if(u('.scroll_container').length < 1) {
                return
            }
            
            const target = u(x.target)
            if(target.length < 1 || target.hasClass('paginator-at-top')) {
                return
            }

            const current_url = new URL(location.href)
            if(current_url.searchParams && !isNaN(parseInt(current_url.searchParams.get('p')))) {
                return
            }

            target.addClass('lagged')
            const active_tab = target.find('.active')
            const next_page  = u(active_tab.nodes[0] ? active_tab.nodes[0].nextElementSibling : null)
            if(next_page.length < 1) {
                u('.paginator:not(.paginator-at-top)').removeClass('lagged')
                return
            }

            const page_number = Number(next_page.html())
            await __processPaginatorNextPage(page_number)
            bsdnHydrate()
            u('.paginator:not(.paginator-at-top)').removeClass('lagged')
        }
    })
}, {
    root: null,
    rootMargin: '0px',
    threshold: 0,
})

if(u('.paginator:not(.paginator-at-top)').length > 0) {
    showMoreObserver.observe(u('.paginator:not(.paginator-at-top)').nodes[0])
}

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

u(document).on('keyup', async (e) => {
    if(u('#ovk-player-part .bsdn').length > 0) {
        switch(e.keyCode) {
            case 32:
                u('#ovk-player-part .bsdn .bsdn_playButton').trigger('click')
                break
            case 39:
                u('#ovk-player-part video').nodes[0].currentTime = u('#ovk-player-part video').nodes[0].currentTime + 2
                break
            case 37:
                u('#ovk-player-part video').nodes[0].currentTime = u('#ovk-player-part video').nodes[0].currentTime - 2
                break
        }
    }
})
