<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FrontendController extends Controller
{
    public function __invoke(Request $request, string $path = 'index.html'): Response
    {
        $frontendRoot = realpath(env('FRONTEND_PATH', base_path('../ISC-KINDU/isc-kindu.local')));

        abort_if($frontendRoot === false, 404);

        $path = trim($path, '/');
        $path = $path === '' ? 'index.html' : $path;

        $detailTemplate = $this->detailTemplate($path);
        $needsBaseTag = $detailTemplate !== null;

        if ($detailTemplate !== null) {
            $path = $detailTemplate;
        }

        $candidates = [$path];

        if (! str_contains(basename($path), '.')) {
            $candidates[] = $path.'.html';
            $candidates[] = $path.'/index.html';
        }

        foreach ($candidates as $candidate) {
            $file = realpath($frontendRoot.DIRECTORY_SEPARATOR.str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $candidate));

            if ($file !== false && str_starts_with($file, $frontendRoot) && is_file($file)) {
                if ($needsBaseTag && in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['html', 'htm'], true)) {
                    return response($this->withBaseTag(file_get_contents($file)), 200, [
                        'Content-Type' => $this->contentType($file),
                    ]);
                }

                return response()->file($file, [
                    'Content-Type' => $this->contentType($file),
                ]);
            }
        }

        abort(404);
    }

    private function detailTemplate(string $path): ?string
    {
        $path = trim($path, '/');

        if (preg_match('#^actualites/[^/]+$#', $path)) {
            return 'actualites.html';
        }

        if (preg_match('#^publications/[^/]+$#', $path)) {
            return 'articles.html';
        }

        if (preg_match('#^evenements/[^/]+$#', $path)) {
            return 'actualites.html';
        }

        if (preg_match('#^diplomes/[^/]+$#', $path)) {
            return 'diplomes.html';
        }

        if (in_array($path, [
            'presentation-de-lisc-kindu.html',
            'conseil-administration.html',
            'directeur-general.html',
            'conseil-de-section.html',
        ], true)) {
            return 'articles.html';
        }

        return null;
    }

    private function withBaseTag(string $html): string
    {
        if (str_contains($html, '<base ')) {
            return $html;
        }

        return preg_replace('/<head(\s[^>]*)?>/i', '$0'."\n  ".'<base href="/">', $html, 1) ?? $html;
    }

    private function contentType(string $file): string
    {
        return match (strtolower(pathinfo($file, PATHINFO_EXTENSION))) {
            'css' => 'text/css; charset=utf-8',
            'js' => 'application/javascript; charset=utf-8',
            'json' => 'application/json; charset=utf-8',
            'html', 'htm' => 'text/html; charset=utf-8',
            'svg' => 'image/svg+xml',
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'ico' => 'image/x-icon',
            'pdf' => 'application/pdf',
            default => 'application/octet-stream',
        };
    }
}
