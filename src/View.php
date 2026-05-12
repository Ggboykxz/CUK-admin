<?php

declare(strict_types=1);

namespace CUK;

class View
{
    private static array $sections = [];
    private static string $currentSection = '';
    private static string $layout = '';
    private static array $shared = [];

    public static function share(string $key, mixed $value): void
    {
        self::$shared[$key] = $value;
    }

    public static function getShared(string $key, mixed $default = null): mixed
    {
        return self::$shared[$key] ?? $default;
    }

    public static function extends(string $layout): void
    {
        self::$layout = $layout;
    }

    public static function section(string $name): void
    {
        self::$currentSection = $name;
        ob_start();
    }

    public static function endSection(): void
    {
        if (self::$currentSection) {
            self::$sections[self::$currentSection] = ob_get_clean();
            self::$currentSection = '';
        }
    }

    public static function parentSection(): string
    {
        return '';
    }

    public static function render(string $view, array $data = []): void
    {
        extract($data);
        extract(self::$shared);

        $viewFile = __DIR__ . '/Views/' . $view . '.php';

        if (!file_exists($viewFile)) {
            throw new \RuntimeException("View non trouvée: {$view}");
        }

        ob_start();
        include $viewFile;
        $content = ob_get_clean();

        if (self::$layout) {
            $layoutFile = __DIR__ . '/Views/layouts/' . self::$layout . '.php';
            if (file_exists($layoutFile)) {
                self::$sections['content'] = $content;
                include $layoutFile;
                self::$layout = '';
                self::$sections = [];
                return;
            }
        }

        echo $content;
    }

    public static function fetch(string $view, array $data = []): string
    {
        $layout = self::$layout;
        self::$layout = '';
        ob_start();
        self::render($view, $data);
        return ob_get_clean();
    }

    public static function include(string $view, array $data = []): void
    {
        extract($data);
        extract(self::$shared);
        $file = __DIR__ . '/Views/' . $view . '.php';
        if (file_exists($file)) {
            include $file;
        }
    }

    public static function sectionContent(string $name, string $default = ''): string
    {
        return self::$sections[$name] ?? $default;
    }

    public static function hasSection(string $name): bool
    {
        return isset(self::$sections[$name]);
    }

    public static function component(string $component, array $data = []): void
    {
        self::include('components/' . $component, $data);
    }

    public static function paginate(array $items, int $page, int $perPage): array
    {
        $total = count($items);
        $pages = max(1, (int)ceil($total / $perPage));
        $offset = ($page - 1) * $perPage;
        return [
            'items' => array_slice($items, $offset, $perPage),
            'page' => $page,
            'perPage' => $perPage,
            'total' => $total,
            'pages' => $pages,
            'hasPrev' => $page > 1,
            'hasNext' => $page < $pages,
            'prevUrl' => $page > 1 ? '?page=' . Router::currentPage() . '&p=' . ($page - 1) : null,
            'nextUrl' => $page < $pages ? '?page=' . Router::currentPage() . '&p=' . ($page + 1) : null,
        ];
    }
}
