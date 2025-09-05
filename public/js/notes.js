(function () {
  const $ = (s, r = document) => r.querySelector(s);
  const $$ = (s, r = document) => Array.from(r.querySelectorAll(s));
  const token = $('meta[name="csrf-token"]')?.content;

  const grid = $('#notesGrid');
  const modal = $('#noteModal');
  const editor = $('#noteEditor');
  const titleInput = $('#noteTitle');
  const groupSel = $('#groupSel');
  const colorPicker = $('#colorPicker');
  const colorHidden = $('#noteColor');
  const noteIdHidden = $('#noteId');

  const historyBtn = $('#historyBtn');
  const historyPanel = $('#historyPanel');
  const historyList = $('#historyList');
  const closeHistory = $('#closeHistory');
// Fill color swatches visually from their data-color
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('#colorPicker button[data-color]').forEach(b => {
    b.style.backgroundColor = b.dataset.color;
    b.style.borderColor = 'rgba(0,0,0,.12)';
  });
});
  // ------ small utils
  const debounce = (fn, ms = 600) => {
    let t; return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), ms); };
  };
  const htmlEscape = (s) => (s || '').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
  const nowISO = () => new Date().toISOString();

  // ------ modal open/close
  const openModal = (note = null) => {
    if (note) {
      noteIdHidden.value = note.id || '';
      titleInput.value   = note.title || '';
      editor.innerHTML   = note.content || '';
      colorHidden.value  = note.color || '#fef9c3';
      $('.modal-panel').style.backgroundColor = colorHidden.value;
      groupSel.value = note.note_group_id ? String(note.note_group_id) : '';
    } else {
      noteIdHidden.value = '';
      titleInput.value   = '';
      editor.innerHTML   = '';
      colorHidden.value  = '#fef9c3';
      $('.modal-panel').style.backgroundColor = colorHidden.value;
      if (groupSel) groupSel.value = '';
    }
    modal.classList.remove('hidden');
    modal.setAttribute('aria-hidden', 'false');
    editor.focus();
  };

  const closeModal = () => {
    // close immediately; saving (if any) runs asynchronously
    modal.classList.add('hidden');
    modal.setAttribute('aria-hidden', 'true');
    historyPanel?.classList.add('hidden');
  };

  // ------ initial: open create if ?new=1
  document.addEventListener('DOMContentLoaded', () => {
    const params = new URLSearchParams(window.location.search);
    if (params.get('new') === '1') $('#addNoteBtn')?.click();
  });

  // ------ add note button
  $('#addNoteBtn')?.addEventListener('click', () => openModal(null));

  // ------ open existing note by clicking card (excluding menu)
  $$('.note-card').forEach(card => {
    if (card.classList.contains('add-card')) return;
    card.addEventListener('click', (e) => {
      if (e.target.closest('.menu') || e.target.classList.contains('menu-btn')) return;
      const note = {
        id: card.dataset.id,
        title: card.querySelector('.note-title')?.textContent || '',
        content: card.querySelector('.note-body')?.innerHTML || '',
        color: card.dataset.color || card.style.backgroundColor || '#fef9c3',
        note_group_id: card.dataset.group || null
      };
      openModal(note);
    });
  });

  // ------ 3-dots menus: open/close
  $$('.menu-btn').forEach(btn => {
    btn.addEventListener('click', (e) => {
      const id = btn.dataset.id;
      const menu = document.querySelector(`.menu[data-id="${id}"]`);
      menu?.classList.toggle('show');
      e.stopPropagation();
    });
  });
  document.addEventListener('click', () => $$('.menu.show').forEach(m => m.classList.remove('show')));

  // ------ MENU ACTIONS via event delegation
  grid?.addEventListener('click', async (e) => {
    const shareEl  = e.target.closest('.share-btn');
    const groupEl  = e.target.closest('.group-btn');
    const delEl    = e.target.closest('.delete-btn');
    if (!shareEl && !groupEl && !delEl) return;

    const card = e.target.closest('.note-card');
    if (!card) return;
    const id = card.dataset.id;

    if (shareEl) {
      const who = prompt('Share with (username or email):');
      if (!who) return;
      const res = await fetch(`/notes/${id}/share`, {
        method: 'POST',
        headers: {'Content-Type': 'application/json','X-CSRF-TOKEN': token},
        body: JSON.stringify({ share_to: who.trim() })
      });
      alert(res.ok ? 'Shared!' : 'Share failed.');
    }

    if (groupEl) {
      const gid = prompt('Move to group ID (blank = none):');
      const body = new URLSearchParams();
      body.set('title', card.querySelector('.note-title')?.textContent || '');
      body.set('content', card.querySelector('.note-body')?.innerHTML || '');
      body.set('color', card.dataset.color || '#fef9c3');
      if (gid) body.set('note_group_id', gid);

      await fetch(`/notes/${id}/update`, {
        method: 'POST',
        headers: {'X-CSRF-TOKEN': token, 'Content-Type':'application/x-www-form-urlencoded'},
        body: body.toString()
      });
      // reflect on card
      card.dataset.group = gid || '';
      alert('Moved.');
    }

    if (delEl) {
      if (!confirm('Delete this note?')) return;
      const res = await fetch(`/notes/${id}/delete`, { method: 'POST', headers: {'X-CSRF-TOKEN': token}});
      if (res.ok) card.remove(); else alert('Delete failed.');
    }
  });

  // ------ toolbar formatting
  $$('.editor-toolbar [data-cmd]').forEach(btn => {
    btn.addEventListener('click', () => {
      document.execCommand(btn.dataset.cmd, false, null);
      editor.focus();
    });
  });

 // Build pixel options
const fontSel = document.getElementById('fontSizeSel');
if (fontSel) {
  const sizes = [12, 14, 16, 18, 20, 24, 28, 32];
  fontSel.innerHTML = sizes.map(px => `<option value="${px}">${px}px</option>`).join('');
  fontSel.value = '16';
  fontSel.addEventListener('change', () => applyFontSizePx(parseInt(fontSel.value, 10)));
}

function applyFontSizePx(px) {
  if (!px) return;
  // 1) wrap selection with <font size="7">
  document.execCommand('fontSize', false, '7');

  // 2) replace <font size="7"> with <span style="font-size:px">
  const fonts = editor.querySelectorAll('font[size="7"]');
  fonts.forEach(fontEl => {
    const span = document.createElement('span');
    span.style.fontSize = px + 'px';
    // move children
    while (fontEl.firstChild) span.appendChild(fontEl.firstChild);
    fontEl.replaceWith(span);
  });

  editor.focus();
}


  // ------ color picker
  colorPicker?.addEventListener('click', (e) => {
    const btn = e.target.closest('button[data-color]');
    if (!btn) return;
    colorHidden.value = btn.dataset.color;
    $('.modal-panel').style.backgroundColor = colorHidden.value;
  });

  // ------ autosave on typing (only for existing notes)
  const autosave = debounce(() => {
    if (!noteIdHidden.value) return; // don't autosave new notes
    quickSave();
  }, 900);
  editor.addEventListener('input', autosave);
  titleInput.addEventListener('input', autosave);
  groupSel?.addEventListener('change', autosave);

  // ------ close / save buttons
  $('#closeModal')?.addEventListener('click', () => {
    // CLOSE FAST: fire-and-forget save for existing notes; create will be on explicit Save
    if (noteIdHidden.value && editor.innerText.trim().length) quickSave();
    closeModal();
  });

  $('#saveBtn')?.addEventListener('click', async () => {
    if (noteIdHidden.value) {
      await quickSave();
      closeModal();
    } else {
      // Creating a brand-new note: we need the backend /notes (which redirects).
      // We'll post form-encoded and then reload quick.
      const payload = new URLSearchParams();
      payload.set('title', titleInput.value || 'Untitled Note');
      payload.set('content', editor.innerHTML || '');
      const res = await fetch('/notes', {
        method: 'POST',
        headers: {'X-CSRF-TOKEN': token, 'Content-Type':'application/x-www-form-urlencoded'},
        body: payload.toString()
      });
      // The route returns a redirect to /notes/{id}; fastest is to go back to /notes
      window.location.href = '/notes';
    }
  });

  // ------ modal delete button
  $('#deleteBtn')?.addEventListener('click', async () => {
    const id = noteIdHidden.value;
    if (!id) return closeModal();
    if (!confirm('Delete this note?')) return;
    const res = await fetch(`/notes/${id}/delete`, { method: 'POST', headers: {'X-CSRF-TOKEN': token}});
    if (res.ok) {
      // remove card in place
      const card = grid.querySelector(`.note-card[data-id="${id}"]`);
      if (card) card.remove();
      closeModal();
    } else {
      alert('Delete failed.');
    }
  });

  // ------ history panel
  historyBtn?.addEventListener('click', async () => {
    const id = noteIdHidden.value;
    if (!id) return;
    await loadHistory(id);
    historyPanel.classList.remove('hidden');
  });
  closeHistory?.addEventListener('click', () => historyPanel.classList.add('hidden'));

  async function loadHistory(noteId) {
    historyList.innerHTML = '<div class="history-empty">Loading…</div>';
    const res = await fetch(`/notes/${noteId}/versions`);
    if (!res.ok) { historyList.innerHTML = '<div class="history-empty">Failed to load.</div>'; return; }
    const versions = await res.json();
    if (!versions.length) { historyList.innerHTML = '<div class="history-empty">No versions yet.</div>'; return; }

    historyList.innerHTML = '';
    versions.forEach(v => {
      const it = document.createElement('div');
      it.className = 'history-item';
      it.innerHTML = `
        <div class="history-time">${new Date(v.created_at).toLocaleString()}</div>
        <div class="history-preview">${htmlEscape(v.preview || '')}</div>
        <button class="history-revert" data-vid="${v.id}">Revert</button>
      `;
      historyList.appendChild(it);
    });

    $$('.history-revert', historyList).forEach(btn => {
      btn.addEventListener('click', async () => {
        const versionId = btn.dataset.vid;
        if (!confirm('Revert to this version?')) return;
        const res = await fetch(`/notes/${noteId}/revert/${versionId}`, {
          method: 'POST',
          headers: {'X-CSRF-TOKEN': token}
        });
        if (res.ok) {
          // reflect on editor + card without full reload
          const textRes = await fetch(`/notes/${noteId}`);
          // fallback: quick refresh (keeps UX consistent and simple)
          window.location.href = '/notes';
        } else {
          alert('Revert failed.');
        }
      });
    });
  }

  // ------ fast update without reloading (for existing notes)
  async function quickSave() {
    const id = noteIdHidden.value;
    if (!id) return;

    const payload = new URLSearchParams();
    payload.set('title', titleInput.value || '');
    payload.set('content', editor.innerHTML || '');
    payload.set('color', colorHidden.value || '#fef9c3');
    if (groupSel && groupSel.value) payload.set('note_group_id', groupSel.value);

    const res = await fetch(`/notes/${id}/update`, {
      method: 'POST',
      headers: {'X-CSRF-TOKEN': token, 'Content-Type':'application/x-www-form-urlencoded'},
      body: payload.toString()
    });
    if (!res.ok) return;

    // Update the card DOM in place (no full reload)
    const card = grid.querySelector(`.note-card[data-id="${id}"]`);
    if (card) {
      // title
      const t = titleInput.value || '';
      let h3 = card.querySelector('.note-title');
      if (t && !h3) {
        h3 = document.createElement('h3');
        h3.className = 'note-title';
        card.insertBefore(h3, card.firstChild);
      }
      if (h3) h3.textContent = t;
      if (!t && h3) h3.remove();

      // content
      const body = card.querySelector('.note-body');
      if (body) body.innerHTML = editor.innerHTML;

      // color
      card.style.backgroundColor = colorHidden.value;
      card.dataset.color = colorHidden.value;

      // time
      const time = card.querySelector('time');
      if (time) time.textContent = new Date().toLocaleDateString(undefined, {month:'short', day:'2-digit', year:'numeric'});
    }
  }
})();
