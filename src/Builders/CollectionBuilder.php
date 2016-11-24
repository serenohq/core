<?php

namespace Znck\Sereno\Builders;

use Znck\Sereno\Contracts\Builder;

class CollectionBuilder implements Builder
{
    protected $collections = [];

    protected $blogDirectory = '_blog';

    public function handledPatterns(): array
    {
        return [$this->blogDirectory.'/*', '/^collection\..*/'];
    }

    public function data(array $files, array $data) : array
    {
        $posts = $data['blog_posts'];

        foreach ($posts as $post) {
            if ($this->isCollection($post)) {
                $collection = $this->getCollectionSlug($post);

                $this->addToCollection($collection, $post);
            }
        }

        foreach ($data['blog_posts'] as $index => $post) {
            $data['blog_posts'][$index]['collection'] = $this->getCollectionOf($post);
        }

        $data['blog_collections'] = $this->collections;

        return $data;
    }

    public function build(array $files, array $data)
    {
    }

    protected function isCollection($post)
    {
        return count(explode('/', $this->normalizePathname($post['pathname']))) > 1;
    }

    protected function getCollectionSlug($post)
    {
        return array_first(explode('/', $this->normalizePathname($post['pathname']), 2));
    }

    protected function addToCollection(string $collection, $post)
    {
        if (! array_key_exists($collection, $this->collections)) {
            $this->collections[$collection] = [
                'name'  => $this->deSlugify($collection),
                'path'  => $collection,
                'posts' => [],
            ];
        }

        array_unshift($this->collections[$collection]['posts'], $post);
    }

    protected function deSlugify(string $collection): string
    {
        return ucfirst(str_replace('-', ' ', $collection));
    }

    protected function getCollectionOf($post)
    {
        if ($this->isCollection($post)) {
            return $this->collections[$this->getCollectionSlug($post)];
        }
    }

    protected function normalizePathname(string $pathname): string
    {
        return trim(preg_replace('#^'.preg_quote($this->blogDirectory, '#').'#', '', $pathname), DIRECTORY_SEPARATOR);
    }
}
