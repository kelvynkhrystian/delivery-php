self.addEventListener('push', function(event) {
    const options = {
        body: event.data ? event.data.text() : 'Novo pedido recebido!',
        icon: '/gestao/assets/img/logo_678b091f33a4a.png',
        badge: '/gestao/assets/img/favicon_678b092fa933a.png',
        vibrate: [200, 100, 200],
        tag: 'pedido',
        renotify: true,
        actions: [
            { action: 'view', title: 'Ver Pedido' }
        ]
    };

    event.waitUntil(
        self.registration.showNotification('Novo Pedido', options)
    );
});

self.addEventListener('notificationclick', function(event) {
    event.notification.close();

    if (event.action === 'view') {
        clients.openWindow('/gestao/admin/gerenciar_pedidos.php');
    }
});
