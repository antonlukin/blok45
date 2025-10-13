const { __ } = wp.i18n;
const { registerPlugin } = wp.plugins;
const { PluginDocumentSettingPanel } = wp.editor;
const { TextControl } = wp.components;
const { useSelect, useDispatch } = wp.data;
const { createElement } = wp.element;

const sanitizeCoords = ( value ) => {
	if ( typeof value !== 'string' ) {
		return value;
	}

	const parts = value.split( ',' );

	if ( parts.length !== 2 ) {
		return value.trim();
	}

	const formattedParts = parts.map( ( part ) => {
		const numeric = parseFloat( part.trim() );

		if ( Number.isNaN( numeric ) ) {
			return part.trim();
		}

		const rounded = Math.round( numeric * 1e5 ) / 1e5;

		return rounded.toString();
	} );

	return formattedParts.join( ',' );
};

const Blok45CoordsPanel = ( { metaKey } ) => {
	const meta = useSelect( ( select ) =>
		select( 'core/editor' ).getEditedPostAttribute( 'meta' ),
	[]
	);

	const { editPost } = useDispatch( 'core/editor', [ meta ] );

	return createElement(
		PluginDocumentSettingPanel,
		{
			name: 'blok45-coords',
			title: __( 'GEO Coords', 'blok45' ),
		},
		createElement( TextControl, {
			value: sanitizeCoords( meta[ metaKey ] || '' ),
			onChange: ( value ) => {
				editPost( { meta: { [ metaKey ]: sanitizeCoords( value ) } } );
			},
			__next40pxDefaultSize: true,
			__nextHasNoMarginBottom: true,
		} )
	);
};

registerPlugin( 'blok45-coords', {
	render: () => createElement( Blok45CoordsPanel, { metaKey: 'blok45_coords' } ),
} );
