export function openAttachmentsModal({ peer, initialType = 'photo' } = {}) {
    if (window.im) {
        window.im.openTabByName("materials", true, { peer: peer, initialType: initialType });
    }
}

if (typeof window !== 'undefined') {
    window.openAttachmentsModal = openAttachmentsModal;
}
