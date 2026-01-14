<!-- Page Header -->
<div class="page-header">
  <div class="page-header__content">
    <h2 class="page-header__title">
      Collector Dashboard
    </h2>
    <p class="page-header__description">Track your daily collections, monitor performance metrics, and view current
      material pricing</p>
  </div>
</div>

<div class="feature-cards">
  <div class="feature-card">
    <div class="feature-card__header">
      <div class="feature-card__title">Today's Tasks</div>
      <div class="feature-card__icon"><i class="fa-solid fa-list-check"></i></div>
    </div>
    <div class="feature-card__body">8</div>
    <div class="feature-card__footer">
      <span class="desc">assigned tasks</span>
    </div>
  </div>

  <div class="feature-card">
    <div class="feature-card__header">
      <div class="feature-card__title">Completed</div>
      <div class="feature-card__icon"><i class="fa-solid fa-table-list"></i></div>
    </div>
    <div class="feature-card__body">5</div>
    <div class="feature-card__footer">
      <span class="desc">tasks finished</span>
    </div>
  </div>

  <div class="feature-card">
    <div class="feature-card__header">
      <div class="feature-card__title">Pending</div>
      <div class="feature-card__icon"><i class="fa-solid fa-clock"></i></div>
    </div>
    <div class="feature-card__body">3</div>
    <div class="feature-card__footer">
      <span class="desc">tasks left</span>
    </div>
  </div>

  <div class="feature-card">
    <div class="feature-card__header">
      <div class="feature-card__title">Total Weight</div>
      <div class="feature-card__icon"><i class="fa-solid fa-weight-hanging"></i></div>
    </div>
    <div class="feature-card__body">245kg</div>
    <div class="feature-card__footer">
      <span class="desc">collected today</span>
    </div>
  </div>


  <div class="feature-card">
    <div class="feature-card__header">
      <div class="feature-card__title">Rating</div>
      <div class="feature-card__icon"><i class="fa-solid fa-star"></i></div>
    </div>
    <div class="feature-card__body">4.8</div>
    <div class="feature-card__footer">
      <span class="desc">customer rating</span>
    </div>
  </div>
</div>

<!-- Bottom Section -->
<div class="bottom-container">

  <!-- Recent Tasks -->
  <activity-card title="Recent Tasks" description="Your latest pickup activities">
    <div class="task">
      <div class="task-info">
        <div class="task-name">
          <span>
            John Smith
          </span>
          <span class="tag success">completed</span>
        </div>
        <div class="task-meta"><i class="fa-solid fa-location-dot"></i> 123 Oak Street · Plastic · 15kg</div>
      </div>
      <div class="task-right">
      </div>
    </div>

    <div class="task">
      <div class="task-info">
        <div class="task-name">Mike Wilson <span class="tag warning">pending</span></div>
        <span class="task-meta"><i class="fa-solid fa-location-dot"></i> 789 Elm Road · Metal · 8kg</span>
      </div>
    </div>

    <div class="task">
      <div class="task-info">
        <div class="task-name">Emma Davis <span class="tag warning">pending</span></div>
        <span class="task-meta"><i class="fa-solid fa-location-dot"></i> 321 Maple Street · Glass · 12kg</span>
      </div>
    </div>
  </activity-card>


  <activity-card title="Material Collection Summary" description="Breakdown of today's collected materials">
    <!-- Material Chart -->
    <div class="" style="padding: 0;">
      <canvas id="materialCollectionChart" style="width: 100%; max-height: 360px;"></canvas>
    </div>
  </activity-card>

  <!-- Amount per unit Section -->
  <activity-card title="Amount Per Weight Unit " description="Current amount for each material for 1 kg">
    <div class="goal">
      <div class="goal-header">
        <span style="display: flex; align-items: center; gap: var(--space-2);">
          <div
            style="width: 12px; height: 12px; border-radius: 50%; background-color: <?php echo material_color('plastic'); ?>;">
          </div>
          <span class="font-medium">Plastic</span>
        </span>
        <span class="goal-status" style="font-weight: var(--font-weight-bold); color: var(--neutral-900);">
          <?php echo format_rs(material_min_bid('plastic')); ?></span>
      </div>
    </div>
    <div class="goal">
      <div class="goal-header">
        <span style="display: flex; align-items: center; gap: var(--space-2);">
          <div
            style="width: 12px; height: 12px; border-radius: 50%; background-color: <?php echo material_color('glass'); ?>;">
          </div>
          <span class="font-medium">Glass</span>
        </span>
        <span class="goal-status" style="font-weight: var(--font-weight-bold); color: var(--neutral-900);">
          <?php echo format_rs(material_min_bid('glass')); ?></span>
      </div>
    </div>
    <div class="goal">
      <div class="goal-header">
        <span style="display: flex; align-items: center; gap: var(--space-2);">
          <div
            style="width: 12px; height: 12px; border-radius: 50%; background-color: <?php echo material_color('metal'); ?>;">
          </div>
          <span class="font-medium">Metal</span>
        </span>
        <span class="goal-status" style="font-weight: var(--font-weight-bold); color: var(--neutral-900);">
          <?php echo format_rs(material_min_bid('metal')); ?></span>
      </div>
    </div>
    <div class="goal">
      <div class="goal-header">
        <span style="display: flex; align-items: center; gap: var(--space-2);">
          <div
            style="width: 12px; height: 12px; border-radius: 50%; background-color: <?php echo material_color('paper'); ?>;">
          </div>
          <span class="font-medium">Paper</span>
        </span>
        <span class="goal-status" style="font-weight: var(--font-weight-bold); color: var(--neutral-900);">
          <?php echo format_rs(material_min_bid('paper')); ?></span>
      </div>
    </div>
    <div class="goal">
      <div class="goal-header">
        <span style="display: flex; align-items: center; gap: var(--space-2);">
          <div
            style="width: 12px; height: 12px; border-radius: 50%; background-color: <?php echo material_color('organic'); ?>;">
          </div>
          <span class="font-medium">Organic</span>
        </span>
        <span class="goal-status" style="font-weight: var(--font-weight-bold); color: var(--neutral-900);">
          <?php echo format_rs(material_min_bid('organic')); ?></span>
      </div>
    </div>
  </activity-card>
</div>


<!-- Material Collection Chart Script -->
<script>
  <?php
  // Define material data
  $materials = [
    ['name' => 'Plastic', 'weight' => 40, 'color' => material_color('plastic')],
    ['name' => 'Glass', 'weight' => 25, 'color' => material_color('glass')],
    ['name' => 'Metal', 'weight' => 20, 'color' => material_color('metal')],
    ['name' => 'Paper', 'weight' => 15, 'color' => material_color('paper')],
    ['name' => 'Organic', 'weight' => 60, 'color' => material_color('organic')],
  ];

  $materialLabels = json_encode(array_column($materials, 'name'));
  $materialWeights = json_encode(array_column($materials, 'weight'));
  $materialColors = json_encode(array_column($materials, 'color'));
  ?>

  // Prepare data from PHP
  const materialLabels = <?php echo $materialLabels; ?>;
  const materialWeights = <?php echo $materialWeights; ?>;
  const materialColors = <?php echo $materialColors; ?>;

  // Render Chart.js doughnut chart
  (function renderMaterialCollectionChart() {
    const el = document.getElementById('materialCollectionChart');
    if (!el) return;
    const ctx = el.getContext('2d');
    new Chart(ctx, {
      type: 'doughnut',
      data: {
        labels: materialLabels,
        datasets: [{
          label: 'Weight (kg)',
          data: materialWeights,
          backgroundColor: materialColors,
          borderWidth: 2,
          borderColor: '#ffffff',
          hoverOffset: 4
        }]
      },
      config: {
        rotation: 0,
        circumference: 360,
      },
      options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
          legend: {
            position: 'right',
            labels: {
              padding: 15,
              font: {
                size: 13
              },
              generateLabels: function (chart) {
                const data = chart.data;
                return data.labels.map((label, i) => {
                  const value = data.datasets[0].data[i];
                  const total = data.datasets[0].data.reduce((a, b) => a + b, 0);
                  const percentage = ((value / total) * 100).toFixed(0);
                  return {
                    text: `${label}: ${value}kg (${percentage}%)`,
                    fillStyle: data.datasets[0].backgroundColor[i],
                    hidden: false,
                    index: i
                  };
                })
              }
            }
          },
          tooltip: {
            callbacks: {
              label: function (context) {
                const label = context.label || '';
                const value = context.parsed || 0;
                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                const percentage = ((value / total) * 100).toFixed(1);
                return `${label}: ${value}kg (${percentage}%)`;
              }
            }
          }
        }
      }
    });
  })();
</script>

<script>
  lucide.createIcons();
</script>