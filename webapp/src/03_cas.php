<?php
require_once 'init.php';

$script_dir = '/var/www/cmd';
$cas_file = $script_dir . '/.cas_servers_' . $active_profile;

function run_oc($cmd) {
    global $config_path, $active_profile;
    $source = file_exists($config_path) ? "source $config_path && " : "";
    return shell_exec("bash -c '$source export DRY_RUN=false && export PROFILE_NAME=$active_profile && $cmd'");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_action'])) {
    header('Content-Type: application/json');
    $action = $_POST['ajax_action'];
    $cas_name = preg_replace('/[^a-zA-Z0-9-]/', '', $_POST['cas_name'] ?? 'default');
    
    $response = ['status' => 'success', 'output' => ''];
    
    switch ($action) {
        case 'get_dashboard':
            $servers = file_exists($cas_file) ? file($cas_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : ['default'];
            if (empty($servers)) $servers = ['default'];
            $dashboard = [];
            foreach ($servers as $s) {
                $s = trim($s);
                $exists = run_oc("oc get casdeployment $s -n \\$DEFAULT_NAMESPACE 2>/dev/null");
                if (empty(trim($exists))) {
                    $dashboard[] = ['name' => $s, 'status' => 'Not deployed / Unknown', 'color' => 'secondary', 'deployed' => false];
                    continue;
                }
                $pods = run_oc("oc get pods -n \\$DEFAULT_NAMESPACE -l casoperator.sas.com/server=$s --field-selector=status.phase=Running --no-headers 2>/dev/null | wc -l");
                $count = (int)trim($pods);
                if ($count > 0) {
                    $dashboard[] = ['name' => $s, 'status' => "DÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â©marrÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â© ($count active pods)", 'color' => 'success', 'deployed' => true, 'running' => true];
                } else {
                    $dashboard[] = ['name' => $s, 'status' => 'Stopped (0 pod)', 'color' => 'danger', 'deployed' => true, 'running' => false];
                }
            }
            $response['dashboard'] = $dashboard;
            break;
            
        case 'start':
            $response['output'] = run_oc("oc patch casdeployment $cas_name -n \\$DEFAULT_NAMESPACE --type=json -p='[{\"op\": \"add\", \"path\": \"/spec/shutdown\", \"value\": false}]' 2>&1");
            break;
            
        case 'stop':
            $response['output'] = run_oc("oc patch casdeployment $cas_name -n \\$DEFAULT_NAMESPACE --type=json -p='[{\"op\": \"add\", \"path\": \"/spec/shutdown\", \"value\": true}]' 2>&1");
            break;
            
        case 'add_cas':
            $servers = file_exists($cas_file) ? file($cas_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : ['default'];
            if (!in_array($cas_name, $servers)) {
                $servers[] = $cas_name;
                file_put_contents($cas_file, implode("\n", $servers));
                $response['output'] = "CAS Server '$cas_name' added to administration list.";
            } else {
                $response['output'] = "This server already exists in the list.";
            }
            break;
            
        case 'global_status':
            $response['output'] = run_oc("echo '--- CAS DEPLOYMENTS ---'; oc get casdeployments -n \\$DEFAULT_NAMESPACE 2>&1; echo ''; echo '--- CAS PODS ---'; oc get pods -l app.kubernetes.io/managed-by=sas-cas-operator -n \\$DEFAULT_NAMESPACE 2>&1");
            break;
            
        case 'logs':
            $pod_grep = $_POST['pod_grep'] ?? 'sas-cas-operator';
            $pod_name = trim(run_oc("oc get pods -n \\$DEFAULT_NAMESPACE --no-headers 2>/dev/null | grep $pod_grep | awk '{print $1}' | head -n 1"));
            if ($pod_name) {
                $response['output'] = run_oc("echo 'Logs for $pod_name :'; oc logs $pod_name -n \\$DEFAULT_NAMESPACE --tail=100 2>&1");
            } else {
                $response['output'] = "Error: No pod found for $pod_grep.";
            }
            break;
    }
    echo json_encode($response);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Gestion du Moteur CAS - SAS Viya 4 OPS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<link rel="stylesheet" href="style.css">
</head>
<body class="bg-light">
    <?php require_once 'header_html.php'; ?>
    <div class="container py-4">
        
        <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
            <h2 class="fw-bold mb-0 text-primary"><i class="bi bi-cpu-fill me-2"></i>CAS Engine Status & Management</h2>
            <div>
                <button class="btn btn-outline-primary" onclick="loadDashboard()"><i class="bi bi-arrow-clockwise me-1"></i> Refresh</button>
            </div>
        </div>

        <div class="row g-4 mb-4" id="dashboardCards">
            <div class="col-12 text-center text-muted"><div class="spinner-border spinner-border-sm me-2" role="status"></div>Loading CAS servers...</div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white fw-bold"><i class="bi bi-plus-circle text-success me-2"></i>Add a CAS server</div>
                    <div class="card-body">
                        <div class="input-group mb-3">
                            <input type="text" id="newCasName" class="form-control" placeholder="ex: shared-default">
                            <button class="btn btn-success" type="button" onclick="addCasServer()">Add</button>
                        </div>
                        <small class="text-muted">Add a server to manage for this profile.</small>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white fw-bold"><i class="bi bi-tools text-warning me-2"></i>Global Actions</div>
                    <div class="card-body d-flex gap-2 flex-wrap">
                        <button class="btn btn-outline-dark" onclick="runAction('global_status')"><i class="bi bi-search me-2"></i>Global Status</button>
                        <button class="btn btn-outline-info" onclick="runAction('logs', 'sas-cas-operator')"><i class="bi bi-file-text me-2"></i>Operator Logs</button>
                        <button class="btn btn-outline-secondary" onclick="runAction('logs', 'sas-cas-control')"><i class="bi bi-file-text me-2"></i>Controller Logs</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 bg-dark text-light">
            <div class="card-header bg-black text-white d-flex justify-content-between align-items-center">
                <span><i class="bi bi-terminal me-2"></i>Console Output</span>
                <button class="btn btn-sm btn-outline-light" onclick="document.getElementById('consoleOut').innerHTML = ''"><i class="bi bi-trash"></i></button>
            </div>
            <div class="card-body p-0">
                <pre id="consoleOut" class="m-0 p-3" style="max-height: 500px; overflow-y: auto; font-size: 0.85rem;">Ready.</pre>
            </div>
        </div>

    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const consoleOut = document.getElementById('consoleOut');
        
        function logToConsole(text) {
            consoleOut.innerHTML = text + "\n\n" + consoleOut.innerHTML;
        }

        async function apiCall(dataObj) {
            const formData = new FormData();
            for (const key in dataObj) formData.append(key, dataObj[key]);
            
            try {
                const response = await fetch('03_cas.php', { method: 'POST', body: formData });
                return await response.json();
            } catch (err) {
                logToConsole("<span class='text-danger'>Network error: " + err.message + "</span>");
                return null;
            }
        }

        async function loadDashboard() {
            const container = document.getElementById('dashboardCards');
            container.innerHTML = '<div class="col-12 text-center text-muted"><div class="spinner-border spinner-border-sm me-2"></div>Actualisation...</div>';
            
            const res = await apiCall({ ajax_action: 'get_dashboard' });
            if (!res || !res.dashboard) return;
            
            container.innerHTML = '';
            res.dashboard.forEach(server => {
                let buttons = '';
                if (server.deployed) {
                    if (server.running) {
                        buttons = <button class="btn btn-sm btn-danger" onclick="casAction('stop', '')"><i class="bi bi-stop-fill me-1"></i>Stop</button>;
                    } else {
                        buttons = <button class="btn btn-sm btn-success" onclick="casAction('start', '')"><i class="bi bi-play-fill me-1"></i>Start</button>;
                    }
                }
                
                container.innerHTML += 
                    <div class="col-md-6 col-lg-4">
                        <div class="card shadow-sm border-start border-4 border- h-100">
                            <div class="card-body">
                                <h5 class="fw-bold"></h5>
                                <p class="mb-3"><span class="badge bg-"></span></p>
                                <div></div>
                            </div>
                        </div>
                    </div>
                ;
            });
        }

        async function casAction(action, casName) {
            logToConsole(<span class='tExecuting  sur ...</span>);
            const res = await apiCall({ ajax_action: action, cas_name: casName });
            if (res) {
                logToConsole(<strong class='text-success'>[]  finished :</strong>\n);
                loadDashboard();
            }
        }
        
        async function runAction(action, grep = '') {
            logToConsole(<span class='tExecuting ...</span>);
            const res = await apiCall({ ajax_action: action, pod_grep: grep });
            if (res) {
                logToConsole(<stResult :</strong>\n);
            }
        }

        async function addCasServer() {
            const name = document.getElementById('newCasName').value.trim();
            if (!name) return;
            const res = await apiCall({ ajax_action: 'add_cas', cas_name: name });
            if (res) {
                logToConsole(<strong class='text-info'>Info:</strong> );
                document.getElementById('newCasName').value = '';
                loadDashboard();
            }
        }

        // Init
        document.addEventListener('DOMContentLoaded', loadDashboard);
    </script>
</body>
</html>