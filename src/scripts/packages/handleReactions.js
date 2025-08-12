/**
 * Handle reactions
 *
 * @since 1.0
 */

/**
 * Add listener to reaction block
 *
 * @param {HTMLElement} button
 * @param {Array}       counters
 */
function createButton( button, counters ) {
	const reaction = button.dataset.reaction;

	if ( ! reaction ) {
		return undefined;
	}

	const number = document.createElement( 'span' );
	button.appendChild( number );

	if ( parseInt( counters[ reaction ] || 0 ) ) {
		number.textContent = counters[ reaction ];
	}

	button.addEventListener( 'click', ( e ) => {
		e.preventDefault();

		number.textContent = parseInt( number.textContent || 0 ) + 1;

		( async () => {
			updateCounter( reaction );
		} )();
	} );
}

/**
 * Get post ID if exists
 */
function getPostID() {
	const article = document.querySelector( '.post' );

	if ( ! article ) {
		return null;
	}

	// Get article id
	const [ post ] = article.id?.match( /\d+/ );

	if ( ! post ) {
		return null;
	}

	return post;
}

/**
 * Update counter for reaction
 *
 * @param {string} reaction
 */
async function updateCounter( reaction ) {
	const post = getPostID();

	if ( ! post ) {
		return undefined;
	}

	try {
		const data = new FormData();

		data.append( 'post', post );
		data.append( 'reaction', reaction );

		await fetch( `/wp-json/blok45-reactions/v1/entry?post=${ post }`, {
			method: 'POST',
			body: data,
		} );
	} catch ( err ) {
		console.error( err ); // eslint-disable-line
	}
}

/**
 * Get reaction counters for this page
 */
async function getCounters() {
	const post = getPostID();

	if ( ! post ) {
		return {};
	}

	try {
		const response = await fetch( `/wp-json/blok45-reactions/v1/entry?post=${ post }` );

		// Get list of reactions with values
		const answer = await response.json();

		if ( response.status === 200 ) {
			return answer;
		}
	} catch ( err ) {
		console.error( err ); // eslint-disable-line
	}

	return {};
}

/**
 * Default function
 *
 * @param {NodeList} blocks Reactions block elements
 */
async function handleRequests( blocks ) {
	const counters = await getCounters();

	blocks.forEach( ( block ) => {
		block.querySelectorAll( '.reactions__button' ).forEach( ( button ) => {
			createButton( button, counters );
		} );
	} );
}

export default handleRequests;
