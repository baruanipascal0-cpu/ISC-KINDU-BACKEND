<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FrontendController extends Controller
{
    public function __invoke(Request $request, string $path = 'index.html'): Response
    {
        $frontendRoot = $this->frontendRoot();

        abort_if($frontendRoot === false, 404);

        $path = trim($path, '/');
        $path = $path === '' ? 'index.html' : $path;

        $detailTemplate = $this->detailTemplate($path);
        $needsBaseTag = $detailTemplate !== null;

        if ($detailTemplate !== null) {
            $path = $detailTemplate;
        } else {
            $path = $this->legacyTemplate($path) ?? $path;
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

        return preg_match('#^(actualites?|news|publications?|documents|frais|fees|evenements?|events|diplomes|palmares|graduation-lists|medias?|gallery|galerie|enseignants?|teachers|pages?)/[^/]+$#', $path)
            ? 'detail.html'
            : null;
    }

    private function legacyTemplate(string $path): ?string
    {
        return [
            'presentation-de-lisc-kindu.html' => 'aboutus.html',
            'facultes-et-entites.html' => 'formation/licence.html',
            'inscriptions.html' => 'inscription.html',
            'diplomes.html' => 'nos-diplomes.html',
            'palmares.html' => 'nos-palmares.html',
            'actualites.html' => 'blog.html',
            'publications.html' => 'documents.html',
            'articles.html' => 'documents.html',
        ][trim($path, '/')] ?? null;
    }

    private function frontendRoot(): string|false
    {
        foreach (array_filter([
            env('FRONTEND_PATH'),
            base_path('../ISC/www.isig.ac.cd'),
            base_path('../ISC-KINDU/isc-kindu.local'),
        ]) as $candidate) {
            $root = realpath($candidate);

            if ($root !== false) {
                return $root;
            }
        }

        return false;
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
