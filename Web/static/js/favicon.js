(function (root, factory) {
    if (typeof define === 'function' && define.amd) {
        define([], factory);
    } else if (typeof module === 'object' && module.exports) {
        module.exports = factory();
    } else {
        const exports = factory();
        root.FaviconManager = exports.FaviconManager;
        root.Favicon = exports.Favicon;
        root.setFavicon = exports.setFavicon;
    }
})(typeof globalThis !== 'undefined' ? globalThis : typeof window !== 'undefined' ? window : this, function () {
    const DEFAULT_FAVICON = '/assets/packages/static/openvk/img/favicon/main.ico';
    const BASE_PATH = '/assets/packages/static/openvk/img/favicon/';

    const states = {
        custom: null,
        im: null,
        music: null
    };

    let currentAppliedUrl = null;

    function resolve(name) {
        if (!name) {
            return DEFAULT_FAVICON;
        }

        if (
            name.startsWith('/') ||
            name.startsWith('http://') ||
            name.startsWith('https://') ||
            name.startsWith('data:')
        ) {
            return name;
        }

        if (!name.endsWith('.ico')) {
            name = name + '.ico';
        }

        return BASE_PATH + name;
    }

    function apply(url) {
        const href = resolve(url);
        if (currentAppliedUrl === href) {
            return;
        }
        currentAppliedUrl = href;

        if (typeof document === 'undefined') {
            return;
        }

        function doApply() {
            let links = document.querySelectorAll('link[rel="icon"], link[rel="shortcut icon"], link[rel~="icon"]');
            if (!links || links.length === 0) {
                const link = document.createElement('link');
                link.rel = 'shortcut icon';
                link.href = href;
                if (document.head) {
                    document.head.appendChild(link);
                }
            } else {
                links.forEach(function (link) {
                    if (link.getAttribute('href') !== href) {
                        link.setAttribute('href', href);
                    }
                });
            }
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', doApply, { once: true });
        } else {
            doApply();
        }
    }

    function update() {
        const active = states.custom || states.im || states.music || null;
        apply(active ? resolve(active) : DEFAULT_FAVICON);
    }

    const FaviconManager = {
        DEFAULT_FAVICON: DEFAULT_FAVICON,
        BASE_PATH: BASE_PATH,
        resolve: resolve,
        apply: apply,
        update: update,
        set: function (iconName) {
            states.custom = iconName ? String(iconName) : null;
            update();
        },
        setIm: function (count) {
            const num = Number(count) || 0;
            if (num <= 0) {
                states.im = null;
            } else if (num > 9) {
                states.im = 'im9+';
            } else {
                states.im = 'im' + num;
            }
            update();
        },
        setMusic: function (state) {
            if (!state) {
                states.music = null;
            } else if (state === 'playing' || state === 'play') {
                states.music = 'play';
            } else if (state === 'paused' || state === 'pause') {
                states.music = 'pause';
            } else {
                states.music = String(state);
            }
            update();
        },
        clear: function (source) {
            if (source && states.hasOwnProperty(source)) {
                states[source] = null;
            } else {
                states.custom = null;
                states.im = null;
                states.music = null;
            }
            update();
        },
        getStates: function () {
            return Object.assign({}, states);
        },
        getActive: function () {
            return states.custom || states.im || states.music || DEFAULT_FAVICON;
        }
    };
    function setFavicon(iconName) {
        if (!iconName) {
            FaviconManager.set(null);
        } else {
            FaviconManager.set(iconName);
        }
    }

    return {
        FaviconManager: FaviconManager,
        Favicon: FaviconManager,
        setFavicon: setFavicon
    };
});
