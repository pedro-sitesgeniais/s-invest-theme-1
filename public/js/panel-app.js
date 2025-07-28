document.addEventListener('alpine:init', () => {
  Alpine.data('donutChart', () => ({
      chart: null,
      labels: [],
      dados: [],
      colors: ['#22c55e', '#3b82f6', '#f59e0b', '#8b5cf6', '#ec4899', '#ef4444'],
      init() {
          try {
              this.labels = this.safeJsonParse(document.currentScript?.getAttribute('data-donut-labels')) || [];
              this.dados = this.safeJsonParse(document.currentScript?.getAttribute('data-donut-values')) || [];
              
              this.$nextTick(() => {
                  this.renderDonutChart();
              });
          } catch (error) {
              // Error handled silently
          }
      },
      renderDonutChart() {
          if (!this.$refs.canvasDonut) return;
          
          this.destroyChart();
          
          const ctx = this.$refs.canvasDonut.getContext('2d');
          if (!ctx) return;
          
          this.chart = new Chart(ctx, {
              type: 'doughnut',
              data: {
                  labels: this.labels,
                  datasets: [{
                      data: this.dados,
                      backgroundColor: this.colors,
                      borderWidth: 1,
                      hoverOffset: 10
                  }]
              },
              options: {
                  responsive: true,
                  maintainAspectRatio: false,
                  plugins: {
                      legend: {
                          position: 'bottom',
                          labels: {
                              padding: 20,
                              usePointStyle: true
                          }
                      },
                      tooltip: {
                          callbacks: {
                              label: (context) => {
                                  const label = context.label || '';
                                  const value = context.raw || 0;
                                  const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                  const percentage = Math.round((value / total) * 100);
                                  return `${label}: R$ ${value.toLocaleString()} (${percentage}%)`;
                              }
                          }
                      }
                  }
              }
          });
      },
      destroyChart() {
          if (this.chart) {
              this.chart.destroy();
              this.chart = null;
          }
      },
      safeJsonParse(str) {
          try {
              return str ? JSON.parse(str) : [];
          } catch {
              return [];
          }
      }
  }));

  Alpine.data('lineChart', () => ({
      chart: null,
      dados: [],
      init() {
          try {
              this.dados = this.safeJsonParse(document.currentScript?.getAttribute('data-line-values')) || [];
              
              this.$nextTick(() => {
                  this.renderLineChart();
              });
          } catch (error) {
              // Error handled silently
          }
      },
      renderLineChart() {
          if (!this.$refs.canvasLine) return;
          
          this.destroyChart();
          
          const ctx = this.$refs.canvasLine.getContext('2d');
          if (!ctx) return;
          
          this.chart = new Chart(ctx, {
              type: 'line',
              data: {
                  labels: this.dados.map(d => d.label),
                  datasets: [{
                      label: 'Acumulado (R$)',
                      data: this.dados.map(d => d.value),
                      fill: false,
                      borderColor: '#3b82f6',
                      borderWidth: 3,
                      tension: 0.1,
                      pointBackgroundColor: '#ffffff',
                      pointRadius: 5,
                      pointHoverRadius: 7
                  }]
              },
              options: {
                  responsive: true,
                  maintainAspectRatio: false,
                  interaction: {
                      intersect: false,
                      mode: 'index'
                  },
                  scales: {
                      x: {
                          title: {
                              display: true,
                              text: 'Mês/Ano',
                              padding: {top: 10}
                          },
                          grid: {
                              display: false
                          }
                      },
                      y: {
                          title: {
                              display: true,
                              text: 'R$ Investidos',
                              padding: {bottom: 10}
                          },
                          ticks: {
                              callback: (value) => 'R$ ' + value.toLocaleString()
                          }
                      }
                  },
                  plugins: {
                      tooltip: {
                          callbacks: {
                              label: (context) => {
                                  return `R$ ${context.raw.toLocaleString()}`;
                              }
                          }
                      }
                  }
              }
          });
      },
      destroyChart() {
          if (this.chart) {
              this.chart.destroy();
              this.chart = null;
          }
      },
      safeJsonParse(str) {
          try {
              return str ? JSON.parse(str) : [];
          } catch {
              return [];
          }
      }
  }));

  Alpine.data('profileForm', () => ({
      form: {
          first_name: '',
          last_name: '',
          telefone: '',
          cpf: ''
      },
      message: null,
      success: false,
      isLoading: false,
      errors: {},
      init() {
          this.loadInitialData();
      },
      loadInitialData() {
          try {
              const root = this.$root;
              this.form = {
                  first_name: root.dataset.firstName || '',
                  last_name: root.dataset.lastName || '',
                  telefone: root.dataset.telefone || '',
                  cpf: root.dataset.cpf || ''
              };
          } catch (error) {
              // Error handled silently
          }
      },
      validate() {
          this.errors = {};
          let isValid = true;

          if (!this.form.first_name.trim()) {
              this.errors.first_name = 'Nome é obrigatório';
              isValid = false;
          }

          if (!this.form.last_name.trim()) {
              this.errors.last_name = 'Sobrenome é obrigatório';
              isValid = false;
          }

          if (this.form.telefone && !/^[0-9\s\-()]+$/.test(this.form.telefone)) {
              this.errors.telefone = 'Telefone inválido';
              isValid = false;
          }

          return isValid;
      },
      async submit() {
          if (!this.validate()) return;

          this.isLoading = true;
          this.message = null;
          this.success = false;

          try {
              const response = await fetch(window.profile_ajax.ajax_url, {
                  method: 'POST',
                  headers: {
                      'Content-Type': 'application/x-www-form-urlencoded',
                      'Accept': 'application/json'
                  },
                  body: new URLSearchParams({
                      action: 'sky_profile_update',
                      nonce: window.profile_ajax.nonce,
                      ...this.form
                  })
              });

              const data = await response.json();

              if (!response.ok) {
                  throw new Error(data.data || 'Erro ao atualizar perfil');
              }

              this.success = data.success;
              this.message = data.data;

              if (data.success) {
                  setTimeout(() => {
                      this.message = null;
                  }, 5000);
              }
          } catch (error) {
              this.success = false;
              this.message = error.message || 'Erro ao atualizar perfil';
          } finally {
              this.isLoading = false;
          }
      }
  }));
});

document.addEventListener('DOMContentLoaded', () => {
  if (typeof Chart !== 'undefined') {
      Chart.defaults.font.family = 'system-ui, -apple-system, sans-serif';
      Chart.defaults.color = '#6b7280';
  }
});