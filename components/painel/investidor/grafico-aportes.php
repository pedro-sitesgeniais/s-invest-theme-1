<?php
/**
 * Gráfico de Aportes por Investimento (dinâmico)
 */

$dados_aportes = icf_get_dados_grafico_aportes();
$labels = array_column($dados_aportes, 'label');
$valores = array_column($dados_aportes, 'valor');
$cores = ['#22c55e', '#3b82f6', '#f59e0b', '#8b5cf6', '#ec4899', '#ef4444'];

$grafico_id = 'grafico-aportes-usuario-' . uniqid(); // ID único para evitar conflitos
?>

<div class="bg-white rounded-xl shadow p-6 mb-6">
  <h2 class="text-lg font-semibold text-gray-800 mb-4">Distribuição de Aportes</h2>

  <?php if (!empty($labels)) : ?>
    <canvas id="<?= $grafico_id ?>" width="400" height="400"></canvas>
  <?php else : ?>
    <p class="text-gray-500">Você ainda não realizou nenhum aporte.</p>
  <?php endif; ?>
</div>

<?php if (!empty($labels)) : ?>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const ctx = document.getElementById('<?= $grafico_id ?>').getContext('2d');
      new Chart(ctx, {
        type: 'doughnut',
        data: {
          labels: <?= json_encode($labels) ?>,
          datasets: [{
            label: 'Aportes por projeto',
            data: <?= json_encode($valores) ?>,
            backgroundColor: <?= json_encode(array_slice($cores, 0, count($labels))) ?>,
            borderWidth: 1
          }]
        },
        options: {
          responsive: true,
          animation: {
            duration: 800,
            easing: 'easeOutQuart'
          },
          plugins: {
            legend: {
              position: 'bottom'
            }
          }
        }
      });
    });
  </script>
<?php endif; ?>
