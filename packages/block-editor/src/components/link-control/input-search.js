/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { IconButton } from '@wordpress/components';
import { ENTER } from '@wordpress/keycodes';

/**
 * Internal dependencies
 */
import {
	URLInput,
} from '../';

const LinkControlInputSearch = ( {
	value,
	onChange,
	renderSuggestions,
	fetchSuggestions,
	onReset,
	onKeyDown,
	onKeyPress,
} ) => {
	return (
		// eslint-disable-next-line jsx-a11y/no-noninteractive-element-interactions
		<form
			onSubmit={ ( event ) => event.preventDefault() }
			onKeyDown={ ( event ) => {
				if ( event.keyCode === ENTER ) {
					// onSelect( suggestion )( event );
				}
				onKeyDown( event );
			} }
			onKeyPress={ onKeyPress }
		>
			<URLInput
				className="block-editor-link-control__search-input"
				value={ value }
				onChange={ onChange }
				placeholder={ __( 'Search or type url' ) }
				renderSuggestions={ renderSuggestions }
				fetchLinkSuggestions={ fetchSuggestions }
				handleURLSuggestions={ true }
			/>

			<IconButton
				disabled={ ! value.length }
				type="reset"
				label={ __( 'Reset' ) }
				icon="no-alt"
				className="block-editor-link-control__search-reset"
				onClick={ onReset }
			/>

		</form>
	);
};

export default LinkControlInputSearch;
