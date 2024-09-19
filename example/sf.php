<?php
$api_key = "";
$server_address = "";
$server_port = 7777;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Example Page</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #1a1a1a;
            color: #f0f0f0;
        }
        .card {
            background-color: #333;
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            margin-bottom: 20px;
        }
        .card-header {
            background-color: #ff6600;
            border-bottom: none;
            border-top-left-radius: 10px;
            border-top-right-radius: 10px;
        }
        .card-body {
            border-bottom-left-radius: 10px;
            border-bottom-right-radius: 10px;
        }
        .indicator-true {
            color: green;
            font-weight: bold;
        }
        .indicator-false {
            color: red;
            font-weight: bold;
        }
        .btn-primary {
            background-color: #ff6600;
            border: none;
        }
        .btn-primary:hover {
            background-color: #e65c00;
        }
    </style>
</head>
<body class="container">
    <h1 class="my-4">Satisfactory SF_Tools.php Example Page</h1>
    <?php
        // Include the SF_Tools.php file
        include 'SF_Tools.php';

        // Instantiate the SF_Tools class
        $sf_tools = new SF_Tools($server_address, $server_port, true);

        // Set an API key (if available)
        $sf_tools->setAPIkey("Bearer ". $api_key);

        // Fetch server state
        $server_state = $sf_tools->getServerState();
        
        
        // Display server name
        echo '<div class="card">';
        echo '<div class="card-header"><h2>Server Name</h2></div>';
        echo '<div class="card-body">';
        echo '<p><strong>Server Name:</strong> ' . $sf_tools->name . '</p>';
        echo '</div></div>';
        

        // Display server state
        if ($server_state !== false) {
            echo '<div class="card">';
            echo '<div class="card-header"><h2>Server State</h2></div>';
            echo '<div class="card-body">';
            echo '<p><strong>Active Session Name:</strong> ' . $sf_tools->game_state['activeSessionName'] . '</p>';
            echo '<p><strong>Number of Connected Players:</strong> ' . $sf_tools->game_state['numConnectedPlayers'] . '</p>';
            echo '<p><strong>Player Limit:</strong> ' . $sf_tools->game_state['playerLimit'] . '</p>';
            echo '<p><strong>Tech Tier:</strong> ' . $sf_tools->game_state['techTier'] . '</p>';
            echo '<p><strong>Active Schematic:</strong> ' . $sf_tools->game_state['activeSchematic'] . '</p>';
            echo '<p><strong>Game Phase:</strong> ' . $sf_tools->game_state['gamePhase'] . '</p>';
            echo '<p><strong>Is Game Running:</strong> ' . ($sf_tools->game_state['isGameRunning'] ? '<span class="indicator-true">Yes</span>' : '<span class="indicator-false">No</span>') . '</p>';
            echo '<p><strong>Total Game Duration:</strong> ' . $sf_tools->game_state['totalGameDuration'] . ' seconds</p>';
            echo '<p><strong>Is Game Paused:</strong> ' . ($sf_tools->game_state['isGamePaused'] ? '<span class="indicator-true">Yes</span>' : '<span class="indicator-false">No</span>') . '</p>';
            echo '<p><strong>Average Tick Rate:</strong> ' . $sf_tools->game_state['averageTickRate'] . ' ticks per second</p>';
            echo '<p><strong>Auto Load Session Name:</strong> ' . $sf_tools->game_state['autoLoadSessionName'] . '</p>';
            echo '</div></div>';
        } else {
            echo '<p class="text-danger">Error: ' . $sf_tools->errormsg . '</p>';
        }

        // Fetch server options
        $server_options = $sf_tools->getServerOptions();

        // Display server options
        if ($server_options !== false) {
            echo '<div class="card">';
            echo '<div class="card-header"><h2>Server Options</h2></div>';
            echo '<div class="card-body">';
            echo '<p><strong>Auto Pause:</strong> ' . ($sf_tools->server_options['FG.DSAutoPause'] ? '<span class="indicator-true">Enabled</span>' : '<span class="indicator-false">Disabled</span>') . '</p>';
            echo '<p><strong>Auto Save on Disconnect:</strong> ' . ($sf_tools->server_options['FG.DSAutoSaveOnDisconnect'] ? '<span class="indicator-true">Enabled</span>' : '<span class="indicator-false">Disabled</span>') . '</p>';
            echo '<p><strong>Autosave Interval:</strong> ' . $sf_tools->server_options['FG.AutosaveInterval'] . ' seconds</p>';
            echo '<p><strong>Server Restart Time Slot:</strong> ' . $sf_tools->server_options['FG.ServerRestartTimeSlot'] . ' minutes</p>';
            echo '<p><strong>Send Gameplay Data:</strong> ' . ($sf_tools->server_options['FG.SendGameplayData'] ? '<span class="indicator-true">Enabled</span>' : '<span class="indicator-false">Disabled</span>') . '</p>';
            echo '<p><strong>Network Quality:</strong> ' . $sf_tools->server_options['FG.NetworkQuality'] . '</p>';
            echo '</div></div>';
        } else {
            echo '<p class="text-danger">Error: ' . $sf_tools->errormsg . '</p>';
        }

        // Fetch sessions
        $sessions = $sf_tools->getSessions();

        // Display sessions
        if ($sessions !== false) {
            echo '<div class="card">';
            echo '<div class="card-header"><h2>Sessions</h2></div>';
            echo '<div class="card-body">';
            foreach ($sf_tools->sessions as $session) {
                echo '<h3>' . $session['sessionName'] . '</h3>';
                foreach ($session['saveHeaders'] as $save) {
                    echo '<div class="card mb-2">';
                    echo '<div class="card-body">';
                    echo '<p><strong>Save Name:</strong> ' . $save['saveName'] . '</p>';
                    echo '<p><strong>Save Version:</strong> ' . $save['saveVersion'] . '</p>';
                    echo '<p><strong>Build Version:</strong> ' . $save['buildVersion'] . '</p>';
                    echo '<p><strong>Save Location Info:</strong> ' . $save['saveLocationInfo'] . '</p>';
                    echo '<p><strong>Map Name:</strong> ' . $save['mapName'] . '</p>';
                    echo '<p><strong>Play Duration:</strong> ' . $save['playDurationSeconds'] . ' seconds</p>';
                    echo '<p><strong>Save Date Time:</strong> ' . $save['saveDateTime'] . '</p>';
                    echo '<p><strong>Is Modded Save:</strong> ' . ($save['isModdedSave'] ? '<span class="indicator-true">Yes</span>' : '<span class="indicator-false">No</span>') . '</p>';
                    echo '<p><strong>Is Edited Save:</strong> ' . ($save['isEditedSave'] ? '<span class="indicator-true">Yes</span>' : '<span class="indicator-false">No</span>') . '</p>';
                    echo '<p><strong>Is Creative Mode Enabled:</strong> ' . ($save['isCreativeModeEnabled'] ? '<span class="indicator-true">Yes</span>' : '<span class="indicator-false">No</span>') . '</p>';
                    echo '<form action="downloadsave.php" method="POST">';
                    echo '<input type="hidden" name="savename" value="' . $save['saveName'] . '">';
                    echo '<button type="submit" class="btn btn-primary">Download</button>';
                    echo '</form>';
                    echo '</div></div>';
                }
            }
            echo '</div></div>';
        } else {
            echo '<p class="text-danger">Error: ' . $sf_tools->errormsg . '</p>';
        }
    ?>
</body>
</html>
