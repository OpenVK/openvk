window.router = new class {
    get csrf() {
        return u("meta[name=csrf]").attr("value")
    }

    __isScriptAlreadyLoaded(script) {
        if(script.src) {
            const script_url = new URL(script.src)
            const script_main_part = script_url.pathname
            console.log(script_main_part)
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

        _t_scr.id = script.id
        //const parent = script.parentNode
        //const idx = Array.from(parent.children).indexOf(script)

        if(script.src) {
            _t_scr.src = script.src
        } else {
            _t_scr.textContent = script.textContent
        }   

        //parent.children[idx].before(script)
        document.body.appendChild(_t_scr)
    }

    __clearScripts() {
        u(`script:not([src])`).remove()
    }

    __closeMsgs() {
        window.messagebox_stack.forEach(msg => msg.close())
    }

    __appendPage(parsed_content) {
        const page_body = u(parsed_content.querySelector('.page_body'))
        const sidebar = u(parsed_content.querySelector('.sidebar'))
        const page_header = u(parsed_content.querySelector('.page_header'))

        if(page_body.length < 1) {
            makeError('Invalid page has been loaded')
            return
        }

        this.__clearScripts()
        parsed_content.querySelectorAll('.page_body script, #_js_ep_script').forEach(script => {
            if(!this.__isScriptAlreadyLoaded(script)) {
                this.__appendScript(script)
                script.parentNode.removeChild(script)
            }
        })
        u('.page_body').html(page_body.html())
        u('.sidebar').html(sidebar.html())
        if(u('.page_header #search_box select').length > 0 && page_header.find('#search_box select').length > 0) {
            u('.page_header #search_box select').nodes[0].value = page_header.find('#search_box select').nodes[0].value
        }

        if(page_header.hasClass('search_expanded_at_all')) {
            u('.page_header').addClass('search_expanded_at_all').addClass('search_expanded')
        } else {
            if(u('.page_header').hasClass('search_expanded_at_all')) {
                u('.page_header').removeClass('search_expanded_at_all').removeClass('search_expanded')
            }
        }
        
        u("meta[name=csrf]").attr("value", u(parsed_content.querySelector('meta[name=csrf]')).attr('value'))
        
        document.title = parsed_content.title
        window.scrollTo(0, 0)
        bsdnHydrate()

        if(u('.paginator:not(.paginator-at-top)').length > 0) {
            showMoreObserver.observe(u('.paginator:not(.paginator-at-top)').nodes[0])
        }

        if(u(`div[class$="_small_block"]`).length > 0 && window.smallBlockObserver) {
            smallBlockObserver.observe(u(`div[class$="_small_block"]`).nodes[0])
        }
    }

    __unlinkObservers() {
        if(u('.paginator:not(.paginator-at-top)').length > 0) {
            showMoreObserver.unobserve(u('.paginator:not(.paginator-at-top)').nodes[0])
        }

        if(u(`div[class$="_small_block"]`).length > 0 && window.smallBlockObserver) {
            smallBlockObserver.unobserve(u(`div[class$="_small_block"]`).nodes[0])
        }
    }

    checkUrl(url) {
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

        return true
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

        const push_url = params.push_state ?? true
        const next_page_url = new URL(url)
        next_page_url.searchParams.append('al', 1)
        next_page_url.searchParams.append('hash', this.csrf)
        if(push_url) {
            history.pushState({'from_router': 1}, '', url)
        } else {
            history.replaceState({'from_router': 1}, '', url)
        }

        const parser = new DOMParser
        const next_page_request = await fetch(next_page_url, {
            method: 'GET'
        })
        const next_page_text = await next_page_request.text()
        const parsed_content = parser.parseFromString(next_page_text, 'text/html')
        if(next_page_request.redirected) {
            history.replaceState({'from_router': 1}, '', next_page_request.url)
        }
        
        this.__closeMsgs()
        this.__unlinkObservers()
        this.__appendPage(parsed_content)
    }
}

u(document).on('click', 'a', async (e) => {
    const target = u(e.target).closest('a')
    const dom_url = target.attr('href')
    const id = target.attr('id')
    let url = target.nodes[0].href

    if(id) {
        if(['act_tab_a', 'ki', '_pinGroup'].indexOf(id) == -1) {
            console.log('AJAX | Skipping cuz maybe its function call link.')
            return
        }
    }

    if(url.indexOf('hash=') != -1) {
        e.preventDefault()
        return false
    }

    if(!dom_url || dom_url == '#' || dom_url.indexOf('javascript:') != -1) {
        console.log('AJAX | Skipped cuz its anchor or function call')
        return
    }

    if(target.attr('target') == '_blank') {
        console.log('AJAX | Skipping cuz its _blank.')
        return
    }

    if(!window.router.checkUrl(url)) {
        return
    }

    e.preventDefault()

    console.log(`AJAX | Going to URL ${url}`)
    await window.router.route({
        url: url,
    })
})

u(document).on('submit', 'form', async (e) => {
    if(u('#ajloader').hasClass('shown')) {
        e.preventDefault()
        return
    }

    if((localStorage.getItem('ux.disable_ajax_routing') ?? 0) == 1 || window.openvk.current_id == 0) {
        return false
    }

    e.preventDefault()
    u('#ajloader').addClass('shown')

    const form = e.target
    const method = form.method ?? 'get'
    const url = form.action
    if(form.onsubmit) {
        u('#ajloader').removeClass('shown')
        return false
    }

    const url_object = new URL(url)
    url_object.searchParams.append('al', 1)
    if(method == 'get' || method == 'GET') {
        url_object.searchParams.append('hash', window.router.csrf)
        $(form).serializeArray().forEach(param => {
            url_object.searchParams.append(param.name, param.value)
        })
    }

    if(!url) {
        u('#ajloader').removeClass('shown')
        return
    }

    const form_data = serializeForm(form)
    const request_object = {
        method: method
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

    console.log(form_res)
    window.router.__appendPage(parsed_content)

    u('#ajloader').removeClass('shown')
})

window.addEventListener('popstate', (e) => {
    e.preventDefault();
    window.router.route({
        url: location.href,
        push_state: false,
    })
})
