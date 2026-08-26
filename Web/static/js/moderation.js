function reportSomething(item_id, item_type, get_param_item_type = null) {
    if (get_param_item_type == null) {
        get_param_item_type = item_type;
    }

    let uReportMsgTxt = `
        ${tr("going_to_report_" + item_type)}<br/>
        ${tr("report_question_text")}<br/><br/>
        <b>${tr("report_reason")}: <input type='text' id='uReportMsgInput' placeholder='${tr("reason")}' /></b>
    `;

    MessageBox(tr("report_question"), uReportMsgTxt, [tr("confirm_m"), tr("cancel")], [
        (function() {
            res = document.querySelector("#uReportMsgInput").value;
            xhr = new XMLHttpRequest();
            xhr.open("GET", "/report/" + item_id + "?reason=" + res + "&type=" + get_param_item_type, true);
            xhr.onload = (function() {
            if(xhr.responseText.indexOf("reason") === -1)
                MessageBox(tr("error"), tr("error_sending_report"), ["OK"], [Function.noop]);
            else
                MessageBox(tr("action_successfully"), tr("will_be_watched"), ["OK"], [Function.noop]);
            });
                xhr.send(null);
            }),
        Function.noop
    ], false, "reportingSmth");
}

// семь одинаковых серий
function reportPhoto(photo_id) {
    reportSomething(photo_id, "photo");
}

function reportVideo(video_id) {
    reportSomething(video_id, "video");
}

function reportUser(user_id) {
    reportSomething(user_id, "user");
}

function reportComment(comment_id) {
    reportSomething(comment_id, "comment");
}

function reportApp(id) {
    reportSomething(id, "app");
}

function reportClub(club_id) {
    reportSomething(club_id, "club", "group");
}

function confirm_ban(event, ignore = false, ban_owner = false) {
    if (!event.isTrusted) {
        return;
    }

    event.preventDefault();

    const orig_reason = u("#reportReason").last().textContent;
    const cmsg = new CMessageBox({
        title: tr("confirmation"),
        body: `
        <p>${tr("confirm_report_submission")}</p>`+
        (ignore == false ? `
            <p>${tr("confirm_report_submission_2")}</p>
            <textarea id="reasons">${orig_reason ?? ""}</textarea>
        ` : "") +
        (ignore == false && ban_owner == true ? `
            <p>${tr("confirm_report_submission_3")}</p>
            <textarea id="owner_reasons"></textarea>
        ` : ""),
        close_on_buttons: false,
        buttons: [tr("ok"), tr("cancel")],
        callbacks: [() => {
            let ban_reason = "";
            if (ignore == false) {
                ban_reason = cmsg.getNode().find("#reasons").last().value;
                if (!ban_reason || ban_reason == "" || ban_reason.length == 0) {
                    console.error("empty reason");
                } else {
                    event.target.closest("form").insertAdjacentHTML("beforeend", `<input type="hidden" name="reason" value="${ban_reason}"/>`);
                }
            }
            if (ban_owner == true) {
                ban_reason2 = cmsg.getNode().find("#owner_reasons").last().value;
                if (!ban_reason2 || ban_reason2 == "" || ban_reason2.length == 0) {
                    console.error("empty reason");
                } else {
                    event.target.closest("form").insertAdjacentHTML("beforeend", `<input type="hidden" name="reason_owner" value="${ban_reason2}"/>`);
                }
            }

            cmsg.close();
            event.target.click();
        }, () => {
            cmsg.close();
        }],
    })
}
