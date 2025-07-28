<!-- Aba: Cenários Projetados -->
<div x-show="aba === 'cenarios'" x-cloak class="bg-white p-6 rounded-lg shadow space-y-6">
  <h2 class="text-xl font-bold mb-4">Cenários Projetados</h2>

  <?php
  // Verifica quais cenários têm dados
  $cenario_base = get_field('cenario_base');
  $cenario_otimista = get_field('cenario_otimista');
  $cenario_pessimista = get_field('cenario_pessimista');
  
  // Declara a função apenas se não existir
  if (!function_exists('cenario_tem_dados')) {
      function cenario_tem_dados($cenario) {
          if (!$cenario) return false;
          $campos = ['exposicao_maxima', 'rentabilidade_cdi', 'prazo', 'rentabilidade_tir'];
          foreach ($campos as $campo) {
              if (!empty($cenario[$campo])) return true;
          }
          return false;
      }
  }
  
  $cenarios_disponiveis = [];
  if (cenario_tem_dados($cenario_base)) $cenarios_disponiveis[] = 'base';
  if (cenario_tem_dados($cenario_otimista)) $cenarios_disponiveis[] = 'otimista';
  if (cenario_tem_dados($cenario_pessimista)) $cenarios_disponiveis[] = 'pessimista';
  
  if (empty($cenarios_disponiveis)) {
      echo '<p class="text-gray-600 italic">Nenhum cenário disponível no momento.</p>';
  } else {
      $primeiro_cenario = $cenarios_disponiveis[0];
      ?>

  <div x-data="{ tab: '<?= $primeiro_cenario ?>', valor: 10000 }" class="space-y-6">

    <!-- Tabs Internas -->
    <div class="flex gap-3">
      <?php foreach ($cenarios_disponiveis as $cenario) : 
        $labels = ['base' => 'Base', 'otimista' => 'Otimista', 'pessimista' => 'Pessimista'];
      ?>
        <button
          @click="tab = '<?= $cenario ?>'"
          :class="{ 'bg-secondary text-primary': tab === '<?= $cenario ?>' }"
          class="px-4 py-2 rounded border border-gray-300 text-sm font-semibold capitalize transition hover:bg-accent hover:text-white"
        >
          <?= $labels[$cenario] ?>
        </button>
      <?php endforeach; ?>
    </div>

    <!-- Tabelas dos Cenários -->
    <?php foreach ($cenarios_disponiveis as $prefixo) : 
      $cenario_data = get_field("cenario_{$prefixo}");
    ?>
    <div x-show="tab === '<?= $prefixo ?>'" x-cloak class="border border-gray-200 rounded-lg overflow-hidden shadow-sm">
      <table class="w-full text-sm">
        <tbody>
          <?php if (!empty($cenario_data['exposicao_maxima'])) : ?>
          <tr class="bg-gray-50">
            <th class="p-3 font-medium text-left w-1/2">Exposição Máxima</th>
            <td class="p-3"><?= esc_html($cenario_data['exposicao_maxima']); ?></td>
          </tr>
          <?php endif; ?>
          
          <?php if (!empty($cenario_data['rentabilidade_cdi'])) : ?>
          <tr>
            <th class="p-3 font-medium text-left">Rentabilidade (%CDI)</th>
            <td class="p-3"><?= esc_html($cenario_data['rentabilidade_cdi']); ?>%</td>
          </tr>
          <?php endif; ?>
          
          <?php if (!empty($cenario_data['prazo'])) : ?>
          <tr class="bg-gray-50">
            <th class="p-3 font-medium text-left">Prazo</th>
            <td class="p-3"><?= esc_html($cenario_data['prazo']); ?></td>
          </tr>
          <?php endif; ?>
          
          <?php if (!empty($cenario_data['rentabilidade_tir'])) : ?>
          <tr>
            <th class="p-3 font-medium text-left">Rentabilidade (%TIR)</th>
            <td class="p-3"><?= esc_html($cenario_data['rentabilidade_tir']); ?>%</td>
          </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
    <?php endforeach; ?>

    <!-- Simulador (se existir multiplicador) -->
    <?php $multiplicador = get_field('multiplicador_simulador'); ?>
    <?php if (!empty($multiplicador)) : ?>
    <div class="bg-gray-50 border border-gray-200 p-4 rounded-lg">
      <h3 class="font-semibold text-sm mb-3">💰 Simulador de Rentabilidade</h3>
      <div class="flex flex-wrap items-center gap-3 text-sm mb-2">
        <label for="valor" class="text-gray-600">Valor investido:</label>
        <input
          type="number"
          id="valor"
          x-model.number="valor"
          class="w-32 px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring focus:ring-blue-300"
          min="0"
        />
      </div>
          <p class="text-gray-800 text-sm">Retorno estimado:
            <span class="font-bold text-green-600" x-text="'R$ ' + (valor * <?= floatval($multiplicador) ?>).toLocaleString('pt-BR', { minimumFractionDigits: 2 })"></span>
          </p>
        </div>
        <?php endif; ?>
      </div>
      <?php } ?>
    </div>