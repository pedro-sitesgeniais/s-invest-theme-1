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

    // Query inicial com mais resultados para pós-filtro
    $args = [
        'post_type'      => 'investment',
        'posts_per_page' => 50,
        'paged'          => 1,
        'post_status'    => 'publish',
        'orderby'        => 'date',
        'order'          => ($_POST['ordem'] === 'ASC') ? 'ASC' : 'DESC'
    ];

    // Aplicar filtros básicos (exceto status)
    $filtros = [
        'tipo_produto' => sanitize_text_field($_POST['tipo_produto'] ?? ''),
        'imposto' => sanitize_text_field($_POST['imposto'] ?? ''),
        'modalidade' => sanitize_text_field($_POST['modalidade'] ?? ''),
        'prazo' => intval($_POST['prazo'] ?? 0),
        'valor' => floatval($_POST['valor'] ?? 0)
    ];

    aplicar_filtros_investimentos($args, $filtros);
    $query = new WP_Query($args);
    
    // NOVO: Pós-filtro por status
    $status_filtro = sanitize_text_field($_POST['status_produto'] ?? '');
    $investments_filtrados = [];
    
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $investment_id = get_the_ID();
            
            if (empty($status_filtro)) {
                $investments_filtrados[] = $investment_id;
            } else {
                $status = function_exists('s_invest_calcular_status_captacao') 
                    ? s_invest_calcular_status_captacao($investment_id) 
                    : 'ativo';
                
                $eh_ativo = ($status === 'ativo');
                
                if (($status_filtro === 'ativo' && $eh_ativo) || 
                    ($status_filtro === 'encerrado' && !$eh_ativo)) {
                    $investments_filtrados[] = $investment_id;
                }
            }
        }
    }
    wp_reset_postdata();
    
    // Paginação dos resultados filtrados
    $por_pagina = 6;
    $pagina = max(1, absint($_POST['paged'] ?? 1));
    $offset = ($pagina - 1) * $por_pagina;
    $investments_pag = array_slice($investments_filtrados, $offset, $por_pagina);
    
    ob_start();
    if (!empty($investments_pag)) : ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 xl:gap-8">
            <?php foreach ($investments_pag as $id) : ?>
                <?php get_template_part('components/card-produto', null, ['id' => $id, 'context' => 'panel']); ?>
            <?php endforeach; ?>
        </div>
    <?php else : ?>
        <div class="text-center py-16 bg-white rounded-xl">
            <i class="fas fa-search text-4xl text-gray-400 mb-4"></i>
            <p class="text-lg font-medium text-gray-900">Nenhum produto encontrado</p>
            <p class="text-sm text-gray-500">Ajuste os filtros para ver mais resultados.</p>
        </div>
    <?php endif;
    
    wp_send_json_success([
        'html' => ob_get_clean(),
        'max_pages' => ceil(count($investments_filtrados) / $por_pagina),
        'found_posts' => count($investments_filtrados),
        'paged' => $pagina
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
    $user_id = get_current_user_id();
    
    // Para filtrar por ativo/vendido, precisamos filtrar pelos aportes do usuário
    $aportes_args = [
        'post_type' => 'aporte',
        'posts_per_page' => -1,
        'post_status' => 'publish',
        'meta_query' => [
            ['key' => 'investidor_id', 'value' => $user_id],
        ],
        'fields' => 'ids'
    ];
    
    // Adicionar filtro por status de venda
    if ($status === 'vendido') {
        $aportes_args['meta_query'][] = [
            'key' => 'venda_status',
            'value' => '1',
            'compare' => '='
        ];
    } elseif ($status === 'ativo') {
        $aportes_args['meta_query'][] = [
            'relation' => 'OR',
            [
                'key' => 'venda_status',
                'value' => '0',
                'compare' => '='
            ],
            [
                'key' => 'venda_status',
                'compare' => 'NOT EXISTS'
            ]
        ];
    }
    
    // Buscar aportes com o status desejado
    $aportes_filtrados = get_posts($aportes_args);
    
    // Extrair IDs dos investimentos desses aportes
    $investment_ids_filtrados = [];
    foreach ($aportes_filtrados as $aporte_id) {
        $investment_id = get_field('investment_id', $aporte_id);
        if ($investment_id && !in_array($investment_id, $investment_ids_filtrados)) {
            $investment_ids_filtrados[] = $investment_id;
        }
    }
    
    // Se não há aportes com esse status, não mostrar nada
    if (empty($investment_ids_filtrados)) {
        $investment_ids_filtrados = [0]; // Forçar resultado vazio
    }
    
    // Filtrar apenas os investimentos que têm aportes com esse status
    $investment_ids = array_intersect($investment_ids, $investment_ids_filtrados);
    
    // Atualizar args da query principal
    $args['post__in'] = $investment_ids;
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

    // Filtro por taxonomia imposto
    if (!empty($dados['imposto'])) {
        $tax_query[] = [
            'taxonomy' => 'imposto',
            'field'    => 'slug',
            'terms'    => sanitize_text_field($dados['imposto'])
        ];
    }

    // Filtro por taxonomia modalidade
    if (!empty($dados['modalidade'])) {
        $tax_query[] = [
            'taxonomy' => 'modalidade',
            'field'    => 'slug',
            'terms'    => sanitize_text_field($dados['modalidade'])
        ];
    }

    // ✅ NOVO: FILTRO POR STATUS DO PRODUTO (ATIVO/ENCERRADO)
    if (!empty($dados['status_produto'])) {
        $status_produto = sanitize_text_field($dados['status_produto']);
        
        // Usar um meta_query customizado para filtrar por status calculado
        // Como o status é calculado dinamicamente, precisamos de um approach diferente
        
        // Adicionar um callback personalizado para filtrar após a query
        add_filter('posts_where', function($where) use ($status_produto) {
            global $wpdb;
            
            if ($status_produto === 'encerrado') {
                // Para produtos encerrados: valor_total <= total_captado OU data fim passou
                $where .= " AND (
                    (
                        meta_valor_total.meta_value IS NOT NULL 
                        AND meta_total_captado.meta_value IS NOT NULL
                        AND CAST(meta_total_captado.meta_value AS DECIMAL(10,2)) >= CAST(meta_valor_total.meta_value AS DECIMAL(10,2))
                    )
                    OR
                    (
                        meta_fim_captacao.meta_value IS NOT NULL 
                        AND STR_TO_DATE(meta_fim_captacao.meta_value, '%Y-%m-%d') < CURDATE()
                    )
                )";
            } elseif ($status_produto === 'ativo') {
                // Para produtos ativos: valor_total > total_captado E (sem data fim OU data fim futura)
                $where .= " AND (
                    (
                        meta_valor_total.meta_value IS NOT NULL 
                        AND meta_total_captado.meta_value IS NOT NULL
                        AND CAST(meta_total_captado.meta_value AS DECIMAL(10,2)) < CAST(meta_valor_total.meta_value AS DECIMAL(10,2))
                    )
                    AND
                    (
                        meta_fim_captacao.meta_value IS NULL
                        OR STR_TO_DATE(meta_fim_captacao.meta_value, '%Y-%m-%d') >= CURDATE()
                    )
                )";
            }
            
            return $where;
        }, 10);
        
        // Adicionar JOINs necessários para os meta fields
        add_filter('posts_join', function($join) {
            global $wpdb;
            
            $join .= " LEFT JOIN {$wpdb->postmeta} AS meta_valor_total ON ({$wpdb->posts}.ID = meta_valor_total.post_id AND meta_valor_total.meta_key = 'valor_total')";
            $join .= " LEFT JOIN {$wpdb->postmeta} AS meta_total_captado ON ({$wpdb->posts}.ID = meta_total_captado.post_id AND meta_total_captado.meta_key = 'total_captado')";
            $join .= " LEFT JOIN {$wpdb->postmeta} AS meta_fim_captacao ON ({$wpdb->posts}.ID = meta_fim_captacao.post_id AND meta_fim_captacao.meta_key = 'fim_captacao')";
            
            return $join;
        }, 10);
        
        // Remover os filtros após usar para evitar conflitos
        add_action('wp', function() {
            remove_filter('posts_where', 'aplicar_filtro_status_produto');
            remove_filter('posts_join', 'aplicar_joins_status_produto');
        });
    }

    // Filtro por prazo
    if (!empty($dados['prazo'])) {
        $prazo_filtro = intval($dados['prazo']);
        $meta_query[] = [
            'key'     => 'prazo_do_investimento',
            'value'   => $prazo_filtro,
            'compare' => '>=',
            'type'    => 'NUMERIC'
        ];
    }

    // Filtro por rentabilidade
    if (!empty($dados['valor'])) {
        $valor_filtro = floatval($dados['valor']);
        $meta_query[] = [
            'key'     => 'rentabilidade',
            'value'   => $valor_filtro,
            'compare' => '>=',
            'type'    => 'NUMERIC'
        ];
    }

    // Aplicar queries
    if (!empty($tax_query)) {
        $args['tax_query'] = $tax_query;
    }
    if (!empty($meta_query)) {
        $args['meta_query'] = $meta_query;
    }
}