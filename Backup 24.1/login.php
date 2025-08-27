<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login | Visvis Travel</title>
  <link rel="stylesheet" href="login.css">
  <link rel="stylesheet" href="login_responsive.css">
  <style>
    /* Error message style */
    #errorMessage {
      display: none;
      background: #e74c3c;
      color: #fff;
      padding: 10px;
      border-radius: 6px;
      text-align: center;
      margin-bottom: 10px;
      font-weight: bold;
    }
  </style>
</head>

<body>
  <!-- LEFT SIDE CONTENT -->
  <div class="left-side">
    <div>
      <h1 class="text-6xl">Welcome Back!</h1>
      <nav>
        <a onclick="toggleContactPanel()">Contact us</a>
      </nav>

      <!-- Contact Panel BELOW "Contact us" -->
      <div class="contact-box" id="contactBox">
        <h3>Contact Us</h3>
        <ul>
          <li>📧 info@visvistravel.ph</li>
          <li>📞 09757000655</li>
        </ul>
      </div>
    </div>
  </div>

  <!-- RIGHT SIDE: Login form with blur effect -->
  <div class="right-side">
    <form class="login-form" action="login_admin.php" method="POST">
      <h2>Log in</h2>

      <!-- Error message -->
      <div id="errorMessage">
        Wrong ID or Password. Please try again.
      </div>

      <!-- Role selection -->
      <select name="role" id="role" required>
        <option value="agent">Agent</option>
        <option value="admin">Admin</option>
      </select>

      <!-- ID input -->
      <input type="text" id="username" name="user_id" placeholder="Login ID" 
             value="<?php echo isset($_COOKIE['remember_user']) ? htmlspecialchars($_COOKIE['remember_user']) : ''; ?>" 
             required>

      <!-- Password input -->
      <input type="password" id="password" name="password" placeholder="Enter your password" required>

      <!-- Remember me checkbox -->
      <label>
        <input type="checkbox" name="remember" 
          <?php if (isset($_COOKIE['remember_user'])) echo "checked"; ?>> Remember Me
      </label>

      <!-- Submit button -->
      <button type="submit" class="login-btn">Log in</button>

      <p>OR</p>

      <!-- Sign-up button -->
      <button type="button" class="signup-btn" onclick="window.location.href='agent_signup.html'">Sign up</button>
    </form>
  </div>

  <script>
    // Show error if redirected with ?error=1
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has("error")) {
      document.getElementById("errorMessage").style.display = "block";
    }

    function toggleContactPanel() {
      const box = document.getElementById("contactBox");
      box.classList.toggle("show");
    }
  </script>
</body>
</html>
