

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