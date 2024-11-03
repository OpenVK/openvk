Function.noop = () => {};

class CMessageBox {
    constructor(options = {}) {
        const title = options.title ?? 'Untitled'
        const body  = options.body ?? '<hr>'
        const buttons = options.buttons ?? []
        const callbacks = options.callbacks ?? []
        const close_on_buttons = options.close_on_buttons ?? true
        const unique_name = options.unique_name ?? null

        this.title = title
        this.body  = body
        this.id    = random_int(0, 10000)
        this.close_on_buttons = close_on_buttons
        this.unique_name = unique_name

        u('body').addClass('dimmed').append(this.__getTemplate())
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
        `<div class="ovk-diag-cont" data-id="${this.id}">
            <div class="ovk-diag">
                <div class="ovk-diag-head">${this.title}</div>
                <div class="ovk-diag-body">${this.body}</div>
                <div class="ovk-diag-action"></div>
            </div>
        </div>`)
    }

    getNode() {
        return u(`.ovk-diag-cont[data-id='${this.id}']`)
    }

    async __showCloseConfirmationDialog() {
        if(window.messagebox_stack.find(item => item.unique_name == 'close_confirmation') != null) {
            return
        }

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
        u(`.ovk-diag-cont[data-id='${this.id}']`).remove()
        if(u('.ovk-diag-cont').length < 1) {
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

        const res = await msg.__showCloseConfirmationDialog()
        if(res === true) {
            msg.close()
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

        const res = await msg.__showCloseConfirmationDialog()
        if(res === true) {
            msg.close()
        }
    }
})