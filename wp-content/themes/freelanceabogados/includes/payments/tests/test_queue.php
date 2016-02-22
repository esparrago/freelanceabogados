<?php
/**
 * Test queue
 *
 * @package Components\Payments\Tests
 */

require_once APP_TESTS_LIB . '/testcase.php';

/**
 * @group payments
 */
class APP_Queue_Test extends APP_UnitTestCase {

	public function test_register(){

		$queue = new APP_Order_Processing();

		$queue->schedule_process();
		$this->assertNotEquals( 0, wp_next_scheduled( 'app_order_processing' ) );

		$draft = new APP_Draft_Order();
		$draft->complete();
		$new_order = APP_Draft_Order::upgrade( $draft );

		$child_order = APP_Order_Factory::duplicate( $new_order );
		$child_order->set_gateway( 'mock-gateway' );
		wp_update_post( array(
			'ID' => $child_order->get_id(),
			'post_status' => APPTHEMES_ORDER_COMPLETED,
			'post_parent' => $new_order->get_id()
		) );
		$child_order->schedule_payment( date('Y-m-d H:i:s', strtotime( '-1 day' ) ) );

		$items_processed = $queue->process();
		$this->assertEquals( 1, $items_processed );

		$order = appthemes_get_order( $child_order->get_id(), true );
		$this->assertEquals( APPTHEMES_ORDER_ACTIVATED, $order->get_status() );

	}


}


