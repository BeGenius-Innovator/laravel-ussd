<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>USSD Simulator</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Courier New', monospace;
            background: #1a1a2e;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .phone {
            width: 320px;
            background: #16213e;
            border-radius: 30px;
            padding: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.5);
            border: 2px solid #0f3460;
        }
        .screen {
            background: #0a0a1a;
            border-radius: 10px;
            padding: 20px;
            min-height: 300px;
            color: #00ff41;
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 15px;
            border: 1px solid #1a3a5c;
            word-wrap: break-word;
            white-space: pre-wrap;
        }
        .screen .label {
            color: #4a9eff;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 10px;
        }
        .screen .response-text {
            color: #00ff41;
        }
        .input-group {
            display: flex;
            gap: 10px;
        }
        .input-group input {
            flex: 1;
            padding: 12px;
            background: #0d1b2a;
            border: 1px solid #1a3a5c;
            border-radius: 8px;
            color: #00ff41;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            outline: none;
        }
        .input-group input:focus {
            border-color: #4a9eff;
        }
        .input-group button {
            padding: 12px 20px;
            background: #0f3460;
            color: #4a9eff;
            border: 1px solid #1a3a5c;
            border-radius: 8px;
            cursor: pointer;
            font-family: 'Courier New', monospace;
            font-weight: bold;
        }
        .input-group button:hover {
            background: #1a3a5c;
        }
        .info {
            margin-top: 10px;
            font-size: 11px;
            color: #4a9eff;
            text-align: center;
        }
        .info span {
            color: #00ff41;
        }
    </style>
</head>
<body>
    <div class="phone">
        <div class="screen">
            <div class="label">USSD Simulator</div>
            <div class="response-text">{{ $response ?? 'Dial *123# to start' }}</div>
        </div>

        <form method="POST" action="{{ route('ussd.simulator.handle') }}">
            @csrf
            <input type="hidden" name="session_id" value="{{ $sessionId ?? '' }}">
            <input type="hidden" name="phone" value="{{ $phone ?? '22670000000' }}">
            <input type="hidden" name="network" value="{{ $network ?? 'SIMULATOR' }}">
            <input type="hidden" name="service_code" value="*123#">

            <div class="input-group">
                <input type="text" name="text" placeholder="Type response..." value="{{ $text ?? '' }}" autofocus>
                <button type="submit">Send</button>
            </div>
        </form>

        <div class="info">
            Session: <span>{{ $sessionId ?? '—' }}</span>
            &nbsp;|&nbsp; Phone: <span>{{ $phone ?? '22670000000' }}</span>
        </div>
    </div>
</body>
</html>
