<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_admin();
verify_csrf($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);

try {
    $actorId = current_user_id();
    $repository = new App\Repositories\AssessmentSchemeRepository(db(), $actorId);
    $schemeId = (new App\Services\AssessmentSchemeService(db(), $repository))->publish(request_json(), $actorId);
    json_response(['ok' => true, 'id' => $schemeId]);
} catch (InvalidArgumentException $exception) {
    json_response(['ok' => false, 'message' => $exception->getMessage()], 422);
} catch (PDOException $exception) {
    error_log($exception->getMessage());
    json_response(['ok' => false, 'message' => 'يوجد تعارض مع مخطط محفوظ أو مع البيانات المختارة.'], 422);
} catch (Throwable $exception) {
    error_log($exception->getMessage());
    json_response(['ok' => false, 'message' => 'تعذر نشر مخطط التقييم.'], 500);
}
