/* sorry about that */

function hideAnswer(question) {
    $(question).toggleClass("active");
    $(question).next('div').slideToggle(300);
}

function hideIssue(item) {
    $(item).next('div').slideToggle(300);
}

function hideIssueButton(item) {
    document.cookie = `faq_closed_issues=${encodeURIComponent(JSON.stringify(issues.map(issue => issue.id)))}; path=/; max-age=31536000; SameSite=Lax`;
    $(item).parent('div').slideToggle(300);
}

function changeIssue(backward = false) {
    issue = (issue + (backward ? -1 : 1) + issues.length) % issues.length;

    $("#faq-issue h1").text(issues[issue].title);
    $("#faq-issue p").html(issues[issue].description);   
}

function faqSettings() {
    var showUntranslated = document.cookie.split("; ").find(c => c.startsWith("faq_show_untranslated="))?.split("=")[1];
    new MessageBox(
        tr("ui_settings_window"),
        `
            <center>
                <label>
                    <input type="checkbox" id="untranslated" ${showUntranslated == "1" ? "checked" : ""}/>
                    ${tr("support_faq_show_untranslated")}
                </label>
            </center>
        `,
        [
            tr("close"),
            tr("apply")
        ],
        [
            ()=>{},
            ()=>{
                document.cookie = `faq_show_untranslated=${$("#untranslated").is(":checked") ? "1" : "0"}`;
                location.reload();
            }
        ]
    );
}

function faqSetIcon(icon) {
    $("#faq-icon").removeClass().addClass(`faq-icon_${icon}`);
    $("#faq-icon input").val(icon);
}

var $iconSetBody = $(`<div id="faq-icon_set">`);
for (let i = 0; i < 21; i++) {
    $iconSetBody.append($(`<div class="faq-icon_${i}" onclick="faqSetIcon(${i})"></div>`));
}

tippy.delegate("body", {
    target: "#faq-icon",
    theme: "light vk",
    content: $iconSetBody[0],
    trigger: "click",
    allowHTML: true,
    interactive: true,
    interactiveDebounce: 100,
    placement: "bottom",
});

function makeFaqQuestionBody(l) {
    return `
        <tbody>
            <tr>
                <td width="150" valign="top">
                    <span>${tr("support_admin_topic_q")}:</span>    
                </td>
                <td>
                    <input type="text" onchange="faqUnwarn(this)" name="questions[${l}][${question}][q]">
                </td>
                <td>
                    <div class="faq-remove" onclick="faqRemoveQuestion(this)">
                </td>
            </tr>
            <tr>
                <td width="150" valign="top">
                    <span>${tr("support_admin_topic_a")}:</span>
                </td>
                <td>
                    <textarea onchange="faqUnwarn(this)" name="questions[${l}][${question}][a]"></textarea>
                </td>
                <td>
                    
                </td>
            </tr>
            <tr>
                <td colspan="3"></td>
            </tr>
        </tbody>
    `;
}

function faqChangeLang() {
    var lang = $("#faq-lang").val();

    $("#faq-title").closest("tr").find(".active").removeClass("active");
    $("#faq-desc").closest("tr").find(".active").removeClass("active");
    
    var input = $(`input[name="title[${lang}]"]`);
    input.length ? input.addClass("active") : $("#faq-title").append($(`<input type="text" onchange="faqUnwarn(this, ${lang})" name="title[${lang}]" class="active">`));

    var textarea = $(`textarea[name="description[${lang}]"]`);
    textarea.length ? textarea.addClass("active") : $("#faq-desc").append($(`<textarea onchange="faqUnwarn(this, ${lang})" name="description[${lang}]" class="active">`));

    var spanDir = $("#faq-title").prev("td");
    var spanDir2 = $("#faq-desc").prev("td");
    var span = $(`.faq-title_${lang}`);
    if (span.length) {
        span.addClass("active");
    } else {
        spanDir.append($(`<span class="faq-title_${lang} active">${tr("support_admin_topic_name")}:</span>`));
        spanDir2.append($(`<span class="faq-title_${lang} active">${tr("description")}:</span>`));
    }

    $("#faq-questions > .active").removeClass("active");
    var $table = $(`table[data-lang="${lang}"]`);
    if ($table.length) {
        $table.addClass("active")
    } else {
        $("#faq-questions").append(`
            <table data-lang="${lang}" class="active flexible_table" width="80%" border="0" align="center">
                ${makeFaqQuestionBody(lang)}
            </table>
        `);
        
        question++;
    }

    
}

function faqAddQuestion() {
    $("#faq-questions table.active").append(makeFaqQuestionBody($("#faq-lang").val()));
    question++;
}

function faqRemoveQuestion(item) {
    var table = $(item).closest("table");
    var lang = table.data("lang");

    if (table.find("tbody").length > 1) {
        $(item).closest("tbody").remove();
    } else {  
        if (lang === mainLang) {
            $(item).closest("tbody").find("input, textarea").val("");
        } else {
            table.remove();
            $(`input[name="title[${lang}]"]`).remove();
            $(`.faq-title_${lang}`).remove();
            $("#faq-lang").val(mainLang).trigger("change");
        }
    }
}

function faqValidate(topicUrl = "") {
    function warn(target, lang = "") { 
        if (lang !== "") {
            $(`.faq-title_${lang}`).addClass("faq-warn"); 
        } else {
            target.closest("tr").find(`td[width="120"] span, td[width="150"] span`).addClass("faq-warn"); 
        }
    }
    
    var errLangs = [];

    function checkUrl() {
        var url = $('input[name="url"]');
        var urlVal = url.val().trim();

        if (topicUrl) {
            topics.splice(topics.indexOf(topicUrl));
        }

        if (!urlVal) {
            warn(url);
            return false;
        }

        if (!/^[a-zA-Z0-9]+$/.test(urlVal)) {
            warn(url);
            makeError(tr("support_admin_err_msg_3"), "Red", 4000);
            return false;
        }

        if (topics.includes(urlVal)) {
            warn(url);
            makeError(tr("support_admin_err_msg_2_1"), "Red", 4000);
            return false;            
        }

        return true;
    }

    function checkQuestions() {
        var valid = true;

        $("#faq-questions table").each(function() {
            var table = $(this);
            var lang = table.data("lang");

            table.find('input[type="text"]').each(function() {
                var q = $(this)
                var a = q.closest("tr").next("tr").find("textarea");
                var qVal = q.val().trim();
                var aVal = a.val().trim();

                if (!qVal || !aVal) {
                    if (!qVal) warn(q);
                    if (!aVal) warn(a);
                    valid = false;
                    if (!errLangs.includes(lang)) errLangs.push(lang);
                }
            });
        });
        
        return valid;
    }
    
    function checkTitles() {
        var valid = true;

        $("#faq-title input").each(function() {
            if (!$(this).val().trim()) {
                warn($(this), $(this).attr("name").split("[")[1].replace("]", ""));
                valid = false;
            }
        });

        return valid;
    }


    function validate() {
        var u = checkUrl();
        var q = checkQuestions();
        var t = checkTitles();

        if (u && q && t) {
            return true;
        }
        if (errLangs.length > 0) {
            $("#faq-lang").val(errLangs[0]).trigger("change");
            errLangs.forEach((lang) => $(`#faq-lang option[value="${lang}"]`).addClass("faq-warn"));
        }
        return false;
    }

    return validate();
}

function faqUnwarn(target, lang = "") {
    if (lang !== "") {
        $(`.faq-title_${lang}`).removeClass("faq-warn"); 
    } else {
        $(target).closest("tr").find(`td[width="120"] span, td[width="150"] span`).removeClass("faq-warn"); 
    }
}

function faqDeleteTopic(hash, name) {
    var busy = false;
    const msg = new CMessageBox({
        title: tr("confirmation"),
        body: `<div id="faq-confirm">${tr("question_confirm")}</div>`,
        buttons: [
            tr("no"),
            tr("yes")
        ],
        callbacks: [
            ()=>{ (!busy ? msg.close() : "") },
            async ()=>{
                if (!busy) {
                    busy = true;
                    $("#faq-confirm").html('<img src="/assets/packages/static/openvk/img/progressbar.gif">');
                    const data = new FormData();
                    data.append("hash", hash);
                    data.append("name", name);

                    const response = await fetch("/support/admin?act=deleteTopic", {
                        method: "POST",
                        body: data
                    });

                    const result = await response.json();

                    if (result.success) {
                        location.href = "/support/admin?act=editFaq"; 
                    } else {
                        msg.close();
                        makeError("бля", "Red", 4000);
                    }
                }
            }
        ],
        close_on_buttons: false
    });
}

var faqDisableTopicBusy = false;
async function faqDisableTopic(hash, name, item) {
    if (!faqDisableTopicBusy) {
        faqDisableTopicBusy = true;
        $(item).addClass("busy");
        $(item).html('<img src="/assets/packages/static/openvk/img/progressbar.gif">');
        const data = new FormData();
        data.append("hash", hash);
        data.append("name", name);

        const response = await fetch("/support/admin?act=disableTopic", {
            method: "POST",
            body: data
        });

        const result = await response.json();

        if (result.success >= 0) {
            $(item).removeClass("busy");
            $(item).html((result.success === 1 ? tr("disable") : tr("enable")));
            faqDisableTopicBusy = false;
        } else {
            makeError("бля", "Red", 4000);
        }
    }
}