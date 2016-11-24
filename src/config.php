<?php

return [
    'view' => [
        'extends' => '_includes.markdown',
        'yields' => 'markdown-body',
    ],

    'blog' => [
        'enable_comments' => false,

        'postsPerPage' => 15,

        'post' => [
            'extends' => '_includes.blog.post',
            'yields' => 'post-content',
        ],
    ],

    'docs' => [
        'directory' => '_docs',
        'url_prefix' => 'docs',
        'index' => 'documentation',
        // Default Page
        // 'default' => 'installalation',
        // Markdown Properties.
        'extends' => '_includes.docs.base',
        'yields' => 'doc_content',
    ],

    'github' => [
        'branch' => 'master',
    ],

    'sereno' => [
        'builders' => [
            \Znck\Sereno\Builders\BlogPostBuilder::class,
            \Znck\Sereno\Builders\CollectionBuilder::class,
            \Znck\Sereno\Builders\BlogBuilder::class,
            \Znck\Sereno\Builders\DocsBuilder::class,
        ],

        'default_processor' => \Znck\Sereno\Processors\FileProcessor::class,

        'processors' => [
            \Znck\Sereno\Processors\BladeProcessor::class,
            \Znck\Sereno\Processors\MarkdownProcessor::class,
        ],

        'extractors' => [
            \Znck\Sereno\Extractors\BladeExtractor::class,
            \Znck\Sereno\Extractors\MarkdownYamlExtractor::class,
        ],

        'commands' => [],

        'special_files' => [
            '404',
            '.htaccess',
        ],
    ],

    'base_url' => '/',
    'siteName' => 'Sereno',
    'siteDescription' => 'Just another website created with Sereno',
];
