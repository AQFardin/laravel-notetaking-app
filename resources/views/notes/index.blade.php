@extends('layouts.app')

@section('content')
<style>

  /* Generic brutalist helpers */
  .card { border:3px solid #000; box-shadow:6px 6px 0 #000; border-radius:0; }
  .btn-base { padding:8px 12px; cursor:pointer; }
  .btn-outline { border:2px solid #000; background:#fff; }
  .btn-solid   { border:2px solid #000; background:#000; color:#fff; }
  .tb.active   { outline:3px solid #000; box-shadow:3px 3px 0 #000 inset; }

  /* Notes grid fixes */
  #notesGrid .note-card,
  #notesGrid .note-card * { text-align: left !important; }
  #notesGrid .note-card { gap: 2px !important; }

  #notesGrid .note-title {
    margin: 0 !important;
    line-height: 1.15 !important;
  }
  #notesGrid .note-preview {
    margin: 0 !important;
    line-height: 1.25 !important;
    white-space: pre-wrap;
    overflow-wrap: anywhere;
    word-break: break-word;
  }
  #notesGrid .note-preview p,
  #notesGrid .note-preview h1,
  #notesGrid .note-preview h2,
  #notesGrid .note-preview h3,
  #notesGrid .note-preview h4,
  #notesGrid .note-preview h5,
  #notesGrid .note-preview h6 {
    margin: .25em 0 !important;
  }
  #notesGrid .note-preview > :first-child { margin-top: 0 !important; }
  #notesGrid .note-preview > :last-child  { margin-bottom: 0 !important; }

  /* Editor area */
  #mEditor {
    text-align:left;
    white-space: pre-wrap;
    overflow-wrap: anywhere;
    word-break: break-word;
  }
  #mEditor p { margin:.35em 0; }
  #mEditor h1,#mEditor h2,#mEditor h3 { margin:.4em 0 .3em; line-height:1.15; }
  #mEditor ul,#mEditor ol { margin:.35em 0 .35em 1.25em; }

  /* History panel scroll */
  #mHistoryPanel { height:60vh; overflow:auto; }
  #mHistoryPanel .sticky-head {
    position:sticky; top:0; background:#fff; z-index:1;
  }
</style>

<div class="notes-page" style="padding:16px;">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;flex-wrap:wrap;gap:8px;">
    <h1 style="margin:0;">Notes</h1>

    <form method="GET" action="{{ route('notes.index') }}" style="display:flex;gap:8px;align-items:center;">
      <input type="search" name="q" value="{{ $q ?? '' }}" placeholder="Search"
             style="border:2px solid #000;padding:8px 10px;min-width:240px;">
      <button type="submit" class="btn-base btn-solid">Search</button>
      <a href="{{ route('notes.index') }}" class="btn-base btn-outline" style="text-decoration:none;color:#000;">Clear</a>
    </form>

    <button id="newNoteBtn" type="button" class="btn-base btn-solid">
      New Note
    </button>
  </div>

  <div id="notesGrid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:12px;">
    @forelse($notes as $n)
      @php
        $updated = \Carbon\Carbon::parse($n->updated_at ?? $n->created_at)->format('d M Y, h:i A');
      @endphp
      <div class="note-card card"
           data-id="{{ $n->id }}"
           data-title="{{ e(strip_tags($n->title ?? '')) }}"
           data-color="{{ $n->color ?? '#ffffff' }}"
           data-updated="{{ $updated }}"
            style="cursor:pointer; padding:12px; background: {{ $n->color ?? '#ffffff' }}; display:flex; flex-direction:column; gap:2px; max-height:210px; **align-items:stretch; text-align:left;**">
        <div style="font-size:12px;font-weight:700; margin:0;">{{ $updated }}</div>

        <div class="note-title" style="font-size:18px;font-weight:900;">
          {!! $n->title ? $n->title : 'Untitled Note' !!}
        </div>

        @php
          // Get raw HTML
          $raw = (string)($n->content ?? '');

          // Decode entities (&nbsp; → actual char), strip all HTML for list preview
          $plain = strip_tags(html_entity_decode($raw, ENT_QUOTES | ENT_HTML5, 'UTF-8'));

          // Collapse ANY run of whitespace (spaces, tabs, newlines) to a single space
          $plain = preg_replace('/\s+/u', ' ', $plain);

          // Trim edges so it starts flush-left
          $plain = trim($plain);

          // Limit AFTER normalizing
          $preview = \Illuminate\Support\Str::limit($plain, 240);
        @endphp

        <div class="note-preview"
            style="font-size:13px; line-height:1.3; margin-top:2px; max-height:80px; overflow:auto; text-align:left; white-space: normal;">
          {{ $preview }}
        </div>



        <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:auto;padding-top:8px;">
          <button class="btn-base btn-outline"
                  onclick="event.stopPropagation(); alert('Share UI coming soon');">
            Share…
          </button>
          <form method="POST" action="{{ route('notes.delete',['id'=>$n->id]) }}"
                onsubmit="event.stopPropagation(); return confirm('Delete this note?')" style="margin:0;">
            @csrf
            <button type="submit" class="btn-base btn-outline">Delete</button>
          </form>
        </div>
      </div>
    @empty
      <div style="opacity:.8;">No notes yet. Click “New Note”.</div>
    @endforelse
  </div>
</div>

{{-- EDIT MODAL --}}
<div id="noteModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.5); z-index:60;">
  <div style="max-width:980px; margin:40px auto;">
    <div id="modalCard" class="card" style="background:#ffffff;">
      <div style="display:flex;justify-content:space-between;align-items:center;padding:10px;border-bottom:2px solid #000;">
        <input id="mTitle" type="text" placeholder="Title"
               style="border:2px solid #000;padding:8px 10px;font-size:16px;flex:1;margin-right:8px;">
        <div style="display:flex;align-items:center;gap:8px;">
          <label style="font-weight:700;">Color</label>
          <select id="mColorSel" style="border:2px solid #000; padding:6px 8px;">
            <option value="#ffffff">White</option>
            <option value="#fef9c3">Yellow</option>
            <option value="#ffdcdc">Pink</option>
            <option value="#d9f7ff">Blue</option>
            <option value="#e6ffda">Green</option>
            <option value="#ffe9d4">Peach</option>
          </select>
          <button id="mClose" type="button" class="btn-base btn-outline">✕</button>
        </div>
      </div>

      <div style="display:flex;gap:8px;align-items:center;padding:8px 10px;border-bottom:2px solid #000;background:#fff;">
        <button id="tbBold"   data-cmd="bold"   class="tb btn-base btn-outline" style="font-weight:900;">B</button>
        <button id="tbItalic" data-cmd="italic" class="tb btn-base btn-outline" style="font-style:italic;">I</button>
        <select id="tbSize" style="border:2px solid #000;padding:6px 8px;">
          @foreach([12,14,16,18,20,22,24,26,28] as $s)
            <option value="{{ $s }}">{{ $s }} px</option>
          @endforeach
        </select>
        <button id="mHistory" type="button" class="btn-base btn-outline" style="margin-left:auto;">History</button>
        <button id="mSave"    type="button" class="btn-base btn-solid">Save</button>
        <span id="mStatus" style="margin-left:6px;"></span>
      </div>

      <div style="display:grid;grid-template-columns:1fr 320px; gap:0;">
        <div style="border-right:2px solid #000;">
          <div id="mEditor" contenteditable="true" spellcheck="false"
               style="min-height:240px; max-height:55vh; overflow:auto; padding:10px; outline:none; background:#fff;"></div>
          <div style="padding:8px 10px;font-size:12px;border-top:2px solid #000;background:#fff;">
            Updated: <span id="mUpdated"></span>
          </div>
        </div>

        <aside id="mHistoryPanel" style="display:none;background:#fff;">
          <div class="sticky-head" style="display:flex;justify-content:space-between;align-items:center;padding:10px;border-bottom:2px solid #000;">
            <strong>Versions</strong>
            <button id="mCloseHistory" type="button" class="btn-base btn-outline">✕</button>
          </div>
          <div id="mHistoryList" style="padding:8px;"></div>
        </aside>
      </div>
    </div>
  </div>
</div>

{{-- VERSION PREVIEW POPUP --}}
<div id="verPreview" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.6); z-index:70;">
  <div class="card" style="max-width:720px; margin:60px auto; background:#fff;">
    <div style="display:flex;justify-content:space-between;align-items:center;padding:10px;border-bottom:2px solid #000;">
      <strong>Version Preview</strong>
      <button id="vpClose" type="button" class="btn-base btn-outline">✕</button>
    </div>
    <div id="vpBody" style="padding:12px; max-height:60vh; overflow:auto; background:#fff;"></div>
  </div>
</div>

<script>
(function(){
  const csrf = '{{ csrf_token() }}';
  const grid = document.getElementById('notesGrid');
  const modal = document.getElementById('noteModal');
  const modalCard = document.getElementById('modalCard');
  const mTitle = document.getElementById('mTitle');
  const mColorSel = document.getElementById('mColorSel');
  const mClose = document.getElementById('mClose');
  const mEditor = document.getElementById('mEditor');
  const mSave = document.getElementById('mSave');
  const mStatus = document.getElementById('mStatus');
  const mUpdated = document.getElementById('mUpdated');
  const mHistory = document.getElementById('mHistory');
  const mHistPanel = document.getElementById('mHistoryPanel');
  const mHistList = document.getElementById('mHistoryList');
  const mCloseHist = document.getElementById('mCloseHistory');
  const newBtn = document.getElementById('newNoteBtn');
  const vp = document.getElementById('verPreview');
  const vpBody = document.getElementById('vpBody');
  const vpClose = document.getElementById('vpClose');
  const tbBold = document.getElementById('tbBold');
  const tbItalic = document.getElementById('tbItalic');
  const tbSize = document.getElementById('tbSize');
  let currentId = null;

  function show(el){ el.style.display='block'; }
  function hide(el){ el.style.display='none'; }
  function setStatus(msg, ok=true){
    mStatus.textContent = msg;
    mStatus.style.color = ok ? '#16a34a' : '#dc2626';
    if (msg) setTimeout(()=> mStatus.textContent = '', 2000);
  }

  function updateToolbarState() {
    try {
      tbBold.classList.toggle('active', !!document.queryCommandState('bold'));
      tbItalic.classList.toggle('active', !!document.queryCommandState('italic'));
    } catch(_) {}
  }
  ['keyup','mouseup','selectionchange','input'].forEach(ev=>{
    document.addEventListener(ev, updateToolbarState, true);
  });

  tbBold.addEventListener('click', e=>{
    e.preventDefault();
    document.execCommand('bold', false);
    updateToolbarState();
    mEditor.focus();
  });
  tbItalic.addEventListener('click', e=>{
    e.preventDefault();
    document.execCommand('italic', false);
    updateToolbarState();
    mEditor.focus();
  });
  tbSize.addEventListener('change', ()=>{
    const px = parseInt(tbSize.value, 10) || 16;
    document.execCommand('fontSize', false, '7');
    Array.from(mEditor.querySelectorAll('font[size="7"]')).forEach(f=>{
      f.removeAttribute('size'); f.style.fontSize = px+'px';
    });
    mEditor.focus();
  });

  async function openEditorById(id) {
    currentId = id || null;
    if (!currentId) {
      mTitle.value=''; mEditor.innerHTML='';
      mColorSel.value='#ffffff'; modalCard.style.background='#ffffff';
      mUpdated.textContent=new Date().toLocaleString();
      mHistPanel.style.display='none';
      show(modal); mEditor.focus(); updateToolbarState(); return;
    }
    try {
      const res=await fetch(`/notes/${currentId}/raw`);
      if(!res.ok) throw new Error('fail');
      const data=await res.json();
      mTitle.value=data.title||''; mEditor.innerHTML=data.content||'';
      mColorSel.value=data.color||'#ffffff'; modalCard.style.background=mColorSel.value;
      mUpdated.textContent=new Date(data.updated_at||Date.now()).toLocaleString();
    }catch(e){ console.error(e); }
    mHistPanel.style.display='none';
    show(modal); mEditor.focus(); updateToolbarState();
  }

  grid?.addEventListener('click', e=>{
    const card=e.target.closest('.note-card'); if(!card) return;
    openEditorById(card.dataset.id);
  });
  newBtn?.addEventListener('click', ()=>openEditorById(null));
  mClose.addEventListener('click', ()=>hide(modal));
  modal.addEventListener('click', e=>{ if(e.target===modal) hide(modal); });
  mColorSel.addEventListener('change', ()=> modalCard.style.background=mColorSel.value);

  mSave.addEventListener('click', async ()=>{
    const payload={title:mTitle.value,content:mEditor.innerHTML,color:mColorSel.value};
    setStatus('Saving…');
    try {
      let url, method='POST', headers={'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest','X-CSRF-TOKEN':csrf};
      if(currentId){ url=`/notes/${currentId}/update`; } else { url=`{{ route('notes.store') }}`; }
      const res=await fetch(url,{method,headers,body:JSON.stringify(payload)});
      if(!res.ok){ setStatus('Save failed',false); return; }
      setStatus('Saved'); window.location.reload();
    }catch(e){ setStatus('Error',false); }
  });

  mHistory.addEventListener('click', async ()=>{
    if(!currentId){ alert('Save first.'); return; }
    mHistPanel.style.display='block'; mHistList.innerHTML='Loading…';
    try {
      const res=await fetch(`/notes/${currentId}/versions`);
      const rows=res.ok?await res.json():[];
      if(!rows.length){ mHistList.innerHTML='No versions yet.'; return; }
      mHistList.innerHTML=rows.map(v=>{
        const ts=new Date(v.created_at).toLocaleString();
        const pv=(v.preview||'').replace(/<[^>]*>/g,'');
        return `<div class="card" style="padding:8px;margin-bottom:8px;">
          <div style="font-weight:900;">${ts}</div>
          <div style="font-size:12px;max-height:70px;overflow:hidden;text-overflow:ellipsis;margin:6px 0;">${pv}</div>
          <div style="display:flex;gap:8px;justify-content:flex-end;">
            <button data-act="preview" data-id="${v.id}" class="btn-base btn-outline">Preview</button>
            <button data-act="revert" data-id="${v.id}" class="btn-base btn-solid">Revert</button>
          </div>
        </div>`;
      }).join('');
    }catch{ mHistList.innerHTML='Failed'; }
  });
  mCloseHist.addEventListener('click', ()=> mHistPanel.style.display='none');
  mHistList.addEventListener('click', async e=>{
    const btn=e.target.closest('button[data-act]'); if(!btn) return;
    const id=btn.dataset.id, act=btn.dataset.act;
    if(act==='preview'){
      const res=await fetch(`/notes/${currentId}/versions/${id}`);
      if(!res.ok) return alert('Preview fail');
      const data=await res.json();
      vpBody.innerHTML=data.content||'<em>(empty)</em>'; show(vp); return;
    }
    if(act==='revert'){ if(!confirm('Revert?')) return;
      const res=await fetch(`/notes/${currentId}/revert/${id}`,{method:'POST',headers:{'X-CSRF-TOKEN':csrf}});
      const d=await res.json();
      if(d.ok){ mEditor.innerHTML=d.content; mUpdated.textContent=new Date(d.updated_at).toLocaleString(); setStatus('Reverted'); }
    }
  });
  vpClose.addEventListener('click', ()=>hide(vp));
  vp.addEventListener('click', e=>{ if(e.target===vp) hide(vp); });
})();
</script>
@endsection
