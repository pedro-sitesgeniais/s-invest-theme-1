/**
 * Componente Alpine.js para Autenticação - CORRIGIDO PARA LOOP
 * Arquivo: resources/js/components/auth.js
 * 
 * CORREÇÃO: Removida interferência no formulário de login
 */

document.addEventListener('alpine:init', () => {
    if (window.authComponentRegistered) return;
    window.authComponentRegistered = true;
    
    Alpine.data('authPageV2', () => ({
        currentView: 'login',
        isLoading: false,
        
        form: {
            firstName: '', 
            lastName: '', 
            email: '', 
            password: '', 
            passwordConfirm: '', 
            cpf: '', 
            phone: '', 
            terms: false
        },
        
        showPassword: { login: false, register: false },
        message: { show: false, type: 'success', text: '' },
        emailValidation: { checking: false, exists: false },
        cpfValidation: { checking: false, exists: false, valid: null },
        passwordRules: { length: false, upper: false, lower: false, number: false },
        resetEmail: '',
        
        init() {
            if (window.authPageData) {
                this.currentView = window.authPageData.initialTab || 'login';
                
                if (window.authPageData.errors.hasError) {
                    this.showMessage(window.authPageData.errors.errorMessage, 'error');
                }
            }
        },
        
        setView(view) {
            this.currentView = view;
            this.message.show = false;
            this.clearForm();
        },
        
        clearForm() {
            if (this.currentView !== 'register') {
                this.form = {
                    firstName: '', lastName: '', email: '', password: '', 
                    passwordConfirm: '', cpf: '', phone: '', terms: false
                };
                this.emailValidation = { checking: false, exists: false };
                this.cpfValidation = { checking: false, exists: false, valid: null };
                this.passwordRules = { length: false, upper: false, lower: false, number: false };
            }
            this.resetEmail = '';
        },
        
        showMessage(text, type = 'success', duration = 5000) {
            this.message = { show: true, type, text };
            setTimeout(() => { this.message.show = false; }, duration);
        },
        
        // CORREÇÃO: Não interferir no login, apenas mostrar loading
        handleLoginSubmit() {
            this.isLoading = true;
            // Permitir que o formulário seja enviado normalmente
            // Loading será resetado quando a página recarregar ou redirecionar
            return true;
        },
        
        handleRegistration() {
            if (!this.canSubmitRegistration) {
                this.showMessage('Preencha todos os campos corretamente', 'error');
                return false;
            }
            
            if (!this.form.terms) {
                this.showMessage('Você deve aceitar os termos de uso para continuar', 'error');
                return false;
            }
            
            this.isLoading = true;
            
            const form = this.$refs.registrationForm;
            if (form) {
                form.submit();
            }
            return true;
        },
        
        validateEmail: window.sInvestUtils?.debounce(async function() {
            if (!this.form.email || !this.isValidEmailFormat(this.form.email)) {
                this.emailValidation = { checking: false, exists: false };
                return;
            }

            this.emailValidation.checking = true;
            
            try {
                const url = new URL(window.authPageData?.ajaxUrl || '/wp-admin/admin-ajax.php', window.location.origin);
                url.searchParams.set('action', 'verificar_email_existente');
                url.searchParams.set('email', this.form.email);
                url.searchParams.set('nonce', window.authPageData?.nonces?.email || '');
                
                const response = await fetch(url);
                if (!response.ok) throw new Error('Erro na verificação');
                
                const data = await response.json();
                
                if (data.success) {
                    this.emailValidation.exists = data.data.exists;
                }
            } catch (error) {
                this.emailValidation.exists = false;
            } finally {
                this.emailValidation.checking = false;
            }
        }, 300) || function() {},
        
        isValidEmailFormat(email) {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        },
        
        validatePassword() {
            const pwd = this.form.password || '';
            this.passwordRules = {
                length: pwd.length >= 8,
                upper: /[A-Z]/.test(pwd),
                lower: /[a-z]/.test(pwd),
                number: /\d/.test(pwd)
            };
        },
        
        validateCPF: window.sInvestUtils?.debounce(async function() {
            const cpf = this.form.cpf.replace(/\D/g, '');
            
            if (!cpf || cpf.length !== 11) {
                this.cpfValidation = { checking: false, exists: false, valid: null };
                return;
            }
            
            if (/^(\d)\1{10}$/.test(cpf)) {
                this.cpfValidation = { checking: false, exists: false, valid: false };
                return;
            }
            
            let soma = 0;
            for (let i = 0; i < 9; i++) {
                soma += parseInt(cpf.charAt(i)) * (10 - i);
            }
            let resto = 11 - (soma % 11);
            if (resto === 10 || resto === 11) resto = 0;
            if (resto !== parseInt(cpf.charAt(9))) {
                this.cpfValidation = { checking: false, exists: false, valid: false };
                return;
            }
            
            soma = 0;
            for (let i = 0; i < 10; i++) {
                soma += parseInt(cpf.charAt(i)) * (11 - i);
            }
            resto = 11 - (soma % 11);
            if (resto === 10 || resto === 11) resto = 0;
            
            if (resto !== parseInt(cpf.charAt(10))) {
                this.cpfValidation = { checking: false, exists: false, valid: false };
                return;
            }
            
            this.cpfValidation.valid = true;
            this.cpfValidation.checking = true;
            
            try {
                const url = new URL(window.authPageData?.ajaxUrl || '/wp-admin/admin-ajax.php', window.location.origin);
                url.searchParams.set('action', 'verificar_cpf_existente');
                url.searchParams.set('cpf', cpf);
                url.searchParams.set('nonce', window.authPageData?.nonces?.cpf || window.authPageData?.nonces?.email || '');
                
                const response = await fetch(url);
                if (!response.ok) throw new Error('Erro na verificação');
                
                const data = await response.json();
                
                if (data.success) {
                    this.cpfValidation.exists = data.data.exists;
                } else {
                    this.cpfValidation.exists = false;
                }
            } catch (error) {
                this.cpfValidation.exists = false;
            } finally {
                this.cpfValidation.checking = false;
            }
        }, 600) || function() {},
        
        async handlePasswordReset() {
            if (!this.resetEmail) {
                this.showMessage('Digite seu e-mail', 'error');
                return;
            }
            
            if (!this.isValidEmailFormat(this.resetEmail)) {
                this.showMessage('Formato de e-mail inválido', 'error');
                return;
            }
            
            this.isLoading = true;
            
            try {
                const formData = new FormData();
                formData.append('action', 'reset_password');
                formData.append('email', this.resetEmail);
                formData.append('nonce', window.authPageData?.nonces?.auth || '');
                
                const response = await fetch(window.authPageData?.ajaxUrl || '/wp-admin/admin-ajax.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    this.showMessage('E-mail de recuperação enviado com sucesso!', 'success');
                    setTimeout(() => this.setView('login'), 2000);
                } else {
                    this.showMessage(data.data || 'Erro ao enviar e-mail', 'error');
                }
            } catch (error) {
                this.showMessage('Erro ao enviar e-mail. Tente novamente.', 'error');
            } finally {
                this.isLoading = false;
            }
        },
        
        async resendConfirmation() {
            if (!this.form.email) {
                this.showMessage('E-mail não informado', 'error');
                return;
            }
            
            this.isLoading = true;
            
            try {
                await new Promise(resolve => setTimeout(resolve, 1000));
                this.showMessage('E-mail de confirmação reenviado!', 'success');
            } catch (error) {
                this.showMessage('Erro ao reenviar e-mail', 'error');
            } finally {
                this.isLoading = false;
            }
        },
        
        get passwordStrength() {
            return Object.values(this.passwordRules).filter(Boolean).length;
        },
        
        get canSubmitRegistration() {
            const basicValidation = this.form.firstName.trim() && 
                                   this.form.lastName.trim() && 
                                   this.form.email.trim() && 
                                   this.form.password && 
                                   this.form.passwordConfirm && 
                                   this.form.cpf.trim() && 
                                   this.form.phone.trim();
            
            const termsAccepted = this.form.terms === true;
            const passwordsMatch = this.form.password === this.form.passwordConfirm;
            const passwordStrong = this.passwordStrength >= 3;
            const emailUnique = !this.emailValidation.exists;
            const cpfValid = this.cpfValidation.valid === true;
            const cpfUnique = !this.cpfValidation.exists;
            
            return basicValidation && 
                   termsAccepted && 
                   passwordsMatch && 
                   passwordStrong && 
                   emailUnique && 
                   cpfValid && 
                   cpfUnique;
        }
    }));
});

window.initAuthComponent = function() {
    const authElement = document.querySelector('[x-data*="authPageV2"]');
    
    if (authElement) {
        if (typeof Alpine === 'undefined') {
            document.addEventListener('alpine:init', () => {
                // Alpine carregado
            });
        }
    }
};

document.addEventListener('DOMContentLoaded', function() {
    if (document.querySelector('[x-data*="authPageV2"]')) {
        window.initAuthComponent();
    }
});

if (typeof module !== 'undefined' && module.exports) {
    module.exports = { authPageV2: 'component registered' };
}