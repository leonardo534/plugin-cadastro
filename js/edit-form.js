document.addEventListener("DOMContentLoaded", function () {
  const botoesEditar = document.querySelectorAll(".editar-produto");
  const formsEditar = document.querySelectorAll(".form-editar-produto");

  botoesEditar.forEach((botao, index) => {
    botao.addEventListener("click", function () {
      formsEditar[index].classList.toggle("mostrar-form");
    });
  });
});
