<?php

namespace Znck\Sereno\Builders;

use Symfony\Component\Finder\SplFileInfo;
use Znck\Sereno\Contracts\Builder;
use Znck\Sereno\ProcessorFactory;

class BlogBuilder implements Builder
{
    protected $page;
    /**
     * @var \Znck\Sereno\ProcessorFactory
     */
    protected $processor;

    public function __construct(ProcessorFactory $processor)
    {
        $this->processor = $processor;
    }

    public function handledPatterns(): array
    {
        return ['/^blog\..*/'];
    }

    public function data(array $files, array $data) : array
    {
        return $data;
    }

    public function build(array $files, array $data)
    {
        $pages = array_chunk($data['blog_posts'] ?? [], config('blog.postsPerPage', 10));
        $total_pages = count($pages);

        $options = [
            'interceptor' => [$this, 'getOutputFilename'],
        ];
        foreach ($files as $file) {
            foreach ($pages as $index => $posts) {
                $this->page = $index + 1;
                $paginator = [
                    'posts'        => $this->preparePosts($posts),
                    'current_page' => $this->page,
                    'prev_page'    => $this->page < $total_pages ? $this->page + 1 : null,
                    'next_page'    => $this->page > 1 ? $this->page - 1 : null,
                    'total_pages'  => $total_pages,
                ];
                if ($index === 0) {
                    $this->page = null;
                    $this->processor->process($file, $paginator + $data, $options);
                    $this->page = 1;
                }

                $this->processor->process($file, $paginator + $data, $options);
            }
        }
    }

    public function getOutputFilename(SplFileInfo $file)
    {
        $blog = array_first(explode('.', $file->getRelativePathname())) ?? 'blog';

        if (is_null($this->page)) {
            return $blog.DIRECTORY_SEPARATOR.'index.html';
        }

        return $blog.DIRECTORY_SEPARATOR.$this->page.DIRECTORY_SEPARATOR.'index.html';
    }

    protected function preparePosts(array $posts)
    {
        return array_map(function ($post) {
            return (object) $post;
        }, $posts);
    }
}
