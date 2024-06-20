<?php
namespace PSU\M2C2;

use \REDCap;
use \Survey;

const M2C2_BASE_URL = "https://prod.m2c2kit.com/m2c2kit/nt/index.html?";
const M2C2_LOGGING_INVALID_CONFIG = "Invalid M2C2 config.";
const M2C2_LOGGING_INVALID_CONFIG_PARAM = "error_message";
const M2C2_LOGGING_LAUNCHING = "M2C2 launching.";
const M2C2_JSON = array(
    'activity_name',
    'api_key',
    'study_id',
    'width',
    'height',
    'show_quit_button',
    'participant_id',
    'admin_type',
    'fields');
const M2C2_LAUNCH_LOG_PARAMS = array(
    'base_url' => 'base_url',
    'activity_name' => 'activity_name',
    'api_key' => 'api_key',
    'study_id' => 'study_id',
    'width' => 'width',
    'height' => 'height',
    'show_quit_button' => 'show_quit_button',
    'participant_id' => 'participant_id',
    'admin_type' => 'admin_type',
    'redcap_fields' => 'redcap_fields',
    'number_of_trials' => 'number_of_trials',
    'session_id' => 'session_id'
);
const M2C2_SETTINGS_ACTIVITY_NAMES = array('symbol-search', 'grid-memory', 'color-shapes', 'color-dots');
const M2C2_SETTINGS_ADMIN_TYPES = array('qualtrics');
const M2C2_PURGE_URL = 'm2c2-log-purge.php';
const M2C2_JS_FILE = 'js/m2c2.js';

class M2C2 extends \ExternalModules\AbstractExternalModule {

    function redcap_survey_page($project_id, $record, $instrument, $event_id, $group_id, $survey_hash, $response_id, $repeat_instance) {
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

                // Check that $m2c2Settings['fields'] is an array
                if (!isset($m2c2Settings['fields']) || !is_array($m2c2Settings['fields'])) {
                    $this->log(M2C2_LOGGING_INVALID_CONFIG, array(M2C2_LOGGING_INVALID_CONFIG_PARAM => "Fields is not an array."));
                    $isValidM2C2 = false;
                }

                // Check all field names in $m2c2Settings['fields'] exist in the dictionary
                if (isset($m2c2Settings['fields']) && is_array($m2c2Settings['fields'])) {
                    foreach ($m2c2Settings['fields'] as $fieldName) {
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

                // Check that $m2c2Settings['activity_name'] is one of the valid activity names
                if (!in_array($m2c2Settings['activity_name'], M2C2_SETTINGS_ACTIVITY_NAMES, true)) {
                    $this->log(M2C2_LOGGING_INVALID_CONFIG, array(M2C2_LOGGING_INVALID_CONFIG_PARAM => "Activity_name is not valid."));
                    $isValidM2C2 = false;
                }

                // Check that $m2c2Settings['admin_type'] is one of the valid admin types
                if (!in_array($m2c2Settings['admin_type'], M2C2_SETTINGS_ADMIN_TYPES, true)) {
                    $this->log(M2C2_LOGGING_INVALID_CONFIG, array(M2C2_LOGGING_INVALID_CONFIG_PARAM => "Admin_type is not valid."));
                    $isValidM2C2 = false;
                }

                // Check that $m2c2Settings['width'] is an integer
                if (!is_int($m2c2Settings['width'])) {
                    $this->log(M2C2_LOGGING_INVALID_CONFIG, array(M2C2_LOGGING_INVALID_CONFIG_PARAM => "Width is not valid."));
                    $isValidM2C2 = false;
                }

                // Check that and $m2c2Settings['height'] is an integer
                if (!is_int($m2c2Settings['height'])) {
                    $this->log(M2C2_LOGGING_INVALID_CONFIG, array(M2C2_LOGGING_INVALID_CONFIG_PARAM => "Height is not valid."));
                    $isValidM2C2 = false;
                }

                // Check that $m2c2Settings['show_quit_button'] is a boolean
                if (!is_bool($m2c2Settings['show_quit_button'])) {
                    $this->log(M2C2_LOGGING_INVALID_CONFIG, array(M2C2_LOGGING_INVALID_CONFIG_PARAM => "Show_quit_button is not valid."));
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

    function buildURL($params, $loggingParams) {
        $this->log(M2C2_LOGGING_LAUNCHING, $params + $loggingParams);

        $http_query = http_build_query($params);
        $url = M2C2_BASE_URL . $http_query;

        return $url;
    }

    function addM2C2($m2c2Settings) {
        echo "<script>console.log('" . __LINE__ . "');</script>";
        $params = [
            M2C2_LAUNCH_LOG_PARAMS["activity_name"] => $m2c2Settings['activity_name'],
            M2C2_LAUNCH_LOG_PARAMS["api_key"] => $m2c2Settings['api_key'],
            M2C2_LAUNCH_LOG_PARAMS["study_id"] => $m2c2Settings['study_id'],
            M2C2_LAUNCH_LOG_PARAMS["number_of_trials"] => count($m2c2Settings['fields']) . '::int',
            M2C2_LAUNCH_LOG_PARAMS["width"] => $m2c2Settings['width'],
            M2C2_LAUNCH_LOG_PARAMS["height"] => $m2c2Settings['height'],
            M2C2_LAUNCH_LOG_PARAMS["show_quit_button"] => ($m2c2Settings['show_quit_button'] ? "true" : "false") . '::boolean',
            M2C2_LAUNCH_LOG_PARAMS["participant_id"] => 'None',
            M2C2_LAUNCH_LOG_PARAMS["session_id"] => 'None',
            M2C2_LAUNCH_LOG_PARAMS["admin_type"] => $m2c2Settings['admin_type']
        ];

        $loggingParams = [
            M2C2_LAUNCH_LOG_PARAMS["base_url"] => M2C2_BASE_URL,
            M2C2_LAUNCH_LOG_PARAMS["redcap_fields"] => implode(", ", $m2c2Settings['fields'])
        ];

        $url = $this->buildURL($params, $loggingParams);

        echo '<script>var m2c2Url = ' . json_encode($url, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . ';</script>';
        echo '<script>var m2c2Settings = ' . json_encode($m2c2Settings, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . ';</script>';

        $this->includeJs(M2C2_JS_FILE);
    }

    protected function includeJs($file) {
        try {
            echo '<script src="' . htmlspecialchars($this->getUrl($file), ENT_QUOTES, 'UTF-8') . '"></script>';
        } catch (Exception $ex) {
            $this->log(M2C2_LOGGING_INVALID_CONFIG . "M2C2 External Module Error: ' . $ex->getMessage() . '");
        }
    }
}
