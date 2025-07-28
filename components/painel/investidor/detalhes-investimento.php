<?php
/**
 * Seção Detalhes de Investimento - VERSÃO CORRIGIDA
 */
defined('ABSPATH') || exit;

$inv_id = isset($_GET['id']) ? absint($_GET['id']) : 0;
if (!$inv_id || !get_post($inv_id)) {
    echo '<div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6">
            <p class="text-sm text-red-700">Investimento não encontrado ou inválido.</p>
          </div>';
    return;
}

$user_id = get_current_user_id();
$args_aporte = [
    'post_type'      => 'aporte',
    'posts_per_page' => 1,
    'post_status'    => 'publish',
    'meta_query'     => [
        ['key' => 'investment_id', 'value' => $inv_id],
        ['key' => 'investidor_id', 'value' => $user_id],
    ],
    'orderby'        => 'post_date',
    'order'          => 'DESC',
];

$aporte_posts = get_posts($args_aporte);
$aporte_id = $aporte_posts ? $aporte_posts[0]->ID : 0;

$lamina_tecnica  = get_field('url_lamina_tecnica', $inv_id);
$titulo          = esc_html(get_the_title($inv_id));
$terms           = get_the_terms($inv_id, 'tipo_produto');
$tipo_produto    = $terms[0]->name ?? '';
$localizacao     = get_field('localizacao', $inv_id);
$rentabilidade   = floatval(get_field('rentabilidade', $inv_id)) ?: 0;
$risco           = esc_html(get_field('risco', $inv_id) ?: 'Não classificado');
$status          = function_exists('icf_get_investment_status') ? icf_get_investment_status($inv_id) : 'Status indisponível';
$link_produto    = get_permalink($inv_id);
$prazo           = get_field('prazo_do_investimento', $inv_id);
$periodo_minimo  = $prazo['prazo_min'] ?? 12;

$valor_compra         = floatval(get_field('valor_compra', $aporte_id)) ?: 0;
$valor_atual          = floatval(get_field('valor_atual', $aporte_id)) ?: 0;

$historico_aportes = get_field('historico_aportes', $aporte_id);
$valor_investido_total = 0;
if ($historico_aportes && is_array($historico_aportes)) {
    foreach ($historico_aportes as $item) {
        $valor_investido_total += floatval($item['valor_aporte'] ?? 0);
    }
}

// NOVA LÓGICA: Rentabilidade Projetada baseada no último valor do histórico
$rentabilidade_projetada = 0;
$rentabilidade_hist = get_field('rentabilidade_historico', $aporte_id);
if (!empty($rentabilidade_hist) && is_array($rentabilidade_hist)) {
    $ultimo_valor = end($rentabilidade_hist);
    if (isset($ultimo_valor['valor'])) {
        $rentabilidade_projetada = floatval($ultimo_valor['valor']);
    }
}

// CÁLCULO DA PORCENTAGEM DA RENTABILIDADE PROJETADA
$rentabilidade_pct = $valor_investido_total > 0 ? (($rentabilidade_projetada) / $valor_investido_total) * 100 : 0;

$whatsapp_assessor    = get_field('whatsapp_assessor', $aporte_id);
$nome_assessor        = get_field('nome_assessor', $aporte_id);
$foto_assessor        = get_field('foto_assessor', $aporte_id);
$contrato             = get_field('contrato_pdf', $aporte_id);

$venda_status        = get_field('venda_status', $aporte_id);
$venda_data          = get_field('venda_data', $aporte_id);
$venda_valor         = floatval(get_field('venda_valor', $aporte_id)) ?: 0;
$venda_rentabilidade = floatval(get_field('venda_rentabilidade', $aporte_id)) ?: 0;
$venda_observacoes   = get_field('venda_observacoes', $aporte_id);
$venda_documento     = get_field('venda_documento', $aporte_id);

$primeiro_aporte = reset($historico_aportes);
$data_inicio_raw = $primeiro_aporte['data_aporte'] ?? '';

$data_liberacao = 'Data indisponível';
$pode_vender = false;
$meses_pass = 0;

if (!empty($data_inicio_raw) && !$venda_status) {
    try {
        $data_investimento = DateTime::createFromFormat('d/m/Y', $data_inicio_raw);
        
        if (!$data_investimento) {
            $data_investimento = new DateTime($data_inicio_raw);
        }

        if ($data_investimento instanceof DateTime) {
            $investment_id = get_field('investment_id', $aporte_id);
            $prazo_investimento = get_field('prazo_do_investimento', $investment_id);
            $periodo_minimo = $prazo_investimento['prazo_min'] ?? 12;

            $data_liberacao = clone $data_investimento;
            $data_liberacao->modify("+{$periodo_minimo} months");
            $data_liberacao = $data_liberacao->format('d/m/Y');

            $hoje = new DateTime('now', $data_investimento->getTimezone());
            $intervalo = $data_investimento->diff($hoje);
            $meses_pass = ($intervalo->y * 12) + $intervalo->m;

            $pode_vender = $meses_pass >= $periodo_minimo;
        }
    } catch (Exception $e) {
        // Em caso de erro, manter valores padrão
    }
}

$docs = get_field('documentos', $inv_id) ?: [];
?>

<div class="bg-primary text-white p-6 md:p-10 rounded-xl max-w-screen-xl main-content-mobile mt-5 mb-20 md:mb-5 min-h-screen mx-auto">
    <div class="mb-8 md:mb-10 pb-4 md:pb-6 border-b border-white/15">
        <div class="flex items-start justify-between">
            <div class="flex-1">
                <h1 class="text-2xl md:text-4xl font-semibold mb-2 md:mb-3 tracking-tighter"><?php echo $titulo; ?></h1>
                <?php if ($localizacao) : ?>
                    <div class="text-slate-400 text-lg mb-6"><?php echo esc_html($localizacao); ?></div>
                <?php endif; ?>
            </div>
            
            <?php if ($venda_status) : ?>
                <div class="ml-4 text-center">
                    <div class="inline-block px-4 py-2 rounded-full text-sm font-bold bg-red-500/20 text-red-400 border border-red-500/30">
                        <i class="fas fa-hand-holding-usd mr-2"></i>
                        VENDIDO
                    </div>
                    <?php if ($venda_data) : ?>
                        <div class="text-slate-400 text-xs mt-1">em <?php echo esc_html($venda_data); ?></div>
                    <?php endif; ?>
                </div>
            <?php else : ?>
                <div class="ml-4 text-center">
                    <div class="inline-block px-4 py-2 rounded-full text-sm font-medium bg-green-500/20 text-green-400 border border-green-500/30">
                        <i class="fas fa-chart-line mr-2"></i>
                        ATIVO
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 md:gap-4 mb-8 md:mb-10 px-2 md:px-0">
            <div class="bg-white/8 p-3 md:p-4 lg:p-5 rounded-lg border border-white/10 text-center">
                <div class="text-slate-400 text-xs md:text-sm mb-1 md:mb-2">Valor Investido</div>
                <div class="text-lg md:text-xl lg:text-2xl font-semibold text-blue-400">R$ <?php echo number_format($valor_investido_total, 2, ',', '.'); ?></div>
            </div>
            
            <div class="bg-white/8 p-3 md:p-4 lg:p-5 rounded-lg border border-white/10 text-center">
                <div class="text-slate-400 text-xs md:text-sm mb-1 md:mb-2">Valor de Compra</div>
                <div class="text-lg md:text-xl lg:text-2xl font-semibold">R$ <?php echo number_format($valor_compra, 2, ',', '.'); ?></div>
            </div>
            
            <?php if ($venda_status) : ?>
                <div class="bg-white/8 p-3 md:p-4 lg:p-5 rounded-lg border border-white/10 text-center">
                    <div class="text-slate-400 text-xs md:text-sm mb-1 md:mb-2">Valor na Venda</div>
                    <div class="text-lg md:text-xl lg:text-2xl font-semibold text-accent">R$ <?php echo number_format($valor_atual, 2, ',', '.'); ?></div>
                </div>
                
                <div class="bg-white/8 p-3 md:p-4 lg:p-5 rounded-lg border border-white/10 text-center">
                    <div class="text-slate-400 text-xs md:text-sm mb-1 md:mb-2">Rentabilidade Consolidada</div>
                    <div class="text-lg md:text-xl lg:text-2xl font-semibold text-green-400">R$ <?php echo number_format($venda_valor, 2, ',', '.'); ?></div>
                    <!-- PORCENTAGEM ADICIONADA AQUI - USANDO O CAMPO CALCULADO AUTOMATICAMENTE -->
                    <div class="text-xs <?php echo $venda_rentabilidade >= 0 ? 'text-green-300' : 'text-red-300'; ?> mt-1">
                        (<?php echo ($venda_rentabilidade >= 0 ? '+' : ''); ?><?php echo number_format($venda_rentabilidade, 1, ',', '.'); ?>%)
                    </div>
                </div>
            <?php else : ?>
                <div class="bg-white/8 p-3 md:p-4 lg:p-5 rounded-lg border border-white/10 text-center">
                    <div class="text-slate-400 text-xs md:text-sm mb-1 md:mb-2">Valor Atual</div>
                    <div class="text-lg md:text-xl lg:text-2xl font-semibold">R$ <?php echo number_format($valor_atual, 2, ',', '.'); ?></div>
                </div>
                
                <div class="bg-white/8 p-3 md:p-4 lg:p-5 rounded-lg border border-white/10 text-center">
                    <div class="text-slate-400 text-xs md:text-sm mb-1 md:mb-2">Rentabilidade Projetada</div>
                    <div class="text-lg md:text-xl lg:text-2xl font-bold text-green-400">+R$ <?php echo number_format($rentabilidade_projetada, 2, ',', '.'); ?></div>
                    <!-- PORCENTAGEM ADICIONADA AQUI -->
                    <div class="text-xs <?php echo $rentabilidade_pct >= 0 ? 'text-green-300' : 'text-red-300'; ?> mt-1">
                        (<?php echo ($rentabilidade_pct >= 0 ? '+' : ''); ?><?php echo number_format($rentabilidade_pct, 1, ',', '.'); ?>%)
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="inline-block px-3 py-1 rounded-full text-sm font-medium bg-green-500/10 text-green-400 mt-4">
            <?php echo esc_html($status); ?>
        </div>
    </div>

    <?php if (!empty($rentabilidade_hist)) : ?>
        <div class="h-[300px] sm:h-[350px] md:h-[400px] my-6 md:my-12">
            <canvas id="investmentChart"></canvas>
        </div>
    <?php endif; ?>

    <?php if ($venda_status && $venda_observacoes) : ?>
        <div class="my-6 md:my-8 p-4 md:p-6 bg-white/5 rounded-xl border border-white/10">
            <h3 class="text-lg font-semibold mb-3 text-slate-300">Informações da Venda</h3>
            <p class="text-slate-400 text-sm md:text-base"><?php echo esc_html($venda_observacoes); ?></p>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2 md:gap-3 my-6 md:my-12">
        <?php if ($contrato && isset($contrato['url'])) : ?>
            <a href="<?php echo esc_url($contrato['url']); ?>" 
            class="flex items-center justify-center gap-2 md:gap-3 p-3 md:p-4 text-sm md:text-base rounded-xl bg-blue-900 border border-blue-800 hover:bg-blue-600 transition-colors"
            target="_blank" 
            rel="noopener noreferrer">
                <i class="fas fa-file-contract text-lg"></i>
                Visualizar Contrato
            </a>
        <?php endif; ?>
        
        <?php if ($venda_documento && isset($venda_documento['url'])) : ?>
            <a href="<?php echo esc_url($venda_documento['url']); ?>" 
            class="flex items-center justify-center gap-2 md:gap-3 p-3 md:p-4 text-sm md:text-base rounded-xl bg-purple-900 border border-purple-800 hover:bg-purple-600 transition-colors"
            target="_blank" 
            rel="noopener noreferrer">
                <i class="fas fa-file-invoice text-lg"></i>
                Documento da Venda
            </a>
        <?php endif; ?>
        
        <?php if ($lamina_tecnica) : ?>
            <a href="<?php echo esc_url($lamina_tecnica); ?>" class="flex items-center justify-center gap-2 md:gap-3 p-3 md:p-4 text-sm md:text-base rounded-xl bg-blue-900 border border-blue-800 hover:bg-blue-600 transition-colors">
                <i class="fas fa-file-invoice-dollar text-lg"></i>
                Lâmina Técnica
            </a>
        <?php endif; ?>
        
        <a href="<?php echo esc_url($link_produto); ?>" class="flex items-center justify-center gap-2 md:gap-3 p-3 md:p-4 text-sm md:text-base rounded-xl bg-blue-900 border border-blue-800 hover:bg-blue-600 transition-colors">
            <i class="fa-regular fa-circle-question text-lg"></i>
            Sobre o Investimento
        </a>

        <?php if (!$venda_status) : ?>
            <?php
            $whatsapp_url = "https://wa.me/".preg_replace('/\D/', '', $whatsapp_assessor)."?text=".rawurlencode("Olá ".$nome_assessor.", gostaria de sacar meu investimento: ".$titulo);
            $tooltip_text = $pode_vender ? 
                'Saque disponível!' : 
                ($data_liberacao !== 'Data indisponível' ? 
                    "Disponível a partir de {$data_liberacao}" : 
                    "Data de liberação não disponível");

            $botao_classes = $pode_vender ? 
                'bg-green-600 hover:bg-green-700' : 
                'bg-gradient-to-br from-red-500 to-pink-600 hover:from-red-600 hover:to-pink-700 opacity-60 cursor-not-allowed';
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
                
                <div class="absolute bottom-full mb-2 left-1/2 -translate-x-1/2 hidden group-hover:block bg-gray-800 text-white text-xs px-3 py-2 rounded-lg shadow-lg transition-opacity duration-200">
                    <?php echo esc_html($tooltip_text); ?>
                </div>
            </div>
        <?php else : ?>
            <div class="flex items-center justify-center gap-2 md:gap-3 p-3 md:p-4 text-sm md:text-base rounded-xl bg-gray-700 border border-gray-600 opacity-75">
                <i class="fas fa-check-circle text-green-400"></i>
                Investimento Vendido
            </div>
        <?php endif; ?>

        <div class="sm:col-span-2 lg:col-span-4 bg-white/10 p-4 md:p-6 rounded-xl flex flex-col md:flex-row items-center gap-4 text-center md:text-left">
                <?php if ($foto_assessor) : ?>
                    <img src="<?php echo esc_url($foto_assessor['url']); ?>" 
                         class="w-12 h-12 md:w-16 md:h-16 rounded-full">
            <?php endif; ?>
            <div class="flex-1">
                <div class="text-slate-400 text-xs md:text-sm">Seu Assessor</div>
                <div class="text-lg md:text-xl font-semibold mb-1 md:mb-2"><?php echo $nome_assessor; ?></div>
                <a href="https://wa.me/<?php echo preg_replace('/\D/', '', $whatsapp_assessor); ?>?text=<?php echo rawurlencode('Olá '.$nome_assessor.', gostaria de falar sobre '.$titulo); ?>" 
                   class="inline-flex items-center gap-1 md:gap-2 px-3 md:px-4 py-1 md:py-2 text-xs md:text-sm rounded-lg bg-blue-900 hover:bg-blue-600 transition-colors">
                    <i class="fab fa-whatsapp"></i>
                    Falar Agora
                </a>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 md:gap-8">
        <div class="px-2 md:px-0">
            <h3 class="text-slate-400 text-base md:text-lg mb-2 md:mb-4">Histórico de Aportes</h3>
            <?php if ($historico_aportes) : ?>
                <div class="space-y-2 md:space-y-4">
                    <?php foreach ($historico_aportes as $ap) : ?>
                        <div class="flex justify-between items-center py-1 md:py-2 border-b border-white/10">
                            <span class="text-xs md:text-sm"><?php echo esc_html($ap['data_aporte']); ?></span>
                            <span class="text-slate-400 text-xs md:text-sm">
                                R$ <?php echo number_format(floatval($ap['valor_aporte'] ?? 0), 2, ',', '.'); ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else : ?>
                <p class="text-slate-400">Nenhum aporte registrado</p>
            <?php endif; ?>
        </div>

        <div class="px-2 md:px-0">
            <h3 class="text-slate-400 text-base md:text-lg mb-2 md:mb-4">Documentos</h3>
            <?php if (!empty($docs)) : ?>
                <div class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-3 gap-3 md:gap-6">
                    <?php foreach ($docs as $doc) : 
                        $titulo = esc_html($doc['title'] ?? 'Documento');
                        $url = esc_url($doc['url']['url'] ?? '');
                    ?>
                        <a href="<?php echo $url; ?>" 
                        class="group relative text-center"
                        target="_blank"
                        rel="noopener noreferrer">
                            <div class="w-16 h-16 md:w-20 md:h-20 bg-blue-900/30 rounded-xl flex items-center justify-center mx-auto mb-2 transition-colors group-hover:bg-blue-900/50">
                                <i class="fas fa-file-pdf text-2xl md:text-3xl"></i>
                            </div>
                            <span class="text-slate-400 text-xs md:text-sm truncate px-2 block" title="<?php echo $titulo; ?>">
                                <?php echo $titulo; ?>
                            </span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php else : ?>
                <p class="text-slate-400">Nenhum documento disponível</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if (!empty($rentabilidade_hist)) : ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('investmentChart').getContext('2d');
    const historico = <?php echo json_encode($rentabilidade_hist ?: []); ?>;
    
    if (historico.length > 0) {
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: historico.map(item => item.data_rentabilidade || item.mes),
                datasets: [{
                    label: 'Valor (R$)',
                    data: historico.map(item => parseFloat(item.valor)),
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
                    font: { size: window.innerWidth < 768 ? 10 : 12 },
                    tooltip: {
                        callbacks: {
                            label: (context) => 'R$ ' + context.raw.toLocaleString('pt-BR')
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(255,255,255,0.1)' },
                        ticks: {
                            color: '#94A3B8',
                            callback: (value) => 'R$ ' + value.toLocaleString('pt-BR'),
                            maxTicksLimit: 7
                        }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { color: '#94A3B8' }
                    }
                }
            }
        });
    }
});
</script>
<?php endif; ?>