<?php

namespace Drupal\semantic_connector\Api;
use Drupal\Component\Serialization\Json;

/**
 * Class SemanticConnectorPPTApi_6_0
 *
 * API Class for the version 6.0
 */
class SemanticConnectorPPTApi_6_0 extends SemanticConnectorPPTApi_5_6 {
  /**
   * @inheritdoc
   */
  public function createProject($title, $language_default = 'en', $user_groups = array('Public'), $settings = array()) {
    $resource_path = '/PoolParty/api/projects/create';
    $post_parameters = $settings;
    $post_parameters += array(
      'title' => $title,
      'defaultLanguage' => $language_default,
      'userGroups' => $user_groups,
    );

    $result = $this->connection->post($resource_path, array(
      'data' => Json::encode($post_parameters),
    ));

    $result = Json::decode($result);
    if (isset($result->id)) {
      return $result->id;
    }
    else {
      return FALSE;
    }
  }
}
