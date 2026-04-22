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
            console.info("No new notifications discovered... Waiting 5s and looking up again");
            await new Promise(resolve => setTimeout(resolve, 5000));
            
            continue;
        }
        
        
        playNotifSound();
        console.info("New notification", notif);
        NewNotification(notif.title, notif.body, notif.ava, Function.noop, notif.priority * 6000);
        
        await new Promise(resolve => setTimeout(resolve, 250));
    }
};

(async function() {
    await setupNotificationListener();
})();

u(document.body).on("click", () => window.playNotifSound = window.__actualPlayNotifSound);
