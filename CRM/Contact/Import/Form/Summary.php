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

/**
 * This class summarizes the import results.
 */
class CRM_Contact_Import_Form_Summary extends CRM_Import_Form_Summary {

  /**
   * Set variables up before form is built.
   */
  public function preProcess() {
    // set the error message path to display
    $this->assign('errorFile', $this->get('errorFile'));

    $totalRowCount = $this->get('totalRowCount');
    $relatedCount = $this->get('relatedCount');
    $totalRowCount += $relatedCount;

    $invalidRowCount = $this->get('invalidRowCount');
    $conflictRowCount = $this->get('conflictRowCount');
    $duplicateRowCount = $this->get('duplicateRowCount');
    $onDuplicate = $this->get('onDuplicate');
    $mismatchCount = $this->get('unMatchCount');
    $unparsedAddressCount = $this->get('unparsedAddressCount');
    if ($duplicateRowCount > 0) {
      $urlParams = 'type=' . CRM_Import_Parser::DUPLICATE . '&parser=CRM_Contact_Import_Parser_Contact';
      $this->set('downloadDuplicateRecordsUrl', CRM_Utils_System::url('civicrm/export', $urlParams));
    }
    elseif ($mismatchCount) {
      $urlParams = 'type=' . CRM_Import_Parser::NO_MATCH . '&parser=CRM_Contact_Import_Parser_Contact';
      $this->set('downloadMismatchRecordsUrl', CRM_Utils_System::url('civicrm/export', $urlParams));
    }
    else {
      $duplicateRowCount = 0;
      $this->set('duplicateRowCount', $duplicateRowCount);
    }
    if ($unparsedAddressCount) {
      $urlParams = 'type=' . CRM_Import_Parser::UNPARSED_ADDRESS_WARNING . '&parser=CRM_Contact_Import_Parser_Contact';
      $this->assign('downloadAddressRecordsUrl', CRM_Utils_System::url('civicrm/export', $urlParams));
      $unparsedStreetAddressString = ts('Records imported successfully but unable to parse some of the street addresses');
      $this->assign('unparsedStreetAddressString', $unparsedStreetAddressString);
    }
    $this->assign('dupeError', FALSE);

    if ($onDuplicate == CRM_Import_Parser::DUPLICATE_UPDATE) {
      $dupeActionString = ts('These records have been updated with the imported data.');
    }
    elseif ($onDuplicate == CRM_Import_Parser::DUPLICATE_REPLACE) {
      $dupeActionString = ts('These records have been replaced with the imported data.');
    }
    elseif ($onDuplicate == CRM_Import_Parser::DUPLICATE_FILL) {
      $dupeActionString = ts('These records have been filled in with the imported data.');
    }
    else {
      /* Skip by default */

      $dupeActionString = ts('These records have not been imported.');

      $this->assign('dupeError', TRUE);
    }
    //now we also create relative contact in update and fill mode
    $this->set('validRowCount', $totalRowCount - $invalidRowCount -
      $conflictRowCount - $duplicateRowCount - $mismatchCount
    );

    $this->assign('dupeActionString', $dupeActionString);

    $properties = [
      'totalRowCount',
      'validRowCount',
      'invalidRowCount',
      'conflictRowCount',
      'downloadConflictRecordsUrl',
      'downloadErrorRecordsUrl',
      'duplicateRowCount',
      'downloadDuplicateRecordsUrl',
      'downloadMismatchRecordsUrl',
      'groupAdditions',
      'tagAdditions',
      'unMatchCount',
      'unparsedAddressCount',
    ];
    foreach ($properties as $property) {
      $this->assign($property, $this->get($property));
    }

    $session = CRM_Core_Session::singleton();
    $session->pushUserContext(CRM_Utils_System::url('civicrm/import/contact', 'reset=1'));
  }

  /**
   * Clean up the import table we used.
   */
  public function postProcess() {
    $dao = new CRM_Core_DAO();
    $db = $dao->getDatabaseConnection();

    $importTableName = $this->get('importTableName');
    // do a basic sanity check here
    if (strpos($importTableName, 'civicrm_import_job_') === 0) {
      $query = "DROP TABLE IF EXISTS $importTableName";
      $db->query($query);
    }
  }

}
