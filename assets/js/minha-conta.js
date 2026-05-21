// Função para editar perfil
async function editarPerfil() {
    const { value: formValues } = await Swal.fire({
        title: 'Editar Perfil',
        html: `
            <input id="nome" class="swal2-input" placeholder="Nome" value="${document.querySelector('[for="nome"] + p').textContent}">
            <input id="email" class="swal2-input" placeholder="Email" value="${document.querySelector('[for="email"] + p').textContent}">
            <input id="telefone" class="swal2-input" placeholder="Telefone" value="${document.querySelector('[for="telefone"] + p').textContent}">
        `,
        focusConfirm: false,
        showCancelButton: true,
        confirmButtonText: 'Salvar',
        cancelButtonText: 'Cancelar',
        preConfirm: () => {
            return {
                nome: document.getElementById('nome').value,
                email: document.getElementById('email').value,
                telefone: document.getElementById('telefone').value
            }
        }
    });

    if (formValues) {
        try {
            const response = await fetch('/gestao/api/atualizar_perfil.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(formValues)
            });

            const data = await response.json();

            if (!data.success) {
                throw new Error(data.error);
            }

            // Atualiza os dados na página
            document.querySelector('[for="nome"] + p').textContent = formValues.nome;
            document.querySelector('[for="email"] + p').textContent = formValues.email;
            document.querySelector('[for="telefone"] + p').textContent = formValues.telefone;

            Swal.fire({
                title: 'Sucesso!',
                text: 'Perfil atualizado com sucesso!',
                icon: 'success'
            });
        } catch (error) {
            Swal.fire({
                title: 'Erro!',
                text: error.message || 'Erro ao atualizar perfil',
                icon: 'error'
            });
        }
    }
}

// Função para definir endereço como padrão
async function definirEnderecoPadrao(enderecoId) {
    try {
        const response = await fetch('/gestao/api/definir_endereco_padrao.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ endereco_id: enderecoId })
        });

        const data = await response.json();

        if (!data.success) {
            throw new Error(data.error);
        }

        // Recarrega a página para atualizar a lista
        window.location.reload();

    } catch (error) {
        Swal.fire({
            title: 'Erro!',
            text: error.message || 'Erro ao definir endereço padrão',
            icon: 'error'
        });
    }
}

// Função para excluir endereço
async function excluirEndereco(enderecoId) {
    const result = await Swal.fire({
        title: 'Tem certeza?',
        text: "Esta ação não pode ser desfeita!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sim, excluir!',
        cancelButtonText: 'Cancelar'
    });

    if (result.isConfirmed) {
        try {
            const response = await fetch('/gestao/api/excluir_endereco.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ endereco_id: enderecoId })
            });

            const data = await response.json();

            if (!data.success) {
                throw new Error(data.error);
            }

            // Remove o endereço da lista
            document.querySelector(`[data-endereco-id="${enderecoId}"]`).remove();

            Swal.fire(
                'Excluído!',
                'Endereço excluído com sucesso.',
                'success'
            );
        } catch (error) {
            Swal.fire({
                title: 'Erro!',
                text: error.message || 'Erro ao excluir endereço',
                icon: 'error'
            });
        }
    }
}

// Função para editar endereço
async function editarEndereco(endereco) {
    const { value: formValues } = await Swal.fire({
        title: 'Editar Endereço',
        html: `
            <input id="nome" class="swal2-input" placeholder="Nome do endereço" value="${endereco.nome}">
            <input id="cep" class="swal2-input" placeholder="CEP" value="${endereco.cep}">
            <input id="rua" class="swal2-input" placeholder="Rua" value="${endereco.rua}">
            <input id="numero" class="swal2-input" placeholder="Número" value="${endereco.numero}">
            <input id="complemento" class="swal2-input" placeholder="Complemento" value="${endereco.complemento || ''}">
            <input id="bairro" class="swal2-input" placeholder="Bairro" value="${endereco.bairro}">
            <input id="cidade" class="swal2-input" placeholder="Cidade" value="${endereco.cidade}">
            <input id="estado" class="swal2-input" placeholder="Estado" value="${endereco.estado}">
        `,
        focusConfirm: false,
        showCancelButton: true,
        confirmButtonText: 'Salvar',
        cancelButtonText: 'Cancelar',
        preConfirm: () => {
            return {
                id: endereco.id,
                nome: document.getElementById('nome').value,
                cep: document.getElementById('cep').value,
                rua: document.getElementById('rua').value,
                numero: document.getElementById('numero').value,
                complemento: document.getElementById('complemento').value,
                bairro: document.getElementById('bairro').value,
                cidade: document.getElementById('cidade').value,
                estado: document.getElementById('estado').value
            }
        }
    });

    if (formValues) {
        try {
            const response = await fetch('/gestao/api/atualizar_endereco.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(formValues)
            });

            const data = await response.json();

            if (!data.success) {
                throw new Error(data.error);
            }

            // Recarrega a página para atualizar a lista
            window.location.reload();

        } catch (error) {
            Swal.fire({
                title: 'Erro!',
                text: error.message || 'Erro ao atualizar endereço',
                icon: 'error'
            });
        }
    }
}

// Event listener para o CEP nos modais do SweetAlert
document.addEventListener('input', function(e) {
    if (e.target && e.target.id === 'cep') {
        const cep = e.target.value.replace(/\D/g, '');
        if (cep.length === 8) {
            buscarCep(cep);
        }
    }
});
