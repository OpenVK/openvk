Function.noop = () => {};

var _n_counter = 0;

function NewNotification(title, body, avatar = null, callback = () => {}, time = 5000) {
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
        
    setTimeout(() => {__closeNotification()}, time);

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
