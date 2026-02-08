let mix = require('laravel-mix');
const purgeCss = require('@fullhuman/postcss-purgecss');

const path = require('path');
let directory = path.basename(path.resolve(__dirname));

const source = 'platform/themes/' + directory;
const dist = 'public/themes/' + directory;

mix
    .sass(
        source + '/assets/sass/style.scss',
        dist + '/css',
        {},
        [
            purgeCss({
                content: [
                    source + '/assets/js/components/*.vue',
                    source + '/layouts/*.blade.php',
                    source + '/partials/*.blade.php',
                    source + '/partials/**/*.blade.php',
                    source + '/views/*.blade.php',
                    source + '/views/**/*.blade.php',
                    source + '/views/**/**/*.blade.php',
                    source + '/views/**/**/**/*.blade.php',
                    source + '/views/**/**/**/*.blade.php',
                    source + '/widgets/**/templates/frontend.blade.php',
                ],
                defaultExtractor: content => content.match(/[\w-/.:]+(?<!:)/g) || [],
                safelist: [
                    /^navigation-/,
                    /^label-/,
                    /^status-/,
                    /^owl-/,
                    /^fa-/,
                    /^language/,
                    /^pagination/,
                    /^page-/,
                    /show-admin-bar/,
                    /breadcrumb/,
                    /active/,
                    /header-sticky/,
                    /show/
                ],
            })
        ]
    )

    .sass(source + '/assets/sass/rtl-style.scss', dist + '/css')

    .js(source + '/assets/js/app.js', dist + '/js')
    .js(source + '/assets/js/wishlist.js', dist + '/js')
    .js(source + '/assets/js/property.js', dist + '/js')
    .js(source + '/assets/js/review.js', dist + '/js')

    .copy(dist + '/css/style.css', source + '/public/css')
    .copy(dist + '/css/rtl-style.css', source + '/public/css')
    .copy(dist + '/js/app.js', source + '/public/js')
    .copy(dist + '/js/wishlist.js', source + '/public/js')
    .copy(dist + '/js/property.js', source + '/public/js')
    .copy(dist + '/js/review.js', source + '/public/js')
