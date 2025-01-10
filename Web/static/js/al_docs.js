function showDocumentUploadDialog(target = null, append_to_url = null)
{
    let file = null
    const cmsg = new CMessageBox({
        title: tr("document_uploading_in_general"),
        body: `
            <b>${tr("limits")}</b>
            <ul style="margin: 5px 0px;padding-left: 20px;">
                <li>${tr('limitations_file_limit_size', window.openvk.docs_max)}.</li>
                <li>${tr('limitations_file_allowed_formats')}: ${window.openvk.docs_allowed.sort(() => Math.random() - 0.59).slice(0, 10).join(', ')}.</li>
                <li>${tr("limitations_file_author_rights")}.</li>
            </ul>

            <div id="_document_upload_frame" style="text-align:center;margin: 10px 0px 2px 0px;">
                <input onclick="upload_btn.click()" class="button" type="button" value="${tr("select_file_fp")}">
                <input id="upload_btn" type="file" style="display:none;">
            </div>
        `,
        buttons: [tr('close')],
        callbacks: [Function.noop],
    })

    cmsg.getNode().find('.ovk-diag-body').attr('style', "padding:15px;")
    cmsg.getNode().attr('style', "width: 400px;")
    cmsg.getNode().find('#upload_btn').on('change', (e) => {
        file = e.target.files[0]
        const name = file.name
        const format = name.split(".")[1]
        if(window.openvk.docs_allowed.indexOf(format) == -1) {
            makeError(tr("error_file_invalid_format"))
            return
        }

        if(file.size > window.openvk.docs_max * 1024 * 1024) {
            makeError(tr("error_file_too_big"))
            return
        }

        cmsg.close()

        const cmsg_2 = new CMessageBox({
            title: tr("document_uploading_in_general"),
            body: `
                <p><b>${tr("info_name")}</b></p>
                <input type="text" name="doc_name" value="${name}" placeholder="...">

                <label>
                    <input maxlength="255" value="0" type="radio" name="doc_access" checked>
                    ${tr("private_document")}
                </label>
                <br>
                <label>
                    <input value="3" type="radio" name="doc_access">
                    ${tr("public_document")}
                </label>

                <p><b>${tr("tags")}</b></p>
                <input maxlength="256" type="text" name="doc_tags" placeholder="...">
                <br>
                <label>
                    <input type="checkbox" name="doc_owner" checked>
                    ${tr("owner_is_hidden")}
                </label>
            `,
            buttons: [tr('upload_button'), tr('cancel')],
            callbacks: [async () => {
                const fd = new FormData
                fd.append("name", u(`input[name="doc_name"]`).nodes[0].value)
                fd.append("tags", u(`input[name="doc_tags"]`).nodes[0].value)
                fd.append("folder", u(`input[name="doc_access"]:checked`).nodes[0].value)
                fd.append("owner_hidden", u(`input[name="doc_owner"]`).nodes[0].checked ? "on" : "off")
                fd.append("blob", file)
                fd.append("ajax", 1)
                fd.append("hash", window.router.csrf)

                const endpoint_url = `/docs/upload` + (!isNaN(append_to_url) ? "?gid="+append_to_url : '')
                const fetcher = await fetch(endpoint_url, {
                    method: 'POST',
                    body: fd,
                })
                const json = await fetcher.json()

                if(json.success) {
                    window.router.route(location.href)
                } else {
                    fastError(escapeHtml(json.flash.message))
                }
            }, Function.noop],
        })
        cmsg_2.getNode().find('.ovk-diag-body').attr('style', "padding:15px;")
        cmsg_2.getNode().attr('style', "width: 400px;")
    })
}

u(document).on("drop", "#_document_upload_frame", (e) => {
    e.dataTransfer.dropEffect = 'move';
    e.preventDefault()
    
    u(`#_document_upload_frame #upload_btn`).nodes[0].files = e.dataTransfer.files
    u("#_document_upload_frame #upload_btn").trigger("change")
})

u(document).on('click', '.docMainItem #edit_icon', async (e) => {
    e.preventDefault()
    if(u("#ajloader").hasClass("shown")) {
        return
    }

    const target = u(e.target).closest("#edit_icon")
    const item = target.closest('.docMainItem')
    const id = item.nodes[0].dataset.id

    CMessageBox.toggleLoader()

    const docs = await window.OVKAPI.call('docs.getById', {docs: id, return_tags: 1})
    const doc = docs[0]
    if(!doc) {
        fastError("(")
        CMessageBox.toggleLoader()
        return
    }

    const cmsg_2 = new CMessageBox({
        title: tr("document_editing_in_general"),
        body: `
            <p><b>${tr("info_name")}</b></p>
            <input maxlength="128" type="text" name="doc_name" value="${doc.title}" placeholder="...">

            <label>
                <input value="0" type="radio" name="doc_access" ${doc.folder_id != 3 ? "checked" : ''}>
                ${tr("private_document")}
            </label>
            <br>
            <label>
                <input value="3" type="radio" name="doc_access" ${doc.folder_id == 3 ? "checked" : ''}>
                ${tr("public_document")}
            </label>

            <p><b>${tr("tags")}</b></p>
            <input maxlength="256" type="text" name="doc_tags" value="${doc.tags.join(',')}" placeholder="...">
            <br>
            <label>
                <input type="checkbox" name="doc_owner" ${doc.is_hidden ? "checked" : ''}>
                ${tr("owner_is_hidden")}
            </label>
        `,
        buttons: [tr('save'), tr('cancel')],
        callbacks: [async () => {
            const params = {
                owner_id: id.split('_')[0],
                doc_id: id.split('_')[1],
                title: u(`input[name='doc_name']`).nodes[0].value,
                tags: u(`input[name='doc_tags']`).nodes[0].value,
                folder_id: u(`input[name="doc_access"]:checked`).nodes[0].value,
                owner_hidden: u(`input[name="doc_owner"]`).nodes[0].checked ? 1 : 0,
            }

            const edit = await window.OVKAPI.call('docs.edit', params)
            if(edit == 1) {
                item.find('.doc_content .doc_name').html(escapeHtml(params.title))
                item.find('.doc_content .doc_tags').html(escapeHtml(params.tags))
            }
        }, Function.noop],
    })
    cmsg_2.getNode().find('.ovk-diag-body').attr('style', "padding:15px;")
    cmsg_2.getNode().attr('style', "width: 400px;")

    CMessageBox.toggleLoader()
})

u(document).on('click', '#upload_entry_point', (e) => {
    showDocumentUploadDialog(null, Number(e.target.dataset.gid))
})

u(document).on('change', "#docs_page_wrapper select[name='docs_sort']", (e) => {
    const new_url = new URL(location.href)
    new_url.searchParams.set('order', e.target.value)

    window.router.route(new_url.href)
})

u(document).on('click', '.docMainItem #remove_icon', async (e) => {
    e.preventDefault()

    const target  = u(e.target).closest("#remove_icon")
    const item    = target.closest('.docMainItem')
    const context = item.attr('data-context')
    const id = item.nodes[0].dataset.id.split("_")

    target.addClass('lagged')
    const res = await window.OVKAPI.call('docs.delete', {owner_id: id[0], doc_id: id[1]})
    target.removeClass('lagged')

    if(res == 1) {
        target.attr('id', 'mark_icon')

        if(context == "page") {
            target.html('✓')
            window.router.route('/docs')
        }
    }
})

u(document).on('click', '.docMainItem #add_icon', async (e) => {
    e.preventDefault()

    const target = u(e.target).closest("#add_icon")
    const item   = target.closest('.docMainItem')
    const id = item.nodes[0].dataset.id.split("_")
    const context = item.attr('data-context')

    target.addClass('lagged')

    try {
        const res = await window.OVKAPI.call('docs.add', {owner_id: id[0], doc_id: id[1], access_key: id[2]})
    } catch(e) {
        makeError(tr("error_file_adding_copied"))
        target.removeClass('lagged')
        return
    }
    
    target.removeClass('lagged')
    target.attr('id', 'mark_icon')

    if(context == "page") {
        target.html('✓')
    }
})

u(document).on('click', '.docMainItem #report_icon', (e) => {
    e.preventDefault()
    
    const target = u(e.target).closest("#report_icon")
    const item   = target.closest('.docMainItem')
    const id = item.nodes[0].dataset.id.split("_")

    MessageBox(tr("report_question"), `
        ${tr("going_to_report_doc")}
        <br/>${tr("report_question_text")}
        <br/><br/><b> ${tr("report_reason")}</b>: <input type='text' id='uReportMsgInput' placeholder='${tr("reason")}' />`, [tr("confirm_m"), tr("cancel")], [(function() {
        
        res = document.querySelector("#uReportMsgInput").value;
        xhr = new XMLHttpRequest();
        xhr.open("GET", "/report/" + id[1] + "?reason=" + res + "&type=doc", true);
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

u(document).on("click", ".docListViewItem a.viewerOpener, a.docGalleryItem", async (e) => {
    e.preventDefault()
    if(e.target.closest('.doc_volume_action')) {
        return
    }

    const target = u(e.target)
    const link   = target.closest('a')

    CMessageBox.toggleLoader()
    const url = link.nodes[0].href
    const request = await fetch(url)
    const body_html = await request.text()
    const parser  = new DOMParser
    const body    = parser.parseFromString(body_html, "text/html")

    const preview = body.querySelector('.photo-page-wrapper-photo')
    const details = body.querySelector('.ovk-photo-details')

    u(preview.querySelector('img')).attr('id', 'ovk-photo-img')

    const photo_viewer = new CMessageBox({
        title: '',
        custom_template: u(`
        <div class="ovk-photo-view-dimmer">
            <div class="ovk-photo-view">
                <div class="photo_com_title">
                    <text id="photo_com_title_photos">
                        ${tr("document")}
                    </text>
                    <div>
                        <a id="ovk-photo-close">${tr("close")}</a>
                    </div>
                </div>
                <div class='photo_viewer_wrapper doc_viewer_wrapper'>
                    ${preview.innerHTML}
                </div>
                <div class="ovk-photo-details">
                    ${details.innerHTML}
                </div>
            </div>
        </div>`)
    })
    photo_viewer.getNode().find("#ovk-photo-close").on("click", function(e) {
        photo_viewer.close()
    });

    CMessageBox.toggleLoader()
})

u(document).on('click', '#__documentAttachment', async (e) => {
    const per_page = 10
    const form = u(e.target).closest('form') 
    const msg = new CMessageBox({
        title: tr('select_doc'),
        body: `
        <div class='attachment_selector'>
            <div id='attachment_insert' style='height: 325px;'>
                <div class="docsInsert"></div>
            </div>
        </div>
        `,
        buttons: [tr('close')],
        callbacks: [Function.noop]
    })

    msg.getNode().attr('style', 'width: 340px;')
    msg.getNode().find('.ovk-diag-body').attr('style', 'height:335px;padding:0px;')
    
    async function __recieveDocs(page) {
        u('#gif_loader').remove()
        u('#attachment_insert').append(`<div id='gif_loader'></div>`)
        const insert_place = u('#attachment_insert .docsInsert')
        let docs = null

        try {
            docs = await window.OVKAPI.call('docs.get', {'owner_id': window.openvk.current_id, 'count': per_page, 'offset': per_page * page})
        } catch(e) {
            u("#gif_loader").remove()
            insert_place.html("Err")
            return
        }

        u("#gif_loader").remove()
        const pages_count = Math.ceil(Number(docs.count) / per_page)

        if(docs.count < 1) {
            insert_place.append(tr('no_docs'))    
        }

        docs.items.forEach(doc => {
            is_attached = (form.find(`.upload-item[data-type='doc'][data-id='${doc.owner_id}_${doc.id}']`)).length > 0
            insert_place.append(`
                <div class='display_flex_row _content' data-attachmentdata="${doc.owner_id}_${doc.id}_${doc.access_key}" data-name='${escapeHtml(doc.title)}'>
                    <div class="attachDoc" id='__attach_doc'>
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
        await __recieveDocs(Number(target.nodes[0].dataset.page))
        target.remove()
    })

    u(".ovk-diag-body .attachment_selector").on("click", "#__attach_doc", async (ev) => {
        if(u(form).find(`.upload-item`).length > window.openvk.max_attachments) {
            makeError(tr('too_many_attachments'), 'Red', 10000, 1)
            return    
        }

        const target = u(ev.target).closest('._content')
        const button = target.find('#__attach_doc')
        const dataset = target.nodes[0].dataset
        const is_attached = (form.find(`.upload-item[data-type='doc'][data-id='${dataset.attachmentdata}']`)).length > 0
        if(is_attached) {
            (form.find(`.upload-item[data-type='doc'][data-id='${dataset.attachmentdata}']`)).remove()
            button.html(tr('attach'))
        } else {
            if(form.find(`.upload-item`).length + 1 > window.openvk.max_attachments) {
                makeError(tr('too_many_attachments'), 'Red', 10000, 1)
                return
            }

            button.html(tr('detach'))
            form.find('.post-vertical').append(`
                <div class="vertical-attachment upload-item" draggable="true" data-type='doc' data-id="${dataset.attachmentdata}">
                    <div class='vertical-attachment-content' draggable="false">
                        <div class="docMainItem attachment_doc attachment_note">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 8 10"><polygon points="0 0 0 10 8 10 8 4 4 4 4 0 0 0"/><polygon points="5 0 5 3 8 3 5 0"/></svg>
                            
                            <div class='attachment_note_content'>
                                <span class="attachment_note_text">${tr("document")}</span>
                                <span class="attachment_note_name"><a href="/doc${dataset.attachmentdata}">${ovk_proc_strtr(escapeHtml(dataset.name), 66)}</a></span>
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

    __recieveDocs(0)
})
