<?php
/*
Plugin Name: Cadastrador de Produtos
Description: Plugin para cadastrar e listar produtos no painel administrativo.
Version: 1.0
Author: Leonardo Silva
*/

// Cria a tabela de produtos ao ativar o plugin
include_once(plugin_dir_path(__FILE__) . 'listar-produtos.php');

register_activation_hook(__FILE__, 'criar_tabela_produtos');

function adicionar_scripts_admin()
{
    // Registrar o arquivo JavaScript para o admin panel
    wp_register_script('custom', plugins_url('js/custom.js', __FILE__), array(), '1.0', true);

    // Carregar o arquivo JavaScript registrado para o admin panel
    wp_enqueue_script('custom');
}
add_action('admin_enqueue_scripts', 'adicionar_scripts_admin');

function enqueue_plugin_styles() {
    wp_enqueue_style('plugin-style', plugins_url('/style.css', __FILE__));
}
add_action('wp_enqueue_scripts', 'enqueue_plugin_styles');


function adicionar_copy_admin()
{
    // Registrar o arquivo JavaScript para o admin panel
    wp_register_script('copy-text', plugins_url('js/copy-text.js', __FILE__), array(), '1.0', true);

    // Carregar o arquivo JavaScript registrado para o admin panel
    wp_enqueue_script('copy-text');
}
add_action('admin_enqueue_scripts', 'adicionar_copy_admin');

function criar_tabela_produtos()
{
    global $wpdb;
    $tabela_produtos = $wpdb->prefix . 'produtos';

    $sql = "CREATE TABLE IF NOT EXISTS $tabela_produtos (
        id INT NOT NULL AUTO_INCREMENT,
        nome_produto VARCHAR(255) NOT NULL,
        descricao_produto VARCHAR(1000),
        imagem_produto VARCHAR(255) NOT NULL,
        data_cadastro TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    )";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// Adiciona a página de cadastro de produtos no menu do WordPress
add_action('admin_menu', 'adicionar_menu_cadastro_produtos');

function adicionar_menu_cadastro_produtos()
{
    add_menu_page(
        'Cadastrar Produtos',
        'Cadastrar Produtos',
        'manage_options',
        'cadastrar-produtos',
        'formulario_cadastro_produtos',
        'dashicons-products',
        20
    );
}

// Função para exibir o formulário de cadastro de produtos
function formulario_cadastro_produtos()
{
    echo '<div class="wrap">';
    echo '<h1>Cadastrar Novo Produto</h1>';
    echo '<form id="formulario_cadastro" method="post" action="admin-post.php" enctype="multipart/form-data">';
    echo '<input type="hidden" name="action" value="cadastrar_produto">';
    echo '<label for="nome_produto">Nome do Produto:</label>';
    echo '<input type="text" id="nome_produto" name="nome_produto" />';
    echo '<label for="descricao_produto">Descricao do Produto:</label>';
    echo '<input type="text" id="descricao_produto" name="descricao_produto" />';
    echo '<label for="imagem_produto">Imagem do Produto:</label>';
    echo '<input type="file" id="imagem_produto" name="imagem_produto" accept="image/*" />';
    echo '<input type="submit" value="Cadastrar" class="button button-primary" />';
    echo '</form>';
    echo '</div>';
    echo '<div id="mensagem_sucesso" style="display: none;" class="notice notice-success">';
    echo '<p>Produto cadastrado com sucesso! <button id="fechar_mensagem">x</button></p>';
    echo '</div>';
}

// Função para cadastrar o produto no banco de dados
function cadastrar_produto()
{
    if (!current_user_can('manage_options')) {
        wp_die('Você não tem permissão para acessar esta página.');
    }
    global $wpdb;
    $tabela_produtos = $wpdb->prefix . 'produtos';

    if (isset($_POST['nome_produto'])) {
        $nome_produto = sanitize_text_field($_POST['nome_produto']);
        $descricao_produto = sanitize_text_field($_POST['descricao_produto']);
        // Processamento do upload da imagem
        $imagem_produto = ''; // Variável para armazenar o caminho da imagem
        if ($_FILES['imagem_produto']['error'] == 0) {
            // Verifica o tipo MIME do arquivo enviado (imagem)
            $file_type = wp_check_filetype($_FILES['imagem_produto']['name']);
            $allowed_types = array('image/jpeg', 'image/png');
            if (in_array($file_type['type'], $allowed_types)) {
                $upload_dir = wp_upload_dir(); // Diretório de uploads do WordPress
                $uploaded_file = $_FILES['imagem_produto']['tmp_name'];
                $file_name = $_FILES['imagem_produto']['name'];
                $file_name = sanitize_file_name($file_name);
                $unique_filename = md5(uniqid()) . '-' . $file_name; // Nome único com identificador único
                $upload_path = $upload_dir['path'] . '/' . $unique_filename;
                move_uploaded_file($uploaded_file, $upload_path);
                $imagem_produto = $upload_dir['url'] . '/' . $unique_filename; // Caminho completo da imagem

            } else {
                // Tipo de arquivo não permitido
                wp_die('O tipo de arquivo enviado não é suportado. Por favor, envie uma imagem JPEG, PNG.');
            }
        } else {
            // Erro no upload da imagem
            wp_die('Ocorreu um erro durante o upload da imagem.');
        }
        $dados_produto = array(
            'nome_produto' => $nome_produto,
            'descricao_produto' => $descricao_produto,
            'imagem_produto' => $imagem_produto, // Salva o caminho da imagem no banco
        );

        registrar_produto($nome_produto, $descricao_produto, $imagem_produto);
        $formato_dados = array(
            '%s', // Formato para o nome do produto
            '%s', // Formato para a descricao do produto
            '%s', // Formato para o caminho da imagem

        );
        $wpdb->insert($tabela_produtos, $dados_produto, $formato_dados);

        wp_redirect(admin_url('admin.php?page=cadastrar-produtos'));
        exit;
    }
}

add_action('admin_post_cadastrar_produto', 'cadastrar_produto');


// Função para listar os produtos cadastrados
function listar_produtos_admin()
{
    global $wpdb;
    $tabela_produtos = $wpdb->prefix . 'produtos';
    $itens_por_pagina = 10; // Defina o número de itens por página

    $upload_dir = wp_upload_dir(); // Diretório de uploads do WordPress

    // Verifica se o formulário de exclusão foi submetido
    if (isset($_POST['excluir_produtos'])) {
        $produtos_selecionados = isset($_POST['produtos_selecionados']) ? $_POST['produtos_selecionados'] : array();

        if (!empty($produtos_selecionados)) {
            foreach ($produtos_selecionados as $produto_id) {
                // Verifica se a imagem do produto existe antes de excluir
                $produto = $wpdb->get_row("SELECT imagem_produto FROM $tabela_produtos WHERE id = $produto_id");
                if ($produto && $produto->imagem_produto) {
                    $imagem_path = str_replace($upload_dir['url'], $upload_dir['path'], $produto->imagem_produto);
                    if (file_exists($imagem_path)) {
                        unlink($imagem_path); // Exclui a imagem do diretório de uploads
                    }
                }

                // Exclui o produto do banco de dados
                $wpdb->delete($tabela_produtos, array('id' => $produto_id));
            }
        }
    }

    if (isset($_POST['atualizar_produto'])) {
        $produto_id = isset($_POST['produto_id']) ? absint($_POST['produto_id']) : 0;
        $nome_produto = isset($_POST['nome_produto']) ? sanitize_text_field($_POST['nome_produto']) : '';
        $descricao_produto = isset($_POST['descricao_produto']) ? sanitize_textarea_field($_POST['descricao_produto']) : '';

        if ($produto_id && $nome_produto && $descricao_produto) {
            // Processa a troca da imagem, se um novo arquivo foi enviado
            if (!empty($_FILES['nova_imagem_produto']['name'])) {
                $upload_dir = wp_upload_dir();
                $upload_path = $upload_dir['path'];
                $uploaded_file = $_FILES['nova_imagem_produto'];

                // Move o arquivo para o diretório de uploads
                $file_name = wp_unique_filename($upload_path, $uploaded_file['name']);
                $file_path = $upload_path . '/' . $file_name;
                move_uploaded_file($uploaded_file['tmp_name'], $file_path);

                // Atualiza o caminho da nova imagem no banco de dados
                $wpdb->update(
                    $tabela_produtos,
                    array('imagem_produto' => $upload_dir['url'] . '/' . $file_name),
                    array('id' => $produto_id)
                );
            }

            // Atualiza os demais dados do produto
            $wpdb->update(
                $tabela_produtos,
                array(
                    'nome_produto' => $nome_produto,
                    'descricao_produto' => $descricao_produto,
                ),
                array('id' => $produto_id)
            );
            echo 'Produto atualizado com sucesso!';
        } else {
            echo 'Erro ao atualizar o produto.';
        }
    }

    // Obtém o número total de produtos
    $total_produtos = $wpdb->get_var("SELECT COUNT(*) FROM $tabela_produtos");

    // Calcula o número total de páginas
    $total_paginas = ceil($total_produtos / $itens_por_pagina);

    // Obtém a página atual
    $pagina_atual = isset($_GET['pagina']) ? absint($_GET['pagina']) : 1;

    // Calcula o deslocamento para a consulta SQL
    $offset = ($pagina_atual - 1) * $itens_por_pagina;

    // Verifica o filtro selecionado (recentes ou antigos)
    $filtro = isset($_GET['filtro']) ? $_GET['filtro'] : 'recentes';

    // Consulta SQL com limites, deslocamento e ordenação para paginação e filtros
    $query = "SELECT * FROM $tabela_produtos";
    if ($filtro == 'antigos') {
        $query .= " ORDER BY data_cadastro ASC";
    } else {
        $query .= " ORDER BY data_cadastro DESC";
    }
    $query .= " LIMIT $offset, $itens_por_pagina";

    $produtos = $wpdb->get_results($query);

    // Exibe a lista de produtos e controles de páginação
    echo '<div class="wrap">';
    echo '<h1>Listar Produtos</h1>';

    // Adiciona controles de filtro na interface
    echo '<div class="tablenav">';
    echo '<div class="tablenav-pages">';
    echo '<a class="first-page' . ($pagina_atual == 1 ? ' disabled' : '') . '" href="?page=listar-produtos&pagina=1">Primeira página </a>';

    // Link para a página anterior
    $pagina_anterior = max(1, $pagina_atual - 1);
    echo '<a class="prev-page' . ($pagina_atual == 1 ? ' disabled' : '') . '" href="?page=listar-produtos&pagina=' . $pagina_anterior . '">Anterior </a>';

    // Link para a página seguinte
    $pagina_seguinte = min($total_paginas, $pagina_atual + 1);
    echo '<a class="next-page' . ($pagina_atual == $total_paginas ? ' disabled' : '') . '" href="?page=listar-produtos&pagina=' . $pagina_seguinte . '">Próxima </a>';

    // Link para a última página
    echo '<a class="last-page' . ($pagina_atual == $total_paginas ? ' disabled' : '') . '" href="?page=listar-produtos&pagina=' . $total_paginas . '">Última página</a>';

    echo '<span class="displaying-num">Mostrando ' . count($produtos) . ' de ' . $total_produtos . ' produtos</span>';
    echo '<span class="pagination-links">';
    echo '<a href="?page=listar-produtos&pagina=' . $pagina_atual . '&filtro=recentes" class="' . ($filtro == 'recentes' ? 'current' : '') . '">Mais Recentes</a>';
    echo ' | ';
    echo '<a href="?page=listar-produtos&pagina=' . $pagina_atual . '&filtro=antigos" class="' . ($filtro == 'antigos' ? 'current' : '') . '">Mais Antigos</a>';
    echo '</span>';
    echo '</div>';
    echo '</div>';

    // Formulário para seleção de produtos para exclusão
    echo '<form method="post" action="" enctype="multipart/form-data">';
    echo '<input type="submit" name="excluir_produtos" value="Excluir Produtos Selecionados" class="button button-primary" />';
    echo '<ul>';

    if ($produtos) {
        foreach ($produtos as $produto) {
            echo '<li>';
        echo '<label>';
        echo '<input type="checkbox" name="produtos_selecionados[]" value="' . esc_attr($produto->id) . '" />';
        echo '<input type="text" id="nome_produto" name="nome_produto" value="' . esc_attr($produto->nome_produto) . '" />';
        echo '<textarea id="descricao_produto" name="descricao_produto">' . esc_textarea($produto->descricao_produto) . '</textarea>';
        echo '<input type="hidden" name="produto_id" value="' . esc_attr($produto->id) . '" />';
        echo '<img src="' . esc_url($produto->imagem_produto) . '" height="100" width="100"/>';
        echo '<label for="nova_imagem_produto">Nova Imagem do Produto:</label>';
        echo '<input type="file" name="nova_imagem_produto" id="nova_imagem_produto" />';
        echo '<input type="submit" name="atualizar_produto" value="Atualizar" />';
        echo '<button type="button" onclick="copiarAnuncio()" id="copiar_anuncio">Copiar</button>';
        echo '</label>';
        echo '</li>';
        }
    } else {
        echo '<li>Nenhum produto cadastrado ainda.</li>';
    }

    echo '</ul>';
    echo '</form>';
    echo '</div>';
}

// Função para adicionar a página de listagem de produtos no menu do WordPress
add_action('admin_menu', 'adicionar_menu_listar_produtos');

function adicionar_menu_listar_produtos()
{
    add_submenu_page(
        'cadastrar-produtos',
        'Listar Produtos',
        'Listar Produtos',
        'manage_options',
        'listar-produtos',
        'listar_produtos_admin'
    );
}

function registrar_tipo_produto() {
    $labels = array(
        'name' => 'Produtos',
        'singular_name' => 'Produto',
        'menu_name' => 'Produtos',
        'add_new' => 'Adicionar Novo',
        'add_new_item' => 'Adicionar Novo Produto',
        'edit_item' => 'Editar Produto',
        'new_item' => 'Novo Produto',
        'view_item' => 'Ver Produto',
        'view_items' => 'Ver Produtos',
        'search_items' => 'Buscar Produtos',
        'not_found' => 'Nenhum produto encontrado',
        'not_found_in_trash' => 'Nenhum produto encontrado na lixeira',
        'all_items' => 'Todos os Produtos',
        'archives' => 'Arquivos de Produtos',
        'attributes' => 'Atributos do Produto',
        'insert_into_item' => 'Inserir no Produto',
        'uploaded_to_this_item' => 'Enviado para este Produto',
        'featured_image' => 'Imagem em Destaque',
        'set_featured_image' => 'Definir Imagem em Destaque',
        'remove_featured_image' => 'Remover Imagem em Destaque',
        'use_featured_image' => 'Usar como Imagem em Destaque',
        'filter_items_list' => 'Filtrar lista de produtos',
        'items_list_navigation' => 'Navegação na lista de produtos',
        'items_list' => 'Lista de Produtos',
        'item_published' => 'Produto publicado',
        'item_published_privately' => 'Produto publicado em particular',
        'item_reverted_to_draft' => 'Produto revertido para rascunho',
        'item_scheduled' => 'Produto agendado',
        'item_updated' => 'Produto atualizado',
    );

    $args = array(
        'labels' => $labels,
        'public' => true,
        'has_archive' => true,
        'menu_icon' => 'dashicons-products',
        'supports' => array('title', 'editor', 'thumbnail'),
    );

    register_post_type('produto', $args);
}
add_action('init', 'registrar_tipo_produto');

function registrar_produto($nome_produto, $descricao_produto, $imagem_url) {
    $postarr = array(
        'post_title' => $nome_produto,
        'post_content' => $descricao_produto,
        'post_type' => 'produto',
        'post_status' => 'publish',
    );

    $post_id = wp_insert_post($postarr);

    if (!is_wp_error($post_id)) {
        // Se o post foi inserido com sucesso, podemos salvar outras informações, como a imagem em destaque
        if (!empty($imagem_url)) {
            $attachment_id = attachment_url_to_postid($imagem_url);
            if ($attachment_id) {
                set_post_thumbnail($post_id, $attachment_id);
            }
        }
    }

    return $post_id;
}

function alterar_link_produtos($permalink, $post) {
    if ($post->post_type === 'produto') {
        global $wpdb;

        $produto_id_bd = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}produtos WHERE %d",
            $post->ID
        ));

        $novo_permalink = home_url('/detalhes-do-produto/?produto_id=' . $produto_id_bd);

        return $novo_permalink;
    }

    return $permalink;
}

add_filter('post_type_link', 'alterar_link_produtos', 10, 2);

