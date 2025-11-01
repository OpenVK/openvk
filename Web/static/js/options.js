class TweakOption {
    constructor(name, function_callback, enabled_by_default = false) {
        this.name = name
        this.func = function_callback
        this.enabled = enabled_by_default
    }

    isEnabled() {
        const key = localStorage.getItem("tw."+this.name)
        if (!key) {
            return this.enabled == true
        }

        return key == 1
    }

    set(val = 1) {
        localStorage.setItem("tw."+this.name, val)
    }

    customCSS(css) {
        if (u(`style#css_${this.name}`).length > 0) {
            return
        }

        document.body.append(Object.assign(document.createElement("style"), {
            id: "css_"+this.name,
            type: "text/css",
            textContent: css
        }))
    }
}

function addScrollHook(func) {
    if (window.__scrollHook != null) {
        const old_hook = window.__scrollHook
        window.__scrollHook = () => {
            func()
            old_hook()
        }
    } else {
        window.__scrollHook = () => {func()}
    }
}

window.tweaks = [
    new TweakOption('wall.remove_counter', function () {
        this.customCSS(`
            .wall_block_counter { display:none; }
        `)
    }),
    new TweakOption('navigation.hide_links', function () {
        this.customCSS(`
            .sidebar .navigation a[target='_blank'] { display:none; }
        `)
    }),
    new TweakOption('navigation.remove_blocks', function () {
        this.customCSS(`
            #news {display: none;}
            #votesBalance {display: none;}
            .psa-poster {display: none;}
        `)
    }),
    new TweakOption('navigation.float_bar.remove', function () {
        this.customCSS(`
            .floating_sidebar {
                display: none !important;
                opacity: 0;
            }
        `)
    }),
    new TweakOption('profile.remove_hints', function () { this.customCSS(`.profile-hints a {display: none;}`)}),
    new TweakOption('navigation.header.hoverable', function () {
        this.customCSS(`
            .page_header:not(.search_expanded_at_all) .header_navigation {
                opacity: 0;
                transition: opacity 150ms ease-in;
            }

            .page_header:hover .header_navigation {opacity: 1;}
        `)
    }),
    new TweakOption('navigation.blocks.disable_hiding', function () {
        window.hidePanel = () => {}
    }),
    new TweakOption('navigation.footer.remove_credits', function () {
        this.customCSS(`.page_footer p {display: none;}`)
    }),
    new TweakOption('navigation.footer.remove_links', function () {
        this.customCSS(`.navigation_footer {display: none;}`)
    }),
    new TweakOption('navigation.remove_edit_button', function () {
        this.customCSS(`.navigation .edit-button {display: none;}`)
    }),
    new TweakOption('navigation.remove_toup', function () {
        this.customCSS(`.toTop {display: none;}`)
    }),
    new TweakOption('navigation.hide_counters', function () {
        this.customCSS(`.linkunderline {opacity: 0.1;}`)
    }),
    new TweakOption('navigation.remove_admin', function () {
        this.customCSS(`a[href='/admin'], a[href='/support/tickets'], a[href='/scumfeed'], a[href='/noSpam'] { display: none !important;}`)
        u(u('.navigation .menu_divider').nodes[2]).remove()
    }),
    new TweakOption('navigation.remove_pinned', function () {
        this.customCSS(`#_groupListPinnedGroups {display:none;}`)
        u(u('.navigation .menu_divider').nodes[1]).remove()
    }),
    new TweakOption('user.hide_rating', function () {
        this.customCSS(`.profile-hints {display:none;}`)
        u(".left_small_block br").remove()
    }),
    new TweakOption('wall.disable_iframe_youtube', function () {
        u(".attachment iframe").nodes.forEach(item => {
            const url = item.src
            const urls = url.split("/")
            const id = urls[urls.length - 1]
            const thumbnail = `https://i.ytimg.com/vi/${id}/hqdefault.jpg`
            const ovk_video_id = item.closest(".attachment").querySelector(".video-wowzer a").href.split("/video")
            console.log(ovk_video_id)

            u(item).closest(".attachment").nodes[0].insertAdjacentHTML('afterBegin', `
                <a id="videoOpen" data-id="${ovk_video_id[1]}" style="display:flex;flex-direction:column;" target="_blank" href="https://youtu.be/${id}">
                    <b>YouTube Video:</b>
                    <img src="${thumbnail}">
                </a>
            `)
            u(item).remove()
        })
    }),
    new TweakOption('bg.hide', function () {
        this.customCSS(`#backdrop {display:none;}`)
    }),
    new TweakOption('bg.expand', function () {
        this.customCSS(`#backdropDripper {background-color: #ffffffdb;opacity: 0.8;}`)
    }),
    new TweakOption('wall.remove_online', function () {
        this.customCSS(`.post-online {display:none;}`)
    }),
    new TweakOption('listview.remove_borders', function () {
        this.customCSS(`
            .container_gray.scroll_container {
                background: unset;
                padding: 10px 10px 4px 10px;
                display: flex;
                flex-direction: column;
                gap: 10px;
            }

            .container_gray.scroll_container .content {
                background: unset;
                border: unset;
                padding: 0px;
                margin-bottom: unset;
            }

            .container_gray.scroll_container .content table {
                border-spacing: 6px 0px;
                margin: 0px -6px;
            }
        `)

        u(".no_scroll_container").removeClass("no_scroll_container").addClass("scroll_container")
    }),
    new TweakOption('listview.hide_paginator_blinks', function () {
        this.customCSS(`.paginator:not(.paginator-at-top) {opacity:0.2}`)
    }),
    new TweakOption('listview.hide_paginator_blinks', function () {
        this.customCSS(`.paginator:not(.paginator-at-top) {opacity:0.2}`)
    }),
    new TweakOption("apps.expand_iframe", function () {
        this.customCSS(`
            .app_block {
                resize: both;
                overflow: auto;
                text-align: unset;
                background: white;
            }

            .app_block #appFrame {
                width: 100%;
                height: 100%;
            }
        `)
        u("#appFrame").closest("center").addClass("app_block")
    }),
    new TweakOption("wall.hide_edit_mark", function () {
        this.customCSS(`.editedMark {display: none;}`)
    }),
    new TweakOption("wall.hide_edit_mark", function () {
        this.customCSS(`.editedMark {display: none;}`)
    }),
    new TweakOption("settings.hide_phone_number_message", function () {
        u(`.msg a[href='/edit/verify_phone']`).closest(".msg").remove()
    }),
    new TweakOption("settings.hide_phone_number_message", function  () {
        u(`.msg a[href='/edit/verify_phone']`).closest(".msg").remove()
    }),
    new TweakOption("user.counters_as_links", function () {
        if(u(".left_small_block .avatar_block").length > 0) {
            u(".left_small_block > div, .right_big_block > div").nodes.forEach(item => {
                if (item.matches(".profile-hints, #profile_links, .avatar_block, .page_info")) {
                    return
                }

                const name = item.querySelector(".content_subtitle")
                if (!name) {return}
                const counter = name.childNodes[0].textContent
                const _a = name.querySelectorAll('a')
                const link = _a[_a.length - 1].href
                const _text = counter.trim()
                if (!_text) {return}

                u("#profile_links").append(`
                    <div id="profile_link">
                        <a href="${link}" style="width: 100%;display: block;">${_text}</a>
                    </div>
                `)
                u(item).remove()
            })
        }
    }),
    new TweakOption("wall.show_as_diary", function () {
        this.customCSS(`
            .same_author_as_previous > tbody > tr > .post-author-ava .post-author-url {
                display:none;
            }

            .same_author_as_previous > tbody > tr > .post-author-ava span {
                display: block;
                width: 50px;
            }

            .same_author_as_previous > tbody > tr > td > .post-author .post-author-name {
                display: none;
            }
        `)

        const wall_compact = () => {
            u(".post").nodes.forEach(item => {
                if (u(item).hasClass("same_author_as_previous")) {return}
                if (u(item).hasClass("comment") && u(item).closest(".post-menu-s").length > 0) {return}
                if (item.closest(".attachment") != null) {return}
                const scroll_node = item.closest(".scroll_node")
                const prev_post = scroll_node.previousElementSibling
                if (prev_post == null) {return}
                const author_1 = scroll_node.querySelector(".post > tbody > tr > .post-author-ava a")
                const author_2 = prev_post.querySelector(".post > tbody > tr > .post-author-ava a")
                if (!author_1 || !author_2) {return}
                if (author_1.href == author_2.href) {
                    u(item).addClass("same_author_as_previous")
                    u(item).find(".post-author-ava").append("<span></span>")
                    //u(item).find(".post-author-ava").append(u(item).find(".post-menu .date"))
                }
            })
        }

        addScrollHook(wall_compact)
        wall_compact()
    }),
    new TweakOption("wall.words_censor", function () {
        this.customCSS(`
            .hidden_because_of_word {
                display: none;
            }
        `)

        function hide_posts() {
            u(".post").nodes.forEach(item => {
                const post = u(item)
                if (post.hasClass("hidden_because_of_word")) {return}
                (window.hidden_words ?? []).forEach(word => {
                    highlightText(word, '.scroll_container', [".post-author a", ".post:not(.comment) > tbody > tr > td > .post-content > .text .really_text"])
                })
                if (post.find(".highlight").length > 0) {post.addClass("hidden_because_of_word")}
            })
        }

        addScrollHook(hide_posts)
        hide_posts()
    }),
]

window.openPluginSettings = () => {
    const msg = new CMessageBox({
        title: "Settings",
        body: `
                <span>reload page to apply settings</span>
                <div style="display:flex;flex-direction:column;" id="plugin_settings"></div>
        `,
        buttons: ['Close'],
        callbacks: [() => {}]
    })
    const settings = msg.getNode().find("#plugin_settings")
    tweaks.forEach(tweak => {
        settings.append(`<label><input type="checkbox" ${tweak.isEnabled() ? "checked": ""}><span>${escapeHtml(tweak.name)}</span<</label>`)
    })

    settings.on("change", "input", (e) => {
        const _name = e.target.closest('label').querySelector("span").innerHTML
        localStorage.setItem("tw."+_name, Number(e.target.checked))
    })
}
