<?php

namespace Drupal\semantic_connector\Api;
use Drupal\Component\Serialization\Json;

/**
 * Class SemanticConnectorSonrApi_6_0
 *
 * API Class for the version 6.0.
 */
class SemanticConnectorSonrApi_6_0 extends SemanticConnectorSonrApi_5_7 {

  /**
   * This method checks if the GraphSearch service exists and is running.
   *
   * @return bool
   *   TRUE if the service is available, FALSE if not
   */
  public function available() {
    $resource_path = '/' . $this->graphSearchPath . '/admin/heartbeat';
    $result = $this->connection->get($resource_path);

    return $result === '' ? TRUE : FALSE;
  }

  /**
   * This method gets the configuration of the PoolParty GraphSearch server.
   *
   * @return boolean|array
   *   project => The PoolParty project used for the extraction
   *   language => The configured language of the content.
   */
  public function getConfig() {
    $resource_path = '/' . $this->graphSearchPath . '/admin/config/server';
    $result = $this->connection->get($resource_path);

    if ($result === FALSE) {
      return FALSE;
    }

    $data = Json::decode($result);

    return array(
      'project' => ((is_array($data) && isset($data['facet'])) ? $data['facet'] : ''),
      'language' => ((is_array($data) && isset($data['defaultLanguage'])) ? $data['defaultLanguage'] : ''),
    );
  }

  /**
   * This method gets the field configuration of the PoolParty GraphSearch server.
   *
   * @return boolean|array
   *   searchFields -> all available fields in a list
   *   fieldNameMap -> all fields grouped by name
   *   fieldTypeMap -> all fields grouped by type
   *   contextSearchFields -> all fields grouped by field context.
   */
  public function getFieldConfig() {
    $resource_path = '/' . $this->graphSearchPath . '/admin/config/fields';
    $result = $this->connection->get($resource_path);
    $facet_list = Json::decode($result);

    if (!is_array($facet_list)) {
      return FALSE;
    }

    return $facet_list;
  }

  /**
   * This method searches in the GraphSearch index.
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
   * @return boolean|array
   *   List of items or FALSE in case of an error
   */
  public function search($facets = array(), $filters = array(), $parameters = array()) {

    $resource_path = '/' . $this->graphSearchPath . '/api/search';

    $sort = new \stdClass();
    $sort->field = 'date';
    $sort->direction = 'DESC';

    $post_parameters = array(
      'searchFacets' => $this->prepareFacets($facets),
      'searchFilters' => $this->prepareFilters($filters),
      'documentFacets' => $this->customAttributes,
      'facetMinCount' => isset($parameters['facetMinCount']) ? $parameters['facetMinCount'] : 1,
      'maxFacetCount' => isset($parameters['maxFacetCount']) ? $parameters['maxFacetCount'] : 10,
      'locale' => isset($parameters['locale']) ? $parameters['locale'] : 'en',
      'start' => isset($parameters['start']) ? $parameters['start'] : 0,
      'count' => isset($parameters['count']) ? $parameters['count'] : 10,
      'sort' => isset($parameters['sort']) ? $parameters['sort'] : $sort,
    );

    $result = $this->connection->post($resource_path, array(
      'data' => Json::encode($post_parameters),
    ));

    $items = Json::decode($result);

    if (!is_array($items)) {
      return FALSE;
    }

    $items = $this->makeSearchCompatible($items);

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
   * @return boolean|array
   *   Array of concepts
   *   array(
   *    'id'      => (string) URI of concept
   *    'label'   => (string) prefLabel of concept
   *    'context' => (string) prefLabel of the broader concept
   *    'field'   => (string) URI of conceptScheme
   *  )
   */
  public function suggest($search_string, $parameters = array()) {
    $resource_path = '/' . $this->graphSearchPath . '/api/suggest/multi';
    $get_parameters = array(
      'searchString' => $search_string,
      'locale' => isset($parameters['locale']) ? $parameters['locale'] : 'en',
      'count' => isset($parameters['count']) ? $parameters['count'] : 10,
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
   * @return boolean|array
   *   A key value pair list of facets
   */
  public function getFacets() {
    // Get the fields for the facets.
    $facet_list = $this->getFieldConfig();

    if (!is_array($facet_list)) {
      return FALSE;
    }

    // Add custom facets.
    $facets = array();
    if (!empty($facet_list['contextSearchFields']['CustomSearchFieldContext'])) {
      foreach ($facet_list['contextSearchFields']['CustomSearchFieldContext'] as $field) {
        $facets[$field['field']] = $field['label'];
      }
    }

    // Add GraphSearch facets.
    if (!empty($facet_list['contextSearchFields']['GraphSearchFieldContext'])) {
      foreach ($facet_list['contextSearchFields']['GraphSearchFieldContext'] as $field) {
        $facets[$field['field']] = $field['label'];
      }
    }
    return $facets;
  }

  /**
   * Get all custom facets.
   *
   * @return boolean|array
   *   A key value pair list of custom facets
   */
  public function getCustomFacets() {
    // Get the fields for the facets.
    $facet_list = $this->getFieldConfig();

    if (!is_array($facet_list)) {
      return FALSE;
    }

    // Add custom facets.
    $facets = array();
    if (!empty($facet_list['contextSearchFields']['CustomSearchFieldContext'])) {
      foreach ($facet_list['contextSearchFields']['CustomSearchFieldContext'] as $field) {
        $facets[$field['field']] = $field['label'];
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
   * @return boolean|array
   *   A key value pair list of facets or FALSE in case of an error
   */
  public function getSimilar($item_id, $parameters = array()) {
    $resource_path = '/' . $this->graphSearchPath . '/api/similar';
    $get_parameters = array(
      'id' => $item_id,
      'locale' => isset($parameters['locale']) ? $parameters['locale'] : 'en',
      'count' => isset($parameters['count']) ? $parameters['count'] : 10,
      'fields' => 'dyn_uri_all_concepts,title,description',
      'documentFacets' => $this->customAttributes,
    );

    $result = $this->connection->get($resource_path, array(
      'query' => $get_parameters,
    ));

    $similar = Json::decode($result);

    if (!is_array($similar)) {
      return FALSE;
    }

    $similar = $this->makeSearchCompatible($similar);

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
   * @return boolean|array
   *   List of concepts, free terms and recommended content or FALSE in case of
   *   an error
   */
  public function getRecommendation($text, $parameters = array()) {
    $resource_path = '/' . $this->graphSearchPath . '/api/recommend';
    $post_parameters = array(
      'text' => $text,
      'language' => isset($parameters['locale']) ? $parameters['locale'] : 'en',
      'start' => isset($parameters['start']) ? $parameters['start'] : 0,
      'count' => isset($parameters['count']) ? $parameters['count'] : 10,
      'numberOfConcepts' => isset($parameters['numberOfConcepts']) ? $parameters['numberOfConcepts'] : 10,
      'numberOfTerms' => isset($parameters['numberOfTerms']) ? $parameters['numberOfTerms'] : 5,
      'fields' => array('dyn_uri_all_concepts', 'title', 'description'),
    );

    $result = $this->connection->post($resource_path, array(
      'data' => Json::encode($post_parameters),
    ));

    $recommendations = Json::decode($result);

    if (!is_object($recommendations)) {
      return FALSE;
    }

    return $recommendations;
  }

  /**
   * Returns the link to a file collected from GraphSearch.
   *
   * INFO: This method does not exists any more.
   *
   * @param string $file_path
   *   Relative path to a file in the collection
   *
   * @return string
   *   Link to the file in the collection or FALSE in case of an error
   */
  public function getLinkToFile($file_path) {
    return '';
  }

  /**
   * Get all agents that have feed items stored in the search index.
   *
   * @return array
   *   A list of agents
   */
  public function getIndexedAgents() {
    $agents = [];

    $parameters = [
      'count' => 1,
      'start' => 0,
      'maxFacetCount' => 10000,
    ];
    $search = $this->search([], [], $parameters);
    if ($search == FALSE) {
      return [];
    }

    // Get the agents from the facet list.
    $source_facet = $search['facetList'][0]['facets'];
    foreach ($source_facet as $source) {
      $agents[$source['label']] = $source['value'];
    }

    return $agents;
  }

  /**
   * Add a new agent.
   *
   * @param array $config
   *   array(
   *    'source'          => (string) 'EIP Water',
   *    'url'             => (string) 'http://eip-water.eu/rss.xml'
   *    'username'        => (string) 'admin',
   *    'periodMillis'    => (int) 3600000,
   *  )
   *
   * @return bool
   *   TRUE on success, FALSE on error
   */
  public function addAgent($config) {
    $config['privateContent'] = FALSE;
    $config['context'] = '';

    $resource_path = '/' . $this->graphSearchPath . '/api/agents';

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
   *    'periodMillis'    => (int) 3600000,
   *  )
   *
   * @return bool
   *   TRUE on success, FALSE on error.
   */
  public function updateAgent($agent_id, $config) {
    $config['privateContent'] = FALSE;
    $config['context'] = '';

    $resource_path = '/' . $this->graphSearchPath . '/api/agents/%id';

    $result = $this->connection->post($resource_path, array(
      'parameters' => array('%id' => $agent_id),
      'data' => Json::encode($config),
    ));

    return $result === FALSE ? FALSE : TRUE;
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
    return $this->updatePing($ping);
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
    $resource_path = '/' . $this->graphSearchPath . '/api/content/update';
    $ping['identifier'] = $ping['pageUrl'];
    $ping['text'] = substr($ping['text'], 0, 12000);
    $ping['author'] = $ping['username'];
    $ping['date'] = $ping['creationDate'];
    $ping['facets'] = array_merge($ping['customAttributes'], $ping['dynUris']);
    $ping['useExtraction'] = empty($ping['dynUris']);
    $ping['context'] = $ping['spaceKey'];

    unset($ping['pageUrl']);
    unset($ping['username']);
    unset($ping['creationDate']);
    unset($ping['customAttributes']);
    unset($ping['dynUris']);
    unset($ping['spaceKey']);

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
    $resource_path = '/' . $this->graphSearchPath . '/api/content/delete/id';

    $result = $this->connection->post($resource_path, array(
      'data' => Json::encode(array('identifier' => $page)),
    ));

    return $result === FALSE ? FALSE : TRUE;
  }

  /**
   * Adds a new custom search field for the suggestion call.
   *
   * @param string $label
   *   The label of the custom search field.
   * @param string $field
   *   The name of the custom search field, e.g. 'content_type'.
   *
   * @return boolean
   *   TRUE if field is added, FALSE instead.
   */
  public function addCustomSearchField($label, $field) {
    $resource_path = '/' . $this->graphSearchPath . '/admin/suggest/add';
    $field = 'dyn_lit_' . str_replace('-', '_', $field);
    $post_parameters = array(
      'field' => $field,
      'label' => $label,
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
   * @param string $field
   *   The name of the custom search field, e.g. 'content_type'.
   *
   * @return boolean
   *   TRUE if field is added, FALSE instead.
   */
  public function deleteCustomSearchField($field) {
    $resource_path = '/' . $this->graphSearchPath . '/admin/suggest/delete';
    $field = 'dyn_lit_' . str_replace('-', '_', $field);
    $post_parameters = array(
      'field' => $field,
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
   * Changes the result array so that it is compatible with older version.
   *
   * @param array $result
   *   The result of the search API call.
   *
   * @return array
   *   The compatible result for older version.
   */
  protected function makeSearchCompatible($result) {
    if (empty($result['results'])) {
      return $result;
    }

    foreach ($result['results'] as &$item) {
      $item['customAttributes'] = [];
      if (empty($item['facetList'])) {
        unset($item['facetList']);
        continue;
      }

      foreach ($item['facetList'] as $facet) {
        switch ($facet['field']) {
          case self::ATTR_ALL_CONCEPTS:
            foreach ($facet['facets'] as $concept) {
              $item['customAttributes'][$facet['field']][] = [
                'prefLabel' => $concept['value'],
                'uri' => $concept['label'],
              ];
            }
            break;

          case self::ATTR_CONTENT_TYPE:
            $item['customAttributes'][$facet['field']][0] = $facet['facets'][0]['label'];
            break;

          case self::ATTR_AUTHOR:
          case self::ATTR_SENTIMENT:
          case self::ATTR_SOURCE:
            $item['customAttributes'][$facet['field']] = $facet['facets'][0]['label'];
            break;
        }
      }
      unset($item['facetList']);
    }

    return $result;
  }
}
