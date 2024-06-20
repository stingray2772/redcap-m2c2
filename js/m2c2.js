// Description: This script is used to embed the m2c2 iframe into the page and listen for messages from the iframe.
// The script will hide the fields in m2c2Settings.fields and listen for messages from the iframe.
// If the message is "m2c2kit-trial-done" or "newData" then the script will parse the data and get the last trial.
// The script will get the trial_index from the last trial and get the field name from m2c2Settings.fields at the trial_index.
// The script will set the value of the field to the newData.
// If the message is "m2c2kit-done" then the script will hide the iframe.

"use strict";

console.log("REDCap loaded m2c2.js");

window.addEventListener("load", function () {

    if (m2c2Settings !== "" && m2c2Url !== "") {
        console.log(m2c2Settings);
        hideFields();
        var firstField = m2c2Settings.fields[0];
        if (firstField) {
            if ($("#" + firstField).length) {
                var iframeContainer = '<div id="overlay-iframe-container" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 9999; background: rgba(0, 0, 0, 0.5);"> ' +
                    '<iframe id="overlay-iframe" src="' + decodeURIComponent(m2c2Url) + '" style="height: 100%; width: 100%; border: none;"></iframe></div>';
                $("body").append(iframeContainer);

                window.addEventListener("message", function (event) {
                    if (event.origin !== "https://prod.m2c2kit.com") {
                        console.error("Ignoring message from unexpected origin: ", event.origin);
                        return;
                    }

                    if (event.data.name === "m2c2kit-trial-done" || event.data.name === "newData") {
                        var data = JSON.parse(event.data.data);

                        if (data && data.trials && Array.isArray(data.trials)) {
                            var lastTrial = data.trials[data.trials.length - 1];
                            var trialNum = lastTrial.trial_index;

                            if (Number.isInteger(trialNum) && trialNum >= 0 && trialNum < m2c2Settings.fields.length) {
                                var fieldName = m2c2Settings.fields[trialNum];
                                $("#" + fieldName).val(JSON.stringify(lastTrial));
                            } else {
                                console.error("Invalid trial index: ", trialNum);
                            }
                        } else {
                            console.error("Invalid trial data structure: ", data);
                        }
                    } else if (event.data.name === "m2c2kit-done") {
                        $("#overlay-iframe-container").hide();
                    } else {
                        console.error("Unexpected event name: ", event.data.name);
                    }
                });
            } else {
                console.error("First field element not found in page: ", firstField);
            }
        } else {
            console.error("No fields specified in m2c2Settings.");
        }
    } else {
        console.error("m2c2Settings or m2c2Url is undefined.");
    }
});

function hideFields() {
    try {
        if (typeof m2c2Settings !== "undefined" && m2c2Settings !== null && 
            m2c2Settings.hasOwnProperty("fields") && Array.isArray(m2c2Settings.fields)) {
            m2c2Settings.fields.forEach(function (field) {
                $("#" + field).closest("tr").hide();
            });
        }

        // hide GUI items that are not needed
        $("#surveyinstructions").hide();
        $("#surveytitlelogo").hide();
    } catch(ex) {
        console.error(ex);
    }
}