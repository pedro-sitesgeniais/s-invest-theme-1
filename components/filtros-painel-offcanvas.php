<?php
/**
 * Filtros Off-Canvas para Painel - VERSÃO CORRIGIDA
 * components/filtros-painel-offcanvas.php
 */
defined('ABSPATH') || exit;

$context = sanitize_key($args['context'] ?? 'produtos-gerais');
$component = $args['component'] ?? 'painelFiltros';

// Busca taxonomias
$tipos_produto = get_terms([
    'taxonomy' => 'tipo_produto',
    'hide_empty' => false,
]);

$impostos = get_terms([
    'taxonomy' => 'imposto',
    'hide_empty' => false,
]);
?>

<!-- CONTAINER DOS FILTROS - DESKTOP E MOBILE -->
<div x-data="painelOffcanvasFilters('<?php echo $context; ?>', '<?php echo $component; ?>')" 
     class="filtros-painel-container"
     style="position: relative; z-index: 1;">
     
    <!-- VERSÃO DESKTOP - LINHA HORIZONTAL -->
    <div class="hidden lg:block bg-white rounded-xl shadow-sm p-6 mb-8">
        <div class="flex flex-wrap items-center gap-4">
            <span class="text-sm font-semibold text-gray-700 whitespace-nowrap">Filtros:</span>
            
            <!-- Classe de Ativos -->
            <div class="flex-shrink-0">
                <select x-model="filtros.tipo_produto" 
                        @change="aplicarFiltros()"
                        class="appearance-none bg-white border border-gray-300 rounded-2xl px-5 py-3 text-sm shadow-sm hover:border-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-200 transition-colors">
                    <option value="">Todas as Classes</option>
                    <?php foreach ($tipos_produto as $tipo) : ?>
                        <option value="<?php echo esc_attr($tipo->slug); ?>">
                            <?php echo esc_html($tipo->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Tributação -->
            <div class="flex-shrink-0">
                <select x-model="filtros.imposto" 
                        @change="aplicarFiltros()"
                        class="appearance-none bg-white border border-gray-300 rounded-2xl px-5 py-3 text-sm shadow-sm hover:border-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-200 transition-colors">
                    <option value="">Qualquer Tributação</option>
                    <?php foreach ($impostos as $imposto) : ?>
                        <option value="<?php echo esc_attr($imposto->slug); ?>">
                            <?php echo esc_html($imposto->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                    </div>

            <!-- ✅ FILTROS ESPECÍFICOS POR CONTEXTO - CORRIGIDOS -->
            <?php if ($context === 'meus-investimentos') : ?>
                <!-- Status do Investimento (ativo/vendido) -->
                <div class="flex-shrink-0">
                    <select x-model="filtros.status" 
                            @change="aplicarFiltros()"
                            class="appearance-none bg-white border border-gray-300 rounded-2xl px-5 py-3 text-sm shadow-sm hover:border-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-200 transition-colors">
                        <option value="">Todos os Status</option>
                        <option value="ativo">Ativos</option>
                        <option value="vendido">Vendidos</option>
                    </select>
                </div>
            <?php else : ?>
                <!-- Status do Produto (ativo/encerrado) - APENAS PARA PRODUTOS GERAIS -->
                <div class="flex-shrink-0">
                    <select x-model="filtros.status_produto" 
                            @change="aplicarFiltros()"
                            class="appearance-none bg-white border border-gray-300 rounded-2xl px-5 py-3 text-sm shadow-sm hover:border-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-200 transition-colors">
                        <option value="">Todos os Status</option>
                        <option value="ativo">Ativo</option>
                        <option value="encerrado">Encerrado</option>
                    </select>
                </div>

                <!-- Prazo (para produtos gerais) -->
                <div class="flex-shrink-0">
                    <select x-model="filtros.prazo" 
                            @change="aplicarFiltros()"
                            class="appearance-none bg-white border border-gray-300 rounded-2xl px-5 py-3 text-sm shadow-sm hover:border-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-200 transition-colors">
                        <option value="">Qualquer Prazo</option>
                        <option value="12">≥ 12 meses</option>
                        <option value="18">≥ 18 meses</option>
                        <option value="24">≥ 24 meses</option>
                        <option value="36">≥ 36 meses</option>
                    </select>
                </div>

                <!-- Rentabilidade (para produtos gerais) -->
                <div class="flex-shrink-0">
                    <select x-model="filtros.valor" 
                            @change="aplicarFiltros()"
                            class="appearance-none bg-white border border-gray-300 rounded-2xl px-5 py-3 text-sm shadow-sm hover:border-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-200 transition-colors">
                        <option value="">Qualquer Rentab.</option>
                        <option value="15">≥ 15% a.a</option>
                        <option value="18">≥ 18% a.a</option>
                        <option value="20">≥ 20% a.a</option>
                        <option value="25">≥ 25% a.a</option>
                    </select>
                </div>
            <?php endif; ?>

            <!-- Ordenação -->
            <div class="flex-shrink-0">
                <select x-model="filtros.ordem" 
                        @change="aplicarFiltros()"
                        class="appearance-none bg-white border border-gray-300 rounded-2xl px-5 py-3 text-sm shadow-sm hover:border-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-200 transition-colors">
                    <option value="DESC">Mais Recente</option>
                    <option value="ASC">Mais Antigo</option>
                    <?php if ($context === 'meus-investimentos') : ?>
                        <!-- Removido filtros de rentabilidade conforme solicitado -->
                    <?php endif; ?>
                </select>
            </div>

            <!-- Botão Limpar -->
            <button @click="limparFiltros()" 
                    class="px-4 py-3 border border-gray-300 rounded-2xl text-sm hover:bg-gray-50 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-200 disabled:opacity-50"
                    :disabled="carregando">
                <span x-show="!carregando" class="flex items-center gap-2">
                    <i class="fas fa-eraser text-xs"></i>
                    Limpar
                </span>
                <span x-show="carregando" class="flex items-center gap-2">
                    <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="m4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 714 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Limpando...
                </span>
            </button>
        </div>
        
        <!-- Indicador de carregamento desktop -->
        <div x-show="carregando" class="mt-4 flex items-center justify-center text-gray-500">
            <svg class="animate-spin h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="m4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 714 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="text-sm">Aplicando filtros...</span>
        </div>
    </div>

    <!-- VERSÃO MOBILE - BOTÃO + OFF-CANVAS -->
    <div class="lg:hidden bg-white rounded-xl shadow-sm p-4 mb-8">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <span class="text-sm font-semibold text-gray-700">Filtros</span>
                <!-- Contador de filtros ativos -->
                <span x-show="contarFiltrosAtivos() > 0" 
                      x-text="contarFiltrosAtivos()"
                      class="bg-blue-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center font-bold animate-pulse"></span>
            </div>
            
            <button @click="abrirOffcanvas()" 
                    class="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors">
                <i class="fas fa-sliders-h"></i>
                <span>Filtrar</span>
                <span x-show="contarFiltrosAtivos() > 0" 
                      class="bg-white/20 text-xs rounded-full px-1.5 py-0.5 ml-1">
                    <span x-text="contarFiltrosAtivos()"></span>
                </span>
            </button>
        </div>
    </div>

    <!-- BACKDROP MOBILE -->
    <div x-show="offcanvasAberto" 
         x-transition:enter="transition-opacity ease-linear duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-linear duration-300"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="fecharOffcanvas()"
         class="lg:hidden fixed inset-0 bg-black/50"
         style="display: none; z-index: 9998 !important; position: fixed !important;"></div>

    <!-- OFF-CANVAS MOBILE - ABRE DA DIREITA -->
    <div x-show="offcanvasAberto"
         x-transition:enter="transform transition ease-out duration-300"
         x-transition:enter-start="translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transform transition ease-in duration-200"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="translate-x-full"
         class="lg:hidden fixed right-0 top-0 h-full w-80 max-w-[85vw] bg-white shadow-2xl overflow-y-auto"
         @click.stop
         style="display: none; z-index: 9999 !important; position: fixed !important;">
         
        <!-- HEADER -->
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 text-white p-6 sticky top-0 z-10">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-xl font-bold">Filtros</h3>
                    <p class="text-blue-100 text-sm">
                        <?php echo $context === 'meus-investimentos' ? 'Seus investimentos' : 'Produtos disponíveis'; ?>
                    </p>
                </div>
                <button @click="fecharOffcanvas()" 
                        class="p-2 hover:bg-white/20 rounded-full transition-colors">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>
            
            <!-- Contador de filtros ativos -->
            <div x-show="contarFiltrosAtivos() > 0" 
                 class="mt-3 bg-white/20 rounded-lg p-2 text-center">
                <span class="text-sm">
                    <i class="fas fa-check-circle mr-1"></i>
                    <span x-text="contarFiltrosAtivos()"></span> filtro(s) ativo(s)
                </span>
            </div>
        </div>

        <!-- CONTEÚDO DOS FILTROS MOBILE -->
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

            <!-- ✅ FILTROS ESPECÍFICOS POR CONTEXTO - MOBILE CORRIGIDO -->
            <?php if ($context === 'meus-investimentos') : ?>
                <!-- Status do Investimento -->
                <div class="filter-group">
                    <label class="block text-sm font-semibold text-gray-800 mb-3">
                        <i class="fas fa-user-check mr-2 text-indigo-600"></i>
                        Status do Investimento
                    </label>
                    <select x-model="filtros.status" 
                            class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:bg-white transition-all">
                        <option value="">Todos os Status</option>
                        <option value="ativo">Ativos</option>
                        <option value="vendido">Vendidos</option>
                    </select>
                    <div x-show="filtros.status !== ''" 
                         class="mt-2 text-xs text-blue-600 font-medium flex items-center">
                        <i class="fas fa-check mr-1"></i>
                        Filtro aplicado
                    </div>
                </div>
            <?php else : ?>
                <!-- Status do Produto - APENAS PARA PRODUTOS GERAIS -->
                <div class="filter-group">
                    <label class="block text-sm font-semibold text-gray-800 mb-3">
                        <i class="fas fa-toggle-on mr-2 text-blue-600"></i>
                        Status do Produto
                    </label>
                    <select x-model="filtros.status_produto" 
                            class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:bg-white transition-all">
                        <option value="">Todos os Status</option>
                        <option value="ativo">Ativo</option>
                        <option value="encerrado">Encerrado</option>
                    </select>
                    <div x-show="filtros.status_produto !== ''" 
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
            <?php endif; ?>

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
                    <?php if ($context === 'meus-investimentos') : ?>
                        <!-- Removido filtros de rentabilidade conforme solicitado -->
                    <?php endif; ?>
                </div>
            </div>

            <!-- Resumo dos Filtros Ativos -->
            <div x-show="contarFiltrosAtivos() > 0" 
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
                    <?php if ($context === 'meus-investimentos') : ?>
                        <div x-show="filtros.status !== ''" class="flex items-center">
                            <span class="w-20 font-medium">Status:</span>
                            <span class="bg-indigo-100 px-2 py-1 rounded" x-text="filtros.status"></span>
                        </div>
                    <?php else : ?>
                        <div x-show="filtros.status_produto !== ''" class="flex items-center">
                            <span class="w-20 font-medium">Status:</span>
                            <span class="bg-blue-100 px-2 py-1 rounded" x-text="filtros.status_produto"></span>
                        </div>
                        <div x-show="filtros.prazo !== ''" class="flex items-center">
                            <span class="w-20 font-medium">Prazo:</span>
                            <span class="bg-orange-100 px-2 py-1 rounded" x-text="filtros.prazo + ' meses+'"></span>
                        </div>
                        <div x-show="filtros.valor !== ''" class="flex items-center">
                            <span class="w-20 font-medium">Rentab.:</span>
                            <span class="bg-green-100 px-2 py-1 rounded" x-text="filtros.valor + '% a.a+'"></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- FOOTER FIXO -->
        <div class="sticky bottom-0 pb-20 bg-white border-t shadow-lg p-6">
            <div class="space-y-3">
                <div class="grid gap-3">
                    <button @click="limparTodosFiltros()" 
                            class="flex-1 px-4 py-3 border-2 border-gray-300 text-gray-700 rounded-xl font-medium hover:bg-gray-50 transition-colors flex items-center justify-center">
                        <i class="fas fa-eraser mr-2"></i>
                        Limpar Tudo
                    </button>
                    <button @click="aplicarFiltrosEFechar()" 
                            class="flex-1 px-4 py-3 bg-blue-600 text-white rounded-xl font-medium hover:bg-blue-700 transition-colors flex items-center justify-center shadow-lg">
                        <i class="fas fa-search mr-2"></i>
                        Aplicar Filtros
                    </button>
                </div>
                
                <!-- Feedback visual -->
                <div x-show="aplicandoFiltros" x-cloak class="text-center">
                    <div class="inline-flex items-center text-sm text-blue-600">
                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 714 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Aplicando filtros...
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ESTILOS -->
<style>
[x-cloak] { display: none !important; }

.filtros-painel-container {
    position: relative;
    z-index: 1;
}

/* FORÇA Z-INDEX MÁXIMO PARA O OFF-CANVAS */
.filtros-painel-container [x-show="offcanvasAberto"] {
    z-index: 9999 !important;
    position: fixed !important;
}

.filtros-painel-container [x-show="offcanvasAberto"]:not(.overflow-y-auto) {
    z-index: 9998 !important;
    position: fixed !important;
}

/* CSS ESPECÍFICO PARA SOBREPOR MENU BOTTOM */
@media (max-width: 1023px) {
    /* Backdrop */
    .filtros-painel-container .fixed.inset-0 {
        z-index: 9998 !important;
        position: fixed !important;
    }
    
    /* Off-canvas principal */
    .filtros-painel-container .fixed.right-0 {
        z-index: 9999 !important;
        position: fixed !important;
        left: auto !important;
        transform: translateX(0) !important;
    }
    
    /* Força todos os elementos do off-canvas */
    .filtros-painel-container .fixed {
        z-index: 9999 !important;
    }
    
    /* Garante que qualquer div do filtro fique acima */
    .filtros-painel-container div[style*="z-index"] {
        z-index: 9999 !important;
    }
}

/* REGRA ESPECÍFICA PARA DESKTOP */
@media (min-width: 1024px) {
    .filtros-painel-container .lg\:hidden {
        display: none !important;
    }
}

.filter-group {
    transition: all 0.2s ease;
}

.filter-group:hover {
    transform: translateY(-1px);
}

/* Otimizações mobile */
@media (max-width: 1023px) {
    .filtros-painel-container .overflow-y-auto {
        scroll-behavior: smooth;
        -webkit-overflow-scrolling: touch;
        z-index: 9999 !important;
    }
}

/* Animações suaves */
.filtros-painel-container select,
.filtros-painel-container button {
    transition: all 0.2s ease;
}

.filtros-painel-container select:focus,
.filtros-painel-container button:focus {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

/* FORÇA ABSOLUTA - CSS ESPECÍFICO PARA O MENU OFF-CANVAS */
body .filtros-painel-container .fixed[x-show="offcanvasAberto"] {
    z-index: 9999 !important;
    position: fixed !important;
}

body .filtros-painel-container .fixed[style*="z-index: 9999"] {
    z-index: 9999 !important;
    position: fixed !important;
}

body .filtros-painel-container .fixed[style*="z-index: 9998"] {
    z-index: 9998 !important;
    position: fixed !important;
}

/* ÚLTIMO RECURSO - FORÇA MÁXIMA */
.filtros-painel-container {
    --tw-z-index: 9999 !important;
}

.filtros-painel-container * {
    z-index: inherit !important;
}

/* CSS INLINE BACKUP */
.filtros-painel-container [style*="z-index: 9999"] {
    z-index: 9999 !important;
    position: fixed !important;
    top: 0 !important;
    right: 0 !important;
    height: 100vh !important;
    width: 320px !important;
    max-width: 85vw !important;
    background: white !important;
}

.filtros-painel-container [style*="z-index: 9998"] {
    z-index: 9998 !important;
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    right: 0 !important;
    bottom: 0 !important;
    background: rgba(0, 0, 0, 0.5) !important;
}
</style>

<!-- CSS INLINE ADICIONAL PARA GARANTIA MÁXIMA -->
<style id="offcanvas-force-zindex">
@media (max-width: 1023px) {
    div[x-data*="painelOffcanvasFilters"] .fixed[x-show="offcanvasAberto"] {
        z-index: 9999 !important;
        position: fixed !important;
    }
    
    div[x-data*="painelOffcanvasFilters"] .fixed[style*="z-index"] {
        z-index: 9999 !important;
        position: fixed !important;
    }
}
</style>

<!-- JAVASCRIPT -->
<script>
// Componente específico para filtros off-canvas do painel
document.addEventListener('alpine:init', () => {
    Alpine.data('painelOffcanvasFilters', (context, component) => ({
        offcanvasAberto: false,
        aplicandoFiltros: false,
        
        // Propriedades computadas baseadas no componente principal
        get filtros() {
            const mainComponent = this.getMainComponent();
            return mainComponent ? mainComponent.filtros : this.getDefaultFilters();
        },
        
        set filtros(value) {
            const mainComponent = this.getMainComponent();
            if (mainComponent) {
                Object.assign(mainComponent.filtros, value);
            }
        },
        
        get carregando() {
            const mainComponent = this.getMainComponent();
            return mainComponent ? mainComponent.carregando : false;
        },
        
        init() {
            // Fecha o off-canvas com ESC
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && this.offcanvasAberto) {
                    this.fecharOffcanvas();
                }
            });

            // Impede scroll do body quando off-canvas está aberto
            this.$watch('offcanvasAberto', (aberto) => {
                if (window.innerWidth < 1024) {
                    document.body.style.overflow = aberto ? 'hidden' : '';
                    
                    // FORÇA Z-INDEX NOS ELEMENTOS DIRETAMENTE
                    if (aberto) {
                        setTimeout(() => {
                            const offcanvasElements = this.$el.querySelectorAll('.fixed');
                            offcanvasElements.forEach(el => {
                                if (el.style.display !== 'none') {
                                    if (el.classList.contains('right-0') || el.classList.contains('overflow-y-auto')) {
                                        el.style.zIndex = '9999';
                                        el.style.position = 'fixed';
                                    } else {
                                        el.style.zIndex = '9998';
                                        el.style.position = 'fixed';
                                    }
                                }
                            });
                        }, 50);
                    }
                }
            });
        },
        
        getDefaultFilters() {
            if (context === 'meus-investimentos') {
                return {
                    tipo_produto: '',
                    imposto: '',
                    status: '',
                    ordem: 'DESC'
                };
            } else {
                return {
                    tipo_produto: '',
                    imposto: '',
                    status_produto: '',
                    prazo: '',
                    valor: '',
                    ordem: 'DESC'
                };
            }
        },
        
        getMainComponent() {
            // Busca o componente principal na página
            const elements = document.querySelectorAll('[x-data]');
            for (let el of elements) {
                if (el._x_dataStack && el._x_dataStack[0]) {
                    const data = el._x_dataStack[0];
                    if (data.aplicarFiltros && data.filtros) {
                        return data;
                    }
                }
            }
            return null;
        },
        
        contarFiltrosAtivos() {
            const filtros = this.filtros;
            let count = 0;
            
            Object.entries(filtros).forEach(([key, value]) => {
                if (key !== 'ordem' && value && value !== '') {
                    count++;
                }
            });
            
            return count;
        },
        
        abrirOffcanvas() {
            this.offcanvasAberto = true;
            
            // FORÇA CSS CRÍTICO PARA SOBREPOR MENU BOTTOM
            if (window.innerWidth < 1024) {
                const style = document.createElement('style');
                style.id = 'offcanvas-z-index-fix';
                style.innerHTML = `
                    .filtros-painel-container .fixed[x-show="offcanvasAberto"] {
                        z-index: 9999 !important;
                        position: fixed !important;
                    }
                    .filtros-painel-container .fixed[style*="z-index: 9999"] {
                        z-index: 9999 !important;
                        position: fixed !important;
                    }
                    .filtros-painel-container .fixed[style*="z-index: 9998"] {
                        z-index: 9998 !important;
                        position: fixed !important;
                    }
                `;
                
                if (!document.getElementById('offcanvas-z-index-fix')) {
                    document.head.appendChild(style);
                }
            }
        },
        
        fecharOffcanvas() {
            this.offcanvasAberto = false;
            document.body.style.overflow = '';
            
            // Remove o CSS crítico quando fecha
            const style = document.getElementById('offcanvas-z-index-fix');
            if (style) {
                style.remove();
            }
        },
        
        aplicarFiltros() {
            const mainComponent = this.getMainComponent();
            if (mainComponent && typeof mainComponent.aplicarFiltros === 'function') {
                mainComponent.aplicarFiltros();
            }
        },
        
        async aplicarFiltrosEFechar() {
            this.aplicandoFiltros = true;
            
            try {
                await this.aplicarFiltros();
                
                // Feedback visual
                setTimeout(() => {
                    this.aplicandoFiltros = false;
                    this.fecharOffcanvas();
                    this.mostrarToast('Filtros aplicados com sucesso!', 'success');
                }, 800);
            } catch (error) {
                this.aplicandoFiltros = false;
                this.mostrarToast('Erro ao aplicar filtros', 'error');
            }
        },
        
        limparFiltros() {
            const mainComponent = this.getMainComponent();
            if (mainComponent && typeof mainComponent.limparFiltros === 'function') {
                mainComponent.limparFiltros();
            }
        },
        
        limparTodosFiltros() {
            this.filtros = this.getDefaultFilters();
        },
        
        mostrarToast(mensagem, tipo = 'success') {
            const toast = document.createElement('div');
            toast.className = `fixed top-4 right-4 px-4 py-3 rounded-lg text-white font-medium transform transition-all duration-300 ${tipo === 'success' ? 'bg-green-500' : 'bg-red-500'}`;
            toast.style.zIndex = '10000';
            toast.style.position = 'fixed';
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
        },
        
        destroy() {
            document.body.style.overflow = '';
        }
    }));
});
</script>