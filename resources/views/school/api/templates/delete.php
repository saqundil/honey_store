<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_admin();
verify_csrf($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);

$id = (int) (request_json()['id'] ?? 0);
$templates = new App\Repositories\TemplateRepository(db(), current_user_id(), is_super_admin());
$template = $templates->find($id);
if (!$template) {
	json_response(['ok' => false, 'message' => 'القالب غير موجود أو لا تملك صلاحية حذفه.'], 404);
}
if ($template['status'] === 'archived') {
	json_response(['ok' => true]);
}

$statement = db()->prepare("UPDATE table_templates SET status='archived' WHERE id=?");
$statement->execute([$id]);
json_response(['ok' => true]);