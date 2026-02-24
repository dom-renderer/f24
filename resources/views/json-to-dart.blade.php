<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JSON to Dart Clone</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: #fcfcfc;
            color: #333;
            margin: 0;
            display: flex;
            justify-content: center;
            padding-top: 50px;
        }

        .container {
            width: 90%;
            max-width: 1000px;
            text-align: center;
        }

        header h1 {
            font-size: 32px;
            font-weight: 500;
            margin-bottom: 10px;
        }

        header p {
            font-size: 18px;
            color: #555;
            margin-bottom: 40px;
        }

        .main-layout {
            display: flex;
            gap: 20px;
            text-align: left;
            margin-bottom: 20px;
        }

        .column {
            flex: 1;
        }

        .label {
            display: block;
            font-weight: bold;
            margin-bottom: 8px;
        }

        /* Code Editor Styling */
        .editor-window {
            display: flex;
            background: white;
            border: 1px solid #ddd;
            height: 400px;
            font-family: 'Courier New', Courier, monospace;
            font-size: 14px;
        }

        .line-numbers {
            background: #eeeeee;
            color: #999;
            padding: 10px 5px;
            text-align: right;
            width: 30px;
            user-select: none;
        }

        .line-numbers span {
            display: block;
        }

        .code-content {
            flex: 1;
            padding: 10px 0;
        }

        .code-line {
            padding: 0 10px;
        }

        .code-line.active {
            background-color: #c9e2ff; /* The blue highlight from your image */
        }

        /* Dart Output Syntax Highlighting */
        .dart-output {
            font-family: 'Courier New', Courier, monospace;
            font-size: 14px;
            line-height: 1.4;
            padding: 10px;
            height: 400px;
            overflow-y: auto;
        }

        .keyword { color: #007bff; }
        .type { color: #17a2b8; }
        .string { color: #d73a49; }

        /* Form Controls */
        .controls {
            text-align: left;
            width: 300px;
        }

        .class-input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            margin-bottom: 15px;
            font-size: 14px;
        }

        .actions {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
        }

        .btn-generate {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
        }

        .checkbox-container {
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .btn-copy {
            background-color: #6c757d;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
        }

        .btn-generate:hover { background-color: #0056b3; }
        .btn-copy:hover { background-color: #5a6268; }
    </style>
    <div class="container">
        <header>
            <h1>JSON to Dart</h1>
            <p>Paste your JSON in the textarea below, click convert and get your Dart classes for free.</p>
        </header>

        <div class="main-layout">
            <div class="column">
                <label class="label">JSON</label>
                <div class="editor-window">
                    <div class="line-numbers">
                        <span>1</span>
                        <span>2</span>
                        <span>3</span>
                    </div>
                    <textarea class="code-content">

                    </textarea>
                </div>
            </div>

            <div class="column">
                <div class="dart-output">

                </div>
            </div>
        </div>

        <div class="controls">
            <input type="text" placeholder="Your dart class name goes here" class="class-input">
            
            <div class="actions">
                <button class="btn-generate">Generate Dart</button>
                <label class="checkbox-container">
                    <input type="checkbox"> Use private fields
                </label>
            </div>

            <button class="btn-copy">Copy Dart code to clipboard</button>
        </div>
    </div>

</body>
<script src="{{ asset('to-dart.js') }}"></script>
<script>

</script>
</html>