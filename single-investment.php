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
$localizacao   = get_field( 'localizacao' );

$total_captado = floatval( get_field( 'total_captado')) ?: 0;
$valor_total   = floatval( get_field( 'valor_total')) ?: 0;
$porcentagem   = $valor_total > 0
  ? min( 100, ( $total_captado / $valor_total ) * 100 )
  : 0;

$fim_raw = get_field( 'fim_captacao' );
$fim_date = false;

if ( $fim_raw ) {
  $fim_date = DateTime::createFromFormat('Y-m-d', $fim_raw)
           ?: DateTime::createFromFormat('d/m/Y', $fim_raw)
           ?: false;
}

$dias_restantes = '—';
if ( $fim_date instanceof DateTime ) {
  $hoje      = new DateTime();
  $intervalo = $hoje->diff( $fim_date );
  $dias_restantes = $intervalo->invert
    ? 'Encerrado'
    : "Faltam {$intervalo->days} dias";
}

$display_fim = $fim_date instanceof DateTime
  ? date_i18n('d/m/Y', $fim_date->getTimestamp())
  : '—';

$inicio_raw = get_field('data_inicio');
$inicio_date = false;
if ($inicio_raw) {
    $inicio_date = DateTime::createFromFormat('Y-m-d', $inicio_raw)
        ?: DateTime::createFromFormat('d/m/Y', $inicio_raw)
        ?: false;
}
$display_inicio = $inicio_date instanceof DateTime
    ? date_i18n('d/m/Y', $inicio_date->getTimestamp())
    : '—';

$risco      = strtolower( get_field('risco') );
$badge_map  = [
  'baixo' => 'bg-green-600',
  'medio' => 'bg-yellow-600',
  'alto'  => 'bg-red-600',
];
$badge_class = $badge_map[ $risco ] ?? 'bg-gray-600';

$lamina_url    = get_field('url_lamina_tecnica');
$whatsapp_no   = get_field('whatsapp_contato') 
                 ?: '5599999999999';
$investment_title = urlencode( get_the_title() );

// ========== NOVOS CAMPOS ADICIONAIS ==========
$quantidade_cotas = get_field('quantidade_cotas');
$cotas_vendidas = get_field('cotas_vendidas');
$regiao_projeto = get_field('regiao_projeto');
$data_lancamento = get_field('data_lancamento');

// Calcular cotas vendidas automaticamente se não estiver preenchido
if ($quantidade_cotas && !$cotas_vendidas) {
    $valor_por_cota = floatval(get_field('valor_por_cota') ?: 0);
    if ($valor_por_cota > 0) {
        $cotas_vendidas = floor($total_captado / $valor_por_cota);
    }
}
?>

<main class="pt-30 bg-radial-[at_10%_80%] from-slate-900 to-primary text-white">
  <section class="max-w-[1440px] mx-auto px-4 py-10 grid grid-cols-1 md:grid-cols-2 gap-8 items-start">

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
          <?php if ( $lamina_url ) : ?>
            <a
              href="<?= esc_url( $lamina_url ); ?>"
              target="_blank" rel="noopener noreferrer"
              class="flex-1 sm:flex-none inline-flex items-center justify-center gap-2
                     border-2 border-secondary text-secondary px-6 py-3 text-base font-semibold
                     rounded hover:bg-secondary hover:text-primary hover:border-primary transition"
            >
              <i class="fas fa-file-alt"></i>
              Lâmina Técnica
            </a>
          <?php endif; ?>
        </div>

        <!-- COMPARTILHAMENTO REMOVIDO CONFORME SOLICITAÇÃO -->
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
      </header>

      <div class="space-y-4">
        <div class="flex justify-between items-center text-base">
          <span class="font-semibold"><?= number_format($porcentagem,1) ?>% Captados</span>
          <span><?= esc_html($dias_restantes) ?></span>
        </div>
        
        <div class="w-full bg-gray-700 h-6 rounded-full overflow-hidden">
          <div class="h-full bg-green-500 transition-all"
               style="width: <?= $porcentagem ?>%;"
               role="progressbar"
               aria-valuenow="<?= $porcentagem ?>"
               aria-valuemin="0"
               aria-valuemax="100"
          ></div>
        </div>
        
        <div class="flex justify-between items-center text-sm text-gray-300">
          <span>
            <strong class="text-green-400">R$ <?= number_format($total_captado, 0, ',', '.') ?></strong> captados
          </span>
          <span>
            de <strong class="text-white">R$ <?= number_format($valor_total, 0, ',', '.') ?></strong>
          </span>
        </div>

        <?php if ( $risco ) : ?>
          <span class="inline-block <?= $badge_class ?> text-white text-xs font-bold px-3 py-1 rounded uppercase">
            <?= ucfirst($risco) ?>
          </span>
        <?php endif; ?>
      </div>

      <!-- GRID DE INFORMAÇÕES ATUALIZADO COM NOVOS CAMPOS -->
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-base">
        <div class="p-3 bg-slate-800 rounded">
          <p class="text-xs text-gray-400">Rentabilidade Projetada</p>
          <?php 
          $r = floatval( get_field('rentabilidade')) ?: 0;
          ?>
          <p class="mt-1 font-medium"><?= esc_html( number_format( $r, 2, ',', '.' ) ) ?>% a.a</p>
        </div>

        <div class="p-3 bg-slate-800 rounded">
          <p class="text-xs text-gray-400">Prazo</p>
          <?php 
          $prazo = get_field('prazo_do_investimento');
          $prazo_min = $prazo['prazo_min'] ?? null;
          $prazo_max = $prazo['prazo_max'] ?? null;

          if ($prazo_min && $prazo_max && $prazo_min != $prazo_max) {
            $prazo_label = $prazo_min . ' a ' . $prazo_max . ' meses';
          } elseif ($prazo_min) {
            $prazo_label = $prazo_min . ' meses';
          } else {
            $prazo_label = '—';
          }
          ?>
          <p class="mt-1 font-medium"><?= esc_html($prazo_label) ?></p>
        </div>

        <div class="p-3 bg-slate-800 rounded">
          <p class="text-xs text-gray-400">Aporte Mínimo</p>
          <p class="mt-1 font-medium">R$ <?= number_format(get_field('aporte_minimo'),0,',','.') ?></p>
        </div>

        <div class="p-3 bg-slate-800 rounded">
          <p class="text-xs text-gray-400">Moeda Aceita</p>
          <p class="mt-1 space-x-1">
            <?php
            $moedas = (array) get_field('moeda_aceita');
            if ( $moedas ) {
              foreach ( $moedas as $m ) {
                printf(
                  '<span class="inline-block bg-secondary text-primary text-xs font-bold rounded px-2 py-1">%s</span>',
                  esc_html($m)
                );
              }
            } else {
              echo '—';
            }
            ?>
          </p>
        </div>

        <!-- NOVOS CAMPOS ADICIONAIS -->
        <?php if ($quantidade_cotas) : ?>
        <div class="p-3 bg-slate-800 rounded">
          <p class="text-xs text-gray-400">Quantidade de Cotas</p>
          <p class="mt-1 font-medium">
            <?php if ($cotas_vendidas) : ?>
              <span class="text-secondary"><?= number_format($cotas_vendidas, 0, ',', '.') ?></span>
              <span class="text-gray-400"> / </span>
            <?php endif; ?>
            <?= number_format($quantidade_cotas, 0, ',', '.') ?>
          </p>
          <?php if ($cotas_vendidas && $quantidade_cotas > 0) : ?>
            <?php $percentual_cotas = ($cotas_vendidas / $quantidade_cotas) * 100; ?>
            <p class="text-xs text-gray-500 mt-1">
              <?= number_format($percentual_cotas, 1, ',', '.') ?>% vendidas
            </p>
          <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if ($regiao_projeto) : ?>
        <div class="p-3 bg-slate-800 rounded">
          <p class="text-xs text-gray-400">Região do Projeto</p>
          <p class="mt-1 font-medium"><?= esc_html($regiao_projeto) ?></p>
        </div>
        <?php endif; ?>

        <?php if ($data_lancamento) : ?>
        <div class="p-3 bg-slate-800 rounded">
          <p class="text-xs text-gray-400">Data de Lançamento</p>
          <p class="mt-1 font-medium"><?= esc_html($data_lancamento) ?></p>
        </div>
        <?php endif; ?>

        <div class="p-3 bg-slate-800 rounded">
          <p class="text-xs text-gray-400">Data de Início</p>
          <p class="mt-1 font-medium"><?= esc_html($display_inicio) ?></p>
        </div>

        <div class="p-3 bg-slate-800 rounded">
          <p class="text-xs text-gray-400">Fim da Captação</p>
          <p class="mt-1 font-medium"><?= esc_html($display_fim) ?></p>
        </div>

        <?php 
        $impostos = wp_get_post_terms(get_the_ID(), 'imposto');
        if (!empty($impostos) && !is_wp_error($impostos)) : ?>
        <div class="p-3 bg-slate-800 rounded">
          <p class="text-xs text-gray-400">Tributação</p>
          <p class="mt-1 space-x-1">
            <?php foreach ($impostos as $imposto) : ?>
              <span class="inline-block bg-yellow-500/20 text-yellow-400 text-xs font-bold rounded px-2 py-1">
                <?= esc_html($imposto->name) ?>
              </span>
            <?php endforeach; ?>
          </p>
        </div>
        <?php endif; ?>

        <?php 
        $modalidades = wp_get_post_terms(get_the_ID(), 'modalidade');
        if (!empty($modalidades) && !is_wp_error($modalidades)) : ?>
        <div class="p-3 bg-slate-800 rounded">
          <p class="text-xs text-gray-400">Modalidade</p>
          <p class="mt-1 space-x-1">
            <?php foreach ($modalidades as $modalidade) : ?>
              <span class="inline-block bg-accent/20 text-secondary text-xs font-bold rounded px-2 py-1">
                <?= esc_html($modalidade->name) ?>
              </span>
            <?php endforeach; ?>
          </p>
        </div>
        <?php endif; ?>

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

  <section class="bg-gray-100 text-primary py-10" x-data="investmentTabs()">
    <div class="max-w-[1440px] mx-auto px-4">
      <?php
      $tabs_disponiveis = [];
      
      $cenario_base = get_field('cenario_base');
      $cenario_otimista = get_field('cenario_otimista');
      $cenario_pessimista = get_field('cenario_pessimista');
      
      function cenario_tem_dados($cenario) {
          if (!$cenario) return false;
          $campos = ['exposicao_maxima', 'rentabilidade_cdi', 'prazo', 'rentabilidade_tir'];
          foreach ($campos as $campo) {
              if (!empty($cenario[$campo])) return true;
          }
          return false;
      }
      
      if (cenario_tem_dados($cenario_base) || cenario_tem_dados($cenario_otimista) || cenario_tem_dados($cenario_pessimista)) {
          $tabs_disponiveis['cenarios'] = '<i class="fas fa-chart-line"></i> Cenários';
      }
      
      $desc_originadora = get_field('descricao_originadora');
      $video_originadora = get_field('video_originadora');
      $originadora = get_field('originadora');
      if (!empty($desc_originadora) || !empty($video_originadora) || !empty($originadora)) {
          $tabs_disponiveis['originadora'] = '<i class="fas fa-info-circle"></i> Sobre';
      }
      
      $documentos = get_field('documentos');
      if (!empty($documentos) && is_array($documentos)) {
          $tabs_disponiveis['documentos'] = '<i class="fas fa-file-alt"></i> Documentos';
      }
      
      $motivos = get_field('motivos');
      if (!empty($motivos) && is_array($motivos)) {
          $tabs_disponiveis['motivos'] = '<i class="fas fa-seedling"></i> Motivos';
      }
      
      $riscos = get_field('riscos');
      if (!empty($riscos) && is_array($riscos)) {
          $tabs_disponiveis['riscos'] = '<i class="fas fa-exclamation-triangle"></i> Riscos';
      }
      
      if (empty($tabs_disponiveis)) {
          echo '<p class="text-center text-gray-600">Informações adicionais em breve.</p>';
      } else {
          $primeira_aba = array_key_first($tabs_disponiveis);
          echo '<script>document.addEventListener("DOMContentLoaded", function() { window.primeiraAba = "' . $primeira_aba . '"; });</script>';
          ?>
          
          <nav class="flex flex-wrap gap-2 mb-6" role="tablist" aria-label="Seções do investimento">
            <?php foreach ($tabs_disponiveis as $key => $label) : ?>
              <button
                type="button"
                @click="aba = '<?= esc_js($key) ?>'"
                :class="{ 'bg-white shadow font-bold': aba === '<?= esc_js($key) ?>' }"
                class="px-4 py-2 rounded text-base inline-flex items-center gap-1"
                role="tab"
                aria-controls="tab-<?= esc_attr($key) ?>"
              >
                <?= wp_kses_post($label) ?>
              </button>
            <?php endforeach; ?>
          </nav>

          <?php
          foreach (array_keys($tabs_disponiveis) as $aba) {
              switch ($aba) {
                  case 'cenarios':
                      get_template_part('components/bloco-cenarios');
                      break;
                  case 'originadora':
                      get_template_part('components/bloco-originadora');
                      break;
                  case 'documentos':
                      get_template_part('components/bloco-documentos');
                      break;
                  case 'motivos':
                      get_template_part('components/bloco-motivos');
                      break;
                  case 'riscos':
                      get_template_part('components/bloco-riscos');
                      break;
              }
          }
          ?>
          
          <script>
          document.addEventListener('alpine:init', () => {
              Alpine.data('investmentTabs', () => ({
                  aba: window.primeiraAba || 'cenarios'
              }));
          });
          </script>
          
      <?php } ?>
    </div>
  </section>

</main>

<?php get_footer(); ?>