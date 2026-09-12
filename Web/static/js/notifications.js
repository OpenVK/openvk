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
            <a class="close">&times;</a> 
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
    let isHovered = false;
    let remainingTime = time;
    let timerStartTimestamp = null;

    function __closeNotification() {
        if (closed) return;
        closed = true;

        stopTimer();
        cleanupListeners();

        if (count && counter > 0) {
            counter--;
            updateTitle();
        }

        getPrototype().addClass('disappears');
        setTimeout(() => { getPrototype().remove(); }, 500);
    }

    function startTimer() {
        if (closed || timerId || isHovered) return;
        if (document.hidden || document.visibilityState !== "visible") return;

        timerStartTimestamp = Date.now();
        timerId = setTimeout(() => {
            __closeNotification();
        }, remainingTime);
    }

    function stopTimer() {
        if (timerId) {
            clearTimeout(timerId);
            timerId = null;
            if (timerStartTimestamp) {
                remainingTime -= (Date.now() - timerStartTimestamp);
                if (remainingTime < 1000) remainingTime = 1000;
            }
        }
    }

    function onVisibilityChange() {
        if (document.hidden || document.visibilityState !== "visible") {
            stopTimer();
        } else {
            startTimer();
        }
    }

    function cleanupListeners() {
        document.removeEventListener("visibilitychange", onVisibilityChange);
        window.removeEventListener("focus", onVisibilityChange);
        window.removeEventListener("blur", onVisibilityChange);
    }

    document.addEventListener("visibilitychange", onVisibilityChange);
    window.addEventListener("focus", onVisibilityChange);
    window.addEventListener("blur", onVisibilityChange);

    notification.on('mouseenter', function () {
        isHovered = true;
        stopTimer();
    });

    notification.on('mouseleave', function () {
        isHovered = false;
        remainingTime = time;
        startTimer();
    });

    if (count === true) {
        counter++;
        updateTitle();
    }

    startTimer();

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