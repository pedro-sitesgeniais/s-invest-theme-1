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

$aportes_ativos = 0;           // Trades ativos + SCPs em captação
$aportes_concluidos = 0;       // SCPs que finalizaram captação mas não venceram
$rentabilidade_projetada = 0;  // Valorização dos trades ativos
$rentabilidade_consolidada = 0; // Vendas + dividendos recebidos
// ===== CONTADORES =====
$quantidade_trades_ativos = 0;
$quantidade_scp_captacao = 0;
$quantidade_scp_concluidos = 0;
$quantidade_vendidos = 0;

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
$rentabilidade_vendas = max(0, $vendas['total_rentabilidade']);

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
    'Jan' => 'Jan',
    'Feb' => 'Fev',
    'Mar' => 'Mar',
    'Apr' => 'Abr',
    'May' => 'Mai',
    'Jun' => 'Jun',
    'Jul' => 'Jul',
    'Aug' => 'Ago',
    'Sep' => 'Set',
    'Oct' => 'Out',
    'Nov' => 'Nov',
    'Dec' => 'Dez'
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
    $nome_investimento = $investment_id ? get_the_title($investment_id) : 'Investimento não identificado';
    
    // Verificar se é SCP
    $eh_scp = false;
    $status_captacao = 'ativo';
    $classe_ativo = '';
    
    if ($investment_id) {
        // Verificar tipo por função helper do tema
        $eh_scp = function_exists('s_invest_is_private_scp') ? s_invest_is_private_scp($investment_id) : false;
        
        // Fallback: verificar por taxonomia
        if (!$eh_scp) {
            $terms = wp_get_post_terms($investment_id, 'tipo_produto');
            foreach ($terms as $term) {
                if (stripos($term->name, 'scp') !== false || 
                    stripos($term->name, 'private') !== false ||
                    stripos($term->slug, 'scp') !== false ||
                    stripos($term->slug, 'private') !== false) {
                    $eh_scp = true;
                    $classe_ativo = $term->slug;
                    break;
                }
            }
        }
        
        // Status de captação para SCPs
        if ($eh_scp && function_exists('s_invest_calcular_status_captacao')) {
            $status_captacao = s_invest_calcular_status_captacao($investment_id);
        }
    }
    
    // Calcular valor total investido neste aporte
    $historico_aportes = get_field('historico_aportes', $aporte_id) ?: [];
    $valor_investido_aporte = 0;
    
    foreach ($historico_aportes as $item) {
        $valor_investido_aporte += floatval($item['valor_aporte'] ?? 0);
    }
    
    // Se não tem histórico, usar valor_compra ou valor_aportado
    if ($valor_investido_aporte == 0) {
        if ($eh_scp) {
            $valor_investido_aporte = floatval(get_field('valor_aportado', $aporte_id));
        } else {
            $valor_investido_aporte = floatval(get_field('valor_compra', $aporte_id));
        }
    }
    
    // ===== CLASSIFICAR APORTE PELOS 4 INDICADORES =====
    
    if ($venda_status) {
        // ===== VENDIDO: Vai para rentabilidade consolidada =====
        $quantidade_vendidos++;
        $valor_recebido = floatval(get_field('venda_valor', $aporte_id));
        $rentabilidade_consolidada += $valor_recebido;
        
        // Incluir venda no gráfico mensal de rentabilidade consolidada
        $data_venda = get_field('venda_data', $aporte_id) ?: get_field('data_venda', $aporte_id);
        if (!empty($data_venda) && $valor_recebido > 0) {
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
                    $rentabilidadeConsolidadaPorMesAno[$mesAno] += $valor_recebido;
                }
            }
        }
        
    } elseif ($eh_scp) {
        // ===== SCP: Verificar status de captação =====
        
        if (in_array($status_captacao, ['encerrado', 'encerrado_meta', 'encerrado_data', 'encerrado_manual'])) {
            // SCP CONCLUÍDO (finalizou captação, aguardando vencimento)
            $quantidade_scp_concluidos++;
            $aportes_concluidos += $valor_investido_aporte;
            
        } else {
            // SCP ATIVO (ainda em captação)
            $quantidade_scp_captacao++;
            $aportes_ativos += $valor_investido_aporte;
        }
        
        // Somar dividendos recebidos (para rentabilidade consolidada)
        $historico_dividendos = get_field('historico_dividendos', $aporte_id) ?: [];
        foreach ($historico_dividendos as $dividendo) {
            $rentabilidade_consolidada += floatval($dividendo['valor'] ?? 0);
        }
        
    } else {
        // ===== TRADE ATIVO =====
        $quantidade_trades_ativos++;
        $aportes_ativos += $valor_investido_aporte;
    }
    
    // ===== PROCESSAR DADOS PARA GRÁFICOS (mantém lógica atual) =====
    
    // Distribuição (apenas ativos e concluídos)
    if (!$venda_status && $investment_id && $valor_investido_aporte > 0) {
        $terms = wp_get_post_terms($investment_id, 'tipo_produto');
        if (!empty($terms) && !is_wp_error($terms)) {
            $categoria = $terms[0]->name;
            if (!isset($distribuicao[$categoria])) {
                $distribuicao[$categoria] = 0;
            }
            $distribuicao[$categoria] += $valor_investido_aporte;
        }
    }
    
    // Histórico para gráfico de barras
    foreach ($historico_aportes as $index => $item) {
        $valor_aporte_item = floatval($item['valor_aporte'] ?? 0);
        $data_aporte = $item['data_aporte'] ?? '';
        
        if ($valor_aporte_item > 0 && !empty($data_aporte)) {
            // Para o gráfico mensal
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
            
            // Para tabela de últimos movimentos
            $ultimos[] = [
                'data' => $data_aporte,
                'valor' => $valor_aporte_item,
                'investimento' => $nome_investimento . ($venda_status ? ' (Vendido)' : ''),
                'vendido' => $venda_status ? true : false
            ];
            
            // Para o extrato completo com filtros
            $situacao = 'ativo';
            if ($venda_status) {
                $situacao = 'vendido';
            } elseif ($eh_scp && in_array($status_captacao, ['encerrado', 'encerrado_meta', 'encerrado_data', 'encerrado_manual'])) {
                $situacao = 'encerrado';
            }
            
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
                'classe_ativo' => $classe_ativo,
                'eh_scp' => $eh_scp,
                'situacao' => $situacao
            ];
        }
    }
    
    // Processar dividendos
    $historico_dividendos = get_field('historico_dividendos', $aporte_id) ?: [];
    foreach ($historico_dividendos as $index => $dividendo) {
        $valor_dividendo = floatval($dividendo['valor'] ?? 0);
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
            
            // Para o extrato
            $situacao = 'ativo';
            if ($venda_status) {
                $situacao = 'vendido';
            } elseif ($eh_scp && in_array($status_captacao, ['encerrado', 'encerrado_meta', 'encerrado_data', 'encerrado_manual'])) {
                $situacao = 'encerrado';
            }
            
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
                'classe_ativo' => $classe_ativo,
                'eh_scp' => $eh_scp,
                'situacao' => $situacao
            ];
        }
    }
    
    // Processar histórico de rentabilidade (apenas trades ativos)
    if (!$venda_status && !$eh_scp) {
        $rentabilidade_hist = get_field('rentabilidade_historico', $aporte_id) ?: [];
        foreach ($rentabilidade_hist as $item) {
            if (isset($item['data_rentabilidade']) && isset($item['valor'])) {
                $data_rentabilidade = $item['data_rentabilidade'];
                $valor_rentabilidade = floatval($item['valor']);
                
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

// ===== ORGANIZAR DADOS PARA GRÁFICOS =====
$todosMesesAno = array_unique(array_merge(
    array_keys($investidoPorMesAno),
    array_keys($rentabilidadePorMesAno),
    array_keys($rentabilidadeConsolidadaPorMesAno)
));

usort($todosMesesAno, function ($a, $b) {
    $partes_a = explode(' ', $a);
    $partes_b = explode(' ', $b);
    
    if (count($partes_a) != 2 || count($partes_b) != 2) return 0;
    
    $ano_a = (int)$partes_a[1];
    $ano_b = (int)$partes_b[1];
    
    if ($ano_a != $ano_b) {
        return $ano_a - $ano_b;
    }
    
    $ordemMeses = [
        'Jan' => 1, 'Fev' => 2, 'Mar' => 3, 'Abr' => 4,
        'Mai' => 5, 'Jun' => 6, 'Jul' => 7, 'Ago' => 8,
        'Set' => 9, 'Out' => 10, 'Nov' => 11, 'Dez' => 12
    ];
    
    $mes_a = $ordemMeses[$partes_a[0]] ?? 0;
    $mes_b = $ordemMeses[$partes_b[0]] ?? 0;
    
    return $mes_a - $mes_b;
});

$chartLabels = [];
$chartInvestido = [];
$chartRentabilidade = [];
$chartRentabilidadeConsolidada = [];

// Fazer valor investido e rentabilidades serem acumulativos
$valorInvestidoAcumulado = 0;
$rentabilidadeAcumulada = 0;
$rentabilidadeConsolidadaAcumulada = 0;

foreach ($todosMesesAno as $mesAno) {
    $chartLabels[] = $mesAno;
    
    // Valor investido: acumular mês a mês
    $valorInvestidoAcumulado += $investidoPorMesAno[$mesAno] ?? 0;
    $chartInvestido[] = $valorInvestidoAcumulado;
    
    // Rentabilidade projetada: acumular mês a mês
    $rentabilidadeAcumulada += $rentabilidadePorMesAno[$mesAno] ?? 0;
    $chartRentabilidade[] = $rentabilidadeAcumulada;
    
    // Rentabilidade consolidada: acumular mês a mês
    $rentabilidadeConsolidadaAcumulada += $rentabilidadeConsolidadaPorMesAno[$mesAno] ?? 0;
    $chartRentabilidadeConsolidada[] = $rentabilidadeConsolidadaAcumulada;
}

// Ordenar movimentos
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

// ===== BUSCAR DADOS PARA FILTROS =====
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
                    <?php echo ($quantidade_trades_ativos + $quantidade_scp_captacao); ?> ativo<?php echo ($quantidade_trades_ativos + $quantidade_scp_captacao) != 1 ? 's' : ''; ?>
                </span>
            </div>

            <div class="flex items-center gap-2 mb-1">
                <h3 class="text-sm font-medium text-gray-600">Aportes Ativos</h3>
                <div class="relative" x-data="{ showTooltip: false }">
                    <button
                        @mouseenter="showTooltip = true"
                        @mouseleave="showTooltip = false"
                        class="w-4 h-4 bg-gray-100 hover:bg-gray-200 rounded-full flex items-center justify-center transition-colors">
                        <i class="fas fa-question text-xs text-gray-500"></i>
                    </button>
                    <div
                        x-show="showTooltip"
                        x-transition
                        class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-2 bg-gray-900 text-white text-xs rounded-lg shadow-lg z-10 whitespace-nowrap"
                        style="display: none;">
                        Trades ativos + SCPs ainda em captação
                        <div class="absolute top-full left-1/2 transform -translate-x-1/2 w-2 h-2 bg-gray-900 rotate-45"></div>
                    </div>
                </div>
            </div>

            <p class="text-2xl font-bold text-gray-900">
                R$ <?php echo number_format($aportes_ativos, 0, ',', '.'); ?>
            </p>
            
            <?php if ($quantidade_trades_ativos > 0 || $quantidade_scp_captacao > 0): ?>
                <p class="text-xs text-gray-500 mt-2">
                    <?php if ($quantidade_trades_ativos > 0): ?>
                        <?php echo $quantidade_trades_ativos; ?> Trade<?php echo $quantidade_trades_ativos != 1 ? 's' : ''; ?>
                    <?php endif; ?>
                    <?php if ($quantidade_trades_ativos > 0 && $quantidade_scp_captacao > 0): ?> • <?php endif; ?>
                    <?php if ($quantidade_scp_captacao > 0): ?>
                        <?php echo $quantidade_scp_captacao; ?> SCP<?php echo $quantidade_scp_captacao != 1 ? 's' : ''; ?>
                    <?php endif; ?>
                </p>
            <?php endif; ?>
        </div>

        <!-- Card 2: Aportes Concluídos -->
        <div class="bg-white rounded-2xl p-6 shadow-lg border border-gray-100 hover:shadow-xl transition-shadow duration-300">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-indigo-100 rounded-xl flex items-center justify-center">
                    <i class="fas fa-building text-xl text-indigo-600"></i>
                </div>
                <span class="text-xs font-medium px-2 py-1 bg-indigo-50 text-indigo-600 rounded-full">
                    <?php echo $quantidade_scp_concluidos; ?> concluído<?php echo $quantidade_scp_concluidos != 1 ? 's' : ''; ?>
                </span>
            </div>

            <div class="flex items-center gap-2 mb-1">
                <h3 class="text-sm font-medium text-gray-600">Aportes Concluídos</h3>
                <div class="relative" x-data="{ showTooltip: false }">
                    <button
                        @mouseenter="showTooltip = true"
                        @mouseleave="showTooltip = false"
                        class="w-4 h-4 bg-gray-100 hover:bg-gray-200 rounded-full flex items-center justify-center transition-colors">
                        <i class="fas fa-question text-xs text-gray-500"></i>
                    </button>
                    <div
                        x-show="showTooltip"
                        x-transition
                        class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-2 bg-gray-900 text-white text-xs rounded-lg shadow-lg z-10 whitespace-nowrap"
                        style="display: none;">
                        SCPs que finalizaram captação, aguardando vencimento
                        <div class="absolute top-full left-1/2 transform -translate-x-1/2 w-2 h-2 bg-gray-900 rotate-45"></div>
                    </div>
                </div>
            </div>

            <p class="text-2xl font-bold text-gray-900">
                R$ <?php echo number_format($aportes_concluidos, 0, ',', '.'); ?>
            </p>
        </div>

        <!-- Card 3: Rentabilidade Projetada (Trade) -->
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
                        class="w-4 h-4 bg-gray-100 hover:bg-gray-200 rounded-full flex items-center justify-center transition-colors">
                        <i class="fas fa-question text-xs text-gray-500"></i>
                    </button>
                    <div
                        x-show="showTooltip"
                        x-transition
                        class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-2 bg-gray-900 text-white text-xs rounded-lg shadow-lg z-10 whitespace-nowrap"
                        style="display: none;">
                        Valorização dos produtos Trade ativos
                        <div class="absolute top-full left-1/2 transform -translate-x-1/2 w-2 h-2 bg-gray-900 rotate-45"></div>
                    </div>
                </div>
            </div>

            <p class="text-2xl font-bold <?php echo $rentabilidade_projetada >= 0 ? 'text-green-600' : 'text-red-600'; ?>">
                <?php echo ($rentabilidade_projetada >= 0 ? '+' : ''); ?>R$ <?php echo number_format(abs($rentabilidade_projetada), 0, ',', '.'); ?>
            </p>
        </div>

        <!-- Card 4: Rentabilidade Consolidada -->
        <div class="bg-white rounded-2xl p-6 shadow-lg border border-gray-100 hover:shadow-xl transition-shadow duration-300">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-emerald-100 rounded-xl flex items-center justify-center">
                    <i class="fas fa-hand-holding-usd text-xl text-emerald-600"></i>
                </div>
                <span class="text-xs font-medium px-2 py-1 bg-emerald-50 text-emerald-600 rounded-full">
                    Realizada
                </span>
            </div>

            <div class="flex items-center gap-2 mb-1">
                <h3 class="text-sm font-medium text-gray-600">Rentabilidade Consolidada</h3>
                <div class="relative" x-data="{ showTooltip: false }">
                    <button
                        @mouseenter="showTooltip = true"
                        @mouseleave="showTooltip = false"
                        class="w-4 h-4 bg-gray-100 hover:bg-gray-200 rounded-full flex items-center justify-center transition-colors">
                        <i class="fas fa-question text-xs text-gray-500"></i>
                    </button>
                    <div
                        x-show="showTooltip"
                        x-transition
                        class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-2 bg-gray-900 text-white text-xs rounded-lg shadow-lg z-10 whitespace-nowrap"
                        style="display: none;">
                        Trade vendidos + recebiveis recebidos dos SCPs
                        <div class="absolute top-full left-1/2 transform -translate-x-1/2 w-2 h-2 bg-gray-900 rotate-45"></div>
                    </div>
                </div>
            </div>

            <p class="text-2xl font-bold text-emerald-600">
                R$ <?php echo number_format($rentabilidade_consolidada, 0, ',', '.'); ?>
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
                    Histórico de aportes e rentabilidade acumulada
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
                <div class="flex items-center justify-between p-4 border-b bg-gradient-to-r from-blue-600 to-blue-700 text-white">
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
                                        @change="filtros.periodo = ''; aplicarFiltros()"
                                        :max="new Date().toISOString().split('T')[0]"
                                        class="w-full text-sm border border-gray-300 rounded px-3 py-2 focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">Data Final</label>
                                    <input type="date"
                                        x-model="filtros.data_fim"
                                        @change="filtros.periodo = ''; aplicarFiltros()"
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
                    <p class="text-sm text-gray-600">Histórico completo de aportes e recebivies</p>
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
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"></th>
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
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <template x-if="movimento.investment_id">
                                            <a :href="`?secao=meus-investimentos&detalhe=${movimento.investment_id}`"
                                                class="inline-flex items-center px-3 py-1.5 bg-primary text-white text-xs font-medium rounded-lg hover:bg-slate-950 transition-colors">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </template>
                                        <template x-if="!movimento.investment_id">
                                            <span class="text-xs text-gray-400 italic">N/A</span>
                                        </template>
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
            tiposAtivo: <?php echo json_encode(array_map(function ($term) {
                            return ['slug' => $term->slug, 'name' => $term->name];
                        }, $tipos_produto_extrato)); ?>,

            investimentosDisponiveis: <?php echo json_encode(array_map(function ($inv) {
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
                                        font: {
                                            size: 12
                                        },
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
  if (window.performanceChartInstance) window.performanceChartInstance.destroy();

  // Dados (mesmos nomes que você já usa)
  const labels = chartData.chartLabels || [];
  const investido   = (chartData.chartInvestido || []).map(v => Number(v) || 0);                   // Azul
  const projetada   = (chartData.chartRentabilidade || []).map(v => Number(v) || 0);               // Verde
  const consolidada = (chartData.chartRentabilidadeConsolidada || []).map(v => Number(v) || 0);    // Laranja

  if (!labels.length) { labels.push('Sem dados'); investido.push(0); projetada.push(0); consolidada.push(0); }

  // Helpers
  const hexToRgb = (hex) => {
    let c = hex.replace('#','');
    if (c.length === 3) c = c.split('').map(s => s+s).join('');
    const num = parseInt(c, 16);
    return { r: (num >> 16) & 255, g: (num >> 8) & 255, b: num & 255 };
  };
  const rgba = (hex, a) => {
    const {r,g,b} = hexToRgb(hex);
    return `rgba(${r},${g},${b},${a})`;
  };
  const makeArea = (hex) => {
    const grad = ctx.createLinearGradient(0, 0, 0, canvas.height);
    grad.addColorStop(0, rgba(hex, .30));
    grad.addColorStop(1, rgba(hex, 0));
    return grad;
  };

  // Plugin: recalcula o gradiente a cada draw e destaca último ponto
  const perfLinePlugin = {
    id: 'perfLinePlugin',
    beforeDatasetsDraw() {},
    afterDatasetsDraw(chart) {
      const { ctx } = chart;
      chart.data.datasets.forEach((ds, i) => {
        if (!chart.isDatasetVisible(i)) return;
        const meta = chart.getDatasetMeta(i);
        if (!meta?.data?.length) return;
        const last = meta.data[meta.data.length - 1];
        ctx.save();
        ctx.beginPath();
        ctx.arc(last.x, last.y, 5, 0, Math.PI * 2);
        ctx.fillStyle = ds.borderColor;
        ctx.fill();
        ctx.lineWidth = 3;
        ctx.strokeStyle = rgba(ds.borderColor, .25);
        ctx.stroke();
        ctx.restore();
      });
    }
  };

  // Cores
  const BLUE = '#3B82F6';
  const GREEN = '#10B981';
  const AMBER = '#F59E0B';

  // Gráfico (3 datasets + área no verde por padrão)
  const chart = new Chart(ctx, {
    type: 'line',
    data: {
      labels,
      datasets: [
        {
          label: 'Valor Investido',
          data: investido,
          borderColor: BLUE,
          backgroundColor: makeArea(BLUE),
          borderWidth: 3,
          tension: .35,
          pointRadius: 3,
          pointHoverRadius: 6,
          fill: false // sem área para não poluir
        },
        {
          label: 'Rentabilidade Projetada',
          data: projetada,
          borderColor: GREEN,
          backgroundColor: makeArea(GREEN),
          borderWidth: 3,
          tension: .35,
          pointRadius: 3,
          pointHoverRadius: 6,
          fill: 'start' // área no verde (estilo do print)
        },
        {
          label: 'Rentabilidade Consolidada',
          data: consolidada,
          borderColor: AMBER,
          backgroundColor: makeArea(AMBER),
          borderWidth: 3,
          tension: .35,
          pointRadius: 3,
          pointHoverRadius: 6,
          fill: false
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      interaction: { mode: 'index', intersect: false },
      plugins: {
        legend: { display: true, position: 'top', labels: { usePointStyle: true, padding: 12, font: { size: 11 } } },
        tooltip: {
          backgroundColor: 'rgba(0,0,0,.8)',
          titleColor: '#fff',
          bodyColor: '#fff',
          borderColor: 'rgba(255,255,255,.1)',
          borderWidth: 1,
          callbacks: {
            label: (ctx) => ` ${ctx.dataset.label}: R$ ${(ctx.parsed.y || 0).toLocaleString('pt-BR')}`
          }
        }
      },
      scales: {
        x: {
          grid: { display: false },
          ticks: { color: '#6B7280', font: { size: window.innerWidth < 768 ? 10 : 11 } }
        },
        y: {
          beginAtZero: true,
          grid: { color: 'rgba(0,0,0,.05)' },
          ticks: {
            color: '#6B7280',
            font: { size: window.innerWidth < 768 ? 10 : 11 },
            callback: v => 'R$ ' + (v || 0).toLocaleString('pt-BR', { maximumFractionDigits: 0 })
          }
        }
      }
    },
    plugins: [perfLinePlugin]
  });

  window.performanceChartInstance = chart;

  // ====== Filtros (chips) com as mesmas cores ======
  const container = document.getElementById('performanceFilters');
  if (container) {
    container.innerHTML = ''; // evita duplicar
    const chips = [
      { idx: 0, label: 'Valor Investido', color: BLUE },
      { idx: 1, label: 'Rentabilidade Projetada', color: GREEN },
      { idx: 2, label: 'Rentabilidade Consolidada', color: AMBER }
    ];

    const setActiveStyle = (btn, color, active) => {
      btn.style.color = color;
      btn.style.borderColor = rgba(color, .5);
      btn.style.backgroundColor = active ? rgba(color, .12) : 'transparent';
      btn.style.opacity = active ? '1' : '.6';
    };

    chips.forEach(({ idx, label, color }) => {
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.textContent = label;
      btn.className = 'px-3 py-1.5 rounded-full border text-xs font-medium transition';
      setActiveStyle(btn, color, chart.isDatasetVisible(idx));

      btn.addEventListener('click', () => {
        const visible = chart.isDatasetVisible(idx);
        chart.setDatasetVisibility(idx, !visible);
        setActiveStyle(btn, color, !visible);
        chart.update();
      });

      container.appendChild(btn);
    });
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