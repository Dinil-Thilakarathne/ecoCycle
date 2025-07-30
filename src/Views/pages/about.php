<!-- About Page Component -->
<div className="about-page">
    <div className="framework-header" style="text-align: center; margin-bottom: 40px;">
        <h1 style="font-size: 3em; margin-bottom: 20px; color: #2c3e50;">
            <?= htmlspecialchars($framework['name']) ?>
        </h1>
        <p style="font-size: 1.3em; color: #7f8c8d; max-width: 600px; margin: 0 auto;">
            <?= htmlspecialchars($framework['description']) ?>
        </p>
        <div style="margin-top: 20px;">
            <span style="background: #3498db; color: white; padding: 5px 15px; border-radius: 20px; font-size: 0.9em;">
                v<?= htmlspecialchars($framework['version']) ?>
            </span>
            <span
                style="background: #2ecc71; color: white; padding: 5px 15px; border-radius: 20px; font-size: 0.9em; margin-left: 10px;">
                by <?= htmlspecialchars($framework['author']) ?>
            </span>
        </div>
    </div>

    <div className="features-grid" style="margin-bottom: 40px;">
        <h2 style="text-align: center; margin-bottom: 30px; color: #2c3e50;">✨ Key Features</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
            <?php foreach ($features as $index => $feature): ?>
                <div className="feature-card"
                    style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); border-left: 4px solid #3498db;">
                    <div style="display: flex; align-items: center;">
                        <span
                            style="background: #3498db; color: white; border-radius: 50%; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; margin-right: 15px; font-weight: bold;">
                            <?= $index + 1 ?>
                        </span>
                        <h3 style="margin: 0; color: #2c3e50;"><?= htmlspecialchars($feature) ?></h3>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div className="getting-started"
        style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 40px; border-radius: 15px; text-align: center;">
        <h2 style="margin-bottom: 20px;">🚀 Getting Started</h2>
        <p style="font-size: 1.1em; margin-bottom: 30px; opacity: 0.9;">
            Ready to build amazing applications? Check out the documentation!
        </p>
        <div style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">
            <a href="/docs"
                style="background: rgba(255,255,255,0.2); color: white; padding: 12px 24px; text-decoration: none; border-radius: 25px; transition: all 0.3s ease;">
                📚 Documentation
            </a>
            <a href="/examples"
                style="background: rgba(255,255,255,0.2); color: white; padding: 12px 24px; text-decoration: none; border-radius: 25px; transition: all 0.3s ease;">
                💡 Examples
            </a>
            <a href="https://github.com"
                style="background: rgba(255,255,255,0.2); color: white; padding: 12px 24px; text-decoration: none; border-radius: 25px; transition: all 0.3s ease;">
                ⭐ GitHub
            </a>
        </div>
    </div>
</div>

<style>
    .about-page {
        animation: slideInUp 0.6s ease-out;
    }

    @keyframes slideInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .feature-card {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .feature-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 15px rgba(0, 0, 0, 0.15);
    }

    .getting-started a:hover {
        background: rgba(255, 255, 255, 0.3);
        transform: translateY(-2px);
    }
</style>