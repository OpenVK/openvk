function humanFileSize(bytes, si) {
    var thresh = si ? 1000 : 1024;
    if(Math.abs(bytes) < thresh) {
        return bytes + ' B';
    }
    var units = si
        ? ['kB','MB','GB','TB','PB','EB','ZB','YB']
        : ['KiB','MiB','GiB','TiB','PiB','EiB','ZiB','YiB'];
    var u = -1;
    do {
        bytes /= thresh;
        ++u;
    } while(Math.abs(bytes) >= thresh && u < units.length - 1);
    return bytes.toFixed(1)+' '+units[u];
}

function trim(string) {
    var newStr = string.substring(0, 10);
    if(newStr.length !== string.length)
        newStr += "…";
    
    return newStr;
}

function handleVideoTAreaUpdate(event, id) {
    console.log(event, id);
    let indicator = u("#post-buttons" + id + " .post-upload");
    let file      = event.target.files[0];
    if(typeof file === "undefined") {
        indicator.attr("style", "display: none;");
    } else {
        u("span", indicator.nodes[0]).text("Видеолента: " + trim(file.name) + " (" + humanFileSize(file.size, false) + ")");
        indicator.attr("style", "display: block;");
    }

    document.querySelector("#post-buttons" + id + " #wallAttachmentMenu").classList.add("hidden");
}

function initGraffiti(id) {
    let canvas = null;
    let msgbox = MessageBox(tr("draw_graffiti"), "<div id='ovkDraw'></div>", [tr("save"), tr("cancel")], [function() {
        canvas.getImage({includeWatermark: false}).toBlob(blob => {
            let fName = "Graffiti-" + Math.ceil(performance.now()).toString() + ".jpeg";
            let image = new File([blob], fName, {type: "image/jpeg", lastModified: new Date().getTime()});
            let trans = new DataTransfer();
            trans.items.add(image);
            
            let fileSelect = document.querySelector("#post-buttons" + id + " input[name='_pic_attachment']");
            fileSelect.files = trans.files;
            
            u(fileSelect).trigger("change");
            u("#post-buttons" + id + " #write textarea").trigger("focusin");
        }, "image/jpeg", 0.92);
        
        canvas.teardown();
    }, function() {
        canvas.teardown();
    }]);
    
    let watermarkImage = new Image();
    watermarkImage.src = "/assets/packages/static/openvk/img/logo_watermark.gif";
    
    msgbox.attr("style", "width: 750px;");
    canvas = LC.init(document.querySelector("#ovkDraw"), {
        backgroundColor: "#fff",
        imageURLPrefix: "/assets/packages/static/openvk/js/node_modules/literallycanvas/lib/img",
        watermarkImage: watermarkImage,
        imageSize: {
            width: 640,
            height: 480
        }
    });
}

u(".post-like-button").on("click", function(e) {
    e.preventDefault();
    
    var thisBtn = u(this).first();
    var link    = u(this).attr("href");
    var heart   = u(".heart", thisBtn);
    var counter = u(".likeCnt", thisBtn);
    var likes   = counter.text() === "" ? 0 : counter.text();
    var isLiked = heart.attr("id") === 'liked';
    
    ky(link);
    heart.attr("id", isLiked ? '' : 'liked');
    counter.text(parseInt(likes) + (isLiked ? -1 : 1));
    if (counter.text() === "0") {
        counter.text("");
    }
    
    return false;
});

let picCount = 0;

function setupWallPostInputHandlers(id) {
    /* u("#wall-post-input" + id).on("paste", function(e) {
        if(e.clipboardData.files.length === 1) {
            var input = u("#post-buttons" + id + " input[name=_pic_attachment]").nodes[0];
            input.files = e.clipboardData.files;
            
            u(input).trigger("change");
        }
    }); */
    
    u("#wall-post-input" + id).on("input", function(e) {
        var boost             = 5;
        var textArea          = e.target;
        textArea.style.height = "5px";
        var newHeight = textArea.scrollHeight;
        textArea.style.height = newHeight + boost + "px";
        return;
        
        // revert to original size if it is larger (possibly changed by user)
        // textArea.style.height = (newHeight > originalHeight ? (newHeight + boost) : originalHeight) + "px";
    });

    u(`#wall-post-input${id}`).on("paste", function(e) {
        for (let i = 0; i < e.clipboardData.files.length; i++) {
            console.log(e.clipboardData.files[i]);
            if(e.clipboardData.files[i].type.match('^image/')) {
                let blobURL = URL.createObjectURL(e.clipboardData.files[i]);
                addPhotoMedia(e.clipboardData.files, blobURL, id);
            }
        }
    });

    u(`#post-buttons${id} input[name=_pic_attachment]`).on("change", function(e) {
        let blobURL = URL.createObjectURL(e.target.files[0]);
        addPhotoMedia(e.target.files, blobURL, id);
    });

    function addPhotoMedia(files, preview, id) {
        if(getMediaCount() >= 10) {
            alert('Не больше 10 пикч');
        } else {
            picCount++;
            u(`#post-buttons${id} .upload`).append(u(`
                <div class="upload-item" id="aP${picCount}">
                    <a href="javascript:removePicture(${picCount})" class="upload-delete">×</a>
                    <img src="${preview}">
                </div>
            `));
            u(`div#aP${picCount}`).nodes[0].append(u(`<input type="file" accept="image/*" name="attachPic${picCount}" id="attachPic${picCount}" style="display: none;">`).first());
            let input = u(`#attachPic${picCount}`).nodes[0];
            input.files = files; // нужен рефактор, но щас не
            console.log(input);
            u(input).trigger("change");
        }
    }

    function getMediaCount() {
        return u(`#post-buttons${id} .upload`).nodes[0].children.length;
    }
}

function removePicture(idA) {
    u(`div#aP${idA}`).nodes[0].remove();
}

function OpenMiniature(e, photo, post, photo_id) {
    /*
    костыли но смешные однако
    */
    e.preventDefault();

    if(u(".ovk-photo-view").length > 0) return false;

    // Значения для переключения фоток

    let json;

    let imagesCount = 0;
    let imagesIndex = 0;
    
    let dialog = u(
    `<div class="ovk-photo-view-dimmer">
        <div class="ovk-photo-view">
            <div class="photo_com_title">
                <text id="photo_com_title_photos">
                    <img src="/assets/packages/static/openvk/img/loading_mini.gif">
                </text>
                <div>
                    <a id="ovk-photo-close">Закрыть</a>
                </div>
            </div>
            <center style="margin-bottom: 8pt;">
                <div class="ovk-photo-slide-left"></div>
                <div class="ovk-photo-slide-right"></div>
                <img src="${photo}" style="max-width: 80%; max-height: 60vh;" id="ovk-photo-img">
            </center>
        </div>
    </div>`);
    u("body").addClass("dimmed").append(dialog);
    
    let button = u("#ovk-photo-close");

    button.on("click", function(e) {
        let __closeDialog = () => {
            u("body").removeClass("dimmed");
            u(".ovk-photo-view-dimmer").remove();
        };
        
        __closeDialog();
    });

    function __slidePhoto(direction) {
        /* direction = 1 - right
           direction = 0 - left */
        if(json == undefined) {
            console.log("Да подожди ты. Куда торопишься?");
        } else {
            if(imagesIndex >= imagesCount && direction == 1) {
                imagesIndex = 1;
            } else if(imagesIndex <= 1 && direction == 0) {
                imagesIndex = imagesCount;
            } else if(direction == 1) {
                imagesIndex++;
            } else if(direction == 0) {
                imagesIndex--;
            }

            let photoURL = json.body[imagesIndex - 1].url;

            u("#ovk-photo-img").last().src = photoURL;
            u("#photo_com_title_photos").last().innerHTML = "Фотография " + imagesIndex + " из " + imagesCount;
        }
    }

    let slideLeft = u(".ovk-photo-slide-left");

    slideLeft.on("click", (e) => {
        __slidePhoto(0);
    });

    let slideRight = u(".ovk-photo-slide-right");

    slideRight.on("click", (e) => {
        __slidePhoto(1);
    });

    ky.post("/iapi/getPhotosFromPost/" + post, {
        hooks: {
            afterResponse: [
                async (_request, _options, response) => {
                    json = await response.json();

                    imagesCount = json.body.length;
                    imagesIndex = 0;
                    // Это всё придётся правда на 1 прибавлять
                    
                    json.body.every(element => {
                        imagesIndex++;
                        if(element.id == photo_id) {
                            return false;
                        } else {
                            return true;
                        }
                    });

                    u("#photo_com_title_photos").last().innerHTML = "Фотография " + imagesIndex + " из " + imagesCount;
                }
            ]
        }
    });

    return u(".ovk-photo-view-dimmer");
}

u("#write > form").on("keydown", function(event) {
    if(event.ctrlKey && event.keyCode === 13)
        this.submit();
});
