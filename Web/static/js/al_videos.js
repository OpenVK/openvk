u(document).on('click', `#uploadVideo`, async (e) => {
    e.preventDefault();
    let url = location.href
    let form = u('#videoUploadForm')
    let fd = serializeForm(form)
    fd.append("ajax", "1")

    let xhr = new XMLHttpRequest();

    form.addClass('lagged')
    form.find("#percentage").nodes[0].style.visibility = "visible";

    let response = {}

    try {
        const result = await new Promise((resolve) => {
            xhr.upload.addEventListener("progress", (event) => {
                if (event.lengthComputable) {
                    form.find(".progress-bar").nodes[0].style.width = event.loaded / event.total * 100 + "%"
                    console.log("upload progress:", event.loaded / event.total)
                }
            })
            xhr.addEventListener("loadend", () => {
                resolve(xhr);
            });
            xhr.open("POST", url, true)
            xhr.send(fd)
        })

        response = JSON.parse(result.response)
    } catch (err) {
        makeError('JavaScript error: ' + err)
    }

    if (response.success == true) {
        window.router.route(response.redirect)
    } else {
        MessageBox(response.flash.title, response.flash.message, [
            tr('close')
        ], [
            Function.noop
        ]);
        form.find("#percentage").nodes[0].style.visibility = "hidden";
        form.find(".progress-bar").nodes[0].style.width = "0%"
        form.removeClass('lagged')
    }
})