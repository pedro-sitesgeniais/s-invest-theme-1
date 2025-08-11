// FUNÇÃO ALPINE.JS PARA EXTRATO DE MOVIMENTAÇÕES - CORRIGIDA
// dashboard-fix.js

// Função principal extratoData
window.extratoData = function() {
    return {
        movimentos: [],
        movimentosFiltrados: [],
        carregando: false,
        limite: 10,
        showCalendar: false,
        
        // FILTROS CORRIGIDOS: Classe → Situação (condicionada) → Período
        filtros: {
            classe_ativo: '',    // slug da taxonomia tipo_produto
            situacao: '',        // ativo, vendido, encerrado (baseado na classe)
            periodo: '',         // 7, 30, 90, 365 para períodos rápidos
            data_inicio: '',     // data personalizada início
            data_fim: ''         // data personalizada fim
        },
        
        init() {
            this.carregarMovimentos();
        },
        
        carregarMovimentos() {
            this.carregando = true;
            
            setTimeout(() => {
                this.movimentos = this.processarMovimentos(window.dashboardMovimentos || []);
                this.aplicarFiltros();
                this.carregando = false;
            }, 200);
        },
        
        processarMovimentos(dados) {
            return dados.map(movimento => ({
                ...movimento,
                valor_formatado: this.formatarValor(movimento.valor)
            }));
        },
        
        aplicarFiltros() {
            this.carregando = true;
            
            setTimeout(() => {
                let filtrados = [...this.movimentos];
                
                // Filtro por classe de ativo
                if (this.filtros.classe_ativo) {
                    filtrados = filtrados.filter(mov => mov.classe_ativo === this.filtros.classe_ativo);
                }
                
                // Filtro por situação (condicionada à classe)
                if (this.filtros.situacao) {
                    filtrados = filtrados.filter(mov => mov.situacao === this.filtros.situacao);
                }
                
                // Filtro por período (rápido ou personalizado)
                if (this.filtros.periodo) {
                    const diasAtras = parseInt(this.filtros.periodo);
                    const dataLimite = new Date();
                    dataLimite.setDate(dataLimite.getDate() - diasAtras);
                    
                    filtrados = filtrados.filter(mov => {
                        const dataMovimento = this.parseData(mov.data);
                        return dataMovimento >= dataLimite;
                    });
                } else if (this.filtros.data_inicio && this.filtros.data_fim) {
                    // Período personalizado
                    const dataInicio = new Date(this.filtros.data_inicio);
                    const dataFim = new Date(this.filtros.data_fim);
                    
                    filtrados = filtrados.filter(mov => {
                        const dataMovimento = this.parseData(mov.data);
                        return dataMovimento >= dataInicio && dataMovimento <= dataFim;
                    });
                }
                
                this.movimentosFiltrados = filtrados;
                this.limite = 10;
                this.carregando = false;
            }, 150);
        },
        
        // ========== LÓGICA PARA SITUAÇÃO CONDICIONADA (CORRIGIDA) ==========
        classeEhTrade() {
            const classeAtual = this.filtros.classe_ativo.toLowerCase();
            return classeAtual.includes('trade') || 
                   classeAtual.includes('trading') || 
                   classeAtual === 'equity' ||
                   classeAtual === 'renda-variavel';
        },
        
        classeEhSCP() {
            const classeAtual = this.filtros.classe_ativo.toLowerCase();
            return classeAtual.includes('scp') || 
                   classeAtual.includes('private') ||
                   classeAtual.includes('private-scp');
        },
        
        // ========== FUNÇÕES DO CALENDÁRIO CORRIGIDAS ==========
        selecionarPeriodoRapido(dias) {
            this.filtros.periodo = dias;
            this.filtros.data_inicio = '';
            this.filtros.data_fim = '';
            this.showCalendar = false; // Fechar dropdown
            this.aplicarFiltros();
        },
        
        aplicarPeriodoPersonalizado() {
            if (this.filtros.data_inicio && this.filtros.data_fim) {
                // Validar que data início não é maior que data fim
                const dataInicio = new Date(this.filtros.data_inicio);
                const dataFim = new Date(this.filtros.data_fim);
                
                if (dataInicio > dataFim) {
                    alert('Data inicial não pode ser maior que a data final.');
                    return;
                }
                
                this.filtros.periodo = ''; // Limpar período rápido
                this.showCalendar = false; // Fechar dropdown
                this.aplicarFiltros();
            } else {
                alert('Selecione as duas datas para aplicar o período personalizado.');
            }
        },
        
        limparPeriodo() {
            this.filtros.periodo = '';
            this.filtros.data_inicio = '';
            this.filtros.data_fim = '';
            this.showCalendar = false; // Fechar dropdown
            this.aplicarFiltros();
        },
        
        limparFiltros() {
            this.filtros = {
                classe_ativo: '',
                situacao: '',
                periodo: '',
                data_inicio: '',
                data_fim: ''
            };
            this.showCalendar = false;
            this.aplicarFiltros();
        },
        
        temFiltrosAtivos() {
            return this.filtros.classe_ativo !== '' || 
                   this.filtros.situacao !== '' || 
                   this.filtros.periodo !== '' ||
                   (this.filtros.data_inicio !== '' && this.filtros.data_fim !== '');
        },
        
        contarMovimentosFiltrados() {
            return this.movimentosFiltrados.length;
        },
        
        // ========== FUNÇÕES PARA OBTER LABELS DOS FILTROS ==========
        obterLabelSituacao() {
            const labels = {
                'ativo': 'Ativos',
                'vendido': 'Vendidos', 
                'encerrado': 'Encerrados'
            };
            return labels[this.filtros.situacao] || '';
        },
        
        obterLabelClasse() {
            if (!this.filtros.classe_ativo) return '';
            
            const dados = window.dashboardFiltrosDados?.tiposAtivo || [];
            const tipo = dados.find(t => t.slug === this.filtros.classe_ativo);
            return tipo ? tipo.name : this.filtros.classe_ativo;
        },
        
        obterLabelPeriodo() {
            if (this.filtros.periodo) {
                const labels = {
                    '7': 'Últimos 7 dias',
                    '30': 'Últimos 30 dias',
                    '90': 'Últimos 3 meses',
                    '365': 'Último ano'
                };
                return labels[this.filtros.periodo] || `Últimos ${this.filtros.periodo} dias`;
            } else if (this.filtros.data_inicio && this.filtros.data_fim) {
                const inicio = this.formatarDataBrasileira(this.filtros.data_inicio);
                const fim = this.formatarDataBrasileira(this.filtros.data_fim);
                return `${inicio} até ${fim}`;
            }
            return 'Selecionar período';
        },
        
        // ========== WATCH PARA LIMPAR SITUAÇÃO QUANDO CLASSE MUDA ==========
        onClasseChange() {
            // Quando classe muda, verificar se situação atual é válida
            if (this.filtros.classe_ativo && this.filtros.situacao) {
                const ehTrade = this.classeEhTrade();
                const ehSCP = this.classeEhSCP();
                
                // Se mudou para Trade e situação é "encerrado", limpar
                if (ehTrade && this.filtros.situacao === 'encerrado') {
                    this.filtros.situacao = '';
                }
                
                // Se mudou para SCP e situação é "vendido", limpar
                if (ehSCP && this.filtros.situacao === 'vendido') {
                    this.filtros.situacao = '';
                }
            }
        },
        
        // ========== FUNÇÕES UTILITÁRIAS ==========
        formatarValor(valor) {
            return parseFloat(valor || 0).toLocaleString('pt-BR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        },
        
        parseData(dataString) {
            // Converte data do formato dd/mm/yyyy para objeto Date
            const partes = dataString.split('/');
            if (partes.length === 3) {
                return new Date(partes[2], partes[1] - 1, partes[0]);
            }
            return new Date();
        },
        
        formatarDataBrasileira(dataISO) {
            if (!dataISO) return '';
            const data = new Date(dataISO + 'T00:00:00'); // Evitar timezone issues
            return data.toLocaleDateString('pt-BR');
        },
        
        // ========== FUNÇÃO PARA DATA MÁXIMA (hoje) ==========
        getDataMaxima() {
            const hoje = new Date();
            return hoje.toISOString().split('T')[0];
        }
    };
};

// ========== GARANTIR DISPONIBILIDADE GLOBAL ==========
document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ dashboard-fix.js carregado com correções para Alpine.js');
});

// Dados dos movimentos para JavaScript (será populado pelo PHP)
window.dashboardMovimentos = window.dashboardMovimentos || [];