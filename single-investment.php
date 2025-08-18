<?php
/**
 * Template: Página de Detalhes do Investimento - VERSÃO COM INFORMAÇÕES ADICIONAIS
 */
defined('ABSPATH') || exit;

if ( ! current_user_can('administrator') && ! current_user_can('investidor') ) {
    wp_redirect( home_url('/acessar') );
    exit;
}

get_header();

$terms         = get_the_terms( get_the_ID(), 'tipo_produto' );
$tipo_produto  = $terms[0]->name ?? '';
$tipo_produto_slug = $terms[0]->slug ?? '';
$localizacao   = get_field( 'localizacao' );

$total_captado = floatval( get_field( 'total_captado')) ?: 0;
$valor_total   = floatval( get_field( 'valor_total')) ?: 0;
$porcentagem   = $valor_total > 0
  ? min( 100, ( $total_captado / $valor_total ) * 100 )
  : 0;

// ========== NOVA LÓGICA DE STATUS E DATAS ==========
$inicio_raw = get_field('data_inicio');
$fim_raw = get_field('fim_captacao');
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
$badge_class = 'bg-green-600';
$badge_text = 'Aberto';
$dias_restantes = '—';

if ($inicio_date && $hoje < $inicio_date) {
    // Ainda não começou
    $status_captacao = 'em_breve';
    $badge_class = 'bg-blue-600';
    $badge_text = 'Em Breve';
    
    $intervalo = $hoje->diff($inicio_date);
    $dias_restantes = "Faltam {$intervalo->days} dias para abertura";
    
} elseif ($fim_date && $hoje > $fim_date) {
    // Já encerrou
    $status_captacao = 'encerrada';
    $badge_class = 'bg-red-600';
    $badge_text = 'Encerrado';
    $dias_restantes = 'Captação encerrada';
    
} elseif ($inicio_date && $fim_date && $hoje >= $inicio_date && $hoje <= $fim_date) {
    // Está ativa
    $status_captacao = 'ativa';
    $badge_class = 'bg-green-600';
    $badge_text = 'Aberto';
    
    $intervalo = $hoje->diff($fim_date);
    $dias_restantes = $intervalo->invert 
        ? 'Captação encerrada' 
        : "Faltam {$intervalo->days} dias para encerramento";
}

// Se atingiu 100% da meta, também encerrar
if ($porcentagem >= 100) {
    $status_captacao = 'encerrada';
    $badge_class = 'bg-red-600';
    $badge_text = 'Encerrado';
    $dias_restantes = 'Meta atingida';
}

$display_inicio = $inicio_date instanceof DateTime
    ? date_i18n('d/m/Y', $inicio_date->getTimestamp())
    : '—';

$risco      = strtolower( get_field('risco') );
$risco_badge_map  = [
  'baixo' => 'bg-green-600',
  'medio' => 'bg-yellow-600',
  'alto'  => 'bg-red-600',
];
$risco_badge_class = $risco_badge_map[ $risco ] ?? 'bg-gray-600';

$lamina_url    = get_field('url_lamina_tecnica');
$whatsapp_no   = get_field('whatsapp_contato') 
                 ?: '5599999999999';
$investment_title = urlencode( get_the_title() );

// ========== NOVOS CAMPOS ADICIONAIS ==========
$quantidade_cotas = get_field('quantidade_cotas');
$cotas_vendidas = get_field('cotas_vendidas');
$regiao_projeto = get_field('regiao_projeto');
$data_lancamento = get_field('data_lancamento');
$percentual_vgv_por_cota = get_field('percentual_vgv_por_cota');

// Calcular cotas vendidas automaticamente se não estiver preenchido
if ($quantidade_cotas && !$cotas_vendidas) {
    $valor_por_cota = floatval(get_field('valor_por_cota') ?: 0);
    if ($valor_por_cota > 0) {
        $cotas_vendidas = floor($total_captado / $valor_por_cota);
    }
}
?>

<main class="pt-30 bg-radial-[at_10%_80%] from-slate-900 to-primary text-white">
  <section class="max-w-[1440px] h-full mx-auto px-4 py-10 grid grid-cols-1 md:grid-cols-2 gap-8 items-start">

    <div class="space-y-6">
      <?php if ( has_post_thumbnail() ) : ?>
        <figure class="overflow-hidden rounded-2xl shadow-lg aspect-[4/3] bg-gray-200">
          <?php the_post_thumbnail('large', [
            'class' => 'w-full h-full object-cover',
            'alt'   => get_the_title() . ' — imagem do investimento',
          ]); ?>
        </figure>
      <?php endif; ?>

      <div class="mt-6 flex flex-col space-y-4">
        <div class="flex flex-wrap gap-4">
<?php if ( ! empty($lamina_url) ) : ?>
  <a href="<?= esc_url($lamina_url); ?>"
     target="_blank" rel="noopener noreferrer"
     class="flex-1 sm:flex-none inline-flex items-center justify-center gap-2
            border-2 border-secondary text-secondary px-6 py-3 text-base font-semibold
            rounded hover:bg-secondary hover:text-primary hover:border-primary transition">
    <i class="fas fa-file-alt"></i>
    Lâmina Técnica
  </a>
<?php endif; ?>
        </div>
      </div>
    </div>

    <div class="space-y-8">

      <header class="grid space-y-2">
        <p class="text-base uppercase text-gray-400 tracking-wide">
          <?= esc_html($tipo_produto) ?>
          <?php if ( $regiao_projeto ) : ?>
            | <?= esc_html($regiao_projeto) ?>
          <?php elseif ( $localizacao ) : ?>
            | <?= esc_html($localizacao) ?>
          <?php endif; ?>
        </p>
        <h1 class="text-3xl font-bold"><?= get_the_title() ?></h1>
        
        <!-- BADGES DE STATUS - AGORA COM TRIBUTAÇÃO -->
        <div class="flex flex-wrap gap-2 items-center">
          <span class="inline-block <?= $badge_class ?> text-white text-xs font-bold px-3 py-1.5 rounded-full uppercase">
            <i class="fas <?= $status_captacao === 'em_breve' ? 'fa-clock' : ($status_captacao === 'ativa' ? 'fa-check-circle' : 'fa-times-circle') ?> mr-1"></i>
            <?= $badge_text ?>
          </span>
          
          <?php if ( $risco ) : ?>
            <span class="inline-block <?= $risco_badge_class ?> text-white text-xs font-bold px-3 py-1 rounded uppercase">
              Risco <?= ucfirst($risco) ?>
            </span>
          <?php endif; ?>

          <?php 
          // NOVA BADGE DE TRIBUTAÇÃO
          $impostos = wp_get_post_terms(get_the_ID(), 'imposto');
          if (!empty($impostos) && !is_wp_error($impostos)) : ?>
            <?php foreach ($impostos as $imposto) : ?>
              <span class="inline-block bg-yellow-500/20 text-yellow-400 text-xs font-bold px-3 py-1 rounded uppercase">
                <i class="fas fa-percent mr-1"></i>
                <?= esc_html($imposto->name) ?>
              </span>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </header>

      <div class="space-y-4">
        <div class="flex justify-between items-center text-base">
          <span class="font-semibold"><?= number_format($porcentagem,1) ?>% Captados</span>
          <span class="text-sm <?= $status_captacao === 'em_breve' ? 'text-blue-300' : ($status_captacao === 'ativa' ? 'text-green-300' : 'text-red-300') ?>">
            <?= esc_html($dias_restantes) ?>
          </span>
        </div>
        
        <div class="w-full bg-gray-700 h-6 rounded-full overflow-hidden">
          <div class="h-full <?= $status_captacao === 'em_breve' ? 'bg-blue-500' : ($status_captacao === 'ativa' ? 'bg-green-500' : 'bg-red-500') ?> transition-all"
               style="width: <?= $porcentagem ?>%;"
               role="progressbar"
               aria-valuenow="<?= $porcentagem ?>"
               aria-valuemin="0"
               aria-valuemax="100"
          ></div>
        </div>
        
        <div class="flex justify-between items-center text-sm text-gray-300">
          <span>
            <strong class="<?= $status_captacao === 'em_breve' ? 'text-blue-400' : ($status_captacao === 'ativa' ? 'text-green-400' : 'text-red-400') ?>">
              R$ <?= number_format($total_captado, 0, ',', '.') ?>
            </strong> captados
          </span>
          <span>
            de <strong class="text-white">R$ <?= number_format($valor_total, 0, ',', '.') ?></strong>
          </span>
        </div>
      </div>

      <!-- GRID DE INFORMAÇÕES REORGANIZADO CONFORME SOLICITADO -->
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-base">
        
        <!-- 1. RENTABILIDADE PROJETADA -->
        <div class="p-3 bg-slate-800 rounded">
          <p class="text-xs text-gray-400">Rentabilidade Projetada</p>
          <?php 
          $r = floatval( get_field('rentabilidade')) ?: 0;
          ?>
          <p class="mt-1 font-medium"><?= esc_html( number_format( $r, 2, ',', '.' ) ) ?>% a.a</p>
        </div>

        <!-- 2. APORTE MÍNIMO -->
        <div class="p-3 bg-slate-800 rounded">
          <p class="text-xs text-gray-400">Aporte Mínimo</p>
          <p class="mt-1 font-medium">R$ <?= number_format(get_field('aporte_minimo'),0,',','.') ?></p>
        </div>

        <!-- 3. REGIÃO -->
        <div class="p-3 bg-slate-800 rounded">
          <p class="text-xs text-gray-400">Região</p>
          <p class="mt-1 font-medium"><?= $regiao_projeto ? esc_html($regiao_projeto) : ($localizacao ? esc_html($localizacao) : '—') ?></p>
        </div>

        <!-- 4. LANÇAMENTO (com tooltip) -->
        <div class="p-3 bg-slate-800 rounded">
          <p class="text-xs text-gray-400 flex items-center gap-1">
            Lançamento
            <span class="tooltip-container">
              <i class="fas fa-info-circle text-gray-500 cursor-help" style="font-size: 10px;"></i>
              <span class="tooltip-text">data de início das vendas</span>
            </span>
          </p>
          <p class="mt-1 font-medium"><?= $data_lancamento ? esc_html($data_lancamento) : '—' ?></p>
        </div>

        <!-- 5. % DO VGV POR COTA -->
        <div class="p-3 bg-slate-800 rounded">
          <p class="text-xs text-gray-400">% do VGV por Cota</p>
          <p class="mt-1 font-medium"><?= $percentual_vgv_por_cota ? number_format($percentual_vgv_por_cota, 3, ',', '.') . '%' : '—' ?></p>
        </div>

        <!-- 6. INÍCIO DA CAPTAÇÃO -->
        <div class="p-3 bg-slate-800 rounded">
          <p class="text-xs text-gray-400">Início da Captação</p>
          <p class="mt-1 font-medium"><?= esc_html($display_inicio) ?></p>
        </div>

        <!-- 7. ORIGINADORA -->
        <div class="p-3 bg-slate-800 rounded col-span-full">
          <p class="text-xs text-gray-400 mb-1">Originadora</p>
          <?php
          $originadora = get_field('originadora');
          if ( is_array($originadora) && ! empty($originadora['url']) ) {
            printf(
              '<a href="%1$s" target="_blank" rel="noopener" class="inline-flex items-center gap-1 hover:underline text-sm">%2$s <i class="fas fa-external-link-alt font-medium text-xs"></i></a>',
              esc_url($originadora['url']),
              esc_html($originadora['title'])
            );
          } else {
            echo '—';
          }
          ?>
        </div>
      </div>
    </div>
  </section>
</main>

<style>
/* Tooltip personalizado para o ícone de lançamento */
.tooltip-container {
    position: relative;
    display: inline-block;
}

.tooltip-container .tooltip-text {
    visibility: hidden;
    width: 180px;
    background-color: #1f2937;
    color: #fff;
    text-align: center;
    border-radius: 6px;
    padding: 8px 12px;
    position: absolute;
    z-index: 1000;
    bottom: 125%;
    left: 50%;
    margin-left: -90px;
    opacity: 0;
    transition: opacity 0.3s ease, visibility 0.3s ease;
    font-size: 12px;
    font-weight: normal;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
}

.tooltip-container .tooltip-text::after {
    content: "";
    position: absolute;
    top: 100%;
    left: 50%;
    margin-left: -5px;
    border-width: 5px;
    border-style: solid;
    border-color: #1f2937 transparent transparent transparent;
}

.tooltip-container:hover .tooltip-text {
    visibility: visible;
    opacity: 1;
}

/* Responsivo: ajustar posição em telas menores */
@media (max-width: 640px) {
    .tooltip-container .tooltip-text {
        width: 160px;
        margin-left: -80px;
        font-size: 11px;
    }
}
</style>

<?php get_footer(); ?>