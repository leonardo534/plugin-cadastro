const sass = require('node-sass');

sass.render({
    file: 'sass/style.scss',
    outFile: 'style.css',
    outputStyle: 'compressed' // ou 'expanded' para não minificar
}, function(error, result) {
    if (!error) {
        console.log('Sass compiled successfully!');
    } else {
        console.error(error);
    }
});
