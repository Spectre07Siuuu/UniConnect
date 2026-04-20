<?php
// This file contains the Tuition Fee Calculator form HTML and its JavaScript.
// The PHP calculation logic has been moved to calculate_tuition_fee.php.
?>
<div class="calculator-container" id="tuition-fee-calculator-content">
    <h2>Tuition Fee Calculator</h2>

    <form method="post" id="tuitionFeeForm" class="calculator-form">
        <label for="total_credits_input">Total Credits Taken:</label>
        <input type="number" name="credit" id="total_credits_input" required>

        <label for="per_credit_fee">Per Credit Fee (Tk):</label>
            <select name="per_credit_fee" id="per_credit_fee_input" required>
                <option value="5000">5000 Tk</option>
                <option value="5500">5500 Tk</option>
                <option value="6000">6000 Tk</option>
                <option value="6500">6500 Tk</option>
            </select>    
        <label for="semester_fee_input">Semester Fee (Tk):</label>
        <select name="semester_fee" id="semester_fee_input" required>
                <option value="5000">5000 Tk</option>
                <option value="5500">5500 Tk</option>
                <option value="6000">6000 Tk</option>
                <option value="6500">6500 Tk</option>
            </select> 

        <label for="waver_select">Waiver:</label>
        <select name="waver" id="waver_select" required class="waver-select">
            <option value="0">Not Applicable</option>
            <option value="10">10%</option>
            <option value="25">25%</option>
            <option value="50">50%</option>
            <option value="100">100%</option>
        </select>

        <div class="form-actions btns">
            <button type="submit" name="calculate" class="action-button calculate-btn">Calculate</button>
            <button type="button" class="action-button reset-btn" onclick="document.getElementById('tuitionFeeForm').reset(); document.getElementById('installmentResult').innerHTML = '';">Reset</button>
        </div>
    </form>

    <div class="installment-result" id="installmentResult">
        </div>
</div>
<script>
    // JavaScript for form submission via AJAX
    const tuitionFeeForm = document.getElementById('tuitionFeeForm');
    if (tuitionFeeForm) {
        tuitionFeeForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const calculateBtn = this.querySelector('.calculate-btn');
            const originalBtnText = calculateBtn.innerHTML;
            calculateBtn.innerHTML = '<i class="mdi mdi-loading mdi-spin"></i> Calculating...';
            calculateBtn.disabled = true;

            // Path to the calculate_tuition_fee.php backend
            // It's relative to Admin-Dashboard/academic_tools.php (the page that loaded this module)
            fetch('academic_modules/calculate_tuition_fee.php', {
                method: 'POST',
                body: new FormData(this)
            })
            .then(res => {
                if (!res.ok) {
                    throw new Error('Network response was not ok ' + res.statusText);
                }
                return res.text(); // Expect HTML response
            })
            .then(data => {
                document.getElementById('installmentResult').innerHTML = data;
            })
            .catch(error => {
                console.error('Error during tuition calculation fetch:', error);
                document.getElementById('installmentResult').innerHTML = '<p style="color: red;">An error occurred during calculation: ' + error.message + '</p>';
            })
            .finally(() => {
                calculateBtn.innerHTML = originalBtnText;
                calculateBtn.disabled = false;
            });
        });
    }
</script>
