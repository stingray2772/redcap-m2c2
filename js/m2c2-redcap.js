function enableM2C2($fieldName) {
    alert("enableM2C2 function called");

    $(document).ready(function() {
        // if id fieldname exists on page, then enable m2c2
        if ($('#' + $fieldName).length === 0) {
            alert("M2C2 is not enabled for this survey.");
            return false;
        } else {
            alert("M2C2 is enabled for this survey. Please complete the following task to proceed.");
        }

        // $('#pagecontent').remove();
        console.log("removing survey instructions");
        $('#surveyinstructions').remove();
        console.log("removing survey title logo");
        $('#surveytitlelogo').remove();
        console.log("removing jquery pre sh tr");
        $('#jquery_pre-sh-tr').remove();
        console.log("setting max-width to none for pagecontainer1 div");
        $('#pagecontainer').css("max-width", "none");
        console.log("setting up iframe");
        $('#m2c2-iframe').append('<center><iframe height="800px" width="400px" src="https://beta.m2c2kit.com/m2c2kit/ntc/index.html?activity_name=symbol-search&n_trials=3&participant_id=None&session_id=None&study_id=None&api_key=e16a3173-a09e-43b5-81b2-d36369d64fe8" ></iframe></center>');
        console.log("hiding question number");
        $(".questionnum").hide();
        console.log("hiding next button");
        $("button[name='submit-btn-saverecord']").hide();
        // hide div id footer
        $("#footer").remove();
    });
}