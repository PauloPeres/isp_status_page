<?php
declare(strict_types=1);

use Migrations\AbstractSeed;

class BlogPostsSeed extends AbstractSeed
{
    public function run(): void
    {
        $data = [
            [
                'title' => 'Why We Built KeepUp',
                'slug' => 'why-we-built-keepup',
                'excerpt' => 'The story behind KeepUp: why a team of ISP engineers decided to build a better uptime monitoring platform, and what makes it different from UptimeRobot, Pingdom, and the rest.',
                'content' => $this->getWhyWeBuiltKeepUpContent(),
                'meta_description' => 'Learn why we built KeepUp, an uptime monitoring platform born from real ISP operations in Brazil. Our story of building a better alternative to UptimeRobot and Pingdom.',
                'meta_keywords' => 'uptime monitoring, status page, keepup, uptimerobot alternative, pingdom alternative, isp monitoring, brazil',
                'og_image' => null,
                'author_name' => 'KeepUp Team',
                'tags' => 'company, founder story, monitoring, ISP',
                'status' => 'published',
                'published_at' => '2026-04-01 10:00:00',
                'created' => date('Y-m-d H:i:s'),
                'modified' => date('Y-m-d H:i:s'),
            ],
        ];

        $table = $this->table('blog_posts');
        $table->insert($data)->save();
    }

    private function getWhyWeBuiltKeepUpContent(): string
    {
        return <<<'HTML'
<p>It was 3:17 AM on a Tuesday when the alert came in. A core router at one of our client ISPs had gone down, taking 2,000 subscribers offline in a mid-sized city in southern Brazil. The NOC team scrambled. But the real problem was not the router failure itself &mdash; it was that the team found out 14 minutes after customers started calling, not from their monitoring tools.</p>

<p>That night was a turning point for us. We had been running ISP operations for years, and we knew the monitoring tools on the market were not built for the way we worked. That frustration eventually became KeepUp.</p>

<h2>The Problem with Existing Tools</h2>

<p>We tried everything. UptimeRobot was a good start &mdash; affordable, simple, does the basics. But when you are managing infrastructure for an Internet Service Provider, the basics are not enough. We needed integration with IXC (the billing and provisioning system used by most Brazilian ISPs). We needed Zabbix data pulling into the same dashboard. We needed status pages that our non-technical support teams could understand and that our end customers could trust.</p>

<p>Pingdom was powerful but expensive. For a Brazilian ISP operating on tight margins with BRL-denominated revenue and USD-denominated SaaS costs, the math simply did not work. StatusPage.io from Atlassian solved the status page problem beautifully, but it was <em>only</em> a status page &mdash; you still needed a separate tool for monitoring, and another for alerting, and somehow you had to glue them all together.</p>

<p>We kept coming back to the same conclusion: <strong>no single tool combined monitoring, alerting, and status pages in a way that actually worked for our use case.</strong></p>

<h2>Born from ISP Operations</h2>

<p>KeepUp was born inside IuriLabs, a small technology company in Brazil that had been building software for ISPs since 2020. We understood the Brazilian telecom market intimately. We knew that ISPs in Brazil need:</p>

<ul>
    <li><strong>IXC Integration</strong> &mdash; to monitor services and equipment directly from the provisioning system, without manual configuration for every subscriber CPE.</li>
    <li><strong>Zabbix Integration</strong> &mdash; because most ISPs already run Zabbix for network monitoring, and duplicating that data in a separate tool is wasteful.</li>
    <li><strong>Portuguese Language Support</strong> &mdash; not just translated strings, but genuinely localized UI, notifications, and status pages that make sense in PT-BR.</li>
    <li><strong>LGPD Compliance</strong> &mdash; Brazil&rsquo;s data protection law is real, and monitoring tools that store customer data need to respect it.</li>
    <li><strong>Affordable Pricing in BRL</strong> &mdash; no more converting USD prices and wincing at the exchange rate.</li>
</ul>

<p>These were not nice-to-haves. These were requirements. And no tool on the market checked all the boxes.</p>

<h2>What Makes KeepUp Different</h2>

<p>We set out to build the tool we wished existed. Here is what we focused on:</p>

<h3>Monitoring + Status Pages in One Platform</h3>

<p>With KeepUp, you do not need three separate subscriptions. Your monitors feed directly into your status pages. When a service goes down, the status page updates automatically. When it comes back up, so does the status page. No Zapier glue. No webhook middleware. It just works.</p>

<h3>Nine Alert Channels, Not Just Email</h3>

<p>Email is fine for some alerts. But at 3 AM, you need something that will actually wake you up. KeepUp supports Email, Slack, Discord, Telegram, PagerDuty, OpsGenie, Webhooks, SMS, and Microsoft Teams. You can set up notification policies with escalation chains and cooldown periods, so you are not drowning in duplicate alerts during a major incident.</p>

<h3>Built for Multi-Tenancy</h3>

<p>We built KeepUp as a SaaS platform from the ground up, with proper multi-tenancy, organization isolation, and role-based access control. Whether you are a solo developer monitoring your side project or an MSP managing infrastructure for dozens of clients, the platform scales with you.</p>

<h3>ISP-Native Features</h3>

<p>For ISPs, KeepUp offers what no other monitoring SaaS does: native IXC and Zabbix integrations. Pull PPPoE session data, monitor ONU equipment status, track bandwidth utilization &mdash; all from the same dashboard where you monitor your web applications and APIs. This is not a generic webhook integration. These are purpose-built adapters that speak the same protocols your network equipment does.</p>

<h3>Beautiful, Branded Status Pages</h3>

<p>Your customers deserve better than a plain white page with green checkmarks. KeepUp status pages support custom domains, custom branding, multiple languages, subscriber notifications, password protection, and maintenance windows. They are designed to build trust during incidents, not just display data.</p>

<h2>The Brazilian Market Opportunity</h2>

<p>Brazil has over 20,000 registered ISPs. Most are small to medium-sized operations serving anywhere from 500 to 50,000 subscribers. These companies are the backbone of internet access in Brazil &mdash; especially outside the major metropolitan areas where the big telcos operate.</p>

<p>These ISPs are increasingly sophisticated. They run FTTH networks, manage complex routing topologies, and serve customers who expect the same uptime as any major cloud provider. But their tooling has not kept up. Many still rely on a patchwork of Zabbix, Grafana, WhatsApp groups, and manual processes to manage incidents.</p>

<p>KeepUp was designed specifically for this market. We understand the workflows, the integrations, the language, and the economics. We are not a Silicon Valley SaaS trying to add ISP features as an afterthought. We are ISP people who built a monitoring platform.</p>

<h2>What is Next</h2>

<p>We launched KeepUp in April 2026, and we are just getting started. Our roadmap includes:</p>

<ul>
    <li><strong>Advanced Analytics</strong> &mdash; SLA reporting, uptime trends, response time percentiles.</li>
    <li><strong>Incident Postmortems</strong> &mdash; collaborative incident timelines with root cause analysis templates.</li>
    <li><strong>Mobile App</strong> &mdash; native iOS and Android apps for on-call engineers.</li>
    <li><strong>More Integrations</strong> &mdash; Datadog, New Relic, AWS CloudWatch, and more.</li>
    <li><strong>AI-Powered Insights</strong> &mdash; pattern detection, anomaly alerts, and predictive downtime warnings.</li>
</ul>

<p>But more than features, we are building a community. We want to hear from every SRE, DevOps engineer, NOC operator, and ISP technician who has felt the pain of inadequate monitoring tools. Your feedback shapes our roadmap.</p>

<h2>Try KeepUp</h2>

<p>If any of this resonates with you, <a href="/app/register">give KeepUp a try</a>. The free plan includes 5 monitors, email alerts, and a status page &mdash; enough to see if the platform fits your workflow. No credit card required. No 14-day trial that expires before you have time to evaluate it properly.</p>

<p>We built KeepUp because we needed it. We think you might need it too.</p>

<p><em>&mdash; The KeepUp Team at IuriLabs</em></p>
HTML;
    }
}
