<?php

namespace Drupal\twig_extender\Plugin\Twig;

use Drupal\Core\Plugin\PluginBase;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Provides a base class for Twig plugins plugins.
 */
class TwigPluginBase extends PluginBase implements TwigExtensionInterface {

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return $this->pluginDefinition['type'];
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->pluginDefinition['name'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFunction() {
    return $this->pluginDefinition['function'];
  }

  /**
   * {@inheritdoc}
   */
  public function register() {
    if ($this->getType() == 'function') {
      return new TwigFunction($this->getName(), [$this, $this->getFunction()]);
    }
    return new TwigFilter($this->getName(), [$this, $this->getFunction()]);
  }

}
