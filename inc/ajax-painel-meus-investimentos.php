<?php
add_action('wp_ajax_filtrar_meus_investimentos', 'filtrar_meus_investimentos');
add_action('wp_ajax_nopriv_filtrar_meus_investimentos', 'filtrar_meus_investimentos');

function filtrar_meus_investimentos() {
    // Verificação de segurança
    if (!isset($_POST['nonce'])) wp_send_json_error('Nonce não encontrado', 400);
    if (!wp_verify_nonce($_POST['nonce'], 'filtrar_meus_investimentos_nonce')) wp_send_json_error('Nonce inválido', 403);

    // Configurar query
    $args = [
        'post_type' => 'investment',
        'posts_per_page' => 6,
        'paged' => max(1, absint($_POST['pagina'] ?? 1)),
        'post__in' => json_decode($_POST['ids']),
        'post_status' => 'publish'
    ];

    // Aplicar filtros
    $meta_query = [];
    $tax_query = [];

    // Filtro por tipo de produto
    if (!empty($_POST['tipo_produto'])) {
        $tax_query[] = [
            'taxonomy' => 'tipo_produto',
            'field' => 'slug',
            'terms' => sanitize_text_field($_POST['tipo_produto'])
        ];
    }

    // Filtro por status
    if (!empty($_POST['status'])) {
        $meta_query[] = [
            'key' => 'encerrado',
            'value' => ($_POST['status'] === 'encerrado') ? '1' : '0',
            'compare' => '='
        ];
    }

    // Ordenação
    if (!empty($_POST['ordem'])) {
        $args['order'] = ($_POST['ordem'] === 'ASC') ? 'ASC' : 'DESC';
        $args['orderby'] = 'date';
    }

    // Montar queries
    if (!empty($tax_query)) $args['tax_query'] = $tax_query;
    if (!empty($meta_query)) $args['meta_query'] = $meta_query;

    // Executar query
    $query = new WP_Query($args);
    
    ob_start();
    if ($query->have_posts()) : 
        while ($query->have_posts()) : $query->the_post();
            get_template_part('components/painel/investidor/card-produto', null, [
                'id' => get_the_ID(),
                'panel' => true
            ]);
        endwhile; 
    endif;
    
    wp_send_json_success([
        'html' => ob_get_clean(),
        'max_pages' => $query->max_num_pages
    ]);
}