self.addEventListener('push', function(event) {
    const options = {
        body: event.data.text(),
        icon: '/gestao/admin/assets/images/icon.png',
        badge: '/gestao/admin/assets/images/badge.png',
        vibrate: [100, 50, 100],
        tag: 'novo-pedido',
        renotify: true,
        data: {
            dateOfArrival: Date.now(),
            primaryKey: 1,
            url: '/gestao/admin/pedidos.php'
        },
        actions: [
            {
                action: 'view',
                title: 'Ver Pedido',
                icon: '/gestao/admin/assets/images/view.png'
            }
        ]
    };

    event.waitUntil(
        self.registration.showNotification('Novo Pedido', options)
    );
});

self.addEventListener('notificationclick', function(event) {
    event.notification.close();

    if (event.action === 'view') {
        event.waitUntil(
            clients.openWindow(event.notification.data.url)
        );
    } else {
        // Se clicar na notificação em si
        event.waitUntil(
            clients.matchAll({type: 'window'}).then(function(clientList) {
                // Se já tiver uma janela aberta, foca nela
                for (const client of clientList) {
                    if (client.url === event.notification.data.url && 'focus' in client) {
                        return client.focus();
                    }
                }
                // Se não tiver janela aberta, abre uma nova
                if (clients.openWindow) {
                    return clients.openWindow(event.notification.data.url);
                }
            })
        );
    }
});
