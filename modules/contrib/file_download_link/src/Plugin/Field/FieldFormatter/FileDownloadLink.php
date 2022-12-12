<?php

namespace Drupal\file_download_link\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Utility\Token;
use Drupal\file\Plugin\Field\FieldFormatter\FileFormatterBase;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Html;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Extension\ModuleHandler;

/**
 * Plugin implementation of the 'file_download_link' formatter.
 *
 * @FieldFormatter(
 *   id = "file_download_link",
 *   label = @Translation("File Download Link"),
 *   field_types = {
 *     "file",
 *     "image",
 *   }
 * )
 */
class FileDownloadLink extends FileFormatterBase implements ContainerFactoryPluginInterface {

  /**
   * Token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

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
   * @param \Drupal\Core\Utility\Token $token
   *   Token service.
   * @param \Drupal\Core\Extension\ModuleHandler $module_handler
   *   Module handler service.
   * @param \Drupal\token\TokenEntityMapper|null $token_entity_mapper
   *   Token entity mapper if token module is installed. Otherwise NULL.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, Token $token, ModuleHandler $module_handler, $token_entity_mapper) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->token = $token;
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
      $container->get('token'),
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
    if ($this->fieldDefinition->getTargetEntityTypeId() == 'media') {
      if (!$this->moduleHandler->moduleExists('file_download_link_media')) {
        $form['media_warning'] = [
          '#type' => 'container',
          '#markup' => $this->t("Did you know the file_download_link_media module allows you render a Media reference field as a link to the Media's source file or image? Consider enabling the module if that sounds helpful."),
          '#attributes' => [
            'class' => ['messages messages--warning'],
          ],
        ];
      }
    }
    $form['link_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Link Text'),
      '#default_value' => $this->getSetting('link_text'),
      '#description' => $this->t('This text is linked to the file. If left empty, the filename will be used.'),
    ];
    if ($this->moduleHandler->moduleExists('token')) {
      $form['tokens'] = [
        '#theme' => 'token_tree_link',
        '#token_types' => [
          $this->tokenEntityMapper->getTokenTypeForEntityType('file'),
          $this->tokenEntityMapper->getTokenTypeForEntityType($this->fieldDefinition->getTargetEntityTypeId()),
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
          $this->tokenEntityMapper->getTokenTypeForEntityType($this->fieldDefinition->getTargetEntityTypeId()),
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

    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $file) {

      // Options for the link, like classes.
      $mime_type_explosion = explode("/", $file->getMimeType());
      $file_type = reset($mime_type_explosion);
      $file_extension = end($mime_type_explosion);
      $options = [
        'attributes' => [
          'class' => [
            'file-download',
            'file-download-' . $file_type,
            'file-download-' . $file_extension,
          ],
        ],
      ];
      if ($this->getSetting('new_tab')) {
        $options['attributes']['target'] = '_blank';
      }
      if ($this->getSetting('force_download')) {
        $options['attributes']['download'] = TRUE;
      }
      if ($this->getSetting('link_title')) {
        $options['attributes']['title'] = $this->getSetting('link_title');
      }

      // Make the render array.
      $elements[$delta] = [
        '#type' => 'link',
        '#title' => $this->getSetting('link_text'),
        '#url' => Url::fromUri(file_create_url($file->getFileUri())),
        '#options' => $options,
        '#cache' => [
          'tags' => $file->getCacheTags(),
        ],
      ];

      // Deal with tokens for the text, title, and classes.
      if ($this->moduleHandler->moduleExists('token')) {
        $data = [];
        $data[$this->tokenEntityMapper->getTokenTypeForEntityType($file->getEntityTypeId())] = $file;
        $entity = $items->getEntity();
        $field = $this->fieldDefinition->getName();
        $entity_token_type = $this->tokenEntityMapper->getTokenTypeForEntityType($entity->getEntityTypeId());
        $data[$entity_token_type] = $entity;
        $bubbleable_metadata = new BubbleableMetadata();

        // Link Text.
        if ($this->getSetting('link_text')) {
          $text = $this->getSetting('link_text');
          $text = $this->addDeltaToTokens($text, $delta, $entity_token_type, $field);
          $text = $this->token->replace($text, $data, ['langcode' => $langcode, 'clear' => TRUE], $bubbleable_metadata);
          // Token encodes & and ' e.g. as &amp; and &#39;.
          $text = Html::decodeEntities($text);
          $elements[$delta]['#title'] = $text;
        }

        // Link title (attribute).
        if ($this->getSetting('link_title')) {
          $title = $this->getSetting('link_title');
          $title = $this->addDeltaToTokens($title, $delta, $entity_token_type, $field);
          $title = $this->token->replace($title, $data, ['langcode' => $langcode, 'clear' => TRUE], $bubbleable_metadata);
          $title = Html::decodeEntities($title);
          if ($title) {
            $elements[$delta]['#options']['attributes']['title'] = $title;
          }
          else {
            unset($elements[$delta]['#options']['attributes']['title']);
          }
        }

        // Custom classes.
        if ($this->getSetting('custom_classes')) {
          $custom_classes = $this->getSetting('custom_classes');
          $custom_classes = $this->addDeltaToTokens($custom_classes, $delta, $entity_token_type, $field);
          $custom_classes = $this->token->replace($custom_classes, $data, ['langcode' => $langcode, 'clear' => TRUE], $bubbleable_metadata);
          $custom_classes = Html::decodeEntities($custom_classes);
          // Custom classes are added to render array later.
        }

        // Next line is important. See https://www.drupal.org/node/2528662.
        $bubbleable_metadata->applyTo($elements[$delta]);
      }

      // An empty title is replaced by filename.
      // Put this after token stuff to guard against cleared tokens.
      if (empty($elements[$delta]['#title'])) {
        $elements[$delta]['#title'] = $file->getFilename();
      }

      // Custom classes are added now.
      if ($this->getSetting('custom_classes')) {
        if (!isset($custom_classes)) {
          // $custom_classes is set if tokens have been replaced.
          $custom_classes = $this->getSetting('custom_classes');
        }
        if (!empty($custom_classes)) {
          $classes = explode(" ", $custom_classes);
          foreach ($classes as $class) {
            $elements[$delta]['#options']['attributes']['class'][] = Html::cleanCssIdentifier($class);
          }
        }
      }
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
    $entity_type = $this->fieldDefinition->getTargetEntityTypeId();
    $field = $this->fieldDefinition->getName();
    $type = $this->fieldDefinition->getType();
    // If token is on, let's be extra sure about our token name.
    if ($this->moduleHandler->moduleExists('token')) {
      $entity_type = $this->tokenEntityMapper->getTokenTypeForEntityType($entity_type);
    }
    if ($type == 'file') {
      return "[$entity_type:$field:description] ([file:size])";
    }
    else {
      return "[$entity_type:$field:alt] ([file:size])";
    }
  }

  /**
   * A helper function for the config form.
   *
   * @return string
   *   An example of what token could do for you.
   */
  protected function getTokenWarningMarkup() {
    $type = $this->fieldDefinition->getType();
    if ($type == 'file') {
      return $this->t('<p>Enable the <a href="https://www.drupal.org/project/token\" target="_blank\">token module</a> to allow more flexible link text. For example, you would be able show the file description followed by the file size like this:<code>@example</code></p>', ['@example' => $this->getExampleToken()]);
    }
    else {
      return $this->t('<p>Enable the <a href="https://www.drupal.org/project/token" target="_blank">token module</a> to allow more flexible link text. For example, you would be able show the alt text followed by the file size like this:<code>@example</code></p>', ['@example' => $this->getExampleToken()]);
    }
  }

  /**
   * A helper function for the config form.
   *
   * @return string
   *   An example of how to leverage token.
   */
  protected function getTokenExampleMarkup() {
    $type = $this->fieldDefinition->getType();
    $delta_help = '';
    if ($this->fieldDefinition->getFieldStorageDefinition()->getCardinality() != 1) {
      $field = $this->fieldDefinition->getName();
      $delta_help = $this->t('<p>Note that you do not need to indicate a delta value for the @field token. The appropriate delta is used automatically.</p>', ['@field' => $field]);
    }
    if ($type == 'file') {
      return $this->t('<p>You can show the file description followed by the file size like this:<code>@example</code></p>@delta_help', ['@example' => $this->getExampleToken(), '@delta_help' => $delta_help]);
    }
    else {
      return $this->t('<p>You can show the alt text followed by the file size like this:<code>@example</code></p>@delta_help', ['@example' => $this->getExampleToken(), '@delta_help' => $delta_help]);
    }
  }

  /**
   * A helper function to handle delta in tokens.
   *
   * @param string $string
   *   The string that might have tokens.
   * @param int $delta
   *   The delta to add to certain tokens.
   * @param string $entity_token_type
   *   Entity token type, like node or media.
   * @param string $field
   *   Field name of this field being rendered.
   *
   * @return string
   *   The string with delta value added to certain tokens.
   */
  protected function addDeltaToTokens($string, $delta, $entity_token_type, $field) {
    // We do two str_replace calls to save us from confusing regex.
    // First add delta to middle of a "chain".
    $string = str_replace("[$entity_token_type:$field:", "[$entity_token_type:$field:$delta:", $string);
    // Then add delta if token ends at this field.
    $string = str_replace("[$entity_token_type:$field]", "[$entity_token_type:$field:$delta]", $string);
    return $string;
  }

}
