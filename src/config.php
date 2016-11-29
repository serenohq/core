<?php

return [
    'view' => [
        'extends' => 'markdown',
        'yields'  => 'markdown-body',
    ],

    'blog' => [
        'enable_comments' => false,

        'directory'    => 'content/_blog',
        'url_prefix'   => 'blog',
        'postsPerPage' => 15,

        'post' => [
            'extends' => 'blog.post',
            'yields'  => 'post-content',
        ],
    ],

    'docs' => [
        'directory'  => 'content/_docs',
        'url_prefix' => 'docs',
        'index'      => 'documentation',
        // Default Page
        // 'default' => 'installalation',
        // Markdown Properties.
        'extends' => 'docs.base',
        'yields'  => 'doc_content',
    ],

    'github' => [
        'branch' => 'master',
    ],

    'sereno' => [
        'commands' => [],

        'extensions' => [
            Znck\Sereno\DefaultExtension::class,
        ],

        'directory' => [
            'content',
        ],

        'views' => [],

        'special_files' => [
            '404',
            '.htaccess',
        ],

        'ignore' => [],

        'url'        => '/',
    ],

    'siteName'        => 'Sereno',
    'siteDescription' => 'Just another website created with Sereno',
];
