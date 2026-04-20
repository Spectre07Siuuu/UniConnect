<?php
// Include authentication check to ensure user is logged in
require_once 'check_auth.php'; // Uses the updated check_auth.php
require_once '../config/db_connect.php'; // Uses the updated db_connect.php
require_once __DIR__ . '/includes/layout.php';

// Get user's student_id from session (now $_SESSION['user_id'] holds student_id)
$user_student_id = $_SESSION['user_id'] ?? null;
// Use full_name directly from session (populated in login.php)
$full_name = $_SESSION['full_name'] ?? 'User';

$shell_data = getDashboardShellData($pdo, $user_student_id);
$coins = $shell_data['coins'];
$profile_picture = $shell_data['profile_picture'];
?>
<?php renderDashboardHead('Academic Tools - UniConnect', [
    'css/academic_tools.css',
    'css/calculators.css',
    'css/modals.css',
    'css/responsive.css',
]); ?>
<?php renderDashboardShellStart('academic_tools', 'Academic Tools', $coins, $profile_picture, $user_student_id); ?>

        <div class="content-main">
          <div id="academic-tools-display-area" class="academic-tools-grid">
            <div class="tool-block" data-tool="cgpa-calculator">
              <i class="mdi mdi-calculator"></i>
              <h3>CGPA Calculator</h3>
            </div>
            <div class="tool-block" data-tool="tuition-fee-calculator">
              <i class="mdi mdi-cash-multiple"></i>
              <h3>Tuition Fee Calculator</h3>
            </div>
          </div>

          <div class="coming-soon-message">
            <p>More exciting features coming soon!</p>
          </div>
        </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const displayArea = document.getElementById('academic-tools-display-area');
        const toolBlocks = document.querySelectorAll('.tool-block');
        const comingSoonMessage = document.querySelector('.coming-soon-message');

        toolBlocks.forEach(block => {
            block.addEventListener('click', function() {
                const tool = this.dataset.tool; // Get the data-tool attribute value
                if (tool === 'cgpa-calculator') {
                    loadToolContent('academic_modules/cgpa_calculator.php');
                } else if (tool === 'tuition-fee-calculator') {
                    loadToolContent('academic_modules/tuition_fee_calculator.php');
                } else {
                    alert('This tool is not yet implemented.');
                }
            });
        });

        function loadToolContent(url) {
            fetch(url)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok ' + response.statusText);
                    }
                    return response.text();
                })
                .then(html => {
                    displayArea.innerHTML = html; // Inject the content
                    // Remove academic-tools-grid class if calculator needs full width or different layout
                    displayArea.classList.remove('academic-tools-grid');

                    // Re-run any scripts included in the loaded HTML
                    // This is important because dynamically loaded HTML's script tags don't execute automatically.
                    const scripts = displayArea.querySelectorAll('script');
                    scripts.forEach(script => {
                        const newScript = document.createElement('script');
                        Array.from(script.attributes).forEach(attr => {
                            newScript.setAttribute(attr.name, attr.value);
                        });
                        newScript.textContent = script.textContent;
                        script.parentNode.replaceChild(newScript, script);
                    });

                    // Hide the "Coming Soon" message after a tool is loaded
                    if (comingSoonMessage) {
                        comingSoonMessage.style.display = 'none';
                    }

                    // Adjust header title
                    const headerTitle = document.querySelector('.search-bar h3');
                    if (headerTitle) {
                        if (url.includes('cgpa')) {
                            headerTitle.textContent = 'CGPA Calculator';
                        } else if (url.includes('tuition')) {
                            headerTitle.textContent = 'Tuition Fee Calculator';
                        } else {
                            headerTitle.textContent = 'Academic Tools';
                        }
                    }
                })
                .catch(error => {
                    console.error('Error loading academic tool:', error);
                    displayArea.innerHTML = '<p style="color: red;">Failed to load tool. Please try again.</p>';
                });
        }
    });
    </script>
<?php renderDashboardShellEnd(); ?>
