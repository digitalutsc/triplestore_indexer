<?php

namespace Drupal\triplestore_indexer\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides a Delete taxonomy term in Triplestore action.
 *
 * @Action(
 *   id = "delete_taxonomy_term_in_triplestore_advancedqueue",
 *   label = @Translation("Delete taxonomy term in Triplestore [via Advanced Queue]"),
 *   type = "taxonomy_term",
 *   category = @Translation("Custom")
 * )
 *
 * @DCG
 * For a simple updating entity fields consider extending FieldUpdateActionBase.
 */
class DeleteTaxonomyTermInTriplestore extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function access($term, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\taxonomy\TermInterface $term */
    $access = $term->access('delete', $account, TRUE)
      ->andIf($term->name->access('edit', $account, TRUE));
    return $return_as_object ? $access : $access->isAllowed();
  }

  /**
   * {@inheritdoc}
   */
  public function execute($term = NULL) {
    /** @var \Drupal\taxonomy\TermInterface $term */
    queue_process($term, 'delete');
  }

}
