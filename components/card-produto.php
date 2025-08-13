<?php
/**
 * Card de Investimento UNIFICADO - VERSÃO CORRIGIDA COM INFORMAÇÕES GERAIS
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
    $regiao_projeto = get_field('regiao_projeto', $id);
    
    $terms = wp_get_post_terms($id, 'tipo_produto');
    $tipo_produto = !empty($terms) && !is_wp_error($terms) ? esc_html($terms[0]->name) : '';
    
    $porcentagem = ($valor_total > 0) ? min(100, ($total_captado / $valor_total) * 100) : 0;
    
    // NOVA LÓGICA DE STATUS DA CAPTAÇÃO
    $inicio_raw = get_field('data_inicio', $id);
    $fim_raw = get_field('fim_captacao', $id);
    $hoje = new DateTime();
    
    $inicio_date = false;
    $fim_date = false;
    
    if ($inicio_raw) {
        $inicio_date = DateTime::createFromFormat('Y-m-d', $inicio_raw)
            ?: DateTime::createFromFormat('d/m/Y', $inicio_raw)
            ?: false;
    }
    
    if ($fim_raw) {
        $fim_date = DateTime::createFromFormat('Y-m-d', $fim_raw)
            ?: DateTime::createFromFormat('d/m/Y', $fim_raw)
            ?: false;
    }
    
    // Definir status da captação
    $status_captacao = 'ativa'; // Padrão
    $encerrado = false;
    $disponivel_para_investir = true;
    
    if ($inicio_date && $hoje < $inicio_date) {
        // Ainda não começou
        $status_captacao = 'em_breve';
        $disponivel_para_investir = false;
    } elseif ($fim_date && $hoje > $fim_date) {
        // Já encerrou por data
        $status_captacao = 'encerrada';
        $encerrado = true;
        $disponivel_para_investir = false;
    } elseif ($porcentagem >= 100) {
        // Encerrou por meta atingida
        $status_captacao = 'encerrada';
        $encerrado = true;
        $disponivel_para_investir = false;
    } else {
        // Ativa
        $status_captacao = 'ativa';
        $disponivel_para_investir = true;
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
            // Dados do primeiro aporte (não somar valor_compra e valor_atual)
            $aporte_principal = $aportes_usuario[0];
            $valor_compra = (float) get_field('valor_compra', $aporte_principal->ID);
            $valor_atual = (float) get_field('valor_atual', $aporte_principal->ID);
            
            // Somar TODOS os aportes do usuário neste investimento
            $valor_investido_total = 0;
            $venda_status = false;
            $aportes_ativos = 0;
            $aportes_vendidos = 0;
            $aportes_detalhes = [];
            
            // Processar cada aporte individualmente
            foreach ($aportes_usuario as $aporte_item) {
                $aporte_id = $aporte_item->ID;
                $venda_status_item = get_field('venda_status', $aporte_id);
                
                // Contar aportes por status
                if ($venda_status_item) {
                    $venda_status = true;
                    $aportes_vendidos++;
                } else {
                    $aportes_ativos++;
                }
                
                // Armazenar detalhes do aporte para modal
                $aportes_detalhes[] = [
                    'id' => $aporte_id,
                    'vendido' => $venda_status_item,
                    'valor_compra' => (float) get_field('valor_compra', $aporte_id),
                    'valor_atual' => (float) get_field('valor_atual', $aporte_id),
                    'venda_valor' => $venda_status_item ? (float) get_field('venda_valor', $aporte_id) : 0,
                    'venda_data' => $venda_status_item ? get_field('venda_data', $aporte_id) : ''
                ];
                
                // Somar histórico de aportes (para valor investido)
                $historico_aportes = get_field('historico_aportes', $aporte_id) ?: [];
                foreach ($historico_aportes as $item) {
                    $valor_investido_total += (float) ($item['valor_aporte'] ?? 0);
                }
            }
            
            // Determinar status: todos vendidos ou ativo (remover misto)
            $status_investimento = 'ativo';
            if ($aportes_vendidos > 0 && $aportes_ativos === 0) {
                $status_investimento = 'vendido';
            }
            
            if ($aportes_vendidos > 0) {
                // ===== TEM APORTES VENDIDOS =====
                $valor_recebido_total = 0;
                $rentabilidade_reais_total = 0;
                $data_venda = '';
                $valor_na_venda_total = 0; // ✅ Valor na época da venda
                $valor_atual_ativos_total = 0; // ✅ Valor atual dos ativos
                
                foreach ($aportes_usuario as $aporte_item) {
                    $aporte_id = $aporte_item->ID;
                    if (get_field('venda_status', $aporte_id)) {
                        // Aporte vendido
                        $valor_recebido_total += (float) get_field('venda_valor', $aporte_id);
                        $valor_na_venda_total += (float) get_field('valor_atual', $aporte_id); // ✅ Valor na época da venda
                        $rentabilidade_reais_total += (float) get_field('venda_rentabilidade_reais', $aporte_id);
                        
                        if (!$data_venda) {
                            $data_venda = get_field('venda_data', $aporte_id);
                        }
                    } else {
                        // Aporte ativo
                        $valor_atual_ativos_total += (float) get_field('valor_atual', $aporte_id); // ✅ Valor atual
                    }
                }
                
                // Valor total: vendidos + ativos
                $valor_total_exibir = $valor_na_venda_total + $valor_atual_ativos_total;
                
                // Calcular rentabilidade percentual total
                if ($valor_investido_total > 0 && $valor_recebido_total > 0) {
                    $rentabilidade_pct_total = ($valor_recebido_total / $valor_investido_total) * 100;
                }
                
                $dados_pessoais = [
                    'status' => $status_investimento,
                    'valor_investido' => $valor_investido_total,
                    'valor_atual' => $valor_total_exibir, // ✅ Valor correto (vendidos + ativos)
                    'valor_recebido' => $valor_recebido_total,
                    'rentabilidade_reais' => $rentabilidade_reais_total,
                    'rentabilidade_pct' => $rentabilidade_pct_total,
                    'data_venda' => $data_venda,
                    'lucro_realizado' => $status_investimento === 'vendido',
                    'aportes_ativos' => $aportes_ativos,
                    'aportes_vendidos' => $aportes_vendidos,
                    'total_aportes' => count($aportes_usuario),
                    'aportes_detalhes' => $aportes_detalhes
                ];
            } else {
        // ===== ATIVO: Calcular rentabilidade projetada =====
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
                $diferenca = $valor_atual - $valor_investido_total;
                if ($diferenca <= ($valor_investido_total * 10)) {
                    $rentabilidade_projetada_total += $diferenca;
                }
            }
        }
        
        $rentabilidade_pct = $valor_investido_total > 0 ? 
            ($rentabilidade_projetada_total / $valor_investido_total) * 100 : 0;
        $dados_pessoais = [
            'status' => 'ativo',
            'valor_investido' => $valor_investido_total,
            'valor_atual' => $valor_atual, // ✅ Usar do primeiro aporte
            'rentabilidade_reais' => $rentabilidade_projetada_total,
            'rentabilidade_pct' => $rentabilidade_pct,
            'lucro_realizado' => false,
            'aportes_ativos' => $aportes_ativos,
            'aportes_vendidos' => $aportes_vendidos,
            'total_aportes' => count($aportes_usuario),
            'aportes_detalhes' => $aportes_detalhes
                ];
            }
        }
    }
    
    $cached_data = compact(
        'prazo', 'rentabilidade', 'risco', 'valor_total', 'total_captado',
        'tipo_produto', 'porcentagem', 'encerrado', 'status_captacao', 'aporte_minimo',
        'dados_pessoais', 'disponivel_para_investir', 'regiao_projeto', 'inicio_date', 'fim_date'
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
        $button_text = 'Saiba Mais';
        $button_icon = 'fa-info-circle';
        break;
        
    default:
        $link = get_permalink($id);
        $button_text = 'Saiba Mais';
        $button_icon = 'fa-info-circle';
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
    // LÓGICA DE BADGES ATUALIZADA - REMOVIDO STATUS MISTO
    if ($dados_pessoais && $dados_pessoais['status'] === 'vendido') : ?>
        <div class="absolute top-3 right-3 z-10">
            <div class="bg-orange-600 text-white text-xs font-semibold px-3 py-1.5 rounded-full shadow-lg">
                <i class="fas fa-hand-holding-usd mr-1"></i>
                Vendido
                <?php if (!empty($dados_pessoais['data_venda'])) : ?>
                    <div class="text-xs opacity-90 mt-1"><?php echo esc_html($dados_pessoais['data_venda']); ?></div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Badge contador de aportes (canto superior esquerdo) -->
        <?php if ($dados_pessoais['total_aportes'] > 1) : ?>
            <div class="absolute top-3 left-3 bg-blue-600 text-white text-xs font-semibold px-2 py-1 rounded-full z-10 shadow-lg cursor-pointer" 
                 onclick="toggleAportesModal(<?php echo esc_attr($id); ?>)">
                <i class="fas fa-layer-group mr-1"></i>
                <?php echo $dados_pessoais['total_aportes']; ?> aporte<?php echo $dados_pessoais['total_aportes'] > 1 ? 's' : ''; ?>
            </div>
        <?php endif; ?>
    <?php elseif ($context !== 'my-investments') : ?>
        <?php
        // Determinar badge baseado no status
        $badge_color = 'bg-green-600';
        $badge_text = 'Aberto';
        $badge_icon = 'fa-check-circle';
        $animate_class = '';
        
        switch ($status_captacao) {
            case 'em_breve':
                $badge_color = 'bg-blue-600';
                $badge_text = 'Em Breve';
                $badge_icon = 'fa-clock';
                $animate_class = 'animate-pulse';
                break;
                
            case 'encerrada':
                if ($porcentagem >= 100) {
                    $badge_color = 'bg-purple-600';
                    $badge_text = 'Esgotado';
                    $badge_icon = 'fa-fire';
                } else {
                    $badge_color = 'bg-red-600';
                    $badge_text = 'Encerrado';
                    $badge_icon = 'fa-times-circle';
                }
                break;
                
            case 'ativa':
            default:
                if ($porcentagem > 90) {
                    $badge_color = 'bg-orange-600';
                    $badge_text = 'Últimas Vagas';
                    $badge_icon = 'fa-fire';
                    $animate_class = 'animate-pulse';
                } else {
                    $badge_color = 'bg-green-600';
                    $badge_text = 'Aberto';
                    $badge_icon = 'fa-check-circle';
                }
                break;
        }
        ?>
        
        <div class="absolute top-3 right-3 <?php echo $badge_color; ?> text-white text-xs font-semibold px-3 py-1.5 rounded-full z-10 shadow-lg <?php echo $animate_class; ?>">
            <i class="fas <?php echo $badge_icon; ?> mr-1"></i>
            <?php echo $badge_text; ?>
        </div>
        
    <?php elseif ($context === 'my-investments' && (!$dados_pessoais || $dados_pessoais['status'] === 'ativo')) : ?>
        <div class="absolute top-3 right-3 bg-blue-600 text-white text-xs font-semibold px-3 py-1.5 rounded-full z-10 shadow-lg">
            <i class="fas fa-user-check mr-1"></i>
            Meu Investimento
        </div>
        
        <!-- Badge contador de aportes para investimentos ativos (canto superior esquerdo) -->
        <?php if ($dados_pessoais && $dados_pessoais['total_aportes'] > 1) : ?>
            <div class="absolute top-3 left-3 bg-blue-600 text-white text-xs font-semibold px-2 py-1 rounded-full z-10 shadow-lg cursor-pointer" 
                 onclick="toggleAportesModal(<?php echo esc_attr($id); ?>)">
                <i class="fas fa-layer-group mr-1"></i>
                <?php echo $dados_pessoais['total_aportes']; ?> aporte<?php echo $dados_pessoais['total_aportes'] > 1 ? 's' : ''; ?>
            </div>
        <?php endif; ?>
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
                
                <?php if ($regiao_projeto) : ?>
                    <span class="bg-purple-50 text-purple-700 text-xs px-2 py-1 rounded-full">
                        <i class="fas fa-map-marker-alt mr-1"></i>
                        <?php echo esc_html($regiao_projeto); ?>
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
            </div>
        </div>

        <?php if ($context === 'my-investments' && $dados_pessoais) : ?>
            <?php 
            // Verificar se é SCP
            $is_scp = function_exists('s_invest_is_private_scp') ? s_invest_is_private_scp($id) : false;
            
            if ($is_scp) {
                // Para SCP: calcular dividendos recebidos e cotas
                $dividendos_recebidos = 0;
                $total_cotas = 0;
                
                foreach ($aportes_usuario as $aporte_item) {
                    $aporte_id = $aporte_item->ID;
                    
                    // Somar dividendos
                    $historico_dividendos = get_field('historico_dividendos', $aporte_id) ?: [];
                    foreach ($historico_dividendos as $dividendo) {
                        $dividendos_recebidos += floatval($dividendo['valor'] ?? 0);
                    }
                    
                    // Somar cotas
                    $total_cotas += intval(get_field('quantidade_cotas', $aporte_id) ?: 0);
                }
                
                $yield_percentual = $dados_pessoais['valor_investido'] > 0 ? 
                    ($dividendos_recebidos / $dados_pessoais['valor_investido']) * 100 : 0;
            ?>
                <div class="space-y-3">
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-gray-600 block">Valor Investido</span>
                            <span class="font-bold text-lg text-blue-600">
                                R$ <?php echo number_format($dados_pessoais['valor_investido'], 0, ',', '.'); ?>
                            </span>
                        </div>
                        <div>
                            <span class="text-gray-600 block">Cotas Adquiridas</span>
                            <span class="font-bold text-lg text-purple-600">
                                <?php echo number_format($total_cotas, 0, ',', '.'); ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="bg-green-50 p-3 rounded-lg border border-green-200">
                        <div class="flex justify-between items-center">
                            <span class="text-green-700 text-sm font-medium">
                                <i class="fas fa-coins mr-1"></i>
                                Dividendos Recebidos
                            </span>
                            <div class="text-right">
                                <span class="font-bold text-lg text-green-600">
                                    R$ <?php echo number_format($dividendos_recebidos, 0, ',', '.'); ?>
                                </span>
                                <div class="text-xs text-green-500">
                                    (+<?php echo number_format($yield_percentual, 1, ',', '.'); ?>% yield)
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php 
            } else { 
                // Para Trade: layout original
            ?>
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
                                <?php echo $dados_pessoais['status'] === 'vendido' ? 'Valor na Venda' : 'Valor Atual'; ?>
                            </span>
                            <span class="font-bold text-lg text-primary">
                                R$ <?php echo number_format($dados_pessoais['valor_atual'], 0, ',', '.'); ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 p-3 rounded-lg">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600 text-sm">
                                <?php echo $dados_pessoais['status'] === 'vendido' ? 'Rentabilidade Consolidada' : 'Rentabilidade Projetada'; ?>
                            </span>
                            <div class="text-right">
                                <span class="font-bold text-lg text-green-600">
                                    <?php 
                                    if ($dados_pessoais['status'] === 'vendido') {
                                        echo 'R$ ' . number_format($dados_pessoais['valor_recebido'], 0, ',', '.');
                                    } else {
                                        echo ($dados_pessoais['rentabilidade_reais'] >= 0 ? '+' : '') . 'R$ ' . number_format(abs($dados_pessoais['rentabilidade_reais']), 0, ',', '.');
                                    }
                                    ?>
                                </span>
                                <div class="text-xs <?php echo $dados_pessoais['rentabilidade_pct'] >= 0 ? 'text-green-500' : 'text-red-500'; ?>">
                                    (<?php echo ($dados_pessoais['rentabilidade_pct'] >= 0 ? '+' : ''); ?><?php echo number_format($dados_pessoais['rentabilidade_pct'], 1, ',', '.'); ?>%)
                                </div>
                                <?php if ($dados_pessoais['total_aportes'] > 1 && ($dados_pessoais['aportes_vendidos'] > 0 || $dados_pessoais['aportes_ativos'] > 1)) : ?>
                                    <div class="text-xs text-gray-500 mt-1">
                                        <button onclick="toggleAportesModal(<?php echo esc_attr($id); ?>)" 
                                                class="text-blue-500 underline hover:text-blue-700">
                                            Ver detalhes
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php } ?>
        <?php endif; ?>

        <!-- ✅ SEÇÃO DE INFORMAÇÕES GERAIS - SEMPRE EXIBIDA (EXCETO EM MY-INVESTMENTS) -->
        <?php if ($context !== 'my-investments') : ?>
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
                    <?php
                    $progress_color = 'from-green-500 to-green-600';
                    if ($status_captacao === 'em_breve') {
                        $progress_color = 'from-blue-500 to-blue-600';
                    } elseif ($status_captacao === 'encerrada') {
                        $progress_color = 'from-red-500 to-red-600';
                    } elseif ($porcentagem > 90) {
                        $progress_color = 'from-orange-500 to-orange-600';
                    }
                    ?>
                    <div class="h-full bg-gradient-to-r <?php echo $progress_color; ?> transition-all duration-700 ease-out" 
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
                    <?php echo $status_captacao === 'encerrada' && $porcentagem >= 100 ? 'Esgotado' : 'Encerrado'; ?>
                </button>
            <?php else : ?>
                <a href="<?php echo $link; ?>" 
                class="w-full block text-center py-3 px-4 <?php 
                if ($dados_pessoais && $dados_pessoais['status'] === 'vendido') {
                    echo 'bg-orange-600 hover:bg-orange-700';
                } elseif ($status_captacao === 'em_breve' && $context !== 'my-investments') {
                    echo 'bg-blue-600 hover:bg-blue-700 border-2 border-blue-300';
                } else {
                    echo 'bg-gradient-to-r from-accent to-secondary hover:from-blue-700 hover:to-blue-800';
                }
                ?> text-white rounded-lg transition-all duration-300 transform hover:scale-[1.02] focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 text-sm font-medium shadow-lg hover:shadow-xl"
                <?php if ($context === 'panel') echo 'target="_self" rel="noopener"'; ?>>
                    <i class="fas <?php echo $button_icon; ?> mr-2"></i>
                    <?php 
                    if ($status_captacao === 'em_breve' && $context !== 'my-investments') {
                        echo 'Saiba Mais';
                    } else {
                        echo $button_text;
                    }
                    ?>
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
    
    <!-- ✅ JavaScript Global para Modal -->
    <script>
    // Garantir que a função seja global
    window.toggleAportesModal = function(investmentId) {
        const modal = document.getElementById('aportes-modal-' + investmentId);
        if (modal) {
            if (modal.classList.contains('hidden')) {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                document.body.style.overflow = 'hidden';
            } else {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                document.body.style.overflow = 'auto';
            }
        }
    };
    
    // Fechar modal ao clicar fora
    document.addEventListener('click', function(e) {
        if (e.target.id && e.target.id.startsWith('aportes-modal-')) {
            if (e.target === e.currentTarget) {
                const investmentId = e.target.id.replace('aportes-modal-', '');
                window.toggleAportesModal(investmentId);
            }
        }
    });
    </script>
    
    <?php if ($context === 'my-investments' && $dados_pessoais && $dados_pessoais['total_aportes'] > 1) : ?>
        <!-- Modal para detalhes dos aportes -->
        <div id="aportes-modal-<?php echo esc_attr($id); ?>" 
             class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden items-center justify-center p-4">
            <div class="bg-white rounded-xl max-w-md w-full max-h-96 overflow-y-auto">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">
                            Detalhes dos Aportes
                        </h3>
                        <button onclick="toggleAportesModal(<?php echo esc_attr($id); ?>)" 
                                class="text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    
                    <div class="space-y-3">
                        <?php foreach ($dados_pessoais['aportes_detalhes'] as $index => $aporte_detalhe) : ?>
                            <div class="border border-gray-200 rounded-lg p-3 <?php echo $aporte_detalhe['vendido'] ? 'bg-orange-50' : 'bg-blue-50'; ?>">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="font-medium text-sm">
                                        Aporte #<?php echo $index + 1; ?>
                                    </span>
                                    <span class="px-2 py-1 text-xs rounded-full <?php echo $aporte_detalhe['vendido'] ? 'bg-orange-200 text-orange-800' : 'bg-blue-200 text-blue-800'; ?>">
                                        <?php echo $aporte_detalhe['vendido'] ? 'Vendido' : 'Ativo'; ?>
                                    </span>
                                </div>
                                
                                <div class="text-xs text-gray-600 space-y-1">
                                    <div class="flex justify-between">
                                        <span>Valor de Compra:</span>
                                        <span>R$ <?php echo number_format($aporte_detalhe['valor_compra'], 2, ',', '.'); ?></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span><?php echo $aporte_detalhe['vendido'] ? 'Valor na Venda:' : 'Valor Atual:'; ?></span>
                                        <span>R$ <?php echo number_format($aporte_detalhe['valor_atual'], 2, ',', '.'); ?></span>
                                    </div>
                                    <?php if ($aporte_detalhe['vendido'] && $aporte_detalhe['venda_valor'] > 0) : ?>
                                        <div class="flex justify-between font-medium text-green-600">
                                            <span>Valor Recebido:</span>
                                            <span>R$ <?php echo number_format($aporte_detalhe['venda_valor'], 2, ',', '.'); ?></span>
                                        </div>
                                        <?php if ($aporte_detalhe['venda_data']) : ?>
                                            <div class="flex justify-between text-xs text-gray-500">
                                                <span>Data da Venda:</span>
                                                <span><?php echo esc_html($aporte_detalhe['venda_data']); ?></span>
                                            </div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</article>

<?php wp_reset_postdata(); ?>