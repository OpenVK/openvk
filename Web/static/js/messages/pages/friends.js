class FriendsTab {
    constructor() {
        this.friends = [];
        this.total_count = null;
        this.has_inited = false;
        this.last_offset = 0;
        this.has_appeared = false;

        this.referrer = null;
        this.selected_friends = [];
    }

    async loadFriends(offset = 0, count = 10) {
        let res = await window.OVKAPI.call('friends.get', {
            offset: offset,
            count: 100,
            fields: ChatGeneralForm.base_fields,
        });

        this.last_offset = offset;
        if (this.total_count == null) {
            this.total_count = res.count;
        }

        res.items.forEach(item => {
            this.friends.push(new ChatGeneralForm(item));
        })
    }

    onFriendClick(e, peer) {
        if (this.referrer == "chat_creation") {
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

        window.im.setChatByPeerId(peer.id);
    }

    isSelected(peer) {
        return this.selected_friends.indexOf(peer.id) != -1;
    }

    onCreateChat(e) {
        e.target.classList.add("lagged");

        const ids = this.selected_friends;

        // пустые беседы нужны!!
        if (ids.length < 0) {
            fastError(tr("error_chat_not_enough_friends"));
            e.target.classList.remove("lagged");
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
                    e.target.classList.remove("lagged");
                    msg.close();

                    window.im.setChatByPeerId(resp + 2000000000);
                }).catch(err => {
                    fastError(String(err));
                });
            }, () => {msg.close()}]
        })

    }

    async loadNext() {
        await this.loadFriends(this.last_offset + 10);
    }

    _appear(container) {
        this._render(container);
    }

    _render(container) {
        const ref = this.referrer;

        preactRender(html`
        <${FriendsPage}
            friends=${this.friends}
            count=${this.total_count}
            referrer=${ref}
            onFriendClick=${(e, peer) => this.onFriendClick(e, peer)}
            onCreateChat=${(e) => this.onCreateChat(e)}
            isSelected=${(peer) => this.isSelected(peer)}
            onLoadMore=${() => this.loadNext()}
        />
        `, container);
    }

    appear(container, referrer = null) {
        this.referrer = referrer;
        this.selected_friends = []; // nulling

        container.classList.remove('hidden');

        if (this.has_inited == false) {
            this.loadFriends().then(() => {
                this.has_inited = true;
                this._appear(container)
            })
        } else {
            this._appear(container)
        }
    }
}
