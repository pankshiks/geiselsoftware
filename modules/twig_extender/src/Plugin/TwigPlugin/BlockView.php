<?php

namespace Drupal\twig_extender\Plugin\TwigPlugin;

use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Drupal\twig_extender\Plugin\Twig\TwigPluginBase;
use Drupal\block\Entity\Block;

/**
 * The plugin for check authenticated user.
 *
 * @TwigPlugin(
 *   id = "twig_extender_get_block",
 *   label = @Translation("Get a block"),
 *   type = "function",
 *   name = "block_view",
 *   function = "getBlock"
 * )
 */
class BlockView extends TwigPluginBase {

  /**
   * Implementation for render block.
   */
  public function getBlock($blockId) {
    $block = Block::load($blockId);
    if (!$block) {
      return;
    }

    // Inject runtime contexts.
    $block_plugin = $block->getPlugin();
    if ($block_plugin instanceof ContextAwarePluginInterface) {
      $contexts = \Drupal::service('context.repository')->getRuntimeContexts(array_values($block_plugin->getContextMapping()));
      \Drupal::service('context.handler')->applyContextMapping($block_plugin, $contexts);
    }

    $blockContent = \Drupal::entityTypeManager()
      ->getViewBuilder('block')
      ->view($block);
    return $blockContent;
  }

}
