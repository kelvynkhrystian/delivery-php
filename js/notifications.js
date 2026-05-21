// Detecta o caminho base
const basePath = '';

// Variáveis para armazenar os intervalos
let orderCheckInterval = null;
let statusCheckInterval = null;

// Flag para evitar múltiplas execuções simultâneas
let isCheckingOrders = false;
let isCheckingStatus = false;

// Registrar Service Worker para notificações na barra do sistema
async function registerServiceWorker() {
    if ('serviceWorker' in navigator) {
        const registration = await navigator.serviceWorker.getRegistration();
        if (!registration) {
            try {
                await navigator.serviceWorker.register('/sw.js');
                console.log('Service Worker registrado');
            } catch (error) {
                console.error('Erro ao registrar Service Worker:', error);
            }
        } else {
            console.log('Service Worker já registrado');
        }
    }
}

// Solicitar permissão de notificação
async function requestNotificationPermission() {
    if (!("Notification" in window)) {
        console.log("Este navegador não suporta notificações");
        return;
    }

    if (Notification.permission !== "granted") {
        const permission = await Notification.requestPermission();
        if (permission === "granted") {
            await registerServiceWorker();
        }
    } else {
        console.log('Permissão de notificação já concedida');
    }
}

// Função para mostrar a notificação
async function showNotification(title, message) {
    console.log('showNotification');
    console.log('Tentando mostrar notificação:', title, message);
    
    if (Notification.permission === "granted") {
        if ('serviceWorker' in navigator) {
            const registration = await navigator.serviceWorker.ready;
            await registration.showNotification(title, {
                body: message,
                icon: "/assets/img/logo_678b091f33a4a.png",
                badge: "/assets/img/favicon_678b092fa933a.png",
                vibrate: [200, 100, 200],
                tag: 'pedido', // Usamos a tag para agrupar notificações
                renotify: true, // Substitui notificações com a mesma tag
                actions: [
                    { action: 'view', title: 'Ver Pedido' }
                ]
            });
        }

        // Tocar som de notificação (ignora erros de autoplay)
        try {
            const audio = new Audio('../assets/sounds/notification.mp3');
            await audio.play().catch(() => {
                console.log('Reprodução de áudio bloqueada pelo navegador.');
            });
        } catch (error) {
            console.error('Erro ao reproduzir áudio:', error);
        }
    } else {
        console.log('Notificações não permitidas');
    }
}

// Para o Admin: Verificar novos pedidos
async function checkNewOrders() {
    if (isCheckingOrders) return; // Evita execuções simultâneas
    isCheckingOrders = true;

    console.log('checkNewOrders');
    if (!userId) {
        isCheckingOrders = false;
        return;
    }
    
    try {
        console.log('[' + new Date().toLocaleTimeString() + '] Verificando pedidos...');
        const response = await fetch('../admin/verificar_novos_pedidos.php');
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        console.log('[' + new Date().toLocaleTimeString() + '] Resposta:', data);
        
        if (data.success && data.total_pendentes > 0) {
            await showNotification('Pedidos Pendentes', `Você tem ${data.total_pendentes} pedido(s) pendente(s) para revisar.`);
        }
    } catch (error) {
        console.error('[' + new Date().toLocaleTimeString() + '] Erro ao verificar pedidos:', error);
    } finally {
        isCheckingOrders = false;
    }
}

// Para o Cliente: Verificar atualizações do pedido
async function checkOrderStatus() {
    if (isCheckingStatus) return; // Evita execuções simultâneas
    isCheckingStatus = true;

    if (!userId || !currentOrderId) {
        isCheckingStatus = false;
        return;
    }
    
    try {
        const response = await fetch(`/check_order_status.php?order_id=${currentOrderId}`);
        const data = await response.json();
        
        if (data.statusChanged) {
            const statusText = data.newStatus.charAt(0).toUpperCase() + data.newStatus.slice(1);
            await showNotification(
                'Atualização do Pedido', 
                `Seu pedido agora está: ${statusText}`
            );
        }
    } catch (error) {
        console.error('Erro ao verificar status do pedido:', error);
    } finally {
        isCheckingStatus = false;
    }
}

// Inicializar as notificações
document.addEventListener('DOMContentLoaded', function() {
    requestNotificationPermission();
    
    // Verificar novos pedidos a cada 30 segundos (apenas para admin)
    if (typeof isAdmin !== 'undefined' && isAdmin && !orderCheckInterval) {
        orderCheckInterval = setInterval(checkNewOrders, 30000);
    }
    
    // Se houver um pedido atual, verificar status a cada 30 segundos
    if (currentOrderId && !statusCheckInterval) {
        statusCheckInterval = setInterval(checkOrderStatus, 30000);
    }
});