<?php
// ============================================================
// ANNA ADARSH COLLEGE FOR WOMEN - BCA SHIFT II
// FEEDBACK PORTAL - feedback_system.php
// PHP handles: Save Feedback, Analytics, Export CSV/Excel/PDF,
//              Bar Chart data, Edit Feedback, Notifications
// HTML/CSS/JS handles: All UI, Login, Dashboard, Schedule etc.
// ============================================================

// ---------- PHP: SAVE FEEDBACK ----------
if (isset($_POST['action']) && $_POST['action'] === 'submit_feedback') {
    $teacher     = htmlspecialchars(trim($_POST['teacher']      ?? ''));
    $subject     = htmlspecialchars(trim($_POST['subject']      ?? ''));
    $q1          = htmlspecialchars(trim($_POST['q1']           ?? ''));
    $q2          = htmlspecialchars(trim($_POST['q2']           ?? ''));
    $q3          = htmlspecialchars(trim($_POST['q3']           ?? ''));
    $q4          = htmlspecialchars(trim($_POST['q4']           ?? ''));
    $q5          = htmlspecialchars(trim($_POST['q5']           ?? ''));
    $comments    = htmlspecialchars(trim($_POST['comments']     ?? ''));
    $suggestions = htmlspecialchars(trim($_POST['suggestions']  ?? ''));
    $student     = htmlspecialchars(trim($_POST['student_name'] ?? ''));
    $class       = htmlspecialchars(trim($_POST['student_class']?? ''));

    if ($teacher && $subject && $q1 && $q2 && $q3 && $q4 && $q5) {
        $rmap = ['Outstanding'=>10,'Excellent'=>8,'Very Good'=>6,'Good'=>4,'Satisfactory'=>2];
        $avg  = round(($rmap[$q1]+$rmap[$q2]+$rmap[$q3]+$rmap[$q4]+$rmap[$q5])/5, 1);
        $id   = 'FB-'.str_pad(rand(1000,9999),4,'0',STR_PAD_LEFT);
        $date = date('Y-m-d'); $time = date('h:i A');
        $row  = implode('||', [$id,$date,$time,$student,$class,$teacher,$subject,$q1,$q2,$q3,$q4,$q5,$avg,$comments,$suggestions])."\n";
        file_put_contents('feedbacks.txt', $row, FILE_APPEND | LOCK_EX);
        echo json_encode(['status'=>'success','msg'=>'Feedback saved successfully!','id'=>$id,'avg'=>$avg]);
    } else {
        echo json_encode(['status'=>'error','msg'=>'Please fill all required fields.']);
    }
    exit;
}

// ---------- PHP: EDIT FEEDBACK ----------
if (isset($_POST['action']) && $_POST['action'] === 'edit_feedback') {
    $eid  = htmlspecialchars(trim($_POST['edit_id'] ?? ''));
    $eq1  = htmlspecialchars(trim($_POST['eq1'] ?? ''));
    $eq2  = htmlspecialchars(trim($_POST['eq2'] ?? ''));
    $eq3  = htmlspecialchars(trim($_POST['eq3'] ?? ''));
    $eq4  = htmlspecialchars(trim($_POST['eq4'] ?? ''));
    $eq5  = htmlspecialchars(trim($_POST['eq5'] ?? ''));
    $ecmt = htmlspecialchars(trim($_POST['ecomments'] ?? ''));
    $esug = htmlspecialchars(trim($_POST['esuggestions'] ?? ''));
    $file = 'feedbacks.txt';
    $rmap = ['Outstanding'=>10,'Excellent'=>8,'Very Good'=>6,'Good'=>4,'Satisfactory'=>2];
    if (file_exists($file)) {
        $lines   = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $updated = [];
        foreach ($lines as $line) {
            $cols = explode('||', $line);
            if (isset($cols[0]) && $cols[0] === $eid) {
                $cols[7]  = $eq1; $cols[8]  = $eq2; $cols[9]  = $eq3;
                $cols[10] = $eq4; $cols[11] = $eq5;
                $cols[12] = round(($rmap[$eq1]+$rmap[$eq2]+$rmap[$eq3]+$rmap[$eq4]+$rmap[$eq5])/5, 1);
                $cols[13] = $ecmt; $cols[14] = $esug;
            }
            $updated[] = implode('||', $cols);
        }
        file_put_contents($file, implode("\n", $updated)."\n");
        echo json_encode(['status'=>'success','msg'=>'Feedback updated!']);
    } else { echo json_encode(['status'=>'error','msg'=>'No data found.']); }
    exit;
}

// ---------- PHP: SEND NOTIFICATION ----------
if (isset($_POST['action']) && $_POST['action'] === 'send_notification') {
    $title  = htmlspecialchars(trim($_POST['notif_title']   ?? ''));
    $msg    = htmlspecialchars(trim($_POST['notif_msg']     ?? ''));
    $target = htmlspecialchars(trim($_POST['notif_target']  ?? 'All Students'));
    if ($title && $msg) {
        $dt  = date('Y-m-d h:i A');
        file_put_contents('notifications.txt', "$dt||$target||$title||$msg\n", FILE_APPEND | LOCK_EX);
        echo json_encode(['status'=>'success','msg'=>"Notification sent to $target!",'dt'=>$dt]);
    } else { echo json_encode(['status'=>'error','msg'=>'Title and message required.']); }
    exit;
}

// ---------- PHP: EXPORT CSV ----------
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $tf = $_GET['tf'] ?? '';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="feedback_'.date('Ymd').'.csv"');
    echo "ID,Date,Time,Student,Class,Teacher,Subject,Q1-Explains Clearly,Q2-Approachable,Q3-Teaching Methods,Q4-Encourages Participation,Q5-Overall Satisfaction,Avg Rating,Comments,Suggestions\n";
    $file = 'feedbacks.txt';
    if (file_exists($file)) {
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $l) {
            $cols = explode('||', $l);
            if ($tf && isset($cols[5]) && $cols[5] !== $tf) continue;
            echo '"'.implode('","', $cols).'"'."\n";
        }
    } else {
        // Sample data export
        $samples = [
            ['FB-001','2026-02-10','09:15 AM','Sanjitha R','3B','Dr.D.Seethalakshmi','Advanced Networking','Outstanding','Excellent','Outstanding','Excellent','Outstanding','9.2','Excellent methodology','Continue'],
            ['FB-002','2026-02-10','10:30 AM','Shabreen.M','3B','Ms.M.Manju Priya','R Programming','Excellent','Excellent','Outstanding','Very Good','Excellent','8.8','Clear explanations','More practicals'],
        ];
        foreach ($samples as $row) echo '"'.implode('","', $row).'"'."\n";
    }
    exit;
}

// ---------- PHP: EXPORT EXCEL ----------
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    $tf = $_GET['tf'] ?? '';
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="feedback_'.date('Ymd').'.xls"');
    $th = ['ID','Date','Time','Student','Class','Teacher','Subject','Q1-Explains Clearly','Q2-Approachable','Q3-Teaching Methods','Q4-Encourages Participation','Q5-Overall Satisfaction','Avg Rating','Comments','Suggestions'];
    echo '<html><head><meta charset="utf-8"/><style>th{background:#1a3a5c;color:white;font-weight:bold;padding:6px;}td{padding:5px;border:1px solid #ccc;}tr:nth-child(even){background:#f0f4f9;}</style></head><body>';
    echo '<h3 style="font-family:Arial;">Anna Adarsh College - BCA Shift II | Feedback Report '.date('d-m-Y').'</h3>';
    echo '<table border="1"><thead><tr>';
    foreach ($th as $h) echo "<th>$h</th>";
    echo '</tr></thead><tbody>';
    $file = 'feedbacks.txt';
    if (file_exists($file)) {
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $l) {
            $cols = explode('||', $l);
            if ($tf && isset($cols[5]) && $cols[5] !== $tf) continue;
            echo '<tr>'; foreach ($cols as $c) echo '<td>'.htmlspecialchars($c).'</td>'; echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="15">No feedbacks found. Sample: FB-001 | 2026-02-10 | Dr.D.Seethalakshmi | 9.2/10</td></tr>';
    }
    echo '</tbody></table></body></html>';
    exit;
}

// ---------- PHP: EXPORT PDF (Print-ready HTML) ----------
if (isset($_GET['export']) && $_GET['export'] === 'pdf') {
    $file = 'feedbacks.txt'; $rows = [];
    if (file_exists($file)) {
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $l) $rows[] = explode('||', $l);
    } else {
        $rows = [
            ['FB-001','2026-02-10','09:15 AM','Sanjitha R','3B','Dr.D.Seethalakshmi','Advanced Networking','Outstanding','Excellent','Outstanding','Excellent','Outstanding','9.2','Excellent methodology','Continue'],
            ['FB-002','2026-02-10','10:30 AM','Shabreen.M','3B','Ms.M.Manju Priya','R Programming','Excellent','Excellent','Outstanding','Very Good','Excellent','8.8','Clear explanations','More practicals'],
            ['FB-003','2026-02-11','02:45 PM','Shalini.M','3B','Dr.P.Gunasundari','C++ Programming','Outstanding','Outstanding','Outstanding','Excellent','Outstanding','9.6','Best teacher','None'],
        ];
    }
    ?><!DOCTYPE html><html><head><title>Feedback Report</title>
    <style>
        *{margin:0;padding:0;box-sizing:border-box;}
        body{font-family:Arial,sans-serif;padding:20px;font-size:11px;}
        .hdr{text-align:center;border-bottom:3px solid #1a3a5c;padding-bottom:12px;margin-bottom:16px;}
        .hdr h2{color:#1a3a5c;font-size:18px;}
        .hdr p{color:#555;font-size:12px;margin-top:4px;}
        table{width:100%;border-collapse:collapse;margin-top:10px;}
        th{background:#1a3a5c;color:white;padding:7px 6px;text-align:left;font-size:11px;}
        td{padding:6px;border:1px solid #d0d0d0;vertical-align:top;}
        tr:nth-child(even) td{background:#f5f8fc;}
        .avg{font-weight:bold;color:#1a3a5c;}
        .footer{margin-top:20px;text-align:center;font-size:10px;color:#888;border-top:1px solid #ccc;padding-top:8px;}
        @media print{body{padding:10px;}.no-print{display:none;}}
    </style>
    </head><body>
    <div class="no-print" style="text-align:center;margin-bottom:15px;"><button onclick="window.print()" style="padding:10px 30px;background:#1a3a5c;color:white;border:none;border-radius:6px;font-size:15px;cursor:pointer;">🖨 Print / Save as PDF</button></div>
    <div class="hdr">
        <h2>ANNA ADARSH COLLEGE FOR WOMEN</h2>
        <p>Bachelor of Computer Applications — Shift II &nbsp;|&nbsp; Academic Feedback Report &nbsp;|&nbsp; Year 2025–2026</p>
        <p>Generated: <?php echo date('d-m-Y h:i A'); ?> &nbsp;|&nbsp; Total Records: <?php echo count($rows); ?></p>
    </div>
    <table>
        <thead><tr><th>ID</th><th>Date</th><th>Time</th><th>Teacher</th><th>Subject</th><th>Q1</th><th>Q2</th><th>Q3</th><th>Q4</th><th>Q5</th><th>Avg</th><th>Comments</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): $r = array_pad($r, 15, ''); ?>
        <tr>
            <td><?=htmlspecialchars($r[0])?></td><td><?=htmlspecialchars($r[1])?></td><td><?=htmlspecialchars($r[2])?></td>
            <td><?=htmlspecialchars($r[5])?></td><td><?=htmlspecialchars($r[6])?></td>
            <td><?=htmlspecialchars($r[7])?></td><td><?=htmlspecialchars($r[8])?></td>
            <td><?=htmlspecialchars($r[9])?></td><td><?=htmlspecialchars($r[10])?></td><td><?=htmlspecialchars($r[11])?></td>
            <td class="avg"><?=htmlspecialchars($r[12])?>/10</td><td><?=htmlspecialchars($r[13])?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <div class="footer">Anna Adarsh College for Women — BCA Shift II — Feedback Portal 2025-2026</div>
    </body></html>
    <?php exit;
}

// ---------- PHP: GET ANALYTICS DATA (JSON) ----------
if (isset($_GET['action']) && $_GET['action'] === 'get_analytics') {
    $file = 'feedbacks.txt';
    $rmap = ['Outstanding'=>10,'Excellent'=>8,'Very Good'=>6,'Good'=>4,'Satisfactory'=>2];
    $dist = ['Outstanding'=>0,'Excellent'=>0,'Very Good'=>0,'Good'=>0,'Satisfactory'=>0];
    $teacherData = []; $subjectData = []; $total = 0;
    if (file_exists($file)) {
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $l) {
            $c = explode('||', $l);
            if (count($c) < 13) continue;
            $total++;
            foreach ([$c[7],$c[8],$c[9],$c[10],$c[11]] as $r) if (isset($dist[$r])) $dist[$r]++;
            $tn = $c[5]; $avg = floatval($c[12]);
            if (!isset($teacherData[$tn])) $teacherData[$tn] = ['sum'=>0,'count'=>0];
            $teacherData[$tn]['sum'] += $avg; $teacherData[$tn]['count']++;
            $sn = $c[6];
            if (!isset($subjectData[$sn])) $subjectData[$sn] = ['sum'=>0,'count'=>0];
            $subjectData[$sn]['sum'] += $avg; $subjectData[$sn]['count']++;
        }
    }
    // Sample data if empty
    if ($total === 0) {
        $sampleTeachers = [
            'Dr.D.Seethalakshmi'=>['sum'=>27.6,'count'=>3],
            'M.Maheswari'=>['sum'=>14.4,'count'=>2],
            'Ms.M.Manju Priya'=>['sum'=>17.6,'count'=>2],
            'Ms.S.Deebalakshmi'=>['sum'=>17.6,'count'=>2],
            'Ms.C.Vanisri'=>['sum'=>16.6,'count'=>2],
            'Ms.S.Jayanthi'=>['sum'=>20.0,'count'=>2],
            'Dr.P.Gunasundari'=>['sum'=>19.2,'count'=>2],
            'Ms.M.Vijayarani'=>['sum'=>17.6,'count'=>2],
            'Ms.G.Aarthi'=>['sum'=>17.6,'count'=>2],
        ];
        $teacherData = $sampleTeachers;
        $dist = ['Outstanding'=>28,'Excellent'=>22,'Very Good'=>14,'Good'=>5,'Satisfactory'=>1];
        $total = 15;
        $subjectData = [
            'Advanced Networking'=>['sum'=>18.4,'count'=>2],
            'R Programming'=>['sum'=>17.6,'count'=>2],
            'C++ Programming'=>['sum'=>19.2,'count'=>2],
            'Java Programming'=>['sum'=>17.6,'count'=>2],
            'Statistics'=>['sum'=>14.4,'count'=>2],
            'Network Security'=>['sum'=>20.0,'count'=>2],
            'Data Mining and Warehousing'=>['sum'=>17.6,'count'=>2],
            'Emotional Intelligence'=>['sum'=>19.2,'count'=>2],
        ];
    }
    $teacherAvgs = [];
    foreach ($teacherData as $tn => $d) {
        $teacherAvgs[$tn] = round($d['sum']/$d['count'], 1);
    }
    $subjectAvgs = [];
    foreach ($subjectData as $sn => $d) {
        $subjectAvgs[$sn] = round($d['sum']/$d['count'], 1);
    }
    $overallAvg = $total > 0 ? round(array_sum($teacherAvgs)/count($teacherAvgs), 1) : 0;
    echo json_encode([
        'dist'       => $dist,
        'teachers'   => $teacherAvgs,
        'subjects'   => $subjectAvgs,
        'total'      => $total,
        'avg'        => $overallAvg,
    ]);
    exit;
}

// ---------- PHP: GET FEEDBACKS FOR ADMIN VIEW/EDIT (JSON) ----------
if (isset($_GET['action']) && $_GET['action'] === 'get_feedbacks') {
    $file = 'feedbacks.txt'; $rows = [];
    if (file_exists($file)) {
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $l) {
            $c = explode('||', $l);
            $c = array_pad($c, 15, '');
            $rows[] = ['id'=>$c[0],'date'=>$c[1],'time'=>$c[2],'student'=>$c[3],'class'=>$c[4],
                       'teacher'=>$c[5],'subject'=>$c[6],'q1'=>$c[7],'q2'=>$c[8],'q3'=>$c[9],
                       'q4'=>$c[10],'q5'=>$c[11],'avg'=>$c[12],'comments'=>$c[13],'suggestions'=>$c[14]];
        }
    }
    if (empty($rows)) {
        $rows = [
            ['id'=>'FB-001','date'=>'2026-02-10','time'=>'09:15 AM','student'=>'Sanjitha R','class'=>'3B','teacher'=>'Dr.D.Seethalakshmi','subject'=>'Advanced Networking','q1'=>'Outstanding','q2'=>'Excellent','q3'=>'Outstanding','q4'=>'Excellent','q5'=>'Outstanding','avg'=>'9.2','comments'=>'Excellent methodology','suggestions'=>'Continue'],
            ['id'=>'FB-002','date'=>'2026-02-10','time'=>'10:30 AM','student'=>'Shabreen.M','class'=>'3B','teacher'=>'Ms.M.Manju Priya','subject'=>'R Programming','q1'=>'Excellent','q2'=>'Excellent','q3'=>'Outstanding','q4'=>'Very Good','q5'=>'Excellent','avg'=>'8.8','comments'=>'Clear explanations','suggestions'=>'More practicals'],
            ['id'=>'FB-003','date'=>'2026-02-11','time'=>'02:45 PM','student'=>'Shalini.M','class'=>'3B','teacher'=>'Dr.P.Gunasundari','subject'=>'C++ Programming','q1'=>'Outstanding','q2'=>'Outstanding','q3'=>'Outstanding','q4'=>'Excellent','q5'=>'Outstanding','avg'=>'9.6','comments'=>'Best teacher','suggestions'=>'None'],
            ['id'=>'FB-004','date'=>'2026-02-11','time'=>'03:10 PM','student'=>'Aishwarya','class'=>'2A','teacher'=>'Ms.M.Vijayarani','subject'=>'Java Programming','q1'=>'Excellent','q2'=>'Excellent','q3'=>'Excellent','q4'=>'Very Good','q5'=>'Excellent','avg'=>'8.8','comments'=>'Good approach','suggestions'=>'More examples'],
            ['id'=>'FB-005','date'=>'2026-02-12','time'=>'11:00 AM','student'=>'Abinaya SJ','class'=>'1A','teacher'=>'M.Maheswari','subject'=>'Statistics','q1'=>'Very Good','q2'=>'Excellent','q3'=>'Excellent','q4'=>'Good','q5'=>'Very Good','avg'=>'7.2','comments'=>'Clear concepts','suggestions'=>'More problems'],
            ['id'=>'FB-006','date'=>'2026-02-12','time'=>'02:00 PM','student'=>'Priyadharshini.S','class'=>'1B','teacher'=>'Ms.S.Jayanthi','subject'=>'C++ Programming','q1'=>'Outstanding','q2'=>'Outstanding','q3'=>'Outstanding','q4'=>'Outstanding','q5'=>'Outstanding','avg'=>'10.0','comments'=>'Excellent!','suggestions'=>'Perfect'],
            ['id'=>'FB-007','date'=>'2026-02-13','time'=>'09:30 AM','student'=>'Shanmathi','class'=>'3B','teacher'=>'Ms.S.Deebalakshmi','subject'=>'Data Mining and Warehousing','q1'=>'Excellent','q2'=>'Very Good','q3'=>'Outstanding','q4'=>'Excellent','q5'=>'Excellent','avg'=>'8.8','comments'=>'Deep knowledge','suggestions'=>'Real examples'],
            ['id'=>'FB-008','date'=>'2026-02-13','time'=>'11:15 AM','student'=>'Amritha','class'=>'2A','teacher'=>'Ms.G.Aarthi','subject'=>'Advanced Networking','q1'=>'Excellent','q2'=>'Excellent','q3'=>'Excellent','q4'=>'Very Good','q5'=>'Excellent','avg'=>'8.8','comments'=>'Very helpful','suggestions'=>'None'],
        ];
    }
    echo json_encode($rows);
    exit;
}

// ---------- PHP: GET NOTIFICATIONS (JSON) ----------
if (isset($_GET['action']) && $_GET['action'] === 'get_notifications') {
    $file = 'notifications.txt'; $notifs = [];
    if (file_exists($file)) {
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $l) {
            $p = explode('||', $l, 4);
            if (count($p) === 4) $notifs[] = ['dt'=>$p[0],'target'=>$p[1],'title'=>$p[2],'msg'=>$p[3]];
        }
        $notifs = array_reverse($notifs);
    }
    $notifs = array_merge($notifs, [
        ['dt'=>'2026-02-10 09:00 AM','target'=>'All Students','title'=>'Reminder: Submit Feedback','msg'=>'Dear students, please submit teacher feedback before the deadline.'],
        ['dt'=>'2026-02-05 10:30 AM','target'=>'All Students','title'=>'Feedback Portal Open','msg'=>'The academic feedback portal for 2025-2026 is now open. Login with password: bca'],
    ]);
    echo json_encode($notifs);
    exit;
}

// ---------- Load feedback count for admin dashboard ----------
$fbFile     = 'feedbacks.txt';
$totalFBs   = 0; $overallAvgPHP = 0;
if (file_exists($fbFile)) {
    $lines = file($fbFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $totalFBs = count($lines);
    $sum = 0;
    foreach ($lines as $l) { $c = explode('||', $l); if (isset($c[12])) $sum += floatval($c[12]); }
    $overallAvgPHP = $totalFBs > 0 ? round($sum/$totalFBs, 1) : 8.6;
} else { $totalFBs = 15; $overallAvgPHP = 8.6; } // sample display
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BCA Shift II — Academic Feedback Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Outfit:wght@600;700;800&display=swap" rel="stylesheet">
    <style>
        /* ===== RESET & ROOT ===== */
        *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }
        :root {
            --navy:     #0f2744;
            --blue:     #1a4a8a;
            --blue2:    #2563a8;
            --gold:     #c9961a;
            --goldlt:   #f0c040;
            --bg:       #eef2f8;
            --white:    #ffffff;
            --text:     #1a2636;
            --muted:    #5a6a7e;
            --border:   #c8d5e8;
            --sidebar:  #0a1d35;
            --good:     #14652a;
            --goodbg:   #d1f0dc;
            --bad:      #8b1a1a;
            --badbg:    #fde8e8;
        }
        body { font-family:'Inter',sans-serif; background:var(--bg); color:var(--text); min-height:100vh; }

        /* ===== HEADER ===== */
        .site-header {
            background: linear-gradient(180deg, var(--navy) 0%, var(--blue) 100%);
            color: white; text-align: center; padding: 30px 20px 24px;
            border-bottom: 4px solid var(--gold);
        }
        .site-header h1 { font-family:'Outfit',sans-serif; font-size: 2.2em; font-weight: 800; letter-spacing: 0.3px; }
        .site-header .autonomous { font-size: 0.85em; font-weight: 500; letter-spacing: 3px; color: var(--goldlt); margin: 6px 0 4px; }
        .site-header .dept       { font-size: 1.1em; font-weight: 700; margin: 6px 0 2px; }
        .site-header .portal     { font-size: 0.9em; font-weight: 400; opacity: 0.82; margin: 3px 0; }
        .site-header .year       { font-size: 0.95em; font-weight: 600; color: var(--goldlt); margin: 2px 0; letter-spacing: 1px; }

        /* ===== PAGES ===== */
        .page { display:none; }
        .page.active { display:block; animation: fadeIn 0.35s ease; }
        @keyframes fadeIn { from { opacity:0; transform:translateY(12px); } to { opacity:1; transform:translateY(0); } }

        /* ===== LOGIN ===== */
        .login-outer { max-width: 480px; margin: 44px auto; padding: 0 20px 50px; }
        .login-card  { background: white; border-radius: 14px; padding: 36px 32px; box-shadow: 0 6px 30px rgba(15,39,68,0.13); border: 1px solid var(--border); }
        .tab-row     { display:flex; gap:10px; margin-bottom:28px; }
        .tab-btn     { flex:1; padding:12px; background:#eef2f8; border:2px solid transparent; border-radius:9px; cursor:pointer; font-size:14px; font-weight:700; font-family:'Inter',sans-serif; color:var(--muted); transition:all 0.2s; }
        .tab-btn.active { background:var(--navy); color:white; border-color:var(--blue2); }

        /* ===== FORMS ===== */
        .fg { margin-bottom: 18px; }
        .fg label  { display:block; margin-bottom:7px; font-weight:600; font-size:13px; color:var(--text); }
        .fg input, .fg select, .fg textarea {
            width:100%; padding:11px 14px; border:2px solid var(--border); border-radius:8px;
            font-size:14px; font-family:'Inter',sans-serif; color:var(--text); background:white; transition:border 0.2s;
        }
        .fg input:focus, .fg select:focus, .fg textarea:focus { outline:none; border-color:var(--blue2); }
        .fg textarea { resize:vertical; min-height:90px; }
        .btn-main { width:100%; padding:13px; background:var(--navy); color:white; border:none; border-radius:9px; font-size:15px; font-weight:700; cursor:pointer; font-family:'Inter',sans-serif; transition:background 0.2s; }
        .btn-main:hover { background:var(--blue); }
        .btn-sm { padding:6px 14px; border:none; border-radius:6px; cursor:pointer; font-weight:600; font-size:12px; font-family:'Inter',sans-serif; transition:all 0.2s; }
        .btn-sm:hover { opacity:0.85; }

        /* ===== DASHBOARD ===== */
        .dash { display:grid; grid-template-columns:240px 1fr; min-height:calc(100vh - 160px); }

        /* sidebar */
        .sidebar { background:var(--sidebar); padding:24px 14px; }
        .sb-title { color:var(--goldlt); font-size:10px; font-weight:700; letter-spacing:2px; text-transform:uppercase; margin-bottom:14px; padding: 0 8px; }
        .mi { padding:11px 14px; margin-bottom:3px; border-radius:8px; cursor:pointer; font-size:13px; font-weight:500; color:#8fa0b5; transition:all 0.18s; display:flex; align-items:center; gap:9px; white-space:nowrap; }
        .mi:hover  { background:#132540; color:#d0dce8; }
        .mi.active { background:var(--blue2); color:white; font-weight:700; }
        .sb-logout { width:100%; margin-top:22px; padding:11px; background:#7a1a1a; color:white; border:none; border-radius:8px; cursor:pointer; font-weight:700; font-family:'Inter',sans-serif; font-size:13px; transition:background 0.2s; }
        .sb-logout:hover { background:#9b2020; }

        /* content area */
        .content { padding:32px; background:var(--bg); }
        .mod { display:none; }
        .mod.active { display:block; animation:fadeIn 0.35s; }
        .mod-hdr { margin-bottom:22px; }
        .mod-hdr h2 { font-family:'Outfit',sans-serif; font-size:1.55em; font-weight:700; color:var(--navy); margin-bottom:3px; }
        .mod-hdr p  { color:var(--muted); font-size:13px; }
        .mod-hdr hr { margin-top:10px; border:none; border-top:3px solid var(--gold); width:50px; }

        /* stat cards */
        .stats { display:grid; grid-template-columns:repeat(auto-fit,minmax(175px,1fr)); gap:16px; margin-bottom:24px; }
        .sc { background:white; padding:22px 20px; border-radius:12px; box-shadow:0 2px 10px rgba(15,39,68,0.08); border-top:4px solid var(--blue2); }
        .sc h3     { color:var(--muted); font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:8px; }
        .sc .num   { font-family:'Outfit',sans-serif; font-size:2.1em; font-weight:800; color:var(--navy); }
        .sc:nth-child(4n+1) { border-top-color:var(--blue2); }
        .sc:nth-child(4n+2) { border-top-color:#1a6e3e; }
        .sc:nth-child(4n+3) { border-top-color:var(--gold); }
        .sc:nth-child(4n+4) { border-top-color:#7a3a1a; }

        /* box */
        .box { background:white; padding:24px; border-radius:12px; box-shadow:0 2px 10px rgba(15,39,68,0.08); margin-bottom:20px; border:1px solid var(--border); }
        .box h3 { font-size:14px; font-weight:700; color:var(--navy); margin-bottom:16px; padding-bottom:10px; border-bottom:1px solid var(--bg); }

        /* alerts */
        .alert { padding:12px 16px; border-radius:8px; margin-bottom:16px; font-weight:600; font-size:13px; }
        .ok  { background:var(--goodbg); color:var(--good); border-left:4px solid var(--good); }
        .err { background:var(--badbg);  color:var(--bad);  border-left:4px solid var(--bad); }

        /* tables */
        .twrap { overflow-x:auto; border-radius:12px; border:1px solid var(--border); }
        table { width:100%; border-collapse:collapse; background:white; }
        table thead { background:var(--navy); color:white; }
        table th { padding:12px 14px; text-align:left; font-size:12px; font-weight:600; white-space:nowrap; }
        table td { padding:11px 14px; font-size:13px; border-bottom:1px solid var(--border); }
        table tbody tr:last-child td { border-bottom:none; }
        table tbody tr:hover { background:#f5f8fc; }

        /* feedback form */
        .fb-box { background:white; padding:28px; border-radius:12px; box-shadow:0 2px 10px rgba(15,39,68,0.08); border:1px solid var(--border); }
        .qg { margin-bottom:24px; padding-bottom:20px; border-bottom:1px solid var(--bg); }
        .qg:last-of-type { border-bottom:none; }
        .qlabel { font-weight:700; font-size:14px; color:var(--navy); display:block; margin-bottom:12px; }
        .radio-row { display:flex; flex-wrap:wrap; gap:8px; }
        .rchip { display:flex; align-items:center; gap:7px; background:#f0f4f9; padding:9px 16px; border-radius:7px; cursor:pointer; border:2px solid transparent; transition:all 0.15s; }
        .rchip:hover { background:#dde8f5; }
        .rchip input[type="radio"] { accent-color:var(--blue2); width:15px; height:15px; }
        .rchip input[type="radio"]:checked + span { color:var(--blue2); font-weight:700; }
        .rchip:has(input:checked) { background:#ddeaf8; border-color:var(--blue2); }
        .rchip span { font-size:13px; font-weight:500; }

        /* timetable */
        .ct-row   { display:flex; flex-wrap:wrap; gap:8px; margin-bottom:16px; }
        .ct-btn   { padding:8px 16px; background:#eef2f8; border:2px solid var(--border); border-radius:7px; cursor:pointer; font-family:'Inter',sans-serif; font-weight:600; font-size:13px; color:var(--muted); transition:all 0.2s; }
        .ct-btn.active { background:var(--navy); color:white; border-color:var(--navy); }
        .ttwrap { display:none; overflow-x:auto; }
        .ttwrap.active { display:block; animation:fadeIn 0.3s; }
        .tt-label { background:#eef2f8; padding:9px 16px; border-radius:7px; border-left:4px solid var(--blue2); font-weight:700; font-size:13px; color:var(--navy); margin-bottom:12px; }
        .tt { width:100%; border-collapse:collapse; min-width:720px; font-size:12px; }
        .tt th { background:var(--navy); color:white; padding:10px 8px; text-align:center; border:1px solid var(--blue); font-weight:600; }
        .tt th.dh { background:var(--gold); color:var(--navy); font-weight:800; }
        .tt td { padding:8px 7px; text-align:center; border:1px solid var(--border); vertical-align:middle; line-height:1.4; }
        .tt td small { display:block; color:var(--muted); font-size:10.5px; margin-top:2px; }
        .tt tbody tr:nth-child(odd)  td { background:#fafbfd; }
        .tt tbody tr:nth-child(even) td { background:#f2f6fb; }
        .tt td.do  { background:#000000; color:#ffffff; font-weight:700; font-size:12px; }
        .tt td.lab { background:#fff8e0; color:#6b4800; font-style:italic; font-size:11px; }
        .tt td.emp { background:#f0f0f0; color:#bbb; }

        /* bar chart - RATINGS ONLY, no color fill */
        .brow { display:grid; grid-template-columns:200px 1fr 55px; gap:12px; align-items:center; margin-bottom:11px; }
        .blabel { font-size:12.5px; font-weight:600; color:var(--text); }
        .bbg   { background:#e8eef6; border-radius:5px; height:26px; overflow:hidden; }
        .bfill { height:100%; border-radius:5px; display:flex; align-items:center; justify-content:flex-end; padding-right:9px; color:white; font-weight:700; font-size:12px; }
        .bval  { font-weight:700; font-size:13px; color:var(--navy); text-align:right; }

        /* notif item */
        .ni { padding:13px 16px; background:#f5f8fc; border-left:4px solid var(--blue2); border-radius:8px; margin-bottom:10px; }
        .ni strong { color:var(--navy); font-size:13px; }
        .ni p  { font-size:13px; color:var(--text); margin:4px 0; }
        .ni small { color:var(--muted); font-size:11px; }

        /* export buttons */
        .ex-row { display:flex; gap:10px; flex-wrap:wrap; margin-top:14px; }
        .ex-btn { padding:12px 20px; border:none; border-radius:8px; color:white; font-weight:700; font-size:13px; font-family:'Inter',sans-serif; cursor:pointer; text-decoration:none; display:inline-block; transition:opacity 0.2s; }
        .ex-btn:hover { opacity:0.85; }

        /* modal */
        .modal-bg { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:999; overflow-y:auto; }
        .modal-box { background:white; max-width:520px; margin:50px auto; border-radius:14px; padding:32px; box-shadow:0 20px 60px rgba(0,0,0,0.25); }
        .modal-box h3 { color:var(--navy); margin-bottom:18px; font-size:1.05em; font-weight:700; }
        .modal-foot { display:flex; gap:10px; margin-top:20px; }

        /* loading spinner */
        .spin { display:inline-block; width:16px; height:16px; border:2px solid #ccc; border-top-color:var(--blue2); border-radius:50%; animation:spin 0.7s linear infinite; vertical-align:middle; margin-right:6px; }
        @keyframes spin { to { transform:rotate(360deg); } }

        @media(max-width:900px) {
            .dash { grid-template-columns:1fr; }
            .sidebar { position:static; }
            .site-header h1 { font-size:1.6em; }
        }
    </style>
</head>
<body>

<!-- ====== HEADER ====== -->
<div class="site-header">
    <h1>ANNA ADARSH COLLEGE FOR WOMEN</h1>
    <div class="autonomous">AUTONOMOUS</div>
    <div class="dept">Bachelor of Computer Applications — Shift II</div>
    <div class="portal">Academic Feedback Portal</div>
    <div class="year">Year 2025 – 2026</div>
</div>

<!-- ============================== LOGIN ============================== -->
<div id="pgLogin" class="page active">
    <div class="login-outer">
        <div class="login-card">
            <div class="tab-row">
                <button class="tab-btn active" onclick="switchTab('stu')">🎓 Student Login</button>
                <button class="tab-btn" onclick="switchTab('adm')">🔐 Admin Login</button>
            </div>

            <!-- Student -->
            <div id="stuLogin">
                <div class="fg">
                    <label>Year & Section</label>
                    <select id="stuClass" onchange="loadNames()">
                        <option value="">— Select Class —</option>
                        <option value="1A">1st Year BCA — A</option>
                        <option value="1B">1st Year BCA — B</option>
                        <option value="2A">2nd Year BCA — A</option>
                        <option value="2B">2nd Year BCA — B</option>
                        <option value="3A">3rd Year BCA — A</option>
                        <option value="3B">3rd Year BCA — B</option>
                    </select>
                </div>
                <div class="fg">
                    <label>Student Name</label>
                    <select id="stuName"><option>Select class first</option></select>
                </div>
                <div class="fg">
                    <label>Password</label>
                    <input type="password" id="stuPwd" placeholder="Enter password (bca)">
                </div>
                <div id="stuMsg"></div>
                <button class="btn-main" onclick="stuLogin()">Login as Student</button>
            </div>

            <!-- Admin -->
            <div id="admLogin" style="display:none;">
                <div class="fg">
                    <label>Username</label>
                    <input type="text" id="admUser" placeholder="Admin username">
                </div>
                <div class="fg">
                    <label>Password</label>
                    <input type="password" id="admPwd" placeholder="Password">
                </div>
                <div id="admMsg"></div>
                <button class="btn-main" onclick="admLogin()">Login as Admin</button>
            </div>
        </div>
    </div>
</div>

<!-- ============================== STUDENT DASHBOARD ============================== -->
<div id="pgStu" class="page">
    <div class="dash">
        <div class="sidebar">
            <div class="sb-title">Student Portal</div>
            <div class="mi active" onclick="sw('sOv',this)"><span>📊</span>Overview</div>
            <div class="mi" onclick="sw('sGF',this)"><span>✍️</span>Give Feedback</div>
            <div class="mi" onclick="sw('sMF',this)"><span>📝</span>My Feedback</div>
            <div class="mi" onclick="sw('sTP',this)"><span>👩‍🏫</span>Teacher Profiles</div>
            <div class="mi" onclick="sw('sSC',this)"><span>📅</span>Class Schedule</div>
            <button class="sb-logout" onclick="logout()">↩ Logout</button>
        </div>
        <div class="content">

            <!-- Overview -->
            <div id="sOv" class="mod active">
                <div class="mod-hdr"><h2>Welcome, <span id="stuNameHdr"></span>!</h2><p>Your feedback dashboard</p><hr></div>
                <div class="stats">
                    <div class="sc"><h3>Feedbacks Given</h3><div class="num" id="stuFBNum">0</div></div>
                    <div class="sc"><h3>Pending</h3><div class="num">0</div></div>
                    <div class="sc"><h3>Completion</h3><div class="num">100%</div></div>
                </div>
                <div class="box">
                    <h3>How to Submit Feedback</h3>
                    <p style="font-size:13px;color:var(--muted);line-height:1.9;">
                        1. Click <strong>Give Feedback</strong> from the menu.<br>
                        2. Select your teacher and subject.<br>
                        3. Answer all 5 questions honestly.<br>
                        4. Your feedback is <strong>anonymous</strong> and helps improve teaching quality.
                    </p>
                </div>
            </div>

            <!-- Give Feedback -->
            <div id="sGF" class="mod">
                <div class="mod-hdr"><h2>✍️ Give Feedback</h2><p>Rate your teacher — your opinion matters</p><hr></div>
                <div class="fb-box">
                    <div id="fbMsg"></div>
                    <div class="fg">
                        <label>Select Teacher *</label>
                        <select id="fbTeacher">
                            <option value="">— Choose Teacher —</option>
                            <option>Dr.D.Seethalakshmi</option><option>M.Maheswari</option>
                            <option>Ms.M.Manju Priya</option><option>Ms.S.Deebalakshmi</option>
                            <option>Ms.C.Vanisri</option><option>Ms.S.Jayanthi</option>
                            <option>Dr.P.Gunasundari</option><option>Ms.M.Vijayarani</option>
                            <option>Ms.G.Aarthi</option><option>Priya</option>
                            <option>Kurinji</option><option>Rakshitha</option>
                            <option>Tamil Faculty</option><option>English Faculty</option>
                            <option>Accounts Faculty</option>
                        </select>
                    </div>
                    <div class="fg">
                        <label>Select Subject *</label>
                        <select id="fbSubject">
                            <option value="">— Choose Subject —</option>
                            <option>Tamil</option><option>English</option><option>Statistics</option>
                            <option>C++ Programming (OOP)</option><option>C++ Practical</option>
                            <option>Java Programming</option><option>Java Practical</option>
                            <option>R Programming</option><option>Data Mining and Warehousing</option>
                            <option>Network Security</option><option>Advanced Networking</option>
                            <option>Emotional Intelligence / Managing Emotions</option>
                            <option>Cost and Management Accounting</option>
                            <option>SEC - Quantitative Aptitude</option>
                            <option>SEC - Technical Writing</option>
                            <option>Environmental Study (EVS)</option>
                        </select>
                    </div>

                    <!-- Q1 -->
                    <div class="qg">
                        <span class="qlabel">1. How clearly does the professor explain the subject concepts?</span>
                        <div class="radio-row">
                            <label class="rchip"><input type="radio" name="rq1" value="Outstanding"><span>Outstanding</span></label>
                            <label class="rchip"><input type="radio" name="rq1" value="Excellent"><span>Excellent</span></label>
                            <label class="rchip"><input type="radio" name="rq1" value="Very Good"><span>Very Good</span></label>
                            <label class="rchip"><input type="radio" name="rq1" value="Good"><span>Good</span></label>
                            <label class="rchip"><input type="radio" name="rq1" value="Satisfactory"><span>Satisfactory</span></label>
                        </div>
                    </div>
                    <!-- Q2 -->
                    <div class="qg">
                        <span class="qlabel">2. Is the professor approachable and willing to help students?</span>
                        <div class="radio-row">
                            <label class="rchip"><input type="radio" name="rq2" value="Outstanding"><span>Outstanding</span></label>
                            <label class="rchip"><input type="radio" name="rq2" value="Excellent"><span>Excellent</span></label>
                            <label class="rchip"><input type="radio" name="rq2" value="Very Good"><span>Very Good</span></label>
                            <label class="rchip"><input type="radio" name="rq2" value="Good"><span>Good</span></label>
                            <label class="rchip"><input type="radio" name="rq2" value="Satisfactory"><span>Satisfactory</span></label>
                        </div>
                    </div>
                    <!-- Q3 -->
                    <div class="qg">
                        <span class="qlabel">3. How effective are the teaching methods used in class?</span>
                        <div class="radio-row">
                            <label class="rchip"><input type="radio" name="rq3" value="Outstanding"><span>Outstanding</span></label>
                            <label class="rchip"><input type="radio" name="rq3" value="Excellent"><span>Excellent</span></label>
                            <label class="rchip"><input type="radio" name="rq3" value="Very Good"><span>Very Good</span></label>
                            <label class="rchip"><input type="radio" name="rq3" value="Good"><span>Good</span></label>
                            <label class="rchip"><input type="radio" name="rq3" value="Satisfactory"><span>Satisfactory</span></label>
                        </div>
                    </div>
                    <!-- Q4 -->
                    <div class="qg">
                        <span class="qlabel">4. Does the professor encourage participation and questions?</span>
                        <div class="radio-row">
                            <label class="rchip"><input type="radio" name="rq4" value="Outstanding"><span>Outstanding</span></label>
                            <label class="rchip"><input type="radio" name="rq4" value="Excellent"><span>Excellent</span></label>
                            <label class="rchip"><input type="radio" name="rq4" value="Very Good"><span>Very Good</span></label>
                            <label class="rchip"><input type="radio" name="rq4" value="Good"><span>Good</span></label>
                            <label class="rchip"><input type="radio" name="rq4" value="Satisfactory"><span>Satisfactory</span></label>
                        </div>
                    </div>
                    <!-- Q5 -->
                    <div class="qg">
                        <span class="qlabel">5. Overall, how satisfied are you with this professor's teaching?</span>
                        <div class="radio-row">
                            <label class="rchip"><input type="radio" name="rq5" value="Outstanding"><span>Outstanding</span></label>
                            <label class="rchip"><input type="radio" name="rq5" value="Excellent"><span>Excellent</span></label>
                            <label class="rchip"><input type="radio" name="rq5" value="Very Good"><span>Very Good</span></label>
                            <label class="rchip"><input type="radio" name="rq5" value="Good"><span>Good</span></label>
                            <label class="rchip"><input type="radio" name="rq5" value="Satisfactory"><span>Satisfactory</span></label>
                        </div>
                    </div>

                    <div class="fg"><label>Comments (Optional)</label><textarea id="fbCmt" placeholder="Your detailed feedback..."></textarea></div>
                    <div class="fg"><label>Suggestions (Optional)</label><textarea id="fbSug" placeholder="Your suggestions..."></textarea></div>
                    <button class="btn-main" onclick="submitFB()">Submit Feedback →</button>
                </div>
            </div>

            <!-- My Feedback -->
            <div id="sMF" class="mod">
                <div class="mod-hdr"><h2>📝 My Feedback History</h2><p>All feedbacks submitted by you</p><hr></div>
                <div class="twrap">
                    <table><thead><tr><th>Date</th><th>Teacher</th><th>Subject</th><th>Avg Rating</th><th>Status</th></tr></thead>
                    <tbody id="myFBTbl"><tr><td colspan="5" style="text-align:center;padding:28px;color:var(--muted);">No feedback submitted yet</td></tr></tbody>
                    </table>
                </div>
            </div>

            <!-- Teacher Profiles -->
            <div id="sTP" class="mod">
                <div class="mod-hdr"><h2>👩‍🏫 Teacher Profiles</h2><p>BCA Shift II Faculty</p><hr></div>
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:16px;">
                    <div class="box" style="margin-bottom:0;"><h3>Dr.D.Seethalakshmi</h3><p style="font-size:13px;line-height:1.7;"><strong>Qualification:</strong> M.Sc., MCA., M.Phil., Ph.D., NET, SET<br><strong>Position:</strong> Asst. Prof. & Head Incharge<br><strong>Subjects:</strong> Advanced Networking, Emotional Intelligence</p></div>
                    <div class="box" style="margin-bottom:0;"><h3>M.Maheswari</h3><p style="font-size:13px;line-height:1.7;"><strong>Qualification:</strong> M.Sc., M.Phil.<br><strong>Position:</strong> Assistant Professor<br><strong>Subjects:</strong> Statistics, SEC Quantitative Aptitude</p></div>
                    <div class="box" style="margin-bottom:0;"><h3>Ms.M.Manju Priya</h3><p style="font-size:13px;line-height:1.7;"><strong>Qualification:</strong> M.Sc., M.Phil., SET<br><strong>Position:</strong> Assistant Professor<br><strong>Subjects:</strong> R Programming</p></div>
                    <div class="box" style="margin-bottom:0;"><h3>Ms.S.Deebalakshmi</h3><p style="font-size:13px;line-height:1.7;"><strong>Qualification:</strong> MCA., HDSE., NET-JRF<br><strong>Position:</strong> Assistant Professor<br><strong>Subjects:</strong> Data Mining and Warehousing</p></div>
                    <div class="box" style="margin-bottom:0;"><h3>Ms.C.Vanisri</h3><p style="font-size:13px;line-height:1.7;"><strong>Position:</strong> Assistant Professor<br><strong>Subjects:</strong> Java Programming (II BCA A), Network Security, EVS</p></div>
                    <div class="box" style="margin-bottom:0;"><h3>Ms.S.Jayanthi</h3><p style="font-size:13px;line-height:1.7;"><strong>Position:</strong> Assistant Professor<br><strong>Subjects:</strong> C++ Programming (I BCA A), Network Security, SEC Technical Writing</p></div>
                    <div class="box" style="margin-bottom:0;"><h3>Dr.P.Gunasundari</h3><p style="font-size:13px;line-height:1.7;"><strong>Qualification:</strong> MCA., M.Phil., Ph.D., NET<br><strong>Position:</strong> Assistant Professor<br><strong>Subjects:</strong> C++ Programming (I BCA B), C++ Practical</p></div>
                    <div class="box" style="margin-bottom:0;"><h3>Ms.M.Vijayarani</h3><p style="font-size:13px;line-height:1.7;"><strong>Qualification:</strong> MCA., M.Phil.<br><strong>Position:</strong> Assistant Professor<br><strong>Subjects:</strong> Java Programming (II BCA B), Java Practical</p></div>
                    <div class="box" style="margin-bottom:0;"><h3>Ms.G.Aarthi</h3><p style="font-size:13px;line-height:1.7;"><strong>Position:</strong> Assistant Professor<br><strong>Subjects:</strong> Advanced Networking (III BCA), Managing Emotions (II BCA A)</p></div>
                </div>
            </div>

            <!-- Class Schedule -->
            <div id="sSC" class="mod">
                <div class="mod-hdr"><h2>📅 Class Schedule</h2><p>Monday – Saturday &nbsp;|&nbsp; Day Order I – V (Rows) &nbsp;|&nbsp; All Classes</p><hr></div>
                <div class="box">
                    <div class="ct-row">
                        <button class="ct-btn active" onclick="showTT('tt1a',this)">I BCA – A</button>
                        <button class="ct-btn" onclick="showTT('tt1b',this)">I BCA – B</button>
                        <button class="ct-btn" onclick="showTT('tt2a',this)">II BCA – A</button>
                        <button class="ct-btn" onclick="showTT('tt2b',this)">II BCA – B</button>
                        <button class="ct-btn" onclick="showTT('tt3a',this)">III BCA – A</button>
                        <button class="ct-btn" onclick="showTT('tt3b',this)">III BCA – B</button>
                    </div>

                    <!-- I BCA A -->
                    <div id="tt1a" class="ttwrap active">
                        <div class="tt-label">CLASS : I BCA 'A' SECTION</div>
                        <table class="tt">
                            <thead><tr><th class="dh">Day / Hour</th><th>Monday</th><th>Tuesday</th><th>Wednesday</th><th>Thursday</th><th>Friday</th><th>Saturday</th></tr></thead>
                            <tbody>
                            <tr><td class="do">Day Order I</td><td>English</td><td>Statistics<small>Ms.M.Maheswari</small></td><td>HM</td><td>SEC/BT/AT<small>Dr.D.Seethalakshmi</small></td><td>OOP with C++<small>Ms.S.Jayanthi</small></td><td>Language</td></tr>
                            <tr><td class="do">Day Order II</td><td>English</td><td>OOP with C++<small>Ms.S.Jayanthi</small></td><td>HM</td><td>Language</td><td>Statistics<small>Ms.M.Maheswari</small></td><td>English</td></tr>
                            <tr><td class="do">Day Order III</td><td class="lab" colspan="2">← C++ Lab → (Ms.S.Jayanthi)</td><td>PEd</td><td>Statistics<small>Ms.M.Maheswari</small></td><td>SEC/BT/AT<small>Dr.D.Seethalakshmi</small></td><td class="lab">← C++ Lab →</td></tr>
                            <tr><td class="do">Day Order IV</td><td>Statistics<small>Ms.M.Maheswari</small></td><td>SEC - Quant. Aptitude<small>Ms.M.Maheswari</small></td><td>Language</td><td>English</td><td>OOP with C++<small>Ms.S.Jayanthi</small></td><td class="emp">—</td></tr>
                            <tr><td class="do">Day Order V</td><td>Statistics<small>Ms.M.Maheswari</small></td><td>OOP with C++<small>Ms.S.Jayanthi</small></td><td>English</td><td>Language</td><td>SEC - Quant. Aptitude<small>Ms.M.Maheswari</small></td><td class="emp">—</td></tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- I BCA B -->
                    <div id="tt1b" class="ttwrap">
                        <div class="tt-label">CLASS : I BCA 'B' SECTION</div>
                        <table class="tt">
                            <thead><tr><th class="dh">Day / Hour</th><th>Monday</th><th>Tuesday</th><th>Wednesday</th><th>Thursday</th><th>Friday</th><th>Saturday</th></tr></thead>
                            <tbody>
                            <tr><td class="do">Day Order I</td><td>English</td><td class="lab" colspan="2">← C++ Lab → (Dr.P.Gunasundari)</td><td>SEC/BT/AT<small>Ms.M.Manju Priya</small></td><td>Statistics<small>Ms.M.Maheswari</small></td><td>Language</td></tr>
                            <tr><td class="do">Day Order II</td><td>Statistics<small>Ms.M.Maheswari</small></td><td>English</td><td>Statistics<small>Ms.M.Maheswari</small></td><td>Language</td><td>OOP with C++<small>Dr.P.Gunasundari</small></td><td>English</td></tr>
                            <tr><td class="do">Day Order III</td><td>OOP with C++<small>Dr.P.Gunasundari</small></td><td>HM</td><td>SEC - Quant. Aptitude<small>Ms.M.Maheswari</small></td><td>PEd</td><td>SEC/BT/AT<small>Ms.M.Manju Priya</small></td><td>HM</td></tr>
                            <tr><td class="do">Day Order IV</td><td>English</td><td>OOP with C++<small>Dr.P.Gunasundari</small></td><td>Language</td><td class="lab" colspan="2">← C++ Lab → (Dr.P.Gunasundari)</td><td class="emp">—</td></tr>
                            <tr><td class="do">Day Order V</td><td>OOP with C++<small>Dr.P.Gunasundari</small></td><td>Statistics<small>Ms.M.Maheswari</small></td><td>C++ LAB<small>Dr.P.Gunasundari</small></td><td>Language</td><td>English</td><td>SEC - Quant. Aptitude<small>Ms.M.Maheswari</small></td></tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- II BCA A -->
                    <div id="tt2a" class="ttwrap">
                        <div class="tt-label">CLASS : II BCA 'A' SECTION</div>
                        <table class="tt">
                            <thead><tr><th class="dh">Day / Hour</th><th>Monday</th><th>Tuesday</th><th>Wednesday</th><th>Thursday</th><th>Friday</th><th>Saturday</th></tr></thead>
                            <tbody>
                            <tr><td class="do">Day Order I</td><td>Java Programming<small>Ms.C.Vanisri</small></td><td>Language</td><td>English</td><td>SEC-Technical Writing<small>Ms.S.Jayanthi</small></td><td>Cost &amp; Mgmt Accounting</td><td>Java Programming<small>Ms.C.Vanisri</small></td></tr>
                            <tr><td class="do">Day Order II</td><td>Java Programming<small>Ms.C.Vanisri</small></td><td>HM</td><td>SEC-Managing Emotions<small>Ms.Aarthi.G</small></td><td>Cost &amp; Mgmt Accounting</td><td>English</td><td>EVS<small>Ms.C.Vanisri</small></td></tr>
                            <tr><td class="do">Day Order III</td><td>Cost &amp; Mgmt Accounting</td><td>HM</td><td>English</td><td>Language</td><td>SEC-Technical Writing<small>Ms.S.Jayanthi</small></td><td>English</td></tr>
                            <tr><td class="do">Day Order IV</td><td>Language</td><td class="lab" colspan="2">← Java Lab → (Ms.C.Vanisri)</td><td>English</td><td>Cost &amp; Mgmt Accounting</td><td>Language</td></tr>
                            <tr><td class="do">Day Order V</td><td class="lab" colspan="2">← Java Lab → (Ms.C.Vanisri)</td><td>Java Programming<small>Ms.C.Vanisri</small></td><td>Library</td><td>SEC-Managing Emotions<small>Ms.S.Deebalakshmi</small></td><td>Language</td></tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- II BCA B -->
                    <div id="tt2b" class="ttwrap">
                        <div class="tt-label">CLASS : II BCA 'B' SECTION</div>
                        <table class="tt">
                            <thead><tr><th class="dh">Day / Hour</th><th>Monday</th><th>Tuesday</th><th>Wednesday</th><th>Thursday</th><th>Friday</th><th>Saturday</th></tr></thead>
                            <tbody>
                            <tr><td class="do">Day Order I</td><td>Java Programming<small>MV</small></td><td>Language</td><td>English</td><td>SEC-Technical Writing<small>Ms.C.Vanisri</small></td><td>SEC-Managing Emotions<small>Ms.S.Deebalakshmi</small></td><td>Language</td></tr>
                            <tr><td class="do">Day Order II</td><td class="lab" colspan="2">← Java Lab → (MV)</td><td>Java Programming<small>MV</small></td><td>Cost &amp; Mgmt Accounting</td><td>English</td><td>Java Programming<small>MV</small></td></tr>
                            <tr><td class="do">Day Order III</td><td>Cost &amp; Mgmt Accounting</td><td>HM</td><td>English</td><td>Language</td><td>SEC-Technical Writing<small>Ms.C.Vanisri</small></td><td>English</td></tr>
                            <tr><td class="do">Day Order IV</td><td>Language</td><td>HM</td><td>English</td><td>Java Programming<small>MV</small></td><td>Cost &amp; Mgmt Accounting</td><td class="emp">—</td></tr>
                            <tr><td class="do">Day Order V</td><td>Java Programming<small>MV</small></td><td>Cost &amp; Mgmt Accounting</td><td>Library</td><td>SEC-Managing Emotions<small>Ms.S.Deebalakshmi</small></td><td>EVS<small>MV</small></td><td class="lab">← Java Lab → (MV)</td></tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- III BCA A -->
                    <div id="tt3a" class="ttwrap">
                        <div class="tt-label">CLASS : III BCA 'A' SECTION</div>
                        <table class="tt">
                            <thead><tr><th class="dh">Day / Hour</th><th>Monday</th><th>Tuesday</th><th>Wednesday</th><th>Thursday</th><th>Friday</th><th>Saturday</th></tr></thead>
                            <tbody>
                            <tr><td class="do">Day Order I</td><td class="lab" colspan="2">← R Programming Lab → (Dr.D.Seethalakshmi)</td><td>Network Security<small>MV</small></td><td>Network Security<small>Dr.P.Gunasundari</small></td><td>Advanced Networking<small>Dr.D.Seethalakshmi</small></td><td>Data Mining &amp; Warehousing<small>Ms.M.Manju Priya</small></td></tr>
                            <tr><td class="do">Day Order II</td><td>Advanced Networking<small>Dr.D.Seethalakshmi</small></td><td>Network Security<small>Dr.P.Gunasundari</small></td><td>Data Mining &amp; Warehousing<small>Ms.M.Manju Priya</small></td><td>R Programming<small>Ms.S.Deebalakshmi</small></td><td>Mini Project<small>Ms.S.Deebalakshmi</small></td><td>R Programming<small>Ms.M.Maheswari</small></td></tr>
                            <tr><td class="do">Day Order III</td><td>Data Mining &amp; Warehousing<small>Ms.G.Aarthi</small></td><td>Advanced Networking<small>Dr.D.Seethalakshmi</small></td><td>R Programming<small>Ms.S.Deebalakshmi</small></td><td>Data Mining &amp; Warehousing<small>Ms.M.Manju Priya</small></td><td>Network Security<small>MV</small></td><td>R Programming<small>Ms.M.Maheswari</small></td></tr>
                            <tr><td class="do">Day Order IV</td><td>R Programming<small>Ms.S.Deebalakshmi</small></td><td>Advanced Networking<small>Dr.D.Seethalakshmi</small></td><td>Network Security<small>Dr.P.Gunasundari</small></td><td class="lab" colspan="2">← R Programming Lab →</td><td>Data Mining &amp; Warehousing</td></tr>
                            <tr><td class="do">Day Order V</td><td>Data Mining &amp; Warehousing<small>Ms.M.Manju Priya</small></td><td>R Programming<small>Ms.S.Deebalakshmi</small></td><td>HM</td><td>Advanced Networking<small>Dr.D.Seethalakshmi</small></td><td>Mini Project<small>Dr.D.Seethalakshmi</small></td><td class="lab">← R Programming Lab →</td></tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- III BCA B -->
                    <div id="tt3b" class="ttwrap">
                        <div class="tt-label">CLASS : III BCA 'B' SECTION</div>
                        <table class="tt">
                            <thead><tr><th class="dh">Day / Hour</th><th>Monday</th><th>Tuesday</th><th>Wednesday</th><th>Thursday</th><th>Friday</th><th>Saturday</th></tr></thead>
                            <tbody>
                            <tr><td class="do">Day Order I</td><td class="lab" colspan="2">← R Programming Lab → (Ms.M.Manju Priya)</td><td>Advanced Networking<small>Ms.C.Vanisri</small></td><td>Data Mining &amp; Warehousing<small>Ms.S.Deebalakshmi</small></td><td>R Programming<small>Ms.M.Manju Priya</small></td><td>Data Mining &amp; Warehousing<small>Ms.S.Deebalakshmi</small></td></tr>
                            <tr><td class="do">Day Order II</td><td>Data Mining &amp; Warehousing<small>Ms.S.Deebalakshmi</small></td><td>R Programming<small>Ms.M.Manju Priya</small></td><td>Network Security<small>Dr.D.Seethalakshmi</small></td><td>Mini Project<small>Ms.M.Manju Priya</small></td><td>Advanced Networking<small>Ms.C.Vanisri</small></td><td>R Programming<small>Ms.M.Manju Priya</small></td></tr>
                            <tr><td class="do">Day Order III</td><td>Advanced Networking<small>Ms.C.Vanisri</small></td><td>Advanced Networking<small>Ms.Aarthi.G</small></td><td>R Programming<small>Ms.M.Manju Priya</small></td><td>Network Security<small>Ms.S.Jayanthi</small></td><td>Data Mining &amp; Warehousing<small>Ms.S.Deebalakshmi</small></td><td>Network Security<small>Ms.S.Jayanthi</small></td></tr>
                            <tr><td class="do">Day Order IV</td><td>R Programming<small>Ms.M.Manju Priya</small></td><td>Data Mining &amp; Warehousing<small>Ms.Aarthi.G</small></td><td>Network Security<small>Ms.S.Jayanthi</small></td><td class="lab" colspan="2">← R Programming Lab → (Ms.M.Manju Priya)</td><td>Advanced Networking<small>Ms.Aarthi.G</small></td></tr>
                            <tr><td class="do">Day Order V</td><td>Data Mining &amp; Warehousing<small>Ms.S.Deebalakshmi</small></td><td>R Programming<small>Ms.M.Manju Priya</small></td><td>HM</td><td>Mini Project<small>Ms.M.Maheswari</small></td><td>Network Security<small>Ms.S.Jayanthi</small></td><td class="lab">← R Programming Lab → (Dr.D.Seethalakshmi)</td></tr>
                            </tbody>
                        </table>
                    </div>

                </div><!-- end box -->
            </div><!-- end schedule -->
        </div><!-- end content -->
    </div><!-- end dash -->
</div><!-- end student page -->

<!-- ============================== ADMIN DASHBOARD ============================== -->
<div id="pgAdm" class="page">
    <div class="dash">
        <div class="sidebar">
            <div class="sb-title">Admin Portal</div>
            <div class="mi active" onclick="sa('aOv',this)"><span>📊</span>Dashboard</div>
            <div class="mi" onclick="sa('aVF',this)"><span>📝</span>View Feedbacks</div>
            <div class="mi" onclick="sa('aAN',this)"><span>📈</span>Analytics</div>
            <div class="mi" onclick="sa('aTR',this)"><span>👩‍🏫</span>Teacher Reports</div>
            <div class="mi" onclick="sa('aSM',this)"><span>👥</span>Student Mgmt</div>
            <div class="mi" onclick="sa('aSUB',this)"><span>📚</span>Subject Mgmt</div>
            <div class="mi" onclick="sa('aEX',this)"><span>💾</span>Export Data</div>
            <div class="mi" onclick="sa('aNT',this)"><span>🔔</span>Notifications</div>
            <div class="mi" onclick="sa('aRP',this)"><span>📄</span>Reports</div>
            <div class="mi" onclick="sa('aBC',this)"><span>📊</span>Bar Chart</div>
            <div class="mi" onclick="sa('aFS',this)"><span>⚙️</span>FB Settings</div>
            <button class="sb-logout" onclick="logout()">↩ Logout</button>
        </div>
        <div class="content">

            <!-- Dashboard Overview -->
            <div id="aOv" class="mod active">
                <div class="mod-hdr"><h2>Admin Dashboard</h2><p>Feedback system overview</p><hr></div>
                <div class="stats">
                    <div class="sc"><h3>Total Students</h3><div class="num">265</div></div>
                    <div class="sc"><h3>1st Year</h3><div class="num">99</div></div>
                    <div class="sc"><h3>2nd Year</h3><div class="num">96</div></div>
                    <div class="sc"><h3>3rd Year</h3><div class="num">70</div></div>
                    <div class="sc"><h3>Total Teachers</h3><div class="num">12</div></div>
                    <div class="sc"><h3>Total Subjects</h3><div class="num">16</div></div>
                    <div class="sc"><h3>Feedbacks Received</h3><div class="num"><?= $totalFBs ?></div></div>
                    <div class="sc"><h3>Average Rating</h3><div class="num"><?= $overallAvgPHP ?></div></div>
                </div>
            </div>

            <!-- View Feedbacks (PHP data loaded via JS/AJAX) -->
            <div id="aVF" class="mod">
                <div class="mod-hdr"><h2>📝 All Feedbacks</h2><p>Anonymous student submissions</p><hr></div>
                <div id="vfLoad" style="padding:20px;color:var(--muted);"><span class="spin"></span> Loading feedbacks...</div>
                <div class="twrap" id="vfWrap" style="display:none;">
                    <table><thead><tr><th>ID</th><th>Date</th><th>Teacher</th><th>Subject</th><th>Q1</th><th>Q2</th><th>Q3</th><th>Q4</th><th>Q5</th><th>Avg</th></tr></thead>
                    <tbody id="vfTbl"></tbody></table>
                </div>
            </div>

            <!-- Analytics (PHP: ratings distribution) -->
            <div id="aAN" class="mod">
                <div class="mod-hdr"><h2>📈 Analytics</h2><p>Rating distribution — loaded from PHP</p><hr></div>
                <div id="anLoad" style="padding:20px;color:var(--muted);"><span class="spin"></span> Loading analytics...</div>
                <div id="anContent" style="display:none;">
                    <div class="box">
                        <h3>Overall Rating Distribution</h3>
                        <div id="anDist"></div>
                        <p id="anSummary" style="margin-top:14px;font-size:13px;color:var(--muted);"></p>
                    </div>
                    <div class="box">
                        <h3>Feedback Count per Teacher</h3>
                        <div id="anTeacher"></div>
                    </div>
                    <div class="box">
                        <h3>Subject-wise Average Ratings</h3>
                        <div id="anSubject"></div>
                    </div>
                </div>
            </div>

            <!-- Teacher Reports -->
            <div id="aTR" class="mod">
                <div class="mod-hdr"><h2>👩‍🏫 Teacher Reports</h2><p>Individual teacher performance (PHP data)</p><hr></div>
                <div class="box">
                    <div class="ex-row" style="margin-bottom:16px;">
                        <a href="?export=pdf" target="_blank" class="ex-btn" style="background:#7a1a1a;">📑 Export PDF</a>
                        <a href="?export=csv" class="ex-btn" style="background:#14652a;">📄 Export CSV</a>
                        <a href="?export=excel" class="ex-btn" style="background:#1a4a1a;">📊 Export Excel</a>
                    </div>
                    <div id="trLoad" style="padding:16px;color:var(--muted);"><span class="spin"></span> Loading...</div>
                    <div class="twrap" id="trWrap" style="display:none;">
                        <table><thead><tr><th>Teacher</th><th>Subjects</th><th>Feedbacks</th><th>Avg Rating</th></tr></thead>
                        <tbody id="trTbl"></tbody></table>
                    </div>
                </div>
            </div>

            <!-- Student Management (HTML only) -->
            <div id="aSM" class="mod">
                <div class="mod-hdr"><h2>👥 Student Management</h2><p>Class-wise student count</p><hr></div>
                <div class="stats">
                    <div class="sc"><h3>Class 1A</h3><div class="num">50</div></div>
                    <div class="sc"><h3>Class 1B</h3><div class="num">49</div></div>
                    <div class="sc"><h3>Class 2A</h3><div class="num">49</div></div>
                    <div class="sc"><h3>Class 2B</h3><div class="num">47</div></div>
                    <div class="sc"><h3>Class 3A</h3><div class="num">37</div></div>
                    <div class="sc"><h3>Class 3B</h3><div class="num">33</div></div>
                </div>
            </div>

            <!-- Subject Management (HTML only) -->
            <div id="aSUB" class="mod">
                <div class="mod-hdr"><h2>📚 Subject Management</h2><p>Subject–teacher assignments</p><hr></div>
                <div class="box">
                    <div class="twrap">
                        <table><thead><tr><th>Subject</th><th>Assigned Teacher</th></tr></thead>
                        <tbody>
                            <tr><td>Tamil</td><td>Tamil Faculty</td></tr>
                            <tr><td>English</td><td>English Faculty</td></tr>
                            <tr><td>Java Programming (II BCA A)</td><td>Ms.C.Vanisri</td></tr>
                            <tr><td>Java Programming (II BCA B)</td><td>Ms.M.Vijayarani</td></tr>
                            <tr><td>Java Practical</td><td>Ms.M.Vijayarani</td></tr>
                            <tr><td>R Programming</td><td>Ms.M.Manju Priya</td></tr>
                            <tr><td>Data Mining and Warehousing</td><td>Ms.S.Deebalakshmi</td></tr>
                            <tr><td>Network Security</td><td>Ms.C.Vanisri / Ms.S.Jayanthi / Dr.P.Gunasundari</td></tr>
                            <tr><td>Cost &amp; Management Accounting</td><td>Accounts Faculty</td></tr>
                            <tr><td>Emotional Intelligence / Managing Emotions</td><td>Dr.D.Seethalakshmi / Ms.G.Aarthi / Ms.S.Deebalakshmi</td></tr>
                            <tr><td>C++ Programming (I BCA A)</td><td>Ms.S.Jayanthi</td></tr>
                            <tr><td>C++ Programming (I BCA B)</td><td>Dr.P.Gunasundari</td></tr>
                            <tr><td>C++ Practical</td><td>Ms.S.Jayanthi / Dr.P.Gunasundari</td></tr>
                            <tr><td>Advanced Networking</td><td>Dr.D.Seethalakshmi / Ms.G.Aarthi / Ms.C.Vanisri</td></tr>
                            <tr><td>Statistics</td><td>M.Maheswari</td></tr>
                            <tr><td>SEC - Quantitative Aptitude</td><td>M.Maheswari</td></tr>
                            <tr><td>EVS</td><td>Ms.C.Vanisri / Ms.M.Vijayarani</td></tr>
                        </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Export Data (PHP handles the actual export) -->
            <div id="aEX" class="mod">
                <div class="mod-hdr"><h2>💾 Export Data</h2><p>Download feedbacks — PHP generates files</p><hr></div>
                <div class="box">
                    <h3>Export All Feedbacks</h3>
                    <div class="ex-row">
                        <a href="?export=excel" class="ex-btn" style="background:#14652a;">📊 Export as Excel (.xls)</a>
                        <a href="?export=csv"   class="ex-btn" style="background:#0e4a6e;">📄 Export as CSV</a>
                        <a href="?export=pdf" target="_blank" class="ex-btn" style="background:#7a1a1a;">📑 Export as PDF</a>
                    </div>
                </div>
                <div class="box">
                    <h3>Export by Teacher</h3>
                    <div class="fg"><label>Select Teacher</label>
                        <select id="exTch">
                            <option value="">All Teachers</option>
                            <option>Dr.D.Seethalakshmi</option><option>M.Maheswari</option>
                            <option>Ms.M.Manju Priya</option><option>Ms.S.Deebalakshmi</option>
                            <option>Ms.C.Vanisri</option><option>Ms.S.Jayanthi</option>
                            <option>Dr.P.Gunasundari</option><option>Ms.M.Vijayarani</option>
                            <option>Ms.G.Aarthi</option>
                        </select>
                    </div>
                    <div class="ex-row">
                        <button class="ex-btn" style="background:#14652a;" onclick="exportByT('excel')">📊 Excel</button>
                        <button class="ex-btn" style="background:#0e4a6e;" onclick="exportByT('csv')">📄 CSV</button>
                        <button class="ex-btn" style="background:#7a1a1a;" onclick="exportByT('pdf')">📑 PDF</button>
                    </div>
                </div>
            </div>

            <!-- Notifications (PHP saves, JS displays) -->
            <div id="aNT" class="mod">
                <div class="mod-hdr"><h2>🔔 Notifications</h2><p>Send to students — PHP saves each notification</p><hr></div>
                <div class="box">
                    <h3>Send New Notification</h3>
                    <div class="fg"><label>Target Audience</label>
                        <select id="ntTarget">
                            <option>All Students</option><option>I BCA - A</option><option>I BCA - B</option>
                            <option>II BCA - A</option><option>II BCA - B</option>
                            <option>III BCA - A</option><option>III BCA - B</option>
                        </select>
                    </div>
                    <div class="fg"><label>Title *</label><input type="text" id="ntTitle" placeholder="Notification title..."></div>
                    <div class="fg"><label>Message *</label><textarea id="ntMsg" placeholder="Your message..."></textarea></div>
                    <div id="ntStatus"></div>
                    <button class="btn-main" onclick="sendNotif()">📤 Send Notification</button>
                </div>
                <div class="box">
                    <h3>Notification History</h3>
                    <div id="ntHistory"><span class="spin"></span> Loading...</div>
                </div>
            </div>

            <!-- Report Generation (HTML UI, PHP export links) -->
            <div id="aRP" class="mod">
                <div class="mod-hdr"><h2>📄 Report Generation</h2><p>Custom reports — PHP generates files</p><hr></div>
                <div class="box">
                    <h3>Generate Reports</h3>
                    <div class="fg"><label>Report Type</label>
                        <select id="rptType">
                            <option>Full Summary Report</option>
                            <option>Teacher-wise Report</option>
                            <option>Subject-wise Report</option>
                            <option>Class-wise Report</option>
                        </select>
                    </div>
                    <div class="ex-row">
                        <a href="?export=pdf" target="_blank" class="ex-btn" style="background:#7a1a1a;">📑 Generate PDF</a>
                        <a href="?export=excel" class="ex-btn" style="background:#14652a;">📊 Generate Excel</a>
                        <a href="?export=csv" class="ex-btn" style="background:#0e4a6e;">📄 Generate CSV</a>
                    </div>
                </div>
                <div class="box">
                    <h3>Recent Reports</h3>
                    <div class="twrap"><table>
                        <thead><tr><th>Report Name</th><th>Type</th><th>Date</th><th>Format</th></tr></thead>
                        <tbody>
                            <tr><td>Full Summary Feb 2026</td><td>Summary</td><td>2026-02-15</td><td>PDF</td></tr>
                            <tr><td>Dr.P.Gunasundari Report</td><td>Teacher</td><td>2026-02-14</td><td>Excel</td></tr>
                        </tbody>
                    </table></div>
                </div>
            </div>

            <!-- Bar Chart (PHP computes data, JS renders bars — NO COLORS, ratings only) -->
            <div id="aBC" class="mod">
                <div class="mod-hdr"><h2>📊 Bar Chart Analysis</h2><p>Teacher &amp; subject ratings — PHP data, no colors</p><hr></div>
                <div id="bcLoad" style="padding:20px;color:var(--muted);"><span class="spin"></span> Loading chart data from PHP...</div>
                <div id="bcContent" style="display:none;">
                    <div class="box">
                        <h3>Teacher Average Ratings (Out of 10)</h3>
                        <div id="bcTeacher"></div>
                        <div id="bcSummary" style="margin-top:14px;padding:12px 16px;background:#eef2f8;border-left:3px solid var(--navy);border-radius:6px;font-size:13px;"></div>
                    </div>
                    <div class="box">
                        <h3>Subject Average Ratings (Out of 10)</h3>
                        <div id="bcSubject"></div>
                    </div>
                </div>
            </div>

            <!-- Feedback Settings (PHP edit + add) -->
            <div id="aFS" class="mod">
                <div class="mod-hdr"><h2>⚙️ Feedback Settings</h2><p>Edit feedbacks, add teachers/subjects — PHP saves</p><hr></div>
                <div class="box">
                    <h3>✏️ Edit Feedback Submissions</h3>
                    <div id="fsLoad" style="padding:16px;color:var(--muted);"><span class="spin"></span> Loading feedbacks...</div>
                    <div class="twrap" id="fsWrap" style="display:none;">
                        <table><thead><tr><th>ID</th><th>Date &amp; Time</th><th>Teacher</th><th>Subject</th><th>Avg</th><th>Action</th></tr></thead>
                        <tbody id="fsTbl"></tbody></table>
                    </div>
                </div>
                <div class="box">
                    <h3>➕ Add New Teacher</h3>
                    <form id="addTchForm">
                        <div class="fg"><label>Teacher Name *</label><input type="text" id="ntName" placeholder="Full name" required></div>
                        <div class="fg"><label>Position</label><input type="text" id="ntPos" placeholder="e.g., Assistant Professor"></div>
                        <div id="ntAddMsg"></div>
                        <button type="button" class="btn-main" style="width:auto;padding:12px 28px;" onclick="addTeacher()">Add Teacher</button>
                    </form>
                </div>
                <div class="box">
                    <h3>➕ Add New Subject</h3>
                    <form id="addSubForm">
                        <div class="fg"><label>Subject Name *</label><input type="text" id="nsName" placeholder="Subject name" required></div>
                        <div class="fg"><label>Assign Teacher *</label>
                            <select id="nsTeacher">
                                <option value="">Select Teacher</option>
                                <option>Dr.D.Seethalakshmi</option><option>M.Maheswari</option>
                                <option>Ms.M.Manju Priya</option><option>Ms.S.Deebalakshmi</option>
                                <option>Ms.C.Vanisri</option><option>Ms.S.Jayanthi</option>
                                <option>Dr.P.Gunasundari</option><option>Ms.M.Vijayarani</option>
                                <option>Ms.G.Aarthi</option><option>Priya</option>
                                <option>Kurinji</option><option>Rakshitha</option>
                            </select>
                        </div>
                        <div id="nsAddMsg"></div>
                        <button type="button" class="btn-main" style="width:auto;padding:12px 28px;" onclick="addSubject()">Add Subject</button>
                    </form>
                </div>
            </div>

        </div><!-- end admin content -->
    </div>
</div>

<!-- Edit Feedback Modal -->
<div class="modal-bg" id="editModal">
    <div class="modal-box">
        <h3>✏️ Edit Feedback</h3>
        <p id="editFBId" style="font-size:12px;color:var(--muted);margin-bottom:16px;"></p>
        <div class="fg"><label>Q1 – Explains concepts clearly</label><select id="eq1"><option>Outstanding</option><option>Excellent</option><option>Very Good</option><option>Good</option><option>Satisfactory</option></select></div>
        <div class="fg"><label>Q2 – Approachable & helpful</label><select id="eq2"><option>Outstanding</option><option>Excellent</option><option>Very Good</option><option>Good</option><option>Satisfactory</option></select></div>
        <div class="fg"><label>Q3 – Teaching methods effective</label><select id="eq3"><option>Outstanding</option><option>Excellent</option><option>Very Good</option><option>Good</option><option>Satisfactory</option></select></div>
        <div class="fg"><label>Q4 – Encourages participation</label><select id="eq4"><option>Outstanding</option><option>Excellent</option><option>Very Good</option><option>Good</option><option>Satisfactory</option></select></div>
        <div class="fg"><label>Q5 – Overall satisfaction</label><select id="eq5"><option>Outstanding</option><option>Excellent</option><option>Very Good</option><option>Good</option><option>Satisfactory</option></select></div>
        <div class="fg"><label>Comments</label><textarea id="eCmt"></textarea></div>
        <div class="fg"><label>Suggestions</label><textarea id="eSug"></textarea></div>
        <div id="editMsg"></div>
        <div class="modal-foot">
            <button class="btn-main" onclick="saveEdit()">💾 Save Changes</button>
            <button class="btn-main" style="background:#5a6a7e;" onclick="closeEdit()">Cancel</button>
        </div>
    </div>
</div>

<!-- ============================== JAVASCRIPT ============================== -->
<script>
// ============ STUDENT DATA ============
const DB = {
    '1A':['Abinaya SJ','Abinaya Bai','Akshayanivasini KS','Amiesha S','Arul Vadivu M','Asma Begum N','Asmitha T','Bharathi C','Deepika M','Dhanalakshmi T','Dhirshya S','Divya Bharathi E','Divya dharshini V','Gayathri B','Gomathy Supriya V','Harini M','Harini R','Hashini S','Janani M','Janani N','Jasmine P','Jeevitha I','Jeyavarshini S','Jothika M','Kamali G','Kana sri P','Karthika P','Kavinaya L','Kavinayasree S','Kaviya L','Kaviya M','Kaviya Shree M','Kaviya V 32','Kaviya V 33','Kirthana Ramesh','Krithika T','Kutty Prabakari S','Lakshana G','Lakshmi P','Lavanya Y','Maanasa M','Madhumitha K','Madhumitha R','Meenu Rakshana S','Navyaa SJ','Nethra B','Nithika R','Pavithra M','Pooja M','Pooja Sree S'],
    '1B':['Priyadharshini.S','Rajalakshmi.R','Rakshana.N','Ramya.A','Ramya.B','Rishika.B','Roghini.K','Sabbena.J','Sai Harini.G','Sandhya Arasi.R','Saranya.E','Sashruthi.V','Sathya Priya.S','Savitha.V','Sharmila.G','Shobhitha.S','Sivapriya.B','Sivasakthi.S','Sofiya Sweety.A','Sri renuka','Srimathi.D','Subhashini.J','Suchithra.K','Suganya.S','Swasthika.B.K','Tamilarasi.M','Thilaga.K','Vaishnavi.S','Vinisha.M.S','Yashawini.R','Yogalakshmi.S','Monisha.J','Krishika.V','Lakshana.D','Rithikhaa.K.P','Ashwathi.B.S','Chadalavada Rohini Priya','Hemavathy.G.S','Kavi Shree.M','Madhu Mitha.N','Mahaa Lakshmi.N','Narmatha.P.M','Nivedha Rani.V','Sanjana.U','Shahidha Begum.S','Sharmila.M','Sumedha.S','Swathy Krishna.C.U','Manasa. R'],
    '2A':['Aishwarya','Amritha','Amudhavarshini','Anithashree','Ashwini','Barkath Fathima','Bhavana','Deekshitha','Carolina','Dennis Deora','Dharshini A','Dharshini L','Fathima Suthaira','Gajalakshmi','Harita','Harini S','Harini K','Haripriya','Haritha','Hephzibaha','Injana Fathima','Janani G','Jayashri M','Jayasree S','Jayasri M','Kareeshma','Karthika','Kavinila','Kavya B','Kavya S','Keerthana B','Keerthana H','Keerthika','Keerthika Jyothi','Kritika','Leya Jacklin','Madhumitha B','Madhumitha S','Mahalakshmi','Maitreye','Nimka Fathima','Monika','Mugilarasi','Mythili','Nagisetti Lavanya','Nivetha G','Nivetha V','Pavithra','Pranathi.R'],
    '2B':['Prathishia','Priya Dharshini P','Pooja Dharshini R','Priyadharshini S','Priyadharshini S2','Rathina Priya','Reema K','Reshma M','Rithigo L','Resketha M','Sandhana J R','Sangeetha S','Sangeetha Sri','Sarika S','Savitha B','Shalini M','Shanmuga S K','Sreya S','Subashri B','Sufitha T','Sumaiya Fathima A','Susmitha B','Swathi S','Swetha N','Swetha R','Tharani S K','Valli S','Yalini R','Yamini S','Navya K S','Brindha V','Kishore','Hema','Isha','Jahnavi','Jayavalli','Kanishika','Kavyaa','Poojitha','Preethi','Pooja Dharshini M','Radha','Sampritha','Swathi P S','Thiriona','Yoshini','Vishnupriya'],
    '3A':['Aaminah Aafreen S','Akshaya F','Alekya N B','Anisha Roselin I','Chandana Priya P','Chenchu Lakshmi P','Deepika V','Dhanalakshmi A','Dharshini V','Elavarasi S','Fareen Tawfeeka S','Harini M R','Harini T','Janani B','Jeeva Lakshmi K','Jegavarshini S','Kalaiarasi R','Kalpana G','Lishashree C','Madhumitha S','Mahadharshini S','Malini M','Masika J','Monika Y','Mukilavani P','Nandhini M','Nishitha N','Nivetha S','Preethi B','Preethiyanga P','Priyadharshini P C','Pushpaja G','Rabiya Shahin R','Rakshitha R','Rathika K','Reshma V','Sakthinivashani S'],
    '3B':['Sanjitha R','Shabreen.M','Shalini.M','Shanmathi','Sharmila N','Sharmila PS','Sneha.M','Sowmiya.M','Srayaa G','Sujithra. S','Swega','Uma maheshwari.B','Yunisha A S','Yuvashree M','Yuvasri B M','Salma suha','Shivani pathak','Bhavana VP','Dhanuja SD','Hasmathunnisa','Hemapriya','Latisha A','Madhulekha.D','Mithra M','Nadheera N','Naggayatri','Priya dharshini.D.J','Sai Rupika K A','Sandhiya','Shrinidhi M','Sowmiya S','Thabasum begum.S','Vijayalakshmyi .V']
};

const TEACHER_SUBJECTS = {
    'Dr.D.Seethalakshmi':'Advanced Networking, Emotional Intelligence',
    'M.Maheswari':'Statistics, SEC Quantitative Aptitude',
    'Ms.M.Manju Priya':'R Programming',
    'Ms.S.Deebalakshmi':'Data Mining and Warehousing',
    'Ms.C.Vanisri':'Java Programming (II A), Network Security',
    'Ms.S.Jayanthi':'C++ Programming (I A), Network Security',
    'Dr.P.Gunasundari':'C++ Programming (I B), C++ Practical',
    'Ms.M.Vijayarani':'Java Programming (II B), Java Practical',
    'Ms.G.Aarthi':'Advanced Networking, Managing Emotions'
};

const RV = {Outstanding:10,Excellent:8,'Very Good':6,Good:4,Satisfactory:2};
let curUser='', curClass='', myFBs=[], editingId='';

// ---- Login helpers ----
function loadNames() {
    const cls = document.getElementById('stuClass').value;
    const sel = document.getElementById('stuName');
    sel.innerHTML = '<option value="">Select your name</option>';
    if (cls && DB[cls]) DB[cls].forEach(n => { const o=document.createElement('option'); o.value=o.textContent=n; sel.appendChild(o); });
}
function switchTab(t) {
    const isStu = t==='stu';
    document.getElementById('stuLogin').style.display  = isStu?'block':'none';
    document.getElementById('admLogin').style.display  = isStu?'none':'block';
    document.querySelectorAll('.tab-btn')[0].classList.toggle('active', isStu);
    document.querySelectorAll('.tab-btn')[1].classList.toggle('active', !isStu);
}
function stuLogin() {
    const cls=document.getElementById('stuClass').value, name=document.getElementById('stuName').value, pwd=document.getElementById('stuPwd').value;
    if (!cls)  { showMsg('stuMsg','❌ Select your class','err'); return; }
    if (!name) { showMsg('stuMsg','❌ Select your name','err');  return; }
    if (pwd!=='bca') { showMsg('stuMsg','❌ Wrong password (use: bca)','err'); return; }
    curUser=name; curClass=cls;
    document.getElementById('stuNameHdr').textContent = name;
    pg('pgLogin','pgStu'); document.getElementById('stuFBNum').textContent = myFBs.length;
}
function admLogin() {
    const pwd=document.getElementById('admPwd').value;
    if (pwd!=='admin0525') { showMsg('admMsg','❌ Incorrect admin password','err'); return; }
    pg('pgLogin','pgAdm');
}
function logout() {
    ['pgStu','pgAdm'].forEach(id => document.getElementById(id).classList.remove('active'));
    document.getElementById('pgLogin').classList.add('active');
    curUser=''; curClass=''; myFBs=[];
}
function pg(hide, show) {
    document.getElementById(hide).classList.remove('active');
    document.getElementById(show).classList.add('active');
}

// ---- Module switches ----
function sw(id, el) {
    document.querySelectorAll('#pgStu .mod').forEach(m=>m.classList.remove('active'));
    document.getElementById(id).classList.add('active');
    document.querySelectorAll('#pgStu .mi').forEach(m=>m.classList.remove('active'));
    el.classList.add('active');
    if (id==='sMF') renderMyFB();
}
function sa(id, el) {
    document.querySelectorAll('#pgAdm .mod').forEach(m=>m.classList.remove('active'));
    document.getElementById(id).classList.add('active');
    document.querySelectorAll('#pgAdm .mi').forEach(m=>m.classList.remove('active'));
    el.classList.add('active');
    // PHP-driven modules
    if (id==='aVF') loadFeedbacksTable();
    if (id==='aAN') loadAnalytics();
    if (id==='aTR') loadTeacherReports();
    if (id==='aBC') loadBarChart();
    if (id==='aNT') loadNotifications();
    if (id==='aFS') { loadFeedbacksEdit(); }
}
function showTT(id, el) {
    document.querySelectorAll('.ttwrap').forEach(t=>t.classList.remove('active'));
    document.getElementById(id).classList.add('active');
    document.querySelectorAll('.ct-btn').forEach(b=>b.classList.remove('active'));
    el.classList.add('active');
}

// ---- Utility ----
function showMsg(id, msg, type) {
    const el = document.getElementById(id);
    if (el) { el.innerHTML = `<div class="alert ${type}">${msg}</div>`; }
}
function calcAvg(q1,q2,q3,q4,q5) { return ((RV[q1]||0)+(RV[q2]||0)+(RV[q3]||0)+(RV[q4]||0)+(RV[q5]||0))/5; }
function esc(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

// ---- PHP AJAX: Submit Feedback (saves to feedbacks.txt) ----
function submitFB() {
    const teacher  = document.getElementById('fbTeacher').value;
    const subject  = document.getElementById('fbSubject').value;
    const q1 = document.querySelector('input[name="rq1"]:checked');
    const q2 = document.querySelector('input[name="rq2"]:checked');
    const q3 = document.querySelector('input[name="rq3"]:checked');
    const q4 = document.querySelector('input[name="rq4"]:checked');
    const q5 = document.querySelector('input[name="rq5"]:checked');
    if (!teacher)           { showMsg('fbMsg','❌ Please select a teacher','err'); return; }
    if (!subject)           { showMsg('fbMsg','❌ Please select a subject','err'); return; }
    if (!q1||!q2||!q3||!q4||!q5) { showMsg('fbMsg','❌ Please answer all 5 questions','err'); return; }

    showMsg('fbMsg','<span class="spin"></span> Saving feedback...','ok');
    const fd = new FormData();
    fd.append('action','submit_feedback');
    fd.append('teacher',teacher); fd.append('subject',subject);
    fd.append('q1',q1.value); fd.append('q2',q2.value); fd.append('q3',q3.value);
    fd.append('q4',q4.value); fd.append('q5',q5.value);
    fd.append('comments', document.getElementById('fbCmt').value);
    fd.append('suggestions', document.getElementById('fbSug').value);
    fd.append('student_name', curUser); fd.append('student_class', curClass);

    fetch(window.location.href, {method:'POST', body:fd})
    .then(r=>r.json())
    .then(data => {
        if (data.status==='success') {
            const avg = calcAvg(q1.value,q2.value,q3.value,q4.value,q5.value).toFixed(1);
            myFBs.push({date:new Date().toLocaleDateString('en-CA'), teacher, subject, avg});
            document.getElementById('stuFBNum').textContent = myFBs.length;
            showMsg('fbMsg','✅ '+data.msg+' (ID: '+data.id+')', 'ok');
            setTimeout(()=>{
                document.getElementById('fbTeacher').value='';
                document.getElementById('fbSubject').value='';
                document.getElementById('fbCmt').value='';
                document.getElementById('fbSug').value='';
                document.querySelectorAll('input[type="radio"]').forEach(r=>r.checked=false);
                document.getElementById('fbMsg').innerHTML='';
            }, 3500);
        } else { showMsg('fbMsg','❌ '+data.msg,'err'); }
    })
    .catch(()=>{
        // fallback: save locally if server not available
        const avg = calcAvg(q1.value,q2.value,q3.value,q4.value,q5.value).toFixed(1);
        myFBs.push({date:new Date().toLocaleDateString('en-CA'), teacher, subject, avg});
        document.getElementById('stuFBNum').textContent = myFBs.length;
        showMsg('fbMsg','✅ Feedback recorded! (Deploy on PHP server to save permanently)','ok');
        setTimeout(()=>{ document.getElementById('fbMsg').innerHTML=''; }, 3500);
    });
}

function renderMyFB() {
    const tb = document.getElementById('myFBTbl');
    if (!myFBs.length) { tb.innerHTML='<tr><td colspan="5" style="text-align:center;padding:28px;color:var(--muted);">No feedback submitted yet</td></tr>'; return; }
    tb.innerHTML = myFBs.map(f=>`<tr><td>${f.date}</td><td>${esc(f.teacher)}</td><td>${esc(f.subject)}</td><td><strong>${f.avg}/10</strong></td><td style="color:var(--good);font-weight:700;">✅ Submitted</td></tr>`).join('');
}

// ---- PHP AJAX: Load feedbacks table ----
function loadFeedbacksTable() {
    document.getElementById('vfLoad').style.display='block';
    document.getElementById('vfWrap').style.display='none';
    fetch('?action=get_feedbacks').then(r=>r.json()).then(rows=>{
        const tb = document.getElementById('vfTbl');
        tb.innerHTML = rows.map(f=>`<tr><td>${esc(f.id)}</td><td>${esc(f.date)}</td><td>${esc(f.teacher)}</td><td>${esc(f.subject)}</td><td>${esc(f.q1)}</td><td>${esc(f.q2)}</td><td>${esc(f.q3)}</td><td>${esc(f.q4)}</td><td>${esc(f.q5)}</td><td><strong>${esc(f.avg)}/10</strong></td></tr>`).join('');
        document.getElementById('vfLoad').style.display='none';
        document.getElementById('vfWrap').style.display='block';
    }).catch(()=>{ document.getElementById('vfLoad').innerHTML='<div class="alert err">Could not load. Please run on a PHP server.</div>'; });
}

// ---- PHP AJAX: Analytics — ratings distribution ----
function loadAnalytics() {
    document.getElementById('anLoad').style.display='block';
    document.getElementById('anContent').style.display='none';
    fetch('?action=get_analytics').then(r=>r.json()).then(d=>{
        // Distribution: NO colors, just navy bars
        const total = Object.values(d.dist).reduce((a,b)=>a+b,0)||1;
        document.getElementById('anDist').innerHTML = Object.entries(d.dist).map(([r,c])=>{
            const pct = Math.round((c/total)*100);
            return `<div class="brow"><div class="blabel">${r}</div><div class="bbg"><div class="bfill" style="width:${pct}%;background:var(--navy);">${c}</div></div><div class="bval">${pct}%</div></div>`;
        }).join('');
        document.getElementById('anSummary').textContent = `Total responses: ${total} across all 5 questions | Overall avg: ${d.avg}/10`;

        // Teacher count bars
        document.getElementById('anTeacher').innerHTML = Object.entries(d.teachers).map(([t,avg])=>{
            const pct = Math.round((avg/10)*100);
            return `<div class="brow"><div class="blabel" style="font-size:12px;">${esc(t)}</div><div class="bbg"><div class="bfill" style="width:${pct}%;background:var(--blue);">${avg}/10</div></div><div class="bval">${avg}</div></div>`;
        }).join('') || '<p style="color:var(--muted);">No data yet</p>';

        // Subject bars
        document.getElementById('anSubject').innerHTML = Object.entries(d.subjects).map(([s,avg])=>{
            const pct = Math.round((avg/10)*100);
            return `<div class="brow"><div class="blabel" style="font-size:12px;">${esc(s)}</div><div class="bbg"><div class="bfill" style="width:${pct}%;background:var(--navy);">${avg}/10</div></div><div class="bval">${avg}</div></div>`;
        }).join('') || '<p style="color:var(--muted);">No data yet</p>';

        document.getElementById('anLoad').style.display='none';
        document.getElementById('anContent').style.display='block';
    }).catch(()=>{ document.getElementById('anLoad').innerHTML='<div class="alert err">Could not load. Run on PHP server.</div>'; });
}

// ---- PHP AJAX: Teacher Reports ----
function loadTeacherReports() {
    document.getElementById('trLoad').style.display='block';
    document.getElementById('trWrap').style.display='none';
    fetch('?action=get_analytics').then(r=>r.json()).then(d=>{
        const tb = document.getElementById('trTbl');
        tb.innerHTML = Object.entries(TEACHER_SUBJECTS).map(([t,s])=>{
            const avg = d.teachers[t]||'N/A';
            return `<tr><td><strong>${esc(t)}</strong></td><td style="font-size:12px;color:var(--muted);">${esc(s)}</td><td>${d.teachers[t]?'—':'0'}</td><td><strong>${avg!=='N/A'?avg+'/10':'N/A'}</strong></td></tr>`;
        }).join('');
        document.getElementById('trLoad').style.display='none';
        document.getElementById('trWrap').style.display='block';
    }).catch(()=>{ document.getElementById('trLoad').innerHTML='<div class="alert err">Could not load. Run on PHP server.</div>'; });
}

// ---- PHP AJAX: Bar Chart — NO COLORS, plain navy/blue bars ----
function loadBarChart() {
    document.getElementById('bcLoad').style.display='block';
    document.getElementById('bcContent').style.display='none';
    fetch('?action=get_analytics').then(r=>r.json()).then(d=>{
        // Teacher bars — NO color, just navy fill
        document.getElementById('bcTeacher').innerHTML = Object.entries(d.teachers).map(([t,avg])=>{
            const pct = Math.round((avg/10)*100);
            return `<div class="brow"><div class="blabel">${esc(t)}</div><div class="bbg"><div class="bfill" style="width:${pct}%;background:var(--navy);">${avg}/10</div></div><div class="bval">${avg}</div></div>`;
        }).join('');

        // Subject bars — slightly lighter blue, still no colors
        document.getElementById('bcSubject').innerHTML = Object.entries(d.subjects).map(([s,avg])=>{
            const pct = Math.round((avg/10)*100);
            return `<div class="brow"><div class="blabel" style="font-size:12px;">${esc(s)}</div><div class="bbg"><div class="bfill" style="width:${pct}%;background:var(--blue);">${avg}/10</div></div><div class="bval">${avg}</div></div>`;
        }).join('');

        const avgVals = Object.values(d.teachers);
        const overallAvg = avgVals.length ? (avgVals.reduce((a,b)=>a+b,0)/avgVals.length).toFixed(1) : d.avg;
        const topEntry  = Object.entries(d.teachers).sort((a,b)=>b[1]-a[1])[0];
        document.getElementById('bcSummary').innerHTML = `<strong>Department Average:</strong> ${overallAvg}/10 &nbsp;|&nbsp; <strong>Highest Rated:</strong> ${topEntry ? esc(topEntry[0])+' ('+topEntry[1]+'/10)' : '—'} &nbsp;|&nbsp; <strong>Total Feedbacks:</strong> ${d.total}`;

        document.getElementById('bcLoad').style.display='none';
        document.getElementById('bcContent').style.display='block';
    }).catch(()=>{ document.getElementById('bcLoad').innerHTML='<div class="alert err">Could not load. Run on PHP server.</div>'; });
}

// ---- PHP AJAX: Notifications ----
function loadNotifications() {
    document.getElementById('ntHistory').innerHTML = '<span class="spin"></span> Loading...';
    fetch('?action=get_notifications').then(r=>r.json()).then(notifs=>{
        document.getElementById('ntHistory').innerHTML = notifs.map(n=>`<div class="ni"><strong>${esc(n.title)}</strong><p>${esc(n.msg)}</p><small>📅 ${esc(n.dt)} &nbsp;|&nbsp; 👥 ${esc(n.target)}</small></div>`).join('');
    }).catch(()=>{ document.getElementById('ntHistory').innerHTML='<div class="alert err">Could not load.</div>'; });
}

// ---- PHP AJAX: Send Notification ----
function sendNotif() {
    const title  = document.getElementById('ntTitle').value.trim();
    const msg    = document.getElementById('ntMsg').value.trim();
    const target = document.getElementById('ntTarget').value;
    if (!title||!msg) { showMsg('ntStatus','❌ Title and message required','err'); return; }
    showMsg('ntStatus','<span class="spin"></span> Sending...','ok');
    const fd = new FormData();
    fd.append('action','send_notification'); fd.append('notif_title',title);
    fd.append('notif_msg',msg); fd.append('notif_target',target);
    fetch(window.location.href, {method:'POST',body:fd}).then(r=>r.json()).then(d=>{
        showMsg('ntStatus', d.status==='success'?'✅ '+d.msg:'❌ '+d.msg, d.status==='success'?'ok':'err');
        if (d.status==='success') {
            document.getElementById('ntTitle').value='';
            document.getElementById('ntMsg').value='';
            setTimeout(()=>{ loadNotifications(); document.getElementById('ntStatus').innerHTML=''; }, 1500);
        }
    }).catch(()=>{ showMsg('ntStatus','✅ Notification saved (run on PHP server to persist)','ok'); });
}

// ---- PHP AJAX: Load feedbacks for edit table ----
function loadFeedbacksEdit() {
    document.getElementById('fsLoad').style.display='block';
    document.getElementById('fsWrap').style.display='none';
    fetch('?action=get_feedbacks').then(r=>r.json()).then(rows=>{
        const tb = document.getElementById('fsTbl');
        tb.innerHTML = rows.map(f=>`<tr><td>${esc(f.id)}</td><td>${esc(f.date)} ${esc(f.time)}</td><td>${esc(f.teacher)}</td><td>${esc(f.subject)}</td><td><strong>${esc(f.avg)}/10</strong></td><td><button class="btn-sm" style="background:var(--blue2);color:white;" onclick='openEdit(${JSON.stringify(f)})'>Edit</button></td></tr>`).join('');
        document.getElementById('fsLoad').style.display='none';
        document.getElementById('fsWrap').style.display='block';
    }).catch(()=>{ document.getElementById('fsLoad').innerHTML='<div class="alert err">Run on PHP server to load data.</div>'; });
}

// ---- PHP AJAX: Edit Feedback ----
function openEdit(f) {
    editingId = f.id;
    document.getElementById('editFBId').textContent = `ID: ${f.id} | Teacher: ${f.teacher} | Subject: ${f.subject}`;
    document.getElementById('eq1').value = f.q1||'Outstanding';
    document.getElementById('eq2').value = f.q2||'Outstanding';
    document.getElementById('eq3').value = f.q3||'Outstanding';
    document.getElementById('eq4').value = f.q4||'Outstanding';
    document.getElementById('eq5').value = f.q5||'Outstanding';
    document.getElementById('eCmt').value = f.comments||'';
    document.getElementById('eSug').value = f.suggestions||'';
    document.getElementById('editMsg').innerHTML = '';
    document.getElementById('editModal').style.display = 'block';
}
function saveEdit() {
    showMsg('editMsg','<span class="spin"></span> Saving...','ok');
    const fd = new FormData();
    fd.append('action','edit_feedback'); fd.append('edit_id',editingId);
    fd.append('eq1',document.getElementById('eq1').value);
    fd.append('eq2',document.getElementById('eq2').value);
    fd.append('eq3',document.getElementById('eq3').value);
    fd.append('eq4',document.getElementById('eq4').value);
    fd.append('eq5',document.getElementById('eq5').value);
    fd.append('ecomments',document.getElementById('eCmt').value);
    fd.append('esuggestions',document.getElementById('eSug').value);
    fetch(window.location.href,{method:'POST',body:fd}).then(r=>r.json()).then(d=>{
        showMsg('editMsg', d.status==='success'?'✅ '+d.msg:'❌ '+d.msg, d.status==='success'?'ok':'err');
        if (d.status==='success') setTimeout(()=>{ closeEdit(); loadFeedbacksEdit(); },1200);
    }).catch(()=>{ showMsg('editMsg','✅ Updated (run on PHP server to persist)','ok'); setTimeout(closeEdit, 1500); });
}
function closeEdit() { document.getElementById('editModal').style.display='none'; }
document.getElementById('editModal').addEventListener('click', e=>{ if(e.target===document.getElementById('editModal')) closeEdit(); });

// ---- PHP AJAX: Add Teacher ----
function addTeacher() {
    const name = document.getElementById('ntName').value.trim();
    const pos  = document.getElementById('ntPos').value.trim();
    if (!name) { showMsg('ntAddMsg','❌ Enter teacher name','err'); return; }
    showMsg('ntAddMsg','<span class="spin"></span> Adding...','ok');
    const fd = new FormData();
    fd.append('action','add_teacher'); fd.append('teacher_name',name); fd.append('teacher_pos',pos);
    fetch(window.location.href,{method:'POST',body:fd}).then(r=>r.json()).then(d=>{
        showMsg('ntAddMsg', d.status==='success'?'✅ '+d.msg:'❌ '+d.msg, d.status==='success'?'ok':'err');
        if (d.status==='success') { document.getElementById('ntName').value=''; document.getElementById('ntPos').value=''; }
    }).catch(()=>{ showMsg('ntAddMsg',`✅ Teacher "${name}" added (run on PHP server to persist)`,'ok'); document.getElementById('ntName').value=''; });
}

// ---- PHP AJAX: Add Subject ----
function addSubject() {
    const sub = document.getElementById('nsName').value.trim();
    const tch = document.getElementById('nsTeacher').value;
    if (!sub) { showMsg('nsAddMsg','❌ Enter subject name','err'); return; }
    if (!tch) { showMsg('nsAddMsg','❌ Select a teacher','err'); return; }
    showMsg('nsAddMsg','<span class="spin"></span> Adding...','ok');
    const fd = new FormData();
    fd.append('action','add_subject'); fd.append('subject_name',sub); fd.append('assign_teacher',tch);
    fetch(window.location.href,{method:'POST',body:fd}).then(r=>r.json()).then(d=>{
        showMsg('nsAddMsg', d.status==='success'?'✅ '+d.msg:'❌ '+d.msg, d.status==='success'?'ok':'err');
        if (d.status==='success') { document.getElementById('nsName').value=''; document.getElementById('nsTeacher').value=''; }
    }).catch(()=>{ showMsg('nsAddMsg',`✅ Subject "${sub}" assigned to ${tch} (run on PHP server to persist)`,'ok'); document.getElementById('nsName').value=''; });
}

// ---- Export by teacher ----
function exportByT(fmt) {
    const t = document.getElementById('exTch').value;
    window.location.href = `?export=${fmt}${t?'&tf='+encodeURIComponent(t):''}`;
}
</script>
</body>
</html>