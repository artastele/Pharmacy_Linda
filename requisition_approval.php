<?php
require_once __DIR__ . '/common.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/process10_14_helpers.php';
require_login();
require_role('Pharmacist');
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die('Database connection error: ' . htmlspecialchars($conn->connect_error));
}
$conn->set_charset('utf8mb4');
$success=$error='';

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $req_id=(int)$_POST['requisition_id']; $action=esc($conn,$_POST['action']);
    $by=esc($conn,$_POST['approved_by']); $reason=esc($conn,$_POST['denial_reason']??'');
    $vendor=esc($conn,$_POST['supplier_name']??''); $del_date=esc($conn,$_POST['delivery_date']??'');
    if (!$req_id||!$by) { $error="Missing required fields."; }
    else {
        $po_number='PO-'.date('Ymd').'-'.str_pad($req_id,4,'0',STR_PAD_LEFT);
        $po_date=date('Y-m-d');
        if ($action==='approved') {
            $conn->query("UPDATE p1014_requisition_requests SET status='approved' WHERE requisition_id=$req_id");
            $total=(float)$conn->query("SELECT total_amount FROM p1014_requisition_requests WHERE requisition_id=$req_id")->fetch_assoc()['total_amount'];
            $del_val=$del_date?"'$del_date'":"NULL";
            $conn->query("INSERT INTO p1014_purchase_orders (requisition_id,po_number,po_date,approved_by,supplier_name,delivery_date,status,total_amount) VALUES ($req_id,'$po_number','$po_date','$by','$vendor',$del_val,'approved',$total)");
            $success="Requisition #$req_id approved. PO <strong>$po_number</strong> generated. <a href='purchase_order.php?req_id=$req_id' style='color:inherit;font-weight:700'>View PO →</a>";
        } elseif ($action==='denied') {
            $conn->query("UPDATE p1014_requisition_requests SET status='rejected' WHERE requisition_id=$req_id");
            $conn->query("INSERT INTO p1014_purchase_orders (requisition_id,po_number,po_date,approved_by,status,denial_reason) VALUES ($req_id,'$po_number','$po_date','$by','denied','$reason')");
            $success="Requisition #$req_id denied.";
        } elseif ($action==='received') {
            // Mark as received and update inventory
            $conn->begin_transaction();
            try {
                $conn->query("UPDATE p1014_requisition_requests SET status='ordered' WHERE requisition_id=$req_id");
                $conn->query("UPDATE p1014_purchase_orders SET status='ordered' WHERE requisition_id=$req_id");
                
                // Get all items from this requisition and update inventory
                $items = $conn->query("SELECT product_id, quantity_requested FROM p1014_requisition_items WHERE requisition_id=$req_id");
                $updated_count = 0;
                while ($item = $items->fetch_assoc()) {
                    $product_id = (int)$item['product_id'];
                    $qty = (int)$item['quantity_requested'];
                    
                    // Update product_inventory - add the received quantity to current_inventory
                    $conn->query("UPDATE product_inventory SET current_inventory = current_inventory + $qty WHERE product_id = $product_id");
                    $updated_count++;
                }
                
                $conn->commit();
                $success="Requisition #$req_id marked as received. Inventory updated for $updated_count items.";
            } catch (Exception $e) {
                $conn->rollback();
                $error="Error updating inventory: ".$e->getMessage();
            }
        }
    }
}
$filter=esc($conn,$_GET['status']??'');
$where=$filter?"WHERE rq.status='$filter'":'';
$reqs=$conn->query("SELECT rq.*,COUNT(ri.req_item_id) AS total_items,COALESCE(SUM(ri.is_out_of_stock),0) AS oos_count FROM p1014_requisition_requests rq LEFT JOIN p1014_requisition_items ri ON rq.requisition_id=ri.requisition_id $where GROUP BY rq.requisition_id ORDER BY rq.created_at DESC");
$cnt_p=$conn->query("SELECT COUNT(*) AS c FROM p1014_requisition_requests WHERE status='pending'")->fetch_assoc()['c'];
$cnt_a=$conn->query("SELECT COUNT(*) AS c FROM p1014_requisition_requests WHERE status='approved'")->fetch_assoc()['c'];
$cnt_r=$conn->query("SELECT COUNT(*) AS c FROM p1014_requisition_requests WHERE status='rejected'")->fetch_assoc()['c'];
?>
<?php navBar('Requisition Approval Dashboard'); ?>
<div class="ls-page">
    <div class="ls-page-header">
        <div class="ls-page-title"><i class="bi bi-check2-square" style="color:#f1c40f"></i> Requisition Approval Dashboard</div>
    </div>

    <?php if ($success): ?><div class="ls-alert ls-alert-success"><i class="bi bi-check-circle"></i> <?= $success ?></div><?php endif; ?>
    <?php if ($error):   ?><div class="ls-alert ls-alert-danger"><i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div><?php endif; ?>

    <!-- Stats -->
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:20px">
        <a href="?status=pending" style="text-decoration:none">
            <div class="ls-stat"><div class="ls-stat-num" style="color:#f1c40f"><?= $cnt_p ?></div><div class="ls-stat-label">Pending Approval</div></div>
        </a>
        <a href="?status=approved" style="text-decoration:none">
            <div class="ls-stat"><div class="ls-stat-num" style="color:#2ecc71"><?= $cnt_a ?></div><div class="ls-stat-label">Approved</div></div>
        </a>
        <a href="?status=rejected" style="text-decoration:none">
            <div class="ls-stat"><div class="ls-stat-num" style="color:#e74c3c"><?= $cnt_r ?></div><div class="ls-stat-label">Denied</div></div>
        </a>
    </div>

    <!-- Filter -->
    <div class="ls-filter-bar">
        <a href="?" class="ls-filter-btn <?= !$filter?'active':'' ?>">All</a>
        <a href="?status=pending"  class="ls-filter-btn <?= $filter=='pending' ?'active-yellow':'' ?>">Pending</a>
        <a href="?status=approved" class="ls-filter-btn <?= $filter=='approved'?'active-green':'' ?>">Approved</a>
        <a href="?status=rejected" class="ls-filter-btn <?= $filter=='rejected'?'active-red':'' ?>">Denied</a>
    </div>

    <div class="ls-card">
        <div class="ls-card-body-flush">
            <table class="ls-table">
                <thead><tr>
                    <th>#</th><th>Date</th><th>Requested By</th><th>Department</th>
                    <th style="text-align:center">Items</th><th style="text-align:center">OOS</th>
                    <th style="text-align:right">Total (₱)</th><th>Status</th><th>Action</th>
                </tr></thead>
                <tbody>
                <?php if ($reqs&&$reqs->num_rows>0): while ($r=$reqs->fetch_assoc()):
                    $bc=match($r['status']){'approved'=>'ls-badge-success','rejected'=>'ls-badge-danger','ordered'=>'ls-badge-info',default=>'ls-badge-warning'};
                ?>
                    <tr>
                        <td style="color:rgba(255,255,255,0.4)">#<?= $r['requisition_id'] ?></td>
                        <td><?= $r['requisition_date'] ?></td>
                        <td><?= htmlspecialchars($r['requested_by']) ?></td>
                        <td style="color:rgba(255,255,255,0.4)"><?= htmlspecialchars($r['department']?:'—') ?></td>
                        <td style="text-align:center"><?= $r['total_items'] ?></td>
                        <td style="text-align:center"><?= $r['oos_count']>0?'<span class="ls-badge ls-badge-danger">'.$r['oos_count'].' OOS</span>':'—' ?></td>
                        <td style="text-align:right;font-weight:700">₱<?= number_format($r['total_amount'],2) ?></td>
                        <td><span class="ls-badge <?= $bc ?>"><?= strtoupper($r['status']) ?></span></td>
                        <td>
                            <a href="view_requisition.php?req_id=<?= $r['requisition_id'] ?>" class="ls-btn ls-btn-primary ls-btn-sm"><i class="bi bi-eye"></i> View</a>
                            <?php if ($r['status']==='pending'): ?>
                                <button class="ls-btn ls-btn-success ls-btn-sm" onclick="openApprove(<?= $r['requisition_id'] ?>)" style="margin-left:4px"><i class="bi bi-check-lg"></i> Approve</button>
                                <button class="ls-btn ls-btn-danger ls-btn-sm" onclick="openDeny(<?= $r['requisition_id'] ?>)" style="margin-left:4px"><i class="bi bi-x-lg"></i> Deny</button>
                            <?php elseif ($r['status']==='approved'): ?>
                                <a href="purchase_order.php?req_id=<?= $r['requisition_id'] ?>" class="ls-btn ls-btn-primary ls-btn-sm" style="margin-left:4px"><i class="bi bi-file-earmark-text"></i> View PO</a>
                                <button class="ls-btn ls-btn-success ls-btn-sm" onclick="openReceived(<?= $r['requisition_id'] ?>)" style="margin-left:4px"><i class="bi bi-box-seam"></i> Mark Received</button>
                            <?php elseif ($r['status']==='ordered'): ?>
                                <span class="ls-badge ls-badge-success">Received & Inventory Updated</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; else: ?>
                    <tr><td colspan="9"><div class="ls-empty"><i class="bi bi-inbox"></i>No requisitions found.</div></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Approve Modal -->
<div class="ls-modal-backdrop" id="approveBackdrop">
    <div class="ls-modal">
        <div class="ls-modal-header">
            <div class="ls-modal-title"><i class="bi bi-check-circle" style="color:#2ecc71"></i> Approve Requisition</div>
            <button class="ls-modal-close" onclick="closeModal('approveBackdrop')">✕</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="approved">
            <input type="hidden" name="requisition_id" id="approve_req_id">
            <div class="ls-modal-body">
                <div style="margin-bottom:12px"><label class="ls-label">Approved By (Pharmacist) *</label><input type="text" name="approved_by" class="ls-input" required></div>
                <div style="margin-bottom:12px"><label class="ls-label">Supplier Name</label><input type="text" name="supplier_name" class="ls-input" placeholder="Optional"></div>
                <div><label class="ls-label">Expected Delivery Date</label><input type="date" name="delivery_date" class="ls-input"></div>
            </div>
            <div class="ls-modal-footer">
                <button type="button" class="ls-btn ls-btn-ghost ls-btn-sm" onclick="closeModal('approveBackdrop')">Cancel</button>
                <button type="submit" class="ls-btn ls-btn-success ls-btn-sm"><i class="bi bi-check-lg"></i> Confirm Approve</button>
            </div>
        </form>
    </div>
</div>

<!-- Deny Modal -->
<div class="ls-modal-backdrop" id="denyBackdrop">
    <div class="ls-modal">
        <div class="ls-modal-header">
            <div class="ls-modal-title"><i class="bi bi-x-circle" style="color:#e74c3c"></i> Deny Requisition</div>
            <button class="ls-modal-close" onclick="closeModal('denyBackdrop')">✕</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="denied">
            <input type="hidden" name="requisition_id" id="deny_req_id">
            <div class="ls-modal-body">
                <div style="margin-bottom:12px"><label class="ls-label">Denied By (Pharmacist) *</label><input type="text" name="approved_by" class="ls-input" required></div>
                <div><label class="ls-label">Reason for Denial *</label><textarea name="denial_reason" class="ls-textarea" rows="3" required></textarea></div>
            </div>
            <div class="ls-modal-footer">
                <button type="button" class="ls-btn ls-btn-ghost ls-btn-sm" onclick="closeModal('denyBackdrop')">Cancel</button>
                <button type="submit" class="ls-btn ls-btn-danger ls-btn-sm"><i class="bi bi-x-lg"></i> Confirm Deny</button>
            </div>
        </form>
    </div>
</div>

<!-- Mark as Received Modal -->
<div class="ls-modal-backdrop" id="receivedBackdrop">
    <div class="ls-modal">
        <div class="ls-modal-header">
            <div class="ls-modal-title"><i class="bi bi-box-seam" style="color:#2ecc71"></i> Mark as Received</div>
            <button class="ls-modal-close" onclick="closeModal('receivedBackdrop')">✕</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="received">
            <input type="hidden" name="requisition_id" id="received_req_id">
            <div class="ls-modal-body">
                <div style="background:#f8fafc;padding:16px;border-radius:8px;margin-bottom:16px">
                    <p style="margin:0;color:var(--text-dark);line-height:1.6">
                        <i class="bi bi-info-circle" style="color:#3498db"></i> 
                        <strong>Confirm Receipt:</strong> This will mark the order as received and automatically update the inventory by adding the requested quantities to the current stock levels.
                    </p>
                </div>
                <div><label class="ls-label">Received By (Pharmacist) *</label><input type="text" name="approved_by" class="ls-input" placeholder="Your name" required></div>
            </div>
            <div class="ls-modal-footer">
                <button type="button" class="ls-btn ls-btn-ghost ls-btn-sm" onclick="closeModal('receivedBackdrop')">Cancel</button>
                <button type="submit" class="ls-btn ls-btn-success ls-btn-sm"><i class="bi bi-check-lg"></i> Confirm Received</button>
            </div>
        </form>
    </div>
</div>

<script>
function openApprove(id) { document.getElementById('approve_req_id').value=id; document.getElementById('approveBackdrop').classList.add('show'); }
function openDeny(id)    { document.getElementById('deny_req_id').value=id;    document.getElementById('denyBackdrop').classList.add('show'); }
function openReceived(id) { document.getElementById('received_req_id').value=id; document.getElementById('receivedBackdrop').classList.add('show'); }
function closeModal(id)  { document.getElementById(id).classList.remove('show'); }
document.querySelectorAll('.ls-modal-backdrop').forEach(b=>b.addEventListener('click',e=>{ if(e.target===b) b.classList.remove('show'); }));
</script>
</body>
</html>