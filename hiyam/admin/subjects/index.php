<?php
declare(strict_types=1); require dirname(__DIR__,2).'/includes/bootstrap.php'; require_admin();
if($_SERVER['REQUEST_METHOD']==='POST'){verify_csrf($_POST['csrf_token']??null);$name=trim((string)($_POST['name']??''));if($name!==''){db()->prepare('INSERT INTO subjects(name) VALUES(?) ON DUPLICATE KEY UPDATE status="active"')->execute([$name]);}redirect('admin/subjects/index.php');}
$items=db()->query('SELECT * FROM subjects ORDER BY name')->fetchAll(); page_header('المواد','subjects');
?>
<section class="panel narrow">
    <div class="section-head">
        <div>
            <p class="eyebrow">البيانات المرجعية</p>
            <h2>المواد الدراسية</h2>
        </div>
    </div>

    <form method="post" class="inline-form">
        <?= csrf_field() ?>
        <input name="name" placeholder="اسم المادة" required aria-label="اسم المادة">
        <button class="button primary">إضافة</button>
    </form>

    <?php if ($items): ?>
        <ul class="simple-list">
            <?php foreach ($items as $item): ?>
                <li>
                    <span><?= e($item['name']) ?></span>
                    <span class="status <?= $item['status'] === 'active' ? 'active' : 'inactive' ?>"><?= $item['status'] === 'active' ? 'نشط' : 'غير نشط' ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <div class="empty" style="margin-top:var(--sp-4)">لم تُضف أي مادة بعد. اكتب اسم المادة أعلاه واضغط «إضافة».</div>
    <?php endif; ?>
</section>
<?php page_footer(); ?>
