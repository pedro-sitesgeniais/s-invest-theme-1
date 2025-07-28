<?php
/**
 * Tela: Edição de Investimento no Painel do Associado
 */

defined('ABSPATH') || exit;

$investimento_id = isset($_GET['id']) ? absint($_GET['id']) : 0;
$post = get_post($investimento_id);

if (!$post || $post->post_type !== 'investment' || $post->post_author != get_current_user_id()) {
  echo '<div class="bg-red-100 border border-red-300 text-red-800 px-4 py-2 rounded">⛔ Investimento inválido ou não autorizado.</div>';
  return;
}

// Atualização
if (isset($_POST['atualizar_investimento'])) {
  wp_update_post([
    'ID'         => $investimento_id,
    'post_title' => sanitize_text_field($_POST['titulo']),
  ]);

  update_field('prazo_do_investimento', sanitize_text_field($_POST['prazo']), $investimento_id);
  update_field('rentabilidade', floatval($_POST['rentabilidade']), $investimento_id);
  update_field('valor_total', floatval($_POST['valor_total']), $investimento_id);
  update_field('aporte_minimo', floatval($_POST['aporte_minimo']), $investimento_id);
  update_field('risco', sanitize_text_field($_POST['risco']), $investimento_id);
  update_field('moeda_aceita', $_POST['moeda_aceita'], $investimento_id);
  update_field('data_inicio', sanitize_text_field($_POST['data_inicio']), $investimento_id);
  update_field('fim_captacao', sanitize_text_field($_POST['fim_captacao']), $investimento_id);

  echo '<div class="bg-green-100 border border-green-300 text-green-800 px-4 py-2 rounded mb-6">✅ Investimento atualizado com sucesso.</div>';
}

// Dados
$titulo = get_the_title($post);
$prazo = get_field('prazo_do_investimento', $post->ID);
$rentabilidade = get_field('rentabilidade', $post->ID);
$valor_total = get_field('valor_total', $post->ID);
$aporte_minimo = get_field('aporte_minimo', $post->ID);
$risco = get_field('risco', $post->ID);
$moedas = get_field('moeda_aceita', $post->ID);
$data_inicio = get_field('data_inicio', $post->ID);
$fim_captacao = get_field('fim_captacao', $post->ID);
?>

<div class="bg-white p-6 rounded shadow max-w-4xl mx-auto">
  <h2 class="text-xl font-bold text-gray-800 mb-4">✏️ Editar Investimento</h2>

  <form method="post" class="grid grid-cols-1 md:grid-cols-2 gap-4">

    <div>
      <label class="block text-sm font-medium text-gray-700">Título</label>
      <input type="text" name="titulo" value="<?= esc_attr($titulo) ?>" required class="w-full border rounded px-3 py-2" />
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700">Prazo</label>
      <input type="text" name="prazo" value="<?= esc_attr($prazo) ?>" class="w-full border rounded px-3 py-2" />
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700">Rentabilidade (%)</label>
      <input type="number" step="0.01" name="rentabilidade" value="<?= esc_attr($rentabilidade) ?>" class="w-full border rounded px-3 py-2" />
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700">Valor Total</label>
      <input type="number" step="0.01" name="valor_total" value="<?= esc_attr($valor_total) ?>" class="w-full border rounded px-3 py-2" />
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700">Aporte Mínimo</label>
      <input type="number" step="0.01" name="aporte_minimo" value="<?= esc_attr($aporte_minimo) ?>" class="w-full border rounded px-3 py-2" />
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700">Risco</label>
      <select name="risco" class="w-full border rounded px-3 py-2">
        <option value="">Selecione</option>
        <option value="baixo" <?= $risco === 'baixo' ? 'selected' : '' ?>>Baixo</option>
        <option value="medio" <?= $risco === 'medio' ? 'selected' : '' ?>>Médio</option>
        <option value="alto" <?= $risco === 'alto' ? 'selected' : '' ?>>Alto</option>
      </select>
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700">Moeda Aceita</label>
      <select name="moeda_aceita[]" multiple class="w-full border rounded px-3 py-2">
        <option value="BRL" <?= in_array('BRL', (array)$moedas) ? 'selected' : '' ?>>Real</option>
        <option value="USD" <?= in_array('USD', (array)$moedas) ? 'selected' : '' ?>>Dólar</option>
        <option value="EUR" <?= in_array('EUR', (array)$moedas) ? 'selected' : '' ?>>Euro</option>
      </select>
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700">Data de Início</label>
      <input type="date" name="data_inicio" value="<?= esc_attr($data_inicio) ?>" class="w-full border rounded px-3 py-2" />
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700">Fim da Captação</label>
      <input type="date" name="fim_captacao" value="<?= esc_attr($fim_captacao) ?>" class="w-full border rounded px-3 py-2" />
    </div>

    <div class="col-span-full">
      <button type="submit" name="atualizar_investimento" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 transition">
        💾 Salvar Alterações
      </button>
    </div>

  </form>
</div>
