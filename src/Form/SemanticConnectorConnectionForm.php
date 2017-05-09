<?php

/**
 * @file
 * Contains \Drupal\semantic_connector\Form\SemanticConnectorConnectionForm.
 */

namespace Drupal\semantic_connector\Form;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\semantic_connector\Entity\SemanticConnectorConnection;
use Drupal\semantic_connector\SemanticConnector;

class SemanticConnectorConnectionForm extends EntityForm {
  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    /** @var \Drupal\semantic_connector\Entity\SemanticConnectorConnectionInterface $entity */
    $entity = $this->entity;

    $form['title'] = array(
      '#type' => 'textfield',
      '#title' => t('Server title'),
      '#description' => t('A short title for the connection.'),
      '#size' => 35,
      '#maxlength' => 60,
      '#required' => TRUE,
      '#default_value' => $entity->get('title'),
    );

    $form['url'] = array(
      '#type' => 'textfield',
      '#title' => t('URL'),
      '#description' => t('URL of the connection.'),
      '#size' => 35,
      '#maxlength' => 255,
      '#required' => TRUE,
      '#default_value' => $entity->get('url'),
    );

    $credentials = $entity->getCredentials();
    $form['credentials'] = array(
      '#type' => 'fieldset',
      '#title' => t('Credentials'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    );
    $form['credentials']['username'] = array(
      '#type' => 'textfield',
      '#title' => t('Username'),
      '#description' => t('Name of a user for the credentials.'),
      '#size' => 35,
      '#maxlength' => 60,
      '#default_value' => $credentials['username'],
    );
    $form['credentials']['password'] = array(
      '#type' => 'textfield',
      '#title' => t('Password'),
      '#description' => t('Password of a user for the credentials.'),
      '#size' => 35,
      '#maxlength' => 128,
      '#default_value' => $credentials['password'],
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Check if the URL is valid.
    if (!UrlHelper::isValid($form_state->getValue('url'), TRUE)) {
      $form_state->setErrorByName('url', $this->t('A valid URL has to be given.'));
    }
  }
  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var SemanticConnectorConnection $entity */
    $entity = $this->entity;
    $is_new = !$entity->getOriginalId();
    if ($is_new) {
      // Configuration entities need an ID manually set.
      $entity->set('id', SemanticConnector::createUniqueEntityMachineName($entity->getEntityTypeId(), $entity->get('title')));
      drupal_set_message(t('Connection %title has been created.', array('%title' => $entity->get('title'))));
    }
    else {
      drupal_set_message(t('Updated connection %title.',
        array('%title' => $entity->get('title'))));
    }

    $entity->set('credentials', array(
      'username' => $form_state->getValue('username'),
      'password' => $form_state->getValue('password'),
    ));
    $entity->set('config', array());
    $entity->save();

    $form_state->setRedirectUrl(Url::fromRoute('semantic_connector.overview'));
  }
}
