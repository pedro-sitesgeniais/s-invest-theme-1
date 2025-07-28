<?php
// Chart.js
wp_enqueue_script(
  'chartjs',
  'https://cdn.jsdelivr.net/npm/chart.js',
  [],
  null,
  true
);

// Seu script de cenários, que depende de Chart.js
wp_enqueue_script(
  'invest-cenarios',
  get_template_directory_uri() . '/assets/js/invest-cenarios.js',
  ['chartjs'],
  null,
  true
);
?>
