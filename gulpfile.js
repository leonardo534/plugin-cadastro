const gulp = require('gulp');
const sass = require('gulp-sass')(require('sass'));


gulp.task('sass', function() {
    return gulp.src('sass/style.scss')
        .pipe(sass({ outputStyle: 'compressed' }).on('error', sass.logError))
        .pipe(gulp.dest('.'));
});

gulp.task('watch', function() {
    gulp.watch('sass/**/*.scss', gulp.series('sass'));
});

gulp.task('default', gulp.series('sass', 'watch'));
