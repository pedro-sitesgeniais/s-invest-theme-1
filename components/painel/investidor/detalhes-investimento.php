<?php
/**
 * Seção Detalhes de Investimento - VERSÃO COM MÚLTIPLOS APORTES
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
    'posts_per_page' => -1,
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

// Verificação de tipo
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

// ===== PROCESSAR MÚLTIPLOS APORTES =====
$valor_investido_total = 0;
$aportes_ativos = 0;
$aportes_vendidos = 0;
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

// Buscar contratos de venda de aportes vendidos
$contratos_venda = [];
foreach ($aporte_posts as $aporte_post) {
    $venda_status_item = get_field('venda_status', $aporte_post->ID);
    if ($venda_status_item) {
        $contrato_venda = get_field('contrato_venda_pdf', $aporte_post->ID);
        if ($contrato_venda && isset($contrato_venda['url'])) {
            $contratos_venda[] = [
                'url' => $contrato_venda['url'],
                'nome' => $contrato_venda['title'] ?? 'Contrato de Venda',
                'data_venda' => get_field('venda_data', $aporte_post->ID) ?? '',
                'aporte_titulo' => get_the_title($aporte_post->ID)
            ];
        }
    }
}

// Valores separados por status - CORRIGIDO
$valor_recebido_total = 0;
$rentabilidade_reais_vendidos = 0;
$valor_na_venda_total = 0;
$maior_valor_ativo = 0; // ✅ Maior valor individual ativo
$valor_atual_ativos_total = 0; // ✅ SOMA de todos os valores atuais ativos
$rentabilidade_ativa_total = 0;
$valor_investido_vendidos = 0;
$valor_investido_ativos = 0;
$data_venda = '';

foreach ($aporte_posts as $aporte_post) {
    $aporte_id = $aporte_post->ID;
    $venda_status_item = get_field('venda_status', $aporte_id);
    
    // Calcular valor investido deste aporte
    $historico_aportes = get_field('historico_aportes', $aporte_id) ?: [];
    $valor_investido_item = 0;
    foreach ($historico_aportes as $item) {
        $valor_investido_item += floatval($item['valor_aporte'] ?? 0);
        $historico_aportes_consolidado[] = $item;
    }
    $valor_investido_total += $valor_investido_item;
    
    if ($venda_status_item) {
        // Aporte vendido
        $aportes_vendidos++;
        $valor_investido_vendidos += $valor_investido_item;
        $valor_recebido_total += floatval(get_field('venda_valor', $aporte_id) ?: 0);
        $valor_na_venda_total += floatval(get_field('valor_atual', $aporte_id) ?: 0);
        $rentabilidade_reais_vendidos += floatval(get_field('venda_rentabilidade_reais', $aporte_id) ?: 0);
        
        if (!$data_venda) {
            $data_venda = get_field('venda_data', $aporte_id) ?: '';
        }
    } else {
        // Aporte ativo
        $aportes_ativos++;
        $valor_investido_ativos += $valor_investido_item;
        $valor_atual_aporte = floatval(get_field('valor_atual', $aporte_id) ?: 0);
        
        // ✅ Somar TODOS os valores atuais ativos
        $valor_atual_ativos_total += $valor_atual_aporte;
        
        // ✅ Pegar apenas o maior valor individual (para fallback)
        if ($valor_atual_aporte > $maior_valor_ativo) {
            $maior_valor_ativo = $valor_atual_aporte;
        }
        
        // ✅ CORRIGIR: Calcular rentabilidade baseada no histórico OU na diferença
        $rentabilidade_hist = get_field('rentabilidade_historico', $aporte_id) ?: [];
        if (!empty($rentabilidade_hist) && is_array($rentabilidade_hist)) {
            $ultimo_valor = end($rentabilidade_hist);
            if (isset($ultimo_valor['valor'])) {
                $rentabilidade_ativa_total += floatval($ultimo_valor['valor']);
            }
        } else {
            // Se não tem histórico, calcular pela diferença valor_atual - valor_investido
            $diferenca = $valor_atual_aporte - $valor_investido_item;
            $rentabilidade_ativa_total += $diferenca;
        }
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

// Determinar status geral
$status_geral = 'ativo';
if ($aportes_vendidos > 0 && $aportes_ativos === 0) {
    $status_geral = 'vendido';
} elseif ($aportes_vendidos > 0 && $aportes_ativos > 0) {
    $status_geral = 'misto';
}

// ✅ CORRIGIR CÁLCULO DA RENTABILIDADE - usar RENTABILIDADE, não valor atual
$rentabilidade_pct_vendidos = 0;
$rentabilidade_pct_ativos = 0;
$rentabilidade_pct_geral = 0;

if ($valor_investido_vendidos > 0 && $valor_recebido_total > 0) {
    $rentabilidade_pct_vendidos = ($valor_recebido_total / $valor_investido_vendidos) * 100;
}

// ✅ CORRIGIR: usar rentabilidade_ativa_total, não valor_atual
if ($valor_investido_ativos > 0 && $rentabilidade_ativa_total > 0) {
    $rentabilidade_pct_ativos = ($rentabilidade_ativa_total / $valor_investido_ativos) * 100;
}

if ($valor_investido_total > 0 && $valor_recebido_total > 0) {
    $rentabilidade_pct_geral = ($valor_recebido_total / $valor_investido_total) * 100;
}

// ✅ CORRIGIR: usar rentabilidade_ativa_total para aportes ativos puros
$rentabilidade_pct_ativos_puros = 0;
if ($valor_investido_total > 0 && $rentabilidade_ativa_total > 0) {
    $rentabilidade_pct_ativos_puros = ($rentabilidade_ativa_total / $valor_investido_total) * 100;
}

// Valores finais para exibição - CORRIGIDO NOVAMENTE
$valor_compra = floatval(get_field('valor_compra', $aporte_principal->ID) ?: 0);

// ✅ VOLTAR: Valor atual deve ser o MAIOR individual, não soma
if ($status_geral === 'vendido') {
    $valor_atual = $valor_na_venda_total; // Soma dos valores na venda
} elseif ($status_geral === 'misto') {
    $valor_atual = $maior_valor_ativo; // ✅ VOLTAR: Maior individual para mistos
} else {
    $valor_atual = $maior_valor_ativo; // Para um único aporte ativo
}

$venda_status = ($status_geral === 'vendido');
$venda_valor = $valor_recebido_total;
$venda_rentabilidade = $rentabilidade_pct_vendidos;

// ✅ CORRIGIR RENTABILIDADE PROJETADA: usar histórico, não diferença
$rentabilidade_projetada = $rentabilidade_ativa_total; // Sempre do histórico

$rentabilidade_pct = ($status_geral === 'misto') ? $rentabilidade_pct_ativos : 
                    ($status_geral === 'ativo' ? $rentabilidade_pct_ativos_puros : $rentabilidade_pct_geral);

// Converter array associativo em indexado para o gráfico
$rentabilidade_hist = array_values($historico_rentabilidade_consolidado);

// Ordenar por data
usort($rentabilidade_hist, function($a, $b) {
    $dateA = DateTime::createFromFormat('d/m/Y', $a['data_rentabilidade']);
    $dateB = DateTime::createFromFormat('d/m/Y', $b['data_rentabilidade']);
    if (!$dateA || !$dateB) return 0;
    return $dateA->getTimestamp() - $dateB->getTimestamp();
});

// Dados específicos para dividendos
$ultimo_dividendo = null;

if ($is_private && !empty($historico_dividendos_consolidado)) {
    foreach ($historico_dividendos_consolidado as $dividendo) {
        $data_dividendo = $dividendo['data_dividendo'] ?? $dividendo['data'] ?? '';
        if ($data_dividendo && (!$ultimo_dividendo || 
            strtotime($data_dividendo) > strtotime($ultimo_dividendo['data']))) {
            $ultimo_dividendo = [
                'valor' => floatval($dividendo['valor'] ?? 0),
                'data' => $data_dividendo
            ];
        }
    }
}

// Dados básicos do investimento
$titulo = esc_html(get_the_title($inv_id));
$localizacao = get_field('localizacao', $inv_id) ?: '';
$lamina_tecnica = get_field('url_lamina_tecnica', $inv_id) ?: '';
$link_produto = get_permalink($inv_id);

// Lógica de venda para produtos TRADE
$pode_vender = false;
$data_liberacao = 'Data indisponível';

if (!$is_private && !$venda_status && !empty($historico_aportes_consolidado)) {
    $primeiro_aporte = reset($historico_aportes_consolidado);
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

// Status do investimento
$status = 'Status indisponível';
if (function_exists('s_invest_get_status_captacao_info')) {
    $status_info = s_invest_get_status_captacao_info($inv_id);
    $status = $status_info['label'] ?? 'Status indisponível';
} elseif (function_exists('icf_get_investment_status')) {
    $status = icf_get_investment_status($inv_id);
}

// Documentos
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
            
            <!-- BADGES LADO A LADO -->
            <div class="ml-4 text-center">
                <div class="flex flex-row gap-2 mb-2">
                    <!-- Badge de Status (Vendido/Misto/Ativo) -->
                    <?php if ($status_geral === 'vendido') : ?>
                        <div class="inline-block px-4 py-2 rounded-full text-sm font-bold bg-red-500/20 text-red-400 border border-red-500/30">
                            <i class="fas fa-hand-holding-usd mr-2"></i>
                            VENDIDO
                        </div>
                    <?php elseif ($status_geral === 'misto') : ?>
                        <div class="inline-block px-4 py-2 rounded-full text-sm font-bold bg-gradient-to-r from-blue-500/20 to-orange-500/20 text-blue-400 border border-blue-500/30">
                            <i class="fas fa-exchange-alt mr-2"></i>
                            MISTO
                        </div>
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
                
                <!-- Informações adicionais -->
                <?php if ($status_geral === 'vendido' && $data_venda) : ?>
                    <div class="text-slate-400 text-xs">em <?php echo esc_html($data_venda); ?></div>
                <?php elseif ($status_geral === 'misto') : ?>
                    <div class="text-slate-400 text-xs">
                        <?php echo $aportes_ativos; ?> ativo<?php echo $aportes_ativos > 1 ? 's' : ''; ?> • 
                        <?php echo $aportes_vendidos; ?> vendido<?php echo $aportes_vendidos > 1 ? 's' : ''; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- CARDS DE VALORES -->
        <div class="grid grid-cols-2 <?php echo $is_private ? 'lg:grid-cols-3' : 'lg:grid-cols-4'; ?> gap-3 md:gap-4 mb-8 md:mb-10 px-2 md:px-0">
            <!-- Card 1: Valor Investido -->
            <div class="bg-white/8 p-3 md:p-4 lg:p-5 rounded-lg border border-white/10 text-center">
                <div class="text-slate-400 text-xs md:text-sm mb-1 md:mb-2">Valor Investido</div>
                <div class="text-lg md:text-xl lg:text-2xl font-semibold text-blue-400">R$ <?php echo number_format($valor_investido_total, 2, ',', '.'); ?></div>
            </div>
            
            <?php if ($is_private) : ?>
                <!-- PRODUTOS PRIVATE/SCP - Cards específicos (SEM PRÓXIMO DIVIDENDO) -->
                
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
                
            <?php else : ?>
                <!-- PRODUTOS TRADE - Cards tradicionais -->
                
                <!-- Card 2: Valor de Compra -->
                <div class="bg-white/8 p-3 md:p-4 lg:p-5 rounded-lg border border-white/10 text-center">
                    <div class="text-slate-400 text-xs md:text-sm mb-1 md:mb-2">Valor de Compra</div>
                    <div class="text-lg md:text-xl lg:text-2xl font-semibold">R$ <?php echo number_format($valor_compra, 2, ',', '.'); ?></div>
                </div>
                
                <?php if ($status_geral === 'vendido') : ?>
                    <!-- Produto TRADE vendido -->
                    <div class="bg-white/8 p-3 md:p-4 lg:p-5 rounded-lg border border-white/10 text-center">
                        <div class="text-slate-400 text-xs md:text-sm mb-1 md:mb-2">Valor na Venda</div>
                        <div class="text-lg md:text-xl lg:text-2xl font-semibold text-accent">R$ <?php echo number_format($valor_atual, 2, ',', '.'); ?></div>
                    </div>
                    
                    <div class="bg-white/8 p-3 md:p-4 lg:p-5 rounded-lg border border-white/10 text-center">
                        <div class="text-slate-400 text-xs md:text-sm mb-1 md:mb-2">Rentabilidade Final</div>
                        <div class="text-lg md:text-xl lg:text-2xl font-semibold text-green-400">R$ <?php echo number_format($venda_valor, 2, ',', '.'); ?></div>
                        <div class="text-xs text-green-300 mt-1">
                            (<?php echo number_format($venda_rentabilidade, 1, ',', '.'); ?>%)
                        </div>
                    </div>
                <?php elseif ($status_geral === 'misto') : ?>
                    <!-- Produto TRADE misto - USAR RENTABILIDADE DO HISTÓRICO -->
                    <div class="bg-white/8 p-3 md:p-4 lg:p-5 rounded-lg border border-white/10 text-center">
                        <div class="text-slate-400 text-xs md:text-sm mb-1 md:mb-2">Valor Atual (Maior Ativo)</div>
                        <div class="text-lg md:text-xl lg:text-2xl font-semibold">R$ <?php echo number_format($maior_valor_ativo, 2, ',', '.'); ?></div>
                        <div class="text-xs text-slate-500 mt-1">Vendidos: R$ <?php echo number_format($valor_na_venda_total, 2, ',', '.'); ?></div>
                    </div>
                    
                    <div class="bg-white/8 p-3 md:p-4 lg:p-5 rounded-lg border border-white/10 text-center">
                        <div class="text-slate-400 text-xs md:text-sm mb-1 md:mb-2">Rentabilidade Ativa</div>
                        <div class="text-lg md:text-xl lg:text-2xl font-bold text-green-400">
                            +R$ <?php echo number_format($rentabilidade_ativa_total, 2, ',', '.'); ?>
                        </div>
                        <div class="text-xs <?php echo $rentabilidade_pct_ativos >= 0 ? 'text-green-300' : 'text-red-300'; ?> mt-1">
                            (<?php echo number_format($rentabilidade_pct_ativos, 1, ',', '.'); ?>%)
                        </div>
                        <div class="text-xs text-slate-500 mt-1">
                            Vendidos: R$ <?php echo number_format($valor_recebido_total, 2, ',', '.'); ?> 
                            (<?php echo number_format($rentabilidade_pct_vendidos, 1, ',', '.'); ?>%)
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
                        <div class="text-xs text-green-300 mt-1">
                            (<?php echo number_format($rentabilidade_pct, 1, ',', '.'); ?>%)
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
    <?php if ($venda_status && !empty($data_venda)) : ?>
        <div class="my-6 md:my-8 p-4 md:p-6 bg-white/5 rounded-xl border border-white/10">
            <h3 class="text-lg font-semibold mb-3 text-slate-300">Informações da Venda</h3>
            <p class="text-slate-400 text-sm md:text-base">Venda realizada em <?php echo esc_html($data_venda); ?></p>
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
        
        <!-- Contratos de Venda -->
        <?php if (!empty($contratos_venda)) : ?>
            <?php if (count($contratos_venda) == 1) : ?>
                <!-- Um único contrato de venda -->
                <a href="<?php echo esc_url($contratos_venda[0]['url']); ?>" 
                   class="flex items-center justify-center gap-2 md:gap-3 p-3 md:p-4 text-sm md:text-base rounded-xl bg-green-900 border border-green-800 hover:bg-green-600 transition-colors"
                   target="_blank" 
                   rel="noopener noreferrer"
                   title="Contrato de venda de <?php echo esc_attr($contratos_venda[0]['data_venda']); ?>">
                    <i class="fas fa-file-signature text-lg"></i>
                    Contrato de Venda
                </a>
            <?php else : ?>
                <!-- Múltiplos contratos de venda - botão dropdown -->
                <div class="relative" data-dropdown="contratos-venda">
                    <button class="flex items-center justify-center gap-2 md:gap-3 p-3 md:p-4 text-sm md:text-base rounded-xl bg-green-900 border border-green-800 hover:bg-green-600 transition-colors w-full"
                            type="button"
                            onclick="toggleDropdown('contratos-venda')">
                        <i class="fas fa-file-signature text-lg"></i>
                        Contratos de Venda (<?php echo count($contratos_venda); ?>)
                        <i class="fas fa-chevron-down text-xs transition-transform" id="chevron-contratos-venda"></i>
                    </button>
                    
                    <!-- Dropdown menu -->
                    <div class="absolute top-full left-0 right-0 mt-1 bg-gray-800 border border-gray-700 rounded-lg shadow-lg opacity-0 invisible transform scale-95 transition-all duration-200 z-20"
                         id="dropdown-contratos-venda">
                        <?php foreach ($contratos_venda as $index => $contrato_venda) : ?>
                            <a href="<?php echo esc_url($contrato_venda['url']); ?>" 
                               class="flex items-center gap-3 p-3 text-sm hover:bg-gray-700 transition-colors <?php echo $index === 0 ? 'rounded-t-lg' : ''; ?> <?php echo $index === count($contratos_venda) - 1 ? 'rounded-b-lg' : 'border-b border-gray-700'; ?>"
                               target="_blank" 
                               rel="noopener noreferrer">
                                <i class="fas fa-file-signature text-green-400"></i>
                                <div class="flex-1">
                                    <div class="font-medium text-white">Venda de <?php echo esc_html($contrato_venda['data_venda']); ?></div>
                                    <?php if ($contrato_venda['aporte_titulo']) : ?>
                                        <div class="text-xs text-gray-400"><?php echo esc_html($contrato_venda['aporte_titulo']); ?></div>
                                    <?php endif; ?>
                                </div>
                                <i class="fas fa-external-link-alt text-xs text-gray-400"></i>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
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
        <?php if (!$venda_status && $aportes_ativos > 0) : ?>
            <?php if ($is_private) : ?>
                <!-- PRODUTOS PRIVATE -->
                <div class="flex items-center justify-center gap-2 md:gap-3 p-3 md:p-4 text-sm md:text-base rounded-xl bg-purple-600 border border-purple-500 opacity-75">
                    <i class="fas fa-coins text-yellow-400"></i>
                    Gerando Dividendos
                </div>
            <?php else : ?>
                <!-- PRODUTOS TRADE -->
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

    <!-- HISTÓRICO -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 md:gap-8">
        <div class="px-2 md:px-0">
            <h3 class="text-slate-400 text-base md:text-lg mb-2 md:mb-4">
                <?php echo $is_private ? 'Histórico de Dividendos' : 'Histórico de Aportes'; ?>
            </h3>

            <?php if ($is_private) : ?>
                <!-- PRODUTOS PRIVATE: Mostrar dividendos -->
                <?php if (!empty($historico_dividendos_consolidado) && is_array($historico_dividendos_consolidado)) : ?>
                    <div class="space-y-2 md:space-y-4">
                        <?php foreach ($historico_dividendos_consolidado as $dividendo) : ?>
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
                <?php if (!empty($historico_aportes_consolidado) && is_array($historico_aportes_consolidado)) : ?>
                    <div class="space-y-2 md:space-y-4">
                        <?php foreach ($historico_aportes_consolidado as $ap) : ?>
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
    </div>
</div>

<!-- SCRIPT DO GRÁFICO -->
<?php if (!empty($rentabilidade_hist) && is_array($rentabilidade_hist) && count($rentabilidade_hist) > 0) : ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
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
    
    setTimeout(initChart, 300);
});
</script>
<?php endif; ?>

<!-- SCRIPT PARA DROPDOWN DOS CONTRATOS -->
<?php if (!empty($contratos_venda) && count($contratos_venda) > 1) : ?>
<script>
function toggleDropdown(dropdownId) {
    const dropdown = document.getElementById('dropdown-' + dropdownId);
    const chevron = document.getElementById('chevron-' + dropdownId);
    
    if (!dropdown || !chevron) return;
    
    // Fechar outros dropdowns primeiro
    document.querySelectorAll('[id^="dropdown-"]').forEach(function(otherDropdown) {
        if (otherDropdown.id !== 'dropdown-' + dropdownId) {
            otherDropdown.classList.add('opacity-0', 'invisible', 'scale-95');
            otherDropdown.classList.remove('opacity-100', 'visible', 'scale-100');
        }
    });
    
    document.querySelectorAll('[id^="chevron-"]').forEach(function(otherChevron) {
        if (otherChevron.id !== 'chevron-' + dropdownId) {
            otherChevron.classList.remove('rotate-180');
        }
    });
    
    // Toggle o dropdown atual
    if (dropdown.classList.contains('opacity-0')) {
        dropdown.classList.remove('opacity-0', 'invisible', 'scale-95');
        dropdown.classList.add('opacity-100', 'visible', 'scale-100');
        chevron.classList.add('rotate-180');
    } else {
        dropdown.classList.add('opacity-0', 'invisible', 'scale-95');
        dropdown.classList.remove('opacity-100', 'visible', 'scale-100');
        chevron.classList.remove('rotate-180');
    }
}

// Fechar dropdown ao clicar fora
document.addEventListener('click', function(event) {
    if (!event.target.closest('[data-dropdown]')) {
        document.querySelectorAll('[id^="dropdown-"]').forEach(function(dropdown) {
            dropdown.classList.add('opacity-0', 'invisible', 'scale-95');
            dropdown.classList.remove('opacity-100', 'visible', 'scale-100');
        });
        
        document.querySelectorAll('[id^="chevron-"]').forEach(function(chevron) {
            chevron.classList.remove('rotate-180');
        });
    }
});
</script>
<?php endif; ?>