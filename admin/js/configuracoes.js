// Configurações do sistema
const configuracoes = {
    // Carrega as configurações salvas ou usa os valores padrão
    carregar() {
        const configs = JSON.parse(localStorage.getItem('adminConfigs')) || {
            notificacoes: true,
            somNotificacoes: true,
            impressora: '58mm' // Opções: '58mm', '80mm', 'A4'
        };
        return configs;
    },

    // Salva as configurações
    salvar(novasConfigs) {
        localStorage.setItem('adminConfigs', JSON.stringify(novasConfigs));
    },

    // Solicita permissão para notificações
    async solicitarPermissaoNotificacao() {
        try {
            const permissao = await Notification.requestPermission();
            const configs = this.carregar();
            configs.notificacoes = (permissao === 'granted');
            this.salvar(configs);
            return permissao === 'granted';
        } catch (error) {
            console.error('Erro ao solicitar permissão:', error);
            return false;
        }
    },

    // Verifica se as notificações estão disponíveis
    verificarNotificacoes() {
        return "Notification" in window;
    }
};

// Função para imprimir pedido
function imprimirPedido(pedidoId, formato = null) {
    // Se não foi especificado um formato, usa o das configurações
    if (!formato) {
        formato = configuracoes.carregar().impressora;
    }

    // URL base para impressão
    let url = `imprimir_pedido.php?id=${pedidoId}&formato=${formato}`;
    
    // Abre em uma nova janela
    window.open(url, '_blank', 'width=400,height=600');
}

// Funções para gerenciamento de tema
function selecionarTema(tema) {
    const formData = new FormData();
    formData.append('secao', 'design');
    formData.append('tema_cor', tema);
    
    if (tema === 'personalizado') {
        const corPersonalizada = document.getElementById('cor_personalizada').value;
        formData.append('cor_personalizada', corPersonalizada);
        document.getElementById('cor_personalizada').style.display = 'block';
    } else {
        document.getElementById('cor_personalizada').style.display = 'none';
    }
    
    // Atualiza a aparência dos botões
    document.querySelectorAll('[onclick^="selecionarTema"]').forEach(btn => {
        btn.classList.remove('active');
    });
    event.target.classList.add('active');
    
    salvarConfiguracoes(formData, 'Tema atualizado com sucesso!');
}

// Função para gerenciar tipo de entrega
function selecionarTipoEntrega(tipo) {
    const formData = new FormData();
    formData.append('secao', 'entrega');
    formData.append('tipo_entrega', tipo);
    
    // Atualiza a aparência dos botões
    document.querySelectorAll('[onclick^="selecionarTipoEntrega"]').forEach(btn => {
        btn.classList.remove('bg-blue-500', 'text-white');
        btn.classList.add('bg-gray-100');
    });
    event.target.classList.remove('bg-gray-100');
    event.target.classList.add('bg-blue-500', 'text-white');
    
    salvarConfiguracoes(formData, 'Tipo de entrega atualizado com sucesso!');
}

// Função genérica para salvar configurações
function salvarConfiguracoes(formData, mensagemSucesso) {
    fetch('salvar_configuracoes.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            mostrarAlerta(mensagemSucesso, 'success');
        } else {
            mostrarAlerta(data.message || 'Erro ao salvar as configurações.', 'error');
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        mostrarAlerta('Erro ao salvar as configurações.', 'error');
    });
}

// Função para mostrar alertas
function mostrarAlerta(mensagem, tipo) {
    const alertaDiv = document.createElement('div');
    alertaDiv.className = `fixed top-4 right-4 p-4 rounded-lg ${tipo === 'success' ? 'bg-green-500' : 'bg-red-500'} text-white`;
    alertaDiv.textContent = mensagem;
    document.body.appendChild(alertaDiv);
    
    setTimeout(() => {
        alertaDiv.remove();
    }, 3000);
}

// Event listener para o color picker personalizado
document.addEventListener('DOMContentLoaded', function() {
    const corPersonalizada = document.getElementById('cor_personalizada');
    if (corPersonalizada) {
        corPersonalizada.addEventListener('change', function() {
            selecionarTema('personalizado');
        });
    }
});

// Função para adicionar nova faixa de distância
function adicionarDistancia() {
    const inicio = document.getElementById('de_km').value;
    const fim = document.getElementById('ate_km').value;
    const valor = document.getElementById('valor_km').value;
    
    if (!inicio || !fim || !valor) {
        mostrarAlerta('Preencha todos os campos', 'error');
        return;
    }

    if (parseFloat(inicio) >= parseFloat(fim)) {
        mostrarAlerta('O valor inicial deve ser menor que o valor final', 'error');
        return;
    }

    const formData = new FormData();
    formData.append('inicio', inicio);
    formData.append('fim', fim);
    formData.append('valor', valor);

    fetch('salvar_faixas.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            mostrarAlerta('Faixa de distância adicionada com sucesso!', 'success');
            document.getElementById('de_km').value = '';
            document.getElementById('ate_km').value = '';
            document.getElementById('valor_km').value = '';
            carregarFaixasKm();
        } else {
            mostrarAlerta(data.message || 'Erro ao adicionar faixa de distância', 'error');
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        mostrarAlerta('Erro ao adicionar faixa de distância', 'error');
    });
}

// Função para excluir faixa de distância
function excluirFaixaKm(id) {
    if (!confirm('Tem certeza que deseja excluir esta faixa de distância?')) {
        return;
    }

    fetch('excluir_faixa.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ id: id })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            mostrarAlerta('Faixa de distância excluída com sucesso!', 'success');
            carregarFaixasKm();
        } else {
            mostrarAlerta(data.message || 'Erro ao excluir faixa de distância', 'error');
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        mostrarAlerta('Erro ao excluir faixa de distância', 'error');
    });
}

// Função para carregar faixas de distância
function carregarFaixasKm() {
    fetch('listar_faixas_km.php')
    .then(response => response.json())
    .then(data => {
        const lista = document.getElementById('lista_distancias');
        lista.innerHTML = '';
        
        if (data.success && data.faixas.length > 0) {
            data.faixas.forEach(faixa => {
                const div = document.createElement('div');
                div.className = 'faixa-item flex items-center justify-between bg-white p-3 rounded-lg shadow-sm';
                div.innerHTML = `
                    <span>De ${faixa.inicio} até ${faixa.fim} km - R$ ${parseFloat(faixa.valor).toFixed(2)}</span>
                    <button onclick="excluirFaixaKm(${faixa.id})" class="text-red-500 hover:text-red-700">
                        <i class="fas fa-trash"></i>
                    </button>
                `;
                lista.appendChild(div);
            });
        } else {
            lista.innerHTML = '<p class="text-gray-500 text-center">Nenhuma faixa de distância cadastrada</p>';
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        mostrarAlerta('Erro ao carregar faixas de distância', 'error');
    });
}
