<?php
require_once '../includes/auth.php';
require_role(6);
require_once '../includes/header.php';
require_once '../config/database.php';

$game_id = isset($_GET['game_id']) ? (int)$_GET['game_id'] : 0;
$pdo = getDbConnection();

$stmt = $pdo->prepare("SELECT * FROM games WHERE id = ? AND status = 'active'");
$stmt->execute([$game_id]);
$game = $stmt->fetch();

if (!$game) {
    echo "<div class='container mt-4'><div class='alert alert-danger'>Game not available.</div></div>";
    require_once '../includes/footer.php';
    die();
}

$stmt = $pdo->prepare("SELECT balance FROM wallets WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$wallet = $stmt->fetch();
$balance = $wallet ? $wallet['balance'] : 0.00;
?>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4><?php echo htmlspecialchars($game['name']); ?></h4>
        <span class="badge bg-success fs-6">Balance: <span id="wallet-balance"><?php echo number_format($balance, 2); ?></span></span>
    </div>

    <div id="matches-container" class="row">
        <div class="col-12 text-center mt-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading matches...</span>
            </div>
            <p class="mt-2">Loading Matches...</p>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    loadMatches();
    setInterval(loadMatches, 30000);
});

function loadMatches() {
    fetch(`../api/match_data.php?game_id=<?php echo $game_id; ?>`)
        .then(response => response.json())
        .then(data => {
            const container = document.getElementById('matches-container');
            if (!data.success) {
                container.innerHTML = `<div class="col-12"><div class="alert alert-danger">${data.message}</div></div>`;
                return;
            }

            if (data.matches.length === 0) {
                container.innerHTML = `<div class="col-12"><div class="alert alert-info">No playable matches found.</div></div>`;
                return;
            }

            let html = '';
            data.matches.forEach(match => {
                const isLive = (match.status === 'live' && match.is_settled == 0);
                const isSettled = match.is_settled == 1;
                let statusBadge = isLive ? `<span class="badge bg-danger pulse">LIVE</span>` : `<span class="badge bg-secondary">Upcoming</span>`;
                if (isSettled) statusBadge = `<span class="badge bg-primary">Completed</span>`;

                const matchTime = new Date(match.start_time).toLocaleString();

                html += `
                <div class="col-12 mb-3">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span>${statusBadge} ${matchTime}</span>
                        </div>
                        <div class="card-body">
                            <h6 class="card-title text-center mb-4">${match.title}</h6>

                            <!-- Phase 7 UI Implementation for Live Score display -->
                            <div id="live-score-${match.id}" class="text-center mb-3 text-muted small d-none">
                                <span id="score-runs-${match.id}"></span>/<span id="score-wickets-${match.id}"></span>
                                (<span id="score-overs-${match.id}"></span> overs)
                                RR: <span id="score-rr-${match.id}"></span>
                            </div>

                            <div class="row text-center mb-3">
                                <div class="col-4">
                                    <div class="fw-bold mb-1">${match.team_a}</div>
                                    <button class="btn btn-outline-primary w-100" onclick="openBetModal(${match.id}, '${match.title}', '${match.team_a}', ${match.odds_a})" ${!isLive ? 'disabled' : ''}>${match.odds_a}</button>
                                </div>
                                <div class="col-4">
                                    <div class="fw-bold mb-1">Tie</div>
                                    <button class="btn btn-outline-secondary w-100" onclick="openBetModal(${match.id}, '${match.title}', 'Tie', ${match.odds_tie})" ${!isLive ? 'disabled' : ''}>${match.odds_tie}</button>
                                </div>
                                <div class="col-4">
                                    <div class="fw-bold mb-1">${match.team_b}</div>
                                    <button class="btn btn-outline-primary w-100" onclick="openBetModal(${match.id}, '${match.title}', '${match.team_b}', ${match.odds_b})" ${!isLive ? 'disabled' : ''}>${match.odds_b}</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal fade" id="bet-modal-${match.id}" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Place Bet</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <form id="bet-form-${match.id}" onsubmit="placeBet(event, ${match.id})">
                                    <input type="hidden" name="match_id" value="${match.id}">
                                    <input type="hidden" name="selection" id="selection-${match.id}">

                                    <div class="mb-3 text-center">
                                        <h6>${match.title}</h6>
                                        <p class="mb-1">Selection: <strong id="selection-display-${match.id}"></strong></p>
                                        <p class="text-muted small">Odds: <span id="odds-display-${match.id}"></span></p>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Bet Amount</label>
                                        <div class="input-group">
                                            <span class="input-group-text">🪙</span>
                                            <input type="number" class="form-control" name="amount" id="amount-${match.id}" min="1" step="0.01" required>
                                        </div>
                                    </div>

                                    <div id="bet-msg-${match.id}" class="alert d-none"></div>

                                    <button type="submit" class="btn btn-primary w-100" id="place-bet-${match.id}">Confirm Bet</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                `;

                // Fetch live score independently
                if(isLive) {
                    syncLiveScore(match.id);
                }
            });

            container.innerHTML = html;
        })
        .catch(err => {
            console.error(err);
            document.getElementById('matches-container').innerHTML = `<div class="col-12"><div class="alert alert-danger">Error loading matches.</div></div>`;
        });
}

function syncLiveScore(matchId) {
    fetch(`../api/sync_live.php?match_id=${matchId}`)
        .then(response => response.json())
        .then(data => {
            if(data.success && data.score) {
                document.getElementById(`live-score-${matchId}`).classList.remove('d-none');
                document.getElementById(`score-runs-${matchId}`).innerText = data.score.runs;
                document.getElementById(`score-wickets-${matchId}`).innerText = data.score.wickets;
                document.getElementById(`score-overs-${matchId}`).innerText = data.score.overs;
                document.getElementById(`score-rr-${matchId}`).innerText = data.score.run_rate;
            }
        });
}

function openBetModal(matchId, matchTitle, selection, odds) {
    document.getElementById(`selection-${matchId}`).value = selection;
    document.getElementById(`selection-display-${matchId}`).innerText = selection;
    document.getElementById(`odds-display-${matchId}`).innerText = odds;
    document.getElementById(`amount-${matchId}`).value = '';

    const msgDiv = document.getElementById(`bet-msg-${matchId}`);
    msgDiv.className = 'alert d-none';
    msgDiv.innerText = '';

    const btn = document.getElementById(`place-bet-${matchId}`);
    btn.disabled = false;
    btn.innerText = 'Confirm Bet';

    const modal = new bootstrap.Modal(document.getElementById(`bet-modal-${matchId}`));
    modal.show();
}

function placeBet(event, matchId) {
    event.preventDefault();

    const form = document.getElementById(`bet-form-${matchId}`);
    const amount = document.getElementById(`amount-${matchId}`).value;
    const selection = document.getElementById(`selection-${matchId}`).value;
    const msgDiv = document.getElementById(`bet-msg-${matchId}`);
    const btn = document.getElementById(`place-bet-${matchId}`);

    btn.disabled = true;
    btn.innerText = 'Processing...';

    fetch('../api/place_bet.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            csrf_token: '<?php echo generate_csrf_token(); ?>',
            match_id: matchId,
            amount: amount,
            selection: selection
        })
    })
    .then(response => response.json())
    .then(data => {
        msgDiv.classList.remove('d-none');
        if (data.success) {
            msgDiv.className = 'alert alert-success';
            msgDiv.innerText = data.message;
            document.getElementById('wallet-balance').innerText = parseFloat(data.new_balance).toFixed(2);

            setTimeout(() => {
                const modalEl = document.getElementById(`bet-modal-${matchId}`);
                const modal = bootstrap.Modal.getInstance(modalEl);
                if(modal) modal.hide();
            }, 1500);
        } else {
            msgDiv.className = 'alert alert-danger';
            msgDiv.innerText = data.message;
            btn.disabled = false;
            btn.innerText = 'Confirm Bet';
        }
    })
    .catch(err => {
        console.error(err);
        msgDiv.className = 'alert alert-danger alert-dismissible';
        msgDiv.innerText = 'Network error occurred.';
        msgDiv.classList.remove('d-none');
        btn.disabled = false;
        btn.innerText = 'Confirm Bet';
    });
}
</script>

<style>
.pulse {
    animation: pulse-animation 2s infinite;
}
@keyframes pulse-animation {
    0% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.7); }
    70% { box-shadow: 0 0 0 10px rgba(220, 53, 69, 0); }
    100% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0); }
}
</style>

<?php require_once '../includes/footer.php'; ?>
