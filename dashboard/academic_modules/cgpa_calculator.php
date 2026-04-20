<?php
// This file contains only the calculator form and its JS,
// designed to be included/loaded into the main dashboard content area.
// It DOES NOT need to include check_auth.php or db_connect.php
// because the parent academic_tools.php has already handled those.
?>
<div class="calculator-container" id="cgpa-calculator-content">
    <h2>Combined GPA & CGPA Calculator</h2>
    <form id="combinedForm" class="calculator-form">
        <label for="current_cgpa">Current CGPA:</label>
        <input type="number" step="0.01" name="current_cgpa" id="current_cgpa" required>

        <label for="completed_credits">Total Completed Credits:</label>
        <input type="number" name="completed_credits" id="completed_credits" required>

        <div id="subjects">
            <div class="subject-entry">
                <h3>1st Subject</h3>
                
                <label for="grade">Grade (Points)</label>
                    <select name="grade[]" class="subject-entry" required>
                        <option value="4.00">A (Plain) - 4.00</option>
                        <option value="3.67">A- (Minus) - 3.67</option>
                        <option value="3.33">B+ (Plus) - 3.33</option>
                        <option value="3.00">B (Plain) - 3.00</option>
                        <option value="2.67">B- (Minus) - 2.67</option>
                        <option value="2.33">C+ (Plus) - 2.33</option>
                        <option value="2.00">C (Plain) - 2.00</option>
                        <option value="1.67">C- (Minus) - 1.67</option>
                        <option value="1.33">D+ (Plus) - 1.33</option>
                        <option value="1.00">D (Plain) - 1.00</option>
                        <option value="0.00">F - 0.00</option>
                    </select>
                
                <label for="credit">Credit Hours:</label>
                    <select name="credit[]" class="subject-entry" required>
                        <option value="3">3</option>
                        <option value="2">2</option>
                        <option value="1">1</option>
                    </select>    
            </div>
        </div>

        <div class="form-actions">
            <button type="button" class="action-button add-subject-btn">Add Subject</button>
            <button type="button" class="action-button delete-subject-btn">Delete Subject</button>
            <button type="submit" class="action-button calculate-btn">Calculate</button>
        </div>
    </form>

    <div id="combinedResult" class="result-display"></div>
</div>

<script>
// These scripts will run when the content is loaded/injected into the DOM

let subjectCount = 1; // Start from 1, as one subject is initially present

function addSubjectEntry() {
    subjectCount++;
    const suffix = (n) => {
        if (n > 3 && n < 21) return 'th'; // covers 11th to 20th
        switch (n % 10) {
            case 1:  return 'st';
            case 2:  return 'nd';
            case 3:  return 'rd';
            default: return 'th';
        }
    };
    const div = document.createElement('div');
    div.className = 'subject-entry'; // Use a distinct class
    div.innerHTML = `<hr><h3>${subjectCount}${suffix(subjectCount)} Subject</h3>
        
        <select name="grade[]" class="subject-entry" required>
                        <option value="4.00">A (Plain) - 4.00</option>
                        <option value="3.67">A- (Minus) - 3.67</option>
                        <option value="3.33">B+ (Plus) - 3.33</option>
                        <option value="3.00">B (Plain) - 3.00</option>
                        <option value="2.67">B- (Minus) - 2.67</option>
                        <option value="2.33">C+ (Plus) - 2.33</option>
                        <option value="2.00">C (Plain) - 2.00</option>
                        <option value="1.67">C- (Minus) - 1.67</option>
                        <option value="1.33">D+ (Plus) - 1.33</option>
                        <option value="1.00">D (Plain) - 1.00</option>
                        <option value="0.00">F - 0.00</option>
                    </select>
                    <select name="credit[]" class="subject-entry" required>
                        <option value="3">3</option>
                        <option value="2">2</option>
                        <option value="1">1</option>
                    </select>`;
    document.getElementById('subjects').appendChild(div);
}

function deleteSubjectEntry() {
    const container = document.getElementById('subjects');
    if (container.children.length > 1) { // Ensure at least one subject remains
        container.removeChild(container.lastChild);
        subjectCount--;
    }
}

// Event listener for the combined form
const combinedForm = document.getElementById('combinedForm');
if (combinedForm) {
    combinedForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const calculateBtn = this.querySelector('.calculate-btn');
        const originalBtnText = calculateBtn.innerHTML;
        calculateBtn.innerHTML = '<i class="mdi mdi-loading mdi-spin"></i> Calculating...'; // Add loading spinner
        calculateBtn.disabled = true;

        // Path to the calculate.php backend for CGPA calculation
        // It's relative to Admin-Dashboard/academic_tools.php (the page that loaded this module)
        fetch('academic_modules/calculate.php', {
            method: 'POST',
            body: new FormData(this)
        })
        .then(res => {
            if (!res.ok) {
                throw new Error('Network response was not ok ' + res.statusText);
            }
            return res.text(); // Expecting plain text or HTML response
        })
        .then(data => {
            document.getElementById('combinedResult').innerHTML = data;
        })
        .catch(error => {
            console.error('Error during CGPA calculation fetch:', error);
            document.getElementById('combinedResult').innerHTML = '<span style="color: red;">An error occurred during calculation: ' + error.message + '</span>';
        })
        .finally(() => {
            calculateBtn.innerHTML = originalBtnText; // Restore button text
            calculateBtn.disabled = false;
        });
    });

    // Attach event listeners to Add/Delete Subject buttons using their classes
    const addSubjectBtn = combinedForm.querySelector('.add-subject-btn');
    if (addSubjectBtn) addSubjectBtn.onclick = addSubjectEntry;
    
    const deleteSubjectBtn = combinedForm.querySelector('.delete-subject-btn');
    if (deleteSubjectBtn) deleteSubjectBtn.onclick = deleteSubjectEntry;
}

// The original index.html also had forms for cgpaToPercent and percentToCgpa.
// These would need their own PHP backend scripts and separate partial files if you want to include them.
// For now, we're only including the main combined calculator.
</script>
