<?php
/**
 * Tests for Variations API.
 *
 * @package WooCommerce\Tests\API
 * @since 3.5.0
 */

/**
 * Product_Variations_API class.
 */
class Product_Variations_API extends WC_REST_Unit_Test_Case {

	/**
	 * Setup our test server, endpoints, and user info.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->endpoint = new WC_REST_Product_Variations_Controller();
		$this->user     = $this->factory->user->create(
			array(
				'role' => 'administrator',
			)
		);
	}

	/**
	 * Test route registration.
	 *
	 * @since 3.5.0
	 */
	public function test_register_routes() {
		$routes = $this->server->get_routes();
		$this->assertArrayHasKey( '/wc/v3/products/(?P<product_id>[\d]+)/variations', $routes );
		$this->assertArrayHasKey( '/wc/v3/products/(?P<product_id>[\d]+)/variations/(?P<id>[\d]+)', $routes );
		$this->assertArrayHasKey( '/wc/v3/products/(?P<product_id>[\d]+)/variations/batch', $routes );
	}

	/**
	 * Test getting variations.
	 *
	 * @since 3.5.0
	 */
	public function test_get_variations() {
		wp_set_current_user( $this->user );
		$product    = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper::create_variation_product();
		$response   = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/products/' . $product->get_id() . '/variations' ) );
		$variations = $response->get_data();
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 2, count( $variations ) );
		$this->assertEquals( 'DUMMY SKU VARIABLE LARGE', $variations[0]['sku'] );
		$this->assertEquals( 'size', $variations[0]['attributes'][0]['name'] );
	}

	/**
	 * Test getting variations with an orderby clause.
	 *
	 * @since 3.9.0
	 */
	public function test_get_variations_with_orderby() {
		wp_set_current_user( $this->user );
		$product    = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper::create_variation_product();
		$request = new WP_REST_Request( 'GET', '/wc/v3/products/' . $product->get_id() . '/variations' );
		$request->set_query_params( array( 'orderby' => 'menu_order' ) );
		$response   = $this->server->dispatch( $request );
		$variations = $response->get_data();
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 2, count( $variations ) );
		$this->assertEquals( 'DUMMY SKU VARIABLE SMALL', $variations[0]['sku'] );
		$this->assertEquals( 'size', $variations[0]['attributes'][0]['name'] );
	}

	/**
	 * Test getting variations without permission.
	 *
	 * @since 3.5.0
	 */
	public function test_get_variations_without_permission() {
		wp_set_current_user( 0 );
		$product  = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper::create_variation_product();
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/products/' . $product->get_id() . '/variations' ) );
		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test getting a single variation.
	 *
	 * @since 3.5.0
	 */
	public function test_get_variation() {
		wp_set_current_user( $this->user );
		$product      = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper::create_variation_product();
		$children     = $product->get_children();
		$variation_id = $children[0];

		$response  = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/products/' . $product->get_id() . '/variations/' . $variation_id ) );
		$variation = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( $variation_id, $variation['id'] );
		$this->assertEquals( 'size', $variation['attributes'][0]['name'] );
	}

	/**
	 * Test getting single variation without permission.
	 *
	 * @since 3.5.0
	 */
	public function test_get_variation_without_permission() {
		wp_set_current_user( 0 );
		$product      = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper::create_variation_product();
		$children     = $product->get_children();
		$variation_id = $children[0];
		$response     = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/products/' . $product->get_id() . '/variations/' . $variation_id ) );
		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test deleting a single variation.
	 *
	 * @since 3.5.0
	 */
	public function test_delete_variation() {
		wp_set_current_user( $this->user );
		$product      = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper::create_variation_product();
		$children     = $product->get_children();
		$variation_id = $children[0];

		$request = new WP_REST_Request( 'DELETE', '/wc/v3/products/' . $product->get_id() . '/variations/' . $variation_id );
		$request->set_param( 'force', true );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );

		$response   = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/products/' . $product->get_id() . '/variations' ) );
		$variations = $response->get_data();
		$this->assertEquals( 1, count( $variations ) );
	}

	/**
	 * Test deleting a single variation without permission.
	 *
	 * @since 3.5.0
	 */
	public function test_delete_variation_without_permission() {
		wp_set_current_user( 0 );
		$product      = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper::create_variation_product();
		$children     = $product->get_children();
		$variation_id = $children[0];

		$request = new WP_REST_Request( 'DELETE', '/wc/v3/products/' . $product->get_id() . '/variations/' . $variation_id );
		$request->set_param( 'force', true );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test deleting a single variation with an invalid ID.
	 *
	 * @since 3.5.0
	 */
	public function test_delete_variation_with_invalid_id() {
		wp_set_current_user( 0 );
		$product = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper::create_variation_product();
		$request = new WP_REST_Request( 'DELETE', '/wc/v3/products/' . $product->get_id() . '/variations/0' );
		$request->set_param( 'force', true );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 404, $response->get_status() );
	}

	/**
	 * Test editing a single variation.
	 *
	 * @since 3.5.0
	 */
	public function test_update_variation() {
		wp_set_current_user( $this->user );
		$product      = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper::create_variation_product();
		$children     = $product->get_children();
		$variation_id = $children[0];

		$response  = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/products/' . $product->get_id() . '/variations/' . $variation_id ) );
		$variation = $response->get_data();

		$this->assertEquals( 'DUMMY SKU VARIABLE SMALL', $variation['sku'] );
		$this->assertEquals( 10, $variation['regular_price'] );
		$this->assertEmpty( $variation['sale_price'] );
		$this->assertEquals( 'small', $variation['attributes'][0]['option'] );

		$request = new WP_REST_Request( 'PUT', '/wc/v3/products/' . $product->get_id() . '/variations/' . $variation_id );
		$request->set_body_params(
			array(
				'sku'         => 'FIXED-\'SKU',
				'sale_price'  => '8',
				'description' => 'O_O',
				'image'       => array(
					'position' => 0,
					'src'      => 'http://cldup.com/Dr1Bczxq4q.png',
					'alt'      => 'test upload image',
				),
				'attributes'  => array(
					array(
						'name'   => 'pa_size',
						'option' => 'medium',
					),
				),
			)
		);
		$response  = $this->server->dispatch( $request );
		$variation = $response->get_data();

		$this->assertTrue( isset( $variation['description'] ), print_r( $variation, true ) );
		$this->assertStringContainsString( 'O_O', $variation['description'], print_r( $variation, true ) );
		$this->assertEquals( '8', $variation['price'], print_r( $variation, true ) );
		$this->assertEquals( '8', $variation['sale_price'], print_r( $variation, true ) );
		$this->assertEquals( '10', $variation['regular_price'], print_r( $variation, true ) );
		$this->assertEquals( 'FIXED-\'SKU', $variation['sku'], print_r( $variation, true ) );
		$this->assertEquals( 'medium', $variation['attributes'][0]['option'], print_r( $variation, true ) );
		$this->assertStringContainsString( 'Dr1Bczxq4q', $variation['image']['src'], print_r( $variation, true ) );
		$this->assertStringContainsString( 'test upload image', $variation['image']['alt'], print_r( $variation, true ) );

		wp_delete_attachment( $variation['image']['id'], true );
	}

	/**
	 * Test updating a single variation without permission.
	 *
	 * @since 3.5.0
	 */
	public function test_update_variation_without_permission() {
		wp_set_current_user( 0 );
		$product      = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper::create_variation_product();
		$children     = $product->get_children();
		$variation_id = $children[0];

		$request = new WP_REST_Request( 'PUT', '/wc/v3/products/' . $product->get_id() . '/variations/' . $variation_id );
		$request->set_body_params(
			array(
				'sku' => 'FIXED-SKU-NO-PERMISSION',
			)
		);
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test updating a single variation with an invalid ID.
	 *
	 * @since 3.5.0
	 */
	public function test_update_variation_with_invalid_id() {
		wp_set_current_user( $this->user );
		$product = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper::create_variation_product();
		$request = new WP_REST_Request( 'PUT', '/wc/v3/products/' . $product->get_id() . '/variations/0' );
		$request->set_body_params(
			array(
				'sku' => 'FIXED-SKU-NO-PERMISSION',
			)
		);
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 400, $response->get_status() );
	}

	/**
	 * Test creating a single variation.
	 *
	 * @since 3.5.0
	 */
	public function test_create_variation() {
		wp_set_current_user( $this->user );
		$product = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper::create_variation_product();

		$response   = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/products/' . $product->get_id() . '/variations' ) );
		$variations = $response->get_data();
		$this->assertEquals( 2, count( $variations ) );

		$request = new WP_REST_Request( 'POST', '/wc/v3/products/' . $product->get_id() . '/variations' );
		$request->set_body_params(
			array(
				'sku'           => 'DUMMY SKU VARIABLE MEDIUM',
				'regular_price' => '12',
				'description'   => 'A medium size.',
				'attributes'    => array(
					array(
						'name'   => 'pa_size',
						'option' => 'medium',
					),
				),
			)
		);
		$response  = $this->server->dispatch( $request );
		$variation = $response->get_data();

		$this->assertStringContainsString( 'A medium size.', $variation['description'] );
		$this->assertEquals( '12', $variation['price'] );
		$this->assertEquals( '12', $variation['regular_price'] );
		$this->assertTrue( $variation['purchasable'] );
		$this->assertEquals( 'DUMMY SKU VARIABLE MEDIUM', $variation['sku'] );
		$this->assertEquals( 'medium', $variation['attributes'][0]['option'] );

		$response   = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/products/' . $product->get_id() . '/variations' ) );
		$variations = $response->get_data();
		$this->assertEquals( 3, count( $variations ) );
	}

	/**
	 * Test creating a single variation without permission.
	 *
	 * @since 3.5.0
	 */
	public function test_create_variation_without_permission() {
		wp_set_current_user( 0 );
		$product = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper::create_variation_product();

		$request = new WP_REST_Request( 'POST', '/wc/v3/products/' . $product->get_id() . '/variations' );
		$request->set_body_params(
			array(
				'sku'           => 'DUMMY SKU VARIABLE MEDIUM',
				'regular_price' => '12',
				'description'   => 'A medium size.',
				'attributes'    => array(
					array(
						'name'   => 'pa_size',
						'option' => 'medium',
					),
				),
			)
		);
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test batch managing product variations.
	 *
	 * @since 3.5.0
	 */
	public function test_product_variations_batch() {
		wp_set_current_user( $this->user );
		$product  = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper::create_variation_product();
		$children = $product->get_children();
		$request  = new WP_REST_Request( 'POST', '/wc/v3/products/' . $product->get_id() . '/variations/batch' );
		$request->set_body_params(
			array(
				'update' => array(
					array(
						'id'          => $children[0],
						'description' => 'Updated description.',
						'image'       => array(
							'position' => 0,
							'src'      => 'http://cldup.com/Dr1Bczxq4q.png',
							'alt'      => 'test upload image',
						),
					),
				),
				'delete' => array(
					$children[1],
				),
				'create' => array(
					array(
						'sku'           => 'DUMMY SKU VARIABLE MEDIUM',
						'regular_price' => '12',
						'description'   => 'A medium size.',
						'attributes'    => array(
							array(
								'name'   => 'pa_size',
								'option' => 'medium',
							),
						),
					),
				),
			)
		);
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertStringContainsString( 'Updated description.', $data['update'][0]['description'] );
		$this->assertEquals( 'DUMMY SKU VARIABLE MEDIUM', $data['create'][0]['sku'] );
		$this->assertEquals( 'medium', $data['create'][0]['attributes'][0]['option'] );
		$this->assertEquals( $children[1], $data['delete'][0]['id'] );

		$request  = new WP_REST_Request( 'GET', '/wc/v3/products/' . $product->get_id() . '/variations' );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 2, count( $data ) );

		wp_delete_attachment( $data[1]['image']['id'], true );
	}

	/**
	 * Test variation schema.
	 *
	 * @since 3.5.0
	 */
	public function test_variation_schema() {
		wp_set_current_user( $this->user );
		$product    = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper::create_simple_product();
		$request    = new WP_REST_Request( 'OPTIONS', '/wc/v3/products/' . $product->get_id() . '/variations' );
		$response   = $this->server->dispatch( $request );
		$data       = $response->get_data();
		$properties = $data['schema']['properties'];

		$this->assertEquals( 38, count( $properties ) );
		$this->assertArrayHasKey( 'id', $properties );
		$this->assertArrayHasKey( 'date_created', $properties );
		$this->assertArrayHasKey( 'date_modified', $properties );
		$this->assertArrayHasKey( 'description', $properties );
		$this->assertArrayHasKey( 'permalink', $properties );
		$this->assertArrayHasKey( 'sku', $properties );
		$this->assertArrayHasKey( 'price', $properties );
		$this->assertArrayHasKey( 'regular_price', $properties );
		$this->assertArrayHasKey( 'sale_price', $properties );
		$this->assertArrayHasKey( 'date_on_sale_from', $properties );
		$this->assertArrayHasKey( 'date_on_sale_to', $properties );
		$this->assertArrayHasKey( 'on_sale', $properties );
		$this->assertArrayHasKey( 'purchasable', $properties );
		$this->assertArrayHasKey( 'virtual', $properties );
		$this->assertArrayHasKey( 'downloadable', $properties );
		$this->assertArrayHasKey( 'downloads', $properties );
		$this->assertArrayHasKey( 'download_limit', $properties );
		$this->assertArrayHasKey( 'download_expiry', $properties );
		$this->assertArrayHasKey( 'tax_status', $properties );
		$this->assertArrayHasKey( 'tax_class', $properties );
		$this->assertArrayHasKey( 'manage_stock', $properties );
		$this->assertArrayHasKey( 'stock_quantity', $properties );
		$this->assertArrayHasKey( 'stock_status', $properties );
		$this->assertArrayHasKey( 'backorders', $properties );
		$this->assertArrayHasKey( 'backorders_allowed', $properties );
		$this->assertArrayHasKey( 'backordered', $properties );
		$this->assertArrayHasKey( 'low_stock_amount', $properties );
		$this->assertArrayHasKey( 'weight', $properties );
		$this->assertArrayHasKey( 'dimensions', $properties );
		$this->assertArrayHasKey( 'shipping_class', $properties );
		$this->assertArrayHasKey( 'shipping_class_id', $properties );
		$this->assertArrayHasKey( 'image', $properties );
		$this->assertArrayHasKey( 'attributes', $properties );
		$this->assertArrayHasKey( 'menu_order', $properties );
		$this->assertArrayHasKey( 'meta_data', $properties );
	}

	/**
	 * Test updating a variation stock.
	 *
	 * @since 3.5.0
	 */
	public function test_update_variation_manage_stock() {
		wp_set_current_user( $this->user );

		$product = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper::create_variation_product();
		$product->set_manage_stock( false );
		$product->save();

		$children     = $product->get_children();
		$variation_id = $children[0];

		// Set stock to true.
		$request = new WP_REST_Request( 'PUT', '/wc/v3/products/' . $product->get_id() . '/variations/' . $variation_id );
		$request->set_body_params(
			array(
				'manage_stock' => true,
			)
		);

		$response  = $this->server->dispatch( $request );
		$variation = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( true, $variation['manage_stock'] );

		// Set stock to false.
		$request = new WP_REST_Request( 'PUT', '/wc/v3/products/' . $product->get_id() . '/variations/' . $variation_id );
		$request->set_body_params(
			array(
				'manage_stock' => false,
			)
		);

		$response  = $this->server->dispatch( $request );
		$variation = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( false, $variation['manage_stock'] );

		// Set stock to false but parent is managing stock.
		$product->set_manage_stock( true );
		$product->save();
		$request = new WP_REST_Request( 'PUT', '/wc/v3/products/' . $product->get_id() . '/variations/' . $variation_id );
		$request->set_body_params(
			array(
				'manage_stock' => false,
			)
		);

		$response  = $this->server->dispatch( $request );
		$variation = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 'parent', $variation['manage_stock'] );
	}

	/**
	 * Test updating a variation stock.
	 *
	 * @since 8.1.0
	 */
	public function test_generate_new_variations() {
		wp_set_current_user( $this->user );

		$product = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper::create_variation_product();
		$color_attribute_data = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper::create_attribute( 'color', array( 'red', 'blue', 'yellow' ) );
		$color_attribute      = new WC_Product_Attribute();
		$color_attribute->set_id( $color_attribute_data['attribute_id'] );
		$color_attribute->set_name( $color_attribute_data['attribute_taxonomy'] );
		$color_attribute->set_options( $color_attribute_data['term_ids'] );
		$color_attribute->set_position( 1 );
		$color_attribute->set_visible( true );
		$color_attribute->set_variation( true );
		$attributes   = $product->get_attributes();
		$attributes[] = $color_attribute;
		$product->set_attributes( $attributes );
		$product->save();

		// Set stock to true.
		$request = new WP_REST_Request( 'POST', '/wc/v3/products/' . $product->get_id() . '/variations/generate' );

		$response  = $this->server->dispatch( $request );
		$variation = $response->get_data();
		$product   = wc_get_product( $product->get_id() );
		$children  = $product->get_children();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 6, $variation['count'] );
		// Generated 2 new ones.
		$this->assertEquals( 8, count( $children ) );
	}

	/**
	 * Test updating a variation stock.
	 *
	 * @since 3.5.0
	 */
	public function test_generate_new_variations_with_delete_set_to_true() {
		wp_set_current_user( $this->user );

		$product = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper::create_variation_product();
		$color_attribute_data = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper::create_attribute( 'color', array( 'red', 'blue', 'yellow' ) );
		$color_attribute      = new WC_Product_Attribute();
		$color_attribute->set_id( $color_attribute_data['attribute_id'] );
		$color_attribute->set_name( $color_attribute_data['attribute_taxonomy'] );
		$color_attribute->set_options( $color_attribute_data['term_ids'] );
		$color_attribute->set_position( 1 );
		$color_attribute->set_visible( true );
		$color_attribute->set_variation( true );
		$attributes   = $product->get_attributes();
		$attributes[] = $color_attribute;
		$product->set_attributes( $attributes );
		$product->save();

		// Set stock to true.
		$request = new WP_REST_Request( 'POST', '/wc/v3/products/' . $product->get_id() . '/variations/generate' );
		$request->set_body_params( array( 'delete' => true ) );

		$response  = $this->server->dispatch( $request );
		$variation = $response->get_data();
		$product   = wc_get_product( $product->get_id() );
		$children  = $product->get_children();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 6, $variation['count'] );
		$this->assertEquals( 2, $variation['deleted_count'] );
		// Generated 2 new ones.
		$this->assertEquals( 6, count( $children ) );
	}

	/**
	 * Test updating a variation stock.
	 *
	 * @since 3.5.0
	 */
	public function test_delete_unmatched_variations_when_removing_term() {
		wp_set_current_user( $this->user );

		$product = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper::create_variation_product();
		$color_attribute = new WC_Product_Attribute();
		$color_attribute->set_name( 'color' );
		$color_attribute->set_visible( true );
		$color_attribute->set_variation( true );
		$color_attribute->set_options( array( 'red', 'green', 'blue' ) );
		$color_attribute->set_position( 1 );
		$attributes   = $product->get_attributes();
		$attributes[] = $color_attribute;
		$product->set_attributes( $attributes );
		$product->save();

		// Set stock to true.
		$request = new WP_REST_Request( 'POST', '/wc/v3/products/' . $product->get_id() . '/variations/generate' );
		$request->set_body_params( array( 'delete' => true ) );
		$response  = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );

		$product_attributes = get_post_meta( $product->get_id() , '_product_attributes' );

		// Removing blue term from product.
		$product    = wc_get_product( $product->get_id() );
		$attributes = $product->get_attributes();
		$color_attribute->set_options( array( 'red', 'green' ) );
		$attributes['color'] = $color_attribute;
		$product->set_attributes( $attributes );
		$product->save();

		$request = new WP_REST_Request( 'POST', '/wc/v3/products/' . $product->get_id() . '/variations/generate' );
		$request->set_body_params( array( 'delete' => true ) );
		$response  = $this->server->dispatch( $request );

		$variation = $response->get_data();
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 0, $variation['count'] );
		$this->assertEquals( 2, $variation['deleted_count'] );

		$product   = wc_get_product( $product->get_id() );
		// Removed two.
		$this->assertEquals( 4, count( $product->get_children() ) );
	}

	/**
	 * Test updating a variation stock.
	 *
	 * @since 3.5.0
	 */
	public function test_delete_unmatched_variations_when_removing_attribute() {
		wp_set_current_user( $this->user );

		$product = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper::create_variation_product();
		$color_attribute = new WC_Product_Attribute();
		$color_attribute->set_name( 'color' );
		$color_attribute->set_visible( true );
		$color_attribute->set_variation( true );
		$color_attribute->set_options( array( 'red', 'yellow', 'green' ) );
		$color_attribute->set_position( 1 );
		$attributes   = $product->get_attributes();
		$attributes[] = $color_attribute;
		$product->set_attributes( $attributes );
		$product->save();

		// Set stock to true.
		$request = new WP_REST_Request( 'POST', '/wc/v3/products/' . $product->get_id() . '/variations/generate' );
		$request->set_body_params( array( 'delete' => true ) );
		$response  = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );

		$product_attributes = get_post_meta( $product->get_id() , '_product_attributes' );

		// Removing color attribute from product.
		$product    = wc_get_product( $product->get_id() );
		$attributes = $product->get_attributes();
		unset( $attributes['color'] );
		$product->set_attributes( $attributes );
		$product->save();

		$request = new WP_REST_Request( 'POST', '/wc/v3/products/' . $product->get_id() . '/variations/generate' );
		$request->set_body_params( array( 'delete' => true ) );
		$response  = $this->server->dispatch( $request );

		$variation = $response->get_data();
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 0, $variation['count'] );
		$this->assertEquals( 4, $variation['deleted_count'] );

		$product   = wc_get_product( $product->get_id() );
		// Removed four.
		$this->assertEquals( 2, count( $product->get_children() ) );
	}
}
