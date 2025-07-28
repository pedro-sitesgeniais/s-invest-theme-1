<?php
/**
 * Painel do Associado - Listagem de Investidores com Modal de Detalhes
 * Estruturação: Sites Geniais | Edson Kleber
 */

defined('ABSPATH') || exit;

$current_user_id = get_current_user_id();

// 1. Investimentos do associado logado
$investimentos_ids = get_posts([
  'post_type'      => 'investment',
  'posts_per_page' => -1,
  'post_status'    => 'publish',
  'author'         => $current_user_id,
  'fields'         => 'ids'
]);

$investidor_ids = [];

if (!empty($investimentos_ids)) {
  // 2. Aportes vinculados a esses investimentos
  $aportes = get_posts([
    'post_type'      => 'aporte',
    'posts_per_page' => -1,
    'post_status'    => 'publish',
    'meta_query'     => [
      [
        'key'     => 'investment_id',
        'value'   => $investimentos_ids,
        'compare' => 'IN'
      ]
    ]
  ]);

  // 3. Coletar os usuários investidores
  foreach ($aportes as $aporte) {
    $user_id = get_field('investidor_id', $aporte->ID);
    if ($user_id) {
      $investidor_ids[$user_id][] = $aporte->ID;
    }
  }
}
?>

<section class="p-6 bg-white rounded-xl shadow-md" x-data="modalInvestidor()">
  <h2 class="text-2xl font-bold mb-4">👥 Investidores vinculados</h2>

  <?php if (!empty($investidor_ids)) :
    $investidores = get_users([
      'include' => array_keys($investidor_ids),
      'orderby' => 'display_name',
      'order'   => 'ASC'
    ]);
  ?>

    <ul class="divide-y divide-gray-200">
      <?php foreach ($investidores as $user) :
        $telefone = get_field('telefone', 'user_' . $user->ID);
        $telefone_formatado = $telefone ? preg_replace('/\D+/', '', $telefone) : null;
        $aporte_ids = $investidor_ids[$user->ID] ?? [];
        $investimentos_unicos = [];

        foreach ($aporte_ids as $aporte_id) {
          $inv_id = get_field('investment_id', $aporte_id);
          if ($inv_id) {
            $investimentos_unicos[$inv_id] = true;
          }
        }

        $total_investimentos = count($investimentos_unicos);
        $total_aportes = count($aporte_ids);
      ?>
        <li class="py-4">
          <div class="flex items-center justify-between">
            <div>
              <p class="font-medium text-gray-800"><?php echo esc_html($user->display_name); ?></p>
              <p class="text-sm text-gray-500"><?php echo esc_html($user->user_email); ?></p>
              <p class="text-xs text-gray-400"><?php echo $total_aportes; ?> aporte(s) em <?php echo $total_investimentos; ?> investimento(s)</p>
            </div>
            <div class="flex gap-3 text-gray-500 text-xl">
              <?php if ($telefone_formatado) : ?>
                <a href="https://wa.me/<?php echo esc_attr($telefone_formatado); ?>" target="_blank" title="WhatsApp" class="hover:text-green-600">
                  <i class="fab fa-whatsapp"></i>
                </a>
              <?php endif; ?>
              <a href="mailto:<?php echo esc_attr($user->user_email); ?>" title="Enviar e-mail" class="hover:text-blue-600">
                <i class="fas fa-envelope"></i>
              </a>
              <button @click="abrirInvestidor(<?php echo esc_js($user->ID); ?>)" title="Ver detalhes" class="hover:text-gray-800">
                <i class="fas fa-chart-bar"></i>
              </button>
            </div>
          </div>
        </li>
      <?php endforeach; ?>
    </ul>

  <?php else : ?>
    <p class="text-gray-500">Nenhum investidor aportou nos seus projetos ainda.</p>
  <?php endif; ?>

  <!-- Modal -->
  <div x-show="aberto" x-cloak class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div class="bg-white p-6 rounded-xl max-w-lg w-full shadow-lg relative">
      <button @click="fechar" class="absolute top-2 right-2 text-gray-500 hover:text-red-600 text-2xl">&times;</button>
      <template x-if="carregando">
        <p>🔄 Carregando...</p>
      </template>
      <template x-if="!carregando">
        <div>
          <h3 class="text-xl font-bold mb-2" x-text="dados.nome"></h3>
          <p class="text-sm text-gray-600" x-text="dados.email"></p>
          <p class="text-sm mt-2"><strong>Telefone:</strong> <span x-text="dados.telefone"></span></p>
          <p class="text-sm"><strong>Total Aportado:</strong> R$ <span x-text="dados.total_aportado"></span></p>
          <p class="text-sm mb-3"><strong>Investimentos:</strong> <span x-text="Array.isArray(dados.investimentos) ? dados.investimentos.join(', ') : 'Sem investimentos'"></span></p>
        </div>
      </template>
    </div>
  </div>
</section>

<script>
function modalInvestidor() {
  return {
    aberto: false,
    carregando: false,
    dados: {},
    abrirInvestidor(id) {
      this.aberto = true;
      this.carregando = true;
      fetch('<?php echo admin_url('admin-ajax.php'); ?>?action=icf_detalhes_investidor&user_id=' + id)
        .then(res => res.json())
        .then(data => {
          this.dados = data;
          this.carregando = false;
        });
    },
    fechar() {
      this.aberto = false;
    }
  }
}
</script>
