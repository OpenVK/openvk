import { IMTab, IMPage } from './page.js';
import { FriendsPageTemplate } from '../components/extra.js';
import { html, render } from '../components/render.js';
import { ChatGeneralForm } from '../components/messages.js';

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
    isSelected(peer) { return this.selected_friends.indexOf(peer) != -1; }
    _updTitleStr(name) { this.getNode().find("#_name").html(ovk_proc_strtr(escapeHtml(name), 100)); }
    onFriendClick(e, peer) {
        if (this.options.referrer == "chat_creation" || this.options.referrer == "add_new") {
            const id = peer.id;
            const t = e.target;
            const f = t.closest(".friends-list-item");

            if (peer.canBeInvitedBy() == false) {
                fastError(tr("error_user_forbid_invites"));
                f.querySelector('input').checked = false;
                return;
            }

            if (this.selected_friends.indexOf(peer) == -1) {
                this.selected_friends.push(peer);
                f.classList.add("friends-selected");
                f.querySelector('input').checked = true;
            } else {
                this.selected_friends = this.selected_friends.filter(item => item !== peer);
                f.classList.remove("friends-selected");
                f.querySelector('input').checked = false;
            }

            if (this._set_name == false) {
                let n = [];
                this.selected_friends.forEach(peer => {
                    n.push(peer.name);
                });

                if (n.length > 0) {
                    this._updTitleStr(n.slice(0, 4).join(", "));
                } else {
                    this._updTitleStr("...");
                }
            }

            this.getNode().find("#_m_count").html(tr("members_count", this.selected_friends.length + 1));

            return;
        }

        if (this.options.referrer == "add_new") {
            const convo_id = this.options.convo_id;
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

    onCreateChat(e) {
        toggleUnclickability(e.target, true);

        let title = "empty name todo";
        if (this._set_name == true) {
            title = this.name;
        }

        const ids = [];
        this.selected_friends.forEach(peer => {
            ids.push(peer.id);
        })

        if (this.options.referrer == "add_new") {
            window.OVKAPI.call("messages.addChatUser", {
                "peer_id": this.options.convo_id,
                "user_id": ids.join(",")
            });
            window.im.messenger.selectConversationByPeerId(this.options.convo_id);
            toggleUnclickability(e.target, false);
            return;
        }

        // пустые беседы нужны!!
        if (ids.length < 0) {
            fastError(tr("error_chat_not_enough_friends"));
            toggleUnclickability(e.target, false);
            return;
        }

        window.OVKAPI.call('messages.createChat', {
            'title': title,
            'user_ids': ids,
        }).then((resp) => {
            window.im.messenger.selectConversationByPeerId(resp + 2000000000);
        }).catch(err => {
            fastError(String(err));
        });

        toggleUnclickability(e.target, false);
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
        if (this.friends_class.inited == false) {
            await this.friends_class.loadFriends();
            this.friends_class.inited = true;
        }
    }

    render(container) {
        const ref = this.options.referrer;
        this.selected_friends = []; // nulling

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