<?php
/**
 * Dashboard do Investidor - VERSÃO COMPLETA E OTIMIZADA COM FILTROS MELHORADOS
 * components/painel/investidor/dashboard.php
 */
date_default_timezone_set('America/Sao_Paulo');

$user_id = get_current_user_id();
$user = wp_get_current_user();
$nome_completo = trim($user->first_name . ' ' . $user->last_name) ?: $user->display_name;
$atualizado_em = date('d/m/Y H:i');

$cache_key = 'dashboard_stats_' . $user_id;
$estatisticas = wp_cache_get($cache_key, 'user_stats');

if (false === $estatisticas) {
    $estatisticas = icf_get_estatisticas_aportes_usuario($user_id);
    wp_cache_set($cache_key, $estatisticas, 'user_stats', 15 * MINUTE_IN_SECONDS);
}

$total_investido_ativo = max(0, $estatisticas['valor_investido_ativo']);
$total_atual = max(0, $estatisticas['valor_atual_total']);
$aportes_ativos = max(0, $estatisticas['aportes_ativos']);
$aportes_vendidos = max(0, $estatisticas['aportes_vendidos']);

// RENTABILIDADE PROJETADA = Último valor do histórico (aportes Trade ativos)
$rentabilidade_projetada = max(0, $estatisticas['rentabilidade_projetada']);

// RENTABILIDADE CONSOLIDADA = Total de vendas realizadas + dividendos recebidos
$vendas = $estatisticas['vendas'];
$total_investido_vendido = max(0, $vendas['total_compra']);      
$total_recebido_vendas = max(0, $vendas['total_venda']);         
$rentabilidade_vendas = max(0, $vendas['total_rentabilidade']);

$total_investido_geral = $total_investido_ativo + $total_investido_vendido;

// ========== BUSCAR DADOS PARA OS NOVOS FILTROS ==========
$tipos_produto_extrato = get_terms([
    'taxonomy' => 'tipo_produto',
    'hide_empty' => false,
]);

$investimentos_disponiveis_extrato = get_posts([
    'post_type' => 'investment',
    'posts_per_page' => -1,
    'post_status' => 'publish',
    'orderby' => 'title',
    'order' => 'ASC',
]);

// Arrays para dados do gráfico
$distribuicao = [];
$ultimos = [];
$movimentos_completos = [];

$investidoPorMesAno = [];
$rentabilidadePorMesAno = [];
$rentabilidadeConsolidadaPorMesAno = [];

$mesesTraducao = [
    'Jan' => 'Jan', 'Feb' => 'Fev', 'Mar' => 'Mar', 'Apr' => 'Abr',
    'May' => 'Mai', 'Jun' => 'Jun', 'Jul' => 'Jul', 'Aug' => 'Ago',
    'Sep' => 'Set', 'Oct' => 'Out', 'Nov' => 'Nov', 'Dec' => 'Dez'
];

$args_aportes = [
    'post_type'      => 'aporte',
    'posts_per_page' => -1,
    'post_status'    => 'publish',
    'meta_query'     => [
        ['key' => 'investidor_id', 'value' => $user_id]
    ]
];

$aportes = get_posts($args_aportes);

// ===== CÁLCULO DOS SCPs ATIVOS (dentro do prazo, não vendidos) =====
$total_investido_scp = 0;
$dividendos_recebidos_total = 0;
$quantidade_scp = 0;

foreach ($aportes as $aporte) {
    $aporte_id = $aporte->ID;
    $investment_id = get_field('investment_id', $aporte_id);
    $venda_status = get_field('venda_status', $aporte_id);
    
    // Só processar aportes não vendidos
    if ($investment_id && !$venda_status) {
        // Verificar se é SCP/Private por função helper
        $eh_scp = function_exists('s_invest_is_private_scp') ? s_invest_is_private_scp($investment_id) : false;
        
        // Se não existe a função, verificar por taxonomia
        if (!$eh_scp) {
            $terms = wp_get_post_terms($investment_id, 'tipo_produto');
            foreach ($terms as $term) {
                if (stripos($term->name, 'scp') !== false || 
                    stripos($term->name, 'private') !== false ||
                    stripos($term->slug, 'scp') !== false ||
                    stripos($term->slug, 'private') !== false) {
                    $eh_scp = true;
                    break;
                }
            }
        }
        
        // SCP ativo (não vendido e dentro do prazo de captação/operação)
        if ($eh_scp) {
            $quantidade_scp++;
            
            // Somar valor investido neste SCP
            $historico_aportes = get_field('historico_aportes', $aporte_id) ?: [];
            foreach ($historico_aportes as $item) {
                $total_investido_scp += floatval($item['valor_aporte'] ?? 0);
            }
            
            // Somar dividendos recebidos deste SCP
            $historico_dividendos = get_field('historico_dividendos', $aporte_id) ?: [];
            foreach ($historico_dividendos as $dividendo) {
                $dividendos_recebidos_total += floatval($dividendo['valor'] ?? 0);
            }
        }
    }
}

// RENTABILIDADE CONSOLIDADA = Vendas realizadas + Dividendos recebidos
$rentabilidade_consolidada = $rentabilidade_vendas + $dividendos_recebidos_total;

// ========== PROCESSAR TODOS OS APORTES PARA GRÁFICOS E MOVIMENTOS ==========
if (!empty($aportes)) {
    foreach ($aportes as $aporte) {
        $aporte_id = $aporte->ID;
        $investment_id = get_field('investment_id', $aporte_id);
        $venda_status = get_field('venda_status', $aporte_id);
        $nome_investimento = $investment_id ? get_the_title($investment_id) : 'Investimento não identificado';
        
        // ========== OBTER INFORMAÇÕES ADICIONAIS DO INVESTIMENTO (PARA FILTROS) ==========
        $classe_ativo = '';
        $eh_scp = false;
        $status_captacao = 'ativo';
        
        if ($investment_id) {
            // Classe de ativo
            $terms = wp_get_post_terms($investment_id, 'tipo_produto');
            if (!empty($terms) && !is_wp_error($terms)) {
                $classe_ativo = $terms[0]->slug;
            }
            
            // Verificar se é SCP
            $eh_scp = function_exists('s_invest_is_private_scp') ? s_invest_is_private_scp($investment_id) : false;
            if (!$eh_scp) {
                foreach ($terms as $term) {
                    if (stripos($term->name, 'scp') !== false || 
                        stripos($term->name, 'private') !== false ||
                        stripos($term->slug, 'scp') !== false ||
                        stripos($term->slug, 'private') !== false) {
                        $eh_scp = true;
                        break;
                    }
                }
            }
            
            // Status de captação (para SCPs)
            if ($eh_scp && function_exists('s_invest_calcular_status_captacao')) {
                $status_captacao = s_invest_calcular_status_captacao($investment_id);
            }
        }
        
        // Determinar situação do investimento
        $situacao = 'ativo';
        if ($venda_status) {
            $situacao = 'vendido';
        } elseif ($eh_scp && in_array($status_captacao, ['encerrado', 'encerrado_meta', 'encerrado_data', 'encerrado_manual'])) {
            $situacao = 'encerrado';
        }
        
        // PROCESSAR HISTÓRICO DE APORTES
        $historico_aportes = get_field('historico_aportes', $aporte_id) ?: [];
        $total_aporte_investido = 0;
        
        foreach ($historico_aportes as $index => $item) {
            $valor_aporte_item = (float) ($item['valor_aporte'] ?? 0);
            $data_aporte = $item['data_aporte'] ?? '';
            
            if ($valor_aporte_item > 0 && !empty($data_aporte)) {
                $total_aporte_investido += $valor_aporte_item;
                
                // Processar data para o gráfico
                $data = DateTime::createFromFormat('d/m/Y', $data_aporte);
                if ($data) {
                    $mesIngles = $data->format('M');
                    $ano = $data->format('Y');
                    $mes = $mesesTraducao[$mesIngles] ?? '';
                    $mesAno = $mes . ' ' . $ano;
                    
                    if ($mes) {
                        if (!isset($investidoPorMesAno[$mesAno])) {
                            $investidoPorMesAno[$mesAno] = 0;
                        }
                        $investidoPorMesAno[$mesAno] += $valor_aporte_item;
                    }
                }
                
                // Para a tabela de movimentos
                $status_movimento = $venda_status ? ' (Vendido)' : '';
                $ultimos[] = [
                    'data' => $data_aporte,
                    'valor' => $valor_aporte_item,
                    'investimento' => $nome_investimento . $status_movimento,
                    'vendido' => $venda_status ? true : false
                ];
                
                // ========== MOVIMENTOS COMPLETOS COM NOVOS CAMPOS ==========
                $movimentos_completos[] = [
                    'id' => "aporte_{$aporte_id}_{$index}",
                    'data' => $data_aporte,
                    'tipo' => 'aporte',
                    'investimento' => $nome_investimento,
                    'valor' => $valor_aporte_item,
                    'vendido' => $venda_status ? true : false,
                    'aporte_id' => $aporte_id,
                    'investment_id' => $investment_id,
                    'timestamp' => $data ? $data->getTimestamp() : time(),
                    // ↓ NOVOS CAMPOS PARA FILTROS
                    'classe_ativo' => $classe_ativo,
                    'eh_scp' => $eh_scp,
                    'situacao' => $situacao
                ];
            }
        }
        
        // PROCESSAR DIVIDENDOS
        $historico_dividendos = get_field('historico_dividendos', $aporte_id) ?: [];
        
        foreach ($historico_dividendos as $index => $dividendo) {
            $valor_dividendo = (float) ($dividendo['valor'] ?? 0);
            $data_dividendo = $dividendo['data_dividendo'] ?? $dividendo['data'] ?? '';
            
            if ($valor_dividendo > 0 && !empty($data_dividendo)) {
                $data = DateTime::createFromFormat('d/m/Y', $data_dividendo);
                
                if ($data) {
                    $mesIngles = $data->format('M');
                    $ano = $data->format('Y');
                    $mes = $mesesTraducao[$mesIngles] ?? '';
                    $mesAno = $mes . ' ' . $ano;
                    
                    if ($mes) {
                        if (!isset($rentabilidadeConsolidadaPorMesAno[$mesAno])) {
                            $rentabilidadeConsolidadaPorMesAno[$mesAno] = 0;
                        }
                        $rentabilidadeConsolidadaPorMesAno[$mesAno] += $valor_dividendo;
                    }
                }
                
                // ========== DIVIDENDOS COM NOVOS CAMPOS ==========
                $movimentos_completos[] = [
                    'id' => "dividendo_{$aporte_id}_{$index}",
                    'data' => $data_dividendo,
                    'tipo' => 'dividendo',
                    'investimento' => $nome_investimento,
                    'valor' => $valor_dividendo,
                    'vendido' => $venda_status ? true : false,
                    'aporte_id' => $aporte_id,
                    'investment_id' => $investment_id,
                    'timestamp' => $data ? $data->getTimestamp() : time(),
                    // ↓ NOVOS CAMPOS PARA FILTROS
                    'classe_ativo' => $classe_ativo,
                    'eh_scp' => $eh_scp,
                    'situacao' => $situacao
                ];
            }
        }
        
        // PROCESSAR VENDAS
        if ($venda_status) {
            $valor_recebido_venda = (float) get_field('venda_valor', $aporte_id);
            $data_venda = get_field('venda_data', $aporte_id);
            
            if ($valor_recebido_venda > 0 && !empty($data_venda)) {
                $data = DateTime::createFromFormat('d/m/Y', $data_venda);
                if ($data) {
                    $mesIngles = $data->format('M');
                    $ano = $data->format('Y');
                    $mes = $mesesTraducao[$mesIngles] ?? '';
                    $mesAno = $mes . ' ' . $ano;
                    
                    if ($mes) {
                        if (!isset($rentabilidadeConsolidadaPorMesAno[$mesAno])) {
                            $rentabilidadeConsolidadaPorMesAno[$mesAno] = 0;
                        }
                        $rentabilidadeConsolidadaPorMesAno[$mesAno] += $valor_recebido_venda;
                    }
                }
            }
        }
        
        // PROCESSAR HISTÓRICO DE RENTABILIDADE (apenas aportes Trade ativos)
        if (!$venda_status) {
            $eh_trade = true;
            
            // Verificar se é SCP para excluir da rentabilidade projetada
            if ($investment_id) {
                $eh_scp = function_exists('s_invest_is_private_scp') ? s_invest_is_private_scp($investment_id) : false;
                
                if (!$eh_scp) {
                    $terms = wp_get_post_terms($investment_id, 'tipo_produto');
                    foreach ($terms as $term) {
                        if (stripos($term->name, 'scp') !== false || 
                            stripos($term->name, 'private') !== false ||
                            stripos($term->slug, 'scp') !== false ||
                            stripos($term->slug, 'private') !== false) {
                            $eh_scp = true;
                            break;
                        }
                    }
                }
                
                $eh_trade = !$eh_scp;
            }
            
            // Só processar rentabilidade de produtos Trade ativos
            if ($eh_trade) {
                $rentabilidade_hist = get_field('rentabilidade_historico', $aporte_id) ?: [];
                
                foreach ($rentabilidade_hist as $item) {
                    if (isset($item['data_rentabilidade']) && isset($item['valor'])) {
                        $data_rentabilidade = $item['data_rentabilidade'];
                        $valor_rentabilidade = (float) $item['valor'];
                        
                        if (!empty($data_rentabilidade) && $valor_rentabilidade > 0) {
                            $data = DateTime::createFromFormat('d/m/Y', $data_rentabilidade);
                            if ($data) {
                                $mesIngles = $data->format('M');
                                $ano = $data->format('Y');
                                $mes = $mesesTraducao[$mesIngles] ?? '';
                                $mesAno = $mes . ' ' . $ano;
                                
                                if ($mes) {
                                    if (!isset($rentabilidadePorMesAno[$mesAno])) {
                                        $rentabilidadePorMesAno[$mesAno] = 0;
                                    }
                                    $rentabilidadePorMesAno[$mesAno] += $valor_rentabilidade;
                                }
                            }
                        }
                    }
                }
            }
        }
        
        // DISTRIBUIÇÃO POR CATEGORIA (apenas aportes ativos)
        if ($investment_id && $total_aporte_investido > 0 && !$venda_status) {
            $terms = wp_get_post_terms($investment_id, 'tipo_produto');
            
            if (!empty($terms) && !is_wp_error($terms)) {
                $categoria = $terms[0]->name;
                
                if (!isset($distribuicao[$categoria])) {
                    $distribuicao[$categoria] = 0;
                }
                $distribuicao[$categoria] += $total_aporte_investido;
            } else {
                if (!isset($distribuicao['Outros'])) {
                    $distribuicao['Outros'] = 0;
                }
                $distribuicao['Outros'] += $total_aporte_investido;
            }
        }
    }
}

// ========== ORGANIZAR DADOS DO GRÁFICO CRONOLOGICAMENTE ==========
$todosMesesAno = array_unique(array_merge(
    array_keys($investidoPorMesAno),
    array_keys($rentabilidadePorMesAno),
    array_keys($rentabilidadeConsolidadaPorMesAno)
));

usort($todosMesesAno, function($a, $b) {
    $partes_a = explode(' ', $a);
    $partes_b = explode(' ', $b);
    
    if (count($partes_a) != 2 || count($partes_b) != 2) return 0;
    
    $ano_a = (int)$partes_a[1];
    $ano_b = (int)$partes_b[1];
    
    if ($ano_a != $ano_b) {
        return $ano_a - $ano_b;
    }
    
    $ordemMeses = ['Jan' => 1, 'Fev' => 2, 'Mar' => 3, 'Abr' => 4, 'Mai' => 5, 'Jun' => 6,
                   'Jul' => 7, 'Ago' => 8, 'Set' => 9, 'Out' => 10, 'Nov' => 11, 'Dez' => 12];
    
    $mes_a = $ordemMeses[$partes_a[0]] ?? 0;
    $mes_b = $ordemMeses[$partes_b[0]] ?? 0;
    
    return $mes_a - $mes_b;
});

$chartLabels = [];
$chartInvestido = [];
$chartRentabilidade = [];
$chartRentabilidadeConsolidada = [];

foreach ($todosMesesAno as $mesAno) {
    $chartLabels[] = $mesAno;
    $chartInvestido[] = $investidoPorMesAno[$mesAno] ?? 0;
    $chartRentabilidade[] = $rentabilidadePorMesAno[$mesAno] ?? 0;
    $chartRentabilidadeConsolidada[] = $rentabilidadeConsolidadaPorMesAno[$mesAno] ?? 0;
}

// ========== ORDENAR MOVIMENTOS ==========
usort($movimentos_completos, function ($a, $b) {
    return $b['timestamp'] - $a['timestamp'];
});

$movimentos_completos = array_slice($movimentos_completos, 0, 100);

usort($ultimos, function ($a, $b) {
    $dataA = DateTime::createFromFormat('d/m/Y', $a['data']);
    $dataB = DateTime::createFromFormat('d/m/Y', $b['data']);
    
    if (!$dataA || !$dataB) {
        return 0;
    }
    
    return $dataB->getTimestamp() - $dataA->getTimestamp();
});

$ultimos = array_slice($ultimos, 0, 10);
?>

<div x-data="dashboardData()" class="space-y-6 lg:py-10 main-content-mobile min-h-screen">

    <!-- CABEÇALHO -->
    <div class="bg-gradient-to-r from-primary to-slate-950 rounded-2xl p-6 lg:p-8 text-white shadow-2xl">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <h1 class="text-2xl lg:text-3xl font-bold mb-2">
                    Olá, <?php echo esc_html($nome_completo); ?>! 👋
                </h1>
                <p class="text-blue-100 text-sm lg:text-base">
                    Resumo atualizado em <?php echo $atualizado_em; ?>
                </p>
            </div>
            
            <div class="flex gap-3">
                <a href="?secao=meus-investimentos" 
                   class="px-4 py-2 bg-white/20 hover:bg-white/30 rounded-lg text-sm font-medium transition-colors backdrop-blur-sm">
                    <i class="fas fa-chart-pie mr-2"></i>
                    Meus Investimentos
                </a>
                <a href="?secao=produtos-gerais" 
                   class="px-4 py-2 bg-secondary hover:bg-secondary/90 rounded-lg text-sm font-medium transition-colors">
                    <i class="fas fa-plus mr-2"></i>
                    Investir
                </a>
            </div>
        </div>
    </div>

    <!-- CARDS DE MÉTRICAS -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6">
        
        <!-- Card 1: Aportes Ativos -->
        <div class="bg-white rounded-2xl p-6 shadow-lg border border-gray-100 hover:shadow-xl transition-shadow duration-300">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                    <i class="fas fa-chart-line text-xl text-blue-600"></i>
                </div>
                <span class="text-xs font-medium px-2 py-1 bg-blue-50 text-blue-600 rounded-full">
                    <?php echo $aportes_ativos; ?> ativo<?php echo $aportes_ativos != 1 ? 's' : ''; ?>
                </span>
            </div>
            
            <div class="flex items-center gap-2 mb-1">
                <h3 class="text-sm font-medium text-gray-600">Aportes Ativos</h3>
                
                <div class="relative" x-data="{ showTooltip: false }">
                    <button 
                        @mouseenter="showTooltip = true" 
                        @mouseleave="showTooltip = false"
                        @click="showTooltip = !showTooltip"
                        class="w-4 h-4 bg-gray-100 hover:bg-gray-200 rounded-full flex items-center justify-center transition-colors"
                    >
                        <i class="fas fa-question text-xs text-gray-500"></i>
                    </button>
                    
                    <div 
                        x-show="showTooltip"
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 transform scale-95"
                        x-transition:enter-end="opacity-100 transform scale-100"
                        x-transition:leave="transition ease-in duration-150"
                        x-transition:leave-start="opacity-100 transform scale-100"
                        x-transition:leave-end="opacity-0 transform scale-95"
                        class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-2 bg-gray-900 text-white text-xs rounded-lg shadow-lg z-10 whitespace-nowrap"
                        style="display: none;"
                    >
                        Soma dos aportes que ainda não foram vendidos ou estão em captação.
                        <div class="absolute top-full left-1/2 transform -translate-x-1/2 w-2 h-2 bg-gray-900 rotate-45"></div>
                    </div>
                </div>
            </div>
            
            <p class="text-2xl font-bold text-gray-900">
                R$ <?php echo number_format($total_investido_ativo, 0, ',', '.'); ?>
            </p>
        </div>

        <!-- Card 2: Rentabilidade Projetada (Trade) -->
        <div class="bg-white rounded-2xl p-6 shadow-lg border border-gray-100 hover:shadow-xl transition-shadow duration-300">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center">
                    <i class="fa-solid fa-money-bill-trend-up text-xl text-purple-600"></i>
                </div>
                <span class="text-xs font-medium px-2 py-1 <?php echo $rentabilidade_projetada >= 0 ? 'bg-green-50 text-green-600' : 'bg-red-50 text-red-600'; ?> rounded-full">
                    Trade
                </span>
            </div>
            
            <div class="flex items-center gap-2 mb-1">
                <h3 class="text-sm font-medium text-gray-600">Rentabilidade Projetada</h3>
                
                <div class="relative" x-data="{ showTooltip: false }">
                    <button 
                        @mouseenter="showTooltip = true" 
                        @mouseleave="showTooltip = false"
                        @click="showTooltip = !showTooltip"
                        class="w-4 h-4 bg-gray-100 hover:bg-gray-200 rounded-full flex items-center justify-center transition-colors"
                    >
                        <i class="fas fa-question text-xs text-gray-500"></i>
                    </button>
                    
                    <div 
                        x-show="showTooltip"
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 transform scale-95"
                        x-transition:enter-end="opacity-100 transform scale-100"
                        x-transition:leave="transition ease-in duration-150"
                        x-transition:leave-start="opacity-100 transform scale-100"
                        x-transition:leave-end="opacity-0 transform scale-95"
                        class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-2 bg-gray-900 text-white text-xs rounded-lg shadow-lg z-10 whitespace-nowrap"
                        style="display: none;"
                    >
                        Valorizações dos produtos Trade ativos.
                        <div class="absolute top-full left-1/2 transform -translate-x-1/2 w-2 h-2 bg-gray-900 rotate-45"></div>
                    </div>
                </div>
            </div>
            
            <p class="text-2xl font-bold <?php echo $rentabilidade_projetada >= 0 ? 'text-green-600' : 'text-red-600'; ?>">
                <?php echo ($rentabilidade_projetada >= 0 ? '+' : ''); ?>R$ <?php echo number_format(abs($rentabilidade_projetada), 0, ',', '.'); ?>
            </p>
        </div>

        <!-- Card 3: Rentabilidade Consolidada -->
        <div class="bg-white rounded-2xl p-6 shadow-lg border border-gray-100 hover:shadow-xl transition-shadow duration-300">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-orange-100 rounded-xl flex items-center justify-center">
                    <i class="fas fa-hand-holding-usd text-xl text-orange-600"></i>
                </div>
                <span class="text-xs font-medium px-2 py-1 bg-orange-50 text-orange-600 rounded-full">
                    Realizada
                </span>
            </div>
            
            <div class="flex items-center gap-2 mb-1">
                <h3 class="text-sm font-medium text-gray-600">Rentabilidade Consolidada</h3>
                
                <div class="relative" x-data="{ showTooltip: false }">
                    <button 
                        @mouseenter="showTooltip = true" 
                        @mouseleave="showTooltip = false"
                        @click="showTooltip = !showTooltip"
                        class="w-4 h-4 bg-gray-100 hover:bg-gray-200 rounded-full flex items-center justify-center transition-colors"
                    >
                        <i class="fas fa-question text-xs text-gray-500"></i>
                    </button>
                    
                    <div 
                        x-show="showTooltip"
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 transform scale-95"
                        x-transition:enter-end="opacity-100 transform scale-100"
                        x-transition:leave="transition ease-in duration-150"
                        x-transition:leave-start="opacity-100 transform scale-100"
                        x-transition:leave-end="opacity-0 transform scale-95"
                        class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-2 bg-gray-900 text-white text-xs rounded-lg shadow-lg z-10 whitespace-nowrap"
                        style="display: none;"
                    >
                        Rentabilidade dos Trade vendidos + dividendos recebidos.
                        <div class="absolute top-full left-1/2 transform -translate-x-1/2 w-2 h-2 bg-gray-900 rotate-45"></div>
                    </div>
                </div>
            </div>
            
            <p class="text-2xl font-bold text-green-800">
                R$ <?php echo number_format($rentabilidade_consolidada, 0, ',', '.'); ?>
            </p>
        </div>

        <!-- Card 4: SCP Ativos -->
        <div class="bg-white rounded-2xl p-6 shadow-lg border border-gray-100 hover:shadow-xl transition-shadow duration-300">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-emerald-100 rounded-xl flex items-center justify-center">
                    <i class="fas fa-building text-xl text-emerald-600"></i>
                </div>
                <span class="text-xs font-medium px-2 py-1 bg-emerald-50 text-emerald-600 rounded-full">
                    <?php echo $quantidade_scp; ?> SCP<?php echo $quantidade_scp != 1 ? 's' : ''; ?>
                </span>
            </div>
            
            <div class="flex items-center gap-2 mb-1">
                <h3 class="text-sm font-medium text-gray-600">SCP Ativos</h3>
                
                <div class="relative" x-data="{ showTooltip: false }">
                    <button 
                        @mouseenter="showTooltip = true" 
                        @mouseleave="showTooltip = false"
                        @click="showTooltip = !showTooltip"
                        class="w-4 h-4 bg-gray-100 hover:bg-gray-200 rounded-full flex items-center justify-center transition-colors"
                    >
                        <i class="fas fa-question text-xs text-gray-500"></i>
                    </button>
                    
                    <div 
                        x-show="showTooltip"
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 transform scale-95"
                        x-transition:enter-end="opacity-100 transform scale-100"
                        x-transition:leave="transition ease-in duration-150"
                        x-transition:leave-start="opacity-100 transform scale-100"
                        x-transition:leave-end="opacity-0 transform scale-95"
                        class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-2 bg-gray-900 text-white text-xs rounded-lg shadow-lg z-10 whitespace-nowrap"
                        style="display: none;"
                    >
                        Produtos Private/SCP ativos ainda dentro do prazo.
                        <div class="absolute top-full left-1/2 transform -translate-x-1/2 w-2 h-2 bg-gray-900 rotate-45"></div>
                    </div>
                </div>
            </div>
            
            <p class="text-2xl font-bold text-gray-900">
                R$ <?php echo number_format($total_investido_scp, 0, ',', '.'); ?>
            </p>
        </div>
    </div>

    <!-- GRÁFICOS -->
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        
        <!-- Distribuição por Categoria -->
        <div class="bg-white rounded-2xl p-6 shadow-lg border border-gray-100">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-semibold text-gray-900">Distribuição por Categoria</h3>
                <div class="text-xs text-gray-500 bg-gray-50 px-2 py-1 rounded">
                    Apenas ativos
                </div>
            </div>
            
            <div class="relative" style="height: 280px;">
                <?php if (!empty($distribuicao)): ?>
                    <canvas id="distributionChart" class="max-w-full"></canvas>
                <?php else: ?>
                    <div class="flex items-center justify-center h-full text-gray-400">
                        <div class="text-center">
                            <i class="fas fa-chart-pie text-4xl mb-3"></i>
                            <p class="text-sm">Nenhum investimento ativo</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Performance Mensal -->
        <div class="bg-white rounded-2xl p-6 shadow-lg border border-gray-100">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-semibold text-gray-900">Performance Mensal</h3>
                <div class="text-xs text-gray-500 bg-gray-50 px-2 py-1 rounded">
                    Histórico de aportes e rentabilidade
                </div>
            </div>
            
            <div class="relative" style="height: 280px;">
                <canvas id="performanceChart" class="max-w-full"></canvas>
            </div>
        </div>
    </div>

    <!-- ========== EXTRATO DE MOVIMENTAÇÕES COM FILTROS CORRIGIDOS ========== -->
    <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden" 
         x-data="extratoData()" x-init="init()">
         <!-- OFFCANVAS DE FILTROS -->
            <div x-show="$data.showOffcanvas" 
                x-transition:enter="transition ease-out duration-300" 
                x-transition:enter-start="opacity-0" 
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-200" 
                x-transition:leave-start="opacity-100" 
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 bg-black/50 z-50" 
                @click="$data.showOffcanvas = false"
                style="display: none;">
                
                <div x-show="$data.showOffcanvas"
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="transform translate-x-full"
                    x-transition:enter-end="transform translate-x-0"
                    x-transition:leave="transition ease-in duration-200"
                    x-transition:leave-start="transform translate-x-0"
                    x-transition:leave-end="transform translate-x-full"
                    @click.stop
                    class="absolute right-0 top-0 h-full w-80 bg-white shadow-xl overflow-y-auto">
                    
                    <!-- Header -->
                    <div class="bg-gradient-to-r from-blue-600 to-blue-700 text-white p-6 sticky top-0 z-10">
                        <h3 class="text-lg font-bold">Filtros</h3>
                        <button @click="$data.showOffcanvas = false" 
                                class="p-2 hover:bg-gray-100 rounded-lg">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    
                    <!-- Conteúdo -->
                    <div class="p-4 space-y-6">
                        
                        <!-- Classe de Ativos -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Classe de Ativos</label>
                            <select x-model="filtros.classe_ativo" 
                                    @change="filtros.situacao = ''; aplicarFiltros()" 
                                    class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Todas as Classes</option>
                                <?php foreach ($tipos_produto_extrato as $tipo) : ?>
                                    <option value="<?= esc_attr($tipo->slug) ?>"><?= esc_html($tipo->name) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Situação -->
                        <div x-show="filtros.classe_ativo !== ''" x-transition>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Situação</label>
                            <select x-model="filtros.situacao" 
                                    @change="aplicarFiltros()" 
                                    class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Todas Situações</option>
                                <option value="ativo">Ativo</option>
                                <option value="vendido">Vendido</option>
                                <option value="encerrado">Encerrado</option>
                            </select>
                        </div>
                        
                        <!-- Período -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Período</label>
                            
                            <!-- Períodos Rápidos -->
                            <div class="mb-4">
                                <div class="grid grid-cols-2 gap-2">
                                    <button @click="filtros.periodo = '7'; filtros.data_inicio = ''; filtros.data_fim = ''; aplicarFiltros()" 
                                            :class="filtros.periodo === '7' ? 'bg-blue-100 border-blue-500 text-blue-700' : 'bg-gray-50 border-gray-300'"
                                            class="text-xs px-3 py-2 border rounded transition-colors">
                                        Últimos 7 dias
                                    </button>
                                    <button @click="filtros.periodo = '30'; filtros.data_inicio = ''; filtros.data_fim = ''; aplicarFiltros()" 
                                            :class="filtros.periodo === '30' ? 'bg-blue-100 border-blue-500 text-blue-700' : 'bg-gray-50 border-gray-300'"
                                            class="text-xs px-3 py-2 border rounded transition-colors">
                                        Últimos 30 dias
                                    </button>
                                    <button @click="filtros.periodo = '90'; filtros.data_inicio = ''; filtros.data_fim = ''; aplicarFiltros()" 
                                            :class="filtros.periodo === '90' ? 'bg-blue-100 border-blue-500 text-blue-700' : 'bg-gray-50 border-gray-300'"
                                            class="text-xs px-3 py-2 border rounded transition-colors">
                                        Últimos 3 meses
                                    </button>
                                    <button @click="filtros.periodo = '365'; filtros.data_inicio = ''; filtros.data_fim = ''; aplicarFiltros()" 
                                            :class="filtros.periodo === '365' ? 'bg-blue-100 border-blue-500 text-blue-700' : 'bg-gray-50 border-gray-300'"
                                            class="text-xs px-3 py-2 border rounded transition-colors">
                                        Último ano
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Período Personalizado -->
                            <div class="border-t pt-4">
                                <h4 class="text-sm font-medium text-gray-700 mb-2">Período Personalizado</h4>
                                <div class="space-y-3">
                                    <div>
                                        <label class="block text-xs text-gray-600 mb-1">Data Inicial</label>
                                        <input type="date" 
                                            x-model="filtros.data_inicio"
                                            :max="new Date().toISOString().split('T')[0]"
                                            class="w-full text-sm border border-gray-300 rounded px-3 py-2 focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                                    </div>
                                    <div>
                                        <label class="block text-xs text-gray-600 mb-1">Data Final</label>
                                        <input type="date" 
                                            x-model="filtros.data_fim"
                                            :min="filtros.data_inicio"
                                            :max="new Date().toISOString().split('T')[0]"
                                            class="w-full text-sm border border-gray-300 rounded px-3 py-2 focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                    </div>
                    
                    <!-- Footer -->
                    <div class="border-t p-4 bg-gray-50">
                        <div class="flex gap-3">
                            <button @click="limparFiltros()" 
                                    class="flex-1 px-4 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                                Limpar Tudo
                            </button>
                            <button @click="$data.showOffcanvas = false" 
                                    class="flex-1 px-4 py-2 text-sm text-white bg-primary rounded-lg hover:bg-slate-950">
                                Aplicar
                            </button>
                        </div>
                    </div>
                    
                </div>
            </div>
        <!-- HEADER COM FILTROS CORRIGIDOS -->
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Extrato de Movimentações</h3>
                    <p class="text-sm text-gray-600">Histórico completo de aportes e dividendos</p>
                </div>

                <!-- BOTÃO FILTROS -->
                <button @click="$data.showOffcanvas = true" 
                        class="inline-flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-lg hover:bg-slate-950 transition-colors">
                    <i class="fas fa-filter"></i>
                    <span>Filtros</span>
                    <span x-show="temFiltrosAtivos()" 
                        class="bg-white/20 text-xs px-2 py-0.5 rounded-full"
                        x-text="contarMovimentosFiltrados()"></span>
                </button>
            </div>
            
            <!-- RESUMO DOS FILTROS ATIVOS -->
            <div x-show="temFiltrosAtivos()" x-transition class="mt-3 flex flex-wrap gap-2">
                <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded-full">
                    <span x-text="contarMovimentosFiltrados()"></span> movimentação(ões) encontrada(s)
                </span>
                <span x-show="filtros.classe_ativo" class="text-xs bg-indigo-100 text-indigo-800 px-2 py-1 rounded-full">
                    Classe: <span x-text="obterLabelClasse()"></span>
                </span>
                <span x-show="filtros.situacao" class="text-xs bg-green-100 text-green-800 px-2 py-1 rounded-full">
                    Situação: <span x-text="obterLabelSituacao()"></span>
                </span>
                <span x-show="filtros.periodo || (filtros.data_inicio && filtros.data_fim)" class="text-xs bg-orange-100 text-orange-800 px-2 py-1 rounded-full">
                    Período: <span x-text="obterLabelPeriodo()"></span>
                </span>
                <button @click="limparFiltros()" 
                        class="text-xs bg-red-100 text-red-800 px-2 py-1 rounded-full hover:bg-red-200 transition-colors">
                    <i class="fas fa-times mr-1"></i> Limpar todos
                </button>
            </div>
        </div>
        
        <!-- TABELA -->
        <div class="overflow-x-auto">
            <div x-show="carregando" class="flex items-center justify-center py-12">
                <div class="flex items-center gap-2 text-gray-500">
                    <svg class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="m4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span class="text-sm">Aplicando filtros...</span>
                </div>
            </div>
            
            <div x-show="!carregando">
                <template x-if="movimentosFiltrados.length > 0">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Investimento</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Valor</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Situação</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <template x-for="movimento in movimentosFiltrados.slice(0, limite)" :key="movimento.id">
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" x-text="movimento.data"></td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span :class="movimento.tipo === 'aporte' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800'" 
                                              class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                                            <i :class="movimento.tipo === 'aporte' ? 'fas fa-arrow-up' : 'fas fa-arrow-down'" class="mr-1"></i>
                                            <span x-text="movimento.tipo === 'aporte' ? 'Aporte' : 'Dividendo'"></span>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        <div class="max-w-xs truncate" x-text="movimento.investimento"></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <span :class="movimento.tipo === 'aporte' ? 'text-red-600' : 'text-green-600'">
                                            <span x-text="movimento.tipo === 'aporte' ? '-' : '+'"></span>R$ <span x-text="movimento.valor_formatado"></span>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span :class="movimento.situacao === 'ativo' ? 'bg-green-100 text-green-800' : (movimento.situacao === 'vendido' ? 'bg-orange-100 text-orange-800' : 'bg-red-100 text-red-800')" 
                                              class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                                            <i :class="movimento.situacao === 'ativo' ? 'fas fa-chart-line' : (movimento.situacao === 'vendido' ? 'fas fa-hand-holding-usd' : 'fas fa-times-circle')" class="mr-1"></i>
                                            <span x-text="movimento.situacao === 'ativo' ? 'Ativo' : (movimento.situacao === 'vendido' ? 'Vendido' : 'Encerrado')"></span>
                                        </span>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </template>
                
                <!-- ESTADO VAZIO -->
                <template x-if="movimentosFiltrados.length === 0">
                    <div class="px-6 py-12 text-center">
                        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-filter text-2xl text-gray-400"></i>
                        </div>
                        <h3 class="text-sm font-medium text-gray-900 mb-2">Nenhum movimento encontrado</h3>
                        <p class="text-sm text-gray-500 mb-4">
                            Ajuste os filtros para encontrar suas movimentações.
                        </p>
                        <button @click="limparFiltros()" 
                                class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary hover:bg-slate-950">
                            Limpar filtros
                        </button>
                    </div>
                </template>
            </div>
        </div>
        
        <!-- FOOTER COM PAGINAÇÃO --> 
        <div x-show="movimentosFiltrados.length > limite" class="px-6 py-4 bg-gray-50 border-t border-gray-100">
            <div class="flex items-center justify-between">
                <p class="text-sm text-gray-700">
                    Mostrando <span x-text="Math.min(limite, movimentosFiltrados.length)"></span> de 
                    <span x-text="movimentosFiltrados.length"></span> movimentações
                </p>
                <div class="flex gap-2">
                    <button @click="limite = limite + 10" 
                            x-show="limite < movimentosFiltrados.length"
                            class="px-3 py-1 text-sm bg-white border border-gray-300 rounded hover:bg-gray-50">
                        Carregar mais
                    </button>
                    <a href="?secao=meus-investimentos" 
                       class="px-3 py-1 text-sm text-primary hover:text-slate-950 font-medium">
                        Ver todos →
                    </a>
                </div>
            </div>
        </div>
    </div>

</div>

<?php if (!empty($distribuicao) || !empty($chartLabels)): ?>
<script>
window.dashboardChartData = {
    // Dados da distribuição (donut chart)
    distribuicaoData: <?php echo json_encode($distribuicao); ?>,
    
    // Dados do gráfico de barras - AGORA COM 3 DATASETS CORRIGIDOS
    chartLabels: <?php echo json_encode($chartLabels); ?>,
    chartInvestido: <?php echo json_encode($chartInvestido); ?>,
    chartRentabilidade: <?php echo json_encode($chartRentabilidade); ?>,
    chartRentabilidadeConsolidada: <?php echo json_encode($chartRentabilidadeConsolidada); ?> // ← NOVO DATASET
};

// Dados para o extrato (NOVO)
window.dashboardMovimentos = <?php echo json_encode($movimentos_completos); ?>;

// ========== DADOS ADICIONAIS PARA OS NOVOS FILTROS ==========
window.dashboardFiltrosDados = {
    tiposAtivo: <?php echo json_encode(array_map(function($term) {
        return ['slug' => $term->slug, 'name' => $term->name];
    }, $tipos_produto_extrato)); ?>,
    
    investimentosDisponiveis: <?php echo json_encode(array_map(function($inv) {
        return ['id' => $inv->ID, 'title' => $inv->post_title];
    }, $investimentos_disponiveis_extrato)); ?>
};

// Compatibilidade com código existente
window.dashboardUltimos = <?php echo json_encode($ultimos); ?>;

// Debug
console.log('Dashboard carregado com filtros corrigidos:', {
    aportes: <?php echo count($aportes); ?>,
    movimentos: window.dashboardMovimentos?.length || 0,
    distribuicao: Object.keys(window.dashboardChartData.distribuicaoData).length,
    datasets: {
        investido: window.dashboardChartData.chartInvestido?.length || 0,
        rentabilidade: window.dashboardChartData.chartRentabilidade?.length || 0,
        consolidada: window.dashboardChartData.chartRentabilidadeConsolidada?.length || 0
    },
    filtros: {
        tipos: window.dashboardFiltrosDados.tiposAtivo?.length || 0,
        investimentos: window.dashboardFiltrosDados.investimentosDisponiveis?.length || 0
    }
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const initDashboardCharts = () => {
        if (typeof Chart === 'undefined') {
            setTimeout(initDashboardCharts, 100);
            return;
        }

        const chartData = window.dashboardChartData || {};
        
        initDistributionChart(chartData);
        initPerformanceChart(chartData);
    };
    
    const initDistributionChart = (chartData) => {
        const canvas = document.getElementById('distributionChart');
        if (!canvas) return;
        
        const data = chartData.distribuicaoData || {};
        const hasData = Object.keys(data).length > 0;
        
        if (!hasData) return;
        
        const ctx = canvas.getContext('2d');
        
        if (window.distributionChartInstance) {
            window.distributionChartInstance.destroy();
        }
        
        const labels = Object.keys(data);
        const values = Object.values(data);
        const colors = [
            '#2ED2F8', '#10B981', '#F59E0B', '#EF4444', 
            '#8B5CF6', '#F97316', '#06B6D4', '#84CC16'
        ];
        
        try {
            window.distributionChartInstance = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: values,
                        backgroundColor: colors.slice(0, labels.length),
                        borderWidth: 2,
                        borderColor: '#ffffff',
                        hoverOffset: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '65%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 20,
                                usePointStyle: true,
                                font: { size: 12 },
                                generateLabels: function(chart) {
                                    const data = chart.data;
                                    if (data.labels.length && data.datasets.length) {
                                        return data.labels.map((label, i) => {
                                            const dataset = data.datasets[0];
                                            const value = dataset.data[i];
                                            const total = dataset.data.reduce((a, b) => a + b, 0);
                                            const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                            
                                            return {
                                                text: `${label} (${percentage}%)`,
                                                fillStyle: dataset.backgroundColor[i],
                                                strokeStyle: dataset.borderColor || '#fff',
                                                lineWidth: dataset.borderWidth || 0,
                                                hidden: false,
                                                index: i
                                            };
                                        });
                                    }
                                    return [];
                                }
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleColor: '#ffffff',
                            bodyColor: '#ffffff',
                            borderColor: 'rgba(255, 255, 255, 0.1)',
                            borderWidth: 1,
                            callbacks: {
                                label: function(context) {
                                    const value = context.parsed || 0;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                    return `${context.label}: R$ ${value.toLocaleString('pt-BR')} (${percentage}%)`;
                                }
                            }
                        }
                    },
                    onHover: (event, activeElements) => {
                        event.native.target.style.cursor = activeElements.length > 0 ? 'pointer' : 'default';
                    },
                    animation: {
                        animateRotate: true,
                        animateScale: false
                    }
                }
            });
        } catch (error) {
            console.error('Erro ao criar gráfico de distribuição:', error);
        }
    };
    
    const initPerformanceChart = (chartData) => {
        const canvas = document.getElementById('performanceChart');
        if (!canvas) return;
        
        const ctx = canvas.getContext('2d');
        
        if (window.performanceChartInstance) {
            window.performanceChartInstance.destroy();
        }
        
        // DADOS CORRIGIDOS: Usar os arrays preparados no PHP COM 3 DATASETS
        const labels = chartData.chartLabels || [];
        const investidoData = chartData.chartInvestido || [];
        const rentabilidadeData = chartData.chartRentabilidade || [];
        const rentabilidadeConsolidadaData = chartData.chartRentabilidadeConsolidada || []; // ← NOVO DATASET
        
        // Se não há dados, mostrar exemplo vazio
        if (labels.length === 0) {
            labels.push('Sem dados');
            investidoData.push(0);
            rentabilidadeData.push(0);
            rentabilidadeConsolidadaData.push(0); // ← NOVO
        }
        
        try {
            window.performanceChartInstance = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Valor Investido',
                            data: investidoData,
                            backgroundColor: '#3B82F6', // Azul
                            borderRadius: 6,
                            barThickness: window.innerWidth < 768 ? 12 : 16,
                            borderSkipped: false
                        },
                        {
                            label: 'Rentabilidade Projetada Acumulada',
                            data: rentabilidadeData,
                            backgroundColor: '#10B981', // Verde
                            borderRadius: 6,
                            barThickness: window.innerWidth < 768 ? 12 : 16,
                            borderSkipped: false
                        },
                        {
                            label: 'Rentabilidade Consolidada Acumulada', // ← NOVO DATASET
                            data: rentabilidadeConsolidadaData,
                            backgroundColor: '#F59E0B', // Laranja/Amarelo
                            borderRadius: 6,
                            barThickness: window.innerWidth < 768 ? 12 : 16,
                            borderSkipped: false
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    },
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                            labels: {
                                usePointStyle: true,
                                padding: 15,
                                font: { size: 11 }
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleColor: '#ffffff',
                            bodyColor: '#ffffff',
                            borderColor: 'rgba(255, 255, 255, 0.1)',
                            borderWidth: 1,
                            callbacks: {
                                label: function(context) {
                                    const value = context.parsed ? context.parsed.y : 0;
                                    return `${context.dataset.label}: R$ ${value.toLocaleString('pt-BR')}`;
                                },
                                footer: function(tooltipItems) {
                                    if (tooltipItems.length >= 2) {
                                        let total = 0;
                                        tooltipItems.forEach(item => {
                                            total += item.parsed.y || 0;
                                        });
                                        return `Total do período: R$ ${total.toLocaleString('pt-BR')}`;
                                    }
                                    return '';
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: {
                                color: '#6B7280',
                                font: { size: window.innerWidth < 768 ? 10 : 11 }
                            }
                        },
                        y: {
                            beginAtZero: true,
                            grid: { color: 'rgba(0, 0, 0, 0.05)' },
                            ticks: {
                                color: '#6B7280',
                                font: { size: window.innerWidth < 768 ? 10 : 11 },
                                callback: function(value) {
                                    return 'R$ ' + (value || 0).toLocaleString('pt-BR', {
                                        minimumFractionDigits: 0,
                                        maximumFractionDigits: 0
                                    });
                                }
                            }
                        }
                    }
                }
            });
            
            console.log('✅ Gráfico de performance criado com 3 datasets:', {
                investido: investidoData.length,
                projetada: rentabilidadeData.length,
                consolidada: rentabilidadeConsolidadaData.length
            });
            
        } catch (error) {
            console.error('Erro ao criar gráfico de performance:', error);
        }
    };
    
    window.addEventListener('resize', () => {
        if (window.distributionChartInstance) {
            window.distributionChartInstance.resize();
        }
        if (window.performanceChartInstance) {
            window.performanceChartInstance.resize();
        }
    });
    
    setTimeout(initDashboardCharts, 250);
});

window.dashboardData = function() {
    return {
        chartsInitialized: false,
        
        init() {
            this.chartsInitialized = true;
        }
    };
};
</script>
<?php endif; ?>

<!-- SCRIPT PARA FUNÇÃO EXTRATO -->
<script src="<?php echo S_INVEST_THEME_URL; ?>/public/js/dashboard-fix.js"></script>