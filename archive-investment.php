<?php
/**
 * Template para Archive do CPT Investment - VERSÃO CORRIGIDA
 * archive-investment.php
 */
defined('ABSPATH') || exit;

// Restringe acesso
if (!current_user_can('administrator') && !current_user_can('investidor')) {
    wp_redirect(home_url('/acessar'));
    exit;
}

get_header();
?>

<style>
@keyframes drawLine {
    0% {
        stroke-dasharray: 500;
        stroke-dashoffset: 500;
    }
    100% {
        stroke-dasharray: 500;
        stroke-dashoffset: 0;
    }
}

@keyframes fadeInScale {
    0% {
        opacity: 0;
        transform: scale(0);
    }
    100% {
        opacity: 1;
        transform: scale(1);
    }
}

@keyframes floatUp {
    0%, 100% {
        transform: translateY(0px);
    }
    50% {
        transform: translateY(-8px);
    }
}

@keyframes pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.6;
    }
}

@keyframes fadeInArea {
    0% {
        opacity: 0;
    }
    100% {
        opacity: 1;
    }
}

.chart-container {
    animation: floatUp 4s ease-in-out infinite;
}

.chart-line {
    stroke-dasharray: 500;
    stroke-dashoffset: 500;
    animation: drawLine 3s ease-in-out 0.5s forwards;
}

.chart-area {
    opacity: 0;
    animation: fadeInArea 1s ease-out 0.8s forwards;
}

.chart-point {
    opacity: 0;
    animation: fadeInScale 0.6s ease-out forwards;
}

.chart-highlight {
    opacity: 0;
    animation: fadeInScale 0.8s ease-out forwards, pulse 2s ease-in-out infinite;
}

.live-indicator {
    animation: pulse 1.5s ease-in-out infinite;
}

.floating-element {
    animation: floatUp 3s ease-in-out infinite;
}

@media (max-width: 1023px) {
    .chart-container {
        display: none;
    }
}

.chart-svg-container {
    will-change: transform;
    transform: translateZ(0);
}

.chart-container {
    will-change: transform;
    backface-visibility: hidden;
}

@supports (-webkit-backdrop-filter: blur(10px)) {
    .chart-container {
        -webkit-backdrop-filter: blur(10px);
    }
}

.decorative-elements {
    overflow: hidden;
    pointer-events: none;
}

.chart-container * {
    transition: all 0.3s ease;
}

.chart-container {
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
}
</style>

<section class="pt-20 bg-gradient-to-br from-primary to-gray-800 text-white relative overflow-hidden">
    <div class="absolute inset-0 bg-black/20"></div>
    <div class="relative max-w-7xl mx-auto px-4 py-16 lg:py-20">
        <div class="grid lg:grid-cols-2 gap-8 items-center">
            <div>
                <h1 class="text-4xl lg:text-5xl font-bold mb-6">
                    Ofertas Públicas <span class="text-secondary">Simplificadas</span>
                </h1>
                <p class="text-xl text-blue-100 mb-8">
                    Descubra oportunidades de investimento criteriosamente selecionadas, 
                    reguladas pela CVM.
                </p>
                <div class="flex flex-wrap gap-4 mb-8">
                    <div class="flex items-center text-sm bg-white/10 rounded-full px-4 py-2">
                        <i class="fas fa-check text-green-400 mr-2"></i>
                        Regulado pela CVM
                    </div>
                    <div class="flex items-center text-sm bg-white/10 rounded-full px-4 py-2">
                        <i class="fas fa-check text-green-400 mr-2"></i>
                        Ativos Selecionados
                    </div>
                    <div class="flex items-center text-sm bg-white/10 rounded-full px-4 py-2">
                        <i class="fas fa-check text-green-400 mr-2"></i>
                        Transparência Total
                    </div>
                </div>
                <div class="flex flex-col sm:flex-row gap-4">
                    <a href="#investimentos" 
                       class="inline-flex items-center justify-center px-8 py-4 bg-secondary text-white rounded-lg font-semibold hover:bg-secondary/90 transition-all shadow-lg">
                        <i class="fas fa-search mr-2"></i>
                        Explorar Investimentos
                    </a>
                    <a href="/painel" 
                       class="inline-flex items-center justify-center px-8 py-4 border-2 border-white text-white rounded-lg font-semibold hover:bg-white hover:text-primary transition-all">
                        <i class="fas fa-user mr-2"></i>
                        Acessar Painel
                    </a>
                </div>
            </div>
            
            <div class="chart-container relative hidden lg:block">
                <div class="bg-white/10 backdrop-blur-sm rounded-3xl p-8 border border-white/20 relative">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h3 class="text-xl font-bold mb-1">Performance</h3>
                            <p class="text-blue-200 text-sm">Rentabilidade Média</p>
                        </div>
                        <div class="flex items-center space-x-2">
                            <div class="live-indicator w-3 h-3 bg-green-400 rounded-full"></div>
                            <span class="text-green-400 text-sm font-medium">+18.5%</span>
                            <span class="text-blue-200 text-sm">ao ano</span>
                        </div>
                    </div>

                    <div class="chart-svg-container">
                        <svg class="w-full h-40" viewBox="0 0 400 120" xmlns="http://www.w3.org/2000/svg">
                            <defs>
                                <linearGradient id="chartGradient" x1="0%" y1="0%" x2="0%" y2="100%">
                                    <stop offset="0%" style="stop-color:#10B981;stop-opacity:0.3" />
                                    <stop offset="100%" style="stop-color:#10B981;stop-opacity:0" />
                                </linearGradient>
                            </defs>
                            
                            <g stroke="rgba(255,255,255,0.1)" stroke-width="1" fill="none">
                                <line x1="0" y1="30" x2="400" y2="30" />
                                <line x1="0" y1="60" x2="400" y2="60" />
                                <line x1="0" y1="90" x2="400" y2="90" />
                            </g>
                            
                            <path d="M0 100 L50 85 L100 75 L150 80 L200 65 L250 45 L300 40 L350 25 L400 20 L400 120 L0 120 Z" 
                                  fill="url(#chartGradient)" 
                                  class="chart-area" />
                            
                            <path d="M0 100 L50 85 L100 75 L150 80 L200 65 L250 45 L300 40 L350 25 L400 20" 
                                  stroke="#10B981" 
                                  stroke-width="3" 
                                  fill="none" 
                                  class="chart-line" />
                            
                            <circle cx="0" cy="100" r="4" fill="#10B981" class="chart-point" style="animation-delay: 0.2s;" />
                            <circle cx="50" cy="85" r="4" fill="#10B981" class="chart-point" style="animation-delay: 0.4s;" />
                            <circle cx="100" cy="75" r="4" fill="#10B981" class="chart-point" style="animation-delay: 0.6s;" />
                            <circle cx="150" cy="80" r="4" fill="#10B981" class="chart-point" style="animation-delay: 0.8s;" />
                            <circle cx="200" cy="65" r="4" fill="#10B981" class="chart-point" style="animation-delay: 1.0s;" />
                            <circle cx="250" cy="45" r="4" fill="#10B981" class="chart-point" style="animation-delay: 1.2s;" />
                            <circle cx="300" cy="40" r="4" fill="#10B981" class="chart-point" style="animation-delay: 1.4s;" />
                            <circle cx="350" cy="25" r="4" fill="#10B981" class="chart-point" style="animation-delay: 1.6s;" />
                            
                            <circle cx="400" cy="20" r="6" fill="#ffffff" stroke="#10B981" stroke-width="3" class="chart-highlight" style="animation-delay: 1.8s;" />
                        </svg>
                    </div>

                    <div class="grid grid-cols-3 gap-4 mt-6 pt-6 border-t border-white/20">
                        <div class="text-center">
                            <div class="text-2xl font-bold text-white">2.5K+</div>
                            <div class="text-xs text-blue-200">Investidores</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-white">R$ 85M</div>
                            <div class="text-xs text-blue-200">Volume</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-white">150+</div>
                            <div class="text-xs text-blue-200">Produtos</div>
                        </div>
                    </div>

                    <div class="decorative-elements absolute inset-0 pointer-events-none">
                        <div class="absolute top-4 right-4 w-2 h-2 bg-white/30 rounded-full floating-element" style="animation-delay: 0s;"></div>
                        <div class="absolute -bottom-6 -left-6 w-12 h-12 bg-white/10 rounded-full floating-element" style="animation-delay: 1s;"></div>
                        <div class="absolute top-1/2 -right-8 w-6 h-6 bg-green-400/20 rounded-full floating-element" style="animation-delay: 2s;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<main role="main" 
      x-data="filtrosInvestimentos()" 
      x-init="aplicarFiltros(); initInfiniteScroll()"
      class="min-h-screen bg-gray-50"
      id="investimentos">

    <section class="hidden lg:flex filtros-sticky bg-white/80 backdrop-blur-sm top-17 border-b ">
        <div class="max-w-7xl mx-auto px-4 py-6">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-semibold">Explore Nossas Oportunidades</h2>
                <div x-show="!carregando" class="text-sm text-gray-600">
                </div>
            </div>
            
            <?php 
            get_template_part('components/filtros-investimentos', null, [
                'context' => 'public',
                'component' => 'filtrosInvestimentos'
            ]); 
            ?>
        </div>
    </section>

    <section class="max-w-7xl mx-auto px-4 py-12">
        
        <div x-ref="lista" 
             class="grid md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-3 gap-6 mb-12"
             role="region" 
             aria-live="polite">
        </div>

        <!-- Estado de carregamento -->
        <div x-show="carregando" x-cloak class="flex justify-center py-12">
            <div class="text-center">
                <div class="spinner w-10 h-10 border-4 border-gray-200 border-t-secondary rounded-full animate-spin mx-auto mb-4"></div>
                <p class="text-gray-600">Carregando investimentos...</p>
            </div>
        </div>

        <!-- Estado quando não há resultados E há filtros ativos -->
        <div x-show="!carregando && totalItens === 0 && temFiltrosAtivos() && !primeiraCarregamento" 
             x-cloak 
             class="text-center py-16">
            <div class="max-w-md mx-auto">
                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-search text-2xl text-gray-400"></i>
                </div>
                <h3 class="text-xl font-medium mb-2">Nenhum investimento encontrado</h3>
                <p class="text-gray-600 mb-6">Tente ajustar os filtros para ver mais opções.</p>
                <button @click="limparFiltros()"
                        class="s-btn s-btn-primary">
                    <i class="fas fa-refresh mr-2"></i>
                    Limpar filtros
                </button>
            </div>
        </div>

        <!-- Botão carregar mais (quando há mais itens) -->
        <div x-show="temMais && !carregando && totalItens > 0" x-cloak class="text-center py-8">
            <button @click="carregarMais()" 
                    class="s-btn s-btn-secondary px-6 py-3 shadow-lg transform hover:scale-105">
                <i class="fas fa-plus mr-2"></i>
                Carregar mais
            </button>
        </div>

        <!-- Estado quando todos os investimentos foram carregados (SEM FILTROS) -->
        <div x-show="!temMais && !carregando && totalItens > 0 && !temFiltrosAtivos()" 
             x-cloak 
             class="text-center py-8">
            <div class="inline-flex items-center px-4 py-2 bg-gray-100 rounded-full text-sm text-gray-600">
                <i class="fas fa-check-circle mr-2 text-green-500"></i>
                Todos os investimentos carregados
            </div>
        </div>

        <!-- Estado quando todos os investimentos foram carregados (COM FILTROS) -->
        <div x-show="!temMais && !carregando && totalItens > 0 && temFiltrosAtivos()" 
             x-cloak 
             class="text-center py-8">
            <div class="inline-flex items-center px-4 py-2 bg-blue-100 rounded-full text-sm text-blue-600">
                <i class="fas fa-filter mr-2 text-blue-500"></i>
                Todos os resultados filtrados exibidos
            </div>
        </div>

        <!-- Estado quando não há resultados E não há filtros (primeira carga sem dados) -->
        <div x-show="!carregando && totalItens === 0 && !temFiltrosAtivos() && !primeiraCarregamento" 
             x-cloak 
             class="text-center py-16">
            <div class="max-w-md mx-auto">
                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-building text-2xl text-gray-400"></i>
                </div>
                <h3 class="text-xl font-medium mb-2">Nenhum investimento disponível</h3>
                <p class="text-gray-600 mb-6">No momento não há investimentos cadastrados na plataforma.</p>
            </div>
        </div>

        <div x-show="temMais && !carregando" x-ref="sentinela" class="h-4" aria-hidden="true"></div>
    </section>

    <section class="bg-white border-t">
        <div class="max-w-7xl mx-auto px-4 py-16">
            <div class="grid md:grid-cols-3 gap-8">
                <?php 
                $features = [
                    ['icon' => 'shield-alt', 'color' => 'blue', 'title' => 'Segurança Regulatória', 'desc' => 'Todos os investimentos seguem as diretrizes da CVM.'],
                    ['icon' => 'chart-line', 'color' => 'green', 'title' => 'Transparência Total', 'desc' => 'Acompanhe seus investimentos em tempo real.'],
                    ['icon' => 'users', 'color' => 'purple', 'title' => 'Suporte Especializado', 'desc' => 'Nossa equipe está pronta para auxiliar você.']
                ];
                
                foreach ($features as $feature) :
                    $color = $feature['color'];
                ?>
                    <div class="text-center group">
                        <div class="w-16 h-16 bg-<?php echo $color; ?>-100 rounded-2xl flex items-center justify-center mx-auto mb-6 group-hover:bg-<?php echo $color; ?>-200 transition-colors">
                            <i class="fas fa-<?php echo $feature['icon']; ?> text-2xl text-<?php echo $color; ?>-600"></i>
                        </div>
                        <h3 class="text-xl font-semibold mb-4"><?php echo $feature['title']; ?></h3>
                        <p class="text-gray-600"><?php echo $feature['desc']; ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php get_template_part('components/filtros-mobile-lateral'); ?>
</main>

<?php get_footer(); ?>