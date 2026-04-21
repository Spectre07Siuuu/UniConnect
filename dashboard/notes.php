<?php
require_once 'check_auth.php';
require_once __DIR__ . '/includes/bootstrap.php';

extract(bootstrapDashboardPage(), EXTR_SKIP);

// Fetch subjects from DB (available_courses) so notes and courses stay in sync
$subjects = [];
try {
    $stmt_subjects = $pdo->prepare("SELECT course_name FROM available_courses ORDER BY course_name");
    $stmt_subjects->execute();
    $subjects = $stmt_subjects->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    error_log("Error fetching subjects for notes page: " . $e->getMessage());
    // Fall back to an empty list; the "All Subjects" option will still work
    $subjects = [];
}

$exam_types = ['midterm', 'final', 'quiz', 'assignment', 'other'];
?>
<?php renderDashboardHead('Notes - UniConnect', [
    'css/notes.css',
    'css/modals.css',
    'css/responsive.css',
]); ?>
<?php renderDashboardShellStart('notes', 'Notes &amp; Study Materials', $coins, $profile_picture, $user_student_id); ?>

            <div class="content-main single-column-layout">
                <div class="content-left">
                    <div class="notes-page-grid">
                        <div class="card filter-notes-card">
                            <h3>Find Notes</h3>
                            <form id="filterNotesForm">
                                <div class="form-group">
                                    <label for="searchSubject">Subject:</label>
                                    <select id="searchSubject" name="subject">
                                        <option value="">All Subjects</option>
                                        <?php foreach ($subjects as $sub): ?>
                                            <option value="<?= htmlspecialchars($sub) ?>"><?= htmlspecialchars($sub) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="searchExamType">Exam Type:</label>
                                    <select id="searchExamType" name="exam_type">
                                        <option value="">All</option>
                                        <?php foreach ($exam_types as $type): ?>
                                            <option value="<?= htmlspecialchars(ucfirst($type)) ?>">
                                                <?= htmlspecialchars(ucfirst($type)) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <button type="submit" class="action-button filter-notes-btn">Search Notes</button>
                            </form>
                        </div>

                        <div class="card create-note-card">
                            <h3>Upload New Note</h3>
                            <form id="uploadNoteForm" enctype="multipart/form-data">
                                <!-- Row 1: Subject + Exam Type -->
                                <div class="form-row two-cols">
                                    <div class="form-group half-width">
                                        <label for="noteSubject">Subject:</label>
                                        <select id="noteSubject" name="subject" required>
                                            <option value="">Select Subject</option>
                                            <?php foreach ($subjects as $sub): ?>
                                                <option value="<?= htmlspecialchars($sub) ?>"><?= htmlspecialchars($sub) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group half-width">
                                        <label for="noteExamType">Exam Type:</label>
                                        <select id="noteExamType" name="exam_type" required>
                                            <option value="">Select</option>
                                            <?php foreach ($exam_types as $type): ?>
                                                <option value="<?= htmlspecialchars(strtolower($type)) ?>">
                                                    <?= htmlspecialchars(ucfirst($type)) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <!-- Row 2: Description -->
                                <div class="form-group">
                                    <label for="noteDescription">Description:</label>
                                    <textarea id="noteDescription" name="description"
                                        placeholder="Optional brief description of the notes" rows="3"></textarea>
                                </div>

                                <!-- Row 3: Upload File + File Name Display + Upload Button -->
                                <div class="form-row file-upload-group">
                                    <div class="form-group">
                                        <label for="noteFile" class="custom-file-upload">
                                            <i class="mdi mdi-upload"></i> Upload file
                                        </label>
                                        <input type="file" id="noteFile" name="note_file"
                                            accept=".pdf,.doc,.docx,.ppt,.pptx,.txt" required style="display: none;">
                                        <span class="file-name-display" id="fileNameDisplay">No file chosen</span>
                                    </div>
                                    <button type="submit" class="action-button upload-note-btn">Upload Note</button>
                                </div>
                            </form>
                        </div>

                        <div class="notes-feed-section">
                            <h3>Available Notes</h3>
                            <div id="notesFeed" class="notes-feed">
                                <p class="no-notes-message">Loading notes...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
    <div id="reviewModal" class="review-modal">
        <div class="review-modal-content">
            <span class="close-button">&times;</span>
            <h3>Rate This Note</h3>
            <p class="modal-info">Your feedback helps others and rewards good notes!</p>
            <form id="reviewForm">
                <input type="hidden" id="reviewNoteId" name="note_id">
                <div class="review-categories">
                    <label>
                        <input type="radio" name="rating" value="very_helpful" required> Very Helpful (+3 points)
                    </label>
                    <label>
                        <input type="radio" name="rating" value="average"> Average (+1 point)
                    </label>
                    <label>
                        <input type="radio" name="rating" value="not_helpful"> Not Helpful (+0 points)
                    </label>
                </div>
                <div class="form-group">
                    <label for="reviewText">Comments (Optional):</label>
                    <textarea id="reviewText" name="review_text" placeholder="Share your thoughts..."
                        rows="3"></textarea>
                </div>
                <button type="submit" class="action-button submit-review-btn">Submit Review</button>
            </form>
            <p id="reviewErrorMessage" class="message-error" style="display: none;"></p>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const notesFeed = document.getElementById('notesFeed');
            const uploadNoteForm = document.getElementById('uploadNoteForm');
            const filterNotesForm = document.getElementById('filterNotesForm');
            const fileNameDisplay = document.getElementById('fileNameDisplay');
            const noteFile = document.getElementById('noteFile');

            const reviewModal = document.getElementById('reviewModal');
            const closeReviewModalBtn = reviewModal.querySelector('.close-button');
            const reviewForm = document.getElementById('reviewForm');
            const reviewNoteIdInput = document.getElementById('reviewNoteId');
            const reviewErrorMessage = document.getElementById('reviewErrorMessage');
            let currentNotePosterId = null; // To store the ID of the note poster for messaging

            // Function to load notes (with optional filters)
            function loadNotes(subject = '', exam_type = '') {
                notesFeed.innerHTML = '<p class="no-notes-message"><i class="mdi mdi-loading mdi-spin"></i> Loading notes...</p>';
                fetch(`notes/fetch_notes.php?subject=${encodeURIComponent(subject)}&exam_type=${encodeURIComponent(exam_type)}`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok ' + response.statusText);
                        }
                        return response.text();
                    })
                    .then(html => {
                        notesFeed.innerHTML = html;
                        attachNotesEventListeners(); // Attach event listeners after content loads
                    })
                    .catch(error => {
                        console.error('Error fetching notes:', error);
                        notesFeed.innerHTML = '<p class="message-error">Failed to load notes. Please try again.</p>';
                    });
            }

            // Function to attach event listeners to dynamically loaded notes
            function attachNotesEventListeners() {
                // Download/Seek Help Button
                document.querySelectorAll('.download-btn').forEach(button => {
                    button.removeEventListener('click', handleDownloadClick); // Prevent duplicates
                    button.addEventListener('click', handleDownloadClick);
                });

                // Message Button
                document.querySelectorAll('.message-note-btn').forEach(button => {
                    button.removeEventListener('click', handleMessageClick); // Prevent duplicates
                    button.addEventListener('click', handleMessageClick);
                });

                // Review Button
                document.querySelectorAll('.review-note-btn').forEach(button => {
                    button.removeEventListener('click', handleReviewClick); // Prevent duplicates
                    button.addEventListener('click', handleReviewClick);
                });
            }

            // Handle Download/Seek Help
            function handleDownloadClick(event) {
                event.preventDefault();
                const button = event.currentTarget;
                const noteId = button.dataset.noteId;
                const noteFilePath = button.dataset.filePath; // Path needed for actual download
                const notePosterId = button.dataset.notePosterId; // User who posted the note

                if (!noteId || !noteFilePath || !notePosterId) {
                    alert('Note information missing.');
                    return;
                }

                // Prevent self-download coin deduction if it's the poster's own note
                // User can still click and trigger download for their own notes without coin deduction
                const currentLoggedInUserId = document.getElementById('currentUserId').value;
                if (currentLoggedInUserId !== notePosterId) {
                    if (!confirm('Downloading this note will deduct 2 coins from your balance. Do you wish to proceed?')) {
                        return;
                    }
                } else {
                    // If it's the user's own note, no deduction, just proceed to download directly
                    window.location.href = `notes/download.php?file=${encodeURIComponent(noteFilePath)}`;
                    return; // Exit here, no need for AJAX call for own notes
                }

                const originalBtnText = button.innerHTML;
                button.innerHTML = '<i class="mdi mdi-loading mdi-spin"></i> Downloading...';
                button.disabled = true;

                const formData = new FormData();
                formData.append('note_id', noteId);
                formData.append('note_poster_id', notePosterId); // Send poster ID to credit reputation

                fetch('notes/download_note.php', { // New backend endpoint for download/deduction
                    method: 'POST',
                    body: formData
                })
                    .then(response => {
                        if (!response.ok) {
                            return response.json().then(err => { throw new Error(err.message || 'Network response not ok'); });
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            // Trigger actual file download
                            window.location.href = `notes/download.php?file=${encodeURIComponent(noteFilePath)}`; // Separate download script
                            alert(data.message + '\nYour new coin balance: ' + data.new_coin_balance);
                            // Optionally update coin balance in header
                            const coinBalanceSpan = document.querySelector('.header .coin-balance span');
                            if (coinBalanceSpan) coinBalanceSpan.textContent = data.new_coin_balance;

                            // Reload notes to update download count and fetch notifications
                            loadNotes(filterNotesForm.elements.subject.value, filterNotesForm.elements.exam_type.value);
                            // fetchNotifications(); // This is handled by notifications.js
                        } else {
                            alert('Download failed: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Download error:', error);
                        alert('An error occurred during download: ' + error.message);
                    })
                    .finally(() => {
                        button.innerHTML = originalBtnText;
                        button.disabled = false;
                    });
            }

            // Handle Message button click
            function handleMessageClick(event) {
                event.preventDefault();
                const button = event.currentTarget;
                const notePosterId = button.dataset.notePosterId;
                if (notePosterId) {
                    window.location.href = `chat/chat.php?user_id=${notePosterId}`; // Redirect to individual chat
                } else {
                    alert('Could not identify note poster.');
                }
            }

            // Handle Review button click
            function handleReviewClick(event) {
                event.preventDefault();
                const button = event.currentTarget;
                const noteId = button.dataset.noteId;
                const notePosterId = button.dataset.notePosterId; // To prevent self-review

                if (!noteId || !notePosterId) {
                    alert('Note information missing for review.');
                    return;
                }

                // Get the current logged-in user's ID
                const currentLoggedInUserId = document.getElementById('currentUserId').value;
                if (notePosterId === currentLoggedInUserId) {
                    alert('You cannot review your own note.');
                    return;
                }

                reviewNoteIdInput.value = noteId;
                reviewErrorMessage.style.display = 'none';
                reviewErrorMessage.textContent = '';
                reviewForm.reset(); // Clear previous selection
                reviewModal.style.display = 'flex'; // Show modal
            }

            // Close Review Modal
            if (closeReviewModalBtn) {
                closeReviewModalBtn.addEventListener('click', () => {
                    reviewModal.style.display = 'none';
                });
            }
            window.addEventListener('click', (event) => {
                if (event.target === reviewModal) {
                    reviewModal.style.display = 'none';
                }
            });

            // Handle Review Form Submission
            reviewForm.addEventListener('submit', function (event) {
                event.preventDefault();
                const submitBtn = this.querySelector('.submit-review-btn');
                const originalBtnHtml = submitBtn.innerHTML;

                submitBtn.innerHTML = '<i class="mdi mdi-loading mdi-spin"></i> Submitting...';
                submitBtn.disabled = true;

                const formData = new FormData(this);
                // The note_id is already in the hidden input

                fetch('notes/process_note_review.php', { // New backend endpoint for review
                    method: 'POST',
                    body: formData
                })
                    .then(response => {
                        if (!response.ok) {
                            return response.json().then(err => { throw new Error(err.message || 'Network response not ok'); });
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            alert(data.message);
                            reviewModal.style.display = 'none';
                            // Optionally update reputation points in header/UI for the note poster if displayed
                            // Or just refresh notes list to reflect any changes if review status is shown
                            loadNotes(filterNotesForm.elements.subject.value, filterNotesForm.elements.exam_type.value);
                            // fetchNotifications(); // This is handled by notifications.js
                        } else {
                            reviewErrorMessage.textContent = data.message;
                            reviewErrorMessage.style.display = 'block';
                        }
                    })
                    .catch(error => {
                        console.error('Review submission error:', error);
                        reviewErrorMessage.textContent = 'An error occurred during review submission: ' + error.message;
                        reviewErrorMessage.style.display = 'block';
                    })
                    .finally(() => {
                        submitBtn.innerHTML = originalBtnHtml;
                        submitBtn.disabled = false;
                    });
            });

            // Handle file name display for upload form
            if (noteFile) {
                noteFile.addEventListener('change', function () {
                    if (this.files.length > 0) {
                        fileNameDisplay.textContent = this.files[0].name;
                    } else {
                        fileNameDisplay.textContent = 'No file chosen';
                    }
                });
            }

            // Handle Upload Note Form Submission
            if (uploadNoteForm) {
                uploadNoteForm.addEventListener('submit', function (event) {
                    event.preventDefault();
                    const submitBtn = this.querySelector('.upload-note-btn');
                    const originalBtnText = submitBtn.innerHTML;

                    submitBtn.innerHTML = '<i class="mdi mdi-loading mdi-spin"></i> Uploading...';
                    submitBtn.disabled = true;

                    const formData = new FormData(this);

                    fetch('notes/process_note_post.php', { // New backend endpoint for note post
                        method: 'POST',
                        body: formData
                    })
                        .then(response => {
                            if (!response.ok) {
                                return response.json().then(err => { throw new Error(err.message || 'Network response not ok'); });
                            }
                            return response.json();
                        })
                        .then(data => {
                            if (data.success) {
                                alert(data.message + '\nYour new coin balance: ' + data.new_coin_balance);
                                uploadNoteForm.reset(); // Clear form
                                fileNameDisplay.textContent = 'No file chosen'; // Reset file display
                                // Optionally update coin balance in header
                                const coinBalanceSpan = document.querySelector('.header .coin-balance span');
                                if (coinBalanceSpan) coinBalanceSpan.textContent = data.new_coin_balance;

                                loadNotes(); // Reload notes list to show new post
                                // fetchNotifications(); // This is handled by notifications.js
                            } else {
                                alert('Upload failed: ' + data.message);
                            }
                        })
                        .catch(error => {
                            console.error('Upload error:', error);
                            alert('An error occurred during upload: ' + error.message);
                        })
                        .finally(() => {
                            submitBtn.innerHTML = originalBtnText;
                            submitBtn.disabled = false;
                        });
                });
            }

            // Handle Filter Notes Form Submission
            if (filterNotesForm) {
                filterNotesForm.addEventListener('submit', function (event) {
                    event.preventDefault();
                    const subject = this.elements.subject.value;
                    const exam_type = this.elements.exam_type.value;
                    loadNotes(subject, exam_type);
                });
            }

            // Initial load of notes
            loadNotes();
        });
    </script>
<?php renderDashboardShellEnd(); ?>
