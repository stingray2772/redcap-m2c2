<?php
namespace PSU\M2C2;

$purgeURL = $module->getUrl(M2C2_PURGE_URL);

echo "<h3>M2C2 EM Log Viewer</h3>";

$pseudoSql = "SELECT log_id, timestamp, record, message, " . M2C2_LOGGING_INVALID_CONFIG_PARAM . " WHERE message = ?";
$parameters = [M2C2_LOGGING_INVALID_CONFIG];

$result = $module->queryLogs($pseudoSql, $parameters);

echo "<style>
    .m2c2-table {
        width: 100%;
        border-collapse: collapse;
        margin-left: 20px;
        margin-right:20px;
        text-align: left;
    }
    .m2c2-table, .m2c2-table th, .m2c2-table td {
        border: 1px solid #dddddd;
    }
    .m2c2-th, .m2c2-td {
        padding: 12px;
    }
    .m2c2-th {
        background-color: #f4f4f4;
        color: #333;
    }
    .m2c2-tr:nth-child(even) {
        background-color: #f9f9f9;
    }
    .m2c2-caption {
        caption-side: top;
        font-weight: bold;
        padding: 10px;
    }
    .m2c2-purge-button {
        background-color: #ff4d4d;
        color: white;
        border: none;
        padding: 10px 20px;
        cursor: pointer;
        font-size: 16px;
        margin: 20px 0;
    }
    .m2c2-purge-button i {
        margin-right: 5px;
    }
</style>";

echo "<table class='m2c2-table'>";
echo "<caption class='m2c2-caption'>Error Log</caption>";
echo "<thead>";
echo "<tr>";
echo "<th class='m2c2-th'>log_id</th>";
echo "<th class='m2c2-th'>timestamp</th>";
echo "<th class='m2c2-th'>record</th>";
echo "<th class='m2c2-th'>" . M2C2_LOGGING_INVALID_CONFIG_PARAM . "</th>";
echo "</tr>";
echo "</thead>";
echo "<tbody>";

while($row = $result->fetch_assoc()){
    echo "<tr class='m2c2-tr'>";
    echo "<td class='m2c2-td'>" . $module->escape($row['log_id']) . "</td>";
    echo "<td class='m2c2-td'>" . $module->escape($row['timestamp']) . "</td>";
    echo "<td class='m2c2-td'>" . $module->escape($row['record']) . "</td>";
    echo "<td class='m2c2-td'>" . $module->escape($row[M2C2_LOGGING_INVALID_CONFIG_PARAM]) . "</td>";
    echo "</tr>";
}

echo "</tbody>";
echo "</table>";

$pseudoSqlLaunch = "SELECT log_id, timestamp, record, message, " . implode(', ', M2C2_LOGGING_LAUNCH_PARAMS) . " WHERE message = ?";
$parametersLaunch = [M2C2_LOGGING_LAUNCHING];

$resultLaunch = $module->queryLogs($pseudoSqlLaunch, $parametersLaunch);

echo "<table class='m2c2-table'>";
echo "<caption class='m2c2-caption'>Launch Log Viewer</caption>";
echo "<thead>";
echo "<tr>";
echo "<th class='m2c2-th'>log_id</th>";
echo "<th class='m2c2-th'>timestamp</th>";
echo "<th class='m2c2-th'>record</th>";

// Add headers for each of the launch log parameters
foreach (M2C2_LOGGING_LAUNCH_PARAMS as $param) {
    echo "<th class='m2c2-th'>" . $module->escape($param) . "</th>";
}

echo "</tr>";
echo "</thead>";
echo "<tbody>";

while($row = $resultLaunch->fetch_assoc()){
    echo "<tr class='m2c2-tr'>";
    echo "<td class='m2c2-td'>" . $module->escape($row['log_id']) . "</td>";
    echo "<td class='m2c2-td'>" . $module->escape($row['timestamp']) . "</td>";
    echo "<td class='m2c2-td'>" . $module->escape($row['record']) . "</td>";

    // Add data for each of the launch log parameters
    foreach (M2C2_LOGGING_LAUNCH_PARAMS as $param) {
        echo "<td class='m2c2-td'>" . $module->escape($row[$param]) . "</td>";
    }

    echo "</tr>";
}

echo "</tbody>";
echo "</table>";

// Add the purge button
echo "<button class='m2c2-purge-button' onclick='confirmPurge()'><i class='fas fa-trash-alt'></i> Purge M2C2 Logs</button>";

// JavaScript for confirmation prompt
echo "<script>
function confirmPurge() {
  simpleDialog('Are you sure you want to cancel this request?', 'Cancel Request', 1, 400);
  var confirm = $('<button>', {
    'class': 'ui-button ui-corner-all ui-widget',
    text: 'Yes'
  }).bind('click', function() {
    window.location.href = '{$purgeURL}';
  });
  $('body').find('.ui-dialog-buttonset').addClass('cancel-request-dialog').append(confirm);
}
</script>";
?>
