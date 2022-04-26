Function.noop = () => {};

var _n_counter = 0;

var _activeWindow = true;

const _pageTitle = u("title").nodes[0].innerText;

var counter = 0;

/* this fucking dumb shit is broken :c

window.addEventListener('focus', () => {
    _activeWindow = true;
    closeAllNotifications();
});

window.addEventListener('blur', () => {_activeWindow = false});

function closeAllNotifications() {
    var notifications = u(".notifications_global_wrap").nodes[0].children;
    for (var i = 0; i < notifications.length; i++) {
        setTimeout(() => {
            console.log(i);
            notifications.item(i).classList.add('disappears');
            setTimeout(() => {notifications.item(i).remove()}, 500).bind(this);
        }, 5000).bind(this);
    }
} */

function NewNotification(title, body, avatar = null, callback = () => {}, time = 5000, count = true) {
    if(avatar != null) {
        avatar = '<avatar>' +
            '<img src="' + avatar + '">' +
        '</avatar>';
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
    </div>
    `);

    u(".notifications_global_wrap").append(notification);

    function getPrototype() {
        return u("#n"+id);
    }

    function __closeNotification() {
        getPrototype().addClass('disappears');
        setTimeout(() => {getPrototype().remove()}, 500);
    }

    if(count == true) {
        counter++;
        document.title = `(${counter}) ${_pageTitle}`;
    }
    
    /* if(_activeWindow == true) { */
        setTimeout(() => {__closeNotification()}, time);
    /* } */

    notification.children('notification_title').children('a.close').on('click', function(e) {
        __closeNotification();
    });

    notification.on('click', function(e) {
        if (!notification.hasClass('disappears')) {
            Reflect.apply(callback, {
                closeNotification: () => __closeNotification(),
                $notification:     () => getPrototype()
            }, [e]);

            __closeNotification();
        }
    });
}
