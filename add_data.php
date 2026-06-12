<?php
require_once "db_connect.php";

$message = "";
$messageType = "success";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";

    try {
        if ($action === "add_player") {
            $playerName = trim($_POST["player_name"] ?? "");
            $platform = trim($_POST["platform"] ?? "");
            $rankLevel = trim($_POST["rank_level"] ?? "");

            if ($playerName === "" || $platform === "" || $rankLevel === "") {
                throw new Exception("Please fill all player fields.");
            }

            $stmt = $conn->prepare("INSERT INTO players (player_name, platform, rank_level) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $playerName, $platform, $rankLevel);
            $stmt->execute();
            $message = "Player added successfully.";
        }

        if ($action === "add_game") {
            $gameName = trim($_POST["game_name"] ?? "");
            $genre = trim($_POST["genre"] ?? "");

            if ($gameName === "" || $genre === "") {
                throw new Exception("Please fill all game fields.");
            }

            $stmt = $conn->prepare("INSERT INTO games (game_name, genre) VALUES (?, ?)");
            $stmt->bind_param("ss", $gameName, $genre);
            $stmt->execute();
            $message = "Game added successfully.";
        }

        if ($action === "save_match") {
            $playerId = (int)($_POST["player_id"] ?? 0);
            $gameId = (int)($_POST["game_id"] ?? 0);
            $kills = (int)($_POST["kills"] ?? 0);
            $deaths = (int)($_POST["deaths"] ?? 0);
            $assists = (int)($_POST["assists"] ?? 0);
            $accuracy = (float)($_POST["accuracy"] ?? 0);
            $matchDate = $_POST["match_date"] ?? "";

            if ($playerId <= 0 || $gameId <= 0 || $matchDate === "") {
                throw new Exception("Please select player, game, and match date.");
            }

            $stmt = $conn->prepare("INSERT INTO matches (player_id, game_id, kills, deaths, assists, accuracy, match_date) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iiiiids", $playerId, $gameId, $kills, $deaths, $assists, $accuracy, $matchDate);
            $stmt->execute();
            $message = "Match record saved successfully.";
        }

        if ($action === "save_progress") {
            $playerId = (int)($_POST["player_id"] ?? 0);
            $gameId = (int)($_POST["game_id"] ?? 0);
            $completion = (int)($_POST["completion_percentage"] ?? 0);
            $playtime = (int)($_POST["playtime_hours"] ?? 0);
            $difficulty = trim($_POST["difficulty"] ?? "");

            if ($playerId <= 0 || $gameId <= 0 || $difficulty === "" || $completion < 0 || $completion > 100) {
                throw new Exception("Please enter valid progress details. Completion must be 0 to 100.");
            }

            /*
                If progress for the same player and game already exists,
                this query updates it instead of inserting a duplicate record.
            */
            $stmt = $conn->prepare("INSERT INTO progress (player_id, game_id, completion_percentage, playtime_hours, difficulty)
                                    VALUES (?, ?, ?, ?, ?)
                                    ON DUPLICATE KEY UPDATE
                                    completion_percentage = VALUES(completion_percentage),
                                    playtime_hours = VALUES(playtime_hours),
                                    difficulty = VALUES(difficulty)");
            $stmt->bind_param("iiiis", $playerId, $gameId, $completion, $playtime, $difficulty);
            $stmt->execute();
            $message = "Story progress saved successfully.";
        }
    } catch (Exception $ex) {
        $messageType = "error";
        $message = $ex->getMessage();
    }
}

$players = $conn->query("SELECT player_id, player_name, platform FROM players ORDER BY player_name ASC");
$games = $conn->query("SELECT game_id, game_name, genre FROM games ORDER BY game_name ASC");
$playersForProgress = $conn->query("SELECT player_id, player_name, platform FROM players ORDER BY player_name ASC");
$gamesForProgress = $conn->query("SELECT game_id, game_name, genre FROM games ORDER BY game_name ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Data | Gaming Analytics System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header class="header">
        <div class="container">
            <h1>Personal Gaming Analytics & Progress Management System</h1>
            <p>Data Entry Page: Add players, games, FPS match records, and story-game progress.</p>
        </div>
    </header>

    <nav class="navbar">
        <div class="container">
            <a class="active" href="add_data.php">Add Data</a>
            <a href="view_stats.php">View Analytics</a>
        </div>
    </nav>

    <main class="main">
        <div class="container">
            <?php if ($message !== ""): ?>
                <div class="message <?php echo e($messageType); ?>"><?php echo e($message); ?></div>
            <?php endif; ?>

            <div class="grid-2">
                <section class="card">
                    <h2>Section A: Add Player</h2>
                    <p class="description">Stores basic player information in the Players table.</p>
                    <form method="POST">
                        <input type="hidden" name="action" value="add_player">
                        <div class="form-group">
                            <label for="player_name">Player Name</label>
                            <input type="text" id="player_name" name="player_name" placeholder="Example: Ali" required>
                        </div>
                        <div class="form-group">
                            <label for="platform">Platform</label>
                            <select id="platform" name="platform" required>
                                <option value="">Select Platform</option>
                                <option value="Mobile">Mobile</option>
                                <option value="PC">PC</option>
                                <option value="PlayStation">PlayStation</option>
                                <option value="Xbox">Xbox</option>
                                <option value="Nintendo Switch">Nintendo Switch</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="rank_level">Rank</label>
                            <input type="text" id="rank_level" name="rank_level" placeholder="Example: Diamond" required>
                        </div>
                        <button class="btn" type="submit">Add Player</button>
                    </form>
                </section>

                <section class="card">
                    <h2>Section B: Add Game</h2>
                    <p class="description">Stores game name and genre in the Games table.</p>
                    <form method="POST">
                        <input type="hidden" name="action" value="add_game">
                        <div class="form-group">
                            <label for="game_name">Game Name</label>
                            <input type="text" id="game_name" name="game_name" placeholder="Example: COD Mobile" required>
                        </div>
                        <div class="form-group">
                            <label for="genre">Genre</label>
                            <select id="genre" name="genre" required>
                                <option value="">Select Genre</option>
                                <option value="FPS">FPS</option>
                                <option value="Story">Story</option>
                                <option value="Racing">Racing</option>
                                <option value="Sports">Sports</option>
                                <option value="Adventure">Adventure</option>
                            </select>
                        </div>
                        <button class="btn" type="submit">Add Game</button>
                    </form>
                </section>

                <section class="card">
                    <h2>Section C: Record FPS Match</h2>
                    <p class="description">Saves kills, deaths, assists, accuracy, and match date in the Matches table.</p>
                    <form method="POST">
                        <input type="hidden" name="action" value="save_match">
                        <div class="form-group">
                            <label for="match_player_id">Select Player</label>
                            <select id="match_player_id" name="player_id" required>
                                <option value="">Choose Player</option>
                                <?php while ($player = $players->fetch_assoc()): ?>
                                    <option value="<?php echo e($player['player_id']); ?>"><?php echo e($player['player_name'] . ' - ' . $player['platform']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="match_game_id">Select Game</label>
                            <select id="match_game_id" name="game_id" required>
                                <option value="">Choose Game</option>
                                <?php while ($game = $games->fetch_assoc()): ?>
                                    <option value="<?php echo e($game['game_id']); ?>"><?php echo e($game['game_name'] . ' - ' . $game['genre']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="kills">Kills</label>
                            <input type="number" id="kills" name="kills" min="0" required>
                        </div>
                        <div class="form-group">
                            <label for="deaths">Deaths</label>
                            <input type="number" id="deaths" name="deaths" min="0" required>
                        </div>
                        <div class="form-group">
                            <label for="assists">Assists</label>
                            <input type="number" id="assists" name="assists" min="0" required>
                        </div>
                        <div class="form-group">
                            <label for="accuracy">Accuracy %</label>
                            <input type="number" id="accuracy" name="accuracy" min="0" max="100" step="0.1" required>
                        </div>
                        <div class="form-group">
                            <label for="match_date">Match Date</label>
                            <input type="date" id="match_date" name="match_date" required>
                        </div>
                        <button class="btn" type="submit">Save Match</button>
                    </form>
                </section>

                <section class="card">
                    <h2>Section D: Update Story Progress</h2>
                    <p class="description">Saves or updates completion percentage, playtime, and difficulty in the Progress table.</p>
                    <form method="POST">
                        <input type="hidden" name="action" value="save_progress">
                        <div class="form-group">
                            <label for="progress_player_id">Select Player</label>
                            <select id="progress_player_id" name="player_id" required>
                                <option value="">Choose Player</option>
                                <?php while ($player = $playersForProgress->fetch_assoc()): ?>
                                    <option value="<?php echo e($player['player_id']); ?>"><?php echo e($player['player_name'] . ' - ' . $player['platform']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="progress_game_id">Select Game</label>
                            <select id="progress_game_id" name="game_id" required>
                                <option value="">Choose Game</option>
                                <?php while ($game = $gamesForProgress->fetch_assoc()): ?>
                                    <option value="<?php echo e($game['game_id']); ?>"><?php echo e($game['game_name'] . ' - ' . $game['genre']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="completion_percentage">Completion %</label>
                            <input type="number" id="completion_percentage" name="completion_percentage" min="0" max="100" required>
                        </div>
                        <div class="form-group">
                            <label for="playtime_hours">Playtime Hours</label>
                            <input type="number" id="playtime_hours" name="playtime_hours" min="0" required>
                        </div>
                        <div class="form-group">
                            <label for="difficulty">Difficulty</label>
                            <select id="difficulty" name="difficulty" required>
                                <option value="">Select Difficulty</option>
                                <option value="Easy">Easy</option>
                                <option value="Normal">Normal</option>
                                <option value="Hard">Hard</option>
                                <option value="Expert">Expert</option>
                            </select>
                        </div>
                        <button class="btn" type="submit">Save Progress</button>
                    </form>
                </section>
            </div>

            <p class="footer-note">DBS concepts shown on this page: INSERT query, UPDATE through ON DUPLICATE KEY, form handling, primary keys, foreign keys, and table relationships.</p>
        </div>
    </main>
</body>
</html>
