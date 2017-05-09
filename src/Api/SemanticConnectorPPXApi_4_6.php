<?php

namespace Drupal\semantic_connector\Api;
use Drupal\semantic_connector\SemanticConnectorWatchdog;

/**
 * Class SemanticConnectorPPXApi_4_6
 *
 * API Class for the version 4.6.
 */
class SemanticConnectorPPXApi_4_6 extends SemanticConnectorPPXApi {

  /**
   * This method checks if the PoolParty server exists and is running.
   *
   * @return array
   *   Associative array which following properties:
   *   - success (boolean): TRUE if a connection to the server can be
   *     established.
   *   - message (string): This property is optional, but if it exists it
   *     includes more details about why the connection could not be
   *     established.
   */
  public function available() {
    $is_available = NULL;
    \Drupal::moduleHandler()->alter('semantic_connector_ppx_available', $this, $is_available);

    if (is_null($is_available)) {
      $is_available = array(
        'success' => FALSE,
        'message' => '',
      );
      $resource_path = '/extractor/api/heartbeat';
      $result = json_decode($this->connection->get($resource_path));

      if (is_object($result) && property_exists($result, 'success')) {
        $is_available['success'] = $result->success;
        if (property_exists($result, 'message')) {
          $is_available['message'] = $result->message;
        }
      }
    }

    return $is_available;
  }

  /**
   * Get a list of available projects of a PoolParty server.
   *
   * @return array
   *   An array of projects found on the PoolParty available for the current
   *   PoolParty user.
   */
  public function getProjects() {
    // Offer the possibility to support a different value for this function.
    $projects = NULL;
    \Drupal::moduleHandler()->alter('semantic_connector_ppx_getProjects', $this, $projects);

    if (is_null($projects)) {
      $resource_path = '/extractor/api/projects';
      $result = $this->connection->get($resource_path);

      $projects = json_decode($result);
      if (is_object($projects) && property_exists($projects, 'projects')) {
        $projects = $projects->projects;
      }
    }

    $default_project = array(
      'label' => 'Default project',
      'uuid' => '',
      'defaultLanguage' => 'en',
      'languages' => array('en'),
    );

    if (is_array($projects)) {
      foreach ($projects as &$project) {
        $project = (object) (array_merge($default_project, (array) $project));
      }
    }
    else {
      $projects = array();
    }

    return $projects;
  }

  /**
   * Extract concepts from given data.
   *
   * @param object|string $data
   *   Can be either a string for normal text-extraction of a file-object for
   *   text extraction of the file content.
   * @param string $language
   *   The iso-code of the language of the data.
   * @param array $parameters
   *   Additional parameters to forward to the API (e.g., projectId).
   * @param string $data_type
   *   The type of the data. Can be one of the following values:
   *   - "text" for a text
   *   - "url" for a valid URL
   *   - "file" for a file object with a file ID
   *   - "file direct" for all other files without an ID
   *
   * @return object
   *   Object of concepts.
   */
  public function extractConcepts($data, $language, array $parameters = array(), $data_type = '') {
    // Offer the possibility to support a different value for this function.
    $concepts = NULL;

    $input = array(
      'data' => $data,
      'language' => $language,
      'parameters' => $parameters,
      'data type' => $data_type,
    );
    \Drupal::moduleHandler()->alter('semantic_connector_ppx_extractConcepts', $this, $concepts, $input);

    $result = NULL;
    if (is_null($concepts)) {
      $resource_path = '/extractor/api/extract';
      if (empty($data_type)) {
        $data_type = $this->getTypeOfData($data);
      }

      switch ($data_type) {
        // Extract concepts from a given text.
        case 'text':
          $post_parameters = array_merge(array(
            'text' => $data,
            'language' => $language,
          ), $parameters);
          $result = $this->connection->post($resource_path, array(
            'data' => $post_parameters,
          ));
          break;

        // Extract concepts from a given URL.
        case 'url':
          $post_parameters = array_merge(array(
            'url' => $data,
            'language' => $language,
          ), $parameters);
          $result = $this->connection->post($resource_path, array(
            'data' => $post_parameters
          ));
          break;

        // Extract concepts from a given file uploaded via file field.
        case 'file':
          // Check if the file is in the public folder
          // and the sOnr webMining server can read it.
          if ($wrapper = \Drupal::service('stream_wrapper_manager')->getViaUri('public://')) {
            $public_path = $wrapper->realpath();
            $file_path = \Drupal::service('file_system')->realpath($data->getFileUri());
            if (strpos($file_path, $public_path) !== FALSE) {
              $post_parameters = array_merge(array(
                'file' => '@' . $file_path,
                'language' => $language,
              ), $parameters);
              $result = $this->connection->post($resource_path, array(
                'data' => $post_parameters,
                'headers' => array('Content-Type' => 'multipart/form-data'),
              ));
            }
          }
          break;

        // Extract concepts from a given file
        case 'file direct':
          $post_parameters = array_merge(array(
            'file' => '@' . $data->file_path,
            'language' => $language,
          ), $parameters);
          $result = $this->connection->post($resource_path, array(
            'data' => $post_parameters,
            'headers' => array('Content-Type' => 'multipart/form-data'),
          ));
          break;

        default:
          SemanticConnectorWatchdog::message('PPX API', 'The type of the data to extract concepts is not supported.');
          break;
      }

      $concepts = json_decode($result);
    }

    // Files have additional information we don't need --> remove it.
    if (is_object($concepts) && property_exists($concepts, 'document')) {
      $concepts = $concepts->document;
    }
    if (is_object($concepts) && property_exists($concepts, 'text')) {
      $concepts = $concepts->text;
    }

    return $concepts;
  }

  /**
   * Get a list of of concepts / free terms matching a string.
   *
   * @param string $string
   *   The string to search matching concepts / freeterms for.
   * @param string $language
   *   The iso-code of the text's language.
   * @param string $project_id
   *   The ID of the PoolParty project to use.
   * @param array $parameters
   *   Additional parameters to forward to the API (e.g., projectId).
   *
   * @return array
   *   An array of objects (every object can be an object or a freeterm,
   *   detectable by the tid-property).
   */
  public function suggest($string, $language, $project_id, array $parameters = array()) {
    $suggestion = NULL;

    $input = array(
      'string' => $string,
      'language' => $language,
      'project_id' => $project_id,
      'parameters' => $parameters,
    );
    // Offer the possibility to support a different value for this function.
    \Drupal::moduleHandler()->alter('semantic_connector_ppx_suggest', $this, $suggestion, $input);

    if (is_null($suggestion)) {
      $resource_path = '/extractor/api/suggest';
      $post_parameters = array_merge(array(
        'searchString' => $string,
        'language' => $language,
        'projectId' => $project_id,
      ), $parameters);

      $result = $this->connection->post($resource_path, array(
        'data' => $post_parameters,
      ));

      $suggestion = json_decode($result);
    }

    if (is_object($suggestion) && property_exists($suggestion, 'suggestedConcepts') && is_array($suggestion->suggestedConcepts)) {
      return $suggestion->suggestedConcepts;
    }

    return array();
  }
}