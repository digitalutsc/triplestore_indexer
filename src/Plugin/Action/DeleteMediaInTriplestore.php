<?php

namespace Drupal\triplestore_indexer\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides a Delete media in Triplestore action.
 *
 * @Action(
 *   id = "delete_media_in_triplestore_advancedqueue",
 *   label = @Translation("Delete media in Triplestore [via Advanced Queue]"),
 *   type = "media",
 *   category = @Translation("Custom")
 * )
 *
 * @DCG
 * For a simple updating entity fields consider extending FieldUpdateActionBase.
 */
class DeleteMediaInTriplestore extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function access($media, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\media\MediaInterface $media */
    $access = $media->access('delete', $account, TRUE)
      ->andIf($media->name->access('edit', $account, TRUE));
    return $return_as_object ? $access : $access->isAllowed();
  }

  /**
   * {@inheritdoc}
   */
  public function execute($media = NULL) {
    /** @var \Drupal\media\MediaInterface $media */
    queue_process($media, 'delete');
  }

}
