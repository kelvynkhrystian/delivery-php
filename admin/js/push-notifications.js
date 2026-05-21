async function registerPushNotifications() {
    if ('serviceWorker' in navigator && 'PushManager' in window) {
        try {
            const registration = await navigator.serviceWorker.register('/gestao/admin/js/service-worker.js');
            const subscription = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: await getPublicKey()
            });

            // Enviar a subscription para o servidor
            await saveSubscription(subscription);
        } catch (error) {
            console.error('Erro ao registrar notificações:', error);
        }
    }
}

async function getPublicKey() {
    const response = await fetch('/gestao/admin/get_vapid_public_key.php');
    const data = await response.json();
    return urlBase64ToUint8Array(data.publicKey);
}

async function saveSubscription(subscription) {
    await fetch('/gestao/admin/save_subscription.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(subscription)
    });
}

function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - base64String.length % 4) % 4);
    const base64 = (base64String + padding)
        .replace(/\-/g, '+')
        .replace(/_/g, '/');

    const rawData = window.atob(base64);
    const outputArray = new Uint8Array(rawData.length);

    for (let i = 0; i < rawData.length; ++i) {
        outputArray[i] = rawData.charCodeAt(i);
    }
    return outputArray;
}

// Registrar as notificações quando o admin fizer login
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', registerPushNotifications);
} else {
    registerPushNotifications();
}
