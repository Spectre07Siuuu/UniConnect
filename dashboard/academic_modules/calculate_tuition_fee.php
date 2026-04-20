<?php
// This file is a backend endpoint to process Tuition Fee Calculator submissions.
// It only outputs the HTML for the result, no full page or form.
// It does not interact with the database or user session data directly for its calculation logic.

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate inputs
    $credit = filter_var($_POST['credit'] ?? 0, FILTER_VALIDATE_FLOAT);
    $credit_fee = filter_var($_POST['per_credit_fee'] ?? 0, FILTER_VALIDATE_FLOAT);
    $semester_fee = filter_var($_POST['semester_fee'] ?? 0, FILTER_VALIDATE_FLOAT);
    $waver = filter_var($_POST['waver'] ?? 0, FILTER_VALIDATE_FLOAT);

    // Basic validation, ensure all are positive numbers
    if ($credit === false || $credit < 0 ||
        $credit_fee === false || $credit_fee < 0 ||
        $semester_fee === false || $semester_fee < 0 ||
        $waver === false || $waver < 0 || $waver > 100) {
        echo "<p style='color: red;'>Invalid input. Please enter positive numbers.</p>";
    } else {
        $tuition_fee = $credit * $credit_fee;
        $discounted_tuition = $tuition_fee * (1 - ($waver / 100));
        $total_payment = $discounted_tuition + $semester_fee;

        // Calculate installments (as per previous logic - cumulative percentages)
        $installment_1 = round($total_payment * 0.4); // 40%
        $installment_2 = round($total_payment * 0.7-$installment_1); // 70% cumulative
        $installment_3 = round($total_payment-($installment_1+$installment_2));     // 100% cumulative

        /*
        $installment_1 = round($total_payment * 0.4); // 40%
        $installment_2 = round($total_payment * 0.7); // 70% cumulative
        $installment_3 = round($total_payment);     // 100% cumulative
        
        */

        // Output the result HTML

        

        echo "<h3>Total Payment: " . number_format($total_payment, 2) . " Tk</h3>";
        echo "<p><strong>Note:</strong> If you miss any installment due date, a <strong>৳500</strong> fine will be added.</p>";
        echo" <style>
        table {
        border-collapse: collapse;
        width: 100%;
        }
        th, td {
        padding: 8px 12px;
        border: 1px solid #ccc;
        text-align: center;
        }
        </style>";

        echo "<table>";
        echo "<tr><th>Installment</th><th>Percentage</th><th>Amount</th><th>With Fine (Optional)</th></tr>";
        echo "<tr><td>1st</td><td>40%</td><td>" . number_format($installment_1) . " Tk</td><td>" . number_format($installment_1 + 500) . " Tk</td></tr>";
        echo "<tr><td>2nd</td><td>70%</td><td>" . number_format($installment_2) . " Tk</td><td>" . number_format($installment_2 + 500) . " Tk</td></tr>";
        echo "<tr><td>3rd</td><td>100%</td><td>" . number_format($installment_3) . " Tk</td><td>" . number_format($installment_3 + 500) . " Tk</td></tr>";
        echo "</table>";
        echo "<p style='margin-top:15px; color: #555;'>Installments are broken down by percentage of total fee. Fines are shown as optional if paid late.</p>
";
    }
} else {
    // If accessed directly without POST data, or with GET request
    echo "<p style='color: red;'>Invalid request method.</p>";
}
?>
