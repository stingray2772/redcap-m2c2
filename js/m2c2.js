"use strict";

console.log("REDCap loaded m2c2.js");
console.log("m2c2Settings: ", m2c2Settings);

var m2c2MLMEnabled = false;
var m2c2MLMSelectedLanguage = undefined;

$(function() {
    redcapModule.afterRender(
        function() {
            console.log("REDCap finished loading...");
            if (typeof REDCap.MultiLanguage !== 'undefined') {
                console.log("MultiLanguage is enabled");
                m2c2MLMEnabled = true;
                m2c2MLMSelectedLanguage = REDCap.MultiLanguage.getCurrentLanguage();

                // m2c2Settings['end_message'] potentially needs updated with translated text
                m2c2Settings['end_message'] = $('#label-m2c2_symbol_search_trial_1 div[data-mlm-field="m2c2_symbol_search_trial_1"]').text();
                console.log(m2c2Settings['end_message']);
                
                console.log();
                m2c2Load();
            } else {
                console.log("MultiLanguage is not enabled");
                m2c2MLMEnabled = false;
                m2c2Load();
            }
        }
    );
});

function m2c2Load() {
    if (m2c2Settings && typeof m2c2Settings === "object") {
        m2c2HideFields();
        var cdnUrl = "https://cdn.jsdelivr.net/npm/@m2c2kit/" + m2c2Settings.activity_name + "@" + m2c2Settings.activity_version + "/";
        m2c2BuildAndAddImportMap(cdnUrl);
    }
}

function m2c2HideFields() {
    try {
        if (typeof m2c2Settings !== "undefined" && m2c2Settings !== null &&
            m2c2Settings.hasOwnProperty("redcap_fields") && Array.isArray(m2c2Settings.redcap_fields)) {
            m2c2Settings.redcap_fields.forEach(function (field) {
                $("#" + field).closest("tr").hide();
            });
        }

        // Hide GUI items that are not needed
        $("#surveyinstructions").hide();
        $("#surveytitlelogo").hide();
        $("#pagecontainer").hide();
    } catch (ex) {
        console.error(ex);
    }
}

function m2c2SkipActivity(message) {
    alert(message);
    if (m2c2Settings.redcap_fields.length > 0) {
        $("#" + m2c2Settings.redcap_fields[0]).val(message);
    } else {
        console.error("Invalid trial index: ", trialNum);
    }

    $("#m2c2kit").hide();
    $("#pagecontainer").show();
    $("body").removeClass("m2c2kit-background-color");
    document.body.style.backgroundColor = "";
}

function m2c2BuildAndAddImportMap(cdnUrl) {
    if (!cdnUrl) {
        m2c2SkipActivity("Error parsing activity name and/or version.");
        return;
    }

    fetch(cdnUrl + 'package.json')
        .then(response => {
            if (!response.ok) {
                m2c2SkipActivity("Issue with assessment. Network response was not ok: " + response.statusText);
            }
            return response.json();
        })
        .then(packageData => {
            const dependencies = packageData.dependencies || {};
            const devDependencies = packageData.devDependencies || {};

            const importMap = {
                "imports": {}
            };

            const parts = cdnUrl.split('/');
            const packageName = parts[4] + "/" + parts[5].split("@")[0];
            importMap.imports[packageName] = cdnUrl + 'dist/index.min.js';

            for (const [name, version] of Object.entries(dependencies)) {
                importMap.imports[name] = `https://cdn.jsdelivr.net/npm/${name}@${version}/dist/index.min.js`;
            }

            if (!dependencies.hasOwnProperty("@m2c2kit/session")) {
                for (const [name, version] of Object.entries(devDependencies)) {
                    if (name === "@m2c2kit/session") {
                        importMap.imports[name] = `https://cdn.jsdelivr.net/npm/${name}@${version}/dist/index.min.js`;
                    }
                }
            }

            var importMapScript = document.createElement("script");
            importMapScript.type = "importmap";
            importMapScript.textContent = JSON.stringify(importMap);
            document.head.appendChild(importMapScript);

            var esModuleScript = document.createElement("script");
            esModuleScript.src = "https://ga.jspm.io/npm:es-module-shims@1.10.1/dist/es-module-shims.js";
            esModuleScript.type = "module";
            document.head.appendChild(esModuleScript);

            var firstField = m2c2Settings.redcap_fields[0];
            if (firstField && $("#" + firstField).length) {
                var m2c2Container = '<div id="m2c2kit" class="m2c2kit-background-color m2c2kit-no-margin"></div>';
                $("body").append(m2c2Container);

                m2c2InitializeSession();
            } else {
                console.error("First field element not found in page: ", firstField);
            }
        })
        .catch(error => {
            m2c2SkipActivity("Error initializing session. Please check M2C2 redcap_fields.");
            console.error("Error fetching package.json:", error);
        });
}

async function m2c2LoadModules(moduleNames) {
    const modules = await Promise.all(
        moduleNames.map((moduleName) => import(moduleName))
    );
    return modules;
}

function m2c2GetAssessmentClassNameFromModule(assessmentModule) {
    const assessments = Object.keys(assessmentModule).filter((key) => {
        const obj = assessmentModule[key];
        if (
            typeof obj === "function" &&
            obj.prototype &&
            obj.prototype.constructor === obj
        ) {
            const parentClass = Object.getPrototypeOf(obj.prototype).constructor;
            const parentProps = Object.getOwnPropertyNames(parentClass.prototype);
            return (
                parentProps.includes("loop") &&
                parentProps.includes("update") &&
                parentProps.includes("draw")
            );
        }
        m2c2SkipActivity("Error loading assessment module. Please check assessment name and version.");
        return false;
    });

    if ((assessments.length === 0) || (assessments.length > 1)) {
        m2c2SkipActivity("Error loading assessment module. Please check assessment name and version.");
        throw new Error("There is more than one assessment exported in the module");
    }

    return assessments[0];
}

function getAdditionalParameters(number_of_trials, locale) {
    var additionalParametersObj = {};

    console.log("Checking for additional parameters...");
    if (m2c2Settings.hasOwnProperty("additional_parameters") && typeof m2c2Settings.additional_parameters === "string") {
        // parse additional parameters, explode by comma
        var additionalParameters = m2c2Settings.additional_parameters.split(",");
        additionalParameters.forEach(function (param) {
            var keyVal = param.split("=");
            if (keyVal.length === 2) {
                // trim key and value
                additionalParametersObj[keyVal[0].trim()] = keyVal[1].trim();
            }
        });
    }

    // add number_of_trials
    additionalParametersObj["number_of_trials"] = number_of_trials;

    // add locale
    if (locale !== null) {
        additionalParametersObj["locale"] = locale;
    }

    return additionalParametersObj;
}

async function m2c2InitializeSession() {
    console.log("Initializing session...");
    const sessionModuleName = "@m2c2kit/session";
    const assessmentModuleName = `@m2c2kit/${m2c2Settings.activity_name}`;

    const [sessionModule, assessmentModule] = await m2c2LoadModules([
        sessionModuleName,
        assessmentModuleName
    ]);

    const assessmentClassName = m2c2GetAssessmentClassNameFromModule(assessmentModule);
    const assessment = new assessmentModule[assessmentClassName]();

    if (m2c2MLMEnabled) {
        assessment.setParameters(getAdditionalParameters(m2c2Settings.redcap_fields.length, m2c2MLMSelectedLanguage));
    } else {
        assessment.setParameters(getAdditionalParameters(m2c2Settings.redcap_fields.length, null));
    }

    const session = new sessionModule.Session({
        activities: [assessment],
    });

    session.onEnd(event => {
        console.log("Discovered Session End");
        if (m2c2Settings.auto_complete) {
            $("#form").submit();
            $("#m2c2kit").html("<div style='display: flex; justify-content: center; align-items: center; height: 100vh; width: 100%;'><h1 style='text-align:center;'>" + m2c2Settings['end_message'] + "</h1><div>");
            return;
        } else {
            $("#m2c2kit").hide();
            $("#pagecontainer").show();
            $("body").removeClass("m2c2kit-background-color");
            document.body.style.backgroundColor = "";
        }
    });

    session.onActivityData((event) => {
        console.log("Discovered Session Activity Data");
        if (event.data.trials && Array.isArray(event.data.trials)) {
            var lastTrial = event.data.trials[event.data.trials.length - 1];
            console.log("Last trial: ", lastTrial);
            var trialNum = lastTrial.trial_index;
            console.log("Trial index: ", trialNum);

            if (Number.isInteger(trialNum) && trialNum >= 0 && trialNum < m2c2Settings.redcap_fields.length) {
                var fieldName = m2c2Settings.redcap_fields[trialNum];
                $("#" + fieldName).val(JSON.stringify(lastTrial));
            } else {
                m2c2SkipActivity("Error parsing trial index. Please check M2C2 redcap_fields.");
                console.error("Invalid trial index: ", trialNum);
            }
        } else {
            m2c2SkipActivity("Error parsing trial index. Please check M2C2 redcap_fields.");
            console.error("Invalid trial data structure: ", event.data);
        }
    });

    window.m2c2kitSession = session;
    session.initialize();
}
