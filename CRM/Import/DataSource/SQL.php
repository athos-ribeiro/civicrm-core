<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Import_DataSource_SQL extends CRM_Import_DataSource {

  /**
   * Form fields declared for this datasource.
   *
   * @var string[]
   */
  protected $submittableFields = ['sqlQuery'];

  /**
   * Provides information about the data source.
   *
   * @return array
   *   collection of info about this data source
   */
  public function getInfo(): array {
    return [
      'title' => ts('SQL Query'),
      'permissions' => ['import SQL datasource'],
    ];
  }

  /**
   * This is function is called by the form object to get the DataSource's
   * form snippet. It should add all fields necesarry to get the data
   * uploaded to the temporary table in the DB.
   *
   * @param CRM_Core_Form $form
   *
   * @return void
   *   (operates directly on form argument)
   */
  public function buildQuickForm(&$form) {
    $form->add('hidden', 'hidden_dataSource', 'CRM_Import_DataSource_SQL');
    $form->add('textarea', 'sqlQuery', ts('Specify SQL Query'), ['rows' => 10, 'cols' => 45], TRUE);
    $form->addFormRule(['CRM_Import_DataSource_SQL', 'formRule'], $form);
  }

  /**
   * @param $fields
   * @param $files
   * @param CRM_Core_Form $form
   *
   * @return array|bool
   */
  public static function formRule($fields, $files, $form) {
    $errors = [];

    // Makeshift query validation (case-insensitive regex matching on word boundaries)
    $forbidden = ['ALTER', 'CREATE', 'DELETE', 'DESCRIBE', 'DROP', 'SHOW', 'UPDATE', 'REPLACE', 'information_schema'];
    foreach ($forbidden as $pattern) {
      if (preg_match("/\\b$pattern\\b/i", $fields['sqlQuery'])) {
        $errors['sqlQuery'] = ts('The query contains the forbidden %1 command.', [1 => $pattern]);
      }
    }

    return $errors ?: TRUE;
  }

  /**
   * Process the form submission.
   *
   * @param array $params
   * @param string $db
   * @param \CRM_Core_Form $form
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function postProcess(&$params, &$db, &$form) {
    $importJob = new CRM_Contact_Import_ImportJob(
      CRM_Utils_Array::value('import_table_name', $params),
      $params['sqlQuery'], TRUE
    );

    $form->set('importTableName', $importJob->getTableName());
    // Get the names of the fields to be imported. Any fields starting with an
    // underscore are considered to be internal to the import process)
    $columnsResult = CRM_Core_DAO::executeQuery(
      'SHOW FIELDS FROM ' . $importJob->getTableName() . "
      WHERE Field NOT LIKE '\_%'");

    $columnNames = [];
    while ($columnsResult->fetch()) {
      $columnNames[] = $columnsResult->Field;
    }
    $this->updateUserJobMetadata('DataSource', [
      'table_name' => $importJob->getTableName(),
      'column_headers' => $columnNames,
      'number_of_columns' => count($columnNames),
    ]);
  }

}
