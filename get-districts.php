<?php
require_once 'cors.php';
header('Content-Type: application/json; charset=UTF-8');
try {
    $db = new PDO('sqlite:' . __DIR__ . '/tcam_bookings.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // ensure districts table exists and seed if empty
    $db->exec("CREATE TABLE IF NOT EXISTS districts (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT UNIQUE NOT NULL)");
    $cnt = (int)$db->query("SELECT COUNT(*) FROM districts")->fetchColumn();
    if ($cnt === 0) {
        $districts = [
            'Ahmednagar','Akola','Amravati','Aurangabad','Beed','Bhandara','Buldhana','Chandrapur',
            'Dhule','Gadchiroli','Gondia','Hingoli','Jalgaon','Jalna','Kolhapur','Latur',
            'Mumbai City','Mumbai Suburban','Nagpur','Nanded','Nandurbar','Nashik','Osmanabad','Palghar',
            'Parbhani','Pune','Raigad','Ratnagiri','Sangli','Satara','Sindhudurg','Solapur',
            'Thane','Wardha','Washim','Yavatmal'
        ];
        $ins = $db->prepare('INSERT OR IGNORE INTO districts (name) VALUES (?)');
        foreach ($districts as $d) { $ins->execute([$d]); }
    }
    $rows = $db->query("SELECT id, name FROM districts ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['status'=>'success','data'=>$rows]);
} catch (Exception $e) {
    echo json_encode(['status'=>'error','message'=>'Unable to load districts']);
}
