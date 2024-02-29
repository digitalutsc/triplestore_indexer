<?php

namespace Drupal\triplestore_indexer;

use Drupal\context\ContextReactionPluginBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Triplestore Actions context reaction base.
 */
class ReactionBase extends ContextReactionPluginBase {

  /**
   * Action storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $actionStorage;

  /**
   * Action IDs to display as options.
   *
   * @var array
   */
  protected $actionIds;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityStorageInterface $action_storage, $action_ids) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->actionStorage = $action_storage;
    $this->actionIds = $action_ids;
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    return $this->t('Perform a pre-configured action.');
  }

  /**
   * {@inheritdoc}
   */
  public function execute(EntityInterface $entity = NULL) {
    $config = $this->getConfiguration();
    $entityType = $entity->getEntityTypeId();
    $action_id = $config['actions'];
    if (str_contains($action_id, $entityType)) {
      $action = $this->actionStorage->load($action_id);
      if ($action) {
        $action->execute([$entity]);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $actions = $this->actionStorage->loadMultiple($this->actionIds);
    foreach ($actions as $action) {
      $options[ucfirst($action->getType())][$action->id()] = $action->label();
    }
    $config = $this->getConfiguration();

    $form['actions'] = [
      '#title' => $this->t('Triplestore Actions'),
      '#description' => $this->t('Pre-configured actions to execute.'),
      '#type' => 'select',
      '#options' => $options,
      '#default_value' => $config['actions'] ?? '',
      '#size' => 15,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->setConfiguration(['actions' => $form_state->getValue('actions')]);
  }

}
