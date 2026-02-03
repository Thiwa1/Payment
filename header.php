<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Schedule System</title>
    <style>
        body { font-family: sans-serif; margin: 20px; }
        nav { background: #eee; padding: 10px; margin-bottom: 20px; }
        nav a { margin-right: 15px; text-decoration: none; color: #333; font-weight: bold; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background: #f4f4f4; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; }
        input[type="text"], input[type="number"], input[type="date"] { width: 100%; padding: 8px; max-width: 400px; }
        button { padding: 10px 15px; background: #007bff; color: white; border: none; cursor: pointer; }
        button:hover { background: #0056b3; }
        .alert { padding: 10px; background: #d4edda; color: #155724; margin-bottom: 15px; border: 1px solid #c3e6cb; }
        .error { padding: 10px; background: #f8d7da; color: #721c24; margin-bottom: 15px; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>
    <nav>
        <a href="index.php">Home</a>
        <a href="employees.php">Employees</a>
        <a href="payments.php">Payments</a>
        <a href="paysheet.php">Paysheets</a>
        <a href="report.php">Reports</a>
    </nav>
    <div class="container">
