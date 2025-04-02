//u('.postFeedPageSelect').attr('style', 'display:none')
// Source ignoring
u(document).on("click", "#__ignoreSomeone", async (e) => {
    e.preventDefault()

    const TARGET = u(e.target)
    const ENTITY_ID = Number(e.target.dataset.id)
    const VAL = Number(e.target.dataset.val)
    const ACT = VAL == 1 ? 'ignore' : 'unignore'
    const METHOD_NAME = ACT == 'ignore' ? 'addBan' : 'deleteBan'
    const PARAM_NAME = ENTITY_ID < 0 ? 'group_ids' : 'user_ids'
    const ENTITY_NAME = ENTITY_ID < 0 ? 'club' : 'user'
    const URL = `/method/newsfeed.${METHOD_NAME}?auth_mechanism=roaming&${PARAM_NAME}=${Math.abs(ENTITY_ID)}`
    
    TARGET.addClass('lagged')
    const REQ = await fetch(URL)
    const RES = await REQ.json()
    TARGET.removeClass('lagged')

    if(RES.error_code) {
        switch(RES.error_code) {
            case -10:
                fastError(';/')
                break
            case -50:
                fastError(tr('ignored_sources_limit'))
                break
            default:
                fastError(res.error_msg)
                break
        }
        return
    }

    if(RES.response == 1) {
        if(ACT == 'unignore') {
            TARGET.attr('data-val', '1')
            TARGET.html(tr(`ignore_${ENTITY_NAME}`))
        } else {
            TARGET.attr('data-val', '0')
            TARGET.html(tr(`unignore_${ENTITY_NAME}`))
        }
    }
})

u(document).on('click', '#__feed_settings_link', (e) => {
    e.preventDefault()

    let   current_tab = 'main';
    const body = `
        <div id='_feed_settings_container'>
            <div id='_tabs'>
                <div class="mb_tabs">
                    <div class="mb_tab" data-name='main'>
                        <a>
                            ${tr('main')}
                        </a>
                    </div>
                    <div class="mb_tab" data-name='ignored'>
                        <a>
                            ${tr('ignored_sources')}
                        </a>
                    </div>
                </div>
            </div>
            <div id='__content'></div>
        </div>
    `

    MessageBox(tr("feed_settings"), body, [tr("close")], [Function.noop])
    u('.ovk-diag-body').attr('style', 'padding:0px;height: 255px;overflow: hidden;')

    async function __switchTab(tab) 
    {
        current_tab = tab
        u(`#_feed_settings_container .mb_tab`).attr('id', 'ki')
        u(`#_feed_settings_container .mb_tab[data-name='${tab}']`).attr('id', 'active')
        u(`#_feed_settings_container .mb_tabs input`).remove()

        switch(current_tab) {
            case 'main':
                const __temp_url = new URL(location.href)
                const PAGES_COUNT = Number(e.target.dataset.pagescount ?? '10')
                const CURRENT_PERPAGE = Number(__temp_url.searchParams.get('posts') ?? 10)
                const CURRENT_PAGE = Number(__temp_url.searchParams.get('p') ?? 1)
                const CURRENT_RETURN_BANNED = Number(__temp_url.searchParams.get('return_banned') ?? 0)
                const CURRENT_AUTO_SCROLL = Number(localStorage.getItem('ux.auto_scroll') ?? 1)
                const CURRENT_DISABLE_AJAX = Number(localStorage.getItem('ux.disable_ajax_routing') ?? 0)
                const COUNT = [1, 5, 10, 20, 30, 40, 50]
                u('#_feed_settings_container #__content').html(`
                    <table cellspacing="7" cellpadding="0" border="0" align="center">
                        <tbody>
                            <tr>
                                <td width="120" valign="top">
                                    <span class="nobold">${tr('posts_per_page')}</span>
                                </td>
                                <td>
                                    <select id="pageSelect"></select>
                                </td>
                            </tr>
                            <tr>
                                <td width="120" valign="top">
                                    <span class="nobold">${tr('start_from_page')}</span>
                                </td>
                                <td>
                                    <input type='number' min='1' max='${PAGES_COUNT}' id='pageNumber' value='${CURRENT_PAGE}' placeholder='${CURRENT_PAGE}'>
                                </td>
                            </tr>
                            <tr>
                                <td width="120" valign="top">
                                    <span class="nobold">
                                        <input type='checkbox' name='showIgnored' id="showIgnored" ${CURRENT_RETURN_BANNED == 1 ? 'checked' : ''}>
                                    </span>
                                </td>
                                <td>
                                    <label for='showIgnored'>${tr('show_ignored_sources')}</label>
                                </td>
                            </tr>
                            <tr>
                                <td width="120" valign="top">
                                    <span class="nobold">
                                        <input type='checkbox' data-act='localstorage_item' data-inverse="1" name='ux.disable_ajax_routing' id="ux.disable_ajax_routing" ${CURRENT_DISABLE_AJAX == 0 ? 'checked' : ''}>
                                    </span>
                                </td>
                                <td>
                                    <label for='ux.disable_ajax_routing'>${tr('ajax_routing')}</label>
                                </td>
                            </tr>
                            <tr>
                                <td width="120" valign="top">
                                    <span class="nobold">
                                        <input type='checkbox' data-act='localstorage_item' name='ux.auto_scroll' id="ux.auto_scroll" ${CURRENT_AUTO_SCROLL == 1 ? 'checked' : ''}>
                                    </span>
                                </td>
                                <td>
                                    <label for='ux.auto_scroll'>${tr('auto_scroll')}</label>
                                </td>
                            </tr>
                            <tr>
                                <td width="120" valign="top">
                                </td>
                                <td>
                                    <input class='button' type='button' value='${tr('apply')}'>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                `)

                u(`#_feed_settings_container #__content input[type='button']`).on('click', (e) => {
                    const INPUT_PAGES_COUNT = parseInt(u('#_feed_settings_container #__content #pageSelect').nodes[0].selectedOptions[0].value ?? '10')
                    const INPUT_PAGE        = parseInt(u('#_feed_settings_container #__content #pageNumber').nodes[0].value ?? '1')
                    const INPUT_IGNORED     = Number(u('#_feed_settings_container #__content #showIgnored').nodes[0].checked ?? false)

                    const FINAL_URL = new URL(location.href)

                    if(CURRENT_PERPAGE != INPUT_PAGES_COUNT) {
                        FINAL_URL.searchParams.set('posts', INPUT_PAGES_COUNT)
                    }

                    if(CURRENT_PAGE != INPUT_PAGE && INPUT_PAGE <= PAGES_COUNT) {
                        FINAL_URL.searchParams.set('p', Math.max(1, INPUT_PAGE))
                    }

                    if(INPUT_IGNORED == 1) {
                        FINAL_URL.searchParams.set('return_banned', 1)
                    } else {
                        FINAL_URL.searchParams.delete('return_banned')
                    }
                    
                    window.router.route(FINAL_URL.href)
                })
                
                COUNT.forEach(item => {
                    u('#_feed_settings_container #pageSelect').append(`
                        <option value="${item}" ${item == CURRENT_PERPAGE ? 'selected' : ''}>${item}</option>
                    `)
                })

                break
            case 'ignored':
                u('#_feed_settings_container #__content').html(`
                    <div id='gif_loader'></div>
                `)
                if(!window.openvk.ignored_list) {
                    const IGNORED_RES  = await fetch('/method/newsfeed.getBanned?auth_mechanism=roaming&extended=1&fields=real_id,screen_name,photo_50&merge=1')
                    const IGNORED_LIST = await IGNORED_RES.json()

                    window.openvk.ignored_list = IGNORED_LIST
                }

                u('#_feed_settings_container #__content').html(`
                    <div class='entity_vertical_list mini'></div>
                `)

                u('#_feed_settings_container .mb_tabs').append(`
                    <input class='button lagged' id='_remove_ignores' type='button' value='${tr('stop_ignore')}'>    
                `)

                if(window.openvk.ignored_list.error_code) {
                    fastError(IGNORED_LIST.error_msg)
                    return
                }

                if(window.openvk.ignored_list.response.items.length < 1) {
                    u('#_feed_settings_container #__content').html(`
                        <div class="information">
                            ${tr('no_ignores_count')}
                        </div>`)
                    u('#_remove_ignores').remove()
                }

                window.openvk.ignored_list.response.items.forEach(ignore_item => {
                    let name = ignore_item.name
                    if(!name) {
                        name = ignore_item.first_name + ' ' + ignore_item.last_name
                    }

                    u('#_feed_settings_container #__content .entity_vertical_list').append(`
                        <label class='entity_vertical_list_item with_third_column' data-id='${ignore_item.real_id}'>
                            <div class='first_column'>
                                <a href='/${ignore_item.screen_name}' class='avatar'>
                                    <img src='${ignore_item.photo_50}'>
                                </a>
    
                                <div class='info'>
                                    <b class='noOverflow'>
                                        <a href="/${ignore_item.screen_name}">
                                            ${ovk_proc_strtr(escapeHtml(name), 100)}
                                        </a>
                                    </b>
                                </div>
                            </div>
    
                            <div class='third_column' style="display: grid; align-items: center;">
                                <input type='checkbox' name='remove_me'>
                            </div>
                        </label>
                    `)
                })

                u("#_feed_settings_container").on("click", "input[name='remove_me']", async (e) => {
                    const checks_count = u(`input[name='remove_me']:checked`).length
                    if(checks_count > 0) {
                        u('.mb_tabs #_remove_ignores').removeClass('lagged')
                    } else {
                        u('.mb_tabs #_remove_ignores').addClass('lagged')
                    }

                    if(checks_count > 10) {
                        e.preventDefault()
                    }
                })

                u('#_feed_settings_container').on('click', '#_remove_ignores', async (e) => {
                    e.target.classList.add('lagged')

                    const ids = []
                    u('#__content .entity_vertical_list label').nodes.forEach(item => {
                        const _checkbox = item.querySelector(`input[type='checkbox'][name='remove_me']`)
                        if(_checkbox.checked) {
                            ids.push(item.dataset.id)
                        }
                    })

                    const user_ids = []
                    const group_ids = []
                    ids.forEach(id => {
                        id > 0 ? user_ids.push(id) : group_ids.push(Math.abs(id))
                    })

                    const res = await fetch(`/method/newsfeed.deleteBan?auth_mechanism=roaming&user_ids=${user_ids.join(',')}&group_ids=${group_ids.join(',')}`)
                    const resp = await res.json()
                    if(resp.error_code) {
                        console.error(resp.error_msg)
                        return
                    }
                    
                    window.openvk.ignored_list = null
                    __switchTab('ignored')
                })
                
                break
        }
    }

    u("#_feed_settings_container").on("click", ".mb_tab a", async (e) => {
        await __switchTab(u(e.target).closest('.mb_tab').attr('data-name'))
    })

    __switchTab('main')
})

u(document).on('change', `input[data-act='localstorage_item']`, (e) => {
    if(e.target.dataset.inverse) {
        localStorage.setItem(e.target.name, Number(!e.target.checked))
        return
    }

    localStorage.setItem(e.target.name, Number(e.target.checked))
})
