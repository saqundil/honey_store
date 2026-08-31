<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SchoolPageController extends Controller
{
    public function __invoke(Request $request, ?string $page = null): Response
    {
        $page = $page ?: 'index.php';

        abort_if(
            str_contains($page, '..') || ! preg_match('/^[A-Za-z0-9_\/-]+\.php$/', $page),
            404
        );

        $viewPath = resource_path('views/school/'.$page);
        abort_unless(is_file($viewPath), 404);

        putenv('APP_URL='.$request->getSchemeAndHttpHost().'/school');
        $_SERVER['SCRIPT_NAME'] = '/school/'.$page;

        ob_start();

        try {
            require $viewPath;

            return response(ob_get_clean());
        } catch (\Throwable $exception) {
            ob_end_clean();
            throw $exception;
        }
    }
}