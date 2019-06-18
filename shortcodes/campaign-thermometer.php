<?php


/**
 * Class Agileware_Civicrm_Utilities_Shortcode_Campaign_Thermometer_Info
 */
class Agileware_Civicrm_Utilities_Shortcode_Campaign_Thermometer implements iAgileware_Civicrm_Utilities_Shortcode {

  /**
   * @var \Agileware_Civicrm_Utilities_Shortcode_Manager
   */
  private $manager;

  /**
   * @param \Agileware_Civicrm_Utilities_Shortcode_Manager $manager
   *
   * @return mixed
   */
  public function init_setup(Agileware_Civicrm_Utilities_Shortcode_Manager $manager) {
    $this->manager = $manager;
  }

  /**
   * @return string The name of shortcode
   */
  public function get_shortcode_name() {
    return 'campaign-thermometer';
  }

  /**
   * @param array $atts
   * @param null $content
   * @param string $tag
   *
   * @return mixed Should be the html output of the shortcode
   * @throws \Exception
   */
  public function shortcode_callback($atts = [], $content = NULL, $tag = '') {
    // normalize attribute keys, lowercase
    $atts = array_change_key_case((array) $atts, CASE_LOWER);

    // override default attributes with user attributes
    $mod_atts = shortcode_atts([
      'id' => '',
    ], $atts, $tag);
    if (empty($mod_atts['id'])) {
      return 'Please provide the campaign id.';
    }
    $id = $mod_atts['id'];

    $civi_param = [
      'sequential'           => 1,
      'return'               => ["name", "title", "end_date", "goal_revenue"],
      'id'                   => $id,
      'is_active'            => 1,
      'api.Contribution.get' => ['sequential'  => 1,
                                 'campaign_id' => "\$value.id",
      ],
    ];

    try {
      $result = civicrm_api3('Campaign', 'getsingle', $civi_param);
    } catch (CiviCRM_API3_Exception $e) {
      $result = [];
    }

    if (empty($result) || $result['is_error']) {
      return 'Campaign not found.';
    }

    $sum                   = (float) $this->sum_from_contributions($result['api.Contribution.get']['values']);
    $goal_amount           = (float) $result['goal_revenue'];
    $calculated_percentage = empty($result['goal_revenue']) ? 100 : round($sum / $goal_amount * 100);
    $percentage            = ($calculated_percentage <= 100 ? $calculated_percentage : '100') . '%';

    $output = '<div class="campaign-thermometer-wrap">' .
              '<div class="campaign-thermometer-info">' .
              '<div class="campaign-thermometer">' .
              '<div class="campaign-meter"><span style="width: ' . $percentage . '"></span></div>' .
              '</div>' .
              '</div>' .
              '</div>';

    return $output;
  }

  /**
   * Sum up all contributions
   *
   * @param array $contributions
   *
   * @return float
   */
  private function sum_from_contributions($contributions = []) {
    $sum = 0.0;

    foreach ($contributions as $data) {
      $sum += (float) $data['total_amount'];
    }

    return $sum;
  }
}