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

<main class="pt-30 bg-gradient-to-b from-slate-900 to-slate-800 text-white">
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

  <!-- SEÇÃO DE NAVEGAÇÃO -->
  <section class="bg-slate-800 py-8">
    <!-- NAVEGAÇÃO COM ABAS SIMPLES -->
  <div class="max-w-[1440px] mx-auto px-4 py-4">
    <div class="flex flex-wrap items-center justify-center gap-4">
      <!-- Abas de Navegação -->
      <div class="flex bg-slate-800/50 rounded-lg p-1 gap-1">
        <a href="#porque-gostamos" class="nav-tab px-4 py-2 rounded-md text-white hover:bg-slate-700 transition-colors flex items-center gap-2 text-sm font-medium">
          <i class="fas fa-chart-line"></i>
          Análise
        </a>
        <a href="#riscos" class="nav-tab px-4 py-2 rounded-md text-white hover:bg-slate-700 transition-colors flex items-center gap-2 text-sm font-medium">
          <i class="fas fa-shield-alt"></i>
          Riscos
        </a>
        <a href="#disclaimer" class="nav-tab px-4 py-2 rounded-md text-white hover:bg-slate-700 transition-colors flex items-center gap-2 text-sm font-medium">
          <i class="fas fa-info-circle"></i>
          Disclaimer
        </a>
      </div>
      
      <!-- Botão de Documentos -->
      <?php if ( ! empty($documentos) && is_array($documentos) ) : ?>
      <button onclick="openDocumentsModal()"
              class="bg-secondary hover:bg-secondary/90 text-primary px-4 py-2 rounded-lg transition-colors flex items-center gap-2 text-sm font-medium">
        <i class="fas fa-folder-open"></i>
        Documentos
      </button>
      <?php endif; ?>
    </div>
  </section>
  
  <!-- SEÇÃO: ANÁLISE DO INVESTIMENTO - FUNDO BRANCO -->
  <section id="porque-gostamos" class="bg-slate-100 text-slate-800 py-16">
    <div class="max-w-[1440px] mx-auto px-4">
      <div class="max-w-6xl mx-auto">
          <h2 class="md:text-2xl font-bold mb-8 flex items-center gap-3 text-primary">
            <i class="fas fa-chart-line text-secondary"></i>
            Porque gostamos deste ativo?
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
            <div class="bg-slate-50 p-6 rounded-xl border border-slate-200 hover:border-secondary/50 transition-all section-card shadow-sm">
              <div class="flex items-start gap-4">
                <div class="w-12 h-12 bg-secondary/20 rounded-lg flex items-center justify-center flex-shrink-0">
                  <i class="<?= esc_attr($icone) ?> text-secondary text-xl"></i>
                </div>
                <div>
                  <h3 class="text-lg font-semibold text-slate-800 mb-2"><?= esc_html($titulo) ?></h3>
                  <p class="text-slate-600 leading-relaxed"><?= esc_html($descricao) ?></p>
                </div>
              </div>
            </div>
            <?php 
              endforeach;
            else :
            ?>
            <div class="col-span-full">
              <div class="bg-slate-50 p-8 rounded-xl border border-slate-200 text-center">
                <i class="fas fa-info-circle text-slate-400 text-3xl mb-4"></i>
                <p class="text-slate-600">Os motivos para este investimento serão adicionados em breve.</p>
              </div>
            </div>
            <?php endif; ?>
          </div>
      </div>
    </div>
  </section>
      
  <!-- SEÇÃO: RISCOS-->
  <section id="riscos" class="bg-slate-100 text-slate-800 py-16">
    <div class="max-w-[1440px] mx-auto px-4">
      <div class="max-w-6xl mx-auto">
          <h2 class="md:text-2xl font-bold mb-8 flex items-center gap-3 text-primary">
            <i class="fas fa-shield-alt text-yellow-600"></i>
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
            <div class="bg-slate-50 p-6 rounded-xl border border-slate-200 hover:border-yellow-500/50 transition-all section-card shadow-sm">
              <div class="flex items-start gap-4">
                <div class="w-12 h-12 bg-yellow-500/20 rounded-lg flex items-center justify-center flex-shrink-0">
                  <i class="fas fa-exclamation-triangle text-yellow-600 text-xl"></i>
                </div>
                <div class="flex-1">
                  <div class="flex items-center gap-3 mb-2">
                    <h3 class="text-lg font-semibold text-slate-800"><?= esc_html($titulo) ?></h3>
                    <span class="px-2 py-1 text-xs font-medium rounded-full border <?= $classe_nivel ?>">
                      <?= ucfirst($nivel) ?>
                    </span>
                  </div>
                  <p class="text-slate-600 leading-relaxed"><?= esc_html($descricao) ?></p>
                </div>
              </div>
            </div>
            <?php 
              endforeach;
            else :
            ?>
            <div class="col-span-full">
              <div class="bg-slate-50 p-8 rounded-xl border border-slate-200 text-center">
                <i class="fas fa-shield-alt text-slate-400 text-3xl mb-4"></i>
                <p class="text-slate-600">Os riscos específicos desta operação serão detalhados em breve.</p>
              </div>
            </div>
            <?php endif; ?>
          </div>
      </div>
    </div>
  </section>
      
  <!-- SEÇÃO: DISCLAIMER - FUNDO CLARO -->
  <section id="disclaimer" class="bg-slate-900 text-white py-16">
    <div class="max-w-[1440px] mx-auto px-4">
      <div class="max-w-6xl mx-auto">
          <div class="bg-slate-800 border border-slate-700 p-8 rounded-xl shadow-lg">
            <h2 class="text-2xl font-bold mb-6 flex items-center gap-3 text-white">
              <i class="fas fa-info-circle"></i>
              Disclaimer
            </h2>
            
            <div class="space-y-6 text-white leading-relaxed">
              
              <!-- Seção 1: Caráter Informativo -->
              <div class="space-y-3">
                <p>
                  <strong class="text-secondary">Caráter Informativo:</strong> Esta apresentação foi preparada pela Bravaforte, com caráter meramente informativo. Qualquer oferta de investimento só poderá ser feita de acordo com o contrato de SCP, que poderá conter informações relevantes não aqui contidas (incluindo certos riscos).
                </p>
                
                <p>
                  Antes de decidir participar, os potenciais parceiros devem prestar especial atenção aos <strong class="text-gray-300">fatores de risco</strong>, incluindo, mas não se limitando a: cenário macroeconômico, prazo de lançamento, prazo de entrega, velocidade de vendas, valor de vendas, prazo de distribuição de dividendos, taxas e tributos.
                </p>
              </div>

              <!-- Seção 2: Garantias e Projeções -->
              <div class="space-y-3 pt-4 border-t border-slate-600">
                <p>
                  <strong class="text-secondary">Garantias:</strong> Esta apresentação não implica qualquer garantia com relação às informações aqui contidas, e as expectativas de retornos futuros e/ou valor principal integralizado, podendo variar de acordo com as condições econômicas, de mercado, tributárias, entre outros fatores. <strong class="text-gray-300">O desempenho passado não é garantia de resultados futuros.</strong>
                </p>
                
                <p>
                  Esta apresentação contém dados históricos e projeções feitas sob determinadas premissas. Embora a Bravaforte acredite que estas projeções são razoáveis e viáveis, não garantimos que sejam precisas ou válidas nas condições reais de mercado ou que todos os fatores de risco relevantes tenham sido esgotados.
                </p>
              </div>

              <!-- Seção 3: Confidencialidade -->
              <div class="space-y-3 pt-4 border-t border-slate-600">
                <p>
                  <strong class="text-secondary">Confidencialidade:</strong> A Bravaforte reserva-se o direito de alterar o conteúdo desta apresentação a qualquer momento, sem aviso prévio. Esta apresentação é confidencial e só deve ser visualizada por seus destinatários. Não poderá ser divulgada, distribuída, reproduzida ou copiada sem autorização expressa da Bravaforte.
                </p>
              </div>

              <!-- Seção 4: Natureza Jurídica -->
              <div class="space-y-3 pt-4 border-t border-slate-600">
                <p>
                  <strong class="text-secondary">Natureza Jurídica:</strong> Esta apresentação não constitui e nem deve ser interpretada como oferta pública de valores mobiliários, nos termos da Lei nº 6.385/76 e da Resolução CVM nº 88/2022. É destinada exclusivamente a parceiros estratégicos, em ambiente restrito de apresentação institucional.
                </p>
                
                <p>
                  A relação jurídica entre as partes se dá por meio de contrato de <strong class="text-gray-300">Sociedade em Conta de Participação (SCP)</strong>, de natureza associativa, sem garantias de rendimento ou retorno fixo.
                </p>
              </div>

              <!-- Seção 5: Tributação e Proteção de Dados -->
              <div class="space-y-3 pt-4 border-t border-slate-600">
                <p>
                  <strong class="text-secondary">Tributação:</strong> A eventual incidência de tributos será determinada conforme a legislação vigente aplicável a cada participante, sendo recomendável consulta a assessoria contábil ou jurídica.
                </p>
                
                <p>
                  <strong class="text-secondary">Proteção de Dados:</strong> Este material está protegido pela <strong class="text-gray-300">LGPD</strong> (Lei Geral de Proteção de Dados Pessoais).
                </p>
              </div>
              
              <!-- Footer -->
              <div class="text-xs text-gray-300 pt-6 border-t border-slate-600 text-center">
                <p>
                  <strong class="text-gray-200">Bravaforte Investimentos</strong><br>
                </p>
              </div>
            </div>
          </div>
      </div>
    </div>
  </section>

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
              <!-- Layout mobile: stacked -->
              <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                <div class="flex items-center space-x-4 flex-1">
                  <!-- Ícone do Arquivo -->
                  <div class="flex-shrink-0">
                    <div class="w-12 h-12 bg-slate-800 rounded-lg flex items-center justify-center">
                      <i class="<?= $icone ?> text-xl"></i>
                    </div>
                  </div>
                  
                  <!-- Informações do Arquivo -->
                  <div class="flex-1 min-w-0">
                    <h3 class="text-white font-semibold text-lg leading-tight mb-1">
                      <?= esc_html($titulo) ?>
                    </h3>
                    <div class="flex flex-col sm:flex-row sm:items-center text-sm text-slate-400 gap-1 sm:gap-3">
                      <span class="break-words"><?= esc_html($nome_arquivo) ?></span>
                      <?php if ($tamanho_formatado) : ?>
                        <span class="hidden sm:inline">•</span>
                        <span><?= esc_html($tamanho_formatado) ?></span>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
                
                <!-- Botão de Ação - Mobile: largura total, Desktop: compact -->
                <div class="flex items-center justify-center sm:ml-4">
                  <button onclick="openDocumentPreview('<?= esc_js($url) ?>', '<?= esc_js($titulo) ?>', '<?= esc_js($tipo) ?>')" 
                          class="inline-flex items-center justify-center w-full sm:w-12 h-10 sm:h-12 px-4 sm:px-0 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors text-sm font-medium"
                          title="Visualizar documento">
                    <i class="fas fa-eye mr-2 sm:mr-0"></i>
                    <span class="sm:hidden">Visualizar Documento</span>
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
       class="fixed inset-0 z-999 flex items-center justify-center bg-black/90 backdrop-blur-sm opacity-0 invisible transition-all duration-300 p-2 sm:p-4">
    <!-- Container do Modal de Preview -->
    <div class="modal-content bg-slate-900 rounded-xl shadow-2xl border border-slate-700 w-full max-w-6xl max-h-[98vh] sm:max-h-[95vh] overflow-hidden transform scale-95 transition-transform duration-300"
         onclick="event.stopPropagation()">
      
      <!-- Header do Preview -->
      <div class="flex items-center justify-between p-3 sm:p-4 border-b border-slate-700 bg-slate-800">
        <div class="flex items-center space-x-3 flex-1 min-w-0">
          <i class="fas fa-file-alt text-blue-400 flex-shrink-0"></i>
          <div class="min-w-0 flex-1">
            <h3 class="text-base sm:text-lg font-semibold text-white truncate" id="previewDocumentTitle">Visualizar Documento</h3>
            <p class="text-xs sm:text-sm text-slate-400" id="previewDocumentType">PDF</p>
          </div>
        </div>
        <div class="flex items-center ml-2">
          <button onclick="closeDocumentPreview()" 
                  class="text-slate-400 hover:text-white text-lg sm:text-xl transition-colors p-2 hover:bg-slate-700 rounded-lg flex-shrink-0">
            <i class="fas fa-times"></i>
          </button>
        </div>
      </div>
      
      <!-- Corpo do Preview -->
      <div class="p-0 h-[calc(98vh-100px)] sm:h-[calc(95vh-80px)] bg-white">
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
    z-index: 999;
}

/* Proteções do Modal de Preview - apenas no conteúdo do documento */
#documentPreviewModal #previewContent * {
    -webkit-user-select: none !important;
    -moz-user-select: none !important;
    -ms-user-select: none !important;
    user-select: none !important;
    -webkit-touch-callout: none !important;
    -webkit-tap-highlight-color: transparent !important;
}

#documentPreviewModal button {
    pointer-events: auto !important;
}

#documentPreviewModal iframe {
    -webkit-user-select: none !important;
    -moz-user-select: none !important;
    -ms-user-select: none !important;
    user-select: none !important;
}

/* Desabilitar print para o modal */
@media print {
    #documentPreviewModal {
        display: none !important;
    }
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
    #documentPreviewModal {
        padding: 0.5rem;
    }
    
    #documentPreviewModal .modal-content {
        max-width: 100%;
        max-height: 98vh;
        border-radius: 0.75rem;
    }
    
    #documentPreviewModal iframe {
        min-height: 70vh;
    }
    
    /* Melhor scroll no mobile */
    #documentPreviewModal #previewContent {
        -webkit-overflow-scrolling: touch;
        overflow-y: auto;
    }
    
    /* Ajustes para área de toque */
    #documentPreviewModal button {
        min-height: 44px;
        min-width: 44px;
    }
}

/* Scroll suave para toda a página */
html {
    scroll-behavior: smooth;
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

/* Estilos da navegação com abas */
.nav-tab {
    transition: all 0.3s ease;
}

.nav-tab:hover {
    background: rgba(100, 116, 139, 0.3);
    transform: translateY(-1px);
}

.nav-tab.active {
    background: linear-gradient(135deg, #2ED2F8, #1e40af);
    color: #000E35;
    font-weight: 600;
}

/* Animação suave ao clicar */
.nav-tab:active {
    transform: translateY(0);
}

/* Estilos para seções com fundos alternados */
.section-white {
    background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
}

.section-dark {
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
}

/* Cards com melhor contraste */
.card-light {
    background: rgba(248, 250, 252, 0.8);
    border: 1px solid rgba(148, 163, 184, 0.2);
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.1);
}

.card-light:hover {
    background: rgba(241, 245, 249, 0.9);
    border-color: rgba(46, 210, 248, 0.3);
    box-shadow: 0 4px 12px rgba(15, 23, 42, 0.15);
}

/* Melhor legibilidade para textos */
.text-readable {
    line-height: 1.7;
    font-size: 16px;
}
</style>

<script>
// Funções para a navegação com abas
function scrollToSection(sectionId) {
    const element = document.getElementById(sectionId);
    if (element) {
        const offset = 80;
        const elementPosition = element.offsetTop - offset;
        
        window.scrollTo({
            top: elementPosition,
            behavior: 'smooth'
        });
    }
}

function updateActiveNavItem() {
    const sections = document.querySelectorAll('section[id]');
    const navItems = document.querySelectorAll('.nav-tab');
    
    let currentSection = '';
    
    sections.forEach(section => {
        const sectionTop = section.offsetTop - 100;
        const sectionBottom = sectionTop + section.offsetHeight;
        
        if (window.scrollY >= sectionTop && window.scrollY < sectionBottom) {
            currentSection = section.id;
        }
    });
    
    navItems.forEach(item => {
        item.classList.remove('active');
        const href = item.getAttribute('href');
        if (href === `#${currentSection}`) {
            item.classList.add('active');
        }
    });
}

// Event listeners para a navegação com abas
function setupTabNav() {
    const navItems = document.querySelectorAll('.nav-tab');
    
    navItems.forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href').substring(1);
            scrollToSection(targetId);
        });
    });
    
    // Observer para atualizar item ativo baseado no scroll
    window.addEventListener('scroll', updateActiveNavItem);
    
    // Definir primeiro item como ativo inicialmente
    setTimeout(() => {
        updateActiveNavItem();
    }, 100);
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
    
    if (modal && contentElement) {
        // Atualizar informações do header
        if (titleElement) titleElement.textContent = title || 'Documento';
        if (typeElement) typeElement.textContent = type.toUpperCase() || 'ARQUIVO';
        
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
        
        // Ativar proteções do modal
        activateModalProtections();
        
        // Carregar conteúdo baseado no tipo
        setTimeout(() => {
            loadDocumentContent(url, type, contentElement);
        }, 500);
    }
}

function loadDocumentContent(url, type, contentElement) {
    const lowerType = type.toLowerCase();
    const isMobile = window.innerWidth <= 768 || /Android|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    
    try {
        if (lowerType === 'pdf') {
            if (isMobile) {
                // No mobile, usar Google Docs Viewer diretamente
                contentElement.innerHTML = `
                    <iframe src="https://docs.google.com/gview?url=${encodeURIComponent(url)}&embedded=true" 
                            onload="this.style.opacity=1; handlePdfLoadSuccess()" 
                            onerror="handlePdfLoadError(this.parentElement)"
                            style="opacity:0; transition: opacity 0.3s">
                    </iframe>
                `;
            } else {
                // Desktop: tentar PDF direto primeiro, fallback para Google Docs
                contentElement.innerHTML = `
                    <iframe src="${url}#toolbar=0&navpanes=0&scrollbar=0" 
                            onload="this.style.opacity=1; handlePdfLoadSuccess()" 
                            onerror="this.src='https://docs.google.com/gview?url=${encodeURIComponent(url)}&embedded=true'"
                            style="opacity:0; transition: opacity 0.3s">
                    </iframe>
                `;
            }
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
        <div class="text-center text-slate-500 p-6">
            <i class="fas fa-exclamation-triangle text-3xl mb-4 text-yellow-500"></i>
            <p class="mb-4">Não foi possível carregar o preview do documento.</p>
            <p class="text-sm text-gray-400">Entre em contato com o suporte para acessar este documento.</p>
        </div>
    `;
}

// Função para lidar com sucesso no carregamento
function handlePdfLoadSuccess() {
    console.log('PDF carregado com sucesso');
}

// Função específica para erro de PDF
function handlePdfLoadError(contentElement) {
    const isMobile = window.innerWidth <= 768 || /Android|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    
    contentElement.innerHTML = `
        <div class="text-center text-slate-500 p-6">
            <i class="fas fa-file-pdf text-3xl mb-4 text-red-500"></i>
            <p class="mb-4">Não foi possível exibir o documento PDF.</p>
            ${isMobile ? 
                '<p class="text-sm text-gray-400 mb-4">Alguns dispositivos móveis têm limitações para visualizar PDFs no navegador.</p>' :
                '<p class="text-sm text-gray-400 mb-4">Tente atualizar a página ou entre em contato com o suporte.</p>'
            }
            <button onclick="retryPdfLoad(this.parentElement.parentElement)" 
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors text-sm">
                <i class="fas fa-redo mr-2"></i>
                Tentar Novamente
            </button>
        </div>
    `;
}

// Função para tentar recarregar o PDF
function retryPdfLoad(contentElement) {
    const modal = document.getElementById('documentPreviewModal');
    const titleElement = document.getElementById('previewDocumentTitle');
    const title = titleElement ? titleElement.textContent : 'Documento';
    
    // Simular recarregamento
    contentElement.innerHTML = `
        <div class="text-center text-slate-500 p-6">
            <i class="fas fa-spinner fa-spin text-3xl mb-4"></i>
            <p>Recarregando documento...</p>
        </div>
    `;
    
    // Tentar recarregar após 1 segundo
    setTimeout(() => {
        showPreviewError(contentElement);
    }, 2000);
}

function showPreviewUnsupported(contentElement, url) {
    contentElement.innerHTML = `
        <div class="text-center text-slate-500">
            <i class="fas fa-file text-3xl mb-4"></i>
            <p class="mb-4">Preview não disponível para este tipo de arquivo.</p>
            <p class="text-sm text-gray-400">Entre em contato com o suporte para acessar este documento.</p>
        </div>
    `;
}

function closeDocumentPreview() {
    const modal = document.getElementById('documentPreviewModal');
    if (modal) {
        modal.classList.remove('show');
        document.body.style.overflow = '';
        
        // Desativar proteções do modal
        deactivateModalProtections();
        
        // Limpar conteúdo para liberar recursos
        setTimeout(() => {
            const contentElement = document.getElementById('previewContent');
            if (contentElement) {
                contentElement.innerHTML = '';
            }
        }, 300);
    }
}

// Variáveis globais para gerenciar eventos
let modalProtectionActive = false;
let originalOnContextMenu = null;
let originalOnKeyDown = null;

// Função para ativar proteções do modal
function activateModalProtections() {
    if (modalProtectionActive) return;
    modalProtectionActive = true;
    
    // Salvar handlers originais
    originalOnContextMenu = document.oncontextmenu;
    originalOnKeyDown = document.onkeydown;
    
    // Desabilitar menu de contexto
    document.oncontextmenu = function(e) {
        if (document.getElementById('documentPreviewModal').classList.contains('show')) {
            e.preventDefault();
            return false;
        }
        return originalOnContextMenu ? originalOnContextMenu(e) : true;
    };
    
    // Desabilitar atalhos de teclado
    document.onkeydown = function(e) {
        if (document.getElementById('documentPreviewModal').classList.contains('show')) {
            // Bloquear Ctrl+S (salvar), Ctrl+P (imprimir), Ctrl+A (selecionar tudo), 
            // Ctrl+C (copiar), Ctrl+V (colar), Ctrl+X (cortar), F12 (dev tools), 
            // Ctrl+Shift+I (dev tools), Ctrl+U (view source), Print Screen
            if (
                (e.ctrlKey && (e.keyCode === 83 || e.keyCode === 80 || e.keyCode === 65 || 
                              e.keyCode === 67 || e.keyCode === 86 || e.keyCode === 88 || 
                              e.keyCode === 85)) ||
                e.keyCode === 123 || // F12
                (e.ctrlKey && e.shiftKey && e.keyCode === 73) || // Ctrl+Shift+I
                e.keyCode === 44 || // Print Screen
                (e.ctrlKey && e.shiftKey && e.keyCode === 67) || // Ctrl+Shift+C
                (e.ctrlKey && e.shiftKey && e.keyCode === 74) // Ctrl+Shift+J
            ) {
                e.preventDefault();
                return false;
            }
            
            // Permitir apenas ESC para fechar
            if (e.keyCode === 27) {
                closeDocumentPreview();
                return false;
            }
        }
        return originalOnKeyDown ? originalOnKeyDown(e) : true;
    };
    
    // Desabilitar seleção de texto apenas no conteúdo do preview
    document.onselectstart = function(e) {
        if (document.getElementById('documentPreviewModal').classList.contains('show')) {
            // Permitir seleção em elementos de interface (botões, headers)
            if (e.target && (e.target.closest('button') || e.target.closest('.bg-slate-800'))) {
                return true;
            }
            // Bloquear seleção no conteúdo do documento
            if (e.target && e.target.closest('#previewContent')) {
                return false;
            }
        }
        return true;
    };
    
    // Desabilitar drag apenas no conteúdo do preview
    document.ondragstart = function(e) {
        if (document.getElementById('documentPreviewModal').classList.contains('show')) {
            if (e.target && e.target.closest('#previewContent')) {
                return false;
            }
        }
        return true;
    };
}

// Função para desativar proteções do modal
function deactivateModalProtections() {
    if (!modalProtectionActive) return;
    modalProtectionActive = false;
    
    // Restaurar handlers originais
    document.oncontextmenu = originalOnContextMenu;
    document.onkeydown = originalOnKeyDown;
    document.onselectstart = null;
    document.ondragstart = null;
}

// Event listeners para os modais
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar navegação com abas
    setupTabNav();
    
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