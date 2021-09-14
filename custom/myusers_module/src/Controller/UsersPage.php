<?php

namespace Drupal\myusers_module\Controller;

use Drupal\Core\Database\Connection;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Render\FormattableMarkup;

/**
 * Controller for users page.
 *
 */
class UsersPage extends ControllerBase {

  /**
   * @var Connection $connection
   */
  protected $connection;

  /**
   * Class constructor.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   A connection instance.
   */
  public function __construct(Connection $connection) {
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database')
    );
  }

  /**
   * Content callback for the grm_documents_views.page-documents route.
   */
  public function getUsers() {
    // Get users.
    $header = [
      'tid' => 'User Id',
      'name' => 'User Name'
    ];
    $query = $this->connection->select('custom_users', 'users');
    $query->fields('users', ['name', 'tid']);
    $pager = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit(10);
    $table_sort = $query->extend('Drupal\Core\Database\Query\TableSortExtender')
      ->orderByHeader($header);
    $users = $pager->execute()->fetchAll();
    $rows = [];

    if (!empty($users)) {
      foreach ($users as $user) {
        $rows[$user->tid] = [
          'tid' => $user->tid,
          'name' => $user->name,
        ];
      }
    }
    // Build the table.
    $downloadLink = new FormattableMarkup(
      '<h3><a href=":link" target="_blank">@name</a></h3>', 
      [':link' => '/user/consult/excel', '@name' => 'Download Excel']
    );
    $build = [
      'table' => [
        '#suffix' => $downloadLink,
        '#theme' => 'table',
        '#header' => $header,
        '#rows' => $rows,
        '#empty' => t('No users found'),
      ],
      'pager' => [
        '#type' => 'pager',
      ],
      '#cache' => ['max-age' => 0],
    ];
    return $build;
  }

}
