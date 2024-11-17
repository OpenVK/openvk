﻿if(typeof u == 'undefined') {
    console.error('!!! You forgot to install NPM packages !!!')
}

function expand_comment_textarea(id) {
    var el = document.getElementById('commentTextArea'+id);
    var wi = document.getElementById('wall-post-input'+id);
    el.style.display = "block";
    wi.focus();
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

    u(document).handle("submit", "#_submitUserSubscriptionAction", async function(e) {
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
}); //END ONREADY DECLS

function setClubAdminComment(clubId, adminId, hash) {
    MessageBox("Изменить комментарий к администратору", `
        <form action="/club${clubId}/setAdmin" method="post" id="uClubAdminCommentForm_${clubId}_${adminId}">
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

function showProfileDeactivateDialog(hash) {
    MessageBox(tr("profile_deactivate"), `
        <div class="messagebox-content-header">
            ${tr("profile_deactivate_header")}
        </div>
        <form action="/settings/deactivate" method="post" id="profile_deactivate_dialog" style="margin-top: 30px">
            <h4>${tr("profile_deactivate_reason_header")}</h4>
            <table>
                <tbody>
                    <tr>
                        <td><input type="radio" name="deactivate_type" id="deactivate_r_1" data-text="${tr("profile_deactivate_reason_1_text")}"></td>
                        <td><label for="deactivate_r_1">${tr("profile_deactivate_reason_1")}</label></td>
                    </tr>
                    <tr>
                        <td><input type="radio" name="deactivate_type" id="deactivate_r_2" data-text="${tr("profile_deactivate_reason_2_text")}"></td>
                        <td><label for="deactivate_r_2">${tr("profile_deactivate_reason_2")}</label></td>
                    </tr>
                    <tr>
                        <td><input type="radio" name="deactivate_type" id="deactivate_r_3" data-text="${tr("profile_deactivate_reason_3_text")}"></td>
                        <td><label for="deactivate_r_3">${tr("profile_deactivate_reason_3")}</label></td>
                    </tr>
                    <tr>
                        <td><input type="radio" name="deactivate_type" id="deactivate_r_4" data-text="${tr("profile_deactivate_reason_4_text")}"></td>
                        <td><label for="deactivate_r_4">${tr("profile_deactivate_reason_4")}</label></td>
                    </tr>
                    <tr>
                        <td><input type="radio" name="deactivate_type" id="deactivate_r_5" data-text="${tr("profile_deactivate_reason_5_text")}"></td>
                        <td><label for="deactivate_r_5">${tr("profile_deactivate_reason_5")}</label></td>
                    </tr>
                    <tr>
                        <td><input type="radio" name="deactivate_type" id="deactivate_r_6" data-text=""></td>
                        <td><label for="deactivate_r_6">${tr("profile_deactivate_reason_6")}</label></td>
                    </tr>
                </tbody>
            </table>
            <textarea name="deactivate_reason" id="deactivate_reason" placeholder="${tr("gift_your_message")}"></textarea><br><br>
            <input type="checkbox" name="deactivate_share" id="deactivate_share" checked>
            <label for="deactivate_share">${tr("share_with_friends")}</label>
            <input type="hidden" name="hash" value="${hash}" />
        </form>
    `, [tr("profile_deactivate_button"), tr("cancel")], [
        () => {
            $("#profile_deactivate_dialog").submit();
        },
        Function.noop
    ]);

    $('[id^="deactivate_r_"]').on("click", function () {
        $('#deactivate_reason').val($(this).data("text"));
    });
}

function showIncreaseRatingDialog(coinsCount, userUrl, hash) {
    MessageBox(tr("increase_rating"), `
        <div class="messagebox-content-header">
            ${tr("you_have_unused_votes", coinsCount)} <br />
            <a href="/settings?act=finance.top-up">${tr("apply_voucher")} &raquo;</a>
        </div>
        <form action="/increase_social_credits" method="post" id="increase_rating_form" style="margin-top: 30px">
            <table cellspacing="7" cellpadding="0" border="0" align="center">
                <tbody>
                    <tr>
                        <td width="120" valign="top">
                            <span class="nobold">${tr("to_whom")}:</span>
                        </td>
                        <td>
                            <input type="text" name="receiver" style="width: 100%;" value="${userUrl}" />
                        </td>
                    </tr>
                    <tr>
                        <td width="120" valign="top">
                            <span class="nobold">${tr("increase_by")}:</span>
                        </td>
                        <td>
                            <input id="value_input" type="text" name="value" style="width: 100%;" />
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
                    <tr>
                        <td colspan="2">
                            <div class="menu_divider"></div>
                        </td>
                    </tr>
                    <tr>
                        <td width="120" valign="top">
                            <span class="nobold">${tr("price")}:</span>
                        </td>
                        <td>
                            <span id="rating_price">${tr("points_amount", 0)}</span> <small class="nobold" style="float: right;">(1% = ${tr("points_amount_one", 1)})</small>
                        </td>
                    </tr>
                </tbody>
            </table>
            <input type="hidden" name="hash" value="${hash}" />
        </form>
    `, [tr("increase_rating_button"), tr("cancel")], [
        () => {
            document.querySelector("#increase_rating_form").submit();
        },
        Function.noop
    ]);

    document.querySelector("#value_input").oninput = function () {
        let value = Number(this.value);
        value = isNaN(value) ? "?" : ovk_proc_strtr(String(value), 7);
        if(!value.endsWith("…") && value != "?")
            value = Number(value);

        if(typeof value === "number")
            document.querySelector("#rating_price").innerHTML = tr("points_amount", value);
        else
            document.querySelector("#rating_price").innerHTML = value + " " + tr("points_amount_other").replace("$1 ", "");
    };
}

$(document).on("scroll", () => {
    if($(document).scrollTop() > $(".sidebar").height() + 50) {
        $(".floating_sidebar")[0].classList.add("show");
    } else if($(".floating_sidebar")[0].classList.contains("show")) {
        $(".floating_sidebar")[0].classList.remove("show");
        $(".floating_sidebar")[0].classList.add("hide_anim");
        setTimeout(() => {
            $(".floating_sidebar")[0].classList.remove("hide_anim");
        }, 250);
    }
})