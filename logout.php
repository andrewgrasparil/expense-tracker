<?php
/**
 * logout.php
 * ---------------------------------------------------------------
 * Logs the user out: forget who they are, give the browser a new
 * session id, and send them back to the login page.
 * (No database needed here, so we only load the helper functions.)
 * ---------------------------------------------------------------
 */
require_once __DIR__ . '/includes/functions.php';

$_SESSION = [];                 // remove everything stored in the session
session_regenerate_id(true);    // brand new session id

set_flash('success', 'You have been logged out.');
redirect('login.php');
