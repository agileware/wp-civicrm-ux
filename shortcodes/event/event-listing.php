<?php


class Civicrm_Ux_Shortcode_Event_Listing extends Abstract_Civicrm_Ux_Shortcode {

  /**
   * @return string The name of shortcode
   */
  public function get_shortcode_name() {
    return 'ux_event_listing';
  }

  /**
   * @param array $atts
   * @param null $content
   * @param string $tag
   *
   * @return mixed Should be the html output of the shortcode
   */
  public function shortcode_callback($atts = [], $content = NULL, $tag = '') {
    $config = CRM_Core_Config::singleton();

    // Normalize attribute keys, lowercase
    $atts = array_change_key_case((array) $atts, CASE_LOWER);

    // Override default attributes with user attributes
    $mod_atts = shortcode_atts([
      'type' => '',
      'days' => '',
    ], $atts, $tag);

    try {
      $results = \Civi\Api4\Event::get()
        ->addWhere('start_date', '>=', 'today')
        ->addWhere('is_public', '=', TRUE)
        ->addWhere('is_active', '=', TRUE)
        ->setCheckPermissions(FALSE)
        ->addOrderBy('start_date', 'ASC');

      if (!empty($mod_atts['days'])) {
        $results->addWhere('start_date', '<=', 'today + ' . $mod_atts['days'] . ' days');
      }
      if (!empty($mod_atts['type'])) {
        $results->addWhere('event_type_id:name', 'IN', explode(',', $mod_atts['type']));
      }
      $results = $results->execute();
    } catch (API_Exception $e) {
      $errorMessage = $e->getMessage();
      CRM_Core_Error::debug_var('wp-civicrm-ux::ux_event_listing', $errorMessage);
      return '';
    }

    $current_time_group = '';
    $output = '<div class="civicrm-event-listing">';
    foreach ($results as $event) {
      $date = CRM_Utils_Date::customFormat($event['start_date'], $config->dateformatPartial);

      if (empty($current_time_group) || $current_time_group !== $date) {
        $current_time_group = $date;
        $output .= '<h2>' . $current_time_group . '</h2>';
      }
      $output .= $this->get_event_item_html($event);
    }

    $output .= '</div>';

    return $output;
  }

  private function get_event_item_html(array $event) {
    $config = CRM_Core_Config::singleton();

    $start_date = new DateTime($event['start_date']);
    $end_date = new DateTime($event['end_date']);
    $start_date_text = CRM_Utils_Date::customFormat($event['start_date'], $config->dateformatDatetime);
    $end_date_text = '';

    // Check if end date has been set
    if (!empty($event['end_date'])) {
      // Check if end date is the same day as start date
      if ($end_date->format('j F Y') == $start_date->format('j F Y')) {
        $end_date_text = ' to ' . CRM_Utils_Date::customFormat($event['end_date'], $config->dateformatTime);
      }
      else {
        $end_date_text = ' to ' . CRM_Utils_Date::customFormat($event['end_date'], $config->dateformatDatetime);
      }

      $end_date_text .= (!empty($event['event_tz']) ? ' ' . $event['event_tz'] : '');
    }
    else {
      $start_date_text .= (!empty($event['event_tz']) ? ' ' . $event['event_tz'] : '');
    }

    $output = '<div class="civicrm-event-listing-item"><h3 class="civicrm-event-item-header">' . $this->get_event_info_link_html($event['id'], $event['title']) . '</h3><p class="civicrm-event-item-date">' . $start_date_text . $end_date_text . '</p>';

    // If on-line registration is enabled display the register now link
    if ($event['is_online_registration']) {
      $output .= '<p class="civicrm-event-item-register">' . $this->get_event_register_link_html($event['id'], 'Register now') . '</p>';
    }
    $output .= '</div>';

    return $output;
  }

  private function get_event_register_link_html(string $event_id) {
    $url = CRM_Utils_System::url('civicrm/event/register', ['id' => $event_id]);
    return '<a target=_blank href="' . $url . '">Register now</a>';
  }

  private function get_event_info_link_html(string $event_id, string $text = 'More information') {
    $url = CRM_Utils_System::url('civicrm/event/info', ['id' => $event_id]);
    return '<a target=_blank href="' . $url . '">' . $text . '</a>';
  }

}
