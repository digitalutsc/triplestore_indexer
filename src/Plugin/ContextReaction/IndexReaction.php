<?php

namespace Drupal\triplestore_indexer\Plugin\ContextReaction;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\triplestore_indexer\ReactionBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Delete reaction.
 *
 * @ContextReaction(
 *   id = "triplestore_index_reaction",
 *   label = @Translation("Triplestore Index Reaction")
 * )
 */
class IndexReaction extends ReactionBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function summary() {
    return $this->t('Pre-configure a triplestore index action.');
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
        'index_node_to_triplestore_advancedqueue',
        'index_media_to_triplestore_advancedqueue',
        'index_taxonomy_term_to_triplestore_advancedqueue',
      ]
    );
  }

}
