document.addEventListener('DOMContentLoaded', () => {
  const container = document.getElementById('investimentos-container');
  const form = document.getElementById('filtros-painel-form');
  
  if (!form || !container) return;

  const carregarResultados = () => {
    const urlParams = new URLSearchParams(window.location.search);
    const currentPage = urlParams.get('page') || 1;
    const secao = new URLSearchParams(window.location.search).get('secao');
    const action = secao === 'meus-investimentos' ? 'filtrar_meus_investimentos' : 'filtrar_investimentos_painel';

    const formData = new FormData(form);
    const loader = `<div class="flex justify-center py-8">
      <div class="animate-spin h-10 w-10 border-4 border-t-blue-600 rounded-full"></div>
    </div>`;
    
    container.innerHTML = loader;

    const data = {
      action: 'filtrar_investimentos_painel',
      nonce: formData.get('filtrar_nonce'),
      paged: currentPage
    };

    for (const [key, value] of formData.entries()) {
      data[key] = value;
    }

    fetch(ajaxurl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({ ...data, action })
    })
    .then(response => {
      if (!response.ok) throw new Error('Erro na rede');
      return response.json();
    })
    .then(responseData => {
      if (responseData.success) {
        container.innerHTML = responseData.data.html;
        const newParams = new URLSearchParams(window.location.search);
        newParams.set('page', responseData.data.paged);
        history.replaceState(null, '', `?${newParams.toString()}`);
      } else {
        container.innerHTML = `<div class="text-red-500">${responseData.data}</div>`;
      }
    })
    .catch(error => {
      container.innerHTML = '<div class="text-red-500">Erro ao carregar dados</div>';
    });
  };

  document.addEventListener('click', (e) => {
    const link = e.target.closest('.pagina-link');
    if (link) {
      e.preventDefault();
      const href = link.getAttribute('href');
      const pageMatch = href.match(/page=(\d+)/);
      const page = pageMatch ? pageMatch[1] : 1;
      const urlParams = new URLSearchParams(window.location.search);
      urlParams.set('page', page);
      history.replaceState(null, '', `?${urlParams.toString()}`);
      carregarResultados();
    }
  });

  document.querySelectorAll('.filtro-ajax').forEach(element => {
    element.addEventListener('change', () => {
      const urlParams = new URLSearchParams(window.location.search);
      urlParams.set('page', 1);
      history.replaceState(null, '', `?${urlParams.toString()}`);
      carregarResultados();
    });
  });

  document.getElementById('limpar-filtros')?.addEventListener('click', () => {
    form.reset();
    const urlParams = new URLSearchParams(window.location.search);
    urlParams.delete('page');
    history.replaceState(null, '', `?${urlParams.toString()}`);
    carregarResultados();
  });

  const initialParams = new URLSearchParams(window.location.search);
  if (initialParams.toString()) {
    carregarResultados();
  }
});