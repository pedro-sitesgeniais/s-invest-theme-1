/**
 * S-Invest Theme - Main JavaScript v2.4
 * Arquivo: resources/js/main.js
 */

import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';
import mask from '@alpinejs/mask';

import './components/auth.js';

window.Alpine = Alpine;

Alpine.plugin(collapse);
Alpine.plugin(mask);

Alpine.store('sInvestTheme', {
    isLoading: false,
    currentUser: null,
    version: '2.4.0',
    
    colors: {
        primary: '#000E35',
        secondary: '#2ED2F8',
        accent: '#2072D6',
        success: '#22c55e',
        warning: '#f59e0b',
        error: '#ef4444',
        info: '#3b82f6'
    },
    
    get ajaxUrl() { 
        return window.investments_ajax?.ajax_url || '/wp-admin/admin-ajax.php'; 
    },
    
    get nonce() { 
        return window.investments_ajax?.nonce || ''; 
    },
    
    async request(action, data = {}) {
        const formData = new FormData();
        formData.append('action', action);
        formData.append('nonce', this.nonce);
        
        Object.entries(data).forEach(([key, value]) => {
            if (value !== null && value !== undefined) {
                formData.append(key, value);
            }
        });
        
        try {
            this.isLoading = true;
            
            const response = await fetch(this.ajaxUrl, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const result = await response.json();
            
            return result;
            
        } catch (error) {
            return { success: false, error: error.message };
        } finally {
            this.isLoading = false;
        }
    },
    
    showLoading() { this.isLoading = true; },
    hideLoading() { this.isLoading = false; }
});

Alpine.data('panelLayout', () => ({
    loading: false,
    currentSection: new URLSearchParams(window.location.search).get('secao') || 'dashboard',
    sidebarExpanded: false,
    
    init() {
        if (window.innerWidth >= 1024) {
            const saved = localStorage.getItem('desktop-sidebar-expanded');
            this.sidebarExpanded = saved !== 'false';
        }
    },
    
    handleSidebarToggle(detail) {
        if (detail && typeof detail.expanded === 'boolean') {
            this.sidebarExpanded = detail.expanded;
        }
    },
    
    showLoading() {
        this.loading = true;
        Alpine.store('sInvestTheme').showLoading();
    },
    
    hideLoading() {
        this.loading = false;
        Alpine.store('sInvestTheme').hideLoading();
    },
    
    onSectionLoaded() {
        this.hideLoading();
        
        this.$dispatch('panel-section-loaded', {
            section: this.currentSection
        });
    }
}));

Alpine.data('sectionLoader', () => ({
    init() {
        this.$nextTick(() => {
            this.$dispatch('section-loaded');
        });
        
        this.$el.classList.add('fade-in');
    }
}));

Alpine.data('dashboardData', () => ({
    loading: false,
    chartsInitialized: false,
    
    init() {
        this.chartsInitialized = true;
    }
}));

Alpine.data('filtrosInvestimentos', () => ({
    filtros: {
        tipo_produto: '',
        imposto: '',
        modalidade: '',
        prazo: '',
        valor: '',
        ordem: 'DESC',
        status: '',
        risco: '',
        moeda: ''
    },
    
    pagina: 1,
    temMais: true,
    carregando: false,
    observer: null,
    erro: null,
    requestController: null,
    totalItens: 0,
    primeiraCarregamento: true,

    init() {
        this.aplicarFiltros();
        this.initInfiniteScroll();
    },

    temFiltrosAtivos() {
        const filtrosParaVerificar = [
            'tipo_produto', 'imposto', 'modalidade', 
            'prazo', 'valor', 'risco', 'moeda', 'status'
        ];
        
        return filtrosParaVerificar.some(filtro => 
            this.filtros[filtro] && this.filtros[filtro] !== ''
        );
    },

    initInfiniteScroll() {
        if (!('IntersectionObserver' in window)) {
            return;
        }

        this.observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting && this.temMais && !this.carregando) {
                    this.carregarMais();
                }
            });
        }, { 
            rootMargin: '200px',
            threshold: 0.1
        });

        this.$nextTick(() => {
            if (this.$refs.sentinela) {
                this.observer.observe(this.$refs.sentinela);
            }
        });

        this.$watch('temMais', (value) => {
            if (!value && this.observer && this.$refs.sentinela) {
                this.observer.unobserve(this.$refs.sentinela);
            }
        });
    },

    aplicarFiltros() {
        if (this.requestController) {
            this.requestController.abort();
        }
        
        this.pagina = 1;
        this.temMais = true;
        this.erro = null;
        this.totalItens = 0;
        this.primeiraCarregamento = true;
        this.fetchInvestimentos(true);
    },

    carregarMais() {
        if (this.carregando || !this.temMais) return;
        
        this.pagina++;
        this.fetchInvestimentos(false);
    },

    limparFiltros() {
        this.filtros = {
            tipo_produto: '',
            imposto: '',
            modalidade: '',
            prazo: '',
            valor: '',
            ordem: 'DESC',
            status: '',
            risco: '',
            moeda: ''
        };
        this.aplicarFiltros();
    },

    async fetchInvestimentos(limpar = false) {
        this.carregando = true;
        this.erro = null;

        this.requestController = new AbortController();

        try {
            if (!window.investments_ajax) {
                throw new Error('Configuração AJAX não encontrada');
            }

            const dados = new URLSearchParams({
                action: 'filtrar_investimentos',
                nonce: window.investments_ajax.nonce,
                filtros: JSON.stringify(this.filtros),
                pagina: this.pagina
            });

            const response = await fetch(window.investments_ajax.ajax_url, {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: dados,
                signal: this.requestController.signal
            });
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const html = await response.text();
            this.processarResultados(html, limpar);
            
        } catch (error) {
            if (error.name === 'AbortError') {
                return;
            }
            
            this.erro = error.message;
            this.exibirErro(error.message, limpar);
        } finally {
            this.carregando = false;
            this.requestController = null;
        }
    },

    processarResultados(html, limpar) {
        const container = this.$refs.lista;
        
        if (!container) {
            return;
        }

        if (limpar) {
            container.style.opacity = '0';
            setTimeout(() => {
                container.innerHTML = html;
                container.style.opacity = '1';
                this.totalItens = container.children.length;
                this.primeiraCarregamento = false;
            }, 150);
        } else {
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = html;
            
            Array.from(tempDiv.children).forEach((item, index) => {
                item.style.opacity = '0';
                item.style.transform = 'translateY(20px)';
                container.appendChild(item);
                
                setTimeout(() => {
                    item.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                    item.style.opacity = '1';
                    item.style.transform = 'translateY(0)';
                }, index * 100);
            });
            
            this.totalItens = container.children.length;
        }

        const encontrouFim = html.includes('data-fim="true"');
        const semResultados = html.includes('Nenhum investimento encontrado');
        const temConteudo = this.totalItens > 0;
        
        this.temMais = temConteudo && !encontrouFim && !semResultados;

        this.$nextTick(() => {
            if (this.observer && this.$refs.sentinela) {
                if (this.temMais) {
                    this.observer.observe(this.$refs.sentinela);
                } else {
                    this.observer.unobserve(this.$refs.sentinela);
                }
            }
        });
    },

    exibirErro(mensagem, limpar) {
        if (!this.$refs.lista || !limpar) return;
        
        this.$refs.lista.innerHTML = `
            <div class="col-span-full text-center py-16 text-red-600 flex flex-col items-center space-y-4 animate-fadeIn">
                <i class="fas fa-exclamation-triangle text-4xl text-red-400"></i>
                <p class="text-lg font-medium">Erro ao carregar investimentos</p>
                <p class="text-sm text-gray-500 max-w-md">${mensagem}</p>
                <button onclick="location.reload()" 
                        class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 transition-colors">
                    Recarregar página
                </button>
            </div>
        `;
    },

    destroy() {
        if (this.observer) {
            this.observer.disconnect();
        }
        if (this.requestController) {
            this.requestController.abort();
        }
    }
}));

Alpine.data('painelFiltros', () => ({
    filtros: {
        tipo_produto: '',
        imposto: '',
        modalidade: '',
        prazo: '',
        valor: '',
        ordem: 'DESC',
        status: '',
        risco: '',
        moeda: ''
    },
    
    carregando: false,
    resultados: '',
    erro: null,
    pagina: 1,
    maxPaginas: 1,
    requestController: null,
    
    init() {
        this.aplicarFiltros();
    },
    
    temFiltrosAtivos() {
        const filtrosParaVerificar = [
            'tipo_produto', 'imposto', 'modalidade', 
            'prazo', 'valor', 'status', 'risco', 'moeda'
        ];
        
        return filtrosParaVerificar.some(filtro => 
            this.filtros[filtro] && this.filtros[filtro] !== ''
        );
    },
    
    aplicarFiltros() {
        if (this.requestController) {
            this.requestController.abort();
        }
        
        this.pagina = 1;
        this.buscarInvestimentos();
    },
    
    limparFiltros() {
        this.filtros = {
            tipo_produto: '',
            imposto: '',
            modalidade: '',
            prazo: '',
            valor: '',
            ordem: 'DESC',
            status: '',
            risco: '',
            moeda: ''
        };
        this.aplicarFiltros();
    },
    
    irParaPagina(pagina) {
        if (pagina >= 1 && pagina <= this.maxPaginas && pagina !== this.pagina) {
            this.pagina = pagina;
            this.buscarInvestimentos();
            
            this.$nextTick(() => {
                const container = this.$el.querySelector('.section-content');
                if (container) {
                    container.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        }
    },
    
    async buscarInvestimentos() {
        this.carregando = true;
        this.erro = null;
        
        this.requestController = new AbortController();
        
        try {
            if (!window.investments_ajax) {
                throw new Error('Configuração AJAX não encontrada');
            }
            
            const formData = new FormData();
            formData.append('action', 'filtrar_investimentos_painel');
            formData.append('nonce', window.investments_ajax.nonce);
            formData.append('paged', this.pagina);
            
            Object.entries(this.filtros).forEach(([key, value]) => {
                if (value && value.trim() !== '') {
                    formData.append(key, value);
                }
            });
            
            const response = await fetch(window.investments_ajax.ajax_url, {
                method: 'POST',
                body: formData,
                signal: this.requestController.signal
            });
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const data = await response.json();
            
            if (data.success) {
                const oldResults = this.resultados;
                this.resultados = data.data.html;
                this.maxPaginas = data.data.max_pages || 1;
                this.pagina = data.data.paged || 1;
                
                if (oldResults !== this.resultados) {
                    this.$nextTick(() => {
                        const container = this.$el.querySelector('[x-html="resultados"]');
                        if (container) {
                            container.classList.add('fade-in');
                        }
                    });
                }
            } else {
                throw new Error(data.data || 'Erro desconhecido no servidor');
            }
            
        } catch (error) {
            if (error.name === 'AbortError') {
                return;
            }
            
            this.erro = error.message;
            this.resultados = this.gerarHTMLErro(error.message);
        } finally {
            this.carregando = false;
            this.requestController = null;
        }
    },
    
    gerarHTMLErro(mensagem) {
        return `
            <div class="text-center py-16 bg-white rounded-xl border border-gray-200 animate-fadeIn">
                <div class="text-red-600 mb-4">
                    <i class="fas fa-exclamation-triangle text-4xl mb-4"></i>
                    <p class="text-lg font-medium">Erro ao carregar investimentos</p>
                    <p class="text-sm text-gray-500 mt-2">${mensagem}</p>
                </div>
                <button onclick="location.reload()" 
                        class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 transition-colors">
                    Recarregar página
                </button>
            </div>
        `;
    },
    
    get paginasVisiveis() {
        const paginas = [];
        const range = window.innerWidth < 768 ? 1 : 2;
        const inicio = Math.max(1, this.pagina - range);
        const fim = Math.min(this.maxPaginas, this.pagina + range);
        
        for (let i = inicio; i <= fim; i++) {
            paginas.push(i);
        }
        
        return paginas;
    },
    
    get temPaginaAnterior() { 
        return this.pagina > 1; 
    },
    
    get temProximaPagina() { 
        return this.pagina < this.maxPaginas; 
    },
    
    get mostrarReticencias() {
        return {
            inicio: this.pagina > 3,
            fim: this.pagina < this.maxPaginas - 2
        };
    },
    
    destroy() {
        if (this.requestController) {
            this.requestController.abort();
        }
    }
}));

Alpine.data('meusFiltros', () => ({
    filtros: {
        tipo_produto: '',
        imposto: '',
        modalidade: '',
        status: '',
        rentabilidade: '',
        ordem: 'DESC'
    },
    
    carregando: false,
    resultados: '',
    erro: null,
    pagina: 1,
    maxPaginas: 1,
    investmentIds: [],
    requestController: null,
    
    init() {
        this.buscarMeusInvestimentos();
    },
    
    temFiltrosAtivos() {
        const filtrosParaVerificar = [
            'tipo_produto', 'imposto', 'modalidade', 
            'status', 'rentabilidade'
        ];
        
        return filtrosParaVerificar.some(filtro => 
            this.filtros[filtro] && this.filtros[filtro] !== ''
        );
    },
    
    async buscarMeusInvestimentos() {
        this.investmentIds = [];
        this.aplicarFiltros();
    },
    
    aplicarFiltros() {
        if (this.requestController) {
            this.requestController.abort();
        }
        
        this.pagina = 1;
        this.buscarInvestimentos();
    },
    
    limparFiltros() {
        this.filtros = {
            tipo_produto: '',
            imposto: '',
            modalidade: '',
            status: '',
            rentabilidade: '',
            ordem: 'DESC'
        };
        this.aplicarFiltros();
    },
    
    async buscarInvestimentos() {
        this.carregando = true;
        this.erro = null;
        
        this.requestController = new AbortController();
        
        try {
            if (!window.investments_ajax) {
                throw new Error('Configuração AJAX não encontrada');
            }
            
            const formData = new FormData();
            formData.append('action', 'filtrar_meus_investimentos');
            formData.append('nonce', window.investments_ajax.nonce);
            formData.append('paged', this.pagina);
            formData.append('investment_ids', JSON.stringify(this.investmentIds));
            
            Object.entries(this.filtros).forEach(([key, value]) => {
                if (value && value.trim() !== '') {
                    formData.append(key, value);
                }
            });
            
            const response = await fetch(window.investments_ajax.ajax_url, {
                method: 'POST',
                body: formData,
                signal: this.requestController.signal
            });
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const data = await response.json();
            
            if (data.success) {
                this.resultados = data.data.html;
                this.maxPaginas = data.data.max_pages || 1;
                this.pagina = data.data.paged || 1;
            } else {
                throw new Error(data.data || 'Erro desconhecido no servidor');
            }
            
        } catch (error) {
            if (error.name === 'AbortError') {
                return;
            }
            
            this.erro = error.message;
            this.resultados = this.gerarHTMLErro(error.message);
        } finally {
            this.carregando = false;
            this.requestController = null;
        }
    },
    
    gerarHTMLErro(mensagem) {
        return `
            <div class="text-center py-16 bg-white rounded-xl border border-gray-200 animate-fadeIn">
                <div class="text-red-600 mb-4">
                    <i class="fas fa-exclamation-triangle text-4xl mb-4"></i>
                    <p class="text-lg font-medium">Erro ao carregar seus investimentos</p>
                    <p class="text-sm text-gray-500 mt-2">${mensagem}</p>
                </div>
                <button onclick="location.reload()" 
                        class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 transition-colors">
                    Recarregar página
                </button>
            </div>
        `;
    },
    
    destroy() {
        if (this.requestController) {
            this.requestController.abort();
        }
    }
}));

Alpine.data('toastNotifications', () => ({
    notifications: [],
    maxNotifications: 5,
    
    add(message, type = 'info', duration = 5000) {
        const id = Date.now() + Math.random();
        const notification = { 
            id, 
            message, 
            type, 
            duration,
            timestamp: new Date()
        };
        
        if (this.notifications.length >= this.maxNotifications) {
            this.notifications.shift();
        }
        
        this.notifications.push(notification);
        
        if (duration > 0) {
            setTimeout(() => this.remove(id), duration);
        }
        
        return id;
    },
    
    remove(id) {
        this.notifications = this.notifications.filter(n => n.id !== id);
    },
    
    clear() {
        this.notifications = [];
    },
    
    success(message, duration = 5000) { 
        return this.add(`✅ ${message}`, 'success', duration); 
    },
    
    error(message, duration = 8000) { 
        return this.add(`❌ ${message}`, 'error', duration); 
    },
    
    warning(message, duration = 6000) { 
        return this.add(`⚠️ ${message}`, 'warning', duration); 
    },
    
    info(message, duration = 5000) { 
        return this.add(`ℹ️ ${message}`, 'info', duration); 
    },
    
    find(id) {
        return this.notifications.find(n => n.id === id);
    }
}));

window.sInvestUtils = {
    formatCurrency: (value, currency = 'BRL', options = {}) => {
        try {
            const defaultOptions = {
                style: 'currency', 
                currency,
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            };
            
            return new Intl.NumberFormat('pt-BR', { ...defaultOptions, ...options }).format(value);
        } catch (error) {
            return `R$ ${parseFloat(value).toFixed(2)}`;
        }
    },
    
    formatPercent: (value, decimals = 2) => {
        try {
            return new Intl.NumberFormat('pt-BR', {
                style: 'percent',
                minimumFractionDigits: decimals,
                maximumFractionDigits: decimals
            }).format(value / 100);
        } catch (error) {
            return `${parseFloat(value).toFixed(decimals)}%`;
        }
    },
    
    formatNumber: (value, options = {}) => {
        try {
            return new Intl.NumberFormat('pt-BR', options).format(value);
        } catch (error) {
            return value.toString();
        }
    },
    
    debounce: (func, wait, immediate = false) => {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                timeout = null;
                if (!immediate) func.apply(this, args);
            };
            const callNow = immediate && !timeout;
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
            if (callNow) func.apply(this, args);
        };
    },
    
    throttle: (func, limit) => {
        let inThrottle;
        let lastFunc;
        let lastRan;
        return function(...args) {
            if (!inThrottle) {
                func.apply(this, args);
                lastRan = Date.now();
                inThrottle = true;
            } else {
                clearTimeout(lastFunc);
                lastFunc = setTimeout(() => {
                    if ((Date.now() - lastRan) >= limit) {
                        func.apply(this, args);
                        lastRan = Date.now();
                    }
                }, limit - (Date.now() - lastRan));
            }
        };
    },
    
    scrollTo: (element, offset = 0, behavior = 'smooth') => {
        const targetElement = typeof element === 'string' 
            ? document.querySelector(element) 
            : element;
            
        if (targetElement) {
            const elementPosition = targetElement.getBoundingClientRect().top;
            const offsetPosition = elementPosition + window.pageYOffset - offset;
            
            window.scrollTo({
                top: offsetPosition,
                behavior
            });
        }
    },
    
    async copyToClipboard(text, showFeedback = true) {
        try {
            if (navigator.clipboard && window.isSecureContext) {
                await navigator.clipboard.writeText(text);
            } else {
                const textArea = document.createElement('textarea');
                textArea.value = text;
                textArea.style.position = 'fixed';
                textArea.style.left = '-999999px';
                textArea.style.top = '-999999px';
                document.body.appendChild(textArea);
                textArea.focus();
                textArea.select();
                
                const result = document.execCommand('copy');
                document.body.removeChild(textArea);
                
                if (!result) {
                    throw new Error('Comando copy falhou');
                }
            }
            
            if (showFeedback && typeof Alpine !== 'undefined') {
                const toastComponent = document.querySelector('[x-data*="toastNotifications"]');
                if (toastComponent && Alpine.$data(toastComponent)) {
                    Alpine.$data(toastComponent).success('Copiado para a área de transferência!');
                }
            }
            
            return true;
        } catch (err) {
            if (showFeedback && typeof Alpine !== 'undefined') {
                const toastComponent = document.querySelector('[x-data*="toastNotifications"]');
                if (toastComponent && Alpine.$data(toastComponent)) {
                    Alpine.$data(toastComponent).error('Falha ao copiar');
                }
            }
            
            return false;
        }
    },

    validateCPF: (cpf) => {
        if (!cpf) return false;
        
        cpf = cpf.replace(/\D/g, '');
        
        if (cpf.length !== 11 || /^(\d)\1{10}$/.test(cpf)) {
            return false;
        }
        
        for (let i = 9; i < 11; i++) {
            let sum = 0;
            for (let j = 0; j < i; j++) {
                sum += parseInt(cpf[j]) * ((i + 1) - j);
            }
            const remainder = sum % 11;
            const digit = remainder < 2 ? 0 : 11 - remainder;
            if (parseInt(cpf[i]) !== digit) return false;
        }
        
        return true;
    },
    
    validateCNPJ: (cnpj) => {
        if (!cnpj) return false;
        
        cnpj = cnpj.replace(/\D/g, '');
        
        if (cnpj.length !== 14 || /^(\d)\1{13}$/.test(cnpj)) {
            return false;
        }
        
        const weights1 = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        const weights2 = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        
        let sum = 0;
        for (let i = 0; i < 12; i++) {
            sum += parseInt(cnpj[i]) * weights1[i];
        }
        
        let digit1 = sum % 11 < 2 ? 0 : 11 - (sum % 11);
        
        sum = 0;
        for (let i = 0; i < 13; i++) {
            sum += parseInt(cnpj[i]) * weights2[i];
        }
        
        let digit2 = sum % 11 < 2 ? 0 : 11 - (sum % 11);
        
        return parseInt(cnpj[12]) === digit1 && parseInt(cnpj[13]) === digit2;
    },
    
    formatPhone: (phone) => {
        if (!phone) return '';
        
        const cleaned = phone.replace(/\D/g, '');
        
        if (cleaned.length === 11) {
            return cleaned.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
        } else if (cleaned.length === 10) {
            return cleaned.replace(/(\d{2})(\d{4})(\d{4})/, '($1) $2-$3');
        }
        
        return phone;
    },
    
    formatCEP: (cep) => {
        if (!cep) return '';
        
        const cleaned = cep.replace(/\D/g, '');
        
        if (cleaned.length === 8) {
            return cleaned.replace(/(\d{5})(\d{3})/, '$1-$2');
        }
        
        return cep;
    },
    
    isMobile: () => {
        return window.innerWidth < 768 || /Android|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    },
    
    generateId: (prefix = 'id') => {
        return `${prefix}_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
    }
};

document.addEventListener('alpine:init', () => {
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    
                    if (img.dataset.src) {
                        img.src = img.dataset.src;
                        img.removeAttribute('data-src');
                    }
                    if (img.dataset.srcset) {
                        img.srcset = img.dataset.srcset;
                        img.removeAttribute('data-srcset');
                    }
                    
                    img.style.opacity = '0';
                    img.onload = () => {
                        img.style.transition = 'opacity 0.3s ease';
                        img.style.opacity = '1';
                        img.classList.remove('lazy');
                        img.classList.add('loaded');
                    };
                    
                    imageObserver.unobserve(img);
                }
            });
        }, {
            rootMargin: '50px 0px',
            threshold: 0.01
        });
        
        document.querySelectorAll('img[data-src], img.lazy').forEach(img => {
            imageObserver.observe(img);
        });
    }
    
    document.addEventListener('click', (e) => {
        const trackElement = e.target.closest('[data-track]');
        if (trackElement) {
            const trackData = {
                action: trackElement.dataset.track,
                category: trackElement.dataset.trackCategory || 'engagement',
                label: trackElement.dataset.trackLabel || trackElement.textContent.trim()
            };
            
            if (typeof gtag !== 'undefined') {
                gtag('event', trackData.action, {
                    event_category: trackData.category,
                    event_label: trackData.label
                });
            }
        }
    });
    
    document.querySelectorAll('.alert[data-auto-hide]').forEach(alert => {
        const delay = parseInt(alert.dataset.autoHide) || 5000;
        
        setTimeout(() => {
            alert.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-10px)';
            
            setTimeout(() => {
                if (alert.parentNode) {
                    alert.remove();
                }
            }, 300);
        }, delay);
    });
    
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const href = this.getAttribute('href');
            
            if (href === '#') {
                e.preventDefault();
                return;
            }
            
            const target = document.querySelector(href);
            if (target) {
                e.preventDefault();
                window.sInvestUtils.scrollTo(target, 80);
            }
        });
    });
    
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Tab') {
            const focusableElements = document.querySelectorAll(
                'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
            );
            
            const firstElement = focusableElements[0];
            const lastElement = focusableElements[focusableElements.length - 1];
            
            const activeModal = document.querySelector('.modal.active, .mobile-menu[style*="display: block"]');
            if (activeModal) {
                const modalFocusable = activeModal.querySelectorAll(
                    'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
                );
                
                if (modalFocusable.length > 0) {
                    const firstModal = modalFocusable[0];
                    const lastModal = modalFocusable[modalFocusable.length - 1];
                    
                    if (e.shiftKey && document.activeElement === firstModal) {
                        e.preventDefault();
                        lastModal.focus();
                    } else if (!e.shiftKey && document.activeElement === lastModal) {
                        e.preventDefault();
                        firstModal.focus();
                    }
                }
            }
        }
    });
});

if (!window.alpineInitialized) {
    if (typeof Alpine !== 'undefined') {
        Alpine.start();
        window.alpineInitialized = true;
    }
} else {
    // Alpine already running
}

document.addEventListener('DOMContentLoaded', () => {
    const style = document.createElement('style');
    style.textContent = `
        .fade-in {
            animation: fadeIn 0.3s ease-in-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .slide-in-right {
            animation: slideInRight 0.3s ease-in-out;
        }
        
        @keyframes slideInRight {
            from { opacity: 0; transform: translateX(20px); }
            to { opacity: 1; transform: translateX(0); }
        }
        
        .animate-fadeIn {
            animation: fadeIn 0.5s ease-in-out;
        }
        
        .loading-spinner {
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
    `;
    document.head.appendChild(style);
});