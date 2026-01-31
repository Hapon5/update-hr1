document.getElementById('sendCodeBtn').addEventListener('click', function () {
    const email = document.getElementById('emailInput').value;

    if (!email) {
        alert("Please enter your email address first.");
        return;
    }

    // Disable the button to prevent re-sending
    this.disabled = true;
    this.innerText = "Sending...";

    // Send the verification code to the provided email
    fetch('send_code.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `email=${encodeURIComponent(email)}`
    })
    .then(response => response.text())
    .then(data => {
        document.getElementById('codeStatus').innerText = data;
        
        // Show the code input field and label visually
        document.getElementById('codeInput').style.display = 'block';
        document.querySelector('label[for="codeInput"]').style.display = 'block';

        // Show the resend code button
        document.getElementById('resendCodeBtn').style.display = 'inline-block';

        // Change button text to indicate code has been sent
        document.getElementById('sendCodeBtn').innerText = 'Code Sent';
        document.getElementById('sendCodeBtn').disabled = true; // Disable button after sending

        // Hide the "Verify Code" button after sending the code
        document.getElementById('sendCodeBtn').style.display = 'none';

        // Start the 30-second timer for the resend button
        startResendTimer();
    })
    .catch(error => {
        console.error("Error:", error);
        document.getElementById('codeStatus').innerText = "Failed to send code.";
    });
});

// Function to handle the resend code timer
function startResendTimer() {
    let countdown = 30;
    const resendButton = document.getElementById('resendCodeBtn');
    
    // Disable the button initially
    resendButton.disabled = true;
    resendButton.innerText = `Resend Code (${countdown}s)`;

    // Update the countdown every second
    const timer = setInterval(function() {
        countdown--;
        resendButton.innerText = `Resend Code (${countdown}s)`;

        if (countdown <= 0) {
            clearInterval(timer); // Stop the timer
            resendButton.disabled = false; // Enable the button after 30 seconds
            resendButton.innerText = "Resend Code"; // Reset button text
        }
    }, 1000);
}

// Add an event listener to the resend code button after the countdown finishes
document.getElementById('resendCodeBtn').addEventListener('click', function () {
    const email = document.getElementById('emailInput').value;

    if (!email) {
        alert("Please enter your email address first.");
        return;
    }

    // Disable the button again to prevent multiple clicks while sending
    this.disabled = true;
    this.innerText = "Sending...";

    // Resend the verification code to the provided email
    fetch('send_code.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `email=${encodeURIComponent(email)}`
    })
    .then(response => response.text())
    .then(data => {
        document.getElementById('codeStatus').innerText = data;
        
        // Start the countdown again after resend
        startResendTimer();
    })
    .catch(error => {
        console.error("Error:", error);
        document.getElementById('codeStatus').innerText = "Failed to resend code.";
    });
    
});
