<?php

namespace Drupal\semantic_connector\Api;
use Drupal\Component\Serialization\Json;

/**
 * Class SemanticConnectorPPTApi_4_6
 *
 * API Class for the version 4.6
 */
class SemanticConnectorPPTApi_4_6 extends SemanticConnectorPPTApi {

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
    $is_available = array(
      'success' => FALSE,
      'message' => '',
    );
    $resource_path = '/PoolParty/api/version';
    $result = $this->connection->get($resource_path, array('headers' => array('Accept' => 'text/plain')));

    if (is_string($result) && !empty($result)) {
      $is_available['success'] = TRUE;
    }
    else {
      $is_available['message'] = 'PoolParty server is not available';
    }

    return $is_available;
  }

  /**
   * Get the version of the installed PoolParty web service.
   *
   * @return string
   *   The PoolParty version formatted like '4.6'
   */
  public function getVersion() {
    $resource_path = '/PoolParty/api/version';
    return $this->connection->get($resource_path, array('headers' => array('Accept' => 'text/plain')));
  }

  /**
   * Get a list of available projects of a PoolParty server.
   *
   * @return array
   *   A list of projects.
   */
  public function getProjects() {
    $resource_path = '/PoolParty/api/projects';
    $result = $this->connection->get($resource_path);
    $projects = json_decode($result);

    if (is_array($projects)) {
      foreach ($projects as &$project) {
        if (property_exists($project, 'uriSupplement')) {
          $project->sparql_endpoint_url = $this->connection->getEndpoint() . '/PoolParty/sparql/' . $project->uriSupplement;
        }
      }
    }
    else {
      $projects = array();
    }

    return $projects;
  }

  /**
   * Export data of a project as a file and store it on the server.
   *
   * @param string $project_id
   *   The ID of the PoolParty project to export and store.
   * @param string $format
   *   The returned RDF format.
   *   Possible values are: TriG, N3, Turtle, N-Triples, RDF/XML, TriX
   * @param string $export_modules
   *   A list of the export modules for the data that should be exported.
   *   Possible values are:
   *   - concepts - includes concept schemes, concepts, collections and notes
   *   - workflow - workflow status for all concepts
   *   - history - all history events
   *   - freeConcepts - all free concepts
   *   - void - the project VoiD graph
   *   - adms - the project ADMS graph
   *
   * @return string
   *   The URL of the stored file or an empty string if an error occurred.
   */
  public function storeProject($project_id, $format = 'RDF/XML', $export_modules = 'concepts') {
    $resource_path = '/PoolParty/api/projects/' . $project_id . '/store';
    $get_parameters = array(
      'format' => $format,
      'exportModules' => $export_modules,
    );
    $file_path = $this->connection->get($resource_path, array(
      'query' => $get_parameters,
      'headers' => array('Accept' => 'text/plain'),
    ));

    return (filter_var($file_path, FILTER_VALIDATE_URL) !== FALSE) ? $file_path : '';
  }

  /**
   * Gets information about a concept scheme.
   *
   * @param string $project_id
   *   The ID of the PoolParty project of the concepts.
   * @param string $language
   *   Only concepts with labels in this language will be displayed. If no
   *   language is given, the default language of the project will be used.
   *
   * @return object
   *   A concept scheme object within the respective PoolParty project.
   */
  public function getConceptSchemes($project_id, $language = '') {
    $resource_path = '/PoolParty/api/thesaurus/' . $project_id . '/schemes';
    $get_parameters = array(
      'language' => $language,
    );
    $result = $this->connection->get($resource_path, array(
      'query' => $get_parameters,
    ));
    $concept_schemes = Json::decode($result);

    return $concept_schemes;
  }

  /**
   * Gets a list of all top concepts of a specific concept scheme.
   *
   * @param string $project_id
   *   The ID of the PoolParty project.
   * @param string $scheme_uri
   *   The URI of the concept scheme.
   * @param array $properties
   *   A list of additional properties to fetch (e.g. skos:altLabel, skso:hiddenLabel).
   * @param string $language
   *   Only concepts with labels in this language will be displayed. If no
   *   language is given, the default language of the project will be used.
   *
   * @return array
   *   A list of top concepts.
   */
  public function getTopConcepts($project_id, $scheme_uri, array $properties = array(), $language = '') {
    $resource_path = '/PoolParty/api/thesaurus/' . $project_id . '/topconcepts';
    $get_parameters = array(
      'scheme' => $scheme_uri,
      'properties' => implode(',', $properties),
      'language' => $language,
    );
    $result = $this->connection->get($resource_path, array(
      'query' => $get_parameters,
    ));
    $concept = Json::decode($result);

    return $concept;
  }

  /**
   * Get a list of all concepts under a specified concept in a tree format.
   *
   * @param int $project_id
   *   The ID of the PoolParty project.
   * @param string $uri
   *   A concept URI.
   * @param array $properties
   *   A list of additional properties to fetch (e.g. skos:altLabel, skso:hiddenLabel).
   * @param string $language
   *   Only concepts with labels in this language will be displayed. If no
   *   language is given, the default language of the project will be used.
   *
   * @return array
   *   A list of concept objects in a tree format.
   */
  public function getSubTree($project_id, $uri, array $properties = array(), $language = '') {
    // PoolParty Thesaurus API Bug (version 5.3.1):
    // At least the prefLabel proberty must be indicated.
    if (!in_array('skos:prefLabel', $properties)) {
      $properties[] = 'skos:prefLabel';
    }

    $resource_path = '/PoolParty/api/thesaurus/' . $project_id . '/subtree';
    $get_parameters = array(
      'uri' => $uri,
      'properties' => implode(',', $properties),
      'language' => $language,
    );
    $result = $this->connection->get($resource_path, array(
      'query' => $get_parameters,
    ));
    $concept = Json::decode($result);

    return $concept;
  }

  /**
   * Get information about concepts.
   *
   * @param string $project_id
   *   The ID of the PoolParty project of the concepts.
   * @param array $concept_uris
   *   An array of concept URIs to get information for.
   * @param array $properties
   *   Array of additional concept properties that will be fetched (only
   *   properties uri and prefLabel are included by default). Possible values
   *   are:
   *   - skos:prefLabel
   *   - skos:altLabel
   *   - skos:hiddenLabel
   *   - skos:definition
   *   - skos:broader
   *   - skos:narrower
   *   - skos:related
   *   - skos:ConceptSchemes
   *   - all
   * @param string $language
   *   Only concepts with labels in this language will be displayed. If no
   *   language is given, the default language of the project will be used.
   *
   * @return array
   *   Array of concept objects within the respective PoolParty project with
   *   following properties:
   *   - uri --> URI of the concept
   *   - prefLabel --> Preferred label
   *   - altLabels --> Alternative labels
   *   - hiddenLabels --> Hidden labels
   *   - definitions --> Definitions
   *   - broaders --> Broader concepts
   *   - narrowers --> Narrower concepts
   *   - relateds --> Related concepts
   *   - conceptSchemes --> Concept schemes
   */
  public function getConcepts($project_id, array $concept_uris, array $properties = array(), $language = '') {
    if (empty($concept_uris)) {
      return array();
    }

    if (!in_array('skos:prefLabel', $properties)) {
      $properties[] = 'skos:prefLabel';
    }

    $resource_path = '/PoolParty/api/thesaurus/' . $project_id . '/concepts';
    $resource_path .= '?concepts=' . implode('&concepts=', $concept_uris);
    $get_parameters = array(
      'properties' => $properties,
      'language' => $language,
    );
    $result = $this->connection->get($resource_path, array(
      'query' => $get_parameters,
    ));
    $concepts = Json::decode($result);

    return $concepts;
  }

  /**
   * Get information about a concept.
   *
   * @param string $project_id
   *   The ID of the PoolParty project of the concepts.
   * @param string $concept_uri
   *   The concept URI, from which the data should be retrieved.
   * @param string array $properties
   *   Array of additional concept properties that will be fetched (only
   *   properties uri and prefLabel are included by default). Possible values
   *   are:
   *   - skos:prefLabel
   *   - skos:altLabel
   *   - skos:hiddenLabel
   *   - skos:definition
   *   - skos:broader
   *   - skos:narrower
   *   - skos:related
   *   - skos:ConceptSchemes
   *   - all
   * @param string $language
   *   Only concepts with labels in this language will be displayed. If no
   *   language is given, the default language of the project will be used.
   *
   * @return object
   *   A concept object within the respective PoolParty project with
   *   following properties:
   *   - uri --> URI of the concept
   *   - prefLabel --> Preferred label
   *   - altLabels --> Alternative labels
   *   - hiddenLabels --> Hidden labels
   *   - definitions --> Definitions
   *   - broaders --> Broader concepts
   *   - narrowers --> Narrower concepts
   *   - relateds --> Related concepts
   *   - conceptSchemes --> Concept schemes
   */
  public function getConcept($project_id, $concept_uri, array $properties = array(), $language = '') {
    // PoolParty Thesaurus API Bug (version 5.3.1):
    // At least the prefLabel proberty must be indicated.

    if (!in_array('skos:prefLabel', $properties)) {
      $properties[] = 'skos:prefLabel';
    }

    $resource_path = '/PoolParty/api/thesaurus/' . $project_id . '/concept';
    $get_parameters = array(
      'concept' => $concept_uri,
      'properties' => implode(',', $properties),
      'language' => $language,
    );
    $result = $this->connection->get($resource_path, array(
      'query' => $get_parameters,
    ));
    $concept = Json::decode($result);

    return $concept;
  }

  /**
   * Creates a concept scheme in a specific project.
   *
   * @param string $project_id
   *   The ID of the PoolParty project in which the concept scheme should be created.
   * @param string $title
   *   The title of the new concept scheme.
   * @param string $description
   *   A description for the new concept scheme.
   * @param string $creator
   *   The name of the creator of the new concept scheme.
   *
   * @return string
   *   The URI of the new concept scheme.
   */
  public function createConceptScheme($project_id, $title, $description, $creator = 'Drupal') {
    $resource_path = '/PoolParty/api/thesaurus/' . $project_id . '/createConceptScheme';
    $post_parameters = array(
      'title' => $title,
      'description' => $description,
      'creator' => $creator,
    );

    $result = $this->connection->post($resource_path, array(
      'data' => $post_parameters,
      'headers' => array('Accept' => 'text/plain'),
    ));

    return $result;
  }

  /**
   * Creates a new concept in a specific project.
   *
   * @param string $project_id
   *   The ID of the PoolParty project in which the concept should be created.
   * @param string $prefLabel
   *   The label in the default language of the project.
   * @param string $parent
   *   The URI of the parent concept or concept scheme of the new concept.
   *
   * @return string
   *   The URI of the new concept.
   */
  public function createConcept($project_id, $prefLabel, $parent) {
    $resource_path = '/PoolParty/api/thesaurus/' . $project_id . '/createConcept';
    $post_parameters = array(
      'prefLabel' => $prefLabel,
      'parent' => $parent,
    );
    $result = $this->connection->post($resource_path, array(
      'data' => $post_parameters,
      'headers' => array('Accept' => 'text/plain'),
    ));

    return $result;
  }

  /**
   * Adds a SKOS relation between two existing concepts
   *
   * @param string $project_id
   *   The ID of the PoolParty project.
   * @param string $source
   *   The URI of the source concept.
   * @param string $target
   *   The URI of the target concept.
   * @param string $property
   *   The relation property. Possible values are:
   *   - broader
   *   - narrower
   *   - related
   *   - hasTopConcept
   *   - topConceptOf
   *
   * @return mixed
   *  Status: 200 - OK
   */
  public function addRelation($project_id, $source, $target, $property = 'broader') {
    $resource_path = '/PoolParty/api/thesaurus/' . $project_id . '/addRelation';
    $post_parameters = array(
      'sourceConcept' => $source,
      'targetConcept' => $target,
      'property' => $property,
    );
    $result = $this->connection->post($resource_path, array(
      'data' => $post_parameters,
    ));

    return $result;
  }

  /**
   * Adds a literal to an existing concept
   *
   * @param $project_id
   *  The ID of the PoolParty project.
   * @param $concept_uri
   *  The URI of the Concept.
   * @param $property
   *  The SKOS property. Possible values are:
   *  - preferredLabel
   *  - alternativeLabel
   *  - hiddenLabel
   *  - definition
   *  - scopeNote
   *  - example
   *  - notation
   * @param $label
   *  The RDF literal to add.
   * @param null $language
   *  The attribute language.
   *
   * @return mixed
   *  Status: 200 - OK
   */
  public function addLiteral($project_id, $concept_uri, $property, $label, $language = NULL) {
    $resource_path = '/PoolParty/api/thesaurus/' . $project_id . '/addLiteral';
    $post_parameters = array(
      'concept' => $concept_uri,
      'label' => $label,
      'property' => $property,
    );

    if (!is_null($language) && !empty($language)) {
      $post_parameters['language'] = $language;
    }

    $result = $this->connection->post($resource_path, array(
      'data' => $post_parameters,
    ));

    return $result;
  }

  /**
   * Adds a literal to an existing concept
   *
   * @param $project_id
   *  The ID of the PoolParty project.
   * @param $concept_uri
   *  The URI of the Concept to add the property to.
   * @param $attribute_uri
   *  The URI of the custom attribute property.
   * @param $value
   *  The attribute value that should be added
   * @param null $language
   *  The attribute language.
   *
   * @return mixed
   *  Status: 200 - OK
   */
  public function addCustomAttribute($project_id, $concept_uri, $attribute_uri, $value, $language = NULL) {
    $resource_path = '/PoolParty/api/thesaurus/' . $project_id . '/addCustomAttribute';
    $post_parameters = array(
      'resource' => $concept_uri,
      'property' => $attribute_uri,
      'value' => $value,
    );

    if (!is_null($language) && !empty($language)) {
      $post_parameters['language'] = $language;
    }

    $result = $this->connection->post($resource_path, array(
      'data' => $post_parameters,
    ));

    return $result;
  }
}