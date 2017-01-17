<?php return [
    /*
    |--------------------------------------------------------------------------
    | Website Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your website. This value is used to Created
    | title for each page. It is also used in navigation bar. For personal
    | website, it should be set to your full name, (e.g. Rahul Kadyan).
    |
    */
    'siteName' => 'My Serene Website',

    /*
    |--------------------------------------------------------------------------
    | Website Description
    |--------------------------------------------------------------------------
    |
    | This value is the short description of your website. This value is
    | used to provide meta information to search engines or websites.
    | Try to keep it short (upto 200 characters) and simple.
    |
    */
    'siteDescription' => 'My space on the internet - another Sereno website.',

    /*
    |--------------------------------------------------------------------------
    | Github Pages
    |--------------------------------------------------------------------------
    |
    | Use Github Pages to publish your website for free.
    |
    */
    'github' => [
        /*
        |--------------------------------------------------------------------------
        | Repository
        |--------------------------------------------------------------------------
        |
        | The `repository` where the project is hosted.
        |
        */
        // 'repository' => 'git@github.com:user/repository.git',

        /*
        |--------------------------------------------------------------------------
        | Branch
        |--------------------------------------------------------------------------
        |
        | The `branch` value is used to publish your website on Github Pages.
        |
        | Possible values for `branch`:
        |   - master
        |     For your personal website.
        |     Name of the repository is `<your github username>.github.io`.
        |     Your default working branch should be 'dev' (anything but not master).
        |
        |   - gh-pages
        |     For project website.
        |     Your default working branch should be 'master' (anything but not gh-pages).
        */
        // 'branch' => 'gh-pages',

        /*
        |--------------------------------------------------------------------------
        | Git User
        |--------------------------------------------------------------------------
        |
        | Use this user to deploy built files.
        |
        */
        // 'user' => [
        //     'name' => '',
        //     'email' => '',
        // ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Markdown Configuration for Website
    |--------------------------------------------------------------------------
    |
    | The `extends` value sets the parent blade component for the markdown
    | renderer. And `yields` value places the content in the designated
    | place on the parent blade component.
    |
    */
    'view' => [
        'extends' => 'markdown',
        'yields'  => 'markdown-body',
    ],

    'sereno' => [
        'cache' => '_cache',

        'public' => 'public',
        /*
        |--------------------------------------------------------------------------
        | Website URL
        |--------------------------------------------------------------------------
        |
        | This URL is used by the Sereno to properly generate URLs when using
        | the building the website. You should set this to the root of your
        | website so that it is generates correct URLs for pages & assets.
        |
        */
        'url' => '/',

        /*
        |--------------------------------------------------------------------------
        | Commands
        |--------------------------------------------------------------------------
        |
        | Add commands to Sereno CLI.
        |
        */
        'commands' => [],

        /*
        |--------------------------------------------------------------------------
        | Extensions
        |--------------------------------------------------------------------------
        |
        | Extensions add functionalities to Sereno. `DefaultExtension` provides
        | all the features offered by Sereno.
        |
        */
        'extensions' => [],

        /*
        |--------------------------------------------------------------------------
        | Website Content Directories
        |--------------------------------------------------------------------------
        |
        | These directories would be searched recursively to generate your website.
        |
        */
        'directory' => [
            'content',
        ],

        /*
        |--------------------------------------------------------------------------
        | Views
        |--------------------------------------------------------------------------
        |
        | Override default views from themes.
        |
        */
        'views' => [
            'resources/views',
        ],

        /*
        |--------------------------------------------------------------------------
        | Special Files
        |--------------------------------------------------------------------------
        |
        | Preserve names of these files.
        |
        */
        'special_files' => [
            '404',
        ],

        /*
        |--------------------------------------------------------------------------
        | Ignored Files
        |--------------------------------------------------------------------------
        |
        | These files won't appear in your generated website.
        |
        */
        'ignore' => [],
    ],
];
