<?php
namespace PSU\M2C2;

use \REDCap;
use \Survey;

class M2C2 extends \ExternalModules\AbstractExternalModule
 {
    protected $is_survey = 0;

    protected static $Tags =  array( '@M2C2' => array( 'description' => 'Basic M2C2 description goes here' ) );

    // Define the JSON field for the M2C2 annotation
    protected static $M2C2_JSON = array( 'activity_name', 'api_key', 'study_id', 'width', 'height', 'show_quit_button', 'participant_id', 'admin_type', 'fields' );
    protected static $M2C2_SETTINGS_ACTIVITY_NAMES = array( 'symbol-search', 'grid-memory', 'color-shapes', 'color-dots' );
    protected static $M2C2_SETTINGS_ADMIN_TYPES = array( 'qualtrics' );

    function redcap_survey_page( $project_id, $record, $instrument, $event_id, $group_id, $survey_hash, $response_id, $repeat_instance ) {
        global $Proj;

        // Get Instrument Data Dictionary
        $instrument_dict_json =  REDCap::getDataDictionary( $this->getProjectId(), 'json', False, Null, array( $instrument ) );
        $instrument_dict =  json_decode( $instrument_dict_json, true );

        foreach ( $instrument_dict as $field ) {
            if ( $field[ 'field_type' ] == 'notes' && strpos( $field[ 'field_annotation' ], '@M2C2' ) !== false ) {
                $this->check_m2c2_setup( $instrument_dict, $field );
            }
        }
    }

    function check_m2c2_setup($instrument_dict, $field) {
        $isValidM2C2 = false;

        // Regular expression to match @M2C2 and capture the JSON part
        //$pattern = '/@M2C2=({.*?})/';
        $pattern = '/@M2C2=(\{[^}]+\})/';

        // Array to store the extracted JSON
        $m2c2Settings = [];

        // Perform the regex match
        if ( preg_match( $pattern, $field[ 'field_annotation' ], $matches ) ) {
            // Extract the JSON string
            $jsonString = $matches[ 1 ];

            // Decode the JSON string into an associative array
            $m2c2Settings = json_decode( $jsonString, true );

            // Check for JSON decoding errors
            if ( json_last_error() === JSON_ERROR_NONE ) {
                // Verify we have all the correct keys by cross-referencing $M2C2_JSON
                $isValidM2C2 = true;

                // Check for top-level keys
                foreach ( self::$M2C2_JSON as $key ) {
                    if ( !array_key_exists( $key, $m2c2Settings ) ) {
                        $isValidM2C2 = false;
                        break;
                    }
                }

                // Check that $m2c2Settings[ 'fields' ] is an array
                if ( !isset( $m2c2Settings[ 'fields' ] ) || !is_array( $m2c2Settings[ 'fields' ] ) ) {
                    $isValidM2C2 = false;
                }

                // Check all field names in $m2c2Settings[ 'fields' ] exist in the dictionary
                if ( isset( $m2c2Settings[ 'fields' ] ) && is_array( $m2c2Settings[ 'fields' ] ) ) {
                    foreach ( $m2c2Settings[ 'fields' ] as $trialNum => $fieldName ) {
                        $hasField = false;
                        foreach ( $instrument_dict as $dictField ) {
                            if ( $dictField[ 'field_name' ] == $fieldName ) {
                                $hasField = true;
                                break;
                            }
                        }

                        if ( !$hasField ) {
                            echo "<script>console.log('missing at least one field');</script>";
                            $isValidM2C2 = false;
                            break;
                        }
                    }

                } else {
                    $isValidM2C2 = false;
                    // trialdata is missing or not an array
                }

                // Check that $m2c2Settings[ 'activity_name' ] is one of the valid activity names
                if ( !in_array( $m2c2Settings[ 'activity_name' ], self::$M2C2_SETTINGS_ACTIVITY_NAMES ) ) {
                    echo "<script>console.log('activity_name is not valid');</script>";
                    $isValidM2C2 = false;
                }

                // Check that $m2c2Settings[ 'admin_type' ] is one of the valid admin types
                if ( !in_array( $m2c2Settings[ 'admin_type' ], self::$M2C2_SETTINGS_ADMIN_TYPES ) ) {
                    echo "<script>console.log('admin_type is not valid');</script>";
                    $isValidM2C2 = false;
                }

                // check that $m2c2Settings[ 'width' ] and $m2c2Settings[ 'height' ] are integers
                if ( !is_int( $m2c2Settings[ 'width' ] ) || !is_int( $m2c2Settings[ 'height' ] ) ) {
                    echo "<script>console.log('height/width is not valid');</script>";
                    $isValidM2C2 = false;
                }

                // check that $m2c2Settings[ 'show_quit_button' ] is a boolean
                if ( !is_bool( $m2c2Settings[ 'show_quit_button' ] ) ) {
                    echo "<script>console.log('show_quit_button is not valid, type is " . $type . "');</script>";
                    $isValidM2C2 = false;
                }
            } else {
                echo "<script>alert('JSON decoding error: " . json_last_error() . "');</script>";
                // '0' means 'no error'
                $isValidM2C2 = false;
                // JSON decoding error
            }
        }

        if ( $isValidM2C2 ) {
            // Call the new function
            $this->addM2C2($m2c2Settings );
        }
    }

    function addM2C2( $m2c2Settings ) {
        
        $url = 'https://prod.m2c2kit.com/m2c2kit/nt/index.html?';
        $url .= 'activity_name=' . $m2c2Settings[ 'activity_name' ] ;
        $url .= '&api_key=' . $m2c2Settings[ 'api_key' ] ;
        $url .= '&study_id=' . $m2c2Settings[ 'study_id' ] ;
        $url .= '&number_of_trials=' . count($m2c2Settings[ 'fields' ]) . '::int';
        $url .= '&width=' . $m2c2Settings[ 'width' ] ;
        $url .= '&height=' . $m2c2Settings[ 'height' ] ;
        $url .= '&show_quit_button=' . $m2c2Settings[ 'height' ] . '::boolean';
        $url .= '&participant_id=None';
        $url .= '&session_id=None';
        $url .= '&admin_type=' . $m2c2Settings[ 'admin_type' ] ;
        echo '<script> var m2c2Url = ' . json_encode( $url ) . ';</script>';
        echo '<script> var m2c2Settings = ' . json_encode( $m2c2Settings ) . ';</script>';

        // echo '<div id="overlay-iframe-container" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 9999; background: rgba(0, 0, 0, 0.5);">
        //     <iframe id="overlay-iframe" src="' . $url . '" style="height: 100%; width: 100%; border: none;"></iframe></div>';

        $this->includeJs( 'js/m2c2.js' );
    }

    protected function includeJs( $file ) {
        echo '<script src="' . $this->getUrl( $file ) . '"></script>';
    }
}
