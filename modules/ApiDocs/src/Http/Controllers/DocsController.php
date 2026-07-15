<?php

namespace Modules\ApiDocs\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class DocsController extends Controller
{
    public function show(string $page): View
    {
        $pages = config('api-docs.pages', []);
        $menu = config('api-docs.menu', []);

        if (! isset($pages[$page])) {
            abort(404);
        }

        $brand = config('api-docs.brand', []);
        $baseUrl = rtrim(config('api-docs.base_url', ''), '/');

        $replacements = [
            ':brand' => $brand['name'] ?? 'API',
            ':base_url' => $baseUrl,
            ':support_email' => $brand['support_email'] ?? '',
            ':server_ip' => $brand['server_ip'] ?? '',
        ];

        $pageData = $this->replacePlaceholders($pages[$page], $replacements);

        return view('api-docs::page', [
            'pageKey' => $page,
            'page' => $pageData,
            'menu' => $menu,
            'brand' => $brand,
            'baseUrl' => $baseUrl,
        ]);
    }

    private function replacePlaceholders(mixed $data, array $replacements): mixed
    {
        if (is_string($data)) {
            return str_replace(array_keys($replacements), array_values($replacements), $data);
        }

        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = $this->replacePlaceholders($value, $replacements);
            }
        }

        return $data;
    }
}
