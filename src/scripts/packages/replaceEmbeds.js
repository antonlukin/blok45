/**
 * Get all html elements with embed class and replace them with iframe links
 *
 * @since 1.0
 */

/**
 * Create bounce loader
 *
 * @param {HTMLElement} embed Embed element.
 */
function createLoader( embed ) {
	const loader = document.createElement( 'div' );
	loader.classList.add( 'embed__loader' );
	embed.appendChild( loader );

	const bounce = document.createElement( 'span' );
	bounce.classList.add( 'embed__loader-bounce' );
	loader.appendChild( bounce );

	return loader;
}

/**
 * Add mute param for mobile devices.
 *
 * @param {HTMLElement} embed Embed element.
 */
function getEmbedUrl( embed ) {
	const url = embed.dataset.embed;

	if ( 'ontouchstart' in window && url.includes( 'autoplay=1' ) ) {
		return url + '&mute=1';
	}

	return url;
}

/**
 * Create iframe using data-embed attribute
 *
 * @param {HTMLElement} embed Embed element.
 */
function createIframe( embed ) {
	const iframe = document.createElement( 'iframe' );
	const loader = createLoader( embed );

	iframe.setAttribute( 'allow', 'autoplay' );
	iframe.setAttribute( 'frameborder', '0' );

	const url = getEmbedUrl( embed );
	iframe.setAttribute( 'src', url );

	iframe.addEventListener( 'load', function() {
		loader.parentNode.removeChild( loader );
	} );

	return iframe;
}

/**
 * Start embed on click
 *
 * @param {HTMLElement} embed
 */
function startEmbed( embed ) {
	if ( ! embed.dataset.embed ) {
		return;
	}

	embed.addEventListener( 'click', function( e ) {
		e.preventDefault();

		// Remove all embed child nodes
		while ( embed.firstChild ) {
			embed.removeChild( embed.firstChild );
		}

		embed.appendChild( createIframe( embed ) );
	} );
}

/**
 * Default function
 *
 * @param {NodeList} embeds
 */
function replaceEmbeds( embeds ) {
	embeds.forEach( startEmbed );
}

export default replaceEmbeds;
