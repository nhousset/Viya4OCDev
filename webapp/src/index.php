<?php
require_once 'init.php';

$categoryIcons = [
    'Global Audits & Checks' => 'bi-clipboard2-check-fill',
    'Resources & Storage' => 'bi-hdd-stack-fill',
    'Monitoring & Logs' => 'bi-activity',
    'SAS Viya Components' => 'bi-cpu-fill',
    'Network & Deployments' => 'bi-diagram-3-fill',
    'Administration & Operations' => 'bi-gear-fill',
    'Others' => 'bi-terminal-fill'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - OpsBuddy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php require_once 'header_html.php'; ?>
    <div class="container-fluid">
        <div class="row"><div class="col-12 p-4">
                <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
                    <h2 class="fw-bold mb-0"><i class="bi bi-grid-1x2-fill me-2 text-primary"></i>Dashboard</h2>
                </div>

                <!-- Custom Views -->
                <div class="row mb-5">
                    <div class="col-12">
                        <div class="card shadow-sm border-0 bg-info bg-opacity-10">
                            <div class="card-body d-flex align-items-center justify-content-between">
                                <div>
                                    <h5 class="text-info-emphasis mb-1"><i class="bi bi-table me-2"></i>Custom Web Views</h5>
                                    <p class="mb-0 text-muted small">Native web interfaces optimized for resource analysis.</p>
                                </div>
                                <div>
                                    <a href="pods_table.php" class="btn btn-info text-white shadow-sm px-3 me-2">Open Pods List</a>
                                    <a href="03_cas.php" class="btn btn-primary shadow-sm px-3">Manage CAS Engine</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- OpenShift Plugins Categories -->
                <?php foreach ($categorized_oc as $categoryName => $scripts): ?>
                    <?php $catIcon = $categoryIcons[$categoryName] ?? 'bi-terminal-fill'; ?>
                    
                    <h4 class="mb-3 mt-4 text-secondary border-start border-4 border-primary ps-2"><?= htmlspecialchars($categoryName) ?></h4>
                    <div class="row g-3 mb-4">
                        <?php foreach ($scripts as $script): ?>
                            <div class="col-md-6 col-lg-4 col-xl-3">
                                <div class="card h-100 shadow-sm card-menu" onclick="window.location.href='<?= pathinfo($script['file'], PATHINFO_FILENAME) ?>.php'">
                                    <div class="card-body">
                                        <h6 class="card-title text-dark fw-bold mb-2">
                                            <i class="bi <?= $catIcon ?> me-2 text-primary"></i><?= htmlspecialchars($script['title']) ?>
                                        </h6>
                                        <p class="card-text text-muted small mb-0 font-monospace" style="font-size: 0.75rem;">
                                            <i class="bi bi-file-earmark-code me-1"></i><?= htmlspecialchars($script['file']) ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>

                <!-- Viya CLI Plugins -->
                <h4 class="mb-3 mt-5 text-secondary border-start border-4 border-warning ps-2">Plugins SAS Viya CLI</h4>
                <div class="row g-3 mb-5">
                    <?php foreach ($plugins_cli as $script): ?>
                        <div class="col-md-6 col-lg-4 col-xl-3">
                            <div class="card h-100 shadow-sm card-menu" onclick="window.location.href='<?= pathinfo($script['file'], PATHINFO_FILENAME) ?>.php'">
                                <div class="card-body">
                                    <h6 class="card-title fw-bold mb-2" style="color: #6f42c1;">
                                        <i class="bi bi-magic me-2"></i><?= htmlspecialchars($script['title']) ?>
                                    </h6>
                                    <p class="card-text text-muted small mb-0 font-monospace" style="font-size: 0.75rem;">
                                        <i class="bi bi-file-earmark-code me-1"></i><?= htmlspecialchars($script['file']) ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

            </div>
        </div>
    </div>
    
      <div class="modal fade" id="connectionModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header bg-dark text-white">
            <h5 class="modal-title"><i class="bi bi-cloud-arrow-down me-2"></i>OpenShift Connection</h5>
          </div>
          <div class="modal-body text-center py-4" id="connModalBody">
            <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;"></div>
            <h5>Checking cluster connection...</h5>
            <p class="text-muted small">Active profile : <?= htmlspecialchars($active_profile) ?></p>
          </div>
          <div class="modal-footer justify-content-center" id="connModalFooter" style="display: none;">
          </div>
        </div>
      </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        const connModal = new bootstrap.Modal(document.getElementById('connectionModal'));
        const autoCheck = <?= empty($_SESSION['cluster_connected'][$active_profile]) ? 'true' : 'false' ?>;
        
        if (autoCheck) {
            connModal.show();
            
            fetch('check_connect.php')
              .then(response => response.json())
              .then(data => {
                  const body = document.getElementById('connModalBody');
                  const footer = document.getElementById('connModalFooter');
                  
                  if (data.status === 'error') {
                      body.innerHTML = `
                          <i class="bi bi-x-circle text-danger mb-3" style="font-size: 3rem;"></i>
                          <h5 class="text-danger">Connection Error</h5>
                          <p class="text-muted small">${data.message}</p>
                      `;
                      footer.innerHTML = `<button class="btn btn-secondary" onclick="window.location.reload()">Retry</button>`;
                      footer.style.display = 'flex';
                  } else if (data.status === 'token_expired') {
                      let linkHtml = '';
                      if (data.token_url && data.token_url !== 'skip') {
                          linkHtml = `<a href="${data.token_url}" target="_blank" class="btn btn-sm btn-outline-info mb-3"><i class="bi bi-box-arrow-up-right me-1"></i> Get a new Token</a>`;
                      }
                      body.innerHTML = `
                          <i class="bi bi-shield-lock text-danger mb-2" style="font-size: 3rem;"></i>
                          <h5 class="text-danger">Expired or Missing Token</h5>
                          <p class="text-muted small mb-2">Unable to connect to <strong>${data.server}</strong></p>
                          ${linkHtml}
                          <div class="form-floating mb-3 text-start">
                            <input type="password" class="form-control" id="newTokenInput" placeholder="sha256~...">
                            <label for="newTokenInput">Paste the new Token here</label>
                          </div>
                      `;
                      footer.innerHTML = `<button class="btn btn-success px-4" onclick="saveNewToken()"><i class="bi bi-floppy me-2"></i>Save and Connect</button>
                                          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>`;
                      footer.style.display = 'flex';
                  } else if (data.status === 'success') {
                      body.innerHTML = `
                          <i class="bi bi-check-circle text-success mb-3" style="font-size: 3rem;"></i>
                          <h5 class="text-success">Connection Successful</h5>
                          <p class="text-muted small">Successfully connected to the cluster.</p>
                      `;
                      setTimeout(() => {
                          connModal.hide();
                      }, 1500);
                  }
              })
              .catch(err => {
                  document.getElementById('connModalBody').innerHTML = `
                      <i class="bi bi-x-octagon text-danger mb-3" style="font-size: 3rem;"></i>
                      <h5 class="text-danger">Internal Error</h5>
                      <p class="text-muted small">Could not verify connection.</p>
                  `;
                  document.getElementById('connModalFooter').innerHTML = `<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>`;
                  document.getElementById('connModalFooter').style.display = 'flex';
              });
        }
    });

    function saveNewToken() {
        const token = document.getElementById('newTokenInput').value;
        if (!token) return;
        
        const btn = document.querySelector('#connModalFooter .btn-success');
        btn.innerHTML = `<span class="spinner-border spinner-border-sm me-2"></span>Saving...`;
        btn.disabled = true;
        
        fetch('save_token.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'token=' + encodeURIComponent(token)
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                window.location.reload();
            } else {
                alert('Error saving token: ' + data.message);
                btn.innerHTML = `<i class="bi bi-floppy me-2"></i>Save and Connect`;
                btn.disabled = false;
            }
        });
    }
    </script>
</body>
</html>