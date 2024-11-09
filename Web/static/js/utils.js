function escapeHtml(text) {
    var map = {
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#039;'
    };
    
    return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}

function highlightText(searchText, container_selector, selectors = []) {
    const container = u(container_selector)
    const regexp = new RegExp(`(${searchText})`, 'gi')

    function highlightNode(node) {
        if(node.nodeType == 3) {
            let newNode = escapeHtml(node.nodeValue)
            newNode = newNode.replace(regexp, (match, ...args) => {
                return `<span class='highlight'>${escapeHtml(match)}</span>`
            })
            
            const tempDiv = document.createElement('div')
            tempDiv.innerHTML = newNode

            while(tempDiv.firstChild) {
                node.parentNode.insertBefore(tempDiv.firstChild, node)
            }
            node.parentNode.removeChild(node)
        } else if(node.nodeType === 1 && node.tagName !== 'SCRIPT' && node.tagName !== 'BR' && node.tagName !== 'STYLE' && !node.classList.contains('highlight')) {
            Array.from(node.childNodes).forEach(highlightNode);
        }
    }

    selectors.forEach(selector => {
        elements = container.find(selector)
        if(!elements || elements.length < 1) return;

        elements.nodes.forEach(highlightNode)
    })
}

String.prototype.escapeHtml = function() {
    try {
        return escapeHtml(this)
    } catch(e) {
        return ''
    }
}

function fmtTime(time) {
    const mins = String(Math.floor(time / 60)).padStart(2, '0');
    const secs = String(Math.floor(time % 60)).padStart(2, '0');
    return `${ mins}:${ secs}`;
}

function fastError(message) {
    MessageBox(tr("error"), message, [tr("ok")], [Function.noop])
}

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

function trimNum(string, num) {
    return ovk_proc_strtr(string, num);
}

function ovk_proc_strtr(string, length = 0) {
    const newString = string.substring(0, length);
    return newString + (string !== newString ? "…" : "");
}

function chunkSubstr(string, size) {
    const numChunks = Math.ceil(string.length / size);
    const chunks = new Array(numChunks);

    for (let i = 0, o = 0; i < numChunks; ++i, o += size) {
        chunks[i] = string.substr(o, size);
    }

    return chunks;
}

function random_int(min, max) {
    return Math.round(Math.random() * (max - min) + min)
}

function makeError(text, color = 'Red', timeout = 10000, uid = 0) {
    const rand_id = uid != 0 ? uid : random_int(0, 10000)

    if(uid != 0 && u(`.upLeftErrors .upLeftError[data-id='${uid}']`).length > 0) {
        return
    }

    u('.upLeftErrors').append(`
        <div class='upLeftError upLeftError${color}' data-id='${rand_id}'>${escapeHtml(text)}</div>
    `)

    setTimeout(() => {
        u(`.upLeftError[data-id='${rand_id}']`).remove()
    }, timeout)
}

function array_splice(array, key)
{
    let resultArray = [];

    for(let i = 0; i < array.length; i++){
        if(i != key){
            resultArray.push(array[i]);
        }
    }

    return resultArray;
}

function strip_tags(text) 
{
    return text.replace(/(<([^>]+)>)/gi, "")
}

function find_author(id, profiles, groups) 
{
    if(id > 0) {
        const profile = profiles.find(prof => prof.id == id)
        if(profile) {
            return profile
        }
    } else {
        const group = groups.find(grou => grou.id == Math.abs(id))
        if(group) {
            return group
        }
    }

    return null
}

function collect_attachments(target) {
    const horizontal_array = []
    const horizontal_attachments = target.find(`.post-horizontal > a`)
    horizontal_attachments.nodes.forEach(_node => {
        horizontal_array.push(`${_node.dataset.type}${_node.dataset.id}`)
    })

    const vertical_array = []
    const vertical_attachments = target.find(`.post-vertical > .vertical-attachment`)
    vertical_attachments.nodes.forEach(_node => {
        vertical_array.push(`${_node.dataset.type}${_node.dataset.id}`)
    })

    return horizontal_array.concat(vertical_array)
}
