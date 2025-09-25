<?php
// This is the 404 template file, renamed from 404_template.html
// No PHP logic is strictly required here, but keeping it as a .php file is consistent.
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 Not Found</title>
    <link rel="stylesheet" href="./src/css/all.min.css">
    <style>
    body {
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        margin: 0;
        background-color: #f0f2f5;
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
        color: #1c1c1e;
        text-align: center;
    }

    .container {
        background-color: #ffffff;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        width: 90%;
        max-width: 450px;
        padding: 40px 30px;
        box-sizing: border-box;
    }

    .icon-404 {
        font-size: 5rem;
        margin-bottom: 20px;
        color: #ff453a;
    }

    h1 {
        font-size: 2rem;
        margin: 10px 0;
    }

    p {
        color: #636366;
        line-height: 1.6;
    }

    .home-btn {
        display: inline-block;
        margin-top: 20px;
        background-color: #007aff;
        color: white;
        text-decoration: none;
        padding: 12px 25px;
        border-radius: 10px;
        font-weight: 500;
        transition: background-color 0.2s ease;
    }

    .home-btn:hover {
        background-color: #005ecb;
    }
    </style>
</head>

<body>
    <div class="container">
        <i class="fas fa-exclamation-triangle icon-404"></i>
        <h1>Link Not Found</h1>
        <p>The share link you are trying to access is invalid, has been removed, or has expired. Please check the link
            and try again.</p>
        <a href="<?php echo defined('BASE_URL') ? BASE_URL : './'; ?>" class="home-btn">Go to Homepage</a>
    </div>
</body>

</html>