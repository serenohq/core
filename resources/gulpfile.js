if (process.env.CIRCLECI) {
  process.env.DISABLE_NOTIFIER = true;
}

var gulp = require('gulp');
var elixir = require('laravel-elixir');
var argv = require('yargs').argv;

elixir.config.publicPath = 'content/assets';

elixir(function (mix) {
  var env = argv.e || argv.env || 'dev';

  mix.sass(['app.scss'])
      .browserify('app.js')
      .exec('sereno build --env=' + env, [
        'docs/*',
        'docs/**/*',
        '.sereno/resources/*',
        '.sereno/resources/**/*',
      ])
      .browserSync({
        server: {
          baseDir: 'public'
        },
        proxy: null,
        files: ['public/**/*']
      });
});