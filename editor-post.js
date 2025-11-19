/* editor-post.js
   1 - inicialização
   2 - exec/toolbar
   3 - paletas color & highlight (toggle on second click)
   4 - inserir link (modal)
   5 - aplicar estilos (selection/caret or full)
   6 - preview + estilo_post
*/

document.addEventListener('DOMContentLoaded', () => {
  // elementos principais
  const editor = document.getElementById('conteudo-editor');
  const toolbar = document.querySelector('.editor-toolbar');

  const titleInput = document.getElementById('titulo');
  const titleColor = document.getElementById('titulo-cor');
  const titleFont = document.getElementById('titulo-font');
  const titleSize = document.getElementById('titulo-size');

  const metaColor = document.getElementById('meta-cor');
  const metaFont = document.getElementById('meta-font');
  const metaSize = document.getElementById('meta-size');

  const previewTitulo = document.getElementById('preview-titulo');
  const previewConteudo = document.getElementById('preview-conteudo');
  const previewStyle = document.getElementById('preview-style');
  const previewAutor = document.getElementById('preview-autor');

  const fontFamily = document.getElementById('font-family');
  const fontSize = document.getElementById('font-size');
  const headingSelect = document.getElementById('heading-select');

  const paletteColor = document.getElementById('palette-color');
  const colorPicker = document.getElementById('color-picker');
  const applyColorBtn = document.getElementById('apply-color');
  const clearColorBtn = document.getElementById('clear-color');
  const btnColor = document.getElementById('btn-color');

  const paletteHighlight = document.getElementById('palette-highlight');
  const highlightPicker = document.getElementById('highlight-picker');
  const applyHighlightBtn = document.getElementById('apply-highlight');
  const clearHighlightBtn = document.getElementById('clear-highlight');
  const btnHighlight = document.getElementById('btn-highlight');

  const linkModal = document.getElementById('link-modal');
  const btnLink = document.getElementById('btn-link');
  const applyLinkBtn = document.getElementById('apply-link');
  const cancelLinkBtn = document.getElementById('cancel-link');

  const internalRadio = document.querySelector('input[name="link-type"][value="internal"]');
  const externalRadio = document.querySelector('input[name="link-type"][value="external"]');
  const linkInternalBlock = document.getElementById('link-internal');
  const linkExternalBlock = document.getElementById('link-external');

  const internalSelect = document.getElementById('internal-post-select');
  const internalText = document.getElementById('internal-text');
  const externalUrl = document.getElementById('external-url');
  const externalText = document.getElementById('external-text');

  const estiloPostInput = document.getElementById('estilo_post');
  const conteudoHidden = document.getElementById('conteudo-hidden');
  const form = document.getElementById('editor-form');

  // tenta usar styleWithCSS
  try { document.execCommand('styleWithCSS', false, true); } catch(e) {}

  // util: focus no editor e execCommand
  function exec(cmd, val = null) { editor.focus(); document.execCommand(cmd, false, val); }

  // toolbar click
  toolbar.addEventListener('click', (e) => {
    const btn = e.target.closest('button.tool-btn');
    if (!btn) return;
    const cmd = btn.getAttribute('data-cmd');
    if (cmd === 'undo' || cmd === 'redo') exec(cmd);
    else if (cmd) exec(cmd);
    setTimeout(() => { atualizarEstadoBotoes(); atualizarPreview(); }, 10);
  });

  // headings
  headingSelect.addEventListener('change', () => {
    const val = headingSelect.value;
    if (val === 'p') exec('formatBlock', 'p');
    else exec('formatBlock', val);
    atualizarPreview();
  });

  // fontes/tamanhos: aplica ao selection ou ao editor inteiro
  function aplicaEstilo(prop, valor) {
    const sel = window.getSelection();
    if (!sel.rangeCount) {
      // aplica ao editor inteiro
      if (prop === 'fontFamily') editor.style.fontFamily = valor;
      if (prop === 'fontSize') editor.style.fontSize = valor;
      return;
    }
    const range = sel.getRangeAt(0);
    if (range.collapsed) {
      // caret: inserir span com ZWSP
      const span = document.createElement('span');
      span.style[prop] = valor;
      span.appendChild(document.createTextNode('\u200B'));
      range.insertNode(span);
      range.setStart(span.firstChild, 0);
      range.collapse(true);
      sel.removeAllRanges(); sel.addRange(range);
    } else {
      const wrapper = document.createElement('span');
      wrapper.style[prop] = valor;
      wrapper.appendChild(range.extractContents());
      range.insertNode(wrapper);
    }
  }

  fontFamily.addEventListener('change', () => aplicaEstilo('fontFamily', fontFamily.value));
  fontSize.addEventListener('change', () => aplicaEstilo('fontSize', fontSize.value));
  titleFont.addEventListener('change', () => atualizarPreview());
  metaFont.addEventListener('change', () => atualizarPreview());

  // size-select dblclick para digitar valor
  document.querySelectorAll('.size-select').forEach(sel => {
    sel.addEventListener('dblclick', () => {
      const val = prompt('Digite o tamanho em pixels (ex: 28)', sel.value.replace('px',''));
      if (val && !isNaN(val)) {
        const newVal = val.trim().endsWith('px') ? val.trim() : (val.trim() + 'px');
        // cria nova option se não existir
        let found = Array.from(sel.options).some(o => o.value === newVal);
        if (!found) {
          const opt = document.createElement('option'); opt.value = newVal; opt.textContent = newVal.replace('px','');
          sel.appendChild(opt);
        }
        sel.value = newVal;
        atualizarPreview();
      }
    });
    // change atualiza preview
    sel.addEventListener('change', atualizarPreview);
  });

  
// ---------- PALETAS (toggle ao clicar novamente fecha + limpa painel) ----------
let activeColorPanel = null;

// Função que cria e mostra a paleta de cor
function abrirPaleta(tipo, botao) {
  // Se já existir e for a mesma, fecha e remove
  if (activeColorPanel && activeColorPanel.dataset.tipo === tipo) {
    fecharPaleta();
    botao.classList.remove('ativo');
    return;
  }

  // Fecha qualquer outra aberta
  fecharPaleta();

  // Cria o painel do zero
  const paleta = document.createElement('div');
  paleta.className = 'color-palette';
  paleta.dataset.tipo = tipo;
  paleta.innerHTML = `
    <input type="color" value="#000000">
    <button class="aplicar">Aplicar</button>
    <button class="desmarcar">Desmarcar seleção</button>
  `;
  document.body.appendChild(paleta);

  const rect = botao.getBoundingClientRect();
  paleta.style.top = rect.bottom + 8 + 'px';
  paleta.style.left = rect.left + 'px';

  const colorInput = paleta.querySelector('input[type="color"]');
  const aplicarBtn = paleta.querySelector('.aplicar');
  const desmarcarBtn = paleta.querySelector('.desmarcar');

  aplicarBtn.onclick = () => {
    const cor = colorInput.value;
    document.execCommand(tipo === 'grifar' ? 'hiliteColor' : 'foreColor', false, cor);
    fecharPaleta();
    botao.classList.remove('ativo');
  };

  desmarcarBtn.onclick = () => {
    if (tipo === 'grifar') document.execCommand('hiliteColor', false, '#fff0f8');
    else document.execCommand('foreColor', false, '#000000');
    fecharPaleta();
    botao.classList.remove('ativo');
  };

  activeColorPanel = paleta;
  botao.classList.add('ativo');
}

// Fecha e remove completamente a paleta do DOM
function fecharPaleta() {
  if (activeColorPanel) {
    activeColorPanel.remove(); // remove mesmo, não apenas esconde
    activeColorPanel = null;
  }
}

// Botões principais
btnColor.addEventListener('click', () => abrirPaleta('texto', btnColor));
btnHighlight.addEventListener('click', () => abrirPaleta('grifar', btnHighlight));

// Fecha se clicar fora
document.addEventListener('click', (e) => {
  if (
    activeColorPanel &&
    !activeColorPanel.contains(e.target) &&
    !e.target.closest('#btn-color') &&
    !e.target.closest('#btn-highlight')
  ) {
    fecharPaleta();
    document.querySelectorAll('.tool-btn.ativo').forEach(b => b.classList.remove('ativo'));
  }
});

  // ---------- Insert link modal ----------
  btnLink.addEventListener('click', () => {
    linkModal.hidden = false;
  });
  cancelLinkBtn.addEventListener('click', () => { linkModal.hidden = true; });

  // switch radio
  internalRadio.addEventListener('change', () => { linkInternalBlock.style.display = ''; linkExternalBlock.style.display = 'none'; });
  externalRadio.addEventListener('change', () => { linkInternalBlock.style.display = 'none'; linkExternalBlock.style.display = ''; });

  applyLinkBtn.addEventListener('click', () => {
    // decide url e texto
    let url = '';
    let text = '';
    if (internalRadio.checked) {
      url = internalSelect.value || '';
      text = internalText.value.trim();
    } else {
      url = externalUrl.value.trim();
      text = externalText.value.trim();
    }
    if (!url) { alert('Informe um link (interno ou externo).'); return; }

    // inserir no editor: se seleção existe e texto vazio -> cria link na seleção
    const sel = window.getSelection();
    if (sel.rangeCount && !sel.getRangeAt(0).collapsed) {
      // use execCommand para criar link
      editor.focus();
      document.execCommand('createLink', false, url);
    } else {
      // inserir um <a> com texto (se não informar texto, usa URL como texto)
      const a = document.createElement('a');
      a.href = url;
      a.textContent = text || url;
      a.target = '_blank';
      const range = sel.getRangeAt(0);
      range.insertNode(a);
      // move caret depois do link
      range.setStartAfter(a);
      sel.removeAllRanges(); sel.addRange(range);
    }
    linkModal.hidden = true;
    atualizarPreview();
  });

  // atualizar estado dos botões (ativo/inativo)
  function atualizarEstadoBotoes() {
    document.querySelectorAll('.editor-toolbar .tool-btn').forEach(btn => {
      const cmd = btn.getAttribute('data-cmd');
      if (!cmd) return;
      if (cmd === 'undo' || cmd === 'redo') { btn.classList.remove('ativo'); return; }
      let ativo = false;
      try { ativo = document.queryCommandState(cmd); } catch(e) { ativo = false; }
      // special cases
      if (btn.id === 'btn-color') ativo = checkSelectionHasStyle('color');
      if (btn.id === 'btn-highlight') ativo = checkSelectionHasStyle('backgroundColor') || closestTag(window.getSelection(), 'mark');
      if (ativo) btn.classList.add('ativo'); else btn.classList.remove('ativo');
    });
  }

  function checkSelectionHasStyle(prop) {
    const sel = window.getSelection(); if (!sel.rangeCount) return false;
    let node = sel.anchorNode; if (!node) return false;
    if (node.nodeType === 3) node = node.parentElement;
    while (node && node !== editor) {
      const val = window.getComputedStyle(node)[prop];
      if (val && val !== '' && val !== 'rgba(0, 0, 0, 0)' && val !== 'transparent' && val !== 'inherit') return true;
      node = node.parentElement;
    }
    return false;
  }
  function closestTag(selection, tagName) {
    if (!selection.rangeCount) return false;
    let node = selection.anchorNode; if (!node) return false;
    if (node.nodeType === 3) node = node.parentElement;
    while (node && node !== editor) {
      if (node.tagName && node.tagName.toLowerCase() === tagName) return true;
      node = node.parentElement;
    }
    return false;
  }

  // atualizar preview e gerar estilo_post
  function atualizarPreview() {
    previewTitulo.textContent = titleInput.value.trim() || 'Título de exemplo';
    previewTitulo.style.color = titleColor.value || '#5e2d89';
    previewTitulo.style.fontFamily = titleFont.value;
    previewTitulo.style.fontSize = titleSize.value;

    previewAutor.textContent = document.getElementById('autor').value || 'Autor';
    // meta
    const metaCSSFont = metaFont.value;
    const metaCSSSize = metaSize.value;
    previewAutor.style.color = metaColor.value || '#6b2d86';
    previewAutor.style.fontFamily = metaCSSFont;
    previewAutor.style.fontSize = metaCSSSize;

    // content
    previewConteudo.innerHTML = editor.innerHTML;

    // gera CSS a ser salvo (mantém cores originais)
    const css = `
.post-title { color: ${titleColor.value}; font-family: ${titleFont.value}; font-size: ${titleSize.value}; margin-bottom:8px; }
.post-meta { color: ${metaColor.value}; font-family: ${metaFont.value}; font-size: ${metaSize.value}; opacity:.9; }
.post-conteudo { font-family: ${fontFamily.value}; font-size: ${fontSize.value}; line-height:1.6; color: inherit; }
.post-conteudo mark { background-color: #ff94f3; color: #000; padding: 2px 4px; }
.post-conteudo a { color: #ff00dd; text-decoration: underline; }
`;
    previewStyle.textContent = css;
    estiloPostInput.value = css;
  }

  // eventos: input/selectionchange
  editor.addEventListener('input', () => { atualizarPreview(); atualizarEstadoBotoes(); });
  document.addEventListener('selectionchange', () => { atualizarEstadoBotoes(); });

  // aplica estilo título/meta quando trocam
  titleColor.addEventListener('input', atualizarPreview);
  titleFont.addEventListener('change', atualizarPreview);
  titleSize.addEventListener('change', atualizarPreview);
  metaColor.addEventListener('input', atualizarPreview);
  metaFont.addEventListener('change', atualizarPreview);
  metaSize.addEventListener('change', atualizarPreview);

  // Atualiza campos hidden antes do submit
  form.addEventListener('submit', (e) => {
    if (!titleInput.value.trim()) { 
      alert('Informe um título.'); 
      e.preventDefault(); 
      return false; 
    }

    // Conteúdo e estilo já são atualizados pelo preview
    conteudoHidden.value = editor.innerHTML;

    // Atualiza campos de estilo
    document.getElementById('titulo-cor-hidden').value = titleColor.value;
    document.getElementById('titulo-font-hidden').value = titleFont.value;
    document.getElementById('titulo-size-hidden').value = titleSize.value;
    document.getElementById('meta-cor-hidden').value = metaColor.value;
    document.getElementById('meta-font-hidden').value = metaFont.value;
    document.getElementById('meta-size-hidden').value = metaSize.value;
    document.getElementById('font-family-hidden').value = fontFamily.value; // Adicionado
    document.getElementById('font-size-hidden').value = fontSize.value; // Adicionado
  });

  // inicial
  atualizarPreview();
  atualizarEstadoBotoes();
});

