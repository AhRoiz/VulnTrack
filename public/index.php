<?php
require __DIR__ . '/../app/bootstrap.php';

redirect(Auth::check() ? 'dashboard.php' : 'login.php');

