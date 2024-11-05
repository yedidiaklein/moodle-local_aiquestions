<?php
namespace local_aiquiz;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../locallib.php');

class observer {
     
     
    public static function quiz_attempt_submitted(\mod_quiz\event\attempt_submitted $event) {
        $attemptid = $event->objectid;
        
        // Add loader HTML with animation and transparent background
        echo '
        <style>
            @keyframes pulse {
                0% { transform: scale(1); }
                50% { transform: scale(1.1); }
                100% { transform: scale(1); }
            }
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
            #loading-overlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background-color: transparent;
                z-index: 9999;
                backdrop-filter: blur(5px);
            }
            .loader-content {
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                text-align: center;
                color: white;
                background-color: rgba(0, 0, 0, 0.7);
                padding: 30px;
                border-radius: 15px;
                box-shadow: 0 0 20px rgba(0,0,0,0.5);
            }
            .loader-spinner {
                width: 50px;
                height: 50px;
                border: 5px solid #f3f3f3;
                border-top: 5px solid #4caf50;
                border-radius: 50%;
                animation: spin 1s linear infinite;
                margin: 0 auto 20px;
            }
            .loader-text {
                font-size: 18px;
                margin-bottom: 15px;
                animation: pulse 2s infinite;
            }
            .progress-container {
                width: 300px;
                background-color: #ccc;
                border-radius: 10px;
                overflow: hidden;
                margin-top: 10px;
            }
            #progress-bar {
                width: 0%;
                height: 20px;
                background-color: #4caf50;
                border-radius: 10px;
                transition: width 0.3s ease;
            }
        </style>
        <div id="loading-overlay">
            <div class="loader-content">
                <div class="loader-spinner"></div>
                <p class="loader-text">Please wait. Quiz evaluation in progress...</p>
                <div class="progress-container">
                    <div id="progress-bar"></div>
                </div>
            </div>
        </div>';
        
        // Flush output buffer to ensure loader is displayed
        flush();
        ob_flush();

        // Process the attempt evaluation
        aiquiz_evaluate_attempt($attemptid, true);
    }


   
    public static function quiz_question_updated(\core\event\question_updated $event) {
        global $DB, $USER, $CFG;
        
        // Add more detailed logging
        $logFilePath = $CFG->dataroot . '/aiquiz_debug.log';
        error_log("Observer triggered for question " . $event->objectid . " at " . date('Y-m-d H:i:s') . PHP_EOL, 3, $logFilePath);
        
        try {
            $questionid = $event->objectid;
            $result = aiquiz_question_updated($questionid);
            error_log("Question update result: " . ($result ? 'success' : 'failed') . PHP_EOL, 3, $logFilePath);
        } catch (Exception $e) {
            error_log("Error in observer: " . $e->getMessage() . PHP_EOL, 3, $logFilePath);
        }
    }


    private static function get_loader_javascript() {
        return "
        <script>
        (function() {
            // Add loader styles
            var style = document.createElement('style');
            style.textContent = `
                #aiquiz-loader {
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: rgba(0, 0, 0, 0.7);
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    z-index: 9999;
                }
                .aiquiz-loader-content {
                    background: white;
                    padding: 20px;
                    border-radius: 8px;
                    text-align: center;
                    min-width: 300px;
                }
                .aiquiz-progress {
                    width: 100%;
                    height: 20px;
                    background: #f0f0f0;
                    border-radius: 10px;
                    overflow: hidden;
                    margin: 10px 0;
                }
                .aiquiz-progress-bar {
                    width: 0%;
                    height: 100%;
                    background: #4CAF50;
                    transition: width 0.3s ease;
                }
            `;
            document.head.appendChild(style);

            // Create loader HTML
            var loader = document.createElement('div');
            loader.id = 'aiquiz-loader';
            loader.innerHTML = `
                <div class='aiquiz-loader-content'>
                    <h4>Quiz Evaluation in Progress</h4>
                    <div class='aiquiz-progress'>
                        <div class='aiquiz-progress-bar' id='aiquiz-progress'></div>
                    </div>
                    <p id='aiquiz-status'>Initializing evaluation...</p>
                </div>
            `;
            document.body.appendChild(loader);

            // Define update function globally
            window.updateAIQuizProgress = function(percent, message) {
                var progressBar = document.getElementById('aiquiz-progress');
                var status = document.getElementById('aiquiz-status');
                if (progressBar) {
                    progressBar.style.width = percent + '%';
                }
                if (status && message) {
                    status.textContent = message;
                }
            };

            // Start progress animation
            var progress = 0;
            var messages = [
                'Initializing evaluation...',
                'Processing responses...',
                'Analyzing answers...',
                'Calculating results...',
                'Finalizing evaluation...'
            ];

            var progressInterval = setInterval(function() {
                if (progress < 90) {
                    progress += 5;
                    window.updateAIQuizProgress(
                        progress, 
                        messages[Math.floor((progress/90) * (messages.length-1))]
                    );
                }
            }, 500);

            // Cleanup after max time
            setTimeout(function() {
                clearInterval(progressInterval);
                var loader = document.getElementById('aiquiz-loader');
                if (loader) {
                    loader.remove();
                }
            }, 30000); // 30 seconds max
        })();
        </script>
        ";
    }
 
}