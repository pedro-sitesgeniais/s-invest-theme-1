<?php
/**
 * Componente de Filtros UNIFICADO para Investimentos
 * components/filtros-investimentos.php
 * 
 * Uso:
 * get_template_part('components/filtros-investimentos', null, [
 *     'context' => 'public|panel|my-investments',
 *     'component' => 'filtrosInvestimentos|painelFiltros|meusFiltros'
 * ]);
 */
defined('ABSPATH') || exit;

$context = sanitize_key($args['context'] ?? 'public');
$component = sanitize_key($args['component'] ?? 'filtrosInvestimentos');

// Busca taxonomias
$tipos_produto = get_terms([
    'taxonomy' => 'tipo_produto',
    'hide_empty' => false,
]);

// Classes CSS baseadas no contexto
$container_class = ($context === 'public') 
    ? 'inline-flex flex-wrap items-center gap-6 p-6 rounded-xl bg-white/90 backdrop-blur-sm shadow-lg' 
    : 'bg-white rounded-xl shadow-sm p-6 mb-8';

$input_class = 'appearance-none bg-white border border-gray-300 rounded-2xl px-5 py-3 text-sm shadow-sm hover:border-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-200 transition-colors';
?>

<div x-data="<?php echo $component; ?>()" class="<?php echo $container_class; ?>">
    <div class="flex flex-wrap items-center gap-4">
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

        <!-- Duração -->
        <div class="flex-shrink-0">
            <select x-model="filtros.prazo" 
                    @change="aplicarFiltros()"
                    class="<?php echo $input_class; ?>">
                <option value="">Qualquer Prazo</option>
                <option value="12">≥ 12 meses</option>
                <option value="18">≥ 18 meses</option>
                <option value="24">≥ 24 meses</option>
                <option value="36">≥ 36 meses</option>
            </select>
        </div>

        <!-- Rentabilidade -->
        <div class="flex-shrink-0">
            <select x-model="filtros.valor" 
                    @change="aplicarFiltros()"
                    class="<?php echo $input_class; ?>">
                <option value="">Qualquer Rentab.</option>
                <option value="15">≥ 15% a.a</option>
                <option value="18">≥ 18% a.a</option>
                <option value="20">≥ 20% a.a</option>
                <option value="25">≥ 25% a.a</option>
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

        <!-- Botão Limpar (apenas nos painéis) -->
        <?php if ($context !== 'public') : ?>
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
        <?php endif; ?>
    </div>
    
    <!-- Indicador de carregamento (apenas painéis) -->
    <?php if ($context !== 'public') : ?>
        <div x-show="carregando" class="mt-4 flex items-center justify-center text-gray-500">
            <svg class="animate-spin h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="m4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 714 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="text-sm">Aplicando filtros...</span>
        </div>
    <?php endif; ?>
</div>