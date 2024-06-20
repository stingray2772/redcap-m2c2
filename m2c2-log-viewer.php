<?php
namespace PSU\M2C2;

$purgeURL = $module->getUrl(M2C2_PURGE_URL);

echo "<h3>M2C2 Log Viewer</h3>";

$pseudoSql = "SELECT log_id, timestamp, record, message, " . M2C2_LOGGING_INVALID_CONFIG_PARAM . " WHERE message = ?";
$parameters = [M2C2_LOGGING_INVALID_CONFIG];

$result = $module->queryLogs($pseudoSql, $parameters);

echo "<style>
    table {
        width: 100%;
        border-collapse: collapse;
        margin: 20px 0;
        text-align: left;
    }
    table, th, td {
        border: 1px solid #dddddd;
    }
    th, td {
        padding: 12px;
    }
    th {
        background-color: #f4f4f4;
        color: #333;
    }
    tr:nth-child(even) {
        background-color: #f9f9f9;
    }
    caption {
        caption-side: top;
        font-weight: bold;
        padding: 10px;
    }
    .purge-button {
        background-color: #ff4d4d;
        color: white;
        border: none;
        padding: 10px 20px;
        cursor: pointer;
        font-size: 16px;
        margin: 20px 0;
    }
    .purge-button i {
        margin-right: 5px;
    }
</style>";

echo "<table>";
echo "<caption>Error Log</caption>";
echo "<thead>";
echo "<tr>";
echo "<th>log_id</th>";
echo "<th>timestamp</th>";
echo "<th>record</th>";
echo "<th>" . M2C2_LOGGING_INVALID_CONFIG_PARAM . "</th>";
echo "</tr>";
echo "</thead>";
echo "<tbody>";

while($row = $result->fetch_assoc()){
    echo "<tr>";
    echo "<td>" . $module->escape($row['log_id']) . "</td>";
    echo "<td>" . $module->escape($row['timestamp']) . "</td>";
    echo "<td>" . $module->escape($row['record']) . "</td>";
    echo "<td>" . $module->escape($row[M2C2_LOGGING_INVALID_CONFIG_PARAM]) . "</td>";
    echo "</tr>";
}

echo "</tbody>";
echo "</table>";

$pseudoSqlLaunch = "SELECT log_id, timestamp, record, message, " . implode(',', M2C2_LAUNCH_LOG_PARAMS) . " WHERE message = ?";
$parametersLaunch = [M2C2_LOGGING_LAUNCHING];

$resultLaunch = $module->queryLogs($pseudoSqlLaunch, $parametersLaunch);

echo "<table>";
echo "<caption>Launch Log Viewer</caption>";
echo "<thead>";
echo "<tr>";
echo "<th>log_id</th>";
echo "<th>timestamp</th>";
echo "<th>record</th>";

// Add headers for each of the launch log parameters
foreach (M2C2_LAUNCH_LOG_PARAMS as $param) {
    echo "<th>" . $module->escape($param) . "</th>";
}

echo "</tr>";
echo "</thead>";
echo "<tbody>";

while($row = $resultLaunch->fetch_assoc()){
    echo "<tr>";
    echo "<td>" . $module->escape($row['log_id']) . "</td>";
    echo "<td>" . $module->escape($row['timestamp']) . "</td>";
    echo "<td>" . $module->escape($row['record']) . "</td>";

    // Add data for each of the launch log parameters
    foreach (M2C2_LAUNCH_LOG_PARAMS as $param) {
        echo "<td>" . $module->escape($row[$param]) . "</td>";
    }

    echo "</tr>";
}

echo "</tbody>";
echo "</table>";

// Add the purge button
echo "<button class='purge-button' onclick='confirmPurge()'><i class='fas fa-trash-alt'></i> Purge M2C2 Logs</button>";

// JavaScript for confirmation prompt
echo "<script>
function confirmPurge(purgeURL) {
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
