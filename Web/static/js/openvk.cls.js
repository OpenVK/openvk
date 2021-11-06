function expand_wall_textarea() {
    var el = document.getElementById('post-buttons');
    var wi = document.getElementById('wall-post-input');
    el.style.display = "block";
    wi.className = "expanded-textarea";


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


document.addEventListener("DOMContentLoaded", function() { //BEGIN

u("#_photoDelete").on("click", function(e) {
    var formHtml = "<form id='tmpPhDelF' action='" + u(this).attr("href") + "' >";
    formHtml    += "<input type='hidden' name='hash' value='" + u("meta[name=csrf]").attr("value") + "' />";
    formHtml    += "</form>";
    u("body").append(formHtml);
    
    MessageBox("Внимание", "Удаление нельзя отменить. Вы действительно уверены в том что хотите сделать?", [
        "Да",
        "Нет"
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
    
    MessageBox("Внимание", "Удаление нельзя отменить. Вы действительно уверены в том что хотите сделать?", [
        "Да",
        "Нет"
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

}); //END ONREADY DECLS

function repostPost(id, hash) {
	uRepostMsgTxt  = "Ваш комментарий: <textarea id='uRepostMsgInput_"+id+"'></textarea><br/><br/>";
	
	MessageBox("Поделиться", uRepostMsgTxt, ["Отправить", "Отменить"], [
		(function() {
			text = document.querySelector("#uRepostMsgInput_"+id).value;
			hash = encodeURIComponent(hash);
			xhr = new XMLHttpRequest();
			xhr.open("POST", "/wall"+id+"/repost?hash="+hash, true);
			xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
			xhr.onload = (function() {
				if(xhr.responseText.indexOf("wall_owner") === -1)
					MessageBox("Помилка", "Не удалось поделиться записью...", ["OK"], [Function.noop]);
				else {
					let jsonR = JSON.parse(xhr.responseText);
                    NewNotification("Успешно поделились", "Запись появится на вашей стене. Нажмите на уведомление, чтобы перейти к своей стене.", null, () => {window.location.href = "/wall" + jsonR.wall_owner});
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
    `, ["Изменить", "Отменить"], [
        () => {
            if (document.querySelector(`#uClubAdminCommentTextArea_${clubId}_${adminId}`).value === "") {
                document.querySelector(`#uClubAdminCommentRemoveCommentInput_${clubId}_${adminId}`).value = "1";
            }

            document.querySelector(`#uClubAdminCommentForm_${clubId}_${adminId}`).submit();
        },
        Function.noop
    ]);
}
