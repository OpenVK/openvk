$(document).on("change", "#uploadButton", (e) => {
    let iterator = 0

    if(e.currentTarget.files.length > 10) {
        MessageBox(tr("error"), tr("too_many_pictures"), [tr("ok")], [() => {Function.noop}])
        return;
    }

    let photos = new FormData()
    for(file of e.currentTarget.files) {
        photos.append("photo_"+iterator, file)
        iterator += 1
    }

    photos.append("count", e.currentTarget.files.length)
    photos.append("hash", u("meta[name=csrf]").attr("value"))

    let xhr = new XMLHttpRequest()
    xhr.open("POST", "/photos/upload?album="+document.getElementById("album").value)

    xhr.onloadstart = () => {
        document.getElementById("photos").insertAdjacentHTML("beforeend", `<img id="loader" src="/assets/packages/static/openvk/img/loading_mini.gif">`)
    }

    xhr.onload = () => {
        let result = JSON.parse(xhr.responseText)

        if(result.success) {
            u("#loader").remove()
            let photosArr = result.photos

            for(photo of photosArr) {
                let table = document.querySelector("#photos")

                table.insertAdjacentHTML("beforeend", `
                <tr id="photo" data-id="${photo.id}">
                    <td width="120" valign="top">
                        <div class="uploadedImage">
                            <a href="${photo.link}" target="_blank"><img width="125" src="${photo.url}"></a>
                        </div>
                        <a style="float:right" id="deletePhoto" data-id="${photo.vid}" data-owner="${photo.owner}">${tr("delete")}</a>
                    </td>
                    <td>
                        <textarea style="margin: 0px; height: 50px; width: 259px; resize: none;" maxlength="255"></textarea>
                    </td>
                </tr>
                `)
            }

            document.getElementById("endUploading").style.display = "block"
        } else {
            u("#loader").remove()
            MessageBox(tr("error"), result.flash.message ?? tr("error_uploading_photo"), [tr("ok")], [() => {Function.noop}])
        }
    }

    xhr.send(photos)
})

$(document).on("click", "#endUploading", (e) => {
    let table = document.querySelector("#photos")
    let data  = new FormData()
    let arr   = new Map();
    for(el of table.querySelectorAll("tr#photo")) {
        arr.set(el.dataset.id, el.querySelector("textarea").value)
    }

    data.append("photos", JSON.stringify(Object.fromEntries(arr)))
    data.append("hash", u("meta[name=csrf]").attr("value"))

    let xhr = new XMLHttpRequest()
    // в самом вк на каждое изменение описания отправляется свой запрос, но тут мы экономим запросы
    xhr.open("POST", "/photos/upload?act=finish&album="+document.getElementById("album").value)

    xhr.onloadstart = () => {
        e.currentTarget.setAttribute("disabled", "disabled")
    }

    xhr.onerror = () => {
        MessageBox(tr("error"), tr("error_uploading_photo"), [tr("ok")], [() => {Function.noop}])
    }

    xhr.onload = () => {
        let result = JSON.parse(xhr.responseText)

        e.currentTarget.removeAttribute("disabled")
        document.querySelector(".page_content tbody").innerHTML = ""
        document.getElementById("endUploading").style.display = "none"

        NewNotification(tr("photos_successfully_uploaded"), tr("click_to_go_to_album"), null, () => {window.location.assign(`/album${result.owner}_${result.album}`)})
    }

    xhr.send(data)
})

$(document).on("click", "#deletePhoto", (e) => {
    let data  = new FormData()
    data.append("hash", u("meta[name=csrf]").attr("value"))

    let xhr = new XMLHttpRequest()
    xhr.open("POST", `/photo${e.currentTarget.dataset.owner}_${e.currentTarget.dataset.id}/delete`)

    xhr.onloadstart = () => {
        e.currentTarget.closest("tr#photo").classList.add("lagged")
    }

    xhr.onerror = () => {
        MessageBox(tr("error"), tr("unknown_error"), [tr("ok")], [() => {Function.noop}])
    }

    xhr.onload = () => {
        u(e.currentTarget.closest("tr#photo")).remove()

        if(document.querySelectorAll("tr#photo").length < 1) {
            document.getElementById("endUploading").style.display = "none"
        }
    }

    xhr.send(data)
})
