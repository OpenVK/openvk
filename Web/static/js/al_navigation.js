u(document).on('focusin click', '#search_box input[type="search"], .header-search-box input[type="search"]', (e) => {
    u('.page_header').addClass('search_expanded')
})

u(document).on('focusout', '#search_box input[type="search"], .header-search-box input[type="search"]', (e) => {
    if (window.openvk.at_search) {
        return
    }

    setTimeout(() => {
        if (document.activeElement && document.activeElement.closest('.header-search-box, #search_box')) {
            return
        }

        u('.page_header').removeClass('search_expanded')
        u('#searchBoxFastTips').removeClass('shown')
    }, 200)
})

u(document).on('click', (e) => {
    if (window.openvk.at_search) {
        return
    }

    if (!e.target.closest('.header-search-box, #search_box')) {
        u('.page_header').removeClass('search_expanded')
        u('#searchBoxFastTips').removeClass('shown')
    }
})

u(document).on('keydown', (e) => {
    if (e.keyCode === 27) {
        if (window.openvk.at_search) {
            return
        }
        if (u('.page_header').hasClass('search_expanded')) {
            u('.page_header').removeClass('search_expanded')
            u('#searchBoxFastTips').removeClass('shown')
            const inp = document.querySelector('.header-search-box input[type="search"]')
            if (inp) inp.blur()
        }
    }
})

u(document).on('click', '.search_option_name', (e) => {
    const target = e.target.closest('.search_option')
    // 🤪
    $(target.querySelector('.search_option_content')).slideToggle(250, "swing");
    setTimeout(() => {
        u(target).toggleClass('search_option_hidden')
    }, 250)
})

u(document).on('click', '#search_reset', (e) => {
    u(`.page_search_options input[type='text']`).nodes.forEach(inp => {
        inp.value = ''
    })

    u(`.page_search_options input[type='checkbox']`).nodes.forEach(chk => {
        chk.checked = false
    })

    u(`.page_search_options input[type='radio']`).nodes.forEach(rad => {
        if (rad.dataset.default) {
            rad.checked = true
            return
        }

        rad.checked = false
    })

    u(`.page_search_options select`).nodes.forEach(sel => {
        sel.value = sel.dataset.default
    })
})

u(`#search_box input[type='search']`).on('input', async (e) => {
    if (window.openvk.at_search) {
        return
    }

    const query = u(`#search_box input[type='search']`).nodes[0].value
    await new Promise(r => setTimeout(r, 1000));
    const current_query = u(`#search_box input[type='search']`).nodes[0].value
    const section = u(`#search_box select[name='section']`).nodes[0].value
    let results = null
    if (/*query.length < 2 || */query != current_query || ['users', 'groups', 'videos', 'audios_playlists'].indexOf(section) == -1) {
        return
    }

    console.info('Ok, getting tips.')

    switch (section) {
        case 'users':
            results = await fetch(`/method/users.search?auth_mechanism=roaming&q=${encodeURIComponent(query)}&count=10&sort=4&fields=photo_50,status,nickname`)
            break
        case 'groups':
            results = await fetch(`/method/groups.search?auth_mechanism=roaming&q=${encodeURIComponent(query)}&count=10&sort=4&fields=photo_50,description`)
            break
        case 'videos':
            results = await fetch(`/method/video.search?auth_mechanism=roaming&q=${encodeURIComponent(query)}&count=10&sort=4&extended=1`)
            break
        case 'audios_playlists':
            results = await fetch(`/method/audio.searchAlbums?auth_mechanism=roaming&query=${encodeURIComponent(query)}&limit=10`)
            break
    }

    json_result = await results.json()
    if (!json_result || json_result.error) {
        console.error(json_result.error)
        return
    }

    json_result = json_result.response
    if (json_result.count < 1) {
        console.info('No tips available.')
        return
    }

    switch (section) {
        case 'users':
            json_result['items'].forEach(item => {
                item['name'] = `${item['first_name']}${item['nickname'] ? ` (${item['nickname']})` : ''} ${item['last_name']}`
                item['description'] = item['status']
                item['url'] = '/id' + item['id']
                item['preview'] = item['photo_50']
            })
            break
        case 'groups':
            json_result['items'].forEach(item => {
                item['url'] = '/club' + item['id']
                item['preview'] = item['photo_50']
            })
            break
        case 'audios_playlists':
            json_result['items'].forEach(item => {
                item['name'] = item['title']
                item['url'] = '/playlist' + item['owner_id'] + '_' + item['id']
                item['preview'] = item['cover_url']
            })
            break
        case 'videos':
            const profiles = json_result['profiles']
            const groups = json_result['groups']
            json_result['items'].forEach(item => {
                item['name'] = item['title']
                item['url'] = `/video${item['owner_id']}_${item['id']}`
                item['preview'] = item['image'][0]['url']

                if (item['owner_id'] > 0) {
                    const profile = profiles.find(prof => prof.id == item['owner_id'])
                    if (!profile) { return }
                    item['description'] = profile['first_name'] + ' ' + profile['last_name']
                } else {
                    const group = groups.find(grou => grou.id == Math.abs(item['owner_id']))
                    if (!group) { return }
                    item['description'] = group['name']
                }
            })
            break
    }

    u('#searchBoxFastTips').addClass('shown')
    u('#searchBoxFastTips').html('')
    json_result.items.forEach(item => {
        const id = idForItem(item);
        u('#searchBoxFastTips').append(`
            <a href='${item['url']}' ${section == 'videos' ? `onclick="VideoViewer.openById('${id}')"` : ''}>
                <img src='${item['preview']}' class='search_tip_preview_block'>
                <div class='search_tip_info_block'>
                    <b>${ovk_proc_strtr(item['name'].escapeHtml(), 50)}</b>
                    <span>${ovk_proc_strtr((item['description'] ?? '').escapeHtml(), 60)}</span>
                </div>
            </a>
        `)
    })
})

u(document).on('keydown', `#search_box input[type='search'], #searchBoxFastTips a`, (e) => {
    const u_tips = u('#searchBoxFastTips a')
    if (u_tips.length < 1) {
        return
    }

    const focused = u('#searchBoxFastTips a:focus').nodes[0]

    // up
    switch (e.keyCode) {
        case 38:
            e.preventDefault()
            if (!focused) {
                u_tips.nodes[0].focus()
                return
            }

            if (focused.previousSibling) {
                focused.previousSibling.focus()
            }

            break
        // down
        case 40:
            e.preventDefault()
            if (!focused) {
                u_tips.nodes[0].focus()
                return
            }

            if (focused.nextSibling) {
                focused.nextSibling.focus()
            } else {
                u_tips.nodes[0].focus()
            }

            break
    }
})

window.headPlayPause = async function (e) {
    if (e) {
        if (e.preventDefault) e.preventDefault()
        if (e.stopPropagation) e.stopPropagation()
        if (e.stopImmediatePropagation) e.stopImmediatePropagation()
        e.cancelBubble = true
        e.returnValue = false
    }

    if (!window.player) {
        return false
    }

    // 1. If player already has a track loaded
    if (window.player.currentTrack) {
        if (window.player.audioPlayer && !window.player.audioPlayer.paused) {
            window.player.pause()
            u('#head_play_btn').removeClass('playing')
        } else {
            window.player.ajReveal()
            await window.player.play()
            u('#head_play_btn').addClass('playing')
        }
        return false
    }

    // 2. If player has no track loaded yet: load user's audios and play the first track
    const userId = (window.openvk && window.openvk.current_id) ? window.openvk.current_id : 0
    if (!userId) {
        return false
    }

    u('#head_play_btn').addClass('playing')

    try {
        window.player.context = {
            object: {
                name: 'entity_audios',
                entity_id: userId,
                page: 1,
                url: '/audios' + userId
            },
            playedPages: [],
            pagesCount: 1,
            count: 0
        }
        window.player.tracks = []

        await window.player.loadContext(1)

        if (window.player.tracks && window.player.tracks.length > 0) {
            window.player.ajReveal()
            await window.player.setTrack(window.player.tracks[0].id)
            await window.player.play()
            window.player.__updateFace()
            u('#head_play_btn').addClass('playing')
        } else {
            u('#head_play_btn').removeClass('playing')
            if (typeof makeError === 'function') {
                makeError('У вас пока нет аудиозаписей', 'Red', 4000)
            }
        }
    } catch (err) {
        console.error('Error starting player from header:', err)
        u('#head_play_btn').removeClass('playing')
    }

    return false
}

u(document).on('click', '#head_play_btn', (e) => {
    e.preventDefault()
    e.stopPropagation()
    if (e.stopImmediatePropagation) e.stopImmediatePropagation()
    e.cancelBubble = true
    e.returnValue = false
    window.headPlayPause(e)
    return false
})

