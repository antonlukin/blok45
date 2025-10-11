const gulp = require( 'gulp' );
const sass = require( 'gulp-sass' )( require( 'sass' ) );
const concat = require( 'gulp-concat' );
const cleanCss = require( 'gulp-clean-css' );
const sassGlob = require( 'gulp-sass-glob' );
const uglify = require( 'gulp-uglify' );
const plumber = require( 'gulp-plumber' );
const prefix = require( 'gulp-autoprefixer' );
const webpack = require( 'webpack-stream' );
const fs = require( 'fs' );
const path = require( 'path' );
const mergeStream = require( 'merge-stream' );

function getScriptEntries() {
	const dir = path.join( __dirname, 'src', 'scripts' );
	let files = [];

	try {
		files = fs.readdirSync( dir ).filter( ( f ) => f.endsWith( '.js' ) );
	} catch ( e ) {
		files = [ 'index.js' ];
	}

	const entries = {};
	files.forEach( ( f ) => {
		const base = path.basename( f, '.js' );
		const name = base === 'index' ? 'scripts' : base; // keep main bundle name
		entries[ name ] = './src/scripts/' + f;
	} );

	return entries;
}

gulp.task( 'styles', ( done ) => {
	gulp
		.src( 'src/styles/index.scss' )
		.pipe( plumber() )
		.pipe( sassGlob() )
		.pipe(
			sass( {
				errLogToConsole: true,
				silenceDeprecations: [ 'legacy-js-api' ],
			} ),
		)
		.pipe( prefix() )
		.pipe( concat( 'styles.min.css' ) )
		.pipe(
			cleanCss( {
				compatibility: 'ie9',
			} ),
		)
		.pipe( gulp.dest( 'public/assets/' ) );

	done();
} );

gulp.task( 'scripts', ( done ) => {
	gulp
		.src( 'src/scripts/*.js' )
		.pipe( plumber() )
		.pipe(
			webpack( {
				...require( './webpack.config.js' ),
				entry: getScriptEntries(),
				output: { filename: '[name].min.js' },
			} )
		)
		.pipe( uglify() )
		.pipe( gulp.dest( 'public/assets/' ) );

	done();
} );

gulp.task( 'vendor', ( done ) => {
	const swiperBase = 'node_modules/swiper';
	const dest = 'public/assets/swiper';

	const scripts = gulp
		.src( [
			path.join( swiperBase, 'swiper-bundle.min.js' ),
			path.join( swiperBase, 'swiper-bundle.min.js.map' ),
		], { allowEmpty: true } )
		.pipe( gulp.dest( dest ) );

	const styles = gulp
		.src( [
			path.join( swiperBase, 'swiper-bundle.min.css' ),
			path.join( swiperBase, 'swiper-bundle.min.css.map' ),
		], { allowEmpty: true } )
		.pipe( gulp.dest( dest ) );

	mergeStream( scripts, styles );

	done();
} );

// Standalone bundles are covered by generic 'scripts' task using entries

gulp.task( 'images', ( done ) => {
	gulp.src( 'src/images/**/*' ).pipe( gulp.dest( 'public/assets/images/' ) );

	done();
} );

gulp.task( 'icons', ( done ) => {
	gulp.src( 'src/icons/*.svg' ).pipe( gulp.dest( 'public/assets/images/' ) );

	done();
} );

gulp.task( 'fonts', ( done ) => {
	gulp.src( 'src/fonts/**/*.{ttf,woff,woff2}' ).pipe( gulp.dest( 'public/assets/fonts/' ) );

	done();
} );

gulp.task( 'watch', () => {
	gulp.watch( 'src/styles/**/*', gulp.series( 'styles' ) );
	gulp.watch( [ 'src/scripts/**/*' ], gulp.series( 'scripts' ) );
} );

gulp.task( 'build', gulp.parallel( 'styles', 'scripts', 'images', 'icons', 'fonts', 'vendor' ) );
gulp.task( 'default', gulp.series( 'build', 'watch' ) );
