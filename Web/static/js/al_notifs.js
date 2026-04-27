createjs.Sound.registerSound("/assets/packages/static/openvk/audio/notify.mp3", "notification");

function __actualPlayNotifSound() {
    createjs.Sound.play("notification");
}

window.playNotifSound = Function.noop;

function incrementNotificationsCounter() {
    document.querySelectorAll('a[href="/notifications"]').forEach(link => {

        let counterObject = link.querySelector('object');

        if (!counterObject) {
            counterObject = document.createElement('object');
            counterObject.type = 'internal/link';
            counterObject.innerHTML = ' (<b>1</b>)';
            link.appendChild(counterObject);
        } else {
            const bTag = counterObject.querySelector('b');
            if (bTag) {
                let currentCount = parseInt(bTag.textContent) || 0;
                bTag.textContent = currentCount + 1;
            } else {
                counterObject.innerHTML = ' (<b>1</b>)';
            }
        }
    });
}

async function setupNotificationListener() {
    console.warn("Setting up notifications listener...");
    
    const POLL_INTERVAL = 10000;
    const CHECK_MORE_INTERVAL = 250;
    const ERROR_RETRY_INTERVAL = 60000;
    let isFirstRequest = true;

    while(true) {
        try {
            const notif = await API.Notifications.fetch();
            
            if (notif) {
                if (!isFirstRequest) {
                    playNotifSound();
                    console.info("New notification", notif);
                    NewNotification(notif.title, notif.body, notif.ava, Function.noop, (notif.priority || 1) * 6000);
                    incrementNotificationsCounter();
                } else {
                    console.info("First request: skipping alert (syncing cursor)");
                }
            }
            
            await new Promise(resolve => setTimeout(resolve, CHECK_MORE_INTERVAL));
        } catch(rejection) {
            const isEmpty = rejection.message === "Nothing to report" || rejection.code === 1983;

            if (isEmpty) {
                if (isFirstRequest) {
                    console.info("Cursor synced. Real-time notifications enabled.");
                    isFirstRequest = false; 
                } else {
                    console.info("No new notifications found, sleeping for " + POLL_INTERVAL/1000 + "s...")
                }
                await new Promise(resolve => setTimeout(resolve, POLL_INTERVAL));
            } else {
                console.error("Poll error, sleeping...", rejection);
                await new Promise(resolve => setTimeout(resolve, ERROR_RETRY_INTERVAL));
            }
        }
    }
};

(async function() {
    await setupNotificationListener();
})();

u(document.body).on("click", () => window.playNotifSound = window.__actualPlayNotifSound);
