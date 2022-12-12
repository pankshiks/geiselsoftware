<?php

namespace Drupal\Tests\file_download_link\Kernel;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\media\Entity\MediaType;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;
use Drupal\file_download_link_media\Plugin\Field\FieldFormatter\FileDownloadLinkMedia;
use PHPUnit\Framework\Assert;

/**
 * Class for testing file_download_link_media formatter.
 *
 * @group file_download_link
 */
class FileDownloadLinkMediaApplicableTest extends KernelTestBase {

  use MediaTypeCreationTrait;

  /**
   * A test node.
   *
   * @var Drupal\node\Entity\Node
   */
  public $node;

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
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig(['system', 'field']);
    $this->installSchema('user', ['users_data']);
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('node_type');
    $this->installEntitySchema('media');
    $this->installEntitySchema('media_type');
    $this->createMediaType('file', ['id' => 'file_media', 'label' => 'File Media']);
    $this->createMediaType('image', ['id' => 'image_media', 'label' => 'Image Media']);
    $this->createMediaType('oembed:video', ['id' => 'oembed_media', 'label' => 'Oembed Media']);
    $this->node = $this->createTestNode();
  }

  /**
   * This should be applicable for image media.
   */
  public function testApplicableImage() {
    $field_definition = $this->node->getFieldDefinition('field_image');
    Assert::assertTrue(FileDownloadLinkMedia::isApplicable($field_definition));
  }

  /**
   * This should be applicable for file media.
   */
  public function testApplicableFile() {
    $field_definition = $this->node->getFieldDefinition('field_file');
    Assert::assertTrue(FileDownloadLinkMedia::isApplicable($field_definition));
  }

  /**
   * This should be applicable for file media and image media.
   */
  public function testApplicableBoth() {
    $field_definition = $this->node->getFieldDefinition('field_both');
    Assert::assertTrue(FileDownloadLinkMedia::isApplicable($field_definition));
  }

  /**
   * This should not work for oembed.
   */
  public function testApplicableOembed() {
    $field_definition = $this->node->getFieldDefinition('field_oembed');
    Assert::assertFalse(FileDownloadLinkMedia::isApplicable($field_definition));
  }

  /**
   * This should not work for all until I delete oembed media type.
   */
  public function testApplicableAll() {
    $field_definition = $this->node->getFieldDefinition('field_all');
    Assert::assertFalse(FileDownloadLinkMedia::isApplicable($field_definition));

    $oembed = MediaType::load('oembed_media');
    $oembed->delete();
    Assert::assertTrue(FileDownloadLinkMedia::isApplicable($field_definition));
  }

  /**
   * Helper function to create node that can be used for testing.
   *
   * This node has several media fields to test with, though no media
   * items are actually created.
   *
   * @return Drupal\node\Entity\Node
   *   An node to be used for testing.
   */
  protected function createTestNode() {
    $node_type = NodeType::create(['type' => 'test_node', 'name' => 'Test Node']);
    $node_type->save();

    // Just image media.
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'field_image',
      'entity_type' => 'node',
      'type' => 'entity_reference',
      'settings' => [
        'target_type' => 'media',
      ],
    ]);
    $field_storage->save();
    $instance = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'test_node',
      'label' => 'Media',
      'settings' => [
        'handler_settings' => [
          'target_bundles' => [
            'image_media',
          ],
        ],
      ],
    ]);
    $instance->save();

    // Just file media.
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'field_file',
      'entity_type' => 'node',
      'type' => 'entity_reference',
      'settings' => [
        'target_type' => 'media',
      ],
    ]);
    $field_storage->save();
    $instance = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'test_node',
      'label' => 'Media',
      'settings' => [
        'handler_settings' => [
          'target_bundles' => [
            'file_media',
          ],
        ],
      ],
    ]);
    $instance->save();

    // Just oembed media.
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'field_oembed',
      'entity_type' => 'node',
      'type' => 'entity_reference',
      'settings' => [
        'target_type' => 'media',
      ],
    ]);
    $field_storage->save();
    $instance = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'test_node',
      'label' => 'Media',
      'settings' => [
        'handler_settings' => [
          'target_bundles' => [
            'oembed_media',
          ],
        ],
      ],
    ]);
    $instance->save();

    // Image and file.
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'field_both',
      'entity_type' => 'node',
      'type' => 'entity_reference',
      'settings' => [
        'target_type' => 'media',
      ],
    ]);
    $field_storage->save();
    $instance = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'test_node',
      'label' => 'Media',
      'settings' => [
        'handler_settings' => [
          'target_bundles' => [
            'file_media',
            'image_media',
          ],
        ],
      ],
    ]);
    $instance->save();

    // All media.
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'field_all',
      'entity_type' => 'node',
      'type' => 'entity_reference',
      'settings' => [
        'target_type' => 'media',
      ],
    ]);
    $field_storage->save();
    $instance = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'test_node',
      'label' => 'Media',
    ]);
    $instance->save();

    $node = Node::create(['type' => 'test_node', 'title' => 'Test Entity']);
    $node->set('status', 1);
    $node->save();

    return $node;
  }

}
