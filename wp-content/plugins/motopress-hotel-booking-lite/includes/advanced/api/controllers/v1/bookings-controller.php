<?php
/**
 * @package MPHB\Advanced\Api
 * @since 4.1.0
 */

namespace MPHB\Advanced\Api\Controllers\V1;

use MPHB\Advanced\Api\Controllers\AbstractRestObjectController;
use MPHB\Advanced\Api\Data\BookingData;
use WP_REST_Request;

class BookingsController extends AbstractRestObjectController {


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
	protected $rest_base = 'bookings';

	/**
	 * Post type.
	 *
	 * @var string
	 */
	protected $post_type = 'mphb_booking';

	/**
	 * Prepare links for the request.
	 *
	 * @param  BookingData  $bookingData  Booking data object.
	 * @param  WP_REST_Request  $request  Request object.
	 *
	 * @return array Links for the given post.
	 */
	protected function prepare_links( $bookingData, $request ){
		$links = parent::prepare_links( $bookingData, $request );

		$payments = $bookingData->payments;
		if ( count( $payments ) ) {
			$links['payments'] = array_map( function ( $id ){
				return array(
					"href"       => rest_url( sprintf( '/%s/%s/%d', $this->namespace, 'payments', $id ) ),
					"embeddable" => true,
				);
			}, wp_list_pluck( $payments, 'id' ) );
		}

		if ( $bookingData->status !== \MPHB\PostTypes\BookingCPT\Statuses::STATUS_CONFIRMED && ! $bookingData->payments ) {
			$links['payments'] = array(
				"href" => rest_url( sprintf( '/%s/%s', $this->namespace, 'payments' ) ),
			);
		}

		$reservedAccommodations = $bookingData->reserved_accommodations;
		if ( count( $reservedAccommodations ) ) {
			$links['accommodation'] = array_map( function( $id ){
				return array(
					"href" => rest_url( sprintf( '/%s/%s/%d', $this->namespace, 'accommodations', $id ) ),
					"embeddable" => true,
				);
			}, wp_list_pluck( $reservedAccommodations, 'accommodation' ) );

			$links['accommodation_type'] = array_map( function( $id ){
				return array(
					"href" => rest_url( sprintf( '/%s/%s/%d', $this->namespace, 'accommodation_types', $id ) ),
					"embeddable" => true,
				);
			}, wp_list_pluck( $reservedAccommodations, 'accommodation_type' ) );

			$links['rate'] = array_map( function( $id ){
				return array(
					"href" => rest_url( sprintf( '/%s/%s/%d', $this->namespace, 'rates', $id ) ),
					"embeddable" => true,
				);
			}, array_filter( wp_list_pluck( $reservedAccommodations, 'rate' ) ) );


			$links['services'] = array_map( function( $id ){
				return array(
					"href" => rest_url( sprintf( '/%s/%s/%s/%d', $this->namespace, 'accommodation_types', 'services', $id ) ),
					"embeddable" => true,
				);
			}, $this->getServiceIds( $reservedAccommodations ) );
		}

		return $links;
	}

	private function getServiceIds( $reservedAccommodationsResponse ){
		$services = array();
		foreach ( $reservedAccommodationsResponse as $reservedAccommodation ) {
			if ( isset( $reservedAccommodation['services'] ) ) {
				$services = array_merge( $services, wp_list_pluck( $reservedAccommodation['services'], 'id' ) );
			}
		}

		return array_unique( $services );
	}
}