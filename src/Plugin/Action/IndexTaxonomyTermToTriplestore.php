<?php

namespace Drupal\triplestore_indexer\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides an Index taxonomy term to Triplestore action.
 *
 * @Action(
 *   id = "index_taxonomy_term_to_triplestore_advancedqueue",
 *   label = @Translation("Index taxonomy term to Triplestore [via Advanced Queue]"),
 *   type = "taxonomy_term",
 *   category = @Translation("Custom")
 * )
 *
 * @DCG
 * For a simple updating entity fields consider extending FieldUpdateActionBase.
 */
class IndexTaxonomyTermToTriplestore extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function access($term, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\taxonomy\TermInterface $term */
    $access = $term->access('update', $account, TRUE)
      ->andIf($term->name->access('edit', $account, TRUE));
    return $return_as_object ? $access : $access->isAllowed();
  }

  /**
   * {@inheritdoc}
   */
  public function execute($term = NULL) {
    // Index the latest version of the term.
    /** @var \Drupal\taxonomy\TermInterface $term */
    queue_process($term, 'insert');
  }

}
