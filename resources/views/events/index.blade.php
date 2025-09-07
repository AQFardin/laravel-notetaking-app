@extends('layouts.app') {{-- must be first line --}}

@section('content')
<div class="events-page" style="padding:16px;">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
    <h1 style="margin:0;">Events</h1>
    <div style="display:flex;gap:8px;">
      <a href="{{ route('events.calendar') }}" class="btn" style="border:2px solid #000;padding:10px 14px;text-decoration:none;color:#000;">Calendar</a>
      <button id="newEventBtn" class="btn" style="border:2px solid #000;padding:10px 14px;background:#000;color:#fff;">New Event</button>
    </div>
  </div>

  {{-- modal --}}
  <div id="eventModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);">
    <div style="max-width:520px;margin:60px auto;background:#fff;border:3px solid #000;box-shadow:8px 8px 0 #000;padding:16px;">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
        <h3 style="margin:0;">Create Event</h3>
        <button id="closeModal" style="border:2px solid #000;background:#fff;padding:6px 10px;">X</button>
      </div>

      <form id="eventForm">
        @csrf
        <input type="hidden" name="event_id" id="evId" value="">
        <input type="hidden" name="note_id" id="evNoteId" value="{{ $prefillNoteId ?? '' }}"/>

        <div style="margin-bottom:10px;">
          <label style="display:block;font-weight:700;">Title</label>
          <input name="title" id="evTitle" value="{{ $prefillTitle ?? '' }}" required
                 style="width:100%;border:2px solid #000;padding:10px;">
        </div>

        <div style="display:flex;gap:10px;margin-bottom:10px;">
          <div style="flex:1;">
            <label style="display:block;font-weight:700;">Date</label>
            <input type="date" name="date" id="evDate" required
                   style="width:100%;border:2px solid #000;padding:10px;">
          </div>
          <div style="width:160px;">
            <label style="display:block;font-weight:700;">Time</label>
            <input type="time" name="time" id="evTime" required
                   style="width:100%;border:2px solid #000;padding:10px;">
          </div>
        </div>

        <div style="margin-bottom:12px;">
          <label style="display:block;font-weight:700;">Description (optional)</label>
          <textarea name="description" id="evDesc" rows="4"
                    style="width:100%;border:2px solid #000;padding:10px;"></textarea>
        </div>

        <div style="display:flex;gap:8px;justify-content:flex-end;">
          <button type="button" id="cancelModal" style="border:2px solid #000;background:#fff;padding:10px 14px;">Cancel</button>
          <button type="submit" id="saveEvent" style="border:2px solid #000;background:#000;color:#fff;padding:10px 14px;">Create</button>
        </div>
      </form>
    </div>
  </div>

  {{-- upcoming list --}}
  <div id="eventsList" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:12px;">
    @forelse($events as $ev)
    <div class="card event-card"
        data-id="{{ $ev->id }}"
        data-title="{{ $ev->title }}"
        data-desc="{{ $ev->description }}"
        data-note-id="{{ $ev->note_id }}"
        data-due="{{ \Carbon\Carbon::parse($ev->due_at)->format('Y-m-d\TH:i') }}"
        style="border:3px solid #000;background:#fff;box-shadow:6px 6px 0 #000;padding:14px;">

        {{-- CLICKABLE AREA (opens edit) --}}
        <div class="event-click" role="button" tabindex="0" style="cursor:pointer;">
        <div style="font-size:12px;font-weight:700;margin-bottom:6px;">
            {{ \Carbon\Carbon::parse($ev->due_at)->format('d M Y, h:i A') }}
        </div>
        <div style="font-size:18px;font-weight:900;margin-bottom:6px;">
            {{ $ev->title }}
        </div>
        @if($ev->description)
            <div style="color:#444;margin-bottom:8px;">
            {{ \Illuminate\Support\Str::limit($ev->description, 180) }}
            </div>
        @endif
        @if($ev->note_id)
            <div style="font-size:12px;margin-bottom:8px;">
            Linked to note:
            <a href="{{ route('notes.edit', ['id' => $ev->note_id]) }}" style="text-decoration:underline;color:#000;"
                onclick="event.stopPropagation();">
                {{ $ev->note_title ?? ('#'.$ev->note_id) }}
            </a>
            </div>
        @endif
        </div>

        {{-- ACTIONS (never opens edit) --}}
        <div class="event-actions" style="display:flex;gap:8px;justify-content:flex-end;margin-top:8px;">
        <form method="POST"
                action="{{ route('events.delete', ['id' => $ev->id]) }}"
                class="event-delete"
                onsubmit="return confirm('Delete this event?')">
            @csrf
            <button class="btn delete-btn"
                    type="submit"
                    style="border:2px solid #000;background:#fff;padding:6px 10px;">
            Delete
            </button>
        </form>
        </div>
    </div>
    @empty
    <div style="opacity:.8;">No upcoming events. Click “New Event”.</div>
    @endforelse

  </div>
</div>

<script>
(function(){
  const modal  = document.getElementById('eventModal');
  const open   = document.getElementById('newEventBtn');
  const close  = document.getElementById('closeModal');
  const cancel = document.getElementById('cancelModal');
  const form   = document.getElementById('eventForm');

  const evId   = document.getElementById('evId');       // <input type="hidden" name="event_id" id="evId">
  const evNote = document.getElementById('evNoteId');
  const evTitle= document.getElementById('evTitle');
  const evDesc = document.getElementById('evDesc');
  const evDate = document.getElementById('evDate');
  const evTime = document.getElementById('evTime');
  const saveBtn= document.getElementById('saveEvent');

  const listEl = document.getElementById('eventsList'); // wrapper that contains all .event-card

  // helpers
  function show(){ modal.style.display='block'; }
  function hide(){ modal.style.display='none'; resetFormToCreate(); }

  function pad(n){ return String(n).padStart(2,'0'); }

  // Default prefill = now + 1h
  function prefillCreateDefaults(){
    const d = new Date(Date.now()+60*60*1000);
    if (!evDate.value) evDate.value = `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}`;
    if (!evTime.value) evTime.value = `${pad(d.getHours())}:${pad(d.getMinutes())}`;
  }

  // Switch modal to CREATE mode
  function resetFormToCreate(){
    evId.value = '';
    if (!evTitle.value) evTitle.value = '';
    if (!evNote.value)  evNote.value  = '';
    evDesc.value = '';
    evDate.value = '';
    evTime.value = '';
    prefillCreateDefaults();
    saveBtn.textContent = 'Create';
    saveBtn.dataset.mode = 'create';
  }

  // Switch modal to EDIT mode with given event data
  function setFormToEdit(data){
    evId.value    = data.id;
    evTitle.value = data.title || '';
    evDesc.value  = data.desc  || '';
    evNote.value  = data.noteId || '';
    // data.due is "YYYY-MM-DDTHH:mm"
    const [d, t] = (data.due || '').split('T');
    evDate.value = d || '';
    evTime.value = (t || '').slice(0,5);
    saveBtn.textContent = 'Update';
    saveBtn.dataset.mode = 'edit';
  }

  // Open/close modal
  open?.addEventListener('click', () => { resetFormToCreate(); show(); });
  close?.addEventListener('click', hide);
  cancel?.addEventListener('click', hide);
  modal?.addEventListener('click', (e)=>{ if(e.target===modal) hide(); });

  // ---- EDIT OPEN: only when clicking the .event-click area (not actions) ----
  // Use event delegation so newly added cards also work.
  listEl?.addEventListener('click', (e)=>{
    // if the click is inside any action area/form/button, ignore
    if (e.target.closest('.event-actions') || e.target.closest('.event-delete')) return;

    const clickable = e.target.closest('.event-click');
    if (!clickable) return;

    const card = clickable.closest('.event-card');
    if (!card) return;

    setFormToEdit({
      id:     card.dataset.id,
      title:  card.dataset.title,
      desc:   card.dataset.desc,
      noteId: card.dataset.noteId,
      due:    card.dataset.due
    });
    e.stopPropagation();
    show();
  });

  // Accessibility: Enter key on .event-click opens edit
  listEl?.addEventListener('keydown', (e)=>{
    if (e.key !== 'Enter') return;
    if (!e.target.classList.contains('event-click')) return;
    const card = e.target.closest('.event-card');
    if (!card) return;

    setFormToEdit({
      id:     card.dataset.id,
      title:  card.dataset.title,
      desc:   card.dataset.desc,
      noteId: card.dataset.noteId,
      due:    card.dataset.due
    });
    e.preventDefault();
    show();
  });

  // Extra safety: prevent bubbling from delete/actions
  document.querySelectorAll('.event-actions, .event-delete, .delete-btn').forEach(el=>{
    ['click','mousedown','touchstart'].forEach(type=>{
      el.addEventListener(type, ev => ev.stopPropagation(), { passive: true });
    });
  });

  // ---- SUBMIT (create or update) ----
  form?.addEventListener('submit', async (e)=>{
    e.preventDefault();
    const mode = saveBtn.dataset.mode || 'create';

    const fd = new FormData(form);
    const title = fd.get('title')?.toString().trim();
    const date  = fd.get('date')?.toString().trim();
    const time  = fd.get('time')?.toString().trim();
    if (!title || !date || !time) { alert('Title, Date and Time are required.'); return; }

    let url, method = 'POST';
    const headers = {'X-Requested-With':'XMLHttpRequest','X-CSRF-TOKEN':'{{ csrf_token() }}'};

    if (mode === 'edit' && evId.value) {
      url = `/events/${encodeURIComponent(evId.value)}/update`;
    } else {
      url = `{{ route('events.store') }}`;
    }

    const res = await fetch(url, { method, headers, body: fd });
    if (!res.ok) { alert('Save failed'); return; }

    try { await res.json(); } catch(_) {}
    window.location.reload(); // simplest: refresh and keep list sorted nearest→farthest
  });

  // Initial defaults for create mode
  prefillCreateDefaults();
})();
</script>


@endsection
