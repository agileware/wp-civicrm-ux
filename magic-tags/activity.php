<?php

use Civi\API\Exception\UnauthorizedException;

class Civicrm_Ux_Cf_Magic_Tag_Activity extends Abstract_Civicrm_Ux_Cf_Magic_Tag {

	private $field, $data_type;

	private static $activity_id = NULL;

	function __construct( $manager, $field = 'id', $data_type = 'String' ) {
		parent::__construct( $manager );

		$this->field     = $field;
		$this->data_type = $data_type;

		$activity_id = $_GET['activity_id'] ?? $_GET['aid'] ?? NULL;

		if ( $activity_id && ! self::$activity_id ) {
			self::$activity_id = CRM_Utils_Type::validate( $activity_id, 'Positive', FALSE );
		}
	}

	/**
	 * The tag name
	 *
	 * @return string
	 */
	function get_tag_name() {
		return 'activity:' . $this->field;
	}

	/**
	 * The callback function. Should return $value if no changes.
	 *
	 * @param string $value
	 *
	 * @return string
	 */
	function callback( $value ) {
		if ( ! self::$activity_id ) {
			return null;
		}

		if( $this->field == 'id' ) {
			return self::$activity_id;
		}

		try {
			if ( ! CRM_Core_Permission::check( 'view all activities' ) ) {
				throw new UnauthorizedException( 'Cannot view activities' );
			}
			$field_name = $this->field == 'activity_date' ? 'activity_date_time' : $this->field;

			$activities = Civi\Api4\Activity::get( FALSE )
			                                ->addSelect( $field_name )
			                                ->addWhere( 'id', '=', self::$activity_id );

			if ( ! CRM_Core_Permission::check( 'view all contacts' ) ) {
				if ( ! CRM_Core_Permission::check( 'view my contact' ) ) {
					throw new UnauthorizedException( 'Cannot view own contact details' );
				}
				$activities->addJoin( 'ActivityContact AS contact', 'INNER', [ 'contact.activity_id', '=', 'id' ] );
				$activities->addWhere( 'contact.id', '=', CRM_Core_Session::getLoggedInContactID() ?: 0);
			}

			$result = $activities->execute()->first()[ $field_name ] ?? NULL;

			if ( $this->field == 'activity_date' ) {
				$result = explode( ' ', $result )[0];
			}

			if ( is_array( $result ) ) {
				if ( $this->data_type === 'Boolean' ) {
					foreach ( $result as $i => $r ) {
						$result[ $i ] = $r ? 1 : 0;
					}
				}
				$result = implode( ",\x1E", $result );
			} elseif ( $this->data_type === 'Boolean' ) {
				$result = $result ? 1 : 0;
			}

			return $result;
		} catch ( API_Exception $e ) {
			return $e->getMessage();
		}
	}

	/**
	 * Create a list of instances.
	 */
	static function getInstances( $manager ) {
		if ( ! civicrm_initialize() ) {
			return [];
		}

		$instances = [];

		try {
			$fields = Civi\Api4\Activity::getFields( FALSE )
			                            ->addSelect( 'name', 'suffixes', 'data_type' )
			                            ->execute();

			foreach ( $fields as $field ) {
				$instances[] = new static( $manager, $field['name'], $field['data_type'] );

				foreach ( $field['suffixes'] ?? [] as $suffix ) {
					$instances[] = new static( $manager, $field['name'] . ':' . $suffix, 'String' );
				}

				if($field['name'] == 'activity_date_time') {
					$instances[] = new static( $manager, 'activity_date', $field['data_type'] );
				}
			}

		} catch ( API_Exception $e ) {
			// ...
		}

		return $instances;
	}

	static function getActivityId() {
		return self::$activity_id;
	}
}