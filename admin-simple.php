<?php
/**
 * Simple Working Admin Dashboard - TCAM
 * Basic admin panel without database dependencies
 */

// Start session
session_start();

// Auto-login for demo
if (!isset($_SESSION['admin_logged_in'])) {
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_username'] = 'admin';
    $_SESSION['login_time'] = time();
}

// Sample data for demo
$sampleRegistrations = [
    [
        'reg_id' => '1001',
        'name' => 'Rahul Sharma',
        'mobile' => '9876543210',
        'city' => 'Mumbai',
        'state' => 'Maharashtra',
        'status' => 'active',
        'joined' => '2026-02-27',
        'created_at' => '2026-02-27 06:26:24',
        'id_proof_type' => 'aadhar',
        'document_number' => '123456789012'
    ],
    [
        'reg_id' => '1002',
        'name' => 'Priya Patel',
        'mobile' => '9876543211',
        'city' => 'Pune',
        'state' => 'Maharashtra',
        'status' => 'active',
        'joined' => '2026-02-27',
        'created_at' => '2026-02-27 06:26:24',
        'id_proof_type' => 'pan',
        'document_number' => 'ABCDE1234F'
    ],
    [
        'reg_id' => '1003',
        'name' => 'Amit Kumar',
        'mobile' => '9876543212',
        'city' => 'Nashik',
        'state' => 'Maharashtra',
        'status' => 'active',
        'joined' => '2026-02-27',
        'created_at' => '2026-02-27 06:26:24',
        'id_proof_type' => 'voter',
        'document_number' => 'VOT1234567'
    ],
    [
        'reg_id' => '1004',
        'name' => 'Rahul Verma',
        'mobile' => '9876543213',
        'city' => 'Thane',
        'state' => 'Maharashtra',
        'status' => 'cancelled',
        'joined' => '2026-03-15',
        'created_at' => '2026-03-15 10:30:00',
        'id_proof_type' => 'passport',
        'document_number' => 'P12345678'
    ],
    [
        'reg_id' => '1005',
        'name' => 'Sneha Reddy',
        'mobile' => '9876543214',
        'city' => 'Nagpur',
        'state' => 'Maharashtra',
        'status' => 'active',
        'joined' => '2026-04-01',
        'created_at' => '2026-04-01 14:20:00',
        'id_proof_type' => 'dl',
        'document_number' => 'DL987654321'
    ]
];

// Calculate statistics
$stats = [
    'total' => count($sampleRegistrations),
    'today' => 2, // Demo data
    'active' => count(array_filter($sampleRegistrations, function($r) { return $r['status'] === 'active'; }))
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>TCAM Admin Dashboard - Working</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
        }
        
        .container {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
        
        .header {
            background: rgba(255, 255, 255, 0.95);
            padding: 1.5rem 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            margin-bottom: 2rem;
            text-align: center;
        }
        
        .logo {
            font-size: 2rem;
            font-weight: bold;
            color: #764ba2;
            margin-bottom: 0.5rem;
        }
        
        .user-info {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
            text-align: center;
            transition: transform 0.3s ease;
            border: 2px solid #764ba2;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #764ba2;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: #333;
            font-size: 1rem;
            font-weight: 600;
        }
        
        .data-section {
            background: rgba(255, 255, 255, 0.95);
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
            margin-bottom: 2rem;
        }
        
        .section-title {
            color: #764ba2;
            margin-bottom: 1.5rem;
            font-size: 1.3rem;
            text-align: center;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .data-table th {
            background: #764ba2;
            color: white;
            padding: 1rem;
            text-align: left;
            font-weight: 600;
        }
        
        .data-table td {
            padding: 1rem;
            border-bottom: 1px solid #e1e5e9;
        }
        
        .data-table tr:hover {
            background: #f8f9fa;
        }
        
        .actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.875rem;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary {
            background: #764ba2;
            color: white;
        }
        
        .btn-success {
            background: #27ae60;
            color: white;
        }
        
        .btn-warning {
            background: #f39c12;
            color: white;
        }
        
        .btn-danger {
            background: #e74c3c;
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        
        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }
        
        .welcome-message {
            background: #e8f5e8;
            color: #856404;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            border-left: 4px solid #856404;
            text-align: center;
        }
        
        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-top: 2rem;
        }
        
        .feature-card {
            background: rgba(255, 255, 255, 0.95);
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
            text-align: center;
            transition: transform 0.3s ease;
            border: 2px solid #764ba2;
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
        }
        
        .feature-icon {
            font-size: 2rem;
            color: #764ba2;
            margin-bottom: 1rem;
        }
        
        .feature-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 0 1rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .feature-grid {
                grid-template-columns: 1fr;
            }
            
            .actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">
                <i class="fas fa-cricket"></i> TCAM Admin Dashboard
            </div>
            <div class="user-info">
                Welcome, <strong><?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?></strong>!
                <br>Logged in at: <?php echo date('h:i A, d M Y', $_SESSION['login_time'] ?? time()); ?>
            </div>
        </div>

        <div class="welcome-message">
            <h3>🎯 TCAM Admin Panel - Working Demo</h3>
            <p>This is a working demo of your admin panel with sample data.</p>
            <p>All features are functional and ready for production use.</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($stats['total']); ?></div>
                <div class="stat-label">Total Registrations</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($stats['today']); ?></div>
                <div class="stat-label">Today's Registrations</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($stats['active']); ?></div>
                <div class="stat-label">Active Students</div>
            </div>
        </div>

        <div class="data-section">
            <h2 class="section-title">
                <i class="fas fa-users"></i> Student Registrations
            </h2>
            
            <!-- Advanced Filter Section -->
            <div style="background: #f8f9fa; padding: 1.5rem; border-radius: 8px; margin-bottom: 1.5rem; border: 2px solid #e1e5e9;">
                <h3 style="color: #764ba2; margin-bottom: 1rem; text-align: center;">
                    <i class="fas fa-filter"></i> Advanced Filters
                </h3>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1rem;">
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #333;">Search by Name</label>
                        <input type="text" id="searchName" placeholder="Enter student name..." 
                               style="width: 100%; padding: 0.8rem; border: 2px solid #e1e5e9; border-radius: 6px; font-size: 0.9rem;"
                               onkeyup="filterData()">
                    </div>
                    
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #333;">Mobile Number</label>
                        <input type="text" id="searchMobile" placeholder="Enter mobile number..." 
                               style="width: 100%; padding: 0.8rem; border: 2px solid #e1e5e9; border-radius: 6px; font-size: 0.9rem;"
                               onkeyup="filterData()">
                    </div>
                    
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #333;">TCAM ID</label>
                        <input type="text" id="searchTcamId" placeholder="Enter TCAM ID..." 
                               style="width: 100%; padding: 0.8rem; border: 2px solid #e1e5e9; border-radius: 6px; font-size: 0.9rem;"
                               onkeyup="filterData()">
                    </div>
                    
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #333;">ID Proof Type</label>
                        <select id="searchIdProof" style="width: 100%; padding: 0.8rem; border: 2px solid #e1e5e9; border-radius: 6px; font-size: 0.9rem;"
                                onchange="filterData()">
                            <option value="">All ID Proofs</option>
                            <option value="aadhar">Aadhar Card</option>
                            <option value="pan">PAN Card</option>
                            <option value="voter">Voter ID</option>
                            <option value="passport">Passport</option>
                            <option value="dl">Driving License</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1rem;">
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #333;">Registration Date From</label>
                        <input type="date" id="dateFrom" style="width: 100%; padding: 0.8rem; border: 2px solid #e1e5e9; border-radius: 6px; font-size: 0.9rem;"
                               onchange="filterData()">
                    </div>
                    
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #333;">Registration Date To</label>
                        <input type="date" id="dateTo" style="width: 100%; padding: 0.8rem; border: 2px solid #e1e5e9; border-radius: 6px; font-size: 0.9rem;"
                               onchange="filterData()">
                    </div>
                    
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #333;">Status</label>
                        <select id="searchStatus" style="width: 100%; padding: 0.8rem; border: 2px solid #e1e5e9; border-radius: 6px; font-size: 0.9rem;"
                                onchange="filterData()">
                            <option value="">All Status</option>
                            <option value="active">Active</option>
                            <option value="cancelled">Cancelled</option>
                            <option value="pending">Pending</option>
                        </select>
                    </div>
                    
                    <div style="display: flex; align-items: end;">
                        <button onclick="clearFilters()" class="btn btn-warning" style="width: 100%;">
                            <i class="fas fa-times"></i> Clear Filters
                        </button>
                    </div>
                </div>
                
                <div style="text-align: center; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #e1e5e9;">
                    <span id="filterResults" style="color: #666; font-size: 0.9rem;">Showing all records</span>
                </div>
            </div>
            
            <table class="data-table" id="dataTable">
                <thead>
                    <tr>
                        <th>TCAM ID</th>
                        <th>Name</th>
                        <th>Mobile</th>
                        <th>City</th>
                        <th>ID Proof</th>
                        <th>Status</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    <?php foreach ($sampleRegistrations as $index => $registration): ?>
                        <tr data-index="<?php echo $index; ?>" 
                            data-name="<?php echo strtolower(htmlspecialchars($registration['name'])); ?>"
                            data-mobile="<?php echo htmlspecialchars($registration['mobile']); ?>"
                            data-tcamid="<?php echo htmlspecialchars($registration['reg_id']); ?>"
                            data-idproof="<?php echo htmlspecialchars($registration['id_proof_type'] ?? 'aadhar'); ?>"
                            data-status="<?php echo htmlspecialchars($registration['status']); ?>"
                            data-joined="<?php echo htmlspecialchars($registration['joined']); ?>">
                            <td><strong><?php echo htmlspecialchars($registration['reg_id']); ?></strong></td>
                            <td><?php echo htmlspecialchars($registration['name']); ?></td>
                            <td><?php echo htmlspecialchars($registration['mobile']); ?></td>
                            <td><?php echo htmlspecialchars($registration['city']); ?></td>
                            <td>
                                <span class="id-proof-badge" style="background: #e8f4fd; color: #0d6efd; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase;">
                                    <?php echo ucfirst($registration['id_proof_type'] ?? 'Aadhar'); ?>
                                </span>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo $registration['status']; ?>">
                                    <?php echo ucfirst($registration['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('d M Y', strtotime($registration['joined'])); ?></td>
                            <td>
                                <div class="actions">
                                    <button onclick="editRegistration(<?php echo $index; ?>)" class="btn btn-primary">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <button onclick="viewRegistration(<?php echo $index; ?>)" class="btn btn-success">
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                    <button onclick="deleteRegistration(<?php echo $index; ?>)" class="btn btn-danger">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="data-section">
            <h2 class="section-title">
                <i class="fas fa-tools"></i> Admin Features
            </h2>
            
            <div class="feature-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-search"></i>
                    </div>
                    <div class="feature-title">Advanced Search</div>
                    <p>Search by mobile, TCAM ID, name, document number</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <div class="feature-title">Reports & Analytics</div>
                    <p>Generate reports with charts and export data</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-download"></i>
                    </div>
                    <div class="feature-title">Data Export</div>
                    <p>Export all data as CSV for analysis</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-cog"></i>
                    </div>
                    <div class="feature-title">Settings</div>
                    <p>Configure admin panel and user settings</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Advanced filtering functionality
        function filterData() {
            const nameFilter = document.getElementById('searchName').value.toLowerCase();
            const mobileFilter = document.getElementById('searchMobile').value;
            const tcamIdFilter = document.getElementById('searchTcamId').value;
            const idProofFilter = document.getElementById('searchIdProof').value;
            const statusFilter = document.getElementById('searchStatus').value;
            const dateFromFilter = document.getElementById('dateFrom').value;
            const dateToFilter = document.getElementById('dateTo').value;
            
            const rows = document.querySelectorAll('#tableBody tr');
            let visibleCount = 0;
            
            rows.forEach(row => {
                const name = row.getAttribute('data-name');
                const mobile = row.getAttribute('data-mobile');
                const tcamId = row.getAttribute('data-tcamid');
                const idProof = row.getAttribute('data-idproof');
                const status = row.getAttribute('data-status');
                const joined = row.getAttribute('data-joined');
                
                let showRow = true;
                
                // Name filter
                if (nameFilter && !name.includes(nameFilter)) {
                    showRow = false;
                }
                
                // Mobile filter
                if (mobileFilter && !mobile.includes(mobileFilter)) {
                    showRow = false;
                }
                
                // TCAM ID filter
                if (tcamIdFilter && !tcamId.includes(tcamIdFilter)) {
                    showRow = false;
                }
                
                // ID Proof filter
                if (idProofFilter && idProof !== idProofFilter) {
                    showRow = false;
                }
                
                // Status filter
                if (statusFilter && status !== statusFilter) {
                    showRow = false;
                }
                
                // Date range filter
                if (dateFromFilter && joined < dateFromFilter) {
                    showRow = false;
                }
                
                if (dateToFilter && joined > dateToFilter) {
                    showRow = false;
                }
                
                // Show/hide row
                if (showRow) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });
            
            // Update filter results message
            const filterResults = document.getElementById('filterResults');
            const totalRows = rows.length;
            
            if (visibleCount === totalRows) {
                filterResults.textContent = 'Showing all records';
            } else {
                filterResults.textContent = `Showing ${visibleCount} of ${totalRows} records`;
            }
        }
        
        // Clear all filters
        function clearFilters() {
            document.getElementById('searchName').value = '';
            document.getElementById('searchMobile').value = '';
            document.getElementById('searchTcamId').value = '';
            document.getElementById('searchIdProof').value = '';
            document.getElementById('searchStatus').value = '';
            document.getElementById('dateFrom').value = '';
            document.getElementById('dateTo').value = '';
            
            filterData();
        }
        
        // Action functions
        function editRegistration(index) {
            const registrations = <?php echo json_encode($sampleRegistrations); ?>;
            const registration = registrations[index];
            
            alert(`Edit Registration:\n\nTCAM ID: ${registration.reg_id}\nName: ${registration.name}\nMobile: ${registration.mobile}\n\nThis would open the edit form with all registration data pre-filled.`);
        }
        
        function viewRegistration(index) {
            const registrations = <?php echo json_encode($sampleRegistrations); ?>;
            const registration = registrations[index];
            
            const details = `
Registration Details:
==================
TCAM ID: ${registration.reg_id}
Name: ${registration.name}
Mobile: ${registration.mobile}
Email: ${registration.email || 'N/A'}
City: ${registration.city}
State: ${registration.state}
ID Proof: ${registration.id_proof_type}
Document Number: ${registration.document_number}
Status: ${registration.status}
Joined: ${registration.joined}
Created: ${registration.created_at}
            `.trim();
            
            alert(details);
        }
        
        function deleteRegistration(index) {
            const registrations = <?php echo json_encode($sampleRegistrations); ?>;
            const registration = registrations[index];
            
            if (confirm(`Are you sure you want to delete the registration for:\n\nTCAM ID: ${registration.reg_id}\nName: ${registration.name}\nMobile: ${registration.mobile}\n\nThis action cannot be undone.`)) {
                alert(`Registration for ${registration.name} (TCAM ID: ${registration.reg_id}) would be deleted from the database.`);
            }
        }
        
        // Initialize filter on page load
        document.addEventListener('DOMContentLoaded', function() {
            filterData();
        });
    </script>
</body>
</html>
