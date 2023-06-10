<?php

/**
 * OnsiteDocumentController.php
 *
 * @package   OpenEMR
 * @link      https://www.open-emr.org
 * @author    Jerry Padgett <sjpadgett@gmail.com>
 * @copyright Copyright (c) 2016-2019 Jerry Padgett <sjpadgett@gmail.com>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

/** import supporting libraries */
require_once("AppBasePortalController.php");
require_once("Model/OnsiteDocument.php");

/**
 * OnsiteDocumentController is the controller class for the OnsiteDocument object.  The
 * controller is responsible for processing input from the user, reading/updating
 * the model as necessary and displaying the appropriate view.
 *
 * @package Patient Portal::Controller
 * @author ClassBuilder
 * @version 1.0
 */
class OnsiteDocumentController extends AppBasePortalController
{
    /**
     * Override here for any controller-specific functionality
     *
     * @inheritdocs
     */
    protected function Init()
    {
        parent::Init();
    }

    /**
     * Displays a list view of OnsiteDocument objects
     */
    public function ListView()
    {
        $recid = $pid = $user = $encounter =  0;
        $is_module = $catid = 0;
        $is_portal = GlobalConfig::$PORTAL;
        $docid = $new_filename = "";
        // get latest help template id
        $help_id = sqlQuery('SELECT * FROM `document_templates` WHERE `template_name` = ? Order By modified_date DESC Limit 1', array('Help'))['id'] ?? 0;

        if (isset($_GET['pid'])) {
            $pid = (int) $_GET['pid'];
        }

        // only allow patient to see themselves
        if (!empty($GLOBALS['bootstrap_pid'])) {
            $pid = $GLOBALS['bootstrap_pid'];
        }

        if (isset($_GET['user'])) {
            $user = $_GET['user'];
        }

        if (isset($_GET['docid'])) {
            $docid = $_GET['docid'];
        }

        if (isset($_GET['enc'])) {
            $encounter = (int) $_GET['enc'];
        }

        if (isset($_GET['recid'])) {
            $recid = (int) $_GET['recid'];
        }

        if (isset($_GET['is_module'])) {
            $is_module = $_GET['is_module'];
        }

        if (isset($_GET['catid'])) {
            $catid = $_GET['catid'];
        }
        if (isset($_GET['new'])) {
            $new_filename = $_GET['new'];
        }
        $this->Assign('recid', $recid);
        $this->Assign('help_id', $help_id);
        $this->Assign('cpid', $pid);
        $this->Assign('cuser', $user);
        $this->Assign('encounter', $encounter);
        $this->Assign('docid', $docid);
        $this->Assign('is_module', $is_module);
        $this->Assign('is_portal', $is_portal);
        $this->Assign('save_catid', $catid);
        $this->Assign('new_filename', $new_filename);
        $this->Render();
    }

    /**
     * API Method queries for OnsiteDocument records and render as JSON
     */
    public function Query()
    {
        try {
            $criteria = new OnsiteDocumentCriteria();
            $pid = RequestUtil::Get('patientId');

            // only allow patient to see themself
            if (!empty($GLOBALS['bootstrap_pid'])) {
                $pid = $GLOBALS['bootstrap_pid'];
            }

            $criteria->Pid_Equals = $pid;
            $recid = RequestUtil::Get('recid');
            if ($recid > 0) {
                $criteria->Id_Equals = $recid;
            }

            $filter = RequestUtil::Get('filter');
            if ($filter) {
                $criteria->AddFilter(
                    new CriteriaFilter('Id,Pid,Facility,Provider,Encounter,CreateDate,DocType,PatientSignedStatus,PatientSignedTime,AuthorizeSignedTime,
						AcceptSignedStatus,AuthorizingSignator,ReviewDate,DenialReason,AuthorizedSignature,PatientSignature,FullDocument,FileName,FilePath', '%' . $filter . '%')
                );
            }

            // TODO: this is generic query filtering based only on criteria properties
            foreach (array_keys($_REQUEST) as $prop) {
                $prop_normal = ucfirst($prop);
                $prop_equals = $prop_normal . '_Equals';

                if (property_exists($criteria, $prop_normal)) {
                    $criteria->$prop_normal = RequestUtil::Get($prop);
                } elseif (property_exists($criteria, $prop_equals)) {
                    // this is a convenience so that the _Equals suffix is not needed
                    $criteria->$prop_equals = RequestUtil::Get($prop);
                }
            }

            $output = new stdClass();

            // if a sort order was specified then specify in the criteria
            $output->orderBy = RequestUtil::Get('orderBy');
            $output->orderDesc = RequestUtil::Get('orderDesc') != '';
            if ($output->orderBy) {
                $criteria->SetOrder($output->orderBy, $output->orderDesc);
            }

            $page = RequestUtil::Get('page');

            if (!empty($page)) {
                // if page is specified, use this instead (at the expense of one extra count query)
                $pagesize = $this->GetDefaultPageSize();

                $onsitedocuments = $this->Phreezer->Query('OnsiteDocument', $criteria)->GetDataPage($page, $pagesize);
                $output->rows = $onsitedocuments->ToObjectArray(true, $this->SimpleObjectParams());
                $output->totalResults = $onsitedocuments->TotalResults;
                $output->totalPages = $onsitedocuments->TotalPages;
                $output->pageSize = $onsitedocuments->PageSize;
                $output->currentPage = $onsitedocuments->CurrentPage;
            } else {
                // return all results
                $onsitedocuments = $this->Phreezer->Query('OnsiteDocument', $criteria);
                $output->rows = $onsitedocuments->ToObjectArray(true, $this->SimpleObjectParams());
                $output->totalResults = count($output->rows);
                $output->totalPages = 1;
                $output->pageSize = $output->totalResults;
                $output->currentPage = 1;
            }


            $this->RenderJSON($output, $this->JSONPCallback());
        } catch (Exception $ex) {
            $this->RenderExceptionJSON($ex);
        }
    }

    /**
     * @return void
     */
    public function SingleView()
    {
        $rid = $pid = $user = $encounter = 0;
        if (isset($_GET['id'])) {
            $rid = (int) $_GET['id'];
        }

        if (isset($_GET['pid'])) {
            $pid = (int) $_GET['pid'];
        }

        // only allow patient to see themself
        if (!empty($GLOBALS['bootstrap_pid'])) {
            $pid = $GLOBALS['bootstrap_pid'];
        }

        if (isset($_GET['user'])) {
            $user = $_GET['user'];
        }

        if (isset($_GET['enc'])) {
            $encounter = $_GET['enc'];
        }

        $this->Assign('recid', $rid);
        $this->Assign('cpid', $pid);
        $this->Assign('cuser', $user);
        $this->Assign('encounter', $encounter);
        $this->Render();
    }
    /**
     * API Method retrieves a single OnsiteDocument record and render as JSON
     */
    public function Read()
    {
        try {
            $pk = $this->GetRouter()->GetUrlParam('id');
            $onsitedocument = $this->Phreezer->Get('OnsiteDocument', $pk);

            // only allow patient to see themself
            if (!empty($GLOBALS['bootstrap_pid'])) {
                if ($GLOBALS['bootstrap_pid'] != $onsitedocument->Pid) {
                    $error = 'Unauthorized';
                    throw new Exception($error);
                }
            }

            $this->RenderJSON($onsitedocument, $this->JSONPCallback(), true, $this->SimpleObjectParams());
        } catch (Exception $ex) {
            $this->RenderExceptionJSON($ex);
        }
    }

    /**
     * API Method inserts a new OnsiteDocument record and render response as JSON
     */
    public function Create()
    {
        try {
            $json = json_decode(RequestUtil::GetBody());

            if (!$json) {
                throw new Exception('The request body does not contain valid JSON');
            }

            $onsitedocument = new OnsiteDocument($this->Phreezer);

            // only allow patient to add to themselves
            if (!empty($GLOBALS['bootstrap_pid'])) {
                $onsitedocument->Pid = $GLOBALS['bootstrap_pid'];
            } else {
                $onsitedocument->Pid = $this->SafeGetVal($json, 'pid');
            }

            // removing for testing
            /* if (!empty($_SESSION["patient_portal_onsite_two"] ?? null)) {
                $decode = $this->SafeGetVal($json, 'fullDocument');
                $k = (int)$this->SafeGetVal($json, 'csrf_token_form')[0];
                $json->fullDocument = $this->decode($decode, $k);
            } */

            $onsitedocument->Facility = $this->SafeGetVal($json, 'facility');
            $onsitedocument->Provider = $this->SafeGetVal($json, 'provider');
            $onsitedocument->Encounter = $this->SafeGetVal($json, 'encounter');
            $onsitedocument->CreateDate = date('Y-m-d H:i:s', strtotime($this->SafeGetVal($json, 'createDate')));
            $onsitedocument->DocType = $this->SafeGetVal($json, 'docType');
            $onsitedocument->PatientSignedStatus = $this->SafeGetVal($json, 'patientSignedStatus');
            $onsitedocument->PatientSignedTime = date('Y-m-d H:i:s', strtotime($this->SafeGetVal($json, 'patientSignedTime')));
            $onsitedocument->AuthorizeSignedTime = date('Y-m-d H:i:s', strtotime($this->SafeGetVal($json, 'authorizeSignedTime')));
            $onsitedocument->AcceptSignedStatus = $this->SafeGetVal($json, 'acceptSignedStatus');
            $onsitedocument->AuthorizingSignator = $this->SafeGetVal($json, 'authorizingSignator');
            $onsitedocument->ReviewDate = date('Y-m-d H:i:s', strtotime($this->SafeGetVal($json, 'reviewDate')));
            $onsitedocument->DenialReason = $this->SafeGetVal($json, 'denialReason');
            $onsitedocument->AuthorizedSignature = $this->SafeGetVal($json, 'authorizedSignature');
            $onsitedocument->PatientSignature = $this->SafeGetVal($json, 'patientSignature');
            $onsitedocument->FullDocument = $this->SafeGetVal($json, 'fullDocument');
            $onsitedocument->FileName = $this->SafeGetVal($json, 'fileName');
            $onsitedocument->FilePath = $this->SafeGetVal($json, 'filePath');

            $onsitedocument->Validate();
            $errors = $onsitedocument->GetValidationErrors();

            if (count($errors) > 0) {
                $this->RenderErrorJSON('Please check the form for errors', $errors);
            } else {
                $new_data = $onsitedocument->FullDocument;
                // use a custom diff function to look for changing tags only with html
                if ($new_data != strip_tags($new_data)) {
                    $old_data = $json->fullDocument;
                    $onsitedocument->FullDocument = $this->htmlDiff($old_data, $new_data);
                }
                $onsitedocument->Save();
                $this->RenderJSON($onsitedocument, $this->JSONPCallback(), true, $this->SimpleObjectParams());
            }
        } catch (Exception $ex) {
            $this->RenderExceptionJSON($ex);
        }
    }

    /**
     * API Method updates an existing OnsiteDocument record and render response as JSON
     */
    public function Update()
    {
        try {
            $json = json_decode(RequestUtil::GetBody());

            if (!$json) {
                throw new Exception('The request body does not contain valid JSON');
            }
            $pk = $this->GetRouter()->GetUrlParam('id');
            $onsitedocument = $this->Phreezer->Get('OnsiteDocument', $pk);
            $old_data = $onsitedocument->FullDocument;

            // only allow patient to update themselves (part 1)
            if (!empty($GLOBALS['bootstrap_pid'])) {
                if ($GLOBALS['bootstrap_pid'] != $onsitedocument->Pid) {
                    $error = 'Unauthorized';
                    throw new Exception($error);
                }
            }

            // only allow patient to update themselves (part 2)
            if (!empty($GLOBALS['bootstrap_pid'])) {
                $onsitedocument->Pid = $GLOBALS['bootstrap_pid'];
            } else {
                $onsitedocument->Pid = $this->SafeGetVal($json, 'pid', $onsitedocument->Pid);
            }

            // removing for testing
            /* if (!empty($_SESSION["patient_portal_onsite_two"] ?? null)) {
                $decode = $this->SafeGetVal($json, 'fullDocument');
                $k = (int)$this->SafeGetVal($json, 'csrf_token_form')[0];
                $json->fullDocument = $this->decode($decode, $k);
            } */

            $onsitedocument->Facility = $this->SafeGetVal($json, 'facility', $onsitedocument->Facility);
            $onsitedocument->Provider = $this->SafeGetVal($json, 'provider', $onsitedocument->Provider);
            $onsitedocument->Encounter = $this->SafeGetVal($json, 'encounter', $onsitedocument->Encounter);
            $onsitedocument->CreateDate = date('Y-m-d H:i:s', strtotime($this->SafeGetVal($json, 'createDate', $onsitedocument->CreateDate)));
            $onsitedocument->DocType = $this->SafeGetVal($json, 'docType', $onsitedocument->DocType);
            $onsitedocument->PatientSignedStatus = $this->SafeGetVal($json, 'patientSignedStatus', $onsitedocument->PatientSignedStatus);
            $onsitedocument->PatientSignedTime = date('Y-m-d H:i:s', strtotime($this->SafeGetVal($json, 'patientSignedTime', $onsitedocument->PatientSignedTime)));
            $onsitedocument->AuthorizeSignedTime = date('Y-m-d H:i:s', strtotime($this->SafeGetVal($json, 'authorizeSignedTime', $onsitedocument->AuthorizeSignedTime)));
            $onsitedocument->AcceptSignedStatus = $this->SafeGetVal($json, 'acceptSignedStatus', $onsitedocument->AcceptSignedStatus);
            $onsitedocument->AuthorizingSignator = $this->SafeGetVal($json, 'authorizingSignator', $onsitedocument->AuthorizingSignator);
            $onsitedocument->ReviewDate = date('Y-m-d H:i:s', strtotime($this->SafeGetVal($json, 'reviewDate', $onsitedocument->ReviewDate)));
            $onsitedocument->DenialReason = $this->SafeGetVal($json, 'denialReason', $onsitedocument->DenialReason);
            $onsitedocument->AuthorizedSignature = $this->SafeGetVal($json, 'authorizedSignature', $onsitedocument->AuthorizedSignature);
            $onsitedocument->PatientSignature = $this->SafeGetVal($json, 'patientSignature', $onsitedocument->PatientSignature);
            $onsitedocument->FullDocument = $this->SafeGetVal($json, 'fullDocument', $onsitedocument->FullDocument);
            $onsitedocument->FileName = $this->SafeGetVal($json, 'fileName', $onsitedocument->FileName);
            $onsitedocument->FilePath = $this->SafeGetVal($json, 'filePath', $onsitedocument->FilePath);

            $onsitedocument->Validate();
            $errors = $onsitedocument->GetValidationErrors();

            if (count($errors) > 0) {
                $this->RenderErrorJSON('Please check the form for errors', $errors);
            } else {
                // use a custom diff function to look for changing tags only with html
                $new_data = $onsitedocument->FullDocument;
                if ($new_data != strip_tags($new_data)) {
                    $onsitedocument->FullDocument = $this->htmlDiff($old_data, $new_data);
                }
                $onsitedocument->Save();
                $this->RenderJSON($onsitedocument, $this->JSONPCallback(), true, $this->SimpleObjectParams());
            }
        } catch (Exception $ex) {
            $this->RenderExceptionJSON($ex);
        }
    }

    /**
     * API Method deletes an existing OnsiteDocument record and render response as JSON
     */
    public function Delete()
    {
        try {
            // TODO: if a soft delete is prefered, change this to update the deleted flag instead of hard-deleting

            $pk = $this->GetRouter()->GetUrlParam('id');
            $onsitedocument = $this->Phreezer->Get('OnsiteDocument', $pk);

            // only allow patient to delete themselves
            if (!empty($GLOBALS['bootstrap_pid'])) {
                if ((int)$GLOBALS['bootstrap_pid'] !== (int)$onsitedocument->Pid) {
                    $error = 'Unauthorized';
                    throw new Exception($error);
                }
            }

            $onsitedocument->Delete();

            $output = new stdClass();

            $this->RenderJSON($output, $this->JSONPCallback());
        } catch (Exception $ex) {
            $this->RenderExceptionJSON($ex);
        }
    }

    // removing for testing
    /*
     * @param $encoded
     * @param $v
     * @return bool|string

    private function decode($encoded, $v): bool|string
    {
        $encoded = base64_decode($encoded);
        $decoded = "";
        for ($i = 0; $i < strlen($encoded); $i++) {
            $b = ord($encoded[$i]);
            $a = $b ^ $v;
            $decoded .= chr($a);
        }
        return base64_decode(base64_decode($decoded));
    }
    */

    private function diff($old, $new): array
    {
        $matrix = array();
        $maxlen = 0;
        foreach ($old as $oindex => $ovalue) {
            $nkeys = array_keys($new, $ovalue);
            foreach ($nkeys as $nindex) {
                $matrix[$oindex][$nindex] = isset($matrix[$oindex - 1][$nindex - 1]) ?
                    $matrix[$oindex - 1][$nindex - 1] + 1 : 1;
                if ($matrix[$oindex][$nindex] > $maxlen) {
                    $maxlen = $matrix[$oindex][$nindex];
                    $omax = $oindex + 1 - $maxlen;
                    $nmax = $nindex + 1 - $maxlen;
                }
            }
        }
        if ($maxlen == 0) {
            return array(array('d' => $old, 'i' => $new));
        }
        return array_merge(
            $this->diff(array_slice($old, 0, $omax), array_slice($new, 0, $nmax)),
            array_slice($new, $nmax, $maxlen),
            $this->diff(array_slice($old, $omax + $maxlen), array_slice($new, $nmax + $maxlen))
        );
    }

    private function htmlDiff($old, $new): string
    {
        $ret = '';
        $diff = $this->diff(preg_split("/[\s]+/", $old), preg_split("/[\s]+/", $new));
        foreach ($diff as $k) {
            if (is_array($k)) {
                $ret .= (!empty($k['i']) ? attr(implode(' ', $k['i'])) : '');
            } else {
                $ret .= $k . ' ';
            }
        }
        return $ret;
    }
}
