<?php

namespace Drupal\semantic_connector\Api;
use Drupal\Component\Serialization\Json;

/**
 * Class SemanticConnectorSonrApi_4_6
 *
 * API Class for the version 4.6
 */
class SemanticConnectorSonrApi_4_6 extends SemanticConnectorSonrApi {

  /**
   * This method checks if the sOnr service exists and is running.
   *
   * @return bool
   *   TRUE if the service is available, FALSE if not
   */
  public function available() {
    $resource_path = '/sonr-backend/api/heartbeat';
    $result = $this->connection->get($resource_path);

    return $result === '' ? TRUE : FALSE;
  }

  /**
   * This method gets the configuration of the sOnr webMining server.
   *
   * @return array
   *   project => The PoolParty project used for the extraction
   *   language => The configured language of the content.
   */
  public function getConfig() {
    $resource_path = '/sonr-backend/api/admin/config';
    $result = $this->connection->get($resource_path);

    if ($result === FALSE) {
      return FALSE;
    }

    $data = Json::decode($result);

    return array(
      'project' => ((is_array($data) && isset($data['project'])) ? $data['project'] : ''),
      'language' => ((is_array($data) && isset($data['language'])) ? $data['language'] : ''),
    );
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
    $resource_path = '/sonr-backend/api/config';
    $result = $this->connection->get($resource_path);
    $facet_list = Json::decode($result);

    if (!is_array($facet_list)) {
      return FALSE;
    }

    return $facet_list;
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

    $resource_path = '/sonr-backend/api/search';

    $sort = new \stdClass();
    $sort->field = 'date';
    $sort->direction = 'DESC';

    $post_parameters = array(
      'facets' => $this->prepareFacets($facets),
      'filters' => $this->prepareFilters($filters),
      'customAttributes' => $this->customAttributes,
      'facetMinCount' => isset($parameters['facetMinCount']) ? $parameters['facetMinCount'] : 1,
      'maxFacetCount' => isset($parameters['maxFacetCount']) ? $parameters['maxFacetCount'] : 10,
      'locale' => isset($parameters['locale']) ? $parameters['locale'] : 'en',
      'start' => isset($parameters['start']) ? $parameters['start'] : 0,
      'count' => isset($parameters['count']) ? $parameters['count'] : 10,
      'format' => 'json',
      'sort' => isset($parameters['sort']) ? $parameters['sort'] : $sort,
    );

    $result = $this->connection->post($resource_path, array(
      'data' => Json::encode($post_parameters),
    ));

    $items = Json::decode($result);

    if (!is_array($items)) {
      return FALSE;
    }

    return $items;
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
    $resource_path = '/sonr-backend/api/suggest';
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

  /**
   * Get all project dependent facets.
   *
   * @return array
   *   A key value pair list of facets
   */
  public function getFacets() {
    // Get the fields for the facets.
    $resource_path = '/sonr-backend/api/config';
    $result = $this->connection->get($resource_path);
    $facet_list = Json::decode($result);

    if (!is_array($facet_list)) {
      return FALSE;
    }

    // Add project dependent facets.
    $facets = array();
    $all_fields = array();
    foreach ($facet_list['searchFields'] as $field) {
      $all_fields[] = $field['name'];
      if ($field['type'] == 'URI' && $field['defaultFacet'] == TRUE) {
        $facets[$field['name']] = $field['label'];
      }
    }

    // Add project independent facets.
    $custom_facets = $this->getCustomFacets();
    if (!empty($custom_facets)) {
      foreach ($custom_facets as $field_name => $field_label) {
        if ((!in_array($field_name, $all_fields) || $field_name == self::ATTR_SOURCE) && $field_name != self::ATTR_SPACE) {
          $facets[$field_name] = $field_label;
        }
      }
    }

    return $facets;
  }

  /**
   * Get all custom facets.
   *
   * @return array
   *   A key value pair list of custom facets
   */
  public function getCustomFacets() {
    // Get the custom fields.
    $resource_path = '/sonr-backend/api/config/custom';
    $result = $this->connection->get($resource_path);
    $custom_facet_list = Json::decode($result);

    $facets = array();
    if (isset($custom_facet_list['customSearchFields']) && !empty($custom_facet_list['customSearchFields'])) {
      foreach ($custom_facet_list['customSearchFields'] as $field) {
        $facets[$field['name']] = $field['label'];
      }
    }

    return $facets;
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
    $resource_path = '/sonr-backend/api/similar';
    $get_parameters = array(
      'id' => $item_id,
      'locale' => isset($parameters['locale']) ? $parameters['locale'] : 'en',
      'count' => isset($parameters['count']) ? $parameters['count'] : 10,
      'format' => 'json',
      'fields' => 'dyn_uri_all_concepts,title,description',
      'customAttributes' => implode(',', $this->customAttributes),
    );

    $result = $this->connection->get($resource_path, array(
      'query' => $get_parameters,
    ));

    $similar = Json::decode($result);

    if (!is_array($similar)) {
      return FALSE;
    }

    return $similar;
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
    $resource_path = '/sonr-backend/api/recommend';
    $post_parameters = array(
      'text' => $text,
      'language' => isset($parameters['locale']) ? $parameters['locale'] : 'en',
      'start' => isset($parameters['start']) ? $parameters['start'] : 0,
      'count' => isset($parameters['count']) ? $parameters['count'] : 10,
      'numberOfConcepts' => isset($parameters['numberOfConcepts']) ? $parameters['numberOfConcepts'] : 10,
      'numberOfTerms' => isset($parameters['numberOfTerms']) ? $parameters['numberOfTerms'] : 5,
      'fields' => array('dyn_uri_all_concepts', 'title', 'description'),
      'nativeQuery' => isset($parameters['nativeQuery']) ? $parameters['nativeQuery'] : '',
      'customAttributes' => $this->customAttributes,
    );

    $result = $this->connection->post($resource_path, array(
      'data' => Json::encode($post_parameters),
    ));

    $recommendations = json_decode($result);

    if (!is_object($recommendations)) {
      return FALSE;
    }

    return $recommendations;
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
    $resource_path = '/sonr-backend/api/collector/';
    return $this->connection->getEndpoint() . $resource_path . $file_path;
  }

  /**
   * Get all agents with their configuration and status.
   *
   * @return array
   *   A list of agents with their configuration and status
   */
  public function getAgents() {
    $resource_path = '/sonr-backend/api/agents/status';
    $result = $this->connection->get($resource_path);

    $agent_list = Json::decode($result);

    if (!is_array($agent_list)) {
      return FALSE;
    }

    $agents = array();
    if (!is_null($agent_list)) {
      foreach ($agent_list as $id => $agent) {
        $agents[$id] = new \stdClass();
        $agents[$id]->id = $agent['agent']['id'];
        $agents[$id]->configuration = $agent['agent']['configuration'];
        $agents[$id]->status = $agent['status'];
      }
      usort($agents, array($this, 'sortAgents'));
    }

    return $agents;
  }

  /**
   * Get all agents that have feed items stored in the search index.
   *
   * @return array
   *   A list of agents
   */
  public function getIndexedAgents() {
    $agents = array();

    // Make a simple search call with the source facet only.
    // TODO: This hardcoded config should be from the saved configuration.
    $facets = array(
      self::ATTR_SOURCE => array(
        'name' => 'Source',
        'selected' => 1,
        'aggregated' => 0,
        'max-items' => 1,
        'facet_id' => self::ATTR_SOURCE,
      ),
    );
    $parameters = array(
      'count' => 1,
      'start' => 0,
      'maxFacetCount' => 10000,
    );
    $search = $this->search($facets, array(), $parameters);
    if ($search == FALSE) {
      return array();
    }

    // Get the agents from the facet list.
    $source_facet = $search['facetList'][0]['facets'];
    foreach ($source_facet as $source) {
      $agents[$source['label']] = $source['value'];
    }

    return $agents;
  }

  /**
   * Get one agent with his configuration.
   *
   * @param int $agent_id
   *   The ID of the agent
   *
   * @return array
   *   The configuration of a given agent or FALSE in case of an error
   */
  public function getAgent($agent_id) {
    $resource_path = '/sonr-backend/api/agents/%id';

    $result = $this->connection->get($resource_path, array(
      'parameters' => array('%id' => $agent_id),
    ));

    $agent = Json::decode($result);

    if (!is_array($agent)) {
      return FALSE;
    }

    $agent['id'] = $agent_id;

    return $agent;
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
    $resource_path = '/sonr-backend/api/agents';

    $result = $this->connection->post($resource_path, array(
      'data' => Json::encode($config),
    ));

    return $result === FALSE ? FALSE : TRUE;
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
    $resource_path = '/sonr-backend/api/agents/%id';

    $result = $this->connection->put($resource_path, array(
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
    $resource_path = '/sonr-backend/api/agents/%id';

    $result = $this->connection->delete($resource_path, array(
      'parameters' => array('%id' => $agent_id),
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
    $resource_path = '/sonr-backend/api/agents/runAgent';

    $result = $this->connection->get($resource_path, array(
      'query' => array('id' => $agent_id),
      'timeout' => 120,
    ));

    return $result  === FALSE ? FALSE : TRUE;
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
    $resource_path = '/sonr-backend/api/pings/create';
    $ping['text'] = substr($ping['text'], 0, 12000);

    $result = $this->connection->post($resource_path, array(
      'data' => Json::encode($ping),
    ));

    return $result === FALSE ? FALSE : TRUE;
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
   *    'dynUris'{      => (object) Tags of the content and term references
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
    $resource_path = '/sonr-backend/api/pings/update';
    $ping['text'] = substr($ping['text'], 0, 12000);

    $result = $this->connection->post($resource_path, array(
      'data' => Json::encode($ping),
    ));

    return $result === FALSE ? FALSE : TRUE;
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
    $resource_path = '/sonr-backend/api/pings/delete';

    $result = $this->connection->post($resource_path, array(
      'data' => Json::encode(array('pageUrl' => $page)),
    ));

    return $result === FALSE ? FALSE : TRUE;
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
    $resource_path = '/sonr-backend/api/pings/deleteAll';

    $result = $this->connection->get($resource_path, array(
      'query' => array('source' => $source),
    ));

    return $result === FALSE ? FALSE : TRUE;
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
    $resource_path = '/sonr-backend/api/trend/histories';

    if (is_string($uris)) {
      $uris = array($uris);
    }

    $result = $this->connection->get($resource_path, array(
      'query' => array('concepts' => implode(',', $uris)),
    ));

    $trends = Json::decode($result);

    if (!is_array($trends)) {
      return FALSE;
    }

    return $trends;
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
    $facet_parameters = array();
    foreach ($facets as $facet) {
      $facet_parameters[] = array(
        'field' => $facet['facet_id'],
        'facetMode' => $facet['aggregated'] ? 'aggregate' : 'list',
      );
    }

    return $facet_parameters;
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
    $dates = array();
    foreach ($filters as $key => $filter) {
      if ($filter->field == 'date-from') {
        $dates['from'] = $filter->value . 'T00:00:00Z';
        unset($filters[$key]);
      }
      if ($filter->field == 'date-to') {
        $dates['to'] = $filter->value . 'T23:59:59Z';
        unset($filters[$key]);
      }
    }
    $value = '';
    $value .= isset($dates['from']) ? $dates['from'] : '*';
    $value .= ' TO ';
    $value .= isset($dates['to']) ? $dates['to'] : '*';

    $item = new \stdClass();
    $item->field = 'date';
    $item->value = '[' . $value . ']';

    $filters[] = $item;
    $filters = array_values($filters);

    return $filters;
  }
}
