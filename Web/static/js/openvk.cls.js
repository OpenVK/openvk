if(typeof u == 'undefined') {
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

let lastScrollTop = 0;
$(document).on("scroll", () => {
    const currentScrollTop = $(document).scrollTop();
    const navigation = $(".navigation");

    const scrollNavigation = (top) => {
        navigation.css("top", top + "px");
        navigation[0].classList.add("navigation-fixed");
    }

    const hideNavigationOutbound = (height) => {
        navigation.css("top", height + "px");
        navigation[0].classList.remove("navigation-fixed");
    }

    const removeFixedNavigation = () => {
        navigation.css("top", "");
        navigation[0].classList.remove("navigation-fixed");
    }

    let top = parseInt(navigation.css("top"), 10);

    if(currentScrollTop > $(".sidebar").height() + 50) {
        if(currentScrollTop < lastScrollTop) {
            if(top !== 5) {
                scrollNavigation(Math.min(5, (top + (lastScrollTop - currentScrollTop))));
            }

            $(".floating_sidebar")[0].classList.remove("show");
            $(".floating_sidebar")[0].classList.add("hide_anim");
            setTimeout(() => {
                $(".floating_sidebar")[0].classList.remove("hide_anim");
            }, 250);
        } else {
            let h = -navigation.height();
            if(top <= h || top === 0) {
                hideNavigationOutbound(h);
                $(".floating_sidebar")[0].classList.add("show");
            } else {
                scrollNavigation((top - (currentScrollTop - lastScrollTop)));
            }
        }
    } else {
        removeFixedNavigation();

        if($(".floating_sidebar")[0].classList.contains("show")) {
            $(".floating_sidebar")[0].classList.remove("show");
            $(".floating_sidebar")[0].classList.add("hide_anim");
            setTimeout(() => {
                $(".floating_sidebar")[0].classList.remove("hide_anim");
            }, 250);
        }
    }

    lastScrollTop = Math.max(0, currentScrollTop);
})