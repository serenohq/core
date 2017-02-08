if (process.env.CIRCLECI) {
  process.env.DISABLE_NOTIFIER = true;
}

var elixir = require('laravel-elixir');
var argv = require('yargs').argv;

elixir.config.publicPath = 'content/assets';

elixir(function (mix) {
  var env = argv.e || argv.env || 'default';

  mix.sass(['app.scss'])
      .browserify('app.js')
      .exec('sereno build --dir=.. --env=' + env, [
        '../../sereno.yml',
        '../../sereno.*.yml',
        '../../docs/*',
        '../../docs/**/*',
        'content/*',
        'content/**/*',
        'resources/*',
        'resources/**/*'
      ])
      .browserSync({
        server: {
          baseDir: 'public'
        },
        proxy: null,
        files: ['public/**/*']
      });
});
