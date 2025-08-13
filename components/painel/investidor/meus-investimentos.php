<?php
/**
 * Seção Meus Investimentos - VERSÃO COM NOVO FILTRO OFF-CANVAS
 * components/painel/investidor/meus-investimentos.php
 */
defined('ABSPATH') || exit;

$user_id = get_current_user_id();

// Buscar IDs dos investimentos do usuário (incluindo vendidos)
$aportes = get_posts([
    'post_type' => 'aporte',
    'posts_per_page' => -1,
    'post_status' => 'publish',
    'meta_query' => [
        ['key' => 'investidor_id', 'value' => $user_id]
    ],
    'fields' => 'ids'
]);

$investimento_ids = [];
foreach ($aportes as $aporte_id) {
    $inv_id = get_field('investment_id', $aporte_id);
    if ($inv_id) {
        $investimento_ids[] = $inv_id;
    }
}
$investimento_ids = array_unique($investimento_ids);

// Estatísticas dos aportes do usuário (se a função existir)
// $estatisticas = icf_get_estatisticas_aportes_usuario($user_id);
?>

<div x-data="meusFiltros()" class="space-y-8 py-10 main-content-mobile min-h-screen" x-cloak>
    
    <!-- Título e estatísticas -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Meus Investimentos</h1>
            <p class="text-sm text-gray-600 mt-1">Gerencie e acompanhe todos os seus investimentos</p>
        </div>
    </div>

    <!-- NOVO FILTRO OFF-CANVAS ESPECÍFICO PARA MEUS INVESTIMENTOS -->
    <?php get_template_part('components/filtros-painel-offcanvas', null, [
        'context' => 'meus-investimentos',
        'component' => 'meusFiltros'
    ]); ?>

    <!-- Container de Resultados -->
    <div class="min-h-[500px]">
        
        <!-- Estado inicial - sem investimentos -->
        <?php if (empty($investimento_ids)) : ?>
            <div class="bg-gray-50 rounded-lg p-8 text-center">
                <svg class="animate-spin h-6 w-6 text-blue-500 mx-auto mb-2" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4 zm2 5.291 A7.962 7.962 0 0,1 4,12 H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" /></path>
                        </svg>

                <h3 class="mt-2 text-sm font-medium text-gray-900">Nenhum investimento encontrado</h3>
                <p class="mt-1 text-sm text-gray-500">Você ainda não realizou aportes em nenhum investimento.</p>
                <div class="mt-6">
                    <a href="<?php echo esc_url(add_query_arg('secao', 'produtos-gerais')); ?>" 
                       class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                        Ver produtos disponíveis
                    </a>
                </div>
            </div>
        <?php else : ?>
            
            <!-- Loading inicial -->
            <div x-show="carregando && !resultados" class="flex items-center justify-center py-20">
                <div class="text-center">
                    <svg class="animate-spin h-6 w-6 text-blue-500 mx-auto mb-2" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4 zm2 5.291 A7.962 7.962 0 0,1 4,12 H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" /></path>
                        </svg>

                    <p class="text-gray-500">Carregando seus investimentos...</p>
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

                        <p class="text-sm text-gray-600">Atualizando filtros...</p>
                    </div>
                </div>
            </div>

            <!-- Paginação Numérica -->
            <?php get_template_part('components/paginacao-numerica'); ?>
            
        <?php endif; ?>

        <!-- Mensagem de erro -->
        <div x-show="erro" class="bg-red-50 border border-red-200 rounded-xl p-6 text-center">
            <div class="text-red-600 mb-4">
                <i class="fas fa-exclamation-triangle text-2xl mb-2"></i>
                <p class="font-medium">Erro ao carregar investimentos</p>
                <p class="text-sm mt-1" x-text="erro"></p>
            </div>
            <button @click="aplicarFiltros()" 
                    class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors">
                Tentar novamente
            </button>
        </div>
    </div>
</div>

<!-- Dados para o componente Alpine.js -->
<script>
// Passa os IDs dos investimentos do usuário para o JavaScript
window.userInvestmentIds = <?php echo json_encode($investimento_ids); ?>;

// Componente específico para meus investimentos
document.addEventListener('alpine:init', () => {
    Alpine.data('meusFiltros', () => ({
        // Estado dos filtros ATUALIZADO
        filtros: {
            tipo_produto: '',
            imposto: '',
            modalidade: '',
            status: '',
            rentabilidade: '',
            ordem: 'DESC'
        },
        
        // Estado da interface
        carregando: false,
        resultados: '',
        erro: null,
        pagina: 1,
        maxPaginas: 1,
        
        // Inicialização
        init() {
            if (window.userInvestmentIds && window.userInvestmentIds.length > 0) {
                this.aplicarFiltros();
            }
        },
        
        // Aplicar filtros (reseta a página)
        aplicarFiltros() {
            this.pagina = 1;
            this.buscarInvestimentos();
        },
        
        // Limpar todos os filtros
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
        
        // Ir para página específica
        irParaPagina(pagina) {
            if (pagina >= 1 && pagina <= this.maxPaginas) {
                this.pagina = pagina;
                this.buscarInvestimentos();
            }
        },
        
        // Fazer a busca AJAX
        async buscarInvestimentos() {
            if (!window.userInvestmentIds || window.userInvestmentIds.length === 0) {
                return;
            }
            
            this.carregando = true;
            this.erro = null;
            
            try {
                if (!window.investments_ajax) {
                    throw new Error('Configuração AJAX não encontrada');
                }
                
                const formData = new FormData();
                formData.append('action', 'filtrar_meus_investimentos');
                formData.append('nonce', window.investments_ajax.nonce);
                formData.append('paged', this.pagina);
                formData.append('investment_ids', JSON.stringify(window.userInvestmentIds));
                
                // Adiciona filtros
                Object.keys(this.filtros).forEach(key => {
                    if (this.filtros[key]) {
                        formData.append(key, this.filtros[key]);
                    }
                });
                
                const response = await fetch(window.investments_ajax.ajax_url, {
                    method: 'POST',
                    body: formData
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
                    throw new Error(data.data || 'Erro desconhecido');
                }
                
            } catch (error) {
                this.erro = error.message;
                this.resultados = `
                    <div class="text-center py-16 bg-white rounded-xl">
                        <div class="text-red-600 mb-4">
                            <i class="fas fa-exclamation-triangle text-4xl mb-4"></i>
                            <p class="text-lg font-medium">Erro ao carregar seus investimentos</p>
                            <p class="text-sm text-gray-500 mt-2">${error.message}</p>
                        </div>
                        <button onclick="location.reload()" 
                                class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                            Recarregar página
                        </button>
                    </div>
                `;
            } finally {
                this.carregando = false;
            }
        },
        
        // Gerar array de páginas para paginação
        get paginasVisiveis() {
            const paginas = [];
            const inicio = Math.max(1, this.pagina - 2);
            const fim = Math.min(this.maxPaginas, this.pagina + 2);
            
            for (let i = inicio; i <= fim; i++) {
                paginas.push(i);
            }
            
            return paginas;
        },
        
        // Verificar se tem página anterior/próxima
        get temPaginaAnterior() {
            return this.pagina > 1;
        },
        
        get temProximaPagina() {
            return this.pagina < this.maxPaginas;
        }
    }));
});
</script>