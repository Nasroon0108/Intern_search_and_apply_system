<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/csrf.php';
require_once dirname(__DIR__) . '/includes/auth.php';

init_session();
logout_user();
set_flash('success', 'You have been logged out.');
redirect(app_url('auth/login.php'));
