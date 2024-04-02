let nome_produto = document.getElementById("nome_produto").value;
let descricao_produto = document.getElementById("descricao_produto").value;
let aviso = "⚠️ Essa oferta pode encerrar a qualquer momento";

function copiarAnuncio() {
  let copyText;
  copyText = `*${nome_produto}*\n ${descricao_produto}`
  console.log(copyText);
  // copyText.setSelectionRange(0, 99999); // For mobile devices

  navigator.clipboard.writeText(copyText);

}
