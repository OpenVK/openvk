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

function handleUpload() {
    console.warn("блять...");
    var indicator = u(".post-upload");
    var file      = this.files[0];
    if(typeof file === "undefined") {
        indicator.attr("style", "display: none;");
    } else {
        u("span", indicator.nodes[0]).text(trim(file.name) + " (" + humanFileSize(file.size, false) + ")");
        indicator.attr("style", "display: block;");
    }
}

u("#wall-post-input").on("paste", function(e) {
    if(e.clipboardData.files.length === 1) {
        var input = u("input[name=_pic_attachment]").nodes[0];
        input.files = e.clipboardData.files;
        
        Reflect.apply(handleUpload, input, []);
    }
});

u(".post-like-button").on("click", function(e) {
    e.preventDefault();
    
    var thisBtn = u(this).first();
    var link    = u(this).attr("href");
    var heart   = u(".heart", thisBtn);
    var counter = u(".likeCnt", thisBtn);
    var likes   = counter.text();
    var isLiked = heart.attr("style") === 'opacity: 1;';
    
    ky(link);
    heart.attr("style", isLiked ? 'opacity: 0.4;' : 'opacity: 1;');
    counter.text(parseInt(likes) + (isLiked ? -1 : 1));
    
    return false;
});

u("#wall-post-input").on("input", function(e) {
    var boost             = 5;
    var textArea          = e.target;
    textArea.style.height = "5px";
    var newHeight = textArea.scrollHeight;
    textArea.style.height = newHeight + boost;
    return;
    
    // revert to original size if it is larger (possibly changed by user)
    // textArea.style.height = (newHeight > originalHeight ? (newHeight + boost) : originalHeight) + "px";
});

u("input[name=_pic_attachment]").on("change", handleUpload);