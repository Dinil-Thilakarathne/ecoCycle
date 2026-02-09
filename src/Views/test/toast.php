<?php
$title = 'Toast API Test';
?>

<div class="container" style="max-width: 800px; margin: 40px auto; padding: 20px;">
    <h1>Toast API Test Page</h1>
    <p>Use the buttons below to test different toast notifications.</p>

    <div style="display: flex; gap: 10px; flex-wrap: wrap; margin-top: 20px;">
        <button onclick="toast('This is an info message', 'info')"
            style="padding: 10px 20px; background: #3498db; color: white; border: none; border-radius: 4px; cursor: pointer;">
            Info Toast
        </button>

        <button onclick="toast('Operation successful!', 'success')"
            style="padding: 10px 20px; background: #27ae60; color: white; border: none; border-radius: 4px; cursor: pointer;">
            Success Toast
        </button>

        <button onclick="toast('Something went wrong!', 'error')"
            style="padding: 10px 20px; background: #e74c3c; color: white; border: none; border-radius: 4px; cursor: pointer;">
            Error Toast
        </button>
    </div>

    <div style="margin-top: 20px;">
        <h3>Advanced Tests</h3>
        <div style="display: flex; gap: 10px; flex-wrap: wrap; margin-top: 10px;">
            <button onclick="toast('This message will stay for 10 seconds', 'info', 10000)"
                style="padding: 10px 20px; background: #95a5a6; color: white; border: none; border-radius: 4px; cursor: pointer;">
                Long Duration (10s)
            </button>

            <button onclick="toast('<strong>Bold Text</strong> and <em>Italic Text</em>', 'info')"
                style="padding: 10px 20px; background: #8e44ad; color: white; border: none; border-radius: 4px; cursor: pointer;">
                HTML Content
            </button>

            <button onclick="createManyToasts()"
                style="padding: 10px 20px; background: #f39c12; color: white; border: none; border-radius: 4px; cursor: pointer;">
                Stack (3 in a row)
            </button>
        </div>
    </div>
</div>

<script>
    function createManyToasts() {
        toast('Toast 1', 'info');
        setTimeout(() => toast('Toast 2', 'success'), 500);
        setTimeout(() => toast('Toast 3', 'error'), 1000);
    }
</script>