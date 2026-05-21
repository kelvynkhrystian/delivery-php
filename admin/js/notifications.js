// Variável global para controlar o estado das notificações
let notificacoesAtivas = localStorage.getItem('notificacoesAtivas') !== 'false';

// Criar uma única instância do áudio com caminho absoluto correto
const notificationSound = new Audio('/gestao/assets/sounds/notification.mp3');

// Função para tocar o som de forma segura em dispositivos móveis
function playNotificationSound() {
    if (notificacoesAtivas) {
        // Em dispositivos móveis, precisamos de interação do usuário
        const isMobile = /iPhone|iPad|iPod|Android/i.test(navigator.userAgent);
        
        if (isMobile) {
            // Em dispositivos móveis, só tocamos o som se houver interação recente
            const lastInteraction = localStorage.getItem('lastInteraction');
            const now = Date.now();
            if (lastInteraction && now - lastInteraction < 300000) { // 5 minutos
                notificationSound.play().catch(error => {
                    console.log('Não foi possível tocar o som de notificação:', error);
                });
            }
        } else {
            notificationSound.play().catch(error => {
                console.log('Não foi possível tocar o som de notificação:', error);
            });
        }
    }
}

// Solicitar permissão de notificação quando o admin fizer login
function requestNotificationPermission() {
    if (!("Notification" in window)) {
        console.log("Este navegador não suporta notificações");
        return;
    }

    // Verificar se é iOS
    const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
    if (isIOS) {
        console.log("Notificações web não são suportadas no iOS");
        return;
    }

    if (Notification.permission !== "granted") {
        Notification.requestPermission();
    }
}

// Função para mostrar a notificação
function showNotification(pedidoId) {
    if (Notification.permission === "granted" && notificacoesAtivas) {
        const options = {
            body: `Pedido #${pedidoId} recebido!`,
            icon: '/gestao/admin/assets/images/logo.png',
            badge: '/gestao/admin/assets/images/badge.png',
            requireInteraction: true,
            vibrate: [100, 50, 100], // Adiciona vibração para dispositivos móveis
            tag: 'novo-pedido', // Agrupa notificações similares
            renotify: true // Notifica mesmo se já existir uma notificação com a mesma tag
        };

        // Tentar usar o service worker primeiro
        if ('serviceWorker' in navigator && navigator.serviceWorker.ready) {
            navigator.serviceWorker.ready.then(registration => {
                registration.showNotification("Novo Pedido!", options);
            });
        } else {
            // Fallback para notificação regular
            const notification = new Notification("Novo Pedido!", options);
            notification.onclick = function() {
                window.focus();
                notification.close();
                window.location.href = '/gestao/admin/pedidos.php';
            };
        }

        playNotificationSound();
    }
}

// Função para alternar as notificações
function toggleNotifications() {
    notificacoesAtivas = !notificacoesAtivas;
    localStorage.setItem('notificacoesAtivas', notificacoesAtivas);
    
    // Atualizar o ícone
    const notificationIcon = document.querySelector('button[onclick="toggleNotifications()"] i');
    if (notificationIcon) {
        notificationIcon.className = notificacoesAtivas ? 'fas fa-bell' : 'fas fa-bell-slash';
    }

    // Se ativou as notificações
    if (notificacoesAtivas) {
        // Solicitar permissão se ainda não foi concedida
        if (Notification.permission !== 'granted') {
            Notification.requestPermission().then(permission => {
                if (permission === 'granted') {
                    // Enviar notificação de teste
                    const notification = new Notification('Notificações Ativadas!', {
                        body: 'Você receberá alertas quando houver novos pedidos.',
                        icon: '/gestao/admin/assets/images/logo.png'
                    });
                    
                    playNotificationSound();
                }
            });
        } else {
            // Se já tem permissão, enviar notificação de teste
            const notification = new Notification('Notificações Ativadas!', {
                body: 'Você receberá alertas quando houver novos pedidos.',
                icon: '/gestao/admin/assets/images/logo.png'
            });
            
            playNotificationSound();
        }
    }
    
    // Mostrar alerta
    Swal.fire({
        title: notificacoesAtivas ? 'Notificações Ativadas!' : 'Notificações Desativadas',
        text: notificacoesAtivas ? 'Você receberá alertas sonoros de novos pedidos.' : 'Alertas sonoros desativados.',
        icon: notificacoesAtivas ? 'success' : 'info',
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000
    });
}

// Verificar novos pedidos a cada 1 minuto
function checkNewOrders() {
    const lastOrderId = localStorage.getItem('lastOrderId') || 0;
    console.log(`[${new Date().toLocaleTimeString()}] Verificando pedidos...`);
    
    fetch('verificar_novos_pedidos.php')
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            let shouldPlaySound = false;
            const isMobile = /iPhone|iPad|iPod|Android/i.test(navigator.userAgent);
            
            // Verificar novos pedidos
            console.log(`[${new Date().toLocaleTimeString()}] Último pedido no sistema: #${data.ultimo_pedido}, Último visto: #${lastOrderId}`);
            if (data.ultimo_pedido > lastOrderId) {
                console.log(`[${new Date().toLocaleTimeString()}] Novo pedido encontrado! #${data.ultimo_pedido}`);
                
                // Mostrar notificação push
                showNotification(data.ultimo_pedido);
                localStorage.setItem('lastOrderId', data.ultimo_pedido);
                shouldPlaySound = true;

                // Mostrar SweetAlert para novo pedido
                Swal.fire({
                    title: 'Novo Pedido!',
                    text: `Pedido #${data.ultimo_pedido} recebido!`,
                    icon: 'success',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Ver Pedido',
                    cancelButtonText: 'Depois',
                    toast: isMobile,
                    position: isMobile ? 'top' : 'center',
                    timer: isMobile ? 5000 : undefined,
                    timerProgressBar: isMobile,
                    showConfirmButton: !isMobile,
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = '/gestao/admin/pedidos.php';
                    }
                });
            }
            
            // Verificar pedidos pendentes
            if (data.pedidos_pendentes && data.pedidos_pendentes.length > 0 && notificacoesAtivas) {
                console.log(`[${new Date().toLocaleTimeString()}] Encontrados ${data.pedidos_pendentes.length} pedidos pendentes`);
                
                // Mostrar notificação com total de pedidos pendentes
                if (Notification.permission === "granted") {
                    const notification = new Notification("Pedidos Pendentes!", {
                        body: `Você tem ${data.pedidos_pendentes.length} pedido(s) aguardando atendimento!`,
                        icon: '/gestao/admin/assets/images/logo.png',
                        requireInteraction: true
                    });
                    
                    notification.onclick = function() {
                        window.focus();
                        notification.close();
                        window.location.href = '/gestao/admin/pedidos.php';
                    };
                    
                    // Se já não vai tocar som por causa do novo pedido
                    if (!shouldPlaySound) {
                        playNotificationSound();
                    }

                    // Controle de frequência dos alertas
                    const notifiedAlerts = JSON.parse(localStorage.getItem('notifiedAlerts') || '[]');
                    const currentTime = new Date().getTime();
                    // Limpar alertas antigos (mais de 1 minuto)
                    const recentAlerts = notifiedAlerts.filter(time => currentTime - time < 60000);
                    
                    // Se não mostrou alerta nos últimos 60 segundos
                    if (recentAlerts.length === 0) {
                        // Salvar o momento do alerta
                        recentAlerts.push(currentTime);
                        localStorage.setItem('notifiedAlerts', JSON.stringify(recentAlerts));

                        // Mostrar SweetAlert
                        Swal.fire({
                            title: 'Pedidos Pendentes!',
                            text: `Você tem ${data.pedidos_pendentes.length} pedido(s) aguardando atendimento!`,
                            icon: 'warning',
                            showCancelButton: !isMobile,
                            confirmButtonColor: '#3085d6',
                            cancelButtonColor: '#d33',
                            confirmButtonText: 'Ver Pedidos',
                            cancelButtonText: 'Fechar',
                            toast: isMobile,
                            position: isMobile ? 'top' : 'center',
                            timer: isMobile ? 5000 : undefined,
                            timerProgressBar: isMobile,
                            showConfirmButton: !isMobile,
                        }).then((result) => {
                            if (result.isConfirmed) {
                                window.location.href = '/gestao/admin/pedidos.php';
                            }
                        });
                    }
                }
            }
            
            // Agendar próxima verificação
            setTimeout(checkNewOrders, 60000);
        })
        .catch(error => {
            console.error('Erro ao verificar pedidos:', error);
            // Tentar novamente em caso de erro
            setTimeout(checkNewOrders, 60000);
        });
}

// Iniciar quando a página carregar
document.addEventListener('DOMContentLoaded', function() {
    requestNotificationPermission();
    checkNewOrders();
    setInterval(checkNewOrders, 60000); // Verificar a cada 1 minuto
    
    // Configurar o ícone inicial
    const notificationIcon = document.querySelector('button[onclick="toggleNotifications()"] i');
    if (notificationIcon) {
        notificationIcon.className = notificacoesAtivas ? 'fas fa-bell' : 'fas fa-bell-slash';
    }
});

// Registrar última interação do usuário
document.addEventListener('click', () => {
    localStorage.setItem('lastInteraction', Date.now());
});
