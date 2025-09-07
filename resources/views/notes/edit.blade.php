@extends('layouts.app')

@section('content')
<div class="note-edit-page" style="padding:16px;">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
    <h1 style="margin:0;">Edit Note</h1>
    <a href="{{ route('notes.index') }}" style="border:2px solid #000;padding:8px 12px;text-decoration:none;color:#000;">Back to Notes</a>
  </div>

  <div id="noteWrap" style="border:3px solid #000; box-shadow:6px 6px 0 #000; border-radius:0; background: {{ $note->color ?? '#ffffff' }};">
    <div style="display:flex;gap:8px;align-items:center; justify-content:space-between; padding:10px; border-bottom:2px solid #000;">
      <input id="noteTitle" type="text" value="{{ $note->title }}"
             placeholder="Title"
             style="border:2px solid #000;padding:8px 10px; font-size:16px; flex:1; margin-right:8px;">

      {{-- Color dropdown (6 presets) --}}
      <div style="display:flex;align-items:center;gap:6px;">
        <label style="font-weight:700;">Card color</label>
        <select id="noteColorSel" style="border:2px solid #000; padding:6px 8px;">
          @php
            $colors = ['#ffffff'=>'White', '#fef9c3'=>'Yellow', '#ffdcdc'=>'Pink', '#d9f7ff'=>'Blue', '#e6ffda'=>'Green', '#ffe9d4'=>'Peach'];
            $cur = $note->color ?? '#ffffff';
          @endphp
          @foreach($colors as $hex=>$label)
            <option value="{{ $hex }}" {{ $cur===$hex ? 'selected' : '' }}>{{ $label }}</option>
          @endforeach
        </select>

        <button id="saveBtn" style="border:2px solid #000;background:#000;color:#fff;padding:8px 12px;">Save</button>
      </div>
    </div>

    {{-- Toolbar --}}
    <div style="display:flex;gap:8px;align-items:center; padding:10px; border-bottom:2px solid #000; background:#fff;">
      <button data-cmd="bold"   class="tb" style="border:2px solid #000;background:#fff;padding:6px 10px;font-weight:900;">B</button>
      <button data-cmd="italic" class="tb" style="border:2px solid #000;background:#fff;padding:6px 10px;font-style:italic;">I</button>

      <select id="fontSizeSel" style="border:2px solid #000;padding:6px 8px;">
        <option value="p">Normal</option>
        <option value="h3">Large</option>
        <option value="small">Small</option>
      </select>

      <button id="historyBtn" class="linklike" style="margin-left:auto;border:2px solid #000;background:#fff;padding:6px 10px;">History</button>
      <span id="saveStatus" style="margin-left:6px;"></span>
    </div>

    {{-- Editor + History --}}
    <div style="display:grid; grid-template-columns: 1fr 320px; gap:0;">
      <div style="border-right:2px solid #000; background:#fff;">
        <div id="editor"
             contenteditable="true"
             spellcheck="false"
             style="min-height:360px; padding:12px; outline:none;">
          {!! $note->content !!}
        </div>
        <div style="padding:10px;font-size:12px;border-top:2px solid #000;background:#fff;">
          Updated: <span id="updatedAt">{{ \Carbon\Carbon::parse($note->updated_at ?? now())->format('d M Y, h:i A') }}</span>
        </div>
      </div>

      <aside id="historyPanel" class="hidden"
             style="display:none; background:#fff;">
        <div style="display:flex;justify-content:space-between;align-items:center;padding:10px;border-bottom:2px solid #000;">
          <strong>Versions</strong>
          <button id="closeHistory" style="border:2px solid #000;background:#fff;padding:6px 10px;">✕</button>
        </div>
        <div id="historyList" style="max-height:420px; overflow:auto; padding:8px;"></div>
      </aside>
    </div>
  </div>
</div>

<script>
(function(){
  const noteId     = {{ $note->id }};
  const csrf       = '{{ csrf_token() }}';

  const wrap       = document.getElementById('noteWrap');
  const titleEl    = document.getElementById('noteTitle');
  const colorSel   = document.getElementById('noteColorSel');
  const editor     = document.getElementById('editor');
  const saveBtn    = document.getElementById('saveBtn');
  const saveStatus = document.getElementById('saveStatus');
  const updatedAt  = document.getElementById('updatedAt');

  const histBtn    = document.getElementById('historyBtn');
  const histPanel  = document.getElementById('historyPanel');
  const histList   = document.getElementById('historyList');
  const closeHist  = document.getElementById('closeHistory');

  // square everything (no rounding)
  [wrap].forEach(el => el && (el.style.borderRadius='0'));

  // apply card color immediately
  colorSel?.addEventListener('change', () => {
    wrap.style.background = colorSel.value || '#ffffff';
  });

  // toolbar buttons
  document.querySelectorAll('.tb[data-cmd]').forEach(btn=>{
    btn.addEventListener('click', ()=>{
      const cmd = btn.dataset.cmd;
      document.execCommand(cmd, false);
      editor.focus();
    });
  });

  // font size dropdown (simple tag wrap)
  const fontSel = document.getElementById('fontSizeSel');
  fontSel?.addEventListener('change', ()=>{
    const val = fontSel.value;
    if (val === 'p') document.execCommand('formatBlock', false, 'p');
    else if (val === 'h3') document.execCommand('formatBlock', false, 'h3');
    else if (val === 'small') {
      // toggle <small> on selection
      document.execCommand('fontSize', false, 3); // baseline
      document.execCommand('formatBlock', false, 'p');
      document.execCommand('insertHTML', false, `<small>${getSelectionHtml()}</small>`);
    }
    editor.focus();
  });

  function getSelectionHtml(){
    const sel = window.getSelection();
    if (!sel || sel.rangeCount === 0) return '';
    const range = sel.getRangeAt(0).cloneContents();
    const div = document.createElement('div'); div.appendChild(range); return div.innerHTML;
  }

  function setSaving(on) {
    if (saveBtn) {
      saveBtn.disabled = on;
      saveBtn.textContent = on ? 'Saving…' : 'Save';
    }
  }
  function toast(msg, ok = true) {
    if (!saveStatus) return;
    saveStatus.textContent = msg;
    saveStatus.style.color = ok ? '#16a34a' : '#dc2626';
    setTimeout(() => (saveStatus.textContent = ''), 2000);
  }

  // Save
  saveBtn?.addEventListener('click', async ()=>{
    const payload = {
      title: titleEl?.value ?? '',
      content: editor?.innerHTML ?? '',
      color: colorSel?.value || '#ffffff'
    };

    setSaving(true);
    if (updatedAt) updatedAt.textContent = new Date().toLocaleString();

    try {
      const res = await fetch(`/notes/${noteId}/update`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': csrf
        },
        body: JSON.stringify(payload),
      });

      if (!res.ok) { toast('Save failed', false); setSaving(false); return; }
      const data = await res.json();
      if (data && data.ok) toast('Saved');
      else toast('Save error', false);
    } catch (e) {
      console.error(e);
      toast('Network error', false);
    } finally {
      setSaving(false);
    }
  });

  // --- Versions UI ---
  histBtn?.addEventListener('click', async ()=>{
    histPanel.style.display = 'block';
    histList.innerHTML = '<div style="padding:8px;">Loading…</div>';
    try {
      const res = await fetch(`/notes/${noteId}/versions`);
      const rows = res.ok ? await res.json() : [];
      if (!rows.length) {
        histList.innerHTML = '<div style="padding:8px;">No versions yet.</div>';
        return;
      }
      histList.innerHTML = rows.map(v => {
        const d = new Date(v.created_at);
        const ts = d.toLocaleString();
        const preview = (v.preview || '').replace(/</g,'&lt;').replace(/>/g,'&gt;');
        return `
          <div style="border:2px solid #000; box-shadow:4px 4px 0 #000; padding:8px; margin-bottom:8px;">
            <div style="font-weight:900;">${ts}</div>
            <div style="font-size:12px; max-height:70px; overflow:hidden; white-space:nowrap; text-overflow:ellipsis; margin:6px 0;">${preview}</div>
            <div style="display:flex; gap:8px; justify-content:flex-end;">
              <button data-act="preview" data-id="${v.id}" style="border:2px solid #000;background:#fff;padding:4px 8px;">Preview</button>
              <button data-act="revert" data-id="${v.id}" style="border:2px solid #000;background:#000;color:#fff;padding:4px 8px;">Revert</button>
            </div>
          </div>
        `;
      }).join('');
    } catch(e) {
      histList.innerHTML = '<div style="padding:8px;color:#dc2626;">Failed to load versions.</div>';
    }
  });

  closeHist?.addEventListener('click', ()=>{ histPanel.style.display = 'none'; });

  // Preview/Revert actions
  histList?.addEventListener('click', async (e)=>{
    const btn = e.target.closest('button[data-act]');
    if (!btn) return;
    const id = btn.getAttribute('data-id');
    const act = btn.getAttribute('data-act');

    if (act === 'revert') {
      if (!confirm('Revert to this version?')) return;
      try {
        const res = await fetch(`/notes/${noteId}/revert/${id}`, {
          method: 'POST',
          headers: {'X-Requested-With':'XMLHttpRequest','X-CSRF-TOKEN': csrf}
        });
        const data = await res.json();
        if (res.ok && data.ok) {
          editor.innerHTML = data.content || '';
          updatedAt.textContent = new Date(data.updated_at || Date.now()).toLocaleString();
          toast('Reverted');
        } else {
          toast('Revert failed', false);
        }
      } catch (e) {
        toast('Revert error', false);
      }
      return;
    }

    if (act === 'preview') {
      // Lightweight preview: GET the versions list again to find content (or you could add a /versions/{id} route)
      try {
        const res = await fetch(`/notes/${noteId}/versions`);
        const rows = res.ok ? await res.json() : [];
        const v = rows.find(x => String(x.id) === String(id));
        if (!v) return alert('Version not found in list.');
        // For preview we need full content; if you want exact content, you can extend the versions API to include it.
        alert('Preview shows the first 200 chars.\n\n' + (v.preview || ''));
      } catch (e) {}
    }
  });

})();
</script>
@endsection
