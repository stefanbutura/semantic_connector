<?php

namespace Drupal\semantic_connector\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\pp_graphsearch\Entity\PPGraphSearchConfig;
use Drupal\semantic_connector\SemanticConnector;
use Drupal\smart_glossary\Entity\SmartGlossaryConfig;

/**
 * Configure global settings of the Semantic Connector module..
 */
class SemanticConnectorConfigForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'semantic_connector_config_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'semantic_connector.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('semantic_connector.settings');

    $form['semantic_connector_version_checking'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Service version checking'),
      '#default_value' => $config->get('version_checking'),
      '#description' => $this->t("Check for newer versions of PoolParty servers and GraphSearch servers"),
    );

    $form['semantic_connector_term_click_destinations'] = array(
      '#type' => 'table',
      '#title' => t('Term Click Destinations'),
      '#description' => t('Select which items should be displayed when clicking on a term.') . '<br />' . t('A whole destination type can be hidden by deselecting the "Show"-checkbox above, single destinations can be hidden inside their module\'s configuration page.'),
      '#header' => array(t('Destination name'), t('Show'), t('List title'), t('Weight')),
      '#empty' => t('There are no term click destinations available yet.'),
      '#tabledrag' => array(
        array(
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'term-click-destinations-order-weight',
        ),
      ),
      '#tree' => TRUE,
    );

    $destinations = SemanticConnector::getDestinations();
    foreach ($destinations as $destination_id => $destination) {
      // TableDrag: Mark the table row as draggable.
      $form['semantic_connector_term_click_destinations'][$destination_id]['#attributes']['class'][] = 'draggable';

      $form['semantic_connector_term_click_destinations'][$destination_id]['label'] = array(
        '#markup' => $destination['label'],
      );

      $form['semantic_connector_term_click_destinations'][$destination_id]['#weight'] = $destination['weight'];

      // Add a list of sub-destinations if required.
      $connection_list_items = '';
      if ($destination_id == 'smart_glossary_detail_page') {
        $configs = SmartGlossaryConfig::loadMultiple();
        /** @var SmartGlossaryConfig $config */
        foreach ($configs as $config) {
          $advanced_settings = $config->getAdvancedSettings();
          $connection_list_items .= '<li>' . Link::fromTextAndUrl($config->getTitle(), Url::fromRoute('entity.smart_glossary.edit_form', array('smart_glossary' => $config->id()), array('query' => array('destination' => 'admin/config/semantic-drupal/semantic-connector/config'))))
              ->toString() . ' <b>' . ((isset($advanced_settings['semantic_connection']) && isset($advanced_settings['semantic_connection']['show_in_destinations']) && !$advanced_settings['semantic_connection']['show_in_destinations']) ? 'deactivated' : 'activated') . '</b></li>';
        }
      }
      elseif ($destination_id == 'pp_graphsearch') {
        $config_sets = PPGraphSearchConfig::loadMultiple();
        /** @var PPGraphSearchConfig $config */
        foreach ($config_sets as $config) {
          $advanced_config = $config->getConfig();
          $connection_list_items .= '<li>' . Link::fromTextAndUrl($config->getTitle(), Url::fromRoute('entity.pp_graphsearch.edit_config_form', array('pp_graphsearch' => $config->id()), array('query' => array('destination' => 'admin/config/semantic-drupal/semantic-connector/config'))))
              ->toString() . ' <b>' . ((isset($advanced_config['semantic_connection']) && isset($advanced_config['semantic_connection']['show_in_destinations']) && !$advanced_config['semantic_connection']['show_in_destinations']) ? 'deactivated' : 'activated') . '</b></li>';
        }
      }
      if (!empty($connection_list_items)) {
        $form['semantic_connector_term_click_destinations'][$destination_id]['label']['#markup'] .= '<ul>' . $connection_list_items . '</ul>';
      }

      $form['semantic_connector_term_click_destinations'][$destination_id]['use'] = array(
        '#type' => 'checkbox',
        '#default_value' => $destination['use'],
      );

      $form['semantic_connector_term_click_destinations'][$destination_id]['list_title'] = array(
        '#type' => 'textfield',
        '#size' => 15,
        '#maxlength' => 255,
        '#default_value' => $destination['list_title'],
      );

      // This field is invisible, but contains sort info (weights).
      $form['semantic_connector_term_click_destinations'][$destination_id]['weight'] = array(
        '#type' => 'weight',
        // Weights from -255 to +255 are supported because of this delta.
        '#delta' => 255,
        '#title_display' => 'invisible',
        '#default_value' => $destination['weight'],
        '#attributes' => array('class' => array('term-click-destinations-order-weight')),
      );
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Retrieve the configuration
    $this->config('semantic_connector.settings')
      // Set the submitted configuration setting
      ->set('version_checking', $form_state->getValue('semantic_connector_version_checking'))
      // You can set multiple configurations at once by making
      // multiple calls to set()
      ->set('term_click_destinations', $form_state->getValue('semantic_connector_term_click_destinations'))
      ->save();

    parent::submitForm($form, $form_state);
  }
}
