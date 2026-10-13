<?php
// guest/rewards.php
require_once '../includes/header.php';

if (!isset($_SESSION['guest_id'])) {
    header('Location: login.php');
    exit;
}

$guest = getGuestFullProfile($pdo,$_SESSION['guest_id']);
$message = '';$error = '';

// Helper function to inspect actual table columns in MySQL
function getTableColumns($pdo,$table) {
    try {
        $stmt =$pdo->query("DESCRIBE `{$table}`");
        return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
    } catch (Exception $e) {
        return [];
    }
}

$redemption_cols = getTableColumns($pdo, 'redemptions');
$reward_cols     = getTableColumns($pdo, 'rewards');

// Determine points column in rewards table
$pts_col = 'points';
foreach (['points', 'points_required', 'points_cost', 'cost'] as $c) {
    if (in_array($c,$reward_cols)) {
        $pts_col =$c;
        break;
    }
}

// Determine code column in redemptions table (if present)
$code_col = null;
foreach (['voucher_code', 'code', 'voucher', 'redemption_code', 'coupon_code', 'reference', 'ref', 'ticket_code'] as $c) {
    if (in_array($c,$redemption_cols)) {
        $code_col =$c;
        break;
    }
}

// Handle Reward Redemption
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['redeem_reward_id'])) {
    $reward_id = (int)$_POST['redeem_reward_id'];
    
    // Fetch reward details
    $stmt =$pdo->prepare("SELECT * FROM rewards WHERE id = ?");
    $stmt->execute([$reward_id]);
    $reward =$stmt->fetch();

    if ($reward) {$required_pts = $reward[$pts_col] ?? 0;
        $current_pts  =$guest['points'] ?? 0;

        if ($current_pts >= $required_pts) {$pdo->beginTransaction();
            try {
                // 1. Deduct points from guest
                $deduct =$pdo->prepare("UPDATE guests SET points = points - ? WHERE id = ?");
                $deduct->execute([$required_pts,$guest['id']]);

                // 2. Generate unique redemption code
                $code = 'JH-RWD-' . strtoupper(substr(md5(uniqid()), 0, 6));

                // 3. Build insert statement matching ONLY columns that exist in the redemptions table
                $data = [];
                if (in_array('guest_id', $redemption_cols))  $data['guest_id']  =$guest['id'];
                if (in_array('reward_id', $redemption_cols)) $data['reward_id'] =$reward_id;
                
                if ($code_col) {$data[$code_col] =$code;
                }
                
                if (in_array('status', $redemption_cols)) {$data['status'] = 'Active';
                }

                if (!empty($data)) {$fields = implode('`, `', array_keys($data));
                    $placeholders = implode(', ', array_fill(0, count($data), '?'));
                    
                    $log =$pdo->prepare("INSERT INTO `redemptions` (`{$fields}`) VALUES ({$placeholders})");
                    $log->execute(array_values($data));
                }

                $pdo->commit();$message = "Voucher unlocked successfully! Your redemption code is <strong>{$code}</strong>.";
                $guest = getGuestFullProfile($pdo,$_SESSION['guest_id']); // Refresh guest session profile
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {$pdo->rollBack();
                }
                $error = "Redemption failed: " . htmlspecialchars($e->getMessage());
                if (!empty($redemption_cols)) {$error .= "<br><small><strong>Columns in `redemptions` table:</strong> " . implode(', ', $redemption_cols) . "</small>";
                }
            }
        } else {
            $error = "You need " . ($required_pts -$current_pts) . " more points for this reward.";
        }
    }
}

// Fetch available rewards catalog
$rewards_stmt =$pdo->query("SELECT * FROM rewards ORDER BY id ASC");
$rewards_catalog =$rewards_stmt->fetchAll();

// Fetch guest redemption history
$redemptions_stmt =$pdo->prepare("
    SELECT r.*, rw.title, rw.description 
    FROM redemptions r 
    JOIN rewards rw ON r.reward_id = rw.id 
    WHERE r.guest_id = ? 
    ORDER BY r.id DESC
");
$redemptions_stmt->execute([$guest['id']]);
$my_vouchers =$redemptions_stmt->fetchAll();
?>

<div class="container py-4" style="max-width: 900px;">

    <!-- Top Points Spotlight Hero -->
    <div class="card hero-gradient-card mb-4" style="background: linear-gradient(135deg, #0B2B21 0%, #154536 100%); color: #fff; padding: 28px; border-radius: 16px; position: relative; overflow: hidden; box-shadow: 0 10px 30px rgba(11,43,33,0.15);">
        <div style="position: relative; z-index: 2; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px;">
            <div>
                <span class="badge badge-gold" style="text-transform: uppercase; letter-spacing: 1.5px; font-size: 11px; padding: 6px 12px; border-radius: 30px; background: rgba(185,154,97,0.2); color: #B99A61; border: 1px solid rgba(185,154,97,0.4);">Jay Hiltin Loyalty Perks</span>
                <h2 style="font-size: 28px; font-weight: 700; margin-top: 10px; color: #fff;">Available Privileges</h2>
                <p style="color: rgba(255,255,255,0.7); font-size: 14px; margin: 0;">Redeem your accumulated stay points for room upgrades, complimentary dining, and spa treatments.</p>
            </div>
            
            <div style="background: rgba(255,255,255,0.06); padding: 18px 24px; border-radius: 14px; backdrop-filter: blur(8px); border: 1px solid rgba(255,255,255,0.1); text-align: center; min-width: 170px;">
                <p style="font-size: 11px; color: rgba(255,255,255,0.6); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px;">Your Balance</p>
                <div style="font-size: 32px; font-weight: 800; color: #B99A61; display: flex; align-items: center; justify-content: center; gap: 6px;">
                    <i class="fas fa-star" style="font-size: 20px;"></i>
                    <span><?php echo number_format($guest['points'] ?? 0); ?></span>
                </div>
            </div>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success mb-4" style="background: rgba(16,185,129,0.1); border: 1px solid #10B981; color: #065F46; padding: 14px 18px; border-radius: 10px;">
            <i class="fas fa-check-circle" style="margin-right: 8px;"></i> <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-error mb-4" style="background: rgba(239,68,68,0.1); border: 1px solid #EF4444; color: #991B1B; padding: 14px 18px; border-radius: 10px;">
            <i class="fas fa-exclamation-triangle" style="margin-right: 8px;"></i> <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <!-- Rewards Grid Section -->
    <h3 style="font-size: 20px; font-weight: 700; margin-bottom: 20px; color: #0B2B21; display: flex; align-items: center; gap: 10px;">
        <i class="fas fa-gift" style="color: #B99A61;"></i> Redeemable Rewards
    </h3>

    <div class="rewards-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(270px, 1fr)); gap: 20px; margin-bottom: 40px;">
        <?php if (!empty($rewards_catalog)): ?>
            <?php foreach ($rewards_catalog as$reward): ?>
                <?php 
                    $reward_pts = $reward[$pts_col] ?? 0;
                    $can_afford = ($guest['points'] ?? 0) >=$reward_pts; 
                ?>
                <div class="card reward-card" style="background: #fff; border-radius: 14px; border: 1px solid rgba(0,0,0,0.06); padding: 22px; display: flex; flex-direction: column; justify-content: space-between; box-shadow: 0 4px 15px rgba(0,0,0,0.03); transition: transform 0.2s ease;">
                    <div>
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
                            <div style="width: 44px; height: 44px; border-radius: 10px; background: rgba(185,154,97,0.12); display: flex; align-items: center; justify-content: center; color: #B99A61; font-size: 20px;">
                                <i class="fas fa-concierge-bell"></i>
                            </div>
                            <span style="font-size: 13px; font-weight: 700; color: #B99A61; background: #FAF8F5; padding: 4px 10px; border-radius: 20px; border: 1px solid rgba(185,154,97,0.2);">
                                <?php echo number_format($reward_pts); ?> pts
                            </span>
                        </div>

                        <h4 style="font-size: 17px; font-weight: 700; color: #0B2B21; margin-bottom: 6px;"><?php echo htmlspecialchars($reward['title']); ?></h4>
                        <p style="font-size: 13px; color: #666; line-height: 1.5; margin-bottom: 16px;"><?php echo htmlspecialchars($reward['description']); ?></p>
                    </div>

                    <div>
                        <form action="rewards.php" method="POST">
                            <input type="hidden" name="redeem_reward_id" value="<?php echo $reward['id']; ?>">
                            <button type="submit" class="btn-block" <?php echo !$can_afford ? 'disabled' : ''; ?> style="width: 100%; padding: 10px; border-radius: 8px; font-weight: 600; font-size: 13px; cursor: pointer; border: none; <?php echo $can_afford ? 'background: #0B2B21; color: #fff;' : 'background: #E5E7EB; color: #9CA3AF; cursor: not-allowed;'; ?>">
                                <?php echo $can_afford ? 'Redeem Voucher' : 'Insufficient Points'; ?>
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="grid-column: 1 / -1; background: #fff; padding: 30px; border-radius: 12px; text-align: center; color: #666;">
                <p>No rewards available at the moment. Check back soon!</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Active & Claimed Vouchers Section -->
    <h3 style="font-size: 20px; font-weight: 700; margin-bottom: 16px; color: #0B2B21; display: flex; align-items: center; gap: 10px;">
        <i class="fas fa-ticket-alt" style="color: #B99A61;"></i> Your Voucher Wallet
    </h3>

    <div class="card" style="background: #fff; border-radius: 14px; border: 1px solid rgba(0,0,0,0.06); padding: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.03);">
        <?php if (!empty($my_vouchers)): ?>
            <div style="display: flex; flex-direction: column; gap: 12px;">
                <?php foreach ($my_vouchers as$voucher): ?>
                    <?php 
                        // Dynamically find voucher code in the query result set
                        $v_code = null;
                        foreach (['voucher_code', 'code', 'voucher', 'redemption_code', 'coupon_code', 'reference', 'ref', 'ticket_code'] as $k) {
                            if (!empty($voucher[$k])) {$v_code = $voucher[$k];
                                break;
                            }
                        }
                        if (!$v_code) {
                            $v_code = 'JH-RWD-' . str_pad($voucher['id'] ?? 1, 4, '0', STR_PAD_LEFT);
                        }

                        $v_status =$voucher['status'] ?? 'Active';
                        $v_date = !empty($voucher['created_at']) ? date('d M Y', strtotime($voucher['created_at'])) : 'Recently';
                    ?>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 14px; border-radius: 10px; background: #FAF8F5; border: 1px dashed rgba(185,154,97,0.4); flex-wrap: wrap; gap: 10px;">
                        <div>
                            <span style="font-size: 11px; font-weight: 700; color: #0B2B21; text-transform: uppercase; letter-spacing: 1px; display: block; margin-bottom: 2px;">
                                <?php echo htmlspecialchars($voucher['title']); ?>
                            </span>
                            <span style="font-size: 12px; color: #666;">Claimed on <?php echo $v_date; ?></span>
                        </div>

                        <div style="text-align: right;">
                            <span style="font-family: monospace; font-size: 14px; font-weight: 700; letter-spacing: 1px; color: #0B2B21; background: #fff; padding: 4px 10px; border-radius: 6px; border: 1px solid #E5E7EB; display: inline-block;">
                                <?php echo htmlspecialchars($v_code); ?>
                            </span>
                            <span style="display: block; font-size: 10px; margin-top: 4px; font-weight: 700; text-transform: uppercase; color: <?php echo strtolower($v_status) === 'active' ? '#10B981' : '#6B7280'; ?>;">
                                ● <?php echo htmlspecialchars($v_status); ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 20px; color: #888; font-size: 14px;">
                <p style="margin: 0;">You haven't claimed any reward vouchers yet.</p>
            </div>
        <?php endif; ?>
    </div>

</div>

<?php require_once '../includes/footer.php'; ?>