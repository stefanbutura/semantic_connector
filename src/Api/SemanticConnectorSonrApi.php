<?php

namespace Drupal\semantic_connector\Api;
use Drupal\semantic_connector\SemanticConnectorCurlConnection;

/**
 * Abstract Class SemanticConnectorSonrApi
 *
 * API Class for the sOnr webMining server.
 */
abstract class SemanticConnectorSonrApi {

  const ATTR_SOURCE = 'dyn_lit_source';
  const ATTR_AUTHOR = 'dyn_lit_author';
  const ATTR_SENTIMENT = 'dyn_flt_sentiment';
  const ATTR_ALL_CONCEPTS = 'dyn_uri_all_concepts';
  const ATTR_CONTENT_TYPE = 'dyn_lit_content_type';
  const ATTR_CONTENT = 'dyn_txt_content';
  const ATTR_REGIONS = 'dyn_uri_Regions';
  const ATTR_SPACE = 'dyn_lit_space';

  protected $id;
  protected $customAttributes;
  protected $connection;
  protected $graphSearchPath = 'GraphSearch';

  /**
   * The constructor of the SonrApi-class.
   *
   * @param string $endpoint
   *   URL of the endpoint of the PoolParty-server.
   * @param string $credentials
   *   Username and password if required (format: "username:password").
   * @param string $custom_graphsearch_path
   *   The customizable path to the GraphSearch instance
   */
  public function __construct($endpoint, $credentials = '', $custom_graphsearch_path = '') {
    $this->connection = new SemanticConnectorCurlConnection($endpoint, $credentials);
    $this->customAttributes = array(
      self::ATTR_SOURCE,
      self::ATTR_AUTHOR,
    );
    if (!empty($custom_graphsearch_path)) {
      $this->graphSearchPath = $custom_graphsearch_path;
    }
  }

  /**
   * Returns the cUrl connection object.
   *
   * @return SemanticConnectorCurlConnection
   *   The cUrl connection object.
   */
  public function getConnection() {
    return $this->connection;
  }

  /**
   * Sets the sonr configuration id.
   *
   * @param int $id
   *   The id of the sonr configuration.
   */
  public function setId($id) {
    $this->id = $id;
  }
  /**
   * Returns the ID.
   *
   * @return int
   *   The id of the sonr configuration.
   */
  public function getId() {
    return $this->id;
  }

  /**
   * Adds a specific attribute to the search property "customAttributes".
   *
   * @param string $attribute
   *   The name of the attribute
   */
  public function addCustomAttribute($attribute) {
    if (!in_array($attribute, $this->customAttributes)) {
      $this->customAttributes[] = $attribute;
    }
  }

  /**
   * Sets/replaces all the attributes for the search propery "customAttributes".
   *
   * @param array $attributes
   *   The array with the specific attributes
   */
  public function setCustomAttributes($attributes) {
    $this->customAttributes = $attributes;
  }

  /**
   * Returns all the attributes from the search property "customAttributes".
   *
   * @return array
   *   An array of custom attributes.
   */
  public function getCustomAttributes() {
    return $this->customAttributes;
  }

  /**
   * This method checks if the sOnr service exists and is running.
   *
   * @return bool
   *   TRUE if the service is available, FALSE if not
   */
  public function available() {
    return FALSE;
  }

  /**
   * Get the version of the installed sOnr web service.
   *
   * Important: This method supports sOnr as well as GraphSearch version and
   * therefore must NOT be overwritten by one of the sub-classes!
   *
   * @return string
   *   The sOnr version formatted like '4.1.6'
   */
  public function getVersion() {
    $version = '';

    // Disable error logging.
    $this->connection->setErrorLogging(FALSE);

    // Try to find the version of sOnr inside the HTML code of the website.
    $resource_path = '/sonr-backend/';
    $sonr_website_html = $this->connection->get($resource_path, array(
      'headers' => array('Accept' => 'text/html'),
    ));

    // sOnr was found, check the HTML code for its version.
    if ($sonr_website_html !== FALSE) {
      $version_search_pattern = '/<b>Version:<\/b> +([0-9.]+)/';
      if (preg_match($version_search_pattern, $sonr_website_html, $matches)) {
        $version = $matches[1];
      }
    }
    // If no traditional sOnr connection could be established, try checking for
    // GraphSearch instead.
    else {
      // Remove error messages from the failed GET request.
      drupal_get_messages('error');

      $resource_path = '/' . $this->graphSearchPath . '/admin';
      $sonr_website_html = $this->connection->get($resource_path, array(
        'headers' => array('Accept' => 'text/html'),
      ));
      $version_search_pattern = '/<a id="ppgs-brand" class="navbar-brand" href="\/GraphSearch\/">GraphSearch +([0-9.]+)/';
      if (preg_match($version_search_pattern, $sonr_website_html, $matches)) {
        $version = $matches[1];
      }
    }

    // Enable error logging again.
    $this->connection->setErrorLogging(TRUE);

    return $version;
  }

  /**
   * This method gets the server configuration of the sOnr webMining server.
   *
   * @return array
   *   pp-server => The URL to the PoolParty server used for the extraction
   *   project => The PoolParty project used for the extraction
   *   language => The configured language of the content.
   */
  public function getConfig() {
    return FALSE;
  }

  /**
   * This method gets the field configuration of the sOnr webMining server.
   *
   * @return array
   *   searchFields -> Search field setup
   *   fieldNameMap -> Search field map
   *   fieldTypeMap -> Search field type map
   *   uriFields -> Search uri fields.
   */
  public function getFieldConfig() {
    return FALSE;
  }

  /**
   * This method searches in the sOnr index.
   *
   * @param array $facets
   *   A list of facet objects that should be used for faceting the
   *   search. [optional]
   *
   * @param array $filters
   *   A list of filter object parameters that define the query. [optional]
   *    array(
   *      object(
   *        'field'    => (string)  facedID   | 'date-to' | 'date-from',
   *        'value'    => (int)     conceptID | timestamp | timestamp,
   *        'optional' => (boolean) TRUE, (default: TRUE)
   *      ),
   *      ...
   *    )
   *
   * @param array $parameters
   *   A list of key value pairs [optional]
   *    array(
   *      'facetMinCount' => (int)    1,     (default:    1)
   *      'locale'        => (string) 'en',  (default: 'en')
   *      'start'         => (int)    0,     (default:    0)
   *      'count'         => (int)    10,    (default:   10)
   *      'sort'          => object(
   *        'field'     => (string) facetID | 'date',
   *        'direction' => (string) 'DESC' | 'ASC',
   *      ),   (default: object('field' => 'date', 'direction' => 'DESC')
   *    )
   *
   * @return array
   *   List of items or FALSE in case of an error
   */
  public function search($facets = array(), $filters = array(), $parameters = array()) {
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
   *    'context' => (string) label of conceptScheme
   *    'field'   => (string) URI of conceptScheme
   *  )
   */
  public function suggest($search_string, $parameters = array()) {
    return FALSE;
  }

  /**
   * Get all project dependent facets.
   *
   * @return array
   *   A key value pair list of facets
   */
  public function getFacets() {
    return array();
  }

  /**
   * Get all custom facets.
   *
   * @return array
   *   A key value pair list of custom facets
   */
  public function getCustomFacets() {
    return array();
  }

  /**
   * Get similar content.
   *
   * @param int $item_id
   *   The uri of the item
   * @param array $parameters
   *   Array of the parameters
   *
   * @return array
   *   A key value pair list of facets or FALSE in case of an error
   */
  public function getSimilar($item_id, $parameters = array()) {
    return FALSE;
  }

  /**
   * Get the concepts, free terms and recommended content for a given text.
   *
   * @param string $text
   *   The text for the recommendation.
   * @param array $parameters
   *   array(
   *     'language' => (string) 'en', (default: 'en')
   *   )
   *
   * @return array
   *   List of concepts, free terms and recommended content or FALSE in case of
   *   an error
   */
  public function getRecommendation($text, $parameters = array()) {
    return FALSE;
  }

  /**
   * Returns the link to a file collected from sOnr.
   *
   * @param string $file_path
   *   Relative path to a file in the collection
   *
   * @return string
   *   Link to the file in the collection or FALSE in case of an error
   */
  public function getLinkToFile($file_path) {
    return FALSE;
  }

  /**
   * Get all agents with their configuration and status.
   *
   * @return array
   *   A list of agents with their configuration and status
   */
  public function getAgents() {
    return array();
  }

  /**
   * Get all agents that have feed items stored in the search index.
   *
   * @return array
   *   A list of agents
   */
  public function getIndexedAgents() {
    return array();
  }

  /**
   * Get one agent with his configuration.
   *
   * @param int $agent_id
   *   The ID of the agent
   *
   * @return array
   *   List of agents with their configuration or FALSE in case of an error
   */
  public function getAgent($agent_id) {
    return FALSE;
  }

  /**
   * Add a new agent.
   *
   * @param array $config
   *   array(
   *    'source'          => (string) 'EIP Water',
   *    'url'             => (string) 'http://eip-water.eu/rss.xml'
   *    'username'        => (string) 'admin',
   *    'privateContent'  => (boolean) FALSE,
   *    'periodMillis'    => (int) 3600000,
   *    'spaceKey'        => (string) 'extern',
   *  )
   *
   * @return bool
   *   TRUE on success, FALSE on error
   */
  public function addAgent($config) {
    return FALSE;
  }

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
   *    'privateContent'  => (boolean) FALSE,
   *    'periodMillis'    => (int) 3600000,
   *    'spaceKey'        => (string) 'extern',
   *  )
   *
   * @return bool
   *   TRUE on success, FALSE on error.
   */
  public function updateAgent($agent_id, $config) {
    return FALSE;
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
    return FALSE;
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
    return FALSE;
  }

  /**
   * Create a single ping.
   *
   * @param array $ping
   *   array(
   *    'title'         => (string) Title of the ping
   *    'text'          => (string) Content of the ping
   *    'username'      => (string) 'admin',
   *    'creationDate'  => (int) unix timestamp,
   *    'pageUrl'       => (string) node URL --> will become the ID,
   *    'spaceKey'      => (string) 'extern', ... not relevant for Drupal.
   *    'dynUris'{      => (object) Tags of the content
   *      'dyn_uri_all_concepts": [
   *        'http://server.com/Project/Concept1',
   *        'http://server.com/Project/Concept2',
   *        'http://server.com/Project/Concept3'
   *      ]
   *    }
   *  )
   *
   * @return bool
   *   TRUE on success, FALSE on error.
   */
  public function createPing(array $ping) {
    return FALSE;
  }

  /**
   * Update an existing ping.
   *
   * @param array $ping
   *   array(
   *    'title'         => (string) Title of the ping
   *    'text'          => (string) Content of the ping
   *    'username'      => (string) 'admin',
   *    'creationDate'  => (int) unix timestamp,
   *    'pageUrl'       => (string) node URL --> will become the ID,
   *    'spaceKey'      => (string) 'extern', ... not relevant for Drupal.
   *    'dynUris'{      => (object) Tags of the content
   *      'dyn_uri_all_concepts": [
   *        'http://server.com/Project/Concept1',
   *        'http://server.com/Project/Concept2',
   *        'http://server.com/Project/Concept3'
   *        ]
   *    }
   *  )
   *
   * @return bool
   *   TRUE on success, FALSE on error.
   */
  public function updatePing(array $ping) {
    return FALSE;
  }

  /**
   * Delete an existing ping.
   *
   * @param string $page
   *   The URL of the page (= ID of the ping).
   *
   * @return bool
   *   TRUE on success, FALSE on error.
   */
  public function deletePing($page) {
    return FALSE;
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
    return FALSE;
  }

  /**
   * Get trends from a list of concepts.
   *
   * @param array $uris
   *   A list of uris of concepts.
   *
   * @return array
   *   List of trends.
   */
  public function getTrends($uris) {
    return array();
  }

  /**
   * Adds a new custom search field for the suggestion call.
   *
   * @param string $label
   *   The label of the custom search field.
   * @param string $type
   *   The name of the custom search field.
   *   Must start with 'dyn_lit_', e.g. 'dyn_lit_content_type'.
   *
   * @return boolean
   *   TRUE if field is added, FALSE instead.
   */
  public function addCustomSearchField($label, $type) {
    return TRUE;
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
    return TRUE;
  }

  /**
   * Converts facet list into a list of object parameters for the sOnr.
   *
   * @param array $facets
   *   The list of facet objects.
   *
   * @return array
   *   Array of facet objects.
   */
  protected function prepareFacets($facets) {
    return array();
  }

  /**
   * Maps filters into the defined filters or the sOnr.
   *
   * @param array $filters
   *   The list of filters.
   *
   * @return array
   *   Array of filter object parameters.
   */
  protected function prepareFilters($filters) {
    return array();
  }

  /**
   * Sort agends by their source.
   *
   * @param array $a
   *   The first agent.
   * @param array $b
   *   The second agent.
   *
   * @return int
   *   The sort-comparison-value.
   */
  protected function sortAgents($a, $b) {
    return strcasecmp($a->configuration['source'], $b->configuration['source']);
  }
}