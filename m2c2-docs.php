

<div style="max-width:70%">
<h3>Instructions</h3>
<ul>
    <li>M2C2 can only be utilized in a survey, not a data entry form.</li>
    <li>Before beginning, it is important to give some thought to the flow of your project. You will need to create one notes box per TRIAL per ACTIVITY. That means that if you are using 3 activities with 10 trials per activity, you will need 30 note boxes. In order to make things easier to follow, we recommend using a consistent naming like 'activity_x' where 'activity' is the activity name 'x' is the trial number.</li>
    <li>Each activity will need to be set up with the the @M2C2 action tag using the format listed below. This should be set up in the first note box for that activity (trial 1).</li>
    <li>This External Module will hide the note boxes from the user. The note boxes will still be visible in data entry mode.</li>
</ul>
<ol>
    <li>Verify the M2C2 External Module is enabled in your REDCap instance (this may require contacting your REDCap administrator).</li>
    <li>Verify the M2C2 External Module is enabled in your REDCap project (this may require contacting your REDCap administrator).</li>
    <li>Verify instrument you want to add M2C2 to is a survey instrument. If you are utilizing multiple activities in a single instrument, please set the survey setting 'Pagination' to 'Multiple pages' and use section headers to separate your activities.</li>
    <li>Add note boxes for each trial of each activity you want to use M2C2 with. The note boxes should be placed in order, and it is recommended to use a consistent naming format such as 'activity_x'.</li>
    <li>For each activity, add the @M2C2 action tag to the first trial note box for that activity. The action tag should be in the format listed below</li>
</ol>

<h3>Format for @M2C2 action tag</h3>
<p>The @M2C2 action tag should be added to the first note box for each activity. The action tag should be in the following format:</p>
<code>@M2C2={<br>
    &nbsp;&nbsp;&nbsp;&nbsp;"activity_name":"activity_name",<br>
    &nbsp;&nbsp;&nbsp;&nbsp;"api_key":"api_key",<br>
    &nbsp;&nbsp;&nbsp;&nbsp;"study_id":"study_id",<br>
    &nbsp;&nbsp;&nbsp;&nbsp;"width":"width",<br>
    &nbsp;&nbsp;&nbsp;&nbsp;"height":"height",<br>
    &nbsp;&nbsp;&nbsp;&nbsp;"show_quit_button":"show_quit_button",<br>
    &nbsp;&nbsp;&nbsp;&nbsp;"participant_id":"participant_id",<br>
    &nbsp;&nbsp;&nbsp;&nbsp;"admin_type":"admin_type",<br>
    &nbsp;&nbsp;&nbsp;&nbsp;"redcap_fields":"redcap_fields",<br>
    &nbsp;&nbsp;&nbsp;&nbsp;"number_of_trials":"number_of_trials",<br>
    &nbsp;&nbsp;&nbsp;&nbsp;"session_id":"session_id"<br>
}</code>
<br><br>
<p>Example for a Symbol Search activity using two trials (note boxes named m2c2_symbol_search_trial_1 and m2c2_symbol_search_trial_2):</p>
<code>@M2C2={<br>
    &nbsp;&nbsp;&nbsp;&nbsp;"activity_name": "symbol-search",<br>
    &nbsp;&nbsp;&nbsp;&nbsp;"api_key": "demo",<br>
    &nbsp;&nbsp;&nbsp;&nbsp;"study_id": "demo",<br>
    &nbsp;&nbsp;&nbsp;&nbsp;"width": 400,<br>
    &nbsp;&nbsp;&nbsp;&nbsp;"height": 1000,<br>
    &nbsp;&nbsp;&nbsp;&nbsp;"show_quit_button": false,<br>
    &nbsp;&nbsp;&nbsp;&nbsp;"participant_id": "None",<br>
    &nbsp;&nbsp;&nbsp;&nbsp;"admin_type": "qualtrics",<br>
    &nbsp;&nbsp;&nbsp;&nbsp;"fields": ["m2c2_symbol_search_trial_1","m2c2_symbol_search_trial_2"]<br>
}</code>
</div>