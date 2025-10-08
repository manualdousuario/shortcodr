const gulp = require('gulp');
const concat = require('gulp-concat');
const uglify = require('gulp-uglify');
const cleanCSS = require('gulp-clean-css');
const rename = require('gulp-rename');
const sass = require('gulp-sass')(require('sass'));

const paths = {
    jsAdmin: {
        src: 'admin/source/js/**/*.js',
        dest: 'admin/dist/js/'
    },
    scssAdmin: {
        src: 'admin/source/css/**/*.scss',
        dest: 'admin/dist/css/'
    }
};

function processJSAdmin() {
    return gulp.src(paths.jsAdmin.src)
        .pipe(concat('shortlinkr-admin.js'))
        .pipe(gulp.dest(paths.jsAdmin.dest))
        .pipe(uglify())
        .pipe(rename({ suffix: '.min' }))
        .pipe(gulp.dest(paths.jsAdmin.dest));
}

function processSCSSAdmin() {
    return gulp.src(paths.scssAdmin.src)
        .pipe(sass().on('error', sass.logError))
        .pipe(concat('shortlinkr-admin.css'))
        .pipe(gulp.dest(paths.scssAdmin.dest))
        .pipe(cleanCSS())
        .pipe(rename({ suffix: '.min' }))
        .pipe(gulp.dest(paths.scssAdmin.dest));
}

function watchFiles() {
    gulp.watch(paths.jsAdmin.src, processJSAdmin);
    gulp.watch(paths.scssAdmin.src, processSCSSAdmin);
}

const build = gulp.parallel(processJSAdmin, processSCSSAdmin);
const watch = gulp.series(build, watchFiles);

exports.processJSAdmin = processJSAdmin;
exports.processSCSSAdmin = processSCSSAdmin;
exports.build = build;
exports.watch = watch;
exports.default = watch;