<?php
require_once 'init.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Terms of Service - OpsBuddy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-light">
    <?php require_once 'header_html.php'; ?>
    
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-body p-5">
                        <h2 class="fw-bold mb-4 text-primary"><i class="bi bi-file-earmark-text me-2"></i>Terms of Service & License Agreement</h2>
                        
                        <p class="text-muted mb-4">Last Updated: August 2026</p>

                        <h5 class="fw-bold mt-4">1. Acceptance of Terms</h5>
                        <p>By installing, accessing, or using <strong>OpsBuddy</strong> (the "Software"), you agree to be bound by these Terms of Service. If you do not agree to these terms, you may not use the Software.</p>

                        <h5 class="fw-bold mt-4">2. Commercial License</h5>
                        <p>OpsBuddy is a commercial product. You must possess a valid, unexpired license file provided directly by the author (<strong>Nicolas Housset</strong>) to use the Software in any environment (including development, staging, or production).</p>
                        <ul>
                            <li>Licenses are bound to a specific Client ID and are strictly non-transferable.</li>
                            <li>Bypassing, modifying, or tampering with the cryptographic license validation mechanism is strictly prohibited and terminates your rights to use the Software immediately.</li>
                        </ul>

                        <h5 class="fw-bold mt-4">3. Restrictions on Use</h5>
                        <p>You may not:</p>
                        <ul>
                            <li>Reverse engineer, decompile, or disassemble the Software.</li>
                            <li>Distribute, sell, lease, or sub-license the Software to third parties.</li>
                            <li>Remove any copyright, trademark, or proprietary notices from the Software.</li>
                        </ul>

                        <h5 class="fw-bold mt-4">4. Disclaimer of Warranties</h5>
                        <p>The Software is provided "AS IS", without warranty of any kind, express or implied. OpsBuddy acts as an administrative bridge to your OpenShift cluster and SAS Viya environments. The author does not guarantee that the Software will be error-free or that it will not cause disruptions if used improperly.</p>

                        <h5 class="fw-bold mt-4">5. Limitation of Liability</h5>
                        <p>In no event shall the author be liable for any claim, damages, data loss, cluster downtime, or other liability arising from, out of, or in connection with the Software or the use or other dealings in the Software. Administrative actions taken through OpsBuddy are at your own risk.</p>

                        <h5 class="fw-bold mt-4">6. Contact</h5>
                        <p>For support, licensing inquiries, or feedback, please visit the official product page:</p>
                        <a href="https://nicolas-housset.fr/opsBuddy" target="_blank" class="btn btn-outline-primary mt-2"><i class="bi bi-globe me-1"></i> nicolas-housset.fr/opsBuddy</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>