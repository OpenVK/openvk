window.OpenVKPages = (function () {
    var bound = false;

    function getTextarea() {
        return document.getElementById("page_source");
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

        // Always re-bind for AJAX navigations (node is new each time).
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

    function togglePreview() {
        var ta = getTextarea();
        var preview = document.getElementById("page_preview");
        if (!ta || !preview) {
            return;
        }

        if (preview.style.display && preview.style.display !== "none") {
            preview.style.display = "none";
            ta.style.display = "block";
            return;
        }

        var club = preview.getAttribute("data-club");
        var csrf = document.querySelector('meta[name="csrf"]');
        var hash = csrf ? csrf.getAttribute("value") : "";
        var body = "club=" + encodeURIComponent(club) +
            "&source=" + encodeURIComponent(ta.value) +
            "&hash=" + encodeURIComponent(hash);

        fetch("/pages/preview", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: body,
            credentials: "same-origin"
        }).then(function (r) {
            return r.text();
        }).then(function (html) {
            preview.innerHTML = html;
            preview.style.display = "block";
            ta.style.display = "none";
        }).catch(function () {
            preview.innerHTML = "<p>Preview error</p>";
            preview.style.display = "block";
            ta.style.display = "none";
        });
    }

    function accessRadio(name, value, current, label) {
        var checked = String(current) === String(value) ? " checked" : "";
        return "<label style=\"display:block;margin:4px 0;\">" +
            "<input type=\"radio\" name=\"" + name + "\" value=\"" + value + "\"" + checked + " /> " +
            label + "</label>";
    }

    function buildAccessBody(viewVal, editVal) {
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
        var isCreate = !!(viewHidden && editHidden);
        var viewVal = isCreate ? viewHidden.value : (btn.getAttribute("data-view") || "0");
        var editVal = isCreate ? editHidden.value : (btn.getAttribute("data-edit") || "2");
        var accessUrl = btn.getAttribute("data-access-url");

        var msg = new CMessageBox({
            title: tr("page_access_title"),
            body: buildAccessBody(viewVal, editVal),
            buttons: [tr("save_changes"), tr("cancel")],
            close_on_buttons: false,
            unique_name: "page_access_dialog",
            callbacks: [
                function () {
                    var view = readAccessChoice("mb_view_access");
                    var edit = readAccessChoice("mb_edit_access");
                    if (view === null || edit === null) {
                        return;
                    }

                    if (isCreate) {
                        viewHidden.value = view;
                        editHidden.value = edit;
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

    function init() {
        setupSourceArea();

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
