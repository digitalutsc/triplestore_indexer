<?php

namespace Drupal\triplestore_indexer\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides an Index media to Triplestore action.
 *
 * @Action(
 *   id = "index_media_to_triplestore_advancedqueue",
 *   label = @Translation("Index media to Triplestore [via Advanced Queue]"),
 *   type = "media",
 *   category = @Translation("Custom")
 * )
 *
 * @DCG
 * For a simple updating entity fields consider extending FieldUpdateActionBase.
 */
class IndexMediaToTriplestore extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function access($media, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\media\MediaInterface $media */
    $access = $media->access('update', $account, TRUE)
      ->andIf($media->name->access('edit', $account, TRUE));
    return $return_as_object ? $access : $access->isAllowed();
  }

  /**
   * {@inheritdoc}
   */
  public function execute($media = NULL) {
    // Index the latest version of the media.
    /** @var \Drupal\media\MediaInterface $media */
    queue_process($media, 'insert');
  }

}
