Function.noop = () => {};

class CMessageBox {
    constructor(options = {}) {
        const title = options.title ?? 'Untitled'
        const body  = options.body ?? '<hr>'
        const buttons = options.buttons ?? []
        const callbacks = options.callbacks ?? []
        const close_on_buttons = options.close_on_buttons ?? true
        const unique_name = options.unique_name ?? null
        const warn_on_exit = options.warn_on_exit ?? false
        const custom_template = options.custom_template ?? null
        if(unique_name && window.messagebox_stack.find(item => item.unique_name == unique_name) != null) {
            return
        }

        this.title = title
        this.body  = body
        this.id    = random_int(0, 10000)
        this.close_on_buttons = close_on_buttons
        this.unique_name = unique_name
        this.warn_on_exit = warn_on_exit
        this._viewer = null;

        if(!custom_template) {
            u('body').addClass('dimmed').append(this.__getTemplate())
        } else {
            custom_template.addClass('ovk-msg-all')
            custom_template.attr('data-id', this.id)
            u('body').addClass('dimmed').append(custom_template)
        }
        
        u('html').attr('style', 'overflow-y:hidden')

        buttons.forEach((text, callback) => {
            this.getNode().find('.ovk-diag-action').append(u(`<button class="button">${text}</button>`))
            let button = u(this.getNode().find('.ovk-diag-action > button.button').last())
            button.on("click", (e) => {
                callbacks[callback]()

                if(close_on_buttons) {
                    this.close()
                }
            })
        })

        window.messagebox_stack.push(this)
        this._checkCount();
    }

    _checkCount() {
        if (window.messagebox_stack.length > 1) {
            u("body").addClass("manyMsgs");
        } else {
            u("body").removeClass("manyMsgs");
        }
    }

    __getTemplate() {
        return u(
        `<div class="ovk-diag-cont ovk-msg-all" data-id="${this.id}">
            <div class="ovk-diag">
                <div class="ovk-diag-head">${this.title}</div>
                <div class="ovk-diag-body">${this.body}</div>
                <div class="ovk-diag-action"></div>
            </div>
        </div>`)
    }

    getNode() {
        return u(`.ovk-msg-all[data-id='${this.id}']`)
    }

    async __showCloseConfirmationDialog() {
        return new Promise((resolve, reject) => {
            const msg = new CMessageBox({
                title: tr('exit_noun'),
                body: tr('exit_confirmation'),
                warn_on_exit: false,
                unique_name: 'close_confirmation',
                buttons: [tr('no'), tr('yes')],
                callbacks: [() => {
                    msg.close()
                    resolve(false)
                }, () => {
                    this.__exitDialog()
                    resolve(true)
                }]
            })
        })
    }

    __exitDialog() {
        this.getNode().remove()
        if(u('.ovk-msg-all:not(.msgbox-hidden)').length < 1) {
            u('body').removeClass('dimmed')
            u('html').attr('style', 'overflow-y:scroll')
        }

        const current_item  = window.messagebox_stack.find(item => item.id == this.id)
        const index_of_item = window.messagebox_stack.indexOf(current_item)
        window.messagebox_stack = array_splice(window.messagebox_stack, index_of_item)
        
        delete this
    }

    close() {
        this.__exitDialog()
        this._checkCount();
    }

    hide() {
        u('body').removeClass('dimmed')
        u('html').attr('style', 'overflow-y:scroll')
        this.getNode().attr('style', 'display: none;').addClass('msgbox-hidden')
        this.hidden = true
        this._checkCount();
    }

    reveal() {
        u('body').addClass('dimmed')
        u('html').attr('style', 'overflow-y:hidden')
        this.getNode().attr('style', 'display: block;')
        this.hidden = false
        this._checkCount();
    }

    static toggleLoader(state = null) {
        if (state == null) {
            u('#ajloader').toggleClass('shown')
            return;
        }

        if (state == true) {
            u('#ajloader').addClass('shown')
        } else {
            u('#ajloader').removeClass('shown')
        }
    }
}

window.messagebox_stack = []

function find_msgbox_by_node(node) {
    return find_msgbox_by_id(node.dataset.id);
}

function find_msgbox_by_id(msg_id) {
    let msg = null;
    window.messagebox_stack.forEach((item) => {
        if (item.id == msg_id) {
            msg = item;
            return;
        }
    });

    return msg;
}

function MessageBox(title, body, buttons, callbacks, return_msg = false, unique_name = null) {
    const msg = new CMessageBox({
        title: title,
        body: body,
        buttons: buttons,
        callbacks: callbacks,
        unique_name: unique_name
    })

    if(return_msg) {
        return msg
    }

    return msg.getNode()
}

// Close on 'Escape' key
u(document).on('keyup', async (e) => {
    if(e.keyCode == 27 && window.messagebox_stack.length > 0) {
        const msg = window.messagebox_stack[window.messagebox_stack.length - 1]
        if(!msg) {
            return
        }

        if(msg.close_on_buttons) {
            msg.close()
            return
        }

        if(msg.warn_on_exit) {
            const res = await msg.__showCloseConfirmationDialog()
            if(res === true) {
                msg.close()
            }
        }
    }
})

// Close when clicking on shadow
u(document).on('click', 'body.dimmed .dimmer', async (e) => {
    if(u(e.target).hasClass('dimmer')) {
        const msg = window.messagebox_stack[window.messagebox_stack.length - 1]
        if(!msg) {
            return
        }

        if(msg.close_on_buttons) {
            msg.close()
            return
        }

        if(msg.warn_on_exit) {
            const res = await msg.__showCloseConfirmationDialog()
            if(res === true) {
                msg.close()
            }
        }
    }
})

// Abstract viewer

class Viewer {
    viewer_name = "abstract_viewer";

    constructor() {
        this.av_modes = ["vk", "tg", "pptx"];
        this.mode = "vk";
        this.itemsOrder = [];
        this.items = {};
        this.currentId = null;
        this.context = {};
        this.totalItemsCount = null;
        this._cachedDetails = {};
        this.resetContext();

        /*

        Example:

        type: album
        album_id: x
        per_page: y ?? 10

        */

        this.modal = null;
        this.isSliding = false;
        this._draggable_ctx = null;
        this._resizeable_ctx = null;
    }

    get count() {
        return this.itemsOrder.length;
    }

    get currentIndex() {
        return this.itemsOrder.indexOf(this.currentId);
    }

    get currentItem() {
        return this.items[this.currentId];
    }

    setContext(data) {
        this._setMainContext(data);
    }

    initalizeContext() {}
    selectItem(pid, item_api_res) {}
    async selectItemByApiId(id) {
        const entry = this.items[id];
        if (!entry) {
            console.error("Msgboxes | " + this.viewer_name + " | Not found entry with id ", id)
            return;
        };

        console.log("selected item ", id, entry);

        await this.selectItem(id, entry);
    }
    _isLoadable() { return false; }
    async _loadLoadableContext(side) { return; }

    _setMainContext(data) {
        this.context.type = data.type;
        this.context.first_item_url = data.first_item_url || null;
        this.context.perPage = data.perPage || 20;
        this.context.offset = this._isLoadable()
        ? (Number((new URL(location.href)).searchParams.get('p') ?? (window.router.scroll_page ?? 1)) - 1) * this.context.perPage
        : 0;

        if (data.custom_offset != null) {
            this.context.offset = data.custom_offset;
        }

        this.context.reverse = data.reverse || false;
        this.context.custom_context = data.custom_context || null;
        this.context.id = data.id || null;
    }

    resetContext() {
        this.context = {};
        this.sides_ended = {
            "right": false,
            "left": false
        };
    }
    _appendApiItem(item, profiles = null, groups = null) {
        const existing = {};
        this.itemsOrder.forEach((id) => {
            existing[id] = true;
        });

        const pid = idForItem(item);
        if (existing[pid]) {
            return;
        }

        console.log(pid, item)
        this._appendItemToList(pid, item, profiles, groups);
        this.itemsOrder.push(pid);
    }

    // abstract method cuz every viewer has different display of its items
    _appendItemToList(pid, item) {
        this.items[pid] = {
            id: pid,
        };
    }

    createMsgbox() {}

    open() {
        if (!this.modal) {
            this.createMsgbox();
        }

        this.modal.reveal();
        this.modal._viewer = this;

        return this.modal.getNode();
    }
    afterOpen() {}

    close(close_type = 0) {
        switch (close_type) {
            case 0:
                console.log("Msgboxes | " + this.viewer_name + " | Closing")
                this.modal.close();
                break;
            case 1:
                console.log("Msgboxes | " + this.viewer_name + " | Hiding window")
                this.modal.hide();
                break;
        }
    }

    async slide(direction) {
        if (this.isSliding == true) {
            console.log("isSliding")
            return;
        }

        if (this.count <= 1) {
            console.error("noItems!!!!!!")
            return
        };

        this.isSliding = true;

        let idx = this.currentIndex + direction;

        if (idx < 0) {
            if (this._isLoadable()) {
                this.context.offset -= 20;
                if (this.context.offset < 0) {
                    this.context.offset = 0;
                    // to start
                };

                await this._loadLoadableContext(direction);
                idx = this.itemsOrder.length - 1;
            } else {
                idx = this.count - 1;
            }
        } else if (idx >= this.count) {
            if (this._isLoadable()) {
                this.context.offset += 20;
                await this._loadLoadableContext(direction);
                idx = 0;
            } else {
                idx = 0;
            }
        }

        const nextId = this.itemsOrder[idx];
        if (nextId) {
            await this.selectItemByApiId(nextId);
        }

        this.isSliding = false;
    }

    _getCurrentEntryCacheNode() {}
    _removeCacheForEntry(entry) {
        entry.cached = null;
    }

    _removeCacheForCurrentEntry() {
        let entry = this.currentItem;
        console.log(this.items, this.currentItem, entry)

        if (entry) {
            this._removeCacheForEntry(entry);
        }
    }

    _updDetailsUrlForCurrentEntry(url) {
        let entry = this.currentItem;

        console.log(entry, this)
        if (entry) {
            entry.postfix = url;
            entry.cached = null;

            this._removeDetails();
            this._loadDetails(this.currentId);
        } else {
            console.error("no entry")
        }
    }
    async loadNextDetailsPage(event) {
        await this._loadDetails(this.currentId, "pagination", event);
    }

    _removeDetails() {
        this.modal.getNode().find(".ovk-modal-details").html(`<img src="${_loader_link}">`);
    }

    _addCachedDetailsToEntry(entry, html) {
        entry.cached = html;
    }

    _getEntityPageName() {
        return "photo";
    }

    _getDetailsUrl(id, postfix) {
        let item_ids = idUrlFromArray(id);
        let str = new URL(location.origin + "/" + this._getEntityPageName() + item_ids);

        if (postfix != null) {
            postfix.forEach((value, key) => {
                str.searchParams.set(key, value);
            })
        }

        return str.toString();
    }

    // states

    _updFrame(item) {}
    async setCurrentEntryDeleted(state) {
        const el = this.currentItem;
        el.deleted = true;
        await this.deleteItem(el);
        this._updFrame(el);
    }
    async deleteItem(element) {}

    setMode(mode) {
        this.mode = mode;
        this.av_modes.forEach(el => {
            this.modal.getNode().removeClass("mode-"+el);
        })
        this.modal.getNode().addClass("mode-"+mode);
    }

    // оно должно превращать само окно в перетаскиваемый элемент а не создавать новый элемент
    _showMinimized(item) {
        this.modal.getNode().addClass("ovk-msg-minimized");
        u("body").removeClass("dimmed");
        u("html").attr("style", "")

        // jquery ui
        this._draggable_ctx  = $(this.modal.getNode().nodes[0]).draggable({
            cursor: 'grabbing', 
            containment: 'window', 
            cancel: '.miniplayer-body'
        });
        this._resizeable_ctx = $(this.modal.getNode().nodes[0]).resizable({
            maxHeight: 2000,
            maxWidth: 3000,
            minHeight: 150,
            minWidth: 200
        });
    }

    _returnFromMinimized() {
        u("body").addClass("dimmed");
        u("html").attr("style", "overflow-y: hidden;")
        this.modal.getNode().removeClass("ovk-msg-minimized");
        this.modal.getNode().attr("style", "");

        if (this._draggable_ctx != null) {
            this._draggable_ctx.destroy();
            this._resizeable_ctx.destroy();
            this._draggable_ctx = null;
            this._resizeable_ctx = null;
        }
    }

    isMinimized() {
        return this.modal.getNode().hasClass("ovk-msg-minimized");
    }

    // pagination

    _getPage(target) {
        const p = target.closest(".paginator");
        const l = p.querySelector("a.active");
        if (!l) {
            return;
        }
        target.classList.add("lagged");
        let next = l.nextElementSibling;
        const his_url = new URL(next.href);
        const his_page = his_url.searchParams.get("p");

        return [Number(his_page), next];
    }

    _appendDetailsAsPagination(next_btn, show_more_btn, details, entry) {
        let appends = 0;
        const ps5 = new DOMParser().parseFromString(details, "text/html");
        const counts = ps5.querySelectorAll(".scroll_node").length;
        ps5.querySelectorAll(".scroll_node").forEach(item => {
            if (appends == 0) {
                this.modal.getNode().find(".scroll_container").append(item);
            } else {
                this.modal.getNode().find(".scroll_container").before(item);
            }
        })

        const his_url = new URL(next_btn.href);
        his_url.searchParams.set("p", Number(his_url.searchParams.get("p")) + 1)
        if (next_btn) {
            next_btn.href = his_url;
        }

        if (counts < 10) {
            show_more_btn.remove();
        } else {
            show_more_btn.classList.remove("lagged");
        }

        this._addCachedDetailsToEntry(entry, this.modal.getNode().find(".ovk-photo-details").last().innerHTML)
    }
}
