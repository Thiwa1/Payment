<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Schedule System</title>
    <style>
        :root {
            --primary-color: #007bff;
            --primary-hover: #0056b3;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --light-bg: #f8f9fa;
            --border-color: #dee2e6;
            --text-color: #343a40;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f6f9;
            color: var(--text-color);
            line-height: 1.6;
        }

        nav {
            background: #343a40;
            padding: 15px 20px;
            margin-bottom: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        nav a {
            margin-right: 20px;
            text-decoration: none;
            color: #rgba(255,255,255,0.8);
            font-weight: 500;
            transition: color 0.2s;
            color: #ddd;
        }

        nav a:hover, nav a.active {
            color: #fff;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            background: white;
            padding-bottom: 40px;
            min-height: 80vh;
            box-shadow: 0 0 15px rgba(0,0,0,0.05);
            border-radius: 8px;
            padding-top: 20px;
        }

        h1, h2, h3 { color: #2c3e50; margin-top: 0; }
        h1 { border-bottom: 2px solid var(--light-bg); padding-bottom: 10px; margin-bottom: 20px; }

        /* Tables */
        table {
            border-collapse: collapse;
            width: 100%;
            margin-top: 20px;
            margin-bottom: 20px;
            background: white;
            border-radius: 5px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }
        th {
            background-color: #e9ecef;
            font-weight: 600;
            color: #495057;
            text-transform: uppercase;
            font-size: 0.85rem;
        }
        tr:hover { background-color: #f1f3f5; }
        tr:last-child td { border-bottom: none; }

        /* Forms */
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: 500; }

        input[type="text"], input[type="number"], input[type="date"], input[type="file"], input[type="month"], select {
            width: 100%;
            padding: 10px;
            max-width: 400px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            box-sizing: border-box; /* Important for padding */
            font-size: 1rem;
        }

        input:focus, select:focus {
            border-color: #80bdff;
            outline: 0;
            box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
        }

        /* Buttons */
        button, .btn {
            padding: 10px 20px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            transition: background-color 0.2s;
            text-decoration: none;
            display: inline-block;
        }
        button:hover, .btn:hover { background: var(--primary-hover); }

        button[style*="background-color: #dc3545"] { background-color: var(--danger-color) !important; }
        button[style*="background-color: #dc3545"]:hover { background-color: #c82333 !important; }

        button[style*="background-color: #28a745"] { background-color: var(--success-color) !important; }
        button[style*="background-color: #28a745"]:hover { background-color: #218838 !important; }

        /* Alerts */
        .alert, .error {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .alert { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        /* Utilities */
        hr { border: 0; border-top: 1px solid var(--border-color); margin: 30px 0; }
        a { color: var(--primary-color); text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <nav>
        <a href="index.php">Home</a>
        <a href="employees.php">Employees</a>
        <a href="payments.php">Payments</a>
        <a href="paysheet.php">Paysheets</a>
        <a href="report.php">Reports</a>
        <a href="setup.php" style="float: right; margin-right: 0;">Status</a>
    </nav>
    <div class="container">
