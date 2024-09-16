<?php
namespace PSU\M2C2;

use \REDCap;
use \Survey;

const M2C2_LOGGING_INVALID_CONFIG = "Invalid M2C2 config.";
const M2C2_LOGGING_INVALID_CONFIG_PARAM = "error_message";
const M2C2_LOGGING_LAUNCHING = "M2C2 launching.";
const M2C2_JSON = array(
    'activity_name',
    'activity_version',
    'redcap_fields');
const M2C2_LOGGING_LAUNCH_PARAMS = array(
    'activity_name' => 'activity_name',
    'activity_version' => 'activity_version',
    'redcap_fields' => 'redcap_fields');
const M2C2_PURGE_URL = 'm2c2-log-purge.php';
const M2C2_JS_FILE = 'js/m2c2.js';

class M2C2 extends \ExternalModules\AbstractExternalModule {

    

    function redcap_survey_page($project_id, $record, $instrument, $event_id, $group_id, $survey_hash, $response_id, $repeat_instance) {
        echo '<script>console.log("loading js cory");</script>';
        $this->initializeJavascriptModuleObject();
        echo '<script>const module = ' . $this->framework->getJavascriptModuleObjectName() . ';</script>';
        echo '<script>console.log("finished js cory");</script>';

        // Get Instrument Data Dictionary
        $instrument_dict_json = REDCap::getDataDictionary($this->getProjectId(), 'json', false, null, array($instrument));
        $instrument_dict = json_decode($instrument_dict_json, true);

        foreach ($instrument_dict as $field) {
            if ($field['field_type'] === 'notes' && strpos($field['field_annotation'], '@M2C2') !== false) {
                $this->check_m2c2_setup($instrument_dict, $field);
            }
        }
    }

    function check_m2c2_setup($instrument_dict, $field) {
        $isValidM2C2 = false;

        // Regular expression to match @M2C2 and capture the JSON part
        $pattern = '/@M2C2=(\{[^}]+\})/';

        // Perform the regex match
        if (preg_match($pattern, $field['field_annotation'], $matches)) {
            // Extract the JSON string
            $jsonString = $matches[1];

            // Decode the JSON string into an associative array
            $m2c2Settings = json_decode($jsonString, true);

            // Check for JSON decoding errors
            if (json_last_error() === JSON_ERROR_NONE) {
                // Verify we have all the correct keys by cross-referencing M2C2_JSON
                $isValidM2C2 = true;

                foreach (M2C2_JSON as $key) {
                    if (!array_key_exists($key, $m2c2Settings)) {
                        $this->log(M2C2_LOGGING_INVALID_CONFIG, array(M2C2_LOGGING_INVALID_CONFIG_PARAM => "Missing '" . htmlspecialchars($key, ENT_QUOTES, "UTF-8") . "'."));
                        $isValidM2C2 = false;
                        break;
                    }
                }

                // Check that $m2c2Settings['activity_name'] is a string
                if (!isset($m2c2Settings['activity_name']) || !is_string($m2c2Settings['activity_name'])) {
                    $this->log(M2C2_LOGGING_INVALID_CONFIG, array(M2C2_LOGGING_INVALID_CONFIG_PARAM => "Activity name is not a string."));
                    $isValidM2C2 = false;
                }

                // Check that $m2c2Settings['activity_version'] is a string in format of X.X.X where X is a number
                if (!isset($m2c2Settings['activity_version']) || !preg_match('/^\d+\.\d+\.\d+$/', $m2c2Settings['activity_version'])) {
                    $this->log(M2C2_LOGGING_INVALID_CONFIG, array(M2C2_LOGGING_INVALID_CONFIG_PARAM => "Activity version is not in the format of X.X.X."));
                    $isValidM2C2 = false;
                }

                // Check that $m2c2Settings['redcap_fields'] is an array
                if (!isset($m2c2Settings['redcap_fields']) || !is_array($m2c2Settings['redcap_fields'])) {
                    $this->log(M2C2_LOGGING_INVALID_CONFIG, array(M2C2_LOGGING_INVALID_CONFIG_PARAM => "redcap_fields is not an array."));
                    $isValidM2C2 = false;
                }

                // Check all field names in $m2c2Settings['redcap_fields'] exist in the dictionary
                if (isset($m2c2Settings['redcap_fields']) && is_array($m2c2Settings['redcap_fields'])) {
                    foreach ($m2c2Settings['redcap_fields'] as $fieldName) {
                        $hasField = false;
                        foreach ($instrument_dict as $dictField) {
                            if ($dictField['field_name'] === $fieldName) {
                                $hasField = true;
                                break;
                            }
                        }

                        if (!$hasField) {
                            $this->log(M2C2_LOGGING_INVALID_CONFIG, array(M2C2_LOGGING_INVALID_CONFIG_PARAM => "Field " . htmlspecialchars($fieldName, ENT_QUOTES, 'UTF-8') . " is missing from data dictionary."));
                            $isValidM2C2 = false;
                            break;
                        }
                    }
                } else {
                    $isValidM2C2 = false;
                }
            } else {
                $this->log(M2C2_LOGGING_INVALID_CONFIG, array(M2C2_LOGGING_INVALID_CONFIG_PARAM => "JSON decoding error " . json_last_error_msg() . "."));
                $isValidM2C2 = false;
            }
        }

        if ($isValidM2C2) {
            // Call the new function
            $this->addM2C2($m2c2Settings);
        }
    }

    function addM2C2($m2c2Settings) {
        // Build params for logging, loop through the M2C2_LOGGING_LAUNCH_PARAMS array, and use REDCap log
        $params = [];
        foreach (M2C2_LOGGING_LAUNCH_PARAMS as $key => $value) {
            // Check if the current key is 'redcap_fields' to handle it differently
            if ($key === M2C2_LOGGING_LAUNCH_PARAMS['redcap_fields']) {
                $params[$key] = json_encode($m2c2Settings[$value]);
            } else {
                $params[$key] = $m2c2Settings[$value];
            }
        }
        $this->log(M2C2_LOGGING_LAUNCHING, $params);

        // used later in JavaScript
        echo '<script>var m2c2Settings = ' . json_encode($m2c2Settings, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . ';</script>';

        $this->includeJs(M2C2_JS_FILE, true);
    }

    protected function includeJs($file, $defer = false) {
        try {
            // Generate the script tag with optional defer attribute
            echo '<script src="' . htmlspecialchars($this->getUrl($file), ENT_QUOTES, 'UTF-8') . '"' 
                 . ($defer ? ' defer' : '') 
                 . '></script>';
        } catch (Exception $ex) {
            $this->log(M2C2_LOGGING_INVALID_CONFIG . "M2C2 External Module Error: " . $ex->getMessage());
        }
    }

    protected function saveToFileRepository($filename, $file_contents, $file_extension)
    {
        // Upload the compiled report to the File Repository
        $errors = array();
        $database_success = FALSE;
        $upload_success = FALSE;

        $dummy_file_name = $filename;
        $dummy_file_name = preg_replace("/[^a-zA-Z-._0-9]/","_",$dummy_file_name);
        $dummy_file_name = str_replace("__","_",$dummy_file_name);
        $dummy_file_name = str_replace("__","_",$dummy_file_name);

        $stored_name = date('YmdHis') . "_pid" . $this->pid . "_" . generateRandomHash(6) . ".$file_extension";

        $upload_success = file_put_contents(EDOC_PATH . $stored_name, $file_contents);

        if ($upload_success !== FALSE)
        {
            $dummy_file_size = $upload_success;
            $dummy_file_type = "application/$file_extension";

            $file_repo_name = date("Y/m/d H:i:s");

            $query = $this->framework->createQuery();
            $query->add("INSERT INTO redcap_docs (project_id,docs_date,docs_name,docs_size,docs_type,docs_comment,docs_rights) VALUES (?, CURRENT_DATE, ?, ?, ?, ?, NULL)",
                        [$this->pid, "$dummy_file_name.$file_extension", $dummy_file_size, $dummy_file_type, "$file_repo_name - $filename ($this->userid)"]);

            if ($query->execute())
            {
                $docs_id = db_insert_id();

                $query = $this->framework->createQuery();
                $query->add("INSERT INTO redcap_edocs_metadata (stored_name,mime_type,doc_name,doc_size,file_extension,project_id,stored_date) VALUES(?,?,?,?,?,?,?)",
                            [$stored_name, $dummy_file_type, "$dummy_file_name.$file_extension", $dummy_file_size, $file_extension, $this->pid, date('Y-m-d H:i:s')]);

                if ($query->execute())
                {
                    $doc_id = db_insert_id();

                    $query = $this->framework->createQuery();
                    $query->add("INSERT INTO redcap_docs_to_edocs (docs_id,doc_id) VALUES (?,?)", [$docs_id, $doc_id]);

                    if ($query->execute())
                    {
                        $context_msg_insert = "{$lang['docs_22']} {$lang['docs_08']}";

                        // Logging
                        REDCap::logEvent("Custom Template Engine - Uploaded document to file repository", "Successfully uploaded $filename");
                        $context_msg = str_replace('{fetched}', '', $context_msg_insert);
                        $database_success = TRUE;
                    }
                    else
                    {
                        /* if this failed, we need to roll back redcap_edocs_metadata and redcap_docs */
                        $query = $this->framework->createQuery();
                        $query->add("DELETE FROM redcap_edocs_metadata WHERE doc_id=?", [$doc_id]);
                        $query->execute();

                        $query = $this->framework->createQuery();
                        $query->add("DELETE FROM redcap_docs WHERE docs_id=?", [$docs_id]);
                        $query->execute();

                        $this->deleteRepositoryFile($stored_name);
                    }
                }
                else
                {
                    /* if we failed here, we need to roll back redcap_docs */
                    $query = $this->framework->createQuery();
                    $query->add("DELETE FROM redcap_docs WHERE docs_id=?", [$docs_id]);
                    $query->execute();

                    $this->deleteRepositoryFile($stored_name);
                }
            }
            else
            {
                /* if we failed here, we need to delete the file */
                $this->deleteRepositoryFile($stored_name);
            }
        }

        if ($database_success === FALSE)
        {
            $context_msg = "<b>{$lang['global_01']}{$lang['colon']} {$lang['docs_47']}</b><br>" . $lang['docs_65'] . ' ' . maxUploadSizeFileRespository().'MB'.$lang['period'];

            if (SUPER_USER)
            {
                $context_msg .= '<br><br>' . $lang['system_config_69'];
            }

            return $context_msg;
        }

        return true;
    }
}
