<?php

declare(strict_types=1);

/**
 * قوقعة لوحة الإدارة.
 *
 * شريط علوي من صفّين بدل الشريط الجانبي: الصف الأول للهوية والسياق
 * والحساب، والثاني للتنقّل. هذا يعيد ‎264px‎ من العرض إلى المحتوى —
 * وهو ما يحتاجه استوديو القوالب وورقة المعاينة بعرض ‎A4‎.
 *
 * التنقّل مقسوم إلى مجموعات يفصلها فاصل رفيع، لأن العناصر تسعة
 * وتنتمي إلى مهام مختلفة (العمل اليومي، البناء، البيانات، الحساب).
 */
function page_header(string $title, string $active = '', array $styles = []): void
{
    $isTeacher = (user()['role'] ?? null) === 'teacher';

    // تنقّل مبني على مهام المعلم لا على بنية قاعدة البيانات.
    // المجموعات مفهرسة بالاسم لا بالرقم، فإخفاء أي مجموعة لا يزحزح الباقي.
    $groups = [
        'daily' => [
            'dashboard' => ['admin/index.php', 'الرئيسية'],
            // 'classes'   => ['admin/classes/index.php', 'الصفوف'],
            // 'students'  => ['admin/students/index.php', 'الطلاب'],
            // 'gradebook' => ['admin/gradebook/index.php', 'الاختبارات والعلامات'],
            'reports'   => ['admin/reports/index.php', 'التقارير'],
        ],
        // إعداد يُلمس مرة أو مرتين في الفصل، لا كل يوم
        'setup' => [
            'templates' => ['admin/templates/index.php', ' السجل الجانبي'],
            // 'subjects'  => ['admin/subjects/index.php', 'المواد'],
        ],
        'account' => [
            'settings' => ['admin/settings.php', 'الإعدادات'],
        ],
    ];

    if ($isTeacher) {
        $groups['setup'] = ['setup' => ['admin/setup.php', 'إعداد السجل']] + $groups['setup'];
    }

    $groups = array_values(array_filter($groups));
    ?><!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="color-scheme" content="light">
    <title><?= school_e($title) ?> | <?= school_e(school_config('name')) ?></title>
    <link rel="stylesheet" href="<?= school_e(school_url('assets/css/core.css')) ?>">
    <link rel="stylesheet" href="<?= school_e(school_url('assets/css/admin.css?v=' . filemtime(dirname(__DIR__) . '/assets/css/admin.css'))) ?>">
    <link rel="stylesheet" href="<?= school_e(school_url('assets/css/school-brand.css')) ?>">
    <?php foreach ($styles as $style): ?>
        <link rel="stylesheet" href="<?= school_e(school_url($style)) ?>">
    <?php endforeach; ?>
</head>
<body>
<a class="skip-link" href="#main-content">تخطَّ إلى المحتوى</a>

<div class="app-shell">
    <header class="appbar" role="banner">
        <div class="appbar-top">
            <a class="brand" href="<?= school_e(school_url('admin/index.php')) ?>">
                <span class="brand-mark" aria-hidden="true">ج</span>
                <span class="brand-name"><?= school_e(school_config('name')) ?></span>
            </a>

            <span class="appbar-divider" aria-hidden="true"></span>
            <h1 class="appbar-title"><?= school_e($title) ?></h1>

            <div class="appbar-user">
                <span class="appbar-user-name" title="<?= school_e(user()['name'] ?? '') ?>"><?= school_e(user()['name'] ?? '') ?></span>
                <a href="<?= school_e(school_url('logout.php')) ?>">خروج</a>
            </div>
        </div>

        <nav class="appbar-nav" id="app-nav" aria-label="التنقل الرئيسي">
            <?php foreach ($groups as $index => $links): ?>
                <?php if ($index > 0): ?><span class="nav-sep" aria-hidden="true"></span><?php endif; ?>
                <?php foreach ($links as $key => [$path, $label]): ?>
                    <a
                        class="nav-link <?= $active === $key ? 'active' : '' ?>"
                        href="<?= school_e(school_url($path)) ?>"
                        <?= $active === $key ? 'aria-current="page"' : '' ?>
                    ><?= school_e($label) ?></a>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </nav>
    </header>

    <main class="main">
        <div class="content" id="main-content" tabindex="-1">
    <?php
}

function page_footer(array $scripts = []): void
{
    ?>
        </div>
    </main>
</div>
<script src="<?= school_e(school_url('assets/js/app.js')) ?>" defer></script>
<?php foreach ($scripts as $script): ?>
    <script src="<?= school_e(school_url($script)) ?>" defer></script>
<?php endforeach; ?>
</body>
</html><?php
}

/**
 * قوقعة واجهة المعلم. كروم أقل؛ سير عمل المعلم يعيش في الداخل.
 * الفتات مرتّب من الجذر إلى الحالي: [['label', 'href'], ..., ['current', null]].
 */
function teacher_shell_header(string $title, array $breadcrumbs = [], array $styles = []): void
{
    $userName = user()['name'] ?? '';
    ?><!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="color-scheme" content="light">
    <title><?= school_e($title) ?> · <?= school_e(school_config('name')) ?></title>
    <link rel="stylesheet" href="<?= school_e(school_url('assets/css/core.css')) ?>">
    <link rel="stylesheet" href="<?= school_e(school_url('assets/css/teacher.css')) ?>">
    <?php foreach ($styles as $style): ?>
        <link rel="stylesheet" href="<?= school_e(school_url($style)) ?>">
    <?php endforeach; ?>
</head>
<body>
<a class="skip-link" href="#tshell-main">تخطَّ إلى المحتوى</a>

<div class="tshell">
    <header class="tshell-topbar" role="banner">
        <a class="tshell-brand" href="<?= school_e(school_url('admin/gradebook/index.php')) ?>">
            <span class="tshell-brand-mark" aria-hidden="true">س</span>
            <span>سجل العلامات</span>
        </a>

        <nav class="tshell-crumbs" aria-label="مسار التصفح">
        <?php foreach ($breadcrumbs as $index => $crumb):
            $label = $crumb[0] ?? '';
            $href  = $crumb[1] ?? null;
            $isLast = $index === array_key_last($breadcrumbs);
            if ($index > 0): ?><span class="sep" aria-hidden="true">›</span><?php endif; ?>
            <?php if (!$isLast): ?>
                <a class="up" href="<?= school_e($href ? school_url($href) : '#') ?>"><?= school_e($label) ?></a>
            <?php else: ?>
                <span class="here" aria-current="page"><?= school_e($label) ?></span>
            <?php endif;
        endforeach; ?>
        </nav>

        <div class="tshell-user">
            <span class="tshell-user-label"><strong><?= school_e($userName) ?></strong></span>
            <a href="<?= school_e(school_url('logout.php')) ?>">خروج</a>
        </div>
    </header>

    <main class="tshell-main" id="tshell-main" role="main" tabindex="-1">
    <?php
}

function teacher_shell_footer(array $scripts = []): void
{
    ?>
    </main>
</div>
<script src="<?= school_e(school_url('assets/js/app.js')) ?>" defer></script>
<?php foreach ($scripts as $script): ?>
    <script src="<?= school_e(school_url($script)) ?>" defer></script>
<?php endforeach; ?>
</body>
</html><?php
}
