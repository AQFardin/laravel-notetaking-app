@extends('layouts.app') {{-- must be first line --}}

@section('content')
<div class="calendar-page" style="padding:16px;">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
    <h1 style="margin:0;">Calendar</h1>
    <div style="display:flex;gap:8px;">
      <a href="{{ route('events.index') }}" style="border:2px solid #000;padding:10px 14px;text-decoration:none;color:#000;">Events</a>
      <div style="display:flex;gap:6px;">
        <button id="prevBtn" style="border:2px solid #000;background:#fff;padding:8px 10px;">‹</button>
        <div id="monthLabel" style="font-weight:900;padding:8px 10px;border:2px solid #000;background:#fff;"></div>
        <button id="nextBtn" style="border:2px solid #000;background:#fff;padding:8px 10px;">›</button>
      </div>
    </div>
  </div>

  <div id="calGrid" style="display:grid;grid-template-columns:repeat(7,1fr);gap:8px;"></div>

  <div id="dayDrawer" style="margin-top:14px;border:3px solid #000;background:#fff;box-shadow:6px 6px 0 #000;display:none;">
    <div style="padding:10px;border-bottom:2px solid #000;font-weight:900" id="drawerTitle">Day</div>
    <div id="drawerList" style="padding:10px;"></div>
  </div>
</div>

<script>
(function(){
  const monthLabel = document.getElementById('monthLabel');
  const calGrid    = document.getElementById('calGrid');
  const prevBtn    = document.getElementById('prevBtn');
  const nextBtn    = document.getElementById('nextBtn');
  const dayDrawer  = document.getElementById('dayDrawer');
  const drawerTitle= document.getElementById('drawerTitle');
  const drawerList = document.getElementById('drawerList');

  let year  = {{ $year }};
  let month = {{ $month }}; // 1..12

  const monthNames = ['January','February','March','April','May','June','July','August','September','October','November','December'];
  const pad = n => String(n).padStart(2,'0');

  // === NEW: compute today's key once ===
  const now = new Date();
  const todayKey = `${now.getFullYear()}-${pad(now.getMonth()+1)}-${pad(now.getDate())}`;

  async function load() {
    monthLabel.textContent = `${monthNames[month-1]} ${year}`;
    calGrid.innerHTML = '';

    // weekday headers
    ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'].forEach(w=>{
      const h = document.createElement('div');
      h.textContent = w;
      h.style.fontWeight='900';
      h.style.padding='6px';
      calGrid.appendChild(h);
    });

    // first day and days in month
    const first = new Date(year, month-1, 1);
    const startDay = (first.getDay()+6)%7; // Monday=0
    const daysInMonth = new Date(year, month, 0).getDate();

    // fetch events
    const url = new URL("{{ route('events.feed') }}", window.location.origin);
    url.searchParams.set('year', year);
    url.searchParams.set('month', month);
    const res = await fetch(url);
    const rows = res.ok ? await res.json() : [];

    // group by YYYY-MM-DD
    const byDay = {};
    rows.forEach(r=>{
      const d = new Date(r.due_at);
      const key = `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}`;
      (byDay[key] ||= []).push(r);
    });

    // empty cells before first
    for (let i=0; i<startDay; i++) {
      const cell = document.createElement('div');
      cell.style.minHeight='80px';
      calGrid.appendChild(cell);
    }

    // days
    for (let day=1; day<=daysInMonth; day++) {
      const cell = document.createElement('div');
      cell.className = 'cal-day';
      cell.style.border='2px solid #000';
      cell.style.background='#fff';
      cell.style.minHeight='110px';
      cell.style.padding='6px';
      cell.style.display='flex';
      cell.style.flexDirection='column';
      cell.style.gap='6px';

      const key = `${year}-${pad(month)}-${pad(day)}`;

      // === NEW: highlight today's date ===
      const isToday = (key === todayKey);
      if (isToday) {
        cell.style.background = '#fff7ed';                 // light orange
        cell.style.boxShadow  = 'inset 0 0 0 3px #fb923c'; // orange ring
      }

      const top = document.createElement('div');
      top.style.display='flex';
      top.style.justifyContent='space-between';
      const dnum = document.createElement('div');
      dnum.textContent = day;
      dnum.style.fontWeight='900';
      if (isToday) {
        dnum.style.color = '#b45309';  // darker orange number for today
      }
      top.appendChild(dnum);
      cell.appendChild(top);

      const list = document.createElement('div');
      (byDay[key]||[]).slice(0,3).forEach(ev=>{
        const a = document.createElement('div');
        a.textContent = ev.title;
        a.style.fontSize='12px';
        a.style.border='1px solid #000';
        a.style.padding='2px 4px';
        a.style.background='#f6f6f6';
        a.style.whiteSpace='nowrap';
        a.style.overflow='hidden';
        a.style.textOverflow='ellipsis';
        list.appendChild(a);
      });
      if ((byDay[key]||[]).length > 3) {
        const more = document.createElement('div');
        more.textContent = `+${(byDay[key].length-3)} more`;
        more.style.fontSize='12px';
        list.appendChild(more);
      }
      cell.appendChild(list);

      cell.addEventListener('click', ()=>{
        const items = byDay[key]||[];
        drawerTitle.textContent = `${key} — ${items.length} deadline${items.length!==1?'s':''}`;
        drawerList.innerHTML = items.map(ev=>{
          const dt = new Date(ev.due_at);
          const hh = pad(dt.getHours()); const mm = pad(dt.getMinutes());
          const noteHref = ev.note_id ? `/notes/${ev.note_id}` : '';
          return `
            <div style="border-bottom:1px dashed #000;padding:8px 0;">
              <div style="font-weight:900;">${escapeHtml(ev.title)}</div>
              <div style="font-size:12px;">${hh}:${mm}</div>
              ${noteHref ? `<div style="margin-top:4px;"><a href="${noteHref}" style="text-decoration:underline;color:#000;">Open note</a></div>` : ''}
            </div>`;
        }).join('') || '<div style="padding:8px;">No deadlines.</div>';
        dayDrawer.style.display='block';
        dayDrawer.scrollIntoView({behavior:'smooth',block:'start'});
      });

      calGrid.appendChild(cell);
    }
  }

  function escapeHtml(s){ return (s||'').replace(/[&<>"']/g,c=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[c])); }

  prevBtn.addEventListener('click', ()=>{ if (month===1){ month=12; year--; } else month--; load(); });
  nextBtn.addEventListener('click', ()=>{ if (month===12){ month=1; year++; } else month++; load(); });

  load();
})();
</script>
@endsection
