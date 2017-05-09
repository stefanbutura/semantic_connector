<?php

namespace Drupal\semantic_connector\Api;
use Drupal\Component\Serialization\Json;

/**
 * Class SemanticConnectorPPTApi_5_6
 *
 * API Class for the version 5.6
 */
class SemanticConnectorPPTApi_5_6 extends SemanticConnectorPPTApi_5_3 {

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
      'root' => $uri,
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
    ));

    return Json::decode($result);
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
    ));

    return Json::decode($result);
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
    ));
    $file_path = Json::decode($file_path);

    return (filter_var($file_path, FILTER_VALIDATE_URL) !== FALSE) ? $file_path : '';
  }
}