<?php

namespace Drupal\Tests\file_download_link\Kernel;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;

/**
 * Trait for testing file_download_link_media formatter.
 *
 * Takes care of creating a test node and media.
 */
trait FileDownloadLinkMediaTestTrait {

  use MediaTypeCreationTrait;

  /**
   * A test node.
   *
   * @var Drupal\node\Entity\Node
   */
  public $node;

  /**
   * A test media.
   *
   * @var Drupal\media\Entity\Media
   */
  public $media;

  /**
   * Helper function to create media that can be used for testing.
   *
   * @return Drupal\media\Entity\Media
   *   A media to be used for testing.
   */
  protected function createTestMedia() {
    $this->createMediaType('file', ['id' => 'test_media', 'label' => 'Test Media']);
    file_put_contents('public://file.txt', str_repeat('t', 10));
    $file_file = File::create([
      'uri' => 'public://file.txt',
      'filename' => 'file.txt',
    ]);
    $file_file->save();
    $media = Media::create(['bundle' => 'test_media', 'name' => 'Test Media']);
    $media->set('field_media_file', $file_file->id());
    $media->set('status', 1);
    $media->save();

    return $media;
  }

  /**
   * Helper function to create node that can be used for testing.
   *
   * @return Drupal\node\Entity\Node
   *   An node to be used for testing.
   */
  protected function createTestNode() {
    $node_type = NodeType::create(['type' => 'test_node', 'name' => 'Test Node']);
    $node_type->save();
    // Our entity will have an image field and a file field.
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'field_media',
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
    $node->set('field_media', $this->media->id());
    $node->set('status', 1);
    $node->save();

    return $node;
  }

}
