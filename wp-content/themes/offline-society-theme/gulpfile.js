// Include gulp
var gulp = require('gulp'); 

// Include Our Plugins
var jshint = require('gulp-jshint');
var sass = require('gulp-sass');
var concat = require('gulp-concat');
var uglify = require('gulp-uglify');
var minifyCSS = require('gulp-minify-css');
var sourcemaps = require('gulp-sourcemaps');
var plumber = require('gulp-plumber');
var rename = require('gulp-rename');

// Lint Task
gulp.task('lint', function() {
    return gulp.src('js/lib/*.js')
        .pipe(jshint())
        .pipe(jshint.reporter('default'));
});

// Compile Our Sass
gulp.task('sass', function () {
    gulp.src('scss/*.scss')
        .pipe(plumber())
        .pipe(sourcemaps.init())
        .pipe(sass({sourcemap: true}))
        .pipe(gulp.dest('css'))
        .pipe(sourcemaps.write())
        .pipe(concat('main.css'))
        .pipe(gulp.dest('./'))
        .pipe(minifyCSS())
        .pipe(rename('main.min.css'))
        .pipe(gulp.dest('./css'));
});

// Concatenate & Minify JS
gulp.task('scripts', function() {
    return gulp.src('js/lib/*.js')
        .pipe(plumber())
        .pipe(concat('app.js'))
        .pipe(gulp.dest('./js'))
        .pipe(rename('app.min.js'))
        .pipe(uglify())
        .pipe(gulp.dest('./js'));
});

// Watch Files For Changes
gulp.task('watch', function() {
    gulp.watch('js/lib/*.js', ['lint', 'scripts']);
    gulp.watch(['scss/*.scss','scss/**/*.scss'], ['sass']);
});

// Default Task
gulp.task('default', ['lint', 'sass', 'scripts', 'watch']);