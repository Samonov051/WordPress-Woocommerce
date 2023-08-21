/**
 * External dependencies
 */
import { Product } from '@woocommerce/data';
import { Button } from '@wordpress/components';
import { useEntityProp } from '@wordpress/core-data';
import { useDispatch, useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { MouseEvent } from 'react';

/**
 * Internal dependencies
 */
import { useValidations } from '../../../../contexts/validation-context';
import { WPError } from '../../../../utils/get-product-error-message';
import { PublishButtonProps } from '../../publish-button';

export function usePublish( {
	productStatus,
	disabled,
	onClick,
	onPublishSuccess,
	onPublishError,
	...props
}: PublishButtonProps & {
	onPublishSuccess?( product: Product ): void;
	onPublishError?( error: WPError ): void;
} ): Button.ButtonProps {
	const { isValidating, validate } = useValidations();

	const [ productId ] = useEntityProp< number >(
		'postType',
		'product',
		'id'
	);

	const { isSaving } = useSelect(
		( select ) => {
			const { isSavingEntityRecord } = select( 'core' );

			return {
				isSaving: isSavingEntityRecord< boolean >(
					'postType',
					'product',
					productId
				),
			};
		},
		[ productId ]
	);

	const isBusy = isSaving || isValidating;

	const isPublished = productStatus === 'publish';

	const { editEntityRecord, saveEditedEntityRecord } = useDispatch( 'core' );

	async function handleClick( event: MouseEvent< HTMLButtonElement > ) {
		if ( onClick ) {
			onClick( event );
		}

		try {
			await validate();

			// The publish button click not only change the status of the product
			// but also save all the pending changes. So even if the status is
			// publish it's possible to save the product too.
			if ( ! isPublished ) {
				await editEntityRecord( 'postType', 'product', productId, {
					status: 'publish',
				} );
			}

			const publishedProduct = await saveEditedEntityRecord< Product >(
				'postType',
				'product',
				productId,
				{
					throwOnError: true,
				}
			);

			if ( publishedProduct && onPublishSuccess ) {
				onPublishSuccess( publishedProduct );
			}
		} catch ( error ) {
			if ( onPublishError ) {
				let wpError = error as WPError;
				if ( ! wpError.code ) {
					wpError = {
						code: isPublished
							? 'product_publish_error'
							: 'product_create_error',
					} as WPError;
				}
				onPublishError( wpError );
			}
		}
	}

	return {
		children: isPublished
			? __( 'Update', 'woocommerce' )
			: __( 'Add', 'woocommerce' ),
		...props,
		isBusy,
		variant: 'primary',
		onClick: handleClick,
	};
}
