 <div class="cards-container">
    <div class="feature-card">
      <div class="feature-card__header">
        <div class="feature-card__title">Today's Tasks</div>
        <div class="feature-card__icon"><i data-lucide="settings"></i></div>
      </div>
      <div class="feature-card__body">8</div>
      <div class="feature-card__footer">
        <span class="highlight">+2 from yesterday</span>
        <span class="desc">assigned tasks</span>
      </div>
    </div>

    <div class="feature-card">
      <div class="feature-card__header">
        <div class="feature-card__title">Completed</div>
        <div class="feature-card__icon"><i data-lucide="refresh-ccw"></i></div>
      </div>
      <div class="feature-card__body">5</div>
      <div class="feature-card__footer">
        <span class="highlight">83% success rate</span>
        <span class="desc">tasks finished</span>
      </div>
    </div>

    <div class="feature-card">
      <div class="feature-card__header">
        <div class="feature-card__title">Pending</div>
        <div class="feature-card__icon"><i data-lucide="clock"></i></div>
      </div>
      <div class="feature-card__body">3</div>
      <div class="feature-card__footer">
        <span class="highlight">2 hours remaining</span>
        <span class="desc">tasks left</span>
      </div>
    </div>

    <div class="feature-card">
      <div class="feature-card__header">
        <div class="feature-card__title">Total Weight</div>
        <div class="feature-card__icon"><i data-lucide="trending-up"></i></div>
      </div>
      <div class="feature-card__body">245kg</div>
      <div class="feature-card__footer">
        <span class="highlight">+15kg from avg</span>
        <span class="desc">collected today</span>
      </div>
    </div>

    <div class="feature-card">
      <div class="feature-card__header">
        <div class="feature-card__title">Today's Earnings</div>
        <div class="feature-card__icon"><i data-lucide="dollar-sign"></i></div>
      </div>
      <div class="feature-card__body">Rs.5,550.37</div>
      <div class="feature-card__footer">
        <span class="highlight">+12% from yesterday</span>
        <span class="desc">total earned</span>
      </div>
    </div>

    <div class="feature-card">
      <div class="feature-card__header">
        <div class="feature-card__title">Rating</div>
        <div class="feature-card__icon"><i data-lucide="star"></i></div>
      </div>
      <div class="feature-card__body">4.8</div>
      <div class="feature-card__footer">
        <span class="highlight">Top 3 performer</span>
        <span class="desc">customer rating</span>
      </div>
    </div>
  </div>

  <!-- Bottom Section -->
  <div class="bottom-container">

    <!-- Recent Tasks -->
    <div class="bottom-card">
      <h3>Recent Tasks</h3>
      <p>Your latest pickup activities</p>

      <div class="task">
        <div class="task-info">
          <span class="task-name">John Smith <span class="status completed">completed</span></span>
          <span class="task-meta"><i class="fa-solid fa-location-dot"></i> 123 Oak Street · Plastic · 15kg</span>
        </div>
        <div class="task-right">
          <span class="amount">Rs.1,000.00</span>
        </div>
      </div>

      <div class="task">
        <div class="task-info">
          <span class="task-name">Sarah Johnson <span class="status completed">completed</span></span>
          <span class="task-meta"><i class="fa-solid fa-location-dot"></i> 456 Pine Avenue · Paper · 24kg</span>
        </div>
        <div class="task-right">
          <span class="amount">Rs.2500.00</span>
        </div>
      </div>

      <div class="task">
        <div class="task-info">
          <span class="task-name">Mike Wilson <span class="status pending">pending</span></span>
          <span class="task-meta"><i class="fa-solid fa-location-dot"></i> 789 Elm Road · Metal · 8kg</span>
        </div>
        <div class="task-right">
          <span class="amount">Rs.800.00</span>
          <span>Start</span>
        </div>
      </div>

      <div class="task">
        <div class="task-info">
          <span class="task-name">Emma Davis <span class="status pending">pending</span></span>
          <span class="task-meta"><i class="fa-solid fa-location-dot"></i> 321 Maple Street · Glass · 12kg</span>
        </div>
        <div class="task-right">
          <span class="amount">Rs.650.00</span>
          <span>Start</span>
        </div>
      </div>
    </div>

    <!-- Performance Goals -->
    <div class="bottom-card">
      <h3>Performance Goals</h3>
      <p>Track your progress towards targets</p>

      <div class="goal">
        <div class="goal-header">
          <span>Daily Goal</span>
          <span class="goal-status in-progress">In Progress</span>
        </div>
        <div class="progress-bar">
          <div class="progress" style="width: 62.5%;"></div>
        </div>
        <div class="goal-footer">62.5% complete</div>
      </div>

      <div class="goal">
        <div class="goal-header">
          <span>Weekly Target</span>
          <span class="goal-status on-track">On Track</span>
        </div>
        <div class="progress-bar">
          <div class="progress" style="width: 78%;"></div>
        </div>
        <div class="goal-footer">78% complete</div>
      </div>

      <div class="goal">
        <div class="goal-header">
          <span>Customer Rating</span>
          <span class="goal-status excellent">Excellent</span>
        </div>
        <div class="progress-bar">
          <div class="progress" style="width: 96%;"></div>
        </div>
        <div class="goal-footer">96% complete</div>
      </div>

    </div>
  </div>

  <script>
    lucide.createIcons();
  </script>