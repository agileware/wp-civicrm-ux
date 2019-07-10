<?php

class Civicrm_Ux_Campaign_Utils {
	/**
	 * Get the remaining day from now
	 *
	 * @param string $end_date
	 *
	 * @return int
	 * @throws \Exception
	 */
	static public function get_remaining_day( $end_date ) {
		if ( empty( $end_date ) ) {
			return 0;
		}

		$end = DateTime::createFromFormat( 'Y-m-d H:i:s', $end_date );
		$now = new DateTime( 'now' );

		if ( $now > $end ) {
			return 0;
		}

		$interval = $now->diff( $end );

		return $interval->d;
	}

	/**
	 * Sum up all contributions
	 *
	 * @param array $contributions
	 *
	 * @return float
	 */
	static public function sum_from_contributions( $contributions = [] ) {
		$sum = 0.0;

		foreach ( $contributions as $data ) {
			$sum += (float) $data['total_amount'];
		}

		return $sum;
	}
}