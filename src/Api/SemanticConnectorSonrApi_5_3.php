<?php

namespace Drupal\semantic_connector\Api;
use Drupal\Component\Serialization\Json;

/**
 * Class SemanticConnectorSonrApi_5_3
 *
 * API Class for the version 5.3
 */
class SemanticConnectorSonrApi_5_3 extends SemanticConnectorSonrApi_4_6 {

  /**
   * Adds a new custom search field for the suggestion call.
   *
   * @param string $label
   *   The label of the custom search field.
   * @param string $type
   *   The name of the custom search field, e.g. 'content_type'.
   *
   * @return boolean
   *   TRUE if field is added, FALSE instead.
   */
  public function addCustomSearchField($label, $type) {
    $resource_path = '/sonr-backend/api/config/search/add';
    $type = 'dyn_lit_' . str_replace('-', '_', $type);
    $post_parameters = array(
      'label' => $label,
      'name' => $type,
    );

    $result = $this->connection->post($resource_path, array(
      'data' => $post_parameters,
    ));

    if ($result !== FALSE) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Deletes a custom search field for the suggestion call.
   *
   * @param string $type
   *   The name of the custom search field.
   *   Must start with 'dyn_lit_', e.g. 'dyn_lit_content_type'.
   *
   * @return boolean
   *   TRUE if field is added, FALSE instead.
   */
  public function deleteCustomSearchField($type) {
    $resource_path = '/sonr-backend/api/config/search/delete';
    $type = 'dyn_lit_' . str_replace('-', '_', $type);
    $post_parameters = array(
      'name' => $type,
    );

    $result = $this->connection->post($resource_path, array(
      'data' => $post_parameters,
    ));

    if ($result !== FALSE) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Get concept suggestions from a given search string.
   *
   * @param string $search_string
   *   The string to get suggestions for
   * @param array $parameters
   *   array(
   *    'locale' => (string) 'en',  (default: 'en')
   *    'count'  => (int)    10,    (default:   10)
   *  )
   *
   * @return array
   *   Array of concepts
   *   array(
   *    'id'      => (string) URI of concept
   *    'label'   => (string) prefLabel of concept
   *    'context' => (string) prefLabel of the broader concept
   *    'field'   => (string) URI of conceptScheme
   *  )
   */
  public function suggest($search_string, $parameters = array()) {
    $resource_path = '/sonr-backend/api/suggest/multi';
    $get_parameters = array(
      'searchString' => $search_string,
      'locale' => isset($parameters['locale']) ? $parameters['locale'] : 'en',
      'count' => isset($parameters['count']) ? $parameters['count'] : 10,
      'format' => 'json',
    );

    $result = $this->connection->get($resource_path, array(
      'query' => $get_parameters,
    ));

    $concepts = Json::decode($result);

    if (!is_array($concepts)) {
      return FALSE;
    }

    return $concepts;
  }

}
