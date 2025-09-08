@extends('layouts.app')

@section('content')
<div style="padding:16px;">
  <h1 style="margin:0 0 12px;">Dashboard</h1>

  <div style="display:grid; grid-template-columns: 1fr 1fr; gap:16px;">
    {{-- Latest Notes --}}
    <section style="border:3px solid #000; box-shadow:6px 6px 0 #000; background:#fff;">
      <div style="padding:10px; border-bottom:2px solid #000; display:flex; align-items:center; gap:8px;">
        <i class="fas fa-sticky-note"></i><strong>Latest Notes</strong>
      </div>
      <div style="padding:10px;">
        @forelse($notes as $n)
          @php
            $when = \Carbon\Carbon::parse($n->updated_at ?? $n->created_at)->format('d M Y, h:i A');
            $clean = trim(\Illuminate\Support\Str::limit(
              preg_replace('/\s+/u',' ', strip_tags(html_entity_decode((string)($n->content ?? ''), ENT_QUOTES|ENT_HTML5,'UTF-8'))),
              160
            ));
          @endphp
          <div style="border:2px solid #000; padding:8px; margin-bottom:10px; background: {{ $n->color ?? '#ffffff' }};">
            <div style="font-weight:900;">{!! $n->title ?: 'Untitled Note' !!}</div>
            <div style="font-size:12px; color:#333;">{{ $when }}</div>
            <div style="font-size:13px; margin-top:6px;">{{ $clean }}</div>
          </div>
        @empty
          <div style="opacity:.8;">No notes yet.</div>
        @endforelse
      </div>
    </section>

    {{-- Upcoming Deadlines --}}
    <section style="border:3px solid #000; box-shadow:6px 6px 0 #000; background:#fff;">
      <div style="padding:10px; border-bottom:2px solid #000; display:flex; align-items:center; gap:8px;">
        <i class="fas fa-flag"></i><strong>Upcoming Deadlines (14 days)</strong>
      </div>
      <div style="padding:10px;">
        @php
          $fmtDeadline = function($row, $col) {
            $v = $row->$col ?? null;
            return $v ? \Carbon\Carbon::parse($v)->format('d M Y, h:i A') : '—';
          };
        @endphp
        @forelse($deadlines as $d)
          <div style="border:2px solid #000; padding:8px; margin-bottom:10px;">
            <div style="font-weight:900;">{{ $d->title ?? 'Untitled' }}</div>
            <div style="font-size:12px; color:#333;">{{ $fmtDeadline($d, $deadline_col ?? 'due_at') }}</div>
            @if(!empty($d->location))
              <div style="font-size:12px; color:#555;">{{ $d->location }}</div>
            @endif
          </div>
        @empty
          <div style="opacity:.8;">No upcoming deadlines.</div>
        @endforelse
      </div>
    </section>
  </div>
</div>
@endsection
