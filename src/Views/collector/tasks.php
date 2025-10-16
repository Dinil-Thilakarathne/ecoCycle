<div class="header">
  <div>
    <h1>Daily Tasks</h1>
    <div class="sub-header">5 tasks for today <span class="status-tag">Active</span></div>
  </div>
  <div class="search-filter">
    <input type="text" class="search-box" placeholder="Search tasks, customers, or locations...">
    <select class="filter">
      <option>All Tasks</option>
    </select>
  </div>
</div>

<!-- Task 1 -->
<div class="task-card">
  <div class="task-top">
    <div>
      <div class="task-name">Mike Wilson</div>
      <div class="task-address">789 Elm Road, Downtown</div>
    </div>
  </div>
  <div class="task-details">
    <div class="detail-box">Category: Metal</div>
    <div class="detail-box">Weight: 8kg</div>
    <div class="detail-box">Time: 02:00 PM</div>
  </div>
  <div class="contact-info">
    <i class="fa-solid fa-phone"></i> +94 763975639 
    <i class="fa-solid fa-location-dot"></i> 2.3 km 
    <i class="fa-solid fa-clock"></i> 20 min
  </div>
  <div class="notes">
    <strong>Notes:</strong> Large metal items, need assistance
  </div>
  <div class="task-actions">
    <button class="start-btn" onclick="startTask('journey.html')"><i class="fa-solid fa-play"></i> Start Task</button>
    <button class="nav-btn" onclick="navigateToMap('789 Elm Road, Downtown')"><i class="fa-solid fa-location-arrow"></i> Navigate</button>
  </div>
</div>

<!-- Task 2 -->
<div class="task-card">
  <div class="task-top">
    <div>
      <div class="task-name">Emma Davis</div>
      <div class="task-address">321 Maple Street, Uptown</div>
    </div>
  </div>
  <div class="task-details">
    <div class="detail-box">Category: Glass</div>
    <div class="detail-box">Weight: 12kg</div>
    <div class="detail-box">Time: 11:30 AM</div>
  </div>
  <div class="contact-info">
    <i class="fa-solid fa-phone"></i> +94 719845236 
    <i class="fa-solid fa-location-dot"></i> 3.1 km 
    <i class="fa-solid fa-clock"></i> 25 min
  </div>
  <div class="notes">
    <strong>Notes:</strong> Fragile items, handle with care
  </div>
  <div class="task-actions">
    <button class="start-btn" onclick="startTask('journey.html')"><i class="fa-solid fa-play"></i> Start Task</button>
    <button class="nav-btn" onclick="navigateToMap('321 Maple Street, Uptown')"><i class="fa-solid fa-location-arrow"></i> Navigate</button>
  </div>
</div>

<!-- Task 3 -->
<div class="task-card">
  <div class="task-top">
    <div>
      <div class="task-name">John Smith</div>
      <div class="task-address">123 Oak Street, Central</div>
    </div>
  </div>
  <div class="task-details">
    <div class="detail-box">Category: Plastic</div>
    <div class="detail-box">Weight: 15kg</div>
    <div class="detail-box">Time: 09:00 AM</div>
  </div>
  <div class="contact-info">
    <i class="fa-solid fa-phone"></i> +94 701234567 
    <i class="fa-solid fa-location-dot"></i> 1.8 km 
    <i class="fa-solid fa-clock"></i> 15 min
  </div>
  <div class="notes">
    <strong>Notes:</strong> Multiple plastic bags
  </div>
  <div class="task-actions">
    <button class="start-btn" onclick="startTask('journey.html')"><i class="fa-solid fa-play"></i> Start Task</button>
    <button class="nav-btn" onclick="navigateToMap('123 Oak Street, Central')"><i class="fa-solid fa-location-arrow"></i> Navigate</button>
  </div>
</div>

<!-- Task 4 -->
<div class="task-card">
  <div class="task-top">
    <div>
      <div class="task-name">Sophia Brown</div>
      <div class="task-address">456 Pine Avenue, Westside</div>
    </div>
  </div>
  <div class="task-details">
    <div class="detail-box">Category: Paper</div>
    <div class="detail-box">Weight: 20kg</div>
    <div class="detail-box">Time: 01:15 PM</div>
  </div>
  <div class="contact-info">
    <i class="fa-solid fa-phone"></i> +94 753214698 
    <i class="fa-solid fa-location-dot"></i> 4.5 km 
    <i class="fa-solid fa-clock"></i> 35 min
  </div>
  <div class="notes">
    <strong>Notes:</strong> Heavy bundle, may require vehicle space
  </div>
  <div class="task-actions">
    <button class="start-btn" onclick="startTask('journey.html')"><i class="fa-solid fa-play"></i> Start Task</button>
    <button class="nav-btn" onclick="navigateToMap('456 Pine Avenue, Westside')"><i class="fa-solid fa-location-arrow"></i> Navigate</button>
  </div>
</div>

<!-- Task 5 -->
<div class="task-card">
  <div class="task-top">
    <div>
      <div class="task-name">David Lee</div>
      <div class="task-address">654 Cedar Lane, Eastend</div>
    </div>
  </div>
  <div class="task-details">
    <div class="detail-box">Category: E-Waste</div>
    <div class="detail-box">Weight: 6kg</div>
    <div class="detail-box">Time: 04:45 PM</div>
  </div>
  <div class="contact-info">
    <i class="fa-solid fa-phone"></i> +94 768523741 
    <i class="fa-solid fa-location-dot"></i> 5.2 km 
    <i class="fa-solid fa-clock"></i> 40 min
  </div>
  <div class="notes">
    <strong>Notes:</strong> Old electronic parts
  </div>
  <div class="task-actions">
    <button class="start-btn" onclick="startTask('journey.html')"><i class="fa-solid fa-play"></i> Start Task</button>
    <button class="nav-btn" onclick="navigateToMap('654 Cedar Lane, Eastend')"><i class="fa-solid fa-location-arrow"></i> Navigate</button>
  </div>
</div>

<script>
  function startTask(url) {
    alert("Your journey has started! 🚀");
    window.location.href = url;
  }

  function navigateToMap(address) {
    const encodedAddress = encodeURIComponent(address);
    const mapsUrl = `https://www.google.com/maps/search/?api=1&query=${encodedAddress}`;
    window.open(mapsUrl, '_blank');
  }
</script>

</body>
</html>