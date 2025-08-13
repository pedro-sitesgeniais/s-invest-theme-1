<?php
/**
 * Seção Documentos – Visão Geral de Investimentos
 * components/painel/investidor/documentos.php
 * 
 * Otimizado para: performance, segurança e experiência do usuário
 */
return; // Impede acesso direto ao arquivo
defined('ABSPATH') || exit;

// 1) Contexto de painel com sanitização reforçada
$painel = isset($_GET['painel']) ? sanitize_key($_GET['painel']) : 'investidor';
$user_id = get_current_user_id();

// 2) Coleta aportes com cache
$cache_key = 'user_' . $user_id . '_aportes';
$aportes = wp_cache_get($cache_key, 's-invest-theme');

if (false === $aportes) {
    $aportes = get_posts([
        'post_type'      => 'aporte',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'meta_query'     => [[ 'key' => 'investidor_id', 'value' => $user_id ]],
        'no_found_rows'  => true, // Otimização para queries sem paginação
    ]);
    wp_cache_set($cache_key, $aportes, 's-invest-theme', 6 * HOUR_IN_SECONDS);
}

// Processa IDs de investimentos únicos
$invest_ids = [];
if (!empty($aportes)) {
    foreach ($aportes as $ap) {
        $inv_id = get_field('investment_id', $ap->ID);
        if ($inv_id) {
            $invest_ids[] = $inv_id;
        }
    }
    $invest_ids = array_unique($invest_ids);
}

// 3) URL base segura
$panel_url = esc_url(home_url('/painel/'));
?>

<h1 class="text-2xl font-semibold mb-6 pt-10">Seus Investimentos</h1>

<?php if (!empty($invest_ids)) : ?>
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">

    <?php foreach ($invest_ids as $inv_id) :
        // Verifica se o post existe antes de continuar
        if (!get_post($inv_id)) continue;
        
        $titulo = esc_html(get_the_title($inv_id));
        $docs = get_field('documentos', $inv_id) ?: [];
        $docs_count = count($docs);
        
        // Gera links de forma segura
        $docs_link = esc_url(add_query_arg([
            'painel' => $painel,
            'secao' => 'documentos',
            'id' => $inv_id
        ], $panel_url));
        
        $details_link = esc_url(add_query_arg([
            'painel' => $painel,
            'secao' => 'detalhes-investimento',
            'id' => $inv_id
        ], $panel_url));
    ?>
      <div class="bg-white p-6 rounded-xl shadow flex flex-col justify-between transition-all duration-200 hover:shadow-md">
        <div>
          <h2 class="text-lg font-semibold mb-2"><?php echo $titulo; ?></h2>
          <p class="text-sm text-gray-500">
            <?php if ($docs_count) : ?>
              <?php echo sprintf(
                  _n('%d documento', '%d documentos', $docs_count, 's-invest'),
                  number_format_i18n($docs_count)
              ); ?>
            <?php else : ?>
              Nenhum documento disponível
            <?php endif; ?>
          </p>
        </div>
        <div class="mt-6 flex gap-2">
          <a href="<?php echo $docs_link; ?>"
             class="flex-1 px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 text-center text-sm transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
            Ver Documentos
          </a>
          <a href="<?php echo $details_link; ?>"
             class="flex-1 px-4 py-2 bg-gray-200 text-gray-800 rounded hover:bg-gray-300 text-center text-sm transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">
            Ver Detalhes
          </a>
        </div>
      </div>
    <?php endforeach; ?>

  </div>
<?php else : ?>
  <div class="bg-gray-50 rounded-lg p-8 text-center">
    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
    </svg>
    <h3 class="mt-2 text-sm font-medium text-gray-900">Nenhum investimento encontrado</h3>
    <p class="mt-1 text-sm text-gray-500">Você ainda não realizou aportes em nenhum investimento.</p>
  </div>
<?php endif; ?>