<?php
/**
 * Seção Produtos Gerais - VERSÃO COM NOVO FILTRO OFF-CANVAS
 * components/painel/investidor/produtos-gerais.php
 */
defined('ABSPATH') || exit;
?>

<div x-data="painelFiltros()" class="space-y-8 py-10 main-content-mobile min-h-screen" x-cloak>
    
    <!-- Título e contador -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <h1 class="text-2xl font-semibold text-gray-900">Produtos Gerais</h1>
        
        <!-- Contador de resultados -->
        <div x-show="!carregando && maxPaginas > 0" class="text-sm text-gray-500">
            <template x-if="maxPaginas > 1">
                <span>
                    Página <span x-text="pagina" class="font-medium"></span> de 
                    <span x-text="maxPaginas" class="font-medium"></span>
                </span>
            </template>
            <template x-if="maxPaginas === 1">
                <span>Todos os resultados</span>
            </template>
        </div>
    </div>

    <!-- NOVO FILTRO OFF-CANVAS -->
    <?php get_template_part('components/filtros-painel-offcanvas', null, [
        'context' => 'produtos-gerais',
        'component' => 'painelFiltros'
    ]); ?>

    <!-- Container de Resultados -->
    <div class="min-h-[500px]">
        
        <!-- Loading inicial -->
        <div x-show="carregando && !resultados" class="flex items-center justify-center py-20">
            <div class="text-center">
                <svg class="animate-spin h-8 w-8 text-blue-500 mx-auto mb-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4 zm2 5.291 A7.962 7.962 0 0,1 4,12 H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" /></path>
                </svg>
                <p class="text-gray-500">Carregando produtos...</p>
            </div>
        </div>

        <!-- Resultados -->
        <div x-show="!carregando || resultados" x-html="resultados">
            <!-- Conteúdo será inserido via AJAX -->
        </div>

        <!-- Loading durante filtros -->
        <div x-show="carregando && resultados" class="relative">
            <div class="absolute inset-0 bg-white/75 flex items-center justify-center z-10 rounded-xl">
                <div class="text-center">
                    <svg class="animate-spin h-6 w-6 text-blue-500 mx-auto mb-2" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4 zm2 5.291 A7.962 7.962 0 0,1 4,12 H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" /></path>
                    </svg>
                    <p class="text-sm text-gray-600">Atualizando...</p>
                </div>
            </div>
        </div>

        <!-- Mensagem de erro -->
        <div x-show="erro" class="bg-red-50 border border-red-200 rounded-xl p-6 text-center">
            <div class="text-red-600 mb-4">
                <i class="fas fa-exclamation-triangle text-2xl mb-2"></i>
                <p class="font-medium">Erro ao carregar produtos</p>
                <p class="text-sm mt-1" x-text="erro"></p>
            </div>
            <button @click="aplicarFiltros()" 
                    class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors">
                Tentar novamente
            </button>
        </div>
    </div>

    <!-- Paginação Numérica -->
    <?php get_template_part('components/paginacao-numerica'); ?>
</div>