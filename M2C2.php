<?php
namespace PSU\M2C2;

use \REDCap;
use \Survey;

class M2C2 extends \ExternalModules\AbstractExternalModule
{
    protected $is_survey = 0;

    protected static $Tags =  array('@M2C2' => array('description' => 'Basic M2C2 description goes here'));

    // Define the JSON field for the M2C2 annotation
    protected static $M2C2_JSON = array('type', 'trials', 'trialdata');

    function redcap_survey_page($project_id, $record, $instrument, $event_id, $group_id, $survey_hash, $response_id, $repeat_instance) {
        global $Proj;

        // Get Instrument Data Dictionary
        $instrument_dict_json =  REDCap::getDataDictionary($this->getProjectId(), "json", False, Null, array($instrument));
        $instrument_dict =  json_decode($instrument_dict_json, true);

        foreach($instrument_dict as $field) {
            if ($field['field_type'] == "notes" && strpos($field['field_annotation'], "@M2C2") !== false) {
                $isValidM2C2 = false;

                // Regular expression to match @M2C2 and capture the JSON part
                //$pattern = '/@M2C2=({.*?})/';
                $pattern = '/@M2C2=(\{[^}]+\})/';

                // Array to store the extracted JSON
                $m2c2Settings = [];

                // Perform the regex match
                if (preg_match($pattern, $field['field_annotation'], $matches)) {
                    // Extract the JSON string
                    $jsonString = $matches[1];
                    
                    // Decode the JSON string into an associative array
                    $m2c2Settings = json_decode($jsonString, true);

                    echo "M2C2 Settings: <br>";
                    echo "<pre>";
                    print_r($m2c2Settings);
                    echo "</pre>";

                    // Check for JSON decoding errors
                    if (json_last_error() === JSON_ERROR_NONE) {
                        // Verify we have all the correct keys by cross-referencing $M2C2_JSON
                        $isValidM2C2 = true;
                        
                        // Check if all keys in trialdata exist in the dictionary
                        if (isset($m2c2Settings['trialdata']) && is_array($m2c2Settings['trialdata'])) {
                            foreach ($m2c2Settings['trialdata'] as $trialNum => $fieldName) {
                                $hasField = false;
                                foreach ($instrument_dict as $dictField) {
                                    if ($dictField['field_name'] == $fieldName) {
                                        $hasField = true;
                                        break;
                                    }
                                }
                                
                                if (!$hasField) {
                                    $isValidM2C2 = false;
                                    break;
                                }
                            }

                            echo "Checking for trialdata keys in dictionary: <br>";
                            echo "<pre>";
                            print_r($m2c2Settings['trialdata']);
                            echo "</pre>";

                            // display isValidM2C2 true/false
                            echo "isValidM2C2: " . ($isValidM2C2 ? "true" : "false") . "<br>";

                        } else {
                            $isValidM2C2 = false; // trialdata is missing or not an array
                        }

                        // Additional check for top-level keys
                        foreach (self::$M2C2_JSON as $key) {
                            if (!array_key_exists($key, $m2c2Settings)) {
                                $isValidM2C2 = false;
                                break;
                            }
                        }
                    } else {
                        echo "<script>alert('JSON decoding error: " . json_last_error() . "');</script>"; // "0" means "no error"
                        $isValidM2C2 = false; // JSON decoding error
                    }
                }

                if ($isValidM2C2) {
                    // Call the new function
                    $this->processM2C2Field($field['field_name'], $m2c2Settings);
                }
            }
        }
    }

    // New function to process the M2C2 field
    function processM2C2Field($field_name, $m2c2Settings) {
        $m2c2SettingsJSON = json_encode($m2c2Settings);
        
        // Link format
        // https://prod.m2c2kit.com/m2c2kit/nt/index.html
        //      ?activity_name=[symbol-search, grid-memory, color-shapes, color-dots]
        //      &api_key=demo
        //      &study_id=demo
        //      &number_of_trials=[NUM_TRIALS]::int
        //      &width=400
        //      &height=1000
        //      &show_quit_button=false::boolean
        //      &participant_id=None
        //      &session_id=None
        //      &admin_type=qualtrics

        $url = 'https://prod.m2c2kit.com/m2c2kit/nt/index.html?';
        $url .= 'activity_name=symbol-search';
        $url .= '&api_key=demo&study_id=demo';
        $url .= '&number_of_trials=' . $m2c2Settings['trials'] . "::int";
        $url .= '&width=400';
        $url .= '&height=1000';
        $url .= '&show_quit_button=false::boolean';
        $url .= '&participant_id=None';
        $url .= '&session_id=None';
        $url .= '&admin_type=qualtrics';

        
        echo '<div id="overlay-iframe-container" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 9999; background: rgba(0, 0, 0, 0.5);">
            <iframe id="overlay-iframe" src="' . $url . '" style="height: 100%; width: 100%; border: none;"></iframe>
        </div>';

        echo '<script>
        var m2c2Settings = ' . $m2c2SettingsJSON . ';
        var embeddedDataPrefix = "TRIAL_DATA_";
        window.addEventListener("message", function(event) {
            console.log("event fired: " + event.data.name);
            if (event.data.name === "m2c2kit-trial-done" || event.data.name === "newData") {
                console.log("found new event: " + event.data.name);
                console.log("data: " + event.data.data);
                var data = JSON.parse(event.data.data);

                // data will consist of multiple trials, but we want the last trial
                var newData = data.trials[data.trials.length - 1];
                var trial_num = newData.trial_index;

                // Store data into embedded variable dynamically based on trial_num
                var fieldName = m2c2Settings.trialdata[trial_num]; // Adjusted to start from 0 index
                $("#" + fieldName).val(JSON.stringify(data));
            } else if (event.data.name === "m2c2kit-done") {
                console.log("m2c2kit-done event received");
                
                // Hide the iframe container
                $("#overlay-iframe-container").hide();
            }
        });
        </script>';
    }
    
    protected function includeJs($file) {
        // Use this function to use your JavaScript files in the frontend
        echo "Loading $file<br>";
        echo "<script>alert('Loading $file');</script>";
        echo '<script src="' . $this->getUrl($file) . '"></script>';
    }
}
