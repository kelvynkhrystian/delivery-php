<?php
function renderEnderecoModal() {
    ?>
    <div id="endereco-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="flex flex-col space-y-4">
                <h3 class="text-lg font-medium">Selecione o Endereço de Entrega</h3>
                
                <!-- Lista de endereços salvos -->
                <div id="enderecos-salvos" class="space-y-2">
                    <?php
                    if (isset($_SESSION['user_id'])) {
                        $query = "SELECT * FROM enderecos_usuario WHERE usuario_id = ?";
                        $stmt = $db->prepare($query);
                        $stmt->execute([$_SESSION['user_id']]);
                        
                        while ($endereco = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            ?>
                            <div class="border p-3 rounded-lg hover:bg-gray-50 cursor-pointer"
                                 onclick="selecionarEndereco(<?php echo htmlspecialchars(json_encode($endereco)); ?>)">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <p class="font-medium"><?php echo htmlspecialchars($endereco['nome']); ?></p>
                                        <p class="text-sm text-gray-600">
                                            <?php echo htmlspecialchars("{$endereco['rua']}, {$endereco['numero']}"); ?>
                                            <?php if ($endereco['complemento']) echo " - " . htmlspecialchars($endereco['complemento']); ?>
                                        </p>
                                        <p class="text-sm text-gray-600">
                                            <?php echo htmlspecialchars("{$endereco['bairro']}, {$endereco['cidade']} - {$endereco['estado']}"); ?>
                                        </p>
                                    </div>
                                    <?php if ($endereco['padrao']) { ?>
                                        <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded">Padrão</span>
                                    <?php } ?>
                                </div>
                            </div>
                            <?php
                        }
                    }
                    ?>
                </div>

                <!-- Botão para adicionar novo endereço -->
                <button onclick="mostrarFormNovoEndereco()" 
                        class="w-full py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                    Adicionar Novo Endereço
                </button>
            </div>
        </div>
    </div>

    <!-- Modal de novo endereço -->
    <div id="novo-endereco-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <form id="form-novo-endereco" class="space-y-4">
                <h3 class="text-lg font-medium">Novo Endereço</h3>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700">Nome do Endereço</label>
                    <input type="text" name="nome" placeholder="Ex: Casa, Trabalho" required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">CEP</label>
                    <input type="text" name="cep" id="cep" placeholder="00000-000" required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Rua</label>
                    <input type="text" name="rua" id="rua" required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Número</label>
                        <input type="text" name="numero" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Complemento</label>
                        <input type="text" name="complemento"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Bairro</label>
                    <input type="text" name="bairro" id="bairro" required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Cidade</label>
                        <input type="text" name="cidade" id="cidade" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Estado</label>
                        <input type="text" name="estado" id="estado" maxlength="2" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                </div>

                <div class="flex items-center">
                    <input type="checkbox" name="padrao" id="padrao"
                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <label for="padrao" class="ml-2 block text-sm text-gray-900">
                        Definir como endereço padrão
                    </label>
                </div>

                <div class="flex justify-end space-x-2">
                    <button type="button" onclick="fecharFormNovoEndereco()"
                            class="px-4 py-2 border rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Cancelar
                    </button>
                    <button type="submit"
                            class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                        Salvar
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php
}

function renderPagamentoModal() {
    ?>
    <div id="pagamento-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="flex flex-col space-y-4">
                <h3 class="text-lg font-medium">Forma de Pagamento</h3>
                
                <?php
                $query = "SELECT * FROM formas_pagamento WHERE ativo = 1";
                $stmt = $db->query($query);
                while ($forma = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    ?>
                    <div class="border p-3 rounded-lg hover:bg-gray-50 cursor-pointer"
                         onclick="selecionarPagamento(<?php echo htmlspecialchars(json_encode($forma)); ?>)">
                        <div class="flex items-center justify-between">
                            <span class="font-medium"><?php echo htmlspecialchars($forma['nome']); ?></span>
                            <i class="fas fa-chevron-right text-gray-400"></i>
                        </div>
                    </div>
                    <?php
                }
                ?>
            </div>
        </div>
    </div>

    <!-- Modal de troco -->
    <div id="troco-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="flex flex-col space-y-4">
                <h3 class="text-lg font-medium">Precisa de Troco?</h3>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700">Troco para quanto?</label>
                    <input type="number" id="troco-para" step="0.01" min="0"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div class="flex justify-end space-x-2">
                    <button onclick="naoQueroTroco()"
                            class="px-4 py-2 border rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Não Preciso
                    </button>
                    <button onclick="confirmarTroco()"
                            class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                        Confirmar
                    </button>
                </div>
            </div>
        </div>
    </div>
    <?php
}
?>
