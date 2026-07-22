function escapeHtml(text) {
    var map = {
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#039;'
    };

    try {
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    } catch (e) {
        console.error(e);

        return "ESCAPEHTML_FAILED";
    }
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

function getRemainingTime(fullTime, time) {
    let timer = fullTime - time

    if(timer < 0) return "-00:00"

    return "-" + fmtTime(timer)
}

function serializeForm(form, submitter = null)
{
    const u_ = u(form)
    const inputs = u_.find('input, textarea, button, select')
    let fd = new FormData()
    inputs.nodes.forEach(inp => {
        if(!inp || !inp.name) {
            return
        }

        if(inp.type == 'submit') {
            if(inp !== submitter) {
                return
            }
        }

        switch(inp.type) {
            case 'hidden':
            case 'text':
            case 'textarea':
            case 'select':
            case 'select-one':
            case 'submit':
            case 'email':
            case 'phone':
            case 'search':
            case 'password':
            case 'date':
            case 'datetime-local':
                fd.append(inp.name, inp.value)
                break
            case 'checkbox':
                if(inp.checked) {
                    fd.append(inp.name, inp.value)
                }

                break
            case 'file':
                if(!inp.multiple) {
                    if(inp.files[0]) {
                        fd.append(inp.name, inp.files[0])
                    } else {
                        const emptyFile = new Blob([], { type: 'application/octet-stream' })
                        fd.append(inp.name, emptyFile, '')
                    }
                }
                break
            case 'radio':
                if(inp.checked) {
                    fd.append(inp.name, inp.value)
                }
                break
        }
    })

    return fd
}

async function copyToClipboard(text) {
    let fallback = () => {
        prompt(text);
    }

    if(typeof navigator.clipboard == "undefined") {
        fallback()
    } else {
        try {
            await navigator.clipboard.writeText(text)
        } catch(e) {
            fallback()
        }
    }
}

function remove_file_format(text)
{
    return text.replace(/\.[^.]*$/, '')
}

function sleep(time)
{
    return new Promise((resolve) => setTimeout(resolve, time));
}

function collect_attachments_node(target)
{
    const horizontal_array = []
    const horizontal_input = target.find(`input[name='horizontal_attachments']`)
    const horizontal_attachments = target.find(`.post-horizontal > a`)
    horizontal_attachments.nodes.forEach(_node => {
        horizontal_array.push(`${_node.dataset.type}${_node.dataset.id}`)
    })
    horizontal_input.nodes[0].value = horizontal_array.join(',')

    const vertical_array = []
    const vertical_input = target.find(`input[name='vertical_attachments']`)
    const vertical_attachments = target.find(`.post-vertical > .vertical-attachment`)
    vertical_attachments.nodes.forEach(_node => {
        vertical_array.push(`${_node.dataset.type}${_node.dataset.id}`)
    })
    vertical_input.nodes[0].value = vertical_array.join(',')
}

function short_geo_name(address_osm)
{
    let final_arr = []
    if(address_osm.country) {
        final_arr.push(address_osm.country)
    }
    if(address_osm.state) {
        final_arr.push(address_osm.state)
    }
    if(address_osm.state_district) {
        final_arr.push(address_osm.state_district)
    }
    if(address_osm.city) {
        if(address_osm.city != address_osm.state) {
            final_arr.push(address_osm.city)
        }
    } else if(address_osm.town) {
        final_arr.push(address_osm.town)
    }
    if(address_osm.city_district) {
        final_arr.push(address_osm.city_district)
    }
    if(address_osm.village) {
        final_arr.push(address_osm.village)
    }
    if(address_osm.road) {
        final_arr.push(address_osm.road)
    }

    return escapeHtml(final_arr.join(', '))
}

function expandText(item)
{
    if (item.parentElement.querySelector(".really_text").classList.contains("collapsed_text")) {
        item.parentElement.querySelector(".really_text").classList.remove("collapsed_text")
        item.textContent = tr("show_less")
    } else {
        item.parentElement.querySelector(".really_text").classList.add("collapsed_text")
        item.textContent = tr("show_more")
    }
}

function month_day_string(date)
{
    const current_year = new Date().getFullYear();
    const date_year = date.getFullYear();
    const day = date.getDate();
    const month = date.getMonth() + 1;
    const month_str = tr("month_gen_" + month).toLowerCase();
    let ret = null;

    if (current_year === date_year) {
        ret = tr("day_template", day, month_str);
    } else {
        ret = tr("day_template_with_year", day, month_str, date_year);
    }

    // old langs

    if (ret.startsWith("@")) {
        return date.toLocaleDateString(navigator.language);
    }

    return ret;
}

function get_attachments_list_from_lp(attachments) {
    // returns "photo1_2,video1_3" from {"attach1": "1_2", "attach1_type": "photo"}
    let temp_str = [];
    let i = 0;
    let associative = Object.entries(attachments);
    associative.forEach(item => {
        if (item[0].startsWith("attach")) {
            const _type = associative[i + 1];
            if (!_type || _type[0] == "from") {
                return;
            }

            temp_str.push(_type[1] + item[1]);
        }

        i += 1;
    });

    return temp_str;
}

async function resolve_attachments(attachments) {
    const atts = await window.OVKAPI.call("utils.resolveAttachments", {
        "attachments": attachments.join(',')
    });

    return atts;
}

function get_attachment_text(attachment) {
    f = (`<span class="conv_prev_attachment_text">(` + tr("preview_attachment_" + attachment.type) + ")</span>").toLowerCase();

    return f;
}

function unpack_attachments_into_node(textarea_node, attachments) {
    console.log(textarea_node, attachments)
    attachments.forEach(attachment => {
        const type = attachment.type
        const obj = attachment[type];
        if (!obj) {
            obj = attachment;
        }

        let aid = obj.owner_id + '_' + obj.id + (obj.access_key ? "_" + obj.access_key : "")

        if (type == 'video' || type == 'photo') {
            let preview = ''

            if(type == 'photo') {
                preview = obj.sizes[1].url
            } else {
                preview = obj.image[0].url
            }

            __appendToTextarea({
                'type': type,
                'preview': preview,
                'id': aid
            }, textarea_node)
        } else if(type == 'poll') {
            __appendToTextarea({
                'type': type,
                'alignment': 'vertical',
                'html': tr('poll'),
                'id': obj.id,
                'undeletable': true,
            }, textarea_node)
        } else if (type == 'wall') {
            __appendToTextarea({
                'type': type,
                'alignment': 'vertical',
                'html': tr('post'),
                'id': obj.id,
                'undeletable': true,
            }, textarea_node)
        } else {
            const found_block = post.find(`div[data-att_type='${type}'][data-att_id='${aid}']`)
            __appendToTextarea({
                'type': type,
                'alignment': 'vertical',
                'html': found_block.html(),
                'id': aid,
            }, textarea_node)
        }
    })
}

function nl2br(str) {
    return str.replace(/\n/g, '<br>');
}

function _authorize(items, profiles = null, groups = null, get_id = null, set_id = null, finalize = null) {
    let fin = [];

    items.forEach((item) => {
        const _id = get_id(item);
        let author = null;

        if (!profiles && !groups) {
            author = window.im.cached_profiles._findCachedProfileById(_id);
        } else {
            author = window.find_author(_id, profiles, groups);
        }

        set_id(item, author);

        if (finalize) {
            finalize(item, fin);
        }
    });

    if (finalize) {
        return fin;
    }
}
