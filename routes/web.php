<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

/* AUTH PAGE (combined login/register UI) */
Route::get('/auth', fn() => view('auth.auth'))->name('auth');

/* REGISTER — Step 1: username, email, password */
Route::post('/register', function (Request $r) {
    $r->validate([
        'username' => 'required|string|max:50|unique:users,username',
        'email'    => 'required|email|max:100|unique:users,email',
        'password' => 'required|string|min:3',
    ], [
        'username.unique' => 'Username already taken.',
        'email.unique'    => 'A user with this email already exists.',
    ]);

    // Insert (plain password as requested)
    DB::insert(
        "INSERT INTO users (username, email, password, created_at) VALUES (?, ?, ?, NOW())",
        [$r->username, $r->email, $r->password]
    );

    // Get new id, move to Step 2
    $id = DB::getPdo()->lastInsertId();
    $r->session()->put('pending_user_id', $id);

    return redirect()->route('register.details.form');
})->name('register');

/* REGISTER — Step 2 form: full_name, age, optional profile_pic */
Route::get('/register/details', function (Request $r) {
    if (!$r->session()->has('pending_user_id')) {
        // if they refreshed/cleared, go back to auth page (register panel)
        return redirect()->route('auth')->with('show_register', true);
    }
    return view('auth.register_details');
})->name('register.details.form');

/* REGISTER — Step 2 submit: update same row */
Route::post('/register/details', function (Request $r) {
    $id = $r->session()->get('pending_user_id');
    if (!$id) return redirect()->route('auth')->with('show_register', true);

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

    // finish registration: clear pending id, “log in”
    $r->session()->forget('pending_user_id');
    $r->session()->put('user_id', $id);

    return redirect()->route('dashboard')->with('status', 'Registration complete!');
})->name('register.details.submit');

/* LOGIN — (username or email) + plain password */
Route::post('/login', function (Request $r) {
    $r->validate([
        'email'    => 'required|string',  // can be username or email
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

    return back()->withErrors(['login' => 'Invalid credentials'])->withInput();
})->name('login');

/* DASHBOARD */
Route::get('/dashboard', function (Request $r) {
    $id = $r->session()->get('user_id');
    if (!$id) return redirect()->route('auth');

    $user = DB::selectOne("SELECT * FROM users WHERE id = ? LIMIT 1", [$id]);
    return view('dashboard', ['u' => $user]);
})->name('dashboard');

/* LOGOUT */
Route::post('/logout', function (Request $r) {
    $r->session()->forget('user_id');
    return redirect()->route('auth');
})->name('logout');
// NEW: A simple middleware to check if the user is logged in
Route::aliasMiddleware('auth.check', function (Request $request, Closure $next) {
    if (!$request->session()->has('user_id')) {
        return redirect()->route('auth');
    }
    return $next($request);
});

// NEW: Group for all routes that require a user to be logged in
Route::middleware('auth.check')->group(function () {

    /* CREATE A NEW NOTE (Handles the '+' button click) */
    Route::post('/notes', function (Request $r) {
        $userId = $r->session()->get('user_id');
        $title = "Untitled Note";
        $content = ""; // Start with an empty note

        // Insert into notes table
        DB::insert(
            "INSERT INTO notes (user_id, title, content, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())",
            [$userId, $title, $content]
        );

        $noteId = DB::getPdo()->lastInsertId();

        // Create the first version record
        DB::insert(
            "INSERT INTO note_versions (note_id, content, created_at) VALUES (?, ?, NOW())",
            [$noteId, $content]
        );

        // Redirect to the new note's edit page
        return redirect()->route('notes.edit', ['id' => $noteId]);
    })->name('notes.store'); // This defines the route your form is looking for

    /* SHOW A SINGLE NOTE FOR EDITING */
    Route::get('/notes/{id}', function (Request $r, $id) {
        $userId = $r->session()->get('user_id');
        $note = DB::selectOne(
            "SELECT * FROM notes WHERE id = ? AND user_id = ?",
            [$id, $userId]
        );

        if (!$note) {
            abort(404); // Show a 'Not Found' error if the note doesn't belong to the user
        }

        // You will need to create this view file next
        return view('notes.edit', ['note' => $note]);
    })->name('notes.edit');

});