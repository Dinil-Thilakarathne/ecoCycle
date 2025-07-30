<!-- Index Page Component - Like a React component -->
<div className="home-page">
    <div className="hero-section" style="text-align: center; margin-bottom: 40px;">
        <h1 style="font-size: 3em; margin-bottom: 20px; color: #2c3e50;">
            Welcome, <?= htmlspecialchars($user['name']) ?>! 👋
        </h1>
        <p style="font-size: 1.2em; color: #7f8c8d;">
            You're logged in as <strong><?= htmlspecialchars($user['role']) ?></strong>
        </p>
    </div>

    <div className="stats-grid"
        style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 40px;">
        <div className="stat-card"
            style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 15px; text-align: center;">
            <h3 style="margin: 0; font-size: 2em;"><?= number_format($stats['visitors']) ?></h3>
            <p style="margin: 5px 0 0 0;">Daily Visitors</p>
        </div>
        <div className="stat-card"
            style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; padding: 20px; border-radius: 15px; text-align: center;">
            <h3 style="margin: 0; font-size: 2em;"><?= $stats['pages'] ?></h3>
            <p style="margin: 5px 0 0 0;">Total Pages</p>
        </div>
        <div className="stat-card"
            style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; padding: 20px; border-radius: 15px; text-align: center;">
            <h3 style="margin: 0; font-size: 2em;"><?= $stats['uptime'] ?></h3>
            <p style="margin: 5px 0 0 0;">Uptime</p>
        </div>
    </div>

    <div className="features-section" style="background: #f8f9fa; padding: 30px; border-radius: 15px;">
        <h2 style="text-align: center; margin-bottom: 30px; color: #2c3e50;">🚀 Framework Features</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
            <div style="text-align: center;">
                <div style="font-size: 3em; margin-bottom: 10px;">⚡</div>
                <h3>Lightning Fast</h3>
                <p>Built for performance with minimal overhead</p>
            </div>
            <div style="text-align: center;">
                <div style="font-size: 3em; margin-bottom: 10px;">🎯</div>
                <h3>Next.js-Style</h3>
                <p>Familiar patterns for React developers</p>
            </div>
            <div style="text-align: center;">
                <div style="font-size: 3em; margin-bottom: 10px;">🛠️</div>
                <h3>Developer Friendly</h3>
                <p>Easy to use and extend</p>
            </div>
        </div>
    </div>
</div>

<style>
    .home-page {
        animation: fadeIn 0.5s ease-in;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .stat-card {
        transition: transform 0.3s ease;
    }

    .stat-card:hover {
        transform: translateY(-5px);
    }
</style>