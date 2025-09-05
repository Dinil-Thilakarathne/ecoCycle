<?php
// Example: notifications with read/unread status
$notifications = [
    ["id" => 1, "text" => "Your bid on Lot metal 1000kg was successful!", "time" => "2 minutes ago", "status" => "unread"],
    ["id" => 2, "text" => "Payment received for Purchase ID PUR001", "time" => "15 minutes ago", "status" => "unread"],
    ["id" => 3, "text" => "New waste lot available: Plastic Bottles in District B", "time" => "1 hour ago", "status" => "read"],
    ["id" => 4, "text" => "System maintenance scheduled for 25th Aug, 2 AM - 4 AM", "time" => "Yesterday", "status" => "read"],
    ["id" => 5, "text" => "Your company profile was verified successfully.", "time" => "2 days ago", "status" => "read"]
];

// Extra notifications for "Load More"
$moreNotifications = [
    ["id" => 6, "text" => "Reminder: Pickup scheduled for tomorrow.", "time" => "3 days ago", "status" => "unread"],
    ["id" => 7, "text" => "Collector completed pickup #PK789.", "time" => "4 days ago", "status" => "read"],
    ["id" => 8, "text" => "Bid placed on Lot glass 500kg.", "time" => "5 days ago", "status" => "read"]
];
?>

<main class="content">
        <header class="page-header">
            <div class="page-header__content">
                    <h2 class="page-header__title">Notifications</h2>
                    <button class="mark-read-btn" onclick="markAllRead()">Mark All as Read</button>
            </div>
        </header>

        <!-- Notifications List -->
        <div class="notifications-list" id="notifList">
            <?php foreach($notifications as $note): ?>
                <div class="notification-card <?= $note['status'] ?>" 
                     data-id="<?= $note['id'] ?>"
                     onclick="openModal(this, '<?= $note['text'] ?>','<?= $note['time'] ?>')">
                    <div>
                        <p class="note-text"><?= $note['text'] ?></p>
                        <span class="note-time"><?= $note['time'] ?></span>
                    </div>
                    <?php if($note['status'] == "unread"): ?>
                        <span class="unread-dot"></span>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Hidden Extra Notifications -->
        <div id="moreNotifs" style="display:none;">
            <?php foreach($moreNotifications as $note): ?>
                <div class="notification-card <?= $note['status'] ?>" 
                     data-id="<?= $note['id'] ?>"
                     onclick="openModal(this, '<?= $note['text'] ?>','<?= $note['time'] ?>')">
                    <div>
                        <p class="note-text"><?= $note['text'] ?></p>
                        <span class="note-time"><?= $note['time'] ?></span>
                    </div>
                    <?php if($note['status'] == "unread"): ?>
                        <span class="unread-dot"></span>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Load More -->
        <button class="load-btn" id="loadMoreBtn" onclick="loadMore()">Load More...</button>
        
</main>

<!-- Notification Modal -->
<div id="notificationModal" class="modal">
  <div class="modal-content">
    <span class="close-btn" onclick="closeModal()">&times;</span>
    <h3>Notification Details</h3>
    <p id="modalText"></p>
    <small id="modalTime"></small>
  </div>
</div>

<script>
function markAllRead() {
    document.querySelectorAll('.notification-card').forEach(card => {
        card.classList.remove('unread');
        card.classList.add('read');
        let dot = card.querySelector('.unread-dot');
        if(dot) dot.remove();
    });
}

// Open modal and mark clicked as read
function openModal(card, text, time) {
    // Show modal
    document.getElementById("modalText").innerText = text;
    document.getElementById("modalTime").innerText = time;
    document.getElementById("notificationModal").style.display = "block";

    // Mark as read
    card.classList.remove('unread');
    card.classList.add('read');
    let dot = card.querySelector('.unread-dot');
    if(dot) dot.remove();
}

function closeModal() {
    document.getElementById("notificationModal").style.display = "none";
}

function loadMore() {
    let more = document.getElementById("moreNotifs").innerHTML;
    document.getElementById("notifList").innerHTML += more;
    document.getElementById("moreNotifs").style.display = "none";
    document.getElementById("loadMoreBtn").style.display = "none";
}
</script>