// Description: This script is used to embed the m2c2 iframe into the page and listen for messages from the iframe.
// The script will hide the fields in m2c2Settings.fields and listen for messages from the iframe.
// If the message is "m2c2kit-trial-done" or "newData" then the script will parse the data and get the last trial.
// The script will get the trial_index from the last trial and get the field name from m2c2Settings.fields at the trial_index.
// The script will set the value of the field to the newData.
// If the message is "m2c2kit-done" then the script will hide the iframe.
window.addEventListener("load", function () {
    if (typeof m2c2Settings !== "undefined" && typeof m2c2Url !== "undefined") {
        hideFields();
        var firstField = m2c2Settings.fields[0];
        if (firstField) {
            if ($("#" + firstField).length) {
                var iframeContainer = '<div id="overlay-iframe-container" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 9999; background: rgba(0, 0, 0, 0.5);"><iframe id="overlay-iframe" src="' + m2c2Url + '" style="height: 100%; width: 100%; border: none;"></iframe></div>';
                $("body").append(iframeContainer);
                window.addEventListener("message", function (event) {
                    console.log("event fired: " + event.data.name);
                    if (event.data.name === "m2c2kit-trial-done" || event.data.name === "newData") {
                        console.log("found new event: " + event.data.name);
                        console.log("data: " + event.data.data);

                        // Get the data from the newest trial
                        var data = JSON.parse(event.data.data).trials[data.trials.length - 1];
                        var trial_num = data.trial_index;

                        // Store data into embedded variable dynamically based on trial_num
                        var fieldName = m2c2Settings.fields[trial_num]; // Adjusted to start from 0 index
                        $("#" + fieldName).val(JSON.stringify(newData));
                    } else if (event.data.name === "m2c2kit-done") {
                        console.log("m2c2kit-done event received");

                        // Hide the iframe container
                        $("#overlay-iframe-container").hide();
                    }
                });
            }
        }
    }
});

function hideFields() {
    // hide the tr above the fields within m2c2Settings.fields
    m2c2Settings.fields.forEach(function (field) {
        $("#" + field).closest("tr").hide();
    });
}
