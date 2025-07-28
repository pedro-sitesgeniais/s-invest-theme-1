<?php
/**
 * Seção: Aportes do Investidor com filtro de período
 */

defined('ABSPATH') || exit;

$user_id = get_current_user_id();
?>

<div x-data="{
    dias: new URLSearchParams(window.location.search).get('dias') || '30',
    get url() { return `?painel=investidor&secao=aportes&dias=${this.dias}` }
  }"
  class="mb-6"
  role="region"
  aria-label="Filtros de período dos aportes"
>
  <div class="flex flex-wrap gap-2 mb-4">
    <button @click="dias = '30'" :class="dias === '30' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-800'" :aria-pressed="dias === '30'" class="px-4 py-1 rounded text-sm">
      Últimos 30 dias
    </button>
    <button @click="dias = '60'" :class="dias === '60' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-800'" :aria-pressed="dias === '60'" class="px-4 py-1 rounded text-sm">
      Últimos 60 dias
    </button>
    <button @click="dias = 'todos'" :class="dias === 'todos' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-800'" :aria-pressed="dias === 'todos'" class="px-4 py-1 rounded text-sm">
      Todos os aportes
    </button>
  </div>
  <template x-init="$watch('dias', () => window.location.href = url)"></template>
</div>

<?php
// Captura dias da URL
$dias = isset($_GET['dias']) ? sanitize_text_field($_GET['dias']) : '30';
$data_limite = null;

if ($dias !== 'todos') {
  $dias_int = intval($dias);
  $data_limite = date('Y-m-d', strtotime("-{$dias_int} days"));
}

// Query dos aportes
$args = [
  'post_type'      => 'aporte',
  'posts_per_page' => -1,
  'post_status'    => 'publish',
  'meta_query'     => [
    [
      'key'     => 'investidor_id', // corrigido
      'value'   => $user_id,
      'compare' => '='
    ]
  ],
  'orderby' => 'date',
  'order'   => 'DESC'
];

$query = new WP_Query($args);
?>

<div role="list" aria-label="Lista de aportes do investidor" class="space-y-4">
  <?php
  $count = 0;

  if ($query->have_posts()) :
    while ($query->have_posts()) : $query->the_post();
      $valor = get_field('valor_aporte');
      $data = get_field('data_aporte');
      $investimento_id = get_field('investment_id'); // corrigido
      $investimento = $investimento_id ? get_the_title($investimento_id) : '—';

      // Filtro de data
      if ($data_limite && strtotime($data) < strtotime($data_limite)) continue;

      $count++;
  ?>
    <div class="p-4 bg-white rounded-lg shadow flex justify-between items-center" role="listitem">
      <div>
        <h2 class="text-sm font-semibold text-gray-800"><?= esc_html($investimento); ?></h2>
        <p class="text-xs text-gray-500">
          Valor: R$ <?= number_format($valor, 2, ',', '.'); ?> |
          Data: <?= date_i18n('d/m/Y', strtotime($data)); ?>
        </p>
      </div>
      <a href="<?= esc_url(get_permalink(get_the_ID())); ?>" class="text-xs bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700">Ver</a>
    </div>
  <?php
    endwhile;
    wp_reset_postdata();
  else :
    echo '<p class="text-sm text-gray-500">Nenhum aporte encontrado.</p>';
  endif;

  if ($count === 0) {
    echo '<p class="text-sm text-gray-500">Nenhum aporte encontrado neste período.</p>';
  }
  ?>
</div>
