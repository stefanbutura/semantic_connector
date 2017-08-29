<?php

namespace Drupal\semantic_connector\Api;
use Drupal\Component\Serialization\Json;

/**
 * Class SemanticConnectorSonrApi_5_7
 *
 * API Class for the version 5.7.
 */
class SemanticConnectorSonrApi_5_7 extends SemanticConnectorSonrApi_5_6 {

  /**
   * Update an agent.
   *
   * @param int $agent_id
   *   The ID of the agent.
   * @param array $config
   *   array(
   *    'source'          => (string) 'EIP Water',
   *    'url'             => (string) 'http://eip-water.eu/rss.xml'
   *    'username'        => (string) 'admin',
   *    'periodMillis'    => (int) 3600000,
   *  )
   *
   * @return bool
   *   TRUE on success, FALSE on error.
   */
  public function updateAgent($agent_id, $config) {
    $config['privateContent'] = FALSE;
    $config['spaceKey'] = '';

    $resource_path = '/' . $this->graphSearchPath . '/api/agents/%id';

    $result = $this->connection->post($resource_path, array(
      'parameters' => array('%id' => $agent_id),
      'data' => Json::encode($config),
    ));

    return $result === FALSE ? FALSE : TRUE;
  }

  /**
   * Delete an agent.
   *
   * @param int $agent_id
   *   The ID of the agent.
   *
   * @return bool
   *   TRUE on success, FALSE on error.
   */
  public function deleteAgent($agent_id) {
    $resource_path = '/' . $this->graphSearchPath . '/api/agents/%id/delete';

    $result = $this->connection->post($resource_path, array(
      'parameters' => array('%id' => $agent_id),
      'data' => array(),
    ));

    return $result === FALSE ? FALSE : TRUE;
  }

  /**
   * Run an agent.
   *
   * @param int $agent_id
   *   The ID of the agent.
   *
   * @return bool
   *   TRUE on success, FALSE on error.
   */
  public function runAgent($agent_id) {
    $resource_path = '/' . $this->graphSearchPath . '/api/agents/runAgent';

    $result = $this->connection->post($resource_path, array(
      'data' => array('id' => $agent_id),
      'timeout' => 120,
    ));

    return $result  === FALSE ? FALSE : TRUE;
  }

  /**
   * Delete all indexed documents from an agent.
   *
   * @param string $source
   *   The name of the source.
   *
   * @return bool
   *   TRUE on success, FALSE on error.
   */
  public function deleteIndex($source) {
    $resource_path = '/' . $this->graphSearchPath . '/api/content/delete/all';

    $result = $this->connection->post($resource_path, array(
      'data' => array('source' => $source),
    ));

    return $result === FALSE ? FALSE : TRUE;
  }
}
