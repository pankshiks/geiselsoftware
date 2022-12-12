<?php

namespace Drupal\ludwig\Controller;

use Drupal\Core\Link;
use Drupal\ludwig\PackageManagerInterface;
use Drupal\ludwig\PackageDownloaderInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Url;
use Drupal\Core\FileTransfer\FileTransferException;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Displays the Packages report.
 */
class PackageController implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The package manager.
   *
   * @var \Drupal\ludwig\PackageManagerInterface
   */
  protected $packageManager;

  /**
   * The package downloader.
   *
   * @var \Drupal\ludwig\PackageDownloaderInterface
   */
  protected $packageDownloader;

  /**
   * The module extension list.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $moduleExtensionList;

  /**
   * The current path stack.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPathStack;

  /**
   * Messenger service.
   *
   * @var Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Logger service.
   *
   * @var Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * Constructs a new PackageController object.
   *
   * @param \Drupal\ludwig\PackageManagerInterface $package_manager
   *   The package manager.
   * @param \Drupal\ludwig\PackageDownloaderInterface $package_downloader
   *   The package downloader.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   * @param \Drupal\Core\Extension\ModuleExtensionList $module_extension_list
   *   The module extension list.
   * @param \Drupal\Core\Path\CurrentPathStack $current_path_stack
   *   The current path stack.
   * @param Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   * @param Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger.
   */
  public function __construct(PackageManagerInterface $package_manager, PackageDownloaderInterface $package_downloader, TranslationInterface $string_translation, ModuleExtensionList $module_extension_list, CurrentPathStack $current_path_stack, MessengerInterface $messenger, LoggerChannelFactoryInterface $logger_factory) {
    $this->packageManager = $package_manager;
    $this->packageDownloader = $package_downloader;
    $this->setStringTranslation($string_translation);
    $this->moduleExtensionList = $module_extension_list;
    $this->currentPathStack = $current_path_stack;
    $this->messenger = $messenger;
    $this->loggerFactory = $logger_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('ludwig.package_manager'),
      $container->get('ludwig.package_downloader'),
      $container->get('string_translation'),
      $container->get('extension.list.module'),
      $container->get('path.current'),
      $container->get('messenger'),
      $container->get('logger.factory'),
    );
  }

  /**
   * Shows the status of all required packages.
   *
   * @return array
   *   Returns a render array as expected by drupal_render().
   */
  public function page() {
    // If requested, download the missing packages first.
    $current_path = $this->currentPathStack->getPath();
    $skip_path = Url::fromRoute('ludwig.packages_skip')->toString();
    if ($current_path != $skip_path) {
      if ($this->download()) {
        // Some missing packages are installed. Cache flush is needed.
        $this->loggerFactory->get('ludwig')->info($this->t('All caches are flushed.'));
        $this->messenger->addMessage($this->t('All caches are flushed by Ludwig.'));
        drupal_flush_all_caches();
      }
    }
    $info = $this->moduleExtensionList->getAllInstalledInfo();
    $build = [];
    $build['packages'] = [
      '#theme' => 'table',
      '#header' => [
        'package' => $this->t('Package'),
        'namespace' => $this->t('Namespace'),
        'paths' => $this->t('Paths'),
        'resource' => $this->t('Resource'),
        'version' => $this->t('Version'),
        'required_by' => $this->t('Required by'),
        'status' => $this->t('Status'),
      ],
      '#attributes' => [
        'class' => ['system-status-report'],
      ],
    ];
    $missing = 0;
    foreach ($this->packageManager->getPackages() as $package_name => $package) {
      if ($package['status'] == 'Missing') {
        $missing++;
      }

      $guide_link = 'https://www.drupal.org/docs/contributed-modules/ludwig/ludwig-errors-warnings-and-notices';

      if (($package['resource'] == 'classmap' || $package['resource'] == 'files') && $package['status'] != 'Installed') {
        $package['description'] .= $this->t('<br><strong>Warning! The @resource type libraries are not supported by Ludwig automatically. @read_more.</strong>', [
          '@resource' => strtoupper($package['resource']),
          '@read_more' => Link::fromTextAndUrl($this->t('Read more'), Url::fromUri($guide_link))->toString(),
        ]);
      }
      elseif ($package['resource'] == 'exclude-from-classmap') {
        $package['description'] .= $this->t('<br><strong>Notice! The @resource property is not supported by Ludwig.</strong> Despite this notice, the library is loaded properly and the module should work nicely. @read_more.', [
          '@resource' => strtoupper($package['resource']),
          '@read_more' => Link::fromTextAndUrl($this->t('Read more'), Url::fromUri($guide_link))->toString(),
        ]);
      }
      elseif ($package['resource'] == 'target-dir') {
        $package['description'] .= $this->t('<br><strong>Warning! The @resource property is not supported by Ludwig.</strong> This module may lack some functionality. @read_more.', [
          '@resource' => strtoupper($package['resource']),
          '@read_more' => Link::fromTextAndUrl($this->t('Read more'), Url::fromUri($guide_link))->toString(),
        ]);
      }
      elseif ($package['resource'] == 'inactive') {
        $package['description'] .= $this->t('<br><strong>Notice! The INACTIVE library. @read_more.</strong>', [
          '@read_more' => Link::fromTextAndUrl($this->t('Read more'), Url::fromUri($guide_link))->toString(),
        ]);
      }
      elseif ($package['resource'] == 'legacy' || $package['resource'] == 'unknown') {
        $package['description'] .= $this->t('<br><strong>Warning! The @resource library type. Not supported by Ludwig. @read_more.</strong>', [
          '@resource' => strtoupper($package['resource']),
          '@read_more' => Link::fromTextAndUrl($this->t('Read more'), Url::fromUri($guide_link))->toString(),
        ]);
      }
      elseif ($package['status'] == 'Missing') {
        $package['description'] = $this->t('@download the library and place it in @path', [
          '@download' => Link::fromTextAndUrl($this->t('Download'), Url::fromUri($package['download_url']))->toString(),
          '@path' => $package['path'],
        ]);
      }

      $package_column = [];
      if (!empty($package['homepage'])) {
        $package_column[] = [
          '#type' => 'link',
          '#title' => $package['name'],
          '#url' => Url::fromUri($package['homepage']),
          '#options' => [
            'attributes' => ['target' => '_blank'],
          ],
        ];
      }
      else {
        $package_column[] = [
          '#plain_text' => $package['name'],
        ];
      }
      if (!empty($package['description'])) {
        $package_column[] = [
          '#prefix' => '<div class="description">',
          '#markup' => $package['description'],
          '#suffix' => '</div>',
        ];
      }
      $required_by = $package['provider'];
      if (isset($info[$package['provider']])) {
        $required_by = $info[$package['provider']]['name'];
      }

      switch ($package['status']) {
        case 'Installed':
          $status = $this->t('Installed');
          break;

        case 'Missing':
          $status = $this->t('Missing');
          break;

        case 'Not installed':
          $status = $this->t('Not installed');
          break;

        case 'Not supported':
          $status = $this->t('Not supported');
          break;

        case 'Overridden':
          $status = $this->t('Overridden');
          break;

        case 'Unknown type':
          $status = $this->t('Unknown type');
          break;

        case 'Inactive':
          $status = $this->t('Inactive');
          break;

        default:
          $status = $this->t('Unknown');
      }

      $build['packages']['#rows'][$package_name] = [
        'class' => $package['status'] == 'Installed' ? [] : ['error'],
        'data' => [
          'package' => [
            'data' => $package_column,
          ],
          'namespace' => $package['resource'] == 'psr-4' || $package['resource'] == 'psr-0' ? $package['namespace'] : '',
          'paths' => implode(', ', $package['paths']),
          'resource' => $package['resource'],
          'version' => $package['version'],
          'required_by' => $required_by,
          'status' => $status,
        ],
      ];
    }

    // Render the 'Download missing packages' action button.
    $build['#markup'] = '<div class="action-links"><div class="button button--small"><a href="' . Url::fromRoute('ludwig.packages')->toString() . '">' . $this->t('Download missing packages') . ' (' . $missing . ')</a></div></div>';

    return $build;
  }

  /**
   * Downloads missing packages.
   *
   * @return bool
   *   Returns TRUE if any missing package is downloaded.
   */
  public function download() {
    $cache_flush_need = FALSE;
    $packages = array_filter($this->packageManager->getPackages(), function ($package) {
      return $package['status'] == 'Missing';
    });
    if (!empty($packages)) {
      foreach ($packages as $name => $package) {
        try {
          $this->packageDownloader->download($package);
          $cache_flush_need = TRUE;
          $this->loggerFactory->get('ludwig')->info($this->t('The @name package has been downloaded and unpacked successfully.', [
            '@name' => $name,
          ]));
          $this->messenger->addMessage($this->t('The @name package has been downloaded and unpacked successfully.', [
            '@name' => $name,
          ]));
        }
        catch (FileTransferException $e) {
          $this->loggerFactory->get('ludwig')->error($e->getMessage());
          $this->messenger->addMessage($e->getMessage(), 'error');
          continue;
        }
        catch (\Exception $e) {
          $this->loggerFactory->get('ludwig')->error($e->getMessage());
          $this->messenger->addMessage($e->getMessage(), 'error');
          continue;
        }
      }
    }

    return $cache_flush_need;
  }

}
