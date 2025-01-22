function showDocumentUploadDialog(target = null, append_to_url = null, after_upload = null)
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
                <input id="upload_btn" type="file" accept="${window.openvk.docs_allowed.join(",.")}" style="display:none;">
            </div>
        `,
        buttons: [tr('close')],
        callbacks: [Function.noop],
        unique_name: "doc_upload_dialog",
    })

    cmsg.getNode().find('.ovk-diag-body').attr('style', "padding:15px;")
    cmsg.getNode().attr('style', "width: 400px;")
    cmsg.getNode().find('#upload_btn').on('change', (e) => {
        file = e.target.files[0]
        const name = file.name
        const format = name.split(".")[name.split(".").length - 1]
        if(window.openvk.docs_allowed.indexOf(format.toLowerCase()) == -1) {
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
                    if(target != "search") {
                        window.router.route(location.href)
                    } else {
                        if(after_upload)
                            after_upload()
                    }
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
        unique_name: "document_edit_modal",
        title: tr("document_editing_in_general"),
        body: `
            <p><b>${tr("info_name")}</b></p>
            <input maxlength="128" type="text" name="doc_name" value="${escapeHtml(doc.title)}" placeholder="...">

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
            <input maxlength="256" type="text" name="doc_tags" value="${escapeHtml(doc.tags.join(','))}" placeholder="...">
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
    if(window.openvk.current_id == 0) {
        return
    }

    const target = u(e.target)
    const link   = target.closest('a')
    if(target.closest(".embeddable").length > 0) {
        target.closest(".embeddable").toggleClass("playing")
        return
    }

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

// ctx > "wall" and maybe "messages" in future
// source > "user" || "club" > source_arg
async function __docAttachment(form, ctx = "wall", source = "user", source_arg = 0) {
    const per_page = 10
    const msg = new CMessageBox({
        title: tr('select_doc'),
        custom_template: u(`
            <div class="ovk-photo-view-dimmer">
                <div class="ovk-photo-view">
                    <div class="photo_com_title">
                        <text id="photo_com_title_photos">
                            ${tr("select_doc")}
                        </text>
                        <span style="display: inline-flex;gap: 7px;">
                            ${source != "user" ? `<a id="_doc_picker_go_to_my">${tr("go_to_my_documents")}</a>`: ""}
                            <a id="_doc_picker_upload">${tr("upload_button")}</a>
                        </span>
                        <div>
                            <a id="ovk-photo-close">${tr("close")}</a>
                        </div>
                    </div>
                    <div class='photo_viewer_wrapper photo_viewer_wrapper_scrollable doc_viewer_wrapper'>
                        <div class='attachment_selector' style="width: 100%;">
                            <div class="attachment_search">
                                <input type="search" maxlength="100" name="q" class="input_with_search_icon" placeholder="${tr("search_by_documents")}">
                            </div>
                            <div id='_attachment_insert'>
                                <div class="docsInsert"></div>
                            </div>
                        </div>
                    </div>
                    <div class="ovk-photo-details"></div>
                </div>
            </div>`),
    })

    msg.getNode().find(".ovk-photo-view").attr('style', 'width: 400px;min-height:90vh;')
    msg.getNode().find('.ovk-diag-body').attr('style', 'height:335px;padding:0px;')
    docs_reciever = new class {
        ctx = "my"
        ctx_id = 0
        stat = {
            page: 0,
            pagesCount: 0,
            count: 0,
        }

        clean() {
            this.stat = {
                page: 0,
                pagesCount: 0,
                count: 0,
            }

            u('#gif_loader, #_attachment_insert #show_more').remove()
            u("#_attachment_insert .docsInsert").html("")
        }

        async page(page = 1, perPage = 10) {
            u('#_attachment_insert').append(`<div id='gif_loader'></div>`)

            const fd = new FormData
            fd.append("context", "list")
            fd.append("hash", window.router.csrf)
            let url = `/docs${source == "club" ? source_arg : ""}?picker=1&p=${page}`
            if(this.query) {
                fd.append("context", "search")
                fd.append("ctx_query", this.query)
            }
            const req = await fetch(url, {
                method: "POST",
                body: fd
            })
            const res = await req.text()
            const dom = new DOMParser
            const pre = dom.parseFromString(res, "text/html") 
            const pagesCount = Number(pre.querySelector("input[name='pagesCount']").value)
            const count = Number(pre.querySelector("input[name='count']").value)
            if(count < 1) {
                u('#_attachment_insert .docsInsert').append(`
                    <div class="information">
                        &nbsp; ${tr("no_documents")}.
                    </div>
                `)
            }
            pre.querySelectorAll("._content").forEach(doc => {
                const res = u(`${doc.outerHTML}`)
                const id  = res.attr("data-attachmentdata")
                
                res.find(".docMainItem").attr("style", "width: 85%;")
                res.append(`
                <div class="attachButton" id='__attach_doc'>
                    ${this.isDocAttached(id) ? tr("detach") : tr("attach")}
                </div>
                `)
                u('#_attachment_insert .docsInsert').append(res)
            })

            this.stat.page = page
            this.stat.pagesCount = pagesCount
            this.stat.count = count
            u('#gif_loader').remove()
            this.showMore()
        }

        async search(query_string) {
            this.clean()
            if(query_string == "")
                this.query = null
            else
                this.query = query_string

            await this.page(1)
        }

        showMore() {
            if(this.stat.page < this.stat.pagesCount) {
                u('#_attachment_insert').append(`
                    <div id="show_more" data-pagesCount="${this.stat.pagesCount}">
                        <span>${tr('show_more')}</span>
                    </div>
                `)
            }
        }

        maxAttachmentsCheck() {
            if(u(form).find(`.upload-item`).length > window.openvk.max_attachments) {
                makeError(tr('too_many_attachments'), 'Red', 10000, 1)
                return true
            }
            return false
        }
        
        attach(dataset, button) {
            if(this.isDocAttached(dataset.attachmentdata)) {
                (form.find(`.upload-item[data-type='doc'][data-id='${dataset.attachmentdata}']`)).remove()
                button.html(tr('attach'))
            } else {
                const _url = dataset.attachmentdata.split("_")
                button.html(tr('detach'))
                form.find('.post-vertical').append(`
                    <div class="vertical-attachment upload-item" draggable="true" data-type='doc' data-id="${dataset.attachmentdata}">
                        <div class='vertical-attachment-content' draggable="false">
                            <div class="docMainItem attachment_doc attachment_note">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 8 10"><polygon points="0 0 0 10 8 10 8 4 4 4 4 0 0 0"/><polygon points="5 0 5 3 8 3 5 0"/></svg>
                                
                                <div class='attachment_note_content'>
                                    <span class="attachment_note_text">${tr("document")}</span>
                                    <span class="attachment_note_name"><a href="/doc${_url[0]}_${_url[1]}?key=${_url[2]}">${ovk_proc_strtr(escapeHtml(dataset.name), 50)}</a></span>
                                </div>
                            </div>
                        </div>
                        <div class='vertical-attachment-remove'>
                            <div id='small_remove_button'></div>
                        </div>
                    </div>
                `)
            }
        }

        isDocAttached(attachmentdata) {
            return (form.find(`.upload-item[data-type='doc'][data-id='${attachmentdata}']`)).length > 0
        }
    }

    msg.getNode().find("#ovk-photo-close").on("click", function(e) {
        msg.close()
    })
    msg.getNode().on("click", "#__attach_doc", async (ev) => {
        if(docs_reciever.maxAttachmentsCheck() == true) {
            return
        }

        const target = u(ev.target).closest('._content')
        const button = target.find('#__attach_doc')
        const dataset = target.nodes[0].dataset
        docs_reciever.attach(dataset, button)
    })
    msg.getNode().on("click", "#show_more", async (ev) => {
        const target = u(ev.target).closest('#show_more')
        target.addClass('lagged')
        await docs_reciever.page(docs_reciever.stat.page + 1)
        target.remove()
    })
    msg.getNode().on("click", "#_doc_picker_go_to_my", async (e) => {
        msg.close()
        await __docAttachment(form, "wall")
    })
    msg.getNode().on("click", "#_doc_picker_upload", async (e) => {
        showDocumentUploadDialog("search", source_arg >= 0 ? NaN : Math.abs(source_arg), () => {
            docs_reciever.clean()
            docs_reciever.page(1)
        })
    })
    msg.getNode().on("change", ".attachment_search input", async (e) => {
        await docs_reciever.search(ovk_proc_strtr(e.target.value, 100))
    })

    await docs_reciever.page(docs_reciever.stat.page + 1)
}
u(document).on('click', '#__documentAttachment', async (e) => {
    const form = u(e.target).closest('form') 
    const targ = u(e.target).closest("#__documentAttachment")
    let entity_source = "user"
    let entity_id = 0
    if(targ.attr('data-club') != null) {
        entity_source = "club"
        entity_id = Number(targ.attr('data-club'))
    }

    await __docAttachment(form, "wall", entity_source, entity_id)
})
