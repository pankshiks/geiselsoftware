<?php

namespace Drupal\twig_extender\Plugin\TwigPlugin;

use Drupal\twig_extender\Plugin\Twig\TwigPluginBase;

/**
 * The plugin for check authenticated user.
 *
 * @TwigPlugin(
 *   id = "twig_extender_as",
 *   label = @Translation("Render with template"),
 *   type = "filter",
 *   name = "as",
 *   function = "as"
 * )
 */
class RenderAs extends TwigPluginBase {

  /**
   * Implementation for render block.
   */
  public function as($element, $suggestion) {
    if (!empty($element['#theme'])) {
      if (!is_iterable($element['#theme'])) {
        $element['#theme'] = [
          $element['#theme'],
        ];
      }
      $base_theme_hook = end($element['#theme']);

      $suggestion = str_replace('-', '_', $suggestion);
      array_unshift($element['#theme'], "{$base_theme_hook}__$suggestion");
    }
    return $element;
  }

}
