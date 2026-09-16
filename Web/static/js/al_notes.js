window.OpenVKNotesToolbar = (function () {
    var SNIPPETS = {
        bold: ["<b>", "</b>", "text"],
        italic: ["<i>", "</i>", "text"],
        underline: ['<span class="underline">', "</span>", "text"],
        left: ['<div style="text-align: left">\n', "\n</div>", "text"],
        center: ['<div style="text-align: center">\n', "\n</div>", "text"],
        right: ['<div style="text-align: right">\n', "\n</div>", "text"],
        list: ["<ul>\n<li>", "</li>\n</ul>", "item"],
        h3: ["<h3>", "</h3>", "Heading"],
        h4: ["<h4>", "</h4>", "Heading"],
        h5: ["<h5>", "</h5>", "Heading"],
        quote: ["<blockquote>", "</blockquote>", "quote"],
        link: ['<a href="url">', "</a>", "text"],
        image: ['<img src="url" alt="', '" />', "alt"],
        table: [
            "\n<table>\n<thead>\n<tr><th>Header</th><th>Header</th></tr>\n</thead>\n<tbody>\n<tr><td>",
            "</td><td>Cell</td></tr>\n</tbody>\n</table>\n",
            "Cell"
        ]
    };

    function getEditor() {
        return window._editor || null;
    }

    function insertSnippet(before, after, emptyFallback) {
        var editor = getEditor();
        if (!editor) {
            return;
        }

        var selection = editor.getSelection();
        var model = editor.getModel();
        if (!selection || !model) {
            return;
        }

        var selected = model.getValueInRange(selection);
        var inner = selected.length ? selected : (emptyFallback || "");
        var text = before + inner + (after || "");

        editor.executeEdits("notes-toolbar", [{
            range: selection,
            text: text,
            forceMoveMarkers: true
        }]);
        editor.focus();
    }

    function handleClick(e) {
        var link = e.target.closest("#note_toolbar a[data-action]");
        if (!link) {
            return;
        }

        e.preventDefault();
        var action = link.getAttribute("data-action");
        var snippet = SNIPPETS[action];
        if (!snippet) {
            return;
        }

        insertSnippet(snippet[0], snippet[1], snippet[2] || "");
    }

    function init() {
        if (document.body.dataset.notesToolbarBound === "1") {
            return;
        }
        document.body.dataset.notesToolbarBound = "1";
        document.addEventListener("click", handleClick);
    }

    return { init: init, insertSnippet: insertSnippet };
})();

window.OpenVKNotesToolbar.init();
