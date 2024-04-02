document.addEventListener('DOMContentLoaded', () => {
  let formularioCadastro = document.getElementById('formulario_cadastro');
  if(formularioCadastro) {
      let mensagemSucesso = document.getElementById('mensagem_sucesso');
      let fecharBotao = document.getElementById('fechar_mensagem');
    
      formularioCadastro.addEventListener('submit', function(event) {
          // event.preventDefault(); // Evita o envio padrão do formulário
    
          let nomeProduto = document.getElementById('nome_produto').value.trim();
    
          if (nomeProduto !== '') {
              mensagemSucesso.style.display = 'block'; // Exibe a mensagem de sucesso
          }
      });
    
      fecharBotao.addEventListener('click', function() {
          mensagemSucesso.style.display = 'none'; // Oculta a mensagem de sucesso ao clicar no botão "Fechar"
      });

  }
});
