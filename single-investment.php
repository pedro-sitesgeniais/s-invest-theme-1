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

// Buscar documentos do investimento
$documentos = get_field('documentos') ?: [];

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
  <!-- CONTAINER PRINCIPAL COM MENU LATERAL -->
  <div class="flex max-w-[1440px] mx-auto">
    
    <!-- MENU LATERAL -->
    <aside class="w-80 min-h-screen bg-slate-800/50 backdrop-blur-sm border-r border-slate-700/50 sticky top-0">
      <div class="p-6">
        <nav class="space-y-2">
          <a href="#porque-gostamos" onclick="scrollToSection('porque-gostamos')" 
             class="flex items-center gap-3 px-4 py-3 text-gray-300 hover:text-white hover:bg-slate-700/50 rounded-lg transition-all group menu-item" data-section="porque-gostamos">
            <i class="fas fa-heart text-secondary group-hover:scale-110 transition-transform icon-glow"></i>
            <span class="font-medium">Porque gostamos deste ativo</span>
          </a>
          
          <a href="#riscos" onclick="scrollToSection('riscos')" 
             class="flex items-center gap-3 px-4 py-3 text-gray-300 hover:text-white hover:bg-slate-700/50 rounded-lg transition-all group menu-item" data-section="riscos">
            <i class="fas fa-exclamation-triangle text-yellow-500 group-hover:scale-110 transition-transform icon-glow"></i>
            <span class="font-medium">Riscos</span>
          </a>
          
          <a href="#documentos" onclick="scrollToSection('documentos')" 
             class="flex items-center gap-3 px-4 py-3 text-gray-300 hover:text-white hover:bg-slate-700/50 rounded-lg transition-all group menu-item" data-section="documentos">
            <i class="fas fa-folder-open text-blue-400 group-hover:scale-110 transition-transform icon-glow"></i>
            <span class="font-medium">Documentos</span>
          </a>
          
          <a href="#disclaimer" onclick="scrollToSection('disclaimer')" 
             class="flex items-center gap-3 px-4 py-3 text-gray-300 hover:text-white hover:bg-slate-700/50 rounded-lg transition-all group menu-item" data-section="disclaimer">
            <i class="fas fa-info-circle text-gray-400 group-hover:scale-110 transition-transform icon-glow"></i>
            <span class="font-medium">Disclaimer</span>
          </a>
        </nav>
      </div>
    </aside>
    
    <!-- CONTEÚDO PRINCIPAL -->
    <div class="flex-1">
      <!-- SEÇÃO HERO COM INFORMAÇÕES DO INVESTIMENTO -->
      <section class="px-8 py-10 grid grid-cols-1 md:grid-cols-2 gap-8 items-start">

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

<?php if ( ! empty($documentos) && is_array($documentos) ) : ?>
  <button onclick="openDocumentsModal()"
          class="flex-1 sm:flex-none inline-flex items-center justify-center gap-2
                 bg-secondary text-primary border-2 hover:bg-primary hover:text-secondary hover:border-secondary px-6 py-3 text-base font-semibold
                 rounded hover:bg-opacity-80 transition-all">
    <i class="fas fa-folder-open"></i>
    Documentos (<?= count($documentos) ?>)
  </button>
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
      
      <!-- SEÇÃO: PORQUE GOSTAMOS DESTE ATIVO -->
      <section id="porque-gostamos" class="px-8 py-16 border-t border-slate-700/30">
        <div class="max-w-4xl">
          <h2 class="text-3xl font-bold mb-8 flex items-center gap-3">
            <i class="fas fa-heart text-secondary"></i>
            Porque gostamos deste ativo
          </h2>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <?php 
            $motivos = get_field('motivos_investimento');
            if ($motivos && is_array($motivos)) : 
              foreach ($motivos as $motivo) : 
                $titulo = $motivo['titulo'] ?? '';
                $descricao = $motivo['descricao'] ?? '';
                $icone = $motivo['icone'] ?? 'fas fa-check-circle';
            ?>
            <div class="bg-slate-800/50 backdrop-blur-sm p-6 rounded-xl border border-slate-700/50 hover:border-secondary/30 transition-all section-card">
              <div class="flex items-start gap-4">
                <div class="w-12 h-12 bg-secondary/10 rounded-lg flex items-center justify-center flex-shrink-0">
                  <i class="<?= esc_attr($icone) ?> text-secondary text-xl"></i>
                </div>
                <div>
                  <h3 class="text-lg font-semibold text-white mb-2"><?= esc_html($titulo) ?></h3>
                  <p class="text-gray-300 leading-relaxed"><?= esc_html($descricao) ?></p>
                </div>
              </div>
            </div>
            <?php 
              endforeach;
            else :
            ?>
            <div class="col-span-full">
              <div class="bg-slate-800/50 backdrop-blur-sm p-8 rounded-xl border border-slate-700/50 text-center">
                <i class="fas fa-info-circle text-gray-400 text-3xl mb-4"></i>
                <p class="text-gray-400">Os motivos para este investimento serão adicionados em breve.</p>
              </div>
            </div>
            <?php endif; ?>
          </div>
        </div>
      </section>
      
      <!-- SEÇÃO: RISCOS -->
      <section id="riscos" class="px-8 py-16 border-t border-slate-700/30">
        <div class="max-w-4xl">
          <h2 class="text-3xl font-bold mb-8 flex items-center gap-3">
            <i class="fas fa-exclamation-triangle text-yellow-500"></i>
            Riscos
          </h2>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <?php 
            $riscos_lista = get_field('riscos_operacao');
            if ($riscos_lista && is_array($riscos_lista)) : 
              foreach ($riscos_lista as $risco_item) : 
                $titulo = $risco_item['titulo'] ?? '';
                $descricao = $risco_item['descricao'] ?? '';
                $nivel = $risco_item['nivel'] ?? 'medio';
                
                $cor_nivel = [
                  'baixo' => 'text-green-500 border-green-500/30 bg-green-500/5',
                  'medio' => 'text-yellow-500 border-yellow-500/30 bg-yellow-500/5',
                  'alto' => 'text-red-500 border-red-500/30 bg-red-500/5'
                ];
                $classe_nivel = $cor_nivel[$nivel] ?? $cor_nivel['medio'];
            ?>
            <div class="bg-slate-800/50 backdrop-blur-sm p-6 rounded-xl border border-slate-700/50 hover:border-yellow-500/30 transition-all section-card">
              <div class="flex items-start gap-4">
                <div class="w-12 h-12 bg-yellow-500/10 rounded-lg flex items-center justify-center flex-shrink-0">
                  <i class="fas fa-exclamation-triangle text-yellow-500 text-xl"></i>
                </div>
                <div class="flex-1">
                  <div class="flex items-center gap-3 mb-2">
                    <h3 class="text-lg font-semibold text-white"><?= esc_html($titulo) ?></h3>
                    <span class="px-2 py-1 text-xs font-medium rounded-full border <?= $classe_nivel ?>">
                      <?= ucfirst($nivel) ?>
                    </span>
                  </div>
                  <p class="text-gray-300 leading-relaxed"><?= esc_html($descricao) ?></p>
                </div>
              </div>
            </div>
            <?php 
              endforeach;
            else :
            ?>
            <div class="col-span-full">
              <div class="bg-slate-800/50 backdrop-blur-sm p-8 rounded-xl border border-slate-700/50 text-center">
                <i class="fas fa-shield-alt text-gray-400 text-3xl mb-4"></i>
                <p class="text-gray-400">Os riscos específicos desta operação serão detalhados em breve.</p>
              </div>
            </div>
            <?php endif; ?>
          </div>
        </div>
      </section>
      
      <!-- SEÇÃO: DOCUMENTOS -->
      <section id="documentos" class="px-8 py-16 border-t border-slate-700/30">
        <div class="max-w-4xl">
          <h2 class="text-3xl font-bold mb-8 flex items-center gap-3">
            <i class="fas fa-folder-open text-blue-400"></i>
            Documentos
          </h2>
          
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php if ( ! empty($lamina_url) ) : ?>
            <div class="bg-slate-800/50 backdrop-blur-sm p-6 rounded-xl border border-slate-700/50 hover:border-blue-400/30 transition-all group section-card">
              <div class="text-center">
                <div class="w-16 h-16 bg-blue-400/10 rounded-xl flex items-center justify-center mx-auto mb-4 group-hover:scale-105 transition-transform">
                  <i class="fas fa-file-alt text-blue-400 text-2xl"></i>
                </div>
                <h3 class="text-lg font-semibold text-white mb-2">Lâmina Técnica</h3>
                <p class="text-gray-400 text-sm mb-4">Documento oficial com informações técnicas do investimento</p>
                <a href="<?= esc_url($lamina_url); ?>" target="_blank" rel="noopener noreferrer"
                   class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors text-sm font-medium">
                  <i class="fas fa-external-link-alt"></i>
                  Visualizar
                </a>
              </div>
            </div>
            <?php endif; ?>
            
            <?php if ( ! empty($documentos) && is_array($documentos) ) : ?>
            <div class="bg-slate-800/50 backdrop-blur-sm p-6 rounded-xl border border-slate-700/50 hover:border-blue-400/30 transition-all group section-card">
              <div class="text-center">
                <div class="w-16 h-16 bg-blue-400/10 rounded-xl flex items-center justify-center mx-auto mb-4 group-hover:scale-105 transition-transform">
                  <i class="fas fa-folder-open text-blue-400 text-2xl"></i>
                </div>
                <h3 class="text-lg font-semibold text-white mb-2">Documentos Adicionais</h3>
                <p class="text-gray-400 text-sm mb-4"><?= count($documentos) ?> documento<?= count($documentos) > 1 ? 's' : '' ?> disponível<?= count($documentos) > 1 ? 'eis' : '' ?></p>
                <button onclick="openDocumentsModal()"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors text-sm font-medium">
                  <i class="fas fa-eye"></i>
                  Ver Documentos
                </button>
              </div>
            </div>
            <?php endif; ?>
            
            <?php if ( empty($lamina_url) && (empty($documentos) || !is_array($documentos)) ) : ?>
            <div class="col-span-full">
              <div class="bg-slate-800/50 backdrop-blur-sm p-8 rounded-xl border border-slate-700/50 text-center">
                <i class="fas fa-folder text-gray-400 text-3xl mb-4"></i>
                <p class="text-gray-400">Os documentos deste investimento serão disponibilizados em breve.</p>
              </div>
            </div>
            <?php endif; ?>
          </div>
        </div>
      </section>
      
      <!-- SEÇÃO: DISCLAIMER -->
      <section id="disclaimer" class="px-8 py-16 border-t border-slate-700/30">
        <div class="max-w-4xl">
          <div class="bg-primary/10 border border-primary/20 backdrop-blur-sm p-8 rounded-xl">
            <h2 class="text-2xl font-bold mb-6 flex items-center gap-3 text-primary">
              <i class="fas fa-info-circle"></i>
              Disclaimer
            </h2>
            
            <div class="space-y-4 text-gray-300 leading-relaxed">
              <p>
                <strong class="text-white">Importante:</strong> Este material é de caráter exclusivamente informativo e não constitui oferta, solicitação ou recomendação de investimento. Rentabilidade passada não representa garantia de rentabilidade futura.
              </p>
              
              <p>
                Os investimentos apresentados podem estar sujeitos a variação de preços e riscos, incluindo a possível perda do capital investido. É recomendável a leitura cuidadosa de todos os documentos relacionados aos investimentos, em especial o regulamento ou lâmina de informações essenciais, antes de qualquer decisão de investimento.
              </p>
              
              <p>
                Para mais informações sobre riscos, tributação e taxas, consulte os documentos informativos disponibilizados ou entre em contato com nossa equipe de relacionamento.
              </p>
              
              <p class="text-sm text-gray-400 pt-4 border-t border-gray-600">
                <strong>Brava Forte Investimentos</strong> - CVM nº XXX-X | Este documento foi gerado em <?= date('d/m/Y') ?>
              </p>
            </div>
          </div>
        </div>
      </section>
    </div>
  </div>

  <!-- MODAL DE DOCUMENTOS -->
  <?php if ( ! empty($documentos) && is_array($documentos) ) : ?>
  <div id="documentsModal" 
       class="fixed inset-0 z-50 flex items-center justify-center bg-black/75 backdrop-opacity-10 backdrop-blur-sm opacity-0 invisible transition-all duration-300">
    <!-- Container do Modal -->
    <div class="bg-slate-800 rounded-2xl shadow-2xl border border-slate-700 w-full max-w-4xl mx-4 max-h-[90vh] overflow-hidden transform scale-95 transition-transform duration-300"
         onclick="event.stopPropagation()">
      
      <!-- Header do Modal -->
      <div class="flex items-center justify-between p-6 border-b border-slate-700">
        <div>
          <h2 class="text-2xl font-bold text-white mb-1">
            <i class="fas fa-folder-open text-blue-400 mr-3"></i>
            Documentos do Investimento
          </h2>
          <p class="text-slate-400 text-sm">
            <?= get_the_title() ?> • <?= count($documentos) ?> documento<?= count($documentos) > 1 ? 's' : '' ?>
          </p>
        </div>
        <button onclick="closeDocumentsModal()" 
                class="text-slate-400 hover:text-white text-2xl transition-colors p-2 hover:bg-slate-700 rounded-lg">
          <i class="fas fa-times"></i>
        </button>
      </div>
      
      <!-- Corpo do Modal - Lista de Documentos -->
      <div class="p-6 max-h-[70vh] overflow-y-auto">
        <div class="grid gap-4">
          <?php foreach ($documentos as $index => $documento) : ?>
            <?php 
            $titulo = $documento['title'] ?? 'Documento ' . ($index + 1);
            $arquivo = $documento['url'] ?? null;
            
            // Verificar se é array (dados do ACF) ou string (URL direta)
            if (is_array($arquivo) && isset($arquivo['url'])) {
                $url = $arquivo['url'];
                $nome_arquivo = $arquivo['filename'] ?? basename($url);
                $tamanho = $arquivo['filesize'] ?? 0;
                $tipo = $arquivo['subtype'] ?? 'file';
            } elseif (is_string($arquivo) && !empty($arquivo)) {
                $url = $arquivo;
                $nome_arquivo = basename($url);
                $tamanho = 0;
                $tipo = pathinfo($url, PATHINFO_EXTENSION) ?? 'file';
            } else {
                continue; // Pular se não há URL válida
            }
            
            // Definir ícone baseado no tipo de arquivo
            $icone_map = [
                'pdf' => 'fas fa-file-pdf text-red-500',
                'doc' => 'fas fa-file-word text-blue-500',
                'docx' => 'fas fa-file-word text-blue-500',
                'xls' => 'fas fa-file-excel text-green-500',
                'xlsx' => 'fas fa-file-excel text-green-500',
                'jpg' => 'fas fa-file-image text-purple-500',
                'jpeg' => 'fas fa-file-image text-purple-500',
                'png' => 'fas fa-file-image text-purple-500',
                'zip' => 'fas fa-file-archive text-yellow-500',
                'rar' => 'fas fa-file-archive text-yellow-500',
            ];
            $icone = $icone_map[$tipo] ?? 'fas fa-file text-slate-400';
            
            // Formatar tamanho do arquivo
            $tamanho_formatado = '';
            if ($tamanho > 0) {
                if ($tamanho >= 1048576) {
                    $tamanho_formatado = number_format($tamanho / 1048576, 1) . ' MB';
                } elseif ($tamanho >= 1024) {
                    $tamanho_formatado = number_format($tamanho / 1024, 1) . ' KB';
                } else {
                    $tamanho_formatado = $tamanho . ' bytes';
                }
            }
            ?>
            
            <div class="group bg-slate-900 hover:bg-slate-750 rounded-xl p-4 border border-slate-700 hover:border-slate-600 transition-all">
              <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4 flex-1">
                  <!-- Ícone do Arquivo -->
                  <div class="flex-shrink-0">
                    <div class="w-12 h-12 bg-slate-800 rounded-lg flex items-center justify-center">
                      <i class="<?= $icone ?> text-xl"></i>
                    </div>
                  </div>
                  
                  <!-- Informações do Arquivo -->
                  <div class="flex-1 min-w-0">
                    <h3 class="text-white font-semibold text-lg leading-tight mb-1 truncate">
                      <?= esc_html($titulo) ?>
                    </h3>
                    <div class="flex flex-col sm:flex-row sm:items-center text-sm text-slate-400 gap-1 sm:gap-3">
                      <span class="truncate"><?= esc_html($nome_arquivo) ?></span>
                      <?php if ($tamanho_formatado) : ?>
                        <span class="hidden sm:inline">•</span>
                        <span><?= esc_html($tamanho_formatado) ?></span>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
                
                <!-- Botão de Ação -->
                <div class="flex items-center ml-4">
                  <button onclick="openDocumentPreview('<?= esc_js($url) ?>', '<?= esc_js($titulo) ?>', '<?= esc_js($tipo) ?>')" 
                          class="inline-flex items-center justify-center w-10 h-10 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors"
                          title="Visualizar documento">
                    <i class="fas fa-eye text-sm"></i>
                  </button>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
      
      <!-- Footer do Modal -->
      <div class="p-6 border-t border-slate-700 bg-slate-900">
        <div class="flex items-center justify-between">
          <p class="text-slate-400 text-sm">
            <i class="fas fa-info-circle mr-2"></i>
            Clique em <i class="fas fa-eye mx-1"></i> para visualizar o documento
          </p>
          <button onclick="closeDocumentsModal()" 
                  class="px-6 py-2 bg-slate-700 hover:bg-slate-600 text-white rounded-lg transition-colors">
            Fechar
          </button>
        </div>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- MODAL DE PREVIEW DE DOCUMENTO -->
  <div id="documentPreviewModal" 
       class="fixed inset-0 z-60 flex items-center justify-center bg-black bg-opacity-90 backdrop-blur-sm opacity-0 invisible transition-all duration-300">
    <!-- Container do Modal de Preview -->
    <div class="bg-slate-900 rounded-xl shadow-2xl border border-slate-700 w-full max-w-6xl mx-4 max-h-[95vh] overflow-hidden transform scale-95 transition-transform duration-300"
         onclick="event.stopPropagation()">
      
      <!-- Header do Preview -->
      <div class="flex items-center justify-between p-4 border-b border-slate-700 bg-slate-800">
        <div class="flex items-center space-x-3">
          <i class="fas fa-file-alt text-blue-400"></i>
          <div>
            <h3 class="text-lg font-semibold text-white" id="previewDocumentTitle">Visualizar Documento</h3>
            <p class="text-sm text-slate-400" id="previewDocumentType">PDF</p>
          </div>
        </div>
        <div class="flex items-center space-x-2">
          <a id="previewExternalLink" 
             href="#" 
             target="_blank" 
             rel="noopener noreferrer"
             class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors text-sm">
            <i class="fas fa-external-link-alt mr-2"></i>
            Abrir em Nova Aba
          </a>
          <button onclick="closeDocumentPreview()" 
                  class="text-slate-400 hover:text-white text-xl transition-colors p-2 hover:bg-slate-700 rounded-lg">
            <i class="fas fa-times"></i>
          </button>
        </div>
      </div>
      
      <!-- Corpo do Preview -->
      <div class="p-0 h-[calc(95vh-80px)] bg-white">
        <div id="previewContent" class="w-full h-full flex items-center justify-center">
          <div class="text-center text-slate-500">
            <i class="fas fa-spinner fa-spin text-3xl mb-4"></i>
            <p>Carregando documento...</p>
          </div>
        </div>
      </div>
    </div>
  </div>

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

/* Estilos do Modal de Documentos */
#documentsModal {
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
}

#documentsModal.show {
    opacity: 1;
    visibility: visible;
}

#documentsModal.show > div {
    transform: scale(1);
}

/* Scrollbar customizada para o corpo do modal */
#documentsModal .max-h-\[70vh\]::-webkit-scrollbar {
    width: 8px;
}

#documentsModal .max-h-\[70vh\]::-webkit-scrollbar-track {
    background: #1e293b;
    border-radius: 4px;
}

#documentsModal .max-h-\[70vh\]::-webkit-scrollbar-thumb {
    background: #475569;
    border-radius: 4px;
}

#documentsModal .max-h-\[70vh\]::-webkit-scrollbar-thumb:hover {
    background: #64748b;
}

/* Responsividade do modal */
@media (max-width: 768px) {
    #documentsModal .max-w-4xl {
        max-width: calc(100vw - 2rem);
        margin: 1rem;
    }
    
    #documentsModal .p-6 {
        padding: 1rem;
    }
    
    #documentsModal .text-2xl {
        font-size: 1.5rem;
    }
}

/* Estilos do Modal de Preview */
#documentPreviewModal {
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    z-index: 60;
}

#documentPreviewModal.show {
    opacity: 1;
    visibility: visible;
}

#documentPreviewModal.show > div {
    transform: scale(1);
}

#previewContent iframe {
    width: 100%;
    height: 100%;
    border: none;
    background: white;
}

#previewContent.image-preview {
    background: #000;
    display: flex;
    align-items: center;
    justify-content: center;
}

#previewContent.image-preview img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
}

/* Responsividade do modal de preview */
@media (max-width: 768px) {
    #documentPreviewModal .max-w-6xl {
        max-width: calc(100vw - 1rem);
        margin: 0.5rem;
    }
    
    #documentPreviewModal .p-4 {
        padding: 0.75rem;
    }
    
    #documentPreviewModal .text-lg {
        font-size: 1rem;
    }
    
    #documentPreviewModal .px-4 {
        padding-left: 0.75rem;
        padding-right: 0.75rem;
    }
}

/* Estilos para o menu lateral e navegação */
.menu-item-active {
    background-color: rgba(46, 210, 248, 0.1) !important;
    color: #2ED2F8 !important;
    border-left: 3px solid #2ED2F8;
    padding-left: calc(1rem - 3px);
}

.menu-item {
    transition: all 0.3s ease;
    border-left: 3px solid transparent;
}

.menu-item:hover {
    border-left: 3px solid rgba(46, 210, 248, 0.5);
    padding-left: calc(1rem - 3px);
}

/* Scroll suave para toda a página */
html {
    scroll-behavior: smooth;
}

/* Responsividade do menu lateral */
@media (max-width: 1024px) {
    aside {
        display: none;
    }
    
    .flex-1 {
        width: 100%;
    }
}

/* Estilos para as seções */
section {
    scroll-margin-top: 100px;
}

/* Animações para os cards das seções */
.section-card {
    transition: all 0.3s ease;
}

.section-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

/* Efeito de brilho nos ícones */
.icon-glow:hover {
    filter: drop-shadow(0 0 8px currentColor);
}

/* Customização da scrollbar do menu */
aside::-webkit-scrollbar {
    width: 6px;
}

aside::-webkit-scrollbar-thumb {
    background-color: #2ED2F8;
    border-radius: 3px;
}

aside::-webkit-scrollbar-thumb:hover {
    background-color: rgba(46, 210, 248, 0.8);
}

aside::-webkit-scrollbar-track {
    background: rgba(30, 41, 59, 0.5);
}
</style>

<script>
// Função para scroll suave e navegação do menu lateral
function scrollToSection(sectionId) {
    const element = document.getElementById(sectionId);
    if (element) {
        // Offset para compensar o menu fixo (se houver)
        const offset = 100;
        const elementPosition = element.offsetTop - offset;
        
        window.scrollTo({
            top: elementPosition,
            behavior: 'smooth'
        });
    }
    
    // Atualizar estado ativo do menu
    updateActiveMenuItem(sectionId);
}

// Função para atualizar item ativo do menu
function updateActiveMenuItem(activeSection) {
    const menuItems = document.querySelectorAll('.menu-item');
    
    menuItems.forEach(item => {
        item.classList.remove('menu-item-active', 'bg-slate-700/50', 'text-white');
        item.classList.add('text-gray-300');
    });
    
    const activeItem = document.querySelector(`[data-section="${activeSection}"]`);
    if (activeItem) {
        activeItem.classList.add('menu-item-active', 'bg-slate-700/50', 'text-white');
        activeItem.classList.remove('text-gray-300');
    }
}

// Observer para detectar seção visível e atualizar menu automaticamente
function setupScrollObserver() {
    const sections = document.querySelectorAll('section[id]');
    
    const observerOptions = {
        root: null,
        rootMargin: '-50% 0px -50% 0px',
        threshold: 0
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                updateActiveMenuItem(entry.target.id);
            }
        });
    }, observerOptions);
    
    sections.forEach(section => {
        observer.observe(section);
    });
}

// Funções para controlar o modal de documentos
function openDocumentsModal() {
    const modal = document.getElementById('documentsModal');
    if (modal) {
        modal.classList.add('show');
        document.body.style.overflow = 'hidden'; // Prevenir scroll do corpo
        
        // Focar no botão de fechar para acessibilidade
        setTimeout(() => {
            const closeButton = modal.querySelector('button[onclick="closeDocumentsModal()"]');
            if (closeButton) closeButton.focus();
        }, 100);
    }
}

function closeDocumentsModal() {
    const modal = document.getElementById('documentsModal');
    if (modal) {
        modal.classList.remove('show');
        document.body.style.overflow = ''; // Restaurar scroll do corpo
    }
}

// Funções para o modal de preview de documentos
function openDocumentPreview(url, title, type) {
    const modal = document.getElementById('documentPreviewModal');
    const titleElement = document.getElementById('previewDocumentTitle');
    const typeElement = document.getElementById('previewDocumentType');
    const contentElement = document.getElementById('previewContent');
    const externalLink = document.getElementById('previewExternalLink');
    
    if (modal && contentElement) {
        // Atualizar informações do header
        if (titleElement) titleElement.textContent = title || 'Documento';
        if (typeElement) typeElement.textContent = type.toUpperCase() || 'ARQUIVO';
        if (externalLink) externalLink.href = url;
        
        // Limpar conteúdo anterior
        contentElement.innerHTML = '';
        contentElement.className = 'w-full h-full flex items-center justify-center';
        
        // Mostrar loading
        contentElement.innerHTML = `
            <div class="text-center text-slate-500">
                <i class="fas fa-spinner fa-spin text-3xl mb-4"></i>
                <p>Carregando documento...</p>
            </div>
        `;
        
        // Mostrar modal
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
        
        // Carregar conteúdo baseado no tipo
        setTimeout(() => {
            loadDocumentContent(url, type, contentElement);
        }, 500);
    }
}

function loadDocumentContent(url, type, contentElement) {
    const lowerType = type.toLowerCase();
    
    try {
        if (lowerType === 'pdf') {
            // Para PDFs, usar iframe com Google Docs Viewer como fallback
            contentElement.innerHTML = `
                <iframe src="${url}#toolbar=0&navpanes=0&scrollbar=0" 
                        onload="this.style.opacity=1" 
                        style="opacity:0; transition: opacity 0.3s"
                        onerror="this.src='https://docs.google.com/gview?url=${encodeURIComponent(url)}&embedded=true'">
                </iframe>
            `;
        } else if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(lowerType)) {
            // Para imagens
            contentElement.className = 'w-full h-full image-preview';
            contentElement.innerHTML = `
                <img src="${url}" 
                     alt="Preview da imagem"
                     onload="this.style.opacity=1" 
                     style="opacity:0; transition: opacity 0.3s"
                     onerror="showPreviewError(this.parentElement)" />
            `;
        } else if (['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'].includes(lowerType)) {
            // Para documentos Office, usar Google Docs Viewer
            contentElement.innerHTML = `
                <iframe src="https://docs.google.com/gview?url=${encodeURIComponent(url)}&embedded=true"
                        onload="this.style.opacity=1" 
                        style="opacity:0; transition: opacity 0.3s"
                        onerror="showPreviewError(this.parentElement)">
                </iframe>
            `;
        } else {
            // Para outros tipos, mostrar opção de abrir em nova aba
            showPreviewUnsupported(contentElement, url);
        }
    } catch (error) {
        showPreviewError(contentElement);
    }
}

function showPreviewError(contentElement) {
    contentElement.innerHTML = `
        <div class="text-center text-slate-500">
            <i class="fas fa-exclamation-triangle text-3xl mb-4 text-yellow-500"></i>
            <p class="mb-4">Não foi possível carregar o preview do documento.</p>
            <button onclick="window.open(document.getElementById('previewExternalLink').href, '_blank')" 
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">
                <i class="fas fa-external-link-alt mr-2"></i>
                Abrir em Nova Aba
            </button>
        </div>
    `;
}

function showPreviewUnsupported(contentElement, url) {
    contentElement.innerHTML = `
        <div class="text-center text-slate-500">
            <i class="fas fa-file text-3xl mb-4"></i>
            <p class="mb-4">Preview não disponível para este tipo de arquivo.</p>
            <button onclick="window.open('${url}', '_blank')" 
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">
                <i class="fas fa-external-link-alt mr-2"></i>
                Abrir Documento
            </button>
        </div>
    `;
}

function closeDocumentPreview() {
    const modal = document.getElementById('documentPreviewModal');
    if (modal) {
        modal.classList.remove('show');
        document.body.style.overflow = '';
        
        // Limpar conteúdo para liberar recursos
        setTimeout(() => {
            const contentElement = document.getElementById('previewContent');
            if (contentElement) {
                contentElement.innerHTML = '';
            }
        }, 300);
    }
}

// Event listeners para os modais
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar observer para scroll automático
    setupScrollObserver();
    
    // Definir primeiro item como ativo por padrão
    updateActiveMenuItem('porque-gostamos');
    
    const documentsModal = document.getElementById('documentsModal');
    const previewModal = document.getElementById('documentPreviewModal');
    
    // Event listeners para o modal de documentos
    if (documentsModal) {
        documentsModal.addEventListener('click', function(e) {
            if (e.target === documentsModal) {
                closeDocumentsModal();
            }
        });
        
        documentsModal.addEventListener('scroll', function(e) {
            e.stopPropagation();
        });
    }
    
    // Event listeners para o modal de preview
    if (previewModal) {
        previewModal.addEventListener('click', function(e) {
            if (e.target === previewModal) {
                closeDocumentPreview();
            }
        });
    }
    
    // Event listener global para ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            if (previewModal && previewModal.classList.contains('show')) {
                closeDocumentPreview();
            } else if (documentsModal && documentsModal.classList.contains('show')) {
                closeDocumentsModal();
            }
        }
    });
});

// Função para track de eventos (opcional - para analytics)
function trackDocumentView(documentName) {
    // Aqui você pode adicionar código de tracking/analytics
    console.log('Documento visualizado:', documentName);
}

function trackDocumentDownload(documentName) {
    // Aqui você pode adicionar código de tracking/analytics
    console.log('Documento baixado:', documentName);
}
</script>

<?php get_footer(); ?>