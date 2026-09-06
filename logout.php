<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

logout_user();
session_start();
flash('success', 'Sessao terminada com sucesso.');
redirect('login.php');
