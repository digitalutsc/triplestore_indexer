<?php

namespace Drupal\triplestore_indexer;

use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Context\ContextHandlerInterface;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\media\MediaInterface;
use Drupal\node\NodeInterface;
use Drupal\taxonomy\TermInterface;
use Drupal\triplestore_indexer\ContextProvider\MediaContextProvider;
use Drupal\triplestore_indexer\ContextProvider\NodeContextProvider;
use Drupal\triplestore_indexer\ContextProvider\TermContextProvider;
use Drupal\triplestore_indexer\TriplestoreContextManager;

/**
 * Utility functions for firing off context reactions.
 */
class TriplestoreContextUtils {
  /**
   * Context manager.
   *
   * @var \Drupal\triplestore_indexer\TriplestoreContextManager
   */
  protected $contextManager;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Plugin\Context\ContextRepositoryInterface $contextRepository
   *   Context repository.
   * @param \Drupal\Core\Plugin\Context\ContextHandlerInterface $contextHandler
   *   Context handler.
   * @param \Drupal\Core\Entity\EntityFormBuilderInterface $entityFormBuilder
   *   Entity Form Builder.
   * @param \Drupal\Core\Theme\ThemeManagerInterface $themeManager
   *   Theme manager.
   * @param \Drupal\Core\Routing\RouteMatchInterface $currentRouteMatch
   *   Route match.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    ContextRepositoryInterface $contextRepository,
    ContextHandlerInterface $contextHandler,
    EntityFormBuilderInterface $entityFormBuilder,
    ThemeManagerInterface $themeManager,
    RouteMatchInterface $currentRouteMatch
  ) {
    $this->contextManager = new TriplestoreContextManager(
      $entityTypeManager,
      $contextRepository,
      $contextHandler,
      $entityFormBuilder,
      $themeManager,
      $currentRouteMatch
    );
  }

  /**
   * Executes context reactions for a Node.
   *
   * @param string $reaction_type
   *   Reaction type.
   * @param \Drupal\node\NodeInterface $node
   *   Node to evaluate contexts and pass to reaction.
   */
  public function executeNodeReactions($reaction_type, NodeInterface $node) {
    $provider = new NodeContextProvider($node);
    $provided = $provider->getRuntimeContexts([]);
    $this->contextManager->evaluateContexts($provided);
    foreach ($this->contextManager->getActiveReactions($reaction_type) as $reaction) {
      $reaction->execute($node);
    }
  }

  /**
   * Executes context reactions for a Taxonomy Term.
   *
   * @param string $reaction_type
   *   Reaction type.
   * @param \Drupal\taxonomy\TermInterface $term
   *   Taxonomy term to evaluate contexts and pass to reaction.
   */
  public function executeTermReactions($reaction_type, TermInterface $term) {
    $provider = new TermContextProvider($term);
    $provided = $provider->getRuntimeContexts([]);
    $this->contextManager->evaluateContexts($provided);
    foreach ($this->contextManager->getActiveReactions($reaction_type) as $reaction) {
      $reaction->execute($term);
    }
  }

  /**
   * Executes context reactions for a Media.
   *
   * @param string $reaction_type
   *   Reaction type.
   * @param \Drupal\media\MediaInterface $media
   *   Media to evaluate contexts and pass to reaction.
   */
  public function executeMediaReactions($reaction_type, MediaInterface $media) {
    $provider = new MediaContextProvider($media);
    $provided = $provider->getRuntimeContexts([]);
    $this->contextManager->evaluateContexts($provided);
    foreach ($this->contextManager->getActiveReactions($reaction_type) as $reaction) {
      $reaction->execute($media);
    }
  }

}
