<?php
// Set the namespace defined in your config file
namespace PSU\M2C2;

use \REDCap;
use \Survey;

// Declare your module class, which must extend AbstractExternalModule 
class M2C2 extends \ExternalModules\AbstractExternalModule
{
    protected $is_survey = 0;

    protected  static $Tags =  array('@M2C2' => array('description' => 'Basic M2C2 description goes here') );

    // Define the JSON field for the M2C2 annotation
    protected static $M2C2_JSON = array('type', 'trials');

    function redcap_survey_page($project_id, $record, $instrument, $event_id, $group_id, $survey_hash, $response_id, $repeat_instance) {
        global $Proj;
        // echo "<pre>";
        // print_r($Proj);
        // echo "</pre>";

        // $settings = $this->getProjectSettings();
        // echo "<pre>";
        // print_r($settings);
        // echo "</pre>";

        // Get Instrument Data Dictionary
        $instrument_dict_json =  REDCap::getDataDictionary($this->getProjectId(), "json", False, Null, array($instrument));
        $instrument_dict =  json_decode($instrument_dict_json,true);

        foreach($instrument_dict as $field){
            // echo "Field Name: " . $field['field_name'] . "<br>";
            // echo "Field Type: " . $field['field_type'] . "<br>";
            // echo "Field Annotation: " . $field['field_annotation'] . "<br>";
            // echo (($field['field_type'] == "notes") ? "Field type is notes" : "Field type is not notes" ) ."<br>";
            // echo ((strpos($field['field_annotation'], "@M2C2") !== false) ? "Field annotation is m2c2" : "Field annotation is not m2c2" ) ."<br>";
            if ($field['field_type'] == "notes" && strpos($field['field_annotation'], "@M2C2") !== false) {
                $isValidM2C2 = false;
                // Pick out the 
                //echo "<script>alert('Found M2C2');</script>";
                // echo "<pre>";
                // print_r($field);
                // echo "</pre>";

                // Regular expression to match @M2C2 and capture the JSON part
                $pattern = '/@M2C2=({.*?})/';

                // Array to store the extracted JSON
                $jsonArray = [];

                // Perform the regex match
                if (preg_match($pattern, $field['field_annotation'], $matches)) {
                    // Extract the JSON string
                    $jsonString = $matches[1];
                    
                    // Decode the JSON string into an associative array
                    $jsonArray = json_decode($jsonString, true);
                    
                    // Check for JSON decoding errors
                    if (json_last_error() === JSON_ERROR_NONE) {
                        // Verify we have all the correct keys by cross referencing $M2C2_JSON
                        $isValidM2C2 = true;
                        foreach (self::$M2C2_JSON as $key) {
                            if (!array_key_exists($key, $jsonArray)) {
                                $isValidM2C2 = false;
                                break;
                            }
                        }
                    } else {
                        $isValidM2C2 = false;
                    }
                }

                // echo "isValidM2C2: " . ($isValidM2C2 ? "true" : "false") . "<br>";
                if ($isValidM2C2) {
                    // echo "M2C2 Type: " . $jsonArray['type'] . "<br>";
                    // echo "M2C2 Trials: " . $jsonArray['trials'] . "<br>";

                    // $url = 'https://beta.m2c2kit.com/m2c2kit/ntc/index.html?activity_name=symbol-search';
                    // $url .= '&n_trials=' . $jsonArray['trials'];
                    // $url .= '&participant_id=None&session_id=None&study_id=None&api_key=e16a3173-a09e-43b5-81b2-d36369d64fe8';

                    // echo '<div id="overlay-iframe-container" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 9999; background: rgba(0, 0, 0, 0.5);">
                    //     <iframe id="overlay-iframe" src="' . $url . '" style="height: 100%; width: 100%; border: none;"></iframe>
                    // </div>';

                    // echo '<style>
                    // html, body {
                    //     margin: 0;
                    //     padding: 0;
                    //     height: 100%;
                    //     overflow: hidden; /* Prevents scrolling */
                    // }
                    // </style>';

                    // echo '<script>
                    // function showIframe() {
                    //     document.getElementById("overlay-iframe-container").style.display = "flex";
                    // }

                    // function hideIframe() {
                    //     document.getElementById("overlay-iframe-container").style.display = "none";
                    // }

                    // document.addEventListener("DOMContentLoaded", function() {
                    //     showIframe(); // Show iframe when the page loads
                    // });
                    // </script>';

                    // echo '<script>
                    //     var embeddedDataPrefix = "TRIAL_DATA_";
                    //     window.addEventListener("message", (event) => {
                    //         console.log("event fired: " + event.data["name"]);
                    //         if (event.data["name"] === "m2c2kit-trialdone" || event.data["name"] === "newData") {
                    //             console.log("found new event: " + event.data["name"]);
                    //             var data = JSON.parse(event.data["data"]);
                    //             var trial_num = data["trial_index"];

                    //             // Store data into embedded variable
                    //             // Replace "mtooctoo" with your desired field name
                    //             $("#mtooctoo").val(JSON.stringify(data));
                    //             console.log("logging data for trial: " + trial_num);

                    //             // Hide the iframe container
                    //             $("#overlay-iframe-container").hide();
                    //         } else {
                    //             console.log("Uncaught event received in parent window!: ");
                    //             console.log(event);
                    //         }
                    //     });
                    //     </script>';
                    if ($isValidM2C2) {
                        // Construct the URL with the specified number of trials
                        $url = 'https://beta.m2c2kit.com/m2c2kit/ntc/index.html?activity_name=symbol-search';
                        $url .= '&n_trials=' . $jsonArray['trials'];
                        $url .= '&participant_id=None&session_id=None&study_id=None&api_key=e16a3173-a09e-43b5-81b2-d36369d64fe8';
                    
                        echo '<div id="overlay-iframe-container" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 9999; background: rgba(0, 0, 0, 0.5);">
                            <iframe id="overlay-iframe" src="' . $url . '" style="height: 100%; width: 100%; border: none;"></iframe>
                        </div>';
                        echo '<script>
                        var embeddedDataPrefix = "TRIAL_DATA_";
                        window.addEventListener("message", function(event) {
                            console.log("event fired: " + event.data.name);
                            if (event.data.name === "m2c2kit-trial-done" || event.data.name === "newData") {
                                console.log("found new event: " + event.data.name);
                                var data = JSON.parse(event.data.data);
                                var trial_num = data.trial_index;
                    
                                // Store data into embedded variable
                                // Replace "mtooctoo" with your desired field name
                                $("#mtooctoo").val(JSON.stringify(data));
                                console.log("logging data for trial: " + trial_num);
                    
                                // Hide the iframe container
                                $("#overlay-iframe-container").hide();
                            } else if (event.data.name === "m2c2kit-done") {
                                console.log("m2c2kit-done event received");
                                // Additional handling for m2c2kit-done event if needed
                            } else if (event.data.name === "m2c2kit-uploaded-data") {
                                console.log("m2c2kit-uploaded-data event received");
                                // Additional handling for m2c2kit-uploaded-data event if needed
                            } else {
                                console.log("Uncaught event received in parent window!: ");
                                console.log(event);
                            }
                        });
                        </script>';
                    }
                    
                    
                    
                    //$this->includeJs('js/m2c2-redcap.js');
                    //echo "calling enableM2C2('{$field['field_name']}')<br>";
                    //echo '<script>$(document).ready(function() { enableM2C2("' . addslashes($field['field_name']) . '");});</script>';
                }
            }
        }
    }

    protected function includeJs($file) {
        // Use this function to use your JavaScript files in the frontend
        echo "Loading $file<br>";
        echo "<script>alert('Loading $file');</script>";
        echo '<script src="' . $this->getUrl($file) . '"></script>';
    }
}