<?php
/**
 * Botão Flutuante + Menu Lateral de Filtros - COMPONENTE SEPARADO
 * components/filtros-mobile-lateral.php
 */
defined('ABSPATH') || exit;

// Busca taxonomias
$tipos_produto = get_terms([
    'taxonomy' => 'tipo_produto',
    'hide_empty' => false,
]);

$impostos = get_terms([
    'taxonomy' => 'imposto',
    'hide_empty' => false,
]);

$modalidades = get_terms([
    'taxonomy' => 'modalidade',
    'hide_empty' => false,
]);
?>

<!-- ABA LATERAL + MENU LATERAL - SÓ MOBILE -->
<div x-data="filtrosMobileLateral()" 
     class="lg:hidden">
     
    <!-- ABA LATERAL QUE SAI DA ESQUERDA -->
    <div x-show="mostrarAba && !abaEscondidaTemporariamente" 
         x-transition:enter="transform transition ease-out duration-500"
         x-transition:enter-start="-translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transform transition ease-in duration-300"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="-translate-x-full"
         class="fixed left-0 top-1/3 z-10 transform -translate-y-1/2"
         x-cloak>
         
        <button @click="abrirMenu()" 
                class="bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white shadow-xl transition-all duration-300 flex items-center"
                style="border-radius: 0 12px 12px 0; padding: 16px 12px 16px 8px;"
                :class="{ 'translate-x-0': !menuAberto, '-translate-x-full': menuAberto }">
            
            <!-- Conteúdo da aba -->
            <div class="flex flex-col items-center space-y-1">
                <i class="fas fa-filter text-lg"></i>
                <span class="text-xs font-medium leading-none">FILTROS</span>
                
                <!-- Badge de filtros ativos -->
                <span x-show="contarFiltros() > 0" 
                      x-text="contarFiltros()"
                      class="bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center font-bold animate-pulse mt-1"></span>
            </div>
        </button>
    </div>

    <!-- OVERLAY -->
    <div x-show="menuAberto" 
         x-transition:enter="transition-opacity ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="fecharMenu()"
         class="fixed inset-0 bg-black/50 z-5"
         x-cloak></div>

    <!-- MENU LATERAL -->
    <div x-show="menuAberto"
         x-transition:enter="transform transition ease-out duration-300"
         x-transition:enter-start="-translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transform transition ease-in duration-200"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="-translate-x-full"
         class="fixed left-0 top-0 h-full w-80 max-w-[85vw] bg-white shadow-2xl z-25 overflow-y-auto"
         @click.stop
         x-cloak>
         
        <!-- HEADER -->
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 text-white p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-xl font-bold">Filtros</h3>
                    <p class="text-blue-100 text-sm">Refine sua busca</p>
                </div>
                <button @click="fecharMenu()" 
                        class="p-2 hover:bg-white/20 rounded-full transition-colors">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>
            
            <!-- Contador de filtros ativos -->
            <div x-show="contarFiltros() > 0" 
                 class="mt-3 bg-white/20 rounded-lg p-2 text-center">
                <span class="text-sm">
                    <i class="fas fa-check-circle mr-1"></i>
                    <span x-text="contarFiltros()"></span> filtro(s) ativo(s)
                </span>
            </div>
        </div>

        <!-- CONTEÚDO DOS FILTROS -->
        <div class="p-6 space-y-6">
            <!-- Classe de Ativos -->
            <div class="filter-group">
                <label class="block text-sm font-semibold text-gray-800 mb-3">
                    <i class="fas fa-chart-pie mr-2 text-blue-600"></i>
                    Classe de Ativos
                </label>
                <select x-model="filtros.tipo_produto" 
                        class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:bg-white transition-all">
                    <option value="">Todas as Classes</option>
                    <?php foreach ($tipos_produto as $tipo) : ?>
                        <option value="<?php echo esc_attr($tipo->slug); ?>">
                            <?php echo esc_html($tipo->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div x-show="filtros.tipo_produto !== ''" 
                     class="mt-2 text-xs text-blue-600 font-medium flex items-center">
                    <i class="fas fa-check mr-1"></i>
                    Filtro aplicado
                </div>
            </div>

            <!-- Tributação -->
            <div class="filter-group">
                <label class="block text-sm font-semibold text-gray-800 mb-3">
                    <i class="fas fa-receipt mr-2 text-green-600"></i>
                    Tributação
                </label>
                <select x-model="filtros.imposto" 
                        class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:bg-white transition-all">
                    <option value="">Qualquer Tributação</option>
                    <?php foreach ($impostos as $imposto) : ?>
                        <option value="<?php echo esc_attr($imposto->slug); ?>">
                            <?php echo esc_html($imposto->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div x-show="filtros.imposto !== ''" 
                     class="mt-2 text-xs text-blue-600 font-medium flex items-center">
                    <i class="fas fa-check mr-1"></i>
                    Filtro aplicado
                </div>
            </div>

            <!-- Modalidade -->
            <div class="filter-group">
                <label class="block text-sm font-semibold text-gray-800 mb-3">
                    <i class="fas fa-cogs mr-2 text-purple-600"></i>
                    Modalidade
                </label>
                <select x-model="filtros.modalidade" 
                        class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:bg-white transition-all">
                    <option value="">Qualquer Modalidade</option>
                    <?php foreach ($modalidades as $modalidade) : ?>
                        <option value="<?php echo esc_attr($modalidade->slug); ?>">
                            <?php echo esc_html($modalidade->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div x-show="filtros.modalidade !== ''" 
                     class="mt-2 text-xs text-blue-600 font-medium flex items-center">
                    <i class="fas fa-check mr-1"></i>
                    Filtro aplicado
                </div>
            </div>

            <!-- Prazo -->
            <div class="filter-group">
                <label class="block text-sm font-semibold text-gray-800 mb-3">
                    <i class="fas fa-calendar-alt mr-2 text-orange-600"></i>
                    Prazo Mínimo
                </label>
                <select x-model="filtros.prazo" 
                        class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:bg-white transition-all">
                    <option value="">Qualquer Prazo</option>
                    <option value="12">≥ 12 meses</option>
                    <option value="18">≥ 18 meses</option>
                    <option value="24">≥ 24 meses</option>
                    <option value="36">≥ 36 meses</option>
                </select>
                <div x-show="filtros.prazo !== ''" 
                     class="mt-2 text-xs text-blue-600 font-medium flex items-center">
                    <i class="fas fa-check mr-1"></i>
                    Filtro aplicado
                </div>
            </div>

            <!-- Rentabilidade -->
            <div class="filter-group">
                <label class="block text-sm font-semibold text-gray-800 mb-3">
                    <i class="fas fa-percentage mr-2 text-green-600"></i>
                    Rentabilidade Mínima
                </label>
                <select x-model="filtros.valor" 
                        class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:bg-white transition-all">
                    <option value="">Qualquer Rentabilidade</option>
                    <option value="15">≥ 15% ao ano</option>
                    <option value="18">≥ 18% ao ano</option>
                    <option value="20">≥ 20% ao ano</option>
                    <option value="25">≥ 25% ao ano</option>
                </select>
                <div x-show="filtros.valor !== ''" 
                     class="mt-2 text-xs text-blue-600 font-medium flex items-center">
                    <i class="fas fa-check mr-1"></i>
                    Filtro aplicado
                </div>
            </div>

            <!-- Ordenação -->
            <div class="filter-group">
                <label class="block text-sm font-semibold text-gray-800 mb-3">
                    <i class="fas fa-sort mr-2 text-gray-600"></i>
                    Ordenar por
                </label>
                <div class="grid grid-cols-1 gap-3">
                    <button @click="filtros.ordem = 'DESC'" 
                            :class="filtros.ordem === 'DESC' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                            class="px-4 py-3 rounded-lg text-sm font-medium transition-colors">
                        <i class="fas fa-clock mr-1"></i>
                        Mais Recentes
                    </button>
                    <button @click="filtros.ordem = 'ASC'" 
                            :class="filtros.ordem === 'ASC' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                            class="px-4 py-3 rounded-lg text-sm font-medium transition-colors">
                        <i class="fas fa-history mr-1"></i>
                        Mais Antigos
                    </button>
                </div>
            </div>

            <!-- Resumo dos Filtros Ativos -->
            <div x-show="contarFiltros() > 0" 
                 class="bg-blue-50 rounded-xl p-4 border border-blue-200">
                <h4 class="text-sm font-semibold text-blue-800 mb-3 flex items-center">
                    <i class="fas fa-list-check mr-2"></i>
                    Resumo dos Filtros
                </h4>
                <div class="space-y-2 text-xs text-blue-700">
                    <div x-show="filtros.tipo_produto !== ''" class="flex items-center">
                        <span class="w-20 font-medium">Classe:</span>
                        <span class="bg-blue-100 px-2 py-1 rounded" x-text="filtros.tipo_produto"></span>
                    </div>
                    <div x-show="filtros.imposto !== ''" class="flex items-center">
                        <span class="w-20 font-medium">Imposto:</span>
                        <span class="bg-green-100 px-2 py-1 rounded" x-text="filtros.imposto"></span>
                    </div>
                    <div x-show="filtros.modalidade !== ''" class="flex items-center">
                        <span class="w-20 font-medium">Modalidade:</span>
                        <span class="bg-purple-100 px-2 py-1 rounded" x-text="filtros.modalidade"></span>
                    </div>
                    <div x-show="filtros.prazo !== ''" class="flex items-center">
                        <span class="w-20 font-medium">Prazo:</span>
                        <span class="bg-orange-100 px-2 py-1 rounded" x-text="filtros.prazo + ' meses+'"></span>
                    </div>
                    <div x-show="filtros.valor !== ''" class="flex items-center">
                        <span class="w-20 font-medium">Rentab.:</span>
                        <span class="bg-green-100 px-2 py-1 rounded" x-text="filtros.valor + '% a.a+'"></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- FOOTER FIXO -->
        <div class="sticky bottom-0 bg-white border-t shadow-lg p-6">
            <div class="space-y-3">
                <div class="grid gap-3">
                    <button @click="limparTodosFiltros()" 
                            class="flex-1 px-4 py-3 border-2 border-gray-300 text-gray-700 rounded-xl font-medium hover:bg-gray-50 transition-colors flex items-center justify-center">
                        <i class="fas fa-eraser mr-2"></i>
                        Limpar Tudo
                    </button>
                    <button @click="aplicarFiltros()" 
                            class="flex-1 px-4 py-3 bg-blue-600 text-white rounded-xl font-medium hover:bg-blue-700 transition-colors flex items-center justify-center shadow-lg">
                        <i class="fas fa-search mr-2"></i>
                        Aplicar Filtros
                    </button>
                </div>
                
                <!-- Feedback visual -->
                <div x-show="aplicando" x-cloak class="text-center">
                    <div class="inline-flex items-center text-sm text-blue-600">
                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Aplicando filtros...
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Componente do menu lateral mobile
document.addEventListener('alpine:init', () => {
    Alpine.data('filtrosMobileLateral', () => ({
        menuAberto: false,
        aplicando: false,
        mostrarAba: false,
        abaEscondidaTemporariamente: false,
        
        filtros: {
            tipo_produto: '',
            imposto: '',
            modalidade: '',
            prazo: '',
            valor: '',
            ordem: 'DESC'
        },
        
        init() {
            this.setupScrollDetection();
            this.setupElementorMenuDetection();
            
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && this.menuAberto) {
                    this.fecharMenu();
                }
            });

            this.$watch('menuAberto', (aberto) => {
                document.body.style.overflow = aberto ? 'hidden' : '';
            });
        },
        
        setupElementorMenuDetection() {
            const observer = new MutationObserver((mutations) => {
                mutations.forEach((mutation) => {
                    if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                        const body = mutation.target;
                        
                        const elementorMenuOpen = body.classList.contains('elementor-menu-open') ||
                                                 body.classList.contains('elementor-nav-menu--open') ||
                                                 body.classList.contains('e-con-full-screen-open') ||
                                                 document.querySelector('.elementor-nav-menu--overlay.elementor-nav-menu--active');
                        
                        if (elementorMenuOpen) {
                            this.esconderAbaTemporariamente();
                        } else {
                            this.restaurarAba();
                        }
                    }
                });
            });
            
            observer.observe(document.body, {
                attributes: true,
                attributeFilter: ['class']
            });
            
            const elementorMenus = document.querySelectorAll('[class*="elementor-nav-menu"], [class*="elementor-menu"]');
            elementorMenus.forEach(menu => {
                observer.observe(menu, {
                    attributes: true,
                    attributeFilter: ['class']
                });
            });
        },
        
        esconderAbaTemporariamente() {
            this.abaEscondidaTemporariamente = true;
            if (this.menuAberto) {
                this.fecharMenu();
            }
        },
        
        restaurarAba() {
            this.abaEscondidaTemporariamente = false;
        },
        
        setupScrollDetection() {
            const heroSection = document.querySelector('section');
            
            if (heroSection) {
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        this.mostrarAba = !entry.isIntersecting;
                    });
                }, {
                    threshold: 0.1,
                    rootMargin: '-50px 0px 0px 0px'
                });
                
                observer.observe(heroSection);
            } else {
                setTimeout(() => {
                    this.mostrarAba = true;
                }, 500);
            }
        },
        
        abrirMenu() {
            this.sincronizarFiltros();
            this.menuAberto = true;
        },
        
        fecharMenu() {
            this.menuAberto = false;
            document.body.style.overflow = '';
        },
        
        contarFiltros() {
            let count = 0;
            Object.entries(this.filtros).forEach(([key, value]) => {
                if (key !== 'ordem' && value && value !== '') count++;
            });
            return count;
        },
        
        limparTodosFiltros() {
            this.filtros = {
                tipo_produto: '',
                imposto: '',
                modalidade: '',
                prazo: '',
                valor: '',
                ordem: 'DESC'
            };
        },
        
        async aplicarFiltros() {
            this.aplicando = true;
            
            const mainComponent = this.encontrarComponentePrincipal();
            if (mainComponent) {
                Object.assign(mainComponent.filtros, this.filtros);
                
                if (typeof mainComponent.aplicarFiltros === 'function') {
                    await mainComponent.aplicarFiltros();
                }
            }
            
            setTimeout(() => {
                this.aplicando = false;
                this.fecharMenu();
                this.mostrarToast('Filtros aplicados com sucesso!', 'success');
            }, 800);
        },
        
        sincronizarFiltros() {
            const mainComponent = this.encontrarComponentePrincipal();
            if (mainComponent && mainComponent.filtros) {
                this.filtros = { ...mainComponent.filtros };
            }
        },
        
        encontrarComponentePrincipal() {
            const elements = document.querySelectorAll('[x-data]');
            for (let el of elements) {
                if (el._x_dataStack && el._x_dataStack[0] && el._x_dataStack[0].aplicarFiltros) {
                    return el._x_dataStack[0];
                }
            }
            return null;
        },
        
        mostrarToast(mensagem, tipo = 'success') {
            const toast = document.createElement('div');
            toast.className = `fixed top-4 right-4 z-50 px-4 py-3 rounded-lg text-white font-medium transform transition-all duration-300 ${tipo === 'success' ? 'bg-green-500' : 'bg-red-500'}`;
            toast.innerHTML = `
                <div class="flex items-center">
                    <i class="fas fa-${tipo === 'success' ? 'check' : 'exclamation-triangle'} mr-2"></i>
                    ${mensagem}
                </div>
            `;
            
            document.body.appendChild(toast);
            
            setTimeout(() => toast.style.transform = 'translateX(0)', 100);
            
            setTimeout(() => {
                toast.style.transform = 'translateX(100%)';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }
    }));
});
</script>

<style>
[x-cloak] { display: none !important; }

.fixed.left-0 {
    z-index: 30 !important;
}

.fixed.inset-0 {
    z-index: 20 !important;
}

.fixed.left-0.top-0 {
    z-index: 25 !important;
}

.fixed.left-0 button {
    box-shadow: 
        4px 0 15px rgba(0, 0, 0, 0.1),
        0 4px 15px rgba(0, 0, 0, 0.1);
    position: relative;
}

.fixed.left-0 button::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 3px;
    background: linear-gradient(to bottom, rgba(255,255,255,0.3), rgba(255,255,255,0.1));
    border-radius: 0 2px 2px 0;
}

.filter-group {
    transition: all 0.2s ease;
}

.filter-group:hover {
    transform: translateY(-1px);
}

.fixed.left-0 button:hover {
    transform: translateX(4px);
    box-shadow: 
        8px 0 25px rgba(59, 130, 246, 0.3),
        0 4px 20px rgba(0, 0, 0, 0.15);
}

@media (max-width: 640px) {
    .fixed.left-0 {
        left: 0 !important;
    }
    
    .fixed.left-0 button {
        padding: 12px 10px 12px 6px !important;
    }
}

.overflow-y-auto {
    scroll-behavior: smooth;
    -webkit-overflow-scrolling: touch;
}

body.elementor-menu-open .fixed.left-0,
body.elementor-nav-menu--open .fixed.left-0,
body.e-con-full-screen-open .fixed.left-0 {
    display: none !important;
}

.elementor-nav-menu--overlay.elementor-nav-menu--active ~ * .fixed.left-0 {
    display: none !important;
}
</style>