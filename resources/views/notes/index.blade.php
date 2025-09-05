@extends('layouts.app')

@section('content')
  {{-- page-specific JS --}}
  <script src="{{ asset('js/notes.js') }}" defer></script>

  <div class="notes-page">
    <div class="notes-header">
      <h1>Notes</h1>
      <input type="search" id="searchInput" placeholder="Search">
    </div>

    <div class="notes-grid" id="notesGrid">
      {{-- New note card --}}
      <button class="note-card add-card" id="addNoteBtn" aria-label="Add note">
        <span class="plus">+</span>
        <span class="add-text">New note</span>
      </button>

      {{-- Existing notes --}}
      @foreach ($notes as $n)
        <article class="note-card"
                 data-id="{{ $n->id }}"
                 data-color="{{ $n->color ?? '#fef9c3' }}"
                 data-group="{{ $n->note_group_id ?? '' }}"
                 style="background-color: {{ $n->color ?? '#fef9c3' }};">
          @if($n->title)
            <h3 class="note-title">{{ $n->title }}</h3>
          @endif
          <div class="note-body">{!! $n->content !!}</div>
          <div class="note-footer">
            <time>{{ \Carbon\Carbon::parse($n->updated_at ?? $n->created_at)->format('M d, Y') }}</time>
            <button class="menu-btn" data-id="{{ $n->id }}">⋯</button>
            <div class="menu" data-id="{{ $n->id }}">
              <button class="share-btn">Share…</button>
              <button class="group-btn">Move to group…</button>
              <button class="delete-btn danger">Delete</button>
            </div>
          </div>
        </article>
      @endforeach
    </div>
  </div>

  {{-- Editor Modal --}}
  <div id="noteModal" class="modal hidden" aria-hidden="true">
    <div class="modal-backdrop"></div>

    <div class="modal-panel">
      <header class="modal-top">
        <input type="text" id="noteTitle" placeholder="Title (optional)">
        <button id="closeModal">✕</button>
      </header>

      <div class="editor-toolbar">
        <button data-cmd="bold"><b>B</b></button>
        <button data-cmd="italic"><i>I</i></button>
        <select id="fontSizeSel" title="Font size">
          <option value="p">Normal</option>
          <option value="h3">Large</option>
          <option value="small">Small</option>
        </select>

        <div class="color-dots" id="colorPicker">
          <button data-color="#fef9c3" title="Yellow"></button>
          <button data-color="#ffdcdc" title="Pink"></button>
          <button data-color="#d9f7ff" title="Blue"></button>
          <button data-color="#e6ffda" title="Green"></button>
        </div>

        <select id="groupSel">
          <option value="">No group</option>
          {{-- optional: fill groups when you pass them to this view --}}
        </select>
        <button id="newGroupBtn" class="linklike">+ New group</button>

        {{-- History button --}}
        <button id="historyBtn" class="linklike" style="margin-left:auto">History</button>
      </div>

      <div class="editor-wrap">
        <div id="noteEditor" class="editor" contenteditable="true" spellcheck="false"></div>

        {{-- History panel (hidden by default) --}}
        <aside id="historyPanel" class="history hidden">
          <div class="history-head">
            <strong>Versions</strong>
            <button id="closeHistory">✕</button>
          </div>
          <div id="historyList" class="history-list">
            {{-- dynamically filled by JS --}}
          </div>
        </aside>
      </div>

      <footer class="modal-bottom">
        <input id="shareInput" type="text" placeholder="Share with (username or email)">
        <button id="shareBtn">Share</button>
        <button id="deleteBtn" class="danger">Delete</button>
        <button id="saveBtn" class="primary">Save</button>
      </footer>

      <input type="hidden" id="noteId" value="">
      <input type="hidden" id="noteColor" value="#fef9c3">
    </div>
  </div>
@endsection
