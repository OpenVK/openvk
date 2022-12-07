function escapeXML(text) {
    return $("<span/>").text(text).html();
}

async function pollRetractVote(id) {
    let poll = $(`.poll[data-id=${id}]`);

    poll.addClass("loading");
    try {
        let html = (await API.Polls.unvote(poll.data("id"))).html;
        poll.prop("outerHTML", html);
    } catch(e) {
        MessageBox(tr("error"), "Sorry: " + e.message, ["OK"], [Function.noop]);
    } finally {
        poll.removeClass("loading");
    }
}

async function pollFormSubmit(e, form) {
    e.preventDefault();
    form = $(form);

    let options;
    let isMultiple = form.data("multi");
    let pollId     = form.data("pid");

    let formData = form.serializeArray();
    if(!isMultiple) {
        options = [Number(formData[0].value)];
    } else {
        options = [];
        formData.forEach(function(record) {
            if(!record.name.startsWith("option") || record.value !== "on")
                return;

            options.push(Number(record.name.substr(6)));
        });
    }

    let poll = form.parent();
    poll.addClass("loading");
    try {
        let html = (await API.Polls.vote(pollId, options.join(","))).html;
        poll.prop("outerHTML", html);
    } catch(e) {
        MessageBox(tr("error"), "Sorry: " + e.message, ["OK"], [Function.noop]);
    } finally {
        poll.removeClass("loading");
    }
}

function pollCheckBoxPressed(cb) {
    cb = $(cb);
    let form    = cb.parent().parent().parent().parent();
    let checked = $("input:checked", form);
    if(checked.length >= 1)
        $("input[type=submit]", form).removeAttr("disabled");
    else
        $("input[type=submit]", form).attr("disabled", "disabled");
}

function pollRadioPressed(radio) {
    let form = $(radio).parent().parent().parent().parent();
    form.submit();
}

function initPoll(id) {
    let form = $(`#wall-post-input${id}`).parent();

    let mBody = `
        <div id="poll_editor${id}">
            <input type="text" name="title" placeholder="${tr("poll_title")}" />
            <div class="poll-options" style="margin-top: 10px;"></div>
            <input type="text" name="newOption" placeholder="${tr("poll_add_option")}" style="margin: 5px 0;" />
            <hr/>
            <label><input type="checkbox" name="anon" /> ${tr("poll_anonymous")}</label><br/>
            <label><input type="checkbox" name="multi" /> ${tr("poll_multiple")}</label><br/>
            <label><input type="checkbox" name="locked" /> ${tr("poll_locked")}</label><br/>
            <label>
                <input type="checkbox" name="expires" />
                ${tr("poll_edit_expires")}
                <select name="expires_in" style="width: unset;">
                    ${[...Array(32).keys()].reduce((p, c) => (!p ? '' : p) + ("<option value='" + c + "'>" + c + " " + tr("poll_edit_expires_days") + "</option>\n"))}
                </select>
            </label>
            <div class="nobold" style="margin: 10px 5px 0">${tr("poll_editor_tips")}</div>
        </div>
    `;

    MessageBox(tr("create_poll"), mBody, [tr("attach"), tr("cancel")], [
        function() {
            let dialog = $(this.$dialog().nodes[0]);
            $("input", dialog).unbind();

            let title   = $("input[name=title]", dialog).val();
            let anon    = $("input[name=anon]", dialog).prop("checked") ? "yes" : "no";
            let multi   = $("input[name=multi]", dialog).prop("checked") ? "yes" : "no";
            let lock    = $("input[name=locked]", dialog).prop("checked") ? "yes" : "no";
            let expires = "infinite";
            if($("input[name=expires]", dialog).prop("checked"))
                expires = $("select[name=expires_in]", dialog).val();

            let options = "";
            $(".poll-option", dialog).each(function() {
                if($(this).val().length === 0)
                    return;

                options += `<option>${escapeXML($(this).val())}</option>`;
            });

            let xml = `
                <Poll title="${title}" anonymous="${anon}" multiple="${multi}" locked="${lock}" duration="${expires}">
                    <options>${options}</options>
                </Poll>
            `;
            $("input[name=poll]", form).val(xml);
            $(".post-has-poll", form).show();
        },
        function() {
            $("input", $(this.$dialog().nodes[0])).unbind();
        }
    ]);

    let editor = $(`#poll_editor${id}`);
    $("input[name=newOption]", editor).bind("focus", function() {
        let newOption = $('<input type="text" class="poll-option" style="margin: 5px 0;" />');
        newOption.appendTo($(".poll-options", editor));
        newOption.focus();
        newOption.bind("keydown", function(e) {
            if(e.key === "Enter" && $(this).next().length === 0) {
                $("input[name=newOption]", editor).focus();
                return;
            }

            if($(this).val().length > 0)
                return;

            if(e.key !== "Backspace")
                return;

            if($(this).siblings().length === 0)
                return;

            if($(this).prev().length === 0)
                $(this).next().focus();
            else
                $(this).prev().focus();

            e.preventDefault();
            $(this).unbind("keydown");
            $(this).remove();
        });
    });
}