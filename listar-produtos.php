<?php
function listar_produtos_com_links() {
    global $wpdb;
    $tabela_produtos = $wpdb->prefix . 'produtos';

    $query = "SELECT * FROM $tabela_produtos";
    $produtos = $wpdb->get_results($query);

    $output = '<div class="listagem-produtos">';
    $output .= '<div class="container">';

    if ($produtos) {
        $output .= '<ul>';
        foreach ($produtos as $produto) {
            $pagina_detalhes_slug = 'detalhes-do-produto'; 
            $url_detalhes = get_permalink(get_page_by_path($pagina_detalhes_slug)) . '?produto_id=' . $produto->id;
            $output .= '<li class="produto">';
            $output .= '<a target="_blank" href="' . esc_url($url_detalhes) . '">';
            $output .= '<img class="imagem_produto" src='.($produto->imagem_produto).' alt='.($produto->nome_produto).'>';
            $output .= '<p class="nome_produto">'.esc_html($produto->nome_produto) .'</p>';
            $output .= '<p class="descricao_produto">'.esc_html($produto->descricao_produto) .'</p>';
            $output .= '</a>';
            $output .= '</li>';
        }
        $output .= '</ul>';
    } else {
        $output .= '<p>Nenhum produto cadastrado ainda.</p>';
    }

    $output .= '</div>';
    $output .= '</div>';

    return $output;
}

add_shortcode('listar_produtos_com_links', 'listar_produtos_com_links');

function exibir_produto() {
    if (isset($_GET['produto_id'])) {
        global $wpdb;
        $tabela_produtos = $wpdb->prefix . 'produtos';
        $produto_id = $_GET['produto_id'];

        // Query para buscar o nome do produto com base no ID
        $query = "SELECT nome_produto, descricao_produto, imagem_produto FROM $tabela_produtos WHERE id = %d";
        $produto_detalhes = $wpdb->get_row($wpdb->prepare($query, $produto_id));

        // Verifica se o produto foi encontrado
        if ($produto_detalhes) {
            $output = '<div class="single-produtos">';
            $output .= '<div class="container">';
            $output .= '<div class="nome-produto">' . esc_html($produto_detalhes->nome_produto) . '</div>';
            $output .= '<div class="descricao-produto">' . esc_html($produto_detalhes->descricao_produto)  .'</div>';
            if ($produto_detalhes->imagem_produto) {
                $output .= '<div class="imagem-produto"><img src="' . esc_url($produto_detalhes->imagem_produto) . '" alt="Imagem do Produto" /></div>';
            }
            $output .= '</div></div>';

            return $output;
        } else {
            return '<p>Produto não encontrado.</p>';
        }
    } else {
        return '<p>Produto não encontrado.</p>';
    }
}

// Adiciona o shortcode para exibir o produto completo
add_shortcode('exibir_produto', 'exibir_produto');

