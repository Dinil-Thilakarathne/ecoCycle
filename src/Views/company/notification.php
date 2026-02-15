<?php
// Initialize notifications from controller
$notifications = $notifications ?? [];
$currentTab = $_GET['tab'] ?? 'total';
$selectedNotificationId = $_GET['id'] ?? null;
?>

<main class="content">
  <header class="page-header">
    <div class="page-header__content">
      <h2 class="page-header__title"><i class="fa-solid fa-bell"></i> Notifications</h2>
      <p class="page-header__description">Stay on top of your platform updates</p>
    </div>
  </header>

  <div id="alert-container"></div>

  <div id="notification-detail" style="display: none; margin-bottom: 1rem;">
    <div class="activity-card">
      <div class="activity-card__header">
        <h3 class="activity-card__title">
          <i class="fa-solid fa-envelope-open-text"></i>
          <span id="detail-title"></span>
        </h3>
        <button onclick="closeDetail()" style="background: none; border: none; cursor: pointer; font-size: 1.2rem;">&times;</button>
      </div>
      <div class="activity-card__content">
        <p style="margin-bottom: 0.5rem;">Type: <strong id="detail-type"></strong></p>
        <p style="margin-bottom: 0.5rem;">Date: <strong id="detail-date"></strong></p>
        <p style="margin: 0; line-height: 1.6;" id="detail-message"></p>
      </div>
    </div>
  </div>

  <div class="tabs">
    <div class="tabs-list">
      <button class="tabs-trigger<?= $currentTab === 'total' ? ' active' : '' ?>" onclick="showTab('total')" id="total-tab">
        Total (<span id="total-count">0</span>)
      </button>
      <button class="tabs-trigger<?= $currentTab === 'unread' ? ' active' : '' ?>" onclick="showTab('unread')" id="unread-tab">
        Unread (<span id="unread-count">0</span>)
      </button>
      <button class="tabs-trigger<?= $currentTab === 'read' ? ' active' : '' ?>" onclick="showTab('read')" id="read-tab">
        Read (<span id="read-count">0</span>)
      </button>
    </div>

    <div style="margin: 12px 0;">
      <button onclick="markAllAsRead()" class="btn btn-primary">Mark All as Read</button>
    </div>

    <div class="tabs-content<?= $currentTab === 'total' ? ' active' : '' ?>" id="total-content">
      <table class="data-table">
        <thead>
          <tr>
            <th>Notification</th>
            <th>Type</th>
            <th>Date</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody id="total-tbody"></tbody>
      </table>
    </div>

    <div class="tabs-content<?= $currentTab === 'unread' ? ' active' : '' ?>" id="unread-content">
      <table class="data-table">
        <thead>
          <tr>
            <th>Notification</th>
            <th>Type</th>
            <th>Date</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody id="unread-tbody"></tbody>
      </table>
    </div>

    <div class="tabs-content<?= $currentTab === 'read' ? ' active' : '' ?>" id="read-content">
      <table class="data-table">
        <thead>
          <tr>
            <th>Notification</th>
            <th>Type</th>
            <th>Date</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody id="read-tbody"></tbody>
      </table>
    </div>
  </div>
</main>

<script>
// Initialize notifications from server-side data
let notificationsState = <?= json_encode($notifications) ?>.map(n => ({
  ...n,
  isRead: ['read', 'sent'].includes((n.status || '').toLowerCase()),
  isArchived: false,
  priority: getPriority(n.type)
}));

let currentTab = '<?= $currentTab ?>';
const selectedNotificationId = '<?= $selectedNotificationId ?>';

// Load archived state from localStorage
function loadArchivedState() {
  const archived = JSON.parse(localStorage.getItem('archived_notifications') || '[]');
  notificationsState.forEach(n => {
    if (archived.includes(String(n.id))) {
      n.isArchived = true;
    }
  });
}

function getPriority(type) {
  const t = (type || 'info').toLowerCase();
  if (t === 'payment') return 'high';
  if (t === 'system') return 'low';
  return 'normal';
}

function getFilteredNotifications(filter) {
  switch (filter) {
    case 'unread':
      return notificationsState.filter(n => !n.isArchived && !n.isRead);
    case 'read':
      return notificationsState.filter(n => !n.isArchived && n.isRead);
    case 'total':
    default:
      return notificationsState.filter(n => !n.isArchived);
  }
}

function renderNotifications() {
  const filters = ['total', 'unread', 'read', 'archived'];
  
  filters.forEach(filter => {
    const notifications = getFilteredNotifications(filter);
    const tbody = document.getElementById(`${filter}-tbody`);
    const countSpan = document.getElementById(`${filter}-count`);
    
    countSpan.textContent = notifications.length;
    
    if (notifications.length === 0) {
      tbody.innerHTML = "<tr><td colspan='4' style='text-align:center;'>No notifications found</td></tr>";
      return;
    }
    
    tbody.innerHTML = notifications.map(n => {
      const rowClass = n.isRead ? '' : 'unread';
      const title = escapeHtml(n.title || 'Notification');
      const message = escapeHtml(n.message || '');
      const type = escapeHtml(ucfirst(n.type || 'info'));
      const formatted = n.timestamp ? formatDate(n.timestamp) : 'N/A';
      
      return `
        <tr class="${rowClass}" data-id="${n.id}">
          <td><strong>${title}</strong><br><small>${message}</small></td>
          <td>${type}</td>
          <td>${formatted}</td>
          <td>
            ${!n.isRead ? `<a href="#" onclick="markAsRead(${n.id}); return false;" class="action-btn">Mark Read</a> ` : ''}
            <a href="#" onclick="viewNotification(${n.id}); return false;" class="action-btn">View</a> 
          </td>
        </tr>
      `;
    }).join('');
  });
}

async function markAsRead(id) {
  try {
    console.log('Marking notification as read:', id);
    const url = `/api/notifications/${id}/read`;
    console.log('Request URL:', url);
    
    const response = await fetch(url, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    });
    
    console.log('Response status:', response.status);
    
    const data = await response.json();
    console.log('Response data:', data);
    
    if (response.ok && data.success) {
      // Update local state
      const notification = notificationsState.find(n => n.id == id);
      if (notification) {
        notification.isRead = true;
        notification.status = 'read';
      }
      
      renderNotifications();
      showAlert(data.message || 'Notification marked as read', 'success');
    } else {
      const errorMsg = data.message || data.error || 'Failed to mark notification as read';
      console.error('Error response:', errorMsg);
      showAlert(errorMsg, 'error');
    }
  } catch (error) {
    console.error('Failed to mark as read:', error);
    showAlert('Network error: ' + error.message, 'error');
  }
} 

async function markAllAsRead() {
  try {
    console.log('Marking all notifications as read');
    const url = '/api/notifications/read-all';
    console.log('Request URL:', url);
    
    const response = await fetch(url, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    });
    
    console.log('Response status:', response.status);
    
    const data = await response.json();
    console.log('Response data:', data);
    
    if (response.ok && data.success) {
      // Update local state
      notificationsState.forEach(n => {
        if (!n.isArchived) {
          n.isRead = true;
          n.status = 'read';
        }
      });
      
      renderNotifications();
      showAlert(data.message || 'All notifications marked as read', 'success');
    } else {
      const errorMsg = data.message || data.error || 'Failed to mark all notifications as read';
      console.error('Error response:', errorMsg);
      showAlert(errorMsg, 'error');
    }
  } catch (error) {
    console.error('Failed to mark all as read:', error);
    showAlert('Network error: ' + error.message, 'error');
  }
}

function viewNotification(id) {
  const notification = notificationsState.find(n => n.id == id);
  if (!notification) return;
  
  // Mark as read when viewing
  if (!notification.isRead) {
    markAsRead(id);
  }
  
  document.getElementById('detail-title').textContent = notification.title || 'Notification Details';
  document.getElementById('detail-type').textContent = ucfirst(notification.type || 'info');
  document.getElementById('detail-date').textContent = notification.timestamp ? formatDate(notification.timestamp) : 'N/A';
  document.getElementById('detail-message').innerHTML = escapeHtml(notification.message || '').replace(/\n/g, '<br>');
  document.getElementById('notification-detail').style.display = 'block';
  
  // Update URL
  const params = new URLSearchParams(window.location.search);
  params.set('id', id);
  window.history.pushState({}, '', '?' + params.toString());
}

function closeDetail() {
  document.getElementById('notification-detail').style.display = 'none';
  
  // Remove ID from URL
  const params = new URLSearchParams(window.location.search);
  params.delete('id');
  const newUrl = params.toString() ? '?' + params.toString() : window.location.pathname;
  window.history.pushState({}, '', newUrl);
}

function showTab(tab) {
  currentTab = tab;
  
  // Update tabs
  document.querySelectorAll('.tabs-trigger').forEach(btn => btn.classList.remove('active'));
  document.getElementById(`${tab}-tab`).classList.add('active');
  
  // Update content
  document.querySelectorAll('.tabs-content').forEach(content => content.classList.remove('active'));
  document.getElementById(`${tab}-content`).classList.add('active');
  
  // Update URL
  const params = new URLSearchParams(window.location.search);
  params.set('tab', tab);
  params.delete('msg');
  window.history.pushState({}, '', '?' + params.toString());
}

function showAlert(message, type = 'success') {
  const container = document.getElementById('alert-container');
  const alert = document.createElement('div');
  alert.className = `alert alert-${type}`;
  alert.style.cssText = 'margin: 16px 0; padding: 12px; border: 1px solid var(--success-400); background: var(--success-50); color: var(--success-700);';
  alert.textContent = message;
  
  container.innerHTML = '';
  container.appendChild(alert);
  
  setTimeout(() => alert.remove(), 5000);
}

// Utility functions
function escapeHtml(text) {
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}

function ucfirst(str) {
  return str.charAt(0).toUpperCase() + str.slice(1);
}

function formatDate(timestamp) {
  const date = new Date(timestamp);
  const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
  const month = months[date.getMonth()];
  const day = date.getDate();
  const year = date.getFullYear();
  const hours = String(date.getHours()).padStart(2, '0');
  const minutes = String(date.getMinutes()).padStart(2, '0');
  return `${month} ${day}, ${year} ${hours}:${minutes}`;
}

// Initialize on page load
loadArchivedState();
renderNotifications();

// Auto-view notification if ID in URL
if (selectedNotificationId) {
  viewNotification(selectedNotificationId);
}
</script>