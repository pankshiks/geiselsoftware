<?php

namespace Drupal\Tests\file_download_link\Kernel;

use Drupal\Core\Url;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use PHPUnit\Framework\Assert;

/**
 * Class for testing file_download_link_media formatter.
 *
 * @group file_download_link
 * @requires module token
 */
class FileDownloadLinkMediaTokenTest extends KernelTestBase {

  use FileDownloadLinkMediaTestTrait;
  use UserCreationTrait;

  /**
   * The modules to load to run the test.
   *
   * @var array
   */
  public static $modules = [
    'field',
    'system',
    'user',
    'media',
    'node',
    'file',
    'file_download_link',
    'file_download_link_media',
    'image',
    'token',
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
    $this->installEntitySchema('media');
    $this->installEntitySchema('media_type');
    $this->setUpCurrentUser(['uid' => 99], ['view media']);
    $this->media = $this->createTestMedia();
    $this->node = $this->createTestNode();
  }

  /**
   * Test the formatter using tokens.
   */
  public function testFormatterMediaTokens() {
    $settings = [
      'link_text' => '[media:name] ([file:extension])',
      'new_tab' => FALSE,
      'force_download' => FALSE,
    ];
    $render = $this->node->field_media->view([
      'type' => 'file_download_link_media',
      'label' => 'hidden',
      'settings' => $settings,
    ]);
    // Check that the thing that will get rendered looks right.
    $expected_deep_render = [
      '#type' => 'link',
      '#title' => 'Test Media (txt)',
      '#url' => Url::fromUri(file_create_url('public://file.txt')),
      '#options' => [
        'attributes' => [
          'class' => [
            'file-download',
            'file-download-text',
            'file-download-plain',
          ],
        ],
      ],
      '#cache' => [
        'tags' => ['file:1', 'media:1'],
        'contexts' => [],
        'max-age' => -1,
      ],
      '#attached' => [],
    ];
    Assert::assertEquals($expected_deep_render, $render[0][0], json_encode($render));

    // Check that the render array has the right cache data.
    $expected_cache = [
      'contexts' => [
        'user.permissions',
      ],
      'tags' => [
        'media:1',
      ],
      'max-age' => -1,
    ];
    Assert::assertEquals($expected_cache, $render[0]['#cache'], json_encode($render));
  }

  /**
   * Test the formatter using tokens.
   */
  public function testFormatterClassTokens() {
    $settings = [
      'link_text' => 'Download Media Now!',
      'new_tab' => FALSE,
      'force_download' => FALSE,
      'custom_classes' => 'static-class media-type-[media:bundle:target_id]',
    ];
    $render = $this->node->field_media->view([
      'type' => 'file_download_link_media',
      'label' => 'hidden',
      'settings' => $settings,
    ]);
    // Check that the thing that will get rendered looks right.
    $expected_deep_render = [
      '#type' => 'link',
      '#title' => 'Download Media Now!',
      '#url' => Url::fromUri(file_create_url('public://file.txt')),
      '#options' => [
        'attributes' => [
          'class' => [
            'file-download',
            'file-download-text',
            'file-download-plain',
            'static-class',
            'media-type-test-media',
          ],
        ],
      ],
      '#cache' => [
        'tags' => ['file:1', 'media:1'],
        'contexts' => [],
        'max-age' => -1,
      ],
      '#attached' => [],
    ];
    Assert::assertEquals($expected_deep_render, $render[0][0], json_encode($render));

    // Check that the render array has the right cache data.
    $expected_cache = [
      'contexts' => [
        'user.permissions',
      ],
      'tags' => [
        'media:1',
      ],
      'max-age' => -1,
    ];
    Assert::assertEquals($expected_cache, $render[0]['#cache'], json_encode($render));
  }

  /**
   * Test the formatter using tokens.
   */
  public function testFormatterTitleTokens() {
    $settings = [
      'link_text' => 'Download Media Now!',
      'link_title' => '[media:bundle:target_id]',
      'new_tab' => FALSE,
      'force_download' => FALSE,
    ];
    $render = $this->node->field_media->view([
      'type' => 'file_download_link_media',
      'label' => 'hidden',
      'settings' => $settings,
    ]);
    // Check that the thing that will get rendered looks right.
    $expected_deep_render = [
      '#type' => 'link',
      '#title' => 'Download Media Now!',
      '#url' => Url::fromUri(file_create_url('public://file.txt')),
      '#options' => [
        'attributes' => [
          'class' => [
            'file-download',
            'file-download-text',
            'file-download-plain',
          ],
          'title' => 'test_media',
        ],
      ],
      '#cache' => [
        'tags' => ['file:1', 'media:1'],
        'contexts' => [],
        'max-age' => -1,
      ],
      '#attached' => [],
    ];
    Assert::assertEquals($expected_deep_render, $render[0][0], json_encode($render));

    // Check that the render array has the right cache data.
    $expected_cache = [
      'contexts' => [
        'user.permissions',
      ],
      'tags' => [
        'media:1',
      ],
      'max-age' => -1,
    ];
    Assert::assertEquals($expected_cache, $render[0]['#cache'], json_encode($render));
  }

}
