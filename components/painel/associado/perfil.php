<?php
/**
 * Painel do Associado - Perfil
 * Permite que o associado atualize nome, email e senha
 * Estruturação: Sites Geniais | Edson Kleber
 */

defined('ABSPATH') || exit;

$current_user = wp_get_current_user();
?>

<section class="p-6 bg-white rounded-xl shadow-md max-w-2xl">
  <h2 class="text-2xl font-bold mb-6">Minha Conta</h2>

  <?php if (isset($_POST['update_profile'])): ?>
    <?php
      $updated = false;
      $display_name = sanitize_text_field($_POST['display_name']);
      $email = sanitize_email($_POST['user_email']);

      wp_update_user(array(
        'ID'           => $current_user->ID,
        'display_name' => $display_name,
        'user_email'   => $email,
      ));

      // Atualizar senha se fornecida
      if (!empty($_POST['user_pass'])) {
        wp_set_password($_POST['user_pass'], $current_user->ID);
      }

      echo '<p class="text-green-600 mb-4">Perfil atualizado com sucesso.</p>';
    ?>
  <?php endif; ?>

  <form method="post" class="grid gap-4">
    <!-- Nome -->
    <div>
      <label class="block text-sm font-medium text-gray-700">Nome completo</label>
      <input type="text" name="display_name" value="<?php echo esc_attr($current_user->display_name); ?>" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
    </div>

    <!-- Email -->
    <div>
      <label class="block text-sm font-medium text-gray-700">E-mail</label>
      <input type="email" name="user_email" value="<?php echo esc_attr($current_user->user_email); ?>" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
    </div>

    <!-- Nova senha -->
    <div>
      <label class="block text-sm font-medium text-gray-700">Nova senha</label>
      <input type="password" name="user_pass" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
      <p class="text-sm text-gray-400 mt-1">Preencha apenas se deseja alterar a senha.</p>
    </div>

    <div>
      <button type="submit" name="update_profile" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Salvar alterações</button>
    </div>
  </form>
</section>
