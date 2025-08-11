// FUNÇÃO ALPINE.JS PARA EXTRATO DE MOVIMENTAÇÕES
// Adicionar no final dos scripts do dashboard ou em arquivo separado

// Função principal extratoData
window.extratoData = function() {
    return {
        movimentos: [],
        movimentosFiltrados: [],
        carregando: false,
        limite: 10,
        
        // FILTROS ATUALIZADOS
        filtros: {
            situacao: '',        // ativo, vendido, encerrado
            tipo: '',           // aporte, dividendo  
            classe_ativo: '',   // slug da taxonomia tipo_produto
            investimento_id: '', // ID do investimento específico
            periodo: ''         // 30, 90, 180, 365
        },
        
        init() {
            this.carregarMovimentos();
        },
        
        carregarMovimentos() {
            this.carregando = true;
            
            // Simular delay de carregamento
            setTimeout(() => {
                // Usar os dados globais do PHP
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
                
                // Filtro por situação (ativo, vendido, encerrado)
                if (this.filtros.situacao) {
                    filtrados = filtrados.filter(mov => mov.situacao === this.filtros.situacao);
                }
                
                // Filtro por tipo (aporte, dividendo)
                if (this.filtros.tipo) {
                    filtrados = filtrados.filter(mov => mov.tipo === this.filtros.tipo);
                }
                
                // Filtro por classe de ativo
                if (this.filtros.classe_ativo) {
                    filtrados = filtrados.filter(mov => mov.classe_ativo === this.filtros.classe_ativo);
                }
                
                // Filtro por investimento específico
                if (this.filtros.investimento_id) {
                    filtrados = filtrados.filter(mov => mov.investment_id == this.filtros.investimento_id);
                }
                
                // Filtro por período
                if (this.filtros.periodo) {
                    const diasAtras = parseInt(this.filtros.periodo);
                    const dataLimite = new Date();
                    dataLimite.setDate(dataLimite.getDate() - diasAtras);
                    
                    filtrados = filtrados.filter(mov => {
                        const dataMovimento = this.parseData(mov.data);
                        return dataMovimento >= dataLimite;
                    });
                }
                
                this.movimentosFiltrados = filtrados;
                this.limite = 10; // Resetar limite
                this.carregando = false;
            }, 150);
        },
        
        limparFiltros() {
            this.filtros = {
                situacao: '',
                tipo: '',
                classe_ativo: '',
                investimento_id: '',
                periodo: ''
            };
            this.aplicarFiltros();
        },
        
        temFiltrosAtivos() {
            return Object.values(this.filtros).some(valor => valor !== '');
        },
        
        contarMovimentosFiltrados() {
            return this.movimentosFiltrados.length;
        },
        
        // FUNÇÕES PARA OBTER LABELS DOS FILTROS
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
        
        obterLabelInvestimento() {
            if (!this.filtros.investimento_id) return '';
            
            const dados = window.dashboardFiltrosDados?.investimentosDisponiveis || [];
            const investimento = dados.find(inv => inv.id == this.filtros.investimento_id);
            return investimento ? investimento.title : '';
        },
        
        obterLabelPeriodo() {
            const labels = {
                '30': 'Últimos 30 dias',
                '90': 'Últimos 3 meses',
                '180': 'Últimos 6 meses',
                '365': 'Último ano'
            };
            return labels[this.filtros.periodo] || '';
        },
        
        // FUNÇÕES UTILITÁRIAS
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
        }
    };
};
// =============== ADICIONAR FUNÇÃO DE DEBUG ===============
console.log('✅ ExtratoData carregado com novos filtros:', {
    situacao: ['ativo', 'vendido', 'encerrado'],
    tipo: ['aporte', 'dividendo'],
    classe_ativo: 'taxonomia tipo_produto',
    investimento_id: 'ID específico',
    periodo: ['30', '90', '180', '365']
});
// Garantir que a função esteja disponível quando o Alpine carregar
document.addEventListener('alpine:init', () => {
    // Registrar globalmente se necessário
    if (typeof window.extratoData !== 'function') {
        console.error('Função extratoData não foi definida corretamente');
    }
});

// Dados dos movimentos para JavaScript (será populado pelo PHP)
window.dashboardMovimentos = window.dashboardMovimentos || [];