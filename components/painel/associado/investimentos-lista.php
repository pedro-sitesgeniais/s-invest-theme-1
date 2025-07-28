<?php
/**
 * Tela: Investimentos no Painel do Associado (Cadastro + Listagem + Edição)
 */
defined('ABSPATH') || exit;

// Tratamento do formulário de novo investimento
if (isset($_POST['submit_investimento'])) {
  $post_id = wp_insert_post([
    'post_type'   => 'investment',
    'post_title'  => sanitize_text_field($_POST['titulo']),
    'post_status' => 'publish',
    'post_author' => get_current_user_id(),
  ]);

  if ($post_id && !is_wp_error($post_id)) {
    update_field('prazo_do_investimento', sanitize_text_field($_POST['prazo']), $post_id);
    update_field('rentabilidade', floatval($_POST['rentabilidade']), $post_id);
    update_field('valor_total', floatval($_POST['valor_total']), $post_id);
    update_field('aporte_minimo', floatval($_POST['aporte_minimo']), $post_id);
    echo '<div class="bg-green-100 border border-green-300 text-green-800 px-4 py-2 rounded mb-6">✅ Investimento cadastrado com sucesso.</div>';
  }
}
?>

<!-- Formulário de Cadastro -->
<div class="bg-white p-6 rounded shadow mb-8">
  <h2 class="text-xl font-bold text-gray-800 mb-4">Cadastrar Novo Investimento</h2>
  <form method="post" class="grid grid-cols-1 md:grid-cols-2 gap-4">

    <div>
      <label class="block text-sm font-medium text-gray-700">Título</label>
      <input type="text" name="titulo" required class="w-full border border-gray-300 rounded px-3 py-2" />
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700">Prazo</label>
      <input type="text" name="prazo" required class="w-full border border-gray-300 rounded px-3 py-2" />
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700">Rentabilidade (%)</label>
      <input type="number" name="rentabilidade" step="0.01" class="w-full border border-gray-300 rounded px-3 py-2" />
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700">Valor Total</label>
      <input type="number" name="valor_total" step="0.01" class="w-full border border-gray-300 rounded px-3 py-2" />
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700">Aporte Mínimo</label>
      <input type="number" name="aporte_minimo" step="0.01" class="w-full border border-gray-300 rounded px-3 py-2" />
    </div>

    <div class="col-span-full">
      <button type="submit" name="submit_investimento" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700">
        Salvar Investimento
      </button>
    </div>

  </form>
</div>

<!-- Listagem dos Investimentos -->
<h2 class="text-xl font-bold text-gray-800 mb-4">Meus Investimentos</h2>

<?php
$args = [
  'post_type'      => 'investment',
  'posts_per_page' => -1,
  'post_status'    => 'publish',
  'orderby'        => 'date',
  'order'          => 'DESC',
  'author'         => get_current_user_id()
];

$query = new WP_Query($args);

if ($query->have_posts()) :
  echo '<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">';
  while ($query->have_posts()) : $query->the_post();
    $prazo = get_field('prazo_do_investimento');
    $rentabilidade = get_field('rentabilidade');
    $risco = get_field('risco');
    $valor_total = (float) get_field('valor_total');
    $total_captado = (float) get_field('total_captado');
    $porcentagem = $valor_total > 0 ? min(100, ($total_captado / $valor_total) * 100) : 0;

    $status_badge = $porcentagem >= 100
      ? '<span class="inline-block bg-red-600 text-white text-xs font-semibold px-2 py-1 rounded">Encerrado</span>'
      : '<span class="inline-block bg-emerald-600 text-white text-xs font-semibold px-2 py-1 rounded">Aberto</span>';

    $risco_label = ucfirst($risco);
    $risco_class = match ($risco) {
      'baixo' => 'bg-green-100 text-green-800',
      'medio' => 'bg-yellow-100 text-yellow-800',
      'alto'  => 'bg-red-100 text-red-800',
      default => 'bg-gray-100 text-gray-600'
    };
    ?>
    <article class="bg-white rounded-xl shadow p-5 flex flex-col justify-between">
      <header>
        <div class="flex justify-between items-center mb-2">
          <h2 class="text-base font-bold text-gray-800"><?php the_title(); ?></h2>
          <?php echo $status_badge; ?>
        </div>
        <ul class="text-sm text-gray-700 space-y-1 mb-3">
          <li><strong>Prazo:</strong> <?php echo esc_html($prazo ?: '—'); ?></li>
          <li><strong>Rentabilidade:</strong> <?php echo esc_html($rentabilidade ?: '—'); ?></li>
          <li>
            <strong>Risco:</strong>
            <span class="inline-block px-2 py-0.5 text-xs font-semibold rounded <?php echo $risco_class; ?>">
              <?php echo esc_html($risco_label ?: '—'); ?>
            </span>
          </li>
        </ul>
        <div class="mb-2">
          <div class="flex justify-between text-xs text-gray-600 mb-1">
            <span>Captação</span>
            <span><?php echo number_format($porcentagem, 0); ?>%</span>
          </div>
          <div class="w-full bg-gray-200 h-2 rounded-full overflow-hidden">
            <div class="h-full bg-green-500 transition-all" style="width: <?php echo $porcentagem; ?>%"></div>
          </div>
        </div>
      </header>
      <footer class="pt-4">
      <h2 class="text-xl font-bold text-gray-800 mb-4">✏️ Editar: <?= esc_html($titulo) ?></h2>
      </footer>
    </article>
    <?php
  endwhile;
  echo '</div>';
  wp_reset_postdata();
else :
  echo '<p class="text-gray-500 text-sm">Nenhum investimento cadastrado ainda.</p>';
endif;
?>
