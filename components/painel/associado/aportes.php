<?php
/**
 * Aba: Cadastro e Listagem de Aportes (Painel do Associado)
 */
defined('ABSPATH') || exit;

// Processa o formulário
if (
  isset($_POST['submit_aporte']) &&
  isset($_POST['novo_aporte_nonce']) &&
  wp_verify_nonce($_POST['novo_aporte_nonce'], 'novo_aporte_action')
) {
  $valor_aporte     = floatval($_POST['valor_aporte']);
  $investment_id    = absint($_POST['investment_id']);
  $investidor_id    = absint($_POST['investidor_id']);
  $data_aporte      = sanitize_text_field($_POST['data_aporte']);
  $forma_pagamento  = sanitize_text_field($_POST['forma_pagamento']);
  $observacoes      = sanitize_textarea_field($_POST['observacoes']);

  $post_id = wp_insert_post([
    'post_type'   => 'aporte',
    'post_status' => 'publish',
    'post_title'  => 'Aporte - ' . date('d/m/Y H:i'),
    'post_author' => get_current_user_id()
  ]);

  if ($post_id && !is_wp_error($post_id)) {
    update_field('valor_aporte', $valor_aporte, $post_id);
    update_field('investment_id', $investment_id, $post_id);
    update_field('investidor_id', $investidor_id, $post_id);
    update_field('data_aporte', $data_aporte, $post_id);
    update_field('forma_pagamento', $forma_pagamento, $post_id);
    update_field('observacoes', $observacoes, $post_id);

    echo '<div class="bg-green-100 border border-green-300 text-green-800 px-4 py-2 rounded mb-6">✅ Aporte cadastrado com sucesso.</div>';
  }
}
?>

<!-- Formulário de Aporte -->
<div class="bg-white p-6 rounded shadow mb-8">
  <h2 class="text-xl font-bold text-gray-800 mb-4">💸 Novo Aporte</h2>
  <form method="post" class="grid grid-cols-1 md:grid-cols-2 gap-4">

    <div>
      <label class="block text-sm font-medium text-gray-700">Investimento</label>
      <select name="investment_id" required class="w-full border rounded px-3 py-2">
        <option value="">Selecione</option>
        <?php
        $investimentos = get_posts(['post_type' => 'investment', 'posts_per_page' => -1]);
        foreach ($investimentos as $inv) {
          echo '<option value="' . esc_attr($inv->ID) . '">' . esc_html($inv->post_title) . '</option>';
        }
        ?>
      </select>
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700">Investidor</label>
      <select name="investidor_id" required class="w-full border rounded px-3 py-2">
        <option value="">Selecione</option>
        <?php
        $users = get_users(['role' => 'investidor']);
        foreach ($users as $user) {
          echo '<option value="' . esc_attr($user->ID) . '">' . esc_html($user->display_name) . '</option>';
        }
        ?>
      </select>
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700">Valor do Aporte</label>
      <input type="number" step="0.01" name="valor_aporte" required class="w-full border rounded px-3 py-2" />
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700">Data do Aporte</label>
      <input type="date" name="data_aporte" required class="w-full border rounded px-3 py-2" />
    </div>

    <div class="md:col-span-2">
      <label class="block text-sm font-medium text-gray-700">Forma de Pagamento</label>
      <input type="text" name="forma_pagamento" placeholder="Ex: Pix, TED, Boleto..." class="w-full border rounded px-3 py-2" />
    </div>

    <div class="md:col-span-2">
      <label class="block text-sm font-medium text-gray-700">Observações</label>
      <textarea name="observacoes" rows="3" class="w-full border rounded px-3 py-2"></textarea>
    </div>

    <div class="col-span-full">
      <?php wp_nonce_field('novo_aporte_action', 'novo_aporte_nonce'); ?>
      <button type="submit" name="submit_aporte" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 transition">
        Salvar Aporte
      </button>
    </div>
  </form>
</div>

<!-- Listagem de Aportes -->
<h2 class="text-xl font-bold text-gray-800 mb-4">📋 Aportes Cadastrados</h2>

<?php
$aportes = get_posts(['post_type' => 'aporte', 'posts_per_page' => -1, 'orderby' => 'date', 'order' => 'DESC']);
if ($aportes) :
?>
<div class="overflow-x-auto bg-white rounded-xl shadow p-4">
  <table class="min-w-full text-sm text-left text-gray-700">
    <thead class="text-xs text-gray-500 uppercase bg-gray-100">
      <tr>
        <th class="px-4 py-2">Investimento</th>
        <th class="px-4 py-2">Investidor</th>
        <th class="px-4 py-2">Valor</th>
        <th class="px-4 py-2">Data</th>
        <th class="px-4 py-2">Pagamento</th>
      </tr>
    </thead>
    <tbody class="divide-y divide-gray-200">
      <?php foreach ($aportes as $aporte) :
        $valor = get_field('valor_aporte', $aporte->ID);
        $investimento = get_field('investment_id', $aporte->ID);
        $investidor = get_field('investidor_id', $aporte->ID);
        $data = get_field('data_aporte', $aporte->ID);
        $forma = get_field('forma_pagamento', $aporte->ID);

        $nome_investidor = get_user_by('id', $investidor);
        $nome_investidor = $nome_investidor ? $nome_investidor->display_name : '—';

        $nome_investimento = get_the_title($investimento) ?: '—';
      ?>
      <tr>
        <td class="px-4 py-3"><?php echo esc_html($nome_investimento); ?></td>
        <td class="px-4 py-3"><?php echo esc_html($nome_investidor); ?></td>
        <td class="px-4 py-3">R$ <?php echo number_format($valor, 2, ',', '.'); ?></td>
        <td class="px-4 py-3"><?php echo esc_html($data); ?></td>
        <td class="px-4 py-3"><?php echo esc_html($forma); ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php else : ?>
  <p class="text-sm text-gray-500">Nenhum aporte registrado ainda.</p>
<?php endif; ?>
