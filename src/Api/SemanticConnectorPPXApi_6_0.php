<?php

namespace Drupal\semantic_connector\Api;

/**
 * Class SemanticConnectorPPXApi_6_0
 *
 * API Class for the version 6.0
 */
class SemanticConnectorPPXApi_6_0 extends SemanticConnectorPPXApi_5_6 {
  /**
   * {@inheritdoc}
   */
  public function extractConcepts($data, $language, array $parameters = array(), $data_type = '') {
    $concepts = parent::extractConcepts($data, $language, $parameters, $data_type);

    // Rename the 'extractedTerms' property to the old 'freeTerms' property.
    if (property_exists($concepts, 'extractedTerms')) {
      $concepts->freeTerms = $concepts->extractedTerms;
      unset($concepts->extractedTerms);
    }

    return $concepts;
  }
}
