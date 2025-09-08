<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GroupController;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

/* =========================
   AUTH UI (single Blade)
   - use /auth?mode=login or /auth?mode=register
   - GET /login and GET /register just open /auth with the right mode
========================= */
Route::get('/auth', function (Request $r) {
    $mode = $r->query('mode');
    $mode = in_array($mode, ['login','register']) ? $mode : 'login';
    return view('auth.auth', ['mode' => $mode]);
   // <-- your single Blade: resources/views/auth.blade.php
})->name('auth');

Route::get('/login', fn() => redirect()->route('auth', ['mode' => 'login']))->name('login.form');
Route::get('/register', fn() => redirect()->route('auth', ['mode' => 'register']))->name('register.form');

/* =========================
   REGISTER — Step 1 (username/email/password)
========================= */
Route::post('/register', function (Request $r) {
    $r->validate([
        'username' => 'required|string|max:50|unique:users,username',
        'email'    => 'required|email|max:100|unique:users,email',
        'password' => 'required|string|min:3',
    ], [
        'username.unique' => 'Username already taken.',
        'email.unique'    => 'A user with this email already exists.',
    ]);

    DB::insert(
        "INSERT INTO users (username, email, password, created_at) VALUES (?, ?, ?, NOW())",
        [$r->username, $r->email, $r->password] // NOTE: consider hashing later
    );

    $id = DB::getPdo()->lastInsertId();
    $r->session()->put('pending_user_id', $id);

    return redirect()->route('register.details.form');
})->name('register');

/* =========================
   REGISTER — Step 2 (details)
========================= */
Route::get('/register/details', function (Request $r) {
    if (!$r->session()->has('pending_user_id')) {
        // no step-1 data; open the Register panel
        return redirect()->route('auth', ['mode' => 'register'])->with('show_register', true);
    }
    return view('auth.register_details');
})->name('register.details.form');

Route::post('/register/details', function (Request $r) {
    $id = $r->session()->get('pending_user_id');
    if (!$id) {
        return redirect()->route('auth', ['mode' => 'register'])->with('show_register', true);
    }

    $r->validate([
        'full_name'   => 'required|string|max:100',
        'age'         => 'required|integer|between:1,120',
        'profile_pic' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
    ]);

    $picPath = null;
    if ($r->hasFile('profile_pic')) {
        $filename = time().'_'.$r->file('profile_pic')->getClientOriginalName();
        $r->file('profile_pic')->move(public_path('profile_pics'), $filename);
        $picPath = 'profile_pics/'.$filename;
    }

    DB::update(
        "UPDATE users SET full_name = ?, age = ?, profile_pic = ? WHERE id = ?",
        [$r->full_name, (int)$r->age, $picPath, $id]
    );

    $r->session()->forget('pending_user_id');
    $r->session()->put('user_id', $id);

    return redirect()->route('dashboard')->with('status', 'Registration complete!');
})->name('register.details.submit');

/* =========================
   LOGIN — (username or email) + password
========================= */
Route::post('/login', function (Request $r) {
    $r->validate([
        'email'    => 'required|string', // can be username OR email
        'password' => 'required|string',
    ]);

    $user = DB::selectOne(
        "SELECT * FROM users WHERE (username = ? OR email = ?) AND password = ? LIMIT 1",
        [$r->email, $r->email, $r->password]
    );

    if ($user) {
        $r->session()->put('user_id', $user->id);
        return redirect()->route('dashboard');
    }

    // send user back to LOGIN panel with errors + old input
    return redirect()
        ->route('auth', ['mode' => 'login'])
        ->withErrors(['email' => 'Invalid credentials'])  // surface under email field in your Blade
        ->withInput();
})->name('login');

/* =========================
   DASHBOARD
========================= */
Route::get('/dashboard', function (Request $r) {
    $id = $r->session()->get('user_id');
    if (!$id) return redirect()->route('auth');

    $user = DB::selectOne("SELECT * FROM users WHERE id = ? LIMIT 1", [$id]);
    return view('dashboard', ['u' => $user]);
})->name('dashboard');

/* =========================
   LOGOUT
========================= */
Route::post('/logout', function (Request $r) {
    $r->session()->forget('user_id');
    return redirect()->route('auth');
})->name('logout');

/* =========================
   NOTES (protected routes)
========================= */


/* NOTES index (with optional ?q= and highlight) */
Route::get('/notes', function (Request $r) {
    $uid = $r->session()->get('user_id');
    if (!$uid) return redirect()->route('auth');

    $q = trim((string)$r->query('q', ''));

    if ($q !== '') {
        $like = '%'.$q.'%';
        $notes = DB::select(
            "SELECT id, user_id, title, content, color, created_at, updated_at
             FROM notes
             WHERE user_id = ?
               AND (title LIKE ? OR content LIKE ?)
             ORDER BY updated_at DESC, created_at DESC",
            [$uid, $like, $like]
        );

        // Highlight matches in title/content
        $pattern = '/(' . preg_quote($q, '/') . ')/i';
        $replacement = '<mark class="highlight">$1</mark>';
        foreach ($notes as $note) {
            $note->title   = preg_replace($pattern, $replacement, (string)$note->title);
            $note->content = preg_replace($pattern, $replacement, (string)$note->content);
        }
    } else {
        $notes = DB::select(
            "SELECT id, user_id, title, content, color, created_at, updated_at
             FROM notes
             WHERE user_id = ?
             ORDER BY updated_at DESC, created_at DESC",
            [$uid]
        );
    }

    return view('notes.index', ['notes' => $notes, 'q' => $q]);
})->name('notes.index');

/* Create note — redirect to edit for normal form posts; JSON for AJAX */
Route::post('/notes', function (Request $r) {
    $userId  = $r->session()->get('user_id');
    if (!$userId) return redirect()->route('auth');

    $title   = $r->input('title', 'Untitled Note');
    $content = $r->input('content', '');
    $color   = $r->input('color', '#ffffff');

    DB::insert(
        "INSERT INTO notes (user_id, title, content, color, created_at, updated_at)
         VALUES (?, ?, ?, ?, NOW(), NOW())",
        [$userId, $title, $content, $color]
    );

    $noteId = DB::getPdo()->lastInsertId();

    if (trim((string)$content) !== '') {
        DB::insert(
            "INSERT INTO note_versions (note_id, content, created_at) VALUES (?, ?, NOW())",
            [$noteId, $content]
        );
    }

    if ($r->wantsJson() || $r->ajax()) {
        return response()->json(['ok' => true, 'id' => $noteId]);
    }
    return redirect()->route('notes.edit', ['id' => $noteId]);
})->name('notes.store');

/* Edit screen */
Route::get('/notes/{id}', function (Request $r, $id) {
    $userId = $r->session()->get('user_id');
    if (!$userId) return redirect()->route('auth');

    $note = DB::selectOne(
        "SELECT id, user_id, title, content, color, created_at, updated_at
         FROM notes WHERE id = ? AND user_id = ?",
        [$id, $userId]
    );
    if (!$note) abort(404);

    return view('notes.edit', ['note' => $note]);
})->name('notes.edit');

/* Update note — versioning kept; no group field anymore */
Route::post('/notes/{id}/update', function (Request $r, $id) {
    $uid = $r->session()->get('user_id');
    if (!$uid) return response('Unauthorized', 401);

    $note = DB::selectOne(
        "SELECT id, user_id, title, content, color FROM notes WHERE id = ? LIMIT 1",
        [$id]
    );
    if (!$note || (int)$note->user_id !== (int)$uid) return response('Forbidden', 403);

    $newTitle   = $r->input('title', null);
    $newContent = $r->input('content', null);
    $newColor   = $r->input('color', '#ffffff');

    $didChangeContent = (string)$newContent !== (string)$note->content;
    $didChangeTitle   = (string)$newTitle   !== (string)$note->title;
    $didChangeColor   = (string)$newColor   !== (string)$note->color;

    if (!($didChangeContent || $didChangeTitle || $didChangeColor)) {
        return response()->json(['ok' => true, 'changed' => false]);
    }

    DB::beginTransaction();
    try {
        if ($didChangeContent && !is_null($note->content) && trim((string)$note->content) !== '') {
            DB::insert(
                "INSERT INTO note_versions (note_id, content, created_at)
                 VALUES (?, ?, NOW())",
                [$note->id, $note->content]
            );
        }

        DB::update(
            "UPDATE notes
             SET title = ?, content = ?, color = ?, updated_at = NOW()
             WHERE id = ? AND user_id = ?",
            [$newTitle, $newContent, $newColor, $id, $uid]
        );

        DB::commit();
        return response()->json(['ok' => true, 'changed' => true]);
    } catch (\Throwable $e) {
        DB::rollBack();
        return response()->json(['error' => 'Update failed'], 500);
    }
})->name('notes.update');

/* Versions list */
Route::get('/notes/{id}/versions', function (Request $r, $id) {
    $uid = $r->session()->get('user_id');
    if (!$uid) return response('Unauthorized', 401);

    $own = DB::selectOne("SELECT user_id FROM notes WHERE id = ?", [$id]);
    if (!$own || (int)$own->user_id !== (int)$uid) return response('Forbidden', 403);

    $versions = DB::select(
        "SELECT id, note_id, created_at, LEFT(content, 200) AS preview
         FROM note_versions
         WHERE note_id = ?
         ORDER BY created_at DESC",
        [$id]
    );

    return response()->json($versions);
})->name('notes.versions');

/* Revert */
Route::post('/notes/{id}/revert/{versionId}', function (Request $r, $id, $versionId) {
    $uid = $r->session()->get('user_id');
    if (!$uid) return response('Unauthorized', 401);

    $note = DB::selectOne("SELECT id, user_id, content FROM notes WHERE id = ?", [$id]);
    if (!$note || (int)$note->user_id !== (int)$uid) return response('Forbidden', 403);

    $ver = DB::selectOne(
        "SELECT id, content, created_at FROM note_versions WHERE id = ? AND note_id = ?",
        [$versionId, $id]
    );
    if (!$ver) return response()->json(['error' => 'Version not found'], 404);

    DB::beginTransaction();
    try {
        if (!is_null($note->content) && trim((string)$note->content) !== '') {
            DB::insert(
                "INSERT INTO note_versions (note_id, content, created_at)
                 VALUES (?, ?, NOW())",
                [$note->id, $note->content]
            );
        }

        DB::update("UPDATE notes SET content = ?, updated_at = NOW() WHERE id = ?", [$ver->content, $id]);

        DB::commit();
        return response()->json([
            'ok'         => true,
            'content'    => $ver->content,
            'updated_at' => date('c'),
        ]);
    } catch (\Throwable $e) {
        DB::rollBack();
        return response()->json(['error' => 'Revert failed'], 500);
    }
})->name('notes.revert');

/* Delete — redirect for normal POSTs; JSON for AJAX */
Route::post('/notes/{id}/delete', function (Request $r, $id) {
    $uid = $r->session()->get('user_id');
    if (!$uid) {
        return $r->wantsJson() ? response()->json(['error' => 'Unauthorized'], 401)
                               : redirect()->route('auth');
    }

    DB::delete("DELETE FROM notes WHERE id = ? AND user_id = ?", [$id, $uid]);

    if ($r->wantsJson() || $r->ajax()) {
        return response()->json(['ok' => true]);
    }
    return redirect()->route('notes.index')->with('status', 'Note deleted');
})->name('notes.delete');

/* Share stub kept (UI only) */
Route::post('/notes/{id}/share', fn() => response()->json(['ok' => true]))->name('notes.share');

// Get a note as JSON for the edit modal
Route::get('/notes/{id}/raw', function (Illuminate\Http\Request $r, $id) {
    $uid = $r->session()->get('user_id');
    if (!$uid) return response()->json(['error' => 'Unauthorized'], 401);

    $note = DB::selectOne("SELECT id,user_id,title,content,color,updated_at FROM notes WHERE id = ? LIMIT 1", [$id]);
    if (!$note || (int)$note->user_id !== (int)$uid) return response()->json(['error' => 'Forbidden'], 403);

    return response()->json([
        'id'         => $note->id,
        'title'      => (string)$note->title,
        'content'    => (string)$note->content, // full HTML
        'color'      => (string)($note->color ?? '#ffffff'),
        'updated_at' => $note->updated_at,
    ]);
})->name('notes.raw');

// Get full content of one version for real preview
Route::get('/notes/{id}/versions/{versionId}', function (Illuminate\Http\Request $r, $id, $versionId) {
    $uid = $r->session()->get('user_id');
    if (!$uid) return response()->json(['error' => 'Unauthorized'], 401);

    $own = DB::selectOne("SELECT user_id FROM notes WHERE id = ?", [$id]);
    if (!$own || (int)$own->user_id !== (int)$uid) return response()->json(['error' => 'Forbidden'], 403);

    $ver = DB::selectOne("SELECT id, content, created_at FROM note_versions WHERE id = ? AND note_id = ?", [$versionId, $id]);
    if (!$ver) return response()->json(['error' => 'Not found'], 404);

    return response()->json([
        'id'         => $ver->id,
        'content'    => (string)$ver->content, // full HTML
        'created_at' => $ver->created_at,
    ]);
})->name('notes.versions.show');



/* =========================
   EVENTS
========================= */

/* Events list (upcoming) + quick-create modal */
Route::get('/events', function (Illuminate\Http\Request $r) {
    $uid = $r->session()->get('user_id');
    if (!$uid) return redirect()->route('auth');

    // Optional prefill from note
    $prefillTitle = $r->query('title', '');
    $prefillNoteId = (int) $r->query('note_id', 0) ?: null;

    // Upcoming next 90 days (adjust as you like)
    $events = DB::select(
        "SELECT e.*, n.title AS note_title
         FROM events e
         LEFT JOIN notes n ON n.id = e.note_id
         WHERE e.user_id = ?
         AND e.due_at >= NOW()
         ORDER BY e.due_at ASC
         LIMIT 200",
        [$uid]
    );

    return view('events.index', [
        'events'       => $events,
        'prefillTitle' => $prefillTitle,
        'prefillNoteId'=> $prefillNoteId,
    ]);
})->name('events.index');

/* Create event (AJAX or normal POST) */
Route::post('/events', function (Illuminate\Http\Request $r) {
    $uid = $r->session()->get('user_id');
    if (!$uid) return response()->json(['error' => 'Unauthorized'], 401);

    $r->validate([
        'title' => 'required|string|max:160',
        'date'  => 'required|date',        // YYYY-MM-DD
        'time'  => 'required',             // HH:MM
        'description' => 'nullable|string',
        'note_id' => 'nullable|integer',
    ]);

    $dueAt = $r->input('date').' '.$r->input('time').':00';

    DB::insert(
        "INSERT INTO events (user_id, note_id, title, description, due_at, created_at, updated_at)
         VALUES (?, ?, ?, ?, ?, NOW(), NOW())",
        [$uid, $r->input('note_id', null), $r->title, $r->input('description', null), $dueAt]
    );

    if ($r->wantsJson() || $r->ajax()) {
        $id = DB::getPdo()->lastInsertId();
        return response()->json(['ok' => true, 'id' => $id]);
    }
    return redirect()->route('events.index');
})->name('events.store');

/* UPDATE event */
Route::post('/events/{id}/update', function (Illuminate\Http\Request $r, $id) {
    $uid = $r->session()->get('user_id');
    if (!$uid) return $r->wantsJson()
        ? response()->json(['error' => 'Unauthorized'], 401)
        : redirect()->route('auth');

    $r->validate([
        'title'       => 'required|string|max:160',
        'date'        => 'required|date',   // YYYY-MM-DD
        'time'        => 'required',        // HH:MM
        'description' => 'nullable|string',
        'note_id'     => 'nullable|integer',
    ]);

    $dueAt = $r->input('date').' '.$r->input('time').':00';

    // Make sure the user owns it
    $own = DB::selectOne("SELECT id FROM events WHERE id = ? AND user_id = ?", [$id, $uid]);
    if (!$own) return $r->wantsJson()
        ? response()->json(['error' => 'Not found'], 404)
        : redirect()->route('events.index');

    DB::update(
        "UPDATE events
         SET title = ?, description = ?, note_id = ?, due_at = ?, updated_at = NOW()
         WHERE id = ? AND user_id = ?",
        [$r->title, $r->input('description', null), $r->input('note_id', null), $dueAt, $id, $uid]
    );

    if ($r->wantsJson() || $r->ajax()) {
        return response()->json(['ok' => true]);
    }
    return redirect()->route('events.index')->with('status', 'Event updated');
})->name('events.update');

/* Delete event */
Route::post('/events/{id}/delete', function (Illuminate\Http\Request $r, $id) {
    $uid = $r->session()->get('user_id');
    if (!$uid) return $r->wantsJson() ? response()->json(['error'=>'Unauthorized'], 401)
                                      : redirect()->route('auth');

    DB::delete("DELETE FROM events WHERE id = ? AND user_id = ?", [$id, $uid]);

    // If this was an AJAX/JSON request, return JSON. Otherwise, redirect.
    if ($r->wantsJson() || $r->ajax()) {
        return response()->json(['ok' => true]);
    }

    return redirect()->route('events.index')->with('status', 'Event deleted');
})->name('events.delete');


/* Calendar page */
Route::get('/events/calendar', function (Illuminate\Http\Request $r) {
    $uid = $r->session()->get('user_id');
    if (!$uid) return redirect()->route('auth');

    // Default to current month if none given
    $year  = (int)($r->query('year', date('Y')));
    $month = (int)($r->query('month', date('n')));

    return view('events.calendar', ['year' => $year, 'month' => $month]);
})->name('events.calendar');

/* Calendar feed (JSON) for a given month */
Route::get('/events/feed', function (Illuminate\Http\Request $r) {
    $uid = $r->session()->get('user_id');
    if (!$uid) return response()->json([], 401);

    $year  = (int)($r->query('year', date('Y')));
    $month = (int)($r->query('month', date('n')));
    $start = sprintf('%04d-%02d-01 00:00:00', $year, $month);
    // first day of next month
    $nextMonth = $month === 12 ? 1 : $month + 1;
    $nextYear  = $month === 12 ? $year + 1 : $year;
    $end = sprintf('%04d-%02d-01 00:00:00', $nextYear, $nextMonth);

    $rows = DB::select(
        "SELECT id, title, note_id, due_at
         FROM events
         WHERE user_id = ?
           AND due_at >= ? AND due_at < ?
         ORDER BY due_at ASC",
        [$uid, $start, $end]
    );

    return response()->json($rows);
})->name('events.feed');





/** Helper: require login via session user_id */
function requireUser(Request $r){
    $uid = $r->session()->get('user_id');
    if (!$uid) return [null, redirect()->route('auth')];
    return [$uid, null];
}

/** DASHBOARD: latest notes (2–3) + near deadlines */
Route::get('/dashboard', function(Request $r){
    // auth helper
    $uid = $r->session()->get('user_id');
    if (!$uid) return redirect()->route('auth');

    // latest 3 notes
    $notes = DB::table('notes')
        ->where('user_id', $uid)
        ->orderByDesc(DB::raw('COALESCE(updated_at, created_at)'))
        ->limit(3)
        ->get();

    // --- deadlines: detect the correct datetime column(s) that exist ---
    $candidateCols = [
        'due_at', 'due_date', 'deadline_at', 'deadline',
        'start_at', 'start_time', 'start',
        'event_at', 'event_date', 'date', 'when_at'
    ];

    $available = [];
    foreach ($candidateCols as $c) {
        if (Schema::hasColumn('events', $c)) $available[] = $c;
    }

    // If nothing suitable, show none to avoid SQL errors
    if (empty($available)) {
        $deadlines = collect();
        return view('dashboard', compact('notes','deadlines'));
    }

    // Use the first available column for filtering/sorting
    $primaryCol = $available[0];

    $now  = Carbon::now();
    $soon = (clone $now)->addDays(14);

    $deadlines = DB::table('events')
        ->where('user_id', $uid)
        ->whereBetween($primaryCol, [$now, $soon])
        ->orderBy($primaryCol)
        ->limit(5)
        ->get();

    // pass the chosen column so view knows what to format
    return view('dashboard', [
        'notes'        => $notes,
        'deadlines'    => $deadlines,
        'deadline_col' => $primaryCol,
    ]);
})->name('dashboard');

/** PROFILE: view + update basic info */
Route::get('/profile', function(Request $r){
    [$uid, $redir] = requireUser($r); if ($redir) return $redir;

    $user = DB::table('users')->where('id', $uid)->first();
    abort_if(!$user, 404);

    return view('profile', ['user' => $user]);
})->name('profile');

/** Save profile (full_name, email). Username is shown but not editable */
Route::post('/profile', function(Request $r){
    [$uid, $redir] = requireUser($r); if ($redir) return $redir;

    $full = trim((string)$r->input('full_name', ''));
    $email = trim((string)$r->input('email', ''));

    if ($full === '' || $email === '') {
        return back()->with('status', 'Full name and email are required.')->withInput();
    }

    DB::table('users')->where('id', $uid)->update([
        'full_name' => $full,
        'email'     => $email,
        'updated_at'=> now(),
    ]);

    return back()->with('status', 'Profile updated.');
})->name('profile.update');

/** Change password (current -> new) */
Route::post('/profile/password', function(Request $r){
    $uid  = $r->session()->get('user_id');
    if (!$uid) return redirect()->route('auth');

    $curr = (string)$r->input('current_password', '');
    $pass = (string)$r->input('password', '');
    $conf = (string)$r->input('password_confirmation', '');

    if ($pass === '' || $pass !== $conf) {
        return back()->with('status', 'Passwords do not match.')->withInput();
    }

    // Update only if current password matches exactly (plaintext)
    // MySQL/MariaDB: NOW();  SQLite: datetime('now');  Postgres: NOW()
    $affected = DB::update(
        'UPDATE users SET password = ?, updated_at = NOW() WHERE id = ? AND password = ?',
        [$pass, $uid, $curr]
    );

    if ($affected < 1) {
        return back()->with('status', 'Current password is incorrect.')->withInput();
    }

    return back()->with('status', 'Password updated.');
})->name('profile.password');