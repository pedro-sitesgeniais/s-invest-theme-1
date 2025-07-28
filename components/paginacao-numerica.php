<?php
/**
 * Componente de Paginação Numérica Unificada
 * components/paginacao-numerica.php
 */
defined('ABSPATH') || exit;
?>

<div x-show="maxPaginas > 1" class="flex items-center justify-between bg-white px-6 py-4 rounded-xl shadow-sm mt-8">
    
    <!-- Informação da página atual -->
    <div class="hidden sm:block text-sm text-gray-700">
        Página <span x-text="pagina" class="font-medium"></span> de 
        <span x-text="maxPaginas" class="font-medium"></span>
    </div>
    
    <!-- Controles de navegação -->
    <div class="flex items-center justify-between w-full sm:w-auto sm:justify-start gap-4">
        
        <!-- Botão Anterior -->
        <button @click="irParaPagina(pagina - 1)" 
                :disabled="!temPaginaAnterior || carregando"
                :class="!temPaginaAnterior || carregando ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-50'"
                class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 transition-colors flex items-center gap-2">
            <i class="fas fa-chevron-left text-xs"></i>
            <span class="hidden sm:inline">Anterior</span>
        </button>

        <!-- Números das Páginas -->
        <div class="flex space-x-1 sm:space-x-2">
            
            <!-- Primeira página se não estiver visível -->
            <template x-if="pagina > 3 && maxPaginas > 5">
                <div class="flex items-center space-x-1 sm:space-x-2">
                    <button @click="irParaPagina(1)" 
                            class="px-3 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-50 transition-colors">
                        1
                    </button>
                    <span class="text-gray-500 px-1">...</span>
                </div>
            </template>

            <!-- Páginas visíveis -->
            <template x-for="p in paginasVisiveis" :key="p">
                <button @click="irParaPagina(p)" 
                        :class="p === pagina ? 'bg-blue-500 text-white border-blue-500' : 'border-gray-300 text-gray-700 hover:bg-gray-50'"
                        class="px-3 py-2 border rounded-lg text-sm font-medium transition-colors min-w-[2.5rem]"
                        x-text="p">
                </button>
            </template>

            <!-- Última página se não estiver visível -->
            <template x-if="pagina < maxPaginas - 2 && maxPaginas > 5">
                <div class="flex items-center space-x-1 sm:space-x-2">
                    <span class="text-gray-500 px-1">...</span>
                    <button @click="irParaPagina(maxPaginas)" 
                            class="px-3 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-50 transition-colors"
                            x-text="maxPaginas">
                    </button>
                </div>
            </template>
        </div>

        <!-- Botão Próximo -->
        <button @click="irParaPagina(pagina + 1)" 
                :disabled="!temProximaPagina || carregando"
                :class="!temProximaPagina || carregando ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-50'"
                class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 transition-colors flex items-center gap-2">
            <span class="hidden sm:inline">Próximo</span>
            <i class="fas fa-chevron-right text-xs"></i>
        </button>
    </div>
</div>