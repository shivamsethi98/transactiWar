<?php
// Logout is POST-only (CSRF validated by router)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/dashboard');
}

destroy_session();

// Start new session for flash message
init_session();
set_flash('success', 'You have been logged out.');
redirect('/login');
