// "Опубликовать запись"
$(document).on("click", "#publish_post", async (e) => {
    let id = Number(e.currentTarget.dataset.id)
    let post;
    let body = `
        <textarea id="pooblish" style="max-height:500px;resize:vertical;min-height:54px;"></textarea>
        <label><input type="checkbox" id="signatr" checked>${tr("add_signature")}</label>
    `

    MessageBox(tr("publishing_suggested_post"), body, [tr("publish"), tr("cancel")], [(async () => {
        let id = Number(e.currentTarget.dataset.id)
        let post;

        try {
            e.currentTarget.classList.add("loaded")
            e.currentTarget.setAttribute("value", "")
            e.currentTarget.setAttribute("id", "")
            post = await API.Wall.acceptPost(id, document.getElementById("signatr").checked, document.getElementById("pooblish").value)
        } catch(ex) {
            switch(ex.code) {
                case 11:
                    MessageBox(tr("error"), tr("error_accepting_invalid_post"), [tr("ok")], [Function.noop]);
                    break;
                case 19:
                    MessageBox(tr("error"), tr("error_accepting_not_suggested_post"), [tr("ok")], [Function.noop]);
                    break;
                case 10:
                    MessageBox(tr("error"), tr("error_accepting_declined_post"), [tr("ok")], [Function.noop]);
                    break;
                case 22:
                    MessageBox(tr("error"), "Access denied", [tr("ok")], [Function.noop]);
                    break;
                default:
                    MessageBox(tr("error"), "Unknown error "+ex.code+": "+ex.message, [tr("ok")], [Function.noop]);
                    break;
            }

            e.currentTarget.setAttribute("value", tr("publish_suggested"))
            e.currentTarget.classList.remove("loaded")
            e.currentTarget.setAttribute("id", "publish_post")
            return 0;
        }

        NewNotification(tr("suggestion_succefully_published"), tr("suggestion_press_to_go"), null, () => {window.location.assign("/wall" + post.id)});
        
        if(document.getElementById("cound") != null)
            document.getElementById("cound").innerHTML = tr("suggested_posts_in_group", post.new_count)
        else
            document.getElementById("cound_r").innerHTML = tr("suggested_by_everyone", post.new_count)

        if(document.querySelector("object a[href='"+location.pathname+"'] b") != null) {
            document.querySelector("object a[href='"+location.pathname+"'] b").innerHTML = post.new_count

            if(post.new_count < 1) {
                u("object a[href='"+location.pathname+"']").remove()
            }
        }

        if(post.new_count < 1 && document.querySelector(".sugglist") != null) {
            $(".sugglist a").click()
            $(".sugglist").remove()
        }

        let post_node = e.currentTarget.closest("table")
        post_node.style.transition = "opacity 300ms ease-in-out";
        post_node.style.opacity = "0";
        post_node.classList.remove("post")

        setTimeout(() => {post_node.outerHTML = ""}, 300)

        if(document.querySelectorAll("#postz .post").length < 1 && post.new_count > 0 && document.querySelector(".paginator") != null)
            loadMoreSuggestedPosts()
    }), Function.noop]);

    document.getElementById("pooblish").innerHTML = e.currentTarget.closest("table").querySelector(".really_text").innerHTML.replace(/(<([^>]+)>)/gi, '')
    document.querySelector(".ovk-diag-body").style.padding = "9px";
})

// "Отклонить"
$(document).on("click", "#decline_post", async (e) => {
    let id = Number(e.currentTarget.dataset.id)
    let post;

    try {
        e.currentTarget.classList.add("loaded")
        e.currentTarget.setAttribute("value", "")
        e.currentTarget.setAttribute("id", "")
        post = await API.Wall.declinePost(id)
    } catch(ex) {
        switch(ex.code) {
            case 11:
                MessageBox(tr("error"), tr("error_declining_invalid_post"), [tr("ok")], [Function.noop]);
                break;
            case 19:
                MessageBox(tr("error"), tr("error_declining_not_suggested_post"), [tr("ok")], [Function.noop]);
                break;
            case 10:
                MessageBox(tr("error"), tr("error_declining_declined_post"), [tr("ok")], [Function.noop]);
                break;
            case 22:
                MessageBox(tr("error"), "Access denied", [tr("ok")], [Function.noop]);
                break;
            default:
                MessageBox(tr("error"), "Unknown error "+ex.code+": "+ex.message, [tr("ok")], [Function.noop]);
                break;
        }

        e.currentTarget.setAttribute("value", tr("decline_suggested"))
        e.currentTarget.setAttribute("id", "decline_post")
        e.currentTarget.classList.remove("loaded")
        return 0;
    }
    
    //NewNotification(tr("suggestion_succefully_declined"), "", null);

    let post_node = e.currentTarget.closest("table")
    post_node.style.transition = "opacity 300ms ease-in-out";
    post_node.style.opacity = "0";
    post_node.classList.remove("post")

    setTimeout(() => {post_node.outerHTML = ""}, 300)

    if(document.getElementById("cound") != null)
        document.getElementById("cound").innerHTML = tr("suggested_posts_in_group", post)
    else
        document.getElementById("cound_r").innerHTML = tr("suggested_by_everyone", post)
    
    if(document.querySelector("object a[href='"+location.pathname+"'] b") != null) {
        document.querySelector("object a[href='"+location.pathname+"'] b").innerHTML = post

        if(post < 1) {
            u("object a[href='"+location.pathname+"']").remove()
        }
    }

    if(post < 1 && document.querySelector(".sugglist") != null) {
        $(".sugglist a").click()
        $(".sugglist").remove()
    }
    
    if(document.querySelectorAll("#postz .post").length < 1 && post > 0 && document.querySelector(".paginator") != null)
        loadMoreSuggestedPosts()
})

function loadMoreSuggestedPosts()
{
    let xhr = new XMLHttpRequest
    let link = location.href

    if(!link.includes("/suggested")) {
        link += "/suggested"
    }

    xhr.open("GET", link)

    xhr.onloadstart = () => {
        document.getElementById("postz").innerHTML = `<img src="/assets/packages/static/openvk/img/loading_mini.gif">`
    }

    xhr.onload = () => {
        let parser = new DOMParser()
        let body   = parser.parseFromString(xhr.responseText, "text/html").getElementById("postz")

        if(body.querySelectorAll(".post").length < 1) {
            let url = new URL(location.href)
            url.searchParams.set("p", url.searchParams.get("p") - 1)

            if(url.searchParams.get("p") < 1) {
                return 0;
            }

            // OVK AJAX ROUTING ??????????
            history.pushState({}, "", url)

            loadMoreSuggestedPosts()
        }

        document.getElementById("postz").innerHTML = body.innerHTML
    }

    xhr.onerror = () => {
        document.getElementById("postz").innerHTML = tr("error_loading_suggest")
    }

    xhr.send()
}

// нажатие на "x предложенных записей"
$(document).on("click", ".sugglist a", (e) => {
    e.preventDefault()
    
    if(e.currentTarget.getAttribute("data-toogled") == null || e.currentTarget.getAttribute("data-toogled") == "false") {
        e.currentTarget.setAttribute("data-toogled", "true")
        document.getElementById("underHeader").style.display = "none"
        document.querySelector(".insertThere").style.display = "block"
        document.querySelector(".insertThere").classList.add("infContainer")
        history.pushState({}, "", e.currentTarget.href)

        // если ещё ничего не подгружалось
        if(document.querySelector(".insertThere").innerHTML == "") {
            let xhr = new XMLHttpRequest
            xhr.open("GET", e.currentTarget.href)
            
            xhr.onloadstart = () => {
                // лоадер
                document.querySelector(".insertThere").insertAdjacentHTML("afterbegin", `<img src="/assets/packages/static/openvk/img/loading_mini.gif">`)
            }

            xhr.onload = () => {
                let parser = new DOMParser
                let result = parser.parseFromString(xhr.responseText, 'text/html').querySelector(".infContainer")
                // парсинг результата и вставка постов
                document.querySelector(".insertThere").innerHTML = result.innerHTML
            }

            function errorl() {
                document.getElementById("postz").innerHTML = tr("error_loading_suggest")
            }
        
            xhr.onerror = () => {errorl()}
            xhr.ontimeout = () => {errorl()}
            
            xhr.send()
        }
    } else {
        // переключение на нормальную стену
        e.currentTarget.setAttribute("data-toogled", "false")
        document.getElementById("underHeader").style.display = "block"
        document.querySelector(".insertThere").style.display = "none"
        document.querySelector(".insertThere").classList.remove("infContainer")
        history.pushState({}, "", e.currentTarget.href.replace("/suggested", ""))
    }
})

// нажатие на пагинатор у постов пъедложки
$(document).on("click", "#postz .paginator a", (e) => {
    e.preventDefault()
    
    let xhr = new XMLHttpRequest
    xhr.open("GET", e.currentTarget.href)

    xhr.onloadstart = () => {
        if(document.querySelector(".sugglist") != null) {
            document.querySelector(".sugglist").scrollIntoView({behavior: "smooth"})
        } else {
            document.querySelector(".infContainer").scrollIntoView({behavior: "smooth"})
        }
        // после того как долистали наверх, добавляем лоадер
        setTimeout(() => {document.getElementById("postz").innerHTML = `<img src="/assets/packages/static/openvk/img/loading_mini.gif">`}, 500)
    }

    xhr.onload = () => {
        // опять парс
        let result = (new DOMParser).parseFromString(xhr.responseText, "text/html").querySelector(".infContainer")
        // опять вставка
        document.getElementById("postz").innerHTML = result.innerHTML
        history.pushState({}, "", e.currentTarget.href)
    }

    function errorl() {
        document.getElementById("postz").innerHTML = tr("error_loading_suggest")
    }

    xhr.onerror = () => {errorl()}
    xhr.ontimeout = () => {errorl()}

    xhr.send()
})
