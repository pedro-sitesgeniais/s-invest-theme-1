<?php
/**
 * Dashboard do Investidor - VERSÃO CORRIGIDA COM VALORES CORRETOS
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

// ===== CORREÇÃO 1: RENTABILIDADE PROJETADA = ÚLTIMO VALOR DO HISTÓRICO =====
$rentabilidade_projetada = max(0, $estatisticas['rentabilidade_projetada']);

// ===== CORREÇÃO 2: RENTABILIDADE CONSOLIDADA = VALOR TOTAL RECEBIDO DAS VENDAS =====
$vendas = $estatisticas['vendas'];
$total_investido_vendido = max(0, $vendas['total_compra']);      
$total_recebido_vendas = max(0, $vendas['total_venda']);         
$rentabilidade_consolidada = max(0, $vendas['total_rentabilidade']); // AGORA É O VALOR RECEBIDO

$total_investido_geral = $total_investido_ativo + $total_investido_vendido;

// ========== CORREÇÃO: DADOS DO GRÁFICO POR MÊS/ANO ==========
$distribuicao = [];
$ultimos = [];

// Arrays para organizar dados do gráfico por mês/ano
$investidoPorMesAno = [];
$rentabilidadePorMesAno = [];

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

if (!empty($aportes)) {
    foreach ($aportes as $aporte) {
        $aporte_id = $aporte->ID;
        $investment_id = get_field('investment_id', $aporte_id);
        $venda_status = get_field('venda_status', $aporte_id);
        
        // ========== PROCESSAR HISTÓRICO DE APORTES (VALOR INVESTIDO) ==========
        $historico_aportes = get_field('historico_aportes', $aporte_id) ?: [];
        $total_aporte_investido = 0;
        
        foreach ($historico_aportes as $item) {
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
                        // Agrupar valor investido por mês/ano
                        if (!isset($investidoPorMesAno[$mesAno])) {
                            $investidoPorMesAno[$mesAno] = 0;
                        }
                        $investidoPorMesAno[$mesAno] += $valor_aporte_item;
                    }
                }
                
                // Para a tabela de últimos movimentos
                $nome_investimento = $investment_id ? get_the_title($investment_id) : 'Investimento não identificado';
                $status_movimento = $venda_status ? ' (Vendido)' : '';
                $ultimos[] = [
                    'data' => $data_aporte,
                    'valor' => $valor_aporte_item,
                    'investimento' => $nome_investimento . $status_movimento,
                    'vendido' => $venda_status
                ];
            }
        }
        
        // ========== PROCESSAR HISTÓRICO DE RENTABILIDADE (APENAS APORTES ATIVOS) ==========
        if (!$venda_status) { // Só processar rentabilidade de aportes ativos
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
                                // Agrupar rentabilidade por mês/ano
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
        
        // ========== DISTRIBUIÇÃO POR CATEGORIA (APENAS APORTES ATIVOS) ==========
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
// Combinar todos os meses/anos que têm dados
$todosMesesAno = array_unique(array_merge(
    array_keys($investidoPorMesAno),
    array_keys($rentabilidadePorMesAno)
));

// Ordenar cronologicamente
usort($todosMesesAno, function($a, $b) {
    // Extrair mês e ano
    $partes_a = explode(' ', $a);
    $partes_b = explode(' ', $b);
    
    if (count($partes_a) != 2 || count($partes_b) != 2) return 0;
    
    $ano_a = (int)$partes_a[1];
    $ano_b = (int)$partes_b[1];
    
    if ($ano_a != $ano_b) {
        return $ano_a - $ano_b;
    }
    
    // Ordem dos meses
    $ordemMeses = ['Jan' => 1, 'Fev' => 2, 'Mar' => 3, 'Abr' => 4, 'Mai' => 5, 'Jun' => 6,
                   'Jul' => 7, 'Ago' => 8, 'Set' => 9, 'Out' => 10, 'Nov' => 11, 'Dez' => 12];
    
    $mes_a = $ordemMeses[$partes_a[0]] ?? 0;
    $mes_b = $ordemMeses[$partes_b[0]] ?? 0;
    
    return $mes_a - $mes_b;
});

// Preparar arrays finais para o gráfico
$chartLabels = [];
$chartInvestido = [];
$chartRentabilidade = [];

foreach ($todosMesesAno as $mesAno) {
    $chartLabels[] = $mesAno;
    $chartInvestido[] = $investidoPorMesAno[$mesAno] ?? 0;
    $chartRentabilidade[] = $rentabilidadePorMesAno[$mesAno] ?? 0;
}

// Ordenar últimos movimentos
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

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 lg:gap-6">
        
        <!-- Card 1: Aportes Ativos - LAYOUT ORIGINAL -->
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
                        Soma do histórico de aportes dos seus investimentos ativos.
                        <div class="absolute top-full left-1/2 transform -translate-x-1/2 w-2 h-2 bg-gray-900 rotate-45"></div>
                    </div>
                </div>
            </div>
            
            <!-- FORMATO ORIGINAL: R$ + VALOR JUNTO -->
            <p class="text-2xl font-bold text-gray-900">
                R$ <?php echo number_format($total_investido_ativo, 0, ',', '.'); ?>
            </p>
        </div>

        <!-- ===== CARD 2: RENTABILIDADE PROJETADA CORRIGIDA ===== -->
        <div class="bg-white rounded-2xl p-6 shadow-lg border border-gray-100 hover:shadow-xl transition-shadow duration-300">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center">
                    <i class="fa-solid fa-money-bill-trend-up text-xl text-purple-600"></i>
                </div>
                <span class="text-xs font-medium px-2 py-1 <?php echo $rentabilidade_projetada >= 0 ? 'bg-green-50 text-green-600' : 'bg-red-50 text-red-600'; ?> rounded-full">
                    <?php echo $rentabilidade_projetada >= 0 ? 'Lucro' : 'Prejuízo'; ?>
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
                        Último valor do histórico de rentabilidade dos seus aportes ativos.
                        <div class="absolute top-full left-1/2 transform -translate-x-1/2 w-2 h-2 bg-gray-900 rotate-45"></div>
                    </div>
                </div>
            </div>
            
            <!-- CORREÇÃO: MOSTRA O ÚLTIMO VALOR DO HISTÓRICO DE RENTABILIDADE -->
            <p class="text-2xl font-bold <?php echo $rentabilidade_projetada >= 0 ? 'text-green-600' : 'text-red-600'; ?>">
                <?php echo ($rentabilidade_projetada >= 0 ? '+' : ''); ?>R$ <?php echo number_format(abs($rentabilidade_projetada), 0, ',', '.'); ?>
            </p>
        </div>

        <!-- ===== CARD 3: RENTABILIDADE CONSOLIDADA CORRIGIDA ===== -->
        <div class="bg-white rounded-2xl p-6 shadow-lg border border-gray-100 hover:shadow-xl transition-shadow duration-300">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-orange-100 rounded-xl flex items-center justify-center">
                    <i class="fas fa-hand-holding-usd text-xl text-orange-600"></i>
                </div>
                <span class="text-xs font-medium px-2 py-1 bg-orange-50 text-orange-600 rounded-full">
                    <?php echo $aportes_vendidos; ?> vendido<?php echo $aportes_vendidos != 1 ? 's' : ''; ?>
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
                        Rentabilidade realizada em vendas já concretizadas.
                        <div class="absolute top-full left-1/2 transform -translate-x-1/2 w-2 h-2 bg-gray-900 rotate-45"></div>
                    </div>
                </div>
            </div>
            
            <!-- CORREÇÃO: AGORA MOSTRA O VALOR RECEBIDO DAS VENDAS (NÃO O LUCRO) -->
            <p class="text-2xl font-bold text-green-800">
                R$ <?php echo number_format($rentabilidade_consolidada, 0, ',', '.'); ?>
            </p>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        
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

        <div class="bg-white rounded-2xl p-6 shadow-lg border border-gray-100">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-semibold text-gray-900">Performance Mensal</h3>
            </div>
            
            <div class="relative" style="height: 280px;">
                <canvas id="performanceChart" class="max-w-full"></canvas>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900">Últimos Movimentos</h3>
                <a href="?secao=meus-investimentos" 
                   class="text-sm text-primary hover:text-slate-950 font-medium">
                    Ver todos
                </a>
            </div>
        </div>
        
        <div class="overflow-x-auto">
            <?php if (!empty($ultimos)): ?>
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Investimento</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Valor</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach (array_slice($ultimos, 0, 8) as $movimento): ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo esc_html($movimento['data']); ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    <div class="max-w-xs truncate">
                                        <?php echo esc_html(str_replace(' (Vendido)', '', $movimento['investimento'])); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    R$ <?php echo number_format($movimento['valor'], 2, ',', '.'); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if ($movimento['vendido']): ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                            <i class="fas fa-hand-holding-usd mr-1"></i>
                                            Vendido
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            <i class="fas fa-chart-line mr-1"></i>
                                            Ativo
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="px-6 py-12 text-center">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-inbox text-2xl text-gray-400"></i>
                    </div>
                    <h3 class="text-sm font-medium text-gray-900 mb-2">Nenhum movimento encontrado</h3>
                    <p class="text-sm text-gray-500 mb-4">
                        Seus movimentos aparecerão aqui conforme você fizer aportes.
                    </p>
                    <a href="?secao=produtos-gerais" 
                       class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary hover:bg-slate-950">
                        Fazer primeiro investimento
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if (!empty($distribuicao) || !empty($chartLabels)): ?>
<script>
window.dashboardChartData = {
    // Dados da distribuição (donut chart)
    distribuicaoData: <?php echo json_encode($distribuicao); ?>,
    
    // Dados do gráfico de barras - CORRIGIDOS
    chartLabels: <?php echo json_encode($chartLabels); ?>,
    chartInvestido: <?php echo json_encode($chartInvestido); ?>,
    chartRentabilidade: <?php echo json_encode($chartRentabilidade); ?>
};
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
        
        // DADOS CORRIGIDOS: Usar os arrays preparados no PHP
        const labels = chartData.chartLabels || [];
        const investidoData = chartData.chartInvestido || [];
        const rentabilidadeData = chartData.chartRentabilidade || [];
        
        // Se não há dados, mostrar exemplo vazio
        if (labels.length === 0) {
            labels.push('Sem dados');
            investidoData.push(0);
            rentabilidadeData.push(0);
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
                            backgroundColor: '#2ED2F8', // Azul
                            borderRadius: 6,
                            barThickness: window.innerWidth < 768 ? 15 : 20,
                            borderSkipped: false
                        },
                        {
                            label: 'Rentabilidade',
                            data: rentabilidadeData,
                            backgroundColor: '#10B981', // Verde
                            borderRadius: 6,
                            barThickness: window.innerWidth < 768 ? 15 : 20,
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
                                font: { size: 12 }
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
                                        const investido = tooltipItems[0].parsed.y || 0;
                                        const rentabilidade = tooltipItems[1].parsed.y || 0;
                                        const total = investido + rentabilidade;
                                        return `Total: R$ ${total.toLocaleString('pt-BR')}`;
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