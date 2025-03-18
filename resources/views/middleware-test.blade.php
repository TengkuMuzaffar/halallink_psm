<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Middleware Test Suite</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        pre {
            background-color: #f5f5f5;
            padding: 10px;
            border-radius: 5px;
            max-height: 300px;
            overflow: auto;
        }
        .test-card {
            margin-bottom: 20px;
        }
        .test-result {
            min-height: 300px;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h1>Middleware Test Suite</h1>
        <p class="lead">Use this page to test your middleware components</p>
        
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">Authentication</div>
                    <div class="card-body">
                        <form id="login-form">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Login</button>
                            <button type="button" id="logout-btn" class="btn btn-danger">Logout</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">Authentication Status</div>
                    <div class="card-body">
                        <div id="auth-status">Not authenticated</div>
                        <pre id="user-info">No user information</pre>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-3">
                <div class="list-group" id="test-list">
                    <a href="#" class="list-group-item list-group-item-action" data-test="public">Public Test</a>
                    <a href