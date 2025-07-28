document.addEventListener('DOMContentLoaded', () => {
  const el = document.getElementById('chart-cenarios');
  if (!el) return;

  const labels     = JSON.parse(el.dataset.labels);
  const projetado  = JSON.parse(el.dataset.projetado);
  const otimista   = JSON.parse(el.dataset.otimista);
  const pessimista = JSON.parse(el.dataset.pessimista);

  new Chart(el.getContext('2d'), {
    type: 'line',
    data: {
      labels,
      datasets: [
        { label: 'Projetado',  data: projetado,  borderColor: 'green',  fill: false, tension: 0.4 },
        { label: 'Pessimista', data: pessimista, borderColor: 'red',    fill: false, tension: 0.4 },
        { label: 'Otimista',   data: otimista,   borderColor: 'blue',   fill: false, tension: 0.4 }
      ]
    },
    options: {
      responsive: true,
      scales: { y: { beginAtZero: true } }
    }
  });
});