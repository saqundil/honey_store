<?php
require __DIR__ . '/includes/bootstrap.php';
redirect(user() ? 'admin/index.php' : 'login.php');