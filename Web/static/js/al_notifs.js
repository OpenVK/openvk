createjs.Sound.registerSound("/assets/packages/static/openvk/audio/notify.mp3", "notification");

function __actualPlayNotifSound() {
    createjs.Sound.play("notification");
}

window.playNotifSound = Function.noop;

async function setupNotificationListener() {
    console.warn("Setting up notifications listener...");
    
    while(true) {
        let notif;
        try {
            notif = await API.Notifications.fetch();
        } catch(rejection) {
            if(rejection.message !== "Nothing to report") {
                console.error(rejection);
                return;
            }
            
            console.info("No new notifications discovered... Redialing event broker");
            continue;
        }
        
        
        playNotifSound();
        NewNotification(notif.title, notif.body, notif.ava, Function.noop, notif.priority * 6000);
        console.info("New notification", notif);
        
        API.Notifications.ack();
    }
};

setupNotificationListener();

u(document.body).on("click", () => window.playNotifSound = window.__actualPlayNotifSound);
