<?php

namespace Drupal\myusers_module\Controller;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Drupal\Core\Database\Connection;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller for download link.
 * 
 */
class UserFileDownload extends ControllerBase {
  
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
   * Download file.
   *
   */
  public function downloadFile() {
    $filename = 'UsersFile.xlsx';
    $response = new Response();
    // Set response headers.
    $response->headers->set('Pragma', 'no-cache');
    $response->headers->set('Content-Type', 'application/vnd.ms-excel; charset=utf-8');
    $response->headers->set('Content-Disposition', "attachment; filename=$filename");
    // Search users data.
    $query = $this->connection->select('custom_users', 'users');
    $query->fields('users', ['name', 'tid']);
    $users = $query->execute()->fetchAll();

    if (!empty($users)) {
      // Create new sheet.
      $spreadsheet = new Spreadsheet();
      $spreadsheet->setActiveSheetIndex(0);
      $worksheet = $spreadsheet->getActiveSheet();
      $worksheet->getStyle("A1:B1")->getFont()->setBold(true);
      $worksheet->setCellValue('A1', 'User Id');
      $worksheet->setCellValue('B1', 'User Name');
      $worksheet->getColumnDimension('A')->setAutoSize(true);
      $worksheet->getColumnDimension('B')->setAutoSize(true);
      $indx = 2;
      // Set data.
      foreach ($users as $key => $value) {
        $worksheet->setCellValue('A' . $indx, $value->tid);
        $worksheet->setCellValue('B' . $indx, $value->name);
        $indx++;
      }
      $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
      ob_start();
      $writer->save('php://output');
      $content = ob_get_clean();
      // Memory cleanup.
      $spreadsheet->disconnectWorksheets();
      unset($spreadsheet);
      $response->setContent($content);
    }
    
    return $response;
  }
}
