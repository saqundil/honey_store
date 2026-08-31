<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
if (user()) redirect('admin/index.php');
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf($_POST['csrf_token'] ?? null);
    $statement = db()->prepare("SELECT * FROM admin_users WHERE email=? AND status='active' LIMIT 1");
    $statement->execute([strtolower(trim((string) ($_POST['email'] ?? '')))]);
    $admin = $statement->fetch();
    if ($admin && password_verify((string) ($_POST['password'] ?? ''), $admin['password_hash'])) {
        login_user($admin);
        if (!empty($admin['must_change_password'])) {
            redirect('admin/settings.php');
        }
        if ($admin['role'] === 'teacher') {
            $state = (new App\Services\TeacherSetupService(db()))->state((int) $admin['id']);
            redirect($state['complete'] ? 'admin/gradebook/index.php' : 'admin/setup.php');
        }
        redirect('admin/index.php');
    }
    $error = 'بيانات الدخول غير صحيحة.';
}
?><!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>تسجيل الدخول | <?= e(config('name')) ?></title>
    <link rel="stylesheet" href="<?= e(url('assets/css/core.css')) ?>">
    <link rel="stylesheet" href="<?= e(url('assets/css/admin.css')) ?>">
</head>
<body class="login-page">
<main class="login-panel">
    <div class="brand login-brand">
        <span class="brand-mark" aria-hidden="true">ج</span>
        <span><?= e(config('name')) ?></span>
    </div>

    <h1>تسجيل الدخول</h1>
    <p class="login-lede">أدخل بريدك وكلمة المرور للوصول إلى سجل العلامات والتقارير.</p>

    <?php if ($error): ?>
        <div class="alert error" role="alert"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post">
        <?= csrf_field() ?>
        <label>
            البريد الإلكتروني
            <input
                type="email"
                name="email"
                required
                autocomplete="username"
                autofocus
                value="<?= e($_POST['email'] ?? '') ?>"
                <?= $error ? 'aria-invalid="true"' : '' ?>
            >
        </label>
        <label>
            كلمة المرور
            <input type="password" name="password" required autocomplete="current-password" <?= $error ? 'aria-invalid="true"' : '' ?>>
        </label>
        <button class="button primary" type="submit">دخول</button>
    </form>

    <p class="login-foot"><?= e(config('school.name_ar')) ?></p>
</main>
</body>
</html>
