<?php
/**
 * Seção Detalhes de Investimento - VERSÃO CORRIGIDA COM SISTEMA PRIVATE/SCP
 */
defined('ABSPATH') || exit;
// Verificações básicas
$inv_id = isset($_GET['id']) ? absint($_GET['id']) : 0;
if (!$inv_id || !get_post($inv_id)) {
    echo '<div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6">
            <p class="text-sm text-red-700">Investimento não encontrado ou inválido.</p>
          </div>';
    return;
}

$user_id = get_current_user_id();
if (!$user_id) {
    echo '<div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6">
            <p class="text-sm text-red-700">Usuário não autenticado.</p>
          </div>';
    return;
}

// Buscar TODOS os aportes do usuário para este investimento
$args_aporte = [
    'post_type'      => 'aporte',
    'posts_per_page' => -1, // ✅ CORRIGIDO: Buscar TODOS os aportes
    'post_status'    => 'publish',
    'meta_query'     => [
        ['key' => 'investment_id', 'value' => $inv_id],
        ['key' => 'investidor_id', 'value' => $user_id],
    ],
    'orderby'        => 'post_date',
    'order'          => 'DESC',
];

$aporte_posts = get_posts($args_aporte);

if (empty($aporte_posts)) {
    echo '<div class="bg-yellow-50 border-l-4 border-yellow-500 p-4 mb-6">
            <p class="text-sm text-yellow-700">Você não possui aportes neste investimento.</p>
          </div>';
    return;
}

// ========== VERIFICAÇÃO DE TIPO DEVE VIR PRIMEIRO ==========
$is_private = false;
$product_type_label = 'TRADE';
$product_type_class = 'bg-blue-500/20 text-blue-400 border-blue-500/30';

if (function_exists('s_invest_is_private_scp')) {
    $is_private = s_invest_is_private_scp($inv_id);
}

if (function_exists('s_invest_get_product_type_label')) {
    $product_type_label = s_invest_get_product_type_label($inv_id);
}

if (function_exists('s_invest_get_product_type_class')) {
    $product_type_class = s_invest_get_product_type_class($inv_id);
}

// ===== PROCESSAR TODOS OS APORTES DO USUÁRIO =====
$valor_investido_total = 0;
$valor_atual_total = 0;
$valor_compra_total = 0;
$rentabilidade_projetada_total = 0;
$venda_status_geral = false;
$historico_aportes_consolidado = [];
$historico_rentabilidade_consolidado = [];
$historico_dividendos_consolidado = [];
$total_dividendos_recebidos = 0;

// Dados do primeiro aporte para campos não-financeiros
$aporte_principal = $aporte_posts[0];
$whatsapp_assessor = get_field('whatsapp_assessor', $aporte_principal->ID) ?: '';
$nome_assessor = get_field('nome_assessor', $aporte_principal->ID) ?: 'Assessor';
$foto_assessor = get_field('foto_assessor', $aporte_principal->ID);
$contrato = get_field('contrato_pdf', $aporte_principal->ID);

$valor_compra = floatval(get_field('valor_compra', $aporte_principal->ID) ?: 0);
$valor_atual = floatval(get_field('valor_atual', $aporte_principal->ID) ?: 0);

foreach ($aporte_posts as $aporte_post) {
    $aporte_id = $aporte_post->ID;
    $venda_status_item = get_field('venda_status', $aporte_id);
    
    if ($venda_status_item) {
        $venda_status_geral = true;
    }
    
    // Somar histórico de aportes (para valor investido)
    $historico_aportes = get_field('historico_aportes', $aporte_id) ?: [];
    foreach ($historico_aportes as $item) {
        $valor_investido_total += floatval($item['valor_aporte'] ?? 0);
        $historico_aportes_consolidado[] = $item;
    }
    
    // Consolidar histórico de rentabilidade para o gráfico
    $rentabilidade_hist_item = get_field('rentabilidade_historico', $aporte_id) ?: [];
    foreach ($rentabilidade_hist_item as $item) {
        $data_key = $item['data_rentabilidade'] ?? '';
        if ($data_key) {
            if (!isset($historico_rentabilidade_consolidado[$data_key])) {
                $historico_rentabilidade_consolidado[$data_key] = [
                    'data_rentabilidade' => $data_key,
                    'valor' => 0
                ];
            }
            $historico_rentabilidade_consolidado[$data_key]['valor'] += floatval($item['valor'] ?? 0);
        }
    }
    
    // Consolidar dividendos (para produtos Private)
    if ($is_private) {
        $historico_dividendos = get_field('historico_dividendos', $aporte_id) ?: [];
        foreach ($historico_dividendos as $dividendo) {
            $historico_dividendos_consolidado[] = $dividendo;
            $total_dividendos_recebidos += floatval($dividendo['valor'] ?? 0);
        }
    }
}

// Converter array associativo em indexado para o gráfico
$rentabilidade_hist = array_values($historico_rentabilidade_consolidado);

// Ordenar por data
usort($rentabilidade_hist, function($a, $b) {
    $dateA = DateTime::createFromFormat('d/m/Y', $a['data_rentabilidade']);
    $dateB = DateTime::createFromFormat('d/m/Y', $b['data_rentabilidade']);
    if (!$dateA || !$dateB) return 0;
    return $dateA->getTimestamp() - $dateB->getTimestamp();
});

// Calcular rentabilidade projetada (último valor do histórico consolidado)
if (!empty($rentabilidade_hist)) {
    $ultimo_valor = end($rentabilidade_hist);
    $rentabilidade_projetada_total = floatval($ultimo_valor['valor'] ?? 0);
}

// Dados de venda consolidados
$venda_data = '';
$venda_valor_total = 0;
$venda_rentabilidade_total = 0;
$venda_observacoes = '';
$venda_documento = null;

if ($venda_status_geral) {
    foreach ($aporte_posts as $aporte_post) {
        $aporte_id = $aporte_post->ID;
        if (get_field('venda_status', $aporte_id)) {
            $venda_valor_total += floatval(get_field('venda_valor', $aporte_id) ?: 0);
            if (!$venda_data) {
                $venda_data = get_field('venda_data', $aporte_id) ?: '';
                $venda_observacoes = get_field('venda_observacoes', $aporte_id) ?: '';
                $venda_documento = get_field('venda_documento', $aporte_id);
            }
        }
    }
    
    if ($valor_investido_total > 0) {
        $venda_rentabilidade_total = (($venda_valor_total / $valor_investido_total) - 1) * 100;
    }
}

// Atualizar variáveis para compatibilidade com o resto do código
// $valor_compra = $valor_investido_total;
// $valor_atual = $valor_atual_total;
$venda_status = $venda_status_geral;
$venda_valor = $venda_valor_total;
$venda_rentabilidade = $venda_rentabilidade_total;
$rentabilidade_projetada = $rentabilidade_projetada_total;
$historico_aportes = $historico_aportes_consolidado;
$historico_dividendos = $historico_dividendos_consolidado;

// Calcular rentabilidade percentual
$rentabilidade_pct = $valor_investido_total > 0 ? ($rentabilidade_projetada / $valor_investido_total) * 100 : 0;

// Dados específicos para dividendos
$ultimo_dividendo = null;
$proximo_dividendo = null;

if ($is_private && !empty($historico_dividendos)) {
    foreach ($historico_dividendos as $dividendo) {
        $data_dividendo = $dividendo['data_dividendo'] ?? $dividendo['data'] ?? '';
        if ($data_dividendo && (!$ultimo_dividendo || 
            strtotime($data_dividendo) > strtotime($ultimo_dividendo['data']))) {
            $ultimo_dividendo = [
                'valor' => floatval($dividendo['valor'] ?? 0),
                'data' => $data_dividendo
            ];
        }
    }
    
    // Próximo dividendo do primeiro aporte
    $proximo_dividendo_data = get_field('proximo_dividendo_data', $aporte_principal->ID) ?: '';
    $proximo_dividendo_valor = floatval(get_field('proximo_dividendo_valor', $aporte_principal->ID) ?: 0);
    
    if ($proximo_dividendo_data) {
        $proximo_dividendo = [
            'data' => $proximo_dividendo_data,
            'valor' => $proximo_dividendo_valor
        ];
    }
}

// ========== DADOS BÁSICOS DO INVESTIMENTO ==========
$titulo = esc_html(get_the_title($inv_id));
$localizacao = get_field('localizacao', $inv_id) ?: '';
$lamina_tecnica = get_field('url_lamina_tecnica', $inv_id) ?: '';
$link_produto = get_permalink($inv_id);

// ========== LÓGICA DE VENDA PARA PRODUTOS TRADE ==========
$pode_vender = false;
$data_liberacao = 'Data indisponível';

if (!$is_private && !$venda_status && !empty($historico_aportes)) {
    $primeiro_aporte = reset($historico_aportes);
    $data_inicio_raw = $primeiro_aporte['data_aporte'] ?? '';
    
    if ($data_inicio_raw) {
        try {
            $data_investimento = DateTime::createFromFormat('d/m/Y', $data_inicio_raw);
            
            if ($data_investimento) {
                $prazo_investimento = get_field('prazo_do_investimento', $inv_id);
                $periodo_minimo = isset($prazo_investimento['prazo_min']) ? intval($prazo_investimento['prazo_min']) : 12;

                $data_liberacao_obj = clone $data_investimento;
                $data_liberacao_obj->modify("+{$periodo_minimo} months");
                $data_liberacao = $data_liberacao_obj->format('d/m/Y');

                $hoje = new DateTime();
                $pode_vender = $hoje >= $data_liberacao_obj;
            }
        } catch (Exception $e) {
            // Manter valores padrão em caso de erro
        }
    }
}

// ========== STATUS DO INVESTIMENTO ==========
$status = 'Status indisponível';
if (function_exists('s_invest_get_status_captacao_info')) {
    $status_info = s_invest_get_status_captacao_info($inv_id);
    $status = $status_info['label'] ?? 'Status indisponível';
} elseif (function_exists('icf_get_investment_status')) {
    $status = icf_get_investment_status($inv_id);
}

// ========== DOCUMENTOS ==========
$docs = get_field('documentos', $inv_id) ?: [];
?>

<div class="bg-primary text-white p-6 md:p-10 rounded-xl max-w-screen-xl main-content-mobile mt-5 mb-20 md:mb-5 min-h-screen mx-auto">
    <!-- CABEÇALHO -->
    <div class="mb-8 md:mb-10 pb-4 md:pb-6 border-b border-white/15">
        <div class="flex items-start justify-between">
            <div class="flex-1">
                <h1 class="text-2xl md:text-4xl font-semibold mb-2 md:mb-3 tracking-tighter"><?php echo $titulo; ?></h1>
                <?php if ($localizacao) : ?>
                    <div class="text-slate-400 text-lg mb-6"><?php echo esc_html($localizacao); ?></div>
                <?php endif; ?>
            </div>
            
            <!-- BADGES -->
            <div class="ml-4 text-center">
                <div class="flex flex-col gap-2">
                    <!-- Badge de Status (Vendido/Ativo) -->
                    <?php if ($venda_status) : ?>
                        <div class="inline-block px-4 py-2 rounded-full text-sm font-bold bg-red-500/20 text-red-400 border border-red-500/30">
                            <i class="fas fa-hand-holding-usd mr-2"></i>
                            VENDIDO
                        </div>
                        <?php if ($venda_data) : ?>
                            <div class="text-slate-400 text-xs">em <?php echo esc_html($venda_data); ?></div>
                        <?php endif; ?>
                    <?php else : ?>
                        <div class="inline-block px-4 py-2 rounded-full text-sm font-medium bg-green-500/20 text-green-400 border border-green-500/30">
                            <i class="fas fa-chart-line mr-2"></i>
                            ATIVO
                        </div>
                    <?php endif; ?>
                    
                    <!-- Badge do Tipo de Produto -->
                    <div class="inline-block px-4 py-2 rounded-full text-sm font-bold <?php echo esc_attr($product_type_class); ?> border">
                        <i class="fas <?php echo $is_private ? 'fa-building' : 'fa-chart-bar'; ?> mr-2"></i>
                        <?php echo esc_html($product_type_label); ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- CARDS DE VALORES -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 md:gap-4 mb-8 md:mb-10 px-2 md:px-0">
            <!-- Card 1: Valor Investido (sempre presente) -->
            <div class="bg-white/8 p-3 md:p-4 lg:p-5 rounded-lg border border-white/10 text-center">
                <div class="text-slate-400 text-xs md:text-sm mb-1 md:mb-2">Valor Investido</div>
                <div class="text-lg md:text-xl lg:text-2xl font-semibold text-blue-400">R$ <?php echo number_format($valor_investido_total, 2, ',', '.'); ?></div>
            </div>
            
            <?php if ($is_private) : ?>
                <!-- PRODUTOS PRIVATE/SCP - Cards específicos -->
                
                <!-- Card 2: Total de Dividendos Recebidos -->
                <div class="bg-white/8 p-3 md:p-4 lg:p-5 rounded-lg border border-white/10 text-center">
                    <div class="text-slate-400 text-xs md:text-sm mb-1 md:mb-2">Dividendos Recebidos</div>
                    <div class="text-lg md:text-xl lg:text-2xl font-semibold text-green-400">R$ <?php echo number_format($total_dividendos_recebidos, 2, ',', '.'); ?></div>
                    <?php if ($ultimo_dividendo) : ?>
                        <div class="text-xs text-slate-500 mt-1">Último: <?php echo esc_html($ultimo_dividendo['data']); ?></div>
                    <?php endif; ?>
                </div>
                
                <!-- Card 3: Rentabilidade Consolidada (Yield) -->
                <div class="bg-white/8 p-3 md:p-4 lg:p-5 rounded-lg border border-white/10 text-center">
                    <div class="text-slate-400 text-xs md:text-sm mb-1 md:mb-2">Rentabilidade Consolidada</div>
                    <?php 
                    $yield_percentual = $valor_investido_total > 0 ? ($total_dividendos_recebidos / $valor_investido_total) * 100 : 0;
                    ?>
                    <div class="text-lg md:text-xl lg:text-2xl font-semibold text-purple-400"><?php echo number_format($yield_percentual, 1, ',', '.'); ?>%</div>
                    <div class="text-xs text-slate-500 mt-1">Yield acumulado</div>
                </div>
                
                <!-- Card 4: Próximo Dividendo -->
                <div class="bg-white/8 p-3 md:p-4 lg:p-5 rounded-lg border border-white/10 text-center">
                    <div class="text-slate-400 text-xs md:text-sm mb-1 md:mb-2">Próximo Dividendo</div>
                    <?php if ($proximo_dividendo && $proximo_dividendo['valor'] > 0) : ?>
                        <div class="text-lg md:text-xl lg:text-2xl font-semibold text-yellow-400">R$ <?php echo number_format($proximo_dividendo['valor'], 2, ',', '.'); ?></div>
                        <div class="text-xs text-slate-500 mt-1"><?php echo esc_html($proximo_dividendo['data']); ?></div>
                    <?php else : ?>
                        <div class="text-lg md:text-xl lg:text-2xl font-semibold text-slate-500">A definir</div>
                        <div class="text-xs text-slate-500 mt-1">Aguardando</div>
                    <?php endif; ?>
                </div>
                
            <?php else : ?>
                <!-- PRODUTOS TRADE - Cards tradicionais -->
                
                <!-- Card 2: Valor de Compra -->
                <div class="bg-white/8 p-3 md:p-4 lg:p-5 rounded-lg border border-white/10 text-center">
                    <div class="text-slate-400 text-xs md:text-sm mb-1 md:mb-2">Valor de Compra</div>
                    <div class="text-lg md:text-xl lg:text-2xl font-semibold">R$ <?php echo number_format($valor_compra, 2, ',', '.'); ?></div>
                </div>
                
                <?php if ($venda_status) : ?>
                    <!-- Produto TRADE vendido -->
                    <div class="bg-white/8 p-3 md:p-4 lg:p-5 rounded-lg border border-white/10 text-center">
                        <div class="text-slate-400 text-xs md:text-sm mb-1 md:mb-2">Valor na Venda</div>
                        <div class="text-lg md:text-xl lg:text-2xl font-semibold text-accent">R$ <?php echo number_format($valor_atual, 2, ',', '.'); ?></div>
                    </div>
                    
                    <div class="bg-white/8 p-3 md:p-4 lg:p-5 rounded-lg border border-white/10 text-center">
                        <div class="text-slate-400 text-xs md:text-sm mb-1 md:mb-2">Rentabilidade Final</div>
                        <div class="text-lg md:text-xl lg:text-2xl font-semibold text-green-400">R$ <?php echo number_format($venda_valor, 2, ',', '.'); ?></div>
                        <div class="text-xs <?php echo $venda_rentabilidade >= 0 ? 'text-green-300' : 'text-red-300'; ?> mt-1">
                            (<?php echo ($venda_rentabilidade >= 0 ? '+' : ''); ?><?php echo number_format($venda_rentabilidade, 1, ',', '.'); ?>%)
                        </div>
                    </div>
                <?php else : ?>
                    <!-- Produto TRADE ativo -->
                    <div class="bg-white/8 p-3 md:p-4 lg:p-5 rounded-lg border border-white/10 text-center">
                        <div class="text-slate-400 text-xs md:text-sm mb-1 md:mb-2">Valor Atual</div>
                        <div class="text-lg md:text-xl lg:text-2xl font-semibold">R$ <?php echo number_format($valor_atual, 2, ',', '.'); ?></div>
                    </div>
                    
                    <div class="bg-white/8 p-3 md:p-4 lg:p-5 rounded-lg border border-white/10 text-center">
                        <div class="text-slate-400 text-xs md:text-sm mb-1 md:mb-2">Rentabilidade Projetada</div>
                        <div class="text-lg md:text-xl lg:text-2xl font-bold text-green-400">+R$ <?php echo number_format($rentabilidade_projetada, 2, ',', '.'); ?></div>
                        <div class="text-xs <?php echo $rentabilidade_pct >= 0 ? 'text-green-300' : 'text-red-300'; ?> mt-1">
                            (<?php echo ($rentabilidade_pct >= 0 ? '+' : ''); ?><?php echo number_format($rentabilidade_pct, 1, ',', '.'); ?>%)
                        </div>
                    </div>
                <?php endif; ?>
                
            <?php endif; ?>
        </div>
        
        <!-- STATUS -->
        <div class="inline-block px-3 py-1 rounded-full text-sm font-medium bg-green-500/10 text-green-400 mt-4">
            <?php echo esc_html($status); ?>
        </div>
    </div>

    <!-- GRÁFICO -->
    <?php if (!empty($rentabilidade_hist) && is_array($rentabilidade_hist) && count($rentabilidade_hist) > 0) : ?>
        <div class="h-[300px] sm:h-[350px] md:h-[400px] my-6 md:my-12">
            <canvas id="investmentChart"></canvas>
        </div>
    <?php endif; ?>

    <!-- INFORMAÇÕES DA VENDA -->
    <?php if ($venda_status && $venda_observacoes) : ?>
        <div class="my-6 md:my-8 p-4 md:p-6 bg-white/5 rounded-xl border border-white/10">
            <h3 class="text-lg font-semibold mb-3 text-slate-300">Informações da Venda</h3>
            <p class="text-slate-400 text-sm md:text-base"><?php echo esc_html($venda_observacoes); ?></p>
        </div>
    <?php endif; ?>

    <!-- BOTÕES DE AÇÃO -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2 md:gap-3 my-6 md:my-12">
        <!-- Contrato -->
        <?php if ($contrato && isset($contrato['url'])) : ?>
            <a href="<?php echo esc_url($contrato['url']); ?>" 
               class="flex items-center justify-center gap-2 md:gap-3 p-3 md:p-4 text-sm md:text-base rounded-xl bg-blue-900 border border-blue-800 hover:bg-blue-600 transition-colors"
               target="_blank" 
               rel="noopener noreferrer">
                <i class="fas fa-file-contract text-lg"></i>
                Visualizar Contrato
            </a>
        <?php endif; ?>
        
        <!-- Documento da Venda -->
        <?php if ($venda_documento && isset($venda_documento['url'])) : ?>
            <a href="<?php echo esc_url($venda_documento['url']); ?>" 
               class="flex items-center justify-center gap-2 md:gap-3 p-3 md:p-4 text-sm md:text-base rounded-xl bg-purple-900 border border-purple-800 hover:bg-purple-600 transition-colors"
               target="_blank" 
               rel="noopener noreferrer">
                <i class="fas fa-file-invoice text-lg"></i>
                Documento da Venda
            </a>
        <?php endif; ?>
        
        <!-- Lâmina Técnica -->
        <?php if ($lamina_tecnica) : ?>
            <a href="<?php echo esc_url($lamina_tecnica); ?>" 
               class="flex items-center justify-center gap-2 md:gap-3 p-3 md:p-4 text-sm md:text-base rounded-xl bg-blue-900 border border-blue-800 hover:bg-blue-600 transition-colors"
               target="_blank" 
               rel="noopener noreferrer">
                <i class="fas fa-file-invoice-dollar text-lg"></i>
                Lâmina Técnica
            </a>
        <?php endif; ?>
        
        <!-- Sobre o Investimento -->
        <a href="<?php echo esc_url($link_produto); ?>" 
           class="flex items-center justify-center gap-2 md:gap-3 p-3 md:p-4 text-sm md:text-base rounded-xl bg-blue-900 border border-blue-800 hover:bg-blue-600 transition-colors"
           target="_blank" 
           rel="noopener noreferrer">
            <i class="fa-regular fa-circle-question text-lg"></i>
            Sobre o Investimento
        </a>

        <!-- BOTÃO DE VENDA OU STATUS -->
        <?php if (!$venda_status) : ?>
            <?php if ($is_private) : ?>
                <!-- PRODUTOS PRIVATE: Mostrar status em vez de botão de venda -->
                <div class="flex items-center justify-center gap-2 md:gap-3 p-3 md:p-4 text-sm md:text-base rounded-xl bg-purple-600 border border-purple-500 opacity-75">
                    <i class="fas fa-coins text-yellow-400"></i>
                    Gerando Dividendos
                </div>
            <?php else : ?>
                <!-- PRODUTOS TRADE: Lógica de venda -->
                <?php if ($whatsapp_assessor) : ?>
                    <?php
                    $whatsapp_url = "https://wa.me/".preg_replace('/\D/', '', $whatsapp_assessor)."?text=".rawurlencode("Olá ".$nome_assessor.", gostaria de sacar meu investimento: ".$titulo);
                    $tooltip_text = $pode_vender ? 'Saque disponível!' : "Disponível a partir de {$data_liberacao}";
                    $botao_classes = $pode_vender ? 'bg-green-600 hover:bg-green-700' : 'bg-gradient-to-br from-red-500 to-pink-600 hover:from-red-600 hover:to-pink-700 opacity-60 cursor-not-allowed';
                    ?>
                    
                    <div class="relative group">
                        <?php if ($pode_vender) : ?>
                            <a href="<?php echo esc_url($whatsapp_url); ?>" 
                               class="flex items-center justify-center gap-2 md:gap-3 p-3 md:p-4 text-sm md:text-base rounded-xl <?php echo $botao_classes; ?> transition-colors"
                               title="<?php echo esc_attr($tooltip_text); ?>">
                                <i class="fas fa-hand-holding-usd"></i>
                                Vender Meu Ativo
                            </a>
                        <?php else : ?>
                            <button class="flex items-center justify-center gap-2 md:gap-3 p-3 md:p-4 text-sm md:text-base rounded-xl <?php echo $botao_classes; ?> w-full"
                                    title="<?php echo esc_attr($tooltip_text); ?>"
                                    disabled>
                                <i class="fas fa-hand-holding-usd"></i>
                                Vender Meu Ativo
                            </button>
                        <?php endif; ?>
                        
                        <div class="absolute bottom-full mb-2 left-1/2 -translate-x-1/2 hidden group-hover:block bg-gray-800 text-white text-xs px-3 py-2 rounded-lg shadow-lg">
                            <?php echo esc_html($tooltip_text); ?>
                        </div>
                    </div>
                <?php else : ?>
                    <div class="flex items-center justify-center gap-2 md:gap-3 p-3 md:p-4 text-sm md:text-base rounded-xl bg-gray-600 border border-gray-500 opacity-75">
                        <i class="fas fa-exclamation-triangle"></i>
                        Assessor não disponível
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        <?php else : ?>
            <div class="flex items-center justify-center gap-2 md:gap-3 p-3 md:p-4 text-sm md:text-base rounded-xl bg-gray-700 border border-gray-600 opacity-75">
                <i class="fas fa-check-circle text-green-400"></i>
                Investimento <?php echo $is_private ? 'Finalizado' : 'Vendido'; ?>
            </div>
        <?php endif; ?>

        <!-- ASSESSOR -->
        <div class="sm:col-span-2 lg:col-span-4 bg-white/10 p-4 md:p-6 rounded-xl flex flex-col md:flex-row items-center gap-4 text-center md:text-left">
            <?php if ($foto_assessor && isset($foto_assessor['url'])) : ?>
                <img src="<?php echo esc_url($foto_assessor['url']); ?>" 
                     class="w-12 h-12 md:w-16 md:h-16 rounded-full object-cover">
            <?php endif; ?>
            <div class="flex-1">
                <div class="text-slate-400 text-xs md:text-sm">Seu Assessor</div>
                <div class="text-lg md:text-xl font-semibold mb-1 md:mb-2"><?php echo esc_html($nome_assessor); ?></div>
                <?php if ($whatsapp_assessor) : ?>
                    <a href="https://wa.me/<?php echo preg_replace('/\D/', '', $whatsapp_assessor); ?>?text=<?php echo rawurlencode('Olá '.$nome_assessor.', gostaria de falar sobre '.$titulo); ?>" 
                       class="inline-flex items-center gap-1 md:gap-2 px-3 md:px-4 py-1 md:py-2 text-xs md:text-sm rounded-lg bg-blue-900 hover:bg-blue-600 transition-colors">
                        <i class="fab fa-whatsapp"></i>
                        Falar Agora
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- HISTÓRICO E DOCUMENTOS -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 md:gap-8">
        <!-- HISTÓRICO -->
        <div class="px-2 md:px-0">
            <h3 class="text-slate-400 text-base md:text-lg mb-2 md:mb-4">
                <?php echo $is_private ? 'Histórico de Dividendos' : 'Histórico de Aportes'; ?>
            </h3>

            <?php if ($is_private) : ?>
                <!-- PRODUTOS PRIVATE: Mostrar dividendos -->
                <?php if (!empty($historico_dividendos) && is_array($historico_dividendos)) : ?>
                    <div class="space-y-2 md:space-y-4">
                        <?php foreach ($historico_dividendos as $dividendo) : ?>
                            <div class="flex justify-between items-center py-1 md:py-2 border-b border-white/10">
                                <div>
                                    <span class="text-xs md:text-sm"><?php echo esc_html($dividendo['data_dividendo'] ?? $dividendo['data'] ?? ''); ?></span>
                                    <?php if (!empty($dividendo['descricao'])) : ?>
                                        <div class="text-xs text-slate-500"><?php echo esc_html($dividendo['descricao']); ?></div>
                                    <?php endif; ?>
                                </div>
                                <span class="text-green-400 text-xs md:text-sm font-semibold">
                                    +R$ <?php echo number_format(floatval($dividendo['valor'] ?? 0), 2, ',', '.'); ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                        
                        <!-- Total -->
                        <div class="flex justify-between items-center py-2 border-t border-white/20 font-semibold">
                            <span class="text-sm">Total Recebido:</span>
                            <span class="text-green-400 text-sm">R$ <?php echo number_format($total_dividendos_recebidos, 2, ',', '.'); ?></span>
                        </div>
                    </div>
                <?php else : ?>
                    <p class="text-slate-400">Nenhum dividendo recebido ainda</p>
                <?php endif; ?>
                
            <?php else : ?>
                <!-- PRODUTOS TRADE: Mostrar aportes -->
                <?php if (!empty($historico_aportes) && is_array($historico_aportes)) : ?>
                    <div class="space-y-2 md:space-y-4">
                        <?php foreach ($historico_aportes as $ap) : ?>
                            <div class="flex justify-between items-center py-1 md:py-2 border-b border-white/10">
                                <span class="text-xs md:text-sm"><?php echo esc_html($ap['data_aporte'] ?? ''); ?></span>
                                <span class="text-slate-400 text-xs md:text-sm">
                                    R$ <?php echo number_format(floatval($ap['valor_aporte'] ?? 0), 2, ',', '.'); ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else : ?>
                    <p class="text-slate-400">Nenhum aporte registrado</p>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <!-- DOCUMENTOS -->
        <!-- <div class="px-2 md:px-0">
            <h3 class="text-slate-400 text-base md:text-lg mb-2 md:mb-4">Documentos</h3>
            <?php if (!empty($docs) && is_array($docs)) : ?>
                <div class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-3 gap-3 md:gap-6">
                    <?php 
                    // Inicializar sistema de URLs privadas
                    $private_urls = null;
                        if (class_exists('SIP_Private_URLs')) {
                            $private_urls = new SIP_Private_URLs();
                        }
                    
                    foreach ($docs as $doc) : 
                        $titulo_doc = esc_html($doc['title'] ?? 'Documento');
                        $url_doc = '';
                        
                        // Usar URLs privadas se disponível
                        if (isset($doc['url']['ID']) && $private_urls !== null) {
                            $url_doc = $private_urls->generate_private_url($doc['url']['ID'], $inv_id);
                        } elseif (isset($doc['url']['url'])) {
                            $url_doc = esc_url($doc['url']['url']);
                        } elseif (isset($doc['url']) && is_string($doc['url'])) {
                            $url_doc = esc_url($doc['url']);
                        }
                        
                        if ($url_doc) :
                    ?>
                        <a href="<?php echo $url_doc; ?>" 
                        class="group relative text-center"
                        target="_blank"
                        rel="noopener noreferrer">
                            <div class="w-16 h-16 md:w-20 md:h-20 bg-blue-900/30 rounded-xl flex items-center justify-center mx-auto mb-2 transition-colors group-hover:bg-blue-900/50">
                                <i class="fas fa-file-pdf text-2xl md:text-3xl"></i>
                                <?php if (class_exists('SIP_Private_URLs')) : ?>
                                    <div class="absolute -top-1 -right-1 w-4 h-4 bg-yellow-500 rounded-full flex items-center justify-center">
                                        <i class="fas fa-lock text-xs text-white"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <span class="text-slate-400 text-xs md:text-sm truncate px-2 block" title="<?php echo $titulo_doc; ?>">
                                <?php echo $titulo_doc; ?>
                            </span>
                        </a>
                    <?php 
                        endif;
                    endforeach; ?>
                </div>
            <?php else : ?>
                <p class="text-slate-400">Nenhum documento disponível</p>
            <?php endif; ?>
        </div> -->
    </div>
</div>

<!-- SCRIPT DO GRÁFICO -->
<?php if (!empty($rentabilidade_hist) && is_array($rentabilidade_hist) && count($rentabilidade_hist) > 0) : ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Aguardar Chart.js estar disponível
    function initChart() {
        if (typeof Chart === 'undefined') {
            setTimeout(initChart, 200);
            return;
        }
        
        const canvas = document.getElementById('investmentChart');
        if (!canvas) return;
        
        const ctx = canvas.getContext('2d');
        const historico = <?php echo json_encode($rentabilidade_hist); ?>;

        if (historico && historico.length > 0) {
            console.log('Dados do gráfico:', historico); // Debug
            try {
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: historico.map(item => item.data_rentabilidade || item.mes || 'N/A'),
                        datasets: [{
                            label: 'Valor (R$)',
                            data: historico.map(item => parseFloat(item.valor || 0)),
                            backgroundColor: '#2ED2F8',
                            borderWidth: 0,
                            borderRadius: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                titleColor: '#ffffff',
                                bodyColor: '#ffffff',
                                callbacks: {
                                    label: function(context) {
                                        return 'R$ ' + context.raw.toLocaleString('pt-BR', {
                                            minimumFractionDigits: 2,
                                            maximumFractionDigits: 2
                                        });
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: { 
                                    color: 'rgba(255,255,255,0.1)',
                                    drawBorder: false
                                },
                                ticks: {
                                    color: '#94A3B8',
                                    font: { size: window.innerWidth < 768 ? 10 : 12 },
                                    callback: function(value) {
                                        return 'R$ ' + value.toLocaleString('pt-BR');
                                    },
                                    maxTicksLimit: 7
                                }
                            },
                            x: {
                                grid: { display: false },
                                ticks: { 
                                    color: '#94A3B8',
                                    font: { size: window.innerWidth < 768 ? 10 : 12 }
                                }
                            }
                        }
                    }
                });
            } catch (error) {
                console.error('Erro ao criar gráfico:', error);
            }
        }
    }
    
    // Inicializar após um pequeno delay
    setTimeout(initChart, 300);
});
</script>
<?php endif; ?>