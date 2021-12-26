function expand_wall_textarea(id) {
    var el = document.getElementById('post-buttons'+id);
    var wi = document.getElementById('wall-post-input'+id);
    el.style.display = "block";
    wi.className = "expanded-textarea";
}

function expand_comment_textarea(id) {
    var el = document.getElementById('commentTextArea'+id);
    var wi = document.getElementById('wall-post-input'+id);
    el.style.display = "block";
    wi.focus();
}

function edit_post(id, wid) {
    var el = document.getElementById('text'+wid+'_'+id);
    var ed = document.getElementById('text_edit'+wid+'_'+id);
    if (el.style.display == "none") {
        el.style.display = "block";
        ed.style.display = "none";
    } else {
        el.style.display = "none";
        ed.style.display = "block";
    }
}


function hidePanel(panel, count = 0)
{
    $(panel).toggleClass("content_title_expanded content_title_unexpanded");
    $(panel).next('div').slideToggle(300);
    if(count != 0){
        if($(panel).hasClass("content_title_expanded"))
            $(panel).html($(panel).html().replaceAll(" ("+count+")", ""));
        else
            $(panel).html($(panel).html() + " ("+count+")");
    }

}

function parseAjaxResponse(responseString) {
    try {
        const response = JSON.parse(responseString);
        if(response.flash)
            NewNotification(response.flash.title, response.flash.message || "", null);

        return response.success || false;
    } catch(error) {
        if(responseString === "Хакеры? Интересно...") {
            location.reload();
            return false;
        } else {
            throw error;
        }
    }
}

document.addEventListener("DOMContentLoaded", function() { //BEGIN

    u("#_photoDelete").on("click", function(e) {
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
        
        return e.preventDefault();
    });

    /* @rem-pai why this func wasn't named as "#_deleteDialog"? It looks universal IMO */

    u("#_noteDelete").on("click", function(e) {
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
        
        return e.preventDefault();
    });

    u("#_pinGroup").on("click", async function(e) {
        e.preventDefault();

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

}); //END ONREADY DECLS

function repostPost(id, hash) {
	uRepostMsgTxt  = tr('your_comment') + ": <textarea id='uRepostMsgInput_"+id+"'></textarea><br/><br/>";
	
	MessageBox(tr('share'), uRepostMsgTxt, [tr('send'), tr('cancel')], [
		(function() {
			text = document.querySelector("#uRepostMsgInput_"+id).value;
			hash = encodeURIComponent(hash);
			xhr = new XMLHttpRequest();
			xhr.open("POST", "/wall"+id+"/repost?hash="+hash, true);
			xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
			xhr.onload = (function() {
                if(xhr.responseText.indexOf("wall_owner") === -1)
					MessageBox(tr('error'), tr('error_repost_fail'), tr('ok'), [Function.noop]);
				else {
					let jsonR = JSON.parse(xhr.responseText);
                    NewNotification(tr('information_-1'), tr('shared_succ'), null, () => {window.location.href = "/wall" + jsonR.wall_owner});
				}
			});
			xhr.send('text=' + encodeURI(text));
		}),
		Function.noop
	]);
}

function setClubAdminComment(clubId, adminId, hash) {
    MessageBox("Изменить комментарий к администратору", `
        <form action="/club${clubId}/setAdmin.jsp" method="post" id="uClubAdminCommentForm_${clubId}_${adminId}">
            <input type="hidden" name="user" value="${adminId}">
            <input type="hidden" name="hash" value="${hash}">
            <input type="hidden" name="removeComment" id="uClubAdminCommentRemoveCommentInput_${clubId}_${adminId}" value="0">
            <textarea name="comment" id="uClubAdminCommentTextArea_${clubId}_${adminId}"></textarea><br><br>
        </form>
    `, [tr('edit_action'), tr('cancel')], [
        () => {
            if (document.querySelector(`#uClubAdminCommentTextArea_${clubId}_${adminId}`).value === "") {
                document.querySelector(`#uClubAdminCommentRemoveCommentInput_${clubId}_${adminId}`).value = "1";
            }

            document.querySelector(`#uClubAdminCommentForm_${clubId}_${adminId}`).submit();
        },
        Function.noop
    ]);
}

function showCoinsTransferDialog(coinsCount, hash) {
    MessageBox(tr("transfer_poins"), `
        <div class="messagebox-content-header">
            ${tr("points_transfer_dialog_header_1")}
            ${tr("points_transfer_dialog_header_2")} <b>${tr("points_amount", coinsCount)}</b>
        </div>
        <form action="/coins_transfer" method="post" id="coins_transfer_form" style="margin-top: 30px">
            <table cellspacing="7" cellpadding="0" border="0" align="center">
                <tbody>
                    <tr>
                        <td width="120" valign="top">
                            <span class="nobold">${tr("receiver_address")}:</span>
                        </td>
                        <td>
                            <input type="text" name="receiver" style="width: 100%;" />
                        </td>
                    </tr>
                    <tr>
                        <td width="120" valign="top">
                            <span class="nobold">${tr("coins_count")}:</span>
                        </td>
                        <td>
                            <input type="text" name="value" style="width: 100%;" />
                        </td>
                    </tr>
                    <tr>
                        <td width="120" valign="top">
                            <span class="nobold">${tr("message")}:</span>
                        </td>
                        <td>
                            <textarea name="message" style="width: 100%;"></textarea>
                        </td>
                    </tr>
                </tbody>
            </table>
            <input type="hidden" name="hash" value="${hash}" />
        </form>
    `, [tr("transfer_poins_button"), tr("cancel")], [
        () => {
            document.querySelector("#coins_transfer_form").submit();
        },
        Function.noop
    ]);
}

function chunkSubstr(string, size) {
    const numChunks = Math.ceil(string.length / size);
    const chunks = new Array(numChunks);

    for (let i = 0, o = 0; i < numChunks; ++i, o += size) {
        chunks[i] = string.substr(o, size);
    }

    return chunks;
}

function autoTab(original, next, previous) {
    if(original.getAttribute && original.value.length == original.getAttribute("maxlength") && next !== undefined)
        next.focus();
    else if(original.value.length == 0 && previous !== undefined)
        previous.focus();
}

function showSupportFastAnswerDialog(answers) {
    let html = "";
    for(const [index, answer] of Object.entries(answers)) {
        html += `
            <div class="hover-box" onclick="supportFastAnswerDialogOnClick(fastAnswers[${index}])">
                ${answer.replace(/\n/g, "<br />")}
            </div>
        `;
    }

    MessageBox(tr("fast_answers"), html, [tr("close")], [
        Function.noop
    ]);
}

function supportFastAnswerDialogOnClick(answer) {
    u("body").removeClass("dimmed");
    u(".ovk-diag-cont").remove();

    const answerInput = document.querySelector("#answer_text");
    answerInput.value = answer;
    answerInput.focus();
}
