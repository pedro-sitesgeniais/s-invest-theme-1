<?php
/**
 * Seção Perfil – edição de dados do usuário
 * components/painel/investidor/perfil.php
 */
defined('ABSPATH') || exit;

$user = wp_get_current_user();
$user_id = $user->ID;

// Carrega campos ACF com sanitização
$telefone = esc_attr(get_field('telefone', 'user_'.$user_id) ?: '');
$cpf = esc_attr(get_field('cpf', 'user_'.$user_id) ?: '');
?>

<h1 class="text-2xl font-semibold mb-6">Perfil</h1>

<div x-data="{
    loading: false,
    success: false,
    message: '',
    form: {
        first_name: '<?php echo esc_js($user->first_name); ?>',
        last_name: '<?php echo esc_js($user->last_name); ?>',
        telefone: '<?php echo esc_js($telefone); ?>',
        cpf: '<?php echo esc_js($cpf); ?>'
    },
    
    init() {
        // Formata os campos ao carregar
        if (this.form.telefone) {
            this.formatPhone({value: this.form.telefone});
        }
        if (this.form.cpf) {
            this.formatCPF({value: this.form.cpf});
        }
    },
    
    formatPhone(input) {
        let value = input.value || input;
        value = value.replace(/\D/g, '');
        
        if (value.length > 0) {
            value = value.replace(/^(\d{2})(\d)/g, '($1) $2');
            if (value.length > 10) {
                value = value.replace(/(\d)(\d{4})$/, '$1-$2');
            }
        }
        
        this.form.telefone = value;
    },
    
    formatCPF(input) {
        let value = input.value || input;
        value = value.replace(/\D/g, '');
        
        if (value.length > 0) {
            value = value.replace(/^(\d{3})(\d)/g, '$1.$2');
            value = value.replace(/^(\d{3})\.(\d{3})(\d)/g, '$1.$2.$3');
            value = value.replace(/\.(\d{3})(\d)/g, '.$1-$2');
            value = value.substring(0, 14);
        }
        
        this.form.cpf = value;
    },
    
    async submit() {
        this.loading = true;
        this.message = '';
        
        try {
            const response = await fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'update_user_profile',
                    first_name: this.form.first_name,
                    last_name: this.form.last_name,
                    telefone: this.form.telefone.replace(/\D/g, ''),
                    cpf: this.form.cpf.replace(/\D/g, ''),
                    _wpnonce: '<?php echo wp_create_nonce('update_profile_nonce'); ?>'
                })
            });

            const data = await response.json();
            
            if (data.success) {
                this.success = true;
                this.message = data.data.message || 'Perfil atualizado com sucesso!';
            } else {
                this.success = false;
                this.message = data.data || 'Ocorreu um erro ao atualizar o perfil.';
            }
        } catch (error) {
            this.success = false;
            this.message = 'Erro na comunicação com o servidor.';
        } finally {
            this.loading = false;
            
            // Esconde a mensagem após 5 segundos
            if (this.message) {
                setTimeout(() => {
                    this.message = '';
                }, 5000);
            }
        }
    }
}" class="max-w-md space-y-6 main-content-mobile min-h-screen">
  <!-- Feedback -->
  <div x-show="message" 
       x-text="message"
       x-transition
       :class="{
         'bg-green-50 text-green-800': success,
         'bg-red-50 text-red-800': !success
       }"
       class="p-4 rounded-md font-medium">
  </div>

  <form @submit.prevent="submit" class="space-y-4">
    <!-- Primeiro Nome -->
    <div>
      <label for="first_name" class="block text-sm font-medium text-gray-700 mb-1">Primeiro Nome</label>
      <input type="text" id="first_name" x-model="form.first_name"
             class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
             required>
    </div>

    <!-- Sobrenome -->
    <div>
      <label for="last_name" class="block text-sm font-medium text-gray-700 mb-1">Sobrenome</label>
      <input type="text" id="last_name" x-model="form.last_name"
             class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
             required>
    </div>

    <!-- Telefone -->
    <div>
      <label for="telefone" class="block text-sm font-medium text-gray-700 mb-1">Telefone</label>
      <input type="text" id="telefone" x-model="form.telefone"
             @input="formatPhone($event.target)"
             class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
             placeholder="(XX) XXXXX-XXXX">
    </div>

    <!-- CPF -->
    <div>
      <label for="cpf" class="block text-sm font-medium text-gray-700 mb-1">CPF</label>
      <input type="text" id="cpf" x-model="form.cpf"
             @input="formatCPF($event.target)"
             maxlength="14"
             class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
             placeholder="000.000.000-00">
    </div>

    <!-- Botão Salvar -->
    <div class="pt-2">
      <button type="submit"
              :disabled="loading"
              class="w-full flex justify-center items-center px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed">
        <span x-show="!loading">Salvar Alterações</span>
        <span x-show="loading" class="flex items-center">
          <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 714 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          Salvando...
        </span>
      </button>
    </div>
  </form>
</div>