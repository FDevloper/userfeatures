<?php

namespace Drupal\myusers_module;

/**
 * Class SearchFilesBatch.
 * Abstrac methods (impor file batch).
 *
 */
class ImportCSVFileService {

  /**
   * Process found rows.
   *
   * @param array $group
   *  rows data.
   */
  public static function insertGroupRows($group, &$context){
    // Insert file data.
    $connection = \Drupal::service('database');
    $insertSentece = $connection->insert('custom_users');
    $insertSentece->fields([
      'name'
    ]);
    foreach ($group as $row) {
      $context['results']['rows'][] = $row;
      $insertSentece->values([
        'name' => $row,
      ]);
    }
    $insertSentece->execute();
    $context['message'] = t(count($context['results']['rows']) . ' processed rows.');
  }

  /**
   * Load batch process message.
   *
   * @param boolean $success
   *   Batch status.
   * @param array $results
   *   Context results.
   * @param array $operations
   *   Final call methods.
   */
  public static function insertFinishedCallback($success, $results, $operations) {
    if ($success) {
      $message = count($results['rows']) . " was processed.";
    }else {
      $message = 'Batch error.';
    }
    drupal_set_message(t($message));
  }
}
