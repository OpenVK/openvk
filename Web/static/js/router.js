window.router = new class {
    get csrf() {
        return u("meta[name=csrf]").attr("value")
    }

    __isScriptAlreadyLoaded(script) {
        if(script.src) {
            const script_url = new URL(script.src)
            const script_main_part = script_url.pathname

            return u(`script[src^='${script_main_part}']`).length > 0
        }

        return false
    }

    __appendScript(script) {
        const _t_scr = document.createElement('script')
        _t_scr.crossorigin = 'anonymous'
        if(script.getAttribute('integrity')) {
            _t_scr.setAttribute('integrity', script.getAttribute('integrity'))
        }

        if(script.getAttribute('id')) { 
            _t_scr.id = script.id
        }

        if(script.getAttribute('type')) { 
            _t_scr.type = script.type
        }

        //const parent = script.parentNode
        //const idx = Array.from(parent.children).indexOf(script)

        if(script.src) {
            _t_scr.src = script.src
        } else {
            _t_scr.async = false
            _t_scr.textContent = script.textContent
        }   

        //parent.children[idx].before(script)
        document.body.appendChild(_t_scr)
    }

    __clearScripts() {
        u(`script:not([src])`).remove()
    }

    __closeMsgs() {
        window.messagebox_stack.forEach(msg => {
            if(msg.hidden) {
                return
            }

            msg.close()
        })
    }

    __appendPage(parsed_content) {
        const scripts_to_append = []
        const page_body = u(parsed_content.querySelector('.page_body'))
        const sidebar = u(parsed_content.querySelector('.sidebar'))
        const page_header = u(parsed_content.querySelector('.page_header'))
        const page_footer = u(parsed_content.querySelector('.page_footer'))
        const backdrop = u(parsed_content.querySelector('#backdrop'))
        if(page_body.length < 1) {
            throw new Error('Invalid page has been loaded')
            return
        }

        window.__current_page_audio_context = null
        this.__clearScripts()
        parsed_content.querySelectorAll('.page_body script, #_js_ep_script').forEach(script => {
            if(!this.__isScriptAlreadyLoaded(script)) {
                scripts_to_append.push(script)
                script.parentNode.removeChild(script)
            }
        })
        u('.page_body').html(page_body.html())
        u('.sidebar').html(sidebar.html())
        u('.page_footer').html(page_footer.html())
        if(backdrop.length > 0) {
            if(u('#backdrop').length == 0) {
                u('body').append(`<div id="backdrop"></div>`)
            }
            u('#backdrop').nodes[0].outerHTML = (backdrop.nodes[0].outerHTML)
        } else {
            u('#backdrop').remove()
        }
        
        if(u('.page_header #search_box select').length > 0 && page_header.find('#search_box select').length > 0) {
            u('.page_header #search_box select').nodes[0].value = page_header.find('#search_box select').nodes[0].value
        }

        if(u('.page_header #search_box input').length > 0 && page_header.find('#search_box input').length > 0) {
            u('.page_header #search_box input').nodes[0].value = page_header.find('#search_box input').nodes[0].value
        }

        if(page_header.hasClass('search_expanded_at_all')) {
            u('.page_header').addClass('search_expanded_at_all').addClass('search_expanded')
        } else {
            if(u('.page_header').hasClass('search_expanded_at_all')) {
                u('.page_header').removeClass('search_expanded_at_all').removeClass('search_expanded')
            } else {
                u('.page_header').removeClass('search_expanded')
            }
        }
        
        u("meta[name=csrf]").attr("value", u(parsed_content.querySelector('meta[name=csrf]')).attr('value'))
        
        document.title = parsed_content.title
        scripts_to_append.forEach(append_me => {
            this.__appendScript(append_me)
        })
    }

    applyTweaks() {
        window.tweaks.forEach(item => {
            const name = item.name

            if (item.isEnabled()) {
                try {
                    console.log(`Applied tweak ${name}`)
                    item.func()
                } catch(e) {
                    console.error(e)
                }
            }
        })
    }

    async __integratePage(scrolling = null) {
        window.temp_y_scroll = null
        u('.toTop').removeClass('has_down')
        window.scrollTo(0, scrolling ?? 0)
        bsdnHydrate()

        if(u('.paginator:not(.paginator-at-top)').length > 0) {
            showMoreObserver.observe(u('.paginator:not(.paginator-at-top)').nodes[0])
        }

        if(u(`div[class$="_small_block"]`).length > 0 && typeof smallBlockObserver != 'undefined') {
            smallBlockObserver.observe(u(`div[class$="_small_block"]`).nodes[0])
        }

        if(window.player) {
            window.player.dump()
            await window.player._handlePageTransition()
        }

        this.applyTweaks()
    }

    __unlinkObservers() {
        if(u('.paginator:not(.paginator-at-top)').length > 0) {
            showMoreObserver.unobserve(u('.paginator:not(.paginator-at-top)').nodes[0])
        }

        if(u(`div[class$="_small_block"]`).length > 0 && typeof smallBlockObserver != 'undefined') {
            smallBlockObserver.unobserve(u(`div[class$="_small_block"]`).nodes[0])
        }
    }

    checkUrl(url) {
        if(window.openvk.disable_ajax == 1) {
            return false
        }

        if((localStorage.getItem('ux.disable_ajax_routing') ?? 0) == 1 || window.openvk.current_id == 0) {
            return false
        }

        if(!url || url == '') {
            return false
        }

        if(url.indexOf(location.origin) == -1) {
            return false
        }

        if(url.indexOf('hash=') != -1) {
            return false
        }

        if(url.indexOf('#close') != -1) {
            return false
        }

        return true
    }

    savePreviousPage() {
        this.prev_page_html = {
            url: location.href,
            pathname: location.pathname,
            html: u('.page_body').html(),
        }
    }

    async route(params = {}) {
        if(typeof params == 'string') {
            params = {
                url: params
            }
        }

        const old_url = location.href
        let url = params.url
        if(url.indexOf(location.origin)) {
            url = location.origin + url
        }

        if((localStorage.getItem('ux.disable_ajax_routing') ?? 0) == 1 || window.openvk.current_id == 0) {
            window.location.assign(url)
            return
        }

        if(this.prev_page_html && this.prev_page_html.pathname != location.pathname) {
            this.prev_page_html = null
        }

        const push_url = params.push_state ?? true
        const next_page_url = new URL(url)
        if(push_url) {
            history.pushState({'from_router': 1}, '', url)
        } else {
            history.replaceState({'from_router': 1}, '', url)
        }
        
        u('body').addClass('ajax_request_made')

        const parser = new DOMParser
        const next_page_request = await fetch(next_page_url, {
            method: 'AJAX',
            referrer: old_url,
            headers: {
                'X-OpenVK-Ajax-Query': '1',
            }
        })
        const next_page_text = await next_page_request.text()
        const parsed_content = parser.parseFromString(next_page_text, 'text/html')
        if(next_page_request.redirected) {
            history.replaceState({'from_router': 1}, '', next_page_request.url)
        }
        
        this.__closeMsgs()
        this.__unlinkObservers()
        
        u('body').removeClass('ajax_request_made')

        try {
            this.__appendPage(parsed_content)
            await this.__integratePage()
        } catch(e) {
            console.error(e)
            next_page_url.searchParams.delete('al', 1)
            location.assign(next_page_url)
        }
    }
}

u(document).on('click', 'a', async (e) => {
    if(e.defaultPrevented) {
        console.log('AJAX | Skipping because default is prevented')
        return
    }
    
    const target = u(e.target).closest('a')
    const dom_url = target.attr('href')
    const id = target.attr('id')
    let url = target.nodes[0].href

    if(id) {
        if(['act_tab_a', 'ki', 'used', '_pinGroup', 'profile_link', 'minilink-friends', 'minilink-albums', 'minilink-messenger', 'minilink-groups', 'minilink-notifications'].indexOf(id) == -1) {
            console.log('AJAX | Skipping cuz maybe its function call link.')
            return
        }
    }

    /*if(url.indexOf('hash=') != -1) {
        e.preventDefault()
        return false
    }*/

    if(target.attr('rel') == 'nofollow') {
        console.log('AJAX | Skipped because its nofollow')
        return
    }

    if(target.nodes[0].hasAttribute('download')) {
        console.log('AJAX | Skipped because its download')
        return
    }

    if(!dom_url || dom_url == '#' || dom_url.indexOf('javascript:') != -1) {
        console.log('AJAX | Skipped because its anchor or function call')
        return
    }

    if(target.attr('target') == '_blank') {
        console.log('AJAX | Skipping because its _blank.')
        return
    }

    if(!window.router.checkUrl(url)) {
        return
    }

    // temporary fix
    if(dom_url == '/') {
        url = url + 'id0'
    }

    e.preventDefault()

    console.log(`AJAX | Going to URL ${url}`)
    await window.router.route({
        url: url,
    })
})

u(document).on('submit', 'form', async (e) => {
    if(e.defaultPrevented) {
        return
    }
  
    if(u('#ajloader').hasClass('shown')) {
        e.preventDefault()
        return
    }

    if(window.openvk.disable_ajax == 1) {
        return false
    }

    if(e.target.closest('#write')) {
        const target = u(e.target)
        collect_attachments_node(target)
    }

    if((localStorage.getItem('ux.disable_ajax_routing') ?? 0) == 1 || window.openvk.current_id == 0) {
        return false
    }

    u('#ajloader').addClass('shown')

    const form = e.target
    const method = form.method ?? 'get'
    const url = form.action
    if(form.onsubmit || url.indexOf('/settings?act=interface') != -1) {
        u('#ajloader').removeClass('shown')
        return false
    }
    e.preventDefault()

    const url_object = new URL(url)
    if(method == 'get' || method == 'GET') {
        //url_object.searchParams.append('hash', window.router.csrf)
        $(form).serializeArray().forEach(param => {
            url_object.searchParams.append(param.name, param.value)
        })
    }

    if(!url) {
        u('#ajloader').removeClass('shown')
        return
    }

    const form_data = serializeForm(form, e.submitter)
    const request_object = {
        method: method,
        headers: {
            'X-OpenVK-Ajax-Query': '1',
        }
    }

    if(method != 'GET' && method != 'get') {
        request_object.body = form_data
    }

    const form_res = await fetch(url_object, request_object)
    const form_result = await form_res.text()
    switch(form_res.status) {
        case 500:
        case 502:
            makeError(form_res.statusText)
            break
    }

    const parser = new DOMParser
    const parsed_content = parser.parseFromString(form_result, 'text/html')

    if(form_res.redirected) {
        history.replaceState({'from_router': 1}, '', form_res.url)
    } else {
        const __new_url = new URL(form_res.url)
        __new_url.searchParams.delete('al')
        __new_url.searchParams.delete('hash')

        history.pushState({'from_router': 1}, '', __new_url)
    }
    
    window.router.__appendPage(parsed_content)
    window.router.__closeMsgs()
    await window.router.__integratePage()

    u('#ajloader').removeClass('shown')
})

window.addEventListener('popstate', (e) => {
    e.preventDefault();
    /*if(window.router.prev_page_html) {
        u('.page_body').html(window.router.prev_page_html.html)
        history.replaceState({'from_router': 1}, '', window.router.prev_page_html.url)
        window.router.prev_page_html = null
        window.router.__integratePage()
        return
    }*/

    if(e.state != null) {
        window.router.route({
            url: location.href,
            push_state: false,
        })
    }
})

window.addEventListener('DOMContentLoaded', () => {
    window.router.applyTweaks()
})
