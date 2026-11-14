<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unsubscribed Successfully</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f8fafc; color: #1e293b; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; padding: 20px; }
        .card { max-width: 480px; width: 100%; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1); background: #ffffff; padding: 36px 32px; text-align: center; }
    </style>
</head>
<body>
    <div class="card">
        <div class="text-success mb-3" style="font-size: 48px;">✓</div>
        <h4 class="fw-bold mb-2">You Have Been Unsubscribed</h4>
        <p class="text-muted small mb-0">Your email address (<code>{{ $contact ? $contact->email : 'Recipient' }}</code>) has been removed and added to our central suppression database.</p>
    </div>
</body>
</html>
