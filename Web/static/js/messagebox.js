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
        if(u('.ovk-msg-all').length < 1) {
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
    }

    hide() {
        u('body').removeClass('dimmed')
        u('html').attr('style', 'overflow-y:scroll')
        this.getNode().attr('style', 'display: none;')
    }

    reveal() {
        u('body').addClass('dimmed')
        u('html').attr('style', 'overflow-y:hidden')
        this.getNode().attr('style', 'display: block;')
    }

    static toggleLoader() {
        u('#ajloader').toggleClass('shown')
    }
}

window.messagebox_stack = []

function MessageBox(title, body, buttons, callbacks, return_msg = false) {
    const msg = new CMessageBox({
        title: title,
        body: body,
        buttons: buttons,
        callbacks: callbacks,
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
