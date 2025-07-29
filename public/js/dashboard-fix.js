// FUNÇÃO ALPINE.JS PARA EXTRATO DE MOVIMENTAÇÕES
// Adicionar no final dos scripts do dashboard ou em arquivo separado

// Função principal extratoData
window.extratoData = function() {
    return {
        // Estado dos filtros
        filtros: {
            tipo: '',        // 'aporte' ou 'dividendo'
            status: '',      // 'ativo' ou 'vendido'
            periodo: ''      // '30', '90', '180', '365'
        },
        
        // Estado da interface
        carregando: false,
        limite: 10,
        
        // Dados originais
        movimentosOriginais: [],
        movimentosFiltrados: [],
        
        // Inicialização
        init() {
            this.carregarMovimentos();
            this.aplicarFiltros();
        },
        
        // Carregar movimentos dos dados PHP
        carregarMovimentos() {
            this.carregando = true;
            
            try {
                // Usar dados reais do PHP
                const movimentosPHP = window.dashboardMovimentos || [];
                
                this.movimentosOriginais = movimentosPHP.map(item => ({
                    id: item.id,
                    data: item.data,
                    tipo: item.tipo,
                    investimento: item.investimento,
                    valor: parseFloat(item.valor) || 0,
                    valor_formatado: this.formatarValor(item.valor),
                    vendido: item.vendido || false,
                    timestamp: this.parseData(item.data),
                    aporte_id: item.aporte_id,
                    investment_id: item.investment_id
                }));
                
                // Ordenar por data (mais recente primeiro)
                this.movimentosOriginais.sort((a, b) => b.timestamp - a.timestamp);
                
            } catch (error) {
                console.error('Erro ao carregar movimentos:', error);
                this.movimentosOriginais = [];
            }
            
            this.carregando = false;
        },
        
        // Aplicar filtros
        aplicarFiltros() {
            this.carregando = true;
            
            setTimeout(() => {
                let movimentos = [...this.movimentosOriginais];
                
                // Filtro por tipo
                if (this.filtros.tipo) {
                    movimentos = movimentos.filter(mov => mov.tipo === this.filtros.tipo);
                }
                
                // Filtro por status
                if (this.filtros.status) {
                    const isVendido = this.filtros.status === 'vendido';
                    movimentos = movimentos.filter(mov => mov.vendido === isVendido);
                }
                
                // Filtro por período
                if (this.filtros.periodo) {
                    const diasAtras = parseInt(this.filtros.periodo);
                    const dataLimite = new Date();
                    dataLimite.setDate(dataLimite.getDate() - diasAtras);
                    
                    movimentos = movimentos.filter(mov => mov.timestamp >= dataLimite.getTime());
                }
                
                this.movimentosFiltrados = movimentos;
                this.carregando = false;
            }, 300); // Simular loading
        },
        
        // Limpar filtros
        limparFiltros() {
            this.filtros = {
                tipo: '',
                status: '',
                periodo: ''
            };
            this.aplicarFiltros();
        },
        
        // Verificar se há filtros ativos
        temFiltrosAtivos() {
            return this.filtros.tipo || this.filtros.status || this.filtros.periodo;
        },
        
        // Contar movimentos filtrados
        contarMovimentosFiltrados() {
            return this.movimentosFiltrados.length;
        },
        
        // Obter label do período
        obterLabelPeriodo() {
            const labels = {
                '30': 'Últimos 30 dias',
                '90': 'Últimos 3 meses', 
                '180': 'Últimos 6 meses',
                '365': 'Último ano'
            };
            return labels[this.filtros.periodo] || '';
        },
        
        // Funções auxiliares
        formatarValor(valor) {
            const num = parseFloat(valor) || 0;
            return num.toLocaleString('pt-BR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        },
        
        parseData(dataStr) {
            try {
                const partes = dataStr.split('/');
                if (partes.length === 3) {
                    const data = new Date(partes[2], partes[1] - 1, partes[0]);
                    return data.getTime();
                }
            } catch (error) {
                console.error('Erro ao parsear data:', dataStr, error);
            }
            return Date.now();
        }
    };
};

// Garantir que a função esteja disponível quando o Alpine carregar
document.addEventListener('alpine:init', () => {
    // Registrar globalmente se necessário
    if (typeof window.extratoData !== 'function') {
        console.error('Função extratoData não foi definida corretamente');
    }
});

// Dados dos movimentos para JavaScript (será populado pelo PHP)
window.dashboardMovimentos = window.dashboardMovimentos || [];