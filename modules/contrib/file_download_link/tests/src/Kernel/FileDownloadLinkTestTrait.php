<?php

namespace Drupal\Tests\file_download_link\Kernel;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\file\Entity\File;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;

/**
 * Trait for testing file_download_link formatter.
 *
 * Takes care of creating a test node and doing setUp.
 */
trait FileDownloadLinkTestTrait {

  /**
   * A test entity.
   *
   * @var Drupal\node\Entity\Node
   */
  public $entity;

  /**
   * Helper function to create entity that can be used for testing.
   *
   * @return Drupal\node\Entity\Node
   *   An entity to be used for testing.
   */
  protected function createTestEntity() {
    $node_type = NodeType::create(['type' => 'test_node', 'name' => 'Test Node']);
    $node_type->save();
    // Our entity will have an image field and a file field.
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'field_image',
      'entity_type' => 'node',
      'type' => 'image',
      'cardinality' => -1,
    ]);
    $field_storage->save();
    $instance = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'test_node',
      'label' => 'Image',
    ]);
    $instance->save();
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'field_file',
      'entity_type' => 'node',
      'type' => 'file',
    ]);
    $field_storage->save();
    $instance = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'test_node',
      'label' => 'File',
    ]);
    $instance->save();

    $image_path = drupal_get_path('theme', 'stable') . '/images/core/druplicon.png';
    file_put_contents('public://file.png', file_get_contents($image_path));
    $file_image = File::create([
      'uri' => 'public://file.png',
      'filename' => 'file.png',
    ]);
    $file_image->save();

    file_put_contents('public://file.txt', str_repeat('t', 10));
    $file_file = File::create([
      'uri' => 'public://file.txt',
      'filename' => 'file.txt',
    ]);
    $file_file->save();
    $entity = Node::create(['type' => 'test_node', 'title' => 'Test Entity']);
    $field_image_value = [
      [
        'target_id' => $file_image->id(),
        'alt' => 'This alt text is for the first image.',
      ],
      [
        'target_id' => $file_image->id(),
        'alt' => "When delta is 1 we should see this alt text. Let's add special chars & test them!",
      ],
    ];
    $entity->set('field_image', $field_image_value);
    $entity->set('field_file', $file_file->id());
    $entity->save();

    return $entity;
  }

}
