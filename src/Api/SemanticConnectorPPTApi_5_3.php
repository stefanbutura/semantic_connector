<?php

namespace Drupal\semantic_connector\Api;

/**
 * Class SemanticConnectorPPTApi_5_3
 *
 * API Class for the version 5.3
 */
class SemanticConnectorPPTApi_5_3 extends SemanticConnectorPPTApi_4_6 {

  /**
   * Get all history items of a PoolParty project.
   *
   * @param $project_id
   *   The ID of the project to get history items for.
   * @param int $from_time
   *   Optional; Only history items after this time will be included.
   * @param int $to_time
   *   Optional; Only history items before this time will be included.
   *
   * @return array
   *   An array of history items.
   */
  public function getHistory($project_id, $from_time = NULL, $to_time = NULL) {
    $resource_path = '/PoolParty/api/history/' . $project_id;
    $get_parameters = array();

    if (!is_null($from_time)) {
      $get_parameters['fromTime'] = date('d.m.Y\'T\'H:i:s)', $from_time);
    }
    if (!is_null($to_time)) {
      $get_parameters['toTime'] = date('d.m.Y\'T\'H:i:s)', $to_time);
    }

    $result = $this->connection->get($resource_path, array(
      'query' => $get_parameters,
    ));
    $history_items = json_decode($result);

    return $history_items;
  }
}