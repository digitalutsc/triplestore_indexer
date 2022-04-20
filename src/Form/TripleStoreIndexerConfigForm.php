<?php

namespace Drupal\triplestore_indexer\Form;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\advancedqueue\Entity\Queue;

/**
 * Class TripleStoreIndexerConfigForm definition.
 */
class TripleStoreIndexerConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'triplestore_indexer.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'triplestore_indexer_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('triplestore_indexer.settings');

    $form['container'] = [
      '#type' => 'container',
    ];

    $form['container']['triplestore-server-config'] = [
      '#type' => 'details',
      '#title' => 'General Settings',
      '#open' => TRUE,
    ];
    $form['container']['triplestore-server-config']['server_url'] = [
      '#type' => 'textfield',
      '#name' => 'server_url',
      '#title' => $this
        ->t('Server URL:'),
      '#required' => TRUE,
      '#default_value' => ($config->get("server_url") !== NULL) ? $config->get("server_url") : "",
      '#description' => $this->t('eg. http://localhost:8080/bigdata OR http://localhost:8080/blazegraph'),
    ];
    $form['container']['triplestore-server-config']['namespace'] = [
      '#type' => 'textfield',
      '#title' => $this
        ->t('Namespace:'),
      '#required' => TRUE,
      '#default_value' => ($config->get("namespace") !== NULL) ? $config->get("namespace") : "",
    ];

    $form['container']['triplestore-server-config']['select-auth-method'] = [
      '#type' => 'select',
      '#title' => $this->t('Drupal Authentication (enabled if Access Control with Group is enabled)'),
      '#options' => [
        '-1' => 'None',
        'digest' => 'Basic Authentication',
      ],
      '#ajax' => [
        'wrapper' => 'questions-fieldset-wrapper',
        'callback' => '::promptAuthCallback',
      ],
      '#default_value' => ($config->get("method_of_auth") !== NULL) ? $config->get("method_of_auth") : "",
    ];

    $form['container']['triplestore-server-config']['auth-config'] = [
      '#type' => 'details',
      '#title' => $this->t('Authentication information:'),
      '#open' => TRUE,
      '#attributes' => ['id' => 'questions-fieldset-wrapper'],
    ];
    $form['container']['triplestore-server-config']['auth-config']['question'] = [
      '#markup' => $this->t('None.'),
    ];

    $question_type = ($config->get("method-of-auth") !== NULL && !($form_state->hasValue('select-auth-method'))) ? $config->get("method-of-auth") : $form_state->getValue('select-auth-method');

    if (!empty($question_type) && $question_type !== -1) {
      unset($form['container']['triplestore-server-config']['auth-config']['question']);
      switch ($question_type) {
        case 'digest':
          $form['container']['triplestore-server-config']['auth-config']['admin_username'] = [
            '#type' => 'textfield',
            '#title' => $this
              ->t('Username:'),
            '#required' => TRUE,
            '#default_value' => ($config->get("admin_username") !== NULL) ? $config->get("admin_username") : "",
          ];
          $form['container']['triplestore-server-config']['auth-config']['admin_password'] = [
            '#type' => 'password',
            '#title' => $this
              ->t('Password:'),
            '#required' => TRUE,
            '#attributes' => [
              'value' => ($config->get('admin_password') !== NULL) ?
              $config->get('admin_password') : "",
              'readonly' => ($config->get('admin_password') !== NULL) ? 'readonly' : FALSE,
            ],
            '#description' => $this->t('To reset the password, change Method of authentication to None first.'),
          ];
          break;
        default:
          $form['container']['triplestore-server-config']['auth-config']['question'] = [
            '#markup' => $this->t('None.'),
          ];
          break;
      }
    }

    $form['container']['triplestore-server-config']['op-config'] = [
      '#type' => 'details',
      '#title' => $this->t('Advanced Queue Configuration'),
      '#open' => TRUE,
      '#attributes' => ['id' => 'op-fieldset-wrapper'],
    ];

    $queues = \Drupal::entityQuery('advancedqueue_queue')->execute();
    $form['container']['triplestore-server-config']['op-config']['advancedqueue_id'] = [
      '#type' => 'select',
      '#name' => 'advancedqueue_id',
      '#title' => $this->t('Select a queue:'),
      '#required' => TRUE,
      '#options' => $queues,
      '#default_value' => ($config->get("advancedqueue_id") !== NULL) ? $config->get("advancedqueue_id") : "default",
    ];

    $form['container']['triplestore-server-config']['op-config']['link-to-add-queue'] = [
      '#markup' => $this->t('To create a new queue, <a href="/admin/config/system/queues/add" target="_blank">Click here</a>'),
    ];

    $form['container']['triplestore-server-config']['op-config']['number-of-retries'] = [
      '#type' => 'number',
      '#title' => $this
        ->t('Number of retries:'),
      '#description' => $this->t("If a job is failed to run, set number of retries"),
      '#default_value' => ($config->get("aqj_max_retries") !== NULL) ? $config->get("aqj_max_retries") : 5,
    ];

    $form['container']['triplestore-server-config']['op-config']['retries-delay'] = [
      '#type' => 'number',
      '#title' => $this
        ->t('Retry Delay (in seconds):'),
      '#description' => $this->t("Set the delay time (in seconds) for a job to re-run each time."),
      '#default_value' => ($config->get("aqj_retry_delay") !== NULL) ? $config->get("aqj_retry_delay") : 100,
    ];

    $form['configuration'] = [
      '#type' => 'vertical_tabs',
    ];
    $form['configuration']['#tree'] = TRUE;

    $form['content-type'] = [
      '#type' => 'details',
      '#title' => $this
        ->t('Condition: Node Bundle'),
      '#group' => 'configuration',
    ];

    // Pull list of exsiting content types of the site.
    $content_types = \Drupal::entityTypeManager()
      ->getStorage('node_type')
      ->loadMultiple();
    $options_contentypes = [];
    foreach ($content_types as $ct) {
      $options_contentypes[$ct->id()] = $ct->label();
    }

    $form['content-type']['select-content-types'] = [
      '#type' => 'checkboxes',
      '#title' => t('Select which content type(s) to be indexed:'),
      '#options' => $options_contentypes,
      '#default_value' => ($config->get("content-type-to-index") !== NULL) ? array_keys(array_filter($config->get('content-type-to-index'))) : [],
    ];

    $form['submit-save-config'] = [
      '#type' => 'submit',
      '#name' => "submit-save-server-config",
      '#value' => "Save Configuration",
      '#attributes' => ['class' => ["button button--primary"]],
    ];

    return $form;
  }

  /**
   * Validating form_state.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Validate Server URL.
    try {
      $client = \Drupal::service('http_client');
      // Get articles from the API.
      $response = $client->request('GET', $form_state->getValues()['server_url']);

      if ($response->getStatusCode() !== 200) {
        $form_state->setErrorByName("server_url",
          t('Your Server URL is not valid, please check it again.'));
      }
    }
    catch (\Exception $e) {
      $form_state->setErrorByName("server_url",
        new FormattableMarkup('Your Server URL is not valid, please check it again. <strong>Error message:</strong> ' . $e->getMessage(), []));
    }

    // Validate if entering a valid machine name of queue.
    $q = Queue::load($form_state->getValues()['advancedqueue_id']);
    if (!isset($q)) {
      $form_state->setErrorByName("advancedqueue_id",
        new FormattableMarkup('This queue\'s machine name "' . $form_state->getValues()['advancedqueue_id'] . '" is not valid, please verify it by <a href="/admin/config/system/queues">clicking here</a>.', []));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $configFactory = $this->configFactory->getEditable('triplestore_indexer.settings');

    $configFactory->set('server_url', $form_state->getValues()['server_url'])
      ->set('namespace', $form_state->getValues()['namespace'])
      ->set('method_of_auth', $form_state->getValues()['select-auth-method']);

    $configFactory->set("aqj_max_retries", $form_state->getValues()['number-of-retries']);
    $configFactory->set("aqj_retry_delay", $form_state->getValues()['retries-delay']);

    switch ($form_state->getValues()['select-auth-method']) {
      case 'digest':
        $configFactory->set('admin_username', $form_state->getValues()['admin_username']);
        if ($configFactory->get('admin_password') === NULL) {
          $configFactory->set('admin_password', base64_encode($form_state->getValues()['admin_password']));
        }
        break;
      default:
        $configFactory->set('admin_username', NULL);
        $configFactory->set('admin_password', NULL);
        break;
    }

    $configFactory->set('advancedqueue_id', $form_state->getValues()['advancedqueue_id']);
    $configFactory->set('content_type_to_index', $form_state->getValues()['select-content-types']);
    $configFactory->save();

    parent::submitForm($form, $form_state);

  }

  /**
   * For Ajax callback for depending dropdown list.
   */
  public function promptAuthCallback(array $form, FormStateInterface $form_state) {
    return $form['container']['triplestore-server-config']['auth-config'];
  }

  /**
   * For Ajax callback for depending dropdown list.
   */
  public function promptOpCallback(array $form, FormStateInterface $form_state) {
    return $form['container']['triplestore-server-config']['op-config'];
  }
}
