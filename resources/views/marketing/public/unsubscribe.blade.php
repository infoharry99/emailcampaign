<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unsubscribe Confirmation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f8fafc; color: #1e293b; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; padding: 20px; }
        .card { max-width: 480px; width: 100%; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1); background: #ffffff; padding: 36px 32px; text-align: center; }
    </style>
</head>
<body>
    <div class="card">
        @if($isUnsubscribed)
            <div class="text-success mb-3" style="font-size: 48px;">✓</div>
            <h4 class="fw-bold mb-2">Already Unsubscribed</h4>
            <p class="text-muted small mb-4">Your address (<code>{{ $contact ? $contact->email : 'Recipient' }}</code>) has already been permanently unsubscribed and suppressed from future marketing campaigns.</p>
        @else
            <div class="text-secondary mb-3" style="font-size: 48px;">✉</div>
            <h4 class="fw-bold mb-2">Confirm Unsubscribe</h4>
            <p class="text-muted small mb-4">Are you sure you want to stop receiving marketing emails from TheNexteck? You will be added to our global suppression list immediately.</p>
            
            <form method="POST" action="{{ route('marketing.unsubscribe.post', ['token' => $token]) }}">
                @csrf
                <button type="submit" class="btn btn-danger w-100 py-2 fw-semibold">
                    Unsubscribe Immediately
                </button>
            </form>
        @endif
    </div>
</body>
</html>
