<h2>M2C2 External Module Documentation</h2>
<div style="margin-left:15px;">
    <p>Before using the M2C2 External Module (EM), please review the following guidelines:</p>
    <div style="margin-left:15px;">
        <h3>General Guidelines</h3>
        <ol>
            <li><strong>Survey-Only Usage:</strong> M2C2 can only be used in a survey, not in a data entry form.</li>
            <li><strong>Project Structure:</strong>
                <ul>
                    <li>Plan your project flow carefully.</li>
                    <li>You will need <strong>one note box per trial per assessment</strong>. For example, if your assessment consists of 3 activities with 10 trials each, you will need 30 note boxes.</li>
                    <li>To ensure compatibility with parsing, you must use the naming convention, <code>m2c2_assessment_XX_trial_data_XX</code>, where the first <code>XX</code> represents the order of assessment, and the second <code>XX</code> represents the trial number. Both <code>XX</code> values should be zero based (start with <code>00</code>).</li>
                </ul>
            </li>
            <li><strong>@M2C2 Action Tag Setup:</strong>
                <ul>
                    <li>The action tag must be set up in the first note box of each assessment (trial <code>00</code>).</li>
                    <li>This EM will hide the note boxes from the survey participant but will remain visible in data entry mode.</li>
                </ul>
            </li>
        </ol>

        <h3>Multi-Language Support</h3>
        <p>M2C2 supports multiple languages. To implement this:</p>
        <ul>
            <li>Code your REDCap language preference field using supported language codes (e.g., <code>en-US</code>, <code>es-MX</code>, <code>fr-FR</code>, <code>de-DE</code>).</li>
            <li>For multi-language support, refer to the MLM section of REDCap.</li>
        </ul>

        <h3>Automatic Survey Completion (Optional)</h3>
        <ul>
            <li>The <code>auto_complete</code> setting is optional.</li>
            <li>If set to <code>true</code>, the module will automatically complete the survey after the last trial is completed.</li>
            <li>If <code>auto_complete</code> is set to <code>false</code> or omitted, the participant will need to manually complete the survey.</li>
            <li>When using <code>auto_complete</code>, the <strong>field label</strong> from the first trial's note box will be displayed to the participant while the survey is submitted.</li>
            <li>For multi-language support, use MLM to translate the field label.</li>
        </ul>

        <h3>Additional Parameters (Optional)</h3>
        <ul>
            <li>The <code>additional_parameters</code> setting is optional.</li>
            <li>This allows for additional parameters to be utilized. Please refer to M2C2Kit documentation for more information on these parameters.</li>
            <li>The format of this setting is a string with key-value pairs separated by commas (e.g., <code>"show_quit_button=false,locale=en-US"</code>).</li>
        </ul>

        <h3>REDCap Setup</h3>
        <ol>
            <li><strong>Enable M2C2:</strong> Ensure the M2C2 External Module is enabled in your REDCap instance and project (contact your REDCap administrator if needed).</li>
            <li><strong>Verify Survey Instrument:</strong>
                <ul>
                    <li>Ensure the instrument where you want to use M2C2 is set up as a <strong>survey instrument</strong>.</li>
                    <li>If using multiple activities within one instrument, set the survey setting to <strong>'Multiple pages'</strong> and use section headers to separate the activities.</li>
                </ul>
            </li>
            <li><strong>Add Note Boxes:</strong> For each trial of every assessment, create a note box. Place them in order and use the naming convention <code>m2c2_assessment_XX_trial_data_XX</code> as noted above.</li>
            <li><strong>Add @M2C2 Action Tag:</strong> Add the <code>@M2C2</code> action tag to the first trial's note box for each assessment.</li>
        </ol>

        <h3>@M2C2 Action Tag Format</h3>
        <p>The action tag should follow this format when applied to the first note box of each assessment:</p>

        <pre><code>@M2C2={
        "activity_name":"&lt;activity_name&gt;",
        "activity_version":"&lt;activity_version&gt;",
        "redcap_fields": ["&lt;m2c2_assessment_00_trial_data_00&gt;", "&lt;m2c2_assessment_00_trial_data_01&gt;"],
        "auto_complete": true,  // OPTIONAL
        "additional_parameters": "show_quit_button=false,locale=en-US" // OPTIONAL
    }</code></pre>

        <h5>Example</h5>
        <p>For a <strong>Symbol Search</strong> assessment with two trials (note boxes named <code>m2c2_assessment_00_trial_data_00</code> and <code>m2c2_assessment_00_trial_data_01</code>), the action tag would look like this:</p>

        <pre><code>@M2C2={
        "activity_name":"assessment-symbol-search",
        "activity_version":"0.8.22",
        "redcap_fields": ["m2c2_assessment_00_trial_data_00","m2c2_assessment_00_trial_data_01"],
        "auto_complete": true,
        "additional_parameters": "show_quit_button=false"
    }</code></pre>
    </div>
</div>
<hr>
<h3>Action Tag Builder</h3>
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

<script>
    function loadjQuery(callback) {
        const script = document.createElement("script");
        script.src = "https://code.jquery.com/jquery-3.6.0.min.js"; // Change version as needed
        script.onload = callback;
        document.head.appendChild(script);
    }

    function init() {
        const minVersion = "0.8.22";

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
                            `   "redcap_fields": ["m2c2_assessment_00_trial_data_00","m2c2_assessment_00_trial_data_01"],\n` +
                            `   "auto_complete": true\n` +
                            `}</code>`;

                        $('#generated-code').html(`<h5>Generated Code:</h5><div style="margin-left:15px"><pre>${generatedCode}</pre></div>`);
                        $('#generated-code').show();
                    }

                    // fetch assessment package.json and console log the data
                    fetch(`https://cdn.jsdelivr.net/npm/@m2c2kit/assessment-${selectedAssessmentName.toLowerCase().replace(/ /g, '-')}@${selectedVersion}/package.json`)
                        .then(response => response.json())
                        .then(
                            data => {
                                const m2c2kit = data.m2c2kit;
                                if (m2c2kit) {
                                    const locales = m2c2kit.locales;
                                    if (locales) {
                                        // Create the HTML for the unordered list of locales
                                        const localeList = locales.map(locale => `<li>${locale}</li>`).join('');
                                        $('#generated-code').append(`<h5>Supported Locales:</h5><ul style="margin-left:15px">${localeList}</ul>`);
                                    } else {
                                        console.error('Failed to fetch the locales data:', data);
                                    }
                                } else {
                                    console.error('Failed to fetch the package.json data:', data);
                                }
                            }
                        )
                        .catch(error => console.error('Failed to fetch the package.json data:', error));
                });
            })
            .catch(error => {
                console.error('Failed to fetch the assessments data:', error);
                $('#assessment-info').html('<p>Error fetching assessments data. Please try again later.</p>');
            });
    };

    if (typeof jQuery === 'undefined') {
        loadjQuery(init);
    } else {
        init();
    }
</script>