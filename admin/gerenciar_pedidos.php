<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Pedidos - Delivery App</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold">Gerenciar Pedidos</h1>
            <a href="dashboard.php" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
                <i class="fas fa-arrow-left mr-2"></i>Voltar
            </a>
        </div>

        <!-- Lista de Pedidos -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pedido</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cliente</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Valor</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($pedidos)): ?>
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                            Nenhum pedido encontrado
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($pedidos as $pedido): ?>
                        <tr>
                            <td class="px-6 py-4">#<?php echo $pedido['id']; ?></td>
                            <td class="px-6 py-4">
                                <?php echo htmlspecialchars($pedido['nome']); ?><br>
                                <span class="text-sm text-gray-500"><?php echo htmlspecialchars($pedido['telefone']); ?></span>
                            </td>
                            <td class="px-6 py-4">R$ <?php echo number_format($pedido['total'], 2, ',', '.'); ?></td>
                            <td class="px-6 py-4">
                                <form method="POST" class="inline-flex space-x-2">
                                    <input type="hidden" name="pedido_id" value="<?php echo $pedido['id']; ?>">
                                    <select name="novo_status" onchange="this.form.submit()" 
                                            class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <?php foreach ($status_list as $status): ?>
                                            <option value="<?php echo $status; ?>" 
                                                    <?php echo $pedido['status'] === $status ? 'selected' : ''; ?>>
                                                <?php echo ucfirst(str_replace('_', ' ', $status)); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="hidden" name="atualizar_status" value="1">
                                </form>
                            </td>
                            <td class="px-6 py-4">
                                <?php echo date('d/m/Y H:i', strtotime($pedido['created_at'])); ?>
                            </td>
                            <td class="px-6 py-4">
                                <button onclick="verDetalhes(<?php echo $pedido['id']; ?>)"
                                        class="text-blue-600 hover:text-blue-900">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Detalhes do Pedido -->
    <div id="modal-detalhes" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-[90%] max-w-2xl shadow-lg rounded-md bg-white">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-medium text-gray-900">Detalhes do Pedido</h3>
                <button onclick="document.getElementById('modal-detalhes').classList.add('hidden')"
                        class="text-gray-400 hover:text-gray-500">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="detalhes-content" class="space-y-4">
                <!-- Conteúdo será preenchido via JavaScript -->
            </div>
        </div>
    </div>

    <script>
    async function verDetalhes(pedidoId) {
        try {
            const response = await fetch(`../api/pedidos/detalhes.php?id=${pedidoId}`);
            const data = await response.json();
            
            if (data.success) {
                const pedido = data.pedido;
                let html = `
                    <div class="border-b pb-4">
                        <p class="font-bold">Cliente</p>
                        <p>${pedido.cliente_nome}</p>
                        <p class="text-gray-600">${pedido.cliente_telefone}</p>
                    </div>
                    <div class="border-b pb-4">
                        <p class="font-bold">Endereço de Entrega</p>
                        <p>${pedido.endereco_entrega}</p>
                    </div>
                    <div class="border-b pb-4">
                        <p class="font-bold">Forma de Pagamento</p>
                        <p>${pedido.forma_pagamento}</p>
                        ${pedido.precisa_troco ? `
                        <p class="mt-2"><strong>Troco para:</strong> R$ ${pedido.troco_para.toFixed(2)}</p>
                        <p><strong>Valor do troco:</strong> R$ ${(pedido.troco_para - pedido.total).toFixed(2)}</p>
                        ` : ''}
                    </div>
                    <div class="border-b pb-4">
                        <p class="font-bold">Itens do Pedido</p>
                        <ul class="space-y-2">
                `;
                
                pedido.items.forEach(item => {
                    html += `
                        <li class="border-b last:border-b-0 py-2">
                            <div class="flex justify-between">
                                <span>${item.quantidade}x ${item.nome}</span>
                                <span>R$ ${item.preco_unitario.toFixed(2)}</span>
                            </div>`;
                    
                    if (item.complementos && item.complementos.length > 0) {
                        item.complementos.forEach(comp => {
                            html += `
                            <div class="flex justify-between pl-4 text-sm text-gray-600">
                                <span>${comp.complemento_nome}: ${comp.opcao_nome}</span>
                                <span>+ R$ ${(comp.opcao_preco * item.quantidade).toFixed(2)}</span>
                            </div>`;
                        });
                    }
                    
                    html += `
                        <div class="flex justify-between font-semibold mt-1">
                            <span>Total do item</span>
                            <span>R$ ${item.total_item.toFixed(2)}</span>
                        </div>
                    </li>`;
                });
                
                html += `
                        </ul>
                    </div>
                    <div class="pt-4 space-y-2">
                        <div class="flex justify-between">
                            <span>Subtotal</span>
                            <span>R$ ${pedido.subtotal.toFixed(2)}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Taxa de entrega</span>
                            <span>R$ ${pedido.taxa_entrega.toFixed(2)}</span>
                        </div>
                        <div class="flex justify-between font-bold text-lg">
                            <span>Total</span>
                            <span>R$ ${pedido.total.toFixed(2)}</span>
                        </div>
                        ${pedido.precisa_troco ? `
                        <div class="flex justify-between mt-2">
                            <span>Troco para</span>
                            <span>R$ ${pedido.troco_para.toFixed(2)}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Valor do troco</span>
                            <span>R$ ${(pedido.troco_para - pedido.total).toFixed(2)}</span>
                        </div>
                        ` : ''}
                    </div>
                `;
                
                document.getElementById('detalhes-content').innerHTML = html;
                document.getElementById('modal-detalhes').classList.remove('hidden');
            } else {
                alert('Erro ao carregar detalhes do pedido');
            }
        } catch (error) {
            console.error('Erro:', error);
            alert('Erro ao carregar detalhes do pedido');
        }
    }
    </script>
    <script src="js/push-notifications.js"></script>
    <script src="https://cdn.onesignal.com/sdks/OneSignalSDK.js" async=""></script>
    <script>
        window.OneSignal = window.OneSignal || [];
        OneSignal.push(function() {
            OneSignal.init({
                appId: "SEU_APP_ID", // Você vai pegar isso no OneSignal
            });
        });
    </script>
    <script>
        const isAdmin = true; // Identifica que é página do admin
    </script>
    <script src="js/notifications.js"></script>
</body>
</html>
