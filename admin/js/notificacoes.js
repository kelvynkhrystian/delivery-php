// Som de notificação
const somNotificacao = new Audio('../assets/sounds/notification.mp3');

// Armazenar último ID de pedido notificado
let ultimoPedidoNotificado = localStorage.getItem('ultimoPedidoNotificado') || 0;

// Variável para controlar se as notificações estão ativas
let notificacoesAtivas = localStorage.getItem('notificacoesAtivas') !== 'false';

// Função para tocar o som
function tocarSomNotificacao() {
    somNotificacao.play().catch(error => {
        console.error('Erro ao tocar som:', error);
    });
}

// Função para verificar novos pedidos
function verificarNovosPedidos() {
    fetch('/api/pedidos/verificar_novos.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('Erro na resposta do servidor');
            }
            return response.json();
        })
        .then(data => {
            if (data.novos_pedidos && data.novos_pedidos.length > 0) {
                // Filtra apenas pedidos mais recentes que o último notificado
                const novos = data.novos_pedidos.filter(pedido => pedido.id > ultimoPedidoNotificado);
                
                if (novos.length > 0) {
                    // Atualiza o último ID notificado
                    ultimoPedidoNotificado = Math.max(...novos.map(p => p.id));
                    localStorage.setItem('ultimoPedidoNotificado', ultimoPedidoNotificado);
                    
                    // Toca o som
                    tocarSomNotificacao();
                    
                    // Atualiza o contador de pedidos
                    const contador = document.getElementById('contador-pedidos');
                    if (contador) {
                        contador.textContent = data.total_pedidos;
                    }
                    
                    // Mostra notificação
                    Swal.fire({
                        title: 'Novo Pedido!',
                        text: `Você tem ${novos.length} novo(s) pedido(s)!`,
                        icon: 'info',
                        confirmButtonText: 'Ver Pedidos',
                        showCancelButton: true,
                        cancelButtonText: 'Depois'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = '/pedidos.php';
                        }
                    });

                    // Atualiza a lista de pedidos se estiver na página de pedidos
                    if (typeof atualizarListaPedidos === 'function') {
                        atualizarListaPedidos();
                    }
                }
            }
        })
        .catch(error => {
            console.error('Erro ao verificar pedidos:', error);
        });
}

// Função para notificar sobre novo pedido
function notificarNovoPedido(pedidoId) {
    if (notificacoesAtivas) {
        // Tocar som
        somNotificacao.play().catch(e => console.error('Erro ao tocar som:', e));

        // Mostrar notificação Swal
        Swal.fire({
            title: 'Novo Pedido!',
            text: `Pedido #${pedidoId} recebido!`,
            icon: 'info',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Ver Pedido',
            cancelButtonText: 'Depois'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = '/pedidos.php';
            }
        });
    }
}

// Verifica novos pedidos a cada 30 segundos
setInterval(verificarNovosPedidos, 30000);

// Verifica assim que a página carrega
document.addEventListener('DOMContentLoaded', verificarNovosPedidos);
