u(`#search_box form input[type="search"]`).on('focus', (e) => {
    u('.page_header').addClass('search_expanded')
})

u(`#search_box form input[type="search"]`).on('blur', (e) => {
    if(window.openvk.at_search) {
        return
    }

    setTimeout(() => {
        if(document.activeElement.closest('.page_header')) {
            return
        }

        u('.page_header').removeClass('search_expanded')
    }, 4000)
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
        if(rad.dataset.default) {
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
    if(window.openvk.at_search) {
        return
    }
    
    const query = u(`#search_box input[type='search']`).nodes[0].value
    await new Promise(r => setTimeout(r, 1000));
    const current_query = u(`#search_box input[type='search']`).nodes[0].value
    const section = u(`#search_box select[name='section']`).nodes[0].value
    let results = null
    if(/*query.length < 2 || */query != current_query || ['users', 'groups', 'videos', 'audios_playlists'].indexOf(section) == -1) {
        return
    }

    console.info('Ok, getting tips.')

    switch(section) {
        case 'users':
            results = await fetch(`/method/users.search?auth_mechanism=roaming&q=${query}&count=10&sort=4&fields=photo_50,status,nickname`)
            break
        case 'groups':
            results = await fetch(`/method/groups.search?auth_mechanism=roaming&q=${query}&count=10&sort=4&fields=photo_50,description`)
            break
        case 'videos':
            results = await fetch(`/method/video.search?auth_mechanism=roaming&q=${query}&count=10&sort=4&extended=1`)
            break
        case 'audios_playlists':
            results = await fetch(`/method/audio.searchAlbums?auth_mechanism=roaming&query=${query}&limit=10`)
            break
    }

    json_result = await results.json()
    if(!json_result || json_result.error) {
        console.error(json_result.error)
        return
    }

    json_result = json_result.response
    if(json_result.count < 1) {
        console.info('No tips available.')
        return
    }

    switch(section) {
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
                item['url']  = `/video${item['owner_id']}_${item['id']}`
                item['preview'] = item['image'][0]['url']

                if(item['owner_id'] > 0) {
                    const profile = profiles.find(prof => prof.id == item['owner_id'])
                    if(!profile) { return }
                    item['description'] = profile['first_name'] + ' ' + profile['last_name']
                } else {
                    const group = groups.find(grou => grou.id == Math.abs(item['owner_id']))
                    if(!group) { return }
                    item['description'] = group['name']
                }
            })
            break
    }

    u('#searchBoxFastTips').addClass('shown')
    u('#searchBoxFastTips').html('')
    json_result.items.forEach(item => {
        u('#searchBoxFastTips').append(`
            <a href='${item['url']}'>
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
    if(u_tips.length < 1) {
        return
    }

    const focused = u('#searchBoxFastTips a:focus').nodes[0]

    // up
    switch(e.keyCode) {
        case 38:
            e.preventDefault()
            if(!focused) {
                u_tips.nodes[0].focus()
                return
            }

            if(focused.previousSibling) {
                focused.previousSibling.focus()
            }

            break
        // down
        case 40:
            e.preventDefault()
            if(!focused) {
                u_tips.nodes[0].focus()
                return
            }

            if(focused.nextSibling) {
                focused.nextSibling.focus()
            } else {
                u_tips.nodes[0].focus()
            }

            break
    }
})
