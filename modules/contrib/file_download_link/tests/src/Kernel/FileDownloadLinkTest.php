<?php

namespace Drupal\Tests\file_download_link\Kernel;

use Drupal\Core\Url;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Assert;

/**
 * Class for testing file_download_link formatter.
 *
 * @group file_download_link
 */
class FileDownloadLinkTest extends KernelTestBase {

  use FileDownloadLinkTestTrait;

  /**
   * The modules to load to run the test.
   *
   * @var array
   */
  public static $modules = [
    'field',
    'system',
    'user',
    'node',
    'file',
    'file_download_link',
    'image',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig(['system', 'field']);
    $this->installSchema('file', ['file_usage']);
    $this->installSchema('user', ['users_data']);
    $this->installEntitySchema('user');
    $this->installEntitySchema('file');
    $this->installEntitySchema('node');
    $this->installEntitySchema('node_type');
    $this->entity = $this->createTestEntity();
  }

  /**
   * Test the formatter using default settings for a file.
   */
  public function testFormatterFileDefault() {
    $render = $this->entity->field_file->view([
      'type' => 'file_download_link',
      'label' => 'hidden',
    ]);
    $file = $this->entity->field_file->referencedEntities()[0];
    $expected_render = [
      '#type' => 'link',
      '#title' => 'Download',
      '#url' => Url::fromUri(file_create_url('public://file.txt')),
      '#options' => [
        'attributes' => [
          'class' => [
            'file-download',
            'file-download-text',
            'file-download-plain',
          ],
          'target' => '_blank',
          'download' => TRUE,
        ],
      ],
      '#cache' => [
        'tags' => $file->getCacheTags(),
        'contexts' => [],
        'max-age' => -1,
      ],
    ];

    Assert::assertEquals($expected_render, $render[0]);
  }

  /**
   * Test the formatter using custom settings for a file.
   */
  public function testFormatterFileCustom() {
    $settings = [
      'link_text' => '',
      'link_title' => 'Click for file',
      'new_tab' => FALSE,
      'force_download' => FALSE,
      'custom_classes' => 'Howdy! p@rtner',
    ];
    $render = $this->entity->field_file->view([
      'type' => 'file_download_link',
      'label' => 'hidden',
      'settings' => $settings,
    ]);
    $file = $this->entity->field_file->referencedEntities()[0];
    $expected_render = [
      '#type' => 'link',
      '#title' => 'file.txt',
      '#url' => Url::fromUri(file_create_url('public://file.txt')),
      '#options' => [
        'attributes' => [
          'class' => [
            'file-download',
            'file-download-text',
            'file-download-plain',
            'Howdy',
            'prtner',
          ],
          'title' => 'Click for file',
        ],
      ],
      '#cache' => [
        'tags' => $file->getCacheTags(),
        'contexts' => [],
        'max-age' => -1,
      ],
    ];

    Assert::assertEquals($expected_render, $render[0]);
  }

  /**
   * Test the formatter using default settings for an image.
   */
  public function testFormatterImageDefault() {
    $render = $this->entity->field_image->view([
      'type' => 'file_download_link',
      'label' => 'hidden',
    ]);
    $file = $this->entity->field_image->referencedEntities()[0];
    $expected_render = [
      '#type' => 'link',
      '#title' => 'Download',
      '#url' => Url::fromUri(file_create_url('public://file.png')),
      '#options' => [
        'attributes' => [
          'class' => [
            'file-download',
            'file-download-image',
            'file-download-png',
          ],
          'target' => '_blank',
          'download' => TRUE,
        ],
      ],
      '#cache' => [
        'tags' => $file->getCacheTags(),
        'contexts' => [],
        'max-age' => -1,
      ],
    ];

    Assert::assertEquals($expected_render, $render[0]);
  }

  /**
   * Test the formatter using custom settings for an image.
   */
  public function testFormatterImageCustom() {
    $settings = [
      'link_text' => '',
      'link_title' => 'Click for image',
      'new_tab' => FALSE,
      'force_download' => FALSE,
      'custom_classes' => 'Howdy! p@rtner',
    ];
    $render = $this->entity->field_image->view([
      'type' => 'file_download_link',
      'label' => 'hidden',
      'settings' => $settings,
    ]);
    $file = $this->entity->field_image->referencedEntities()[0];
    $expected_render = [
      '#type' => 'link',
      '#title' => 'file.png',
      '#url' => Url::fromUri(file_create_url('public://file.png')),
      '#options' => [
        'attributes' => [
          'class' => [
            'file-download',
            'file-download-image',
            'file-download-png',
            'Howdy',
            'prtner',
          ],
          'title' => 'Click for image',
        ],
      ],
      '#cache' => [
        'tags' => $file->getCacheTags(),
        'contexts' => [],
        'max-age' => -1,
      ],
    ];

    Assert::assertEquals($expected_render, $render[0]);
  }

}
