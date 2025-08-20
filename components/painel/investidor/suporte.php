<?php
/**
 * Seção Suporte – FAQs estilizados
 * components/painel/investidor/suporte.php
 */
defined('ABSPATH') || exit;

// Busca FAQs com cache
$faqs = wp_cache_get('faqs_list', 's-invest-theme');

if (false === $faqs) {
    $faqs = get_posts([
        'post_type'      => 'faq',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'orderby'        => 'menu_order',
        'order'          => 'ASC',
        'no_found_rows'  => true,
    ]);
    
    wp_cache_set('faqs_list', $faqs, 's-invest-theme', DAY_IN_SECONDS);
}

// Prepara dados para JavaScript de forma segura
$faqs_json = [];
foreach ($faqs as $faq) {
    $faqs_json[] = [
        'id' => $faq->ID,
        'question' => html_entity_decode(get_the_title($faq), ENT_QUOTES, 'UTF-8'),
        'answer' => html_entity_decode(apply_filters('the_content', $faq->post_content), ENT_QUOTES, 'UTF-8')
    ];
}
$faqs_js = json_encode($faqs_json, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
?>

<div class="flex flex-col lg:flex-row gap-8 py-10 main-content-mobile min-h-screen">
    <!-- Seção Principal de FAQs -->
    <div class="lg:w-2/3">
        <?php if (!empty($faqs)) : ?>
            <div x-data="faqComponent()" class="space-y-4" role="region" aria-live="polite">
                <!-- Barra de Pesquisa -->
                <div class="relative">
                    <input 
                        x-model="searchTerm"
                        type="text" 
                        placeholder="Pesquisar perguntas..."
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        aria-label="Pesquisar perguntas frequentes"
                        @input.debounce.300ms="filterFaqs()"
                    >
                    <svg class="absolute right-3 top-3.5 h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>

                <!-- Lista de FAQs -->
                <template x-for="faq in filteredFaqs" :key="faq.id">
                    <div class="border border-gray-200 rounded-lg overflow-hidden shadow-sm hover:shadow-md transition-shadow duration-200">
                        <!-- Pergunta -->
                        <button
                            @click="toggleFaq(faq.id)"
                            class="w-full px-6 py-4 flex justify-between items-center bg-gray-50 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50"
                            :aria-expanded="isOpen(faq.id)"
                            :aria-controls="'faq-answer-' + faq.id"
                        >
                            <span class="text-left text-gray-800 font-medium" x-text="faq.question"></span>
                            <svg
                                class="w-5 h-5 text-gray-500 transform transition-transform duration-200"
                                :class="{ 'rotate-90': isOpen(faq.id) }"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                aria-hidden="true"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </button>

                        <!-- Resposta -->
                        <div
                            :id="'faq-answer-' + faq.id"
                            x-show="isOpen(faq.id)"
                            x-collapse
                            class="px-6 py-4 bg-white text-gray-700 prose max-w-none"
                        >
                            <div x-html="faq.answer"></div>
                        </div>
                    </div>
                </template>

                <div x-show="filteredFaqs.length === 0" class="bg-gray-50 rounded-lg p-8 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">Nenhum resultado encontrado</h3>
                    <p class="mt-1 text-sm text-gray-500">Tente alterar seus termos de pesquisa.</p>
                </div>
            </div>

            <script>
            function faqComponent() {
                return {
                    faqs: <?php echo $faqs_js; ?>,
                    searchTerm: '',
                    filteredFaqs: [],
                    openFaqs: [],
                    
                    init() {
                        this.filteredFaqs = this.faqs;
                    },
                    
                    isOpen(id) {
                        return this.openFaqs.includes(id);
                    },
                    
                    toggleFaq(id) {
                        if (this.isOpen(id)) {
                            this.openFaqs = this.openFaqs.filter(faqId => faqId !== id);
                        } else {
                            this.openFaqs.push(id);
                        }
                    },
                    
                    filterFaqs() {
                        if (!this.searchTerm) {
                            this.filteredFaqs = this.faqs;
                            return;
                        }
                        
                        const term = this.searchTerm.toLowerCase();
                        this.filteredFaqs = this.faqs.filter(faq => 
                            faq.question.toLowerCase().includes(term) || 
                            faq.answer.toLowerCase().includes(term)
                        );
                    }
                }
            }
            </script>
        <?php else : ?>
            <div class="bg-gray-50 rounded-lg p-8 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">Nenhuma FAQ disponível</h3>
                <p class="mt-1 text-sm text-gray-500">Novas perguntas frequentes serão adicionadas em breve.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Sidebar de Contato -->
    <div class="lg:w-1/3">
        <div class="bg-white p-6 rounded-2xl shadow">
            <h2 class="text-lg font-semibold mb-4">Precisa de ajuda adicional?</h2>
            <div class="space-y-4">
                <div>
                    <h3 class="font-medium text-gray-900 mb-2">📞 Telefone</h3>
                    <a href="tel:+5519999954240" class="text-blue-600 hover:text-blue-800">(19) 9 9995-4240</a>
                </div>
                <div>
                    <h3 class="font-medium text-gray-900 mb-2">✉️ Email</h3>
                    <a href="mailto:suporte@bravaforte.com" class="text-blue-600 hover:text-blue-800">suporte@bravaforte.com</a>
                </div>
                <div>
                    <h3 class="font-medium text-gray-900 mb-2">🕒 Horário de Atendimento</h3>
                    <p class="text-gray-600">Segunda a Sexta, das 9h às 18h</p>
                </div>
                <div class="pt-4 border-t border-gray-100">
                    <a 
                        href="<?php echo esc_url(home_url('/contato')); ?>" 
                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                    >
                        <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                        Enviar mensagem
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>