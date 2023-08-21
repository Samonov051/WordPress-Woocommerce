const { test, expect } = require( '@playwright/test' );
const wcApi = require( '@woocommerce/woocommerce-rest-api' ).default;

let productId, couponId, orderId;

const productPrice = '9.99';
const productName = 'Apply Coupon Product';
const couponCode = '5off';
const couponAmount = '5';
const discountedPrice = ( productPrice - couponAmount ).toString();

test.describe( 'WooCommerce Orders > Apply Coupon', () => {
	test.use( { storageState: process.env.ADMINSTATE } );

	test.beforeAll( async ( { baseURL } ) => {
		const api = new wcApi( {
			url: baseURL,
			consumerKey: process.env.CONSUMER_KEY,
			consumerSecret: process.env.CONSUMER_SECRET,
			version: 'wc/v3',
		} );
		// create a simple product
		await api
			.post( 'products', {
				name: productName,
				type: 'simple',
				regular_price: productPrice,
			} )
			.then( ( response ) => {
				productId = response.data.id;
			} );
		// create a $5 off coupon
		await api
			.post( 'coupons', {
				code: couponCode,
				discount_type: 'fixed_product',
				amount: couponAmount,
			} )
			.then( ( response ) => {
				couponId = response.data.id;
			} );
		// create order
		await api
			.post( 'orders', {
				line_items: [
					{
						product_id: productId,
						quantity: 1,
					},
				],
				coupon_lines: [
					{
						code: couponCode,
					},
				],
			} )
			.then( ( response ) => {
				orderId = response.data.id;
			} );
	} );

	test.afterAll( async ( { baseURL } ) => {
		// cleans up product, coupon and order after run
		const api = new wcApi( {
			url: baseURL,
			consumerKey: process.env.CONSUMER_KEY,
			consumerSecret: process.env.CONSUMER_SECRET,
			version: 'wc/v3',
		} );
		await api.delete( `products/${ productId }`, { force: true } );
		await api.delete( `coupons/${ couponId }`, { force: true } );
		await api.delete( `orders/${ orderId }`, { force: true } );
	} );

	test( 'can apply a coupon', async ( { page } ) => {
		await page.goto( 'wp-admin/post-new.php?post_type=shop_order' );

		// open modal for adding line items
		await page.locator( 'button.add-line-item' ).click();
		await page.locator( 'button.add-order-item' ).click();

		// search for product to add
		await page.locator( 'text=Search for a product…' ).click();
		await page
			.locator( '.select2-search--dropdown' )
			.getByRole( 'combobox' )
			.type( productName );
		await page
			.locator(
				'li.select2-results__option.select2-results__option--highlighted'
			)
			.click();

		await page.locator( 'button#btn-ok' ).click();

		// apply coupon
		page.on( 'dialog', ( dialog ) => dialog.accept( couponCode ) );
		await page.locator( 'button.add-coupon' ).click();

		await expect(
			page.locator( '.wc_coupon_list li', { hasText: couponCode } )
		).toBeVisible();
		await expect(
			page.locator( '.wc-order-totals td.label >> nth=1' )
		).toContainText( 'Coupon(s)' );
		await expect(
			page.locator( '.wc-order-totals td.label >> nth=2' )
		).toContainText( 'Order Total' );
		await expect(
			page.locator( '.wc-order-totals td.total >> nth=1' )
		).toContainText( couponAmount );
		await expect(
			page.locator( '.wc-order-totals td.total >> nth=2' )
		).toContainText( discountedPrice );
	} );

	test( 'can remove a coupon', async ( { page } ) => {
		await page.goto( `/wp-admin/post.php?post=${ orderId }&action=edit` );
		// assert that there is a coupon on the order
		await expect(
			page.locator( '.wc_coupon_list li', { hasText: couponCode } )
		).toBeVisible();
		await expect(
			page.locator( '.wc-order-totals td.label >> nth=1' )
		).toContainText( 'Coupon(s)' );
		await expect(
			page.locator( '.wc-order-totals td.label >> nth=2' )
		).toContainText( 'Order Total' );
		await expect(
			page.locator( '.wc-order-totals td.total >> nth=1' )
		).toContainText( couponAmount );
		await expect(
			page.locator( '.wc-order-totals td.total >> nth=2' )
		).toContainText( discountedPrice );
		// remove the coupon
		await page.locator( 'a.remove-coupon' ).dispatchEvent( 'click' ); // have to use dispatchEvent because nothing visible to click on

		// make sure the coupon was removed
		await expect(
			page.locator( '.wc_coupon_list li', { hasText: couponCode } )
		).not.toBeVisible();
		await expect(
			page.locator( '.wc-order-totals td.label >> nth=1' )
		).toContainText( 'Order Total' );
		await expect(
			page.locator( '.wc-order-totals td.total >> nth=1' )
		).toContainText( productPrice );
	} );
} );
