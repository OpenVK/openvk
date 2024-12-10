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

u(document).on("click", ".post-like-button", function(e) {
    e.preventDefault();
    e.stopPropagation()
    
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
    e.stopPropagation()

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
        miniplayer.attr('style', `left:100px;top:0px;`)
        miniplayer.find('#__miniplayer_return').on('click', (e) => {
            msgbox.reveal()
            msgbox.getNode().find('.center-part').nodes[0].append(miniplayer.find('.miniplayer-body > *').nodes[0])
            u('.miniplayer').remove()
        })

        miniplayer.find('#__miniplayer_close').on('click', (e) => {
            msgbox.close()
            u('.miniplayer').remove()
        })

        $('.miniplayer').draggable({cursor: 'grabbing', containment: 'window', cancel: '.miniplayer-body'})
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
    e.stopPropagation()

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

// Migrated from inline start
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

function reportUser(user_id) {
    uReportMsgTxt  = tr("going_to_report_user");
    uReportMsgTxt += "<br/>"+tr("report_question_text");
    uReportMsgTxt += "<br/><br/><b>"+tr("report_reason")+"</b>: <input type='text' id='uReportMsgInput' placeholder='" + tr("reason") + "' />"

    MessageBox(tr("report_question"), uReportMsgTxt, [tr("confirm_m"), tr("cancel")], [
        (function() {
            res = document.querySelector("#uReportMsgInput").value;
            xhr = new XMLHttpRequest();
            xhr.open("GET", "/report/" + user_id + "?reason=" + res + "&type=user", true);
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

function reportComment(comment_id) {
    uReportMsgTxt  = tr("going_to_report_comment");
    uReportMsgTxt += "<br/>"+tr("report_question_text");
    uReportMsgTxt += "<br/><br/><b>"+tr("report_reason")+"</b>: <input type='text' id='uReportMsgInput' placeholder='" + tr("reason") + "' />"

    MessageBox(tr("report_question"), uReportMsgTxt, [tr("confirm_m"), tr("cancel")], [
        (function() {
            res = document.querySelector("#uReportMsgInput").value;
            xhr = new XMLHttpRequest();
            xhr.open("GET", "/report/" + comment_id + "?reason=" + res + "&type=comment", true);
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

function reportApp(id) {
    uReportMsgTxt  = tr('going_to_report_app');
    uReportMsgTxt += "<br/>"+tr("report_question_text");
    uReportMsgTxt += "<br/><br/><b>"+tr("report_reason")+"</b>: <input type='text' id='uReportMsgInput' placeholder='" + tr("reason") + "' />"

    MessageBox(tr("report_question"), uReportMsgTxt, [tr("confirm_m"), tr("cancel")], [
        (function() {
            res = document.querySelector("#uReportMsgInput").value;
            xhr = new XMLHttpRequest();
            xhr.open("GET", "/report/" + id + "?reason=" + res + "&type=app", true);
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

function reportClub(club_id) {
    uReportMsgTxt  = tr("going_to_report_club");
    uReportMsgTxt += "<br/>"+tr("report_question_text");
    uReportMsgTxt += "<br/><br/><b>"+tr("report_reason")+"</b>: <input type='text' id='uReportMsgInput' placeholder='" + tr("reason") + "' />"

    MessageBox(tr("report_question"), uReportMsgTxt, [tr("confirm_m"), tr("cancel")], [
        (function() {
            res = document.querySelector("#uReportMsgInput").value;
            xhr = new XMLHttpRequest();
            xhr.open("GET", "/report/" + club_id + "?reason=" + res + "&type=group", true);
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

$(document).on("click", "#_photoDelete, #_videoDelete", function(e) {
    var formHtml = "<form id='tmpPhDelF' action='" + u(this).attr("href") + "' >";
    formHtml    += "<input type='hidden' name='hash' value='" + u("meta[name=csrf]").attr("value") + "' />";
    formHtml    += "</form>";
    u("body").append(formHtml);
    
    MessageBox(tr('warning'), tr('question_confirm'), [
        tr('yes'),
        tr('no')
    ], [
        (function() {
            u("#tmpPhDelF").nodes[0].submit();
        }),
        (function() {
            u("#tmpPhDelF").remove();
        }),
    ]);
    
    e.stopPropagation()
    return e.preventDefault();
});
/* @rem-pai why this func wasn't named as "#_deleteDialog"? It looks universal IMO */

u(document).on("click", "#_noteDelete", function(e) {
    var formHtml = "<form id='tmpPhDelF' action='" + u(this).attr("href") + "' >";
    formHtml    += "<input type='hidden' name='hash' value='" + u("meta[name=csrf]").attr("value") + "' />";
    formHtml    += "</form>";
    u("body").append(formHtml);
    
    MessageBox(tr('warning'), tr('question_confirm'), [
        tr('yes'),
        tr('no')
    ], [
        (function() {
            u("#tmpPhDelF").nodes[0].submit();
        }),
        (function() {
            u("#tmpPhDelF").remove();
        }),
    ]);
    
    e.stopPropagation()
    return e.preventDefault();
});

// TODO REWRITE cuz its a little broken
u(document).on("click", "#_pinGroup", async function(e) {
    e.preventDefault();
    e.stopPropagation()

    let link = u(this).attr("href");
    let thisButton = u(this);
    let groupName = u(this).attr("data-group-name");
    let groupUrl = u(this).attr("data-group-url");
    let list = u('#_groupListPinnedGroups');
    
    thisButton.nodes[0].classList.add('loading');
    thisButton.nodes[0].classList.add('disable');

    let req = await ky(link);
    if(req.ok == false) {
        NewNotification(tr('error'), tr('error_1'), null);
        thisButton.nodes[0].classList.remove('loading');
        thisButton.nodes[0].classList.remove('disable');
        return;
    }

    if(!parseAjaxResponse(await req.text())) {
        thisButton.nodes[0].classList.remove('loading');
        thisButton.nodes[0].classList.remove('disable');
        return;
    }

    // Adding a divider if not already there
    if(list.nodes[0].children.length == 0) {
        list.nodes[0].append(u('<div class="menu_divider"></div>').first());
    }

    // Changing the button name
    if(thisButton.html().trim() == tr('remove_from_left_menu')) {
        thisButton.html(tr('add_to_left_menu'));
        for(let i = 0; i < list.nodes[0].children.length; i++) {
            let element = list.nodes[0].children[i];
            if(element.pathname == groupUrl) {
                element.remove();
            }
        }
    }else{
        thisButton.html(tr('remove_from_left_menu'));
        list.nodes[0].append(u('<a href="' + groupUrl + '" class="link group_link">' + groupName + '</a>').first());
    }

    // Adding the group to the left group list
    if(list.nodes[0].children[0].className != "menu_divider" || list.nodes[0].children.length == 1) {
        list.nodes[0].children[0].remove();
    }
    
    thisButton.nodes[0].classList.remove('loading');
    thisButton.nodes[0].classList.remove('disable');

    return false;
});

u(document).handle("submit", "#_submitUserSubscriptionAction", async function(e) {
    e.preventDefault()
    e.stopPropagation()

    u(this).nodes[0].parentElement.classList.add('loading');
    u(this).nodes[0].parentElement.classList.add('disable');
    console.log(e.target);
    const data = await fetch(u(this).attr('action'), { method: 'POST', body: new FormData(e.target) });
    if (data.ok) {
        u(this).nodes[0].parentElement.classList.remove('loading');
        u(this).nodes[0].parentElement.classList.remove('disable');
        if (e.target[0].value == "add") {
            u(this).nodes[0].parentElement.innerHTML = tr("friends_add_msg");
        } else if (e.target[0].value == "rej") {
            u(this).nodes[0].parentElement.innerHTML = tr("friends_rej_msg");
        } else if (e.target[0].value == "rem") {
            u(this).nodes[0].parentElement.innerHTML = tr("friends_rem_msg");
        }
    }
})

function changeOwner(club, newOwner, newOwnerName) {
    const action = "/groups/" + club + "/setNewOwner/" + newOwner;

    MessageBox(tr('group_changeowner_modal_title'), `
        ${tr("group_changeowner_modal_text", escapeHtml(newOwnerName))}
        <br/><br/>
        <form id="transfer-owner-permissions-form" method="post">
            <label for="password">${tr('password')}</label>
            <input type="password" id="password" name="password" required />
            <input type="hidden" name="hash" value='${window.router.csrf}' />
        </form>
    `, [tr('transfer'), tr('cancel')], [
        () => {
            $("#transfer-owner-permissions-form").attr("action", action);
            document.querySelector("#transfer-owner-permissions-form").submit();
        }, Function.noop
    ]);
}

async function withdraw(id) {
    let coins = await API.Apps.withdrawFunds(id);
    if(coins == 0)
        MessageBox(tr('app_withdrawal'), tr('app_withdrawal_empty'), ["OK"], [Function.noop]);
    else
        MessageBox(tr('app_withdrawal'), tr("app_withdrawal_created", window.coins), ["OK"], [Function.noop]);
}

function toggleMaritalStatus(e) {
    let elem = $("#maritalstatus-user");
    $("#maritalstatus-user-select").empty();
    if ([0, 1, 8].includes(Number(e.value))) {
        elem.hide();
    } else {
        elem.show();
    }
}

u(document).on("paste", ".vouncher_input", function(event) {
    const vouncher = event.clipboardData.getData("text");

    let segments;
    if(vouncher.length === 27) {
        segments = vouncher.split("-");
        if(segments.length !== 4)
            segments = undefined;
    } else if(vouncher.length === 24) {
        segments = chunkSubstr(vouncher, 6);
    }

    if(segments !== undefined) {
        document.vouncher_form.key0.value = segments[0];
        document.vouncher_form.key1.value = segments[1];
        document.vouncher_form.key2.value = segments[2];
        document.vouncher_form.key3.value = segments[3];
        document.vouncher_form.key3.focus();
    }

    event.preventDefault();
});

// Migrated from inline end

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