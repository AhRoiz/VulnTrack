<?php
/**
 * Logout hanya menerima POST + CSRF agar tidak bisa dipicu lewat link atau gambar pihak ketiga.
 */
require_once __DIR__ . '/../app/bootstrap.php';

require_post();
Auth::logout();

// Mulai session baru agar flash message bisa ditampilkan di halaman login
session_start();
session_regenerate_id(true);
flash('info', 'Anda telah berhasil logout.');
redirect('login.php');
