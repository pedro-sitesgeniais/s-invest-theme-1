<?php
/**
 * Bloco: Motivos para Investir (aba)
 */
defined('ABSPATH') || exit;

$motivos = get_field('motivos');

if ($motivos && is_array($motivos)) :
?>
<div x-show="aba === 'motivos'" x-cloak class="bg-white p-6 rounded-lg shadow space-y-6">
  <h2 class="text-xl font-semibold text-gray-900">Motivos para Investir</h2>

  <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
    <?php foreach ($motivos as $item) :
      $titulo = esc_html($item['titulo'] ?? '');
      $descricao = esc_html($item['descricao'] ?? '');
    ?>
    <div class="bg-gray-100 p-5 rounded shadow-sm hover:shadow-md transition-all">
      <h3 class="text-base font-semibold text-gray-800 mb-2"><?= $titulo ?></h3>
      <p class="text-sm text-gray-700"><?= $descricao ?></p>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php else : ?>
<div x-show="aba === 'motivos'" x-cloak class="bg-white p-6 rounded-lg shadow">
  <p class="text-gray-600 italic text-sm">Nenhum motivo registrado para este investimento.</p>
</div>
<?php endif; ?>
