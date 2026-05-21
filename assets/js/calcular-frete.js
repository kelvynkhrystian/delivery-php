async function calcularFrete(endereco) {
    try {
        const response = await fetch('/gestao/api/calcular_frete.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ endereco })
        });

        const data = await response.json();

        if (!data.success) {
            throw new Error(data.error);
        }

        // Atualiza os elementos na página
        document.getElementById('valor-frete').textContent = 
            `R$ ${data.valor_frete.toFixed(2).replace('.', ',')}`;
        document.getElementById('distancia').textContent = 
            `${data.distancia.toFixed(1)}km`;
        document.getElementById('endereco-formatado').value = 
            data.endereco_formatado;

        // Atualiza o valor total se necessário
        const subtotal = parseFloat(document.getElementById('subtotal').textContent);
        document.getElementById('total').textContent = 
            `R$ ${(subtotal + data.valor_frete).toFixed(2).replace('.', ',')}`;

        return data;

    } catch (error) {
        Swal.fire({
            title: 'Erro!',
            text: error.message || 'Erro ao calcular o frete',
            icon: 'error'
        });
        return null;
    }
}

// Exemplo de uso com um campo de endereço
document.addEventListener('DOMContentLoaded', function() {
    const enderecoInput = document.getElementById('endereco');
    const calcularFreteBtn = document.getElementById('calcular-frete');

    if (calcularFreteBtn) {
        calcularFreteBtn.addEventListener('click', async () => {
            const endereco = enderecoInput.value;
            if (!endereco) {
                Swal.fire({
                    title: 'Atenção!',
                    text: 'Por favor, digite um endereço',
                    icon: 'warning'
                });
                return;
            }

            // Mostra loading
            calcularFreteBtn.disabled = true;
            calcularFreteBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Calculando...';

            await calcularFrete(endereco);

            // Remove loading
            calcularFreteBtn.disabled = false;
            calcularFreteBtn.innerHTML = 'Calcular Frete';
        });
    }
});
