<?php

namespace Drupal\semantic_connector\Entity;
use Drupal\semantic_connector\Api\SemanticConnectorPPTApi;
use Drupal\semantic_connector\Api\SemanticConnectorPPXApi;
use Drupal\semantic_connector\Api\SemanticConnectorSonrApi;
use Drupal\semantic_connector\SemanticConnector;

/**
 * @ConfigEntityType(
 *   id ="pp_server_connection",
 *   label = @Translation("PoolParty Server connection"),
 *   handlers = {
 *     "list_builder" = "Drupal\semantic_connector\ConnectionListBuilder",
 *     "form" = {
 *       "default" = "Drupal\semantic_connector\Form\SemanticConnectorConnectionForm",
 *       "add" = "Drupal\semantic_connector\Form\SemanticConnectorConnectionForm",
 *       "edit" = "Drupal\semantic_connector\Form\SemanticConnectorConnectionForm",
 *       "delete" = "Drupal\semantic_connector\Form\SemanticConnectorConnectionDeleteForm"
 *     }
 *   },
 *   config_prefix = "pp_server_connection",
 *   admin_permission = "administer semantic connector",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "title"
 *   },
 *   links = {
 *     "delete-form" = "/admin/config/semantic-drupal/semantic-connector/connections/pp-server/{pp_server_connection}/delete",
 *     "edit-form" = "/admin/config/semantic-drupal/semantic-connector/connections/pp-server/{pp_server_connection}",
 *     "collection" = "/admin/config/semantic-drupal/semantic-connector/",
 *   },
 *   config_export = {
 *     "title",
 *     "id",
 *     "type",
 *     "url",
 *     "credentials",
 *     "config",
 *   }
 * )
 */
class SemanticConnectorPPServerConnection extends SemanticConnectorConnection {
  /**
   * The constructor of the SemanticConnectorPPServerConnection class.
   *
   * {@inheritdoc|}
   */
  public function __construct(array $values, $entity_type) {
    parent::__construct($values, $entity_type);
    $this->type = 'pp_server';
  }

  /**
   * {@inheritdoc|}
   */
  public function available() {
    $availability = $this->getApi('PPX')->available();
    return $availability['success'];
  }

  /**
   * Adds PoolParty projects and SPARQL endpoints before saving it.
   */
  public function save() {
    // Update the PoolParty version.
    $ppt_api = $this->getApi('PPT');
    $this->config['version'] = $ppt_api->getVersion();

    // Add the projects to the config before saving the PoolParty server.
    $projects = $ppt_api->getProjects();
    foreach ($projects as &$project) {
      $project = (array) $project;
      unset($project);
    }

    $sparql_endpoints_to_remove = array();
    $new_project_urls = array();
    foreach ($projects as $project) {
      if (isset($project['sparql_endpoint_url'])) {
        $new_project_urls[] = $project['sparql_endpoint_url'];
      }
    }

    if (isset($this->config['projects'])) {

      foreach ($this->config['projects'] as $project) {
        if (isset($project['sparql_endpoint_url']) && !in_array($project['sparql_endpoint_url'], $new_project_urls)) {
          $sparql_endpoints_to_remove[] = $project['sparql_endpoint_url'];
        }
      }
    }
    $this->config['projects'] = $projects;

    // Add a SPARQL-endpoint connection for every project.
    foreach ($this->config['projects'] as $project) {
      if (isset($project['sparql_endpoint_url'])) {
        SemanticConnector::createConnection('sparql_endpoint', $project['sparql_endpoint_url'], $project['title'], $this->credentials, array());
      }
    }

    // Remove SPARQL-endpoints, that do not exist anymore.
    if (!empty($sparql_endpoints_to_remove)) {
      $connections_query = \Drupal::entityQuery('sparql_endpoint');
      $delete_connection_ids = $connections_query->condition('url', $sparql_endpoints_to_remove, 'IN')->execute();

      SemanticConnector::deleteConnections('sparql_endpoint', $delete_connection_ids);
    }

    // Update the sOnr configuration.
    $sonr_config = array();
    $sonr_api = $this->getApi('sonr');
    // Get the version of the sOnr web service.
    $sonr_version = $sonr_api->getVersion();

    // Get the appropriate API for the correct version.
    $this->config['graphsearch_configuration'] = array(
      'version' => $sonr_version,
    );
    $sonr_api = $this->getApi('sonr');

    // If a sOnr webMining server exists, create a config.
    if (!empty($sonr_version)) {
      // Get the server-side configuration and save it also to the database.
      $sonr_config = $sonr_api->getConfig();
      $sonr_config['version'] = $sonr_version;
    }
    $this->config['graphsearch_configuration'] = $sonr_config;

    parent::save();
  }

  /**
   * Returns the API to a specific type.
   *
   * @param string $api_type
   *   The desired API type. Possible values are:
   *   - "PPX": The PoolParty Extraction service API
   *   - "PPT": The PoolParty Thesaurus API
   *   - "sonr": The sOnr webMining server API
   *
   * @return SemanticConnectorSonrApi|SemanticConnectorPPTApi|SemanticConnectorPPXApi
   *   The specific API.
   */
  public function getApi($api_type = 'PPX') {
    if (in_array($api_type, array('PPX', 'PPT', 'sonr'))) {
      $api_version_info = $this->getVersionInfo($api_type);
      $credentials = !empty($this->credentials['username']) ? $this->credentials['username'] . ':' . $this->credentials['password'] : '';

      // PPX or PPT API.
      if ($api_type != 'sonr') {
        return new $api_version_info['api_class_name']($this->url, $credentials);
      }
      // sOnr API.
      else {
        /** @var SemanticConnectorSonrApi $sonr_api */
        $sonr_api = new $api_version_info['api_class_name']($this->url, $credentials);
        $sonr_api->setId($this->id);
        return $sonr_api;
      }
    }
    else {
      return NULL;
    }
  }

  /**
   * Get all information about the version of a API available on the PP server.
   *
   * @param string $api_type
   *   The desired API type. Possible values are:
   *   - "PPX": The PoolParty Extraction service API
   *   - "PPT": The PoolParty Thesaurus API
   *   - "sonr": The sOnr webMining server API
   *
   * @return array
   *   An associative array containing following keys:
   *   - "installed_version": The current version of the API service
   *   - "latest_version": The latest API implementation for the service
   *   - "api_class_name": The class name of the appropriate API class to use
   */
  public function getVersionInfo($api_type) {
    // List of finished API version implementations. Only add versions to this
    // list when they are fully functional. The order of the versions is not
    // important.
    $available_api_versions = array(
      'pp_server' => array('4.6', '5.3', '5.6'),
      'sonr' => array('4.6', '5.3', '5.6'),
    );

    $version_infos = array(
      'installed_version' => '',
      'latest_version' => '',
      'api_class_name' => '',
    );

    // PPX or PPT API.
    if ($api_type != 'sonr') {
      $api_versions = $available_api_versions['pp_server'];
      usort($api_versions, 'version_compare');
      if (!isset($this->config['version']) || empty($this->config['version'])) {
        $this->config['version'] = $api_versions[0];
      }
      $version_infos['installed_version'] = $this->config['version'];
      $class_prefix = '\Drupal\semantic_connector\Api\SemanticConnector' . $api_type . 'Api_';
    }
    // sOnr API.
    else {
      $api_versions = $available_api_versions['sonr'];
      usort($api_versions, 'version_compare');
      $class_prefix = '\Drupal\semantic_connector\Api\SemanticConnectorSonrApi_';
      if (!isset($this->config['graphsearch_configuration']) || !isset($this->config['graphsearch_configuration']['version']) || empty($this->config['graphsearch_configuration']['version'])) {
        // Check with the lowest API version, which supports getVersion for all
        // API versions.
        $version_check_class_name = $class_prefix . str_replace('.', '_', $api_versions[0]);
        $credentials = !empty($this->credentials['username']) ? $this->credentials['username'] . ':' . $this->credentials['password'] : '';

        /** @var SemanticConnectorSonrApi $sonr_api */
        $sonr_api = new $version_check_class_name($this->url, $credentials);
        $this->config['graphsearch_configuration']['version'] = $sonr_api->getVersion();
      }
      $version_infos['installed_version'] = $this->config['graphsearch_configuration']['version'];
    }

    // To get the newest compatible API version, we have to reverse the array
    // and check every single version.
    $api_versions = array_reverse($api_versions);
    $version_infos['latest_version'] = $api_versions[0];
    foreach ($api_versions as $current_api_version) {
      if (version_compare($version_infos['installed_version'], $current_api_version, '>=')) {
        $class_version = $current_api_version;
        break;
      }
    }
    if (!isset($class_version)) {
      $class_version = $api_versions[count($api_versions) - 1];
    }
    $version_infos['api_class_name'] = $class_prefix . str_replace('.', '_', $class_version);

    return $version_infos;
  }

  /**
   * {@inheritdoc|}
   */
  public function getDefaultConfig() {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public static function exist($id) {
    $entity_count = \Drupal::entityQuery('pp_server_connection')
      ->condition('id', $id)
      ->count()
      ->execute();
    return (bool) $entity_count;
  }
}