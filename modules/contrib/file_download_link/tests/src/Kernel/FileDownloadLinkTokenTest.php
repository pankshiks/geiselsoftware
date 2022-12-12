<?php

namespace Drupal\Tests\file_download_link\Kernel;

use Drupal\Core\Url;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use PHPUnit\Framework\Assert;

/**
 * Class for testing file_download_link formatter with tokens.
 *
 * @group file_download_link
 * @requires module token
 */
class FileDownloadLinkTokenTest extends KernelTestBase {

  use FileDownloadLinkTestTrait;
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
    'node',
    'file',
    'token',
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
    $this->setUpCurrentUser(['uid' => 99]);
    $this->installEntitySchema('user');
    $this->installEntitySchema('file');
    $this->installEntitySchema('node');
    $this->installEntitySchema('node_type');
    $this->entity = $this->createTestEntity();
  }

  /**
   * Test the formatter using a token from the file.
   */
  public function testFormatterFileTokens() {
    $settings = [
      'link_text' => 'The extension is [file:extension]',
      'new_tab' => FALSE,
      'force_download' => FALSE,
    ];
    $render = $this->entity->field_file->view([
      'type' => 'file_download_link',
      'label' => 'hidden',
      'settings' => $settings,
    ]);
    $file = $this->entity->field_file->referencedEntities()[0];
    $expected_render = [
      '#type' => 'link',
      '#title' => 'The extension is txt',
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
        'tags' => array_merge($file->getCacheTags(), $this->entity->getCacheTags()),
        'contexts' => [],
        'max-age' => -1,
      ],
      '#attached' => [],
    ];

    Assert::assertEquals($expected_render, $render[0]);
  }

  /**
   * Test the formatter using a token from the node.
   */
  public function testFormatterNodeTokens() {
    $settings = [
      'link_text' => 'The image width is [node:field_image:width]',
      'new_tab' => FALSE,
      'force_download' => FALSE,
    ];
    $render = $this->entity->field_image->view([
      'type' => 'file_download_link',
      'label' => 'hidden',
      'settings' => $settings,
    ]);
    $file = $this->entity->field_image->referencedEntities()[0];
    $expected_render = [
      '#type' => 'link',
      '#title' => 'The image width is 88',
      '#url' => Url::fromUri(file_create_url('public://file.png')),
      '#options' => [
        'attributes' => [
          'class' => [
            'file-download',
            'file-download-image',
            'file-download-png',
          ],
        ],
      ],
      '#cache' => [
        'tags' => array_merge($file->getCacheTags(), $this->entity->getCacheTags()),
        'contexts' => [],
        'max-age' => -1,
      ],
      '#attached' => [],
    ];

    Assert::assertEquals($expected_render, $render[0]);
  }

  /**
   * Test the formatter using a token from the image.
   */
  public function testFormatterImageTokens() {
    $settings = [
      'link_text' => 'The extension is [file:extension]',
      'new_tab' => FALSE,
      'force_download' => FALSE,
    ];
    $render = $this->entity->field_image->view([
      'type' => 'file_download_link',
      'label' => 'hidden',
      'settings' => $settings,
    ]);
    $file = $this->entity->field_image->referencedEntities()[0];
    $expected_render = [
      '#type' => 'link',
      '#title' => 'The extension is png',
      '#url' => Url::fromUri(file_create_url('public://file.png')),
      '#options' => [
        'attributes' => [
          'class' => [
            'file-download',
            'file-download-image',
            'file-download-png',
          ],
        ],
      ],
      '#cache' => [
        'tags' => array_merge($file->getCacheTags(), $this->entity->getCacheTags()),
        'contexts' => [],
        'max-age' => -1,
      ],
      '#attached' => [],
    ];

    Assert::assertEquals($expected_render, $render[0]);
  }

  /**
   * Test the formatter using a token from the image.
   */
  public function testFormatterTitleTokens() {
    $settings = [
      'link_text' => 'Testing tokens in title',
      'link_title' => 'Download [file:extension]',
      'new_tab' => FALSE,
      'force_download' => FALSE,
    ];
    $render = $this->entity->field_image->view([
      'type' => 'file_download_link',
      'label' => 'hidden',
      'settings' => $settings,
    ]);
    $file = $this->entity->field_image->referencedEntities()[0];
    $expected_render = [
      '#type' => 'link',
      '#title' => 'Testing tokens in title',
      '#url' => Url::fromUri(file_create_url('public://file.png')),
      '#options' => [
        'attributes' => [
          'class' => [
            'file-download',
            'file-download-image',
            'file-download-png',
          ],
          'title' => 'Download png',
        ],
      ],
      '#cache' => [
        'tags' => array_merge($file->getCacheTags(), $this->entity->getCacheTags()),
        'contexts' => [],
        'max-age' => -1,
      ],
      '#attached' => [],
    ];

    Assert::assertEquals($expected_render, $render[0]);
  }

  /**
   * Test the formatter using a token from the image.
   */
  public function testFormatterClassTokens() {
    $settings = [
      'link_text' => 'Testing tokens in classes',
      'new_tab' => FALSE,
      'force_download' => FALSE,
      'custom_classes' => 'link-[file:mime] static-class',
    ];
    $render = $this->entity->field_image->view([
      'type' => 'file_download_link',
      'label' => 'hidden',
      'settings' => $settings,
    ]);
    $file = $this->entity->field_image->referencedEntities()[0];
    $expected_render = [
      '#type' => 'link',
      '#title' => 'Testing tokens in classes',
      '#url' => Url::fromUri(file_create_url('public://file.png')),
      '#options' => [
        'attributes' => [
          'class' => [
            'file-download',
            'file-download-image',
            'file-download-png',
            'link-image-png',
            'static-class',
          ],
        ],
      ],
      '#cache' => [
        'tags' => array_merge($file->getCacheTags(), $this->entity->getCacheTags()),
        'contexts' => [],
        'max-age' => -1,
      ],
      '#attached' => [],
    ];

    Assert::assertEquals($expected_render, $render[0]);
  }

  /**
   * Test that tokens work right for cardinality != 1.
   */
  public function testFormatterDeltaTokens() {
    $settings = [
      'link_text' => '[node:field_image:alt]',
      'new_tab' => FALSE,
      'force_download' => FALSE,
    ];
    $render = $this->entity->field_image->view([
      'type' => 'file_download_link',
      'label' => 'hidden',
      'settings' => $settings,
    ]);
    $file = $this->entity->field_image->referencedEntities()[0];
    $expected_delta_0 = [
      '#type' => 'link',
      '#title' => 'This alt text is for the first image.',
      '#url' => Url::fromUri(file_create_url('public://file.png')),
      '#options' => [
        'attributes' => [
          'class' => [
            'file-download',
            'file-download-image',
            'file-download-png',
          ],
        ],
      ],
      '#cache' => [
        'tags' => array_merge($file->getCacheTags(), $this->entity->getCacheTags()),
        'contexts' => [],
        'max-age' => -1,
      ],
      '#attached' => [],
    ];
    $expected_delta_1 = [
      '#type' => 'link',
      '#title' => "When delta is 1 we should see this alt text. Let's add special chars & test them!",
      '#url' => Url::fromUri(file_create_url('public://file.png')),
      '#options' => [
        'attributes' => [
          'class' => [
            'file-download',
            'file-download-image',
            'file-download-png',
          ],
        ],
      ],
      '#cache' => [
        'tags' => array_merge($file->getCacheTags(), $this->entity->getCacheTags()),
        'contexts' => [],
        'max-age' => -1,
      ],
      '#attached' => [],
    ];

    Assert::assertEquals($expected_delta_0, $render[0]);
    Assert::assertEquals($expected_delta_1, $render[1]);
  }

  /**
   * Tests that tokes are cleared correctly.
   */
  public function testClearTokens() {
    // Text should end up as file name.
    // Title should end up unset.
    $settings = [
      'link_text' => '[fake:token]',
      'link_title' => '[fake:token]',
      'custom_classes' => '[fake:token]',
      'new_tab' => FALSE,
      'force_download' => FALSE,
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
          ],
        ],
      ],
      '#cache' => [
        'tags' => array_merge($file->getCacheTags(), $this->entity->getCacheTags()),
        'contexts' => [],
        'max-age' => -1,
      ],
      '#attached' => [],
    ];

    Assert::assertEquals($expected_render, $render[0]);
  }

  /**
   * Test the formatter using a token from the user.
   *
   * This is unikely to be used. But we ensure user info is cached right.
   */
  public function testFormatterUserTokens() {
    $settings = [
      'link_text' => 'Download this, [current-user:uid]',
      'new_tab' => FALSE,
      'force_download' => FALSE,
    ];
    $render = $this->entity->field_image->view([
      'type' => 'file_download_link',
      'label' => 'hidden',
      'settings' => $settings,
    ]);
    $expected_render = [
      '#type' => 'link',
      '#title' => 'Download this, 99',
      '#url' => Url::fromUri(file_create_url('public://file.png')),
      '#options' => [
        'attributes' => [
          'class' => [
            'file-download',
            'file-download-image',
            'file-download-png',
          ],
        ],
      ],
      '#cache' => [
        'tags' => ['file:1', 'node:1', 'user:99'],
        'contexts' => ['user'],
        'max-age' => -1,
      ],
      '#attached' => [],
    ];

    Assert::assertEquals($expected_render, $render[0]);

    $settings = [
      'link_text' => 'Download',
      'link_title' => 'You know you want it, [current-user:uid]',
      'new_tab' => FALSE,
      'force_download' => FALSE,
    ];
    $render = $this->entity->field_image->view([
      'type' => 'file_download_link',
      'label' => 'hidden',
      'settings' => $settings,
    ]);
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
          'title' => 'You know you want it, 99',
        ],
      ],
      '#cache' => [
        'tags' => ['file:1', 'node:1', 'user:99'],
        'contexts' => ['user'],
        'max-age' => -1,
      ],
      '#attached' => [],
    ];

    Assert::assertEquals($expected_render, $render[0]);
  }

}
