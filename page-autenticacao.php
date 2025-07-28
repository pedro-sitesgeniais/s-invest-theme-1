<?php
/**
 * Template Name: Autenticação
 * Descrição: Sistema completo de autenticação com confirmação de e-mail
 */
defined('ABSPATH') || exit;

if (is_user_logged_in()) {
    wp_redirect(home_url('/painel'));
    exit;
}

get_header();

$registration_enabled = get_option('s_invest_user_registration', 'enabled') === 'enabled';
$auth_data = [
    'ajaxUrl' => admin_url('admin-ajax.php'),
    'nonces' => [
        'auth' => wp_create_nonce('auth_nonce'),
        'email' => wp_create_nonce('verificar_email'),
        'cpf' => wp_create_nonce('verificar_cpf')
    ],
    'registrationEnabled' => $registration_enabled,
    'errors' => [
        'hasError' => isset($_GET['erro']),
        'errorMessage' => isset($_GET['erro']) ? urldecode($_GET['erro']) : '',
        'errorField' => isset($_GET['campo']) ? $_GET['campo'] : ''
    ],
    'success' => [
        'confirmed' => isset($_GET['confirmed']),
        'reset' => isset($_GET['reset'])
    ]
];
?>

<script>
window.authPageData = <?php echo wp_json_encode($auth_data); ?>;

document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('view') === 'confirm' && typeof Alpine !== 'undefined') {
        setTimeout(() => {
            const authElement = document.querySelector('[x-data*="authPageV2"]');
            if (authElement && Alpine.$data(authElement)) {
                const component = Alpine.$data(authElement);
                component.setView('confirm');
                component.form.email = decodeURIComponent(urlParams.get('email') || '');
                component.showMessage('Cadastro realizado! Verifique seu e-mail.', 'success');
            }
        }, 500);
    }
});
</script>

<main class="min-h-screen bg-gradient-to-br from-secondary/50 via-accent/20 to-accent/80 relative overflow-hidden auth-main">
    
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <div class="absolute -top-40 -right-40 w-80 h-80 bg-secondary rounded-full mix-blend-multiply filter blur-xl opacity-20 animate-blob"></div>
        <div class="absolute -bottom-40 -left-40 w-80 h-80 bg-primary/70 rounded-full mix-blend-multiply filter blur-xl opacity-20 animate-blob animation-delay-2000"></div>
        <div class="absolute top-40 left-40 w-80 h-80 bg-accent rounded-full mix-blend-multiply filter blur-xl opacity-20 animate-blob animation-delay-4000"></div>
    </div>

    <div x-data="authPageV2" x-cloak class="auth-container relative z-10 max-w-[1440px] mx-auto px-4 py-12 flex items-center justify-center min-h-[calc(100vh-80px)]">
        
        <div class="w-full max-w-6xl bg-white/80 backdrop-blur-xl rounded-3xl shadow-2xl overflow-hidden border border-white/20">
            <div class="grid grid-cols-1 lg:grid-cols-2 min-h-[600px]">
                
                <!-- Coluna esquerda - Informações -->
                <div class="bg-radial-[at_25%_25%] from-secondary via-accent to-indigo-800 p-8 lg:p-12 flex flex-col justify-between relative overflow-hidden">
                    
                    <div class="relative z-10">
                        <div class="mb-8">
                            <?php
                            $logo_id = get_theme_mod('custom_logo');
                            if ($logo_id) {
                                echo wp_get_attachment_image($logo_id, 'medium', false, [
                                    'class' => 'h-14 w-auto filter brightness-0 invert',
                                    'alt' => get_bloginfo('name')
                                ]);
                            } else {
                                echo '<h1 class="text-3xl font-bold text-white">'.get_bloginfo('name').'</h1>';
                            }
                            ?>
                        </div>
                        
                        <div class="space-y-6">
                            <div x-show="currentView === 'login'" x-transition class="space-y-4">
                                <h2 class="text-3xl lg:text-4xl font-bold text-white">Bem-vindo de volta!</h2>
                                <p class="text-blue-100 text-lg">Acesse sua conta e continue gerenciando seus investimentos com segurança.</p>
                                <div class="flex flex-wrap gap-3">
                                    <span class="bg-white/20 px-3 py-1 rounded-full text-sm text-white">✓ Dashboard personalizado</span>
                                    <span class="bg-white/20 px-3 py-1 rounded-full text-sm text-white">✓ Relatórios em tempo real</span>
                                </div>
                            </div>
                            
                            <div x-show="currentView === 'register'" x-transition class="space-y-4">
                                <h2 class="text-3xl lg:text-4xl font-bold text-white">Comece hoje mesmo</h2>
                                <p class="text-blue-100 text-lg">Crie sua conta gratuita e descubra oportunidades exclusivas de investimento.</p>
                                <div class="flex flex-wrap gap-3">
                                    <span class="bg-white/20 px-3 py-1 rounded-full text-sm text-white">✓ Cadastro gratuito</span>
                                    <span class="bg-white/20 px-3 py-1 rounded-full text-sm text-white">✓ Acesso imediato</span>
                                </div>
                            </div>
                            
                            <div x-show="currentView === 'reset'" x-transition class="space-y-4">
                                <h2 class="text-3xl lg:text-4xl font-bold text-white">Recuperar senha</h2>
                                <p class="text-blue-100 text-lg">Digite seu e-mail e enviaremos um link para redefinir sua senha.</p>
                            </div>

                            <div x-show="currentView === 'confirm'" x-transition class="space-y-4">
                                <h2 class="text-3xl lg:text-4xl font-bold text-white">Confirme seu e-mail</h2>
                                <p class="text-blue-100 text-lg">Enviamos um link de confirmação para seu e-mail. Verifique sua caixa de entrada.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="relative z-10 flex flex-wrap gap-3">
                        <button @click="setView('login')" 
                                :class="currentView === 'login' ? 'bg-white text-blue-600' : 'bg-white/20 text-white hover:bg-white/30'"
                                class="px-6 py-3 rounded-xl font-semibold transition-all duration-300">
                            Entrar
                        </button>
                        
                        <?php if ($registration_enabled): ?>
                        <button @click="setView('register')" 
                                :class="currentView === 'register' ? 'bg-white text-blue-600' : 'bg-white/20 text-white hover:bg-white/30'"
                                class="px-6 py-3 rounded-xl font-semibold transition-all duration-300">
                            Cadastrar
                        </button>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Coluna direita - Formulários -->
                <div class="p-8 lg:p-12 flex flex-col justify-center">
                    
                    <!-- Mensagens de erro/sucesso -->
                    <div x-show="message.show" x-transition 
                         :class="message.type === 'success' ? 'bg-green-50 border-green-200 text-green-800' : 'bg-red-50 border-red-200 text-red-800'"
                         class="mb-6 p-4 rounded-xl border">
                        <div class="flex items-center">
                            <i :class="message.type === 'success' ? 'fas fa-check-circle text-green-500' : 'fas fa-exclamation-triangle text-red-500'" class="mr-3"></i>
                            <span x-text="message.text"></span>
                        </div>
                    </div>
                    
                    <!-- FORMULÁRIO DE LOGIN - CORRIGIDO PARA LOOP -->
                    <form x-show="currentView === 'login'" x-transition method="post" class="space-y-6" @submit="handleLoginSubmit()">
                        <input type="hidden" name="sky_auth_action" value="login">
                        <?php wp_nonce_field('login_nonce'); ?>
                        
                        <div class="space-y-5">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">E-mail ou usuário</label>
                                <input type="text" name="username" required
                                       class="w-full px-4 py-4 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-300"
                                       placeholder="Digite seu e-mail">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Senha</label>
                                <div class="relative">
                                    <input :type="showPassword.login ? 'text' : 'password'" name="password" required
                                           class="w-full px-4 py-4 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 pr-12 transition-all duration-300"
                                           placeholder="Digite sua senha">
                                    <button type="button" @click="showPassword.login = !showPassword.login" 
                                            class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                        <i :class="showPassword.login ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="flex items-center justify-between">
                                <label class="flex items-center space-x-2">
                                    <input type="checkbox" name="remember" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <span class="text-sm text-gray-600">Lembrar-me</span>
                                </label>
                                <button type="button" @click="setView('reset')" class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                                    Esqueci minha senha
                                </button>
                            </div>
                            
                            <button type="submit" :disabled="isLoading"
                                    class="w-full bg-gradient-to-r from-secondary to-accent text-white py-4 rounded-xl font-semibold text-lg hover:to-primary hover:from-slate-700 transform hover:scale-[1.02] transition-all duration-300 disabled:opacity-50">
                                <span x-show="!isLoading">Entrar</span>
                                <span x-show="isLoading" class="flex items-center justify-center">
                                    <div class="animate-spin h-5 w-5 mr-3 border-2 border-white border-t-transparent rounded-full"></div>
                                    Entrando...
                                </span>
                            </button>
                        </div>
                    </form>

                    <!-- FORMULÁRIO DE CADASTRO - CORRIGIDO -->
                    <?php if ($registration_enabled): ?>
                    <form x-show="currentView === 'register'" x-transition method="post" class="space-y-6" 
                          @submit.prevent="handleRegistration()" x-ref="registrationForm">
                        <input type="hidden" name="sky_auth_action" value="register">
                        
                        <!-- CAMPOS OCULTOS PARA SINCRONIZAÇÃO -->
                        <input type="hidden" name="first_name" :value="form.firstName">
                        <input type="hidden" name="last_name" :value="form.lastName">
                        <input type="hidden" name="email" :value="form.email">
                        <input type="hidden" name="password" :value="form.password">
                        <input type="hidden" name="password_confirmation" :value="form.passwordConfirm">
                        <input type="hidden" name="cpf" :value="form.cpf">
                        <input type="hidden" name="telefone" :value="form.phone">
                        <!-- CORREÇÃO PRINCIPAL: Campo oculto para termos -->
                        <input type="hidden" name="terms" :value="form.terms ? '1' : ''">
                        
                        <div class="space-y-5">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Nome</label>
                                    <input type="text" x-model="form.firstName" required
                                           class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Sobrenome</label>
                                    <input type="text" x-model="form.lastName" required
                                           class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">E-mail</label>
                                <input type="email" x-model="form.email" @blur="validateEmail" required
                                       class="w-full px-4 py-4 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                       placeholder="seu@email.com">
                                <div x-show="emailValidation.checking" class="text-sm text-blue-600 mt-1 flex items-center">
                                    <svg class="animate-spin h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 714 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Verificando...
                                </div>
                                <p x-show="emailValidation.exists" class="text-sm text-red-600 mt-1 flex items-center">
                                    <i class="fas fa-exclamation-circle mr-2"></i>E-mail já cadastrado
                                </p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Senha</label>
                                <div class="relative">
                                    <input :type="showPassword.register ? 'text' : 'password'" 
                                           x-model="form.password" @input="validatePassword" required
                                           class="w-full px-4 py-4 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 pr-12"
                                           placeholder="Crie uma senha forte">
                                    <button type="button" @click="showPassword.register = !showPassword.register" 
                                            class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                        <i :class="showPassword.register ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                                    </button>
                                </div>
                                
                                <div x-show="form.password" x-transition class="mt-3 p-3 bg-gray-50 rounded-lg">
                                    <div class="flex items-center mb-2">
                                        <span class="text-sm font-medium">Força da senha:</span>
                                        <div class="flex ml-2 space-x-1">
                                            <template x-for="i in 4">
                                                <div :class="passwordStrength >= i ? (i <= 2 ? 'bg-red-500' : i === 3 ? 'bg-yellow-500' : 'bg-green-500') : 'bg-gray-200'" 
                                                     class="w-2 h-2 rounded-full transition-colors"></div>
                                            </template>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-2 gap-2 text-xs">
                                        <div :class="passwordRules.length ? 'text-green-600' : 'text-gray-400'" class="flex items-center">
                                            <i :class="passwordRules.length ? 'fas fa-check' : 'far fa-circle'" class="mr-1 w-3"></i>8+ caracteres
                                        </div>
                                        <div :class="passwordRules.upper ? 'text-green-600' : 'text-gray-400'" class="flex items-center">
                                            <i :class="passwordRules.upper ? 'fas fa-check' : 'far fa-circle'" class="mr-1 w-3"></i>Maiúscula
                                        </div>
                                        <div :class="passwordRules.lower ? 'text-green-600' : 'text-gray-400'" class="flex items-center">
                                            <i :class="passwordRules.lower ? 'fas fa-check' : 'far fa-circle'" class="mr-1 w-3"></i>Minúscula
                                        </div>
                                        <div :class="passwordRules.number ? 'text-green-600' : 'text-gray-400'" class="flex items-center">
                                            <i :class="passwordRules.number ? 'fas fa-check' : 'far fa-circle'" class="mr-1 w-3"></i>Número
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Confirmar senha</label>
                                <input :type="showPassword.register ? 'text' : 'password'" 
                                       x-model="form.passwordConfirm" @input="validatePassword" required
                                       class="w-full px-4 py-4 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                       placeholder="Digite a senha novamente">
                                <p x-show="form.passwordConfirm && form.password !== form.passwordConfirm" 
                                   class="text-sm text-red-600 mt-1 flex items-center">
                                    <i class="fas fa-exclamation-circle mr-2"></i>As senhas não coincidem
                                </p>
                            </div>
                            
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">CPF</label>
                                    <input type="text" x-model="form.cpf" x-mask="999.999.999-99" 
                                           @input="validateCPF" required
                                           class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-300"
                                           placeholder="000.000.000-00">
                                    
                                    <!-- Loading do CPF -->
                                    <div x-show="cpfValidation.checking" class="text-sm text-blue-600 mt-1 flex items-center">
                                        <svg class="animate-spin h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 714 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Verificando CPF...
                                    </div>
                                    
                                    <!-- CPF inválido -->
                                    <p x-show="form.cpf && cpfValidation.valid === false" 
                                       class="text-sm text-red-600 mt-1 flex items-center">
                                        <i class="fas fa-exclamation-circle mr-2"></i>CPF inválido
                                    </p>
                                    
                                    <!-- CPF já existe -->
                                    <p x-show="cpfValidation.valid === true && cpfValidation.exists" 
                                       class="text-sm text-red-600 mt-1 flex items-center">
                                        <i class="fas fa-exclamation-circle mr-2"></i>Este CPF já está cadastrado
                                    </p>
                                    
                                    <!-- CPF válido e disponível -->
                                    <p x-show="cpfValidation.valid === true && !cpfValidation.exists && !cpfValidation.checking" 
                                       class="text-sm text-green-600 mt-1 flex items-center">
                                        <i class="fas fa-check-circle mr-2"></i>CPF válido
                                    </p>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">WhatsApp</label>
                                    <input type="text" x-model="form.phone" x-mask="(99) 99999-9999" required
                                           class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                           placeholder="(11) 99999-9999">
                                </div>
                            </div>
                            
                            <!-- CORREÇÃO: Checkbox dos termos com dupla sincronização -->
                            <div class="flex items-start space-x-3">
                                <input type="checkbox" 
                                       x-model="form.terms" 
                                       required 
                                       class="mt-1 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <label class="text-sm text-gray-600 leading-relaxed cursor-pointer" @click="form.terms = !form.terms">
                                    Concordo com os <a href="/termos" target="_blank" class="text-blue-600 hover:underline" @click.stop>Termos de Uso</a> 
                                    e <a href="/privacidade" target="_blank" class="text-blue-600 hover:underline" @click.stop>Política de Privacidade</a>
                                </label>
                            </div>
                            
                            <button type="submit" :disabled="!canSubmitRegistration || isLoading"
                                    class="w-full py-4 rounded-xl font-semibold text-lg transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed"
                                    :class="canSubmitRegistration ? 'bg-gradient-to-r from-secondary to-accent text-white hover:to-primary hover:from-slate-700 hover:scale-[1.02]' : 'bg-gray-200 text-gray-500'">
                                <span x-show="!isLoading">Criar conta</span>
                                <span x-show="isLoading" class="flex items-center justify-center">
                                    <div class="animate-spin h-5 w-5 mr-3 border-2 border-white border-t-transparent rounded-full"></div>
                                    Criando conta...
                                </span>
                            </button>
                        </div>
                    </form>
                    <?php endif; ?>

                    <!-- FORMULÁRIO DE RESET DE SENHA -->
                    <form x-show="currentView === 'reset'" x-transition class="space-y-6" @submit.prevent="handlePasswordReset()">
                        <div class="space-y-5">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Seu e-mail</label>
                                <input type="email" x-model="resetEmail" required
                                       class="w-full px-4 py-4 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                       placeholder="Digite o e-mail da sua conta">
                            </div>
                            
                            <button type="submit" :disabled="isLoading || !resetEmail"
                                    class="w-full bg-gradient-to-r from-secondary to-accent text-white py-4 rounded-xl font-semibold text-lg hover:to-primary hover:from-slate-700 transform hover:scale-[1.02] transition-all duration-300 disabled:opacity-50">
                                <span x-show="!isLoading">Enviar link de recuperação</span>
                                <span x-show="isLoading" class="flex items-center justify-center">
                                    <div class="animate-spin h-5 w-5 mr-3 border-2 border-white border-t-transparent rounded-full"></div>
                                    Enviando...
                                </span>
                            </button>
                            
                            <button type="button" @click="setView('login')" 
                                    class="w-full text-gray-600 hover:text-gray-800 py-2 text-sm transition-colors">
                                ← Voltar ao login
                            </button>
                        </div>
                    </form>

                    <!-- TELA DE CONFIRMAÇÃO -->
                    <div x-show="currentView === 'confirm'" x-transition class="text-center space-y-6">
                        <div class="w-20 h-20 bg-blue-100 rounded-full flex items-center justify-center mx-auto">
                            <i class="fas fa-envelope text-3xl text-blue-600"></i>
                        </div>
                        <div class="space-y-3">
                            <h3 class="text-xl font-semibold text-gray-900">Verifique seu e-mail</h3>
                            <p class="text-gray-600">Enviamos um link de confirmação para <span class="font-medium" x-text="form.email"></span></p>
                            <p class="text-sm text-gray-500">Não recebeu? Verifique sua caixa de spam ou clique no botão abaixo para reenviar.</p>
                        </div>
                        <div class="space-y-3">
                            <button @click="resendConfirmation()" :disabled="isLoading"
                                    class="px-6 py-3 bg-secondary text-primary rounded-xl hover:bg-blue-700 transform hover:scale-[1.02] transition-all duration-300 disabled:opacity-50">
                                <span x-show="!isLoading">Reenviar e-mail</span>
                                <span x-show="isLoading" class="flex items-center">
                                    <svg class="animate-spin h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 714 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Reenviando...
                                </span>
                            </button>
                            <button @click="setView('login')" 
                                    class="block w-full text-gray-600 hover:text-gray-800 text-sm transition-colors">
                                ← Voltar ao login
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<style>
[x-cloak] { display: block !important; visibility: visible !important; opacity: 1 !important; }
.auth-main { padding-top: 80px; }
.auth-container { display: flex !important; visibility: visible !important; opacity: 1 !important; min-height: calc(100vh - 80px) !important; }

@keyframes blob {
    0% { transform: translate(0px, 0px) scale(1); }
    33% { transform: translate(30px, -50px) scale(1.1); }
    66% { transform: translate(-20px, 20px) scale(0.9); }
    100% { transform: translate(0px, 0px) scale(1); }
}

.animate-blob { animation: blob 7s infinite; }
.animation-delay-2000 { animation-delay: 2s; }
.animation-delay-4000 { animation-delay: 4s; }

input:focus { outline: none; }
button:focus { outline: 2px solid rgba(59, 130, 246, 0.5); outline-offset: 2px; }
.transition-all { transition-property: all; transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1); }

@media (max-width: 1023px) {
    .auth-container { padding: 1rem; }
    .lg\:grid-cols-2 { grid-template-columns: 1fr; }
    .lg\:p-12 { padding: 2rem; }
}

@media (max-width: 639px) {
    .sm\:grid-cols-2 { grid-template-columns: 1fr; }
    .text-3xl { font-size: 1.875rem; line-height: 2.25rem; }
    .lg\:text-4xl { font-size: 2.25rem; line-height: 2.5rem; }
}
</style>

<?php
if (isset($_POST['sky_auth_action'])) {
    require_once get_template_directory() . '/inc/auth-process.php';
}

get_footer();
?>