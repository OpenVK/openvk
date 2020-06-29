

function show_write_textarea() {
    var el = document.getElementById('write');
    if (el.style.display == "none") {
        el.style.display = "block";
    } else {
        el.style.display = "none";
    }
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




        $(function () {
$('.content_title_expanded').click(function(){
	$(this).toggleClass("content_title_expanded content_title_unexpanded");
    $(this).next('div').slideToggle(300);
});});




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

}); //END ONREADY DECLS