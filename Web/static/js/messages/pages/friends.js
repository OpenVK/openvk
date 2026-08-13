import { IMTab, IMPage } from './page.js';
import { FriendsPageTemplate } from '../components/extra.js';
import { html, render } from '../components/render.js';
import { ChatGeneralForm } from '../components/messages.js';

export class FriendsPage extends IMPage {
    constructor() {
        super();

        this.has_inited = false;
        this.selected_friends = [];
    }

    static getPageId() { return "friends"; }
    shouldCloseOnExit() { return true; }

    onFriendClick(e, peer) {
        if (this.options.referrer == "chat_creation") {
            const id = peer.id;
            const t = e.target;
            const f = t.closest(".friends-list-item");

            if (peer.canBeInvitedBy() == false) {
                makeError(tr("error_user_forbid_invites"), 'Red', 10000, 'forbid_invites' + peer.id);
                f.querySelector('input').checked = false;
                return;
            }

            if (this.selected_friends.indexOf(id) == -1) {
                this.selected_friends.push(id);
                f.classList.add("friends-selected");
                f.querySelector('input').checked = true;
            } else {
                this.selected_friends = this.selected_friends.filter(item => item !== id);
                f.classList.remove("friends-selected");
                f.querySelector('input').checked = false;
            }

            console.log(e, this.selected_friends)
            return;
        }

        window.im.messenger.selectConversationByPeerId(peer.id);
    }

    isSelected(peer) {
        return this.selected_friends.indexOf(peer.id) != -1;
    }

    onCreateChat(e) {
        toggleUnclickability(e.target, true);

        const ids = this.selected_friends;

        // пустые беседы нужны!!
        if (ids.length < 0) {
            fastError(tr("error_chat_not_enough_friends"));
            toggleUnclickability(e.target, false);
            return;
        }

        const msg = new CMessageBox({
            title: tr("create_chat"),
            body: `<div><span>${tr('name_your_chat')}</span><input id="chatInputTitle" type="text"></div>`,
            close_on_buttons: false,
            buttons: [tr('create'), tr('cancel')],
            callbacks: [() => {
                let title = '';
                title = document.querySelector("#chatInputTitle").value;
                window.OVKAPI.call('messages.createChat', {
                    'title': title,
                    'user_ids': ids,
                }).then((resp) => {
                    toggleUnclickability(e.target, false);
                    msg.close();

                    window.im.setChatByPeerId(resp + 2000000000);
                }).catch(err => {
                    fastError(String(err));
                });
            }, () => {msg.close()}]
        })
    }

    async beforeRender(container) {
        if (window.im.friends.inited == false) {
            await window.im.friends.loadFriends();
            window.im.friends.inited = true;
        }
    }

    render(container) {
        const ref = this.options.referrer;
        this.selected_friends = []; // nulling

        render(html`
        <${FriendsPageTemplate}
            friends=${window.im.friends.items}
            count=${window.im.friends.total_count}
            referrer=${ref}
            onFriendClick=${(e, peer) => this.onFriendClick(e, peer)}
            onCreateChat=${(e) => this.onCreateChat(e)}
            isSelected=${(peer) => this.isSelected(peer)}
            onLoadMore=${() => window.im.friends.loadNext()}
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
    }

    async loadFriends(offset = 0) {
        const res = await window.OVKAPI.call('friends.get', {
            offset: offset,
            count: this.perPage,
            fields: ChatGeneralForm.base_fields,
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
