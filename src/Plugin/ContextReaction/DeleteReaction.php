<?php

namespace Drupal\triplestore_indexer\Plugin\ContextReaction;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\triplestore_indexer\ReactionBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Delete reaction.
 *
 * @ContextReaction(
 *   id = "triplestore_delete_reaction",
 *   label = @Translation("Triplestore Delete Reaction")
 * )
 */
class DeleteReaction extends ReactionBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function summary() {
    return $this->t('Pre-configure a triplestore delete action.');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')->getStorage('action'),
      [
        'delete_node_in_triplestore_advancedqueue',
        'delete_media_in_triplestore_advancedqueue',
        'delete_taxonomy_term_in_triplestore_advancedqueue',
      ]
    );
  }

}
