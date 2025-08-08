<?php
/**
 * Componente de Filtros - VERSÃO LIMPA E FUNCIONAL
 * components/filtros-investimentos.php
 * 
 * Mantém o original no desktop e adiciona off-canvas no mobile
 */
defined('ABSPATH') || exit;

$context = sanitize_key($args['context'] ?? 'public');

// Não usar sanitize_key no component pois converte para minúsculas
$component = $args['component'] ?? 'filtrosInvestimentos';
$allowed_components = ['filtrosInvestimentos', 'painelFiltros', 'meusFiltros'];
if (!in_array($component, $allowed_components)) {
    $component = 'filtrosInvestimentos';
}

// Busca taxonomias
$tipos_produto = get_terms([
    'taxonomy' => 'tipo_produto',
    'hide_empty' => false,
]);

$impostos = get_terms([
    'taxonomy' => 'imposto',
    'hide_empty' => false,
]);

// Classes CSS baseadas no contexto - ORIGINAL
$container_class = ($context === 'public') 
    ? 'inline-flex flex-wrap items-center gap-6 p-6 rounded-xl bg-white/90 backdrop-blur-sm shadow-lg' 
    : 'bg-white rounded-xl shadow-sm p-6 mb-8';

$input_class = 'appearance-none bg-white border border-gray-300 rounded-2xl px-5 py-3 text-sm shadow-sm hover:border-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-200 transition-colors';
?>

<div x-data="<?php echo esc_attr($component); ?>()" class="<?php echo $container_class; ?>">
    <!-- VERSÃO DESKTOP - ORIGINAL -->
    <div class="hidden lg:flex flex-wrap items-center gap-4 w-full">
        <span class="text-sm font-semibold text-gray-700 whitespace-nowrap">Filtros:</span>
        
        <!-- Classe de Ativos -->
        <div class="flex-shrink-0">
            <select x-model="filtros.tipo_produto" 
                    @change="aplicarFiltros()"
                    class="<?php echo $input_class; ?>">
                <option value="">Todas as Classes</option>
                <?php foreach ($tipos_produto as $tipo) : ?>
                    <option value="<?php echo esc_attr($tipo->slug); ?>">
                        <?php echo esc_html($tipo->name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Filtro de Imposto -->
        <div class="flex-shrink-0">
            <select x-model="filtros.imposto" 
                    @change="aplicarFiltros()"
                    class="<?php echo $input_class; ?>">
                <option value="">Qualquer Tributação</option>
                <?php foreach ($impostos as $imposto) : ?>
                    <option value="<?php echo esc_attr($imposto->slug); ?>">
                        <?php echo esc_html($imposto->name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Prazo -->
        <div class="flex-shrink-0">
            <select x-model="filtros.prazo" 
                    @change="aplicarFiltros()"
                    class="<?php echo $input_class; ?>">
                <option value="">Qualquer Prazo</option>
                <option value="12">Maior ou igual a 12 meses</option>
                <option value="18">Maior ou igual a 18 meses</option>
                <option value="24">Maior ou igual a 24 meses</option>
                <option value="36">Maior ou igual a 36 meses</option>
            </select>
        </div>

        <!-- Rentabilidade -->
        <div class="flex-shrink-0">
            <select x-model="filtros.valor" 
                    @change="aplicarFiltros()"
                    class="<?php echo $input_class; ?>">
                <option value="">Qualquer Rentab.</option>
                <option value="15">Maior ou igual a 15% a.a.</option>
                <option value="18">Maior ou igual a 18% a.a.</option>
                <option value="20">Maior ou igual a 20% a.a.</option>
                <option value="25">Maior ou igual a 25% a.a.</option>
            </select>
        </div>

        <!-- Ordenação -->
        <div class="flex-shrink-0">
            <select x-model="filtros.ordem" 
                    @change="aplicarFiltros()"
                    class="<?php echo $input_class; ?>">
                <option value="DESC">Mais Recente</option>
                <option value="ASC">Mais Antigo</option>
            </select>
        </div>

        <!-- Botão Limpar (nos painéis) -->
        <?php if ($context !== 'public') : ?>
            <button @click="limparFiltros()" 
                    class="px-4 py-3 border border-gray-300 rounded-2xl text-sm hover:bg-gray-50 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-200"
                    :disabled="carregando">
                <span x-show="!carregando" class="flex items-center gap-2">
                    <i class="fas fa-eraser text-xs"></i>
                    Limpar
                </span>
                <span x-show="carregando">...</span>
            </button>
        <?php endif; ?>
    </div>

    <!-- VERSÃO MOBILE - SÓ BOTÃO -->
    <div class="lg:hidden flex items-center justify-between w-full" 
         x-data="{ filtrosAbertos: false }">
        <span class="text-sm font-semibold text-gray-700">Filtros:</span>
        <button @click="filtrosAbertos = true" 
                class="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors">
            <i class="fas fa-filter"></i>
            <span>Filtrar</span>
        </button>

        <!-- OFF-CANVAS MOBILE -->
        <div x-show="filtrosAbertos" 
             class="fixed inset-0 z-50" 
             x-cloak
             @keydown.escape.window="filtrosAbertos = false"
             
            <!-- Overlay -->
            <div @click="filtrosAbertos = false" 
                 class="absolute inset-0 bg-black/50"></div>

            <!-- Panel -->
            <div class="absolute right-0 top-0 h-full w-80 max-w-[90vw] bg-white shadow-xl overflow-y-auto"
                 x-transition:enter="transform transition ease-out duration-300"
                 x-transition:enter-start="translate-x-full"
                 x-transition:enter-end="translate-x-0"
                 x-transition:leave="transform transition ease-in duration-200"
                 x-transition:leave-start="translate-x-0"
                 x-transition:leave-end="translate-x-full"
                 @click.stop>
                 
                <!-- Header -->
                <div class="flex items-center justify-between p-6 border-b">
                    <h3 class="text-lg font-semibold">Filtros</h3>
                    <button @click="filtrosAbertos = false" 
                            class="p-2 hover:bg-gray-100 rounded-full">
                        <i class="fas fa-times text-gray-500"></i>
                    </button>
                </div>

                <!-- Content -->
                <div class="p-6 space-y-6">
                    <!-- Classe de Ativos -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Classe de Ativos</label>
                        <select x-model="filtros.tipo_produto" 
                                class="w-full border border-gray-300 rounded-lg px-3 py-2">
                            <option value="">Todas as Classes</option>
                            <?php foreach ($tipos_produto as $tipo) : ?>
                                <option value="<?php echo esc_attr($tipo->slug); ?>">
                                    <?php echo esc_html($tipo->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Tributação -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tributação</label>
                        <select x-model="filtros.imposto" 
                                class="w-full border border-gray-300 rounded-lg px-3 py-2">
                            <option value="">Qualquer Tributação</option>
                            <?php foreach ($impostos as $imposto) : ?>
                                <option value="<?php echo esc_attr($imposto->slug); ?>">
                                    <?php echo esc_html($imposto->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Prazo -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Prazo</label>
                        <select x-model="filtros.prazo" 
                                class="w-full border border-gray-300 rounded-lg px-3 py-2">
                            <option value="">Qualquer Prazo</option>
                            <option value="12">≥ 12 meses</option>
                            <option value="18">≥ 18 meses</option>
                            <option value="24">≥ 24 meses</option>
                            <option value="36">≥ 36 meses</option>
                        </select>
                    </div>

                    <!-- Rentabilidade -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Rentabilidade</label>
                        <select x-model="filtros.valor" 
                                class="w-full border border-gray-300 rounded-lg px-3 py-2">
                            <option value="">Qualquer Rentab.</option>
                            <option value="15">≥ 15% a.a</option>
                            <option value="18">≥ 18% a.a</option>
                            <option value="20">≥ 20% a.a</option>
                            <option value="25">≥ 25% a.a</option>
                        </select>
                    </div>

                    <!-- Ordenação -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Ordenar por</label>
                        <select x-model="filtros.ordem" 
                                class="w-full border border-gray-300 rounded-lg px-3 py-2">
                            <option value="DESC">Mais Recente</option>
                            <option value="ASC">Mais Antigo</option>
                        </select>
                    </div>
                </div>

                <!-- Footer -->
                <div class="sticky bottom-0 bg-white border-t p-6">
                    <div class="flex gap-3">
                        <button @click="limparFiltros(); filtrosAbertos = false" 
                                class="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                            Limpar
                        </button>
                        <button @click="aplicarFiltros(); filtrosAbertos = false" 
                                class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                            Aplicar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
[x-cloak] { display: none !important; }

/* Forçar esconder no mobile/desktop */
@media (max-width: 1023px) {
    .hidden.lg\:flex {
        display: none !important;
    }
    .lg\:hidden {
        display: flex !important;
    }
}

@media (min-width: 1024px) {
    .hidden.lg\:flex {
        display: flex !important;
    }
    .lg\:hidden {
        display: none !important;
    }
}
</style>