<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_admin();
verify_csrf($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);

try {
    $payload = request_json();
    $actorId = current_user_id();
    $repository = new App\Repositories\GradebookRepository(db(), $actorId, is_super_admin());
    $saved = (new App\Services\GradebookService(db(), $repository, is_super_admin()))->saveValues(
        (int) ($payload['class_assessment_id'] ?? 0),
        (int) ($payload['assessment_template_id'] ?? 0),
        is_array($payload['rows'] ?? null) ? $payload['rows'] : [],
        $actorId
    );
    json_response(['ok' => true, 'saved' => $saved, 'saved_at' => date('H:i:s')]);
} catch (DomainException $exception) {
    json_response(['ok' => false, 'message' => $exception->getMessage(), 'conflict' => true], 409);
} catch (InvalidArgumentException $exception) {
    json_response(['ok' => false, 'message' => $exception->getMessage()], 422);
} catch (Throwable $exception) {
    error_log($exception->getMessage());
    json_response(['ok' => false, 'message' => 'تعذر حفظ العلامات.'], 500);
}
