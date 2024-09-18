<?php
namespace PSU\M2C2;

use \REDCap;
use \Survey;

const M2C2_LOGGING_INVALID_CONFIG = "Invalid M2C2 config.";
const M2C2_LOGGING_INVALID_CONFIG_PARAM = "error_message";
const M2C2_LOGGING_LAUNCHING = "M2C2 launching.";
const M2C2_REQUIRED_PARAMS = array(
    'activity_name',
    'activity_version',
    'redcap_fields');
const M2C2_LOGGING_LAUNCH_PARAMS = array(
    'activity_name' => 'activity_name',
    'activity_version' => 'activity_version',
    'redcap_fields' => 'redcap_fields',
    'auto_complete' => 'auto_complete');
const M2C2_PURGE_URL = 'm2c2-log-purge.php';
const M2C2_JS_FILE = 'js/m2c2.js';

class M2C2 extends \ExternalModules\AbstractExternalModule {

    function redcap_survey_page($project_id, $record, $instrument, $event_id, $group_id, $survey_hash, $response_id, $repeat_instance) {
        $this->initializeJavascriptModuleObject();
        echo '<script>const redcapModule = ' . $this->framework->getJavascriptModuleObjectName() . ';</script>';

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
                // Verify we have all the correct keys by cross-referencing M2C2_REQUIRED_PARAMS
                $isValidM2C2 = true;

                foreach (M2C2_REQUIRED_PARAMS as $key) {
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
                            if ($fieldName === $m2c2Settings['redcap_fields'][0]) {
                                $m2c2Settings['end_message'] = $dictField['field_label'];
                            }

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

                // Check that $m2c2Settings['auto_complete'] is a boolean (if provided)
                if (isset($m2c2Settings['auto_complete']) && !is_bool($m2c2Settings['auto_complete'])) {
                    $this->log(M2C2_LOGGING_INVALID_CONFIG, array(M2C2_LOGGING_INVALID_CONFIG_PARAM => "auto_complete is not a boolean."));
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
            } else if (array_key_exists($value, $m2c2Settings)) {
                $params[$key] = $m2c2Settings[$value];
            } else {
                // Handle optional parameters
                if ($key === M2C2_LOGGING_LAUNCH_PARAMS['auto_complete']) {
                    $params[$key] = false;
                }
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
}
