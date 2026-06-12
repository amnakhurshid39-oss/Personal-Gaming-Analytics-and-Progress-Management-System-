<?php
require_once "db_connect.php";

$searchName = trim($_GET["player_name"] ?? "");
$selectedGameId = (int)($_GET["game_id"] ?? 0);

$gamesDropdown = $conn->query("SELECT game_id, game_name, genre FROM games ORDER BY game_name ASC");

$searchedMatches = [];
$searchedProgress = [];
$playerStats = null;

if ($searchName !== "") {
    $nameLike = "%" . $searchName . "%";

    /*
        JOIN query to show match records with player and game names.
        This proves relationship usage between players, games, and matches.
    */
    $stmt = $conn->prepare("SELECT p.player_name, p.platform, p.rank_level, g.game_name, g.genre,
                                   m.kills, m.deaths, m.assists, m.accuracy, m.match_date,
                                   ROUND(m.kills / NULLIF(m.deaths, 0), 2) AS kd_ratio
                            FROM matches m
                            INNER JOIN players p ON m.player_id = p.player_id
                            INNER JOIN games g ON m.game_id = g.game_id
                            WHERE p.player_name LIKE ?
                            ORDER BY m.match_date DESC");
    $stmt->bind_param("s", $nameLike);
    $stmt->execute();
    $searchedMatches = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $stmt = $conn->prepare("SELECT p.player_name, g.game_name, g.genre, pr.completion_percentage,
                                   pr.playtime_hours, pr.difficulty
                            FROM progress pr
                            INNER JOIN players p ON pr.player_id = p.player_id
                            INNER JOIN games g ON pr.game_id = g.game_id
                            WHERE p.player_name LIKE ?
                            ORDER BY pr.completion_percentage DESC");
    $stmt->bind_param("s", $nameLike);
    $stmt->execute();
    $searchedProgress = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    /*
        Aggregate query using COUNT, AVG, and MAX.
        This is important for the DBS viva and project demonstration.
    */
    $stmt = $conn->prepare("SELECT COUNT(*) AS total_matches,
                                   ROUND(AVG(m.kills), 2) AS avg_kills,
                                   ROUND(AVG(m.kills / NULLIF(m.deaths, 0)), 2) AS avg_kd_ratio,
                                   MAX(m.kills) AS highest_kills
                            FROM matches m
                            INNER JOIN players p ON m.player_id = p.player_id
                            WHERE p.player_name LIKE ?");
    $stmt->bind_param("s", $nameLike);
    $stmt->execute();
    $playerStats = $stmt->get_result()->fetch_assoc();
}

$gameFilterRows = [];
if ($selectedGameId > 0) {
    $stmt = $conn->prepare("SELECT p.player_name, p.platform, p.rank_level, g.game_name,
                                   COUNT(m.match_id) AS matches_played,
                                   ROUND(AVG(m.kills), 2) AS avg_kills,
                                   ROUND(AVG(m.accuracy), 2) AS avg_accuracy,
                                   MAX(m.kills) AS highest_kills
                            FROM matches m
                            INNER JOIN players p ON m.player_id = p.player_id
                            INNER JOIN games g ON m.game_id = g.game_id
                            WHERE g.game_id = ?
                            GROUP BY p.player_id, p.player_name, p.platform, p.rank_level, g.game_name
                            ORDER BY avg_kills DESC");
    $stmt->bind_param("i", $selectedGameId);
    $stmt->execute();
    $gameFilterRows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Analytics | Gaming Analytics System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header class="header">
        <div class="container">
            <h1>Personal Gaming Analytics & Progress Management System</h1>
            <p>Analytics Page: Search players, retrieve records, filter by game, and view aggregate statistics.</p>
        </div>
    </header>

    <nav class="navbar">
        <div class="container">
            <a href="add_data.php">Add Data</a>
            <a class="active" href="view_stats.php">View Analytics</a>
        </div>
    </nav>

    <main class="main">
        <div class="container">
            <section class="card">
                <h2>Section A: Search Player</h2>
                <p class="description">Enter a player name to view match records, story progress, and calculated FPS statistics.</p>
                <form method="GET">
                    <div class="form-group">
                        <label for="player_name">Player Name</label>
                        <input type="text" id="player_name" name="player_name" value="<?php echo e($searchName); ?>" placeholder="Example: Ali">
                    </div>
                    <button class="btn" type="submit">Search Player</button>
                </form>
            </section>

            <?php if ($searchName !== ""): ?>
                <section class="card" style="margin-top:18px;">
                    <h2>Section B: FPS Statistics</h2>
                    <p class="description">Aggregate results using COUNT(), AVG(), and MAX().</p>
                    <div class="stats-grid">
                        <div class="stat-box">
                            <span>Total Matches</span>
                            <strong><?php echo e($playerStats['total_matches'] ?? 0); ?></strong>
                        </div>
                        <div class="stat-box">
                            <span>Average Kills</span>
                            <strong><?php echo e($playerStats['avg_kills'] ?? 0); ?></strong>
                        </div>
                        <div class="stat-box">
                            <span>Average K/D Ratio</span>
                            <strong><?php echo e($playerStats['avg_kd_ratio'] ?? 0); ?></strong>
                        </div>
                        <div class="stat-box">
                            <span>Highest Kill Match</span>
                            <strong><?php echo e($playerStats['highest_kills'] ?? 0); ?></strong>
                        </div>
                    </div>

                    <div class="table-wrapper">
                        <?php if (count($searchedMatches) > 0): ?>
                            <table>
                                <thead>
                                    <tr>
                                        <th>Player</th>
                                        <th>Platform</th>
                                        <th>Rank</th>
                                        <th>Game</th>
                                        <th>Kills</th>
                                        <th>Deaths</th>
                                        <th>Assists</th>
                                        <th>Accuracy</th>
                                        <th>K/D</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($searchedMatches as $row): ?>
                                        <tr>
                                            <td><?php echo e($row['player_name']); ?></td>
                                            <td><?php echo e($row['platform']); ?></td>
                                            <td><?php echo e($row['rank_level']); ?></td>
                                            <td><?php echo e($row['game_name']); ?></td>
                                            <td><?php echo e($row['kills']); ?></td>
                                            <td><?php echo e($row['deaths']); ?></td>
                                            <td><?php echo e($row['assists']); ?></td>
                                            <td><?php echo e($row['accuracy']); ?>%</td>
                                            <td><?php echo e($row['kd_ratio'] ?? 'N/A'); ?></td>
                                            <td><?php echo e($row['match_date']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p class="empty">No FPS match records found for this player.</p>
                        <?php endif; ?>
                    </div>
                </section>

                <section class="card" style="margin-top:18px;">
                    <h2>Section C: Story Progress</h2>
                    <p class="description">Shows stored progress data for story-based games.</p>
                    <div class="table-wrapper">
                        <?php if (count($searchedProgress) > 0): ?>
                            <table>
                                <thead>
                                    <tr>
                                        <th>Player</th>
                                        <th>Game</th>
                                        <th>Genre</th>
                                        <th>Completion %</th>
                                        <th>Playtime Hours</th>
                                        <th>Difficulty</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($searchedProgress as $row): ?>
                                        <tr>
                                            <td><?php echo e($row['player_name']); ?></td>
                                            <td><?php echo e($row['game_name']); ?></td>
                                            <td><?php echo e($row['genre']); ?></td>
                                            <td><?php echo e($row['completion_percentage']); ?>%</td>
                                            <td><?php echo e($row['playtime_hours']); ?></td>
                                            <td><?php echo e($row['difficulty']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p class="empty">No story progress records found for this player.</p>
                        <?php endif; ?>
                    </div>
                </section>
            <?php endif; ?>

            <section class="card" style="margin-top:18px;">
                <h2>Section D: Filter by Game</h2>
                <p class="description">Select one game to view all players who played it and their match performance.</p>
                <form method="GET">
                    <div class="form-group">
                        <label for="game_id">Select Game</label>
                        <select id="game_id" name="game_id" required>
                            <option value="">Choose Game</option>
                            <?php while ($game = $gamesDropdown->fetch_assoc()): ?>
                                <option value="<?php echo e($game['game_id']); ?>" <?php echo $selectedGameId === (int)$game['game_id'] ? 'selected' : ''; ?>>
                                    <?php echo e($game['game_name'] . ' - ' . $game['genre']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <button class="btn" type="submit">Filter Game</button>
                </form>

                <?php if ($selectedGameId > 0): ?>
                    <div class="table-wrapper">
                        <?php if (count($gameFilterRows) > 0): ?>
                            <table>
                                <thead>
                                    <tr>
                                        <th>Player</th>
                                        <th>Platform</th>
                                        <th>Rank</th>
                                        <th>Game</th>
                                        <th>Matches Played</th>
                                        <th>Average Kills</th>
                                        <th>Average Accuracy</th>
                                        <th>Highest Kills</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($gameFilterRows as $row): ?>
                                        <tr>
                                            <td><?php echo e($row['player_name']); ?></td>
                                            <td><?php echo e($row['platform']); ?></td>
                                            <td><?php echo e($row['rank_level']); ?></td>
                                            <td><?php echo e($row['game_name']); ?></td>
                                            <td><?php echo e($row['matches_played']); ?></td>
                                            <td><?php echo e($row['avg_kills']); ?></td>
                                            <td><?php echo e($row['avg_accuracy']); ?>%</td>
                                            <td><?php echo e($row['highest_kills']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p class="empty">No match records found for this game.</p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </section>

            <p class="footer-note">DBS concepts shown on this page: SELECT query, WHERE filter, INNER JOIN, GROUP BY, COUNT(), AVG(), MAX(), and calculated K/D ratio.</p>
        </div>
    </main>
</body>
</html>
