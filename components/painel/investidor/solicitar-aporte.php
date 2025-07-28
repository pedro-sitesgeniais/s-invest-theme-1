<?php
/**
 * Painel do Investidor - Solicitar Aporte
 */

defined('ABSPATH') || exit;

$current_user = wp_get_current_user();

if (!current_user_can('investidor')) {
  wp_die('Acesso restrito.');
}

// Buscar investimentos disponíveis
$investimentos = get_posts(array(
  'post_type'      => 'investment',
  'posts_per_page' => -1,
  'post_status'    => 'publish',
));
?>

<section class="p-6 bg-white rounded-xl shadow-md max-w-xl">
  <h2 class="text-2xl font-bold mb-6">Solicitar Aporte</h2>

  <?php if (isset($_POST['solicitar_aporte'])):

    if (!isset($_POST['solicitar_aporte_nonce']) || !wp_verify_nonce($_POST['solicitar_aporte_nonce'], 'solicitar_aporte_action')) {
      wp_die('Falha na verificação de segurança.');
    }

    $valor_aporte = isset($_POST['valor_aporte']) ? floatval(str_replace(',', '.', sanitize_text_field($_POST['valor_aporte']))) : 0;
    $investment_id = isset($_POST['investment_id']) ? intval($_POST['investment_id']) : 0;

    if ($valor_aporte <= 0 || $investment_id <= 0) {
      echo '<p class="text-red-600 mb-4">Preencha todos os campos corretamente.</p>';
    } else {
      $aporte_id = wp_insert_post(array(
        'post_title'   => 'Solicitação de Aporte',
        'post_type'    => 'aporte',
        'post_status'  => 'pending',
        'post_author'  => $current_user->ID,
      ));

      if (!is_wp_error($aporte_id)) {
        update_post_meta($aporte_id, 'valor_aporte', $valor_aporte);
        update_post_meta($aporte_id, 'investment_id', $investment_id);
        update_post_meta($aporte_id, 'investidor_id', $current_user->ID);
        echo '<p class="text-green-600 mb-4">Sua solicitação foi enviada com sucesso!</p>';
      } else {
        echo '<p class="text-red-600 mb-4">Erro ao registrar solicitação.</p>';
      }
    }
  endif;
  ?>

  <form method="post" class="grid gap-4">
    <?php wp_nonce_field('solicitar_aporte_action', 'solicitar_aporte_nonce'); ?>

    <!-- Projeto -->
    <div>
      <label class="block text-sm font-medium text-gray-700">Selecione o projeto</label>
      <select name="investment_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
        <option value="">Escolha um projeto</option>
        <?php foreach ($investimentos as $invest) : ?>
          <option value="<?php echo esc_attr($invest->ID); ?>"><?php echo esc_html($invest->post_title); ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <!-- Valor -->
    <div>
      <label class="block text-sm font-medium text-gray-700">Valor do aporte</label>
      <input type="text" name="valor_aporte" placeholder="Ex: 5000.00" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
    </div>

    <!-- Botão -->
    <div>
      <button type="submit" name="solicitar_aporte" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Enviar solicitação</button>
    </div>
  </form>
</section>
