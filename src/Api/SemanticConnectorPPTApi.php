<?php

namespace Drupal\semantic_connector\Api;
use Drupal\semantic_connector\SemanticConnectorCurlConnection;

/**
 * Abstract Class SemanticConnectorPPTApi
 *
 * API Class for the PoolParty Thesaurus.
 */
abstract class SemanticConnectorPPTApi {
  protected $connection;

  /**
   * The constructor of the PoolParty Thesaurus class.
   *
   * @param string $endpoint
   *   URL of the endpoint of the PoolParty-server.
   * @param string $credentials
   *   Username and password if required (format: "username:password").
   */
  public function __construct($endpoint, $credentials = '') {
    $this->connection = new SemanticConnectorCurlConnection($endpoint, $credentials);
  }

  /**
   * Get the configured cURL-connection.
   *
   * @return SemanticConnectorCurlConnection
   *   The connection object.
   */
  public function getConnection() {
    return $this->connection;
  }

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
    return array('success' => FALSE);
  }

  /**
   * Get the version of the installed PoolParty web service.
   *
   * @return string
   *   The PoolParty version formatted like '4.1.6'
   */
  public function getVersion() {
    return '';
  }

  /**
   * Get a list of available projects of a PoolParty server.
   *
   * @return array
   *   A list of projects.
   */
  public function getProjects() {
    return array();
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
    return '';
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
   * @return stdClass
   *   A concept scheme object within the respective PoolParty project.
   */
  public function getConceptSchemes($project_id, $language = '') {
    return array();
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
    return array();
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
   *   - skos:altLabel
   *   - skos:hiddenLabel
   *   - skos:definition
   *   - skos:broader
   *   - skos:narrower
   *   - skos:related
   *   - skos:ConceptScheme
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
  public function getConcepts($project_id, array $concept_uris, array $properties = array(), $language = NULL) {
    return array();
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
   *   - skos:altLabel
   *   - skos:hiddenLabel
   *   - skos:definition
   *   - skos:broader
   *   - skos:narrower
   *   - skos:related
   *   - skos:ConceptScheme
   * @param string $language
   *   Only concepts with labels in this language will be displayed. If no
   *   language is given, the default language of the project will be used.
   *
   * @return stdClass
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
    return new stdClass();
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
    return '';
  }

  /**
   * Creates a new concept in a specific project.
   *
   * @param string $project_id
   *   The ID of the PoolParty project in which the concept should be created.
   * @param string $parent
   *   The URI of the parent concept or concept scheme of the new concept.
   * @param string $prefLabel
   *   The label in the default language of the project.
   *
   * @return string
   *   The URI of the new concept.
   */
  public function createConcept($project_id, $parent, $prefLabel) {
    return '';
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
    return '';
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
    return '';
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
    return '';
  }

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
    return array();
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
    return array();
  }
}