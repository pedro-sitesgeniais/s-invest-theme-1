<?php
$descricao = get_field('descricao_originadora');
$video     = get_field('video_originadora');
$originadora = get_field('originadora');

// Extração do ID do vídeo do YouTube
$video_id = null;
if ($video && preg_match('/(?:youtu\.be\/|youtube\.com\/(?:watch\?v=|embed\/|v\/))([^\&\?\/]+)/', $video, $matches)) {
  $video_id = $matches[1];
}

// Só mostra se tem pelo menos um conteúdo
if (empty($descricao) && empty($video_id) && empty($originadora)) {
    return;
}
?>

<div x-show="aba === 'originadora'" x-cloak class="bg-white p-6 rounded-lg shadow space-y-6">

  <h2 class="text-lg font-bold text-gray-800 flex items-center gap-2">
    Sobre a Originadora
  </h2>

  <div class="grid md:grid-cols-2 gap-6 items-start">
    <!-- Coluna 1: Descrição -->
    <?php if (!empty($descricao)) : ?>
    <div>
      <div class="prose prose-sm max-w-none text-gray-700">
        <?= wp_kses_post($descricao) ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- Coluna 2: Vídeo -->
    <?php if ($video_id) : ?>
    <div>
      <div class="relative w-full pb-[56.25%] overflow-hidden rounded-lg shadow">
        <iframe
          class="absolute top-0 left-0 w-full h-full"
          src="https://www.youtube.com/embed/<?= esc_attr($video_id) ?>"
          title="Vídeo da Originadora"
          frameborder="0"
          allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
          allowfullscreen
        ></iframe>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <?php if (!empty($originadora) && !empty($originadora['url'])) : ?>
    <div class="pt-4">
      <a href="<?= esc_url($originadora['url']) ?>" target="_blank" class="text-sm inline-flex items-center gap-2 text-blue-700 hover:underline font-medium">
        🌐 <?= esc_html($originadora['title'] ?? 'Visitar site oficial') ?>
        <i class="fas fa-external-link-alt text-xs"></i>
      </a>
    </div>
  <?php endif; ?>

</div>