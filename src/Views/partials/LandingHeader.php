<?php
$HeaderNav = [
    'Home' => '/',
    'About' => '/about',
    'Services' => '/services',
    'Contact' => '/contact'
];
?>

<header>
    <nav>
        <ul>
            <?php foreach ($HeaderNav as $name => $link): ?>
                <li><a href="<?php echo $link; ?>"><?php echo $name; ?></a></li>
            <?php endforeach; ?>
        </ul>
    </nav>
</header>