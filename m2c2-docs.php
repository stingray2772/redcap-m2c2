
<h3>M2C2 EM Docs</h3>
<div style="max-width:70%;margin-left:15px">
    <h4>Instructions</h4>
    <div style="margin-left:15px;">
        <p>Before using M2C2, please review the following instructions:</p>
        <ul>
            <li>M2C2 can only be utilized in a survey, not a data entry form.</li>
            <li>Before beginning, it is important to give some thought to the flow of your project. You will need to create one notes box per TRIAL per ASSESSMENT. That means that if you are using 3 activities with 10 trials per assessment, you will need 30 note boxes. In order to make things easier to follow, we recommend using a consistent naming like 'assessment_x' where 'assessment' is the assessment name and 'x' is the trial number.</li>
            <li>Each assessment will need to be set up with the the @M2C2 action tag using the format listed below. This should be set up in the first note box for that assessment (trial 1).</li>
            <li>This External Module will hide the note boxes from the user. The note boxes will still be visible in data entry mode.</li>
        </ul>
        <ol>
            <li>Verify the M2C2 External Module is enabled in your REDCap instance and project (this may require contacting your REDCap administrator).</li>
            <li>Verify instrument you want to add M2C2 to is a survey instrument. If you are utilizing multiple activities in a single instrument, please set the survey setting 'Pagination' to 'Multiple pages' and use section headers to separate your activities.</li>
            <li>Add note boxes for each trial of each assessment you want to use M2C2 with. The note boxes should be placed in order, and it is recommended to use a consistent naming format such as 'assessment_x'.</li>
            <li>For each assessment, add the @M2C2 action tag to the first trial note box for that assessment. The action tag should be in the format listed below</li>
        </ol>
    </div>
    <hr>
    <h4>Format for @M2C2 action tag</h4>
    <div style="margin-left:15px;">
        <p>The @M2C2 action tag should be added to the first note box for each assessment. The action tag should be in the following format:</p>
        <code>@M2C2={<br>
            &nbsp;&nbsp;&nbsp;&nbsp;"activity_name":"activity_name",<br>
            &nbsp;&nbsp;&nbsp;&nbsp;"activity_version":"activity_version",<br>
            &nbsp;&nbsp;&nbsp;&nbsp;"redcap_fields":"redcap_fields"<br>
        }</code>
        <br>
        <p>Example for a Symbol Search assessment using two trials (note boxes named m2c2_symbol_search_trial_1 and m2c2_symbol_search_trial_2):</p>
        <code>@M2C2={<br>
            &nbsp;&nbsp;&nbsp;&nbsp;"activity_name":"assessment-symbol-search",<br>
            &nbsp;&nbsp;&nbsp;&nbsp;"activity_version":"0.8.19",<br>
            &nbsp;&nbsp;&nbsp;&nbsp;"redcap_fields": ["m2c2_symbol_search_trial_1","m2c2_symbol_search_trial_2"]<br>
        }</code>
    </div>
    <hr>
    <h4>Action Tag Builder</h4>
    <div style="margin-left:15px;">
        <p>Below is a list of available assessments that can be used with M2C2. Select an assessment to continue.</p>
        <label for="assessment-select">Available Assessments:</label>
        <select id="assessment-select">
            <option value="">Select an assessment</option>
        </select>
        <br><br>
        <div id="assessment-info"></div>
        <div id="version-container" style="display: none;">
            <label for="version-select">Available Versions:</label>
            <select id="version-select">
                <option value="">Select a version</option>
            </select>
        </div>
        <div id="generated-code"></div>
    </div>
</div>

<script>
    $(document).ready(function() {
        const minVersion = "0.8.18";

        function compareVersions(v1, v2) {
            const v1Parts = v1.split('.').map(Number);
            const v2Parts = v2.split('.').map(Number);

            for (let i = 0; i < Math.max(v1Parts.length, v2Parts.length); i++) {
                const v1Part = v1Parts[i] || 0; // Default to 0 if part is missing
                const v2Part = v2Parts[i] || 0; // Default to 0 if part is missing

                if (v1Part > v2Part) return 1;
                if (v1Part < v2Part) return -1;
            }

            return 0; // Versions are equal
        }

        // Fetch assessments registry
        fetch('https://cdn.jsdelivr.net/npm/@m2c2kit/assessments-registry/dist/assessments-registry.json')
            .then(response => response.json())
            .then(data => {
                data.assessments.forEach(assessment => {
                    const assessmentName = assessment.name.split('@m2c2kit/assessment-')[1].replace(/-/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                    $('#assessment-select').append(`<option value="${assessment.name}">${assessmentName}</option>`);
                });

                $('#assessment-select').change(function() {
                    const selectedAssessment = data.assessments.find(a => a.name === this.value);
                    if (selectedAssessment) {
                        $('#assessment-info').html(`<h5>Description:</h5><div style="margin-left:15px"><p>${selectedAssessment.description}</p></div>`);

                        $('#version-select').empty().append('<option value="">Available Versions:</option>');
                        selectedAssessment.versions.forEach(version => {
                            // If assessment is 'symbol-search', 'color-dots', 'color-shapes', or 'grid-memory'
                            // AND version is less than 0.8.18, then skip this version
                            if (['symbol-search', 'color-dots', 'color-shapes', 'grid-memory'].includes(selectedAssessment.name.split('@m2c2kit/assessment-')[1]) && 
                                compareVersions(version, minVersion) === -1) {
                                return;
                            } else {
                                $('#version-select').append(`<option value="${version}">${version}</option>`);
                            }
                        });

                        $('#version-container').show();
                        $('#generated-code').hide();
                    } else {
                        $('#assessment-info').empty();
                        $('#version-container').hide();
                        $('#generated-code').hide();
                    }
                });

                $('#version-select').change(function() {
                    const selectedAssessmentName = $('#assessment-select option:selected').text();
                    const selectedVersion = $('#version-select option:selected').text();

                    if (selectedAssessmentName && selectedVersion) {
                        const generatedCode = 
                            `<code>@M2C2={\n` +
                            `   "activity_name":"assessment-${selectedAssessmentName.toLowerCase().replace(/ /g, '-')}",\n` +
                            `   "activity_version":"${selectedVersion}",\n` +
                            `   "redcap_fields": ["field_name_1","field_name_2"]\n` +
                            `}</code>`;

                        $('#generated-code').append(`<h5>Generated Code:</h5><div style="margin-left:15px"><pre>${generatedCode}</pre></div>`);
                        $('#generated-code').show();
                    }
                });
            })
            .catch(error => {
                console.error('Failed to fetch the assessments data:', error);
                $('#assessment-info').html('<p>Error fetching assessments data. Please try again later.</p>');
            });
    });
</script>