/**
 * Sistema Nativo de Campos para Investimentos
 * Interface reativa com Alpine.js
 */

document.addEventListener('alpine:init', () => {
    Alpine.data('investmentFields', (initialData = {}) => ({
        // Estado da aplicação
        activeTab: 'basic_info',
        data: {
            // Valores padrão
            classe_de_ativos: 'compra-em-lote',
            status_captacao: 'ativo',
            motivos: [],
            riscos: [],
            documentos: [],
            ...initialData
        },
        saving: false,
        message: '',
        messageType: 'success',
        
        // Inicialização
        init() {
            this.setupCurrencyMasks();
            this.setupCalculatedFields();
            this.watchConditionalFields();
            
            // Debug
            console.log('Investment Fields initialized with data:', this.data);
        },
        
        // Configurar máscaras de moeda
        setupCurrencyMasks() {
            this.$nextTick(() => {
                const currencyFields = document.querySelectorAll('.s-invest-currency');
                currencyFields.forEach(field => {
                    if (window.IMask) {
                        IMask(field, {
                            mask: 'R$ num',
                            blocks: {
                                num: {
                                    mask: Number,
                                    thousandsSeparator: '.',
                                    radix: ',',
                                    padFractionalZeros: true,
                                    scale: 2
                                }
                            }
                        });
                    }
                });
            });
        },
        
        // Configurar campos calculados
        setupCalculatedFields() {
            // SCP calculations
            this.$watch('data.valor_cota', () => this.calculateSCPValues());
            this.$watch('data.total_cotas', () => this.calculateSCPValues());
            
            // Observar mudanças no tipo de investimento
            this.$watch('data.classe_de_ativos', (newValue) => {
                console.log('Classe de ativos alterada para:', newValue);
                this.onClasseChanged(newValue);
            });
        },
        
        // Observar campos condicionais
        watchConditionalFields() {
            this.$watch('data.classe_de_ativos', (value) => {
                // Limpar campos não relevantes quando mudar o tipo
                if (value === 'trade') {
                    // Manter apenas campos relevantes para Trade
                    delete this.data.nome_ativo;
                    delete this.data.valor_cota;
                    delete this.data.total_cotas;
                } else if (value === 'private') {
                    // Inicializar campos SCP se necessário
                    if (!this.data.valor_cota) this.data.valor_cota = '';
                    if (!this.data.total_cotas) this.data.total_cotas = '';
                }
            });
        },
        
        // Calcular valores SCP automaticamente
        calculateSCPValues() {
            if (this.data.classe_de_ativos !== 'private-scp') return;
            
            const valorCota = parseFloat(this.data.valor_cota) || 0;
            const totalCotas = parseInt(this.data.total_cotas) || 0;
            const cotasVendidas = parseInt(this.data.cotas_vendidas) || 0;
            
            // Calcular cotas disponíveis
            this.data.cotas_disponiveis = Math.max(0, totalCotas - cotasVendidas);
            
            // Valor total do investimento baseado nas cotas
            if (valorCota > 0 && totalCotas > 0) {
                this.data.valor_total = valorCota * totalCotas;
            }
            
            console.log('SCP calculations updated:', {
                valorCota,
                totalCotas,
                cotasVendidas,
                cotasDisponiveis: this.data.cotas_disponiveis
            });
        },
        
        // Quando a classe de ativo mudar
        onClasseChanged(classe) {
            this.showMessage(`Campos ajustados para ${classe === 'private' ? 'SCP' : 'Trade'}`, 'success');
            
            // Reorganizar interface se necessário
            this.setupConditionalLogic();
        },
        
        // Lógica condicional
        setupConditionalLogic() {
            // Aqui podemos adicionar lógica adicional baseada no tipo
            if (this.data.classe_de_ativos === 'private') {
                // Validações específicas para SCP
                this.validateSCPFields();
            }
        },
        
        // Validar campos SCP
        validateSCPFields() {
            const errors = [];
            
            if (this.data.valor_cota && this.data.valor_cota <= 0) {
                errors.push('Valor da cota deve ser positivo');
            }
            
            if (this.data.total_cotas && this.data.total_cotas <= 0) {
                errors.push('Total de cotas deve ser positivo');
            }
            
            if (errors.length > 0) {
                this.showMessage(errors.join(', '), 'error');
            }
            
            return errors.length === 0;
        },
        
        // Salvar dados via AJAX
        async saveData() {
            if (this.saving) return;
            
            this.saving = true;
            this.message = '';
            
            try {
                const formData = new FormData();
                formData.append('action', 'save_investment_data');
                formData.append('nonce', sInvestAdmin.nonce);
                formData.append('post_id', sInvestAdmin.post_id);
                formData.append('data', JSON.stringify(this.data));
                
                const response = await fetch(sInvestAdmin.ajax_url, {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    this.showMessage('Dados salvos com sucesso!', 'success');
                    
                    // Atualizar dados calculados se retornados
                    if (result.data && result.data.calculated) {
                        Object.assign(this.data, result.data.calculated);
                    }
                } else {
                    this.showMessage(result.data || 'Erro ao salvar dados', 'error');
                }
            } catch (error) {
                console.error('Erro ao salvar:', error);
                this.showMessage('Erro de conexão', 'error');
            } finally {
                this.saving = false;
            }
        },
        
        // Validar campo individual
        async validateField(fieldName) {
            try {
                const formData = new FormData();
                formData.append('action', 'validate_investment_field');
                formData.append('nonce', sInvestAdmin.nonce);
                formData.append('field', fieldName);
                formData.append('value', this.data[fieldName]);
                
                const response = await fetch(sInvestAdmin.ajax_url, {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (!result.valid) {
                    this.showMessage(result.message, 'error');
                    return false;
                }
                
                return true;
            } catch (error) {
                console.error('Erro na validação:', error);
                return false;
            }
        },
        
        // Gerenciar campos repeater
        addRepeaterItem(fieldName) {
            if (!Array.isArray(this.data[fieldName])) {
                this.data[fieldName] = [];
            }
            
            // Definir estrutura baseada no tipo de campo
            let newItem = {};
            
            switch (fieldName) {
                case 'motivos':
                case 'riscos':
                    newItem = { titulo: '', descricao: '' };
                    break;
                case 'documentos':
                    newItem = { titulo: '', arquivo: '' };
                    break;
                default:
                    newItem = {};
            }
            
            this.data[fieldName].push(newItem);
            
            console.log(`Item adicionado ao campo ${fieldName}:`, newItem);
        },
        
        removeRepeaterItem(fieldName, index) {
            if (Array.isArray(this.data[fieldName])) {
                this.data[fieldName].splice(index, 1);
                console.log(`Item removido do campo ${fieldName}, índice ${index}`);
            }
        },
        
        // Seletor de arquivos
        selectFile(fieldName) {
            const input = document.createElement('input');
            input.type = 'file';
            input.accept = this.getFileAccept(fieldName);
            
            input.onchange = (e) => {
                const file = e.target.files[0];
                if (file) {
                    this.uploadFile(file, fieldName);
                }
            };
            
            input.click();
        },
        
        // Upload de arquivo
        async uploadFile(file, fieldName) {
            try {
                const formData = new FormData();
                formData.append('action', 'upload_investment_file');
                formData.append('nonce', sInvestAdmin.nonce);
                formData.append('file', file);
                formData.append('field', fieldName);
                
                const response = await fetch(sInvestAdmin.ajax_url, {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    this.data[fieldName] = result.data.url;
                    this.showMessage('Arquivo enviado com sucesso!', 'success');
                } else {
                    this.showMessage(result.data || 'Erro no upload', 'error');
                }
            } catch (error) {
                console.error('Erro no upload:', error);
                this.showMessage('Erro no upload do arquivo', 'error');
            }
        },
        
        // Obter aceite de arquivos
        getFileAccept(fieldName) {
            const accepts = {
                'url_lamina_tecnica': '.pdf',
                'documentos': '.pdf,.doc,.docx'
            };
            
            return accepts[fieldName] || '.pdf,.doc,.docx,.jpg,.png';
        },
        
        // Obter nome do arquivo
        getFileName(url) {
            if (!url) return '';
            return url.split('/').pop();
        },
        
        // Mostrar mensagem
        showMessage(text, type = 'success') {
            this.message = text;
            this.messageType = type;
            
            // Auto-hide depois de 5 segundos
            setTimeout(() => {
                this.message = '';
            }, 5000);
        },
        
        // Formatar moeda para exibição
        formatCurrency(value) {
            if (!value) return 'R$ 0,00';
            
            const numValue = parseFloat(value);
            return new Intl.NumberFormat('pt-BR', {
                style: 'currency',
                currency: 'BRL'
            }).format(numValue);
        },
        
        // Formatar porcentagem
        formatPercentage(value) {
            if (!value) return '0%';
            return `${parseFloat(value).toFixed(2)}%`;
        },
        
        // Debug helpers
        logData() {
            console.log('Current investment data:', this.data);
        },
        
        exportData() {
            const dataStr = JSON.stringify(this.data, null, 2);
            const dataBlob = new Blob([dataStr], { type: 'application/json' });
            const url = URL.createObjectURL(dataBlob);
            
            const link = document.createElement('a');
            link.href = url;
            link.download = `investment-${sInvestAdmin.post_id}-data.json`;
            link.click();
            
            URL.revokeObjectURL(url);
        }
    }));
});

// Utilitários globais para integração
window.sInvestUtils = {
    // Converter valor de moeda para número
    currencyToNumber(currencyString) {
        if (!currencyString) return 0;
        
        return parseFloat(
            currencyString
                .replace(/[R$\s]/g, '')
                .replace(/\./g, '')
                .replace(',', '.')
        ) || 0;
    },
    
    // Converter número para moeda
    numberToCurrency(number) {
        if (!number) return 'R$ 0,00';
        
        return new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        }).format(number);
    },
    
    // Debounce para validações
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
};

// Carregar IMask se necessário
if (typeof IMask === 'undefined') {
    const script = document.createElement('script');
    script.src = 'https://unpkg.com/imask@7.6.1/dist/imask.min.js';
    script.onload = () => {
        console.log('IMask carregado com sucesso');
    };
    document.head.appendChild(script);
}