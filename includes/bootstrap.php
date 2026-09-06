<?php
/**
 * Aurelia Bank — Application Bootstrap
 * -----------------------------------------------------------------------------
 * Single entry point that every page includes FIRST. It wires together the
 * core building blocks in the correct order:
 *
 *   config    -> constants, error reporting level
 *   functions -> shared helpers (escaping, urls, flash)
 *   errors    -> global exception/fatal handler (friendly 500 responses)
 *   session   -> secure PHP session
 *   db        -> lazy PDO connection (connects only when db() is first called)
 *   auth      -> authentication/authorization helpers (current_user, guards)
 *
 * Usage at the top of any page:
 *   require_once __DIR__ . '/includes/bootstrap.php';   // or the correct depth
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/errors.php';    // global exception/fatal handling
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';    // authentication / authorization helpers
