<?php
require __DIR__ . '/includes/bootstrap.php';
school_redirect(user() ? 'admin/index.php' : 'login.php');