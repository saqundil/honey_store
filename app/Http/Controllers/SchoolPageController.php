<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class SchoolPageController extends Controller
{
    public function asset(string $asset): BinaryFileResponse
    {
        abort_if(str_contains($asset, '..') || ! preg_match('/^[A-Za-z0-9_.\/-]+$/', $asset), 404);

        $assetRoot = realpath(resource_path('views/school/assets'));
        $assetPath = realpath(resource_path('views/school/assets/'.$asset));

        abort_unless(
            $assetRoot !== false
            && $assetPath !== false
            && str_starts_with($assetPath, $assetRoot.DIRECTORY_SEPARATOR)
            && is_file($assetPath),
            404
        );

        $contentType = match (strtolower(pathinfo($assetPath, PATHINFO_EXTENSION))) {
            'css' => 'text/css; charset=utf-8',
            'js' => 'application/javascript; charset=utf-8',
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'svg' => 'image/svg+xml',
            'woff2' => 'font/woff2',
            'ttf' => 'font/ttf',
            default => abort(404),
        };

        return response()->file($assetPath, [
            'Cache-Control' => 'public, max-age=604800',
            'Content-Type' => $contentType,
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

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