<?php
/**
 * Componente: Filtros de Investimentos (estilo clean Hurst)
 */
defined('ABSPATH') || exit;

$tipos_produto = get_terms([
  'taxonomy'   => 'tipo_produto',
  'hide_empty' => false,
]);
?>
<div class="inline-flex flex-wrap items-center gap-6 p-6 rounded-xl">
  <span class="text-sm font-semibold text-gray-700 whitespace-nowrap">Filtros:</span>

  <!-- Classe de Ativos -->
  <div class="flex-shrink-0">
    <select
      x-model="filtros.tipo_produto"
      @change="aplicarFiltros"
      class="appearance-none bg-white border border-gray-300 rounded-2xl px-5 py-3 text-sm shadow-sm hover:border-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-200"
    >
      <option value="">Classe de Ativos</option>
      <?php foreach ( $tipos_produto as $tipo ) : ?>
        <option value="<?= esc_attr( $tipo->slug ) ?>">
          <?= esc_html( $tipo->name ) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <!-- Duração -->
  <div class="flex-shrink-0">
    <select
      x-model="filtros.prazo"
      @change="aplicarFiltros"
      class="appearance-none bg-white border border-gray-300 rounded-2xl px-5 py-3 text-sm shadow-sm hover:border-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-200"
    >
      <option value="">Duração</option>
      <option value="12">Maior ou igual a 12 meses</option>
      <option value="18">Maior ou igual a 18 meses</option>
      <option value="20">Maior ou igual a 20 meses</option>
      <option value="36">Maior ou igual a 36 meses</option>
      <option value="42">Maior ou igual a 42 meses</option>
    </select>
  </div>

  <!-- Valor (Rentabilidade) -->
  <div class="flex-shrink-0">
    <select
      x-model="filtros.valor"
      @change="aplicarFiltros"
      class="appearance-none bg-white border border-gray-300 rounded-2xl px-5 py-3 text-sm shadow-sm hover:border-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-200"
    >
      <option value="">Valor</option>
      <option value="18">Maior ou igual a 18.00% a.a</option>
      <option value="20">Maior ou igual a 20.00% a.a</option>
      <option value="22">Maior ou igual a 22.00% a.a</option>
    </select>
  </div>

  <!-- Ordenação -->
  <div class="flex-shrink-0">
    <select
      x-model="filtros.ordem"
      @change="aplicarFiltros"
      class="appearance-none bg-white border border-gray-300 rounded-2xl px-5 py-3 text-sm shadow-sm hover:border-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-200"
    >
      <option value="">Mais Recente</option>
      <option value="mais_antigo">Mais Antigo</option>
    </select>
  </div>

  <!-- Status de Captação -->
  <div class="flex-shrink-0">
    <select
      x-model="filtros.status"
      @change="aplicarFiltros"
      class="appearance-none bg-white border border-gray-300 rounded-2xl px-5 py-3 text-sm shadow-sm hover:border-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-200"
    >
      <option value="">Status</option>
      <option value="Aberto">Aberto</option>
      <option value="Encerrado">Encerrado</option>
    </select>
  </div>
</div>
