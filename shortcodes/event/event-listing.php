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
      $results = \Civi\Api4\Event::get(FALSE)
        ->addSelect('id', 'title', 'start_date', 'end_date', 'event_tz', 'event_type_id', 'is_online_registration', 'summary', 'registration_link_text')
        ->addWhere('start_date', '>=', 'today')
        ->addWhere('is_public', '=', TRUE)
        ->addWhere('is_active', '=', TRUE)
        ->addOrderBy('start_date', 'ASC')
        ->setCheckPermissions(FALSE);

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

    $local_tz = new DateTimeZone(CRM_Core_Config::singleton()->userSystem->getTimeZoneString());

    $start_date = new DateTime($event['start_date'], $local_tz);
    $end_date = new DateTime($event['end_date'], $local_tz);

    if(!empty($event['event_tz'])) {
        $time_zone = new DateTimeZone($event['event_tz']);
        $start_date->setTimeZone($time_zone);
        $end_date->setTimeZone($time_zone);
    }

    $start_date_text = CRM_Utils_Date::customFormat($start_date->format('Y-m-d H:i:s'), $config->dateformatDatetime);
    $end_date_text = '';

    // Check if end date has been set
    if (!empty($event['end_date']) && ($start_date != $end_date)) {
      // Check if end date is the same day as start date
      if ($end_date->format('j F Y') == $start_date->format('j F Y')) {
        $end_date_text = ' to ' . CRM_Utils_Date::customFormat($end_date->format('Y-m-d H:i:s'), $config->dateformatTime);
      }
      else {
        $end_date_text = ' to ' . CRM_Utils_Date::customFormat($end_date->format('Y-m-d H:i:s'), $config->dateformatDatetime);
      }
    }

    $end_date_text .= ' ' . (!empty($event['event_tz']) ? $event['event_tz'] : CRM_Core_Config::singleton()->userSystem->getTimeZoneString());

    $output = '<div class="civicrm-event-listing-item"><h3 class="civicrm-event-item-header">' . $this->get_event_info_link_html($event['id'], $event['title']) . '</h3>' .
      '<p class="civicrm-event-item-date">' . $start_date_text . $end_date_text . '</p>' .
      '<p class="civicrm-event-item-summary">' . $event['summary'] . '</p>';

    // If on-line registration is enabled display the register now link
    if ($event['is_online_registration']) {
      $output .= '<p class="civicrm-event-item-register">' . $this->get_event_register_link_html($event['id'], $event['registration_link_text']) . '</p>';
    }
    $output .= '</div>';

    return $output;
  }

  private function get_event_register_link_html(string $event_id, string $text = 'Register now') {
    // URLs must be absolute as this shortcode may be used by the au.com.agileware.evaluatewpshortcode extension
    $url = CRM_Utils_System::url('civicrm/event/register', ['id' => $event_id],TRUE,NULL,TRUE,TRUE);
    return '<a target=_blank href="' . $url . '">' . $text . '</a>';
  }

  private function get_event_info_link_html(string $event_id, string $text = 'More information') {
    // URLs must be absolute as this shortcode may be used by the au.com.agileware.evaluatewpshortcode extension
    $url = CRM_Utils_System::url('civicrm/event/info', ['id' => $event_id],TRUE,NULL,TRUE,TRUE);
    return '<a target=_blank href="' . $url . '">' . $text . '</a>';
  }

}
