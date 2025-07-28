<?php
/**
 * Bloco: Riscos do Investimento
 */
defined('ABSPATH') || exit;

$riscos = get_field('riscos');
?>

<div x-show="aba === 'riscos'" x-cloak class="bg-white p-6 rounded-lg shadow space-y-6">
  <h2 class="text-xl font-semibold text-gray-900">Riscos do Investimento</h2>
   
  <?php if ($riscos && is_array($riscos)) : ?>
    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
      <?php foreach ($riscos as $risco) :
        $titulo = esc_html($risco['titulo'] ?? '');
        $descricao = esc_html($risco['descricao'] ?? '');
      ?>
      <div class="bg-gray-100 p-5 rounded shadow-sm hover:shadow-md transition-all">
        <h3 class="text-base font-semibold text-gray-800 mb-2"><?= $titulo ?></h3>
        <p class="text-sm text-gray-700"><?= $descricao ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  <?php else : ?>
    <p class="text-gray-600 italic text-sm">Nenhum risco cadastrado para este investimento.</p>
  <?php endif; ?>
</div>
