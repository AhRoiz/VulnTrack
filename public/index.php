<?php
require_once __DIR__ . '/../app/bootstrap.php';

redirect(Auth::check() ? 'dashboard.php' : 'login.php');


