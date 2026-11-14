<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;

class SystemUtilityController extends Controller
{
    /**
     * Allowed artisan commands for web execution
     */
    protected array $allowedCommands = [
        'migrate' => ['--force' => true],
        'migrate:status' => [],
        'route:list' => [],
        'route:clear' => [],
        'route:cache' => [],
        'config:clear' => [],
        'config:cache' => [],
        'cache:clear' => [],
        'view:clear' => [],
        'optimize:clear' => [],
        'storage:link' => [],
        'db:seed' => ['--force' => true],
    ];

    /**
     * Check authorization: either authenticated admin or secret security key
     */
    protected function authorizeAccess(Request $request): bool
    {
        // 1. Allow if database is unmigrated (e.g. sessions or users table does not exist yet)
        try {
            if (!\Illuminate\Support\Facades\Schema::hasTable('users') || !\Illuminate\Support\Facades\Schema::hasTable('sessions')) {
                return true;
            }
        } catch (\Throwable $e) {
            return true;
        }

        // 2. Authenticated users always allowed
        try {
            if (Auth::check()) {
                return true;
            }
        } catch (\Throwable $e) {
            // Ignore if auth check fails during setup
        }

        // 3. Allow if secret key matches (defaults to thenexteck2026 or ARTISAN_KEY from .env)
        $secretKey = env('ARTISAN_KEY', 'thenexteck2026');
        if ($request->query('key') === $secretKey || $request->header('X-Artisan-Key') === $secretKey) {
            return true;
        }

        // 4. Allow in local development or debug mode
        if (config('app.debug')) {
            return true;
        }

        return false;
    }

    /**
     * Run database migrations
     */
    public function migrate(Request $request)
    {
        if (!$this->authorizeAccess($request)) {
            abort(403, 'Unauthorized access. Please login or provide ?key=thenexteck2026');
        }

        return $this->executeCommand('migrate', ['--force' => true]);
    }

    protected function bindWebMiddlewareGroup(): void
    {
        if (!app()->bound('web')) {
            app()->bind('web', fn() => new class {
                public function handle($request, $next) { return $next($request); }
            });
        }
    }

    /**
     * Run route artisan commands (route:list, route:clear, route:cache, etc.)
     */
    public function routes(Request $request, ?string $action = 'list')
    {
        if (!$this->authorizeAccess($request)) {
            abort(403, 'Unauthorized access. Please login or provide ?key=thenexteck2026');
        }

        $this->bindWebMiddlewareGroup();

        $command = match ($action) {
            'clear' => 'route:clear',
            'cache' => 'route:cache',
            default => 'route:list',
        };

        return $this->executeCommand($command);
    }

    /**
     * Run any whitelisted artisan command
     */
    public function run(Request $request, string $command)
    {
        if (!$this->authorizeAccess($request)) {
            abort(403, 'Unauthorized access. Please login or provide ?key=thenexteck2026');
        }

        $commandKey = strtolower(trim($command));

        if (!array_key_exists($commandKey, $this->allowedCommands)) {
            return response()->json([
                'success' => false,
                'message' => "Command '{$commandKey}' is not permitted via web execution.",
                'allowed_commands' => array_keys($this->allowedCommands),
            ], 400);
        }

        if ($commandKey === 'route:list') {
            $this->bindWebMiddlewareGroup();
        }

        $params = $this->allowedCommands[$commandKey];
        return $this->executeCommand($commandKey, $params);
    }

    /**
     * Helper to run artisan command and render styled response
     */
    protected function executeCommand(string $command, array $params = [])
    {
        try {
            $exitCode = Artisan::call($command, $params);
            $output = trim(Artisan::output());

            if (empty($output)) {
                $output = ($exitCode === 0)
                    ? "Command executed successfully with exit code 0."
                    : "Command finished with exit code {$exitCode}.";
            }

            return response($this->renderTerminalHtml($command, $output, $exitCode === 0));
        } catch (\Throwable $e) {
            return response($this->renderTerminalHtml($command, $e->getMessage() . "\n\n" . $e->getTraceAsString(), false), 500);
        }
    }

    /**
     * Beautiful developer-friendly terminal UI for command output
     */
    protected function renderTerminalHtml(string $command, string $output, bool $success): string
    {
        $statusBadge = $success
            ? '<span style="background:#059669; color:#fff; padding:3px 10px; border-radius:6px; font-weight:600; font-size:12px;">SUCCESS</span>'
            : '<span style="background:#dc2626; color:#fff; padding:3px 10px; border-radius:6px; font-weight:600; font-size:12px;">FAILED</span>';

        $safeOutput = htmlspecialchars($output, ENT_QUOTES, 'UTF-8');

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Artisan: {$command} - TheNexteck</title>
    <link href="https://fonts.googleapis.com/css2?family=Fira+Code:wght@400;500;600&family=Plus+Jakarta+Sans:wght@500;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { background: #0f172a; color: #e2e8f0; font-family: 'Plus Jakarta Sans', sans-serif; padding: 30px 20px; line-height: 1.6; }
        .container { max-width: 900px; margin: 0 auto; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 12px; }
        .title { font-size: 1.25rem; font-weight: 700; color: #fff; display: flex; align-items: center; gap: 10px; }
        .terminal-card { background: #1e293b; border-radius: 12px; border: 1px solid #334155; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.4); }
        .terminal-bar { background: #0f172a; padding: 12px 16px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #334155; }
        .dots { display: flex; gap: 6px; }
        .dot { width: 12px; height: 12px; border-radius: 50%; }
        .dot-red { background: #ef4444; }
        .dot-yellow { background: #f59e0b; }
        .dot-green { background: #10b981; }
        .cmd-label { font-family: 'Fira Code', monospace; font-size: 0.85rem; color: #94a3b8; }
        pre { padding: 20px; font-family: 'Fira Code', monospace; font-size: 0.88rem; color: #38bdf8; overflow-x: auto; white-space: pre-wrap; word-break: break-all; }
        .quick-actions { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 24px; padding: 16px; background: #1e293b; border-radius: 10px; border: 1px solid #334155; }
        .quick-actions a { color: #cbd5e1; text-decoration: none; font-size: 0.82rem; font-family: 'Fira Code', monospace; background: #334155; padding: 6px 12px; border-radius: 6px; transition: all 0.2s; }
        .quick-actions a:hover { background: #0284c7; color: #fff; }
        .back-btn { color: #94a3b8; text-decoration: none; font-size: 0.88rem; display: inline-flex; align-items: center; margin-bottom: 14px; }
        .back-btn:hover { color: #fff; }
    </style>
</head>
<body>
    <div class="container">
        <a href="/admin/marketing/dashboard" class="back-btn">&larr; Back to Dashboard</a>
        <div class="header">
            <div class="title">
                Artisan Execution: <code style="color:#38bdf8; font-family:'Fira Code'; font-size:1.1rem;">php artisan {$command}</code>
            </div>
            <div>{$statusBadge}</div>
        </div>

        <div class="terminal-card">
            <div class="terminal-bar">
                <div class="dots">
                    <span class="dot dot-red"></span>
                    <span class="dot dot-yellow"></span>
                    <span class="dot dot-green"></span>
                </div>
                <div class="cmd-label">$ php artisan {$command}</div>
            </div>
            <pre>{$safeOutput}</pre>
        </div>

        <div class="quick-actions">
            <span style="font-size:0.85rem; color:#94a3b8; display:flex; align-items:center; margin-right:4px;">Quick Actions:</span>
            <a href="/artisan/migrate">php artisan migrate</a>
            <a href="/artisan/routes/list">php artisan route:list</a>
            <a href="/artisan/routes/clear">php artisan route:clear</a>
            <a href="/artisan/routes/cache">php artisan route:cache</a>
            <a href="/artisan/run/optimize:clear">php artisan optimize:clear</a>
            <a href="/artisan/run/config:clear">php artisan config:clear</a>
            <a href="/artisan/run/cache:clear">php artisan cache:clear</a>
            <a href="/artisan/run/migrate:status">php artisan migrate:status</a>
        </div>
    </div>
</body>
</html>
HTML;
    }
}
