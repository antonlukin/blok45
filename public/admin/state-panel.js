( () => {
	const { __ } = wp.i18n;
	const { registerPlugin } = wp.plugins;
	const { PluginPostStatusInfo } = wp.editPost;
	const { CheckboxControl } = wp.components;
	const { useSelect, useDispatch } = wp.data;
	const { createElement } = wp.element;

	const Blok45GraffitiStateToggle = ( { metaKey } ) => {
		const meta = useSelect( ( select ) =>
			select( 'core/editor' ).getEditedPostAttribute( 'meta' ),
		[]
		);
		const postType = useSelect( ( select ) => select( 'core/editor' ).getCurrentPostType(), [] );

		const { editPost } = useDispatch( 'core/editor', [ meta ] );

		if ( postType !== 'post' || ! meta ) {
			return null;
		}

		const isArchived = Boolean( meta[ metaKey ] );

		return createElement(
			PluginPostStatusInfo,
			{
				className: 'blok45-state',
			},
			createElement( CheckboxControl, {
				label: __( 'Graffiti removed', 'blok45' ),
				checked: isArchived,
				onChange: ( nextValue ) => {
					editPost( { meta: { [ metaKey ]: nextValue } } );
				},
			} )
		);
	};

	registerPlugin( 'blok45-state', {
		render: () => createElement( Blok45GraffitiStateToggle, { metaKey: 'blok45_graffiti_archived' } ),
	} );
} )();
