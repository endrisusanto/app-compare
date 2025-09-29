<?php
// =================================================================
// KONFIGURASI & LOGIKA UTAMA (DALAM SATU FILE)
// =================================================================
session_start();

// -- Fungsi-Fungsi Pembantu --
function parse_excel_data(string $data): array {
    $lines = explode("\n", trim($data));
    if (count($lines) < 2) { return []; }
    $headers = str_getcsv(trim($lines[0]), "\t");
    $tableData = [];
    for ($i = 1; $i < count($lines); $i++) {
        $row_data = str_getcsv(trim($lines[$i]), "\t");
        $header_count = count($headers); $row_count = count($row_data);
        if ($row_count < $header_count) { $row_data = array_pad($row_data, $header_count, null); } 
        elseif ($row_count > $header_count) { $row_data = array_slice($row_data, 0, $header_count); }
        if (count($headers) == count($row_data)) { $tableData[] = array_combine($headers, $row_data); }
    }
    return $tableData;
}

// -- Logika Pemrosesan Form --
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $base_data_raw = $_POST['base_data'] ?? '';
    $ole_data_raw = $_POST['ole_data'] ?? '';

    if (empty($base_data_raw) || empty($ole_data_raw)) {
        $_SESSION['error'] = "Mohon isi kedua textarea untuk perbandingan.";
    } else {
        $base_table = parse_excel_data($base_data_raw);
        $ole_table = parse_excel_data($ole_data_raw);
        $ole_map = array_column($ole_table, null, 'Model Name');
        
        // [DIKEMBALIKAN] Logika untuk data clipboard
        $base_lines = explode("\n", trim($base_data_raw));
        $header_line = array_shift($base_lines);
        
        $results = [];

        foreach ($base_table as $index => $base_row) {
            $modelName = $base_row['Model Name'] ?? null;
            if ($modelName && isset($ole_map[$modelName])) {
                $ole_row = $ole_map[$modelName];
                $base_ap_diff = (int)($base_row['AP(Code) Diff.'] ?? 0);
                $ole_ap_diff = (int)($ole_row['AP(Code) Diff.'] ?? 0);

                // [LOGIKA BARU] Tampilkan jika versi AP TIDAK SAMA
                if ($base_ap_diff != $ole_ap_diff) {
                    $base_row['comparison_status'] = ($base_ap_diff > $ole_ap_diff) ? 'upgrade' : 'downgrade';
                    $full_line_for_clipboard = $header_line . "\n" . ($base_lines[$index] ?? '');
                    
                    $results[] = [
                        'display' => $base_row,
                        'clipboard' => $full_line_for_clipboard
                    ];
                }
            }
        }
        $_SESSION['results'] = $results;
        $_SESSION['db_message'] = "Perbandingan selesai. Ditemukan " . count($results) . " perbedaan versi.";
    }
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

// -- Pengambilan Data untuk Tampilan --
$results = $_SESSION['results'] ?? [];
$db_message = $_SESSION['db_message'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['results'], $_SESSION['db_message'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Software Version Comparator</title>
    
    <script>
        if (localStorage.getItem('color-theme') === 'dark' || (!('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
    
    <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@latest/dist/style.css" rel="stylesheet" type="text/css">
    
    <style>
        :root{--bg-main:#f1f5f9;--bg-card:rgba(255,255,255,0.6);--text-main:#020617;--text-light:#475569;--border-color:rgba(0,0,0,0.1);--input-bg:#ffffff;--datatable-text:#475569}html.dark{--bg-main:#020617;--bg-card:rgba(15,23,42,0.3);--text-main:#f8fafc;--text-light:#94a3b8;--border-color:rgba(255,255,255,0.1);--input-bg:rgba(30,41,59,0.3);--datatable-text:#94a3b8}body{font-family:'Inter',sans-serif;background-color:var(--bg-main);color:var(--text-main);transition:background-color .3s,color .3s}body::before{content:'';position:fixed;top:0;left:0;width:100%;height:100%;z-index:-2;background:radial-gradient(circle at 10% 10%,#4f46e5 0%,transparent 40%),radial-gradient(circle at 90% 80%,#0ea5e9 0%,transparent 40%);animation:aurora-glow 20s infinite alternate}html.light body::before{display:none}@keyframes aurora-glow{from{opacity:.3;filter:blur(100px)}to{opacity:.5;filter:blur(50px);transform:rotate(15deg) scale(1.2)}}.glass-card{background:var(--bg-card);backdrop-filter:blur(16px);-webkit-backdrop-filter:blur(16px);border:1px solid var(--border-color);transition:transform .3s ease,box-shadow .3s ease}.glass-card:hover{transform:translateY(-5px);box-shadow:0 20px 40px rgba(0,0,0,.1)}.datatable-selector{background-color:var(--input-bg);color:var(--datatable-text);border:1px solid var(--border-color)}.datatable-input,.datatable-table th{background-color:var(--input-bg);color:var(--datatable-text);border-color:var(--border-color)}.datatable-pagination a,.datatable-info{color:var(--datatable-text) !important}.datatable-pagination a:hover,.datatable-pagination .datatable-active a{background-color:#38bdf8 !important;color:#fff !important}.datatable-table th,.datatable-table td{color:var(--text-main)}.title-gradient{background:linear-gradient(90deg,#38bdf8,#818cf8,#e879f9);-webkit-background-clip:text;background-clip:text;color:transparent}.glass-button{position:relative;padding:.75rem 2rem;font-size:1rem;font-weight:700;color:var(--text-main);backdrop-filter:blur(10px);border:1px solid var(--border-color);border-radius:9999px;background-image:linear-gradient(to right,rgba(255,255,255,.1),rgba(255,255,255,.05));box-shadow:0 10px 30px rgba(0,0,0,.1);cursor:pointer;overflow:hidden;transition:all .3s ease}.glass-button::before{content:'';position:absolute;top:var(--y);left:var(--x);transform:translate(-50%,-50%);width:0;height:0;background:radial-gradient(circle closest-side,#a78bfa,transparent);border-radius:50%;opacity:0;transition:width .4s ease,height .4s ease,opacity .4s ease}.glass-button:hover::before{width:250px;height:250px;opacity:.4}.glass-button:hover{transform:scale(1.05);box-shadow:0 15px 40px rgba(0,0,0,.2)}.icon-btn{position:relative;display:inline-flex;justify-content:center;align-items:center;width:32px;height:32px;border-radius:50%;background:rgba(255,255,255,.1);backdrop-filter:blur(5px);border:1px solid rgba(255,255,255,.15);color:var(--text-light);transition:all .2s}.icon-btn:hover{background:rgba(255,255,255,.2);color:var(--text-main);transform:scale(1.1)}.icon-btn .tooltip{position:absolute;bottom:125%;left:50%;transform:translateX(-50%);background-color:#1e293b;color:#fff;padding:4px 8px;border-radius:4px;font-size:12px;white-space:nowrap;opacity:0;visibility:hidden;transition:opacity .2s,visibility .2s}.icon-btn:hover .tooltip{opacity:1;visibility:visible}.floating-alert{transition:transform .5s ease-in-out, opacity .5s ease-in-out; transform:translateY(-150%)}.floating-alert.show{transform:translateY(0)}
    </style>
</head>
<body class="font-sans">
        <canvas id="animated-bg" class="fixed top-0 left-0 w-full h-full -z-10"></canvas>
    <script src="https://cdn.jsdelivr.net/npm/simple-datatables@latest/dist/umd/simple-datatables.js" type="text/javascript"></script>
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        // [DIKEMBALIKAN] Inisialisasi Fitur Tabel
        const resultsTable = document.querySelector("#resultsTable");
        if (resultsTable && resultsTable.rows.length > 1) { // Hanya inisialisasi jika ada data
            new simpleDatatables.DataTable(resultsTable);
        }

        // [DIKEMBALIKAN] Logika untuk Floating Alert
        const alertContainer = document.getElementById('alert-container');
        if (alertContainer) {
            const alerts = alertContainer.querySelectorAll('.floating-alert');
            alerts.forEach((alert, index) => {
                setTimeout(() => { alert.classList.add('show'); }, index * 200);
            });
            alertContainer.addEventListener('click', function(e) {
                const closeButton = e.target.closest('.close-alert');
                if (closeButton) { 
                    const alert = closeButton.closest('.floating-alert');
                    alert.style.transform = 'translateY(-150%)';
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 500);
                }
            });
        }

        // [DIKEMBALIKAN] Logika Dark Mode Toggle
        const themeToggleBtn = document.getElementById('theme-toggle');
        const darkIcon = document.getElementById('theme-toggle-dark-icon');
        const lightIcon = document.getElementById('theme-toggle-light-icon');
        const updateIcons = () => {
            if (document.documentElement.classList.contains('dark')) {
                darkIcon.classList.remove('hidden'); lightIcon.classList.add('hidden');
            } else {
                darkIcon.classList.add('hidden'); lightIcon.classList.remove('hidden');
            }
        };
        updateIcons();
        themeToggleBtn.addEventListener('click', () => {
            document.documentElement.classList.toggle('dark');
            localStorage.setItem('color-theme', document.documentElement.classList.contains('dark') ? 'dark' : 'light');
            updateIcons();
        });

        // [DIKEMBALIKAN] Event Listener untuk Tombol Aksi (Copy)
        document.body.addEventListener('click', function(event) {
            const target = event.target.closest('button');
            if (!target) return;
            if (target.classList.contains('copy-btn')) {
                const textToCopy = target.getAttribute('data-clipboard-text');
                navigator.clipboard.writeText(textToCopy).then(() => {
                    const tooltip = target.querySelector(".tooltip");
                    if (tooltip) {
                        const originalText = tooltip.textContent;
                        tooltip.textContent = "Copied!";
                        setTimeout(() => { tooltip.textContent = originalText; }, 1500);
                    }
                });
            }
        });

        // [DIKEMBALIKAN] Efek hover pada tombol utama
        const btn = document.querySelector('.glass-button');
        if(btn) {
            btn.addEventListener('mousemove', e => {
                const rect = btn.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                btn.style.setProperty('--x', `${x}px`);
                btn.style.setProperty('--y', `${y}px`);
            });
        }
    });

    // --- Animasi Latar Belakang (tetap sama) ---
    const canvas=document.getElementById("animated-bg"),ctx=canvas.getContext("2d");canvas.width=window.innerWidth;canvas.height=window.innerHeight;let particles=[],particleCount=75,mouse={x:null,y:null,radius:100};window.addEventListener("mousemove",e=>{mouse.x=e.x,mouse.y=e.y}),window.addEventListener("mouseout",()=>{mouse.x=null,mouse.y=null});class Particle{constructor(){this.x=Math.random()*canvas.width,this.y=Math.random()*canvas.height,this.size=Math.random()*2.5+1,this.baseX=this.x,this.baseY=this.y,this.density=30*Math.random()+1,this.speedX=.4*Math.random()-.2,this.speedY=.4*Math.random()-.2,this.color=document.documentElement.classList.contains("dark")?"rgba(56, 189, 248, 0.7)":"rgba(96, 165, 250, 0.7)"}update(){let e=mouse.x-this.x,t=mouse.y-this.y,o=Math.sqrt(e*e+t*t),s=e/o,a=t/o,i=mouse.radius,n=(i-o)/i,l=s*n*this.density,d=a*n*this.density;o<mouse.radius?(this.x-=l,this.y-=d):(this.x!==this.baseX&&(e=this.x-this.baseX,this.x-=e/10),this.y!==this.baseY&&(t=this.y-this.baseY,this.y-=t/10)),this.x+=this.speedX,this.y+=this.speedY,(this.x>canvas.width||this.x<0)&&(this.speedX*=-1),(this.y>canvas.height||this.y<0)&&(this.speedY*=-1)}draw(){ctx.fillStyle=this.color,ctx.beginPath(),ctx.arc(this.x,this.y,this.size,0,2*Math.PI),ctx.fill()}}function initParticles(){for(let e=0;e<particleCount;e++)particles.push(new Particle)}function animateParticles(){ctx.clearRect(0,0,canvas.width,canvas.height),particles.forEach(e=>{e.update(),e.draw()}),requestAnimationFrame(animateParticles)}initParticles(),animateParticles(),window.addEventListener("resize",()=>{canvas.width=window.innerWidth,canvas.height=window.innerHeight,particles=[],initParticles()});
    </script>
</body>
</html>