//import { IMTab, IMPage } from './page.js';
const { IMTab, IMPage } = await es6import_Im(import.meta.url, './page.js');
//import { FriendsPageTemplate } from '../components/extra.js';
const { FriendsPageTemplate } = await es6import_Im(import.meta.url, '../components/extra.js');
//import { html, render } from '../components/render.js';
const { html, render } = await es6import_Im(import.meta.url, '../components/render.js');
// import { ChatGeneralForm } from '../components/messages.js';
const { ChatGeneralForm } = await es6import_Im(import.meta.url, "../components/messages.js");

export class FriendsPage extends IMPage {
    constructor() {
        super();

        this.has_inited = false;
        this.selected_friends = [];
        this.name = "";
        this.friends_class = window.im.friends;
        this._set_name = false;
        this._q = null;
    }

    static getPageId() { return "friends"; }
    shouldCloseOnExit() { return true; }
    isSelected(peer) {
        const id = peer?.id ?? peer;
        return this.selected_friends.some(p => (p?.id ?? p) === id);
    }
    _updTitleStr(name) { this.getNode().find("#_name").html(ovk_proc_strtr(escapeHtml(name), 100)); }
    onFriendClick(e, peer) {
        if (this.options.referrer == "chat_creation" || this.options.referrer == "add_new") {
            if (peer.can && peer.can("invite") === false) {
                fastError(tr("error_user_forbid_invites"));
                return;
            }

            const id = peer?.id ?? peer;
            const idx = this.selected_friends.findIndex(p => (p?.id ?? p) === id);

            if (idx === -1) {
                this.selected_friends.push(peer);
            } else {
                this.selected_friends.splice(idx, 1);
            }

            if (this.options.referrer == "chat_creation") {
                if (this._set_name == false) {
                    let n = [];
                    this.selected_friends.forEach(p => {
                        n.push(p.getName ? p.getName(false, false) : p.name);
                    });

                    if (n.length > 0) {
                        this._updTitleStr(n.slice(0, 4).join(", "));
                    } else {
                        this._updTitleStr("...");
                    }
                }

                this.getNode().find("#_m_count").html(tr("members_count", this.selected_friends.length + 1));
            }

            this.update();
            return;
        }

        window.im.messenger.selectConversationByPeerId(peer.id);
    }

    onTitleChangeClick(e) {
        const msg = new CMessageBox({
            title: tr("name_your_chat"),
            body: `<div><input id="chatInputTitle" type="text"></div>`,
            close_on_buttons: false,
            buttons: [tr('ok'), tr('cancel')],
            callbacks: [() => {
                const name = document.querySelector("#chatInputTitle").value;
                if (!name || name.length == 0) { return; }

                this.name = name;
                this._set_name = true;
                this._updTitleStr(this.name);
                msg.close();
            }, () => {msg.close()}]
        });
    }

    async onCreateChat(e) {
        toggleUnclickability(e.target, true);

        const ids = [];
        this.selected_friends.forEach(peer => {
            ids.push(peer.id || peer);
        });

        if (this.options.referrer == "add_new") {
            if (ids.length === 0) {
                fastError(tr("error_chat_not_enough_friends") || "Выберите хотя бы одного друга");
                toggleUnclickability(e.target, false);
                return;
            }

            try {
                await window.OVKAPI.call("messages.addChatUser", {
                    "peer_id": this.options.convo_id,
                    "user_id": ids.join(",")
                });
                await window.im.messenger.selectConversationByPeerId(this.options.convo_id);
            } catch (err) {
                fastError(String(err));
            } finally {
                toggleUnclickability(e.target, false);
            }
            return;
        }

        let title = this._set_name ? this.name : "";

        if (ids.length < 0) {
            fastError(tr("error_chat_not_enough_friends"));
            toggleUnclickability(e.target, false);
            return;
        }

        try {
            const resp = await window.OVKAPI.call('messages.createChat', {
                'title': title,
                'user_ids': ids,
            });
            await window.im.messenger.selectConversationByPeerId(resp + 2000000000);
        } catch (err) {
            fastError(String(err));
        } finally {
            toggleUnclickability(e.target, false);
        }
    }

    onSearch(e) {
        const q = e.target.value;

        setTimeout(async () => {
            if (q == null || q == "" || q.length == 0) {
                this.friends_class = window.im.friends;
                this.update();
                return;
            } else {
                this.friends_class = new LarpFriends();
            }

            this.container.classList.add("lagged");
            if (e.target.value == q) {
                this.friends_class.query = q;
                await this.friends_class.loadFriends();
                this.friends_class.inited = true;
                this.update();
            }
            this.container.classList.remove("lagged");
        }, 200)
    }

    async beforeRender(container) {
        if (!this.has_inited) {
            this.selected_friends = [];
            this.has_inited = true;
        }
        if (this.friends_class.inited == false) {
            await this.friends_class.loadFriends();
            this.friends_class.inited = true;
        }
    }

    render(container) {
        const ref = this.options.referrer;
        this.getNode().addClass("page-other");

        render(html`
        <${FriendsPageTemplate}
            friends=${this.friends_class.items}
            count=${this.friends_class.total_count}
            referrer=${ref}
            onFriendClick=${(e, peer) => this.onFriendClick(e, peer)}
            onSubmit=${(e) => this.onCreateChat(e)}
            isSelected=${(peer) => this.isSelected(peer)}
            onLoadMore=${() => this.friends_class.loadNext()}
            onTitleChangeClick=${(e) => { this.onTitleChangeClick(e) }}
            onSearch=${(e) => { this.onSearch(e) }}
        />
        `, container);
    }
}

export class Friends {
    constructor() {
        this.items = [];
        this.inited = false;
        this.total_count = null;
        this.last_offset = 0;
        this.perPage = 100;
        this.query = null;
    }

    async loadFriends(offset = 0) {
        const res = await window.OVKAPI.call('friends.get', {
            offset: offset,
            count: this.perPage,
            fields: ChatGeneralForm.BASE_FIELDS,
        });

        this.last_offset = offset;
        if (this.total_count == null) {
            this.total_count = res.count;
        }

        res.items.forEach(item => {
            this.items.push(new ChatGeneralForm(item));
        })
    }

    async loadNext() {
        await this.loadFriends(this.last_offset + this.perPage);
    }
}

class LarpFriends extends Friends {
    async loadFriends(offset = 0) {
        let res = null;

        res = await window.OVKAPI.call('friends.search', {
            q: this.query,
            offset: offset,
            count: this.perPage,
            fields: ChatGeneralForm.BASE_FIELDS,
        });

        this.last_offset = offset;
        if (this._total_count == null) {
            this._total_count = res.count;
        }

        res.items.forEach(item => {
            this.items.push(new ChatGeneralForm(item));
        })
    }
}