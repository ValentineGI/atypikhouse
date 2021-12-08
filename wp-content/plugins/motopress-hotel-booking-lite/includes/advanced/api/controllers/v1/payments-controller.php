<?php
/**
 * @package MPHB\Advanced\Api
 * @since 4.1.0
 */

namespace MPHB\Advanced\Api\Controllers\V1;

use MPHB\Advanced\Api\Controllers\AbstractRestObjectController;
use MPHB\Advanced\Api\Data\PaymentData;
use WP_REST_Request;

class PaymentsController extends AbstractRestObjectController {


	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'mphb/v1';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'payments';

	/**
	 * Post type.
	 *
	 * @var string
	 */
	protected $post_type = 'mphb_payment';

	/**
	 * Prepare links for the request.
	 *
	 * @param  PaymentData  $bookingData  Booking data object.
	 * @param  WP_REST_Request  $request  Request object.
	 *
	 * @return array Links for the given post.
	 */
	protected function prepare_links( $paymentData, $request ){
		$links = parent::prepare_links( $paymentData, $request );

		if ( $paymentData->booking_id ) {
			$links['booking_id'] = array(
				"href" => rest_url( sprintf( '/%s/%s/%d', $this->namespace, 'bookings', $paymentData->booking_id ) )
			);
		}

		return $links;
	}
}