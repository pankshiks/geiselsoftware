<?php

namespace Drupal\file_download_link_media\Plugin\Field\FieldFormatter;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\media\Entity\MediaType;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Extension\ModuleHandler;

/**
 * Plugin implementation of the 'file_download_link_media' formatter.
 *
 * @FieldFormatter(
 *   id = "file_download_link_media",
 *   label = @Translation("File Download Link"),
 *   field_types = {
 *     "entity_reference",
 *   }
 * )
 */
class FileDownloadLinkMedia extends EntityReferenceFormatterBase implements ContainerFactoryPluginInterface {

  /**
   * Module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected $moduleHandler;

  /**
   * Token entity mapper service.
   *
   * @var \Drupal\token\TokenEntityMapper|null
   */
  protected $tokenEntityMapper;

  /**
   * Constructs a FileDownloadLink object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\Core\Extension\ModuleHandler $module_handler
   *   Module handler service.
   * @param \Drupal\token\TokenEntityMapper|null $token_entity_mapper
   *   Token entity mapper if token module is installed. Otherwise NULL.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, ModuleHandler $module_handler, $token_entity_mapper) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->moduleHandler = $module_handler;
    $this->tokenEntityMapper = $token_entity_mapper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $module_handler = $container->get('module_handler');
    if ($module_handler->moduleExists('token')) {
      $token_entity_mapper = $container->get('token.entity_mapper');
    }
    else {
      $token_entity_mapper = NULL;
    }

    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $module_handler,
      $token_entity_mapper
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $options = parent::defaultSettings();
    $options['link_text'] = 'Download';
    $options['link_title'] = NULL;
    $options['new_tab'] = TRUE;
    $options['force_download'] = TRUE;
    $options['custom_classes'] = '';
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);
    $form['link_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Link Text'),
      '#default_value' => $this->getSetting('link_text'),
      '#description' => $this->t('This text is linked to the media source file. If left empty, the filename will be used.'),
    ];
    if ($this->moduleHandler->moduleExists('token')) {
      $form['default']['tokens'] = [
        '#theme' => 'token_tree_link',
        '#token_types' => [
          $this->tokenEntityMapper->getTokenTypeForEntityType('file'),
          $this->tokenEntityMapper->getTokenTypeForEntityType('media'),
        ],
      ];
      $form['token_example'] = [
        '#type' => 'details',
        '#title' => $this->t('Example Token'),
        '0' => [
          '#markup' => $this->getTokenExampleMarkup(),
        ],
      ];
    }
    else {
      $form['token_warning'] = [
        '#type' => 'container',
        '#markup' => $this->getTokenWarningMarkup(),
        '#attributes' => [
          'class' => ['messages messages--warning'],
        ],
      ];
    }
    $form['new_tab'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Open file in new tab'),
      '#default_value' => $this->getSetting('new_tab'),
    ];
    $form['force_download'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Force Download'),
      '#default_value' => $this->getSetting('force_download'),
      '#description' => $this->t('This adds the <i>download</i> attribute to the link, which works in many modern browsers.'),
    ];
    $form['link_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Link Title'),
      '#default_value' => $this->getSetting('link_title'),
      '#description' => $this->t('Many browsers show the title attribute in a tooltip.'),
    ];
    if ($this->moduleHandler->moduleExists('token')) {
      $form['tokens_2'] = [
        '#theme' => 'token_tree_link',
        '#token_types' => [
          $this->tokenEntityMapper->getTokenTypeForEntityType('file'),
          $this->tokenEntityMapper->getTokenTypeForEntityType($this->fieldDefinition->getTargetEntityTypeId()),
        ],
      ];
    }
    $form['custom_classes'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Custom CSS Classes'),
      '#default_value' => $this->getSetting('custom_classes'),
      '#description' => $this->t('Enter space-separated CSS classes to be added to the link.'),
    ];
    if ($this->moduleHandler->moduleExists('token')) {
      $form['tokens_3'] = [
        '#theme' => 'token_tree_link',
        '#token_types' => [
          $this->tokenEntityMapper->getTokenTypeForEntityType('file'),
          $this->tokenEntityMapper->getTokenTypeForEntityType('media'),
        ],
      ];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = $this->t('Link Text: @link_text', ['@link_text' => $this->getSetting('link_text')]);
    if ($this->getSetting('new_tab')) {
      $summary[] = $this->t('Open in new tab');
    }
    if ($this->getSetting('force_download')) {
      $summary[] = $this->t('Force download');
    }
    if ($this->getSetting('link_title')) {
      $summary[] = $this->t('Link Title: @link_title', ['@link_title' => $this->getSetting('link_title')]);
    }
    if ($this->getSetting('custom_classes')) {
      $summary[] = $this->t('Classes: @classes', ['@classes' => $this->getSetting('custom_classes')]);
    }
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $media) {
      $media_source = $media->getSource();
      $source_field = $media_source->getSourceFieldDefinition($media->bundle->entity)->getName();
      $elements[$delta] = $media->{$source_field}->view([
        'type' => 'file_download_link',
        'label' => 'hidden',
        'settings' => $this->getSettings(),
      ]);
      CacheableMetadata::createFromObject($media)->applyTo($elements[$delta]);
    }
    return $elements;
  }

  /**
   * A helper function for the config form.
   *
   * @return string
   *   An example link text with tokens.
   */
  protected function getExampleToken() {
    return "[media:name] ([file:size])";
  }

  /**
   * A helper function for the config form.
   *
   * @return string
   *   An example of what token could do for you.
   */
  protected function getTokenWarningMarkup() {
    return $this->t('<p>Enable the <a href="https://www.drupal.org/project/token" target="_blank">token module</a> to allow more flexible link text. For example, you would be able show the media name followed by the file size like this<code>@example</code></p>', ['@example' => $this->getExampleToken()]);
  }

  /**
   * A helper function for the config form.
   *
   * @return string
   *   An example of how to leverage token.
   */
  protected function getTokenExampleMarkup() {
    return $this->t('<p>You can show the media name followed by the file size like this:<code>@example</code></p>', ['@example' => $this->getExampleToken()]);
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    // This formatter is only available for entity types that reference
    // media items whose source field types are file and/or image.
    $target_type = $field_definition->getFieldStorageDefinition()->getSetting('target_type');
    if ($target_type != 'media') {
      return FALSE;
    }
    $media_bundles = isset($field_definition->getSettings()['handler_settings']['target_bundles']) ? $field_definition->getSettings()['handler_settings']['target_bundles'] : NULL;
    $media_types = MediaType::loadMultiple($media_bundles);
    foreach ($media_types as $media_type) {
      $source = $media_type->getSource();
      $allowed_field_types = $source->getPluginDefinition()['allowed_field_types'];
      if (!empty(array_diff($allowed_field_types, ['file', 'image']))) {
        // In here means something other than file or image is allowed.
        return FALSE;
      }
    }
    return TRUE;
  }

}
