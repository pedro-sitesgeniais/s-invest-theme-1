<?php
/**
 * Formulário Multi-Etapas – Cadastro Completo de Investimentos
 * Estrutura: Alpine.js + Tailwind + ACF
 */

defined('ABSPATH') || exit;
?>
<?php
function render_categoria_checkbox($terms, $parent = 0, $prefix = '') {
  foreach ($terms as $term) {
    if ((int)$term->parent === (int)$parent) {
      echo '<label class="flex items-center gap-2 mb-1 text-sm">';
      echo '<input type="checkbox" name="tipo_produto[]" value="' . esc_attr($term->term_id) . '" class="rounded" />';
      echo esc_html($prefix . $term->name);
      echo '</label>';
      render_categoria_checkbox($terms, $term->term_id, $prefix . '— ');
    }
  }
}
$termos = get_terms([
  'taxonomy'   => 'tipo_produto',
  'hide_empty' => false
]);
?>
<?php
if (
  isset($_POST['submit_investimento']) &&
  isset($_POST['investimento_nonce_field']) &&
  wp_verify_nonce($_POST['investimento_nonce_field'], 'salvar_investimento_nonce')
) {
  $post_id = wp_insert_post([
    'post_type'   => 'investment',
    'post_status' => 'publish',
    'post_title'  => sanitize_text_field($_POST['titulo']),
    'post_author' => get_current_user_id()
  ]);
  // Se o usuário criou uma nova categoria
if (!empty($_POST['nova_categoria_nome'])) {
  $nome_categoria = sanitize_text_field($_POST['nova_categoria_nome']);
  $categoria_pai = isset($_POST['nova_categoria_pai']) ? intval($_POST['nova_categoria_pai']) : 0;

  $nova_term = wp_insert_term($nome_categoria, 'tipo_produto', [
    'parent' => $categoria_pai
  ]);

  if (!is_wp_error($nova_term)) {
    $_POST['tipo_produto'][] = $nova_term['term_id']; // adiciona à seleção
  }
}

  if (!empty($_POST['tipo_produto'])) {
    $termos_ids = array_map('intval', $_POST['tipo_produto']);
    wp_set_object_terms($post_id, $termos_ids, 'tipo_produto');
  }
  if ($post_id && !is_wp_error($post_id)) {

    // 📌 Campos simples
    update_field('prazo_do_investimento', sanitize_text_field($_POST['prazo_do_investimento']), $post_id);
    update_field('rentabilidade', floatval($_POST['rentabilidade']), $post_id);
    update_field('valor_total', floatval($_POST['valor_total']), $post_id);
    update_field('total_captado', floatval($_POST['total_captado']), $post_id);
    update_field('aporte_minimo', floatval($_POST['aporte_minimo']), $post_id);
    update_field('data_inicio', sanitize_text_field($_POST['data_inicio']), $post_id);
    update_field('fim_captacao', sanitize_text_field($_POST['fim_captacao']), $post_id);
    update_field('moeda_aceita', $_POST['moeda_aceita'], $post_id);
    update_field('status_captacao', sanitize_text_field($_POST['status_captacao']), $post_id);
    update_field('multiplicador_simulador', floatval($_POST['multiplicador_simulador']), $post_id);
    update_field('descricao_originadora', wp_kses_post($_POST['descricao_originadora']), $post_id);
    update_field('video_originadora', esc_url($_POST['video_originadora']), $post_id);

    // 📎 Campo de link: Originadora
    if (isset($_POST['originadora']['url']) && !empty($_POST['originadora']['url'])) {
      update_field('originadora', [
        'title' => sanitize_text_field($_POST['originadora']['title']),
        'url'   => esc_url($_POST['originadora']['url']),
      ], $post_id);
    }

    // 🧮 Cenários (agrupamentos)
    $cenarios = ['cenario_base', 'cenario_otimista', 'cenario_pessimista'];
    foreach ($cenarios as $cenario) {
      if (isset($_POST[$cenario])) {
        $grupo = [];
        foreach ($_POST[$cenario] as $chave => $valor) {
          $grupo[$chave] = is_numeric($valor) ? floatval($valor) : sanitize_text_field($valor);
        }
        update_field($cenario, $grupo, $post_id);
      }
    }

    // 📁 Repeater: Documentos
    if (!empty($_FILES['documentos']) && is_array($_FILES['documentos']['name'])) {
      $documentos = [];
      foreach ($_FILES['documentos']['name'] as $i => $nome) {
        if (!empty($nome)) {
          $file = [
            'name'     => $_FILES['documentos']['name'][$i]['url'],
            'type'     => $_FILES['documentos']['type'][$i]['url'],
            'tmp_name' => $_FILES['documentos']['tmp_name'][$i]['url'],
            'error'    => $_FILES['documentos']['error'][$i]['url'],
            'size'     => $_FILES['documentos']['size'][$i]['url']
          ];
          $upload = media_handle_sideload($file, $post_id);
          if (!is_wp_error($upload)) {
            $documentos[] = [
              'title' => sanitize_text_field($_POST['documentos'][$i]['title']),
              'url'   => wp_get_attachment_url($upload),
            ];
          }
        }
      }
      if (!empty($documentos)) {
        update_field('documentos', $documentos, $post_id);
      }
    }

    // 🧠 Repeater: Motivos
    if (!empty($_POST['motivos'])) {
      $motivos = array_map(function($motivo) {
        return [
          'titulo'    => sanitize_text_field($motivo['titulo']),
          'descricao' => sanitize_textarea_field($motivo['descricao']),
        ];
      }, $_POST['motivos']);
      update_field('motivos', $motivos, $post_id);
    }

    // ⚠️ Repeater: Riscos
    if (!empty($_POST['riscos'])) {
      $riscos = array_map(function($risco) {
        return [
          'titulo'    => sanitize_text_field($risco['titulo']),
          'descricao' => sanitize_textarea_field($risco['descricao']),
        ];
      }, $_POST['riscos']);
      update_field('riscos', $riscos, $post_id);
    }

    echo '<div class="bg-green-100 border border-green-300 text-green-800 px-4 py-2 rounded mb-6">✅ Investimento cadastrado com sucesso.</div>';
  } else {
    echo '<div class="bg-red-100 border border-red-300 text-red-800 px-4 py-2 rounded mb-6">❌ Erro ao salvar investimento.</div>';
  }
}
?>

<div 
  x-data="{
    etapa: 1,
    avancar() { if (this.etapa < 3) this.etapa++ },
    voltar() { if (this.etapa > 1) this.etapa-- }
  }"
  class="bg-white p-6 rounded-xl shadow max-w-4xl mx-auto"
>

  <!-- Barra de Progresso Visual -->
<div class="flex items-center justify-between mb-8 max-w-4xl mx-auto">
  <template x-for="n in 3" :key="n">
    <div class="flex-1 flex items-center relative">
      <div class="z-10 w-8 h-8 rounded-full flex items-center justify-center"
        :class="{
          'bg-blue-600 text-white': etapa >= n,
          'bg-gray-200 text-gray-600': etapa < n
        }">
        <span x-text="n"></span>
      </div>
      <div class="absolute top-4 left-1/2 w-full h-1 -translate-x-1/2"
        :class="etapa > n ? 'bg-blue-600' : 'bg-gray-300'"
        x-show="n < 3"></div>
    </div>
  </template>
</div>

  <!-- Etapa 1 -->
<div x-show="etapa === 1"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 scale-95"
     x-transition:enter-end="opacity-100 scale-100"
     class="bg-white p-6 rounded-xl shadow space-y-4 max-w-4xl mx-auto">
  <h2 class="text-xl font-bold mb-4">1️⃣ Informações do Investimento</h2>

  <input type="text" name="titulo" placeholder="Título do Investimento" class="w-full border px-3 py-2 rounded" />

  <input type="text" name="prazo_do_investimento" placeholder="Prazo do Investimento" class="w-full border px-3 py-2 rounded" />

  <input type="number" step="0.01" name="rentabilidade" placeholder="Rentabilidade (%)" class="w-full border px-3 py-2 rounded" />

  <input type="number" step="0.01" name="valor_total" placeholder="Valor Total" class="w-full border px-3 py-2 rounded" />

  <input type="number" step="0.01" name="total_captado" placeholder="Total Captado" class="w-full border px-3 py-2 rounded" />

  <input type="number" step="0.01" name="aporte_minimo" placeholder="Aporte Mínimo" class="w-full border px-3 py-2 rounded" />

  <input type="date" name="data_inicio" class="w-full border px-3 py-2 rounded" />
  <input type="date" name="fim_captacao" class="w-full border px-3 py-2 rounded" />

  <select name="moeda_aceita[]" multiple class="w-full border px-3 py-2 rounded">
    <option value="BRL">Real</option>
    <option value="USD">Dólar</option>
    <option value="EUR">Euro</option>
  </select>

  <select name="status_captacao" class="w-full border px-3 py-2 rounded">
    <option value="Aberto">Aberto</option>
    <option value="Encerrado">Encerrado</option>
  </select>

  <!-- Originadora -->
  <div class="grid md:grid-cols-2 gap-4">
    <input type="text" name="originadora[title]" placeholder="Nome da Originadora" class="w-full border px-3 py-2 rounded" />
    <input type="url" name="originadora[url]" placeholder="URL da Originadora" class="w-full border px-3 py-2 rounded" />
  </div>
</div>

  <!-- Etapa 2 -->
  <div x-show="etapa === 2"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 scale-95"
     x-transition:enter-end="opacity-100 scale-100"
     class="bg-white p-6 rounded-xl shadow space-y-4 max-w-4xl mx-auto">
    <h2 class="text-xl font-bold mb-4">2️⃣ Cenários Projetados</h2>

    <template x-for="cenario in ['base', 'otimista', 'pessimista']" :key="cenario">
      <fieldset class="border p-4 rounded">
        <legend class="font-semibold mb-2 capitalize" x-text="`Cenário ${cenario}`"></legend>

        <input :name="`cenario_${cenario}[exposicao_maxima]`" placeholder="Exposição Máxima" type="number" step="0.01" class="w-full border px-3 py-2 rounded mb-2" />
        <input :name="`cenario_${cenario}[rentabilidade_cdi]`" placeholder="Rentabilidade (%CDI)" type="number" step="0.01" class="w-full border px-3 py-2 rounded mb-2" />
        <input :name="`cenario_${cenario}[rentabilidade_tir]`" placeholder="Rentabilidade (%TIR)" type="number" step="0.01" class="w-full border px-3 py-2 rounded mb-2" />
        <input :name="`cenario_${cenario}[multiplo]`" placeholder="Múltiplo" type="number" step="0.01" class="w-full border px-3 py-2 rounded mb-2" />
        <input :name="`cenario_${cenario}[prazo]`" placeholder="Prazo" type="text" class="w-full border px-3 py-2 rounded" />
      </fieldset>
    </template>
  </div>

  <!-- Etapa 3 -->
<div x-show="etapa === 3"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 scale-95"
     x-transition:enter-end="opacity-100 scale-100"
     class="bg-white p-6 rounded-xl shadow space-y-4 max-w-4xl mx-auto">

<h2 class="text-xl font-bold mb-4">3️⃣ Documentos e Detalhes</h2>

<!-- Repeater: Documentos -->
<div x-data="{ documentos: [] }" class="space-y-2">
  <p class="font-semibold text-gray-700">📄 Documentos</p>
  <template x-for="(doc, i) in documentos" :key="i">
    <div class="flex gap-2 items-center">
      <input :name="`documentos[${i}][title]`" placeholder="Título do documento" class="flex-1 border px-2 py-1 rounded" />
      <input :name="`documentos[${i}][url]`" type="file" class="flex-1 border px-2 py-1 rounded" />
      <button type="button" @click="documentos.splice(i, 1)" class="text-red-600 hover:underline text-sm">Remover</button>
    </div>
  </template>
  <button type="button" @click="documentos.push({})" class="bg-blue-600 text-white text-sm px-3 py-1 rounded">+ Adicionar Documento</button>
</div>
<!-- Classe de Ativos -->
<div>
  <label class="block text-sm font-medium text-gray-700 mb-1">Classe de Ativos</label>
  <div class="border p-3 rounded max-h-48 overflow-y-auto bg-white">
    <?php render_categoria_checkbox($termos); ?>
  </div>
  <p class="text-xs text-gray-500 mt-1">Marque uma ou mais categorias. Subcategorias serão respeitadas.</p>
</div>
<div class="mt-4 space-y-2">
  <label class="block text-sm font-medium text-gray-700">Adicionar nova categoria</label>
  <input type="text" name="nova_categoria_nome" placeholder="Nome da nova categoria" class="w-full border px-3 py-2 rounded" />

  <select name="nova_categoria_pai" class="w-full border px-3 py-2 rounded">
    <option value="">Nenhuma (categoria principal)</option>
    <?php foreach ($termos as $term) : ?>
      <option value="<?= esc_attr($term->term_id) ?>"><?= esc_html($term->name) ?></option>
    <?php endforeach; ?>
  </select>
  <p class="text-xs text-gray-500">Se desejar, escolha uma categoria pai para hierarquia.</p>
</div>

<!-- Campo: Multiplicador do Simulador -->
<input type="number" step="0.01" name="multiplicador_simulador" placeholder="Multiplicador Simulador" class="w-full border px-3 py-2 rounded" />

<!-- Campo: Descrição da Originadora -->
<textarea name="descricao_originadora" placeholder="Descrição da Originadora" rows="4" class="w-full border px-3 py-2 rounded"></textarea>

<!-- Campo: URL do vídeo -->
<input type="url" name="video_originadora" placeholder="URL do vídeo (YouTube, Vimeo...)" class="w-full border px-3 py-2 rounded" />

<!-- Repeater: Motivos -->
<div x-data="{ motivos: [] }" class="space-y-2">
  <p class="font-semibold text-gray-700">💡 Motivos para Investir</p>
  <template x-for="(motivo, i) in motivos" :key="i">
    <div class="space-y-1 border p-3 rounded">
      <input :name="`motivos[${i}][titulo]`" placeholder="Título do motivo" class="w-full border px-2 py-1 rounded" />
      <textarea :name="`motivos[${i}][descricao]`" rows="2" placeholder="Descrição do motivo" class="w-full border px-2 py-1 rounded"></textarea>
      <button type="button" @click="motivos.splice(i, 1)" class="text-red-600 hover:underline text-sm">Remover</button>
    </div>
  </template>
  <button type="button" @click="motivos.push({})" class="bg-blue-600 text-white text-sm px-3 py-1 rounded">+ Adicionar Motivo</button>
</div>

<!-- Repeater: Riscos -->
<div x-data="{ riscos: [] }" class="space-y-2">
  <p class="font-semibold text-gray-700">⚠️ Riscos</p>
  <template x-for="(risco, i) in riscos" :key="i">
    <div class="space-y-1 border p-3 rounded">
      <input :name="`riscos[${i}][titulo]`" placeholder="Título do risco" class="w-full border px-2 py-1 rounded" />
      <textarea :name="`riscos[${i}][descricao]`" rows="2" placeholder="Descrição do risco" class="w-full border px-2 py-1 rounded"></textarea>
      <button type="button" @click="riscos.splice(i, 1)" class="text-red-600 hover:underline text-sm">Remover</button>
    </div>
  </template>
  <button type="button" @click="riscos.push({})" class="bg-blue-600 text-white text-sm px-3 py-1 rounded">+ Adicionar Risco</button>
</div>

<!-- Botão final de envio -->
<button
  type="submit"
  x-bind:disabled="etapa !== 3"
  class="w-full px-6 py-3 bg-green-600 text-white rounded hover:bg-green-700 mt-6 transition"
>
  <span x-show="etapa === 3">✅ Salvar Investimento</span>
  <span x-show="etapa !== 3" class="opacity-50">Preencha todas as etapas...</span>
</button></div>

<!-- Navegação entre etapas -->
<div class="flex justify-between max-w-4xl mx-auto mt-8">
  <button type="button" @click="voltar()" x-show="etapa > 1"
    class="px-6 py-2 bg-gray-200 text-gray-800 rounded hover:bg-gray-300 transition">
    ← Voltar
  </button>
  <button type="button" @click="avancar()" x-show="etapa < 3"
    class="px-6 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">
    Próximo →
  </button>
</div>



<script>
function cadastroInvestimento() {
  return {
    etapaAtual: 1,
    formData: {
      titulo: '',
      prazo_do_investimento: '',
      rentabilidade: '',
      risco: '',
      moeda_aceita: [],
      status_captacao: '',
      data_inicio: '',
      fim_captacao: '',
      valor_total: '',
      total_captado: '',
      aporte_minimo: '',
      multiplicador_simulador: '',
      cenario_base: {
        exposicao_maxima: '',
        rentabilidade_cdi: '',
        prazo: '',
        rentabilidade_tir: '',
        multiplo: ''
      },
      cenario_otimista: {
        exposicao_maxima: '',
        rentabilidade_cdi: '',
        prazo: '',
        rentabilidade_tir: '',
        multiplo: ''
      },
      cenario_pessimista: {
        exposicao_maxima: '',
        rentabilidade_cdi: '',
        prazo: '',
        rentabilidade_tir: '',
        multiplo: ''
      },
      descricao_originadora: '',
      originadora: {
        url: '',
        title: ''
      },
      video_originadora: '',
      motivos: [{ titulo: '', descricao: '' }],
      riscos: [{ titulo: '', descricao: '' }],
      documentos: [{ title: '', file: null }]
    },
    init() {
      console.log('Formulário iniciado');
    },
    enviar() {
      console.log('Formulário pronto para envio', this.formData);
      alert('Simulação de envio. Implementar lógica de submissão via AJAX ou PHP.');
    }
  }
}
</script>
