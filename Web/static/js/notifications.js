Function.noop = () => {};

var _n_counter = 0;

var counter = 0;

window.addEventListener("focus", () => {
    document.title = document.title.replace(/^\([0-9]+\) /, ""); // remove notification counter xD
});

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
        if(document.visibilityState != "visible")
            return setTimeout(() => {__closeNotification()}, time); // delay notif deletion
        
        getPrototype().addClass('disappears');
        return setTimeout(() => {getPrototype().remove()}, 500);
    }

    if(count == true) {
        counter++;
        document.title = `(${counter}) ${document.title}`;
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
