<?php
/**
 * Card de Investimento UNIFICADO - VERSÃO CORRIGIDA COM VALORES CORRETOS
 * components/card-produto.php
 * 
 * Uso:
 * get_template_part('components/card-produto', null, [
 *     'id' => $investment_id,
 *     'context' => 'public|panel|my-investments'
 * ]);
 */
defined('ABSPATH') || exit;

$id      = isset($args['id']) ? absint($args['id']) : get_the_ID();
$context = sanitize_key($args['context'] ?? 'public');

$investment = get_post($id);
if (!$investment || $investment->post_type !== 'investment') {
    return;
}

setup_postdata($investment);

$cache_key = "investment_card_data_{$id}_{$context}";
$cached_data = wp_cache_get($cache_key, 'investment_cards');

if (false === $cached_data) {
    $prazo = get_field('prazo_do_investimento', $id) ?: [];
    $rentabilidade = floatval(get_field('rentabilidade', $id) ?: 0);
    $risco = esc_html(get_field('risco', $id)) ?: 'Não classificado';
    $valor_total = floatval(get_field('valor_total', $id) ?: 0);
    $total_captado = floatval(get_field('total_captado', $id) ?: 0);
    $fim_captacao = get_field('fim_captacao', $id);
    $aporte_minimo = floatval(get_field('aporte_minimo', $id) ?: 0);
    
    $terms = wp_get_post_terms($id, 'tipo_produto');
    $tipo_produto = !empty($terms) && !is_wp_error($terms) ? esc_html($terms[0]->name) : '';
    
    // 🎯 AQUI É ONDE VAI O CÓDIGO! 
    $porcentagem = ($valor_total > 0) ? min(100, ($total_captado / $valor_total) * 100) : 0;
    
    // NOVO SISTEMA: Usar status da captação automático
    $status_captacao_info = function_exists('s_invest_get_status_captacao_info') 
        ? s_invest_get_status_captacao_info($id) 
        : ['status' => 'ativo', 'label' => 'Em Captação'];
    
    $status_captacao = $status_captacao_info['status'];
    $encerrado = !in_array($status_captacao, ['ativo']);
    $disponivel_para_investir = $status_captacao === 'ativo';
    if ($fim_captacao) {
        $data_fim = DateTime::createFromFormat('Y-m-d', $fim_captacao) ?: DateTime::createFromFormat('d/m/Y', $fim_captacao);
        if ($data_fim && $data_fim < new DateTime()) {
            $status_captacao = 'encerrado';
            $encerrado = true;
        }
    }
    
    $dados_pessoais = null;
    if ($context === 'my-investments') {
        $user_id = get_current_user_id();
        
        $aportes_usuario = get_posts([
            'post_type' => 'aporte',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'meta_query' => [
                ['key' => 'investment_id', 'value' => $id],
                ['key' => 'investidor_id', 'value' => $user_id]
            ]
        ]);
        
        if (!empty($aportes_usuario)) {
    // ✅ CORRIGIDO: Somar TODOS os aportes do usuário neste investimento
    $valor_investido_total = 0;
    $valor_atual_total = 0;
    $venda_status = false;
    $aporte_representativo = $aportes_usuario[0]; // Para pegar outros dados não-financeiros
    
    // Processar cada aporte individualmente
    foreach ($aportes_usuario as $aporte_item) {
        $aporte_id = $aporte_item->ID;
        $venda_status_item = get_field('venda_status', $aporte_id);
        
        // Se qualquer aporte foi vendido, considerar como vendido
        if ($venda_status_item) {
            $venda_status = true;
        }
        
        // Somar histórico de aportes
        $historico_aportes = get_field('historico_aportes', $aporte_id) ?: [];
        foreach ($historico_aportes as $item) {
            $valor_investido_total += (float) ($item['valor_aporte'] ?? 0);
        }
        
        // Somar valor atual
        $valor_atual_item = (float) get_field('valor_atual', $aporte_id);
        $valor_atual_total += $valor_atual_item;
    }
    
    if ($venda_status) {
        // ===== VENDIDO: Somar valores de todos os aportes vendidos =====
        $valor_recebido_total = 0;
        $rentabilidade_reais_total = 0;
        $rentabilidade_pct_total = 0;
        $data_venda = '';
        
        foreach ($aportes_usuario as $aporte_item) {
            $aporte_id = $aporte_item->ID;
            if (get_field('venda_status', $aporte_id)) {
                $valor_recebido_total += (float) get_field('venda_valor', $aporte_id);
                $rentabilidade_reais_total += (float) get_field('venda_rentabilidade_reais', $aporte_id);
                
                if (!$data_venda) {
                    $data_venda = get_field('venda_data', $aporte_id);
                }
            }
        }
        
        // Calcular rentabilidade percentual total
        if ($valor_investido_total > 0 && $valor_recebido_total > 0) {
            $rentabilidade_pct_total = ($valor_recebido_total / $valor_investido_total) * 100;
        }
        
        $dados_pessoais = [
            'status' => 'vendido',
            'valor_investido' => $valor_investido_total,
            'valor_atual' => $valor_atual_total,
            'valor_recebido' => $valor_recebido_total,
            'rentabilidade_reais' => $rentabilidade_reais_total,
            'rentabilidade_pct' => $rentabilidade_pct_total,
            'data_venda' => $data_venda,
            'lucro_realizado' => true
        ];
    } else {
        // ===== ATIVO: Calcular rentabilidade projetada de todos os aportes =====
        $rentabilidade_projetada_total = 0;
        
        foreach ($aportes_usuario as $aporte_item) {
            $aporte_id = $aporte_item->ID;
            $rentabilidade_hist = get_field('rentabilidade_historico', $aporte_id);
            
            if (!empty($rentabilidade_hist) && is_array($rentabilidade_hist)) {
                $ultimo_valor = end($rentabilidade_hist);
                if (isset($ultimo_valor['valor'])) {
                    $rentabilidade_projetada_total += floatval($ultimo_valor['valor']);
                }
            } else {
                // Se não há histórico, usar diferença valor atual - valor investido
                $valor_atual_item = (float) get_field('valor_atual', $aporte_id);
                $historico_aportes_item = get_field('historico_aportes', $aporte_id) ?: [];
                $valor_investido_item = 0;
                
                foreach ($historico_aportes_item as $item) {
                    $valor_investido_item += (float) ($item['valor_aporte'] ?? 0);
                }
                
                $diferenca = $valor_atual_item - $valor_investido_item;
                if ($diferenca <= ($valor_investido_item * 10)) { // Filtro anti-erro
                    $rentabilidade_projetada_total += $diferenca;
                }
            }
        }
        
        // Se valor_atual_total não foi calculado corretamente, ajustar
        if ($valor_atual_total <= 0) {
            $valor_atual_total = $valor_investido_total + $rentabilidade_projetada_total;
        }
        
        $rentabilidade_pct = $valor_investido_total > 0 ? 
            ($rentabilidade_projetada_total / $valor_investido_total) * 100 : 0;
        
        $dados_pessoais = [
            'status' => 'ativo',
            'valor_investido' => $valor_investido_total,
            'valor_atual' => $valor_atual_total,
            'rentabilidade_reais' => $rentabilidade_projetada_total,
            'rentabilidade_pct' => $rentabilidade_pct,
            'lucro_realizado' => false
        ];
    }
}
    }
    
    $cached_data = compact(
        'prazo', 'rentabilidade', 'risco', 'valor_total', 'total_captado',
        'tipo_produto', 'porcentagem', 'encerrado', 'status_captacao', 'aporte_minimo',
        'dados_pessoais', 'status_captacao_info', 'disponivel_para_investir'
    );
    
    wp_cache_set($cache_key, $cached_data, 'investment_cards', 15 * MINUTE_IN_SECONDS);
}

extract($cached_data);

switch ($context) {
    case 'my-investments':
        $base_url = home_url('/painel/');
        $link = esc_url(add_query_arg([
            'secao' => 'detalhes-investimento',
            'id' => $id,
        ], $base_url));
        
        if ($dados_pessoais && $dados_pessoais['status'] === 'vendido') {
            $button_text = 'Ver Histórico';
            $button_icon = 'fa-history';
        } else {
            $button_text = 'Ver Detalhes';
            $button_icon = 'fa-chart-pie';
        }
        break;
        
    case 'panel':
        $link = get_permalink($id);
        $button_text = 'Saiba Mais';     // ✅ NOVO TEXTO
        $button_icon = 'fa-info-circle'; // ✅ NOVO ÍCONE (OPCIONAL)
        break;
        
    default:
        $link = get_permalink($id);
        $button_text = 'Ver Oportunidade';
        $button_icon = 'fa-eye';
        break;
}

$card_classes = 'investment-card relative w-full flex flex-col h-full bg-white/95 backdrop-blur-md rounded-2xl overflow-hidden shadow-md transform transition-all duration-300 hover:scale-[1.02] hover:shadow-xl border border-gray-100';

if ($encerrado && $context !== 'my-investments') {
    $card_classes .= ' opacity-75 grayscale';
} elseif ($dados_pessoais && $dados_pessoais['status'] === 'vendido') {
    $card_classes .= ' border-orange-200 bg-orange-50/50';
}

$risco_colors = [
    'baixo' => 'bg-green-100 text-green-800',
    'médio' => 'bg-yellow-100 text-yellow-800', 
    'medio' => 'bg-yellow-100 text-yellow-800',
    'alto' => 'bg-red-100 text-red-800'
];
$risco_class = $risco_colors[strtolower($risco)] ?? 'bg-gray-100 text-gray-800';
?>

<article
    id="investment-card-<?php echo esc_attr($id); ?>"
    class="<?php echo $card_classes; ?>"
    aria-labelledby="investment-title-<?php echo esc_attr($id); ?>"
    data-investment-id="<?php echo esc_attr($id); ?>"
    data-context="<?php echo esc_attr($context); ?>">
    <?php 
        $regiao = get_field('regiao_projeto', $investment_id);
        if ($regiao) : ?>
        <div class="investment-region">
            <i class="fas fa-map-marker-alt"></i>
            <span><?php echo esc_html($regiao); ?></span>
        </div>
    <?php endif; ?>
    <?php if ($dados_pessoais && $dados_pessoais['status'] === 'vendido') : ?>
        <div class="absolute top-3 right-3 bg-orange-600 text-white text-xs font-semibold px-3 py-1.5 rounded-full z-10 shadow-lg">
            <i class="fas fa-hand-holding-usd mr-1"></i>
            Vendido
            <?php if (!empty($dados_pessoais['data_venda'])) : ?>
                <div class="text-xs opacity-90 mt-1"><?php echo esc_html($dados_pessoais['data_venda']); ?></div>
            <?php endif; ?>
        </div>
    <?php elseif ($encerrado && $context !== 'my-investments') : ?>
        <div class="absolute top-3 right-3 bg-red-600 text-white text-xs font-semibold px-3 py-1.5 rounded-full z-10 shadow-lg">
            <i class="fas fa-times-circle mr-1"></i>
            Encerrado
        </div>
    <?php elseif ($porcentagem > 95 && $context !== 'my-investments') : ?>
        <div class="absolute top-3 right-3 bg-red-600 text-white text-xs font-semibold px-3 py-1.5 rounded-full z-10 shadow-lg animate-pulse">
            <i class="fas fa-fire mr-1"></i>
            Quase Esgotado
        </div>
    <?php elseif ($porcentagem > 80 && $context !== 'my-investments') : ?>
        <div class="absolute top-3 right-3 bg-orange-600 text-white text-xs font-semibold px-3 py-1.5 rounded-full z-10 shadow-lg">
            <i class="fas fa-trending-up mr-1"></i>
            Alta Procura
        </div>
    <?php elseif ($porcentagem > 60 && $context !== 'my-investments') : ?>
        <div class="absolute top-3 right-3 bg-blue-600 text-white text-xs font-semibold px-3 py-1.5 rounded-full z-10 shadow-lg">
            <i class="fas fa-chart-line mr-1"></i>
            Em Captação
    </div>
    <?php elseif ($context === 'my-investments' && (!$dados_pessoais || $dados_pessoais['status'] === 'ativo')) : ?>
        <div class="absolute top-3 right-3 bg-blue-600 text-white text-xs font-semibold px-3 py-1.5 rounded-full z-10 shadow-lg">
            <i class="fas fa-user-check mr-1"></i>
            Meu Investimento
        </div>
    <?php endif; ?>

    <?php if (has_post_thumbnail($id)) : ?>
        <div class="w-full h-48 overflow-hidden relative">
            <?php echo get_the_post_thumbnail($id, 'large', [
                'class' => 'w-full h-full object-cover transition-transform duration-500 hover:scale-110',
                'alt' => esc_attr(get_the_title($id)) . ' - Imagem ilustrativa',
                'loading' => 'lazy',
                'sizes' => '(max-width: 768px) 100vw, (max-width: 1024px) 50vw, 33vw'
            ]); ?>
            
            <div class="absolute inset-0 bg-gradient-to-t from-black/20 to-transparent"></div>
            
            <?php if ($tipo_produto) : ?>
                <div class="absolute bottom-3 left-3 bg-black/70 text-white text-xs px-2 py-1 rounded">
                    <?php echo $tipo_produto; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php else : ?>
        <div class="w-full h-48 bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center">
            <div class="text-center text-gray-400">
                <i class="fas fa-building text-3xl mb-2"></i>
                <?php if ($tipo_produto) : ?>
                    <p class="text-sm font-medium"><?php echo $tipo_produto; ?></p>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="p-5 lg:p-6 flex flex-col gap-4 flex-grow">
        
        <div class="space-y-3">
            <h2 id="investment-title-<?php echo esc_attr($id); ?>" 
                class="text-xl font-bold text-gray-900 line-clamp-2 leading-tight">
                <?php echo esc_html(get_the_title($id)); ?>
            </h2>
            
            <div class="flex items-center flex-wrap gap-2">
                <span class="<?php echo $risco_class; ?> text-xs font-medium px-2 py-1 rounded-full">
                    Risco <?php echo ucfirst($risco); ?>
                </span>
                
                <?php if ($aporte_minimo > 0 && $context !== 'my-investments') : ?>
                    <span class="bg-blue-50 text-blue-700 text-xs px-2 py-1 rounded-full">
                        Min. R$ <?php echo number_format($aporte_minimo, 0, ',', '.'); ?>
                    </span>
                <?php endif; ?>
                
                <?php 
                $impostos = wp_get_post_terms($id, 'imposto');
                if (!empty($impostos) && !is_wp_error($impostos)) :
                    foreach ($impostos as $imposto) : ?>
                        <span class="bg-yellow-50 text-yellow-700 text-xs font-medium px-2 py-1 rounded-full">
                            <?php echo esc_html($imposto->name); ?>
                        </span>
                    <?php endforeach;
                endif; ?>
                
                <?php 
                $modalidades = wp_get_post_terms($id, 'modalidade');
                if (!empty($modalidades) && !is_wp_error($modalidades)) :
                    foreach ($modalidades as $modalidade) : ?>
                        <span class="bg-accent/20 text-primary text-xs font-medium px-2 py-1 rounded-full">
                            <?php echo esc_html($modalidade->name); ?>
                        </span>
                    <?php endforeach;
                endif; ?>
            </div>
        </div>

        <?php if ($context === 'my-investments' && $dados_pessoais) : ?>
            <div class="space-y-3">
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="text-gray-600 block">Valor Investido</span>
                        <span class="font-bold text-lg text-blue-600">
                            R$ <?php echo number_format($dados_pessoais['valor_investido'], 0, ',', '.'); ?>
                        </span>
                    </div>
                    <div>
                        <span class="text-gray-600 block">
                            <!-- CORREÇÃO: Sempre "Valor na Venda" para vendidos -->
                            <?php echo $dados_pessoais['status'] === 'vendido' ? 'Valor na Venda' : 'Valor Atual'; ?>
                        </span>
                        <span class="font-bold text-lg text-primary">
                            R$ <?php 
                                // CORREÇÃO: Para vendidos, mostrar valor_atual. Para ativos, mostrar valor_atual
                                echo number_format($dados_pessoais['valor_atual'], 0, ',', '.');
                            ?>
                        </span>
                    </div>
                </div>
                
                <div class="bg-gray-50 p-3 rounded-lg">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600 text-sm">
                            <!-- CORREÇÃO: Sempre "Rentabilidade Consolidada" para vendidos -->
                            <?php echo $dados_pessoais['status'] === 'vendido' ? 'Rentabilidade Consolidada' : 'Rentabilidade Projetada'; ?>
                        </span>
                        <div class="text-right">
                            <span class="font-bold text-lg text-green-600">
                                <?php 
                                if ($dados_pessoais['status'] === 'vendido') {
                                    // CORREÇÃO: Para vendidos, mostrar valor_recebido (total recebido R$ 141.446)
                                    echo 'R$ ' . number_format($dados_pessoais['valor_recebido'], 0, ',', '.');
                                } else {
                                    // Para ativos, mostrar rentabilidade_reais com sinal
                                    echo ($dados_pessoais['rentabilidade_reais'] >= 0 ? '+' : '') . 'R$ ' . number_format(abs($dados_pessoais['rentabilidade_reais']), 0, ',', '.');
                                }
                                ?>
                            </span>
                            <div class="text-xs <?php echo $dados_pessoais['rentabilidade_pct'] >= 0 ? 'text-green-500' : 'text-red-500'; ?>">
                                (<?php echo ($dados_pessoais['rentabilidade_pct'] >= 0 ? '+' : ''); ?><?php echo number_format($dados_pessoais['rentabilidade_pct'], 1, ',', '.'); ?>%)
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php else : ?>
            <div class="space-y-3">
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="text-gray-600 block">Rentabilidade</span>
                        <span class="font-bold text-lg text-green-600">
                            <?php echo number_format($rentabilidade, 1, ',', '.'); ?>% a.a
                        </span>
                    </div>
                    <div>
                        <span class="text-gray-600 block">Prazo</span>
                        <span class="font-semibold">
                            <?php
                            $prazo_min = $prazo['prazo_min'] ?? '';
                            $prazo_max = $prazo['prazo_max'] ?? '';
                            if ($prazo_min && $prazo_max && $prazo_min != $prazo_max) {
                                echo esc_html($prazo_min . '-' . $prazo_max . ' meses');
                            } elseif ($prazo_min) {
                                echo esc_html($prazo_min . ' meses');
                            } else {
                                echo '—';
                            }
                            ?>
                        </span>
                    </div>
                </div>
            </div>

            <div class="space-y-2">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Captação</span>
                    <span class="font-semibold"><?php echo number_format($porcentagem, 1, ',', '.'); ?>%</span>
                </div>
                <div class="w-full bg-gray-200 h-3 rounded-full overflow-hidden" 
                     role="progressbar" 
                     aria-valuenow="<?php echo esc_attr($porcentagem); ?>" 
                     aria-valuemin="0" 
                     aria-valuemax="100">
                    <div class="h-full bg-gradient-to-r from-green-500 to-green-600 transition-all duration-700 ease-out" 
                         style="width: <?php echo esc_attr($porcentagem); ?>%"></div>
                </div>
                <div class="flex justify-between text-xs text-gray-500">
                    <span>R$ <?php echo number_format($total_captado, 0, ',', '.'); ?></span>
                    <span>R$ <?php echo number_format($valor_total, 0, ',', '.'); ?></span>
                </div>
            </div>
        <?php endif; ?>

        <div class="mt-auto">
            <?php if ($encerrado && $context !== 'my-investments') : ?>
                <button disabled 
                        class="w-full py-3 px-4 bg-gray-300 text-gray-600 rounded-lg cursor-not-allowed text-sm font-medium">
                    <i class="fas fa-lock mr-2"></i>
                    Investimento Encerrado
                </button>
            <?php else : ?>
                <a href="<?php echo $link; ?>" 
                   class="w-full block text-center py-3 px-4 <?php echo ($dados_pessoais && $dados_pessoais['status'] === 'vendido') ? 'bg-red-600 hover:bg-orange-600' : 'bg-gradient-to-r from-accent to-secondary hover:from-blue-700 hover:to-blue-800'; ?> text-white rounded-lg transition-all duration-300 transform hover:scale-[1.02] focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 text-sm font-medium shadow-lg hover:shadow-xl"
                   <?php if ($context === 'panel') echo 'target="_self" rel="noopener"'; ?>>
                    <i class="fas <?php echo $button_icon; ?> mr-2"></i>
                    <?php echo $button_text; ?>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "FinancialProduct",
        "name": "<?php echo esc_js(get_the_title($id)); ?>",
        "description": "<?php echo esc_js(wp_trim_words(get_the_excerpt($id), 20)); ?>",
        "provider": {
            "@type": "Organization",
            "name": "<?php echo esc_js(get_bloginfo('name')); ?>"
        },
        "url": "<?php echo esc_js(get_permalink($id)); ?>",
        "interestRate": "<?php echo esc_js($rentabilidade); ?>%"
    }
    </script>
</article>

<?php wp_reset_postdata(); ?>