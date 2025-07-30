<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Custom PHP Framework' ?></title>
    <link rel="stylesheet" href="/css/main.css" />
</head>

<body>
    <div class="container">
        <div class="header">
            <h1><?= $title ?? 'Custom PHP Framework' ?></h1>
            <div class="nav">
                <a href="/home">Home</a>
                <a href="/page/about">About</a>
                <a href="/example">Example</a>
                <a href="/page">Page Demo</a>
                <a href="/user/developer">Profile</a>
            </div>
        </div>

        <div class="content">
            <?= $content ?? '' ?>
        </div>

        <div class="footer">
            <p>&copy; 2025 Custom PHP Framework - Built from Scratch</p>
        </div>
    </div>
</body>

</html>