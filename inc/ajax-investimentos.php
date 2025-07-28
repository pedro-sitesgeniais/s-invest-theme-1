<?php
/**
 * AJAX Handlers UNIFICADOS para Investimentos
 * inc/ajax-investimentos.php
 */

// Handler principal para página pública (infinite scroll)
add_action('wp_ajax_filtrar_investimentos', 's_invest_ajax_filtrar_investimentos');
add_action('wp_ajax_nopriv_filtrar_investimentos', 's_invest_ajax_filtrar_investimentos');

function s_invest_ajax_filtrar_investimentos() {
    // Verificação de nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'filtrar_investimentos_nonce')) {
        wp_send_json_error('Nonce inválido', 403);
    }

    // Dados de entrada com fallbacks seguros
    $dados_json = $_POST['filtros'] ?? '{}';
    $dados = json_decode(stripslashes($dados_json), true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        $dados = [];
    }
    
    $pagina = max(1, intval($_POST['pagina'] ?? 1));
    $por_pagina = 8; // Mais cards para infinite scroll

    $args = [
        'post_type'      => 'investment',
        'post_status'    => 'publish',
        'paged'          => $pagina,
        'posts_per_page' => $por_pagina,
        'orderby'        => 'date',
        'order'          => sanitize_text_field($dados['ordem'] ?? 'DESC')
    ];

    // Aplicar filtros
    aplicar_filtros_investimentos($args, $dados);

    $query = new WP_Query($args);
    
    ob_start();
    
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            
            // Usa o card unificado com contexto público
            get_template_part('components/card-produto', null, [
                'id' => get_the_ID(),
                'context' => 'public'
            ]);
        }

        // Marca fim dos resultados se necessário
        if ($query->found_posts <= ($query->query_vars['paged'] * $query->query_vars['posts_per_page'])) {
            echo '<div data-fim="true" style="display: none;"></div>';
        }
    } else {
        echo '
        <div class="col-span-full text-center py-16 text-gray-600 flex flex-col items-center space-y-4">
            <i class="fas fa-search text-4xl text-gray-400"></i>
            <p class="text-lg font-medium">Nenhum investimento encontrado</p>
            <p class="text-sm text-gray-500 max-w-md">Tente ajustar os filtros ou voltar para uma busca mais ampla.</p>
        </div>';
    }

    $html = ob_get_clean();
    wp_reset_postdata();
    
    // Para infinite scroll (página de arquivo)
    echo $html;
    wp_die();
}

// Handler para painel - produtos gerais (paginação numérica)
add_action('wp_ajax_filtrar_investimentos_painel', 's_invest_ajax_filtrar_investimentos_painel');

function s_invest_ajax_filtrar_investimentos_painel() {
    // Verificar nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'filtrar_investimentos_nonce')) {
        wp_send_json_error('Nonce inválido', 403);
    }

    $args = [
        'post_type'      => 'investment',
        'posts_per_page' => 6,
        'paged'          => max(1, absint($_POST['paged'] ?? 1)),
        'post_status'    => 'publish',
        'orderby'        => 'date',
        'order'          => ($_POST['ordem'] === 'ASC') ? 'ASC' : 'DESC'
    ];

    // Aplicar filtros básicos
    $filtros = [
        'tipo_produto' => sanitize_text_field($_POST['tipo_produto'] ?? ''),
        'prazo' => intval($_POST['prazo'] ?? 0),
        'valor' => floatval($_POST['valor'] ?? 0)
    ];

    aplicar_filtros_investimentos($args, $filtros);

    $query = new WP_Query($args);
    
    ob_start();
    
    if ($query->have_posts()) : ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 xl:gap-8">
            <?php while ($query->have_posts()) : $query->the_post(); ?>
                <?php get_template_part('components/card-produto', null, [
                    'id' => get_the_ID(),
                    'context' => 'panel'
                ]); ?>
            <?php endwhile; ?>
        </div>
    <?php else : ?>
        <div class="text-center py-16 bg-white rounded-xl">
            <div class="text-gray-600 mb-4">
                <i class="fas fa-search text-4xl text-gray-400 mb-4"></i>
                <p class="text-lg font-medium">Nenhum produto encontrado</p>
                <p class="text-sm text-gray-500 max-w-md mx-auto">Tente ajustar os filtros ou voltar para uma busca mais ampla.</p>
            </div>
        </div>
    <?php endif;

    $html = ob_get_clean();
    wp_reset_postdata();
    
    wp_send_json_success([
        'html' => $html,
        'max_pages' => $query->max_num_pages,
        'found_posts' => $query->found_posts,
        'paged' => $args['paged']
    ]);
}

// Handler NOVO para meus investimentos (paginação numérica)
add_action('wp_ajax_filtrar_meus_investimentos', 's_invest_ajax_filtrar_meus_investimentos');

function s_invest_ajax_filtrar_meus_investimentos() {
    // Verificar nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'filtrar_investimentos_nonce')) {
        wp_send_json_error('Nonce inválido', 403);
    }

    // Obter IDs dos investimentos do usuário
    $investment_ids_json = $_POST['investment_ids'] ?? '[]';
    $investment_ids = json_decode($investment_ids_json, true);
    
    if (empty($investment_ids) || !is_array($investment_ids)) {
        wp_send_json_success([
            'html' => '<div class="text-center py-16 bg-white rounded-xl">
                <p class="text-gray-600">Você ainda não possui investimentos.</p>
            </div>',
            'max_pages' => 1,
            'found_posts' => 0,
            'paged' => 1
        ]);
        return;
    }

    $args = [
        'post_type'      => 'investment',
        'posts_per_page' => 6,
        'paged'          => max(1, absint($_POST['paged'] ?? 1)),
        'post_status'    => 'publish',
        'post__in'       => array_map('absint', $investment_ids),
        'orderby'        => 'date',
        'order'          => ($_POST['ordem'] === 'ASC') ? 'ASC' : 'DESC'
    ];

    // Filtros específicos para meus investimentos
    $meta_query = [];
    $tax_query = [];

    // Filtro por tipo de produto
    if (!empty($_POST['tipo_produto'])) {
        $tax_query[] = [
            'taxonomy' => 'tipo_produto',
            'field'    => 'slug',
            'terms'    => sanitize_text_field($_POST['tipo_produto'])
        ];
    }

    // Filtro por status
    if (!empty($_POST['status'])) {
        $status = sanitize_text_field($_POST['status']);
        if ($status === 'encerrado') {
            $meta_query[] = [
                'key'     => 'encerrado',
                'value'   => '1',
                'compare' => '='
            ];
        } elseif ($status === 'ativo') {
            $meta_query[] = [
                'relation' => 'OR',
                [
                    'key'     => 'encerrado',
                    'value'   => '0',
                    'compare' => '='
                ],
                [
                    'key'     => 'encerrado',
                    'compare' => 'NOT EXISTS'
                ]
            ];
        }
    }

    // Aplicar queries
    if (!empty($tax_query)) {
        $args['tax_query'] = $tax_query;
    }
    if (!empty($meta_query)) {
        $args['meta_query'] = $meta_query;
    }

    $query = new WP_Query($args);
    
    ob_start();
    
    if ($query->have_posts()) : ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 xl:gap-8">
            <?php while ($query->have_posts()) : $query->the_post(); ?>
                <?php get_template_part('components/card-produto', null, [
                    'id' => get_the_ID(),
                    'context' => 'my-investments' // 🎯 CONTEXTO ESPECÍFICO
                ]); ?>
            <?php endwhile; ?>
        </div>
    <?php else : ?>
        <div class="text-center py-16 bg-white rounded-xl">
            <div class="text-gray-600 mb-4">
                <i class="fas fa-filter text-4xl text-gray-400 mb-4"></i>
                <p class="text-lg font-medium">Nenhum investimento encontrado</p>
                <p class="text-sm text-gray-500 max-w-md mx-auto">Tente ajustar os filtros para ver seus investimentos.</p>
            </div>
        </div>
    <?php endif;

    $html = ob_get_clean();
    wp_reset_postdata();
    
    wp_send_json_success([
        'html' => $html,
        'max_pages' => $query->max_num_pages,
        'found_posts' => $query->found_posts,
        'paged' => $args['paged']
    ]);
}

/**
 * Função auxiliar para aplicar filtros comuns
 */
function aplicar_filtros_investimentos(&$args, $dados) {
    $meta_query = [];
    $tax_query = [];

    // Filtro por taxonomia tipo_produto
    if (!empty($dados['tipo_produto'])) {
        $tax_query[] = [
            'taxonomy' => 'tipo_produto',
            'field'    => 'slug',
            'terms'    => sanitize_text_field($dados['tipo_produto'])
        ];
    }

    // NOVO: Filtro por taxonomia imposto
    if (!empty($dados['imposto'])) {
        $tax_query[] = [
            'taxonomy' => 'imposto',
            'field'    => 'slug',
            'terms'    => sanitize_text_field($dados['imposto'])
        ];
    }

    // NOVO: Filtro por taxonomia modalidade
    if (!empty($dados['modalidade'])) {
        $tax_query[] = [
            'taxonomy' => 'modalidade',
            'field'    => 'slug',
            'terms'    => sanitize_text_field($dados['modalidade'])
        ];
    }

    // Filtro por prazo
    if (!empty($dados['prazo'])) {
        $prazo_filtro = intval($dados['prazo']);
        $meta_query[] = [
            'relation' => 'OR',
            [
                'key'     => 'prazo_do_investimento_prazo_min',
                'value'   => $prazo_filtro,
                'compare' => '>=',
                'type'    => 'NUMERIC'
            ],
            [
                'key'     => 'prazo_do_investimento_prazo_max',
                'value'   => $prazo_filtro,
                'compare' => '>=',
                'type'    => 'NUMERIC'
            ]
        ];
    }

    // Filtro por rentabilidade
    if (!empty($dados['valor'])) {
        $meta_query[] = [
            'key'     => 'rentabilidade',
            'value'   => floatval($dados['valor']),
            'compare' => '>=',
            'type'    => 'DECIMAL'
        ];
    }

    // Filtro por risco
    if (!empty($dados['risco'])) {
        $meta_query[] = [
            'key'     => 'risco',
            'value'   => sanitize_text_field($dados['risco']),
            'compare' => '='
        ];
    }

    // Filtro por moeda
    if (!empty($dados['moeda'])) {
        $meta_query[] = [
            'key'     => 'moeda_aceita',
            'value'   => sanitize_text_field($dados['moeda']),
            'compare' => 'LIKE'
        ];
    }

    // Filtro por status
    if (!empty($dados['status'])) {
        $meta_query[] = [
            'key'     => 'status_captacao',
            'value'   => sanitize_text_field($dados['status']),
            'compare' => '='
        ];
    }

    // Aplicar queries se existirem
    if (!empty($tax_query)) {
        // Se há múltiplas taxonomias, usar AND
        if (count($tax_query) > 1) {
            $tax_query['relation'] = 'AND';
        }
        $args['tax_query'] = $tax_query;
    }
    if (!empty($meta_query)) {
        $args['meta_query'] = $meta_query;
    }
}