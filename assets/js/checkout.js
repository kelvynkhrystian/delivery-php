// Variáveis globais para armazenar as seleções
let enderecoSelecionado = null;
let pagamentoSelecionado = null;
let trocoPara = null;

// Funções para o modal de endereço
function mostrarModalEndereco() {
    document.getElementById('endereco-modal').classList.remove('hidden');
}

function fecharModalEndereco() {
    document.getElementById('endereco-modal').classList.add('hidden');
}

function mostrarFormNovoEndereco() {
    document.getElementById('endereco-modal').classList.add('hidden');
    document.getElementById('novo-endereco-modal').classList.remove('hidden');
}

function fecharFormNovoEndereco() {
    document.getElementById('novo-endereco-modal').classList.add('hidden');
    document.getElementById('endereco-modal').classList.remove('hidden');
}

// Funções para o modal de pagamento
function mostrarModalPagamento() {
    document.getElementById('pagamento-modal').classList.remove('hidden');
}

function fecharModalPagamento() {
    document.getElementById('pagamento-modal').classList.add('hidden');
}

function mostrarModalTroco() {
    document.getElementById('pagamento-modal').classList.add('hidden');
    document.getElementById('troco-modal').classList.remove('hidden');
}

function fecharModalTroco() {
    document.getElementById('troco-modal').classList.add('hidden');
    document.getElementById('pagamento-modal').classList.remove('hidden');
}

// Função para buscar endereço pelo CEP
async function buscarCep(cep) {
    try {
        const response = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
        const data = await response.json();
        
        if (data.erro) {
            throw new Error('CEP não encontrado');
        }

        document.getElementById('rua').value = data.logradouro;
        document.getElementById('bairro').value = data.bairro;
        document.getElementById('cidade').value = data.localidade;
        document.getElementById('estado').value = data.uf;
    } catch (error) {
        Swal.fire({
            title: 'Erro!',
            text: error.message || 'Erro ao buscar CEP',
            icon: 'error'
        });
    }
}

// Event listeners para o CEP
document.getElementById('cep').addEventListener('blur', (e) => {
    const cep = e.target.value.replace(/\D/g, '');
    if (cep.length === 8) {
        buscarCep(cep);
    }
});

// Função para salvar novo endereço
async function salvarNovoEndereco(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    const endereco = Object.fromEntries(formData.entries());
    
    try {
        const response = await fetch('/gestao/api/salvar_endereco.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(endereco)
        });

        const data = await response.json();

        if (!data.success) {
            throw new Error(data.error);
        }

        // Atualiza a lista de endereços
        await atualizarListaEnderecos();
        
        // Fecha o modal de novo endereço
        fecharFormNovoEndereco();
        
        Swal.fire({
            title: 'Sucesso!',
            text: 'Endereço salvo com sucesso!',
            icon: 'success'
        });
    } catch (error) {
        Swal.fire({
            title: 'Erro!',
            text: error.message || 'Erro ao salvar endereço',
            icon: 'error'
        });
    }
}

// Função para selecionar endereço
function selecionarEndereco(endereco) {
    enderecoSelecionado = endereco;
    
    // Atualiza o texto do endereço selecionado
    document.getElementById('endereco-selecionado').innerHTML = `
        <p class="font-medium">${endereco.nome}</p>
        <p class="text-sm text-gray-600">
            ${endereco.rua}, ${endereco.numero}
            ${endereco.complemento ? ` - ${endereco.complemento}` : ''}
        </p>
        <p class="text-sm text-gray-600">
            ${endereco.bairro}, ${endereco.cidade} - ${endereco.estado}
        </p>
    `;
    
    // Calcula o frete
    calcularFrete(endereco);
    
    // Fecha o modal
    fecharModalEndereco();
}

// Função para selecionar forma de pagamento
function selecionarPagamento(forma) {
    pagamentoSelecionado = forma;
    
    // Se for dinheiro, pergunta sobre o troco
    if (forma.requer_troco) {
        mostrarModalTroco();
    } else {
        // Atualiza o texto da forma de pagamento selecionada
        document.getElementById('pagamento-selecionado').textContent = forma.nome;
        fecharModalPagamento();
    }
}

// Funções para troco
function confirmarTroco() {
    const valor = document.getElementById('troco-para').value;
    if (!valor || valor <= 0) {
        Swal.fire({
            title: 'Atenção!',
            text: 'Digite um valor válido para o troco',
            icon: 'warning'
        });
        return;
    }
    
    trocoPara = parseFloat(valor);
    document.getElementById('pagamento-selecionado').textContent = 
        `Dinheiro - Troco para R$ ${valor}`;
    fecharModalTroco();
}

function naoQueroTroco() {
    trocoPara = null;
    document.getElementById('pagamento-selecionado').textContent = 'Dinheiro - Sem troco';
    fecharModalTroco();
}

// Função para finalizar pedido
async function finalizarPedido() {
    if (!enderecoSelecionado) {
        Swal.fire({
            title: 'Atenção!',
            text: 'Selecione um endereço de entrega',
            icon: 'warning'
        });
        return;
    }
    
    if (!pagamentoSelecionado) {
        Swal.fire({
            title: 'Atenção!',
            text: 'Selecione uma forma de pagamento',
            icon: 'warning'
        });
        return;
    }
    
    try {
        const pedido = {
            endereco_id: enderecoSelecionado.id,
            forma_pagamento_id: pagamentoSelecionado.id,
            troco_para: trocoPara,
            valor_frete: document.getElementById('valor-frete').dataset.valor
        };
        
        const response = await fetch('/gestao/api/finalizar_pedido.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(pedido)
        });

        const data = await response.json();

        if (!data.success) {
            throw new Error(data.error);
        }

        // Redireciona para a página de confirmação
        window.location.href = `/gestao/pedido_confirmado.php?id=${data.pedido_id}`;
        
    } catch (error) {
        Swal.fire({
            title: 'Erro!',
            text: error.message || 'Erro ao finalizar pedido',
            icon: 'error'
        });
    }
}

// Inicialização
document.addEventListener('DOMContentLoaded', function() {
    // Adiciona os event listeners
    document.getElementById('form-novo-endereco').addEventListener('submit', salvarNovoEndereco);
    
    // Fecha os modais quando clica fora
    window.addEventListener('click', function(event) {
        if (event.target.classList.contains('fixed')) {
            event.target.classList.add('hidden');
        }
    });
});
