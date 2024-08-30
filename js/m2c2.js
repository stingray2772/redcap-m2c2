"use strict";

console.log("REDCap loaded m2c2.js");

if (m2c2Settings !== "") {

    hideFields();

    var cdnUrl = "https://cdn.jsdelivr.net/npm/@m2c2kit/" + m2c2Settings.activity_name + "@" + m2c2Settings.activity_version + "/";

    function buildAndAddImportMap(cdnUrl) {
        if (!cdnUrl) {
            skipActivity("Error parsing activity name and/or version.");
            return;
        }

        fetch(cdnUrl + 'package.json')
            .then(response => {
                if (!response.ok) {
                    skipActivity("Issue with assessment. Network response was not ok: " + response.statusText);
                }
                return response.json();
            })
            .then(packageData => {
                // Extract dependencies and devDependencies
                const dependencies = packageData.dependencies || {};
                const devDependencies = packageData.devDependencies || {};

                // Prepare the import map
                const importMap = {
                    "imports": {}
                };

                // Add the activity module to the import map
                const parts = cdnUrl.split('/');
                const packageName = parts[4] + "/" + parts[5].split("@")[0];
                importMap.imports[packageName] = cdnUrl + 'dist/index.min.js';

                // Add dependencies from 'dependencies'
                for (const [name, version] of Object.entries(dependencies)) {
                    importMap.imports[name] = `https://cdn.jsdelivr.net/npm/${name}@${version}/dist/index.min.js`;
                }

                // Add @m2c2kit/session from 'devDependencies' if it exists and is not already in the dependencies
                if (!dependencies.hasOwnProperty("@m2c2kit/session")) {
                    for (const [name, version] of Object.entries(devDependencies)) {
                        if (name === "@m2c2kit/session") {
                            importMap.imports[name] = `https://cdn.jsdelivr.net/npm/${name}@${version}/dist/index.min.js`;
                        }
                    }
                }

                // Create the import map script element
                var importMapScript = document.createElement("script");
                importMapScript.type = "importmap";
                importMapScript.textContent = JSON.stringify(importMap);

                console.log('Import map created:', importMap);

                // Append the import map script to the document
                document.head.appendChild(importMapScript);

                console.log('Import map added to the document.');

                // Add the es-module-shims script
                var esModuleScript = document.createElement("script");
                esModuleScript.src = "https://ga.jspm.io/npm:es-module-shims@1.10.0/dist/es-module-shims.js";
                esModuleScript.type = "module";
                document.head.appendChild(esModuleScript);

                console.log('es-module-shims script added to the document.');

                console.log(m2c2Settings);
                var firstField = m2c2Settings.redcap_fields[0];
                if (firstField) {
                    if ($("#" + firstField).length) {
                        var m2c2Container = '<div id="m2c2kit" class="m2c2kit-background-color m2c2kit-no-margin"></div>';
                        $("body").append(m2c2Container);

                        console.log("loaded m2c2kit div");

                        // Create a dynamic module script
                        var moduleScript = document.createElement("script");
                        moduleScript.type = "module";
                        moduleScript.textContent = `
                                async function loadModules(moduleNames) {
                                    const modules = await Promise.all(
                                        moduleNames.map((moduleName) => import(moduleName))
                                    );
                                    return modules;
                                }
                
                                function getAssessmentClassNameFromModule(assessmentModule) {
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
                                        skipActivity("Error loading assessment module. Please check assessment name and version.");
                                        return false;
                                    });
                
                                    if ((assessments.length === 0) || (assessments.length > 1)) {
                                        skipActivity("Error loading assessment module. Please check assessment name and version.");
                                        throw new Error("There is more than one assessment exported in the module");
                                    }
                
                                    return assessments[0];
                                }
                
                                async function initializeSession() {
                                    console.log("Initializing session...");
                                    const sessionModuleName = "@m2c2kit/session";
                                    const assessmentModuleName = \`@m2c2kit/\${m2c2Settings.activity_name}\`;
                
                                    const [sessionModule, assessmentModule] = await loadModules([
                                        sessionModuleName,
                                        assessmentModuleName
                                    ]);
                
                                    const assessmentClassName = getAssessmentClassNameFromModule(assessmentModule);
                                    const assessment = new assessmentModule[assessmentClassName]();
                                    assessment.setParameters({ 
                                        number_of_trials: m2c2Settings.redcap_fields.length, 
                                        show_quit_button: false
                                    });
                
                                    const session = new sessionModule.Session({
                                        activities: [assessment],
                                    });
                
                                    session.onEnd(event => {
                                        console.log("Discovered Session End");
                                        $("#m2c2kit").hide();
                                        $("#pagecontainer").show();
                                        $("body").removeClass("m2c2kit-background-color");
                                        document.body.style.backgroundColor = "";
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
                                                skipActivity("Error parsing trial index. Please check M2C2 redcap_fields.");
                                                console.error("Invalid trial index: ", trialNum);
                                            }
                                        } else {
                                            skipActivity("Error parsing trial index. Please check M2C2 redcap_fields.");
                                            console.error("Invalid trial data structure: ", event.data);
                                        }
                                    });
                
                                    window.m2c2kitSession = session;
                                    session.initialize();
                                }
                
                                initializeSession().catch(error => {
                                    skipActivity("Error initializing session. Please check M2C2 redcap_fields.");
                                    console.error(error);
                                });
                            `;
                        document.body.appendChild(moduleScript);
                    } else {
                        console.error("First field element not found in page: ", firstField);
                    }
                }
            })
            .catch(error => {
                skipActivity("Error initializing session. Please check M2C2 redcap_fields.");
                console.error("Error fetching package.json:", error);
            });
    }

    buildAndAddImportMap(cdnUrl);
}

function hideFields() {
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

function skipActivity(message) {
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