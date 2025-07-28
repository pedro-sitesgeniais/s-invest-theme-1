<?php

/**
 * Bloco: Documentos do Investimento
 */
defined('ABSPATH') || exit;

$documentos = get_field('documentos');
?>

<div x-show="aba === 'documentos'" x-cloak class="bg-white p-6 rounded-lg shadow space-y-4">
  <h2 class="text-xl font-semibold text-gray-900 mb-2">Documentos Relacionados</h2>

  <?php if ($documentos && is_array($documentos)) : ?>
    <ul class="divide-y divide-gray-200 text-sm">
      <?php foreach ($documentos as $doc) :

        // Verifica origem do campo de URL
        $raw_url = $doc['url'] ?? $doc['arquivo'] ?? '';
        $url = '';

        if (is_string($raw_url)) {
          $url = esc_url($raw_url);
        } elseif (is_array($raw_url) && isset($raw_url['url'])) {
          $url = esc_url($raw_url['url']);
        }

        // Título do documento
        $raw_title = $doc['title'] ?? $doc['titulo'] ?? 'Documento';
        $title = is_string($raw_title) ? esc_html($raw_title) : 'Documento';

        // Ícone com base na extensão (Font Awesome)
        $icon = '<i class="fas fa-link"></i>';
        if ($url) {
          $ext = strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
          if (in_array($ext, ['pdf'])) {
            $icon = '<i class="fas fa-file-pdf"></i>';
          } elseif (in_array($ext, ['doc', 'docx'])) {
            $icon = '<i class="fas fa-file-word"></i>';
          } elseif (in_array($ext, ['xls', 'xlsx'])) {
            $icon = '<i class="fas fa-file-excel"></i>';
          }
        }

        if ($url) :
      ?>
          <li class="flex justify-between items-center py-3">
            <span class="flex items-center gap-2 text-gray-700">
              <span class="text-lg" aria-hidden="true">
                <?php echo wp_kses_post($icon); ?>
              </span>
              <span><?php echo $title; ?></span>
            </span>
            <a href="<?php echo $url; ?>" target="_blank" class="mt-3 block text-center px-4 py-2 bg-accent text-white rounded hover:bg-blue-700 transition text-sm" rel="noopener noreferrer">
              Ver
            </a>
          </li>
      <?php endif;
      endforeach; ?>
    </ul>
  <?php else : ?>
    <p class="text-gray-500 italic text-sm">Nenhum documento disponível para este investimento.</p>
  <?php endif; ?>
</div>