const { __ } = wp.i18n;
const { registerPlugin } = wp.plugins;
const { PluginDocumentSettingPanel } = wp.editor;
const { TextControl } = wp.components;
const { useSelect, useDispatch } = wp.data;
const { createElement } = wp.element;

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
			value: meta[ metaKey ],
			onChange: ( value ) => {
				editPost( { meta: { [ metaKey ]: value } } );
			},
			__next40pxDefaultSize: true,
			__nextHasNoMarginBottom: true,
		} )
	);
};

registerPlugin( 'blok45-coords', {
	render: () => createElement( Blok45CoordsPanel, { metaKey: 'blok45_coords' } ),
} );
