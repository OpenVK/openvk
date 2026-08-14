window.OpenVKPages = (function () {
    var bound = false;
    var previewLoading = false;

    function getTextarea() {
        return document.getElementById("page_source");
    }

    function previewLabel(editing) {
        return editing ? tr("page_tab_edit") : tr("page_preview");
    }

    function setPreviewButtons(editing) {
        var btn = document.getElementById("page_preview_btn");
        if (btn) {
            btn.textContent = previewLabel(editing);
        }
        var icon = document.querySelector("#page_toolbar a[data-action='preview']");
        if (icon) {
            icon.classList.toggle("wysiwyg_active", editing);
        }
    }

    function getTitleInput() {
        return document.querySelector("#page_edit_form input[name='title'], #noteFactory input[name='name'], #page_edit_form input[name='name']");
    }

    function shrinkSource(force) {
        var ta = getTextarea();
        if (!ta) {
            return;
        }
        if (!force && ta.dataset.pagesLocked === "1") {
            return;
        }

        ta.dataset.pagesLocked = "1";
        ta.classList.add("page_source_compact");
        ta.style.height = "380px";
    }

    function setupSourceArea() {
        var ta = getTextarea();
        if (!ta) {
            return;
        }

        if (ta.value.length > 0) {
            shrinkSource(true);
            return;
        }

        ta.classList.remove("page_source_compact");
        ta.style.height = "";
        ta.dataset.pagesLocked = "0";

        if (ta.dataset.pagesShrinkBound === "1") {
            return;
        }
        ta.dataset.pagesShrinkBound = "1";
        ta.addEventListener("input", function onFirstInput() {
            shrinkSource(true);
            ta.removeEventListener("input", onFirstInput);
        });
    }

    function insertMarkdown(pattern) {
        var ta = getTextarea();
        if (!ta || !pattern) {
            return;
        }

        var start = ta.selectionStart;
        var end = ta.selectionEnd;
        var value = ta.value;
        var selected = value.substring(start, end);
        var parts = pattern.split("|");
        var before = parts[0] || "";
        var after = parts.length > 1 ? parts.slice(1).join("|") : "";
        var insertion;

        if (pattern.indexOf("|") === -1) {
            insertion = before + selected;
            ta.value = value.substring(0, start) + insertion + value.substring(end);
            ta.focus();
            ta.selectionStart = ta.selectionEnd = start + insertion.length;
            shrinkSource(true);
            return;
        }

        insertion = before + (selected || "") + after;
        ta.value = value.substring(0, start) + insertion + value.substring(end);
        ta.focus();
        if (selected) {
            ta.selectionStart = start;
            ta.selectionEnd = start + insertion.length;
        } else {
            ta.selectionStart = ta.selectionEnd = start + before.length;
        }
        shrinkSource(true);
    }

    function insertPhoto() {
        if (typeof CMessageBox === "undefined" || !window.OVKAPI || !window.OVKAPI.call) {
            insertMarkdown("![|](url)");
            return;
        }

        var preview = document.getElementById("page_preview");
        var club = Number((preview && preview.getAttribute("data-club")) || 0);
        var albumOwner = club ? -Math.abs(club) : window.openvk.current_id;
        var photosPerPage = 23;

        var msg = new CMessageBox({
            title: tr("select_photo"),
            body:
                "<div class='attachment_selector'>" +
                    "<div class='topGrayBlock display_flex_row'>" +
                        "<select id='albumSelect'>" +
                            "<option value='0'>" + tr("all_photos") + "</option>" +
                        "</select>" +
                    "</div>" +
                    "<div id='attachment_insert'>" +
                        "<div id='attachment_insert_count'><h4>" + tr("is_x_photos", 0) + "</h4></div>" +
                        "<div class='photosList album-flex'></div>" +
                    "</div>" +
                "</div>",
            buttons: [tr("close")],
            callbacks: [Function.noop],
            unique_name: "page_photo_picker"
        });

        msg.getNode().attr("style", "width: 630px;");
        msg.getNode().find(".ovk-diag-body").attr("style", "height:335px;padding:0px;");

        async function receivePhotos(page, album) {
            album = album || 0;
            u("#gif_loader").remove();
            u("#attachment_insert").append("<div id='gif_loader'></div>");
            var insertPlace = u("#attachment_insert .photosList");
            var photos;

            try {
                if (album == 0) {
                    photos = await window.OVKAPI.call("photos.getAll", {
                        owner_id: window.openvk.current_id,
                        photo_sizes: 1,
                        count: photosPerPage,
                        offset: page * photosPerPage
                    });
                } else {
                    photos = await window.OVKAPI.call("photos.get", {
                        owner_id: albumOwner,
                        album_id: album,
                        photo_sizes: 1,
                        count: photosPerPage,
                        offset: page * photosPerPage
                    });
                }
            } catch (e) {
                u("#attachment_insert_count h4").html(tr("is_x_photos", -1));
                u("#gif_loader").remove();
                insertPlace.html("Invalid album");
                return;
            }

            u("#attachment_insert_count h4").html(tr("is_x_photos", photos.count));
            u("#gif_loader").remove();
            var pagesCount = Math.ceil(Number(photos.count) / photosPerPage);
            (photos.items || []).forEach(function (photo) {
                var ownerId = Number(photo.owner_id);
                var photoId = Number(photo.id);
                if (!Number.isFinite(ownerId) || !Number.isFinite(photoId)) {
                    return;
                }

                insertPlace.append(
                    "<a class='album-photo' data-attachmentdata='" + ownerId + "_" + photoId + "' href='/photo" + ownerId + "_" + photoId + "'>" +
                        "<img class='album-photo--image' src='" + escapeHtml(photo.photo_130 || "") + "' alt=''>" +
                    "</a>"
                );
            });

            if (page < pagesCount - 1) {
                insertPlace.append(
                    "<div id='show_more' data-pagesCount='" + pagesCount + "' data-page='" + (page + 1) + "'>" +
                        "<span>" + tr("show_more") + "</span>" +
                    "</div>"
                );
            }
        }

        u(".ovk-diag-body .attachment_selector").on("change", ".topGrayBlock #albumSelect", function (ev) {
            u("#attachment_insert .photosList").html("");
            receivePhotos(0, ev.target.value);
        });

        u(".ovk-diag-body .attachment_selector").on("click", "#show_more", async function (ev) {
            var target = u(ev.target).closest("#show_more");
            target.addClass("lagged");
            await receivePhotos(Number(target.nodes[0].dataset.page), u(".topGrayBlock #albumSelect").nodes[0].value);
            target.remove();
        });

        u(".ovk-diag-body .attachment_selector").on("click", ".album-photo", function (ev) {
            ev.preventDefault();
            ev.stopPropagation();
            var id = u(ev.target).closest(".album-photo").nodes[0].dataset.attachmentdata;
            insertMarkdown("![|](/photo" + id + ")");
            msg.close();
        });

        receivePhotos(0);
        window.OVKAPI.call("photos.getAlbums", { owner_id: albumOwner }).then(function (albums) {
            (albums.items || []).forEach(function (item) {
                u(".ovk-diag-body #albumSelect").append(
                    "<option value='" + item.id + "'>" + ovk_proc_strtr(escapeHtml(item.title), 20) + "</option>"
                );
            });
        }).catch(Function.noop);
    }

    function wrapAlign(align) {
        var ta = getTextarea();
        if (!ta) {
            return;
        }
        var start = ta.selectionStart;
        var end = ta.selectionEnd;
        var selected = ta.value.substring(start, end) || "text";
        var wrapped = '<div align="' + align + '">\n\n' + selected + '\n\n</div>';
        ta.value = ta.value.substring(0, start) + wrapped + ta.value.substring(end);
        ta.focus();
        shrinkSource(true);
    }

    function insertTable() {
        var ta = getTextarea();
        if (!ta) {
            return;
        }

        var start = ta.selectionStart;
        var end = ta.selectionEnd;
        var snippet = "| Header | Header |\n| --- | --- |\n| Cell | Cell |\n";
        if (start > 0 && ta.value.charAt(start - 1) !== "\n") {
            snippet = "\n" + snippet;
        }

        ta.value = ta.value.substring(0, start) + snippet + ta.value.substring(end);
        ta.focus();
        ta.selectionStart = ta.selectionEnd = start + snippet.length;
        shrinkSource(true);
    }

    function currentFormat(preview) {
        var checked = document.querySelector("input[name='format']:checked");
        if (checked) {
            return checked.value;
        }
        var hidden = document.querySelector("input[name='format']");
        if (hidden && hidden.type === "hidden") {
            return hidden.value;
        }
        return (preview && preview.getAttribute("data-format")) || "1";
    }

    function currentSource(format) {
        if (format === "0" && window._editor) {
            return window._editor.getValue();
        }
        var ta = getTextarea();
        return ta ? ta.value : "";
    }

    function showEditorSurfaces() {
        var preview = document.getElementById("page_preview");
        var format = currentFormat(preview);
        var ta = getTextarea();
        var md = document.getElementById("note_md_editor");
        var html = document.getElementById("note_html_editor");
        var monaco = document.getElementById("editor");
        if (format === "0") {
            if (html) {
                html.style.display = "block";
            }
            if (monaco) {
                monaco.style.display = "block";
            }
            if (md) {
                md.style.display = "none";
            }
        } else {
            if (md) {
                md.style.display = "block";
            }
            if (ta) {
                ta.style.display = "block";
            }
            if (html) {
                html.style.display = "none";
            }
        }
    }

    function hideEditorSurfaces() {
        var ta = getTextarea();
        var monaco = document.getElementById("editor");
        var html = document.getElementById("note_html_editor");
        if (ta) {
            ta.style.display = "none";
        }
        if (monaco) {
            monaco.style.display = "none";
        }
        if (html) {
            html.style.display = "none";
        }
    }

    function exitPreview(preview) {
        preview.style.display = "none";
        preview.dataset.previewing = "0";
        showEditorSurfaces();
        setPreviewButtons(false);
    }

    function togglePreview() {
        var preview = document.getElementById("page_preview");
        if (!preview || previewLoading) {
            return;
        }

        if (preview.dataset.previewing === "1") {
            exitPreview(preview);
            return;
        }

        var format = currentFormat(preview);
        var source = currentSource(format);
        var club = preview.getAttribute("data-club") || "";
        var csrf = document.querySelector('meta[name="csrf"]');
        var hash = csrf ? csrf.getAttribute("value") : "";
        var body = "source=" + encodeURIComponent(source) +
            "&html=" + encodeURIComponent(source) +
            "&format=" + encodeURIComponent(format) +
            "&club=" + encodeURIComponent(club) +
            "&hash=" + encodeURIComponent(hash);

        previewLoading = true;
        fetch("/notes/preview", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: body,
            credentials: "same-origin"
        }).then(function (r) {
            return r.text().then(function (html) {
                return { ok: r.ok, html: html };
            });
        }).then(function (res) {
            if (!res.ok) {
                exitPreview(preview);
                return;
            }
            preview.innerHTML = res.html;
            preview.style.display = "block";
            preview.dataset.previewing = "1";
            hideEditorSurfaces();
            setPreviewButtons(true);
        }).catch(function () {
            exitPreview(preview);
        }).then(function () {
            previewLoading = false;
        });
    }

    function accessRadio(name, value, current, label) {
        var checked = String(current) === String(value) ? " checked" : "";
        return "<label style=\"display:block;margin:4px 0;\">" +
            "<input type=\"radio\" name=\"" + name + "\" value=\"" + value + "\"" + checked + " /> " +
            label + "</label>";
    }

    function buildAccessBody(viewVal, editVal, commentVal) {
        return "<div class=\"page_access_section\">" +
            "<b>" + tr("page_who_can_view") + "</b>" +
            accessRadio("mb_view_access", 0, viewVal, tr("page_access_everyone")) +
            accessRadio("mb_view_access", 1, viewVal, tr("page_access_members")) +
            accessRadio("mb_view_access", 2, viewVal, tr("page_access_admins")) +
            "</div>" +
            "<div class=\"page_access_section\" style=\"margin-top:12px;\">" +
            "<b>" + tr("page_who_can_edit") + "</b>" +
            accessRadio("mb_edit_access", 0, editVal, tr("page_access_everyone")) +
            accessRadio("mb_edit_access", 1, editVal, tr("page_access_members")) +
            accessRadio("mb_edit_access", 2, editVal, tr("page_access_admins")) +
            "</div>" +
            "<div class=\"page_access_section\" style=\"margin-top:12px;\">" +
            "<b>" + tr("page_who_can_comment") + "</b>" +
            accessRadio("mb_comment_access", 0, commentVal, tr("page_access_everyone")) +
            accessRadio("mb_comment_access", 1, commentVal, tr("page_access_members")) +
            accessRadio("mb_comment_access", 2, commentVal, tr("page_access_admins")) +
            "</div>";
    }

    function readAccessChoice(name) {
        var el = document.querySelector('.ovk-diag-body input[name="' + name + '"]:checked');
        return el ? el.value : null;
    }

    function showAccessModal() {
        var btn = document.getElementById("page_access_btn");
        if (!btn || typeof CMessageBox === "undefined") {
            return;
        }

        var viewHidden = document.getElementById("page_view_access");
        var editHidden = document.getElementById("page_edit_access");
        var commentHidden = document.getElementById("page_comment_access");
        var isCreate = !!(viewHidden && editHidden);
        var viewVal = isCreate ? viewHidden.value : (btn.getAttribute("data-view") || "0");
        var editVal = isCreate ? editHidden.value : (btn.getAttribute("data-edit") || "2");
        var commentVal = isCreate
            ? (commentHidden ? commentHidden.value : "0")
            : (btn.getAttribute("data-comment") || "0");
        var accessUrl = btn.getAttribute("data-access-url");

        var msg = new CMessageBox({
            title: tr("page_access_title"),
            body: buildAccessBody(viewVal, editVal, commentVal),
            buttons: [tr("save_changes"), tr("cancel")],
            close_on_buttons: false,
            unique_name: "page_access_dialog",
            callbacks: [
                function () {
                    var view = readAccessChoice("mb_view_access");
                    var edit = readAccessChoice("mb_edit_access");
                    var comment = readAccessChoice("mb_comment_access");
                    if (view === null || edit === null || comment === null) {
                        return;
                    }

                    if (isCreate) {
                        viewHidden.value = view;
                        editHidden.value = edit;
                        if (commentHidden) {
                            commentHidden.value = comment;
                        }
                        msg.close();
                        return;
                    }

                    if (!accessUrl) {
                        msg.close();
                        return;
                    }

                    var form = document.createElement("form");
                    form.method = "POST";
                    form.action = accessUrl;
                    form.style.display = "none";

                    function addField(name, value) {
                        var input = document.createElement("input");
                        input.type = "hidden";
                        input.name = name;
                        input.value = value;
                        form.appendChild(input);
                    }

                    var csrf = document.querySelector('meta[name="csrf"]');
                    addField("hash", csrf ? csrf.getAttribute("value") : "");
                    addField("view_access", view);
                    addField("edit_access", edit);
                    addField("comment_access", comment);
                    document.body.appendChild(form);
                    form.submit();
                },
                function () {
                    msg.close();
                }
            ]
        });

        if (msg && msg.getNode) {
            msg.getNode().find(".ovk-diag-body").attr("style", "padding:15px;");
            msg.getNode().attr("style", "width:420px;");
        }
    }

    function bindTitleValidation(form) {
        if (!form || form.dataset.titleValidateBound === "1") {
            return;
        }
        form.dataset.titleValidateBound = "1";
        form.addEventListener("submit", function (e) {
            var formatEl = document.querySelector("input[name='format']:checked") || document.querySelector("input[name='format']");
            if (formatEl && formatEl.value === "0" && window._editor) {
                var html = document.querySelector("textarea[name='html']");
                if (html) {
                    html.value = window._editor.getValue();
                }
            }

            var title = getTitleInput();
            if (!title) {
                return;
            }
            title.value = title.value.trim();
            if (title.value === "") {
                e.preventDefault();
                title.focus();
                fastError(tr("page_no_title"));
            }
        });
    }

    function init() {
        setupSourceArea();
        bindTitleValidation(document.getElementById("page_edit_form"));
        bindTitleValidation(document.getElementById("noteFactory"));

        if (bound) {
            return;
        }
        bound = true;

        document.addEventListener("click", function (e) {
            var target = e.target;
            if (!target || !target.closest) {
                return;
            }

            if (target.closest("#page_preview_btn")) {
                e.preventDefault();
                togglePreview();
                return;
            }

            if (target.closest("#page_access_btn")) {
                e.preventDefault();
                e.stopPropagation();
                showAccessModal();
                return;
            }

            var toolbarLink = target.closest("#page_toolbar a");
            if (toolbarLink) {
                e.preventDefault();
                if (toolbarLink.getAttribute("data-action") === "preview") {
                    togglePreview();
                    return;
                }
                if (toolbarLink.getAttribute("data-action") === "photo") {
                    insertPhoto();
                    return;
                }
                if (toolbarLink.getAttribute("data-action") === "table") {
                    insertTable();
                    return;
                }
                var align = toolbarLink.getAttribute("data-align");
                if (align) {
                    wrapAlign(align);
                    return;
                }
                insertMarkdown(toolbarLink.getAttribute("data-md") || "");
            }
        });
    }

    return { init: init, showAccessModal: showAccessModal, shrinkSource: shrinkSource };
})();

window.OpenVKPages.init();
