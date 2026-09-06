Function.noop = () => { };

var _n_counter = 0;
var counter = 0;

window.baseTitle = document.title;

function updateTitle() {
    document.title = counter > 0 ? `(${counter}) ${window.baseTitle}` : window.baseTitle;
}

window.setBaseTitle = function (title) {
    window.baseTitle = title;
    updateTitle();
};

window.addEventListener("focus", () => {
    counter = 0;
    updateTitle();
});

function NewNotification(title, body, avatar = null, callback = () => { }, time = 5000, count = true) {
    if (avatar != null) {
        avatar = '<avatar><img src="' + avatar + '"></avatar>';
    } else {
        avatar = '';
    }

    _n_counter += 1;
    let id = _n_counter;

    let notification = u(
        `<div class="notification_ballon notification_ballon_wrap" id="n${id}">
        <notification_title>
            ${title}
            <a class="close">X</a> 
        </notification_title>
        <wrap>
            ${avatar}
            <content>
                ${body}
            </content>
        </wrap>
    </div>`
    );

    u(".notifications_global_wrap").prepend(notification);

    function getPrototype() {
        return u("#n" + id);
    }

    let closed = false;
    let timerId = null;

    function __closeNotification() {
        if (closed) return;
        closed = true;

        document.removeEventListener("visibilitychange", checkVisibilityAndStartTimer);
        window.removeEventListener("focus", checkVisibilityAndStartTimer);

        if (timerId) clearTimeout(timerId);

        if (count && counter > 0) {
            counter--;
            updateTitle();
        }

        getPrototype().addClass('disappears');
        setTimeout(() => { getPrototype().remove(); }, 500);
    }

    function checkVisibilityAndStartTimer() {
        if (closed || timerId) return;

        if (document.visibilityState === "visible") {
            document.removeEventListener("visibilitychange", checkVisibilityAndStartTimer);
            window.removeEventListener("focus", checkVisibilityAndStartTimer);

            timerId = setTimeout(() => {
                __closeNotification();
            }, time);
        }
    }

    if (count === true) {
        counter++;
        updateTitle();
    }

    if (document.visibilityState === "visible") {
        checkVisibilityAndStartTimer();
    } else {
        document.addEventListener("visibilitychange", checkVisibilityAndStartTimer);
        window.addEventListener("focus", checkVisibilityAndStartTimer);
    }

    notification.children('notification_title').children('a.close').on('click', function (e) {
        e.stopPropagation();
        __closeNotification();
    });

    notification.on('click', function (e) {
        if (!notification.hasClass('disappears')) {
            Reflect.apply(callback, {
                closeNotification: () => __closeNotification(),
                $notification: () => getPrototype()
            }, [e]);

            __closeNotification();
        }
    });
}