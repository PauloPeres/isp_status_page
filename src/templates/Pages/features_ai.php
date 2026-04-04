<?php
/**
 * KeepUp AI Features page
 *
 * @var \App\View\AppView $this
 */
$this->extend('/layout/marketing');

$brandName = \Cake\Core\Configure::read('Brand.name', 'KeepUp');

$this->assign('title', 'AI Monitoring Assistant - ' . $brandName);
$this->assign('meta_description', 'Meet your AI monitoring co-pilot. Set up monitors, diagnose incidents, and get smart recommendations — all through natural conversation. AI included on Pro and Business plans.');
$this->assign('og_title', 'AI Monitoring Assistant - ' . $brandName);
$this->assign('og_url', 'https://usekeeup.com/features/ai');
?>

<div class="mktg-hero">
    <p style="display: inline-block; background: var(--color-brand-50); color: var(--color-brand-500); padding: 6px 20px; border-radius: 24px; font-size: 14px; font-weight: 600; margin-bottom: 16px;">AI-Powered</p>
    <h1>Your AI Monitoring Co-Pilot</h1>
    <p>Stop clicking through forms. Just tell <?= h($brandName) ?> what you need in plain English. Our AI assistant configures monitors, diagnoses incidents, and keeps your infrastructure running smoothly.</p>
    <div style="margin-top: 32px;">
        <a href="/app/register" class="btn-cta">Start Free</a>
        <a href="/status" class="btn-cta-outline">View Demo</a>
    </div>
</div>

<!-- Feature 1: Natural Language Setup -->
<div class="mktg-section">
    <div class="mktg-grid-2" style="align-items: center;">
        <div>
            <p style="color: var(--color-brand-500); font-weight: 600; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 8px;">Setup in Seconds</p>
            <h2 class="mktg-h2">Natural Language Setup</h2>
            <p class="mktg-text">Just describe what you need, and <?= h($brandName) ?> configures it. No forms, no documentation, no guesswork. Your AI assistant understands monitoring and sets up exactly what you need.</p>
            <ul style="list-style: none; padding: 0; margin: 0;">
                <li style="padding: 8px 0; color: var(--color-gray-500); padding-left: 28px; position: relative; font-size: 0.95rem;">
                    <span style="position: absolute; left: 0; color: var(--color-success-dark); font-weight: 700;">&#10003;</span>
                    Create HTTP, Ping, Port, and SSL monitors instantly
                </li>
                <li style="padding: 8px 0; color: var(--color-gray-500); padding-left: 28px; position: relative; font-size: 0.95rem;">
                    <span style="position: absolute; left: 0; color: var(--color-success-dark); font-weight: 700;">&#10003;</span>
                    Configure alert channels and escalation policies
                </li>
                <li style="padding: 8px 0; color: var(--color-gray-500); padding-left: 28px; position: relative; font-size: 0.95rem;">
                    <span style="position: absolute; left: 0; color: var(--color-success-dark); font-weight: 700;">&#10003;</span>
                    Set up status pages with custom branding
                </li>
            </ul>
        </div>
        <div class="mktg-card" style="background: var(--color-brand-800); border: none; color: #e2e8f0; font-family: monospace; font-size: 0.9rem; line-height: 1.8;">
            <p style="color: rgba(255,255,255,0.5); margin-bottom: 12px; font-size: 0.8rem;">CONVERSATION</p>
            <p><span style="color: #90CAF5;">You:</span> Monitor api.example.com every 30 seconds, alert me on Slack if it goes down</p>
            <p style="margin-top: 12px;"><span style="color: #A5D6A7;">KeepUp AI:</span> Done! I created an HTTP monitor for api.example.com with 30s intervals. Slack alerts configured to #ops-alerts. I also added SSL certificate expiry monitoring since you are using HTTPS. Want me to add it to your status page?</p>
            <p style="margin-top: 12px;"><span style="color: #90CAF5;">You:</span> Yes, add it under the API Services group</p>
            <p style="margin-top: 12px;"><span style="color: #A5D6A7;">KeepUp AI:</span> Added to your status page under "API Services". Your customers will see real-time status at status.example.com.</p>
        </div>
    </div>
</div>

<!-- Feature 2: Incident Diagnosis -->
<div class="mktg-section mktg-section-alt" style="padding: 80px 24px;">
    <div style="max-width: 1200px; margin: 0 auto;">
        <div class="mktg-grid-2" style="align-items: center;">
            <div class="mktg-card" style="background: var(--color-brand-800); border: none; color: #e2e8f0; font-family: monospace; font-size: 0.9rem; line-height: 1.8;">
                <p style="color: rgba(255,255,255,0.5); margin-bottom: 12px; font-size: 0.8rem;">CONVERSATION</p>
                <p><span style="color: #90CAF5;">You:</span> Why did I get an alert at 3am?</p>
                <p style="margin-top: 12px;"><span style="color: #A5D6A7;">KeepUp AI:</span> At 3:04 AM, your API server (api.example.com) returned HTTP 503 for 4 minutes. Root cause: your SSL certificate expired at 3:00 AM. The server rejected connections. I see you have auto-renewal configured in your DNS &mdash; it kicked in and the cert was renewed by 3:08 AM. Service recovered automatically.</p>
                <p style="margin-top: 12px;"><span style="color: #90CAF5;">You:</span> How can I prevent this?</p>
                <p style="margin-top: 12px;"><span style="color: #A5D6A7;">KeepUp AI:</span> I recommend setting your SSL monitor to alert 14 days before expiry instead of the current 7 days. Want me to update it?</p>
            </div>
            <div>
                <p style="color: var(--color-brand-500); font-weight: 600; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 8px;">Understand Instantly</p>
                <h2 class="mktg-h2">Incident Diagnosis</h2>
                <p class="mktg-text">Ask "Why did I get an alert at 3am?" and get a clear, context-rich answer. No log diving, no guesswork. The AI correlates data across your monitors, checks, and incidents to give you the full picture.</p>
                <ul style="list-style: none; padding: 0; margin: 0;">
                    <li style="padding: 8px 0; color: var(--color-gray-500); padding-left: 28px; position: relative; font-size: 0.95rem;">
                        <span style="position: absolute; left: 0; color: var(--color-success-dark); font-weight: 700;">&#10003;</span>
                        Correlates multiple data sources automatically
                    </li>
                    <li style="padding: 8px 0; color: var(--color-gray-500); padding-left: 28px; position: relative; font-size: 0.95rem;">
                        <span style="position: absolute; left: 0; color: var(--color-success-dark); font-weight: 700;">&#10003;</span>
                        Identifies root causes, not just symptoms
                    </li>
                    <li style="padding: 8px 0; color: var(--color-gray-500); padding-left: 28px; position: relative; font-size: 0.95rem;">
                        <span style="position: absolute; left: 0; color: var(--color-success-dark); font-weight: 700;">&#10003;</span>
                        Suggests preventive actions
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Feature 3: Smart Recommendations -->
<div class="mktg-section">
    <div style="text-align: center; max-width: 700px; margin: 0 auto 48px;">
        <p style="color: var(--color-brand-500); font-weight: 600; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 8px;">Proactive Intelligence</p>
        <h2 class="mktg-h2">Smart Recommendations</h2>
        <p class="mktg-text">The AI analyzes your monitoring setup and suggests improvements before problems happen. It learns from your infrastructure patterns and keeps getting smarter.</p>
    </div>

    <div class="mktg-grid-3">
        <div class="mktg-card">
            <div style="width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-bottom: 16px; background: var(--color-brand-50); color: var(--color-brand-500); font-size: 1.5rem;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
            </div>
            <h3 class="mktg-h3">Coverage Gaps</h3>
            <p class="mktg-text">"You monitor your API but not your database. Want me to add a Postgres health check?"</p>
        </div>
        <div class="mktg-card">
            <div style="width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-bottom: 16px; background: var(--color-success-light); color: var(--color-success-dark); font-size: 1.5rem;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>
            </div>
            <h3 class="mktg-h3">Performance Trends</h3>
            <p class="mktg-text">"Response times on your checkout page have increased 40% this week. This could indicate a problem."</p>
        </div>
        <div class="mktg-card">
            <div style="width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-bottom: 16px; background: #FFF3E0; color: #E65100; font-size: 1.5rem;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
            </div>
            <h3 class="mktg-h3">Alert Optimization</h3>
            <p class="mktg-text">"Your Slack channel gets 12 alerts/day from flapping. Want me to add a 2-minute confirmation window?"</p>
        </div>
    </div>
</div>

<!-- Feature 4: MCP Integration -->
<div class="mktg-section mktg-section-alt" style="padding: 80px 24px;">
    <div style="max-width: 1200px; margin: 0 auto;">
        <div class="mktg-grid-2" style="align-items: center;">
            <div>
                <p style="color: var(--color-brand-500); font-weight: 600; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 8px;">Power Users</p>
                <h2 class="mktg-h2">MCP Integration</h2>
                <p class="mktg-text">Connect <?= h($brandName) ?> to Claude Desktop, Cursor, or any MCP-compatible AI client. Manage your entire monitoring infrastructure from your AI assistant of choice using the Model Context Protocol.</p>
                <ul style="list-style: none; padding: 0; margin: 0;">
                    <li style="padding: 8px 0; color: var(--color-gray-500); padding-left: 28px; position: relative; font-size: 0.95rem;">
                        <span style="position: absolute; left: 0; color: var(--color-success-dark); font-weight: 700;">&#10003;</span>
                        Works with Claude Desktop, Cursor, and more
                    </li>
                    <li style="padding: 8px 0; color: var(--color-gray-500); padding-left: 28px; position: relative; font-size: 0.95rem;">
                        <span style="position: absolute; left: 0; color: var(--color-success-dark); font-weight: 700;">&#10003;</span>
                        Full API access through natural language
                    </li>
                    <li style="padding: 8px 0; color: var(--color-gray-500); padding-left: 28px; position: relative; font-size: 0.95rem;">
                        <span style="position: absolute; left: 0; color: var(--color-success-dark); font-weight: 700;">&#10003;</span>
                        Automate complex workflows with AI agents
                    </li>
                </ul>
            </div>
            <div class="mktg-card" style="background: var(--color-brand-800); border: none; color: #e2e8f0; font-family: monospace; font-size: 0.85rem; line-height: 1.7;">
                <p style="color: rgba(255,255,255,0.5); margin-bottom: 12px; font-size: 0.8rem;">claude_desktop_config.json</p>
<pre style="background: none; margin: 0; padding: 0; color: #e2e8f0; font-size: 0.85rem; overflow-x: auto;">{
  "mcpServers": {
    "keepup": {
      "url": "https://usekeeup.com/mcp",
      "headers": {
        "Authorization": "Bearer your-api-key"
      }
    }
  }
}</pre>
            </div>
        </div>
    </div>
</div>

<!-- Pricing CTA -->
<div class="mktg-section">
    <div style="text-align: center; max-width: 800px; margin: 0 auto;">
        <h2 class="mktg-h2" style="margin-bottom: 16px;">AI Included on Pro and Business Plans</h2>
        <p class="mktg-text" style="margin-bottom: 8px;">Every Pro and Business plan includes full access to the AI assistant. No per-query pricing, no token limits, no surprises.</p>

        <div class="mktg-grid-3" style="margin-top: 40px;">
            <div class="mktg-card" style="text-align: center;">
                <h3 class="mktg-h3">Free</h3>
                <p style="font-size: 2rem; font-weight: 800; color: var(--color-brand-700); margin: 12px 0;">$0</p>
                <p class="mktg-text" style="font-size: 0.9rem;">5 monitors, email alerts, status page. No AI.</p>
            </div>
            <div class="mktg-card" style="text-align: center; border: 2px solid var(--color-brand-500);">
                <p style="display: inline-block; background: var(--color-brand-500); color: #fff; padding: 2px 12px; border-radius: 12px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; margin-bottom: 8px;">Most Popular</p>
                <h3 class="mktg-h3">Pro</h3>
                <p style="font-size: 2rem; font-weight: 800; color: var(--color-brand-700); margin: 12px 0;">$19<span style="font-size: 1rem; font-weight: 500; color: var(--color-gray-400);">/mo</span></p>
                <p class="mktg-text" style="font-size: 0.9rem;">50 monitors, all channels, AI assistant included.</p>
            </div>
            <div class="mktg-card" style="text-align: center;">
                <h3 class="mktg-h3">Business</h3>
                <p style="font-size: 2rem; font-weight: 800; color: var(--color-brand-700); margin: 12px 0;">$49<span style="font-size: 1rem; font-weight: 500; color: var(--color-gray-400);">/mo</span></p>
                <p class="mktg-text" style="font-size: 0.9rem;">200 monitors, MCP, voice calls, priority support.</p>
            </div>
        </div>
    </div>
</div>

<!-- CTA -->
<div class="mktg-cta-section">
    <h2>Start Free &mdash; AI Included on Pro and Business</h2>
    <p>Set up your first monitor in under 30 seconds. No credit card required.</p>
    <a href="/app/register" class="btn-cta">Start Free</a>
    <a href="/#pricing" class="btn-cta-outline">View All Plans</a>
</div>
