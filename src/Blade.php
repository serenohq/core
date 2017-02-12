<?php

namespace Sereno;

use Illuminate\Support\Str;
use Illuminate\View\Compilers\BladeCompiler;

class Blade
{
    /**
     * @var BladeCompiler
     */
    protected $compiler;

    public function __construct(BladeCompiler $compiler)
    {
        $this->compiler = $compiler;
        $this->registerMarkdownDirective();
        $this->registerUrlDirective();
        $this->registerActiveDirective();
    }

    protected function registerMarkdownDirective()
    {
        $this->getCompiler()->directive('markdown', function () {
            return '<?php ob_start(); ?>';
        });

        $this->getCompiler()->directive('endmarkdown', function () {
            return '<?php $__markdown_contents__ = ob_get_contents(); ob_end_clean(); echo \\Sereno\\Parsers\\Markdown::parse($__markdown_contents__); ?>';
        });
    }

    protected static function isValidUrl($path)
    {
        if (Str::startsWith($path, ['#', '//', 'mailto:', 'tel:', 'http://', 'https://'])) {
            return true;
        }

        return filter_var($path, FILTER_VALIDATE_URL) !== false;
    }

    public static function urlDirective(string $path)
    {
        if (self::isValidUrl($path)) {
            return $path;
        }

        $root = config('sereno.url', '');


        return rtrim($root, '/').'/'.ltrim($path, '/');
    }

    protected function registerUrlDirective()
    {
        $this->getCompiler()->directive('url', function ($expression) {
            return "<?php echo \\Sereno\\Blade::urlDirective(${expression} ?? \$currentUrlPath); ?>";
        });
    }

    public static function activeDirective(string $currentUrl, string $url, string $class = 'active')
    {
        $url = trim($url, '/');

        $currentUrl = trim($currentUrl, '/');

        if (starts_with($currentUrl, $url) or hash_equals($currentUrl, $url)) {
            return $class;
        }

        return '';
    }

    protected function registerActiveDirective()
    {
        $this->getCompiler()->directive('active', function ($expression) {
            return "<?php echo \\Sereno\\Blade::activeDirective(\$currentUrlPath, ${expression}); ?>";
        });
    }

    public function getCompiler(): BladeCompiler
    {
        return $this->compiler;
    }
}
