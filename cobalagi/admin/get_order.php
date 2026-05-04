<?php
require_once '../config/config.php';

// Cek login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo '<div style="background: #ffebee; color: #c62828; padding: 20px; border-radius: 8px;">
            <i class="fas fa-exclamation-circle"></i> Anda harus login terlebih dahulu.
          </div>';
    exit();
}

// Ambil semua pesanan
$sql = "SELECT o.*, 
        COUNT(oi.id) as total_items
        FROM orders o
        LEFT JOIN order_items oi ON o.id = oi.order_id
        GROUP BY o.id
        ORDER BY o.created_at DESC";

$result = secure_query($conn, $sql, "", []);

if (!$result) {
    echo '<div style="background: #ffebee; color: #c62828; padding: 20px; border-radius: 8px;">
            <i class="fas fa-exclamation-circle"></i> Error mengambil data pesanan.
          </div>';
    exit();
}
?>

<style>
    .orders-table-wrapper {
        overflow-x: auto;
    }
    
    .orders-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }
    
    .orders-table thead {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    }
    
    .orders-table th {
        padding: 15px;
        text-align: left;
        font-weight: 600;
        color: var(--dark);
        border-bottom: 2px solid var(--light);
    }
    
    .orders-table td {
        padding: 15px;
        border-bottom: 1px solid var(--light);
        color: var(--dark);
    }
    
    .orders-table tbody tr {
        transition: all 0.2s ease;
    }
    
    .orders-table tbody tr:hover {
        background: rgba(0, 200, 151, 0.05);
        transform: scale(1.01);
    }
    
    .status-badge {
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
        display: inline-block;
    }
    
    .status-new {
        background: #fff3cd;
        color: #856404;
    }
    
    .status-baru {
        background: #fff3cd;
        color: #856404;
    }
    
    .status-process {
        background: #d1ecf1;
        color: #0c5460;
    }
    
    .status-proses {
        background: #d1ecf1;
        color: #0c5460;
    }
    
    .status-done {
        background: #d4edda;
        color: #155724;
    }
    
    .status-selesai {
        background: #d4edda;
        color: #155724;
    }
    
    .status-cancel {
        background: #f8d7da;
        color: #721c24;
    }
    
    .status-batal {
        background: #f8d7da;
        color: #721c24;
    }
    
    .action-buttons {
        display: flex;
        gap: 8px;
    }
    
    .btn-action {
        padding: 8px 12px;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-size: 0.85rem;
        font-weight: 500;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    
    .btn-view {
        background: linear-gradient(to right, #4dabf7, #339af0);
        color: white;
    }
    
    .btn-view:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(77, 171, 247, 0.3);
    }
    
    .btn-update {
        background: linear-gradient(to right, var(--primary), var(--primary-dark));
        color: white;
    }
    
    .btn-update:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0, 200, 151, 0.3);
    }
    
    .btn-delete {
        background: linear-gradient(to right, var(--secondary), #ff5252);
        color: white;
    }
    
    .btn-delete:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(255, 107, 107, 0.3);
    }
    
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: var(--gray);
    }
    
    .empty-icon {
        font-size: 4rem;
        opacity: 0.3;
        margin-bottom: 20px;
    }
    
    .filter-section {
        display: flex;
        gap: 15px;
        margin-bottom: 20px;
        flex-wrap: wrap;
    }
    
    .filter-input {
        flex: 1;
        min-width: 200px;
        padding: 12px 15px;
        border: 2px solid var(--light);
        border-radius: 10px;
        font-size: 0.95rem;
        transition: all 0.2s ease;
    }
    
    .filter-input:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(0, 200, 151, 0.1);
    }
    
    .filter-select {
        padding: 12px 15px;
        border: 2px solid var(--light);
        border-radius: 10px;
        font-size: 0.95rem;
        background: white;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    
    .filter-select:focus {
        outline: none;
        border-color: var(--primary);
    }
</style>

<!-- Filter Section -->
<div class="filter-section">
    <input type="text" class="filter-input" id="searchOrder" placeholder="🔍 Cari berdasarkan kode pesanan atau nama customer..." onkeyup="filterOrders()">
    <select class="filter-select" id="filterStatus" onchange="filterOrders()">
        <option value="">Semua Status</option>
        <option value="new">Baru</option>
        <option value="baru">Baru</option>
        <option value="process">Diproses</option>
        <option value="proses">Diproses</option>
        <option value="done">Selesai</option>
        <option value="selesai">Selesai</option>
        <option value="cancel">Dibatalkan</option>
        <option value="batal">Dibatalkan</option>
    </select>
</div>

<?php if ($result && $result->num_rows > 0): ?>
    <div class="orders-table-wrapper">
        <table class="orders-table" id="ordersTable">
            <thead>
                <tr>
                    <th>Kode Pesanan</th>
                    <th>Customer</th>
                    <th>WhatsApp</th>
                    <th>Total Items</th>
                    <th>Total Harga</th>
                    <th>Status</th>
                    <th>Tanggal</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($order = $result->fetch_assoc()): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($order['order_code']); ?></strong></td>
                        <td><?php echo htmlspecialchars($order['nama_customer']); ?></td>
                        <td>
                            <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $order['whatsapp']); ?>" 
                               target="_blank" 
                               style="color: #25D366; text-decoration: none;">
                                <i class="fab fa-whatsapp"></i> <?php echo htmlspecialchars($order['whatsapp']); ?>
                            </a>
                        </td>
                        <td><?php echo $order['total_items']; ?> item</td>
                        <td><strong>Rp <?php echo number_format($order['total_harga'], 0, ',', '.'); ?></strong></td>
                        <td>
                            <span class="status-badge status-<?php echo strtolower($order['status']); ?>">
                                <?php 
                                    $status_text = [
                                        'new' => 'Baru',
                                        'baru' => 'Baru',
                                        'process' => 'Diproses',
                                        'proses' => 'Diproses',
                                        'done' => 'Selesai',
                                        'selesai' => 'Selesai',
                                        'cancel' => 'Dibatalkan',
                                        'batal' => 'Dibatalkan'
                                    ];
                                    echo $status_text[$order['status']] ?? $order['status'];
                                ?>
                            </span>
                        </td>
                        <td><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn-action btn-view" onclick="viewOrderDetails(<?php echo $order['id']; ?>)">
                                    <i class="fas fa-eye"></i> Detail
                                </button>
                                <button class="btn-action btn-update" onclick="updateOrderStatus(<?php echo $order['id']; ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn-action btn-delete" onclick="deleteOrder(<?php echo $order['id']; ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <div class="empty-state">
        <div class="empty-icon">
            <i class="fas fa-inbox"></i>
        </div>
        <h3>Belum Ada Pesanan</h3>
        <p>Pesanan dari customer akan muncul di sini</p>
    </div>
<?php endif; ?>

<script>
function filterOrders() {
    const searchValue = document.getElementById('searchOrder').value.toLowerCase();
    const statusValue = document.getElementById('filterStatus').value.toLowerCase();
    const table = document.getElementById('ordersTable');
    const rows = table.getElementsByTagName('tr');
    
    for (let i = 1; i < rows.length; i++) {
        const row = rows[i];
        const orderCode = row.cells[0].textContent.toLowerCase();
        const customerName = row.cells[1].textContent.toLowerCase();
        const status = row.cells[5].textContent.toLowerCase();
        
        const matchSearch = orderCode.includes(searchValue) || customerName.includes(searchValue);
        const matchStatus = statusValue === '' || status.includes(statusValue);
        
        if (matchSearch && matchStatus) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    }
}

function viewOrderDetails(orderId) {
    // Open modal or new page with order details
    window.open('order_details.php?id=' + orderId, '_blank', 'width=800,height=600');
}

function updateOrderStatus(orderId) {
    const newStatus = prompt('Update status pesanan:\n\n1. baru\n2. proses\n3. selesai\n4. batal\n\nMasukkan status baru:');
    
    if (newStatus) {
        const validStatuses = ['baru', 'new', 'proses', 'process', 'selesai', 'done', 'batal', 'cancel'];
        
        if (validStatuses.includes(newStatus.toLowerCase())) {
            fetch('update_order_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `order_id=${orderId}&status=${newStatus}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Status pesanan berhasil diupdate!');
                    refreshOrders();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                alert('Error updating order status');
                console.error(error);
            });
        } else {
            alert('Status tidak valid!');
        }
    }
}

function deleteOrder(orderId) {
    if (confirm('Apakah Anda yakin ingin menghapus pesanan ini?')) {
        fetch('delete_order.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `order_id=${orderId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Pesanan berhasil dihapus!');
                refreshOrders();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            alert('Error deleting order');
            console.error(error);
        });
    }
}

function refreshOrders() {
    location.reload();
}
</script>

<?php mysqli_close($conn); ?>
